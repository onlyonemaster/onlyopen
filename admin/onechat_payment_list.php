<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

extract($_GET);
$date_today = date("Y-m-d");
$nowPage = $_REQUEST['nowPage'] ?: 1;
$search_plan    = trim($_GET['search_plan'] ?? '');
$search_status  = trim($_GET['search_status'] ?? '');
$search_keyword = trim($_GET['search_keyword'] ?? '');
$search_start   = trim($_GET['search_start_date'] ?? date('Y-m-01'));
$search_end     = trim($_GET['search_end_date'] ?? date('Y-m-d'));

// 페이지네이션
$perPage = 25;
$offset  = ($nowPage - 1) * $perPage;

// WHERE 절
$where = "p.member_type LIKE 'onechat_%'";
if ($search_plan) {
    $safe_plan = mysqli_real_escape_string($self_con, $search_plan);
    $where .= " AND p.member_type LIKE 'onechat_{$safe_plan}\_%'";
}
if ($search_status) {
    $safe_status = mysqli_real_escape_string($self_con, $search_status);
    if ($safe_status === 'test') {
        $where .= " AND p.member_type LIKE '%_test'";
    } elseif ($safe_status === 'real') {
        $where .= " AND p.member_type NOT LIKE '%_test'";
    } else {
        $where .= " AND p.end_status='{$safe_status}'";
    }
}
if ($search_keyword) {
    $safe_kw = mysqli_real_escape_string($self_con, $search_keyword);
    $where .= " AND (p.buyer_id LIKE '%{$safe_kw}%' OR p.orderNumber LIKE '%{$safe_kw}%')";
}
$where .= " AND p.date BETWEEN '{$search_start} 00:00:00' AND '{$search_end} 23:59:59'";

// 총 건수
$cnt_sql = "SELECT COUNT(*) AS cnt FROM tjd_pay_result p WHERE {$where}";
$cnt_r   = mysqli_query($self_con, $cnt_sql);
$total   = (int)mysqli_fetch_assoc($cnt_r)['cnt'];
$totalPages = ceil($total / $perPage);

// 내부 서브쿼리용 WHERE (p. 접두사 제거)
$where_inner = str_replace('p.', '', $where);

// ★ 2단계 쿼리: ① tjd_pay_result LIMIT 25건 → ② buyer_id로 Gn_Member IN() 조회
// CONVERT JOIN 제거 → PK 인덱스 정상 사용, charset 불일치도 해결
$list_sql = "SELECT orderNumber, buyer_id, member_type, TotPrice,
                    payMethod, end_status, date, end_date
             FROM tjd_pay_result
             WHERE {$where_inner}
             ORDER BY date DESC
             LIMIT {$offset}, {$perPage}";
$list_r = mysqli_query($self_con, $list_sql);

// 25건의 buyer_id 수집
$rows = [];
$buyer_ids = [];
while ($row = mysqli_fetch_assoc($list_r)) {
    $rows[] = $row;
    $bid = mysqli_real_escape_string($self_con, $row['buyer_id']);
    $buyer_ids[$bid] = $bid;
}
$list_r = $rows; // 이후 while 루프 호환

// Gn_Member에서 이름 일괄 조회 (PK 인덱스, 초고속)
$mem_names = [];
if ($buyer_ids) {
    $id_list = "'" . implode("','", $buyer_ids) . "'";
    $m_sql = "SELECT mem_id, mem_name, mem_nick FROM Gn_Member WHERE mem_id IN ({$id_list})";
    $m_r = mysqli_query($self_con, $m_sql);
    while ($m = mysqli_fetch_assoc($m_r)) {
        $mem_names[$m['mem_id']] = ['name' => $m['mem_name'], 'nick' => $m['mem_nick']];
    }
}

// 플랜명 맵
$planNames = ['free'=>'Free','basic'=>'Basic','standard'=>'Standard','pro'=>'Pro','business'=>'Business','b2b-biz'=>'Business','b2b-pro'=>'Pro B2B','b2b-team'=>'Team B2B','team'=>'Team'];
?>
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="/jquery.lightbox_me.js"></script>
<style>
  .content-wrapper { margin-left:230px; padding-top:50px; }
  .oc-page { padding: 10px 20px 20px; }
  .oc-page h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
  .oc-page h3 i { color: #f97316; margin-right: 6px; }
  .oc-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
  .oc-toolbar select, .oc-toolbar input[type=text], .oc-toolbar input[type=date] { padding: 5px 8px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 13px; }
  .oc-toolbar button { padding: 6px 14px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: 600; }
  .oc-btn-primary { background: #3b82f6; color: #fff; }
  .oc-btn-success { background: #10b981; color: #fff; }
  .oc-btn-warning { background: #f97316; color: #fff; }
  .oc-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
  .oc-table th { background: #f8fafc; padding: 10px; text-align: left; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; font-size: 12px; }
  .oc-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .oc-table tr:hover td { background: #f8fafc; }
  .badge-test { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .badge-real { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .oc-pagination { margin-top: 14px; text-align: center; }
  .oc-pagination a { display: inline-block; padding: 5px 11px; margin: 0 2px; border: 1px solid #d1d5db; border-radius: 5px; color: #3b82f6; text-decoration: none; font-size: 13px; }
  .oc-pagination strong { display: inline-block; padding: 5px 11px; margin: 0 2px; background: #3b82f6; color: #fff; border-radius: 5px; font-size: 13px; }

  /* 테스트 구독 모달 */
  .oc-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; }
  .oc-modal-overlay.show { display:flex; }
  .oc-modal { background:#fff; border-radius:14px; width:500px; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,.18); }
  .oc-modal h4 { margin:0 0 16px; font-size:17px; }
  .oc-modal h4 i { color:#f97316; margin-right:6px; }
  .oc-modal label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:5px; }
  .oc-modal input, .oc-modal select { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; box-sizing:border-box; margin-bottom:12px; }
  .oc-modal .oc-radio-row { display:flex; gap:16px; margin-bottom:14px; }
  .oc-modal .oc-radio-row label { display:inline-flex; align-items:center; gap:4px; font-weight:400; margin-bottom:0; cursor:pointer; }
  .oc-modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:6px; }
  .oc-modal-actions button { padding:9px 20px; border-radius:7px; border:none; cursor:pointer; font-size:13px; font-weight:600; }
  .oc-modal-actions .btn-cancel { background:#e2e8f0; color:#334155; }
  .oc-modal-actions .btn-apply { background:#f97316; color:#fff; }
  .oc-alert { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:6px; padding:8px 12px; font-size:12px; margin-bottom:12px; }

</style>

<div class="wrapper">
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>원챗 구독결제 관리 <small>결제 내역 조회 및 테스트 구독</small></h1>
      <ol class="breadcrumb">
        <li><a href="/admin/"><i class="fa fa-dashboard"></i> 홈</a></li>
        <li>원챗(OneChat) 결제관리</li>
        <li class="active">구독결제 관리</li>
      </ol>
    </section>
    <section class="content">
    <div class="oc-page">

    <!-- 툴바 -->
    <form method="get" class="oc-toolbar">
      <input type="hidden" name="nowPage" value="1">
      <label style="font-size:12px;">시작일</label><input type="date" name="search_start_date" value="<?=htmlspecialchars($search_start)?>">
      <label style="font-size:12px;">종료일</label><input type="date" name="search_end_date" value="<?=htmlspecialchars($search_end)?>">
      <label style="font-size:12px;">플랜</label><select name="search_plan"><option value="">전체</option><?php foreach(['basic'=>'Basic','standard'=>'Standard','pro'=>'Pro','business'=>'Business','b2b-pro'=>'Pro B2B','b2b-team'=>'Team B2B'] as $k=>$v){ echo '<option value="'.$k.'"'.($search_plan==$k?' selected':'').'>'.$v.'</option>'; } ?></select>
      <label style="font-size:12px;">유형</label><select name="search_status"><option value="">전체</option><option value="real" <?=$search_status==='real'?'selected':''?>>실결제</option><option value="test" <?=$search_status==='test'?'selected':''?>>테스트</option></select>
      <input type="text" name="search_keyword" placeholder="회원ID / 주문번호" value="<?=htmlspecialchars($search_keyword)?>" style="width:160px;">
      <button type="submit" class="oc-btn-primary">검색</button>
      <button type="button" class="oc-btn-warning" onclick="openTestModal()">+ 🧪 테스트결제</button>
      <button type="button" class="oc-btn-success" onclick="openCustomModal()">+ 💳 커스텀결제</button>
    </form>

    <!-- 목록 -->
    <table class="oc-table">
      <thead><tr><th>주문번호</th><th>회원ID</th><th>회원명</th><th>플랜</th><th>과금주기</th><th>금액</th><th>결제수단</th><th>유형</th><th>상태</th><th>결제일시</th><th>만료예정</th><th>삭제</th></tr></thead>
      <tbody>
      <?php foreach ($list_r as $row):
        $mt = str_replace('onechat_', '', $row['member_type']);
        $parts = explode('_', $mt);
        $plan = $parts[0] ?? '';
        $billing = $parts[1] ?? '';
        $is_test = ($billing === 'test' || strpos($row['orderNumber'], 'TEST-') === 0);
        $planLabel = $planNames[$plan] ?? $plan;
        $billingLabel = $is_test ? '테스트' : ($billing === 'yearly' ? '연간' : '월간');
        $memInfo = $mem_names[$row['buyer_id']] ?? ['name' => '', 'nick' => ''];
        $memName = $memInfo['name'] ?: $memInfo['nick'] ?: '-';
      ?>
      <tr>
        <td style="font-family:monospace; font-size:11px;"><?=htmlspecialchars($row['orderNumber'])?></td>
        <td><?=htmlspecialchars($row['buyer_id'])?></td>
        <td><?=htmlspecialchars($memName)?></td>
        <td><?=htmlspecialchars($planLabel)?></td>
        <td><?=htmlspecialchars($billingLabel)?></td>
        <td><?=number_format($row['TotPrice'])?></td>
        <td><?=htmlspecialchars($row['payMethod'])?></td>
        <td><?=$is_test?'<span class="badge-test">🧪테스트</span>':'<span class="badge-real">✅실결제</span>'?></td>
        <td><?=htmlspecialchars($row['end_status'])?></td>
        <td><?=htmlspecialchars(substr($row['date'],0,16))?></td>
        <td><?=htmlspecialchars($row['end_date'] ? substr($row['end_date'],0,10) : '-')?></td>
        <td><?=$is_test?'<button class="btn-sm danger" onclick="deleteTestOrder(\''.htmlspecialchars($row['orderNumber']).'\', this)" title="테스트 주문 삭제">🗑</button>':'-'?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(count($list_r)==0): ?>
      <tr><td colspan="12" style="text-align:center; padding:30px; color:#94a3b8;">결제 내역이 없습니다.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <!-- 페이지네이션 -->
    <?php if($totalPages>1): ?>
    <div class="oc-pagination">
      <?php for($p=1;$p<=$totalPages;$p++):
        $qs = "nowPage=$p&search_plan=$search_plan&search_status=$search_status&search_keyword=$search_keyword&search_start_date=$search_start&search_end_date=$search_end";
        echo ($p==$nowPage) ? "<strong>$p</strong>" : "<a href=\"?$qs\">$p</a>";
      endfor; ?>
    </div>
    <?php endif; ?>
  </div>
  </section>
</div>

<!-- ── 테스트 구독 생성 모달 ────────────────────────────── -->
<div class="oc-modal-overlay" id="testModalOverlay">
  <div class="oc-modal">
    <h4><i class="fa fa-flask"></i> 테스트 구독 생성</h4>

    <!-- ① 회원 ID 직접 입력 -->
    <label>① 대상 회원</label>
    <input type="text" id="testMemId" placeholder="회원ID 입력">

    <!-- ② 플랜 선택 -->
    <label>② 플랜 및 주기</label>
    <select id="testPlanSelect">
      <option value="basic">Basic - ₩9,900/월</option>
      <option value="standard">Standard - ₩19,900/월</option>
      <option value="pro" selected>Pro - ₩49,000/월</option>
      <option value="business">Business - ₩99,000/월</option>
      <option value="b2b-pro">Pro B2B - ₩199,000/월</option>
      <option value="b2b-team">Team B2B - ₩299,000/월</option>
    </select>
    <div class="oc-radio-row">
      <label><input type="radio" name="testBilling" value="monthly" checked> 월간</label>
      <label><input type="radio" name="testBilling" value="yearly"> 연간</label>
    </div>

    <div class="oc-alert">
      ⚠️ 실제 토스 결제 없이 즉시 적용됩니다. tjd_pay_result에 'TEST-' 접두어로 기록되어 실결제와 분리됩니다.
    </div>

    <div class="oc-modal-actions">
      <button class="btn-cancel" onclick="closeTestModal()">취소</button>
      <button class="btn-apply" onclick="applyTestSub()">✅ 적용하기</button>
    </div>
  </div>
</div>

<!-- ── 커스텀 결제 생성 모달 ────────────────────────────── -->
<div class="oc-modal-overlay" id="customModalOverlay">
  <div class="oc-modal" style="width:540px;">
    <h4><i class="fa fa-credit-card"></i> 커스텀 결제 생성</h4>

    <!-- 모드 선택 -->
    <label>결제 방식</label>
    <div class="oc-radio-row" style="margin-bottom:14px;">
      <label><input type="radio" name="customMode" value="template" checked onchange="toggleCustomMode()"> 템플릿 기반 (기존 플랜 선택)</label>
      <label><input type="radio" name="customMode" value="full" onchange="toggleCustomMode()"> 완전 커스텀 (직접 입력)</label>
    </div>

    <!-- ① 회원 ID -->
    <label>① 대상 회원</label>
    <input type="text" id="customMemId" placeholder="회원ID 입력">

    <!-- 템플릿 기반 영역 -->
    <div id="customTemplateArea">
      <label>② 기준 플랜 선택</label>
      <select id="customPlanSelect" onchange="onCustomPlanChange()">
        <option value="basic" data-price="9900" data-profile="500" data-ai_msg="300" data-resp="300" data-bot="3">Basic - ₩9,900/월</option>
        <option value="standard" data-price="19900" data-profile="1500" data-ai_msg="900" data-resp="900" data-bot="10">Standard - ₩19,900/월</option>
        <option value="pro" selected data-price="49000" data-profile="10000" data-ai_msg="6000" data-resp="6000" data-bot="30">Pro - ₩49,000/월</option>
        <option value="business" data-price="99000" data-profile="50000" data-ai_msg="30000" data-resp="30000" data-bot="100">Business - ₩99,000/월</option>
        <option value="b2b-pro" data-price="199000" data-profile="150000" data-ai_msg="90000" data-resp="90000" data-bot="300">Pro B2B - ₩199,000/월</option>
        <option value="b2b-team" data-price="299000" data-profile="500000" data-ai_msg="300000" data-resp="300000" data-bot="500">Team B2B - ₩299,000/월</option>
      </select>
    </div>

    <!-- 가격 입력 -->
    <label>③ 결제 금액</label>
    <input type="number" id="customPrice" placeholder="금액 입력 (원)" value="49000" style="font-size:16px; font-weight:700;">

    <!-- 조건 수정 -->
    <label>④ 사용 한도 (조건)</label>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
      <div><label style="font-size:11px; margin-bottom:2px;">프로필</label><input type="number" id="customProfile" value="10000" placeholder="건"></div>
      <div><label style="font-size:11px; margin-bottom:2px;">AI 메시지</label><input type="number" id="customAiMsg" value="6000" placeholder="건"></div>
      <div><label style="font-size:11px; margin-bottom:2px;">AI 응답자</label><input type="number" id="customResp" value="6000" placeholder="건"></div>
      <div><label style="font-size:11px; margin-bottom:2px;">봇 생성</label><input type="number" id="customBot" value="30" placeholder="개"></div>
    </div>

    <!-- 과금 주기 -->
    <label style="margin-top:12px;">⑤ 과금 주기</label>
    <div class="oc-radio-row">
      <label><input type="radio" name="customBilling" value="monthly" checked> 월간</label>
      <label><input type="radio" name="customBilling" value="yearly"> 연간</label>
    </div>

    <div class="oc-alert">
      💡 실제 토스페이먼츠 결제가 진행됩니다. 관리자가 결제링크를 생성한 후 해당 회원에게 URL을 전달하면 회원이 직접 카드 등록 및 결제를 완료합니다.
    </div>

    <div class="oc-modal-actions">
      <button class="btn-cancel" onclick="closeCustomModal()">취소</button>
      <button class="btn-apply" style="background:#8b5cf6;" onclick="createCustomPay()">🔗 결제링크 생성</button>
    </div>

    <!-- 생성된 링크 표시 영역 -->
    <div id="customLinkResult" style="display:none; margin-top:16px; padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
      <div style="font-size:12px; font-weight:600; color:#166534; margin-bottom:6px;">✅ 결제링크가 생성되었습니다</div>
      <div style="font-size:11px; color:#475569; margin-bottom:4px;">아래 URL을 회원에게 전달하세요:</div>
      <input type="text" id="customPayUrl" readonly style="font-family:monospace; font-size:11px; width:100%; background:#fff; cursor:pointer;" onclick="this.select();document.execCommand('copy');showToast('📋 URL이 복사되었습니다!', 'success');">
      <div style="font-size:10px; color:#94a3b8; margin-top:4px;">※ 클릭하면 자동 복사됩니다</div>
    </div>
  </div>
</div>

<script>
function openTestModal() { document.getElementById('testModalOverlay').classList.add('show'); }
function closeTestModal() { document.getElementById('testModalOverlay').classList.remove('show'); }

// ── 커스텀 결제 모달 ─────────────────────────────────
function openCustomModal() { document.getElementById('customModalOverlay').classList.add('show'); onCustomPlanChange(); }
function closeCustomModal() { document.getElementById('customModalOverlay').classList.remove('show'); document.getElementById('customLinkResult').style.display='none'; }

function toggleCustomMode() {
  var isTemplate = document.querySelector('input[name=customMode]:checked').value === 'template';
  document.getElementById('customTemplateArea').style.display = isTemplate ? 'block' : 'none';
  if (!isTemplate) {
    document.getElementById('customPrice').value = '';
    document.getElementById('customPrice').placeholder = '금액 직접 입력';
  } else {
    onCustomPlanChange();
  }
}

function onCustomPlanChange() {
  var sel = document.getElementById('customPlanSelect');
  var opt = sel.options[sel.selectedIndex];
  document.getElementById('customPrice').value = opt.getAttribute('data-price');
  document.getElementById('customProfile').value = opt.getAttribute('data-profile');
  document.getElementById('customAiMsg').value = opt.getAttribute('data-ai_msg');
  document.getElementById('customResp').value = opt.getAttribute('data-resp');
  document.getElementById('customBot').value = opt.getAttribute('data-bot');
}

function createCustomPay() {
  var memId = document.getElementById('customMemId').value.trim();
  var price = parseInt(document.getElementById('customPrice').value);
  var profile = parseInt(document.getElementById('customProfile').value);
  var aiMsg = parseInt(document.getElementById('customAiMsg').value);
  var resp = parseInt(document.getElementById('customResp').value);
  var bot = parseInt(document.getElementById('customBot').value);
  var billing = document.querySelector('input[name=customBilling]:checked').value;
  var mode = document.querySelector('input[name=customMode]:checked').value;

  if (!memId) { alert('회원ID를 입력하세요.'); return; }
  if (!price || price < 100) { alert('올바른 결제 금액을 입력하세요. (최소 100원)'); return; }
  if (!profile || !aiMsg || !resp || !bot) { alert('모든 사용 한도를 입력하세요.'); return; }

  if (!confirm(memId+'님에게 커스텀 결제를 생성할까요?\n\n금액: ₩'+price.toLocaleString()+'\n주기: '+(billing==='yearly'?'연간':'월간')+'\n프로필:'+profile+' AI메시지:'+aiMsg+' 응답자:'+resp+' 봇:'+bot)) return;

  var btn = document.querySelector('#customModalOverlay .btn-apply');
  var origText = btn.textContent;
  btn.textContent = '⏳ 생성 중...';
  btn.disabled = true;

  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('price', price);
  fd.append('billing', billing);
  fd.append('profile', profile);
  fd.append('ai_msg', aiMsg);
  fd.append('resp', resp);
  fd.append('bot', bot);
  fd.append('mode', mode);

  var ctrl = new AbortController();
  var timeoutId = setTimeout(function(){ ctrl.abort(); }, 15000);

  fetch('/admin/ajax/onechat_custom_subscribe.php', {method:'POST', body:fd, signal:ctrl.signal})
    .then(function(r){return r.json();})
    .then(function(d){
      clearTimeout(timeoutId);
      btn.textContent = origText;
      btn.disabled = false;
      if(d.ok){
        document.getElementById('customPayUrl').value = d.pay_url;
        document.getElementById('customLinkResult').style.display = 'block';
        showToast('✅ 결제링크가 생성되었습니다!', 'success');
      } else {
        alert('❌ '+ (d.error||'오류'));
      }
    })
    .catch(function(e){
      clearTimeout(timeoutId);
      btn.textContent = origText;
      btn.disabled = false;
      if (e.name === 'AbortError') {
        alert('⏰ 요청 시간이 15초를 초과했습니다. 서버 상태를 확인해주세요.');
      } else {
        alert('통신 오류: '+e.message);
      }
    });
}

function applyTestSub() {
  var memId = document.getElementById('testMemId').value.trim();
  var planId = document.getElementById('testPlanSelect').value;
  var billing = document.querySelector('input[name=testBilling]:checked').value;
  if (!memId) { alert('회원ID를 입력하세요.'); return; }
  if (!confirm(memId+'님에게 '+planId+'('+billing+') 테스트 구독을 적용할까요?')) return;

  // 로딩 표시
  var btn = document.querySelector('.btn-apply');
  var origText = btn.textContent;
  btn.textContent = '⏳ 적용 중...';
  btn.disabled = true;

  var fd = new FormData();
  fd.append('mem_id', memId);
  fd.append('plan_id', planId);
  fd.append('billing', billing);

  // ★ 15초 타임아웃: 서버 장애/MySQL 정체 시 무한 대기 방지
  var ctrl = new AbortController();
  var timeoutId = setTimeout(function(){ ctrl.abort(); }, 15000);

  fetch('/admin/ajax/onechat_test_subscribe.php', {method:'POST', body:fd, signal:ctrl.signal})
    .then(function(r){return r.json();})
    .then(function(d){
      clearTimeout(timeoutId);
      clearTimeout(timeoutId);
      btn.textContent = origText;
      btn.disabled = false;
      if(d.ok){
        closeTestModal();
        showToast('✅ '+memId+'님 '+planId+' 테스트 구독 적용 완료! (만료: '+d.expires_at+')', 'success');
        document.getElementById('testMemId').value = '';
        // ★ AJAX 행 삽입: 전체 페이지 재로딩 없이 새 행을 테이블 상단에 추가
        insertNewRow(d);
      } else {
        alert('❌ '+ (d.error||'오류'));
      }
    })
    .catch(function(e){
      clearTimeout(timeoutId);
      btn.textContent = origText;
      btn.disabled = false;
      if (e.name === 'AbortError') {
        alert('⏰ 요청 시간이 15초를 초과했습니다. 서버 상태를 확인해주세요.');
      } else {
        alert('통신 오류: '+e.message);
      }
    });
}

// 토스트 메시지 (간단한 인라인 알림)
// ★ 테스트 구독 성공 시 새 행을 테이블에 삽입 (새로고침 없이)
function insertNewRow(d) {
  var tbody = document.querySelector('.oc-table tbody');
  // "결제 내역이 없습니다" 행 제거
  var emptyRow = tbody.querySelector('td[colspan]');
  if (emptyRow) emptyRow.parentNode.remove();

  var planNames = {free:'Free',basic:'Basic',standard:'Standard',pro:'Pro',business:'Business','b2b-biz':'Business','b2b-pro':'Pro B2B','b2b-team':'Team B2B',team:'Team'};
  var planLabel = planNames[d.plan_id] || d.plan_id;
  var billingLabel = d.billing === 'yearly' ? '연간' : '월간';
  var now = new Date();
  var dateStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0')+' '+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
  var expiresStr = d.expires_at ? d.expires_at.substring(0,10) : '-';

  var tr = document.createElement('tr');
  tr.innerHTML =
    '<td style="font-family:monospace;font-size:11px;">'+escHtml(d.order_id)+'</td>'+
    '<td>'+escHtml(d.member_id)+'</td>'+
    '<td>'+escHtml(d.member_name)+'</td>'+
    '<td>'+escHtml(planLabel)+'</td>'+
    '<td>'+escHtml(billingLabel)+'</td>'+
    '<td>0</td>'+
    '<td>AdminTest</td>'+
    '<td><span class="badge-test">🧪테스트</span></td>'+
    '<td>Y</td>'+
    '<td>'+escHtml(dateStr)+'</td>'+
    '<td>'+escHtml(expiresStr)+'</td>'+
    '<td><button class=\"btn-sm danger\" onclick=\"deleteTestOrder(\''+escHtml(d.order_id)+'\', this)\" title=\"테스트 주문 삭제\">🗑</button></td>';
  tbody.insertBefore(tr, tbody.firstChild);
}
function escHtml(s) {
  var d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

// ★ 테스트 주문 삭제 (API 호출 + 행 제거)
function deleteTestOrder(orderNumber, btn) {
  if (!confirm('⚠️ 주문번호 ['+orderNumber+'] 테스트 주문을 정말 삭제할까요?\n\n이 작업은 되돌릴 수 없습니다.')) return;
  var origText = btn.textContent;
  btn.textContent = '⏳';
  btn.disabled = true;
  var fd = new FormData();
  fd.append('orderNumber', orderNumber);
  fetch('/admin/ajax/onechat_dashboard_api.php?action=delete_test_order', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){
        var tr = btn.closest('tr');
        tr.style.transition = 'opacity 0.3s';
        tr.style.opacity = '0';
        setTimeout(function(){ tr.remove(); }, 300);
        showToast('🗑 테스트 주문이 삭제되었습니다.', 'success');
      } else {
        btn.textContent = origText;
        btn.disabled = false;
        alert('❌ '+ (d.error||'오류'));
      }
    })
    .catch(function(e){
      btn.textContent = origText;
      btn.disabled = false;
      alert('통신 오류: '+e.message);
    });
}

function showToast(msg, type) {
  var t = document.createElement('div');
  t.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 20px;border-radius:8px;color:#fff;font-size:14px;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,.2);transition:opacity .4s;'+
    (type==='success' ? 'background:#10b981;' : 'background:#ef4444;');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.remove(); }, 400); }, 3500);
}
</script>

  </div>
  </section>
  </div><!-- /.content-wrapper -->
</div><!-- /.wrapper -->
<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>