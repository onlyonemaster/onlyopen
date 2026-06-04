<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<style>
.sys-dash{padding:15px 20px}.sys-dash h3{margin:0 0 8px;font-size:18px;color:#333}.sys-dash h3 i{color:#6366f1;margin-right:6px}
.sys-kpi-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.sys-kpi-card{flex:1;min-width:130px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;transition:transform .15s}
.sys-kpi-card:hover{transform:translateY(-2px)}
.sys-kpi-card .kpi-val{font-size:26px;font-weight:800;color:#1e293b;margin:6px 0}
.sys-kpi-card .kpi-label{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sys-kpi-card .kpi-sub{font-size:12px;margin-top:4px}.sys-kpi-card .kpi-sub.warn{color:#f59e0b}.sys-kpi-card .kpi-sub.up{color:#10b981}
.sys-kpi-icon{font-size:22px;margin-bottom:4px}
.sys-chart-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px}
.sys-chart-box{flex:1;min-width:360px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.sys-chart-box h4{margin:0 0 10px;font-size:14px;color:#64748b}
.sys-chart-canvas-wrap{position:relative;height:260px}
.sys-chart-canvas-wrap canvas{width:100%!important;height:100%!important}
.sys-insight-box{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px}
.sys-insight-item{padding:8px 0;font-size:13px;color:#334155;line-height:1.7;border-bottom:1px solid #f1f5f9}
.sys-insight-item:last-child{border-bottom:none}.sys-insight-item.danger{border-left:3px solid #ef4444;padding-left:12px;background:#fef2f2}.sys-insight-item.warning{border-left:3px solid #f59e0b;padding-left:12px;background:#fffbeb}.sys-insight-item.info{border-left:3px solid #3b82f6;padding-left:12px;background:#eff6ff}
.sys-health-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.sys-health-chip{background:#dcfce7;color:#166534;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}.sys-health-chip.fail{background:#fee2e2;color:#991b1b}.sys-health-chip.warn{background:#fef3c7;color:#92400e}
.sys-queue-table{width:100%;border-collapse:collapse;font-size:13px}.sys-queue-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}.sys-queue-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.urgency-3{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}.urgency-2{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}.urgency-1{background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:11px}
@keyframes sp{0%,100%{opacity:1}50%{opacity:.6}}.live-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:6px;animation:sp 1.5s ease-in-out infinite}
</style>
<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>
<div class="content-wrapper">
<section class="content-header"><h1>원챗시스템 대시보드 <small>AI 기반 실시간 모니터링</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol></section>
<section class="content"><div class="sys-dash">
<div class="sys-health-row" id="healthBar"><span class="sys-health-chip"><span class="live-dot"></span>시스템 점검 중...</span></div>
<div class="sys-kpi-row" id="kpiRow">
<div class="sys-kpi-card"><div class="sys-kpi-icon">🤖</div><div class="kpi-label">활성 챗봇</div><div class="kpi-val" id="kpiBots">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">👥</div><div class="kpi-label">총 연락처</div><div class="kpi-val" id="kpiContacts">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">💬</div><div class="kpi-label">응답률</div><div class="kpi-val" id="kpiRespRate">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">⏳</div><div class="kpi-label">대기열</div><div class="kpi-val" id="kpiQueue">-</div><div class="kpi-sub" id="kpiUrgent"></div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">🧠</div><div class="kpi-label">AI 자동 처리율</div><div class="kpi-val" id="kpiAuto">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">🎯</div><div class="kpi-label">활성 시나리오</div><div class="kpi-val" id="kpiScenarios">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">📱</div><div class="kpi-label">오늘 활성 DM</div><div class="kpi-val" id="kpiDm">-</div></div>
<div class="sys-kpi-card"><div class="sys-kpi-icon">🆕</div><div class="kpi-label">오늘 신규 회원</div><div class="kpi-val" id="kpiNewSoft">-</div></div>
</div>
<div class="sys-chart-row">
<div class="sys-chart-box"><h4>📊 통합 대화 트렌드</h4><div class="sys-chart-canvas-wrap"><canvas id="unifiedChatChart"></canvas></div></div>
<div class="sys-chart-box"><h4>🎭 의도 분포</h4><div class="sys-chart-canvas-wrap"><canvas id="intentChart"></canvas></div></div>
</div>
<div class="sys-chart-row">
<div class="sys-insight-box" style="flex:1;min-width:340px"><h4>🤖 AI 인사이트 리포트</h4><div id="aiInsightReport" style="color:#94a3b8">데이터를 불러오는 중입니다...</div></div>
<div class="sys-chart-box" style="flex:1.5;min-width:400px"><h4>🔴 긴급 대기열 (상위 10건)</h4>
<table class="sys-queue-table"><thead><tr><th>고객</th><th>회원</th><th>마지막 메시지</th><th>의도</th><th>긴급도</th><th>시간</th></tr></thead>
<tbody id="urgentQueueBody"><tr><td colspan="6" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table></div>
</div>
</div></section></div>
<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function f(n){return n.toLocaleString('ko-KR');}

function jurl(url,opt){
  opt=opt||{};
  opt.credentials='include';
  return fetch(url,opt).then(function(r){return r.json();});
}

function rKPI(d){
  document.getElementById('kpiBots').textContent=f(d.active_bots)+'개';
  document.getElementById('kpiContacts').textContent=f(d.total_contacts)+'건';
  document.getElementById('kpiRespRate').textContent=d.response_rate+'%';
  document.getElementById('kpiQueue').textContent=f(d.queue_pending)+'건';
  var e=document.getElementById('kpiUrgent');
  if(d.queue_urgent>0){e.className='kpi-sub warn';e.textContent='긴급 '+d.queue_urgent+'건';}
  else{e.className='kpi-sub up';e.textContent='긴급 없음';}
  document.getElementById('kpiAuto').textContent=d.ai_auto_rate+'%';
  document.getElementById('kpiScenarios').textContent=f(d.active_scenarios)+'개';
  document.getElementById('kpiDm').textContent=f(d.active_dm_today)+'건';
  document.getElementById('kpiNewSoft').textContent=f(d.new_soft_today)+'명';
}

var ci=null;
function rUC(d){
  var ctx=document.getElementById('unifiedChatChart').getContext('2d');
  if(ci)ci.destroy();
  var m={};
  (d.chat_daily||[]).forEach(function(r){m[r.day]=(m[r.day]||0)+r.count;});
  (d.broadcast_daily||[]).forEach(function(r){m[r.day]=(m[r.day]||0)+r.count;});
  var l=Object.keys(m).sort();
  if(!l.length){ci=null;return;}
  ci=new Chart(ctx,{
    type:'bar',
    data:{labels:l,datasets:[{label:'통합 대화',data:l.map(function(k){return m[k];}),backgroundColor:'rgba(99,102,241,0.7)',borderColor:'#6366f1',borderWidth:1,borderRadius:4}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:function(v){return f(v)+'건';}},beginAtZero:true}}}
  });
}

var ii=null;
function rIC(an){
  var ctx=document.getElementById('intentChart').getContext('2d');
  if(ii)ii.destroy();
  var ints=an.intents||[];
  if(!ints.length){ii=null;return;}
  var cols=['#6366f1','#10b981','#f97316','#8b5cf6','#ef4444','#f59e0b','#06b6d4','#ec4899'];
  ii=new Chart(ctx,{
    type:'doughnut',
    data:{labels:ints.map(function(d){return d.label||'기타';}),datasets:[{data:ints.map(function(d){return d.count;}),backgroundColor:cols.slice(0,ints.length),borderWidth:2,borderColor:'#fff'}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{font:{size:12},padding:12}}}}
  });
}

function rIR(rp){
  var ins=rp.insights||[];
  var h='';
  if(!ins.length)h='<div class="sys-insight-item info">모든 시스템이 정상입니다.</div>';
  else ins.forEach(function(i){
    var cl=i.level==='danger'?'danger':(i.level==='warning'?'warning':'info');
    h+='<div class="sys-insight-item '+cl+'">'+i.icon+' '+i.msg+'</div>';
  });
  h+='<div class="sys-insight-item" style="color:#94a3b8;font-size:11px">갱신 시각: '+(rp.generated_at||'-')+'</div>';
  document.getElementById('aiInsightReport').innerHTML=h;
}

function rUQ(list){
  var h='';
  list.forEach(function(i){
    var uh='';
    if(i.urgency>=3)uh='<span class="urgency-3">긴급</span>';
    else if(i.urgency>=2)uh='<span class="urgency-2">주의</span>';
    else uh='<span class="urgency-1">일반</span>';
    h+='<tr><td>'+(i.visitor_name||'익명')+'</td><td>'+(i.member_name||'-')+'</td><td>'+(i.last_msg||'-')+'</td><td>'+(i.intent||'-')+'</td><td>'+uh+'</td><td>'+(i.created_at||'').substring(11,16)+'</td></tr>';
  });
  if(!h)h='<tr><td colspan="6">대기 중인 항목이 없습니다.</td></tr>';
  document.getElementById('urgentQueueBody').innerHTML=h;
}

function rHB(he){
  var cs=[];
  (he.checks||[]).forEach(function(c){
    var cl=c.status==='FAIL'?'fail':(c.status.indexOf('WARN')===0?'warn':'');
    cs.push('<span class="sys-health-chip '+cl+'">'+c.name+': '+c.status+'</span>');
  });
  document.getElementById('healthBar').innerHTML=cs.join('');
}

function L(){
  jurl(A+'?action=system_kpi').then(function(d){if(d.ok)rKPI(d);});
  jurl(A+'?action=unified_chat_analysis&period=30').then(function(d){if(d.ok)rUC(d);});
  jurl(A+'?action=ai_analytics&period=30').then(function(d){if(d.ok)rIC(d);});
  var p1=jurl(A+'?action=ai_insight_report');
  var p2=jurl(A+'?action=queue_list&urgency=2&page=1');
  var p3=jurl(A+'?action=ai_health_check');
  Promise.all([p1,p2,p3]).then(function(rs){
    if(rs[0]&&rs[0].ok)rIR(rs[0]);
    if(rs[1]&&rs[1].ok)rUQ(rs[1].list||[]);
    if(rs[2]&&rs[2].ok)rHB(rs[2]);
  });
}

L();
setInterval(L,60000);
})();
</script></div></div>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
