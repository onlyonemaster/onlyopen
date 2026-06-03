<?php
/**
 * /aimessage/mychat/api/inbox.php
 *  Actions: list, add, update, remove, clear
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

$KEY = 'inbox';
$list = mc_store_get($uid, $KEY, []);

switch ($action) {
  case 'list':
    $limit = (int)($body['limit'] ?? 200);
    mc_ok(['items'=> array_slice($list, 0, $limit)]);
    break;
  case 'add':
    $item = $body['item'] ?? [];
    if (!is_array($item)) { mc_fail('invalid_item'); break; }
    $item['id'] = $item['id'] ?? ('i_'.bin2hex(random_bytes(4)));
    $item['ts'] = $item['ts'] ?? (time()*1000);
    array_unshift($list, $item);
    if (count($list) > 2000) $list = array_slice($list, 0, 2000);
    mc_store_set($uid, $KEY, $list);
    mc_ok(['item'=>$item]);
    break;
  case 'update':
    $id = $body['id'] ?? '';
    $patch = $body['patch'] ?? [];
    $found = null;
    foreach ($list as &$it){
      if (($it['id'] ?? '') === $id){
        foreach ($patch as $k=>$v) $it[$k] = $v;
        $found = $it;
        break;
      }
    }
    unset($it);
    if (!$found){ mc_fail('not_found'); break; }
    mc_store_set($uid, $KEY, $list);
    mc_ok(['item'=>$found]);
    break;
  case 'remove':
    $id = $body['id'] ?? '';
    $out = array_values(array_filter($list, function($it) use ($id){ return ($it['id'] ?? '') !== $id; }));
    mc_store_set($uid, $KEY, $out);
    mc_ok(['removed'=>true]);
    break;
  case 'clear':
    mc_store_set($uid, $KEY, []);
    mc_ok(['cleared'=>true]);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
