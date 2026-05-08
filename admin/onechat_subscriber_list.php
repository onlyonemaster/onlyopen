<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

extract($_GET);
$nowPage = $_REQUEST['nowPage'] ?: 1;
$search_plan    = trim($_GET['search_plan'] ?? '');
$search_status  = trim($_GET['search_status'] ?? ''); // expiring, overused
$search_keyword = trim($_GET['search_keyword'] ?? '');

$perPage = 30;
$offset  = ($nowPage - 1) * $perPage;

// WHERE
$where = "1=1";
if ($search_plan) {
    $safe_plan = mysqli_real_escape_string($self_con, $search_plan);
    $where .= " AND m.service_type='{$safe_plan}'";
} else {
    // 유료 구독자만 기본 표시 (Free 제외)
    $where .= " AND m.service_type IS NOT NULL AND m.service_type != '' AND m.service_type != '0' AND m.service_type != 'free'";
}
if ($search_status === 'expiring') {
    $where .= " AND m.sub_end_date IS NOT NULL AND m.sub_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)";
}
if ($search_status === 'overused') {
    // 프로필 80%+ 사용
    $where .= " AND m.ai_profile_limit > 0 AND (m.ai_profile_used / m.ai_profile_limit) >= 0.8";
}
if ($search_keyword) {
    $safe_kw = mysqli_real_escape_string($self_con, $search_keyword);
    $where .= " AND (m.mem_id LIKE '%{$safe_kw}%' OR m.mem_name LIKE '%{$safe_kw}%' OR m.mem_nick LIKE '%{$safe_kw}%')";
}

// 총 건수 + 플랜별 분포 (★ 최적화: 2개 쿼리 → 1개로 통합)
$cnt_sql = "SELECT COUNT(*) AS cnt FROM Gn_Member m WHERE {$where}";
$cnt_r   = mysqli_query($self_con, $cnt_sql);
$total   = (int)mysqli_fetch_assoc($cnt_r)['cnt'];
$totalPages = ceil($total / $perPage);

// 플랜별 분포 (summary bar 용, COUNT 쿼리 직후 바로 실행)
$planNames = ['free'=>'Free','basic'=>'Basic','standard'=>'Standard','pro'=>'Pro','business'=>'Business','b2b-pro'=>'Pro B2B','b2b-team'=>'Team B2B','team'=>'Team'];
$plan_summary = [];
$sum_sql = "SELECT service_type, COUNT(*) AS cnt FROM Gn_Member m WHERE {$where} GROUP BY service_type ORDER BY cnt DESC";
$sum_r = mysqli_query($self_con, $sum_sql);
while ($srow = mysqli_fetch_assoc($sum_r)) {
    $plan_summary[] = ['name' => ($planNames[$srow['service_type']] ?? $srow['service_type']), 'cnt' => (int)$srow['cnt']];
}

// 목록
$list_sql = "SELECT m.mem_id, m.mem_name, m.mem_nick, m.service_type, m.sub_end_date,
             m.ai_profile_limit, m.ai_profile_used,
             m.ai_msg_person_limit, m.ai_msg_person_used,
             m.ai_resp_limit, m.ai_resp_used,
             m.first_regist
             FROM Gn_Member m
             WHERE {$where}
             ORDER BY m.first_regist DESC LIMIT {$offset}, {$perPage}";
$list_r = mysqli_query($self_con, $list_sql);
?>
<style>
  .content-wrapper { margin-left:230px; padding-top:50px; }
  .oc-page { padding: 10px 20px 20px; }
  .oc-page h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
  .oc-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
  .oc-toolbar select, .oc-toolbar input[type=text] { padding: 5px 8px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 13px; }
  .oc-toolbar button { padding: 6px 14px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: 600; }
  .oc-btn-primary { background: #3b82f6; color: #fff; }
  .oc-btn-danger { background: #ef4444; color: #fff; }
  .oc-btn-warning { background: #f97316; color: #fff; }
  .oc-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
  .oc-table th { background: #f8fafc; padding: 8px 10px; text-align: left; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: 12px; }
  .oc-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .oc-table tr:hover td { background: #f8fafc; }
  .oc-pagination { margin-top: 14px; text-align: center; }
  .oc-pagination a { display: inline-block; padding: 5px 11px; margin: 0 2px; border: 1px solid #d1d5db; border-radius: 5px; color: #3b82f6; text-decoration: none; font-size: 13px; }
  .oc-pagination strong { display: inline-block; padding: 5px 11px; margin: 0 2px; background: #3b82f6; color: #fff; border-radius: 5px; font-size: 13px; }
  .usage-bar-wrap { width: 80px; height: 6px; background: #e2e8f0; border-radius: 3px; display: inline-block; vertical-align: middle; margin-right: 4px; }
  .usage-bar-fill { height: 100%; border-radius: 3px; }
  .usage-bar-fill.green { background: #10b981; }
  .usage-bar-fill.orange { background: #f59e0b; }
  .usage-bar-fill.red { background: #ef4444; }
  .badge-expiring { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
  .btn-sm { padding: 3px 10px; border-radius: 4px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; font-size: 11px; margin: 1px; }
  .btn-sm:hover { background: #f1f5f9; }
  .btn-sm.danger { color: #ef4444; border-color: #fecaca; }
  .btn-sm.danger:hover { background: #fef2f2; }
</style>

<div class="wrapper">
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>원챗 구독회원 관리 <small>구독자 사용량 및 상태 관리</small></h1>
      <ol class="breadcrumb">
        <li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
        <li>원챗(OneChat) 결제관리</li>
        <li class="active">구독회원 관리</li>
      </ol>
    </section>
    <section class="content">
    <div class="oc-page">

    <!-- 툴바 -->
    <form method="get" class="oc-toolbar">
      <input type="hidden" name="nowPage" value="1">
      <label style="font-size:12px;">플랜</label><select name="search_plan"><option value="">전체(유료)</option><?php foreach(['basic'=>'Basic','standard'=>'Standard','pro'=>'Pro','business'=>'Business','b2b-pro'=>'Pro B2B','b2b-team'=>'Team B2B'] as $k=>$v){ echo '<option value="'.$k.'"'.($search_plan==$k?' selected':'').'>'.$v.'</option>'; } ?></select>
      <label style="font-size:12px;">상태</label><select name="search_status"><option value="">전체</option><option value="expiring" <?=$search_status==='expiring'?'selected':''?>>만료 30일 이내</option><option value="overused" <?=$search_status==='overused'?'selected':''?>>사용량 80%+</option></select>
      <input type="text" name="search_keyword" placeholder="회원ID / 이름" value="<?=htmlspecialchars($search_keyword)?>" style="width:160px;">
      <button type="submit" class="oc-btn-primary">검색</button>
      <button type="button" class="oc-btn-warning" onclick="openTestModal()">+ 🧪 테스트 구독 생성</button>
    </form>

    <!-- 요약 (★ 최적화: inline 쿼리 제거 → 사전 계산된 $plan_summary 사용) -->
    <div style="background:#fff; border-radius:8px; padding:12px 16px; margin-bottom:14px; box-shadow:0 1px 4px rgba(0,0,0,.06); font-size:13px; color:#64748b;">
      전체 유료 구독자 <b style="color:#1e293b;"><?=number_format($total)?></b>명
      <?php foreach ($plan_summary as $ps): ?>
       &nbsp;|&nbsp; <?=htmlspecialchars($ps['name'])?> <b><?=$ps['cnt']?></b>명
      <?php endforeach; ?>
    </div>

    <!-- 목록 -->
    <table class="oc-table">
      <thead><tr><th>회원ID</th><th>회원명</th><th>플랜</th><th>프로필</th><th>AI메시지</th><th>응답자</th><th>만료일</th><th>작업</th></tr></thead>
      <tbody>
      <?php while($row = mysqli_fetch_assoc($list_r)):
        $plan = $row['service_type'] ?: 'free';
        $planLabel = $planNames[$plan] ?? $plan;

        // 프로필 사용률
        $pLimit = max(1, (int)$row['ai_profile_limit']);
        $pUsed  = (int)$row['ai_profile_used'];
        $pPct   = min(100, round($pUsed / $pLimit * 100));
        $pColor = $pPct >= 90 ? 'red' : ($pPct >= 70 ? 'orange' : 'green');

        // AI 메시지 사용률
        $mLimit = max(1, (int)$row['ai_msg_person_limit']);
        $mUsed  = (int)$row['ai_msg_person_used'];
        $mPct   = min(100, round($mUsed / $mLimit * 100));
        $mColor = $mPct >= 90 ? 'red' : ($mPct >= 70 ? 'orange' : 'green');

        // 응답자 사용률
        $rLimit = max(1, (int)$row['ai_resp_limit']);
        $rUsed  = (int)$row['ai_resp_used'];
        $rPct   = min(100, round($rUsed / $rLimit * 100));
        $rColor = $rPct >= 90 ? 'red' : ($rPct >= 70 ? 'orange' : 'green');

        // 만료 임박 체크
        $expiring = false;
        if ($row['sub_end_date']) {
            $daysLeft = (strtotime($row['sub_end_date']) - time()) / 86400;
            $expiring = ($daysLeft >= 0 && $daysLeft <= 30);
        }
      ?>
      <tr>
        <td style="font-family:monospace;"><?=htmlspecialchars($row['mem_id'])?></td>
        <td><?=htmlspecialchars($row['mem_name'] ?: $row['mem_nick'] ?: '-')?></td>
        <td><b><?=htmlspecialchars($planLabel)?></b></td>
        <td>
          <div class="usage-bar-wrap"><div class="usage-bar-fill <?=$pColor?>" style="width:<?=$pPct?>%"></div></div>
          <span style="font-size:11px;"><?=$pUsed?>/<?=number_format($pLimit)?> (<?=$pPct?>%)</span>
        </td>
        <td>
          <div class="usage-bar-wrap"><div class="usage-bar-fill <?=$mColor?>" style="width:<?=$mPct?>%"></div></div>
          <span style="font-size:11px;"><?=$mUsed?>/<?=number_format($mLimit)?> (<?=$mPct?>%)</span>
        </td>
        <td>
          <div class="usage-bar-wrap"><div class="usage-bar-fill <?=$rColor?>" style="width:<?=$rPct?>%"></div></div>
          <span style="font-size:11px;"><?=$rUsed?>/<?=number_format($rLimit)?> (<?=$rPct?>%)</span>
        </td>
        <td>
          <?=htmlspecialchars($row['sub_end_date'] ? substr($row['sub_end_date'],0,10) : '-')?>
          <?=$expiring ? ' <span class="badge-expiring">⚠️임박</span>' : ''?>
        </td>
        <td>
          <button class="btn-sm" onclick="changePlan('<?=$row['mem_id']?>','<?=$plan?>')" title="플랜 변경">🔄</button>
          <button class="btn-sm" onclick="extendTestSub('<?=$row['mem_id']?>','<?=$plan?>')" title="+1개월 연장">📅</button>
          <button class="btn-sm" onclick="resetUsage('<?=$row['mem_id']?>')" title="사용량 리셋">🔄0</button>
          <button class="btn-sm danger" onclick="revertFree('<?=$row['mem_id']?>','<?=htmlspecialchars($row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'])?>')" title="Free로 되돌리기">🚫</button>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if(mysqli_num_rows($list_r)==0): ?>
      <tr><td colspan="8" style="text-align:center; padding:30px; color:#94a3b8;">구독회원이 없습니다.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <!-- 페이지네이션 -->
    <?php if($totalPages>1): ?>
    <div class="oc-pagination">
      <?php for($p=1;$p<=$totalPages;$p++):
        $qs = "nowPage=$p&search_plan=$search_plan&search_status=$search_status&search_keyword=$search_keyword";
        echo ($p==$nowPage) ? "<strong>$p</strong>" : "<a href=\"?$qs\">$p</a>";
      endfor; ?>
    </div>
    <?php endif; ?>
  </div>
  </section>
</div>

<!-- ── 테스트 구독 모달 ────────────────────────────── -->
<div class="oc-modal-overlay" id="testModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:14px; width:500px; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,.18);">
    <h4 style="margin:0 0 16px;">🧪 테스트 구독 생성</h4>
    <label style="display:block; font-weight:600; margin-bottom:5px;">① 대상 회원</label>
    <input type="text" id="testMemId" placeholder="회원ID" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:12px; box-sizing:border-box;">
    <label style="display:block; font-weight:600; margin-bottom:5px;">② 플랜</label>
    <select id="testPlan" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:12px; box-sizing:border-box;">
      <option value="basic">Basic - ₩9,900</option>
      <option value="standard">Standard - ₩19,900</option>
      <option value="pro" selected>Pro - ₩49,000</option>
      <option value="business">Business - ₩99,000</option>
      <option value="b2b-pro">Pro B2B - ₩199,000</option>
      <option value="b2b-team">Team B2B - ₩299,000</option>
    </select>
    <label style="display:block; font-weight:600; margin-bottom:5px;">③ 주기</label>
    <div style="display:flex; gap:16px; margin-bottom:14px;">
      <label><input type="radio" name="testBilling2" value="monthly" checked> 월간</label>
      <label><input type="radio" name="testBilling2" value="yearly"> 연간</label>
    </div>
    <div style="background:#fef2f2; color:#991b1b; padding:8px 12px; border-radius:6px; font-size:12px; margin-bottom:12px;">
      ⚠️ 실제 결제 없이 즉시 적용됩니다.
    </div>
    <div style="display:flex; gap:8px; justify-content:flex-end;">
      <button onclick="closeTestModalSub()" style="padding:9px 20px; border-radius:7px; border:none; background:#e2e8f0; cursor:pointer;">취소</button>
      <button onclick="applyTestSub()" style="padding:9px 20px; border-radius:7px; border:none; background:#f97316; color:#fff; cursor:pointer;">✅ 적용</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script>
function openTestModal() {
  document.getElementById('testModalOverlay').style.display='flex';
}
function closeTestModalSub() {
  document.getElementById('testModalOverlay').style.display='none';
}

function applyTestSub() {
  var memId = document.getElementById('testMemId').value.trim();
  var planId = document.getElementById('testPlan').value;
  var billing = document.querySelector('input[name=testBilling2]:checked').value;
  if (!memId) { alert('회원ID를 입력하세요.'); return; }
  if (!confirm(memId+'님에게 '+planId+'('+billing+') 테스트 구독을 적용할까요?')) return;

  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('plan_id', planId);
  fd.append('billing', billing);
  fetch('/admin/ajax/onechat_test_subscribe.php', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ alert('✅ 적용 완료'); location.reload(); }
      else { alert('❌ '+ (d.error||'오류')); }
    });
}

function changePlan(memId, currentPlan) {
  var plan = prompt('변경할 플랜ID (basic/standard/pro/business/b2b-pro/b2b-team):', currentPlan);
  if (!plan) return;
  var billing = confirm('연간 결제로 할까요? (취소=월간)') ? 'yearly' : 'monthly';
  if (!confirm(memId+' → '+plan+' ('+billing+') 변경?')) return;
  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('plan_id', plan);
  fd.append('billing', billing);
  fetch('/admin/ajax/onechat_test_subscribe.php', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ alert('✅ 변경 완료'); location.reload(); }
      else { alert('❌ '+ (d.error||'오류')); }
    });
}

function extendTestSub(memId, plan) {
  if (!confirm(memId+'님의 구독을 1개월 연장할까요?')) return;
  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('plan_id', plan || 'pro');
  fd.append('billing', 'monthly');
  fetch('/admin/ajax/onechat_test_subscribe.php', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ alert('✅ 연장 완료 (만료: '+d.expires_at+')'); location.reload(); }
      else { alert('❌ '+ (d.error||'오류')); }
    });
}

function resetUsage(memId) {
  if (!confirm(memId+'님의 모든 사용량을 리셋할까요?')) return;
  fetch('/admin/ajax/onechat_dashboard_api.php?action=reset_usage&mem_id='+encodeURIComponent(memId))
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ alert('✅ 리셋 완료'); location.reload(); }
      else { alert('❌ '+ (d.error||'오류')); }
    });
}

function revertFree(memId, memName) {
  if (!confirm('⚠️ '+memName+'('+memId+')님을 Free로 되돌릴까요?\n\n사용량은 유지되며 구독 정보는 초기화됩니다.')) return;
  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('plan_id', 'free');
  fd.append('billing', 'monthly');
  fetch('/admin/ajax/onechat_test_subscribe.php', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){ alert('✅ Free로 변경 완료'); location.reload(); }
      else { alert('❌ '+ (d.error||'오류')); }
    });
}
</script>

  </div>
  </section>
  </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>