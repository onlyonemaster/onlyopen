<?php
/**
 * /aimessage/mychat/api/legacy.php
 *  Actions: load, save, ping_alive
 *  - 실서버에서는 발송/암호화 워커 분리. 여기서는 정책 저장만.
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

$KEY='legacy';

switch ($action) {
  case 'load':
    mc_ok(['legacy'=> mc_store_get($uid, $KEY, null)]);
    break;
  case 'save':
    $l = $body['legacy'] ?? null;
    if (!is_array($l)){ mc_fail('invalid'); break; }
    mc_store_set($uid, $KEY, $l);
    mc_ok(['saved'=>true]);
    break;
  case 'ping_alive':
    $l = mc_store_get($uid, $KEY, []);
    $l['last_alive'] = time()*1000;
    mc_store_set($uid, $KEY, $l);
    mc_ok(['last_alive'=>$l['last_alive']]);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
