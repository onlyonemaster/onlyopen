<?php
/**
 * /aimessage/mychat/api/settings.php
 *  Actions: save_settings, get_settings, save_api_key, get_api_key, test_api_key
 *  ⚠️ 실서버에서는 API 키를 반드시 KMS/암호화 컬럼에 저장하세요.
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init();
$uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

try {
  switch ($action) {
    case 'save_settings':
      $s = $body['settings'] ?? [];
      // never persist raw api keys here unless caller marked it
      mc_store_set($uid, 'settings', $s);
      mc_ok(['saved'=>true]);
      break;

    case 'get_settings':
      $s = mc_store_get($uid, 'settings', null);
      mc_ok(['settings'=>$s]);
      break;

    case 'save_api_key':
      $provider = $body['provider'] ?? '';
      $key      = $body['api_key'] ?? '';
      $extra    = [
        'chat_model'  => $body['chat_model']  ?? null,
        'embed_model' => $body['embed_model'] ?? null,
        'base_url'    => $body['base_url']    ?? null
      ];
      if (!$provider || !$key) { mc_fail('missing_provider_or_key'); break; }
      // simple "encrypt at rest" using a passphrase env var (demo only)
      $pass = getenv('MYCHAT_KEY_SECRET') ?: 'demo-secret-not-for-production';
      $iv = random_bytes(16);
      $cipher = openssl_encrypt($key, 'AES-128-CBC', md5($pass), OPENSSL_RAW_DATA, $iv);
      $blob = base64_encode($iv.$cipher);
      $keys = mc_store_get($uid, 'api_keys', []);
      $keys[$provider] = ['cipher'=>$blob, 'mask'=>'••••'.substr($key, -4), 'meta'=>$extra, 'ts'=>time()];
      mc_store_set($uid, 'api_keys', $keys);
      mc_ok(['saved'=>true, 'mask'=>$keys[$provider]['mask']]);
      break;

    case 'get_api_key':
      $provider = $body['provider'] ?? '';
      $keys = mc_store_get($uid, 'api_keys', []);
      if (!isset($keys[$provider])){ mc_fail('not_found'); break; }
      // return mask only (never the raw key over JSON)
      mc_ok(['mask'=>$keys[$provider]['mask'], 'meta'=>$keys[$provider]['meta']]);
      break;

    case 'test_api_key':
      // demo: pretend to test — accept any non-empty
      $provider = $body['provider'] ?? '';
      $key      = $body['api_key'] ?? '';
      if (!$provider || !$key) { mc_fail('missing'); break; }
      if (strlen($key) < 10) { mc_fail('key_too_short'); break; }
      mc_ok(['provider'=>$provider, 'ok_at'=>time(), 'note'=>'demo-test (실 OpenAI/Anthropic ping은 서버 구현 필요)']);
      break;

    default:
      mc_fail('unknown_action', ['received'=>$action]);
  }
} catch (\Throwable $e) {
  mc_fail('server_error', ['detail'=> getenv('APP_DEBUG') ? $e->getMessage() : 'internal'], 500);
}
