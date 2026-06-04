<?php  
$path = "./";
include_once $_SERVER["DOCUMENT_ROOT"] . "/_head_v01.php";
if (empty($_SESSION['one_member_id']) && empty($_SESSION['iam_member_id'])) {
    echo "<script>location.replace('/ma.php');</script>";
    exit;
}
$send_ids = $member_1['step_send_ids'];
if ($send_ids != "") {
	$cnt = explode(",", $send_ids);
	$send_ids_cnt = $cnt = count($cnt);
}
// AI 버튼 숨김: 수동 고정
$_GET['reserv_type'] = 0;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 퍼널관리 리스트
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
input[type=text],
textarea {
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
    transition: background .18s, border-color .18s, color .18s;
}
.button:hover,
input[type=button].button:hover {
    background: var(--nm-dv2-green) !important;
    color: #000 !important;
    border-color: var(--nm-dv2-green) !important;
}

/* ── 라벨 ── */
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

/* ── 공지 박스 (다크모드) ── */
#funnel_list_notice_phone {
    background: #2a2200 !important;
    color: #ffd97a !important;
    border: 1px solid #5a4000;
}
#funnel_list_notice_webmms {
    background: #0a2010 !important;
    color: #7de8a0 !important;
    border: 1px solid #1b5e20;
}
#funnel_list_notice_webmms a {
    color: #4dff80 !important;
}

/* ── 발송채널 라디오 텍스트 ── */
#funnel_ch_wrap span {
    color: var(--nm-dv2-muted) !important;
}

/* ── 팝업 버튼 영역 ── */
.button_app a.btn {
    color: #fff !important;
}

/* ── 서비스콘텐츠 팝업 ── */
#service_contents_popup .modal-body {
    color: var(--nm-dv2-text);
}

/* ── 검색 이미지 다크모드 보정 ── */
.p1 a img {
    filter: brightness(0) invert(1);
    width: 60px;
    height: 30px;
    vertical-align: middle;
    border-radius: 4px;
}

/* ── 서비스콘텐츠 팝업 버튼 강조 ── */
#service_contents_popup .btn.login_signup {
    display: inline-block;
    text-align: center;
    font-weight: var(--fw-bold, 700);
    font-size: var(--fz-label, 14px);
}

/* ── step_mall_set 팝업 ── */
#step_mall_set table.table-bordered {
    border-color: var(--nm-dv2-border) !important;
}
#step_mall_set tbody td {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
    padding: 7px 8px;
    font-size: var(--fz-label, 14px);
}
#step_mall_set .bold {
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
}
#step_mall_set input[type=text],
#step_mall_set textarea,
#step_mall_set input[type=file] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
}
#step_mall_set textarea#iam_mall_desc {
    border: 1px solid var(--nm-dv2-border) !important;
}
#step_mall_set .input-wrap {
    color: var(--nm-dv2-muted);
}
#step_mall_set input[type=radio] {
    margin-right: 4px;
}
#step_mall_set input[type=checkbox] {
    accent-color: var(--nm-dv2-green);
}

/* ── 팝업 공통 버튼 영역 다크모드 ── */
.button_app {
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 14px 0;
}
.button_app a.btn {
    color: #fff !important;
    font-weight: var(--fw-semi, 600);
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-size: var(--fz-label, 14px);
    transition: opacity .18s;
}
.button_app a.btn:hover {
    opacity: 0.85;
}

/* ── sort-by 화살표 ── */
a.sort-by:before { border-bottom-color: var(--nm-dv2-muted) !important; }
a.sort-by:after  { border-top-color: var(--nm-dv2-muted) !important; }

/* ── switch 토글 ── */
.switch .slider {
    background-color: var(--nm-dv2-card2);
}
.switch input:checked + .slider {
    background-color: var(--nm-dv2-green);
}

/* ── service_contents 팝업 ── */
#service_contents_popup .modal-body {
    color: var(--nm-dv2-text);
}
#service_contents_popup .btn.login_signup {
    display: inline-block;
    text-align: center;
    font-weight: var(--fw-bold, 700);
    font-size: var(--fz-label, 14px);
    border-radius: 6px;
}

/* ── .bold 기본 다크모드 컬러 ── */
.bold {
    color: var(--nm-dv2-text) !important;
}

/* ── page-hero 버튼 텍스트 흰색 ── */
.page-hero__btn--secondary {
    color: #fff !important;
}

/* ── 발송채널 토글 버튼 (버튼 형태) ── */
.funnel-ch-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 18px;
    border-radius: 22px;
    font-size: 13px;
    font-weight: var(--fw-semi, 600);
    cursor: pointer;
    transition: all .22s;
    border: 1px solid var(--nm-dv2-border);
    background: var(--nm-dv2-input-bg);
    color: var(--nm-dv2-muted);
    user-select: none;
    white-space: nowrap;
    text-decoration: none;
}
.funnel-ch-btn i { margin-right: 4px; }
.funnel-ch-btn.active {
    background: var(--nm-dv2-accent);
    color: #fff !important;
    border-color: var(--nm-dv2-accent);
    box-shadow: 0 0 12px rgba(91,155,255,0.30);
}
.funnel-ch-btn:hover {
    background: var(--nm-dv2-card2);
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-accent);
}
.funnel-ch-btn.active:hover {
    background: var(--nm-dv2-accent);
    color: #fff !important;
}

/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 11px; }
    .list_table td, .list_table th { padding: 4px 3px; }
    .tooltiptext-bottom {
        width: 90% !important;
        left: 5% !important;
        max-width: 95vw;
    }
    #step_mall_set {
        width: 95% !important;
        left: 2.5% !important;
    }
    .button_app a.btn {
        width: auto !important;
        padding: 8px 16px !important;
    }
    .a1 { flex-direction: column !important; align-items: stretch !important; }
    #funnel_ch_wrap { margin-left: 0 !important; justify-content: flex-start !important; }
}
</style>
<div class="big_div">
	<div class="big_sub">
		<div class="m_div">
			<?php   include "mypage_left_menu.php"; ?>
			<div class="mrl-wrap" style="flex:1;min-width:0;">
<!-- ── 페이지 히어로 배너 ── -->
<div class="page-hero page-hero--action" style="margin-bottom:20px;">
    <div class="page-hero-inner">
        <div class="page-hero-content">
            <span class="page-hero-badge">퍼널관리</span>
            <h1 class="page-hero-title">퍼널관리 <em>리스트</em></h1>
            <p class="page-hero-desc">AI·수동 퍼널 메시지 설계를 등록·관리합니다.</p>
        </div>
        <div class="page-hero-actions">
            <a class="page-hero__btn page-hero__btn--secondary" href="/mypage_reservation_create.php?reserv_type=0">+ 새 퍼널 등록</a>
        </div>
    </div>
</div>

				<div class="m_body">
				<form name="pay_form" action="" method="post" class="my_pay">
					<input type="hidden" name="page" value="<?= $page ?>" />
					<input type="hidden" name="page2" value="<?= $page2 ?>" />
					<div class="a1" style="margin-top:50px; margin-bottom:15px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;">
						<!-- 좌: 타이틀 -->
						<div class="popup_holder popup_text" style="margin:0px">AI퍼널메시지 설계리스트
							<div class="popupbox" style="height: 75px;width: 245px;left: 200px;top: -37px;">AI퍼널메시지 설계를 등록하면 리스트로 보는 기능입니다.<br><br>
								<a class="detail_view" style="color: var(--nm-dv2-accent);" href="https://tinyurl.com/2p8vsjm2" target="_blank">[자세히 보기]</a>
							</div>
						</div>
						<!-- AI 버튼 숨김: 수동만 표시 고정 -->
						<div style="font-size:13px;margin-left:20px;display:none;">
							<input type="radio" id="reserv_type" name="reserv_type" value="0" checked>
						</div>
							<!-- 우: 발송채널 토글 버튼 -->
							<div id="funnel_ch_wrap" style="margin-left:auto;display:flex;align-items:center;gap:10px;font-size:13px;">
								<span style="color: var(--nm-dv2-muted);font-weight:600;">발송채널:</span>
								<button type="button" class="funnel-ch-btn active" id="funnel_ch_btn_phone" onclick="onFunnelListChannelChange('phone')"><i class="fas fa-mobile-alt"></i> 폰문자</button>
								<button type="button" class="funnel-ch-btn" id="funnel_ch_btn_webmms" onclick="onFunnelListChannelChange('webmms')"><i class="fas fa-globe"></i> 웹문자</button>
							</div>
							<!-- 알림: width:100%로 다음 줄, 우측 정렬 (position:relative로 레이아웃 간섭 방지) -->
							<div style="width:100%;display:flex;justify-content:flex-end;margin-top:5px;position:relative;">
								<div id="funnel_list_notice_phone" style="display:block;background:#2a2200;border-radius:7px;padding:6px 28px 6px 10px;font-size:11px;color:#ffd97a;position:relative;max-width:420px;">
									<button onclick="this.parentNode.style.display='none'" style="position:absolute;right:5px;top:3px;background:none;border:none;cursor:pointer;font-size:12px;color:#ffd97a;">&#x2715;</button>
									&#9888;&#65039; 폰문자로 퍼널 메시지 수신자가 월 1,500건이 넘을 경우 통신사에서 고액 문자비가 부과되므로 꼭 웹문자로 발송하셔야 합니다.
								</div>
								<div id="funnel_list_notice_webmms" style="display:none;background:#0a2010;border-radius:7px;padding:6px 28px 6px 10px;font-size:11px;color:#7de8a0;position:relative;max-width:420px;">
									<button onclick="this.parentNode.style.display='none'" style="position:absolute;right:5px;top:3px;background:none;border:none;cursor:pointer;font-size:12px;color:#7de8a0;">&#x2715;</button>
									&#128172; 웹문자로 퍼널을 발송할 경우 사전에 충전하셔야 합니다. <a href="/iam/pay.php?panel=webmms" style="color:#4dff80;font-weight:700;">충전하기 &#9889;</a>
								</div>
							</div>
					</div>
					<div>
						<div class="p1">
							<select name="search_key" class="select">
								<option value="">전체</option>
							</select>
							<input type="text" name="search_text" placeholder="" id="search_text" value="<?= $_REQUEST['search_text'] ?>" />
							<a href="javascript:void(0)" onclick="pay_form.submit()"><img src="images/sub_mypage_11.jpg" /></a>
							<div style="text-align:right;margin-top:0px;float:right;display: flex;">
								<?php   if ($_GET['reserv_type'] != 1) { ?>
									<div class="popup_holder"> <!--Parent-->
										<input type="button" value="메시지세트판매" class="button" onclick="sell_step()">
										<input type="button" value="메시지세트 전송" class="button" onclick="send_step()">
										<input type="button" value="메시지복제하기" class="button" onclick="get_steplist()">
										<input type="hidden" name="send_sms_idx" id="send_sms_idx" value="">
									</div>
									<div class="popup_holder"> <!--Parent-->
										<input type="button" value="메시지세트등록" class="button" onclick="location='mypage_reservation_create.php?reserv_type=0'">
										<div class="popupbox" style="height: 52px;width: 196px;bottom: 37px;">신청자가 정보를 입력하고 제출하면 AI퍼널메시지 설계에 따라 메시지가 자동으로 생성되게 하는 기능입니다.<br><!--Child-->
											<a class="detail_view" href="https://tinyurl.com/uwcybb6p" target="_blank">[자세히 보기]</a>
										</div>
									</div>
									<div class="popup_holder"> <!--Parent-->
										<input type="button" value="선택삭제" class="button" onclick="deleteMultiRow()">
									</div>
								<?php   } ?>
							</div>
						</div>
						<div style="overflow-x:auto;">
							<table class="list_table" style="width:100%;border:none" cellspacing="0" cellpadding="0">
								<?php   if ($_GET['reserv_type'] == 1) { ?>
									<tr>
										<td style="width:2%;"><input type="checkbox" name="allChk" id="allChk" value="<?= $row['event_idx']; ?>"></td>
										<td style="width:6%;">No</td>
										<td style="width:6%;">구분</td>
										<td style="width:10%;">메시지세트제목</td>
										<td style="width:10%;">메시지세트설명</td>
										<td style="width:6%;">생성회차</td>
										<td style="width:6%;">발송주기</td>
										<td style="width:6%;">발송시간</td>
										<!-- <td style="width:15%">프롬프트</td> -->
										<!--<td style="width:3%">파일1</td>
										<td style="width:3%">파일2</td>
										<td style="width:3%">파일3</td>-->
										<td style="width:6%;">신청건수</td>
										<td style="width:9%;">등록일</td>
										<td style="width:9%;">관리</td>
									</tr>
								<?php   } else { ?>
									<tr>
										<td style="width:2%;"><input type="checkbox" name="allChk" id="allChk" value="<?= $row['event_idx']; ?>"></td>
										<td style="width:6%;">No</td>
										<td style="width:6%;">구분</td>
										<td style="width:15%;">메시지세트제목</td>
										<td style="width:15%;">메시지세트설명</td>
										<td style="width:8%">단계</td>
										<td style="width:10%;">발신횟수/건수</td>
										<td style="width:9%;">등록일</td>
										<td style="width:9%;">관리</td>
									</tr>
									<?php  
								}
								if ($_GET['reserv_type'] == 1) {
									$sql_serch = " customer_id ='{$_SESSION['one_member_id']}' ";
								} else {
									$sql_serch = " m_id ='{$_SESSION['one_member_id']}' ";
								}

								//$sql_serch = "";
								// [보안패치] search_date 화이트리스트
								$_sd_allowed = ['reg_date','regdate','reservation_date','up_date'];
								$_safe_search_date = in_array($_REQUEST['search_date']??'', $_sd_allowed) ? $_REQUEST['search_date'] : '';
								if ($_safe_search_date) {
									if ($_REQUEST['rday1']) {
										$start_time = strtotime($_REQUEST['rday1']);
										$sql_serch .= " unix_timestamp({$_safe_search_date}) >=$start_time ";
									}
									if ($_REQUEST['rday2']) {
										$end_time = strtotime($_REQUEST['rday2']);
										$sql_serch .= " unix_timestamp({$_safe_search_date}) <= $end_time ";
									}
								}

								if ($_REQUEST['search_text']) {
									$search_text = mysqli_real_escape_string($self_con, $_REQUEST['search_text'] ?? ''); // [보안패치]
									if ($sql_serch != "") {
										$sql_serch .= " AND ( reservation_title like '%$search_text%' or reservation_desc like '%$search_text%')";
									} else {
										$sql_serch .= " (reservation_title like '%$search_text%' or reservation_desc like '%$search_text%')";
									}
								}

								if ($_GET['reserv_type'] == 1) {
									if ($sql_serch != "") {
										$sql = "SELECT count(sms_idx) as cnt FROM Gn_aievent_ms_info WHERE $sql_serch ";
									} else {
										$sql = "SELECT count(sms_idx) as cnt FROM Gn_aievent_ms_info";
									}
								} else {
									if ($sql_serch != "") {
										$sql = "SELECT count(sms_idx) as cnt FROM Gn_event_sms_info WHERE $sql_serch ";
									} else {
										$sql = "SELECT count(sms_idx) as cnt FROM Gn_event_sms_info";
									}
								}

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
								// [보안패치] order_name 화이트리스트
								$_on_allowed = ['regdate','reservation_date','name','mobile','result'];
								$order_name = in_array($_REQUEST['order_name']??'', $_on_allowed) ? $_REQUEST['order_name'] : 'regdate';
								$intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
								if ($intRowCount) {
									if ($_GET['reserv_type'] == 1) {
										if ($sql_serch != "") {
											$sql = "SELECT * FROM Gn_aievent_ms_info WHERE $sql_serch order by $order_name $order_status limit $int,$intPageSize";
										} else {
											$sql = "SELECT * FROM Gn_aievent_ms_info order by $order_name $order_status limit $int,$intPageSize";
										}
									} else {
										if ($sql_serch != "") {
											$sql = "SELECT * FROM Gn_event_sms_info WHERE $sql_serch order by $order_name $order_status limit $int,$intPageSize";
										} else {
											$sql = "SELECT * FROM Gn_event_sms_info order by $order_name $order_status limit $int,$intPageSize";
										}
									}
									$result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
									$c = 0;
									while ($row = mysqli_fetch_array($result)) {
										if (isset($_GET['reserv_type']))
											$row['reserv_type'] = $_GET['reserv_type'];
										else
											$row['reserv_type'] = 0;
										if ($row['reserv_type'] == 1) {
											$sql = "SELECT count(*) as cnt FROM Gn_aievent_message WHERE sms_idx='{$row['sms_idx']}'";
											$sql_m = "SELECT * FROM Gn_aievent_request WHERE sms_idx='{$row['sms_idx']}'";
											$mresult = mysqli_query($self_con, $sql_m) or die(mysqli_error($self_con));
											$mrow = mysqli_num_rows($mresult);
										} else
											$sql = "SELECT count(*) as cnt FROM Gn_event_sms_step_info WHERE sms_idx='{$row['sms_idx']}'";
										$sresult = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
										$srow = mysqli_fetch_array($sresult);
									?>
										<tr>
											<td>
												<?php   if ($row['sendable'] == 1) { ?>
													<input type="checkbox" class="check" name="sms_idx" value="<?= $row['sms_idx']; ?>" data-event-idx="<?= $row['event_idx'] ?>" data-name="<?= $row['m_id'] ?>" data-mobile="<?= $row['mobile'] ?>" data-event_name_eng="<?= $row['event_name_eng'] ?>" data-title="<?= $row['reservation_title'] ?>" data-desc="<?= $row['reservation_desc'] ?>">
											</td>
										<?php   } ?>
										<td><?= $sort_no ?></td>
										<td style="font-size:12px;"><?= $row['reserv_type'] == "1" ? 'AI' : '수동'; ?></td>
										<td style="font-size:12px;"><?= $row['reservation_title'] ?></td>
										<td><?= $row['reservation_desc'] ?></td>
										<td><?= $row['reserv_type'] == 1 ? number_format($row['ai_step']) : number_format($srow['cnt']) ?>회</td>
										<td><?= $row['reserv_type'] == 1 ? number_format($row['ai_day']) . '일' : number_format($cnt) . ' / ' . number_format($cnt) ?></td>
										<?php   if ($row['reserv_type'] == 1) { ?>
											<td><?= $row['ai_hour'] ?></td>
											<!-- <td>
												<a href="javascript:void(0)" ><?= str_substr($row['ai_prompt'], 0, 60, 'utf-8') ?></a><input type="hidden" name="show_content" value="<?= $row['ai_prompt'] ?>" />
											</td> -->
										<?php  
											/*$file_ext1 = explode('.', $row['ai_file1']);
											$file_ext1 = end($file_ext1);
											$file_ext2 = explode('.', $row['ai_file2']);
											$file_ext2 = end($file_ext2);
											$file_ext3 = explode('.', $row['ai_file3']);
											$file_ext3 = end($file_ext3);

											?>
											<td><?php   if ($file_ext1 != "") { ?>
													<img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext1; ?>" /><a href=<?= $row['ai_file1'] ?>><?= $file_ext1 ?></a>
												<?php   } ?>
											</td>
											<td><?php   if ($file_ext2 != "") { ?>
													<img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext2; ?>" /><a href=<?= $row['ai_file2'] ?>><?= $file_ext2 ?></a>
												<?php   } ?>
											</td>

											<td><?php   if ($file_ext3 != "") { ?>
													<img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext3; ?>" /><a href=<?= $row['ai_file3'] ?>><?= $file_ext3 ?></a>
												<?php   } ?>
											</td>
											<?php   */ } ?>
										<?php   if ($row['reserv_type'] == 1) { ?>
											<td><?= $mrow ?></td>
										<?php   } ?>
										<td><?= $row['regdate'] ?></td>
										<td>
											<?php   if ($row['reserv_type'] == 1) { ?>
												<a href='mypage_reservation_create.php?sms_idx=<?= $row['sms_idx']; ?>&reserv_type=<?= $row['reserv_type']; ?>'>리스트</a>
												<?php  
												if ($row['permission_manage'] == 1) {
												?>
													<!-- <a href='mypage_reservation_edit_ai.php?sms_idx=<?= $row['sms_idx']; ?>&reserv_type=1'>수정</a> -->
												<?php  
												}
												?>
											<?php   } else { ?>
												<a href='mypage_reservation_create.php?sms_idx=<?= $row['sms_idx']; ?>&reserv_type=<?= $row['reserv_type']; ?>'>수정</a>/<a href="javascript:;;" onclick="deleteRow('<?= $row['sms_idx']; ?>','<?= $row['reserv_type']; ?>')">삭제</a>
											<?php   } ?>
										</td>
										</tr>
									<?php  
										$sort_no--;
										$c++;
									}
									?>
									<tr>
										<td colspan="10">
											<?php  
											page_f($page, $page2, $intPageCount, "pay_form");
											?>
										</td>
									</tr>
								<?php  
								} else {
								?>
									<tr>
										<td colspan="10">
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
			</div>
<span class="tooltiptext-bottom" id="tooltiptext_card_edit" style="display:none;">
	<p class="title_app">퍼널메시지세트 전송하기</p>
	<table class="table table-bordered" style="width: 97%;">
		<tbody>
			<tr class="hide_spec">
				<td class="bold" id="remain_count" data-num="" style="width:70px;padding:5px;">전송하기<br>
					<textarea name="step_send_id_count" id="step_send_id_count" style="width:90%;height:50px;background:var(--nm-dv2-input-bg);color:var(--nm-dv2-red);font-size:12px;border:1px solid var(--nm-dv2-border);border-radius:6px" readonly="" data-num="0" placeholder="0건"></textarea>
				</td>
				<td colspan="2" style="padding:5px;">
					<div>
						<textarea name="step_send_id" id="step_send_id" style="background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);width:97%; height:100px;" data-num="0" placeholder="전송할 아이디를 입력하세요.<컴마로 구분>"></textarea>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="button_app">
		<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-card2);border-radius: 6px;padding: 7px 5px;color: var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);">취소하기</a>
		<a href="javascript:send_step_sms()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 6px;color: #fff;padding: 7px 5px;font-weight: var(--fw-bold,700);">전송하기</a>
	</div>
</span>

<span class="tooltiptext-bottom" id="step_mall_set" style="display:none;width:600px;">
	<p class="title_app">메시지세트 등록하기</p>
	<table class="table table-bordered" style="width: 97%;padding: 17px;border: 1px solid var(--nm-dv2-border);">
		<input type="hidden" id="iam_mall_type" value="5">
		<!--input type="hidden" id="iam_mall_link" value="<?= $row_card[0] ?>"-->
		<input type="hidden" id="iam_mall_method" value="creat">
		<input type="hidden" id="iam_mall_idx" value="">
		<tbody>
			<colgroup>
				<col width="20%">
				<col width="80%">
			</colgroup>
			<tr class="bold">
				<td>상품제목</td>
				<td>
					<input type="text" id="iam_mall_title" style="width: 98%;">
				</td>
			</tr>
			<tr class="bold">
				<td>상품부제목</td>
				<td>
					<input type="text" id="iam_mall_sub_title" style="width: 98%;">
				</td>
			</tr>
			<tr class="bold">
				<td>상품썸네일</td>
				<td>
					<div class="input-wrap">
						<input type="radio" name="iam_mall_img_type" value="f" checked>파일가져오기
						<input type="radio" name="iam_mall_img_type" value="u" id="main_type1">이미지주소
					</div>
					<div class="input-wrap" style="margin-top:10px">
						<input type="file" id="iam_mall_img" style="width: 98%;height: 24px;" accept=".jpg,.jpeg,.png,.gif">
						<input type="text" id="iam_mall_img_link" style="height: 24px;width:100%;display:none" placeholder="예시 https://www.abcdef.jpg (png/gif)">
					</div>
					<img id="iam_mall_img_preview" style="width:80%">
				</td>
			</tr>
			<tr class="bold">
				<td>상세설명</td>
				<td>
					<textarea name="iam_mall_desc" placeholder="<?= $MENU['IAM_CONTENTS']['CONTS14']; ?>"
						id="iam_mall_desc" style="border:1px solid var(--nm-dv2-border);min-height:175px;overflow:auto;width:98%;resize: vertical;background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);border-radius:6px;padding:8px;">1. 메시지 세트 제목
								2. 메시지 세트 예약건수
								3. 메시지 세트 적용 대상
								4. 메시지 세트 적용 목표
								5. 메시지 세트 적용 효과
								6. 메시지 세트 홍보 전략</textarea>
				</td>
			</tr>
			<tr class="bold">
				<td>검색키워드</td>
				<td>
					<input type="text" id="iam_mall_keyword" style="height: 24px;width:98%;">
				</td>
			</tr>
			<tr class="bold">
				<td>상품정가</td>
				<td>
					<input type="text" id="iam_mall_price" style="width: 98%;height: 24px;" placeholder="<?= $MENU['IAM_CONTENTS']['CONTS12']; ?>">
				</td>
			</tr>
			<tr class="bold">
				<td>판매가격</td>
				<td>
					<input type="text" id="iam_mall_sell_price" style="width: 98%;height: 24px;" placeholder="<?= $MENU['IAM_CONTENTS']['CONTS13']; ?>">
				</td>
			</tr>
			<tr class="bold">
				<td>노출상태</td>
				<td>
					<input type="checkbox" id="iam_mall_display" checked>&nbsp;&nbsp;&nbsp;노출</input>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="button_app">
		<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-card2);border-radius: 6px;padding: 7px 40px;color: var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);">취소</a>
		<a href="javascript:create_iam_mall(0)" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 6px;color: #fff;padding: 7px 40px;font-weight: var(--fw-bold,700);">등록</a>
	</div>
</span>

<span class="tooltiptext-bottom" id="service_contents_popup" style="display:none;">
	<p class="title_app">판매자 신청하기</p>
	<div class="modal-body">
		<div>
			<div class="login_text" style="padding:15px;color:var(--nm-dv2-text);">
				서비스콘텐츠에서는 본사의 결제시스템으로 판매를 하므로 결제, 판매, 홍보에 대한 수수료 납부가 필요합니다.<br>
				그래서 아래에 판매자신청 버튼을 클릭하셔야 서비스콘텐츠를 사용할수 있습니다.
			</div>
		</div>
	</div>
	<div style="width:100%;text-align: center;padding: 10px 0px;">
		<a href="javascript:hide_service_contents_popup()" class="btn login_signup" style="width: 90%;background-color: var(--nm-dv2-red);color: white;padding: 3px 15px;" id="service_contents_popup_ok">확인</a>
	</div>
</span>

<div id="tutorial-loading"></div>
				</div><!-- .mrl-wrap -->

		</div>
	</div>
</div>
<Script>
$(function() {
	$('#allChk').on("change", function() {
		$('input[name=sms_idx]').prop("checked", $(this).is(":checked"));
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
	/*$("#reserv_type1").on("change", function() {
		let reserv_type = $(this).val();
		let params = new URLSearchParams(window.location.search);
		if (reserv_type) {
			params.set("reserv_type", reserv_type);
			window.location.search = params.toString();
		} else {
			window.location.search = params.toString();
		}
	});*/

	function deleteRow(sms_idx, reserv_type) {
		if (confirm('삭제하시겠습니까?')) {
			$.ajax({
				type: "POST",
				url: "mypage.proc.php",
				data: {
					mode: "reservation_del",
					reserv_type: reserv_type,
					sms_idx: sms_idx
				},
				success: function(data) {
					//$("#ajax_div").html(data);
					alert('삭제되었습니다.');
					refresh_page();
				}
			});
			return false;
		}
	}

	function deleteMultiRow() {
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
					delete_name: <?= $_GET['reserv_type'] == 1 ? "'ai_mypage_reservation_list'" : "'mypage_reservation_list'" ?>,
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

	function send_step() {
		var idx_arr = new Array();
		$('input[name=sms_idx]').each(function() {
			if ($(this).is(":checked") == true) {
				idx_arr.push($(this).val());
			}
		});
		$("#send_sms_idx").val(idx_arr.join(","));

		if (idx_arr.length == 0) {
			alert("전송할 메시지를 선택하세요.");
			return;
		}

		<?php   if ($send_ids != "") { ?>
			$("#step_send_id").val('<?= $send_ids ?>');
			$("#step_send_id_count").val(<?= $send_ids_cnt ?> + "건");
		<?php   } ?>
		$("#tooltiptext_card_edit").show();
		$("#tutorial-loading").show();
	}

	function cancel_set() {
		$("#tooltiptext_card_edit").hide();
		$("#step_mall_set").hide();
		$("#tutorial-loading").hide();
	}

	$("#step_send_id").keyup(function() {
		point = $(this).val();
		var arr = point.split(",");
		cnt = arr.length;
		if (point.indexOf(",") == -1 && point == "") {
			cnt = 0;
		}
		$("#step_send_id_count").val(cnt + "건");
		$('#step_send_id_count').data('num', cnt);
	});

	function send_step_sms() {
		var send_ids = $("#step_send_id").val();
		var sms_idx = $("#send_sms_idx").val();

		if (send_ids == "") {
			alert("아이디를 입력하세요.");
			return;
		}

		$.ajax({
			type: "POST",
			url: "/ajax/step_sms_send.php",
			dataType: "json",
			data: {
				send_ids: send_ids,
				sms_idx: sms_idx,
				type: "step"
			},
			success: function(data) {
				console.log(data);
				alert("전송되었습니다.");
				refresh_page();
			}
		});
	}

	function get_steplist() {
		var win = window.open("../mypage_pop_message_list_for_copylist.php?mode=creat", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
	}

	function sell_step() {
		var idx_arr = new Array();
		$('input[name=sms_idx]').each(function() {
			if ($(this).is(":checked") == true) {
				idx_arr.push($(this).val());
			}
		});
		$("#send_sms_idx").val(idx_arr.join(","));

		if (idx_arr.length == 0) {
			alert("판매할 메시지를 선택하세요.");
			return;
		}
		$("#step_mall_set").show();
		$("#tutorial-loading").show();
	}

	function create_iam_mall(status) {
		var idx_arr = new Array();
		$('input[name=sms_idx]').each(function() {
			if ($(this).is(":checked") == true) {
				idx_arr.push($(this).val());
			}
		});
		$("#send_sms_idx").val(idx_arr.join(","));

		if (idx_arr.length == 0) {
			alert("판매할 메시지를 선택하세요.");
			return;
		}

		if ($("#iam_mall_sell_price").val().trim() == "") {
			alert("판매가를 입력하세요.");
			$("#iam_mall_sell_price").focus();
			return;
		}
		if ('<?= $data['service_type'] ?>' == '3' || status == 1) {
			var formData = new FormData();
			formData.append("step_set", 'Y');
			formData.append("step_set_ids", $("#send_sms_idx").val());
			formData.append("iam_mall_idx", $("#iam_mall_idx").val());
			if ($('#iam_mall_img')[0].files.length) {
				formData.append('iam_mall_img', $('#iam_mall_img')[0].files[0]);
			} else {
				formData.append('iam_mall_img_link', $('#iam_mall_img_link').val());
			}
			formData.append("iam_mall_idx", $("#iam_mall_idx").val());
			formData.append("iam_mall_method", $("#iam_mall_method").val());
			formData.append("iam_mall_type", $("#iam_mall_type").val());
			formData.append("iam_mall_link", $("#iam_mall_link").val());
			formData.append("iam_mall_title", $('#iam_mall_title').val());
			formData.append("iam_mall_sub_title", $('#iam_mall_sub_title').val());
			formData.append("iam_mall_desc", $('#iam_mall_desc').val());
			formData.append("iam_mall_keyword", $('#iam_mall_keyword').val());
			formData.append("iam_mall_price", $('#iam_mall_price').val());
			formData.append("iam_mall_sell_price", $('#iam_mall_sell_price').val());
			formData.append("iam_mall_display", $('#iam_mall_display').prop("checked") == true ? 1 : 0);
			$.ajax({
				type: "POST",
				url: "/iam/ajax/mall.proc.php",
				data: formData,
				contentType: false,
				processData: false,
				success: function(data) {
					alert(data);
					refresh_page();
				}
			});
		} else {
			$("#service_contents_popup").show();
			cancel_set();
			$("#tutorial-loading").show();
			$("#service_contents_popup_ok").attr("href", "javascript:create_iam_mall(1)");
		}
	}
	$('input[name=iam_mall_img_type]').change(function() {
		$('#iam_mall_img_preview').attr('src', "");
		if ($(this).val() == 'f') {
			$("#iam_mall_img").css("display", "block");
			$("#iam_mall_img_link").css("display", "none");
			$("#iam_mall_img_link").val("");
		} else if ($(this).val() == 'u') {
			$("#iam_mall_img").css("display", "none");
			$("#iam_mall_img").val("");
			$("#iam_mall_img_link").css("display", "block");
		}
	});

	// 서비스콘텐츠 팝업 닫기 함수
	window.hide_service_contents_popup = function() {
		$("#service_contents_popup").hide();
		$("#tutorial-loading").hide();
	};
});
</Script>
<?php  
include_once "_foot.php";
?>
<script>
function onFunnelListChannelChange(ch) {
    // 알림 숨기기 & 버튼 활성화 해제
    document.getElementById('funnel_list_notice_phone').style.display  = 'none';
    document.getElementById('funnel_list_notice_webmms').style.display = 'none';
    var btnPhone = document.getElementById('funnel_ch_btn_phone');
    var btnWebmms = document.getElementById('funnel_ch_btn_webmms');
    if (btnPhone) btnPhone.classList.remove('active');
    if (btnWebmms) btnWebmms.classList.remove('active');
    
    if (ch === 'phone') {
        document.getElementById('funnel_list_notice_phone').style.display = 'block';
        if (btnPhone) btnPhone.classList.add('active');
    } else {
        document.getElementById('funnel_list_notice_webmms').style.display = 'block';
        if (btnWebmms) btnWebmms.classList.add('active');
    }
    // sub_6.php의 채널과 동기화 (localStorage 경유)
    try { localStorage.setItem('funnel_send_channel', ch); } catch(e){}
    // DB 저장: sms_idx=0 → 전체 퍼널에 채널 적용
    if (typeof $ !== 'undefined') {
        $.ajax({type:'POST', url:'/iam/ajax/funnel_channel_set.php', data:{sms_idx:0, channel:ch}, dataType:'json'});
    }
}
// 페이지 로드 시 localStorage에서 채널 복원
document.addEventListener('DOMContentLoaded', function() {
    try {
        var saved = localStorage.getItem('funnel_send_channel');
        if (saved === 'webmms') {
            onFunnelListChannelChange('webmms');
        }
    } catch(e){}
});
</script>
