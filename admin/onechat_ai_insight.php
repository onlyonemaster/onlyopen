<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

// ── AI 인사이트 데이터 계산 ──────────────────────────
$planNames = ['free'=>'Free','basic'=>'Basic','standard'=>'Standard','pro'=>'Pro','business'=>'Business','b2b-pro'=>'Pro B2B','b2b-team'=>'Team B2B','team'=>'Team'];

// 1. 이탈 위험 TOP10: 사용량 85%+ 중 만료 30일 이내인 회원
$churn_risk = [];
$sql = "SELECT mem_id, mem_name, mem_nick, service_type,
        ai_profile_limit, ai_profile_used,
        ai_msg_person_limit, ai_msg_person_used,
        ai_resp_limit, ai_resp_used,
        sub_end_date
        FROM Gn_Member
        WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free'
        AND ai_profile_limit > 0
        AND (ai_profile_used / ai_profile_limit) >= 0.85
        AND sub_end_date IS NOT NULL AND sub_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
        ORDER BY (ai_profile_used / ai_profile_limit) DESC
        LIMIT 10";
$r = mysqli_query($self_con, $sql);
while ($row = mysqli_fetch_assoc($r)) {
    $pPct = $row['ai_profile_limit'] > 0 ? round($row['ai_profile_used'] / $row['ai_profile_limit'] * 100) : 0;
    $daysLeft = max(0, round((strtotime($row['sub_end_date']) - time()) / 86400));
    $churn_risk[] = [
        'mem_id'   => $row['mem_id'],
        'mem_name' => $row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'],
        'plan'     => $planNames[$row['service_type']] ?? $row['service_type'],
        'used_pct' => $pPct,
        'days_left' => $daysLeft,
        'risk_level' => $daysLeft <= 7 ? 'high' : ($daysLeft <= 15 ? 'medium' : 'low'),
    ];
}

// 2. 업셀 기회 TOP10: 사용량 70%+이고 현재 Basic/Standard인 회원
$upsell = [];
$sql = "SELECT mem_id, mem_name, mem_nick, service_type,
        ai_profile_limit, ai_profile_used,
        ai_msg_person_limit, ai_msg_person_used,
        ai_resp_limit, ai_resp_used
        FROM Gn_Member
        WHERE service_type IN ('basic','standard')
        AND ai_profile_limit > 0
        AND (ai_profile_used / ai_profile_limit) >= 0.70
        ORDER BY (ai_profile_used / ai_profile_limit) DESC
        LIMIT 10";
$r = mysqli_query($self_con, $sql);
$upsell_targets = ['basic' => 'Pro', 'standard' => 'Pro'];
while ($row = mysqli_fetch_assoc($r)) {
    $pPct = $row['ai_profile_limit'] > 0 ? round($row['ai_profile_used'] / $row['ai_profile_limit'] * 100) : 0;
    $mPct = $row['ai_msg_person_limit'] > 0 ? round($row['ai_msg_person_used'] / $row['ai_msg_person_limit'] * 100) : 0;
    $upsell[] = [
        'mem_id'   => $row['mem_id'],
        'mem_name' => $row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'],
        'plan'     => $planNames[$row['service_type']] ?? $row['service_type'],
        'target'   => $upsell_targets[$row['service_type']] ?? 'Pro',
        'profile_pct' => $pPct,
        'msg_pct'   => $mPct,
    ];
}

// 3. 다음 달 MRR 예측 (★ 최적화: 루프 3개 → GROUP BY 1개)
$recent_revs = [];
$cutoff_3m = date('Y-m-01', strtotime('-2 months'));
$sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(TotPrice) AS total
        FROM tjd_pay_result
        WHERE member_type LIKE 'onechat_%'
        AND member_type NOT LIKE '%_test'
        AND end_status = 'Y'
        AND date >= '{$cutoff_3m}'
        GROUP BY ym ORDER BY ym";
$r = mysqli_query($self_con, $sql);
$revByMonth = [];
while ($row = mysqli_fetch_assoc($r)) $revByMonth[$row['ym']] = (int)$row['total'];
for ($i = 2; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} months"));
    $recent_revs[] = $revByMonth[$ym] ?? 0;
}

$avg_growth = 0;
if (count($recent_revs) >= 2 && $recent_revs[0] > 0) {
    $g1 = ($recent_revs[1] - $recent_revs[0]) / max(1, $recent_revs[0]);
    if (isset($recent_revs[2]) && $recent_revs[1] > 0) {
        $g2 = ($recent_revs[2] - $recent_revs[1]) / max(1, $recent_revs[1]);
        $avg_growth = ($g1 + $g2) / 2;
    } else {
        $avg_growth = $g1;
    }
}
$current_mrr = $recent_revs[count($recent_revs)-1] ?? 0;
$predicted_mrr = (int)($current_mrr * (1 + $avg_growth));
$predicted_optimistic = (int)($predicted_mrr * 1.08);
$predicted_pessimistic = (int)($predicted_mrr * 0.92);

// 4. 요약 통계 (★ 최적화: 2개 쿼리 → 1개로 통합)
$sql = "SELECT
        SUM(CASE WHEN service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free' THEN 1 ELSE 0 END) AS paid,
        SUM(CASE WHEN service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free' AND first_regist >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN 1 ELSE 0 END) AS new_paid
        FROM Gn_Member";
$r = mysqli_query($self_con, $sql);
$row = mysqli_fetch_assoc($r);
$paid_users = (int)$row['paid'];
$new_paid   = (int)$row['new_paid'];
?>
<style>
  .content-wrapper { margin-left:230px; padding-top:50px; }
  .oc-page { padding: 10px 20px 20px; }
  .oc-page h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
  .oc-chart-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .oc-card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

  /* 예측 카드 */
  .predict-cards { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
  .predict-card { flex: 1; min-width: 140px; border-radius: 10px; padding: 16px; text-align: center; }
  .predict-card.basic { background: #eff6ff; border: 1px solid #bfdbfe; }
  .predict-card.opt { background: #ecfdf5; border: 1px solid #a7f3d0; }
  .predict-card.pess { background: #fef2f2; border: 1px solid #fecaca; }
  .predict-card .val { font-size: 24px; font-weight: 800; margin: 6px 0; }
  .predict-card .label { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
  .predict-card.opt .val { color: #059669; }
  .predict-card.pess .val { color: #dc2626; }
  .predict-card.basic .val { color: #3b82f6; }

  /* 리스크 테이블 */
  .risk-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
  .risk-table th { background: #f8fafc; padding: 8px 10px; text-align: left; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: 12px; }
  .risk-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .risk-table tr:hover td { background: #f8fafc; }
  .badge-high { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .badge-medium { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .badge-low { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .badge-upsell { background: #ede9fe; color: #5b21b6; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }

  /* 인사이트 텍스트 */
  .insight-block { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
  .insight-block h4 { margin: 0 0 12px; font-size: 14px; color: #64748b; }
  .insight-item { padding: 8px 0; font-size: 13px; color: #334155; line-height: 1.7; border-bottom: 1px solid #f1f5f9; }
  .insight-item:last-child { border-bottom: none; }
</style>

<div class="wrapper">
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>원챗 AI 결제 인사이트 <small>이탈 위험·업셀 기회·매출 예측</small></h1>
      <ol class="breadcrumb">
        <li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
        <li>원챗(OneChat) 결제관리</li>
        <li class="active">AI 결제 인사이트</li>
      </ol>
    </section>
    <section class="content">
    <div class="oc-page">

    <!-- 다음 달 예측 -->
    <h4 style="color:#64748b; margin-bottom:10px;">📈 다음 달 MRR 예측 (AI 기반)</h4>
    <div class="predict-cards">
      <div class="predict-card basic">
        <div class="label">기본 예측</div>
        <div class="val">₩<?=number_format($predicted_mrr)?></div>
        <div style="font-size:11px; color:#64748b;"><?=round($avg_growth*100,1)?>% 성장률</div>
      </div>
      <div class="predict-card opt">
        <div class="label">낙관적 예측</div>
        <div class="val">₩<?=number_format($predicted_optimistic)?></div>
        <div style="font-size:11px; color:#059669;">+8% 시나리오</div>
      </div>
      <div class="predict-card pess">
        <div class="label">비관적 예측</div>
        <div class="val">₩<?=number_format($predicted_pessimistic)?></div>
        <div style="font-size:11px; color:#dc2626;">-8% 시나리오</div>
      </div>
    </div>

    <!-- 이탈 위험 + 업셀 기회 -->
    <div class="oc-chart-row">
      <!-- 이탈 위험 TOP10 -->
      <div class="oc-card" style="flex:1; min-width:380px;">
        <h4 style="margin:0 0 10px; font-size:14px; color:#64748b;">🚨 이탈 위험 TOP10 (사용량 85%+ & 만료 30일 이내)</h4>
        <table class="risk-table">
          <thead><tr><th>회원</th><th>플랜</th><th>프로필 사용률</th><th>남은 일수</th><th>위험도</th></tr></thead>
          <tbody>
          <?php foreach ($churn_risk as $cr):
            $riskBadge = $cr['risk_level'] === 'high' ? '<span class="badge-high">🔴높음</span>' :
                        ($cr['risk_level'] === 'medium' ? '<span class="badge-medium">🟡중간</span>' :
                        '<span class="badge-low">🟢낮음</span>');
          ?>
          <tr>
            <td><b><?=htmlspecialchars($cr['mem_id'])?></b><br><span style="font-size:11px; color:#94a3b8;"><?=htmlspecialchars($cr['mem_name'])?></span></td>
            <td><?=$cr['plan']?></td>
            <td style="color:<?=$cr['used_pct']>=95?'#ef4444':'#f59e0b'?>; font-weight:600;"><?=$cr['used_pct']?>%</td>
            <td><b><?=$cr['days_left']?>일</b></td>
            <td><?=$riskBadge?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($churn_risk)): ?>
          <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">✅ 현재 이탈 위험이 높은 회원이 없습니다.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- 업셀 기회 TOP10 -->
      <div class="oc-card" style="flex:1; min-width:380px;">
        <h4 style="margin:0 0 10px; font-size:14px; color:#64748b;">💎 업셀 기회 TOP10 (Basic/Standard → Pro 권장)</h4>
        <table class="risk-table">
          <thead><tr><th>회원</th><th>현재 플랜</th><th>추천 플랜</th><th>프로필</th><th>AI메시지</th></tr></thead>
          <tbody>
          <?php foreach ($upsell as $us): ?>
          <tr>
            <td><b><?=htmlspecialchars($us['mem_id'])?></b><br><span style="font-size:11px; color:#94a3b8;"><?=htmlspecialchars($us['mem_name'])?></span></td>
            <td><?=$us['plan']?></td>
            <td><span class="badge-upsell">→ <?=$us['target']?></span></td>
            <td style="color:<?=$us['profile_pct']>=90?'#ef4444':'#f59e0b'?>; font-weight:600;"><?=$us['profile_pct']?>%</td>
            <td><?=$us['msg_pct']?>%</td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($upsell)): ?>
          <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">📊 아직 충분한 데이터가 쌓이지 않았습니다.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- AI 인사이트 요약 -->
    <div class="insight-block">
      <h4>🤖 AI 인사이트 요약</h4>
      <?php
      $insights = [];
      if ($paid_users > 0) {
          $insights[] = '📊 현재 유료 구독자 <b>' . number_format($paid_users) . '명</b>입니다. 이번 달 신규 유료 전환은 <b>' . number_format($new_paid) . '명</b>입니다.';
      } else {
          $insights[] = '💡 아직 유료 구독자가 없습니다. OneChat에서 무료 체험 후 유료 전환을 유도하는 마케팅 캠페인을 검토해보세요.';
      }

      if (!empty($churn_risk)) {
          $high_risk = array_filter($churn_risk, function($c){ return $c['risk_level'] === 'high'; });
          $insights[] = '⚠️ 이탈 위험 HIGH 회원 <b>' . count($high_risk) . '명</b>이 있습니다. 만료 7일 이내로 즉시 조치가 필요합니다.';
      }

      if (!empty($upsell)) {
          $insights[] = '💎 Basic/Standard 플랜 중 <b>' . count($upsell) . '명</b>이 Pro 업셀 대상입니다. 사용량 기반으로 자동 추천되었습니다.';
      }

      if ($avg_growth > 0.05) {
          $insights[] = '📈 최근 3개월 평균 성장률 <b>+' . round($avg_growth * 100, 1) . '%</b>로 건전한 성장세입니다.';
      } elseif ($avg_growth > 0) {
          $insights[] = '📊 최근 3개월 평균 성장률 <b>+' . round($avg_growth * 100, 1) . '%</b>입니다. 성장 가속화를 위한 프로모션을 검토해보세요.';
      } else {
          $insights[] = '📉 최근 매출이 정체 또는 감소 추세입니다. 구독자 유지 및 신규 유치 전략이 필요합니다.';
      }

      $insights[] = '💡 <b>추천 액션:</b> 이탈 위험 회원에게는 할인 연장 제안을, 업셀 대상에게는 Pro 플랜 무료 체험을 제안해보세요.';

      foreach ($insights as $ins) {
          echo '<div class="insight-item">' . $ins . '</div>';
      }
      ?>
    </div>

  </div>
  </section>
  </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>