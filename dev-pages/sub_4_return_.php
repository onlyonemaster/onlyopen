<?php
/**
 * sub_4_return_.php
 * 수발신내역 통합 페이지 (발신내역 · 수신내역 · 수신여부)
 * Design System : nm-darkver2 v2.0
 */
$path = "./";
include_once "_head_v01.php";
if (empty($_SESSION['one_member_id']) && empty($_SESSION['iam_member_id'])) {
    echo "<script>location.replace('/ma.php');</script>";
    exit;
}

// ── 탭 결정 ──
// tab=1 : 발신내역, tab=2 : 수신내역, tab=3 : 수신여부
$tab = (int)($_REQUEST['tab'] ?? 1);
if ($tab < 1 || $tab > 3) $tab = 1;

// ── 멤버 전화번호 ──
$sql_mem = "SELECT mem_phone FROM Gn_Member WHERE mem_id='{$_SESSION['one_member_id']}'";
$res_mem = mysqli_query($self_con, $sql_mem);
$row_mem = mysqli_fetch_array($res_mem);
$mem_phone = $row_mem['mem_phone'] ?? '';
$phone = str_replace("-", "", $mem_phone);

/* ════════════════════════════════════════════════════
   TAB 1 : 발신내역
════════════════════════════════════════════════════ */
if ($tab == 1) {
    $search_category = $_REQUEST['search_category'] ?? '';
    $startdate       = mysqli_real_escape_string($self_con, $_REQUEST['startdate'] ?? '');
    $enddate         = mysqli_real_escape_string($self_con, $_REQUEST['enddate'] ?? '');

    $sql_serch = " mem_id='{$_SESSION['one_member_id']}'";
    $allowed_categories = ['reservation', 'up_date', 'reg_date'];
    $safe_category = in_array($search_category, $allowed_categories) ? $search_category : '';
    if ($safe_category !== '') {
        if ($startdate) $sql_serch .= " AND $safe_category >= '$startdate 00:00:00'";
        if ($enddate)   $sql_serch .= " AND $safe_category <= '$enddate 23:59:59'";
    }

    $sql_table = " Gn_MMS ";
    $chanel    = $_REQUEST['chanel'] ?? '';
    if ($chanel == 2) {
        $sql_serch .= " AND result >= 0 AND (type=2 || type=3 || type=4 || type=10)";
    } elseif ($chanel == 4) {
        $sql_serch .= " AND result >= 0 AND type=6";
    } elseif ($chanel == 9) {
        $sql_serch .= " AND result >= 0 AND type=9";
    } else {
        $sql_serch .= " AND result >= 0 AND title != 'app_check_process' AND (type=1 or type=0)";
    }

    $daily_type = $_REQUEST['daily_type'] ?? '';
    if ($daily_type == "normal")   $sql_serch .= " AND sms_idx is null";
    elseif ($daily_type == "step") $sql_serch .= " AND sms_idx is not null";

    $allowed_fs_cols = ['send_num', 'recv_num', 'title', 'content'];
    $fs_select = $_REQUEST['serch_fs_select'] ?? '';
    $fs_text   = mysqli_real_escape_string($self_con, $_REQUEST['serch_fs_text'] ?? '');
    if (in_array($fs_select, $allowed_fs_cols) && $fs_text !== '') {
        $sql_serch .= " AND {$fs_select} like '{$fs_text}%'";
    }

    $result_filter = $_REQUEST['result'] ?? '';
    if ($result_filter == 1)     $sql_serch .= " AND result=0 AND up_date is not null";
    elseif ($result_filter == 2) $sql_serch .= " AND result=1 AND up_date is null";
    elseif ($result_filter == 3) $sql_serch .= " AND result=3";
    elseif ($result_filter == 4) $sql_serch .= " AND reservation <> ''";

    $sql_cnt = "SELECT count(*) as cnt FROM $sql_table WHERE $sql_serch";
    $res_cnt = mysqli_query($self_con, $sql_cnt) or die(mysqli_error($self_con));
    $row_cnt = mysqli_fetch_array($res_cnt);
    $intRowCount = (int)$row_cnt['cnt'];
    mysqli_free_result($res_cnt);

    $intPageSize = (int)($_POST['lno'] ?? 20);
    if ($intPageSize < 1) $intPageSize = 20;
    $page  = max(1, (int)($_REQUEST['page'] ?? 1));
    $page2 = max(1, (int)($_REQUEST['page2'] ?? 1));
    $sort_no = $intRowCount - ($intPageSize * $page - $intPageSize);
    $int = ($page - 1) * $intPageSize;

    $order_status = ($_REQUEST['order_status'] ?? '') === 'asc' ? 'asc' : 'desc';
    $allowed_order_cols = ['reg_date','up_date','reservation','send_num','recv_num','result'];
    $req_order = $_REQUEST['order_name'] ?? '';
    $order_name = in_array($req_order, $allowed_order_cols) ? $req_order : 'reg_date';
    $intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);

    $sql_data  = "SELECT idx,send_num,recv_num,up_date,reg_date,reservation,title,content,result,jpg,jpg1,jpg2,count_start,count_end,grp_idx,type FROM $sql_table WHERE $sql_serch ORDER BY $order_name $order_status LIMIT $int,$intPageSize";
    $excel_sql = "SELECT idx,send_num,recv_num,up_date,reg_date,reservation,title,content,result,jpg,jpg1,jpg2 FROM $sql_table WHERE $sql_serch ORDER BY $order_name $order_status";
    $excel_sql = str_replace("'", "`", $excel_sql);
    $result_data = mysqli_query($self_con, $sql_data) or die(mysqli_error($self_con));
}

/* ════════════════════════════════════════════════════
   TAB 2 : 수신내역
════════════════════════════════════════════════════ */
if ($tab == 2) {
    $sql_serch2 = " 1 AND mem_id='{$_SESSION['one_member_id']}'";
    if ($_REQUEST['status2'] ?? '') $sql_serch2 .= " AND msg_flag='" . mysqli_real_escape_string($self_con, $_REQUEST['status2']) . "'";
    $serch_col2  = $_REQUEST['serch_colum'] ?? '';
    $serch_text2 = mysqli_real_escape_string($self_con, $_REQUEST['serch_text'] ?? '');
    $allowed_sm_cols = ['ori_num','dest','msg_text'];
    if (in_array($serch_col2, $allowed_sm_cols) && $serch_text2 !== '') {
        $sql_serch2 .= " AND $serch_col2 like '%{$serch_text2}%'";
    }

    $sql_cnt2 = "SELECT count(seq) as cnt FROM sm_log WHERE $sql_serch2";
    $res_cnt2 = mysqli_query($self_con, $sql_cnt2) or die(mysqli_error($self_con));
    $row_cnt2 = mysqli_fetch_array($res_cnt2);
    $intRowCount2 = (int)$row_cnt2['cnt'];

    $intPageSize2 = (int)($_POST['lno'] ?? 20);
    if ($intPageSize2 < 1) $intPageSize2 = 20;
    $page2a = max(1, (int)($_POST['page'] ?? 1));
    $page2b = max(1, (int)($_POST['page2'] ?? 1));
    $sort_no2 = $intRowCount2 - ($intPageSize2 * $page2a - $intPageSize2);
    $int2 = ($page2a - 1) * $intPageSize2;

    $order_status2 = ($_REQUEST['order_status'] ?? '') === 'asc' ? 'asc' : 'desc';
    $order_name2   = 'seq';
    $intPageCount2 = (int)(($intRowCount2 + $intPageSize2 - 1) / $intPageSize2);
    $sql_data2  = "SELECT * FROM sm_log WHERE $sql_serch2 ORDER BY $order_name2 $order_status2 LIMIT $int2,$intPageSize2";
    $excel_sql2 = "SELECT * FROM sm_log WHERE $sql_serch2 ORDER BY $order_name2 $order_status2";
    $excel_sql2 = str_replace("'", "`", $excel_sql2);
    $result_data2 = mysqli_query($self_con, $sql_data2) or die(mysqli_error($self_con));
}

/* ════════════════════════════════════════════════════
   TAB 3 : 수신여부
════════════════════════════════════════════════════ */
if ($tab == 3) {
    $sql_serch3 = " mem_id='{$_SESSION['one_member_id']}'";
    $serch_col3  = $_REQUEST['serch_colum'] ?? '';
    $serch_text3 = mysqli_real_escape_string($self_con, $_REQUEST['serch_text'] ?? '');
    $allowed_agree_cols = ['send_num','recv_num'];
    if (in_array($serch_col3, $allowed_agree_cols) && $serch_text3 !== '') {
        $sql_serch3 .= " AND $serch_col3 like '{$serch_text3}%'";
    }
    $agree_type  = ($_REQUEST['agree_type'] ?? '') === 'agree' ? 'agree' : 'deny';
    $agree_table = ($agree_type === 'agree') ? 'Gn_MMS_Agree' : 'Gn_MMS_Deny';

    if ($agree_type === 'deny' && ($_REQUEST['serch_chanel'] ?? '')) {
        $serch_chanel = (int)$_REQUEST['serch_chanel'];
        $sql_serch3 .= " AND chanel_type = $serch_chanel";
    }

    $sql_cnt3 = "SELECT count(idx) as cnt FROM $agree_table WHERE $sql_serch3";
    $res_cnt3 = mysqli_query($self_con, $sql_cnt3) or die(mysqli_error($self_con));
    $row_cnt3 = mysqli_fetch_array($res_cnt3);
    $intRowCount3 = (int)$row_cnt3['cnt'];

    $intPageSize3 = (int)($_POST['lno'] ?? 20);
    if ($intPageSize3 < 1) $intPageSize3 = 20;
    $page3a = max(1, (int)($_POST['page'] ?? 1));
    $page3b = max(1, (int)($_POST['page2'] ?? 1));
    $sort_no3 = $intRowCount3 - ($intPageSize3 * $page3a - $intPageSize3);
    $int3 = ($page3a - 1) * $intPageSize3;

    $order_status3 = ($_REQUEST['order_status'] ?? '') === 'asc' ? 'asc' : 'desc';
    $order_name3   = 'idx';
    $intPageCount3 = (int)(($intRowCount3 + $intPageSize3 - 1) / $intPageSize3);
    $sql_data3  = "SELECT * FROM $agree_table WHERE $sql_serch3 ORDER BY $order_name3 $order_status3 LIMIT $int3,$intPageSize3";
    $excel_sql3 = "SELECT * FROM $agree_table WHERE $sql_serch3 ORDER BY $order_name3 $order_status3";
    $excel_sql3 = str_replace("'", "`", $excel_sql3);
    $result_data3 = mysqli_query($self_con, $sql_data3) or die(mysqli_error($self_con));

    $today_date3 = date("Y-m-d");
    $sql_today3 = "SELECT count(idx) as today_cnt FROM $agree_table WHERE mem_id='{$_SESSION['one_member_id']}' AND reg_date like '$today_date3%'";
    $res_today3 = mysqli_query($self_con, $sql_today3);
    $row_today3 = mysqli_fetch_array($res_today3);

    $deny_type_arr = ["" => "", "1" => "직접등록", "2" => "자동등록", "3" => "API등록"];
}
?>

<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ══════════════════════════════════════════════════════
   sub_4_return_.php — nm-darkver2 수발신내역 통합
   Design System : nm-darkver2 v2.0  2026-04-23
══════════════════════════════════════════════════════ */
:root {
    --nm-dv2-bg:       #18172F;
    --nm-dv2-card:     #1e2d50;
    --nm-dv2-card2:    #16305c;
    --nm-dv2-border:   rgba(100,160,255,0.25);
    --nm-dv2-text:     #f0f4ff;
    --nm-dv2-muted:    rgba(200,215,255,0.60);
    --nm-dv2-accent:   #a8c8ff;
    --nm-dv2-green:    #82c836;
    --nm-dv2-red:      #e05a5a;
    --nm-dv2-input-bg: #252547;
    --nm-dv2-hover:    rgba(100,160,255,0.08);

    --rl-bg      : var(--nm-dv2-bg);
    --rl-card    : var(--nm-dv2-card);
    --rl-border  : var(--nm-dv2-border);
    --rl-text    : var(--nm-dv2-text);
    --rl-muted   : var(--nm-dv2-muted);
    --rl-accent  : var(--nm-dv2-accent);
    --rl-badge   : var(--nm-dv2-card2);
    --rl-danger  : var(--nm-dv2-red);
    --rl-success : var(--nm-dv2-green);
    --rl-input-bg: var(--nm-dv2-input-bg);
}

body, body.page-sub {
    background: var(--rl-bg) !important;
    color: var(--rl-text) !important;
}
.page-sub, .common-wrap { background: var(--rl-bg) !important; }

/* ── 페이지 래퍼 ── */
.s4r-wrap {
    max-width: 1380px;
    margin: 0 auto;
    padding: 24px 24px 72px;
    box-sizing: border-box;
    min-height: 100vh;
}

/* ── 메인 탭 네비 ── */
.s4r-main-tab-wrap {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    background: var(--rl-card);
    border: 1px solid var(--rl-border);
    border-radius: 14px;
    padding: 6px;
    box-sizing: border-box;
}
.s4r-main-tab {
    flex: 1;
    padding: 11px 20px;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: var(--rl-muted);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all .18s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.s4r-main-tab:hover { background: var(--nm-dv2-hover); color: var(--rl-text); }
.s4r-main-tab.active {
    background: var(--rl-success);
    color: #ffffff;
    font-weight: 700;
}

/* ── 서브탭 (채널/수신유형) ── */
.s4r-tab-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 14px;
}
.s4r-tab {
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--rl-border);
    background: transparent;
    color: var(--rl-muted);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .18s;
}
.s4r-tab:hover, .s4r-tab.active {
    background: var(--rl-success);
    border-color: var(--rl-success);
    color: #ffffff;
}

/* ── 카드 ── */
.s4r-card {
    background: var(--rl-card);
    border: 1px solid var(--rl-border);
    border-radius: 14px;
    padding: 18px 22px;
    margin-bottom: 16px;
    box-sizing: border-box;
    width: 100%;
}

/* ── 검색 폼 ── */
.s4r-search-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.s4r-search-row select,
.s4r-search-row input[type="text"],
.s4r-search-row input[type="date"] {
    background: var(--rl-input-bg) !important;
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    padding: 6px 10px;
    color: #ffffff;
    font-size: 14px;
}

/* ── 액션바 ── */
.s4r-action-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.s4r-count-badge {
    display: inline-block;
    padding: 4px 12px;
    background: var(--rl-badge);
    border: 1px solid var(--rl-border);
    border-radius: 20px;
    font-size: 13px;
    color: var(--rl-text);
}

/* ── 버튼류 ── */
.s4r-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: opacity .18s;
}
.s4r-btn:hover { opacity: 0.85; }
.s4r-btn-primary  { background: var(--rl-success); color: #ffffff; }
.s4r-btn-danger   { background: var(--rl-danger);  color: #fff; }
.s4r-btn-gray     { background: var(--rl-badge);   color: var(--rl-text); border: 1px solid var(--rl-border); }
.s4r-btn-blue     { background: #2563eb; color: #fff; }
.s4r-btn-excel    { background: #1a6b2e; border: 1px solid #2ea04b; color: #fff; }

/* ── 테이블 ── */
.s4r-table-wrap { overflow-x: auto; }
.s4r-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    color: var(--rl-text);
}
.s4r-table thead th {
    background: var(--nm-dv2-card2);
    color: var(--rl-success);
    font-weight: 700;
    padding: 10px 8px;
    border-bottom: 1px solid var(--rl-border);
    white-space: nowrap;
    text-align: center;
}
.s4r-table tbody tr {
    border-bottom: 1px solid rgba(100,160,255,0.10);
    transition: background .15s;
}
.s4r-table tbody tr:hover { background: var(--nm-dv2-hover); }
.s4r-table tbody td {
    padding: 9px 8px;
    text-align: center;
    vertical-align: middle;
    color: var(--rl-text);
}
.s4r-table a { color: var(--rl-accent); text-decoration: none; }
.s4r-table a:hover { text-decoration: underline; }
.s4r-table .empty-row td {
    text-align: center;
    padding: 32px;
    color: var(--rl-muted);
}

/* ── 페이지네이션 ── */
.s4r-pagination { padding: 14px 8px; }
.s4r-pagination .page_f a,
.s4r-pagination .page_f span { color: var(--rl-accent) !important; }

/* ── 푸터바 ── */
.s4r-foot-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 12px 8px;
    border-top: 1px solid var(--rl-border);
    gap: 8px;
    flex-wrap: wrap;
}
.s4r-foot-bar input[type="text"] {
    background: var(--rl-input-bg) !important;
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    padding: 6px 10px;
    color: #ffffff;
    font-size: 13px;
}

/* ── 상태 뱃지 ── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.badge-success { background: rgba(130,200,54,.20); color: #82c836; }
.badge-wait    { background: rgba(168,200,255,.15); color: #a8c8ff; }
.badge-fail    { background: rgba(224,90,90,.20);  color: #e05a5a; }
.badge-reserve { background: rgba(255,180,50,.15); color: #ffb432; }

/* ── 팝업/오버레이 ── */
.s4r-modal-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,.55); z-index: 150; display: none;
}
.s4r-modal-box {
    background: var(--rl-card);
    border: 1px solid var(--rl-border);
    border-radius: 14px;
    padding: 24px;
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    z-index: 200;
    min-width: 320px;
    display: none;
    box-shadow: 0 12px 40px rgba(0,0,0,.5);
    color: var(--rl-text);
}
.s4r-modal-title {
    font-size: 16px; font-weight: 700;
    color: var(--rl-success);
    margin-bottom: 12px;
    display: flex; justify-content: space-between; align-items: center;
}
.s4r-modal-close { cursor: pointer; color: var(--rl-muted); font-size: 20px; }
.s4r-modal-list  { padding: 0; list-style: none; }
.s4r-modal-list li {
    padding: 8px 0;
    border-bottom: 1px solid var(--rl-border);
    font-size: 14px;
    color: var(--rl-text);
}

/* ── 제외번호 등록 모달 ── */
.s4r-deny-modal {
    background: var(--rl-card);
    border: 1px solid var(--rl-border);
    border-radius: 14px;
    padding: 28px 32px;
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    z-index: 200;
    width: 480px;
    max-width: 95vw;
    display: none;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
    color: var(--rl-text);
}
.s4r-deny-modal textarea {
    background: var(--rl-input-bg) !important;
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    color: #ffffff;
    font-size: 13px;
    width: 100%;
    height: 140px;
    padding: 10px;
    resize: vertical;
}
.s4r-deny-modal select {
    background: var(--rl-input-bg) !important;
    border: 1px solid var(--rl-border);
    border-radius: 8px;
    padding: 7px;
    color: #ffffff;
}

/* ── 로딩 오버레이 ── */
#tutorial-loading {
    position: fixed; top:0; left:0; width:100%; height:100%;
    z-index:100; display:none;
    background: rgba(0,0,0,0.65);
}

@media (max-width: 768px) {
    .s4r-wrap { padding: 12px 10px 60px; }
    .s4r-main-tab { font-size: 13px; padding: 9px 10px; }
    .s4r-table { font-size: 11px; }
}
</style>

<!-- ── 메인 콘텐츠 래퍼 ── -->
<main class="common-wrap" id="rl-main">
<div class="s4r-wrap">

    <!-- ── 페이지 히어로 배너 ── -->
    <div class="page-hero page-hero--action" style="margin-bottom:20px;">
        <div class="page-hero-inner">
            <div class="page-hero-content">
                <span class="page-hero-badge">수발신내역</span>
                <h1 class="page-hero-title">수발신내역 <em>통합 조회</em></h1>
                <p class="page-hero-desc">발신내역 · 수신내역 · 수신여부를 한 화면에서 확인하고 관리하세요.</p>
            </div>
            <div class="page-hero-actions">
                <a class="page-hero__btn page-hero__btn--secondary" href="sub_4_return_.php?tab=1">📤 발신내역</a>
                <a class="page-hero__btn page-hero__btn--secondary" href="sub_4_return_.php?tab=2">📥 수신내역</a>
                <a class="page-hero__btn page-hero__btn--secondary" href="sub_4_return_.php?tab=3">✅ 수신여부</a>
            </div>
        </div>
    </div>

    <!-- ── 메인 탭 네비 ── -->
    <div class="s4r-main-tab-wrap">
        <a class="s4r-main-tab <?= $tab==1?'active':'' ?>" href="sub_4_return_.php?tab=1">📤 발신내역</a>
        <a class="s4r-main-tab <?= $tab==2?'active':'' ?>" href="sub_4_return_.php?tab=2">📥 수신내역</a>
        <a class="s4r-main-tab <?= $tab==3?'active':'' ?>" href="sub_4_return_.php?tab=3">✅ 수신여부</a>
    </div>

<?php /* ═══════════ TAB 1 : 발신내역 ═══════════ */ if ($tab == 1): ?>

    <!-- 서브탭: 채널 선택 -->
    <div class="s4r-tab-wrap">
        <a href="sub_4_return_.php?tab=1"           class="s4r-tab <?= !$chanel     ? 'active' : '' ?>">📤 발신/회신문자</a>
        <a href="sub_4_return_.php?tab=1&chanel=2"  class="s4r-tab <?= $chanel==2  ? 'active' : '' ?>">🔁 퍼널</a>
        <a href="sub_4_return_.php?tab=1&chanel=4"  class="s4r-tab <?= $chanel==4  ? 'active' : '' ?>">📅 데일리</a>
        <a href="sub_4_return_.php?tab=1&chanel=9"  class="s4r-tab <?= $chanel==9  ? 'active' : '' ?>">📞 콜백</a>
    </div>

    <!-- 검색 폼 -->
    <div class="s4r-card">
        <form name="t1_form" id="t1_form" method="get">
            <input type="hidden" name="tab" value="1">
            <input type="hidden" name="chanel" value="<?= htmlspecialchars($chanel, ENT_QUOTES) ?>">
            <input type="hidden" name="order_name" value="<?= htmlspecialchars($order_name, ENT_QUOTES) ?>">
            <input type="hidden" name="order_status" value="<?= htmlspecialchars($order_status, ENT_QUOTES) ?>">
            <input type="hidden" name="page" value="1">
            <div class="s4r-search-row">
                <select name="serch_fs_select">
                    <option value="">검색 필드</option>
                    <?php foreach (['send_num'=>'발신번호','recv_num'=>'수신번호','title'=>'문자제목','content'=>'문자내용'] as $k=>$v): ?>
                        <option value="<?=$k?>" <?=$fs_select==$k?'selected':''?>><?=$v?></option>
                    <?php endforeach; ?>
                </select>
                <select name="result">
                    <option value="">전체</option>
                    <option value="1" <?=$result_filter==1?'selected':''?>>성공</option>
                    <option value="2" <?=$result_filter==2?'selected':''?>>대기</option>
                    <option value="3" <?=$result_filter==3?'selected':''?>>실패</option>
                    <option value="4" <?=$result_filter==4?'selected':''?>>예약</option>
                </select>
                <input type="text" name="serch_fs_text" value="<?= htmlspecialchars($fs_text, ENT_QUOTES) ?>" placeholder="검색어">
                <?php if ($chanel != 9): ?>
                    <select name="search_category">
                        <option value="">날짜기준</option>
                        <option value="reservation" <?=$search_category=='reservation'?'selected':''?>>발송예정시간</option>
                        <option value="up_date"     <?=$search_category=='up_date'?'selected':''?>>발송완료시간</option>
                        <option value="reg_date"    <?=$search_category=='reg_date'?'selected':''?>>등록시간</option>
                    </select>
                    <input type="date" name="startdate" value="<?= htmlspecialchars($startdate, ENT_QUOTES) ?>" style="padding:6px;">
                    <input type="date" name="enddate"   value="<?= htmlspecialchars($enddate, ENT_QUOTES) ?>"   style="padding:6px;">
                <?php endif; ?>
                <button type="submit" class="s4r-btn s4r-btn-primary">🔍 검색</button>
                <a href="sub_4_return_.php?tab=1&chanel=<?= htmlspecialchars($chanel, ENT_QUOTES) ?>" class="s4r-btn s4r-btn-gray">초기화</a>
            </div>
        </form>
    </div>

    <!-- 액션바 -->
    <div class="s4r-action-bar">
        <span class="s4r-count-badge">총 <?= number_format($intRowCount) ?>건</span>
        <?php if ($chanel == 4): ?>
            <a href="javascript:show_daily('all')"    class="s4r-btn s4r-btn-gray">전체보기</a>
            <a href="javascript:show_daily('normal')" class="s4r-btn s4r-btn-blue">데일리발송</a>
            <a href="javascript:show_daily('step')"   class="s4r-btn s4r-btn-primary">데일리퍼널</a>
        <?php endif; ?>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="javascript:selected_msg_del()" class="s4r-btn s4r-btn-danger">☒ 선택삭제</a>
            <a href="javascript:all_msg_del('<?= htmlspecialchars($chanel, ENT_QUOTES) ?>')" class="s4r-btn s4r-btn-danger">🗑 전체삭제</a>
        </div>
    </div>

    <!-- 테이블 -->
    <div class="s4r-card" style="padding:0;">
        <div class="s4r-table-wrap">
            <table class="s4r-table">
                <thead>
                    <tr>
                        <th style="width:4%"><input type="checkbox" onclick="check_all(this,'fs_idx');"> 번호</th>
                        <th style="width:7%">소유자</th>
                        <th style="width:9%">발신번호</th>
                        <th style="width:10%">수신번호</th>
                        <th style="width:8%">문자제목</th>
                        <th style="width:14%">문자내용</th>
                        <th style="width:6%">첨부</th>
                        <th style="width:9%">PC전송시간</th>
                        <th style="width:9%">발송예정</th>
                        <th style="width:9%">발송완료</th>
                        <th style="width:8%">성공/실패</th>
                        <th style="width:5%">회신수</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($intRowCount > 0):
                    $c = 0;
                    while ($row = mysqli_fetch_array($result_data)):
                        $row['content'] = stripslashes($row['content']);
                        $row['content'] = str_replace("\\r\\n", "\n", $row['content']);
                        $row['content'] = htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8');

                        $sql_s = "SELECT status,regdate FROM Gn_MMS_status WHERE idx='{$row['idx']}'";
                        $res_s = mysqli_query($self_con, $sql_s);
                        $row_s = mysqli_fetch_array($res_s);

                        $sql_n = "SELECT memo FROM Gn_MMS_Number WHERE mem_id='{$_SESSION['one_member_id']}' AND sendnum='{$row['send_num']}'";
                        $res_n = mysqli_query($self_con, $sql_n);
                        $row_n = mysqli_fetch_array($res_n);

                        $recv_num_arr = explode(",", $row['recv_num']);
                        $recv_num_in  = "'" . implode("','", $recv_num_arr) . "'";
                        $total_cnt    = count($recv_num_arr);

                        $date_val  = $row['up_date'];
                        $reply_cnt = 0;
                        if ($date_val != "") {
                            $sql_reply = "SELECT count(seq) as cnt FROM call_app_log WHERE api_name='receive_sms' AND LENGTH(recv_num)>=10 AND send_num='{$row['send_num']}' AND recv_num in ($recv_num_in) AND recv_num like '01%' AND regdate >= '$date_val' AND sms not like '[%'";
                            $res_reply = mysqli_query($self_con, $sql_reply) or die(mysqli_error($self_con));
                            $row_reply = mysqli_fetch_array($res_reply);
                            $reply_cnt = $row_reply['cnt'];
                        }

                        $sql_cs = "SELECT count(idx) as cnt FROM Gn_MMS_status WHERE idx='{$row['idx']}' AND status='0'";
                        $res_cs = mysqli_query($self_con, $sql_cs);
                        $row_cs = mysqli_fetch_array($res_cs);
                        $success_cnt = (int)$row_cs[0];
                        if ($success_cnt > $total_cnt) $success_cnt = $total_cnt;

                        $reg_date_1hour = strtotime("{$row['reg_date']} +1hours");

                        $show_image_tags = "";
                        if ($row['jpg'])  $show_image_tags .= "<img src='{$row['jpg']}' style='width:500px;'/><br/>";
                        if ($row['jpg1']) $show_image_tags .= "<img src='{$row['jpg1']}' style='width:500px;'/><br/>";
                        if ($row['jpg2']) $show_image_tags .= "<img src='{$row['jpg2']}' style='width:500px;'/><br/>";
                ?>
                    <tr>
                        <td><label><input type="checkbox" name="fs_idx" value="<?=$row['idx']?>"> <?=$sort_no?></label></td>
                        <td><?= htmlspecialchars($row_n['memo'] ?? '', ENT_QUOTES) ?></td>
                        <td>
                            <?= htmlspecialchars($row['send_num'], ENT_QUOTES) ?>
                            <?php if (!$chanel): ?>
                                <br><span style="color:var(--rl-accent);font-size:11px;"><?= $row['type'] ? '(묶음)' : '(개별)' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="javascript:show_recv('show_recv_num','<?=$c?>','수신번호')"><?= str_substr($row['recv_num'], 0, 14, 'utf-8') ?></a>
                            <span style="color:var(--rl-danger);font-weight:700;">(<?=$total_cnt?>)</span>
                            <?php if ($row['grp_idx'] && !$chanel): ?>
                                <span style="color:var(--rl-accent);cursor:pointer;" onclick="show_grp_detail('<?=$row['grp_idx']?>','<?=$row['count_start']?>','<?=$row['count_end']?>')">[보기]</span>
                            <?php endif; ?>
                            <input type="hidden" name="show_recv_num" value="<?= htmlspecialchars($row['recv_num'], ENT_QUOTES) ?>">
                        </td>
                        <td>
                            <a href="javascript:show_recv('show_title','<?=$c?>','문자제목')"><?= str_substr($row['title'], 0, 12, 'utf-8') ?></a>
                            <input type="hidden" name="show_title" value="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>">
                        </td>
                        <td>
                            <a href="javascript:show_recv('show_content','<?=$c?>','문자내용')"><?= str_substr($row['content'], 0, 28, 'utf-8') ?></a>
                            <input type="hidden" name="show_content" value="<?= $row['content'] . $show_image_tags ?>">
                        </td>
                        <td>
                            <?php if ($row['jpg']): ?>
                                <a href="javascript:show_recv('show_jpg','<?=$c?>','첨부파일')">📎이미지</a>
                                <input type="hidden" name="show_jpg" value="<?= htmlspecialchars($row['jpg'], ENT_QUOTES) ?>">
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?= substr($row['reg_date'], 0, 16) ?></td>
                        <td style="font-size:12px;"><?= $row['reservation'] ? substr($row['reservation'], 0, 16) : '' ?></td>
                        <td style="font-size:12px;">
                            <?php
                            if ($row_s['status'] == "-1") {
                                echo '기본앱아님';
                            } elseif (time() > $reg_date_1hour && $row_s['regdate'] == "") {
                                if ($row['reservation'] != "" && $row['reservation'] > date("Y-m-d H:i:s")) {
                                    echo '<a href="javascript:fs_del_num(\'' . $row['idx'] . '\')">취소가능</a>';
                                } elseif ($row['reservation']) {
                                    echo '<span class="badge badge-reserve">예약</span>';
                                } else {
                                    echo '<a href="javascript:fs_del_num(\'' . $row['idx'] . '\')">미수신</a>';
                                }
                            } else {
                                echo substr($row_s['regdate'], 0, 16);
                            }
                            ?>
                        </td>
                        <td style="font-size:12px;">
                            <?php
                            if ($success_cnt == 0) {
                                if (time() > $reg_date_1hour && $row['up_date'] == "") {
                                    if ($row['reservation'] > date("Y-m-d H:i:s")) {
                                        echo '<span class="badge badge-reserve">예약</span>';
                                    } else {
                                        echo '<span class="badge badge-fail">실패</span>';
                                    }
                                } elseif (time() > $reg_date_1hour && $row['up_date'] != "") {
                                    echo '<span class="badge badge-fail">발송실패</span>';
                                } elseif ($row['up_date'] == "" && $row['reservation'] < date("Y-m-d H:i:s")) {
                                    echo '<a href="sub_4_detail.php?idx=' . $row['idx'] . '">발송중</a>';
                                }
                            } else {
                                echo '<a href="sub_4_detail.php?idx=' . $row['idx'] . '">' . $success_cnt . '/' . ($total_cnt - $success_cnt) . '</a>';
                                if ($row['reservation']) echo ' <span class="badge badge-reserve">예약</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="sub_4_return_detail.php?idx=<?=$row['idx']?>&send_num=<?=$row['send_num']?>"><?=$reply_cnt?></a>
                        </td>
                    </tr>
                <?php $c++; $sort_no--; endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row"><td colspan="12">등록된 발신내역이 없습니다.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="s4r-pagination">
            <?php page_f($page, $page2, $intPageCount, "t1_form"); ?>
        </div>
        <div class="s4r-foot-bar">
            <a href="javascript:excel_down('excel_down/fs_down.php?status=1')" class="s4r-btn s4r-btn-excel">📊 엑셀 다운로드</a>
        </div>
    </div>

    <form name="excel_down_form" action="" target="excel_iframe" method="post">
        <input type="hidden" name="grp_id" value="">
        <input type="hidden" name="box_text" value="">
        <input type="hidden" name="excel_sql" value="<?= htmlspecialchars($excel_sql, ENT_QUOTES) ?>">
    </form>
    <iframe name="excel_iframe" style="display:none;"></iframe>

    <!-- 그룹상세 모달 -->
    <div class="s4r-modal-overlay" id="grp_overlay" onclick="hide_mail_box()"></div>
    <div class="s4r-modal-box" id="modal_grp_detail">
        <div class="s4r-modal-title">
            발송상세보기
            <span class="s4r-modal-close" onclick="hide_mail_box()">✕</span>
        </div>
        <ul class="s4r-modal-list">
            <li>그룹명 : <strong id="grp_name"></strong></li>
            <li>총건수 : <strong id="count_all"></strong></li>
            <li id="count_sent_li" style="display:none;">발송건 : <strong id="count_sent"></strong></li>
        </ul>
    </div>

    <script>
    function show_daily(type) {
        var params = new URLSearchParams({
            tab: 1,
            serch_fs_select: <?= json_encode($fs_select) ?>,
            result: <?= json_encode($result_filter) ?>,
            serch_fs_text: <?= json_encode($fs_text) ?>,
            order_status: <?= json_encode($order_status) ?>,
            page: 1,
            chanel: <?= json_encode($chanel) ?>,
            daily_type: type
        });
        location.href = "sub_4_return_.php?" + params.toString();
    }
    function selected_msg_del() {
        var idx_arr = [];
        $('input[name=fs_idx]:checked').each(function() { idx_arr.push($(this).val()); });
        if (!idx_arr.length) { alert("삭제할 메시지를 선택하세요."); return; }
        if (!confirm("선택한 메시지를 삭제하시겠습니까?")) return;
        $.ajax({
            type:"POST", url:"/admin/ajax/delete_func.php", dataType:"json",
            data:{ admin:0, delete_name:"mms_del", id:idx_arr.toString() },
            success:function(data){ if(data==1){ alert('삭제되었습니다.'); location.reload(); } }
        });
    }
    function all_msg_del(type) {
        if (!confirm("모든 페이지 데이터를 전부 삭제합니다. 계속하시겠습니까?")) return;
        $.ajax({
            type:"POST", url:"/admin/ajax/delete_func.php", dataType:"json",
            data:{ admin:0, delete_name:"mms_del", mem_id:'<?= $_SESSION['one_member_id'] ?>', type:type },
            success:function(data){ if(data==1){ alert('삭제되었습니다.'); location.reload(); } }
        });
    }
    function show_grp_detail(grp_id, start, end) {
        $.ajax({
            type:"POST", url:"/admin/ajax/mms_group_detail.php", dataType:"json",
            data:{ grp_id:grp_id },
            success:function(data) {
                $("#grp_name").text(data.grp);
                $("#count_all").text(data.count);
                if (start != 0 && end != 0) { $("#count_sent_li").show(); $("#count_sent").text(start+' - '+end); }
                else { $("#count_sent_li").hide(); }
                $("#modal_grp_detail").show();
                $("#grp_overlay").show();
            }
        });
    }
    function hide_mail_box() {
        $("#modal_grp_detail").hide();
        $("#grp_overlay").hide();
    }
    </script>

<?php /* ═══════════ TAB 2 : 수신내역 ═══════════ */ elseif ($tab == 2): ?>

    <!-- 서브탭: 수신 유형 -->
    <div class="s4r-tab-wrap">
        <a href="sub_4_return_.php?tab=2"           class="s4r-tab <?= !($_REQUEST['status2']??'')    ? 'active' : '' ?>">전체</a>
        <a href="sub_4_return_.php?tab=2&status2=3" class="s4r-tab <?= ($_REQUEST['status2']??'')==3  ? 'active' : '' ?>">수신불가</a>
        <a href="sub_4_return_.php?tab=2&status2=2" class="s4r-tab <?= ($_REQUEST['status2']??'')==2  ? 'active' : '' ?>">없는번호</a>
        <a href="sub_4_return_.php?tab=2&status2=1" class="s4r-tab <?= ($_REQUEST['status2']??'')==1  ? 'active' : '' ?>">번호변경</a>
    </div>

    <!-- 검색 폼 -->
    <div class="s4r-card">
        <form name="t2_form" id="t2_form" method="post">
            <input type="hidden" name="tab" value="2">
            <input type="hidden" name="status2" value="<?= htmlspecialchars($_REQUEST['status2']??'', ENT_QUOTES) ?>">
            <input type="hidden" name="order_name" value="seq">
            <input type="hidden" name="order_status" value="desc">
            <input type="hidden" name="page" value="1">
            <div class="s4r-search-row">
                <select name="serch_colum">
                    <option value="">검색 필드</option>
                    <?php foreach (['ori_num'=>'수신번호','dest'=>'발신번호','msg_text'=>'문자내용'] as $k=>$v): ?>
                        <option value="<?=$k?>" <?=$serch_col2==$k?'selected':''?>><?=$v?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="serch_text" value="<?= htmlspecialchars($serch_text2, ENT_QUOTES) ?>" placeholder="검색어">
                <button type="submit" class="s4r-btn s4r-btn-primary">🔍 검색</button>
                <a href="sub_4_return_.php?tab=2" class="s4r-btn s4r-btn-gray">초기화</a>
                <?php if (($_REQUEST['status2']??'')==1): ?>
                    <a href="javascript:fugai_num('','all')" class="s4r-btn s4r-btn-blue">일괄 덮기</a>
                <?php endif; ?>
                <?php if (($_REQUEST['status2']??'')==2): ?>
                    <a href="javascript:deleteAddress()" class="s4r-btn s4r-btn-danger">주소록에서 삭제</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 액션바 -->
    <div class="s4r-action-bar">
        <span class="s4r-count-badge">총 <?= number_format($intRowCount2) ?>건</span>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="javascript:excel_down('excel_down/log_down.php','','','1')" class="s4r-btn s4r-btn-excel">📊 엑셀</a>
            <a href="javascript:log_del()" class="s4r-btn s4r-btn-danger">🗑 삭제</a>
        </div>
    </div>

    <!-- 테이블 -->
    <div class="s4r-card" style="padding:0;">
        <div class="s4r-table-wrap">
            <table class="s4r-table">
                <thead>
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="check_all(this,'idx_box');"> 선택</th>
                        <th style="width:9%">발신번호</th>
                        <th style="width:8%">소유자명</th>
                        <th style="width:12%">수신일시</th>
                        <th style="width:<?= ($_REQUEST['status2']??'')==1 ? '28%' : '40%' ?>">문자내용</th>
                        <th style="width:10%">수신번호</th>
                        <?php if (($_REQUEST['status2']??'')==1): ?><th style="width:10%">변경된번호</th><?php endif; ?>
                        <th style="width:7%">그룹명</th>
                        <?php if (($_REQUEST['status2']??'')==1): ?><th style="width:8%">처리</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($intRowCount2 > 0):
                    while ($row = mysqli_fetch_array($result_data2)):
                        $sql_n2 = "SELECT memo FROM Gn_MMS_Number WHERE sendnum='{$row['dest']}'";
                        $res_n2 = mysqli_query($self_con, $sql_n2);
                        $row_n2 = mysqli_fetch_array($res_n2);
                ?>
                    <tr>
                        <td><label><input type="checkbox" name="idx_box" value="<?=$row['seq']?>"> <?=$sort_no2?></label></td>
                        <td><?= htmlspecialchars($row['dest'] ?? '', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($row_n2['memo'] ?? '', ENT_QUOTES) ?></td>
                        <td style="font-size:12px;"><?= substr($row['reservation_time'], 0, 16) ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($row['msg_text'] ?? '', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($row['ori_num'] ?? '', ENT_QUOTES) ?></td>
                        <?php if (($_REQUEST['status2']??'')==1): ?>
                            <td><?= htmlspecialchars($row['chg_num'] ?? '', ENT_QUOTES) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['grp_name'] ?? '', ENT_QUOTES) ?></td>
                        <?php if (($_REQUEST['status2']??'')==1): ?>
                            <td><?php if ($row['chg_num']): ?>
                                <a href="javascript:fugai_num('<?=$row['seq']?>','cho')" class="s4r-btn s4r-btn-blue" style="font-size:12px;padding:4px 10px;">덮어쓰기</a>
                            <?php endif; ?></td>
                        <?php endif; ?>
                    </tr>
                <?php $sort_no2--; endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row"><td colspan="8">수신내역이 없습니다.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="s4r-pagination">
            <?php page_f($page2a, $page2b, $intPageCount2, "t2_form"); ?>
        </div>
        <div class="s4r-foot-bar">
            <span style="color:var(--rl-muted);font-size:13px;">새로 추가 :</span>
            <input type="text" id="log_dest_new" placeholder="보낸번호">
            <input type="text" id="log_ori_new"  placeholder="수신번호">
            <button onclick="log_add_new()" class="s4r-btn s4r-btn-primary">저장</button>
        </div>
    </div>

    <form name="t2_form_log" id="t2_form_log" method="post">
        <input type="hidden" name="log_dest" id="log_dest_h">
        <input type="hidden" name="log_ori"  id="log_ori_h">
        <input type="hidden" name="status2"  value="<?= htmlspecialchars($_REQUEST['status2']??'', ENT_QUOTES) ?>">
    </form>
    <form name="excel_down_form" action="" target="excel_iframe2" method="post">
        <input type="hidden" name="excel_sql" value="<?= htmlspecialchars($excel_sql2, ENT_QUOTES) ?>">
    </form>
    <iframe name="excel_iframe2" style="display:none;"></iframe>

    <script>
    function log_add_new() {
        document.getElementById('log_dest_h').value = document.getElementById('log_dest_new').value;
        document.getElementById('log_ori_h').value  = document.getElementById('log_ori_new').value;
        log_add(document.getElementById('t2_form_log'), '0', '<?= htmlspecialchars($_REQUEST['status2']??'', ENT_QUOTES) ?>');
    }
    </script>

<?php /* ═══════════ TAB 3 : 수신여부 ═══════════ */ elseif ($tab == 3): ?>

    <!-- 서브탭: 수신거부/수신동의 -->
    <div class="s4r-tab-wrap">
        <a href="sub_4_return_.php?tab=3&agree_type=deny"  class="s4r-tab <?= ($agree_type=='deny')  ? 'active' : '' ?>">🚫 수신거부</a>
        <a href="sub_4_return_.php?tab=3&agree_type=agree" class="s4r-tab <?= ($agree_type=='agree') ? 'active' : '' ?>">✅ 수신동의</a>
    </div>

    <!-- 검색 폼 -->
    <div class="s4r-card">
        <form name="t3_form" id="t3_form" method="get">
            <input type="hidden" name="tab" value="3">
            <input type="hidden" name="agree_type" value="<?= htmlspecialchars($agree_type, ENT_QUOTES) ?>">
            <input type="hidden" name="page" value="1">
            <div class="s4r-search-row">
                <select name="serch_colum">
                    <option value="">검색 필드</option>
                    <option value="send_num" <?=$serch_col3=='send_num'?'selected':''?>>발신번호</option>
                    <option value="recv_num" <?=$serch_col3=='recv_num'?'selected':''?>>수신번호</option>
                </select>
                <?php if ($agree_type === 'deny'): ?>
                    <select name="serch_chanel">
                        <option value="">채널</option>
                        <?php foreach (['1'=>'폰문자','2'=>'퍼널문자','9'=>'콜백문자','4'=>'데일리문자'] as $k=>$v): ?>
                            <option value="<?=$k?>" <?=(($_REQUEST['serch_chanel']??'')==$k)?'selected':''?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <input type="text" name="serch_text" value="<?= htmlspecialchars($serch_text3, ENT_QUOTES) ?>" placeholder="검색어">
                <button type="submit" class="s4r-btn s4r-btn-primary">🔍 검색</button>
                <a href="sub_4_return_.php?tab=3&agree_type=<?= htmlspecialchars($agree_type, ENT_QUOTES) ?>" class="s4r-btn s4r-btn-gray">초기화</a>
                <?php if ($agree_type === 'deny'): ?>
                    <a href="javascript:deny_add_multi()" class="s4r-btn s4r-btn-blue">메시지 발송 제외번호 등록</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- 액션바 -->
    <div class="s4r-action-bar">
        <span class="s4r-count-badge">전체 <?= number_format($intRowCount3) ?>건</span>
        <span class="s4r-count-badge">오늘 <?= $row_today3['today_cnt'] ?>건</span>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <?php if ($agree_type === 'deny'): ?>
                <a href="javascript:excel_down('excel_down/deny_down.php?down_type=1')" class="s4r-btn s4r-btn-excel">📊 엑셀</a>
                <a href="javascript:deny_del()" class="s4r-btn s4r-btn-danger">🗑 삭제</a>
            <?php else: ?>
                <a href="javascript:excel_down('excel_down/agree_down.php?down_type=1')" class="s4r-btn s4r-btn-excel">📊 엑셀</a>
                <a href="javascript:agree_del()" class="s4r-btn s4r-btn-danger">🗑 삭제</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 테이블 -->
    <form name="sub_4_form" id="sub_4_form" method="get">
        <input type="hidden" name="tab" value="3">
        <input type="hidden" name="agree_type" value="<?= htmlspecialchars($agree_type, ENT_QUOTES) ?>">
        <input type="hidden" name="order_name" value="<?= htmlspecialchars($order_name3, ENT_QUOTES) ?>">
        <input type="hidden" name="order_status" value="<?= htmlspecialchars($order_status3, ENT_QUOTES) ?>">
        <input type="hidden" name="page" value="<?= $page3a ?>">
        <input type="hidden" name="page2" value="<?= $page3b ?>">
    <div class="s4r-card" style="padding:0;">
        <div class="s4r-table-wrap">
            <table class="s4r-table">
                <thead>
                    <tr>
                        <th style="width:5%"><input type="checkbox" onclick="check_all(this,'idx_box');"> 번호</th>
                        <th style="width:6%">소유자</th>
                        <th style="width:10%">발신번호</th>
                        <th style="width:10%">수신번호</th>
                        <th style="width:10%">문자제목</th>
                        <th style="width:12%">문자내용</th>
                        <th style="width:8%">첨부파일</th>
                        <?php if ($agree_type === 'deny'): ?><th style="width:6%">채널</th><?php endif; ?>
                        <th style="width:6%">등록경로</th>
                        <th style="width:12%">등록일시</th>
                        <th style="width:8%">관리</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i3 = 0;
                if ($intRowCount3 > 0):
                    while ($row = mysqli_fetch_array($result_data3)):
                        $sql_n3 = "SELECT memo FROM Gn_MMS_Number WHERE sendnum='{$row['send_num']}'";
                        $res_n3 = mysqli_query($self_con, $sql_n3);
                        $row_n3 = mysqli_fetch_array($res_n3);
                ?>
                    <tr>
                        <td><label><input type="checkbox" name="idx_box" value="<?=$row['idx']?>"> <?=$sort_no3?></label></td>
                        <td><?= htmlspecialchars($row_n3['memo'] ?? '', ENT_QUOTES) ?></td>
                        <td class="g_dt_name_<?=$i3?>"><?= htmlspecialchars($row['send_num'] ?? '', ENT_QUOTES) ?></td>
                        <td style="display:none;" class="g_dt_name_<?=$i3?>"><input type="text" name="<?= $agree_type==='deny' ? 'deny_send' : 'agree_send' ?>" value="<?= htmlspecialchars($row['send_num']??'', ENT_QUOTES) ?>"></td>
                        <td class="g_dt_num_<?=$i3?>"><?= htmlspecialchars($row['recv_num'] ?? '', ENT_QUOTES) ?></td>
                        <td style="display:none;" class="g_dt_num_<?=$i3?>"><input type="text" name="<?= $agree_type==='deny' ? 'deny_recv' : 'agree_recv' ?>" value="<?= htmlspecialchars($row['recv_num']??'', ENT_QUOTES) ?>"></td>
                        <td>
                            <a href="javascript:show_recv('show_title','<?=$i3?>','문자제목')"><?= str_substr($row['title'] ?? '', 0, 16, 'utf-8') ?></a>
                            <input type="hidden" name="show_title" value="<?= htmlspecialchars($row['title']??'', ENT_QUOTES) ?>">
                        </td>
                        <td>
                            <a href="javascript:show_recv('show_content','<?=$i3?>','문자내용')"><?= str_substr($row['content'] ?? '', 0, 24, 'utf-8') ?></a>
                            <input type="hidden" name="show_content" value="<?= htmlspecialchars($row['content']??'', ENT_QUOTES) ?>">
                        </td>
                        <td>
                            <?php if ($row['jpg'] ?? ''): ?>
                                <a href="javascript:show_recv('show_jpg','<?=$i3?>','첨부파일')">📎</a>
                                <input type="hidden" name="show_jpg" value="<?= htmlspecialchars($row['jpg'], ENT_QUOTES) ?>">
                            <?php endif; ?>
                        </td>
                        <?php if ($agree_type === 'deny'): ?>
                        <td>
                            <?php
                            $chanel_names = [1=>'폰문자',2=>'퍼널문자',9=>'콜백문자',4=>'데일리문자'];
                            echo $chanel_names[(int)($row['chanel_type']??0)] ?? '';
                            ?>
                        </td>
                        <?php endif; ?>
                        <td><?= $deny_type_arr[$row['status']??''] ?? '' ?></td>
                        <td style="font-size:12px;"><?= substr($row['reg_date'] ?? '', 0, 16) ?></td>
                        <td>
                            <a href="javascript:void(0)" class="modify_btn_<?=$i3?> s4r-btn s4r-btn-blue" style="font-size:12px;padding:4px 10px;"
                               onclick="g_dt_show_cencle('g_dt_name_','g_dt_num_','modify_btn_','<?=$i3?>')">수정</a>
                            <a href="javascript:void(0)" class="modify_btn_<?=$i3?> s4r-btn s4r-btn-primary" style="font-size:12px;padding:4px 10px;display:none;"
                               onclick="<?= $agree_type==='deny' ? 'deny_add(sub_4_form,\''.$i3.'\',\''.$row['idx'].'\','.(int)($row['chanel_type']??1).')' : 'agree_add(sub_4_form,\''.$i3.'\',\''.$row['idx'].'\')' ?>">저장</a>
                            <a href="javascript:void(0)" class="s4r-btn s4r-btn-danger" style="font-size:12px;padding:4px 10px;"
                               onclick="<?= $agree_type==='deny' ? 'deny_del(\''.$row['idx'].'\')' : 'agree_del(\''.$row['idx'].'\')' ?>">삭제</a>
                        </td>
                    </tr>
                <?php $i3++; $sort_no3--; endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row"><td colspan="<?= $agree_type==='deny' ? 11 : 10 ?>">등록된 내용이 없습니다.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="s4r-pagination">
            <?php page_f($page3a, $page3b, $intPageCount3, "t3_form"); ?>
        </div>
        <?php if ($agree_type === 'deny'): ?>
        <div class="s4r-foot-bar" style="flex-direction:column;align-items:flex-end;gap:12px;">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                <span style="color:var(--rl-muted);font-size:13px;">새로 추가 :</span>
                <select name="reg_chanel" id="reg_chanel" style="padding:7px;border:1px solid var(--rl-border);border-radius:8px;background:var(--rl-input-bg);color:#ffffff;">
                    <option value="1">폰문자</option>
                    <option value="2">퍼널문자</option>
                    <option value="9">콜백문자</option>
                    <option value="4">데일리문자</option>
                </select>
                <input type="text" name="deny_send" id="deny_send_new" style="padding:7px 10px;border:1px solid var(--rl-border);border-radius:8px;background:var(--rl-input-bg);color:#ffffff;" placeholder="발신번호">
                <input type="text" name="deny_recv" id="deny_recv_new" style="padding:7px 10px;border:1px solid var(--rl-border);border-radius:8px;background:var(--rl-input-bg);color:#ffffff;" placeholder="수신번호">
                <button onclick="deny_add(sub_4_form,'<?=$i3?>',0)" class="s4r-btn s4r-btn-primary">저장</button>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="javascript:void(0)" onclick="excel_down('excel_down/deny_down.php?down_type=2')" class="s4r-btn s4r-btn-gray">수신거부샘플.xls</a>
                <label class="s4r-btn s4r-btn-blue" style="cursor:pointer;">
                    엑셀업로드 <input type="file" name="excel_file" style="display:none;" onchange="deny_excel_insert(sub_4_form,'deny')">
                </label>
            </div>
        </div>
        <?php else: ?>
        <div class="s4r-foot-bar">
            <span style="color:var(--rl-muted);font-size:13px;">새로 추가 :</span>
            <input type="text" name="agree_send" id="agree_send_new" style="padding:7px 10px;border:1px solid var(--rl-border);border-radius:8px;background:var(--rl-input-bg);color:#ffffff;" placeholder="발신번호">
            <input type="text" name="agree_recv" id="agree_recv_new" style="padding:7px 10px;border:1px solid var(--rl-border);border-radius:8px;background:var(--rl-input-bg);color:#ffffff;" placeholder="수신번호">
            <button onclick="agree_add(sub_4_form,'<?=$i3?>')" class="s4r-btn s4r-btn-primary">저장</button>
        </div>
        <?php endif; ?>
    </div>
    </form>

    <form name="excel_down_form" action="" target="excel_iframe3" method="post">
        <input type="hidden" name="excel_sql" value="<?= htmlspecialchars($excel_sql3, ENT_QUOTES) ?>">
    </form>
    <iframe name="excel_iframe3" style="display:none;"></iframe>

    <?php if ($agree_type === 'deny'): ?>
    <div class="s4r-modal-overlay" id="deny_overlay" onclick="cancel_set()"></div>
    <div class="s4r-deny-modal" id="deny_multi_modal">
        <div class="s4r-modal-title">
            콜백 제외 대상 추가
            <span class="s4r-modal-close" onclick="cancel_set()">✕</span>
        </div>
        <div style="display:flex;gap:8px;justify-content:center;margin-bottom:16px;">
            <button class="s4r-btn s4r-btn-blue" onclick="get_addr_list('undeny')">제외리스트 보기/해제</button>
            <button class="s4r-btn s4r-btn-gray" onclick="get_addr_list('deny')">내 주소록에서 제외대상 추가</button>
        </div>
        <h4 style="color:var(--rl-text);margin:0 0 8px;">콜백제외번호 입력</h4>
        <textarea id="deny_num_multi" placeholder="전화번호 (쉼표 또는 엔터로 구분)"></textarea>
        <div id="ajax_div" style="margin-top:8px;color:var(--rl-success);font-size:13px;"></div>
        <div style="margin-top:12px;padding:10px;background:rgba(130,200,54,.07);border-radius:8px;font-size:13px;color:var(--rl-muted);line-height:1.7;">
            1. 콜백제외 대상등록 : 주소록가져오기 또는 수동입력으로 제외대상을 등록하세요.<br>
            2. 콜백제외 해제설정 : 제외리스트/해제 클릭 후 번호를 선택해서 해제하세요.
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;justify-content:flex-end;">
            <button onclick="cancel_set()" class="s4r-btn s4r-btn-gray">취소</button>
            <button onclick="add_deny_multi()" class="s4r-btn s4r-btn-primary">등록</button>
        </div>
    </div>
    <input type="hidden" name="btn_type" id="btn_type" value="add_deny">

    <script>
    function deny_add_multi() { $("#deny_multi_modal").show(); $("#deny_overlay").show(); }
    function cancel_set()     { $("#deny_multi_modal").hide(); $("#deny_overlay").hide(); }
    function add_deny_multi() {
        var recv_nums = $("#deny_num_multi").val();
        var send_num  = '<?= $mem_phone ?>';
        var mem_id    = '<?= $_SESSION['one_member_id'] ?>';
        var type      = $("#btn_type").val();
        $.ajax({
            type:"POST", url:"/ajax/add_deny_multi.php",
            data:{ deny_add_send:send_num, deny_add_recv:recv_nums, mem_id:mem_id, reg_chanel:9, type:type },
            success:function(data){ $("#ajax_div").html(data); }
        });
    }
    function get_addr_list(val) {
        $("#btn_type").val(val == "deny" ? "add_deny" : "unadd_deny");
        window.open('/group_detail_for_adddeny.php?phone=<?= $phone ?>&mem_id=<?= $_SESSION['one_member_id'] ?>&type='+val,
            "deny_pop","toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=500,height=600");
    }
    </script>
    <?php endif; ?>

<?php endif; ?>

</div><!-- /.s4r-wrap -->
</main>

<div id="tutorial-loading"></div>

<?php include "_foot.php"; ?>
