<?php
/**
 * 원챗(OneChat) 구독 한도(Quota) 관리 API
 * 
 * GET  ?action=status              → 현재 사용량/한도 조회
 * POST ?action=check&type=profile  → 특정 리소스 사용 가능 여부 확인
 * POST ?action=use&type=profile    → 사용량 1 증가 (원자적)
 * POST ?action=reset               → 월간 리셋 (cron 전용, 또는 결제 시)
 * 
 * 응답형식: { ok:bool, data:{...}, warning:bool|null, blocked:bool|null }
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();

$db    = getDatabaseConnection();
$le    = $db->real_escape_string($login_id);
$action = trim($_GET['action'] ?? 'status');
$type   = trim($_GET['type']   ?? $_POST['type'] ?? '');

// ── 플랜별 한도 정의 (subscribev1.html PLANS + COMPARE_ROWS 기준) ──
function get_plan_limits($plan_id) {
    $plans = [
        'free'      => ['profile' => 30,    'ai_msg' => 15,    'resp' => 15,    'bot' => 1],
        'basic'     => ['profile' => 500,   'ai_msg' => 300,   'resp' => 300,   'bot' => 3],
        'standard'  => ['profile' => 1500,  'ai_msg' => 900,   'resp' => 900,   'bot' => 10],
        'pro'       => ['profile' => 10000, 'ai_msg' => 6000,  'resp' => 6000,  'bot' => 30],
        'business'  => ['profile' => 50000, 'ai_msg' => 30000, 'resp' => 30000, 'bot' => 100],
        'b2b-biz'   => ['profile' => 50000, 'ai_msg' => 30000, 'resp' => 30000, 'bot' => 100],
        'pro_b2b'   => ['profile' => 150000,'ai_msg' => 90000, 'resp' => 90000, 'bot' => 300],
        'b2b-pro'   => ['profile' => 150000,'ai_msg' => 90000, 'resp' => 90000, 'bot' => 300],
        'team'      => ['profile' => 500000,'ai_msg' => 300000,'resp' => 300000,'bot' => 500],
        'b2b-team'  => ['profile' => 500000,'ai_msg' => 300000,'resp' => 300000,'bot' => 500],
    ];
    return $plans[$plan_id] ?? $plans['free'];
}

// ── 현재 회원의 service_type 기준 한도 계산 ──
function get_current_limits($db, $le) {
    $r = $db->query("SELECT service_type, sub_end_date,
        ai_profile_limit, ai_profile_used,
        ai_msg_person_limit, ai_msg_person_used,
        ai_resp_limit, ai_resp_used
        FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
    if (!$r || !$row = $r->fetch_assoc()) return null;

    $plan_id = $row['service_type'] ?: 'free';
    
    // 구독 만료 체크
    $expired = false;
    if ($plan_id !== 'free' && !empty($row['sub_end_date'])) {
        $expired = strtotime($row['sub_end_date']) < time();
    }
    if ($expired) $plan_id = 'free';

    $plan_limits = get_plan_limits($plan_id);
    
    // DB에 저장된 limit이 0이면 plan 기본값 사용
    $profile_limit = (int)($row['ai_profile_limit'] ?? 0);
    if ($profile_limit <= 0) $profile_limit = $plan_limits['profile'];
    
    $msg_limit = (int)($row['ai_msg_person_limit'] ?? 0);
    if ($msg_limit <= 0) $msg_limit = $plan_limits['ai_msg'];
    
    $resp_limit = (int)($row['ai_resp_limit'] ?? 0);
    if ($resp_limit <= 0) $resp_limit = $plan_limits['resp'];
    
    $bot_limit = $plan_limits['bot'];

    return [
        'plan_id'       => $plan_id,
        'expired'       => $expired,
        'sub_end_date'  => $row['sub_end_date'] ?? null,
        'profile'       => ['used' => (int)($row['ai_profile_used'] ?? 0), 'limit' => $profile_limit],
        'ai_msg'        => ['used' => (int)($row['ai_msg_person_used'] ?? 0), 'limit' => $msg_limit],
        'resp'          => ['used' => (int)($row['ai_resp_used'] ?? 0),     'limit' => $resp_limit],
        'bot'           => ['used' => 0, 'limit' => $bot_limit], // bot은 별도 카운트
    ];
}

// ── ACTION: status ─────────────────────────────────────────────
if ($action === 'status') {
    $limits = get_current_limits($db, $le);
    if (!$limits) {
        onechat_json(['error' => '회원 정보를 찾을 수 없습니다.'], 404);
    }

    // 70% 경고 계산
    $warnings = [];
    foreach (['profile', 'ai_msg', 'resp'] as $k) {
        $u = $limits[$k]['used'];
        $l = $limits[$k]['limit'];
        if ($l > 0 && $u > 0) {
            $pct = round(($u / $l) * 100, 1);
            if ($pct >= 70) $warnings[$k] = $pct;
        }
    }

    onechat_json([
        'ok'       => true,
        'plan_id'  => $limits['plan_id'],
        'expired'  => $limits['expired'],
        'limits'   => [
            'profile' => $limits['profile'],
            'ai_msg'  => $limits['ai_msg'],
            'resp'    => $limits['resp'],
            'bot'     => $limits['bot'],
        ],
        'warnings' => empty($warnings) ? null : $warnings,
        'sub_end_date' => $limits['sub_end_date'],
    ]);
}

// ── ACTION: check ──────────────────────────────────────────────
if ($action === 'check') {
    $valid_types = ['profile', 'ai_msg', 'resp'];
    if (!in_array($type, $valid_types)) {
        onechat_json(['error' => '유효하지 않은 리소스 타입입니다. (profile, ai_msg, resp)'], 400);
    }

    $limits = get_current_limits($db, $le);
    if (!$limits) {
        onechat_json(['error' => '회원 정보를 찾을 수 없습니다.'], 404);
    }

    $u = $limits[$type]['used'];
    $l = $limits[$type]['limit'];

    $blocked = ($l > 0 && $u >= $l);
    $pct = ($l > 0) ? round(($u / $l) * 100, 1) : 0;
    $warning = ($pct >= 70 && !$blocked);

    onechat_json([
        'ok'         => !$blocked,
        'blocked'    => $blocked,
        'used'       => $u,
        'limit'      => $l,
        'pct'        => $pct,
        'warning'    => $warning,
        'plan_id'    => $limits['plan_id'],
    ]);
}

// ── ACTION: use ────────────────────────────────────────────────
if ($action === 'use') {
    $valid_types = ['profile', 'ai_msg', 'resp'];
    if (!in_array($type, $valid_types)) {
        onechat_json(['error' => '유효하지 않은 리소스 타입입니다. (profile, ai_msg, resp)'], 400);
    }

    // 컬럼 매핑
    $col_used = [
        'profile' => 'ai_profile_used',
        'ai_msg'  => 'ai_msg_person_used',
        'resp'    => 'ai_resp_used',
    ];
    $col_limit = [
        'profile' => 'ai_profile_limit',
        'ai_msg'  => 'ai_msg_person_limit',
        'resp'    => 'ai_resp_limit',
    ];

    $used_col = $col_used[$type];
    $limit_col = $col_limit[$type];

    // ── 원자적 증가 (UPDATE ... WHERE used < limit) ──
    $sql = "UPDATE Gn_Member 
            SET {$used_col} = {$used_col} + 1 
            WHERE mem_id = '{$le}' 
              AND {$used_col} < {$limit_col}
              AND {$limit_col} > 0";
    $db->query($sql);
    $affected = $db->affected_rows;

    if ($affected === 0) {
        // 한도 초과 → 현재 상태 조회해서 상세 정보 반환
        $limits = get_current_limits($db, $le);
        $u = $limits[$type]['used'] ?? 0;
        $l = $limits[$type]['limit'] ?? 0;

        onechat_json([
            'ok'      => false,
            'blocked' => true,
            'used'    => $u,
            'limit'   => $l,
            'pct'     => ($l > 0) ? round(($u / $l) * 100, 1) : 0,
            'message' => "{$type} 한도를 초과했습니다. ({$u}/{$l})",
            'plan_id' => $limits['plan_id'],
        ], 429); // 429 Too Many Requests
    }

    // 성공 → 갱신된 값 조회
    $r = $db->query("SELECT {$used_col}, {$limit_col} FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
    $row = $r->fetch_assoc();
    $new_used = (int)($row[$used_col] ?? 0);
    $limit    = (int)($row[$limit_col] ?? 0);
    $pct      = ($limit > 0) ? round(($new_used / $limit) * 100, 1) : 0;

    onechat_json([
        'ok'      => true,
        'used'    => $new_used,
        'limit'   => $limit,
        'pct'     => $pct,
        'warning' => ($pct >= 70 && $pct < 100),
    ]);
}

// ── ACTION: reset ───────────────────────────────────────────────
if ($action === 'reset') {
    // 월간 리셋: used=0 (cron, 결제 시)
    $db->query("UPDATE Gn_Member SET 
        ai_profile_used = 0, 
        ai_msg_person_used = 0, 
        ai_resp_used = 0 
        WHERE mem_id = '{$le}'");

    onechat_json(['ok' => true, 'message' => '사용량이 초기화되었습니다.']);
}

// ── unknown action ──────────────────────────────────────────────
onechat_json(['error' => '알 수 없는 action입니다. (status, check, use, reset)'], 400);