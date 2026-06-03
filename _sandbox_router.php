<?php
// Sandbox router for `php -S 0.0.0.0:8088 _sandbox_router.php`
// Goal: serve static files from current dir + run PHP files + allow demo mode for api/*
// Note: returning `false` from a router only works if SCRIPT_FILENAME points to an
//       existing static file in the original request. So we explicitly handle both cases.

putenv('MYCHAT_ALLOW_DEMO=1');
$_ENV['MYCHAT_ALLOW_DEMO'] = '1';

@session_start();
if (empty($_SESSION['user_id'])) {
  $_SESSION['user_id'] = 'demo_master';
}

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// landing redirect
if ($uri === '/' || $uri === '') {
  header('Location: /aimessage/mychat/demo-entry.html');
  exit;
}

$root = __DIR__;
$path = realpath($root . $uri);

// security: must be inside webapp root
if ($path === false || strpos($path, $root) !== 0) {
  // try without realpath (file may not exist or be symlink)
  $path = $root . $uri;
  if (!file_exists($path) || strpos($path, $root) !== 0) {
    http_response_code(404);
    echo '404 — not found: '.htmlspecialchars($uri);
    exit;
  }
}

if (is_dir($path)) {
  foreach (['index.html', 'index.php'] as $idx) {
    if (is_file($path . '/' . $idx)) { $path = $path . '/' . $idx; break; }
  }
  if (is_dir($path)) {
    http_response_code(403);
    echo '403 — directory listing disabled';
    exit;
  }
}

if (!is_file($path)) {
  http_response_code(404);
  echo '404 — not found: '.htmlspecialchars($uri);
  exit;
}

// PHP file → include (within router context)
if (preg_match('/\.php$/i', $path)) {
  $_SERVER['SCRIPT_FILENAME'] = $path;
  $_SERVER['SCRIPT_NAME']     = $uri;
  $_SERVER['PHP_SELF']        = $uri;
  chdir(dirname($path));
  include $path;
  return;
}

// static file → set content-type and stream
$mime = [
  'html'=>'text/html; charset=utf-8',
  'htm' =>'text/html; charset=utf-8',
  'css' =>'text/css; charset=utf-8',
  'js'  =>'application/javascript; charset=utf-8',
  'json'=>'application/json; charset=utf-8',
  'svg' =>'image/svg+xml',
  'png' =>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp',
  'ico' =>'image/x-icon',
  'woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf',
  'mp3' =>'audio/mpeg','wav'=>'audio/wav','webm'=>'audio/webm',
  'mp4' =>'video/mp4',
  'txt' =>'text/plain; charset=utf-8','md'=>'text/plain; charset=utf-8'
];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($mime[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
readfile($path);
