<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.bm-wrap{padding:15px 20px}.bm-wrap h3{margin:0 0 8px;font-size:18px;color:#333}
.bm-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
.bm-toolbar input{padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.bm-toolbar button{padding:6px 16px;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}
.bm-toolbar .btn-primary{background:#6366f1;color:#fff}
.bm-toolbar .btn-warn{background:#f59e0b;color:#fff}
.bm-toolbar .btn-success{background:#10b981;color:#fff}
.bm-table{width:100%;border-collapse:collapse;font-size:13px}
.bm-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.bm-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.badge-on{background:#dcfce7;color:#166534;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.badge-off{background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:999;display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal-box{background:#fff;border-radius:12px;padding:24px;width:90%;max-width:600px;max-height:80vh;overflow-y:auto}
.modal-box h4{margin:0 0 16px;font-size:16px;color:#1e293b}
.modal-row{display:flex;gap:12px;margin-bottom:10px;align-items:center}
.modal-row label{min-width:100px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase}
.modal-row select,.modal-row input{flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}
.toast{position:fixed;top:20px;right:20px;padding:10px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;opacity:0;transition:opacity .3s}
.toast.show{opacity:1}.toast.ok{background:#10b981}.toast.err{background:#ef4444}
</style>

<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

<div class="content-wrapper">
<section class="content-header">
<h1>챗봇 설정 관리 <small>회원별 챗봇 ON/OFF 및 프롬프트 관리</small></h1>
<ol class="breadcrumb"><li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li><li class="active">원챗시스템 관리</li></ol>
</section>

<section class="content"><div class="bm-wrap">

<div class="bm-toolbar">
<input type="text" id="bmSearch" placeholder="회원ID / 이름 검색" style="width:200px">
<button class="btn-primary" onclick="loadList()">검색</button>
<button class="btn-warn" onclick="bulkToggle(0)">선택 챗봇 OFF</button>
<button class="btn-success" onclick="bulkToggle(1)">선택 챗봇 ON</button>
<select id="bmPromptMode" style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
<option value="">프롬프트 모드 변경</option>
<option value="default">기본</option><option value="professional">전문가</option><option value="friendly">친근함</option><option value="sales">세일즈</option>
</select>
<button class="btn-primary" onclick="bulkPrompt()">일괄 적용</button>
</div>

<table class="bm-table">
<thead><tr><th><input type="checkbox" id="bmCheckAll" onclick="toggleAll(this)"></th><th>회원ID</th><th>이름</th><th>서비스</th><th>챗봇</th><th>프롬프트</th><th>대화량</th><th>마지막 활동</th><th>관리</th></tr></thead>
<tbody id="bmBody"><tr><td colspan="9" style="color:#94a3b8">데이터를 불러오는 중입니다...</td></tr></tbody>
</table>
<div id="bmPager" style="margin-top:12px;text-align:center"></div>

</div></section></div></div>

<div class="modal-overlay" id="bmModal">
<div class="modal-box">
<h4>챗봇 상세 설정</h4>
<div id="bmDetail">로딩 중...</div>
<div class="modal-actions">
<button class="btn-warn" onclick="closeModal()">닫기</button>
<button class="btn-primary" onclick="saveDetail()">저장</button>
</div></div></div>
<div id="toast" class="toast"></div>

<script>
(function(){
var A='/admin/ajax/onechat_system_api.php';
function J(url,opt){opt=opt||{};opt.credentials='include';return fetch(url,opt).then(function(r){return r.json();});}
function T(m,c){var t=document.getElementById('toast');t.textContent=m;t.className='toast '+c+' show';setTimeout(function(){t.classList.remove('show');},3000);}

function rList(d){var h='';(d.list||[]).forEach(function(r){h+='<tr><td><input type="checkbox" class="bmCheck" value="'+r.mem_id+'"></td><td>'+r.mem_id+'</td><td>'+r.mem_name+'</td><td>'+r.service_type+'</td><td>'+(r.bot_on?'<span class="badge-on">ON</span>':'<span class="badge-off">OFF</span>')+'</td><td>'+r.prompt_mode+'</td><td>'+r.unread_count+'</td><td>'+r.updated_at.substring(0,10)+'</td><td><button onclick="openDetail(\''+r.mem_id+'\')" style="padding:4px 12px;background:#6366f1;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px">상세</button></td></tr>';});
if(!h)h='<tr><td colspan="9">등록된 챗봇이 없습니다.</td></tr>';
document.getElementById('bmBody').innerHTML=h;}
function rDetail(d){var r=d.data;var h='<div class="modal-row"><label>챗봇 상태</label><select id="bdBotOn"><option value="1"'+(r.bot_on?' selected':'')+'>ON</option><option value="0"'+(r.bot_on?'':' selected')+'>OFF</option></select></div>';
h+='<div class="modal-row"><label>프롬프트</label><select id="bdPrompt">'+['default','professional','friendly','sales'].map(function(v){return'<option value="'+v+'"'+(r.prompt_mode==v?' selected':'')+'>'+v+'</option>';}).join('')+'</select></div>';
h+='<div class="modal-row"><label>말투</label><input id="bdTone" value="'+(r.prompt_tone||'')+'"></div>';
h+='<div class="modal-row"><label>관계</label><input id="bdRel" value="'+(r.prompt_rel||'')+'"></div>';
h+='<div class="modal-row"><label>목적</label><input id="bdPurp" value="'+(r.prompt_purp||'')+'"></div>';
h+='<div style="margin-top:10px;font-size:12px;color:#94a3b8">서비스: '+r.service_type+' | 프로필: '+r.ai_profile_used+'/'+r.ai_profile_limit+' | 메시지: '+r.ai_msg_used+'/'+r.ai_msg_limit+' | 응답: '+r.ai_resp_used+'/'+r.ai_resp_limit+' | 챗봇수: '+r.bot_count+'</div>';
document.getElementById('bmDetail').innerHTML=h;document.getElementById('bmModal').classList.add('show');}
window.loadList=function(p){p=p||1;var q=document.getElementById('bmSearch').value;
J(A+'?action=bot_list&page='+p+'&keyword='+encodeURIComponent(q)).then(function(d){if(d.ok)rList(d);
document.getElementById('bmPager').innerHTML=(d.total_pages>1)?('페이지 '+p+' / '+d.total_pages):'';});};
window.openDetail=function(mid){J(A+'?action=bot_detail&mem_id='+encodeURIComponent(mid)).then(function(d){if(d.ok)rDetail(d);});};
window.closeModal=function(){document.getElementById('bmModal').classList.remove('show');};
window.saveDetail=function(){var fd=new FormData();fd.append('bot_on',document.getElementById('bdBotOn').value);fd.append('prompt_mode',document.getElementById('bdPrompt').value);fd.append('prompt_tone',document.getElementById('bdTone').value);fd.append('prompt_rel',document.getElementById('bdRel').value);fd.append('prompt_purp',document.getElementById('bdPurp').value);
J(A+'?action=bot_update',{method:'POST',body:fd}).then(function(d){if(d.ok){closeModal();loadList();T('저장 완료','ok');}else T('저장 실패','err');});};
window.getChecked=function(){var cs=document.querySelectorAll('.bmCheck:checked');return Array.from(cs).map(function(c){return c.value;});};
window.toggleAll=function(cb){document.querySelectorAll('.bmCheck').forEach(function(c){c.checked=cb.checked;});};
window.bulkToggle=function(on){var ms=getChecked();if(!ms.length){T('회원을 선택하세요','err');return;}
var fd=new FormData();ms.forEach(function(m){fd.append('mem_ids[]',m);});fd.append('bot_on',on);
J(A+'?action=bot_bulk',{method:'POST',body:fd}).then(function(d){if(d.ok){loadList();T(d.updated+'건 변경됨','ok');}else T('실패','err');});};
window.bulkPrompt=function(){var ms=getChecked();var pm=document.getElementById('bmPromptMode').value;if(!ms.length){T('회원을 선택하세요','err');return;}if(!pm){T('프롬프트 모드를 선택하세요','err');return;}
var fd=new FormData();ms.forEach(function(m){fd.append('mem_ids[]',m);});fd.append('prompt_mode',pm);
J(A+'?action=bot_bulk',{method:'POST',body:fd}).then(function(d){if(d.ok){loadList();T(d.updated+'건 변경됨','ok');}else T('실패','err');});};
loadList();
})();
</script>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
