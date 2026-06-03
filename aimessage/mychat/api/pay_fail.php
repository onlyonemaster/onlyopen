<?php
/**
 * 마이챗 결제 실패 처리
 */
$code    = htmlspecialchars($_GET['code']    ?? '');
$message = htmlspecialchars($_GET['message'] ?? '결제가 취소되었습니다.');
?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>결제 실패 · 마이챗</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#f1f5f9;font-family:'Noto Sans KR',sans-serif;
     display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px 24px;
      max-width:360px;width:100%;text-align:center}
.icon{font-size:48px;margin-bottom:16px}
h2{font-size:18px;font-weight:700;margin-bottom:8px;color:#f87171}
p{font-size:13px;color:#94a3b8;margin-bottom:4px}
.code{font-size:11px;color:#64748b;margin-bottom:24px}
.btn{display:block;width:100%;padding:12px;border-radius:10px;font-size:14px;
     font-weight:700;text-decoration:none;cursor:pointer;border:none}
.btn-primary{background:linear-gradient(135deg,#7e22ce,#b45309);color:#fff;margin-bottom:10px}
.btn-outline{background:transparent;border:1px solid #334155;color:#94a3b8}
</style>
</head>
<body>
<div class="card">
  <div class="icon">❌</div>
  <h2>결제에 실패했습니다</h2>
  <p><?= $message ?></p>
  <?php if ($code): ?><p class="code">오류코드: <?= $code ?></p><?php endif; ?>
  <a href="/aimessage/mychat/subscribe.html" class="btn btn-primary">다시 결제하기</a>
  <a href="/aimessage/mychat/" class="btn btn-outline">마이챗으로 돌아가기</a>
</div>
</body>
</html>
