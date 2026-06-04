<?php
$path = "./";
include_once $_SERVER["DOCUMENT_ROOT"] . "/_head_v01.php";
if (empty($_SESSION['one_member_id']) && empty($_SESSION['iam_member_id'])) {
    echo "<script>location.replace('/ma.php');</script>";
    exit;
}
if (!isset($_GET['reserv_type']))
    $_GET['reserv_type'] = $member_1['ai_status'];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<script>
    function copyHtml() {
        var trb = $.trim($('#sHtml').html());
        var IE = (document.all) ? true : false;
        if (IE) {
            if (confirm("이 소스코드를 복사하시겠습니까?")) {
                window.clipboardData.setData("Text", trb);
            }
        } else {
            temp = prompt("Ctrl+C를 눌러 클립보드로 복사하세요", trb);
        }
    }
    $(function() {
        $(".popbutton").click(function() {
            $('.ad_layer_info').lightbox_me({
                centered: true,
                onLoad: function() {}
            });
        });
        $("#reserv_type").on("change", function() {
            let reserv_type = $(this).val();
            let params = new URLSearchParams(window.location.search);
            if (reserv_type) {
                params.set("reserv_type", reserv_type);
                window.location.search = params.toString();
            } else {
                window.location.href = window.location.pathname;
            }
        });
    });
</script>
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 신청관리 리스트
   ============================================ */

/* ── CSS 변수 (nm-darkver2) ── */
:root {
    --nm-dv2-bg:        #18172F;
    --nm-dv2-card:      #1e2d50;
    --nm-dv2-card2:     #16305c;
    --nm-dv2-border:    rgba(100,160,255,0.20);
    --nm-dv2-text:      #f0f4ff;
    --nm-dv2-muted:     rgba(200,215,255,0.60);
    --nm-dv2-accent:    #5b9bff;
    --nm-dv2-green:     #82c836;
    --nm-dv2-red:       #e05a5a;
    --nm-dv2-input-bg:  rgba(255,255,255,0.07);
    --nm-dv2-hover:     rgba(100,160,255,0.08);
    --nm-dv2-shadow:    0 4px 24px rgba(0,0,0,0.55);
}

/* ── 페이지 래퍼 ── */
.mrl-wrap {
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    padding: 20px 20px 80px;
    box-sizing: border-box;
}

/* ── 좌우 레이아웃 ── */
.big_div  { background: var(--nm-dv2-bg); color: var(--nm-dv2-text); min-height: 100vh; }
.big_sub  { background: var(--nm-dv2-bg); }
.m_div    { background: var(--nm-dv2-bg); display: flex; align-items: flex-start; max-width: 1400px; margin: 0 auto; }
.m_body   { background: var(--nm-dv2-bg); padding: 16px 0; }

/* ── 좌측 메뉴 ── */
.mypage_left_menu,
.left_menu,
#left_menu {
    background: var(--nm-dv2-card) !important;
    border-color: var(--nm-dv2-border) !important;
}
.mypage_left_menu a,
.left_menu a,
#left_menu a {
    color: var(--nm-dv2-muted) !important;
}
.mypage_left_menu a:hover,
.left_menu a:hover,
#left_menu a:hover,
.mypage_left_menu a.active,
.left_menu a.on {
    color: var(--nm-dv2-green) !important;
    background: rgba(130,200,54,0.10) !important;
}

/* ── 타이틀 영역 ── */
.a1 {
    color: var(--nm-dv2-text) !important;
    font-size: var(--fz-step, 17px);
    font-weight: var(--fw-bold, 700);
    padding: 4px 0 12px;
}

/* ── 검색 폼 (p1) ── */
.p1 {
    background: var(--nm-dv2-card);
    border: 1px solid var(--nm-dv2-border);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 14px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.p1 select,
.p1 input[type=text],
select.select,
input[type=text] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
    transition: border-color .2s;
}
.p1 input[type=text]:focus,
input[type=text]:focus,
textarea:focus {
    border-color: var(--nm-dv2-green) !important;
    outline: none;
    box-shadow: 0 0 0 2px rgba(130,200,54,0.18);
}
textarea {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
}

/* ── 버튼 ── */
.button,
input[type=button].button {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: var(--fz-label, 14px);
    font-weight: var(--fw-semi, 600);
    cursor: pointer;
    transition: background .18s, border-color .18s;
}
.button:hover,
input[type=button].button:hover {
    background: var(--nm-dv2-green) !important;
    color: #000 !important;
    border-color: var(--nm-dv2-green) !important;
}

/* ── AI/수동 라디오 레이블 ── */
label {
    color: var(--nm-dv2-muted);
    font-size: var(--fz-label, 14px);
}

/* ── 리스트 테이블 ── */
.list_table {
    border-collapse: collapse;
    width: 100%;
    font-size: var(--fz-small, 13px);
}
.list_table tr,
.list_table td,
.list_table th {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    padding: 7px 8px;
    vertical-align: middle;
}
.list_table tr:first-child td,
.list_table thead th {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
}
.list_table tr:hover td {
    background: var(--nm-dv2-hover) !important;
}
.list_table a { color: var(--nm-dv2-accent); text-decoration: none; }
.list_table a:hover { color: var(--nm-dv2-green); text-decoration: underline; }

/* ── 페이징 ── */
.page_div a,
.page_f a,
.page_f span {
    color: var(--nm-dv2-muted) !important;
    background: var(--nm-dv2-card2) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: var(--fz-label, 14px);
}
.page_div a:hover,
.page_div a.on,
.page_f a:hover,
.page_f span.on {
    color: var(--nm-dv2-green) !important;
    border-color: var(--nm-dv2-green) !important;
}

/* ── 팝업박스 (툴팁) ── */
.popupbox {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 8px;
    box-shadow: var(--nm-dv2-shadow);
}
.pop_right {
    position: relative;
    right: 2px;
    display: inline;
    margin-bottom: 6px;
    width: 5px;
}

/* ── 툴팁 팝업 ── */
.tooltiptext-bottom {
    width: 420px;
    font-size: var(--fz-small, 13px);
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border);
    border-radius: 8px;
    box-shadow: var(--nm-dv2-shadow);
    text-align: left;
    position: absolute;
    z-index: 200;
    top: 25%;
    left: 35%;
}
.title_app {
    text-align: center;
    background: var(--nm-dv2-green) !important;
    padding: 10px;
    font-size: var(--fz-step, 17px);
    font-weight: var(--fw-bold, 700);
    color: #000 !important;
    border-radius: 8px 8px 0 0;
}
@media only screen and (max-width: 450px) {
    .tooltiptext-bottom { width: 80%; left: 8%; }
}

/* ── 로딩 오버레이 ── */
#tutorial-loading {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 150;
    display: none;
    background: rgba(24,23,47,0.80);
}

/* ── table-bordered (팝업 내부) ── */
.table-bordered > tbody > tr > td {
    border: 1px solid var(--nm-dv2-border) !important;
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
}

/* ── 파일 아이콘 ── */
.file-icon { display: inline-block; text-align: center; margin: 2px; }
.file-icon a { color: var(--nm-dv2-accent); font-size: 11px; }
.file-icon span { display: block; font-size: 10px; }

/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 11px; }
}
</style>
<div class="big_div">
<!-- ── 페이지 히어로 배너 ── -->


    <div class="big_sub">
        <div class="m_div">
            <?php include "mypage_left_menu.php"; ?>
            <div class="m_body">

                <div class="mrl-wrap" style="flex:1;min-width:0;">
<div class="page-hero page-hero--action" style="margin-bottom:20px;">
    <div class="page-hero-inner">
        <div class="page-hero-content">
            <span class="page-hero-badge">신청관리</span>
            <h1 class="page-hero-title">신청관리 <em>리스트</em></h1>
            <p class="page-hero-desc">이벤트 신청그룹별 고객데이터를 조회·관리합니다.</p>
        </div>
        <div class="page-hero-actions">
            <a class="page-hero__btn page-hero__btn--secondary" href="/mypage_request_list.php">+ 신청 조회</a>
        </div>
    </div>
</div>
<form name="pay_form" action="" method="post" class="my_pay">
                    <input type="hidden" name="page" value="<?= $page ?>" />
                    <input type="hidden" name="page2" value="<?= $page2 ?>" />
                    <div class="a1" style="margin-top:50px; margin-bottom:15px;display:flex">
                        <div class="popup_holder popup_text" style="margin-left:10px;margin-right:10px">고객관리 리스트
                            <div class="popupbox" style="height: 56px;width: 215px;left: 180px;top: -37px;display:none">이벤트 신청그룹창을 통해 신청한 고객데이터를 조회하는 기능입니다.<br><br>
                                <a class="detail_view" style="color: var(--nm-dv2-accent);" href="https://tinyurl.com/bddum95m" target="_blank">[자세히 보기]</a>
                            </div>
                        </div>
                        <?php if ($member_1['ai_status']) { ?>
                            <input type="radio" id="reserv_type" name="reserv_type" value="1" <?php if ($_GET['reserv_type'] != 0) echo "checked" ?>>
                            <label for="ai">AI</label>
                            <input type="radio" id="reserv_type" name="reserv_type" value="0" <?php if ($_GET['reserv_type'] == 0) echo "checked" ?>>
                            <label for="manula">수동</label>
                        <?php   } ?>
                    </div>
                    <div>
                        <div class="p1">
                            <select name="search_key" class="select">
                                <option value="" <?php if ($_REQUEST['search_key'] == "") echo "selected" ?>>전체</option>
                                <option value="name" <?php if ($_REQUEST['search_key'] == "name") echo "selected" ?>>신청자이름</option>
                                <option value="mobile" <?php if ($_REQUEST['search_key'] == "mobile") echo "selected" ?>>신청폰번호</option>
                                <option value="title" <?php if ($_REQUEST['search_key'] == "title") echo "selected" ?>>신청창제목</option>
                                <option value="recv" <?php if ($_REQUEST['search_key'] == "recv") echo "selected" ?>>발송폰번호</option>
                            </select>
                            <input type="text" name="search_text" placeholder="" id="search_text" value="<?= $_REQUEST['search_text'] ?>" />
                            <input type="text" name="sp" placeholder="" id="event_code" value="<?= $_REQUEST['sp'] ?>" readonly style="background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);border-radius:6px;" />
                            <input type="button" value="신청창 조회" class="button " id="searchBtn">
                            <a href="javascript:void(0)" onclick="pay_form.submit()"><img src="images/sub_mypage_11.jpg" /></a>
                            <div style="float:right;">
                                <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                    <input type="button" value="그룹추가" class="button " id="eventAddBtn">
                                    <!--<div class="popupbox" style="height: 75px;width: 280px;bottom: 37px;display:none;">이벤트에 신청한 고객 명단 중에서 특정 고객을 선택해 다른 이벤트에 연결하여 예약문자를 수동으로 연결시켜주는 기능입니다.<br><br><!--Child-->
                                    <!--<a class = "detail_view" href="https://url.kr/NJEhYk" target="_blank">[자세히 보기]</a>
                                    </div>-->
                                </div>
                                <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                    <input type="button" value="신청자추가" class="button" onclick="location.href='mypage_request_edit.php'">
                                    <div class="popupbox" style="height: 65px;width: 170px;bottom: 37px;display:none;">이벤트에 수동으로 고객정보를 입력하여 추가하는 기능입니다.<br><br><!--Child-->
                                        <a class="detail_view" href="https://tinyurl.com/yp8paxze" target="_blank">[자세히 보기]</a>
                                    </div>
                                </div>
                                <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                    <input type="button" value="새디비발송" class="button" onclick="location.href='mypage_oldrequest_list.php'">
                                </div>
                                <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                    <input type="button" value="발송관리" class="button" onclick="location.href='mypage_wsend_list.php'">
                                </div>
                                <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                    <input type="button" value="선택삭제" class="button" id="stepAddBtn" onclick="deleteMultiRow('<?= $_GET['reserv_type'] ?>')">
                                </div>
                            </div>
                        </div>
                        <div>
                            <table class="list_table" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width:2%;"><input type="checkbox" name="allChk" id="allChk" value="<?php echo $row['event_idx']; ?>"></td>
                                    <td style="width:5%;">No</td>
                                    <td style="width:5%;">구분</td>
                                    <td style="width:7%;">신청자<br>[보기]</td>
                                    <td style="width:9%;">신청자<br>폰번호</td>
                                    <td style="width:9%;">신청창제목</td>
                                    <td style="width:10%">발송세트문자<br>회차/발송건수</td>
                                    <td style="width:6%">중단세트문자<br>ON/OFF</td>
                                    <td style="width:8%">발송폰번호</td>
                                    <td style="width:8%">파일</td>
                                    <td style="width:7%;">신청일자<br>시간</td>
                                    <td style="width:7%;">고객세부정보</td>
                                    <td style="width:7%;">수정/삭제</td>
                                </tr>
                                <?php
                                $sql_serch = " m_id ='{$_SESSION['one_member_id']}' ";
                                // [보안패치] search_date 화이트리스트
                                $_sd_allowed = ['reg_date','up_date','send_date'];
                                $_safe_search_date = in_array($_REQUEST['search_date']??'', $_sd_allowed) ? $_REQUEST['search_date'] : '';
                                if ($_safe_search_date) {
                                    if ($_REQUEST['rday1']) {
                                        $start_time = strtotime($_REQUEST['rday1']);
                                        $sql_serch .= " AND unix_timestamp({$_safe_search_date}) >=$start_time ";
                                    }
                                    if ($_REQUEST['rday2']) {
                                        $end_time = strtotime($_REQUEST['rday2']);
                                        $sql_serch .= " AND unix_timestamp({$_safe_search_date}) <= $end_time ";
                                    }
                                }
                                if ($_REQUEST['search_key'] && $_REQUEST['search_text']) {
                                    // [보안패치] search_key 화이트리스트 + search_text 이스케이프
                                    $allowed_sk = ['name', 'mobile', 'recv'];
                                    $safe_sk = in_array($_REQUEST['search_key']??'', $allowed_sk) ? $_REQUEST['search_key'] : '';
                                    $search_text = mysqli_real_escape_string($self_con, $_REQUEST['search_text'] ?? '');
                                    if ($safe_sk === 'name' || $safe_sk === 'mobile') {
                                        $sql_serch .= " AND {$safe_sk} like '%{$search_text}%'";
                                    } else if ($safe_sk === 'recv') {
                                        $sql_serch .= " AND recv like '%{$search_text}%'";
                                    } else if ($_REQUEST['search_key'] == "title") {
                                        $serch_event_sql = "SELECT GROUP_CONCAT(event_idx) AS event_idxs FROM Gn_event WHERE reserv_type = {$_GET['reserv_type']} AND event_title like '%{$_REQUEST['search_text']}%'";
                                        $serch_event_res = mysqli_query($self_con, $serch_event_sql);
                                        $serch_event_row = mysqli_fetch_assoc($serch_event_res);
                                        $event_ids = explode(",", $serch_event_row["event_idxs"]);
                                        $event_ids = implode("','", $event_ids);
                                        $sql_serch .= " AND event_idx in ('{$event_ids}')";
                                    } else if ($_REQUEST['search_key'] == "recv") {
                                        $serch_event_sql = "SELECT GROUP_CONCAT(event_idx) AS event_idxs FROM Gn_event WHERE reserv_type = {$_GET['reserv_type']} AND mobile like '%{$_REQUEST['search_text']}%'";
                                        $serch_event_res = mysqli_query($self_con, $serch_event_sql);
                                        $serch_event_row = mysqli_fetch_assoc($serch_event_res);
                                        $event_ids = explode(",", $serch_event_row["event_idxs"]);
                                        $event_ids = implode("','", $event_ids);
                                        $sql_serch .= " AND event_idx in ('{$event_ids}')";
                                    }
                                }
                                if ($_REQUEST['sp']) {
                                    $sp = $_REQUEST['sp'];
                                    $sql_serch .= " AND sp ='{$sp}'";
                                }

                                if ($_GET['reserv_type'] == 0)
                                    $sql = "SELECT count(request_idx) as cnt FROM Gn_event_request WHERE $sql_serch ";
                                else if ($_GET['reserv_type'] == 1)
                                    $sql = "SELECT count(request_idx) as cnt FROM Gn_aievent_request WHERE $sql_serch ";

                                $result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
                                $row = mysqli_fetch_array($result);
                                $intRowCount = $row['cnt'];
                                if (!$_POST['lno'])
                                    $intPageSize = 20;
                                else
                                    $intPageSize = $_POST['lno'];
                                if ($_POST['page']) {
                                    $page = (int)$_POST['page'];
                                    $sort_no = $intRowCount - ($intPageSize * $page - $intPageSize);
                                } else {
                                    $page = 1;
                                    $sort_no = $intRowCount;
                                }
                                if ($_POST['page2'])
                                    $page2 = (int)$_POST['page2'];
                                else
                                    $page2 = 1;
                                $int = ($page - 1) * $intPageSize;
                                if ($_REQUEST['order_status'])
                                    $order_status = $_REQUEST['order_status'];
                                else
                                    $order_status = "desc";
                                if ($_REQUEST['order_name'])
                                    $order_name = $_REQUEST['order_name'];
                                else
                                    $order_name = "request_idx";
                                $intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
                                if ($intRowCount) {
                                    if ($_GET['reserv_type'] == 1)
                                        $excel_sql = "SELECT * FROM Gn_aievent_request WHERE $sql_serch order by $order_name $order_status";
                                    else if ($_GET['reserv_type'] == 0)
                                        $excel_sql = "SELECT * FROM Gn_event_request WHERE $sql_serch order by $order_name $order_status";
                                    $sql  = $excel_sql . " limit $int,$intPageSize";
                                    $excel_sql = str_replace("'", "`", $excel_sql);
                                    $result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
                                    while ($row = mysqli_fetch_array($result)) {
                                        $sql_event_data = "SELECT * FROM Gn_event WHERE event_idx={$row['event_idx']}";
                                        $res_event_data = mysqli_query($self_con, $sql_event_data);
                                        $row_event_data = mysqli_fetch_array($res_event_data);

                                        if (strpos($row_event_data['event_info'], "other") !== false) {
                                            $event_other_txt = $row['other'];
                                        } else {
                                            $event_other_txt = "";
                                        }
                                ?>
                                        <tr>
                                            <td><input type="checkbox" class="check" name="event_idx" value="<?= $row['request_idx']; ?>" data-name="<?= $row['name'] ?>" data-mobile="<?= $row['mobile'] ?>" data-email="<?= $row['email'] ?>" data-job="<?= $row['job'] ?>" data-event_code="<?= $row['event_code'] ?>" data-counsult_date="<?= $row['counsult_date'] ?>" data-sp="<?= $row['sp'] ?>" data-request_idx="<?= $row['request_idx']; ?>"></td>
                                            <td><?= $sort_no ?></td>
                                            <td><?= $_GET['reserv_type'] == 1 ? "AI" : "수동" ?></td>
                                            <td style="font-size:12px;"><?= $row['name'] ?><br>
                                                <a onclick="window.open('mypage_pop_activity_list.php?request_idx='+'<?= $row['request_idx'] ?>','','top=300,left=300,width=800,height=500,toolbar=no,menubar=no,scrollbars=yes, resizable=yes,location=no, status=no')">[보기]</a>
                                            </td>
                                            <td><?= $row['mobile'] ?></td>
                                            <td>
                                                <?php if ($_GET['reserv_type'] == 0) { ?>
                                                    <a onclick="window.open('/event/event.html?pcode=<?= $row_event_data['pcode'] ?>&sp=<?= $row_event_data['event_name_eng'] ?>','','toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=600');"> <?= $row_event_data['event_title'] ?></a><br><a onclick="window.open('mypage_pop_member_list.php?eventid='+'<?= $row['event_idx'] ?>','','top=300,left=300,width=800,height=500,toolbar=no,menubar=no,scrollbars=yes, resizable=yes,location=no, status=no')">[신청자보기]</a>

                                                <?php   } else { ?>
                                                    <a onclick=''> <?= $row_event_data['event_title'] ?></a><br><a onclick="window.open('mypage_pop_member_list.php?eventid='+'<?= $row['event_idx'] ?>','','top=300,left=300,width=800,height=500,toolbar=no,menubar=no,scrollbars=yes, resizable=yes,location=no, status=no')">[신청자보기]</a>
                                            </td>
                                        <?php   } ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($row_event_data['sms_idx1'] != 0) {
                                                if ($_GET['reserv_type'] == 1)
                                                    $sql = "SELECT reservation_title FROM Gn_aievent_ms_info WHERE sms_idx='{$row_event_data['sms_idx1']}'";
                                                else
                                                    $sql = "SELECT reservation_title FROM Gn_event_sms_info WHERE sms_idx='{$row_event_data['sms_idx1']}'";
                                                $res = mysqli_query($self_con, $sql);
                                                $sms_row = mysqli_fetch_array($res);

                                                if ($_GET['reserv_type'] == 1)
                                                    $sql = "SELECT count(*) FROM Gn_aievent_message WHERE sms_idx='{$row_event_data['sms_idx1']}'";
                                                else
                                                    $sql = "SELECT count(*) FROM Gn_event_sms_step_info WHERE sms_idx='{$row_event_data['sms_idx1']}'";
                                                $res = mysqli_query($self_con, $sql);
                                                $step_row = mysqli_fetch_array($res);
                                                $sql = "SELECT count(*) FROM Gn_MMS WHERE sms_idx='{$row_event_data['sms_idx1']}' AND request_idx='{$row['request_idx']}' AND result=0";
                                                $res = mysqli_query($self_con, $sql);
                                                $send_row = mysqli_fetch_array($res);
                                                echo "<a onclick=\"javascript:window.open('/mypage_reservation_create.php?sms_idx={$row_event_data['sms_idx1']}','','toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=600');\">$sms_row[0]<br><strong>($step_row[0]/$send_row[0])</strong></a>";
                                            }
                                            ?>
                                        </td>
                                        <td><?php
                                            if ($row_event_data['stop_event_idx'] != 0) {
                                                $sql = "SELECT event_title FROM Gn_event WHERE event_idx='{$row_event_data['stop_event_idx']}'";
                                                $res = mysqli_query($self_con, $sql);
                                                $sms_row = mysqli_fetch_array($res);
                                                echo $sms_row[0];
                                            } else {
                                                echo "<strong>OFF</strong>";
                                            }
                                            ?>
                                        </td>
                                        <td><?= $row_event_data['mobile'] ?></td>
                                        <td>
                                            <?php
                                            if ($row['file1']) {
                                                $file_str = explode(".", $row['file1']);
                                                $ext = $ext1 = strtolower(end($file_str));
                                                if ($ext == "json")
                                                    $ext1 = "code";
                                                else if ($ext == "xlsx")
                                                    $ext1 = "excel";
                                                else if ($ext == "txt")
                                                    $ext1 = "alt";
                                                else if ($ext == "csv")
                                                    $ext1 = "csv";
                                                else if ($ext == "pdf")
                                                    $ext1 = "pdf";
                                                else
                                                    $ext1 = "image";
                                            ?>
                                                <div class="file-icon">
                                                    <a href='<?= $row['file1'] ?>' title="<?= $row['file1'] ?>">
                                                        <i class="fas fa-file-<?= $ext1 ?>"></i>
                                                        <span><?= strtoupper($ext) ?></span>
                                                    </a>
                                                </div>
                                            <?php      }
                                            if ($row['file2']) {
                                                $file_str = explode(".", $row['file2']);
                                                $ext = $ext1 = strtolower(end($file_str));
                                                if ($ext == "json")
                                                    $ext1 = "code";
                                                else if ($ext == "xlsx")
                                                    $ext1 = "excel";
                                                else if ($ext == "txt")
                                                    $ext1 = "alt";
                                                else if ($ext == "csv")
                                                    $ext1 = "csv";
                                                else if ($ext == "pdf")
                                                    $ext1 = "pdf";
                                                else
                                                    $ext1 = "image";
                                            ?>
                                                <div class="file-icon">
                                                    <a href='<?= $row['file2'] ?>' title="<?= $row['file2'] ?>">
                                                        <i class="fas fa-file-<?= $ext1 ?>"></i>
                                                        <span><?= strtoupper($ext) ?></span>
                                                    </a>
                                                </div>
                                            <?php      }
                                            if ($row['file3']) {
                                                $file_str = explode(".", $row['file3']);
                                                $ext = $ext1 = strtolower(end($file_str));
                                                if ($ext == "json")
                                                    $ext1 = "code";
                                                else if ($ext == "xlsx")
                                                    $ext1 = "excel";
                                                else if ($ext == "txt")
                                                    $ext1 = "alt";
                                                else if ($ext == "csv")
                                                    $ext1 = "csv";
                                                else if ($ext == "pdf")
                                                    $ext1 = "pdf";
                                                else
                                                    $ext1 = "image";
                                            ?>
                                                <div class="file-icon">
                                                    <a href='<?= $row['file3'] ?>' title="<?= $row['file3'] ?>">
                                                        <i class="fas fa-file-<?= $ext1 ?>"></i>
                                                        <span><?= strtoupper($ext) ?></span>
                                                    </a>
                                                </div>
                                            <?php      }
                                            ?>
                                        </td>
                                        <td><?= $row['regdate'] ?></td>
                                        <td>
                                            <div class="popup_holder popup_text">신청정보
                                                <div class="popupbox" style="height: auto;width: 170px;left: 63px;top: -100px;display:none;">
                                                    [신청정보보기]<br>
                                                    &nbsp;>성별:<?php if ($row['sex'] == "m") echo " 남자";
                                                                else if ($row['sex'] == "f") echo " 여자";
                                                                else echo " "; ?><br>
                                                    &nbsp;>출생년도:<?= $row['birthday']; ?><br>
                                                    &nbsp;>소속/직업:<?= $row['job']; ?><br>
                                                    &nbsp;>이메일:<?= $row['email']; ?><br>
                                                    &nbsp;>거주주소:<?= $row['addr']; ?><br>
                                                    &nbsp;>가입여부:<?= $row['join_yn']; ?><br>
                                                    &nbsp;>기타정보:<?= $row['other']; ?><br>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            if ($_GET['reserv_type'] == 1) { ?>
                                                <a href="mypage_request_edit_ai.php?request_idx=<?php echo $row['request_idx']; ?>">수정</a> /
                                                <a href="javascript:deleteaiRow('<?php echo $row['request_idx']; ?>','<?php echo $row['sp']; ?>')">삭제</a>
                                            <?php
                                            } else {
                                            ?>
                                                <a href="mypage_request_edit.php?request_idx=<?php echo $row['request_idx']; ?>">수정</a> /
                                                <a href="javascript:deleteRow('<?php echo $row['request_idx']; ?>','<?php echo $row['sp']; ?>')">삭제</a>
                                            <?php   } ?>
                                        </td>
                                        </tr>
                                    <?php
                                        $sort_no--;
                                    }
                                    ?>
                                    <tr>
                                        <td colspan="15">
                                            <?php
                                            page_f($page, $page2, $intPageCount, "pay_form");
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="15" style="text-align: right;">
                                            <div class="popup_holder" style="display:inline-block"> <!--Parent-->
                                                <input type="button" value="엑셀 다운받기" class="button" onclick="excel_down('/excel_down/excel_mypage_request_list.php');return false;" style="cursor: pointer">
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="14">
                                            검색된 내용이 없습니다.
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </form>
                <form id="excel_down_form" name="excel_down_form" target="excel_iframe" method="post">
                    <input type="hidden" name="box_text" id="box_text" value="" />
                    <input type="hidden" name="excel_sql" value="<?= $excel_sql ?>" />
                </form>

                <iframe name="excel_iframe" style="display:none;"></iframe>
<span class="tooltiptext-bottom" id="event_other" style="display:none;">
            <p class="title_app">답변 내용<span onclick="cancel()" style="float:right;cursor:pointer;">X</span></p>
            <table class="table table-bordered" style="width: 97%;">
                <tbody>
                    <tr class="hide_spec">
                        <textarea name="set_event_other_req" id="set_event_other_req" style="border:none;width:90%; height:100px;font-size: 12px;padding:20px;" disabled></textarea></td>
                    </tr>
                </tbody>
            </table>
        </span>
        <div id="tutorial-loading"></div>
        <!-- <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/themes/base/jquery-ui.css" rel="stylesheet" />
    <script type="text/javascript" src="https://ajax.aspnetcdn.com/ajax/jquery.ui/1.10.0/jquery-ui.min.js"></script> -->
        <Script>
            function newpop() {
                var win = window.open("mypage_pop_link_list.php", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
            }
            $(function() {
                $('#searchBtn').on("click", function() {
                    newpop();
                });
                $('#searchEventBtn').on("click", function() {
                    newpop();
                });
                $('#searchstepBtn').on("click", function() {
                    var win = window.open("mypage_pop_message_list_for_addstep.php", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
                });
                $("input[name=reserv_type]").on("change", function() {
                    let reserv_type = $(this).val();
                    let params = new URLSearchParams(window.location.search);
                    if (reserv_type) {
                        params.set("reserv_type", reserv_type);
                        window.location.search = params.toString();
                    } else {
                        window.location.search = params.toString();
                    }
                });
            })
            $(document).on("keypress", "#search_text", function(e) {
                if (e.which == 13) {
                    pay_form.submit();
                }
            });

            function newpop() {
                var win = window.open("mypage_pop_link_list.php", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
            }

            function show_txt_detail(txt) {
                $("#set_event_other_req").val(txt);
                $("#event_other").show();
                $("#tutorial-loading").show();
                $('body,html').animate({
                    scrollTop: 200,
                }, 100);
            }

            function cancel() {
                $("#event_other").hide();
                $("#tutorial-loading").hide();
            }
            $(function() {
                $('#popBtn').on("click", function() {
                    newpop()
                });
                $('#allChk').on("change", function() {
                    $('input[name=event_idx]').prop("checked", $(this).is(":checked"));
                    let checkedValues = [];
                    $('input[name=event_idx]:checked').each(function() {
                        checkedValues.push($(this).val());
                    });
                    $("#box_text").val(checkedValues.join(", "));
                })
                $('input[name=event_idx]').on("change", function() {
                    let checkedValues = [];
                    $('input[name=event_idx]:checked').each(function() {
                        checkedValues.push($(this).val());
                    });
                    $("#box_text").val(checkedValues.join(", "));
                })
                $('#eventAddBtn').on("click", function() {
                    var cnt = 0;
                    var event_idx = "";
                    var html = "";
                    $('input[name=event_idx]').each(function() {
                        if ($(this).is(":checked") == true) {
                            cnt++;
                            if (event_idx != "") event_idx += ",";
                            event_idx += $(this).val();
                            html += '<tr>';
                            html += '    <td>';
                            html += '        <input type="hidden" id="request_idx[]" name="request_idx[]" value="' + $(this).data("request_idx") + '" style="width:135px;">';
                            html += '        <input type="text" id="name[]" name="name[]" value="' + $(this).data("name") + '" style="width:135px;">';
                            html += '    </td>';
                            html += '    <td>';
                            html += '        <input type="text" id="mobile[]" name="mobile[]" value="' + $(this).data("mobile") + '" style="width:135px;">';
                            html += '    </td>';
                            html += '    <td>';
                            html += '        <input type="text" id="email[]" name="email[]" value="' + $(this).data("email") + '" style="width:135px;">';
                            html += '    </td>';
                            html += '    <td>';
                            html += '        <input type="text" id="job[]" name="job[]" value="' + $(this).data("job") + '" style="width:135px;">';
                            html += '    </td>';
                            html += '    <td>';
                            html += '        <input type="text" id="sp[]" name="sp[]" value="' + $(this).data("sp") + '" style="width:135px;">';
                            html += '    </td>';
                            html += '</tr>';
                        }
                    });
                    $('#event_receive_info').html(html);
                    if (cnt == 0) {
                        alert('이벤트추가하실 신청자를 선택해주세요.');
                        return;
                    }
                    var phoneno = $(this).siblings().eq(0).find("input").val();
                    $('.ad_layer5').lightbox_me({
                        centered: true,
                        onLoad: function() {}
                    });
                });

                $('#popCloseBtn').on("click", function() {
                    $('.lb_overlay, .ad_layer4').hide();
                });
                $('#popSaveBtn').on("click", function() {
                    if ($('#event_idx').val() == "") {
                        alert('예약문자를 선택해주세요.')
                        return;
                    }
                    if ($('#reservation_date').val() == "") {
                        alert('예약문자를 선택해주세요.')
                        return;
                    }
                    $('#addForm').submit();
                });
                $('#popEventCloseBtn').on("click", function() {
                    $('.lb_overlay, .ad_layer5').hide();
                });
                $('#popstepCloseBtn').on("click", function() {
                    $('.lb_overlay, .6').hide();
                });
                $('#popEventSaveBtn').on("click", function() {
                    if ($('#event_idx').val() == "") {
                        alert('이벤트를 선택해주세요.')
                        return;
                    }
                    if ($('#reservation_date').val() == "") {
                        alert('예약문자를 선택해주세요.')
                        return;
                    }
                    $('#addFormEvent').submit();
                });
                $('#popstepSaveBtn').on("click", function() {
                    if ($('#event_idx').val() == "") {
                        alert('이벤트를 선택해주세요.')
                        return;
                    }
                    if ($('#step_sms_title').val() == "") {
                        alert('퍼널문자를 선택해주세요.')
                        return;
                    }
                    $('#addFormstep').submit();
                });
            })

            function deleteRow(request_id, org_event_code) {
                if (confirm('삭제하시겠습니까?')) {
                    $.ajax({
                        type: "POST",
                        url: "mypage.proc.php",
                        data: {
                            mode: "request_del",
                            request_idx: request_id,
                            org_event_code: org_event_code
                        },
                        success: function(data) {
                            $("#ajax_div").html(data);
                            alert('삭제되었습니다.');
                            refresh_page();
                        }
                    });
                    return false;
                }
            }

            function deleteaiRow(request_id, org_event_code) {
                if (confirm('삭제하시겠습니까?')) {
                    $.ajax({
                        type: "POST",
                        url: "mypage.proc.php",
                        data: {
                            mode: "request_del_ai",
                            request_idx: request_id,
                            org_event_code: org_event_code
                        },
                        success: function(data) {
                            $("#ajax_div").html(data);
                            alert('삭제되었습니다.');
                            refresh_page();
                        }
                    });
                    return false;
                }
            }

            function deleteMultiRow(reservType) {
                var check_array = $(".list_table").children().find(".check");
                var no_array = [];
                var index = 0;
                check_array.each(function() {
                    if ($(this).prop("checked") && $(this).val() > 0)
                        no_array[index++] = $(this).val();
                });

                if (no_array.length == 0) {
                    alert("삭제할 신청창을 선택하세요.");
                    return;
                }
                if (confirm('삭제하시겠습니까?')) {
                    $.ajax({
                        type: "POST",
                        url: "/admin/ajax/delete_func.php",
                        dataType: "json",
                        data: {
                            admin: 0,
                            delete_name: "mypage_request_list",
                            reservType: reservType,
                            id: no_array.toString()
                        },
                        success: function(data) {
                            console.log(data);
                            if (data == 1) {
                                alert('삭제 되었습니다.');
                                refresh_page();
                            }
                        }
                    })
                }
            }

            function removeAll() {
                var no = "";
                if (confirm('모든 페이지 데이타를 모두 삭제합니다.  삭제하시겠어요?')) {
                    $.ajax({
                        type: "POST",
                        url: "/admin/ajax/delete_func.php",
                        data: {
                            admin: 0,
                            delete_name: "mypage_request_list",
                            mem_id: '<?= $_SESSION['one_member_id'] ?>'
                        },
                        success: function() {
                            alert('삭제되었습니다.');
                            refresh_page();
                        },
                        error: function() {
                            alert('삭제 실패');
                        }
                    });
                }
            }

            function showInfo() {
                if ($('#outLayer').css("display") == "none") {
                    $('#outLayer').show();
                } else {
                    $('#outLayer').hide();
                }
            }

            function newMessageEvent() { // test 메시지조회
                // var cnt = 0;
                // $('input[name=event_idx]').each(function() {
                //     if($(this).is(":checked") == true) {
                //         cnt++;
                //     }
                // });
                // if(cnt == 0) {
                //     alert('퍼널문자 추가하실 신청자를 선택해주세요.');
                //     return;
                // }

                // var req_idx_arr = new Array();
                // var i = 0;
                // $("input[name=event_idx]:checked").each(function(){
                //     req_idx_arr[i] = $(this).attr('data-request_idx');
                //     i++;
                // });
                // var win = window.open("../mypage_pop_message_list_for_addstep.php?req_idx="+req_idx_arr.join(","), "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
            }

        </Script>
        <script type="text/javascript" src="jquery.lightbox_me.js"></script>
        <script type="text/javascript" src="/js/mms_send.js"></script>
        <!--script type="text/javascript" src="/plugin/tablednd/js/jquery.tablednd.0.7.min.js"></script-->
        <style>
            /* ── 팝업 레이어 (nm-darkver2) ── */
            .ad_layer4,
            .ad_layer5,
            .ad_layer6 {
                width: 903px;
                max-width: 96vw;
                background: var(--nm-dv2-card, #1e2d50);
                border: 1px solid var(--nm-dv2-border, rgba(100,160,255,0.20));
                border-radius: 12px;
                box-shadow: 0 8px 40px rgba(0,0,0,0.65);
                position: relative;
                box-sizing: border-box;
                padding: 30px 30px 50px 30px;
                display: none;
                color: var(--nm-dv2-text, #f0f4ff);
            }
            .ad_layer4 .pop_title,
            .ad_layer5 .pop_title,
            .ad_layer6 .pop_title {
                font-size: var(--fz-step, 17px);
                font-weight: var(--fw-bold, 700);
                color: var(--nm-dv2-green, #82c836);
                border-bottom: 1px solid var(--nm-dv2-border, rgba(100,160,255,0.20));
                padding-bottom: 10px;
                margin-bottom: 14px;
            }
            .ad_layer4 .info_box,
            .ad_layer5 .info_box,
            .ad_layer6 .info_box {
                background: var(--nm-dv2-card2, #16305c);
                border: 1px solid var(--nm-dv2-border, rgba(100,160,255,0.20));
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 12px;
            }
            .ad_layer4 .info_box_table th,
            .ad_layer5 .info_box_table th,
            .ad_layer6 .info_box_table th {
                background: var(--nm-dv2-card2, #16305c);
                color: var(--nm-dv2-muted, rgba(200,215,255,0.60));
                border: 1px solid var(--nm-dv2-border, rgba(100,160,255,0.20));
                font-size: var(--fz-label, 14px);
                font-weight: var(--fw-semi, 600);
                padding: 8px;
            }
            .ad_layer4 .info_box_table td,
            .ad_layer5 .info_box_table td,
            .ad_layer6 .info_box_table td {
                background: var(--nm-dv2-card, #1e2d50);
                color: var(--nm-dv2-text, #f0f4ff);
                border: 1px solid var(--nm-dv2-border, rgba(100,160,255,0.20));
                padding: 8px;
            }
            .ad_layer4 .ok_box,
            .ad_layer5 .ok_box,
            .ad_layer6 .ok_box {
                text-align: right;
                margin-top: 16px;
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            .layer_close img { filter: invert(1) opacity(0.7); cursor: pointer; }
            .ui-widget-content {
                border: none !important;
                background: var(--nm-dv2-card, #1e2d50) !important;
                color: var(--nm-dv2-text, #f0f4ff) !important;
            }
        </style>
        <div class="ad_layer5">
            <div class="layer_in">
                <span class="layer_close close"><img src="/images/close_button_05.jpg"></span>
                <form method="post" name="addFormEvent" id="addFormEvent" action="mypage.proc.php" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="request_event_add">
                    <input type="hidden" name="m_id" value="<?php echo $_SESSION['one_member_id']; ?>">
                    <div class="pop_title">
                        신규 신청창 수동추가
                    </div>
                    <div class="info_box">
                        <table class="info_box_table" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                    <th class="w200">신청내용</td>
                                    <td style="height:35px;text-align:left;">
                                        <input type="hidden" id="event_idx_event" name="event_idx_" value="" style="width:95px;">
                                        <input type="text" id="event_name_eng_event" name="sp_" value="" style="width:95px;">
                                        <input type="hidden" id="event_pcode_event" name="event_pcode_" value="" style="width:95px;">
                                        <input type="button" value="고객신청그룹 조회" class="button " id="searchEventBtn">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pop_title">
                        신규신청자 정보
                    </div>
                    <div class="info_box">
                        <table class="info_box_table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="w200">신청자이름</th>
                                    <th class="w200">신청자휴대폰</th>
                                    <th class="w200">이메일</th>
                                    <th class="w200">직업</th>
                                    <th class="w200">신청채널</th>
                                </tr>
                            </thead>
                            <tbody id="event_receive_info">
                                <tr>
                                    <td>
                                        <input type="text" id="name[]" name="name[]" value="" style="width:135px;">
                                    </td>
                                    <td>
                                        <input type="text" id="mobile[]" name="mobile[]" value="" style="width:135px;">
                                    </td>
                                    <td>
                                        <input type="text" id="email[]" name="email[]" value="" style="width:135px;">
                                    </td>
                                    <td>
                                        <input type="text" id="job[]" name="job[]" value="" style="width:135px;">
                                    </td>
                                    <td>
                                        <input type="text" id="sp[]" name="sp[]" value="" style="width:135px;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="ok_box">
                        <input type="button" value="취소" class="button " id="popEventCloseBtn">
                        <input type="button" value="저장" class="button" id="popEventSaveBtn">
                    </div>
                </form>
            </div>
        </div>

                </div><!-- .mrl-wrap -->

            </div><!-- .m_body -->
        </div><!-- .m_div -->
    </div><!-- .big_sub -->
</div><!-- .big_div -->
include_once "_foot.php";
?>