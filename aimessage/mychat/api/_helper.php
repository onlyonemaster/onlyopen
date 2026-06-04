<?php
/**
 * Shared helpers for /aimessage/mychat/api/*.php
 *  - JSON-only response, no caching
 *  - session-based auth (가입자 보호)
 *  - graceful body parsing (json + form)
 *  - sandboxed file storage (실서버에서는 DB로 대체)
 */
declare(strict_types=1);

if (!function_exists('mc_init')) {

function mc_init(): array {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  @session_start();

  $uid = $_SESSION['user_id'] ?? null;
  // Sandbox / dev mode: allow anonymous demo user when explicit flag present
  if (!$uid && (getenv('MYCHAT_ALLOW_DEMO') === '1')) {
    $uid = 'demo';
  }
  if (!$uid) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $action = '';
  $body   = [];
  if ($method === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'multipart/form-data') !== false || stripos($ct, 'application/x-www-form-urlencoded') !== false) {
      $action = $_POST['action'] ?? '';
      $body   = $_POST;
    } else {
      $raw  = file_get_contents('php://input') ?: '';
      $body = json_decode($raw, true) ?: [];
      $action = $body['action'] ?? '';
    }
  } else {
    $action = $_GET['action'] ?? '';
    $body   = $_GET;
  }

  return ['uid'=>$uid, 'action'=>$action, 'body'=>$body, 'method'=>$method];
}

function mc_store_dir(string $uid): string {
  $dir = sys_get_temp_dir().'/mychat_store/'.preg_replace('/[^A-Za-z0-9_\-]/','_', (string)$uid);
  if (!is_dir($dir)) @mkdir($dir, 0700, true);
  return $dir;
}
function mc_store_get(string $uid, string $key, $default = null) {
  $f = mc_store_dir($uid).'/'.$key.'.json';
  if (!is_file($f)) return $default;
  $j = json_decode((string)file_get_contents($f), true);
  return $j === null ? $default : $j;
}
function mc_store_set(string $uid, string $key, $val): bool {
  $f = mc_store_dir($uid).'/'.$key.'.json';
  return file_put_contents($f, json_encode($val, JSON_UNESCAPED_UNICODE)) !== false;
}

function mc_ok(array $extra = []): void {
  echo json_encode(array_merge(['ok'=>true], $extra), JSON_UNESCAPED_UNICODE);
}
function mc_fail(string $error, array $extra = [], int $http = 200): void {
  if ($http !== 200) http_response_code($http);
  echo json_encode(array_merge(['ok'=>false, 'error'=>$error], $extra), JSON_UNESCAPED_UNICODE);
}

} // /if
