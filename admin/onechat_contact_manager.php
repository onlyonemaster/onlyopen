<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.ct-wrap{padding:15px 20px}
.ct-tab-nav{display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid #e2e8f0}
.ct-tab-btn{padding:10px 20px;background:none;border:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px}
.ct-tab-btn:hover{color:#475569}.ct-tab-btn.active{color:#06b6d4;border-bottom-color:#06b6d4}
.ct-tab-panel{display:none}.ct-tab-panel.active{display:block}
.ct-toolbar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.ct-toolbar input{padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.ct-toolbar button{padding:6px 16px;background:#06b6d4;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;font-weight:600}
.ct-table{width:100%;border-collapse:collapse;font-size:13px}
.ct-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.ct-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.ct-badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.ct-badge.green{background:#dcfce7;color:#166534}.ct-badge.blue{background:#dbeafe;color:#1e40af}
</style>
<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>
<div class="content-wrapper">
<section class="content-header"><h1>회원·연락처 관리 <small>연락처, 소프트회원, 공유링크</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol></section>
<section class="content"><div class="ct-wrap">
<div class="ct-tab-nav">
<button class="ct-tab-btn active" onclick="switchTab('contact')">연락처</button>
<button class="ct-tab-btn" onclick="switchTab('soft')">소프트회원</button>
<button class="ct-tab-btn" onclick="switchTab('link')">공유링크</button>
</div>
<div class="ct-tab-panel active" id="tabContact">
<div class="ct-toolbar">
<input type="text" id="ctKw" placeholder="이름/연락처/회원ID 검색" style="width:220px">
<button onclick="loadContact(1)">검색</button>
</div>
<table class="ct-table"><thead><tr><th>회원</th><th>수신자</th><th>연락처</th><th>AI 상태</th><th>등록일</th></tr></thead>
<tbody id="contactBody"><tr><td colspan="5" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table><div id="contactPager" style="margin-top:12px;text-align:center"></div>
</div>
<div class="ct-tab-panel" id="tabSoft">
<table class="ct-table"><thead><tr><th>OC-ID</th><th>닉네임</th><th>연락처</th><th>PWA</th><th>업그레이드</th><th>마지막 접속</th><th>가입일</th></tr></thead>
<tbody id="softBody"><tr><td colspan="7" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table><div id="softPager" style="margin-top:12px;text-align:center"></div>
</div>
<div class="ct-tab-panel" id="tabLink">
<table class="ct-table"><thead><tr><th>회원</th><th>챗봇이름</th><th>단축코드</th><th>생성일</th></tr></thead>
<tbody id="linkBody"><tr><td colspan="4" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table><div id="linkPager" style="margin-top:12px;text-align:center"></div>
</div>
</div></section></div></div>
<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function J(u,o){o=o||{};o.credentials='include';return fetch(u,o).then(function(r){return r.json();});}
function f(n){return n.toLocaleString('ko-KR');}
window.switchTab=function(tab){document.querySelectorAll('.ct-tab-btn').forEach(function(b){b.classList.remove('active');});document.querySelectorAll('.ct-tab-panel').forEach(function(p){p.classList.remove('active');});event.target.classList.add('active');document.getElementById('tab'+tab.charAt(0).toUpperCase()+tab.slice(1)).classList.add('active');
if(tab==='contact')loadContact(1);else if(tab==='soft')loadSoft(1);else loadLink(1);};
window.loadContact=function(p){p=p||1;var kw=document.getElementById('ctKw').value;
var u=A+'?action=contact_list&page='+p;if(kw)u+='&keyword='+encodeURIComponent(kw);
J(u).then(function(d){if(!d.ok)return;var h='';(d.list||[]).forEach(function(r){h+='<tr><td>'+r.mem_name+'</td><td>'+r.receiver_name+'</td><td>'+r.receiver_phone+'</td><td>'+r.ai_status+'</td><td>'+r.created_at.substring(0,10)+'</td></tr>';});
if(!h)h='<tr><td colspan="5">연락처가 없습니다.</td></tr>';document.getElementById('contactBody').innerHTML=h;
document.getElementById('contactPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
window.loadSoft=function(p){p=p||1;
J(A+'?action=soft_member_list&page='+p).then(function(d){if(!d.ok)return;var h='';(d.list||[]).forEach(function(r){h+='<tr><td>'+r.oc_id+'</td><td>'+r.nickname+'</td><td>'+r.phone+'</td><td>'+(r.pwa_installed?'<span class="ct-badge green">설치</span>':'')+'</td><td>'+(r.upgraded_to?'<span class="ct-badge blue">업그레이드</span>':'')+'</td><td>'+r.last_seen_at+'</td><td>'+r.created_at.substring(0,10)+'</td></tr>';});
if(!h)h='<tr><td colspan="7">소프트회원이 없습니다.</td></tr>';document.getElementById('softBody').innerHTML=h;
document.getElementById('softPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
window.loadLink=function(p){p=p||1;
J(A+'?action=shared_link_list&page='+p).then(function(d){if(!d.ok)return;var h='';(d.list||[]).forEach(function(r){h+='<tr><td>'+r.mem_name+'</td><td>'+r.chatbot_name+'</td><td>'+r.short_code+'</td><td>'+r.created_at.substring(0,10)+'</td></tr>';});
if(!h)h='<tr><td colspan="4">공유링크가 없습니다.</td></tr>';document.getElementById('linkBody').innerHTML=h;
document.getElementById('linkPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
loadContact(1);
})();
</script>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
