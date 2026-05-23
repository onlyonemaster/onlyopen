<?php
/**
 * [ME-C6] 원챗 라이프 아바타 — 아바타 상태(state) API
 *
 * 엔드포인트:
 *   GET  /aimessage/onechat/api/me_avatar.php
 *        → 현재 아바타 상태 + 카운터 조회
 *
 *   POST /aimessage/onechat/api/me_avatar.php
 *        body(JSON): { "action": "lock"|"unlock"|"pause"|"resume" }
 *        → 상태 전환
 *
 * 액션 의미:
 *   - lock    : status='locked'   + panic_locked_at=NOW()
 *               · GET me_data: privacy_level >= 4 자동 제외
 *               · POST me_data: 423 LOCKED
 *   - unlock  : status='active'   + panic_locked_at=NULL
 *   - pause   : status='paused'   (입력은 허용하되 학습 보류 — 향후 RAG 단계에서 사용)
 *   - resume  : status='active'
 *
 * 인증/보안:
 *   - 기존 onechat_auth() 세션 인증
 *   - 본인의 Gn_onechat_me_avatar 행만 영향
 *   - row가 없으면 첫 GET/POST 시 자동 생성 ('active')
 *
 * 의존 테이블: Gn_onechat_me_avatar (B-4에서 생성)
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23 (C-6)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();
$db       = getDatabaseConnection();
if (!$db) { onechat_json(['error' => 'DB 연결 실패'], 500); }

@$db->set_charset('utf8mb4');

// ────────────────────────────────────────────────────────────
// 공통 유틸
// ────────────────────────────────────────────────────────────
function mea_esc($db, $v) { return $db->real_escape_string((string)$v); }

/** 아바타 행 조회 (없으면 자동 생성). 단일 행 배열 또는 null 반환 */
function mea_load_avatar($db, $login_id) {
    $le = mea_esc($db, $login_id);
    $sql = "SELECT idx, mem_id, avatar_name, identity_text,
                   data_pool_count, decision_count, match_rate,
                   status, panic_locked_at, current_mode,
                   created_at, updated_at
            FROM Gn_onechat_me_avatar
            WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
            LIMIT 1";
    $r = $db->query($sql);
    if ($r && $r->num_rows > 0) {
        return $r->fetch_assoc();
    }
    // 자동 생성
    $ins = "INSERT IGNORE INTO Gn_onechat_me_avatar (mem_id, avatar_name, status)
            VALUES ('{$le}', '나의 아바타', 'active')";
    @$db->query($ins);

    $r2 = $db->query($sql);
    return ($r2 && $r2->num_rows > 0) ? $r2->fetch_assoc() : null;
}

/** 실시간 카운터 보정 (me_data_pool 실 row 수 ↔ avatar.data_pool_count 동기화) */
function mea_recalc_counts($db, $login_id) {
    $le = mea_esc($db, $login_id);
    // data_pool은 is_deleted=0 만 카운트
    $sql = "UPDATE Gn_onechat_me_avatar a
            SET a.data_pool_count = (
              SELECT COUNT(*) FROM Gn_onechat_me_data_pool d
              WHERE d.mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' AND d.is_deleted = 0
            )
            WHERE a.mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'";
    @$db->query($sql);
}

/** 응답 행 정리 (타입 캐스팅 + 가공 필드 추가) */
function mea_to_response($row) {
    if (!$row) return null;
    $row['idx']             = (int)$row['idx'];
    $row['data_pool_count'] = (int)$row['data_pool_count'];
    $row['decision_count']  = (int)$row['decision_count'];
    $row['match_rate']      = $row['match_rate'] !== null ? (float)$row['match_rate'] : null;
    $row['locked']          = ($row['status'] === 'locked');
    $row['paused']          = ($row['status'] === 'paused');
    return $row;
}

// ────────────────────────────────────────────────────────────
// 메서드 라우팅
// ────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handle_get($db, $login_id);
    } elseif ($method === 'POST') {
        handle_post($db, $login_id);
    } else {
        onechat_json(['error' => 'Method Not Allowed'], 405);
    }
} catch (Throwable $e) {
    error_log('[me_avatar.php] ' . $e->getMessage());
    onechat_json(['error' => '서버 내부 오류: ' . $e->getMessage()], 500);
}

// ════════════════════════════════════════════════════════════
// GET — 현재 상태 조회
//   응답: { ok:true, avatar:{ ... }, status, locked, paused }
// ════════════════════════════════════════════════════════════
function handle_get($db, $login_id) {
    // 카운터 보정 (가벼우므로 GET 시마다 1회)
    mea_recalc_counts($db, $login_id);

    $row = mea_load_avatar($db, $login_id);
    if (!$row) {
        onechat_json(['error' => '아바타 행을 가져올 수 없습니다.'], 500);
    }
    $row = mea_to_response($row);

    onechat_json([
        'ok'     => true,
        'status' => $row['status'],
        'locked' => $row['locked'],
        'paused' => $row['paused'],
        'avatar' => $row,
    ]);
}

// ════════════════════════════════════════════════════════════
// POST — 상태 전환
//   body(JSON): { "action": "lock"|"unlock"|"pause"|"resume" }
//   응답: { ok:true, action, prev_status, status, locked, paused, avatar }
// ════════════════════════════════════════════════════════════
function handle_post($db, $login_id) {
    // body 파싱 (JSON 우선, form 폴백)
    $raw = file_get_contents('php://input');
    $b = [];
    if ($raw !== '' && $raw !== false) {
        $j = json_decode($raw, true);
        if (is_array($j)) $b = $j;
    }
    if (empty($b)) $b = $_POST ?: [];

    $action = isset($b['action']) ? strtolower((string)$b['action']) : '';
    $allowed = ['lock', 'unlock', 'pause', 'resume'];
    if (!in_array($action, $allowed, true)) {
        onechat_json(['error' => "action 값이 올바르지 않습니다. (허용: lock|unlock|pause|resume)"], 400);
    }

    // 현재 상태 로드 (자동 생성 포함)
    $row = mea_load_avatar($db, $login_id);
    if (!$row) {
        onechat_json(['error' => '아바타 행을 가져올 수 없습니다.'], 500);
    }
    $prev_status = $row['status'];

    // 액션 → 다음 상태 매핑
    $next_status = null;
    $panic_clause = '';
    switch ($action) {
        case 'lock':
            $next_status = 'locked';
            $panic_clause = ', panic_locked_at = NOW()';
            break;
        case 'unlock':
            $next_status = 'active';
            $panic_clause = ', panic_locked_at = NULL';
            break;
        case 'pause':
            $next_status = 'paused';
            // panic_locked_at 은 유지 (locked가 아니므로 의미상 무관하지만 기존값 보존)
            break;
        case 'resume':
            // resume은 paused에서만 의미 있음. locked 상태에서 resume → unlock와 동일하게 처리하는 게 안전
            $next_status = 'active';
            if ($prev_status === 'locked') {
                $panic_clause = ', panic_locked_at = NULL';
            }
            break;
    }

    // 이미 동일 상태면 멱등 응답
    if ($prev_status === $next_status) {
        $refreshed = mea_to_response(mea_load_avatar($db, $login_id));
        onechat_json([
            'ok'           => true,
            'action'       => $action,
            'no_change'    => true,
            'prev_status'  => $prev_status,
            'status'       => $next_status,
            'locked'       => ($next_status === 'locked'),
            'paused'       => ($next_status === 'paused'),
            'avatar'       => $refreshed,
        ]);
    }

    $le = mea_esc($db, $login_id);
    $next_esc = mea_esc($db, $next_status);

    $upd = "UPDATE Gn_onechat_me_avatar
            SET status = '{$next_esc}'{$panic_clause}, updated_at = NOW()
            WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
            LIMIT 1";
    if (!$db->query($upd)) {
        onechat_json(['error' => 'UPDATE 실패: ' . $db->error], 500);
    }

    // 카운터 동기화 + 최신 상태 반환
    mea_recalc_counts($db, $login_id);
    $refreshed = mea_to_response(mea_load_avatar($db, $login_id));

    onechat_json([
        'ok'           => true,
        'action'       => $action,
        'prev_status'  => $prev_status,
        'status'       => $next_status,
        'locked'       => ($next_status === 'locked'),
        'paused'       => ($next_status === 'paused'),
        'avatar'       => $refreshed,
    ]);
}
