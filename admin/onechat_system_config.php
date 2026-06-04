<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<style>
.sc-wrap{padding:15px 20px}.sc-wrap h3{margin:0 0 8px;font-size:18px;color:#333}.sc-wrap h3 i{color:#64748b;margin-right:6px}
.sc-card{background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px}
.sc-card h4{margin:0 0 14px;font-size:15px;color:#334155;padding-bottom:8px;border-bottom:1px solid #f1f5f9}
.sc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.sc-form-group{margin-bottom:14px}
.sc-form-group label{display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px}
.sc-form-group input,.sc-form-group select,.sc-form-group textarea{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;box-sizing:border-box}
.sc-form-group textarea{resize:vertical;min-height:80px}
.sc-form-group .hint{font-size:11px;color:#94a3b8;margin-top:4px}
.sc-btn{padding:8px 20px;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.sc-btn.primary{background:#6366f1;color:#fff}.sc-btn.primary:hover{background:#4f46e5}
.sc-btn.success{background:#10b981;color:#fff}.sc-btn.success:hover{background:#059669}
.sc-btn.danger{background:#ef4444;color:#fff}.sc-btn.danger:hover{background:#dc2626}
.sc-btn.warning{background:#f59e0b;color:#fff}.sc-btn.warning:hover{background:#d97706}
.sc-health-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
.sc-health-item{background:#f8fafc;border-radius:8px;padding:14px;display:flex;justify-content:space-between;align-items:center;border-left:4px solid #10b981}
.sc-health-item.fail{border-left-color:#ef4444;background:#fef2f2}
.sc-health-item.warn{border-left-color:#f59e0b;background:#fffbeb}
.sc-health-item .hi-name{font-size:13px;color:#334155;font-weight:600}
.sc-health-item .hi-status{font-size:12px;padding:3px 10px;border-radius:12px;font-weight:600}
.sc-health-item .hi-status.ok{background:#dcfce7;color:#166534}
.sc-health-item .hi-status.fail{background:#fee2e2;color:#991b1b}
.sc-health-item .hi-status.warn{background:#fef3c7;color:#92400e}
.sc-switch{position:relative;display:inline-block;width:48px;height:26px}
.sc-switch input{opacity:0;width:0;height:0}
.sc-switch .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#cbd5e1;transition:.3s;border-radius:26px}
.sc-switch .slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background-color:#fff;transition:.3s;border-radius:50%}
.sc-switch input:checked+.slider{background-color:#6366f1}
.sc-switch input:checked+.slider:before{transform:translateX(22px)}
.sc-info-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.sc-info-row .info-label{color:#94a3b8}.sc-info-row .info-value{color:#334155;font-weight:600}
.sc-toast{position:fixed;top:20px;right:20px;padding:10px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;transition:opacity .3s;opacity:0}
.sc-toast.show{opacity:1}.sc-toast.ok{background:#10b981}.sc-toast.err{background:#ef4444}
</style>

<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

<div class="content-wrapper">
<section class="content-header">
<h1>시스템 설정 <small>OneChat 시스템 진단 및 구성</small></h1>
<ol class="breadcrumb">
<li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
<li class="active">원챗시스템 관리</li>
</ol>
</section>

<section class="content">
<div class="sc-wrap">

<!-- 시스템 헬스 체크 -->
<div class="sc-card">
<h4>🏥 시스템 헬스 체크</h4>
<div class="sc-health-grid" id="healthGrid">
<div class="sc-health-item"><div class="hi-name">데이터베이스</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_bots</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_queue</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_chats</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_contacts</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_scenarios</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">Gn_onechat_broadcasts</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">긴급 대기열</div><div class="hi-status ok">확인 중...</div></div>
<div class="sc-health-item"><div class="hi-name">30분 지연 대기</div><div class="hi-status ok">확인 중...</div></div>
</div>
<div style="margin-top:12px">
<button class="sc-btn primary" onclick="runHealthCheck()">🔄 헬스 체크 재실행</button>
<button class="sc-btn warning" onclick="runAutoResolve()">🧠 AI 자동 해결 실행</button>
</div>
</div>

<!-- 시스템 정보 -->
<div class="sc-grid">
<div class="sc-card">
<h4>📋 시스템 정보</h4>
<div id="systemInfo">
<div class="sc-info-row"><span class="info-label">PHP 버전</span><span class="info-value"><?=phpversion()?></span></div>
<div class="sc-info-row"><span class="info-label">MySQL</span><span class="info-value" id="mysqlVersion">-</span></div>
<div class="sc-info-row"><span class="info-label">서버 시간</span><span class="info-value"><?=date('Y-m-d H:i:s')?></span></div>
<div class="sc-info-row"><span class="info-label">문서 루트</span><span class="info-value"><?=$_SERVER['DOCUMENT_ROOT']?></span></div>
<div class="sc-info-row"><span class="info-label">서버 소프트웨어</span><span class="info-value"><?=$_SERVER['SERVER_SOFTWARE']??'Unknown'?></span></div>
</div>
</div>

<div class="sc-card">
<h4>⚙️ AI 자동 해결 설정</h4>
<form id="configForm" onsubmit="saveConfig(event)">
<div class="sc-form-group">
<label>자동 해결 활성화</label>
<label class="sc-switch"><input type="checkbox" id="cfgAutoResolve" checked><span class="slider"></span></label>
<div class="hint">긴급도=0 이고 감정이 긍정인 항목을 AI가 자동으로 해결합니다.</div>
</div>
<div class="sc-form-group">
<label>최대 자동 해결 건수/실행</label>
<input type="number" id="cfgMaxResolve" value="50" min="1" max="500">
<div class="hint">한 번에 자동 해결할 최대 항목 수</div>
</div>
<div class="sc-form-group">
<label>대기열 알림 임계값</label>
<input type="number" id="cfgQueueThreshold" value="20" min="1" max="1000">
<div class="hint">대기열이 이 수치를 초과하면 알림 발생</div>
</div>
<div class="sc-form-group">
<label>긴급도 자동 상승 시간(분)</label>
<input type="number" id="cfgUrgencyBoost" value="30" min="5" max="1440">
<div class="hint">지정된 시간 동안 미해결 시 긴급도가 1씩 자동 상승합니다.</div>
</div>
<button type="submit" class="sc-btn primary">💾 설정 저장</button>
</form>
</div>
</div>

<!-- DB 통계 -->
<div class="sc-card">
<h4>📊 데이터베이스 통계</h4>
<div class="sc-grid" id="dbStats">
<div class="sc-info-row"><span class="info-label">Gn_onechat_bots</span><span class="info-value" id="statBots">-</span></div>
<div class="sc-info-row"><span class="info-label">Gn_onechat_queue</span><span class="info-value" id="statQueue">-</span></div>
<div class="sc-info-row"><span class="info-label">Gn_onechat_chats</span><span class="info-value" id="statChats">-</span></div>
<div class="sc-info-row"><span class="info-label">Gn_onechat_contacts</span><span class="info-value" id="statContacts">-</span></div>
<div class="sc-info-row"><span class="info-label">Gn_onechat_actions</span><span class="info-value" id="statActions">-</span></div>
<div class="sc-info-row"><span class="info-label">Gn_Member (구독)</span><span class="info-value" id="statMembers">-</span></div>
</div>
</div>

<!-- AI 액션 로그 -->
<div class="sc-card">
<h4>📜 AI 액션 로그 (최근 100건)</h4>
<div style="margin-bottom:12px">
<button class="sc-btn success" onclick="refreshActionLog()">🔄 새로고침</button>
</div>
<table style="width:100%;border-collapse:collapse;font-size:13px">
<thead><tr style="background:#f8fafc;text-align:left">
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">시간</th>
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">운영자</th>
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">액션</th>
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">대상</th>
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">결과</th>
<th style="padding:8px 10px;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0">비고</th>
</tr></thead>
<tbody id="actionLogBody"><tr><td colspan="6">로딩 중...</td></tr></tbody>
</table>
</div>

</div>
</section>
</div>
</div>

<div id="toast" class="sc-toast"></div>

<script>
(function(){
var API='/admin/ajax/onechat_system_api.php';
function J(url,opt){opt=opt||{};opt.credentials='include';return J(url,opt).then(function(r){return r.json();});}

function toast(msg,cls){
  var t=document.getElementById('toast');
  t.textContent=msg;t.className='sc-toast '+cls+' show';
  setTimeout(function(){t.classList.remove('show');},3000);
}

function rHealthCheck(d){
  var checks=d.checks||[];
  var g=document.getElementById('healthGrid');
  var h='';
  checks.forEach(function(c){
    var cls=c.status==='FAIL'?'fail':(c.status.indexOf('WARN')===0?'warn':'');
    h+='<div class="sc-health-item'+(cls?' '+cls:'')+'"><div class="hi-name">'+c.name+'</div><div class="hi-status '+(c.status==='OK'?'ok':(c.status==='FAIL'?'fail':'warn'))+'">'+c.status+'</div></div>';
  });
  g.innerHTML=h;
}

function rDBStats(d){
  document.getElementById('statBots').textContent=(d.bot_count||0).toLocaleString('ko-KR')+'건';
  document.getElementById('statQueue').textContent=(d.queue_count||0).toLocaleString('ko-KR')+'건';
  document.getElementById('statChats').textContent=(d.chat_count||0).toLocaleString('ko-KR')+'건';
  document.getElementById('statContacts').textContent=(d.contact_count||0).toLocaleString('ko-KR')+'건';
  document.getElementById('statActions').textContent=(d.action_count||0).toLocaleString('ko-KR')+'건';
  document.getElementById('statMembers').textContent=(d.member_count||0).toLocaleString('ko-KR')+'명';
  document.getElementById('mysqlVersion').textContent=d.mysql_version||'-';
}

function rActionLog(list){
  var h='';
  (list||[]).forEach(function(r){
    h+='<tr><td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+((r.created_at||'').substring(0,16))+'</td>';
    h+='<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+(r.operator_id||'-')+'</td>';
    h+='<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+(r.action||'-')+'</td>';
    h+='<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+(r.target||'-')+'</td>';
    h+='<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+(r.result||'-')+'</td>';
    h+='<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9">'+(r.note||'').substring(0,80)+'</td></tr>';
  });
  if(!h)h='<tr><td colspan="6" style="padding:12px;color:#94a3b8">기록 없음</td></tr>';
  document.getElementById('actionLogBody').innerHTML=h;
}

window.runHealthCheck=function(){
  J(API+'?action=ai_health_check').then(function(r){return r.json();}).then(function(d){
    if(d.ok){rHealthCheck(d);toast('헬스 체크 완료','ok');}
    else toast('헬스 체크 실패','err');
  });
};

window.runAutoResolve=function(){
  J(API+'?action=ai_auto_resolve&mode=bulk').then(function(r){return r.json();}).then(function(d){
    if(d.ok)toast('AI 자동 해결: '+d.resolved+'건 처리됨','ok');
    else toast('자동 해결 실패','err');
  });
};

window.saveConfig=function(e){
  e.preventDefault();
  var fd=new FormData();
  fd.append('auto_resolve',document.getElementById('cfgAutoResolve').checked?'1':'0');
  fd.append('max_resolve',document.getElementById('cfgMaxResolve').value);
  fd.append('queue_threshold',document.getElementById('cfgQueueThreshold').value);
  fd.append('urgency_boost',document.getElementById('cfgUrgencyBoost').value);
  J(API+'?action=system_config&mode=save',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
    if(d.ok)toast('설정 저장 완료','ok');
    else toast('저장 실패','err');
  });
};

window.refreshActionLog=function(){
  J(API+'?action=ai_auto_resolve&mode=list&limit=100').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rActionLog(d.list||[]);
  });
};

function init(){
  J(API+'?action=ai_health_check').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rHealthCheck(d);
  });
  J(API+'?action=system_config').then(function(r){return r.json();}).then(function(d){
    if(d.ok){rDBStats(d);}
  });
  refreshActionLog();
}
init();
})();
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
