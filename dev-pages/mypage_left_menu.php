<?php   
 	$sql = "SELECT * FROM tjd_pay_result WHERE buyer_id = '{$_SESSION['one_member_id']}' AND end_date > '{$date_today}' AND end_status in ('Y','A') order by end_date desc limit 1";
	$res_result = mysqli_query($self_con,$sql);
	$pay_data = mysqli_fetch_array($res_result);
	/*$rights = 0;
	//echo $pay_data['TotPrice'] ;
	//echo $pay_data['member_type'] ;
	if($pay_data['TotPrice'] < "55000") {
	    $rights = 1;    
	} else if($pay_data['TotPrice'] == "55000") {
	    $rights = 2;
	} else if($pay_data['TotPrice'] > "55000") {
	    $rights = 3;
	}*/
?>
<script language="javascript" src="<?=$path?>js/rlatjd_fun.js?m=<?php   echo time();?>"></script>
<script language="javascript" src="<?=$path?>js/rlatjd.js?m=<?php   echo  time();?>"></script>
<style>
/* The switch - the box around the slider */
.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(22px);
  -ms-transform: translateX(22px);
  transform: translateX(22px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}    
</style>
<style>
.m_left_menu{float:left; width:300px;background:#fff;}
/* .m_div {width:1000px;} — 제거 (2026-05-03) */
/* .m_body {width:1000px;} — 제거 (2026-05-03) */
.m_left_menu ul {margin-top:0px;padding-top:0px;padding:0px 10px;}
.m_left_menu li {border:1px solid #eee;padding:10px 0px;}
.m_left_menu li.title{background:#efefef; font-size:16pt}
.select {height:32px;width:100px;}
.button {height:32px;line-height:32px;}
</style>       
<div id="ajax_div"></div>
<!--
       <div class="m_left_menu">
           <ul>
               <li class="title">원퍼널문자</li>
               <?php   if($rights >= 1) {?>
               <li><a href="mypage_link_list.php">이벤트신청페이지</a></li>
               <?php   }?>
               <?php   if($rights > 1) {?>
               <li><a href="mypage_landing_list.php">이벤트랜딩페이지</a></li>
               <?php   }?>
               <?php   if($rights >= 1) {?>
               <li><a href="mypage_request_list.php">이벤트신청리스트</a></li>
               <?php   }?>
               <?php   if($rights > 1) {?>
               <li><a href="mypage_reservation_list.php">퍼널예약문자내역</a></li>
               
               <li><a href="mypage_wsend_list.php">예약발신예정내역</a></li>
               <li><a href="mypage_send_list.php">예약문자발신결과</a></li>
               <?php   }?>
           </ul>
       </div>  
       -->