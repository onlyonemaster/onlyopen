<?php   
	include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
	if ($_POST['one_id'] && $_POST['one_pwd']) {
		$_POST['one_id'] = strtolower(trim($_POST['one_id']));
		$safe_one_id = mysqli_real_escape_string($self_con, $_POST['one_id']);
		$safe_remote_addr = mysqli_real_escape_string($self_con, $_SERVER['REMOTE_ADDR']);
		$site = explode(".", $_SERVER['HTTP_HOST']);
		$safe_site0 = mysqli_real_escape_string($self_con, $site[0]);
		$sql = "SELECT stop_yn,orderNumber,end_status FROM tjd_pay_result WHERE buyer_id='{$safe_one_id}' AND gwc_cont_pay=0 AND end_status in ('Y','A') AND stop_yn='N' and
	        (member_type = 'business' or member_type like '%professional' or member_type like 'basic%' or member_type = 'enterprise' or member_type='베스트상품' or ((iam_pay_type = '' or iam_pay_type = '0' or iam_pay_type = '전문가') AND member_type != '포인트충전')) AND 
			payMethod <> 'POINT' order by end_date desc";
		$res_result = mysqli_query($self_con,$sql);
		$pay_data = mysqli_fetch_array($res_result);
		if ($pay_data == null) {
			$sql = "SELECT stop_yn,orderNumber,end_status FROM tjd_pay_result WHERE buyer_id='{$safe_one_id}' AND gwc_cont_pay=0 AND 
	        (member_type = 'business' or member_type like '%professional' or member_type like 'basic%' or member_type = 'enterprise' or member_type='베스트상품' or ((iam_pay_type = '' or iam_pay_type = '0' or iam_pay_type = '전문가') AND member_type != '포인트충전')) AND 
			payMethod <> 'POINT' order by end_date desc";
			$res_result = mysqli_query($self_con,$sql);
			$pay_data = mysqli_fetch_array($res_result);
			if ($pay_data['stop_yn'] == "Y" || $pay_data['end_status'] == "N" || $pay_data['end_status'] == "E") {
				$sql_m = "UPDATE Gn_Member SET service_type = 0 WHERE mem_id = '{$safe_one_id}'";
				mysqli_query($self_con, $sql_m);
				echo "<script>window.parent.open('/payment_pop.php?index=" . htmlspecialchars($pay_data['orderNumber'], ENT_QUOTES, 'UTF-8') . "&type=user', 'notice_pop', 'toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=600,height=350');</script>";
				//exit;
			}
		}
		$mem_pass = $_POST['one_pwd'];
		//$sql = "SELECT mem_code, mem_id, is_leave, mem_leb, iam_leb,site, site_iam FROM Gn_Member use index(login_index) WHERE mem_leb>0 AND ( (mem_id = '{$_POST['one_id']}' AND web_pwd=password('$mem_pass')) or (mem_email = '{$_POST['one_id']}' AND web_pwd=password('$mem_pass'))) ";
		//$sql = "SELECT mem_code, mem_id, is_leave, mem_leb, iam_leb,site, site_iam FROM Gn_Member use index(login_index) WHERE mem_leb>0 AND ( (mem_id = '{$_POST['one_id']}' or mem_email = '{$_POST['one_id']}') AND ( mem_pass=md5('$mem_pass') or web_pwd=md5('$mem_pass')))";
		$safe_mem_pass = mysqli_real_escape_string($self_con, $mem_pass);
		$sql = "SELECT mem_code, mem_id, is_leave, mem_leb, iam_leb,site, site_iam FROM Gn_Member use index(login_index) WHERE mem_leb>0 AND mem_id = '{$safe_one_id}' AND mem_pass=md5('{$safe_mem_pass}')";
		$resul = mysqli_query($self_con,$sql);
		$row = mysqli_fetch_array($resul);
		if ($row['mem_code'] && $row['is_leave'] == 'N') {
			// 관리자 권한이 있으면 관리자 세션 추가 Add Cooper
			$admin_sql = "SELECT mem_id FROM Gn_Admin WHERE mem_id= '{$safe_one_id}'";
			$admin_result = mysqli_query($self_con,$admin_sql);
			$admin_row = mysqli_fetch_array($admin_result);
			if ($admin_row[0] != "") {
				$_SESSION['one_member_admin_id'] = $_POST['one_id'];
			}
			if ($row['site'] != "") {
				$_SESSION['one_member_id'] = $_POST['one_id'];
				$_SESSION['one_mem_lev'] = $row['mem_leb'];
				$_SESSION['site'] = $row['site'];
				$service_sql = "SELECT mem_id,sub_domain FROM Gn_Service WHERE mem_id= '{$safe_one_id}'";
				$service_result = mysqli_query($self_con,$service_sql);
				$service_row = mysqli_fetch_array($service_result);
				if ($service_row['mem_id'] != "") {
					$url = parse_url($service_row['sub_domain']);
					$_SESSION['one_member_subadmin_id'] = $_POST['one_id'];
					$_SESSION['one_member_subadmin_domain'] = $url['host'];
				}
			}
			if ($row['site_iam'] != "") {
				$_SESSION['iam_member_id'] = $_POST['one_id'];
				$_SESSION['iam_member_leb'] = $row['iam_leb'];
				$_SESSION['site_iam'] = $row['site_iam'];
				$iam_sql = "SELECT mem_id,sub_domain FROM Gn_Iam_Service WHERE mem_id= '{$safe_one_id}'";
				$iam_result = mysqli_query($self_con,$iam_sql);
				$iam_row = mysqli_fetch_array($iam_result);
				if ($iam_row['mem_id'] != "") {
					$url = parse_url($iam_row['sub_domain']);
					$_SESSION['iam_member_subadmin_id'] = $_POST['one_id'];
					$_SESSION['iam_member_subadmin_domain'] = $url['host'];
				}
			}
			//login이력을 기록한다.
			$sql = "SELECT idx FROM gn_hist_login WHERE userid='{$safe_one_id}' AND ip='{$safe_remote_addr}' AND success='N' order by idx desc limit 0,1";
			$resul = mysqli_query($self_con,$sql);
			$hrow = mysqli_fetch_array($resul);
			if ($hrow[0] != "") {
				$safe_hrow0 = mysqli_real_escape_string($self_con, $hrow[0]);
				$sql =  "UPDATE gn_hist_login SET success='Y' WHERE idx='{$safe_hrow0}'";
				$resul = mysqli_query($self_con,$sql);
			} else {
				$sql =  "INSERT INTO gn_hist_login (domain,userid,position,ip,success) values('{$safe_site0}', '{$safe_one_id}', 'iam', '{$safe_remote_addr}', 'Y')";
				$resul = mysqli_query($self_con,$sql);
			}
	
			// 마지막 접속 시간 기록 Add Cooper
			// $memToken = generateRandomString(10);
			$sql =  "UPDATE Gn_Member SET login_date=now(),ext_recm_id='{$safe_site0}' WHERE mem_id= '{$safe_one_id}'";
			$resul = mysqli_query($self_con,$sql);
	
			$alert_sql = "SELECT `no` FROM tjd_sellerboard WHERE pop_yn='Y' ORDER BY DATE DESC LIMIT 0, 3";
			$alret_res = mysqli_query($self_con,$alert_sql);
			while ($alret_row = mysqli_fetch_array($alret_res)) {
				$safe_alert_no = (int)$alret_row['no'];
				echo "<script>if(document.cookie.search('Memo_iam" . $safe_alert_no . "') == -1) window.open('/iam/notice_pop.php?id=" . $safe_alert_no . "', '','toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=800,height=400');</script>";
			}
			if ($row['site_iam'] == $site[0]) {
				if ($_POST['contents_idx']) { ?>
					<script language="javascript">
						var url = "/iam/contents.php?contents_idx=" + <?= json_encode((int)$_POST['contents_idx'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
						window.parent.location.href = url;
					</script>
				<?php   } else { ?>
					<script language="javascript">
						try {
							AppScript.setClearCache();
						} catch (exception) {
	
						}
						<?php if (!empty($_POST['redirect_url'])): ?>
						window.parent.location.replace(<?= json_encode($_POST['redirect_url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
						<?php else: ?>
						window.parent.location.replace('/m');
						<?php endif; ?>
					</script>
				<?php   }
			} else if ($row['site_iam'] != "") {
				// 세션 쿠키 파라미터 설정
				$safe_site = $row['site_iam'] . ".";
				if ($row['site_iam'] == "kiam")
					$safe_site = "";
				if ($_POST['contents_idx']) { ?>
					<script language="javascript">
						var url = "https://" + <?= json_encode($safe_site, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?> + "kiam.kr/iam/contents.php?contents_idx=" + <?= json_encode((int)$_POST['contents_idx'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?> /* + "&key=" + '<?= $sess_id ?>'*/ ;
						window.parent.location.href = url;
					</script>
				<?php   } else if ($_POST['for_report']) {?>
					<script language="javascript">
						var url = "https://" + <?= json_encode($safe_site, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?> + "kiam.kr/iam/mypage_report_list.php?for_report=true";
						window.parent.location.href = url;
					</script>
				<?php   } else { ?>
					<script language="javascript">
						try {
							AppScript.setClearCache();
						} catch (exception) {
	
						}
						var url = "https://" + <?= json_encode($safe_site, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?> + "kiam.kr/m" /*/?key=" + '<?= $sess_id ?>'*/ ;
						window.parent.location.href = url;
					</script>
				<?php   } ?>
			<?php   }
		} else if ($row['mem_code'] && $row['is_leave'] == 'Y') { ?>
			<script language="javascript">
				alert('탈퇴한 회원 아이디입니다.');
				window.parent.login_form.one_id.focus();
			</script>
			<?php  	} else {
			//login이력을 기록한다.
			$msg = "아이디 혹은 비밀번호가 틀렸습니다.";
			$sql = "SELECT idx,count FROM gn_hist_login WHERE userid='{$safe_one_id}' AND ip='{$safe_remote_addr}' AND success='N' order by idx desc limit 0,1";
			$resul = mysqli_query($self_con,$sql);
			$hrow = mysqli_fetch_array($resul);
			if ($hrow[0] != "") {
				$safe_hrow0 = mysqli_real_escape_string($self_con, $hrow[0]);
				$sql =  "UPDATE gn_hist_login SET count=count+1 WHERE idx='{$safe_hrow0}'";
				$resul = mysqli_query($self_con,$sql);
	
				$try_count = intval($hrow[1]) + 1;
				if ($try_count >= 5) { ?>
					<script language="javascript">
						<?php  
						if ($try_count == 5) { ?>
							alert("아이디/비밀번호 찾기로 전환됩니다.");
						<?php   } ?>
						window.parent.location.replace('/id_pw.php');
					</script>
			<?php  
					exit;
				} else if ($try_count >= 3) {
					$msg = '귀하의 계정 정보가 현재 ' . $try_count . '회 오류입니다.\n5회 오류가 발생할 경우 계정찾기로 전환됩니다.\n다시한번 확인하시고 계정정보입력바랍니다.\n감사합니다.';
				}
			} else {
				$sql =  "INSERT INTO gn_hist_login (domain,userid,position,ip,count) values('{$safe_site0}', '{$safe_one_id}', 'iam', '{$safe_remote_addr}', 1)";
				$resul = mysqli_query($self_con,$sql);
			}
			?>
			<script language="javascript">
				alert(<?= json_encode($msg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
				history.back(-1);
			</script>
	<?php   }
	} ?>