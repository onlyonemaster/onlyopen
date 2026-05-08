<?php
/**
 * 원챗(OneChat) 커스텀 결제 성공 처리
 * 토스페이먼츠 결제 승인 → 빌링키 발급 → 커스텀 한도 적용
 */
ob_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';

// ── 파라미터 수집 ─────────────────────────────────
$paymentKey  = trim($_GET['paymentKey']  ?? '');
$customerKey = trim($_GET['customerKey'] ?? '');
$orderId     = trim($_GET['orderId']     ?? '');
$amount      = (int)($_GET['amount']      ?? 0);
$planId      = trim($_GET['planId']      ?? 'custom');
$billing     = trim($_GET['billing']     ?? 'monthly');
$memId       = trim($_GET['memId']       ?? '');
$profile     = (int)($_GET['profile']     ?? 0);
$aiMsg       = (int)($_GET['aiMsg']       ?? 0);
$resp        = (int)($_GET['resp']        ?? 0);
$bot         = (int)($_GET['bot']         ?? 0);

if (!$paymentKey || !$customerKey || !$orderId || !$amount) {
    ob_end_clean();
    echo "<script>alert('결제 정보가 올바르지 않습니다.'); window.close();</script>";
    exit;
}

// 중복 결제 체크
$chk = mysqli_fetch_assoc(mysqli_query($self_con,
    "SELECT COUNT(*) AS cnt, end_status FROM tjd_pay_result WHERE orderNumber='" .
    mysqli_real_escape_string($self_con, $orderId) . "' AND end_status='Y'"));
$already = ($chk['cnt'] > 0);

$error_msg = '';
$billingKey = '';
$end_date = '';

if (!$already) {
    // ── 토스페이먼츠 빌링 승인 요청 ─────────────────
    $secretKey = 'live_sk_6bJXmgo28ewpdAE9lobEVLAnGKWx';
    $encKey    = base64_encode($secretKey . ':');

    $payload = json_encode([
        'paymentKey'  => $paymentKey,
        'customerKey' => $customerKey,
        'orderId'     => $orderId,
        'amount'      => $amount,
    ]);

    $ch = curl_init('https://api.tosspayments.com/v1/billing/authorizations/card');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $encKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($curlErr || $httpCode !== 200) {
        $errCode = $result['code']    ?? 'UNKNOWN';
        $errMsg  = $result['message'] ?? '빌링키 발급 실패';
        error_log("[OCCUSTOM BILLING FAIL] orderId=$orderId httpCode=$httpCode code=$errCode msg=$errMsg");

        $friendly = [
            'REJECT_CARD_COMPANY'    => '카드사에서 결제를 거절했습니다. 다른 카드를 이용해주세요.',
            'INSUFFICIENT_BALANCE'   => '카드 잔액이 부족합니다.',
            'INVALID_CARD_NUMBER'    => '카드 번호가 올바르지 않습니다.',
            'PAYMENT_PROCESS_ABORTED'=> '결제 처리가 중단되었습니다. 다시 시도해주세요.',
        ];
        $error_msg = $friendly[$errCode] ?? $errMsg;
    } else {
        // 성공: billingKey 추출
        $billingKey = $result['billingKey'] ?? '';

        // 갱신일 계산
        $now_ts  = time();
        $next_ts = ($billing === 'yearly')
            ? strtotime('+1 year', $now_ts)
            : strtotime('+1 month', $now_ts);
        $end_date = date("Y-m-d H:i:s", $next_ts);

        // ── tjd_pay_result 업데이트 (pending → 성공) ──
        $safe_orderId = mysqli_real_escape_string($self_con, $orderId);
        $safe_billingKey = mysqli_real_escape_string($self_con, $billingKey);
        mysqli_query($self_con,
            "UPDATE tjd_pay_result SET
                tid         = '{$safe_billingKey}',
                end_status  = 'Y',
                resultCode  = '0000',
                resultMsg   = 'CUSTOM_OK',
                end_date    = '{$end_date}'
             WHERE orderNumber='{$safe_orderId}'"
        ) or error_log("[OCCUSTOM UPDATE ERR] " . mysqli_error($self_con));

        // ── Gn_Member에 커스텀 한도 적용 ─────────────────
        $safe_mem = mysqli_real_escape_string($self_con, $memId);
        mysqli_query($self_con,
            "UPDATE Gn_Member SET
                service_type        = 'custom',
                sub_end_date        = '{$end_date}',
                ai_profile_limit    = {$profile},
                ai_profile_used     = 0,
                ai_msg_person_limit = {$aiMsg},
                ai_msg_person_used  = 0
             WHERE mem_id='{$safe_mem}'"
        ) or error_log("[OCCUSTOM MEM UPDATE ERR] " . mysqli_error($self_con));

        // 응답자 한도 + 봇 한도 (컬럼 있을 때만)
        @mysqli_query($self_con,
            "UPDATE Gn_Member SET
                ai_resp_limit = {$resp},
                ai_resp_used  = 0
             WHERE mem_id='{$safe_mem}'"
        );

        error_log("[OCCUSTOM BILLING SUCCESS] orderId=$orderId memId=$memId amount=$amount billingKey=$billingKey limits=profile:{$profile}/aiMsg:{$aiMsg}/resp:{$resp}/bot:{$bot}");
    }
}

$showSuccess = ($error_msg === '');
$billingLabel = ($billing === 'yearly') ? '연간' : '월간';
$nextDate = $end_date ? date('Y년 m월 d일', strtotime($end_date)) : '';
$memName = $memId;

// 회원명 조회
$mn = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT mem_name, mem_nick FROM Gn_Member WHERE mem_id='" . mysqli_real_escape_string($self_con, $memId) . "' LIMIT 1"));
if ($mn) {
    $memName = $mn['mem_name'] ?: $mn['mem_nick'] ?: $memId;
}

ob_end_clean();
?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $showSuccess ? '커스텀 결제 완료' : '커스텀 결제 실패' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { height: 100%; }
  body { font-family: 'Noto Sans KR', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
  .result-box { max-width: 420px; width:100%; margin: 0 auto; padding: 40px 24px; text-align: center; background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .result-icon { font-size: 64px; margin-bottom: 20px; }
  .result-title { font-size: 22px; font-weight: 800; margin-bottom: 10px; color:#1e293b; }
  .result-desc { font-size: 14px; color: #64748b; line-height: 1.7; margin-bottom: 8px; }
  .result-info { background: #f8fafc; border-radius: 12px; padding: 16px; margin: 24px 0; text-align: left; }
  .result-info .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom:1px solid #f1f5f9; }
  .result-info .row:last-child { border-bottom:none; }
  .result-info .row span:first-child { color: #94a3b8; }
  .result-info .row span:last-child { color: #1e293b; font-weight: 500; }
  .result-price { font-size: 24px; font-weight: 800; color: #8b5cf6; margin: 12px 0 4px; }
  .result-next { font-size: 12px; color: #94a3b8; margin-bottom: 28px; }
  .btn-wrap { display: flex; gap: 10px; justify-content: center; }
  .btn-primary { padding: 14px 36px; background: #8b5cf6; color: #fff; border-radius: 10px; font-size: 15px; font-weight: 700; text-decoration: none; border:none; cursor:pointer; }
  .btn-secondary { padding: 12px 24px; background: #e2e8f0; color: #475569; border-radius: 10px; font-size: 14px; text-decoration: none; }
</style>
</head>
<body>
<div class="result-box">
<?php if ($showSuccess): ?>
  <div class="result-icon">✅</div>
  <div class="result-title">커스텀 결제가 완료되었습니다!</div>
  <div class="result-desc">원챗(OneChat) 맞춤형 구독이 시작되었습니다.</div>
  <div class="result-info">
    <div class="row"><span>회원</span><span><?=htmlspecialchars($memName)?> (<?=htmlspecialchars($memId)?>)</span></div>
    <div class="row"><span>주문번호</span><span style="font-family:monospace;font-size:11px;"><?=htmlspecialchars($orderId)?></span></div>
    <div class="row"><span>과금주기</span><span><?=htmlspecialchars($billingLabel)?></span></div>
    <div class="row"><span>사용한도</span><span>프로필:<?=number_format($profile)?> / AI메시지:<?=number_format($aiMsg)?> / 응답자:<?=number_format($resp)?></span></div>
    <div class="result-price">₩<?=number_format($amount)?></div>
  </div>
  <div class="result-next">다음 갱신일: <?=htmlspecialchars($nextDate)?></div>
  <div class="btn-wrap">
    <a href="/aimessage/onechat/index.html" class="btn-primary">원챗으로 돌아가기</a>
    <a href="javascript:window.close();" class="btn-secondary">창 닫기</a>
  </div>
<?php else: ?>
  <div class="result-icon">❌</div>
  <div class="result-title">결제에 실패했습니다</div>
  <div class="result-desc"><?=htmlspecialchars($error_msg)?></div>
  <div class="result-info">
    <div class="row"><span>주문번호</span><span><?=htmlspecialchars($orderId)?></span></div>
    <div class="row"><span>금액</span><span>₩<?=number_format($amount)?></span></div>
  </div>
  <div class="btn-wrap">
    <button class="btn-secondary" onclick="history.back()">다시 시도하기</button>
    <a href="javascript:window.close();" class="btn-secondary">닫기</a>
  </div>
<?php endif; ?>
</div>
</body>
</html>