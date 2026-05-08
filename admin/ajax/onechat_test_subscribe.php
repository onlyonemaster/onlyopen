<?php
/**
 * 원챗(OneChat) 테스트 구독 처리 API
 * 관리자 전용 - 실제 토스 결제 없이 구독 상태 주입
 */
header('Content-Type: application/json; charset=utf-8');

// ★ 재발 방지: MySQL 커넥션/쿼리 타임아웃 설정 (느린 쿼리로 인한 30초+ 지연 방지)
set_time_limit(15);              // PHP 실행 제한 15초
ini_set('mysql.connect_timeout', 5);  // DB 연결 5초 제한

include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';

// ★ 세션 타임아웃도 MySQL에 적용 (MySQL 8.0: max_execution_time / MariaDB: max_statement_time)
if ($self_con) {
    // MySQL 8.0 문법 (서버 버전 자동 감지)
    $mysql_version = mysqli_get_server_info($self_con);
    if (version_compare($mysql_version, '8.0', '>=')) {
        mysqli_query($self_con, "SET SESSION max_execution_time=10000"); // 10초 쿼리 제한
    } elseif (version_compare($mysql_version, '5.7', '>=')) {
        mysqli_query($self_con, "SET SESSION max_statement_time=10000");
    }
    mysqli_query($self_con, "SET SESSION wait_timeout=10");
}

// ── 관리자 권한 체크 ────────────────────────────────
$is_admin = false;
if (!empty($_SESSION['one_member_admin_id'])) {
    $is_admin = true;
} elseif (in_array($_SESSION['one_member_id'], ['obmms01', 'obmms02', 'db', 'sungmheo', 'lecturem'])) {
    $is_admin = true;
} elseif (!empty($_SESSION['one_member_subadmin_id']) && $_SESSION['one_member_subadmin_domain'] == $_SERVER['HTTP_HOST']) {
    $is_admin = true;
}

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 입력 수집 ───────────────────────────────────────
$mem_id  = trim($_POST['mem_id'] ?? '');
$plan_id = trim($_POST['plan_id'] ?? '');
$billing = trim($_POST['billing'] ?? 'monthly'); // monthly | yearly

// ★ 입력 유효성 빠른 검증 (DB 접근 전에 실행)
if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $mem_id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '회원ID 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$allowed_plans = ['free','basic','standard','pro','business','b2b-biz','b2b-pro','b2b-team'];
if (!in_array($plan_id, $allowed_plans, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '유효하지 않은 플랜입니다: ' . $plan_id], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($billing, ['monthly','yearly'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '과금주기가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 플랜 한도
$plan_limits = [
    'free'     => ['profile' => 30,     'ai_msg' => 15,      'resp' => 15,      'bot' => 1],
    'basic'    => ['profile' => 500,    'ai_msg' => 300,     'resp' => 300,     'bot' => 3],
    'standard' => ['profile' => 1500,   'ai_msg' => 900,     'resp' => 900,     'bot' => 10],
    'pro'      => ['profile' => 10000,  'ai_msg' => 6000,    'resp' => 6000,    'bot' => 30],
    'business' => ['profile' => 50000,  'ai_msg' => 30000,   'resp' => 30000,   'bot' => 100],
    'b2b-biz'  => ['profile' => 50000,  'ai_msg' => 30000,   'resp' => 30000,   'bot' => 100],
    'b2b-pro'  => ['profile' => 150000, 'ai_msg' => 90000,   'resp' => 90000,   'bot' => 300],
    'b2b-team' => ['profile' => 500000, 'ai_msg' => 300000,  'resp' => 300000,  'bot' => 500],
];

if (!$mem_id || !$plan_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '회원ID와 플랜ID는 필수입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$limits = $plan_limits[$plan_id] ?? null;
if (!$limits) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '유효하지 않은 플랜입니다: ' . $plan_id], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 회원 존재 확인 + 현재 플랜 정보 ─────────────────
$safe_id = mysqli_real_escape_string($self_con, $mem_id);
$query = "SELECT mem_id, mem_name, mem_nick, service_type, sub_end_date,
          ai_profile_used, ai_msg_person_used, ai_resp_used
          FROM Gn_Member WHERE mem_id='{$safe_id}' LIMIT 1";
$res = mysqli_query($self_con, $query);
if (!$res || !$row = mysqli_fetch_assoc($res)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => '회원을 찾을 수 없습니다: ' . $mem_id], JSON_UNESCAPED_UNICODE);
    exit;
}

$current_plan = $row['service_type'] ?: 'free';
$member_name  = $row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'];

// ── 만료일 계산 ─────────────────────────────────────
$now_ts  = time();
$next_ts = ($billing === 'yearly') ? strtotime('+1 year', $now_ts) : strtotime('+1 month', $now_ts);
$end_date = date('Y-m-d H:i:s', $next_ts);

// ── tjd_pay_result 저장 (TEST- 접두어) ──────────────
$orderNumber = 'TEST-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
$member_type_suffix = ($plan_id === 'free') ? 'free_test' : $plan_id . '_test';

$pay_info = [
    'orderNumber'    => $orderNumber,
    'tid'            => 'TEST_BILLINGKEY_' . uniqid(),
    'VACT_InputName' => mysqli_real_escape_string($self_con, $member_name),
    'TotPrice'       => 0,
    'month_cnt'      => ($billing === 'yearly') ? 12 : 1,
    'end_date'       => $plan_id === 'free' ? null : $end_date,
    'end_status'     => 'Y',
    'buyertel'       => '01000000000',
    'buyeremail'     => 'admin-test@onechat.kr',
    'payMethod'      => 'AdminTest',
    'buyer_id'       => $safe_id,
    'member_type'    => 'onechat_' . $member_type_suffix,
    'phone_cnt'      => 0,
    'max_cnt'        => 0,
    'db_cnt'         => 0,
    'email_cnt'      => 0,
    'shop_cnt'       => 0,
    'fujia_status'   => 'N',
    'resultCode'     => '0000',
    'resultMsg'      => 'TEST_OK',
    'pc_mobile'      => 'A',
    'add_opt'        => 'N',
    'add_phone'      => 0,
    'onestep1'       => $plan_id,
    'onestep2'       => 'test',
    'member_cnt'     => 0,
];

$sql = "INSERT INTO tjd_pay_result SET ";
foreach ($pay_info as $k => $v) {
    $sql .= "$k='" . mysqli_real_escape_string($self_con, $v) . "',";
}
$sql .= "date=NOW()";
mysqli_query($self_con, $sql) or error_log("[OCTEST DB ERR] " . mysqli_error($self_con));

// ── Gn_Member 업데이트 ──────────────────────────────
$safe_plan = mysqli_real_escape_string($self_con, $plan_id);
if ($plan_id === 'free') {
    // Free로 되돌리기 - 한도만 설정, 사용량은 유지
    mysqli_query($self_con,
        "UPDATE Gn_Member SET
            service_type        = 'free',
            sub_end_date        = NULL,
            ai_profile_limit    = {$limits['profile']},
            ai_msg_person_limit = {$limits['ai_msg']},
            ai_resp_limit       = {$limits['resp']}
         WHERE mem_id='{$safe_id}'"
    ) or error_log("[OCTEST MEM FREE ERR] " . mysqli_error($self_con));
} else {
    mysqli_query($self_con,
        "UPDATE Gn_Member SET
            service_type        = '{$safe_plan}',
            sub_end_date        = '{$end_date}',
            ai_profile_limit    = {$limits['profile']},
            ai_profile_used     = 0,
            ai_msg_person_limit = {$limits['ai_msg']},
            ai_msg_person_used  = 0,
            ai_resp_limit       = {$limits['resp']},
            ai_resp_used        = 0
         WHERE mem_id='{$safe_id}'"
    ) or error_log("[OCTEST MEM UPDATE ERR] " . mysqli_error($self_con));
}

error_log("[OCTEST APPLY] admin={$_SESSION['one_member_id']} target={$mem_id} plan={$plan_id} billing=test");

// ── 응답 ────────────────────────────────────────────
echo json_encode([
    'ok' => true,
    'order_id' => $orderNumber,
    'member_id' => $mem_id,
    'member_name' => $member_name,
    'plan_id' => $plan_id,
    'billing' => 'test',
    'limits' => [
        'profile' => $limits['profile'],
        'ai_msg'  => $limits['ai_msg'],
        'resp'    => $limits['resp'],
        'bot'     => $limits['bot'],
    ],
    'expires_at' => $end_date,
    'previous_plan' => $current_plan,
], JSON_UNESCAPED_UNICODE);