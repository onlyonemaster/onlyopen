<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.sm-wrap{padding:15px 20px}.sm-kpi-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.sm-kpi-card{flex:1;min-width:140px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center}
.sm-kpi-card .kpi-val{font-size:24px;font-weight:800;color:#1e293b;margin:6px 0}
.sm-kpi-card .kpi-label{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sm-kpi-icon{font-size:22px;margin-bottom:4px}
.sm-table{width:100%;border-collapse:collapse;font-size:13px}
.sm-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.sm-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.sm-status{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.sm-status.active{background:#dcfce7;color:#166534}.sm-status.draft{background:#f1f5f9;color:#64748b}
.toast{position:fixed;top:20px;right:20px;padding:10px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;opacity:0;transition:opacity .3s}
.toast.show{opacity:1}.toast.ok{background:#10b981}
</style>
<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>
<div class="content-wrapper">
<section class="content-header"><h1>시나리오 캠페인 관리 <small>AI 시나리오 현황 및 통계</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol></section>
<section class="content"><div class="sm-wrap">
<div class="sm-kpi-row" id="kpiRow">
<div class="sm-kpi-card"><div class="sm-kpi-icon">📋</div><div class="kpi-label">전체 시나리오</div><div class="kpi-val" id="kpiTotal">-</div></div>
<div class="sm-kpi-card"><div class="sm-kpi-icon">✅</div><div class="kpi-label">활성</div><div class="kpi-val" id="kpiActive">-</div></div>
<div class="sm-kpi-card"><div class="sm-kpi-icon">📝</div><div class="kpi-label">평균 컨텍스트</div><div class="kpi-val" id="kpiAvgCtx">-</div></div>
<div class="sm-kpi-card"><div class="sm-kpi-icon">🎯</div><div class="kpi-label">평균 전환</div><div class="kpi-val" id="kpiAvgConv">-</div></div>
</div>
<table class="sm-table"><thead><tr><th>시나리오명</th><th>회원</th><th>상태</th><th>단계</th><th>컨텍스트</th><th>전환</th><th>등록일</th><th>통계</th></tr></thead>
<tbody id="smBody"><tr><td colspan="8" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table>
<div id="smPager" style="margin-top:12px;text-align:center"></div>
</div></section></div></div>
<div id="toast" class="toast"></div>
<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function J(u,o){o=o||{};o.credentials='include';return fetch(u,o).then(function(r){return r.json();});}
function f(n){return n.toLocaleString('ko-KR');}
function rList(d){var list=d.list||[];var h='';list.forEach(function(r){h+='<tr><td>'+r.title+'</td><td>'+r.mem_name+'</td><td><span class="sm-status '+(r.status=='active'?'active':'draft')+'">'+(r.status=='active'?'활성':'임시')+'</span></td><td>'+r.step_confirmed+'</td><td>'+f(r.context_count)+'</td><td>'+f(r.convert_count)+'</td><td>'+r.created_at.substring(0,10)+'</td><td><button onclick="showStats('+r.id+')" style="padding:4px 12px;background:#8b5cf6;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px">통계</button></td></tr>';});
if(!h)h='<tr><td colspan="8">등록된 시나리오가 없습니다.</td></tr>';
document.getElementById('smBody').innerHTML=h;
var t=list.length,ac=list.filter(function(r){return r.status=='active';}).length;
var tc=list.reduce(function(s,r){return s+r.context_count;},0);
var cv=list.reduce(function(s,r){return s+r.convert_count;},0);
document.getElementById('kpiTotal').textContent=f(t)+'개';
document.getElementById('kpiActive').textContent=f(ac)+'개';
document.getElementById('kpiAvgCtx').textContent=(t>0?(tc/t).toFixed(1):'0')+'건';
document.getElementById('kpiAvgConv').textContent=(t>0?(cv/t).toFixed(1):'0')+'건';}
window.load=function(p){p=p||1;
J(A+'?action=scenario_list&page='+p).then(function(d){if(d.ok){rList(d);document.getElementById('smPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';}});};
window.showStats=function(sid){J(A+'?action=scenario_stats&scenario_id='+sid).then(function(d){if(!d.ok)return;
var ctxNames=(d.contexts||[]).map(function(c){return c.ctx_name||'-';}).join(', ');
var evNames=(d.events||[]).map(function(e){return e.event_type+'('+e.cnt+')';}).join(', ');
alert('컨텍스트: '+(ctxNames||'없음')+'\n이벤트: '+(evNames||'없음'));});};
load();
})();
</script>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
