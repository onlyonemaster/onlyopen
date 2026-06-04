<?php
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');
$ctx = stream_context_create(['http'=>['timeout'=>10,'user_agent'=>'Mozilla/5.0','header'=>'Referer: https://onechat.kiam.kr/'],'ssl'=>['verify_peer'=>true]]);
$sdk = @file_get_contents('https://js.tosspayments.com/v1/payment', false, $ctx);
if ($sdk===false){http_response_code(503);echo '// error';exit;}
echo '// Proxied from js.tosspayments.com'.PHP_EOL;
echo $sdk;
