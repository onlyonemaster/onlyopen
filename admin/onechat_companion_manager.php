<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<style>
.cm-wrap{padding:15px 20px}.cm-wrap h3{margin:0 0 8px;font-size:18px;color:#333}.cm-wrap h3 i{color:#ec4899;margin-right:6px}
.cm-kpi-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.cm-kpi-card{flex:1;min-width:140px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;transition:transform .15s}
.cm-kpi-card:hover{transform:translateY(-2px)}
.cm-kpi-card .kpi-val{font-size:22px;font-weight:800;color:#1e293b;margin:6px 0}
.cm-kpi-card .kpi-label{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.cm-kpi-icon{font-size:20px;margin-bottom:4px}
.cm-chart-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px}
.cm-chart-box{flex:1;min-width:340px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.cm-chart-box h4{margin:0 0 10px;font-size:14px;color:#64748b}
.cm-chart-canvas-wrap{position:relative;height:260px}
.cm-chart-canvas-wrap canvas{width:100%!important;height:100%!important}
.cm-table{width:100%;border-collapse:collapse;font-size:13px}
.cm-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.cm-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.cm-status{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600}
.cm-status.active{background:#dcfce7;color:#166534}.cm-status.inactive{background:#f1f5f9;color:#64748b}.cm-status.alert{background:#fee2e2;color:#991b1b}
.cm-tab-nav{display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid #e2e8f0}
.cm-tab-btn{padding:10px 20px;background:none;border:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;transition:all .15s;border-bottom:2px solid transparent;margin-bottom:-2px}
.cm-tab-btn:hover{color:#475569}.cm-tab-btn.active{color:#ec4899;border-bottom-color:#ec4899}
.cm-tab-panel{display:none}.cm-tab-panel.active{display:block}
.cm-diary-entry{background:#fff;border-radius:10px;padding:16px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);border-left:4px solid #ec4899}
.cm-diary-entry .diary-meta{font-size:11px;color:#94a3b8;margin-bottom:6px}
.cm-diary-entry .diary-text{font-size:14px;color:#334155;line-height:1.8;white-space:pre-wrap}
.cm-diary-entry .diary-tags{margin-top:8px}
.cm-diary-entry .diary-tag{display:inline-block;padding:2px 8px;background:#fce7f3;color:#be185d;border-radius:10px;font-size:11px;margin:2px 4px}
</style>

<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

<div class="content-wrapper">
<section class="content-header">
<h1>AI 동행·일기 <small>AI 동행자 관리 및 감정 일기</small></h1>
<ol class="breadcrumb">
<li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
<li class="active">원챗시스템 관리</li>
</ol>
</section>

<section class="content">
<div class="cm-wrap">

<!-- KPI 카드 -->
<div class="cm-kpi-row" id="kpiRow">
<div class="cm-kpi-card"><div class="cm-kpi-icon">🤝</div><div class="kpi-label">활성 동행</div><div class="kpi-val" id="kpiActive">-</div></div>
<div class="cm-kpi-card"><div class="cm-kpi-icon">📝</div><div class="kpi-label">오늘의 일기</div><div class="kpi-val" id="kpiDiaryToday">-</div></div>
<div class="cm-kpi-card"><div class="cm-kpi-icon">📊</div><div class="kpi-label">이번 달 일기</div><div class="kpi-val" id="kpiDiaryMonth">-</div></div>
<div class="cm-kpi-card"><div class="cm-kpi-icon">😊</div><div class="kpi-label">평균 감정 점수</div><div class="kpi-val" id="kpiAvgSentiment">-</div></div>
</div>

<!-- 차트 -->
<div class="cm-chart-row">
<div class="cm-chart-box"><h4>📈 일별 동행 활동 추이</h4>
<div class="cm-chart-canvas-wrap"><canvas id="dailyActivityChart"></canvas></div></div>
<div class="cm-chart-box"><h4>🎭 동행 상태 분포</h4>
<div class="cm-chart-canvas-wrap"><canvas id="statusDistChart"></canvas></div></div>
</div>

<!-- 탭 -->
<div class="cm-tab-nav">
<button class="cm-tab-btn active" onclick="switchTab('companion')">🤝 동행 목록</button>
<button class="cm-tab-btn" onclick="switchTab('diary')">📝 감정 일기</button>
<button class="cm-tab-btn" onclick="switchTab('stats')">📊 통계</button>
</div>

<!-- 동행 목록 탭 -->
<div class="cm-tab-panel active" id="tabCompanion">
<div style="margin-bottom:12px;display:flex;gap:8px;">
<input type="text" id="compSearch" placeholder="회원 ID/이름 검색" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;width:200px">
<button onclick="loadCompanions()" style="padding:6px 16px;background:#ec4899;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">검색</button>
</div>
<table class="cm-table">
<thead><tr><th>회원</th><th>동행자 이름</th><th>관계</th><th>상태</th><th>마지막 활동</th><th>총 대화</th><th>감정 점수</th></tr></thead>
<tbody id="companionBody"><tr><td colspan="7">로딩 중...</td></tr></tbody>
</table>
<div id="compPager" style="margin-top:12px;text-align:center"></div>
</div>

<!-- 감정 일기 탭 -->
<div class="cm-tab-panel" id="tabDiary">
<div style="margin-bottom:12px;display:flex;gap:8px;">
<select id="diaryMember" onchange="loadDiaries()" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
<option value="">전체 회원</option>
</select>
<select id="diarySentiment" onchange="loadDiaries()" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
<option value="">전체 감정</option><option value="positive">긍정</option><option value="negative">부정</option><option value="neutral">중립</option>
</select>
</div>
<div id="diaryEntries">로딩 중...</div>
<div id="diaryPager" style="margin-top:12px;text-align:center"></div>
</div>

<!-- 통계 탭 -->
<div class="cm-tab-panel" id="tabStats">
<div class="cm-chart-row">
<div class="cm-chart-box"><h4>📈 주간 감정 추이</h4>
<div class="cm-chart-canvas-wrap"><canvas id="weeklySentimentChart"></canvas></div></div>
<div class="cm-chart-box"><h4>🔝 동행 랭킹</h4>
<div id="companionRanking">로딩 중...</div></div>
</div>
</div>

</div>
</section>
</div>
</div>

<script>
(function(){
var API='/admin/ajax/onechat_system_api.php';
function J(url,opt){opt=opt||{};opt.credentials='include';return J(url,opt).then(function(r){return r.json();});}
function f(n){return n.toLocaleString('ko-KR');}

var chartA=null,chartS=null,chartW=null;

function rKPI(d){
  document.getElementById('kpiActive').textContent=f(d.active_companions||0)+'명';
  document.getElementById('kpiDiaryToday').textContent=f(d.diary_today||0)+'건';
  document.getElementById('kpiDiaryMonth').textContent=f(d.diary_month||0)+'건';
  document.getElementById('kpiAvgSentiment').textContent=(d.avg_sentiment||0).toFixed(1)+'점';
}

function rDailyActivity(data){
  var ctx=document.getElementById('dailyActivityChart').getContext('2d');
  if(chartA)chartA.destroy();
  var days=data.daily_activity||[];
  chartA=new Chart(ctx,{
    type:'line',
    data:{
      labels:days.map(function(d){return d.day;}),
      datasets:[{
        label:'활동',data:days.map(function(d){return d.count;}),
        borderColor:'#ec4899',backgroundColor:'rgba(236,72,153,0.1)',
        fill:true,tension:0.3,pointRadius:2
      }]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{y:{beginAtZero:true}}
    }
  });
}

function rStatusDist(data){
  var ctx=document.getElementById('statusDistChart').getContext('2d');
  if(chartS)chartS.destroy();
  var st=data.status_distribution||{active:0,inactive:0,alert:0};
  chartS=new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:['활성','비활성','주의'],
      datasets:[{data:[st.active||0,st.inactive||0,st.alert||0],
        backgroundColor:['#10b981','#94a3b8','#ef4444'],borderWidth:2,borderColor:'#fff'}]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{position:'right',labels:{font:{size:12},padding:12}}}
    }
  });
}

function rCompanions(list){
  var h='';
  (list||[]).forEach(function(r){
    var st=r.status||'inactive';
    var stCl=st==='active'?'active':(st==='alert'?'alert':'inactive');
    h+='<tr><td>'+(r.member_name||r.member_id||'-')+'</td>';
    h+='<td>'+(r.companion_name||'-')+'</td>';
    h+='<td>'+(r.relationship||'-')+'</td>';
    h+='<td><span class="cm-status '+stCl+'">'+(st==='active'?'활성':(st==='alert'?'주의':'비활성'))+'</span></td>';
    h+='<td>'+(r.last_active||'-')+'</td>';
    h+='<td>'+f(r.total_chats||0)+'</td>';
    h+='<td>'+(r.sentiment_score||0).toFixed(1)+'</td></tr>';
  });
  if(!h)h='<tr><td colspan="7">등록된 동행이 없습니다.</td></tr>';
  document.getElementById('companionBody').innerHTML=h;
}

function rDiaries(list){
  var h='';
  (list||[]).forEach(function(d){
    var sentEmoji=d.sentiment==='positive'?'😊':(d.sentiment==='negative'?'😢':'😐');
    var sentColor=d.sentiment==='positive'?'#10b981':(d.sentiment==='negative'?'#ef4444':'#94a3b8');
    h+='<div class="cm-diary-entry" style="border-left-color:'+sentColor+'">';
    h+='<div class="diary-meta">'+sentEmoji+' '+(d.member_name||'-')+' · '+(d.created_at||'')+' · 함께한 동행: '+(d.companion_name||'AI')+'</div>';
    h+='<div class="diary-text">'+(d.content||'').replace(/</g,'&lt;')+'</div>';
    if(d.tags){h+='<div class="diary-tags">';d.tags.split(',').forEach(function(t){h+='<span class="diary-tag">'+t.trim()+'</span>';});h+='</div>';}
    h+='</div>';
  });
  if(!h)h='<p style="color:#94a3b8">📭 표시할 일기가 없습니다.</p>';
  document.getElementById('diaryEntries').innerHTML=h;
}

function rRanking(list){
  var h='<table class="cm-table"><tr><th>순위</th><th>회원</th><th>동행자</th><th>대화 수</th><th>감정 점수</th></tr>';
  (list||[]).forEach(function(r,i){
    h+='<tr><td>'+(i+1)+'</td><td>'+(r.member_name||r.member_id)+'</td><td>'+(r.companion_name||'-')+'</td><td>'+f(r.total_chats||0)+'</td><td>'+(r.sentiment_score||0).toFixed(1)+'</td></tr>';
  });
  h+='</table>';
  if(!list||!list.length)h='<p style="color:#94a3b8">데이터 없음</p>';
  document.getElementById('companionRanking').innerHTML=h;
}

function rWeeklySentiment(data){
  var ctx=document.getElementById('weeklySentimentChart').getContext('2d');
  if(chartW)chartW.destroy();
  var w=data.weekly_sentiment||[];
  chartW=new Chart(ctx,{
    type:'line',
    data:{
      labels:w.map(function(d){return d.week;}),
      datasets:[
        {label:'긍정',data:w.map(function(d){return d.positive||0;}),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.1)',fill:true,tension:0.3,pointRadius:3},
        {label:'부정',data:w.map(function(d){return d.negative||0;}),borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,0.1)',fill:true,tension:0.3,pointRadius:3},
        {label:'중립',data:w.map(function(d){return d.neutral||0;}),borderColor:'#94a3b8',backgroundColor:'rgba(148,163,184,0.1)',fill:true,tension:0.3,pointRadius:3}
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:10}}},
      scales:{y:{beginAtZero:true}}
    }
  });
}

window.switchTab=function(tab){
  document.querySelectorAll('.cm-tab-btn').forEach(function(b){b.classList.remove('active');});
  document.querySelectorAll('.cm-tab-panel').forEach(function(p){p.classList.remove('active');});
  event.target.classList.add('active');
  document.getElementById('tab'+tab.charAt(0).toUpperCase()+tab.slice(1)).classList.add('active');
  if(tab==='companion')loadCompanions();
  else if(tab==='diary')loadDiaries();
  else if(tab==='stats')loadStats();
};

window.loadCompanions=function(page){
  page=page||1;
  var q=document.getElementById('compSearch').value;
  var url=API+'?action=companion_stats&type=list&page='+page+'&q='+encodeURIComponent(q);
  J(url).then(function(r){return r.json();}).then(function(d){
    if(d.ok)rCompanions(d.list||[]);
    document.getElementById('compPager').innerHTML=(d.total_pages>1)?('페이지 '+page+' / '+d.total_pages):'';
  });
};

window.loadDiaries=function(page){
  page=page||1;
  var m=document.getElementById('diaryMember').value;
  var s=document.getElementById('diarySentiment').value;
  var url=API+'?action=companion_stats&type=diaries&page='+page;
  if(m)url+='&member='+encodeURIComponent(m);
  if(s)url+='&sentiment='+s;
  J(url).then(function(r){return r.json();}).then(function(d){
    if(d.ok)rDiaries(d.list||[]);
    document.getElementById('diaryPager').innerHTML=(d.total_pages>1)?('페이지 '+page+' / '+d.total_pages):'';
  });
};

window.loadStats=function(){
  J(API+'?action=companion_stats&type=ranking').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rRanking(d.list||[]);
  });
  J(API+'?action=companion_stats&type=weekly').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rWeeklySentiment(d);
  });
};

function init(){
  J(API+'?action=companion_stats').then(function(r){return r.json();}).then(function(d){
    if(!d.ok)return;
    rKPI(d);rDailyActivity(d);rStatusDist(d);
  });
  loadCompanions();
}
init();
})();
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
