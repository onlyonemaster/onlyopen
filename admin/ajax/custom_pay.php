<?php
/**
 * 원챗(OneChat) 커스텀 결제 페이지
 * 회원용 - 토스페이먼츠 위젯으로 카드 등록 및 결제
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';

// ── 파라미터 수집 ─────────────────────────────────
$orderId    = trim($_GET['orderId']    ?? '');
$amount     = (int)($_GET['amount']     ?? 0);
$planId     = trim($_GET['planId']     ?? 'custom');
$billing    = trim($_GET['billing']    ?? 'monthly');
$memId      = trim($_GET['memId']      ?? '');
$memberName = trim($_GET['memberName'] ?? '');
$profile    = (int)($_GET['profile']    ?? 0);
$aiMsg      = (int)($_GET['aiMsg']      ?? 0);
$resp       = (int)($_GET['resp']       ?? 0);
$bot        = (int)($_GET['bot']        ?? 0);

// 필수값 검증
if (!$orderId || !$amount || !$memId) {
    echo "<script>alert('잘못된 결제 정보입니다.'); window.close();</script>";
    exit;
}

// 중복 결제 체크
$chk = mysqli_fetch_assoc(mysqli_query($self_con,
    "SELECT COUNT(*) AS cnt, end_status FROM tjd_pay_result WHERE orderNumber='" .
    mysqli_real_escape_string($self_con, $orderId) . "'"));
$already = ($chk['cnt'] > 0 && $chk['end_status'] === 'Y');

if ($already) {
    echo "<script>alert('이미 결제가 완료된 주문입니다.'); window.close();</script>";
    exit;
}

// 토스 클라이언트 키 (테스트용)
$tossClientKey = 'live_ck_4yKeq5bgrpwbOGgyAJPBVGX0lzW6';
$billingLabel = ($billing === 'yearly') ? '연간' : '월간';
$orderName = '원챗(OneChat) 커스텀 ' . $billingLabel . ' 구독';

?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>원챗(OneChat) 커스텀 결제</title>
<script src="https://js.tosspayments.com/v1/payment"></script>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Noto Sans KR', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
  .pay-box { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); width: 480px; padding: 40px 32px; }
  .pay-box h2 { font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 4px; text-align: center; }
  .pay-box .subtitle { font-size: 13px; color: #94a3b8; text-align: center; margin-bottom: 24px; }
  .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
  .info-row .label { color: #64748b; }
  .info-row .value { color: #1e293b; font-weight: 600; }
  .price-row { display: flex; justify-content: space-between; padding: 14px 0; font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 20px; }
  .price-row .amount { color: #8b5cf6; }
  #payment-method { margin-bottom: 16px; }
  #agreement { margin-bottom: 20px; }
  .btn-pay { width: 100%; padding: 16px; background: #8b5cf6; color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
  .btn-pay:hover { background: #7c3aed; }
  .btn-pay:disabled { background: #c4b5fd; cursor: not-allowed; }
  .alert-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #166534; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="pay-box">
  <h2>💳 원챗(OneChat) 커스텀 결제</h2>
  <div class="subtitle">맞춤형 구독 결제를 진행합니다</div>

  <div class="info-row"><span class="label">회원</span><span class="value"><?=htmlspecialchars($memberName)?> (<?=htmlspecialchars($memId)?>)</span></div>
  <div class="info-row"><span class="label">주문번호</span><span class="value" style="font-family:monospace;font-size:12px;"><?=htmlspecialchars($orderId)?></span></div>
  <div class="info-row"><span class="label">과금주기</span><span class="value"><?=htmlspecialchars($billingLabel)?></span></div>
  <div class="price-row"><span>결제 금액</span><span class="amount">₩<?=number_format($amount)?></span></div>

  <div class="alert-info">
    💡 최초 1회 카드 등록 후 매월 자동 결제됩니다. 언제든지 해지 가능합니다.
  </div>

  <button class="btn-pay" id="payButton">결제하기</button>
</div>

<script>
  var clientKey = '<?=$tossClientKey?>';
  var customerKey = '<?=md5($memId . 'onechat_custom')?>';
  var tossPayments = TossPayments(clientKey);

  var payButton = document.getElementById('payButton');
  payButton.addEventListener('click', function() {
    payButton.textContent = '⏳ 결제 처리 중...';
    payButton.disabled = true;

    // 커스텀 파라미터를 successUrl에 포함
    var successBase = 'https://kiam.kr/admin/ajax/custom_pay_success.php';
    var params = new URLSearchParams({
      orderId: '<?=$orderId?>',
      amount: '<?=$amount?>',
      planId: '<?=$planId?>',
      billing: '<?=$billing?>',
      memId: '<?=$memId?>',
      profile: '<?=$profile?>',
      aiMsg: '<?=$aiMsg?>',
      resp: '<?=$resp?>',
      bot: '<?=$bot?>'
    });

    // ── 구독(빌링) 결제: requestPayment('카드', ...) 사용 ──
    tossPayments.requestPayment('카드', {
      amount: <?=$amount?>,
      orderId: '<?=$orderId?>',
      orderName: '<?=$orderName?>',
      customerKey: customerKey,
      successUrl: successBase + '?' + params.toString(),
      failUrl: 'https://kiam.kr/iam/pay_subscribe_fail.php',
      customerEmail: '<?=$memId?>@onechat.kr',
      customerName: '<?=htmlspecialchars($memberName, ENT_QUOTES)?>',
      isEscrow: false,
      flowMode: 'DEFAULT',
    }).catch(function(error) {
      payButton.textContent = '결제하기';
      payButton.disabled = false;
      if (error.code === 'USER_CANCEL') {
        alert('결제가 취소되었습니다.');
      } else {
        alert('결제 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류'));
      }
    });
  });
</script>
</body>
</html>