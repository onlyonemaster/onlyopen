<?php   
$path = "./";
include_once "_head.php";
extract($_REQUEST);
if (!$_SESSION['one_member_id']) {
?>
    <script language="javascript">
        location.replace('/ma.php');
    </script>
<?php  
    exit;
}
if (!isset($_GET['reserv_type']))
    $reserv_type = $member_1['ai_status'];
if (!isset($_REQUEST['sms_idx'])) {
    $sms_idx = 0;
}
if (!isset($_REQUEST['get_idx'])) {
    $get_idx = 0;
}

if ($sms_idx) {
    if ($reserv_type == 1)
        $sql = "SELECT * FROM Gn_aievent_ms_info  WHERE sms_idx='{$sms_idx}'";
    else
        $sql = "SELECT * FROM Gn_event_sms_info  WHERE sms_idx='{$sms_idx}'";
    $sresul_num = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_array($sresul_num);
}
if ($get_idx) {
    $sql = "SELECT * FROM Gn_event_sms_info  WHERE sms_idx='{$get_idx}'";
    $sresul_num = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_array($sresul_num);
}
?>
<link rel="stylesheet" href="/admin/bootstrap/css/bootstrap.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel='stylesheet' type='text/css' href='/css/sub_4_re.css' />

<script src="/admin/bootstrap/js/bootstrap.min.js"></script>
<script src="/iam/js/layer.min.js" type="application/javascript"></script>
<script src="/iam/js/chat.js"></script>
<script type="text/javascript" src="jquery.lightbox_me.js"></script>
<script type="text/javascript" src="/js/mms_send.js"></script>
<!--script type="text/javascript" src="/plugin/tablednd/js/jquery.tablednd.0.7.min.js"></script-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 퍼널메시지 세트 만들기
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
.m_body   { background: var(--nm-dv2-bg); padding: 16px 0; flex:1; min-width:0; max-width:1400px; width:100%; }

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

/* ── inputs / textareas ── */
select, input[type=text], textarea,
.p1 select, .p1 input[type=text] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
    transition: border-color .2s;
}
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
    color: #fff !important;
    border-color: var(--nm-dv2-green) !important;
}

/* ── 라벨 ── */
label {
    color: var(--nm-dv2-muted);
    font-size: var(--fz-label, 14px);
}

/* ── list_table, list_table1 ── */
.list_table, .list_table1 {
    border-collapse: collapse;
    width: 100%;
    font-size: var(--fz-small, 13px);
}
.list_table tr, .list_table td, .list_table th,
.list_table1 tr, .list_table1 td, .list_table1 th {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    padding: 7px 8px;
    vertical-align: middle;
}
.list_table tr:first-child td, .list_table thead th,
.list_table1 tr:first-child td, .list_table1 tr:first-child th {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
}
.list_table tr:hover td,
.list_table1 tr:hover td {
    background: var(--nm-dv2-hover) !important;
}
.list_table a, .list_table1 a {
    color: var(--nm-dv2-accent);
    text-decoration: none;
}
.list_table a:hover, .list_table1 a:hover {
    color: var(--nm-dv2-green);
    text-decoration: underline;
}

/* ── 페이징 ── */
.page_div a, .page_f a, .page_f span {
    color: var(--nm-dv2-muted) !important;
    background: var(--nm-dv2-card2) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: var(--fz-label, 14px);
}
.page_div a:hover, .page_div a.on,
.page_f a:hover, .page_f span.on {
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
    z-index: 1;
    text-align: left;
    font-size: 12px;
    font-weight: normal;
    padding: 10px;
    position: absolute;
}

/* ── ad_layer4 (예약메시지 팝업) ── */
.ad_layer4 {
    width: 903px;
    height: auto;
    background-color: var(--nm-dv2-card) !important;
    border: 2px solid var(--nm-dv2-border) !important;
    position: relative;
    box-sizing: border-box;
    padding: 30px 30px 50px 30px;
    display: none;
}
.ad_layer4 .pop_title {
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
    font-size: var(--fz-step, 17px);
    margin-bottom: 12px;
}
.ad_layer4 .layer_close img {
    filter: brightness(0) invert(1);
}

/* ── info_box_table ── */
.info_box_table th {
    height: 40px;
    border-bottom: 1px solid var(--nm-dv2-border) !important;
    width: 200px !important;
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    font-size: var(--fz-label, 14px);
}
.info_box_table td {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
}
.info_box_table input[type=text],
.info_box_table textarea,
.info_box_table input[type=file],
.info_box_table select {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
    width: 600px;
    height: auto;
}
.info_box_table textarea {
    min-height: 200px;
}

/* ── ok_box ── */
.ok_box {
    text-align: center;
    margin-top: 16px;
}

/* ── ajax-loading ── */
#ajax-loading {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 9000;
    text-align: center;
    display: none;
    background-color: var(--nm-dv2-bg);
    opacity: 0.85;
}
#ajax-loading img {
    position: absolute;
    top: 50%; left: 50%;
    width: 120px; height: 120px;
    margin: -60px 0 0 -60px;
}

/* ── chat_btn ── */
.chat_btn {
    color: #fff !important;
    border-radius: 7px;
    background-color: var(--nm-dv2-red);
    font-size: 12px;
    border-color: var(--nm-dv2-red);
    padding: 4px 0px;
    margin-right: 3px;
    position: absolute;
    right: 45px;
}

/* ── answer_side ── */
#answer_side, #answer_side1, #answer_side2 {
    width: 90%;
    height: 400px;
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    margin-right: auto;
    margin-left: auto;
    border-radius: 10px;
    margin-top: 12px;
    padding: 35px 30px 10px 30px;
    overflow: auto;
    text-align: left;
    position: relative;
}

/* ── search_keyword ── */
.search_keyword {
    position: relative;
    width: 99%;
    margin: 0 auto;
    margin-top: 10px;
}
.search_keyword textarea {
    width: 78%;
    height: 50px;
    padding: 17px 60px 0 25px;
    border-radius: 15px;
    font-size: 15px;
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    outline-width: 0;
    box-shadow: 0 5px 10px -5px rgba(0,0,0,0.5);
    -webkit-transition: border-color 1000ms ease-out;
    transition: border-color 1000ms ease-out;
}

/* ── send_ask ── */
.send_ask {
    position: absolute;
    top: 0; right: 60px;
    width: 58px; height: 100%;
    background-color: var(--nm-dv2-card2);
    border-radius: 20px;
    border: 1px solid var(--nm-dv2-border);
}

/* ── gpt_req_list_title ── */
#gpt_req_list_title {
    float: left;
    padding: 7px;
    margin-left: 40px;
    background-color: var(--nm-dv2-red);
    color: #fff !important;
    border-radius: 10px;
}

/* ── history, newpane, gpt_act ── */
.history {
    position: absolute;
    top: 5px; left: 80px;
}
.gpt_act {
    position: relative;
    height: 35px;
}
.newpane, .newpane:hover {
    background-color: var(--nm-dv2-card2);
    color: var(--nm-dv2-text) !important;
    padding: 4px;
    border-radius: 10px;
    position: absolute;
    top: 5px; right: 80px;
}

/* ── get_type_name ── */
.get_type_name {
    text-align: left;
    display: flex;
    color: var(--nm-dv2-muted);
}

/* ── iam_table ── */
.iam_table {
    border: 1px solid var(--nm-dv2-border) !important;
    border-collapse: collapse;
    padding: 3px;
    text-align: center;
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
}

/* ── zoom ── */
.zoom { transition: transform .2s; }
.zoom:hover {
    transform: scale(4);
    border: 1px solid var(--nm-dv2-accent);
    box-shadow: 1px 1px 1px 0px rgba(0,0,0,0.5);
}
.zoom-2x { transition: transform .2s; }
.zoom-2x:hover {
    transform: scale(2);
    border: 1px solid var(--nm-dv2-accent);
    box-shadow: 1px 1px 1px 0px rgba(0,0,0,0.5);
}

/* ── del_img_btn ── */
.del_img_btn {
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 3px;
    background-color: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    padding: 5px;
}

/* ── switch 토글 ── */
.switch .slider {
    background-color: var(--nm-dv2-card2);
}
.switch input:checked + .slider {
    background-color: var(--nm-dv2-green);
}

/* ── 모달 다크모드 ── */
.modal-content {
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
}
.modal-header {
    background-color: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    border-bottom: 1px solid var(--nm-dv2-border) !important;
}
.modal-body {
    background-color: var(--nm-dv2-bg) !important;
    color: var(--nm-dv2-text) !important;
}
.modal-footer {
    background-color: var(--nm-dv2-card) !important;
    border-top: 1px solid var(--nm-dv2-border) !important;
}
.modal-footer .btn-submit {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
}
#auto_making_modal .modal-header {
    background-color: var(--nm-dv2-green) !important;
}
#auto_making_modal .modal-header .login_bold {
    color: #fff !important;
}
#gpt_chat_modal .modal-header {
    background: var(--nm-dv2-green) !important;
}
#gpt_chat_modal .modal-header .login_bold {
    color: #fff !important;
}
#gpt_chat_modal .modal-header a {
    color: #fff !important;
}
#gpt_chat_modal .modal-body {
    background-color: var(--nm-dv2-bg) !important;
}
#gpt_chat_modal .modal-footer {
    background-color: var(--nm-dv2-bg) !important;
}

/* ── w200 ── */
.w200 { width: 200px; }

/* ── 수신거부 텍스트 ── */
.deny_msg_span { color: var(--nm-dv2-text) !important; }

/* ── article-title, article-content ── */
.article-title {
    border-bottom: 1px solid var(--nm-dv2-border) !important;
    margin-bottom: 15px;
    font-size: 15px;
    text-align: left;
    color: var(--nm-dv2-text) !important;
}
.article-content {
    display: grid;
    margin-bottom: 15px;
    font-size: 15px;
    text-align: left;
    color: var(--nm-dv2-text) !important;
}

/* ── 기타 ── */
.hided { display: none; }
.copy_msg {
    position: absolute;
    right: 10px; top: 10px;
}

/* ── 챗봇버튼 ── */
.a1 a[href*="mypage_chatbot_list"] {
    background: var(--nm-dv2-accent) !important;
    color: #fff !important;
    border-radius: 6px;
    padding: 4px 12px;
    font-weight: var(--fw-semi, 600);
}

/* ── 반응형 ── */
/* ── nm-btn 버튼 시스템 (글로벌) ── */
.nm-btn { font-size: 16px !important; padding: 10px 28px !important; border-radius: 8px !important; font-weight: var(--fw-bold, 700) !important; cursor: pointer !important; transition: all .2s !important; border: 2px solid transparent !important; min-width: 100px !important; display: inline-block !important; text-align: center !important; }
.nm-btn-primary { background: var(--nm-dv2-green) !important; color: #fff !important; border-color: var(--nm-dv2-green) !important; }
.nm-btn-primary:hover { background: #6ab828 !important; border-color: #6ab828 !important; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(130,200,54,0.35); color: #fff !important; }
.nm-btn-outline { background: transparent !important; color: var(--nm-dv2-text) !important; border-color: var(--nm-dv2-border) !important; }
.nm-btn-outline:hover { background: var(--nm-dv2-hover) !important; border-color: var(--nm-dv2-accent) !important; color: var(--nm-dv2-accent) !important; }
.nm-btn-danger { background: var(--nm-dv2-red) !important; color: #fff !important; border-color: var(--nm-dv2-red) !important; }
.nm-btn-danger:hover { background: #c94a4a !important; }

@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .list_table, .list_table1 { font-size: 11px; }
    .list_table td, .list_table th,
    .list_table1 td, .list_table1 th { padding: 4px 3px; }
    .list_table1 input[type=text],
    .info_box_table input[type=text] { width: 100% !important; max-width: 100%; }
    .ad_layer4 { width: 95% !important; padding: 16px 10px 30px 10px; }
    .a1 { flex-direction: column !important; align-items: stretch !important; }
    .m_body { max-width: 100% !important; }
}

/* ── 모바일 버튼 최적화 ── */
@media (max-width: 768px) {
    .nm-btn { font-size: 15px !important; padding: 12px 20px !important; min-width: 80px !important; width: auto !important; }
    .nm-btn-primary, .nm-btn-outline { width: 48% !important; margin: 4px 1% !important; }
}

/* ═══════════════════════════════════════════════════════════
   nm-darkver2 강제 오버라이드 (sub_4_re.css 대응)
   ═══════════════════════════════════════════════════════════ */

/* 상단 타이틀 "퍼널메시지 세트 만들기" — 검정색 방지 */
.a1, .a1 .popup_holder, .a1 .popup_text,
.popup_text, .popup_holder {
    color: var(--nm-dv2-text) !important;
}
.a1 a, .a1 span, .a1 div {
    color: var(--nm-dv2-text) !important;
}

/* ── ad_layer4 팝업 전체 오버라이드 (sub_4_re.css 가 .ad_layer4 background:#fff, color:#000 을 정의함) ── */
.ad_layer4 {
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.ad_layer4 .pop_title {
    color: var(--nm-dv2-green) !important;
}
.ad_layer4 .info_box {
    border-color: var(--nm-dv2-border) !important;
}
.ad_layer4 .info_box_table th {
    background-color: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    border-color: var(--nm-dv2-border) !important;
}
.ad_layer4 .info_box_table td {
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.ad_layer4 .ok_box {
    color: var(--nm-dv2-text) !important;
}

/* ── 모달 다크모드 강제 오버라이드 ── */
.modal-content,
.modal-header,
.modal-body,
.modal-footer {
    background-color: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.modal-header {
    background-color: var(--nm-dv2-card2) !important;
}
.modal-body {
    background-color: var(--nm-dv2-bg) !important;
}

/* ── AI 모달 내 테이블 / 라벨 ── */
.get_type_name div,
.get_type_name label {
    color: var(--nm-dv2-text) !important;
    font-size: var(--fz-label, 14px);
}
.iam_table {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.iam_table td {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}

/* ── 모달 input, textarea ── */
.modal-body input[type=text],
.modal-body input[type=number],
.modal-body input[type=date],
.modal-body textarea,
.modal-body input[type=file],
#auto_making_modal input[type=text],
#auto_making_modal input[type=number],
#auto_making_modal input[type=date],
#gpt_chat_modal textarea,
#gpt_chat_modal input[type=text] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
}

/* ── 모달 내 일반 텍스트 ── */
.modal-body p, .modal-body div,
#gpt_chat_modal p, #gpt_chat_modal div,
#gpt_chat_modal .container p {
    color: var(--nm-dv2-text) !important;
}

/* ── radio + checkbox label ── */
.get_type_name input[type=radio] + label,
.modal-body label,
.modal-body .get_type_name {
    color: var(--nm-dv2-text) !important;
}

/* ── "수신거부" 관련 ── */
.deny_msg_span {
    color: var(--nm-dv2-text) !important;
}

/* ── info_box_table 오버라이드 ── */
.info_box_table th,
.info_box_table td,
.in_info .info_box_table th,
.in_info .info_box_table td {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.info_box_table th {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
}

/* ── page-hero 버튼 흰색 텍스트 ── */
.page-hero__btn--secondary {
    color: #fff !important;
}

/* ── 모달 닫기버튼 X ── */
#gpt_chat_modal .modal-header a {
    color: #fff !important;
    opacity: 0.8;
}
#gpt_chat_modal .modal-header a:hover {
    opacity: 1;
}

</style>

<div class="big_div">
	<div class="big_sub">
    <div class="m_div">
        <?php   include "mypage_left_menu.php"; ?>
        <div class="m_body">

<!-- === 페이지 히어로 배너 === -->
<div class="page-hero page-hero--action" style="margin-bottom:20px;">
    <div class="page-hero-inner">
        <div class="page-hero-content">
            <span class="page-hero-badge">퍼널관리</span>
            <h1 class="page-hero-title">퍼널메시지세트 <em>만들기</em></h1>
            <p class="page-hero-desc">AI·수동 퍼널 메시지 설계를 등록·관리합니다.</p>
        </div>
        <div class="page-hero-actions">
            <a class="page-hero__btn page-hero__btn--secondary" href="/mypage_reservation_list.php">← 퍼널 리스트</a>
        </div>
    </div>
</div>

            <?php   if ($reserv_type == 0) { ?>
                <div class="a1" style="margin-top:50px; margin-bottom:15px">
                    <div class="popup_holder popup_text">퍼널메시지 세트 만들기
                        <div class="popupbox" style="display:none;height:auto;width:280px;left:230px;top:-37px;z-index:100">메시지를 주기적으로 보내기 위한 퍼널세트를 만드는 기능입니다.<br>
                            <a class="detail_view" style="color: var(--nm-dv2-accent);" href="https://tinyurl.com/yh4e3n5y" target="_blank">[자세히 보기]</a>
                        </div>
                    </div>
                    <p style="clear:both"></p>
                </div>
                <form name="sform" id="sform" action="mypage.proc.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="<?= $sms_idx ? "sms_update" : "sms_save"; ?>" />
                    <input type="hidden" name="sms_idx" value="<?= $sms_idx; ?>" />
                    <input type="hidden" name="event_idx" id="event_idx" value="<?= $row['event_idx']; ?>" />
                    <div class="p1">
                        <table class="list_table1" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <th class="w200">퍼널메시지 세트제목</th>
                                <td><input type="text" name="reservation_title" placeholder="" id="reservation_title" value="<?= $row['reservation_title'] ?>" /> </td>
                            </tr>
                            <tr>
                                <th class="w200">퍼널메시지 세트설명</th>
                                <td>
                                    <input type="text" name="reservation_desc" placeholder="" id="reservation_desc" value="<?= $row['reservation_desc'] ?>" />
                                </td>
                            </tr>
                            <?php   if ($get_idx) { ?>
                                <tr>
                                    <th class="w200">퍼널메시지세트 가져오기</th>
                                    <td>
                                        <input type="hidden" id="event_idx_event" name="event_idx_event" value="<?= $row['event_idx'] ?>" style="width:250px;">
                                        <input type="hidden" id="mb_id_copy" name="mb_id_copy" value="<?= $_SESSION['one_member_id'] ?>" style="width:250px;">
                                        <input type="text" name="mb_id" id="mb_id" value="<?= $_SESSION['one_member_id'] ?>" style="width:250px; height: 27px;">
                                        <input type="hidden" id="ori_sms_idx" name="ori_sms_idx" value="<?= $get_idx ?>" style="width:95px;">
                                        <input type="button" value="메시지세트 조회" class="nm-btn nm-btn-outline" id="searchEventBtn" onclick="newMessageEvent()">
                                    </td>
                                </tr>
                            <?php    } ?>
                        </table>
                    </div>
                    <div style="text-align:center;margin-top:10px">
                        <input type="button" value="저장" class="nm-btn nm-btn-primary" id="saveBtn">
                        <input type="button" value="취소" class="nm-btn nm-btn-outline" id="cancleBtn">
                    </div>
                </form>
            <?php   }
            if ($get_idx > 0) {
                $sms_idx = $get_idx;
            }
            if ($reserv_type) {
                $show = "hidden";
            } else {
                $show = "";
            }
            ?>
            <form name="pay_form" action="" method="post" class="my_pay" style="margin-top:50px">
                <input type="hidden" name="page" value="<?= $page ?>" />
                <input type="hidden" name="page2" value="<?= $page2 ?>" />
                <div class="a1" style="position:relative; min-height:32px; line-height:32px;">
                    <?= $reserv_type ? "퍼널신청고객 리스트" : "메시지 리스트" ?>
                    <?php if ($reserv_type == 1 && $sms_idx): ?>
                    <a href="/mypage_chatbot_list.php?sms_idx=<?= $sms_idx ?>" target="_blank"
                       style="position:absolute; right:0; top:50%; transform:translateY(-50%);
                              padding:4px 12px; background:var(--nm-dv2-accent); color:#fff; border-radius:4px;
                              font-size:12px; text-decoration:none; line-height:1.5;">
                        &#128172; 챗봇대화리스트
                    </a>
                    <?php endif; ?>
                    <p style="clear:both"></p>
                </div>
                <div>
                    <div class="p1" style="float:right;">
                        <input type="button" value="AI로 추가하기" class="nm-btn nm-btn-primary popbutton12" <?= $show ?>>
                        <input type="button" value="발송회차 추가하기" class="nm-btn nm-btn-primary popbutton4" <?= $show ?>>
                    </div>
                    <div>
                        <table class="list_table" width="100%" border="0" cellspacing="0" cellpadding="0">
                            <?php   if ($reserv_type == 1) { ?>
                                <tr>
                                    <td style="width:3%;">번호</td>
                                    <td style="width:5%;">구분</td>
                                    <td style="width:5%;">아이디</td>
                                    <td style="width:6%;">수신자명</td>
                                    <td style="width:6%;">수신자폰</td>
                                    <td style="width:5%;">회차</td>
                                    <td style="width:6%;">발송시간</td>
                                    <td style="width:10%;">AI퍼널제목</td>
                                    <td style="width:5%;">파일1</td>
                                    <td style="width:5%;">파일2</td>
                                    <td style="width:5%;">파일3</td>
                                    <td style="width:6%;">등록일</td>
                                    <td style="width:6%;">관리</td>
                                    <td style="width:6%;">적용</td>
                                </tr>
                            <?php   } else { ?>
                                <tr>
                                    <td style="width:2%;"></td>
                                    <td style="width:6%;">구분</td>
                                    <td style="width:6%;">회차</td>
                                    <td style="width:6%;">발송일시</td>
                                    <td style="width:20%;">메시지제목</td>
                                    <td style="width:35%;">메시지내용</td>
                                    <td style="width:15%;">이미지</td>
                                    <td style="width:10%;">수정/삭제</td>
                                </tr>
                                <?php   }
                            if ($reserv_type == 1)
                                $sql_serch = " sms_idx ='{$sms_idx}' AND m_id='{$_SESSION['one_member_id']}' ";
                            else
                                $sql_serch = " sms_idx ='{$sms_idx}' ";
                            if ($_REQUEST['search_date']) {
                                if ($_REQUEST['rday1']) {
                                    $start_time = strtotime($_REQUEST['rday1']);
                                    $sql_serch .= " AND unix_timestamp({$_REQUEST['search_date']}) >=$start_time ";
                                }
                                if ($_REQUEST['rday2']) {
                                    $end_time = strtotime($_REQUEST['rday2']);
                                    $sql_serch .= " AND unix_timestamp({$_REQUEST['search_date']}) <= $end_time ";
                                }
                            }
                            if ($reserv_type == 1)
                                $sql = "SELECT count(*) as cnt FROM Gn_aievent_request WHERE $sql_serch ";
                            else
                                $sql = "SELECT count(step) as cnt FROM Gn_event_sms_step_info WHERE $sql_serch ";
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
                            else {
                                if ($reserv_type == 1)
                                    $order_name = "request_idx";
                                else
                                    $order_name = "step";
                            }
                            $intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
                            if ($intRowCount && $sms_idx > 0) {
                                if ($reserv_type == 1)
                                    $sql = "SELECT * FROM Gn_aievent_request WHERE  $sql_serch order by $order_name $order_status limit $int,$intPageSize";
                                else
                                    $sql = "SELECT * FROM Gn_event_sms_step_info WHERE $sql_serch order by $order_name $order_status limit $int,$intPageSize";
                                $result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
                                $c = 0;
                                while ($row = mysqli_fetch_array($result)) {
                                    if ($reserv_type == 1) {
                                        $sql_cnt = "SELECT * FROM Gn_aievent_ms_info WHERE sms_idx='{$row['sms_idx']}'";
                                        $cnt_res = mysqli_query($self_con, $sql_cnt);
                                        $cnt_row = mysqli_fetch_assoc($cnt_res);
                                        $sql_stepcnt = "SELECT COUNT(*) as cnt FROM Gn_aievent_message WHERE request_idx='{$row['request_idx']}'";
                                        $stepcnt_res = mysqli_query($self_con, $sql_stepcnt);
                                        $stepcnt_row = mysqli_fetch_array($stepcnt_res);
                                        $row['step'] = $stepcnt_row['cnt'];
                                        $row['title'] = $cnt_row['reservation_title'];
                                    }
                                ?>
                                    <tr>
                                        <td><?= $sort_no ?></td>
                                        <td><?= $reserv_type == 1 ? 'AI' : '수동' ?></td>
                                        <?php   if ($reserv_type == 1) { ?>
                                            <td><?= $row['req_id'] ?></td>
                                            <td><?= $row['name'] ?></td>
                                            <td><?= $row['mobile'] ?></td>
                                        <?php   } ?>
                                        <td style="font-size:12px;"><?= $row['step'] ?></td>
                                        <td style="font-size:12px;"><?= $reserv_type == 1 ? $cnt_row['ai_day'] . '일/' . $cnt_row['ai_hour'] : $row['send_day'] . '일후' ?></td>
                                        <td><?= $row['title'] ?></td>

                                        <?php   if ($reserv_type == 1) {
                                            $file_ext1 = explode('.', $row['file1']);
                                            $file_ext1 = end($file_ext1);
                                            $file_ext2 = explode('.', $row['file2']);
                                            $file_ext2 = end($file_ext2);
                                            $file_ext3 = explode('.', $row['file3']);
                                            $file_ext3 = end($file_ext3);
                                        ?>
                                            <td><?php   if ($file_ext1 != "") { ?>
                                                    <img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext1; ?>" /><a href=<?= $row['file1'] ?>><?= $file_ext1 ?></a>
                                                <?php   } ?>
                                            </td>
                                            <td><?php   if ($file_ext2 != "") { ?>
                                                    <img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext2; ?>" /><a href=<?= $row['file2'] ?>><?= $file_ext2 ?></a>
                                                <?php   } ?>
                                            </td>

                                            <td><?php   if ($file_ext3 != "") { ?>
                                                    <img width="20" height="20" src="https://img.icons8.com/color/<?= $file_ext1; ?>" /><a href=<?= $row['file3'] ?>><?= $file_ext3 ?></a>
                                                <?php   } ?>
                                            </td>
                                            <td><?= $row['regdate'] ?></td>
                                        <?php   } else { ?>
                                            <td>
                                                <a href="javascript:void(0)" onclick="show_recv('show_content','<?= $c ?>','문자내용')"><?= str_substr($row['content'], 0, 190, 'utf-8') ?></a><input type="hidden" name="show_content" value="<?= $row['content'] ?>" />
                                            </td>
                                            <td>
                                                <?php   if ($row['image']) { ?>
                                                    <img class="zoom" src="/upload/<?= $row['image'] ?>" style="max-height:50px">
                                                <?php   }
                                                if ($row['image1']) { ?>
                                                    <img class="zoom" src="/upload/<?= $row['image1'] ?>" style="max-height:50px">
                                                <?php   }
                                                if ($row['image2']) { ?>
                                                    <img class="zoom" src="/upload/<?= $row['image2'] ?>" style="max-height:50px">
                                                <?php   } ?>
                                            </td>
                                        <?php   } ?>
                                        <td>
                                            <?php   if ($reserv_type == 1) {
                                                $sql_check = "SELECT count(*) as cnt FROM Gn_aievent_message WHERE request_idx='{$row['request_idx']}'";
                                                $result_cnt = mysqli_query($self_con, $sql_check) or die(mysqli_error($self_con));
                                                $row_cnt = mysqli_fetch_array($result_cnt);
                                                if ($row_cnt['cnt'] == 0) {
                                            ?>
                                                    <p style="color:var(--nm-dv2-red)"> 생성중</p>
                                                <?php   } else {
                                                ?>
                                                    <a href="list_funnelmessages_details.php?sms_idx=<?= $row['sms_idx'] ?>&customer_id=<?= $row['req_id'] ?>&name=<?= $row['name'] ?>&reqidx=<?= $row['request_idx'] ?>">보기</a>
                                                <?php  
                                                }
                                                ?>
                                            <?php   } else { ?>
                                                <a href="javascript:editRow('<?= $row['sms_detail_idx']; ?>','<?= $row['sms_idx']; ?>')">수정</a>
                                                <a href="javascript:deleteRow('<?= $row['sms_detail_idx']; ?>','<?= $row['sms_idx']; ?>')">삭제</a>
                                            <?php   } ?>
                                        </td>
                                        <?php   if ($reserv_type == 1) { ?>
                                            <td>
                                                <?php  
                                                $sql_status = "SELECT status_yn FROM Gn_aievent_message WHERE request_idx='{$row['request_idx']}' AND status_yn='Y'";
                                                $status_res = mysqli_query($self_con, $sql_status);
                                                $cnt_check = mysqli_num_rows($status_res);
                                                if ($cnt_check > 0) $status = "Y";
                                                else $status = "N";

                                                ?>
                                                <label class="switch">
                                                    <input type="checkbox" name="status" id="stauts_<?= $row['mem_id']; ?>" value="<?= $row['req_id']; ?>" data-idx="<?= $row['sms_idx'] ?>" data-reqidx="<?= $row['request_idx'] ?>" <?= $status == "Y" ? "checked" : "" ?>>
                                                    <span class="slider round" name="status_round" id="stauts_round_<?= $row['mem_id']; ?>"></span>
                                                </label>
                                            </td>
                                        <?php   } ?>
                                    </tr>
                                <?php  
                                    $c++;
                                    $sort_no--;
                                }
                                ?>
                                <tr>
                                    <td colspan="13">
                                        <?php   page_f($page, $page2, $intPageCount, "pay_form"); ?>
                                    </td>
                                </tr>
                            <?php  
                            } else {
                            ?>
                                <tr>
                                    <td colspan="13">
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
            <?php    //}
            ?>
        </div> <!--mbody-->
    </div> <!--mdiv-->
    <div class="ad_layer4">
        <div class="layer_in">
            <span class="layer_close close" onclick="refresh_page()"><img src="/images/close_button_05.jpg"></span>
            <div class="pop_title">
                예약메시지
            </div>
            <div class="info_box">
                <button onclick="show_chat('<?= $member_1['gpt_chat_api_key'] ?>')" class="chat_btn">AI와 대화하기</button>
                <form method="post" name="addForm" id="addForm" action="mypage.proc.php" enctype="multipart/form-data">
                    <input type="hidden" name="sms_idx" value="<?= $sms_idx; ?>">
                    <input type="hidden" name="mode" id="mode" value="step_add">
                    <input type="hidden" name="sms_detail_idx" id="sms_detail_idx">
                    <table class="info_box_table" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <th class="w200">순서</th>
                                <td>
                                    <input type="text" id="step" name="step" value="" style="width:70px;">
                                </td>
                            </tr>
                            <tr>
                                <th class="w200">발송일시</th>
                                <td>
                                    <input type="text" id="send_day" name="send_day" value="" style="width:70px;" maxlength="3">일후(0이면 신청 후 즉시 발송)
                                    <span id="timeArea" style="display:none">
                                        <input type="text" id="send_time_hour" name="send_time_hour" value="" style="width:70px;" maxlength="2"> 시
                                        <!--input type="text" id="send_time_min" name="send_time_min" value="" style="width:70px;" maxlength="2"> 분 (10분단위로 설정가능)-->
                                        <select name="send_time_min" id="send_time_min" style="width:70px;">
                                            <?php  
                                            for ($i = 0; $i < 60; $i += 10) {
                                                $iv = $i == 0 ? "00" : $i;
                                            ?>
                                                <option value="<?= $iv ?>" data="<?= $iv ?>"><?= $iv ?></option>
                                            <?php  
                                            }
                                            ?>
                                        </select>
                                    </span>
                                    <div id="display_day"></div>
                                </td>
                            </tr>
                            <tr>
                                <th>메시지제목</th>
                                <td><input type="text" id="title" name="title" value=""></td>
                            </tr>
                            <tr>
                                <th>메시지내용</th>
                                <td>
                                    <textarea name="content" itemname="내용" id="content" required="" placeholder="메시지내용을 입력하세요" style="width:600px;max-width:100%;height:200px;"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th>수신거부</th>
                                <td><input type="checkbox" id="send_deny_msg" name="send_deny_msg" onclick="deny_msg_click(this,0)" style="float:left;">
                                    <div class="deny_msg_span" style="float:left;">OFF</div>
                                </td>
                            </tr>
                            <tr>
                                <th>이미지1</th>
                                <td><input type="file" id="file" name="image" value="" accept="image/*"><span id="image1"></span></td>
                            </tr>
                            <tr>
                                <th>이미지2</th>
                                <td><input type="file" id="file" name="image1" value="" accept="image/*"><span id="image2"></span></td>
                            </tr>
                            <tr>
                                <th>이미지3</th>
                                <td><input type="file" id="file" name="image2" value="" accept="image/*"><span id="image3"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="ok_box">
                <input type="button" value="취소" class="nm-btn nm-btn-outline" id="popCloseBtn">
                <input type="button" value="저장" class="nm-btn nm-btn-primary" id="popSaveBtn">
            </div>
        </div>
    </div>

    <div id="auto_making_modal" class="modal fade" tabindex="-1" role="dialog" style="overflow-x: auto; overflow-y: auto; display:none;background:rgba(0,0,0,0.7);z-index:8000;">
        <div class="modal-dialog" style="margin: 100px auto;">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header" style="border:none;background-color: var(--nm-dv2-green)">
                    <div class="login_bold" style="margin-bottom: 0px;color: #fff;font-size: 22px;text-align: center">AI로 퍼널메시지 만들기
                    </div>
                </div>
                <div class="modal-body">
                    <div class="container" style="margin-top: 20px;text-align: center;width: 100%;">
                        <table style="width:100%;">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;border-bottom-color: var(--nm-dv2-border);">수집분야설정</td>
                                    <td class="iam_table" style="border-bottom-color: var(--nm-dv2-border);">
                                        <div class="get_type_name">
                                            <div style="width:64px;" onclick="show_keyword('news')">
                                                <input type="radio" name="web_type" id="newsid" style="vertical-align: top;" checked>
                                                <label for="newsid" value="news" style="font-size:17px;">뉴스</label>
                                            </div>
                                            <div style="width: 70px;" onclick="show_keyword('blog')">
                                                <input type="radio" name="web_type" id="blogid" style="vertical-align: top;">
                                                <label for="blogid" value="blog" style="font-size:17px;">블로그</label>
                                            </div>
                                            <div style="width: 70px;" onclick="show_keyword('youtube')">
                                                <input type="radio" name="web_type" id="youtubeid" style="vertical-align: top;">
                                                <label for="youtubeid" value="youtube" style="font-size:17px;">유튜브</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="web_address" style="width:100%;display:none;">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;border-bottom-color: var(--nm-dv2-border);">웹주소입력</td>
                                    <td class="iam_table" style="border-bottom-color: var(--nm-dv2-border);"><input type="text" placeholder="생성하고 싶은 페이지 주소를 넣으세요" id="people_web_address" style="width: 100%;"></td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="contents_key" style="width:100%;">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;border-bottom-color: var(--nm-dv2-border);">콘텐츠 키워드</td>
                                    <td class="iam_table" style="border-bottom-color: var(--nm-dv2-border);"><input type="text" placeholder="콘텐츠 검색시 필요한 키워드를 입력하세요" id="people_contents_key" style="width: 100%;"></td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="contents_time" style="width:100%;">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;border-bottom-color: var(--nm-dv2-border);">콘텐츠 수집기간</td>
                                    <td class="iam_table" style="border-bottom-color: var(--nm-dv2-border);"><input type="date" id="people_contents_start_date" style="width: 40%;border:1px solid var(--nm-dv2-border);" value="<?= date('2010-01-01') ?>">&nbsp;&nbsp;~&nbsp;&nbsp;<input type="date" id="people_contents_end_date" style="width: 40%;border:1px solid var(--nm-dv2-border);" value="<?= date('Y-m-d') ?>"></td>
                                </tr>
                            </tbody>
                        </table>
                        <table style="width:100%">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;">콘텐츠수 입력</td>
                                    <td class="iam_table"><input type="number" placeholder="카드에 넣고 싶은 콘텐츠갯수 지정(최대 <?= $Gn_contents_limit; ?>개/유튜브 30개)" id="people_contents_cnt" min="1" max="<?= $Gn_contents_limit; ?>" style="width: 100%;"></td>
                                </tr>
                            </tbody>
                        </table>
                        <table id="sendtime" style="width:100%;">
                            <tbody>
                                <tr>
                                    <td class="iam_table" style="width: 22.8%;border-top-color: var(--nm-dv2-border);">발송시간</td>
                                    <td class="iam_table" style="border-top-color: var(--nm-dv2-border);"><input type="text" placeholder="휴대폰, 일반전화 중에 선택 입력" id="send_time" style="width: 100%;" value="00:00"></td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- </form> -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="nm-btn nm-btn-outline" style="width:49%;" onclick="goback()">취소</button>
                    <button type="button" class="nm-btn nm-btn-primary" style="width:49%;" onclick="start_making()" id="startmaking">추가하기</button>
                </div>
            </div>
        </div>
    </div>
    <!--GPT chat modal-->
    <div id="gpt_chat_modal" class="modal fade" tabindex="-1" role="dialog" style="overflow-x: auto; overflow-y: auto;background:rgba(0,0,0,0.7);z-index:8100;">
        <div class="modal-dialog" style="margin: 30px auto;width: 100%;max-width:700px">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header" style="background: var(--nm-dv2-green);border-top-left-radius: 5px;border-top-right-radius: 5px;">
                    <div class="login_bold" id="gwc_con_name_modal" style="margin-bottom: 0px;color: #fff;font-size: 17px;text-align: center">콘텐츠 창작AI 알지(ALJI)</div>
                    <a data-dismiss="modal" style="float:right;color:#fff;font-size: 20px;font-weight: bold;margin-top: -27px;cursor:pointer;">X</a>
                </div>
                <div class="modal-body" style="background-color:var(--nm-dv2-bg);">
                    <div class="container" style="text-align: center;width: 100%;">
                        <p><img src="/iam/img/arji_intro_title.png" style="width: 22px;margin-right: 3px;">"알지(ALJI)" 인공지능에게 무엇이든 물어보세요.<br>구체적으로 질문할수록 "알지 AI" 답변이 정교해집니다.</p>
                        <p id="gpt_req_list_title" hidden>질문답변목록</p>
                        <ul id="answer_side" hidden>
                            <a class="copy_msg" href="javascript:copy_msg()"><img src="/iam/img/gpt_res_copy.png" style="height:20px;"></a>
                        </ul>
                        <ul id="answer_side1">
                            <?php  
                            $gpt_qu = get_search_key('gpt_question_example');
                            $gpt_an = get_search_key('gpt_answer_example');
                            $gpt_qu_arr = explode("||", $gpt_qu);
                            $gpt_an_arr = explode("||", $gpt_an);
                            for ($i = 0; $i < count($gpt_qu_arr); $i++) {
                            ?>
                                <li class="article-title" id="q<?= $i ?>" onclick="show('<?= $i ?>')"><img src="/iam/img/chat_Q.png" style="width:30px;margin-right: 10px;"><span class="chat_title"><?= htmlspecialchars_decode($gpt_qu_arr[$i]) ?></span><i id="down<?= $i ?>" class="fa fa-angle-down" style="font-size: 20px;font-weight: bold;margin-left: 10px;"></i><i id="up<?= $i ?>" class="fa fa-angle-up" style="font-size: 20px;font-weight: bold;margin-left: 10px;display:none;"></i></li>
                                <li class="article-content hided" id="a<?= $i ?>"><img src="/iam/img/chat_A.png" style="width:30px;"><span class="chat_answer" style="margin-left: 35px;"><?= htmlspecialchars_decode($gpt_an_arr[$i]) ?></span></li>
                            <?php   } ?>
                        </ul>
                        <ul id="answer_side2" hidden>
                        </ul>
                        <div class="gpt_act">
                            <a class="history" href="javascript:show_req_history();"><img src="/iam/img/gpt_req_list.png" style="height: 25px;"></a>
                            <a class="newpane" href="javascript:show_new_chat();"><span style="font-size: 5px;">NEW</a>
                        </div>
                        <div class="search_keyword">
                            <input type="hidden" name="key" id="key" value="<?= $member_1['gpt_chat_api_key'] ?>">
                            <textarea class="search_input" autocomplete="off" name="question" id="question" value="" title="질문을 입력하세요" placeholder="알지AI에게 질문해보세요" onclick="check_login('<?= $_SESSION['iam_member_id'] ?>')"></textarea>
                            <button type="button" onclick="send_post('<?= $_SESSION['iam_member_id'] ?>')" class="send_ask"><img src="/iam/img/send_ask.png" alt="전송"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="text-align: center;background-color: var(--nm-dv2-bg);padding:7px;">
                    <button type="button" class="nm-btn nm-btn-primary" style="width:50%;padding:10px 0;" onclick="send_chat()">보내기</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div><!-- big_div -->
<div id="ajax-loading"><img src="/iam/img/ajax-loader.gif"></div>
<script>
    function editRow(sms_detail_idx, sms_idx) {
        $.ajax({
            type: "POST",
            url: "mypage.proc.php",
            dataType: "json",
            data: {
                mode: "sms_detail_info",
                sms_detail_idx: sms_detail_idx,
                sms_idx: sms_idx
            },
            success: function(data) {
                $('.ad_layer4').lightbox_me({
                    centered: true,
                    onLoad: function() {
                        $('#mode').val('step_update');
                        $('#sms_detail_idx').val(data.data.sms_detail_idx);
                        var send_time = (data.data.send_time).split(":");
                        $('#step').val(data.data.step);
                        $('#send_day').val(data.data.send_day);
                        if (data.data.send_day != "0")
                            $('#timeArea').show();
                        $('#send_time_hour').val(send_time[0]);
                        $('#send_time_min').val(send_time[1]);
                        $('#title').val(data.data.title);
                        $('#content').val(data.data.content);
                        // $('#send_num').val(data.data.mobile);
                        $('#image1').html('');
                        $('#image2').html('');
                        $('#image3').html('');
                        if (data.data.send_deny == "Y") {
                            $('#send_deny_msg').prop("checked", true);
                            $('.deny_msg_span').html('ON');
                            $('.deny_msg_span').css('color', 'var(--nm-dv2-accent)');
                        } else {
                            $('#send_deny_msg').prop("checked", false);
                            $('.deny_msg_span').html('OFF');
                            $('.deny_msg_span').css('color', 'var(--nm-dv2-red)');
                        }
                        if (data.data.image1)
                            $('#image2').html('<img class="zoom" src="/upload/' + data.data.image1 + '" style="width:80px">&nbsp;&nbsp;<a class="del_img_btn" href="javascript:delete_img(`image2`, ' + data.data.sms_detail_idx + ')">삭제</a>');
                        if (data.data.image2)
                            $('#image3').html('<img class="zoom" src="/upload/' + data.data.image2 + '" style="width:80px">&nbsp;&nbsp;<a class="del_img_btn" href="javascript:delete_img(`image3`, ' + data.data.sms_detail_idx + ')">삭제</a>');
                        if (data.data.image)
                            $('#image1').html('<img class="zoom" src="/upload/' + data.data.image + '" style="width:80px">&nbsp;&nbsp;<a class="del_img_btn" href="javascript:delete_img(`image1`, ' + data.data.sms_detail_idx + ')">삭제</a>');
                    }
                });
            }
        });
        return false;
    }

    function delete_img(val, idx) {
        if (confirm("이미지를 삭제 하시겠습니까?")) {
            $.ajax({
                type: "POST",
                url: "/ajax/step_sms_send.php",
                dataType: "json",
                data: {
                    delete_img: true,
                    img: val,
                    sms_detail_idx: idx
                },
                success: function(data) {
                    console.log(data);
                    $("span[id=" + val + "]").html('');
                    alert('이미지가 삭제 되었습니다.');
                }
            })
        }
    }

    function deleteRow(sms_detail_idx, sms_idx) {
        if (confirm('삭제하시겠습니까?')) {
            $.ajax({
                type: "POST",
                url: "mypage.proc.php",
                data: {
                    mode: "sms_detail_del",
                    sms_detail_idx: sms_detail_idx,
                    sms_idx: sms_idx
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
    $(function() {
        $('#saveBtn').on("click", function() {
            if ($('#reservation_title').val() == "") {
                alert('예약메시지 제목을 입력해주세요.');
                return;
            }
            $('#sform').submit();
        });
        $('#cancleBtn').on("click", function() {
            location.href = "mypage_reservation_list.php";
        });
        $(".popbutton4").click(function() {
            <?php   if ($sms_idx) { ?>
                $('.ad_layer4').lightbox_me({
                    centered: true,
                    onLoad: function() {
                        $('#mode').val('step_add');
                        $('#sms_detail_idx').val('');
                        $('#step').val('');
                        $('#day').val('');
                        $('#send_time_hour').val('');
                        $('#send_time_min').val('');
                        $('#title').val('');
                        $('#content').val('');
                        $('#image1').html('');
                        $('#image2').html('');
                        $('#image3').html('');
                    }
                });
            <?php   } else { ?>
                alert("퍼널예약메시지 세트정보를 먼저 입력하시고 저장을 클릭해주세요.");
            <?php   } ?>
        });
        $('#send_day').on("change", function() {
            if ($(this).val() == "0") {
                $('#timeArea').hide();
            } else {
                $('#timeArea').show();
            }
        });
        $('#send_day').on("keyup", function() {
            if ($(this).val() == "0") {
                $('#timeArea').hide();
            } else {
                $('#timeArea').show();
            }
        });
        $('#popSaveBtn').on("click", function() {
            if ($('#step').val() == "") {
                alert('순서를 입력하세요.');
                return;
            }
            if ($('#send_day').val() == "") {
                alert('발송일시를 입력하세요.');
                return;
            }
            //if($('#send_time_hour').val() == "") {
            //    alert('발송일시를 입력하세요.');
            //    return;
            //}	    
            //
            //if($('#send_time_min').val() == "") {
            //    alert('발송일시를 입력하세요.');
            //    return;
            //}	    	    	    

            if ($('#title').val() == "") {
                alert('제목을 입력하세요.');
                return;
            }
            if ($('#content').val() == "") {
                alert('내용을 입력하세요.');
                return;
            }
            $('#addForm').submit();
        });

        $('#popCloseBtn').on("click", function() {
            $('.lb_overlay, .ad_layer4').hide();
            refresh_page();
        });

        $('.popbutton12').on("click", function() {
            <?php   if ($sms_idx) { ?>
                $('#auto_making_modal').modal("show");
            <?php   } else { ?>
                alert("퍼널예약메시지 세트정보를 먼저 입력하시고 저장을 클릭해주세요.");
            <?php   } ?>
        });
        $("#reserv_type").on("change", function() {
            if ($(this).val() == 1) {
                $(".AI").show();
                $("#reserv_file1").hide();
                $("#reserv_file2").hide();
                $("#reserv_file3").hide();
                $(".my_pay").hide();
            } else {
                $(".AI").hide();
                $(".my_pay").show();
            }
        });
    });

    function start_making() {
        var web_type = $('input[name=web_type]:checked').attr('id');
        console.log(web_type);
        if (web_type == undefined) {
            alert('수집분야를 설정 하세요.');
            return;
        }
        switch (web_type) {
            case 'newsid':
                start_making_web('news');
                break;
            case 'blogid':
                start_making_web('blog');
                break;
            case 'youtubeid':
                start_making_web('youtube');
                break;
            default:
                console.log("SELECT type!");
                break;
        }
    }

    //goodhow 크롤링 서버에 요청 보내기, 상태값 얻어 오기
    function start_making_web(type) {
        var slt = 0;
        var url = '';
        var mem_id_status = '';
        var count_interval = 0;
        var blog_link = 0;
        var contents_keyword = '';
        var sms_idx = <?= $sms_idx ?>;
        address = $("#people_web_address").val();
        if ($("#people_contents_start_date").val() != "") {
            start_date = $("#people_contents_start_date").val().replace(/-/g, "");
            end_date = $("#people_contents_end_date").val().replace(/-/g, "");
        }
        contents_cnt = $("#people_contents_cnt").val();
        send_time = $("#send_time").val();
        contents_keyword = $("#people_contents_key").val();
        if (type == 'youtube') {
            if (contents_cnt == "") {
                alert('갯수를 입력하세요.');
                return;
            }
        } else {
            if (contents_cnt == "" || contents_keyword == "") {
                alert('키워드/갯수를 입력하세요.');
                return;
            }
        }

        url = "https://www.goodhow.com/crawler/crawler/ai_step_mms.php";
        if (type == 'youtube') {
            if ((address.substring(0, 26) == "https://www.youtube.com/c/") || (address.substring(0, 32) == "https://www.youtube.com/channel/") || (address.substring(0, 44) == "https://www.youtube.com/results?search_query") || (address.substring(0, 29) == "https://www.youtube.com/user/")) {
                start_date = end_date = "";
                url = "https://www.goodhow.com/crawler/crawler/ai_step_mms.php";
            } else {
                alert("웹주소 형식이 틀립니다. 옳바른 형식으로 이용 해주세요.");
                return;
            }
        }
        console.log(sms_idx, type, address, contents_cnt, send_time, contents_keyword, start_date, end_date);
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {
                sms_idx: sms_idx,
                type: type,
                address: address,
                contents_cnt: contents_cnt,
                send_time: send_time,
                contents_keyword: contents_keyword,
                start_date: start_date,
                end_date: end_date
            },
            url: url,
            success: function(data) {
                console.log(data);
                if (data == 1) {
                    alert("추가되었습니다.");
                    refresh_page();
                } else {
                    alert("진행중 오류가 발생 하였습니다.");
                    refresh_page();
                }
            }
        });
        $("#startmaking").attr('disabled', true);
    }

    function goback() {
        $('#auto_making_modal').modal("hide");
    }

    $('.switch').on("change", function() {
        var mem_code = $(this).find("input[type=checkbox]").val();
        var status = $(this).find("input[type=checkbox]").is(":checked") == true ? "Y" : "N";
        var sms_idx = $(this).find("input[type=checkbox]").data('idx');
        var request_idx = $(this).find("input[type=checkbox]").data('reqidx');

        $.ajax({
            type: "POST",
            url: "/admin/ajax/ai_funnel_status.php",
            data: {
                mode: 'change_aimsgstate_user',
                mem_id: mem_code,
                sms_idx: sms_idx,
                status: status,
                request_idx: request_idx
            },
            success: function(data) {
                //alert('신청되었습니다.');refresh_page();
                //refresh_page();
            }
        })
    });

    //수집분야별에 따르는 설정값 입력창 현시
    function show_keyword(val) {
        if (val == 'news') {
            $("#contents_key").attr('style', 'width:100%;display:inline-table;');
            $("#contents_time").attr('style', 'width:100%;display:inline-table;');
            $("#web_address").attr('style', 'width:100%;display:none;');
            $("#sendtime").attr('style', 'width:100%;display:inline-table;');
        } else if (val == 'blog') {
            alert("특정 블로그에서 키워드와 매칭되는 게시물 크롤링을 원하시면 웹주소입력란에 다음의 블로그 주소를 입력하세요.\n 예시 : https://blog.naver.com/abcd123\n\n웹주소에 입력을 안하면 전체 블로그에서 키워드와 매칭되는 게시물을 크롤링합니다.");
            $("#contents_key").attr('style', 'width:100%;display:inline-table;');
            $("#contents_time").attr('style', 'width:100%;display:inline-table;');
            $("#web_address").attr('style', 'width:100%;display:inline-table;');
            $("#sendtime").attr('style', 'width:100%;display:inline-table;');
        } else if (val == 'youtube') {
            $("#contents_key").attr('style', 'width:100%;display:none;');
            $("#contents_time").attr('style', 'width:100%;display:none;');
            $("#web_address").attr('style', 'width:100%;display:inline-table;');
            $("#sendtime").attr('style', 'width:100%;display:inline-table;');
        }
    }

    function newMessageEvent() { // test 메시지조회
        var win = window.open("../mypage_pop_message_list_for_copylist.php?mode=change", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
    }

    function open_change_page(ori_sms_idx) {
        location.href = "/mypage_reservation_create.php?get_idx=" + ori_sms_idx;
    }
    //gpt chat script
    var contextarray = [];

    function show_chat(api) {
        $("#gpt_chat_modal").modal('show');
    }
    $(document).ready(function() {
        var textarea = document.getElementById("question");
        var limit = 110; //height limit
        var api_state = '<?= $member_1['gpt_chat_api_key'] ?>';

        textarea.oninput = function() {
            textarea.style.height = "";
            textarea.style.height = Math.min(textarea.scrollHeight, limit) + "px";
        };

        $("#question").on('keydown', function(event) {
            if (api_state == '') {
                alert("회원정보에서 본인의 API 키를 입력해주세요.");
                location.href = "mypage.php";
            }
            if (event.keyCode == 13) {
                if (event.shiftKey) {
                    $("#kw-target").html($("#kw-target").html() + "\n");
                    event.stopPropagation();
                } else {
                    send_post('<?= $_SESSION['iam_member_id'] ?>');
                }
            }
        });
    });

    function check_login(id) {
        if (id == '') {
            $("#intro_modal").modal('show');
        } else {
            return;
        }
    }

    function show_new_chat() {
        $("#answer_side").hide();
        $("#gpt_req_list_title").hide();
        $("#answer_side1").show();
        $("#answer_side2").hide();
    }

    function show(val) {
        if ($('li[id=a' + val + ']').hasClass('hided')) {
            $('li[id=a' + val + ']').removeClass('hided');
            $('i[id=down' + val + ']').css('display', 'none');
            $('i[id=up' + val + ']').css('display', 'inline-block');
        } else {
            $('li[id=a' + val + ']').addClass('hided');
            $('i[id=down' + val + ']').css('display', 'inline-block');
            $('i[id=up' + val + ']').css('display', 'none');
        }
    }

    function show_req_history() {
        $.ajax({
            type: "POST",
            url: "/iam/ajax/manage_gpt_chat.php",
            data: {
                mem_id: "<?= $_SESSION['iam_member_id'] ?>",
                method: 'show_req_list'
            },
            dataType: 'html',
            success: function(data) {
                // console.log(data);
                $("#answer_side").hide();
                $("#answer_side1").hide();
                $("#gpt_req_list_title").show();
                $("#answer_side2").html(data);
                $("#answer_side2").show();
            }
        });
    }

    function copy_msg() {
        var value = $("#answer_side").text().trim();
        console.log(value.trim());
        // return;
        var aux1 = document.createElement("input");
        // 지정된 요소의 값을 할당 한다.
        aux1.setAttribute("value", value);
        // bdy에 추가한다.
        document.body.appendChild(aux1);
        // 지정된 내용을 강조한다.
        aux1.select();
        // 텍스트를 카피 하는 변수를 생성
        document.execCommand("copy");
        // body 로 부터 다시 반환 한다.
        document.body.removeChild(aux1);
        alert("복사되었습니다. 원하는 곳에 붙여 넣으세요.");
    }

    function del_list(id) {
        $.ajax({
            type: "POST",
            url: "/iam/ajax/manage_gpt_chat.php",
            data: {
                method: 'del_req_list',
                id: id
            },
            dataType: 'json',
            success: function(data) {
                if (data.result == "1") {
                    alert('삭제 되었습니다.');
                    show_req_history();
                } else {
                    alert('삭제실패 되었습니다.');
                }
            }
        });
    }

    function articlewrapper(question, answer, str) {
        $("#answer_side").html('<li class="article-title" id="q' + answer + '"><img src="/iam/img/chat_Q.png" style="width:30px;margin-right: 10px;"><span class="chat_title"></span></li>');
        let str_ = ''
        let i = 0
        let timer = setInterval(() => {
            if (str_.length < question.length) {
                str_ += question[i++]
                $("#q" + answer).children('span').text(str_ + '_') //인쇄할 때 커서 추가
            } else {
                clearInterval(timer)
                $("#q" + answer).children('span').text(str_) //인쇄할 때 커서 추가
            }
        }, 5)
        $("#answer_side").append('<li class="article-content" id="' + answer + '"><img src="/iam/img/chat_A.png" style="width:30px;"><span class="chat_answer" style="margin-left: 35px;"></span></li>');
        if (str == null || str == "") {
            str = "서버가 응답하는 데 시간이 걸리면 나중에 다시 시도할 수 있습니다.";
        }
        let str2_ = ''
        let i2 = 0
        let timer2 = setInterval(() => {
            if (str2_.length < str.length) {
                str2_ += str[i2++]
                $("#" + answer).children('span').text(str2_ + '_') //인쇄할 때 커서 추가
            } else {
                clearInterval(timer2)
                $("#" + answer).children('span').text(str2_) //인쇄할 때 커서 추가

            }

            $('#answer_side').animate({
                scrollTop: 10000,
            }, 10);
        }, 25)
    }

    // function send_post(mem_id) {
    //     $("#answer_side1").hide();
    //     $("#answer_side2").hide();
    //     $("#answer_side").show();
    //     $("#ajax-loading").show();
    //     var prompt = $("#question").val();
    //     if (prompt == "") {
    //         alert('질문을 입력해 주세요.');
    //         return;
    //     }

    //     $.ajax({
    //         cache: true,
    //         type: "POST",
    //         url: "/iam/ajax/message.php",
    //         data: {
    //             mem_id:mem_id,
    //             message: prompt,
    //             context:$("#keep").prop("checked")?JSON.stringify(contextarray):'[]',
    //         },
    //         dataType: "json",
    //         success: function (results) {
    //             $("#ajax-loading").hide();
    //             $("#question").val("");
    //             $("#question").css("height", "58px");
    //             // $(".send_ask").css("height", "98%");
    //             contextarray.push([prompt, results.raw_message]);
    //             articlewrapper(prompt,randomString(16),results.raw_message);
    //         }
    //     });
    // }

    function randomString(len) {
        len = len || 32;
        var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678'; /****혼란스러운 문자는 기본적으로 제거됩니다oOLl,9gq,Vv,Uu,I1****/
        var maxPos = $chars.length;
        var pwd = '';
        for (i = 0; i < len; i++) {
            pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
        }
        return pwd;
    }

    function send_chat() {
        var title = $("#answer_side span.chat_title").text();
        var detail = $("#answer_side span.chat_answer").text();
        if (title == "") {
            alert('질문해주세요.');
            return;
        }
        $("#title").val(title);
        $("#content").val(detail);
        $("#gpt_chat_modal").modal('hide');
    } //gpt chat
    function add_file_tab() {
        if ($("#reserv_file1").css('display') == "none")
            $("#reserv_file1").css('display', 'show');
        else if ($("#reserv_file2").css('display') == "none")
            $("#reserv_file2").css('display', 'show');
        else if ($("#reserv_file3").css('display') == "none")
            $("#reserv_file3").css('display', 'show');
    }

    function del_file_tab(idx) {
        if (idx == 3) {
            $("#reserv_file3").css('display', 'none');
            $("#ai_file3").val('');
            $("#ai_file3_txt").val('');
        } else if (idx == 2) {
            if ($("#reserv_file3").css('display') == "none") {
                $("#reserv_file2").css('display', 'none');
                $("#ai_file2").val('');
                $("#ai_file2_txt").val('');
            } else {
                if ($("#ai_file3")[0].files.length > 0) {
                    var file = $("#ai_file3")[0].files[0];
                    var dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    $("#ai_file2")[0].files = dataTransfer.files;
                }
                $("#ai_file2_txt").val($("#ai_file3_txt").val());
                $("#ai_file3_txt").val('');
                $("#ai_file3").val('');
                $("#reserv_file3").css('display', 'none');
            }
        } else {
            if ($("#reserv_file2").css('display') == "none") {
                $("#reserv_file1").css('display', 'none');
                $("#ai_file1").val('');
                $("#ai_file1_txt").val('');
            } else {
                if ($("#reserv_file3").css('display') == "none") {
                    if ($("#ai_file2")[0].files.length > 0) {
                        var file = $("#ai_file2")[0].files[0];
                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        $("#ai_file1")[0].files = dataTransfer.files;
                    }
                    $("#ai_file1_txt").val($("#ai_file2_txt").val());
                    $("#ai_file2_txt").val('');
                    $("#ai_file2").val('');
                    $("#reserv_file2").css('display', 'none');
                } else {
                    if ($("#ai_file2")[0].files.length > 0) {
                        var file = $("#ai_file2")[0].files[0];
                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        $("#ai_file1")[0].files = dataTransfer.files;
                    }
                    if ($("#ai_file3")[0].files.length > 0) {
                        var file = $("#ai_file3")[0].files[0];
                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        $("#ai_file2")[0].files = dataTransfer.files;
                    }
                    $("#ai_file1_txt").val($("#ai_file2_txt").val());
                    $("#ai_file2_txt").val($("#ai_file3_txt").val());
                    $("#ai_file3_txt").val('');
                    $("#ai_file3").val('');
                    $("#reserv_file3").css('display', 'none');
                }
            }
        }
    }
</script>
<?php  
include_once "_foot.php";
?>