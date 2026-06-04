<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.oq-wrap{padding:15px 20px}.oq-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
.oq-toolbar select,.oq-toolbar button{padding:6px 14px;border-radius:6px;font-size:13px;border:1px solid #e2e8f0;cursor:pointer}
.oq-toolbar .btn-ai{background:#6366f1;color:#fff;border:none;font-weight:600}
.oq-toolbar .btn-all{background:#10b981;color:#fff;border:none;font-weight:600}
.oq-table{width:100%;border-collapse:collapse;font-size:13px}
.oq-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.oq-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.u3{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}
.u2{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700}
.u1{background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:11px}
.u0{background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:10px;font-size:11px}
.sent-pos{color:#10b981;font-weight:600}.sent-neg{color:#ef4444;font-weight:600}.sent-neu{color:#94a3b8}
.oq-actions{display:flex;gap:6px}.oq-actions button{padding:3px 10px;border-radius:4px;font-size:11px;cursor:pointer;border:none;font-weight:600}
.oq-actions .btn-resolve{background:#dcfce7;color:#166534}.oq-actions .btn-escalate{background:#fee2e2;color:#991b1b}
.oq-actions .btn-ai{background:#ede9fe;color:#5b21b6}
.oq-note{width:100%;padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:12px;margin-top:4px;box-sizing:border-box}
.toast{position:fixed;top:20px;right:20px;padding:10px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;opacity:0;transition:opacity .3s}
.toast.show{opacity:1}.toast.ok{background:#10b981}.toast.err{background:#ef4444}
</style>
<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>
<div class="content-wrapper">
<section class="content-header"><h1>운영자 대기열 <small>실시간 고객 문의 관리</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol></section>
<section class="content"><div class="oq-wrap">
<div class="oq-toolbar">
<select id="oqFilter" onchange="load()"><option value="">전체 긴급도</option><option value="3">긴급(3)</option><option value="2">주의 이상(2+)</option><option value="1">일반 이상(1+)</option></select>
<button class="btn-ai" onclick="autoResolveAll()">🧠 AI 일괄 자동해결</button>
<button class="btn-all" onclick="load()">새로고침</button>
</div>
<table class="oq-table"><thead><tr><th>고객</th><th>회원</th><th>마지막 메시지</th><th>의도</th><th>감정</th><th>긴급도</th><th>시간</th><th>작업</th></tr></thead>
<tbody id="oqBody"><tr><td colspan="8" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table>
<div id="oqPager" style="margin-top:12px;text-align:center"></div>
</div></section></div></div>
<div id="toast" class="toast"></div>
<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function J(url,opt){opt=opt||{};opt.credentials='include';return fetch(url,opt).then(function(r){return r.json();});}
function T(m,c){var t=document.getElementById('toast');t.textContent=m;t.className='toast '+c+' show';setTimeout(function(){t.classList.remove('show');},3000);}
function ub(u){if(u>=3)return'<span class="u3">긴급</span>';if(u>=2)return'<span class="u2">주의</span>';if(u>=1)return'<span class="u1">일반</span>';return'<span class="u0">낮음</span>';}
function sb(s){if(s=='positive')return'<span class="sent-pos">긍정</span>';if(s=='negative')return'<span class="sent-neg">부정</span>';return'<span class="sent-neu">중립</span>';}
function rT(list){var h='';list.forEach(function(i){h+='<tr><td>'+(i.visitor_name||'익명')+'</td><td>'+(i.member_name||'-')+'</td><td>'+(i.last_msg||'-')+'</td><td>'+(i.intent||'-')+'</td><td>'+sb(i.sentiment)+'</td><td>'+ub(i.urgency)+'</td><td>'+(i.created_at||'').substring(11,16)+'</td><td><div class="oq-actions"><button class="btn-resolve" onclick="doAction('+i.id+',\'resolve\')">해결</button><button class="btn-escalate" onclick="doAction('+i.id+',\'escalate\')">에스컬</button><button class="btn-ai" onclick="aiResolve('+i.id+')">AI해결</button></div></td></tr>';});
if(!h)h='<tr><td colspan="8">대기 중인 항목이 없습니다.</td></tr>';
document.getElementById('oqBody').innerHTML=h;}
window.load=function(p){p=p||1;var f=document.getElementById('oqFilter').value;var url=A+'?action=queue_list&page='+p;if(f)url+='&urgency='+f;
J(url).then(function(d){if(d.ok){rT(d.list||[]);document.getElementById('oqPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';}});};
window.doAction=function(id,type){var fd=new FormData();fd.append('queue_id',id);fd.append('action_type',type);fd.append('action_note','운영자 처리');
J(A+'?action=queue_action',{method:'POST',body:fd}).then(function(d){if(d.ok){load();T('처리 완료','ok');}else T('처리 실패','err');});};
window.aiResolve=function(id){var fd=new FormData();fd.append('queue_id',id);
J(A+'?action=ai_auto_resolve',{method:'POST',body:fd}).then(function(d){if(d.ok){load();T(d.auto_resolved?'AI 자동 해결됨':'관리자 확인 필요 - '+d.reason,'ok');}else T('실패','err');});};
window.autoResolveAll=function(){var rows=document.querySelectorAll('#oqBody tr');var ids=[];rows.forEach(function(r){var btns=r.querySelectorAll('.btn-ai');btns.forEach(function(b){var m=b.getAttribute('onclick');if(m){var id=m.match(/aiResolve\('(\d+)'\)/);if(id)ids.push(id[1]);}});});if(!ids.length){T('처리할 항목이 없습니다','err');return;}
Promise.all(ids.map(function(id){var fd=new FormData();fd.append('queue_id',id);return J(A+'?action=ai_auto_resolve',{method:'POST',body:fd});})).then(function(rs){var cnt=rs.filter(function(r){return r.auto_resolved;}).length;load();T('AI 처리: '+cnt+'/'+rs.length+'건 해결','ok');});};
load();setInterval(load,30000);
})();
</script>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
