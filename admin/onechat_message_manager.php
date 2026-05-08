<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.msg-wrap{padding:15px 20px}
.msg-tab-nav{display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid #e2e8f0}
.msg-tab-btn{padding:10px 20px;background:none;border:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px}
.msg-tab-btn:hover{color:#475569}.msg-tab-btn.active{color:#6366f1;border-bottom-color:#6366f1}
.msg-tab-panel{display:none}.msg-tab-panel.active{display:block}
.msg-toolbar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.msg-toolbar input{padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.msg-toolbar button{padding:6px 16px;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;font-weight:600}
.msg-table{width:100%;border-collapse:collapse;font-size:13px}
.msg-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.msg-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.msg-bubble{display:inline-block;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sent-p{color:#10b981;font-weight:600}.sent-n{color:#ef4444;font-weight:600}
</style>
<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>
<div class="content-wrapper">
<section class="content-header"><h1>메시지/대화 관리 <small>챗봇 대화 로그 및 HI 공지</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol></section>
<section class="content"><div class="msg-wrap">
<div class="msg-tab-nav">
<button class="msg-tab-btn active" onclick="switchTab('chat')">챗봇 대화</button>
<button class="msg-tab-btn" onclick="switchTab('broadcast')">HI 공지발송</button>
</div>
<div class="msg-tab-panel active" id="tabChat">
<div class="msg-toolbar">
<input type="text" id="chatKw" placeholder="키워드 검색" style="width:180px">
<input type="text" id="chatMid" placeholder="회원ID" style="width:140px">
<button onclick="loadChat(1)">검색</button>
</div>
<table class="msg-table"><thead><tr><th>회원</th><th>방문자</th><th>사용자 메시지</th><th>AI 응답</th><th>의도</th><th>감정</th><th>시간</th></tr></thead>
<tbody id="chatBody"><tr><td colspan="7" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table>
<div id="chatPager" style="margin-top:12px;text-align:center"></div>
</div>
<div class="msg-tab-panel" id="tabBroadcast">
<table class="msg-table"><thead><tr><th>운영자</th><th>제목</th><th>대상</th><th>발송</th><th>읽음</th><th>응답</th><th>상태</th><th>시간</th></tr></thead>
<tbody id="brBody"><tr><td colspan="8" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody></table>
<div id="brPager" style="margin-top:12px;text-align:center"></div>
</div>
</div></section></div></div>
<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function J(u,o){o=o||{};o.credentials='include';return fetch(u,o).then(function(r){return r.json();});}
function f(n){return n.toLocaleString('ko-KR');}
function sb(s){if(s=='positive')return'<span class="sent-p">긍정</span>';if(s=='negative')return'<span class="sent-n">부정</span>';return'<span style="color:#94a3b8">중립</span>';}
window.switchTab=function(tab){document.querySelectorAll('.msg-tab-btn').forEach(function(b){b.classList.remove('active');});document.querySelectorAll('.msg-tab-panel').forEach(function(p){p.classList.remove('active');});event.target.classList.add('active');document.getElementById('tab'+tab.charAt(0).toUpperCase()+tab.slice(1)).classList.add('active');
if(tab==='chat')loadChat(1);else loadBroadcast(1);};
window.loadChat=function(p){p=p||1;var kw=document.getElementById('chatKw').value,mid=document.getElementById('chatMid').value;
var u=A+'?action=chat_log&page='+p;if(kw)u+='&keyword='+encodeURIComponent(kw);if(mid)u+='&mem_id='+encodeURIComponent(mid);
J(u).then(function(d){if(!d.ok)return;var h='';(d.list||[]).forEach(function(r){h+='<tr><td>'+r.mem_name+'</td><td>'+r.visitor_name+'</td><td><span class="msg-bubble">'+r.user_message+'</span></td><td><span class="msg-bubble">'+r.ai_response+'</span></td><td>'+r.intent+'</td><td>'+sb(r.sentiment)+'</td><td>'+(r.created_at||'').substring(11,16)+'</td></tr>';});
if(!h)h='<tr><td colspan="7">대화 기록이 없습니다.</td></tr>';document.getElementById('chatBody').innerHTML=h;
document.getElementById('chatPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
window.loadBroadcast=function(p){p=p||1;
J(A+'?action=broadcast_list&page='+p).then(function(d){if(!d.ok)return;var h='';(d.list||[]).forEach(function(r){h+='<tr><td>'+r.operator_name+'</td><td>'+r.title+'</td><td>'+r.target_type+'</td><td>'+f(r.sent_count)+'</td><td>'+f(r.read_count)+'</td><td>'+f(r.reply_count)+'</td><td>'+r.status+'</td><td>'+r.created_at.substring(0,10)+'</td></tr>';});
if(!h)h='<tr><td colspan="8">발송 내역이 없습니다.</td></tr>';document.getElementById('brBody').innerHTML=h;
document.getElementById('brPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
loadChat(1);
})();
</script>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
