<?php
/**
 * /aimessage/mychat/api/anomaly.php
 *  Actions: scan, list, ack
 *  - 실제 룰엔진은 cron으로 분당/시간당 돌고, 여기서는 list/ack/scan 트리거.
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

$KEY='anomaly';
$list = mc_store_get($uid, $KEY, []);

switch ($action) {
  case 'list':
    mc_ok(['items'=>$list]);
    break;
  case 'scan':
    // 실제: 룰엔진 호출 — 여기서는 데모로 50% 확률 1건 추가
    if (mt_rand(0,1) === 0) {
      $alert = [
        'id'=>'a_'.bin2hex(random_bytes(4)),
        'ts'=>time()*1000,
        'kind'=>'mood_low',
        'severity'=>'info',
        'title'=>'정서 톤 약간 하강',
        'detail'=>'최근 3일 일일 요약에서 부정 어휘 비율이 미세 상승. 휴식 권장.',
        'ack'=>false
      ];
      array_unshift($list, $alert);
      mc_store_set($uid, $KEY, $list);
      mc_ok(['alerts'=>[$alert], 'scanned_at'=>time()]);
    } else {
      mc_ok(['alerts'=>[], 'scanned_at'=>time()]);
    }
    break;
  case 'ack':
    $id = $body['id'] ?? '';
    foreach ($list as &$it){
      if (($it['id'] ?? '') === $id){ $it['ack'] = true; break; }
    }
    unset($it);
    mc_store_set($uid, $KEY, $list);
    mc_ok(['acked'=>$id]);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
