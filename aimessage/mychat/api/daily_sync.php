<?php
/**
 * /aimessage/mychat/api/daily_sync.php
 *  Actions: generate_today, list, save_day
 */
declare(strict_types=1);
require __DIR__.'/_helper.php';
$ctx = mc_init(); $uid = $ctx['uid']; $action = $ctx['action']; $body = $ctx['body'];

$KEY='daily_sync';
$days = mc_store_get($uid, $KEY, []);

switch ($action) {
  case 'generate_today':
    // 실제: 사용자 BYO 키로 LLM 호출하여 그날 신호 압축
    mc_ok([
      'ok'=>false, 'error'=>'not_implemented',
      'hint'=>'프론트가 inbox/pool로 자체 요약 생성 중. 운영 단계에서는 cron이 매일 자정 5분 전 호출해 RAG요약 → 저장.'
    ]);
    break;
  case 'list':
    mc_ok(['items'=>$days]);
    break;
  case 'save_day':
    $rec = $body['record'] ?? [];
    if (!is_array($rec) || empty($rec['date'])){ mc_fail('invalid'); break; }
    $rec['ts'] = $rec['ts'] ?? (time()*1000);
    $i = -1;
    foreach ($days as $idx=>$d){ if (($d['date'] ?? '') === $rec['date']) { $i = $idx; break; } }
    if ($i>=0) $days[$i] = $rec; else array_unshift($days, $rec);
    if (count($days) > 400) $days = array_slice($days, 0, 400);
    mc_store_set($uid, $KEY, $days);
    mc_ok(['saved'=>$rec]);
    break;
  default:
    mc_fail('unknown_action', ['received'=>$action]);
}
