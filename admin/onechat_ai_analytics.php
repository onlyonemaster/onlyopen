<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<style>
.aa-wrap{padding:15px 20px}.aa-wrap h3{margin:0 0 8px;font-size:18px;color:#333}.aa-wrap h3 i{color:#8b5cf6;margin-right:6px}
.aa-kpi-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.aa-kpi-card{flex:1;min-width:150px;background:#fff;border-radius:10px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.08);text-align:center;transition:transform .15s}
.aa-kpi-card:hover{transform:translateY(-2px)}
.aa-kpi-card .kpi-val{font-size:24px;font-weight:800;color:#1e293b;margin:6px 0}
.aa-kpi-card .kpi-label{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.aa-kpi-icon{font-size:22px;margin-bottom:4px}
.aa-chart-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px}
.aa-chart-box{flex:1;min-width:350px;background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.aa-chart-box h4{margin:0 0 10px;font-size:14px;color:#64748b}
.aa-chart-canvas-wrap{position:relative;height:280px}
.aa-chart-canvas-wrap canvas{width:100%!important;height:100%!important}
.aa-tag{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;margin:2px 4px}
.aa-tag.hot{background:#fee2e2;color:#991b1b}.aa-tag.rising{background:#fef3c7;color:#92400e}.aa-tag.stable{background:#dcfce7;color:#166534}
.aa-insight-box{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px}
.aa-keyword-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.aa-section-title{font-size:14px;font-weight:700;color:#475569;margin:16px 0 10px;padding-bottom:6px;border-bottom:1px solid #e2e8f0}
.aa-issue-table{width:100%;border-collapse:collapse;font-size:13px}
.aa-issue-table th{background:#f8fafc;padding:8px 10px;text-align:left;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0}
.aa-issue-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.sent-pos{color:#10b981;font-weight:600}.sent-neg{color:#ef4444;font-weight:600}.sent-neu{color:#94a3b8}
</style>

<div class="wrapper">
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

<div class="content-wrapper">
<section class="content-header">
<h1>AI 분석·인사이트 <small>통합 데이터 분석 및 인텔리전스</small></h1>
<ol class="breadcrumb">
<li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
<li class="active">원챗시스템 관리</li>
</ol>
</section>

<section class="content">
<div class="aa-wrap">

<!-- KPI 카드 -->
<div class="aa-kpi-row" id="kpiRow">
<div class="aa-kpi-card"><div class="aa-kpi-icon">📝</div><div class="kpi-label">총 대화량</div><div class="kpi-val" id="kpiTotalChats">-</div></div>
<div class="aa-kpi-card"><div class="aa-kpi-icon">😊</div><div class="kpi-label">긍정 감정 비율</div><div class="kpi-val" id="kpiPositive">-</div></div>
<div class="aa-kpi-card"><div class="aa-kpi-icon">🎯</div><div class="kpi-label">의도 분류 수</div><div class="kpi-val" id="kpiIntents">-</div></div>
<div class="aa-kpi-card"><div class="aa-kpi-icon">🔍</div><div class="kpi-label">주요 키워드</div><div class="kpi-val" id="kpiKeywords">-</div></div>
<div class="aa-kpi-card"><div class="aa-kpi-icon">⚡</div><div class="kpi-label">자동 해결 건수</div><div class="kpi-val" id="kpiResolved">-</div></div>
</div>

<!-- 차트 -->
<div class="aa-chart-row">
<div class="aa-chart-box"><h4>📈 일별 대화 추이 (최근 30일)</h4>
<div class="aa-chart-canvas-wrap"><canvas id="dailyTrendChart"></canvas></div></div>
<div class="aa-chart-box"><h4>📊 의도별 분포</h4>
<div class="aa-chart-canvas-wrap"><canvas id="intentBarChart"></canvas></div></div>
</div>

<div class="aa-chart-row">
<div class="aa-chart-box"><h4>🎭 감정 분석</h4>
<div class="aa-chart-canvas-wrap"><canvas id="sentimentChart"></canvas></div></div>
<div class="aa-chart-box" style="min-width:400px"><h4>🔑 인기 키워드 TOP 15</h4>
<div class="aa-keyword-row" id="keywordCloud">로딩 중...</div></div>
</div>

<!-- AI 인사이트 -->
<div class="aa-insight-box"><h4>🤖 AI 인사이트 분석</h4><div id="aiInsightContent">로딩 중...</div></div>

<!-- 자동 해결 이슈 -->
<div class="aa-section-title">⚡ AI 자동 해결 이슈 로그 (최근 50건)</div>
<table class="aa-issue-table">
<thead><tr><th>시간</th><th>회원</th><th>이슈 유형</th><th>처리 결과</th><th>소요 시간</th></tr></thead>
<tbody id="issueLogBody"><tr><td colspan="5">로딩 중...</td></tr></tbody>
</table>

</div>
</section>
</div>
</div>

<script>
(function(){
var API='/admin/ajax/onechat_system_api.php';
function J(url,opt){opt=opt||{};opt.credentials='include';return J(url,opt).then(function(r){return r.json();});}
function f(n){return n.toLocaleString('ko-KR');}

var chart1=null,chart2=null,chart3=null;

function rKPI(d){
  document.getElementById('kpiTotalChats').textContent=f(d.total_chats)+'건';
  document.getElementById('kpiPositive').textContent=(d.positive_pct||0)+'%';
  document.getElementById('kpiIntents').textContent=f(d.intent_count||0)+'종';
  document.getElementById('kpiKeywords').textContent=f(d.keyword_count||0)+'개';
  document.getElementById('kpiResolved').textContent=f(d.auto_resolved||0)+'건';
}

function rDailyTrend(data){
  var ctx=document.getElementById('dailyTrendChart').getContext('2d');
  if(chart1)chart1.destroy();
  var days=data.daily_trend||[];
  chart1=new Chart(ctx,{
    type:'line',
    data:{
      labels:days.map(function(d){return d.day;}),
      datasets:[{
        label:'대화량',data:days.map(function(d){return d.count;}),
        borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.1)',
        fill:true,tension:0.3,pointRadius:2,pointHoverRadius:5
      }]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{y:{ticks:{callback:function(v){return f(v)+'건';}},beginAtZero:true}}
    }
  });
}

function rIntentBar(data){
  var ctx=document.getElementById('intentBarChart').getContext('2d');
  if(chart2)chart2.destroy();
  var ints=data.intents||[];
  var cols=['#8b5cf6','#10b981','#f97316','#6366f1','#ef4444','#f59e0b','#06b6d4','#ec4899','#84cc16','#14b8a6'];
  chart2=new Chart(ctx,{
    type:'bar',
    data:{
      labels:ints.map(function(d){return d.label;}),
      datasets:[{
        label:'건수',data:ints.map(function(d){return d.count;}),
        backgroundColor:cols.slice(0,ints.length),borderRadius:4
      }]
    },
    options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',
      plugins:{legend:{display:false}},
      scales:{x:{ticks:{callback:function(v){return f(v)+'건';}},beginAtZero:true}}
    }
  });
}

function rSentiment(data){
  var ctx=document.getElementById('sentimentChart').getContext('2d');
  if(chart3)chart3.destroy();
  var s=data.sentiment||{positive:0,negative:0,neutral:0};
  chart3=new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:['긍정','부정','중립'],
      datasets:[{data:[s.positive||0,s.negative||0,s.neutral||0],
        backgroundColor:['#10b981','#ef4444','#94a3b8'],borderWidth:2,borderColor:'#fff'}]
    },
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{position:'right',labels:{font:{size:12},padding:12}}}
    }
  });
}

function rKeywords(kws){
  var h='';
  (kws||[]).forEach(function(k){
    var cl=k.trend==='hot'?'hot':(k.trend==='rising'?'rising':'stable');
    h+='<span class="aa-tag '+cl+'">'+k.word+' ('+k.count+')</span>';
  });
  if(!h)h='<span style="color:#94a3b8">데이터 없음</span>';
  document.getElementById('keywordCloud').innerHTML=h;
}

function rInsight(data){
  var ins=data.insights||[];
  var h='';
  if(!ins.length)h='<p style="color:#94a3b8">🧘 현재 특이사항이 없습니다.</p>';
  else ins.forEach(function(i){
    var cl=i.level==='danger'?'danger':(i.level==='warning'?'warning':'info');
    h+='<div class="aa-tag '+(cl==='danger'?'hot':(cl==='warning'?'rising':'stable'))+'" style="font-size:13px;padding:4px 14px;margin:4px 6px">'+i.msg+'</div>';
  });
  document.getElementById('aiInsightContent').innerHTML=h;
}

function rIssueLog(list){
  var h='';
  (list||[]).forEach(function(r){
    h+='<tr><td>'+((r.created_at||'').substring(0,16))+'</td>';
    h+='<td>'+(r.member_name||r.member_id||'-')+'</td>';
    h+='<td>'+(r.issue_type||'-')+'</td>';
    h+='<td>'+(r.result||'해결')+'</td>';
    h+='<td>'+(r.duration||'-')+'</td></tr>';
  });
  if(!h)h='<tr><td colspan="5">기록된 이슈가 없습니다.</td></tr>';
  document.getElementById('issueLogBody').innerHTML=h;
}

function load(){
  J(API+'?action=ai_analytics&period=30').then(function(r){return r.json();}).then(function(d){
    if(!d.ok)return;
    rKPI(d);rDailyTrend(d);rIntentBar(d);rSentiment(d);rKeywords(d.keywords||[]);
  });
  J(API+'?action=ai_insight_report').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rInsight(d);
  });
  J(API+'?action=ai_auto_resolve&mode=list&limit=50').then(function(r){return r.json();}).then(function(d){
    if(d.ok)rIssueLog(d.list||[]);
  });
}

load();
setInterval(load,120000);
})();
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
