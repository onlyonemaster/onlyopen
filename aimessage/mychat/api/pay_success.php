<?php
/**
 * 마이챗 결제 성공 처리
 * GET: paymentKey, orderId, amount, planId, billing, apiKey(optional), apiProvider(optional)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!function_exists('onechat_auth')) {
    require_once __DIR__ . '/../../onechat/api/auth.php';
}

$mem_id = onechat_auth();
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

$paymentKey  = trim($_GET['paymentKey']  ?? '');
$orderId     = trim($_GET['orderId']     ?? '');
$amount      = (int)($_GET['amount']     ?? 0);
$planId      = trim($_GET['planId']      ?? 'starter');
$billing     = trim($_GET['billing']     ?? 'monthly');
$apiKeyRaw   = trim($_GET['apiKey']      ?? '');
$apiProvider = trim($_GET['apiProvider'] ?? 'deepseek');
$apiModel    = trim($_GET['apiModel']    ?? 'deepseek-chat');

// 플랜·금액 화이트리스트 검증 (가격 위변조 방지)
$PLAN_PRICE = [
    'starter'  => ['monthly'=>9900,  'yearly'=>95040],
    'standard' => ['monthly'=>24900, 'yearly'=>239040],
    'pro'      => ['monthly'=>49900, 'yearly'=>479040],
    'byok'     => ['monthly'=>4900,  'yearly'=>47040],
];
if (!isset($PLAN_PRICE[$planId])) {
    echo "<script>alert('잘못된 플랜입니다.'); location.href='/aimessage/mychat/subscribe.html';</script>"; exit;
}
$expectedAmount = $PLAN_PRICE[$planId][$billing] ?? $PLAN_PRICE[$planId]['monthly'];
if ((int)$amount !== (int)$expectedAmount) {
    error_log("[mychat-pay] 금액 불일치 mem={$mem_id} plan={$planId} billing={$billing} got={$amount} exp={$expectedAmount}");
    echo "<script>alert('결제 금액이 플랜 가격과 일치하지 않습니다.'); location.href='/aimessage/mychat/subscribe.html';</script>"; exit;
}

if (!$paymentKey || !$orderId || !$amount) {
    echo "<script>alert('결제 정보가 올바르지 않습니다.'); location.href='/aimessage/mychat/subscribe.html';</script>"; exit;
}

// 중복 결제 체크
$dup = $db->query("SELECT id FROM mychat_subscriptions WHERE pay_ref='{$db->real_escape_string($orderId)}' LIMIT 1")?->fetch_assoc();
if ($dup) {
    header('Location: /aimessage/mychat/?subscribed=1'); exit;
}

// 토스페이먼츠 승인 요청
$secretKey = 'live_sk_6bJXmgo28ewpdAE9lobEVLAnGKWx';
$encKey    = base64_encode($secretKey . ':');
$payload   = json_encode(['paymentKey'=>$paymentKey,'orderId'=>$orderId,'amount'=>$amount]);

$ch = curl_init('https://api.tosspayments.com/v1/payments/confirm');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Basic '.$encKey, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);

if ($httpCode !== 200 || empty($result['paymentKey'])) {
    $errMsg = $result['message'] ?? '결제 승인 실패';
    echo "<script>alert('결제 오류: ".addslashes($errMsg)."'); location.href='/aimessage/mychat/subscribe.html';</script>"; exit;
}

// 구독 기간 계산
$months   = ($billing === 'yearly') ? 12 : 1;
$startAt  = date('Y-m-d H:i:s');
$expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));

// API키 암호화 (BYOK)
$apiType    = 'platform';
$apiKeyEnc  = null;
if ($planId === 'byok' && $apiKeyRaw) {
    $apiType   = 'user_key';
    $key       = hash('sha256', $mem_id . 'MYCHAT_ENC_SALT_2026', true);
    $iv        = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($apiKeyRaw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $apiKeyEnc = base64_encode($iv . $encrypted);
}

// 기존 구독 취소 처리
$db->query("UPDATE mychat_subscriptions SET status='cancelled' WHERE mem_id='{$esc}' AND status='active'");

// 신규 구독 등록
$esc_plan    = $db->real_escape_string($planId);
$esc_type    = $db->real_escape_string($apiType);
$esc_keyenc  = $apiKeyEnc ? "'{$db->real_escape_string($apiKeyEnc)}'" : 'NULL';
$esc_prov    = $db->real_escape_string($apiProvider);
$esc_model   = $db->real_escape_string($apiModel);
$esc_order   = $db->real_escape_string($orderId);

$db->query("INSERT INTO mychat_subscriptions
    (mem_id, plan_id, api_type, user_api_key_enc, user_api_provider, user_api_model,
     status, started_at, expires_at, pay_ref)
    VALUES ('{$esc}', '{$esc_plan}', '{$esc_type}', {$esc_keyenc}, '{$esc_prov}', '{$esc_model}',
            'active', '{$startAt}', '{$expiresAt}', '{$esc_order}')");

// 아바타 초기 생성
$db->query("INSERT IGNORE INTO mychat_avatar (mem_id) VALUES ('{$esc}')");

header('Location: /aimessage/mychat/?subscribed=1&plan=' . urlencode($planId));
exit;
