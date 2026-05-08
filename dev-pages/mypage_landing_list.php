<?php   
$path = "./";
include_once "_head_v01.php";
if (!$_SESSION['one_member_id']) {

?>
	<script language="javascript">
		location.replace('/ma.php');
	</script>
<?php  
	exit;
}
$sql = "SELECT * FROM Gn_Member  WHERE mem_id='{$_SESSION['one_member_id']}' AND site != ''";
$sresul_num = mysqli_query($self_con, $sql);
$data = mysqli_fetch_array($sresul_num);

if ($data['intro_message'] == "") {
	$data['intro_message'] = "안녕하세요\n
									\n
									귀하의 휴대폰으로\n
									기부문자발송을 시작합니다.\n
									\n
									협조해주셔서 감사합니다^^
									";
}
$dir = ($_REQUEST['order_status'] ?? '') === 'desc' ? 'asc' : 'desc';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<script>
	function copyHtml() {
		var trb = $.trim($('#sHtml').html());
		var IE = (document.all) ? true : false;
		if (IE) {
			if (confirm("이 링크를 복사하시겠습니까?")) {
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
		})

	});
</script>
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 인포랜딩페이지 관리
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

/* ── 툴팁 팝업 (랜딩전송, 아이프레임소스) ── */
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

/* ── sort-by 화살표 색상 ── */
a.sort-by:before { border-bottom-color: var(--nm-dv2-muted) !important; }
a.sort-by:after  { border-top-color: var(--nm-dv2-muted) !important; }

/* ── switch 토글 ── */
.switch .slider {
    background-color: var(--nm-dv2-card2);
}
.switch input:checked + .slider {
    background-color: var(--nm-dv2-green);
}

/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 11px; }
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
            <span class="page-hero-badge">인포랜딩</span>
            <h1 class="page-hero-title">인포랜딩페이지 <em>관리</em></h1>
            <p class="page-hero-desc">자신의 이벤트 상품이나 서비스를 소개하는 랜딩페이지를 관리합니다.</p>
        </div>
        <div class="page-hero-actions">
            <a class="page-hero__btn page-hero__btn--secondary" href="mypage_landing_write.php">+ 랜딩생성</a>
        </div>
    </div>
</div>
<div class="m_body">
				<form name="pay_form" action="" method="post" class="my_pay">
					<input type="hidden" name="page" value="<?= $page ?>" />
					<input type="hidden" name="page2" value="<?= $page2 ?>" />
					<div class="a1" style="margin-top:50px; margin-bottom:15px">
						<div class="popup_holder popup_text"> 인포랜딩페이지 관리
							<div class="popupbox" style="display:none; height: 75px;width: 250px;color: black;left: 70px;top: -37px;">자신의 이벤트 상품이나 서비스를 소개하거나 상세페이지로 만든 랜딩페이지를 리스트로 보는 기능입니다<br><br>
								<a class="detail_view" style="color: blue;" href="https://tinyurl.com/2m653ssk" target="_blank">[자세히 보기]</a>
							</div>
						</div>
						<p style="clear:both"></p>
					</div>
					<div class="container">
						<div class="p1">

							<select name="search_key" class="select">
								<option value="">전체</option>
							</select>
							<input type="text" name="search_text" placeholder="" id="search_text" value="<?= $_REQUEST['search_text'] ?>" />
							<a href="javascript:void(0)" onclick="pay_form.submit()"><img src="images/sub_mypage_11.jpg" /></a>
							<div style="text-align:right;margin-top:0px;float:right;display: flex;">
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="랜딩전송" class="button" onclick="send_landlink()" style="cursor: pointer">
									<input type="hidden" name="send_landlink_idx" id="send_landlink_idx" value="">
								</div>
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="랜딩생성" class="button" onclick="location='mypage_landing_write.php'">
									<div class="popupbox" style="display:none; width: 180px; bottom: -115px; display:none;">자신의 이벤트 상품이나 서비스를 소개하거나 상세페이지로 보여줄수 있도록 제작하는 기능입니다.<br><br><!--Child-->
										<a class="detail_view" style="color: blue;" href="https://tinyurl.com/5xsndmhh" target="_blank">[자세히 보기]</a>
									</div>
								</div>
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="선택삭제" class="button" onclick="deleteMultiRow()" style="cursor: pointer">
								</div>
							</div>
						</div>
						<div style="overflow-x:auto;">
							<table class="list_table" width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="width:2%;"><input type="checkbox" name="allChk" id="allChk"></td>
									<td style="width:4%;">No</td>
									<td style="width:15%;"><a href="?order_name=title&order_status=<?= $dir ?>" class="sort-by">제목</a></td>
									<td style="width:15%;"><a href="?order_name=description&order_status=<?= $dir ?>" class="sort-by">인포랜딩설명</a></td>
									<td style="width:15%;">미리보기</td>
									<td style="width:10%;">아이프레임</td>
									<td style="width:6%"><a href="?order_name=cnt&order_status=<?= $dir ?>" class="sort-by">댓글수</a></td>
									<td style="width:6%"><a href="?order_name=read_cnt&order_status=<?= $dir ?>" class="sort-by">조회수</a></td>
									<td style="width:8%;"><a href="?order_name=regdate&order_status=<?= $dir ?>" class="sort-by">작성일</a></td>
									<td style="width:9%;">수정/삭제</td>
									<td style="width:9%;">노출/중지</td>
								</tr>
								<?php  

								$sql_serch = " m_id ='{$_SESSION['one_member_id']}' ";
								// [보안패치] search_date 컬럼명 화이트리스트
								$_sd_allowed = ['regdate','reg_date','up_date','landing_date'];
								$_safe_search_date = in_array($_REQUEST['search_date'] ?? '', $_sd_allowed) ? $_REQUEST['search_date'] : '';
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

								if ($_REQUEST['search_text']) {
									$search_text = $_REQUEST['search_text'];
									$sql_serch .= " AND ( title like '%{$search_text}%' or description like '%{$search_text}%')";
								}

								$sql = "SELECT count(landing_idx) as cnt FROM Gn_landing WHERE $sql_serch ";
								$result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
								$row = mysqli_fetch_array($result);
								$intRowCount = $row['cnt'];
								if ($intRowCount) {
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
									$_on_allowed = ['title','description','cnt','read_cnt','regdate','landing_idx'];
									$order_name = in_array($_REQUEST['order_name']??'', $_on_allowed) ? $_REQUEST['order_name'] : 'regdate';
									$intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
									$sql = "SELECT * FROM Gn_landing WHERE $sql_serch order by $order_name $order_status limit $int,$intPageSize";
									$result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
									while ($row = mysqli_fetch_array($result)) { ?>
										<tr>
											<td><input type="checkbox" class="check" name="land_idx" value="<?= $row['landing_idx']; ?>"></td>
											<td><?= $sort_no ?></td>
											<td style="font-size:12px;"><?= $row['title'] ?></td>
											<td style="font-size:12px;"><?= $row['description'] ?></td>
											<td style="font-size:12px;">
												<input type="button" value="미리보기" class="button" onclick="viewEvent('<?php   echo $row['short_url'] ?>')">
												<input type="button" value="링크복사" class="button copyLinkBtn" data-link="<?php   echo $row['short_url'] ?>">
											</td>
											<td style="font-size:12px;">
												<input type="button" value="소스보기" class="button" onclick="viewIframeSourceWindow('<?php   echo $row['landing_idx'] ?>')">
											</td>
											<td><?= number_format($row['cnt']) ?></td>
											<td><?= number_format($row['read_cnt']) ?></td>
											<td><?= $row['regdate'] ?></td>
											<td>
												<a href='mypage_landing_write.php?landing_idx=<?php   echo $row['landing_idx']; ?>'>수정</a>/<a href="javascript:;;" onclick="removeRow('<?php   echo $row['landing_idx']; ?>')">삭제</a>
											</td>
											<td>
												<label class="switch">
													<input type="checkbox" name="status" id="stauts_<?php   echo $row['landing_idx']; ?>" value="<?php   echo $row['landing_idx']; ?>" <?php   echo $row['status_yn'] == "Y" ? "checked" : "" ?>>
													<span class="slider round" name="status_round" id="stauts_round_<?php   echo $row['landing_idx']; ?>"></span>
												</label>
											</td>

										</tr>
									<?php  
										$sort_no--;
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
							<!--
            <input type="button" value="랜딩 페이지 삭제" class="button">
            -->
						</div>
					</div>
				</form>
				</div><!-- .mrl-wrap -->
			</div>
		</div>
		<span class="tooltiptext-bottom" id="tooltiptext_card_edit" style="display:none;">
			<p class="title_app">랜딩전송</p>
			<table class="table table-bordered" style="width: 97%;">
				<tbody>
					<tr class="hide_spec">
						<td class="bold" id="remain_count" data-num="" style="width:70px;padding:5px;">전송<br>
							<textarea name="land_send_id_count" id="land_send_id_count" style="width:90%; height:50px;color: red;font-size: 12px" readonly="" data-num="0" placeholder="0건"></textarea>
						</td>
						<td colspan="2" style="padding:5px;">
							<div>
								<textarea name="land_send_id" id="land_send_id" style="border: solid 1px #b5b5b5;width:97%; height:100px;" data-num="0" placeholder="전송할 아이디를 입력하세요.<컴마로 구분>"></textarea>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="button_app">
				<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-card2);border-radius: 3px;padding: 5px;color: var(--nm-dv2-text);border: 1px solid var(--nm-dv2-border);">취소하기</a>
				<a href="javascript:send_land_link()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 3px;color: #fff;padding: 5px;font-weight: var(--fw-bold);">전송하기</a>
			</div>
		</span>
		<span class="tooltiptext-bottom" id="modal_iframe" style="display:none;">
			<div>
				<p class="title_app">아이프레임 소스 <span onclick="closeIframeSourceWindow()" style="float:right;cursor:pointer;color:#000;font-weight:var(--fw-bold);font-size:var(--fz-modal-close, 24px);">X</span></p>
			</div>
			<div style="padding: 20px;width:90%">
				<p style="margin-bottom: 20px" id="txt_source"></p>
				<div style="text-align: center;">
					<a href="javascript:void(0)" onclick="copyIframSource()" style="padding: 10px 25px;color:#000;background-color:var(--nm-dv2-green);font-weight:var(--fw-bold);border-radius:6px;">복사</a>
				</div>
			</div>
		</span>

		<div id="tutorial-loading"></div>
	</div>
</div>

<Script>
	$(function() {
		$('.copyLinkBtn').bind("click", function() {
			var trb = $(this).data("link");
			// 글을 쓸 수 있는 란을 만든다.
			var aux = document.createElement("input");
			// 지정된 요소의 값을 할당 한다.
			aux.setAttribute("value", trb);
			// bdy에 추가한다.
			document.body.appendChild(aux);
			// 지정된 내용을 강조한다.
			aux.select();
			// 텍스트를 카피 하는 변수를 생성
			document.execCommand("copy");
			// body 로 부터 다시 반환 한다.
			document.body.removeChild(aux);
			alert("URL이 복사되었습니다. 원하는 곳에 붙여 넣으세요.");
		});
		$('.switch').on("change", function() {
			var no = $(this).find("input[type=checkbox]").val();
			var status = $(this).find("input[type=checkbox]").is(":checked") == true ? "Y" : "N";
			$.ajax({
				type: "POST",
				url: "mypage.proc.php",
				data: {
					mode: "land_updat_status",
					landing_idx: no,
					status: status,
				},
				success: function(data) {
					//alert('신청되었습니다.');refresh_page();
				}
			})

			//console.log($(this).find("input[type=checkbox]").is(":checked"));
			//console.log($(this).find("input[type=checkbox]").val());
		});
		$('#allChk').on("change", function() {
			$('.check').prop("checked", $(this).is(":checked"));
		});
	})

	function viewEvent(str) {
		window.open(str, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");

	}

	function viewIframeSourceWindow(str) {
		var link = "https://" + "<?= $_SERVER['HTTP_HOST'] ?>" + "/event/event.html?landing_idx=" + str;
		var frameSource = '<iframe src="' + link + '" width="100%" frameborder="0"></iframe>';
		// var IE=(document.all)?true:false;
		// if (IE) {
		//     if(confirm("이 링크를 복사하시겠습니까?")) {
		//         window.clipboardData.setData("Text", frameSource);
		//     } 
		// } else {
		//         temp = prompt("Ctrl+C를 눌러 클립보드로 복사하세요", frameSource);
		// }
		$("#txt_source").text(frameSource);
		$("#modal_iframe").show();
	}

	function closeIframeSourceWindow() {
		$("#modal_iframe").hide();
	}

	function copyIframSource() {
		var trb = $("#txt_source").text();
		// 글을 쓸 수 있는 란을 만든다.
		var aux = document.createElement("input");
		// 지정된 요소의 값을 할당 한다.
		aux.setAttribute("value", trb);
		// bdy에 추가한다.
		document.body.appendChild(aux);
		// 지정된 내용을 강조한다.
		aux.select();
		// 텍스트를 카피 하는 변수를 생성
		document.execCommand("copy");
		// body 로 부터 다시 반환 한다.
		document.body.removeChild(aux);
		$("#modal_iframe").hide();
	}

	function change_message(form) {
		if (form.intro_message.value == "") {
			alert('정보를 입력해주세요.');
			form.intro_message.focus();
			return false;
		}

		$.ajax({
			type: "POST",
			url: "ajax/ajax.php",
			data: {
				mode: "intro_message",
				intro_message: form.intro_message.value
			},
			success: function(data) {
				$("#ajax_div").html(data);
				alert('저장되었습니다.');
			}
		});
		return false;
	}

	function showInfo() {
		if ($('#outLayer').css("display") == "none") {
			$('#outLayer').show();
		} else {
			$('#outLayer').hide();
		}
	}
</Script>


<script>
	//회원가입체크
	function join_check(frm, modify) {
		if (!wrestSubmit(frm))
			return false;
		var id_str = "";
		var app_pwd = "";
		var web_pwd = "";
		var phone_str = "";
		if (document.getElementsByName('pwd')[0])
			app_pwd = document.getElementsByName('pwd')[0].value;
		if (document.getElementsByName('pwd')[1])
			web_pwd = document.getElementsByName('pwd')[1].value;
		if (frm.id)
			id_str = frm.id.value;
		var msg = modify ? "수정하시겠습니까?" : "등록하시겠습니까?";
		var email_str = frm.email_1.value + "@" + frm.email_2.value + frm.email_3.value;
		if (!modify)
			phone_str = frm.mobile_1.value + "-" + frm.mobile_2.value + "-" + frm.mobile_3.value;
		var birth_str = frm.birth_1.value + "-" + frm.birth_2.value + "-" + frm.birth_3.value;
		var is_message_str = frm.is_message.checked ? "Y" : "N";

		var bank_name = frm.bank_name.value;
		var bank_account = frm.bank_account.value;
		var bank_owner = frm.bank_owner.value;

		if (confirm(msg)) {
			$.ajax({
				type: "POST",
				url: "ajax/ajax.php",
				data: {
					join_id: id_str,
					join_nick: frm.nick.value,
					join_pwd: app_pwd,
					join_web_pwd: web_pwd,
					join_name: frm.name.value,
					join_email: email_str,
					join_phone: phone_str,
					join_add1: frm.add1.value,
					join_zy: frm.zy.value,
					join_birth: birth_str,
					join_is_message: is_message_str,
					join_modify: modify,
					bank_name: bank_name,
					bank_account: bank_account,
					bank_owner: bank_owner
				},
				success: function(data) {
					$("#ajax_div").html(data)
				}
			})
		}
	}

	function monthly_remove(no) {
		if (confirm('정기결제 해지신청하시겠습니까?')) {
			$.ajax({
				type: "POST",
				url: "ajax/ajax_add.php",
				data: {
					mode: "monthly",
					no: no
				},
				success: function(data) {
					alert('신청되었습니다.');
					refresh_page();
				}
			})

		}
	}

	function removeRow(no) {
		if (confirm('삭제하시겠습니까?')) {
			$.ajax({
				type: "POST",
				url: "mypage.proc.php",
				data: {
					mode: "land_del",
					landing_idx: no
				},
				success: function(data) {
					alert('삭제되었습니다.');
					refresh_page();
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
					delete_name: "mypage_landing_list",
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
					delete_name: "mypage_landing_list",
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

	function send_landlink() {
		var idx_arr = new Array();
		$('input[name=land_idx]').each(function() {
			if ($(this).is(":checked") == true) {
				idx_arr.push($(this).val());
			}
		});
		$("#send_landlink_idx").val(idx_arr.join(","));

		if (idx_arr.length == 0) {
			alert("전송할 페이지를 선택하세요.");
			return;
		}

		$("#tooltiptext_card_edit").show();
		$("#tutorial-loading").show();
	}

	function cancel_set() {
		$("#tooltiptext_card_edit").hide();
		$("#tutorial-loading").hide();
	}

	function send_land_link() {
		var send_ids = $("#land_send_id").val();
		var land_idx = $("#send_landlink_idx").val();

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
				land_idx: land_idx,
				type: "landlink"
			},
			success: function(data) {
				console.log(data);
				alert("전송되었습니다.");
				refresh_page();
			}
		});
	}
	$("#land_send_id").keyup(function() {
		point = $(this).val();
		var arr = point.split(",");
		cnt = arr.length;
		if (point.indexOf(",") == -1 && point == "") {
			cnt = 0;
		}
		$("#land_send_id_count").val(cnt + "건");
		$('#land_send_id_count').data('num', cnt);
	});
</script>
<?php  
include_once "_foot.php";
?>