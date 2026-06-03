<?php
/**
 * /aimessage/mychat/api/sync_policy.php
 *  Actions: load, save
 *  - 폰 자동 동기화 정책 (소스별 on/주기/보존/공개범위 + 전송 조건)
 *  - 모바일 앱은 별도 엔드포인트(/api/phone_sync.php) 로 데이터를 push, 정책은 GET으로 조회.
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

switch ($action) {
  case 'load':
    mc_ok(['policy'=> mc_store_get($uid, 'sync_policy', null)]);
    break;
  case 'save':
    $p = $body['policy'] ?? null;
    if (!is_array($p)){ mc_fail('invalid_policy'); break; }
    mc_store_set($uid, 'sync_policy', $p);
    mc_ok(['saved'=>true]);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
