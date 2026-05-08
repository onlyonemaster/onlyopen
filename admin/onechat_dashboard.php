<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<style>
  .oc-dash { padding: 15px 20px; }
  .oc-dash h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
  .oc-dash h3 i { color: #f97316; margin-right: 6px; }

  .oc-kpi-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
  .oc-kpi-card { flex: 1; min-width: 140px; background: #fff; border-radius: 10px;
    padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center; }
  .oc-kpi-card .kpi-val { font-size: 28px; font-weight: 800; color: #1e293b; margin: 6px 0; }
  .oc-kpi-card .kpi-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing:.5px; }
  .oc-kpi-card .kpi-sub { font-size: 13px; margin-top: 4px; }
  .oc-kpi-card .kpi-sub.up { color: #10b981; }
  .oc-kpi-card .kpi-sub.down { color: #ef4444; }
  .oc-kpi-icon { font-size: 22px; margin-bottom: 4px; }

  .oc-chart-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .oc-chart-box { flex: 1; min-width: 380px; background: #fff; border-radius: 10px;
    padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .oc-chart-box h4 { margin: 0 0 10px; font-size: 14px; color: #64748b; }
  .oc-chart-canvas-wrap { position: relative; height: 260px; }
  .oc-chart-canvas-wrap canvas { width: 100% !important; height: 100% !important; }

  .oc-insight-box { background: #fff; border-radius: 10px; padding: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
  .oc-insight-box h4 { margin: 0 0 10px; font-size: 14px; color: #64748b; }
  .oc-insight-item { padding: 8px 0; font-size: 13px; color: #334155; line-height: 1.7; border-bottom: 1px solid #f1f5f9; }
  .oc-insight-item:last-child { border-bottom: none; }

  .oc-recent-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .oc-recent-table th { background: #f8fafc; padding: 8px 10px; text-align: left; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
  .oc-recent-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
  .oc-recent-table .badge-test { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
  .oc-recent-table .badge-real { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
</style>

<div class="wrapper">
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>원챗(OneChat) 대시보드 <small>구독 현황 한눈에 보기</small></h1>
      <ol class="breadcrumb">
        <li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
        <li class="active">원챗(OneChat) 결제관리</li>
      </ol>
    </section>

    <section class="content">
      <div class="oc-dash">

      <!-- KPI 카드 -->
      <div class="oc-kpi-row" id="kpiRow">
        <div class="oc-kpi-card"><div class="oc-kpi-icon">👥</div><div class="kpi-label">총 구독자</div><div class="kpi-val" id="kpiTotal">-</div><div class="kpi-sub" id="kpiGrowth">-</div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-icon">💰</div><div class="kpi-label">이번 달 매출 (MRR)</div><div class="kpi-val" id="kpiMRR">-</div><div class="kpi-sub"></div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-icon">🆕</div><div class="kpi-label">이번 달 신규</div><div class="kpi-val" id="kpiNew">-</div><div class="kpi-sub"></div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-icon">📊</div><div class="kpi-label">ARPU</div><div class="kpi-val" id="kpiARPU">-</div><div class="kpi-sub"></div></div>
        <div class="oc-kpi-card"><div class="oc-kpi-icon">📉</div><div class="kpi-label">해지율</div><div class="kpi-val" id="kpiChurn">-</div><div class="kpi-sub"></div></div>
      </div>

      <!-- 차트 -->
      <div class="oc-chart-row">
        <div class="oc-chart-box">
          <h4>📈 월별 매출 추이</h4>
          <div class="oc-chart-canvas-wrap"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="oc-chart-box">
          <h4>🍩 플랜별 구독 분포</h4>
          <div class="oc-chart-canvas-wrap"><canvas id="planDistChart"></canvas></div>
        </div>
      </div>

      <!-- AI 인사이트 + 최근 결제 -->
      <div class="oc-chart-row">
        <div class="oc-insight-box" style="flex:1; min-width:320px;">
          <h4>🤖 AI 요약 인사이트</h4>
          <div id="aiInsights">로딩 중...</div>
        </div>
        <div class="oc-chart-box" style="flex:1.5; min-width:400px;">
          <h4>🧾 최근 구독 결제 10건</h4>
          <table class="oc-recent-table">
            <thead><tr><th>주문번호</th><th>회원</th><th>플랜</th><th>금액</th><th>유형</th><th>일시</th></tr></thead>
            <tbody id="recentPaymentsBody"><tr><td colspan="6">로딩 중...</td></tr></tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
(function(){
  var API_BASE = '/admin/ajax/onechat_dashboard_api.php';

  function fmt(n) { return n.toLocaleString('ko-KR'); }
  function won(n) { return '₩' + fmt(n); }

  function renderKPI(kpi) {
    document.getElementById('kpiTotal').textContent = fmt(kpi.total_subscribers) + '명';
    var gEl = document.getElementById('kpiGrowth');
    if (kpi.subscriber_growth >= 0) {
      gEl.className = 'kpi-sub up';
      gEl.textContent = '↑' + kpi.subscriber_growth + '%';
    } else {
      gEl.className = 'kpi-sub down';
      gEl.textContent = '↓' + Math.abs(kpi.subscriber_growth) + '%';
    }
    document.getElementById('kpiMRR').textContent = won(kpi.mrr);
    document.getElementById('kpiNew').textContent = fmt(kpi.new_this_month) + '명';
    document.getElementById('kpiARPU').textContent = won(kpi.arpu);
    document.getElementById('kpiChurn').textContent = kpi.churn_rate + '%';
  }

  var revenueChartInst = null;
  function renderRevenueChart(labels, values) {
    var ctx = document.getElementById('revenueChart').getContext('2d');
    if (revenueChartInst) revenueChartInst.destroy();
    revenueChartInst = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: '월 매출',
          data: values,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,0.08)',
          fill: true,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#3b82f6',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { ticks: { callback: function(v) { return (v/10000).toFixed(0)+'만원'; } } },
        },
      },
    });
  }

  var planChartInst = null;
  function renderPlanDistChart(distribution) {
    var ctx = document.getElementById('planDistChart').getContext('2d');
    if (planChartInst) planChartInst.destroy();
    var colors = ['#3b82f6','#10b981','#f97316','#8b5cf6','#ef4444','#f59e0b','#06b6d4'];
    planChartInst = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: distribution.map(function(d){ return d.plan; }),
        datasets: [{
          data: distribution.map(function(d){ return d.count; }),
          backgroundColor: colors.slice(0, distribution.length),
          borderWidth: 2,
          borderColor: '#fff',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { font: { size: 12 }, padding: 12 } },
        },
      },
    });
  }

  function renderAIInsights(kpi, distribution) {
    var insights = [];
    if (kpi.total_subscribers === 0) {
      insights.push('아직 구독 데이터가 충분하지 않습니다. 첫 구독이 발생하면 인사이트가 생성됩니다.');
    } else {
      var proCnt = 0, bizCnt = 0;
      distribution.forEach(function(d) {
        if (d.plan_id === 'pro' || d.plan_id === 'b2b-pro') proCnt += d.count;
        if (d.plan_id === 'business' || d.plan_id === 'b2b-biz') bizCnt += d.count;
      });
      insights.push('📊 유료 구독자 ' + fmt(kpi.total_subscribers) + '명 중 Pro/B2B 플랜이 약 ' + Math.round((proCnt+bizCnt)/kpi.total_subscribers*100) + '%를 차지합니다.');
      if (kpi.mrr > kpi.prev_mrr && kpi.prev_mrr > 0) {
        insights.push('📈 이번 달 MRR이 전월 대비 ' + Math.round((kpi.mrr-kpi.prev_mrr)/kpi.prev_mrr*100) + '% 증가했습니다.');
      }
      if (kpi.arpu > 0) {
        insights.push('💰 ARPU는 ' + won(kpi.arpu) + '입니다. Basic→Pro 전환 독려로 ARPU 상승을 기대할 수 있습니다.');
      }
      if (kpi.new_this_month > 0) {
        insights.push('🆕 이번 달 ' + fmt(kpi.new_this_month) + '명의 신규 유료 구독자가 발생했습니다.');
      }
    }
    insights.push('💡 <b>팁:</b> 구독회원 관리 페이지에서 사용량 80% 이상 회원에게 업셀을 제안해보세요.');
    document.getElementById('aiInsights').innerHTML = insights.map(function(m){ return '<div class="oc-insight-item">'+m+'</div>'; }).join('');
  }

  function renderRecentPayments(payments) {
    var html = '';
    payments.forEach(function(p){
      var typeBadge = p.is_test ? '<span class="badge-test">🧪테스트</span>' : '<span class="badge-real">✅실결제</span>';
      html += '<tr>' +
        '<td>' + p.order_id + '</td>' +
        '<td>' + p.member_name + '</td>' +
        '<td>' + p.plan_name + '</td>' +
        '<td>' + won(p.amount) + '</td>' +
        '<td>' + typeBadge + '</td>' +
        '<td>' + (p.date||'').substring(0,10) + '</td>' +
        '</tr>';
    });
    if (!html) html = '<tr><td colspan="6">결제 내역이 없습니다.</td></tr>';
    document.getElementById('recentPaymentsBody').innerHTML = html;
  }

  function loadAll() {
    // ★ 속도 최적화: 3개 API 호출 → 1개로 통합 (action=all)
    fetch(API_BASE + '?action=all')
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.ok) { console.error(d.error); return; }
        renderKPI(d.kpi);
        renderPlanDistChart(d.plan_distribution);
        renderAIInsights(d.kpi, d.plan_distribution);
        renderRevenueChart(d.revenue_labels, d.revenue_values);
        renderRecentPayments(d.payments);
      })
      .catch(function(err){ console.error('대시보드 로딩 실패:', err); });
  }

  loadAll();
  setInterval(loadAll, 300000);
})();
</script>

    </div>
  </section>
  </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>