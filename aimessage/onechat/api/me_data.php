<?php
/**
 * [ME-B5] 원챗 라이프 아바타 — 데이터 풀(me_data_pool) API
 *
 * 엔드포인트:
 *   GET    /aimessage/onechat/api/me_data.php?scope=private|public|both&category=&limit=&offset=&q=
 *   POST   /aimessage/onechat/api/me_data.php           (JSON body로 신규 항목 등록)
 *   DELETE /aimessage/onechat/api/me_data.php?id=NNN    (soft-delete, is_deleted=1)
 *          ※ 일부 호스팅 환경에서 DELETE가 막힐 경우를 대비:
 *             POST + ?_method=DELETE&id=NNN  도 함께 지원 (learn.php 패턴)
 *
 * 인증 / 보안:
 *   - 기존 onechat_auth() 세션 인증 재사용
 *   - 회원 식별자: Gn_Member.mem_id (VARCHAR(30)) → 본 API에서는 $login_id 그대로 사용
 *   - Panic Lock: Gn_onechat_me_avatar.status='locked' 이면
 *       · GET: privacy_level >= 4 항목을 강제 제외
 *       · POST: 신규 입력 차단 (423 LOCKED)
 *       · DELETE: 허용 (자기 데이터 삭제는 항상 가능)
 *
 * 의존 테이블: Gn_onechat_me_data_pool, Gn_onechat_me_avatar
 *   → me_b4_schema.sql 로 사전 생성 필수
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();
$db       = getDatabaseConnection();
if (!$db) { onechat_json(['error' => 'DB 연결 실패'], 500); }

// utf8mb4 강제
@$db->set_charset('utf8mb4');

// ────────────────────────────────────────────────────────────
// 공통 유틸
// ────────────────────────────────────────────────────────────
function me_esc($db, $v) { return $db->real_escape_string((string)$v); }

function me_clamp_int($v, $min, $max, $default) {
    if ($v === null || $v === '') return $default;
    $n = (int)$v;
    if ($n < $min) return $min;
    if ($n > $max) return $max;
    return $n;
}

/** category ENUM 화이트리스트 */
function me_valid_category($c) {
    static $allowed = ['basic','childhood','diary','file','voice','image',
                       'fingerprint','palmistry','physiognomy','saju','astrology',
                       'decision','etc'];
    return in_array($c, $allowed, true);
}

/** scope ENUM 화이트리스트 */
function me_valid_scope($s) {
    return in_array($s, ['private','public','both'], true);
}

/** 로그인 사용자의 아바타 상태(status) 조회 (없으면 row 자동 생성 + 'active') */
function me_get_avatar_status($db, $login_id) {
    $le = me_esc($db, $login_id);
    $sql = "SELECT status FROM Gn_onechat_me_avatar
            WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' LIMIT 1";
    $r = $db->query($sql);
    if ($r && $row = $r->fetch_assoc()) {
        return $row['status'];
    }
    // 최초 1회 자동 생성
    $ins = "INSERT IGNORE INTO Gn_onechat_me_avatar (mem_id, avatar_name, status)
            VALUES ('{$le}', '나의 아바타', 'active')";
    @$db->query($ins);
    return 'active';
}

/** 요청 바디(JSON 또는 form-urlencoded) 파싱 */
function me_read_body() {
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
        $j = json_decode($raw, true);
        if (is_array($j)) return $j;
    }
    return $_POST ?: [];
}

// ────────────────────────────────────────────────────────────
// 메서드 라우팅
// ────────────────────────────────────────────────────────────
$method  = $_SERVER['REQUEST_METHOD'];
$mOver   = strtoupper($_GET['_method'] ?? '');
if ($method === 'POST' && $mOver === 'DELETE') $method = 'DELETE';

$status = me_get_avatar_status($db, $login_id);  // 'active' | 'paused' | 'locked'
$is_locked = ($status === 'locked');

try {
    if ($method === 'GET') {
        handle_get($db, $login_id, $is_locked);
    } elseif ($method === 'POST') {
        if ($is_locked) {
            onechat_json(['error' => ['code' => 'PANIC_LOCKED', 'message' => 'Panic Lock 상태입니다. 입력이 제한됩니다.']], 423);
        }
        handle_post($db, $login_id);
    } elseif ($method === 'DELETE') {
        handle_delete($db, $login_id);
    } else {
        onechat_json(['error' => 'Method Not Allowed'], 405);
    }
} catch (Throwable $e) {
    error_log('[me_data.php] ' . $e->getMessage());
    onechat_json(['error' => '서버 내부 오류: ' . $e->getMessage()], 500);
}

// ════════════════════════════════════════════════════════════
// GET — 목록 조회
//   ?scope=private|public|both|all     (default: all = 본인 전체)
//   ?category=basic|diary|...           (optional)
//   ?q=검색어                            (optional, LIKE 매칭)
//   ?limit=1~200 (default 50)
//   ?offset=0~  (default 0)
//   응답: { ok:true, status, locked, count, items:[ ... ] }
// ════════════════════════════════════════════════════════════
function handle_get($db, $login_id, $is_locked) {
    $le = me_esc($db, $login_id);

    $scope_raw = $_GET['scope'] ?? 'all';
    $where = ["mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'", "is_deleted = 0"];

    if ($scope_raw !== 'all') {
        if (!me_valid_scope($scope_raw)) {
            onechat_json(['error' => 'scope 값이 올바르지 않습니다.'], 400);
        }
        // 'both' 검색 시: 해당 scope만 매칭 (별도 의미: 양쪽 모두 노출되는 항목)
        $where[] = "scope = '" . me_esc($db, $scope_raw) . "'";
    }

    if (!empty($_GET['category'])) {
        $cat = $_GET['category'];
        if (!me_valid_category($cat)) {
            onechat_json(['error' => 'category 값이 올바르지 않습니다.'], 400);
        }
        $where[] = "category = '" . me_esc($db, $cat) . "'";
    }

    if (!empty($_GET['q'])) {
        $q = me_esc($db, $_GET['q']);
        $where[] = "(title LIKE '%{$q}%' OR payload_text LIKE '%{$q}%')";
    }

    // Panic Lock: privacy_level >= 4 자동 제외
    if ($is_locked) {
        $where[] = "privacy_level < 4";
    }

    $limit  = me_clamp_int($_GET['limit']  ?? null, 1, 200, 50);
    $offset = me_clamp_int($_GET['offset'] ?? null, 0, 1000000, 0);

    $sql = "SELECT idx, mem_id, category, scope, title,
                   LEFT(payload_text, 500) AS payload_preview,
                   payload_json,
                   file_url, file_name, file_size,
                   source_msg_id, privacy_level, weight,
                   occurred_at, created_at, updated_at
            FROM Gn_onechat_me_data_pool
            WHERE " . implode(' AND ', $where) . "
            ORDER BY COALESCE(occurred_at, created_at) DESC, idx DESC
            LIMIT {$limit} OFFSET {$offset}";

    $res = $db->query($sql);
    if (!$res) {
        onechat_json(['error' => '조회 실패: ' . $db->error], 500);
    }

    $items = [];
    while ($row = $res->fetch_assoc()) {
        // payload_json 디코딩
        if (!empty($row['payload_json'])) {
            $j = json_decode($row['payload_json'], true);
            if ($j !== null) $row['payload_json'] = $j;
        }
        // 타입 정리
        $row['idx']           = (int)$row['idx'];
        $row['file_size']     = $row['file_size']     !== null ? (int)$row['file_size']     : null;
        $row['source_msg_id'] = $row['source_msg_id'] !== null ? (int)$row['source_msg_id'] : null;
        $row['privacy_level'] = (int)$row['privacy_level'];
        $row['weight']        = (float)$row['weight'];
        $items[] = $row;
    }

    // 전체 count (필터 동일)
    $cntSql = "SELECT COUNT(*) AS c FROM Gn_onechat_me_data_pool WHERE " . implode(' AND ', $where);
    $cRow   = $db->query($cntSql)->fetch_assoc();
    $total  = $cRow ? (int)$cRow['c'] : count($items);

    onechat_json([
        'ok'      => true,
        'status'  => me_get_avatar_status($db, $login_id),
        'locked'  => $is_locked,
        'scope'   => $scope_raw,
        'limit'   => $limit,
        'offset'  => $offset,
        'count'   => count($items),
        'total'   => $total,
        'items'   => $items,
    ]);
}

// ════════════════════════════════════════════════════════════
// POST — 신규 항목 등록
//   body(JSON):
//     {
//       "category":"diary",                  // required (ENUM)
//       "scope":"private",                    // optional (default 'private')
//       "title":"제목",                       // optional (<=200)
//       "payload_text":"본문",                // optional (TEXT)
//       "payload_json":{...},                 // optional (자동 JSON 직렬화)
//       "file_url":"https://...",             // optional
//       "file_name":"foo.pdf",                // optional
//       "file_size":12345,                    // optional
//       "source_msg_id":123,                  // optional (Gn_chat_message.idx)
//       "privacy_level":3,                    // optional (1~5, default 3)
//       "weight":1.00,                        // optional (default 1.00)
//       "occurred_at":"2026-05-23 10:00:00"   // optional
//     }
//   응답: { ok:true, idx, item }
// ════════════════════════════════════════════════════════════
function handle_post($db, $login_id) {
    $b = me_read_body();

    $category = $b['category'] ?? 'etc';
    if (!me_valid_category($category)) {
        onechat_json(['error' => 'category 값이 올바르지 않습니다.'], 400);
    }

    $scope = $b['scope'] ?? 'private';
    if (!me_valid_scope($scope)) {
        onechat_json(['error' => 'scope 값이 올바르지 않습니다.'], 400);
    }

    $title         = isset($b['title'])         ? mb_substr((string)$b['title'], 0, 200) : null;
    $payload_text  = isset($b['payload_text'])  ? (string)$b['payload_text']            : null;
    $payload_json  = isset($b['payload_json'])  ? $b['payload_json']                    : null;
    $file_url      = isset($b['file_url'])      ? (string)$b['file_url']                : null;
    $file_name     = isset($b['file_name'])     ? (string)$b['file_name']               : null;
    $file_size     = isset($b['file_size'])     ? (int)$b['file_size']                  : null;
    $source_msg_id = isset($b['source_msg_id']) ? (int)$b['source_msg_id']              : null;
    $privacy_level = me_clamp_int($b['privacy_level'] ?? 3, 1, 5, 3);
    $weight_raw    = isset($b['weight']) ? (float)$b['weight'] : 1.00;
    if ($weight_raw < 0)   $weight_raw = 0.0;
    if ($weight_raw > 999) $weight_raw = 999.99;
    $occurred_at   = isset($b['occurred_at']) && $b['occurred_at'] !== ''
                     ? (string)$b['occurred_at'] : null;

    // 최소 1개 필드는 있어야 (전부 null이면 의미 없음)
    if (($title === null || $title === '') &&
        ($payload_text === null || $payload_text === '') &&
        ($payload_json === null) &&
        ($file_url === null || $file_url === '')) {
        onechat_json(['error' => 'title / payload_text / payload_json / file_url 중 하나 이상이 필요합니다.'], 400);
    }

    // payload_json은 항상 문자열로 저장
    $payload_json_str = null;
    if ($payload_json !== null) {
        if (is_string($payload_json)) {
            // 이미 JSON 문자열이면 검증
            $decoded = json_decode($payload_json, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                onechat_json(['error' => 'payload_json이 올바른 JSON 문자열이 아닙니다.'], 400);
            }
            $payload_json_str = $payload_json;
        } else {
            $payload_json_str = json_encode($payload_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    // 바인딩 안전성: prepared statement 사용
    $sql = "INSERT INTO Gn_onechat_me_data_pool
            (mem_id, category, scope, title, payload_text, payload_json,
             file_url, file_name, file_size, source_msg_id,
             privacy_level, weight, occurred_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        onechat_json(['error' => 'prepare 실패: ' . $db->error], 500);
    }

    // 타입: s s s s s s s s i i i d s
    $stmt->bind_param(
        'ssssssssiiids',
        $login_id, $category, $scope, $title, $payload_text, $payload_json_str,
        $file_url, $file_name, $file_size, $source_msg_id,
        $privacy_level, $weight_raw, $occurred_at
    );

    if (!$stmt->execute()) {
        onechat_json(['error' => 'INSERT 실패: ' . $stmt->error], 500);
    }
    $new_idx = (int)$stmt->insert_id;
    $stmt->close();

    // 방금 등록된 row 다시 읽어서 반환 (서버 적용 default/timestamps 확인)
    $sel = "SELECT idx, mem_id, category, scope, title,
                   LEFT(payload_text, 500) AS payload_preview,
                   payload_json,
                   file_url, file_name, file_size,
                   source_msg_id, privacy_level, weight,
                   occurred_at, created_at, updated_at
            FROM Gn_onechat_me_data_pool WHERE idx = {$new_idx} LIMIT 1";
    $row = $db->query($sel)->fetch_assoc();
    if ($row && !empty($row['payload_json'])) {
        $j = json_decode($row['payload_json'], true);
        if ($j !== null) $row['payload_json'] = $j;
    }
    if ($row) {
        $row['idx']           = (int)$row['idx'];
        $row['file_size']     = $row['file_size']     !== null ? (int)$row['file_size']     : null;
        $row['source_msg_id'] = $row['source_msg_id'] !== null ? (int)$row['source_msg_id'] : null;
        $row['privacy_level'] = (int)$row['privacy_level'];
        $row['weight']        = (float)$row['weight'];
    }

    // 아바타 카운터 업데이트 (data_pool_count++)
    $le = me_esc($db, $login_id);
    @$db->query("UPDATE Gn_onechat_me_avatar
                 SET data_pool_count = data_pool_count + 1
                 WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'");

    onechat_json([
        'ok'   => true,
        'idx'  => $new_idx,
        'item' => $row,
    ], 201);
}

// ════════════════════════════════════════════════════════════
// DELETE — soft-delete (is_deleted=1)
//   ?id=NNN  (필수)
//   본인 데이터만 가능 (mem_id 일치 확인)
//   응답: { ok:true, idx, deleted:true }
// ════════════════════════════════════════════════════════════
function handle_delete($db, $login_id) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        onechat_json(['error' => 'id 파라미터가 필요합니다.'], 400);
    }
    $le = me_esc($db, $login_id);

    // 존재 + 소유 검증
    $chkSql = "SELECT idx, is_deleted FROM Gn_onechat_me_data_pool
               WHERE idx = {$id}
                 AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
               LIMIT 1";
    $chk = $db->query($chkSql);
    if (!$chk || $chk->num_rows === 0) {
        onechat_json(['error' => '해당 항목을 찾을 수 없습니다.'], 404);
    }
    $cur = $chk->fetch_assoc();
    if ((int)$cur['is_deleted'] === 1) {
        // 이미 삭제 상태여도 성공으로 응답 (idempotent)
        onechat_json([
            'ok'              => true,
            'idx'             => $id,
            'deleted'         => true,
            'already_deleted' => true,
        ]);
    }

    $upd = "UPDATE Gn_onechat_me_data_pool
            SET is_deleted = 1, updated_at = NOW()
            WHERE idx = {$id}
              AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
            LIMIT 1";
    if (!$db->query($upd)) {
        onechat_json(['error' => 'DELETE 실패: ' . $db->error], 500);
    }

    // 카운터 감소 (음수 방지)
    @$db->query("UPDATE Gn_onechat_me_avatar
                 SET data_pool_count = GREATEST(CAST(data_pool_count AS SIGNED) - 1, 0)
                 WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'");

    onechat_json([
        'ok'      => true,
        'idx'     => $id,
        'deleted' => true,
    ]);
}
