<?php
header('Content-Type: application/json; charset=utf-8');
set_time_limit(15);
ini_set('mysql.connect_timeout', 5);
include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';
if ($self_con) { $mv = mysqli_get_server_info($self_con); if (version_compare($mv,'8.0','>=')) mysqli_query($self_con,"SET SESSION max_execution_time=10000"); mysqli_query($self_con,"SET SESSION wait_timeout=10"); }
$is_admin = false;
if (!empty($_SESSION['one_member_admin_id'])) $is_admin = true;
elseif (in_array($_SESSION['one_member_id']??'',['obmms01','obmms02','db','sungmheo','lecturem'])) $is_admin = true;
elseif (!empty($_SESSION['one_member_subadmin_id'])) $is_admin = true;
if (!$is_admin) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'관리자 권한 필요'],JSON_UNESCAPED_UNICODE); exit; }
$action = trim($_GET['action']??'');
$method = $_SERVER['REQUEST_METHOD'];
switch ($action) {
case 'system_kpi': echo json_encode(getSystemKPI(),JSON_UNESCAPED_UNICODE); break;
case 'queue_list': echo json_encode(getQueueList(),JSON_UNESCAPED_UNICODE); break;
case 'queue_assign': if($method!=='POST'){http_response_code(405);exit;} echo json_encode(assignQueue($_POST),JSON_UNESCAPED_UNICODE); break;
case 'queue_action': if($method!=='POST'){http_response_code(405);exit;} echo json_encode(recordAction($_POST),JSON_UNESCAPED_UNICODE); break;
case 'bot_list': echo json_encode(getBotList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'bot_detail': echo json_encode(getBotDetail($_GET),JSON_UNESCAPED_UNICODE); break;
case 'bot_update': if($method!=='POST'){http_response_code(405);exit;} echo json_encode(updateBot($_POST),JSON_UNESCAPED_UNICODE); break;
case 'bot_bulk': if($method!=='POST'){http_response_code(405);exit;} echo json_encode(bulkUpdateBot($_POST),JSON_UNESCAPED_UNICODE); break;
case 'scenario_list': echo json_encode(getScenarioList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'scenario_stats': echo json_encode(getScenarioStats($_GET),JSON_UNESCAPED_UNICODE); break;
case 'chat_log': echo json_encode(getChatLog($_GET),JSON_UNESCAPED_UNICODE); break;
case 'broadcast_list': echo json_encode(getBroadcastList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'contact_list': echo json_encode(getContactList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'soft_member_list': echo json_encode(getSoftMemberList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'shared_link_list': echo json_encode(getSharedLinkList($_GET),JSON_UNESCAPED_UNICODE); break;
case 'ai_analytics': echo json_encode(getAIAnalytics($_GET),JSON_UNESCAPED_UNICODE); break;
case 'ai_insight_report': echo json_encode(getAIInsightReport(),JSON_UNESCAPED_UNICODE); break;
case 'companion_stats': echo json_encode(getCompanionStats($_GET),JSON_UNESCAPED_UNICODE); break;
case 'unified_chat_analysis': echo json_encode(getUnifiedChatAnalysis($_GET),JSON_UNESCAPED_UNICODE); break;
case 'ai_auto_resolve': if($method!=='POST'){http_response_code(405);exit;} echo json_encode(aiAutoResolve($_POST),JSON_UNESCAPED_UNICODE); break;
case 'ai_health_check': echo json_encode(aiHealthCheck(),JSON_UNESCAPED_UNICODE); break;
default: http_response_code(400); echo json_encode(['ok'=>false,'error'=>'알 수 없는 action: '.$action],JSON_UNESCAPED_UNICODE);
}
function getSystemKPI() { global $self_con; $data=['ok'=>true];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_settings WHERE bot_on=1")); $data['active_bots']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_aievent_request")); $data['total_contacts']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(DISTINCT r.request_idx) AS chatted FROM Gn_aievent_request r JOIN Gn_aievent_ms_info s ON s.sms_idx=r.sms_idx JOIN Gn_chatbot_history h ON h.request_idx=r.request_idx")); $tc=(int)($r['chatted']??0);
  $r2=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_aievent_request r JOIN Gn_aievent_ms_info s ON s.sms_idx=r.sms_idx")); $ts=(int)($r2['cnt']??0);
  $data['response_rate']=$ts>0?round($tc/$ts*100,1):0;
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_queue WHERE queue_status='pending'")); $data['queue_pending']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_queue WHERE queue_status='pending' AND urgency>=3")); $data['queue_urgent']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total, SUM(CASE WHEN urgency=0 THEN 1 ELSE 0 END) AS auto FROM Gn_onechat_analysis")); $ta=(int)($r['total']??0); $ar=(int)($r['auto']??0); $data['ai_auto_rate']=$ta>0?round($ar/$ta*100,1):0;
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_soft_members WHERE DATE(created_at)=CURDATE()")); $data['new_soft_today']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(DISTINCT request_idx) AS cnt FROM Gn_chatbot_history WHERE DATE(created_at)=CURDATE()")); $data['active_dm_today']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_scenario WHERE status='active'")); $data['active_scenarios']=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_Member WHERE service_type IN ('basic','standard','pro','business','b2b-biz','b2b-pro','b2b-team')")); $data['paid_subscribers']=(int)($r['cnt']??0);
  return $data;
}
function getQueueList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp; $ug=isset($_GET['urgency'])?(int)$_GET['urgency']:-1;
  $where="q.queue_status='pending'"; if($ug>=0) $where.=" AND q.urgency>={$ug}";
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_onechat_queue q WHERE {$where}")); $total=(int)($cr['total']??0);
  $sql="SELECT q.*, m.mem_name, m.mem_nick FROM Gn_onechat_queue q LEFT JOIN Gn_aievent_request r ON r.request_idx=q.request_idx LEFT JOIN Gn_aievent_ms_info s ON s.sms_idx=q.sms_idx LEFT JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE {$where} ORDER BY q.urgency DESC, q.created_at ASC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'visitor_name'=>$row['visitor_name']??'익명','member_name'=>$row['mem_name']?:$row['mem_nick']?:'-','last_msg'=>mb_strimwidth($row['last_msg']??'',0,80,'...'),'intent'=>$row['intent']??'unknown','sentiment'=>$row['sentiment']??'neutral','urgency'=>(int)($row['urgency']??0),'issue_tags'=>$row['issue_tags']??'','assigned_to'=>$row['assigned_to']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function assignQueue($post) { global $self_con; $id=(int)($post['id']??0); $at=trim($post['assigned_to']??''); if(!$id||!$at) return ['ok'=>false,'error'=>'id와 assigned_to 필수'];
  $sa=mysqli_real_escape_string($self_con,$at); mysqli_query($self_con,"UPDATE Gn_onechat_queue SET assigned_to='{$sa}',updated_at=NOW() WHERE id={$id}"); return ['ok'=>true]; }
function recordAction($post) { global $self_con; $qi=(int)($post['queue_id']??0); $si=(int)($post['sms_idx']??0); $ri=(int)($post['request_idx']??0); $at=trim($post['action_type']??''); $an=trim($post['action_note']??''); $oi=trim($post['operator_id']??$_SESSION['one_member_id']??'admin');
  if(!$qi||!$at) return ['ok'=>false,'error'=>'필수값 누락']; $s=['qi'=>$qi,'si'=>$si,'ri'=>$ri,'oi'=>mysqli_real_escape_string($self_con,$oi),'at'=>mysqli_real_escape_string($self_con,$at),'an'=>mysqli_real_escape_string($self_con,$an)];
  mysqli_query($self_con,"INSERT INTO Gn_onechat_actions SET queue_id={$s['qi']},sms_idx={$s['si']},request_idx={$s['ri']},operator_id='{$s['oi']}',action_type='{$s['at']}',action_note='{$s['an']}',created_at=NOW()");
  $ns=($at==='resolve')?'resolved':'pending'; mysqli_query($self_con,"UPDATE Gn_onechat_queue SET queue_status='{$ns}',updated_at=NOW() WHERE id={$qi}"); return ['ok'=>true]; }
function getBotList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp; $sr=mysqli_real_escape_string($self_con,trim($_GET['keyword']??''));
  $where="1=1"; if($sr) $where.=" AND (m.mem_id LIKE '%{$sr}%' OR m.mem_name LIKE '%{$sr}%' OR m.mem_nick LIKE '%{$sr}%')";
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_onechat_settings os JOIN Gn_aievent_ms_info s ON s.sms_idx=os.sms_idx JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE {$where}")); $total=(int)($cr['total']??0);
  $sql="SELECT os.*, s.customer_id, m.mem_name, m.mem_nick, m.service_type, m.ai_profile_status FROM Gn_onechat_settings os JOIN Gn_aievent_ms_info s ON s.sms_idx=os.sms_idx JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE {$where} ORDER BY os.updated_at DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'mem_id'=>$row['customer_id'],'mem_name'=>$row['mem_name']?:$row['mem_nick']?:$row['customer_id'],'service_type'=>$row['service_type']??'free','bot_on'=>(int)($row['bot_on']??1),'prompt_mode'=>$row['prompt_mode']??'default','prompt_tone'=>$row['prompt_tone']??'','unread_count'=>(int)($row['unread_count']??0),'connect_request_count'=>(int)($row['connect_request_count']??0),'ai_profile_status'=>$row['ai_profile_status']??'','updated_at'=>$row['updated_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getBotDetail() { global $self_con; $mid=mysqli_real_escape_string($self_con,trim($_GET['mem_id']??'')); if(!$mid) return ['ok'=>false,'error'=>'mem_id 필수'];
  $sql="SELECT os.*, m.mem_name, m.mem_nick, m.chatbot_name, m.chatbot_description, m.service_type, m.ai_profile_status, m.ai_profile_limit, m.ai_profile_used, m.ai_msg_person_limit, m.ai_msg_person_used, m.ai_resp_limit, m.ai_resp_used, m.bot_count, m.sub_end_date FROM Gn_onechat_settings os JOIN Gn_aievent_ms_info s ON s.sms_idx=os.sms_idx JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE s.customer_id='{$mid}' LIMIT 1";
  $res=mysqli_query($self_con,$sql); $row=mysqli_fetch_assoc($res); if(!$row) return ['ok'=>false,'error'=>'설정 없음'];
  return ['ok'=>true,'data'=>['mem_id'=>$mid,'mem_name'=>$row['mem_name']?:$row['mem_nick']?:$mid,'bot_on'=>(int)($row['bot_on']??1),'prompt_mode'=>$row['prompt_mode']??'default','prompt_tone'=>$row['prompt_tone']??'','prompt_rel'=>$row['prompt_rel']??'','prompt_purp'=>$row['prompt_purp']??'','prompt_text'=>$row['prompt_text']??'','chatbot_name'=>$row['chatbot_name']??'','chatbot_description'=>$row['chatbot_description']??'','service_type'=>$row['service_type']??'free','ai_profile_status'=>$row['ai_profile_status']??'','ai_profile_limit'=>(int)($row['ai_profile_limit']??0),'ai_profile_used'=>(int)($row['ai_profile_used']??0),'ai_msg_limit'=>(int)($row['ai_msg_person_limit']??0),'ai_msg_used'=>(int)($row['ai_msg_person_used']??0),'ai_resp_limit'=>(int)($row['ai_resp_limit']??0),'ai_resp_used'=>(int)($row['ai_resp_used']??0),'bot_count'=>(int)($row['bot_count']??0),'sub_end_date'=>$row['sub_end_date']??'']];
}
function updateBot($post) { global $self_con; $mid=mysqli_real_escape_string($self_con,trim($post['mem_id']??'')); if(!$mid) return ['ok'=>false,'error'=>'mem_id 필수'];
  $up=[]; if(isset($post['bot_on'])) $up[]="bot_on=".(int)$post['bot_on']; if(isset($post['prompt_mode'])) $up[]="prompt_mode='".mysqli_real_escape_string($self_con,$post['prompt_mode'])."'"; if(isset($post['prompt_tone'])) $up[]="prompt_tone='".mysqli_real_escape_string($self_con,$post['prompt_tone'])."'"; if(isset($post['prompt_rel'])) $up[]="prompt_rel='".mysqli_real_escape_string($self_con,$post['prompt_rel'])."'"; if(isset($post['prompt_purp'])) $up[]="prompt_purp='".mysqli_real_escape_string($self_con,$post['prompt_purp'])."'"; if(isset($post['prompt_text'])) $up[]="prompt_text='".mysqli_real_escape_string($self_con,$post['prompt_text'])."'";
  if(empty($up)) return ['ok'=>false,'error'=>'업데이트할 필드 없음']; $smsRes=mysqli_query($self_con,"SELECT sms_idx FROM Gn_aievent_ms_info WHERE customer_id='{$mid}' LIMIT 1"); $smsRow=mysqli_fetch_assoc($smsRes); if(!$smsRow) return ['ok'=>false,'error'=>'회원 챗봇 없음'];
  $sms_idx=(int)$smsRow['sms_idx']; $ss=implode(', ',$up); mysqli_query($self_con,"UPDATE Gn_onechat_settings SET {$ss}, updated_at=NOW() WHERE sms_idx={$sms_idx}"); return ['ok'=>true]; }
function bulkUpdateBot($post) { global $self_con; $mids=$post['mem_ids']??[]; $bo=isset($post['bot_on'])?(int)$post['bot_on']:null; $pm=trim($post['prompt_mode']??'');
  if(empty($mids)) return ['ok'=>false,'error'=>'mem_ids 필수']; if($bo===null&&!$pm) return ['ok'=>false,'error'=>'변경할 값 없음']; $updated=0;
  foreach($mids as $mid) { $si=mysqli_real_escape_string($self_con,$mid); $sr=mysqli_query($self_con,"SELECT sms_idx FROM Gn_aievent_ms_info WHERE customer_id='{$si}' LIMIT 1"); $sR=mysqli_fetch_assoc($sr); if(!$sR) continue;
    $sm=(int)$sR['sms_idx']; $up=[]; if($bo!==null) $up[]="bot_on={$bo}"; if($pm) $up[]="prompt_mode='".mysqli_real_escape_string($self_con,$pm)."'"; if(!empty($up)) { $ss=implode(', ',$up); mysqli_query($self_con,"UPDATE Gn_onechat_settings SET {$ss}, updated_at=NOW() WHERE sms_idx={$sm}"); $updated++; } }
  return ['ok'=>true,'updated'=>$updated]; }
function getScenarioList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp;
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_scenario")); $total=(int)($cr['total']??0);
  $sql="SELECT sc.*, s.customer_id, m.mem_name, m.mem_nick, (SELECT COUNT(*) FROM Gn_scenario_context WHERE scenario_id=sc.id) AS context_count, (SELECT COUNT(*) FROM Gn_scenario_log WHERE scenario_id=sc.id AND event_type='convert') AS convert_count FROM Gn_scenario sc JOIN Gn_aievent_ms_info s ON s.sms_idx=sc.sms_idx JOIN Gn_Member m ON m.mem_id=s.customer_id ORDER BY sc.updated_at DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'title'=>$row['title']??'','status'=>$row['status']??'draft','mem_id'=>$row['customer_id'],'mem_name'=>$row['mem_name']?:$row['mem_nick']?:'-','step_confirmed'=>(int)($row['step_confirmed']??0),'context_count'=>(int)($row['context_count']??0),'convert_count'=>(int)($row['convert_count']??0),'created_at'=>$row['created_at']??'','activated_at'=>$row['activated_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getScenarioStats() { global $self_con; $sid=(int)($_GET['scenario_id']??0); if(!$sid) return ['ok'=>false,'error'=>'scenario_id 필수'];
  $cr=mysqli_query($self_con,"SELECT * FROM Gn_scenario_context WHERE scenario_id={$sid}"); $ctx=[]; while($row=mysqli_fetch_assoc($cr)) $ctx[]=$row;
  $lr=mysqli_query($self_con,"SELECT event_type, COUNT(*) AS cnt FROM Gn_scenario_log WHERE scenario_id={$sid} GROUP BY event_type"); $ev=[]; while($row=mysqli_fetch_assoc($lr)) $ev[]=$row;
  return ['ok'=>true,'scenario_id'=>$sid,'contexts'=>$ctx,'events'=>$ev]; }
function getChatLog() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp; $kw=mysqli_real_escape_string($self_con,trim($_GET['keyword']??'')); $mid=mysqli_real_escape_string($self_con,trim($_GET['mem_id']??''));
  $where="1=1"; if($kw) $where.=" AND (h.visitor_name LIKE '%{$kw}%' OR h.user_message LIKE '%{$kw}%' OR h.ai_response LIKE '%{$kw}%')"; if($mid) $where.=" AND s.customer_id='{$mid}'";
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_chatbot_history h JOIN Gn_aievent_ms_info s ON s.sms_idx=h.sms_idx WHERE {$where}")); $total=(int)($cr['total']??0);
  $sql="SELECT h.*, s.customer_id, m.mem_name, m.mem_nick FROM Gn_chatbot_history h JOIN Gn_aievent_ms_info s ON s.sms_idx=h.sms_idx LEFT JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE {$where} ORDER BY h.created_at DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'mem_id'=>$row['customer_id'],'mem_name'=>$row['mem_name']?:$row['mem_nick']?:'-','visitor_name'=>$row['visitor_name']??'익명','user_message'=>mb_strimwidth($row['user_message']??'',0,100,'...'),'ai_response'=>mb_strimwidth($row['ai_response']??'',0,100,'...'),'intent'=>$row['intent']??'','sentiment'=>$row['sentiment']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getBroadcastList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp;
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_onechat_broadcast")); $total=(int)($cr['total']??0);
  $sql="SELECT b.*, m.mem_name, m.mem_nick FROM Gn_onechat_broadcast b LEFT JOIN Gn_Member m ON m.mem_id=b.operator_id ORDER BY b.created_at DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'operator_name'=>$row['mem_name']?:$row['mem_nick']?:$row['operator_id'],'title'=>$row['title']??'','target_type'=>$row['target_type']??'','sent_count'=>(int)($row['sent_count']??0),'read_count'=>(int)($row['read_count']??0),'reply_count'=>(int)($row['reply_count']??0),'status'=>$row['status']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getContactList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp; $kw=mysqli_real_escape_string($self_con,trim($_GET['keyword']??''));
  $where="1=1"; if($kw) $where.=" AND (r.receiver_name LIKE '%{$kw}%' OR r.receiver_phone LIKE '%{$kw}%' OR s.customer_id LIKE '%{$kw}%')";
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_aievent_request r JOIN Gn_aievent_ms_info s ON s.sms_idx=r.sms_idx WHERE {$where}")); $total=(int)($cr['total']??0);
  $sql="SELECT r.*, s.customer_id, m.mem_name, m.mem_nick FROM Gn_aievent_request r JOIN Gn_aievent_ms_info s ON s.sms_idx=r.sms_idx LEFT JOIN Gn_Member m ON m.mem_id=s.customer_id WHERE {$where} ORDER BY r.request_idx DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['request_idx'=>(int)$row['request_idx'],'sms_idx'=>(int)$row['sms_idx'],'mem_id'=>$row['customer_id'],'mem_name'=>$row['mem_name']?:$row['mem_nick']?:'-','receiver_name'=>$row['receiver_name']??'','receiver_phone'=>$row['receiver_phone']??'','ai_status'=>$row['ai_status']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getSoftMemberList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp;
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_onechat_soft_members")); $total=(int)($cr['total']??0);
  $sql="SELECT * FROM Gn_onechat_soft_members ORDER BY created_at DESC LIMIT {$off}, {$pp}"; $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'oc_id'=>$row['oc_id']??'','nickname'=>$row['nickname']??'','phone'=>$row['phone']??'','pwa_installed'=>(int)($row['pwa_installed']??0),'upgraded_to'=>$row['upgraded_to']??'','last_seen_at'=>$row['last_seen_at']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getSharedLinkList() { global $self_con; $page=max(1,(int)($_GET['page']??1)); $pp=25; $off=($page-1)*$pp;
  $cr=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total FROM Gn_onechat_shared_link")); $total=(int)($cr['total']??0);
  $sql="SELECT l.*, s.customer_id, m.mem_name, m.mem_nick FROM Gn_onechat_shared_link l JOIN Gn_aievent_ms_info s ON s.sms_idx=l.sms_idx LEFT JOIN Gn_Member m ON m.mem_id=s.customer_id ORDER BY l.created_at DESC LIMIT {$off}, {$pp}";
  $res=mysqli_query($self_con,$sql); $list=[];
  while($row=mysqli_fetch_assoc($res)) $list[]=['id'=>(int)$row['id'],'mem_id'=>$row['customer_id'],'mem_name'=>$row['mem_name']?:$row['mem_nick']?:'-','short_code'=>$row['short_code']??'','chatbot_name'=>$row['chatbot_name']??'','created_at'=>$row['created_at']??''];
  return ['ok'=>true,'total'=>$total,'page'=>$page,'per_page'=>$pp,'list'=>$list];
}
function getAIAnalytics() { global $self_con; $pd=max(1,min(90,(int)($_GET['period']??30)));
  $ir=mysqli_query($self_con,"SELECT intent, COUNT(*) AS cnt FROM Gn_onechat_analysis WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) GROUP BY intent ORDER BY cnt DESC"); $intents=[]; while($row=mysqli_fetch_assoc($ir)) $intents[]=['label'=>$row['intent'],'count'=>(int)$row['cnt']];
  $sr=mysqli_query($self_con,"SELECT sentiment, COUNT(*) AS cnt FROM Gn_onechat_analysis WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) GROUP BY sentiment"); $sentiments=[]; while($row=mysqli_fetch_assoc($sr)) $sentiments[]=['label'=>$row['sentiment'],'count'=>(int)$row['cnt']];
  $kr=mysqli_query($self_con,"SELECT keywords FROM Gn_onechat_analysis WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) AND keywords IS NOT NULL"); $keywords=[]; while($row=mysqli_fetch_assoc($kr)) { $ps=explode(',',$row['keywords']); foreach($ps as $p) { $p=trim($p); if($p) $keywords[$p]=($keywords[$p]??0)+1; } } arsort($keywords); $tk=array_slice($keywords,0,20);
  $isr=mysqli_query($self_con,"SELECT issues FROM Gn_onechat_analysis WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) AND issues IS NOT NULL"); $issues=[]; while($row=mysqli_fetch_assoc($isr)) { $ps=explode(',',$row['issues']); foreach($ps as $p) { $p=trim($p); if($p) $issues[$p]=($issues[$p]??0)+1; } } arsort($issues); $ti=array_slice($issues,0,15);
  $dr=mysqli_query($self_con,"SELECT DATE(analyzed_at) AS day, COUNT(*) AS cnt FROM Gn_onechat_analysis WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) GROUP BY day ORDER BY day"); $daily=[]; while($row=mysqli_fetch_assoc($dr)) $daily[]=['day'=>$row['day'],'count'=>(int)$row['cnt']];
  return ['ok'=>true,'period'=>$pd,'intents'=>$intents,'sentiments'=>$sentiments,'keywords'=>$tk,'issues'=>$ti,'daily'=>$daily];
}
function getAIInsightReport() { global $self_con; $insights=[];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_queue WHERE queue_status='pending' AND urgency>=3")); if(($r['cnt']??0)>0) $insights[]=['level'=>'danger','icon'=>'🔴','msg'=>'긴급 대기열 '.$r['cnt'].'건 — 즉시 확인 필요'];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_Member WHERE sub_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) AND service_type NOT IN ('free','')")); if(($r['cnt']??0)>0) $insights[]=['level'=>'warning','icon'=>'🟡','msg'=>'7일 내 구독 만료 '.$r['cnt'].'명 — 갱신 독려 필요'];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total, SUM(CASE WHEN urgency=0 THEN 1 ELSE 0 END) AS auto FROM Gn_onechat_analysis")); $ar=$r['total']>0?round($r['auto']/$r['total']*100,1):0; $insights[]=['level'=>'info','icon'=>'🤖','msg'=>"AI 자동 처리율: {$ar}% — ".($ar>=70?'양호':'개선 필요')];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_scenario WHERE status='active'")); $insights[]=['level'=>'info','icon'=>'🎯','msg'=>'활성 시나리오 캠페인: '.($r['cnt']??0).'개'];
  return ['ok'=>true,'insights'=>$insights,'generated_at'=>date('Y-m-d H:i:s')];
}
function getCompanionStats() { global $self_con;
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS total, SUM(user_replied) AS replied, COUNT(CASE WHEN status='pending' THEN 1 END) AS pending, COUNT(CASE WHEN status='failed' THEN 1 END) AS failed FROM Gn_companion_schedule"));
  $dr=mysqli_query($self_con,"SELECT DATE(sent_at) AS day, COUNT(*) AS sent, SUM(user_replied) AS replied FROM Gn_companion_schedule WHERE status='sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY day ORDER BY day"); $daily=[]; while($row=mysqli_fetch_assoc($dr)) $daily[]=$row;
  return ['ok'=>true,'summary'=>$r,'daily'=>$daily];
}
function getUnifiedChatAnalysis() { global $self_con; $pd=max(1,min(90,(int)($_GET['period']??30)));
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_chatbot_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY)")); $cc=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_broadcast WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY)")); $bc=(int)($r['cnt']??0);
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_companion_schedule WHERE sent_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY)")); $cpc=(int)($r['cnt']??0);
  $ir=mysqli_query($self_con,"SELECT intent, COUNT(*) AS cnt FROM (SELECT intent FROM Gn_chatbot_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) UNION ALL SELECT intent FROM Gn_onechat_queue WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY)) AS unified GROUP BY intent ORDER BY cnt DESC"); $intents=[]; while($row=mysqli_fetch_assoc($ir)) $intents[]=$row;
  $dr1=mysqli_query($self_con,"SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM Gn_chatbot_history WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) GROUP BY day ORDER BY day"); $cd=[]; while($row=mysqli_fetch_assoc($dr1)) $cd[]=$row;
  $dr2=mysqli_query($self_con,"SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM Gn_onechat_broadcast WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$pd} DAY) GROUP BY day ORDER BY day"); $bd=[]; while($row=mysqli_fetch_assoc($dr2)) $bd[]=$row;
  return ['ok'=>true,'period'=>$pd,'chatbot_total'=>$cc,'broadcast_total'=>$bc,'companion_total'=>$cpc,'unified_intents'=>$intents,'chat_daily'=>$cd,'broadcast_daily'=>$bd];
}
function aiAutoResolve($post) { global $self_con; $qi=(int)($post['queue_id']??0); if(!$qi) return ['ok'=>false,'error'=>'queue_id 필수'];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT * FROM Gn_onechat_queue WHERE id={$qi} AND queue_status='pending'")); if(!$r) return ['ok'=>false,'error'=>'대기열 항목 없음 또는 이미 처리됨'];
  $an=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT * FROM Gn_onechat_analysis WHERE sms_idx={$r['sms_idx']} AND request_idx={$r['request_idx']} LIMIT 1"));
  $car=($r['urgency']==0)&&(($an['sentiment']??'neutral')==='positive');
  if($car) { mysqli_query($self_con,"UPDATE Gn_onechat_queue SET queue_status='resolved',updated_at=NOW() WHERE id={$qi}"); mysqli_query($self_con,"INSERT INTO Gn_onechat_actions (queue_id,sms_idx,request_idx,operator_id,action_type,action_note,created_at) VALUES ({$qi},{$r['sms_idx']},{$r['request_idx']},'AI_SYSTEM','auto_resolve','AI 자동 해결',NOW())"); return ['ok'=>true,'auto_resolved'=>true,'reason'=>'AI 판단: 긴급도 0 + 긍정 감정 → 자동 해결']; }
  return ['ok'=>true,'auto_resolved'=>false,'reason'=>'긴급도 '.$r['urgency'].', 감정 '.($an['sentiment']??'neutral').' → 관리자 확인 필요'];
}
function aiHealthCheck() { global $self_con; $checks=[];
  $checks[]=['name'=>'DB 연결','status'=>$self_con?'OK':'FAIL'];
  $tables=['Gn_onechat_queue','Gn_onechat_settings','Gn_onechat_analysis','Gn_chatbot_history','Gn_onechat_broadcast','Gn_companion_schedule'];
  foreach($tables as $t) { $r=mysqli_query($self_con,"SELECT 1 FROM {$t} LIMIT 1"); $checks[]=['name'=>"테이블: {$t}",'status'=>$r?'OK':'FAIL']; }
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_queue WHERE queue_status='pending' AND urgency>=3")); $checks[]=['name'=>'긴급 대기열','status'=>($r['cnt']??0)>0?'WARN: '.$r['cnt'].'건':'OK'];
  $r=mysqli_fetch_assoc(mysqli_query($self_con,"SELECT COUNT(*) AS cnt FROM Gn_onechat_queue WHERE queue_status='pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)")); $checks[]=['name'=>'30분 이상 미처리','status'=>($r['cnt']??0)>0?'WARN: '.$r['cnt'].'건':'OK'];
  return ['ok'=>true,'checks'=>$checks,'checked_at'=>date('Y-m-d H:i:s')];
}
