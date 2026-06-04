<?php
/**
 * /aimessage/mychat/api/decisions.php
 *  Actions: list, add, update, predict
 *  - "predict" 는 실제로는 BYO API 키 + 사용자 과거 데이터로 RAG 추론을 해야 하지만,
 *    여기서는 데모용 휴리스틱(현재는 ok:false 폴백 응답하여 프론트의 로컬 휴리스틱 사용)
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

$KEY = 'decisions';
$list = mc_store_get($uid, $KEY, []);

switch ($action) {
  case 'list':
    mc_ok(['items'=>$list]);
    break;
  case 'add':
    $rec = $body['record'] ?? [];
    if (!is_array($rec)) { mc_fail('invalid'); break; }
    $rec['id'] = $rec['id'] ?? ('d_'.bin2hex(random_bytes(4)));
    $rec['ts'] = $rec['ts'] ?? (time()*1000);
    array_unshift($list, $rec);
    if (count($list) > 1000) $list = array_slice($list, 0, 1000);
    mc_store_set($uid, $KEY, $list);
    mc_ok(['item'=>$rec]);
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
  case 'predict':
    // 실제 서비스: 사용자 BYO 키로 LLM 호출 + 과거 결정/회고 RAG
    mc_ok(['ok'=>false, 'error'=>'not_implemented',
           'hint'=>'프론트의 localPredict() 휴리스틱 사용 중. RAG+LLM 연결 시 여기서 예측 배열을 반환']);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
