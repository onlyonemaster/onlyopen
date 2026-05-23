<?php
/**
 * [ME-C2] 원챗 라이프 아바타 — 의사결정 추적 API
 *
 * 엔드포인트:
 *   GET    /aimessage/onechat/api/me_decisions.php
 *          ?id=N                    → 단건 상세
 *          ?state=pending|decided|reflected|all  (기본 all)
 *          ?q=검색어                 → title/context_text LIKE
 *          ?limit=20&offset=0
 *
 *   POST   /aimessage/onechat/api/me_decisions.php
 *          body(JSON): { title, context_text?, options[], ai_prediction?, ai_recommended? }
 *          → 새 결정 등록 (predicted_at=NOW())
 *
 *   POST   ?_method=PUT &id=N
 *          body(JSON): {
 *            chosen_option?,                  // DECIDE 단계
 *            outcome_text?, match_score?,     // REFLECT 단계
 *            match_label?,                    // hit|partial|miss|pending
 *            remind_at?                       // ISO datetime
 *          }
 *          → 부분 업데이트 (전달된 필드만)
 *
 *   POST   ?_method=DELETE &id=N
 *          → soft delete (is_deleted=1)
 *
 * 라이프사이클 상태(state):
 *   - pending   : chosen_option IS NULL AND outcome_text IS NULL
 *   - decided   : chosen_option IS NOT NULL AND outcome_text IS NULL
 *   - reflected : outcome_text IS NOT NULL
 *
 * 매칭률 계산:
 *   match_rate = AVG(match_score) WHERE match_label IN ('hit','partial','miss')
 *   → Gn_onechat_me_avatar.match_rate 와 decision_count 자동 동기화
 *
 * 보안/일관성:
 *   - onechat_auth() 세션 인증 필수
 *   - panic_lock 시 POST/PUT/DELETE → HTTP 423 (B-5와 일관)
 *   - mem_id VARCHAR(30) COLLATE utf8mb4_0900_ai_ci
 *   - Prepared statements
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23 (C-2)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();

$db = getDatabaseConnection();
if (!$db) { onechat_json(['error' => 'DB 연결 실패'], 500); }
@$db->set_charset('utf8mb4');

// ────────────────────────────────────────────────────────────
// 메서드 결정 (POST + ?_method=PUT|DELETE 지원)
// ────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && !empty($_GET['_method'])) {
    $m = strtoupper($_GET['_method']);
    if ($m === 'PUT' || $m === 'DELETE') $method = $m;
}

// ────────────────────────────────────────────────────────────
// 공통 유틸
// ────────────────────────────────────────────────────────────
function med_esc($db, $v) { return $db->real_escape_string((string)$v); }

function med_is_locked($db, $login_id) {
    $le = med_esc($db, $login_id);
    $r = $db->query("SELECT status FROM Gn_onechat_me_avatar
                     WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' LIMIT 1");
    if (!$r || $r->num_rows === 0) return false;
    $row = $r->fetch_assoc();
    return ($row['status'] === 'locked');
}

function med_ensure_avatar($db, $login_id) {
    $le = med_esc($db, $login_id);
    $r = $db->query("SELECT idx FROM Gn_onechat_me_avatar
                     WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' LIMIT 1");
    if ($r && $r->num_rows > 0) return;
    $db->query("INSERT IGNORE INTO Gn_onechat_me_avatar (mem_id, status, created_at, updated_at)
                VALUES ('{$le}', 'active', NOW(), NOW())");
}

/**
 * Gn_onechat_me_avatar의 decision_count + match_rate 동기화
 */
function med_recalc_avatar($db, $login_id) {
    $le = med_esc($db, $login_id);
    med_ensure_avatar($db, $login_id);

    // 활성 결정 수
    $cnt_q = $db->query("
        SELECT COUNT(*) AS c
        FROM Gn_onechat_me_decisions
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' AND is_deleted = 0
    ");
    $cnt = $cnt_q ? (int)$cnt_q->fetch_assoc()['c'] : 0;

    // 매칭률 평균 (pending 제외)
    $rate_q = $db->query("
        SELECT AVG(match_score) AS r
        FROM Gn_onechat_me_decisions
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
          AND is_deleted = 0
          AND match_label IN ('hit','partial','miss')
          AND match_score IS NOT NULL
    ");
    $rate_row = $rate_q ? $rate_q->fetch_assoc() : null;
    $rate = ($rate_row && $rate_row['r'] !== null) ? round((float)$rate_row['r'], 2) : null;

    if ($rate === null) {
        $db->query("UPDATE Gn_onechat_me_avatar
                    SET decision_count = {$cnt}, match_rate = NULL, updated_at = NOW()
                    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'");
    } else {
        $db->query("UPDATE Gn_onechat_me_avatar
                    SET decision_count = {$cnt}, match_rate = {$rate}, updated_at = NOW()
                    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'");
    }
    return ['decision_count' => $cnt, 'match_rate' => $rate];
}

function med_derive_state($row) {
    if (!empty($row['outcome_text']) || !empty($row['outcome_at'])) return 'reflected';
    if (!empty($row['chosen_option'])) return 'decided';
    return 'pending';
}

function med_row_to_public($row) {
    $opts = null;
    if (!empty($row['options_json'])) {
        $opts = json_decode($row['options_json'], true);
        if (!is_array($opts)) $opts = null;
    }
    return [
        'idx'            => (int)$row['idx'],
        'title'          => $row['title'],
        'context_text'   => $row['context_text'],
        'options'        => $opts,
        'chosen_option'  => $row['chosen_option'],
        'ai_prediction'  => $row['ai_prediction'],
        'ai_recommended' => $row['ai_recommended'],
        'predicted_at'   => $row['predicted_at'],
        'outcome_text'   => $row['outcome_text'],
        'outcome_at'     => $row['outcome_at'],
        'match_score'    => isset($row['match_score']) ? (is_null($row['match_score']) ? null : (float)$row['match_score']) : null,
        'match_label'    => $row['match_label'] ?? 'pending',
        'remind_at'      => $row['remind_at'],
        'remind_done'    => (int)($row['remind_done'] ?? 0),
        'created_at'     => $row['created_at'],
        'updated_at'     => $row['updated_at'],
        'state'          => med_derive_state($row),
    ];
}

function med_read_body() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

// ────────────────────────────────────────────────────────────
// GET
// ────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $le = med_esc($db, $login_id);

    // 단건
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM Gn_onechat_me_decisions
                WHERE idx = {$id}
                  AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                  AND is_deleted = 0
                LIMIT 1";
        $r = $db->query($sql);
        if (!$r || $r->num_rows === 0) {
            onechat_json(['error' => 'NOT_FOUND'], 404);
        }
        onechat_json(['ok' => true, 'item' => med_row_to_public($r->fetch_assoc())], 200);
    }

    // 목록
    $state  = isset($_GET['state']) ? strtolower(trim($_GET['state'])) : 'all';
    if (!in_array($state, ['all','pending','decided','reflected'], true)) $state = 'all';

    $q      = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $where = ["mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'", "is_deleted = 0"];

    if ($state === 'pending')   $where[] = "(chosen_option IS NULL OR chosen_option = '')"
                                          . " AND (outcome_text IS NULL OR outcome_text = '')";
    if ($state === 'decided')   $where[] = "(chosen_option IS NOT NULL AND chosen_option <> '')"
                                          . " AND (outcome_text IS NULL OR outcome_text = '')";
    if ($state === 'reflected') $where[] = "(outcome_text IS NOT NULL AND outcome_text <> '')";

    if ($q !== '') {
        $qe = med_esc($db, $q);
        $where[] = "(title LIKE '%{$qe}%' OR context_text LIKE '%{$qe}%')";
    }

    $w = implode(' AND ', $where);
    $sql = "SELECT * FROM Gn_onechat_me_decisions
            WHERE {$w}
            ORDER BY idx DESC
            LIMIT {$limit} OFFSET {$offset}";

    $r = $db->query($sql);
    $items = [];
    if ($r) {
        while ($row = $r->fetch_assoc()) $items[] = med_row_to_public($row);
    }

    $count_sql = "SELECT COUNT(*) AS c FROM Gn_onechat_me_decisions WHERE {$w}";
    $cr = $db->query($count_sql);
    $total = $cr ? (int)$cr->fetch_assoc()['c'] : count($items);

    // 집계: 상태별/매칭별
    $sum_q = $db->query("
        SELECT
          SUM(CASE WHEN (chosen_option IS NULL OR chosen_option = '')
                   AND (outcome_text IS NULL OR outcome_text = '') THEN 1 ELSE 0 END) AS pending_cnt,
          SUM(CASE WHEN (chosen_option IS NOT NULL AND chosen_option <> '')
                   AND (outcome_text IS NULL OR outcome_text = '') THEN 1 ELSE 0 END) AS decided_cnt,
          SUM(CASE WHEN outcome_text IS NOT NULL AND outcome_text <> '' THEN 1 ELSE 0 END) AS reflected_cnt,
          SUM(CASE WHEN match_label='hit'     THEN 1 ELSE 0 END) AS hit_cnt,
          SUM(CASE WHEN match_label='partial' THEN 1 ELSE 0 END) AS partial_cnt,
          SUM(CASE WHEN match_label='miss'    THEN 1 ELSE 0 END) AS miss_cnt,
          ROUND(AVG(CASE WHEN match_label IN ('hit','partial','miss')
                         AND match_score IS NOT NULL THEN match_score END), 2) AS match_rate
        FROM Gn_onechat_me_decisions
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
          AND is_deleted = 0
    ");
    $raw = $sum_q ? $sum_q->fetch_assoc() : [];
    // 전체(필터 무시) 총 개수
    $total_all_q = $db->query("SELECT COUNT(*) AS c FROM Gn_onechat_me_decisions
                               WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                                 AND is_deleted = 0");
    $total_all = $total_all_q ? (int)$total_all_q->fetch_assoc()['c'] : 0;

    // me_stats.php와 동일한 단축형 키 스키마 + _cnt 하위호환
    $sum = [
        'total'         => $total_all,
        'pending'       => (int)($raw['pending_cnt']   ?? 0),
        'decided'       => (int)($raw['decided_cnt']   ?? 0),
        'reflected'     => (int)($raw['reflected_cnt'] ?? 0),
        'hit'           => (int)($raw['hit_cnt']       ?? 0),
        'partial'       => (int)($raw['partial_cnt']   ?? 0),
        'miss'          => (int)($raw['miss_cnt']      ?? 0),
        // 하위 호환 (_cnt 접미 키)
        'pending_cnt'   => (int)($raw['pending_cnt']   ?? 0),
        'decided_cnt'   => (int)($raw['decided_cnt']   ?? 0),
        'reflected_cnt' => (int)($raw['reflected_cnt'] ?? 0),
        'hit_cnt'       => (int)($raw['hit_cnt']       ?? 0),
        'partial_cnt'   => (int)($raw['partial_cnt']   ?? 0),
        'miss_cnt'      => (int)($raw['miss_cnt']      ?? 0),
        'match_rate'    => isset($raw['match_rate']) && $raw['match_rate'] !== null
                            ? (float)$raw['match_rate'] : null,
    ];

    onechat_json([
        'ok'      => true,
        'items'   => $items,
        'total'   => $total,
        'limit'   => $limit,
        'offset'  => $offset,
        'state'   => $state,
        'q'       => $q,
        'summary' => $sum,
        'locked'  => med_is_locked($db, $login_id),
    ], 200);
}

// ────────────────────────────────────────────────────────────
// POST (신규 등록) — Panic Lock 시 423
// ────────────────────────────────────────────────────────────
if ($method === 'POST') {
    if (med_is_locked($db, $login_id)) {
        onechat_json(['error' => ['code' => 'LOCKED', 'message' => '잠금 상태에서는 결정을 추가할 수 없습니다.']], 423);
    }
    $body = med_read_body();

    $title = isset($body['title']) ? trim((string)$body['title']) : '';
    if ($title === '') {
        onechat_json(['error' => ['code' => 'INVALID_TITLE', 'message' => '제목은 필수입니다.']], 400);
    }
    if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);

    $context_text   = isset($body['context_text'])   ? (string)$body['context_text']   : null;
    $ai_prediction  = isset($body['ai_prediction'])  ? (string)$body['ai_prediction']  : null;
    $ai_recommended = isset($body['ai_recommended']) ? (string)$body['ai_recommended'] : null;
    $chosen_option  = isset($body['chosen_option'])  ? (string)$body['chosen_option']  : null;
    $remind_at      = isset($body['remind_at'])      ? (string)$body['remind_at']      : null;

    // options 배열 → JSON
    $options_json = null;
    if (isset($body['options']) && is_array($body['options'])) {
        $clean = [];
        foreach ($body['options'] as $o) {
            if (is_string($o)) {
                $s = trim($o);
                if ($s !== '') $clean[] = $s;
            } else if (is_array($o)) {
                // {label:'A', desc:'...'} 형태도 허용
                $lbl = isset($o['label']) ? trim((string)$o['label']) : '';
                if ($lbl !== '') $clean[] = ['label' => $lbl, 'desc' => isset($o['desc']) ? (string)$o['desc'] : ''];
            }
        }
        if (!empty($clean)) $options_json = json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    if ($ai_recommended !== null && mb_strlen($ai_recommended) > 100) {
        $ai_recommended = mb_substr($ai_recommended, 0, 100);
    }
    if ($chosen_option !== null && mb_strlen($chosen_option) > 100) {
        $chosen_option = mb_substr($chosen_option, 0, 100);
    }

    // remind_at 검증
    $remind_at_sql = 'NULL';
    if ($remind_at) {
        $ts = strtotime($remind_at);
        if ($ts !== false) {
            $remind_at_sql = "'" . date('Y-m-d H:i:s', $ts) . "'";
        }
    }

    med_ensure_avatar($db, $login_id);

    $stmt = $db->prepare("
        INSERT INTO Gn_onechat_me_decisions
            (mem_id, title, context_text, options_json,
             chosen_option, ai_prediction, ai_recommended, predicted_at,
             remind_at, match_label, is_deleted, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), " . $remind_at_sql . ", 'pending', 0, NOW(), NOW())
    ");
    if (!$stmt) {
        onechat_json(['error' => 'DB 준비 실패: ' . $db->error], 500);
    }
    $stmt->bind_param('sssssss',
        $login_id, $title, $context_text, $options_json,
        $chosen_option, $ai_prediction, $ai_recommended
    );
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        onechat_json(['error' => 'DB 저장 실패: ' . $err], 500);
    }
    $new_id = $stmt->insert_id;
    $stmt->close();

    $sync = med_recalc_avatar($db, $login_id);

    // 신규 항목 다시 조회 (정확한 created/updated 포함)
    $le = med_esc($db, $login_id);
    $r = $db->query("SELECT * FROM Gn_onechat_me_decisions
                     WHERE idx = {$new_id}
                       AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                     LIMIT 1");
    $item = ($r && $r->num_rows > 0) ? med_row_to_public($r->fetch_assoc()) : ['idx' => $new_id];

    onechat_json([
        'ok'   => true,
        'item' => $item,
        'sync' => $sync,
    ], 201);
}

// ────────────────────────────────────────────────────────────
// PUT (단계 업데이트) — Panic Lock 시 423
// ────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (med_is_locked($db, $login_id)) {
        onechat_json(['error' => ['code' => 'LOCKED', 'message' => '잠금 상태에서는 결정을 수정할 수 없습니다.']], 423);
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        onechat_json(['error' => ['code' => 'INVALID_ID', 'message' => 'id가 필요합니다.']], 400);
    }
    $body = med_read_body();

    // 소유권 확인
    $le = med_esc($db, $login_id);
    $r = $db->query("SELECT * FROM Gn_onechat_me_decisions
                     WHERE idx = {$id}
                       AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                       AND is_deleted = 0
                     LIMIT 1");
    if (!$r || $r->num_rows === 0) {
        onechat_json(['error' => ['code' => 'NOT_FOUND', 'message' => '대상이 없습니다.']], 404);
    }
    $cur = $r->fetch_assoc();

    $updates = [];
    $params  = [];
    $types   = '';

    // 필드별 부분 업데이트
    if (array_key_exists('title', $body)) {
        $t = trim((string)$body['title']);
        if ($t !== '') {
            $updates[] = 'title = ?';
            $params[]  = mb_substr($t, 0, 200);
            $types    .= 's';
        }
    }
    if (array_key_exists('context_text', $body)) {
        $updates[] = 'context_text = ?';
        $params[]  = (string)$body['context_text'];
        $types    .= 's';
    }
    if (array_key_exists('ai_prediction', $body)) {
        $updates[] = 'ai_prediction = ?';
        $params[]  = (string)$body['ai_prediction'];
        $types    .= 's';
    }
    if (array_key_exists('ai_recommended', $body)) {
        $v = (string)$body['ai_recommended'];
        $updates[] = 'ai_recommended = ?';
        $params[]  = mb_substr($v, 0, 100);
        $types    .= 's';
    }
    if (array_key_exists('chosen_option', $body)) {
        $v = (string)$body['chosen_option'];
        $updates[] = 'chosen_option = ?';
        $params[]  = mb_substr($v, 0, 100);
        $types    .= 's';
    }
    if (array_key_exists('options', $body) && is_array($body['options'])) {
        $clean = [];
        foreach ($body['options'] as $o) {
            if (is_string($o)) { $s = trim($o); if ($s !== '') $clean[] = $s; }
            else if (is_array($o)) {
                $lbl = isset($o['label']) ? trim((string)$o['label']) : '';
                if ($lbl !== '') $clean[] = ['label' => $lbl, 'desc' => isset($o['desc']) ? (string)$o['desc'] : ''];
            }
        }
        $updates[] = 'options_json = ?';
        $params[]  = empty($clean) ? null : json_encode($clean, JSON_UNESCAPED_UNICODE);
        $types    .= 's';
    }

    // REFLECT 단계
    $will_reflect = false;
    if (array_key_exists('outcome_text', $body)) {
        $v = (string)$body['outcome_text'];
        $updates[] = 'outcome_text = ?';
        $params[]  = $v;
        $types    .= 's';
        if ($v !== '' && empty($cur['outcome_at'])) {
            $updates[] = 'outcome_at = NOW()';
            $will_reflect = true;
        }
    }
    if (array_key_exists('match_score', $body)) {
        $v = $body['match_score'];
        if ($v === null || $v === '') {
            $updates[] = 'match_score = NULL';
        } else {
            $f = (float)$v;
            if ($f < 0) $f = 0;
            if ($f > 100) $f = 100;
            $updates[] = 'match_score = ?';
            $params[]  = $f;
            $types    .= 'd';
        }
    }
    if (array_key_exists('match_label', $body)) {
        $v = strtolower(trim((string)$body['match_label']));
        if (!in_array($v, ['hit','partial','miss','pending'], true)) {
            onechat_json(['error' => ['code' => 'INVALID_LABEL', 'message' => 'match_label 값이 잘못되었습니다.']], 400);
        }
        $updates[] = 'match_label = ?';
        $params[]  = $v;
        $types    .= 's';
    }
    if (array_key_exists('remind_at', $body)) {
        $v = $body['remind_at'];
        if ($v === null || $v === '') {
            $updates[] = 'remind_at = NULL';
            $updates[] = 'remind_done = 0';
        } else {
            $ts = strtotime((string)$v);
            if ($ts !== false) {
                $updates[] = "remind_at = '" . $db->real_escape_string(date('Y-m-d H:i:s', $ts)) . "'";
                $updates[] = 'remind_done = 0';
            }
        }
    }
    if (array_key_exists('remind_done', $body)) {
        $updates[] = 'remind_done = ?';
        $params[]  = (int)((bool)$body['remind_done']);
        $types    .= 'i';
    }

    if (empty($updates)) {
        onechat_json(['error' => ['code' => 'NO_FIELDS', 'message' => '변경할 필드가 없습니다.']], 400);
    }
    $updates[] = 'updated_at = NOW()';

    // 동적 prepared statement
    $sql = "UPDATE Gn_onechat_me_decisions
            SET " . implode(', ', $updates) . "
            WHERE idx = {$id}
              AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
              AND is_deleted = 0";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        onechat_json(['error' => 'DB 준비 실패: ' . $db->error . ' / SQL: ' . $sql], 500);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        onechat_json(['error' => 'DB 저장 실패: ' . $err], 500);
    }
    $stmt->close();

    $sync = med_recalc_avatar($db, $login_id);

    // 갱신된 항목 재조회
    $r = $db->query("SELECT * FROM Gn_onechat_me_decisions
                     WHERE idx = {$id}
                       AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                     LIMIT 1");
    $item = ($r && $r->num_rows > 0) ? med_row_to_public($r->fetch_assoc()) : null;

    onechat_json([
        'ok'        => true,
        'item'      => $item,
        'reflected' => $will_reflect,
        'sync'      => $sync,
    ], 200);
}

// ────────────────────────────────────────────────────────────
// DELETE (soft) — Panic Lock 시 423
// ────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (med_is_locked($db, $login_id)) {
        onechat_json(['error' => ['code' => 'LOCKED', 'message' => '잠금 상태에서는 결정을 삭제할 수 없습니다.']], 423);
    }
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        onechat_json(['error' => ['code' => 'INVALID_ID', 'message' => 'id가 필요합니다.']], 400);
    }
    $le = med_esc($db, $login_id);
    $r = $db->query("UPDATE Gn_onechat_me_decisions
                     SET is_deleted = 1, updated_at = NOW()
                     WHERE idx = {$id}
                       AND mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
                       AND is_deleted = 0");
    if (!$r) {
        onechat_json(['error' => 'DB 삭제 실패: ' . $db->error], 500);
    }
    $aff = $db->affected_rows;
    if ($aff < 1) {
        onechat_json(['error' => ['code' => 'NOT_FOUND', 'message' => '대상이 없습니다.']], 404);
    }
    $sync = med_recalc_avatar($db, $login_id);
    onechat_json(['ok' => true, 'deleted_id' => $id, 'sync' => $sync], 200);
}

// fallback
onechat_json(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => '허용되지 않은 메서드입니다.']], 405);
