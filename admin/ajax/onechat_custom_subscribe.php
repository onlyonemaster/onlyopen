<?php
/**
 * 원챗(OneChat) 커스텀 결제 링크 생성 API
 * 관리자 전용 - 실제 토스페이먼츠 결제를 위한 결제링크 생성
 */
header('Content-Type: application/json; charset=utf-8');

// 재발 방지: MySQL 커넥션/쿼리 타임아웃
set_time_limit(15);
ini_set('mysql.connect_timeout', 5);

include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';

// MySQL 타임아웃 설정
if ($self_con) {
    $mysql_version = mysqli_get_server_info($self_con);
    if (version_compare($mysql_version, '8.0', '>=')) {
        mysqli_query($self_con, "SET SESSION max_execution_time=10000");
    } elseif (version_compare($mysql_version, '5.7', '>=')) {
        mysqli_query($self_con, "SET SESSION max_statement_time=10000");
    }
    mysqli_query($self_con, "SET SESSION wait_timeout=10");
}

// ── 관리자 권한 체크 ────────────────────────────────
$is_admin = false;
if (!empty($_SESSION['one_member_admin_id'])) {
    $is_admin = true;
} elseif (in_array($_SESSION['one_member_id'] ?? '', ['obmms01', 'obmms02', 'db', 'sungmheo', 'lecturem'])) {
    $is_admin = true;
} elseif (!empty($_SESSION['one_member_subadmin_id']) && ($_SESSION['one_member_subadmin_domain'] ?? '') == ($_SERVER['HTTP_HOST'] ?? '')) {
    $is_admin = true;
}

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 입력 수집 ───────────────────────────────────────
$mem_id  = trim($_POST['mem_id'] ?? '');
$price   = (int)($_POST['price'] ?? 0);
$billing = trim($_POST['billing'] ?? 'monthly');
$profile = (int)($_POST['profile'] ?? 0);
$ai_msg  = (int)($_POST['ai_msg'] ?? 0);
$resp    = (int)($_POST['resp'] ?? 0);
$bot     = (int)($_POST['bot'] ?? 0);
$mode    = trim($_POST['mode'] ?? 'template');

// 입력 유효성 검증
if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $mem_id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '회원ID 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($price < 100) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '결제 금액은 최소 100원 이상이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($billing, ['monthly', 'yearly'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '과금주기가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($profile < 1 || $ai_msg < 1 || $resp < 1 || $bot < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '모든 사용 한도는 1 이상이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 회원 존재 확인 ─────────────────────────────────
$safe_id = mysqli_real_escape_string($self_con, $mem_id);
$query = "SELECT mem_id, mem_name, mem_nick, service_type FROM Gn_Member WHERE mem_id='{$safe_id}' LIMIT 1";
$res = mysqli_query($self_con, $query);
if (!$res || !$row = mysqli_fetch_assoc($res)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => '회원을 찾을 수 없습니다: ' . $mem_id], JSON_UNESCAPED_UNICODE);
    exit;
}

$member_name = $row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'];
$current_plan = $row['service_type'] ?: 'free';

// ── 주문번호 생성 (CUSTOM- 접두어) ─────────────────
$orderNumber = 'CUSTOM-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

// 만료일 계산
$now_ts  = time();
$next_ts = ($billing === 'yearly') ? strtotime('+1 year', $now_ts) : strtotime('+1 month', $now_ts);
$end_date = date('Y-m-d H:i:s', $next_ts);

// ── tjd_pay_result 저장 (pending 상태) ──────────────
$pay_info = [
    'orderNumber'             => $orderNumber,
    'tid'                     => 'CUSTOM_PENDING_' . uniqid(),
    'VACT_InputName'          => mysqli_real_escape_string($self_con, $member_name),
    'TotPrice'                => $price,
    'month_cnt'               => ($billing === 'yearly') ? 12 : 1,
    'end_date'                => $end_date,
    'end_status'              => 'N',      // pending: 아직 결제 안 됨
    'buyertel'                => '01000000000',
    'buyeremail'              => 'admin-custom@onechat.kr',
    'payMethod'               => 'Custom',
    'buyer_id'                => $safe_id,
    'member_type'             => 'onechat_custom_' . $billing,
    'phone_cnt'               => 0,
    'max_cnt'                 => 0,
    'db_cnt'                  => 0,
    'email_cnt'               => 0,
    'shop_cnt'                => 0,
    'fujia_status'            => 'N',
    'resultCode'              => 'PENDING',
    'resultMsg'               => 'CUSTOM_PAY_WAITING',
    'pc_mobile'               => 'A',
    'add_opt'                 => 'N',
    'add_phone'               => 0,
    'onestep1'                => 'custom',
    'onestep2'                => $billing,
    'member_cnt'              => 0,
    'ai_profile_limit_custom' => $profile,
    'ai_msg_limit_custom'     => $ai_msg,
    'ai_resp_limit_custom'    => $resp,
    'bot_limit_custom'        => $bot,
    'is_custom_order'         => 'Y',
];

$sql = "INSERT INTO tjd_pay_result SET ";
foreach ($pay_info as $k => $v) {
    $sql .= "$k='" . mysqli_real_escape_string($self_con, $v) . "',";
}
$sql .= "date=NOW()";
mysqli_query($self_con, $sql) or error_log("[OCCUSTOM DB ERR] " . mysqli_error($self_con));

// ── 결제 URL 생성 ──────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'kiam.kr';

// 파라미터를 URL-safe하게 전달
$payParams = http_build_query([
    'orderId'    => $orderNumber,
    'amount'     => $price,
    'planId'     => 'custom',
    'billing'    => $billing,
    'memId'      => $mem_id,
    'memberName' => $member_name,
    'profile'    => $profile,
    'aiMsg'      => $ai_msg,
    'resp'       => $resp,
    'bot'        => $bot,
]);

// 관리자 결제 페이지 URL
$pay_url = $protocol . '://' . $host . '/admin/ajax/custom_pay.php?' . $payParams;

error_log("[OCCUSTOM CREATE] admin={$_SESSION['one_member_id']} target={$mem_id} order={$orderNumber} price={$price} billing={$billing} mode={$mode} limits=profile:{$profile}/ai_msg:{$ai_msg}/resp:{$resp}/bot:{$bot}");

// ── 응답 ────────────────────────────────────────────
echo json_encode([
    'ok'          => true,
    'order_id'    => $orderNumber,
    'member_id'   => $mem_id,
    'member_name' => $member_name,
    'price'       => $price,
    'billing'     => $billing,
    'pay_url'     => $pay_url,
    'limits'      => [
        'profile' => $profile,
        'ai_msg'  => $ai_msg,
        'resp'    => $resp,
        'bot'     => $bot,
    ],
    'expires_at'  => $end_date,
], JSON_UNESCAPED_UNICODE);