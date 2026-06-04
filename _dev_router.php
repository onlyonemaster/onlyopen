<?php
// 개발 시뮬레이션 라우터 — /disk/daily/home/kiam을 document root로
$DOCROOT = '/disk/daily/home/kiam';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 인증 우회 (시뮬레이션)
putenv('MYCHAT_ALLOW_DEMO=1');
putenv('MYCHAT_KEY_SECRET=dev_simulation_secret');
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.save_path', '/tmp');
  @session_start();
  $_SESSION['user_id'] = 'demo_master';
  $_SESSION['mem_id']  = 'demo_master';
}

if ($uri === '/' || $uri === '') {
  $uri = '/aimessage/mychat/demo-entry.html';
}

$path = $DOCROOT . $uri;
if (!file_exists($path)) {
  http_response_code(404);
  echo "404 Not Found: $uri";
  return true;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = [
  'html'=>'text/html; charset=utf-8','htm'=>'text/html; charset=utf-8',
  'js'=>'application/javascript; charset=utf-8','css'=>'text/css; charset=utf-8',
  'json'=>'application/json; charset=utf-8','svg'=>'image/svg+xml',
  'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
  'ico'=>'image/x-icon','txt'=>'text/plain; charset=utf-8','md'=>'text/markdown; charset=utf-8',
];

if ($ext === 'php') {
  $_SERVER['SCRIPT_FILENAME'] = $path;
  chdir(dirname($path));
  include $path;
  return true;
}

if (isset($mimes[$ext])) {
  header('Content-Type: '.$mimes[$ext]);
}
readfile($path);
return true;
