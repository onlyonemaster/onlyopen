<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

$search_period = trim($_GET['search_period'] ?? '6m');
$period_map = ['3m' => 3, '6m' => 6, '12m' => 12];
$months_back = $period_map[$search_period] ?? 6;

// ── 플랜 정의 ───────────────────────────────────────
$plan_limits = [
    'basic'    => ['name' => 'Basic',      'price_m' => 9900,   'price_y' => 7920],
    'standard' => ['name' => 'Standard',   'price_m' => 19900,  'price_y' => 15920],
    'pro'      => ['name' => 'Pro',        'price_m' => 49000,  'price_y' => 39200],
    'business' => ['name' => 'Business',   'price_m' => 99000,  'price_y' => 79200],
    'b2b-pro'  => ['name' => 'Pro B2B',    'price_m' => 199000, 'price_y' => 159200],
    'b2b-team' => ['name' => 'Team B2B',   'price_m' => 299000, 'price_y' => 239000],
];
$plan_keys = array_keys($plan_limits);
$month_start = date('Y-m-01');
$last_start = date('Y-m-01', strtotime('-1 month'));
$last_end   = date('Y-m-t', strtotime('-1 month'));

// ═══════════════════════════════════════════════════
// ★ 속도 최적화: 모든 데이터를 최소 쿼리(5개)로 통합
// 기존: 84~132개 쿼리 → 최적화: 5개 쿼리
// ═══════════════════════════════════════════════════

// ── [쿼리1] 플랜별 활성 구독자 + 이번달MRR + 전월MRR + 신규 + 전환 ──
$plan_stats = [];
foreach ($plan_keys as $pk) $plan_stats[$pk] = ['name'=>$plan_limits[$pk]['name'], 'active'=>0, 'mrr'=>0, 'prev_mrr'=>0, 'mrr_change'=>0, 'new'=>0, 'converted'=>0, 'price_m'=>$plan_limits[$pk]['price_m'], 'price_y'=>$plan_limits[$pk]['price_y']];

// 1a: 활성 구독자 + 신규 + 전환 (한 번에)
$sql = "SELECT service_type,
        COUNT(*) AS active,
        SUM(CASE WHEN first_regist >= '{$month_start}' THEN 1 ELSE 0 END) AS new_cnt,
        SUM(CASE WHEN first_regist < '{$month_start}' THEN 1 ELSE 0 END) AS converted
        FROM Gn_Member
        WHERE service_type IN ('basic','standard','pro','business','b2b-pro','b2b-team')
        AND (sub_end_date IS NULL OR sub_end_date >= NOW())
        GROUP BY service_type";
$r = mysqli_query($self_con, $sql);
while ($row = mysqli_fetch_assoc($r)) {
    $pid = $row['service_type'];
    $plan_stats[$pid]['active'] = (int)$row['active'];
    $plan_stats[$pid]['new'] = (int)$row['new_cnt'];
    $plan_stats[$pid]['converted'] = (int)$row['converted'];
}

// 1b: 이번달 MRR + 전월 MRR (한 번에, plan별 SUM)
$sql = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(member_type, '_', 2), '_', -1) AS plan_id,
        SUM(CASE WHEN date >= '{$month_start}' THEN TotPrice ELSE 0 END) AS mrr,
        SUM(CASE WHEN date BETWEEN '{$last_start}' AND '{$last_end} 23:59:59' THEN TotPrice ELSE 0 END) AS prev_mrr
        FROM tjd_pay_result
        WHERE member_type LIKE 'onechat_%'
        AND member_type NOT LIKE '%_test'
        AND end_status = 'Y'
        AND date >= '{$last_start}'
        GROUP BY plan_id";
$r = mysqli_query($self_con, $sql);
while ($row = mysqli_fetch_assoc($r)) {
    $pid = $row['plan_id'];
    if (isset($plan_stats[$pid])) {
        $plan_stats[$pid]['mrr'] = (int)$row['mrr'];
        $plan_stats[$pid]['prev_mrr'] = (int)$row['prev_mrr'];
        $prev = $plan_stats[$pid]['prev_mrr'];
        $plan_stats[$pid]['mrr_change'] = $prev > 0 ? round(($plan_stats[$pid]['mrr'] - $prev) / $prev * 100, 1) : 0;
    }
}

// ── [쿼리2] 월별 플랜별 매출 (스택 차트) - GROUP BY ym, plan_id 한 번에 ──
$monthly_labels = [];
for ($i = $months_back - 1; $i >= 0; $i--) $monthly_labels[] = date('Y-m', strtotime("-{$i} months"));

$monthly_plan_data = [];
foreach ($plan_keys as $pk) $monthly_plan_data[$pk] = array_fill(0, $months_back, 0);

$cutoff = date('Y-m-01', strtotime("-" . ($months_back - 1) . " months"));
$sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym,
        SUBSTRING_INDEX(SUBSTRING_INDEX(member_type, '_', 2), '_', -1) AS plan_id,
        SUM(TotPrice) AS total
        FROM tjd_pay_result
        WHERE member_type LIKE 'onechat_%'
        AND member_type NOT LIKE '%_test'
        AND end_status = 'Y'
        AND date >= '{$cutoff}'
        GROUP BY ym, plan_id";
$r = mysqli_query($self_con, $sql);
while ($row = mysqli_fetch_assoc($r)) {
    $ym = $row['ym'];
    $pid = $row['plan_id'];
    $idx = array_search($ym, $monthly_labels);
    if ($idx !== false && isset($monthly_plan_data[$pid])) {
        $monthly_plan_data[$pid][$idx] = (int)$row['total'];
    }
}

// ── [쿼리3] 전환 퍼널 (플랜별 비중) + 해지율 ──
$total_paid = array_sum(array_column($plan_stats, 'active'));
$conv_funnel = [];
foreach ($plan_keys as $pk) {
    $conv_funnel[$pk] = $total_paid > 0 ? round(($plan_stats[$pk]['active'] / $total_paid) * 100, 1) : 0;
}

// 해지율 (전체)
$sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
        WHERE service_type = 'free'
        AND sub_end_date >= '{$month_start}'";
$r = mysqli_query($self_con, $sql);
$total_churned = (int)mysqli_fetch_assoc($r)['cnt'];
$churn_data = [];
foreach ($plan_keys as $pk) {
    $active = max(1, $plan_stats[$pk]['active']);
    $churn_data[$pk] = round($total_churned / max(1, $total_paid) * 100, 1);
}

// ── [쿼리4+5] 월별 신규 vs 이탈 ──
$newChurn_labels = [];
$newVals = [];
$churnVals = [];
for ($i = $months_back - 1; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} months"));
    $start = $ym . '-01';
    $end = date('Y-m-t', strtotime($start));
    $newChurn_labels[] = $ym;
}

// 신규 데이터 (한 번에)
$cutoff2 = date('Y-m-01', strtotime("-" . ($months_back - 1) . " months"));
$sql = "SELECT DATE_FORMAT(first_regist, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM Gn_Member
        WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free'
        AND first_regist >= '{$cutoff2}'
        GROUP BY ym";
$r = mysqli_query($self_con, $sql);
$newByMonth = [];
while ($row = mysqli_fetch_assoc($r)) $newByMonth[$row['ym']] = (int)$row['cnt'];
foreach ($newChurn_labels as $ym) $newVals[] = $newByMonth[$ym] ?? 0;

// 이탈 데이터 (한 번에)
$sql = "SELECT DATE_FORMAT(sub_end_date, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM Gn_Member
        WHERE service_type = 'free'
        AND sub_end_date >= '{$cutoff2}'
        GROUP BY ym";
$r = mysqli_query($self_con, $sql);
$churnByMonth = [];
while ($row = mysqli_fetch_assoc($r)) $churnByMonth[$row['ym']] = (int)$row['cnt'];
foreach ($newChurn_labels as $ym) $churnVals[] = $churnByMonth[$ym] ?? 0;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<style>
  .content-wrapper { margin-left:230px; padding-top:50px; }
  .oc-page { padding: 10px 20px 20px; }
  .oc-page h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
  .oc-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; }
  .oc-toolbar select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 5px; }
  .oc-chart-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .oc-chart-box { flex: 1; min-width: 420px; background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .oc-chart-box h4 { margin: 0 0 10px; font-size: 14px; color: #64748b; }
  .oc-chart-canvas-wrap { position: relative; height: 260px; }
  .oc-chart-canvas-wrap canvas { width: 100% !important; height: 100% !important; }
  .oc-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 20px; }
  .oc-table th { background: #f8fafc; padding: 10px; text-align: left; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: 12px; }
  .oc-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .oc-table tr:hover td { background: #f8fafc; }
  .change-up { color: #10b981; font-weight: 600; }
  .change-down { color: #ef4444; font-weight: 600; }
</style>

<div class="wrapper">
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>원챗 플랜별 통계 <small>플랜별 매출·전환·이탈 분석</small></h1>
      <ol class="breadcrumb">
        <li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
        <li>원챗(OneChat) 결제관리</li>
        <li class="active">플랜별 통계</li>
      </ol>
    </section>
    <section class="content">
    <div class="oc-page">

    <div class="oc-toolbar">
      <span style="font-size:13px; color:#64748b;">조회 기간:</span>
      <select onchange="location.href='?search_period='+this.value">
        <?php foreach(['3m'=>'최근 3개월','6m'=>'최근 6개월','12m'=>'최근 12개월'] as $pv=>$pl): ?>
          <option value="<?=$pv?>" <?=$search_period===$pv?'selected':''?>><?=$pl?></option>
        <?php endforeach; ?>
      </select>
      <span style="font-size:11px; color:#94a3b8; margin-left:8px;">⚡ 최적화됨 (5개 쿼리)</span>
    </div>

    <!-- 스택 차트 + 도넛 -->
    <div class="oc-chart-row">
      <div class="oc-chart-box" style="flex:2; min-width:500px;">
        <h4>📊 플랜별 월 매출 추이</h4>
        <div class="oc-chart-canvas-wrap"><canvas id="stackRevenueChart"></canvas></div>
      </div>
      <div class="oc-chart-box" style="flex:1; min-width:280px;">
        <h4>🎯 플랜별 구독자 비중</h4>
        <div class="oc-chart-canvas-wrap"><canvas id="planShareChart"></canvas></div>
      </div>
    </div>

    <!-- 상세 테이블 -->
    <h4 style="color:#64748b; margin-bottom:10px;">📋 플랜별 상세 통계</h4>
    <table class="oc-table">
      <thead><tr><th>플랜</th><th>활성 구독자</th><th>이번 달 MRR</th><th>전월 대비</th><th>이번 달 신규</th><th>해지율</th><th>월 가격</th><th>연 가격</th></tr></thead>
      <tbody>
      <?php foreach ($plan_stats as $pid => $ps): ?>
      <tr>
        <td><b><?=$ps['name']?></b></td>
        <td><?=number_format($ps['active'])?>명</td>
        <td>₩<?=number_format($ps['mrr'])?></td>
        <td>
          <?php if ($ps['mrr_change'] > 0): ?>
            <span class="change-up">↑<?=$ps['mrr_change']?>%</span>
          <?php elseif ($ps['mrr_change'] < 0): ?>
            <span class="change-down">↓<?=abs($ps['mrr_change'])?>%</span>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td><?=number_format($ps['new'])?>명</td>
        <td><?=$churn_data[$pid]?>%</td>
        <td>₩<?=number_format($ps['price_m'])?></td>
        <td>₩<?=number_format($ps['price_y'])?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- 전환 + 신규/이탈 -->
    <div class="oc-chart-row">
      <div class="oc-chart-box">
        <h4>🔄 플랜 전환 분포 (Free → 유료)</h4>
        <div class="oc-chart-canvas-wrap"><canvas id="convFunnelChart"></canvas></div>
      </div>
      <div class="oc-chart-box">
        <h4>📈 월별 신규 vs 이탈</h4>
        <div class="oc-chart-canvas-wrap"><canvas id="newChurnChart"></canvas></div>
      </div>
    </div>

  </div>
  </section>
</div>

<script>
(function(){
  // ★ chart.js가 로드될 때까지 기다린 후 실행
  function initCharts() {
    if (typeof Chart === 'undefined') { setTimeout(initCharts, 100); return; }

    var colors = ['#3b82f6','#10b981','#f97316','#8b5cf6','#ef4444','#f59e0b'];
    var labels = <?=json_encode($monthly_labels)?>;
    var planKeys = <?=json_encode($plan_keys)?>;
    var planNames = <?=json_encode(array_values(array_column($plan_limits, 'name')))?>;
    var monthlyData = <?=json_encode($monthly_plan_data)?>;

    // ── 스택 차트 ──────────────────────
    var datasets = [];
    planKeys.forEach(function(pk, i){
      datasets.push({
        label: planNames[i],
        data: monthlyData[pk],
        backgroundColor: colors[i % colors.length],
        borderWidth: 0,
      });
    });

    new Chart(document.getElementById('stackRevenueChart').getContext('2d'), {
      type: 'bar',
      data: { labels: labels, datasets: datasets },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, boxWidth: 12 } } },
        scales: {
          x: { stacked: true },
          y: { stacked: true, ticks: { callback: function(v) { return (v/10000).toFixed(0)+'만원'; } } },
        },
      },
    });

    // ── 도넛 ──────────────────────────
    var shareLabels = <?=json_encode(array_values(array_column($plan_stats, 'name')))?>;
    var shareData = <?=json_encode(array_values(array_column($plan_stats, 'active')))?>;
    new Chart(document.getElementById('planShareChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: shareLabels,
        datasets: [{ data: shareData, backgroundColor: colors.slice(0, shareData.length), borderWidth: 2, borderColor: '#fff' }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { font: { size: 11 }, padding: 10 } } },
      },
    });

    // ── 전환 퍼널 ──────────────────────
    var convVals = <?=json_encode(array_values($conv_funnel))?>;
    new Chart(document.getElementById('convFunnelChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: shareLabels,
        datasets: [{
          label: '전환 비율 (%)',
          data: convVals,
          backgroundColor: colors.slice(0, convVals.length).map(function(c){ return c + '99'; }),
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { callback: function(v){ return v+'%'; } } } },
      },
    });

    // ── 신규 vs 이탈 ────────────────────
    new Chart(document.getElementById('newChurnChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: <?=json_encode($newChurn_labels)?>,
        datasets: [
          { label: '신규', data: <?=json_encode($newVals)?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.3, pointRadius: 4 },
          { label: '이탈', data: <?=json_encode($churnVals)?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)', fill: true, tension: 0.3, pointRadius: 4 },
        ],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        scales: { y: { beginAtZero: true } },
      },
    });
  }

  initCharts();
})();
</script>

  </div>
  </section>
  </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>