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
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 데일리발송 세트리스트
   ============================================ */

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

.mrl-wrap { max-width: 1400px; width: 100%; margin: 0 auto; padding: 20px 20px 80px; box-sizing: border-box; }
.big_div  { background: var(--nm-dv2-bg); color: var(--nm-dv2-text); min-height: 100vh; }
.big_sub  { background: var(--nm-dv2-bg); }
.m_div    { background: var(--nm-dv2-bg); display: flex; align-items: flex-start; max-width: 1400px; margin: 0 auto; }
.m_body   { background: var(--nm-dv2-bg); padding: 16px 0; }

li { list-style: none; }

.mypage_left_menu, .left_menu, #left_menu { background: var(--nm-dv2-card) !important; border-color: var(--nm-dv2-border) !important; }
.mypage_left_menu a, .left_menu a, #left_menu a { color: var(--nm-dv2-muted) !important; }
.mypage_left_menu a:hover, .left_menu a:hover, #left_menu a:hover, .mypage_left_menu a.active, .left_menu a.on { color: var(--nm-dv2-green) !important; background: rgba(130,200,54,0.10) !important; }

.a1 { color: var(--nm-dv2-text) !important; font-size: var(--fz-step, 17px); font-weight: var(--fw-bold, 700); padding: 4px 0 12px; }

.p1 { background: var(--nm-dv2-card); border: 1px solid var(--nm-dv2-border); border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.p1 select, .p1 input[type=text], select.select, input[type=text] { background: var(--nm-dv2-input-bg) !important; color: var(--nm-dv2-text) !important; border: 1px solid var(--nm-dv2-border) !important; border-radius: 6px; padding: 6px 10px; font-size: var(--fz-input, 14px); transition: border-color .2s; }
.p1 input[type=text]:focus, input[type=text]:focus, textarea:focus { border-color: var(--nm-dv2-green) !important; outline: none; box-shadow: 0 0 0 2px rgba(130,200,54,0.18); }

.button, input[type=button].button { background: var(--nm-dv2-card2) !important; color: var(--nm-dv2-text) !important; border: 1px solid var(--nm-dv2-border) !important; border-radius: 6px; padding: 6px 14px; font-size: var(--fz-label, 14px); font-weight: var(--fw-semi, 600); cursor: pointer; transition: background .18s, border-color .18s, color .18s; }
.button:hover, input[type=button].button:hover { background: var(--nm-dv2-green) !important; color: #000 !important; border-color: var(--nm-dv2-green) !important; }

label { color: var(--nm-dv2-muted); font-size: var(--fz-label, 14px); }

.list_table { border-collapse: collapse; width: 100%; font-size: var(--fz-small, 13px); }
.list_table tr, .list_table td, .list_table th { background: var(--nm-dv2-card) !important; color: var(--nm-dv2-text) !important; border: 1px solid var(--nm-dv2-border) !important; padding: 7px 8px; vertical-align: middle; }
.list_table tr:first-child td, .list_table thead th, .list_table tr:first-child th { background: var(--nm-dv2-card2) !important; color: var(--nm-dv2-green) !important; font-weight: var(--fw-bold, 700); }
.list_table tr:hover td { background: var(--nm-dv2-hover) !important; }
.list_table a { color: var(--nm-dv2-accent); text-decoration: none; }
.list_table a:hover { color: var(--nm-dv2-green); text-decoration: underline; }

.page_div a, .page_f a, .page_f span { color: var(--nm-dv2-muted) !important; background: var(--nm-dv2-card2) !important; border: 1px solid var(--nm-dv2-border) !important; border-radius: 4px; padding: 3px 8px; font-size: var(--fz-label, 14px); }
.page_div a:hover, .page_div a.on, .page_f a:hover, .page_f span.on { color: var(--nm-dv2-green) !important; border-color: var(--nm-dv2-green) !important; }

.popupbox { background: var(--nm-dv2-card) !important; color: var(--nm-dv2-text) !important; border: 1px solid var(--nm-dv2-border) !important; border-radius: 8px; box-shadow: var(--nm-dv2-shadow); }
.pop_right { position: relative; right: 2px; display: inline; margin-bottom: 6px; width: 5px; }

.page-hero-title     { color: #f0f4ff !important; }
.page-hero-title em  { color: #82c836 !important; }
.page-hero-desc      { color: rgba(255,255,255,0.52) !important; }
.page-hero__btn--secondary { color: rgba(255,255,255,0.85) !important; background: rgba(255,255,255,0.07) !important; border: 1px solid rgba(255,255,255,0.22) !important; }
.page-hero__btn--primary   { color: #0d1f0d !important; background: #82c836 !important; }

@media (max-width: 768px) { .p1 { flex-direction: column; align-items: stretch; } .mrl-wrap { padding: 10px 8px 60px; } .list_table { font-size: 11px; } }
</style>
<div class="big_div">
	<div class="big_sub">
		<div class="m_div">
			<?php   include "mypage_left_menu.php"; ?>
			<div class="mrl-wrap" style="flex:1;min-width:0;">
				<div class="page-hero page-hero--action" style="margin-bottom:20px;">
					<div class="page-hero-inner">
						<div class="page-hero-content">
							<span class="page-hero-badge">데일리발송</span>
							<h1 class="page-hero-title">데일리발송 <em>세트리스트</em></h1>
							<p class="page-hero-desc">디비를 매일 나누어 자동 발송하는 솔루션입니다.</p>
						</div>
						<div class="page-hero-actions">
							<a class="page-hero__btn page-hero__btn--secondary" href="daily_write.php">+ 메시지세트 등록</a>
						</div>
					</div>
				</div>
				<div class="m_body">
				<form name="pay_form" action="" method="post" class="my_pay">
					<input type="hidden" name="page" value="<?= $page ?>" />
					<input type="hidden" name="page2" value="<?= $page2 ?>" />
					<div class="a1" style="margin-top:50px; margin-bottom:15px">
						<li style="float:left;">
							<div class="popup_holder popup_text">데일리발송 세트리스트
								<div class="popupbox" style="display:none;height: 75px;width: 220px;left: 200px;top: -37px;">디비를 매일 발송가능한 숫자로 나누어 매일 발송할 수 있도록 자동화한 문자발송 솔루션입니다.<br><br>
									<a class="detail_view" style="color: var(--nm-dv2-green);" href="https://tinyurl.com/5ey7er6r" target="_blank">[자세히 보기]</a>

								</div>
							</div>
						</li>
						<li style="float:right;"></li>
						<p style="clear:both"></p>
					</div>
					<div>
						<div class="p1">
							<select name="search_key" class="select">
								<option value="all" <?= $_REQUEST['search_key'] == "all" ? "selected" : "" ?>>전체</option>
								<option value="step" <?= $_REQUEST['search_key'] == "step" ? "selected" : "" ?>>데일리퍼널</option>
							</select>
							<input type="text" name="search_text" placeholder="" id="search_text" value="<?= $_REQUEST['search_text'] ?>" />
							<a href="javascript:void(0)" onclick="pay_form.submit()"><img src="images/sub_mypage_11.jpg" /></a>
							<div style="float:right;display: flex;">
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="데일리 발신내역" class="button" onclick="go_sendlist()" style="cursor: pointer">
								</div>
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="메시지세트 등록하기" class="button" onclick="location='daily_write.php'">
									<div class="popupbox" style="display:none; height: 50px;width: 160px;bottom: 37px;">클릭하면 데일리 메시지 세트를 만들 수 있습니다.<br><!--Child-->
										<a class="detail_view" href="https://tinyurl.com/4ktekrcd" target="_blank">[자세히 보기]</a>
									</div>
								</div>
								<div class="popup_holder"> <!--Parent-->
									<input type="button" value="선택삭제" class="button" onclick="deleteMultiRow()" style="cursor: pointer">
								</div>
							</div>
						</div>

						<div>
							<table class="list_table" width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td style="width:2%;"><input type="checkbox" name="allChk" id="allChk"></td>
									<td style="width:6%;">No</td>
									<td style="width:10%;">메시지제목</td>
									<td style="width:8%;">발송폰번호</td>
									<td style="width:10%;">주소록이름</td>
									<td style="width:6%">주소건수</td>
									<td style="width:8%">발송일수</td>
									<td style="width:8%">일발송량</td>
									<td style="width:8%">발송시작일</td>
									<td style="width:8%">발송마감일</td>
									<td style="width:9%;">등록일</td>
									<!--<td style="width:9%;">상태</td>-->
									<td style="width:9%;">관리</td>

								</tr>
								<?php  

								$sql_serch = " mem_id ='{$_SESSION['one_member_id']}' ";
								if ($_REQUEST['search_text']) {

									$search_text = mysqli_real_escape_string($self_con, $_REQUEST['search_text'] ?? ''); // [보안패치]
									$sql_serch .= " AND title like '%{$search_text}%' ";
								}

								if ($_REQUEST['search_key'] == "step") {
									$sql_serch .= " AND step_sms_idx!=0 ";
								}

								$sql = "SELECT count(gd_id) as cnt FROM Gn_daily WHERE $sql_serch ";
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
									$_on_allowed = ['gd_id','title','group_idx','reg_date','up_date'];
									$order_name = in_array($_REQUEST['order_name']??'', $_on_allowed) ? $_REQUEST['order_name'] : 'gd_id';

									$intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
									$sql = "SELECT * FROM Gn_daily WHERE $sql_serch order by $order_name $order_status limit $int,$intPageSize";
									$result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
								?>
									<?php  
									while ($row = mysqli_fetch_array($result)) {
										$sql = "SELECT * FROM Gn_MMS_Group WHERE idx='{$row['group_idx']}'";
										$sresult = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
										$krow = mysqli_fetch_array($sresult);

										$sql = "SELECT count(*) cnt FROM Gn_daily_date WHERE gd_id='{$row['gd_id']}'";
										$sresult = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
										$srow = mysqli_fetch_array($sresult);

									?>
										<tr>
											<td><input type="checkbox" class="check" name="gd_id" value="<?= $row['gd_id']; ?>"></td>
											<td><?= $sort_no ?></td>

											<td style="font-size:12px;"><?= $row['title'] ?></td>
											<td style="font-size:12px;"><?= $row['send_num'] ?></td>
											<td style="font-size:12px;"><?= $krow['grp'] ?></td>
											<td style="font-size:12px;"><?= $row['total_count'] ?></td>
											<td style="font-size:12px;"><?= $srow['cnt'] ?></td>
											<td style="font-size:12px;"><?= $row['daily_cnt'] ?></td>
											<td style="font-size:12px;"><?= $row['start_date'] ?></td>
											<td style="font-size:12px;"><?= $row['end_date'] ?></td>

											<td><?= $row['reg_date'] ?></td>
											<!--<td><?= $row['status'] ?></td>-->
											<td>
												<a href='daily_write.php?gd_id=<?php   echo $row['gd_id']; ?>'>수정</a>/<a href="javascript:;;" onclick="deleteRow('<?php   echo $row['gd_id']; ?>')">삭제</a>
											</td>
										</tr>
									<?php  
										$sort_no--;
									}
									?>
									<tr>
										<td colspan="13">
											<?php  
											page_f($page, $page2, $intPageCount, "pay_form");
											?>
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
							<!--
            <input type="button" value="예약 문자 보내기" class="button">
            -->
						</div>
					</div>
				</form>
				</div>
				</div><!-- .mrl-wrap -->
			</div>
		</div>
	</div>

<Script>
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
		})
	});

	function copyHtml(url) {
		var IE = (document.all) ? true : false;
		if (IE) {
			if (confirm("이 소스코드를 복사하시겠습니까?")) {
				window.clipboardData.setData("Text", url);
			}
		} else {
			temp = prompt("Ctrl+C를 눌러 클립보드로 복사하세요", url);
		}
	}

	function go_sendlist() {
		location.href = "sub_4_return_.php?chanel=4";
	}

	function newpop(str) {
		window.open(str, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");

	}
	$('#allChk').on("change", function() {
		$('.check').prop("checked", $(this).is(":checked"));
	});

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

	function deleteRow(gd_id) {
		if (confirm('삭제하시겠습니까?')) {

			$.ajax({
				type: "POST",
				url: "mypage.proc.php",
				data: {
					mode: "daily_del",
					gd_id: gd_id
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
					delete_name: "daily_list",
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
			});
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
					delete_name: "daily_list",
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
</script>
<?php  
include_once "_foot.php";
?>