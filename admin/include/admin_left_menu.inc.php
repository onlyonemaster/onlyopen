<?php  
    include_once $_SERVER['DOCUMENT_ROOT']."/lib/rlatjd_fun.php";
    $sql="SELECT count(no) as cnt FROM tjd_board WHERE category=2 AND reply is null ";
    $result = mysqli_query($self_con,$sql) or die(mysqli_error($self_con));
    $boardCnt=mysqli_fetch_array($result);

    $sql_coty="SELECT count(coty_id) as cnt FROM gn_coaching_apply WHERE agree=0 ";
    $result_coty = mysqli_query($self_con,$sql_coty) or die(mysqli_error($self_con));
    $boardCnt_coty=mysqli_fetch_array($result_coty);

    $sql_coaching="SELECT count(coach_id) as cnt FROM gn_coach_apply WHERE agree=0 ";
    $result_coaching = mysqli_query($self_con,$sql_coaching) or die(mysqli_error($self_con));
    $boardCnt_coaching=mysqli_fetch_array($result_coaching);

    $date_array = getdate();
    $formated_date  = "";
    $formated_date .= $date_array['year'] . "-";
    $formated_date .= $date_array['mon'] . "-";
    $formated_date .= $date_array['mday'];

    $sql_settlement="SELECT count(no) as cnt FROM tjd_pay_result WHERE end_status='N' AND date>'$formated_date' ";
    $result_settlement = mysqli_query($self_con,$sql_settlement) or die(mysqli_error($self_con));
    $boardCnt_settlement=mysqli_fetch_array($result_settlement);

    $sql_pay_cancle="SELECT count(no) as cnt FROM tjd_pay_result WHERE monthly_status='R' ";
    $result_pay_cancle = mysqli_query($self_con,$sql_pay_cancle) or die(mysqli_error($self_con));
    $boardCnt_pay_cancle=mysqli_fetch_array($result_pay_cancle);

    $sql_money_change="SELECT count(no) as cnt FROM tjd_pay_result WHERE end_status='N' AND member_type = '현금전환'";
    $result_money_change = mysqli_query($self_con,$sql_money_change) or die(mysqli_error($self_con));
    $moneyChange=mysqli_fetch_array($result_money_change);

    $today = date("Y-m-d");
    $sql_newMember="SELECT count(mem_id) as cnt FROM Gn_Member WHERE first_regist>'$today' ";
    $result_newMember = mysqli_query($self_con,$sql_newMember) or die(mysqli_error($self_con));
    $boardCnt_newMember=mysqli_fetch_array($result_newMember);

    $sql_pay_item="SELECT count(no) as cnt FROM Gn_Item_Pay_Result WHERE pay_date >'$today' AND pay_status = 'Y' AND ( point_val=0 or (point_val=1 AND site is not null AND type='servicebuy'))";
    $result_pay_item = mysqli_query($self_con,$sql_pay_item) or die(mysqli_error($self_con));
    $boardCnt_payItem=mysqli_fetch_array($result_pay_item);

    $card_sql ="SELECT count(idx) as cnt FROM Gn_Iam_Name_Card use index(req_data) WHERE req_data >'$today'";
    if($global_is_local){
        $card_res = mysqli_query($self_con,$card_sql) or die(mysqli_error($self_con));
        $card_row = mysqli_fetch_array($card_res);
    }else{
        $redis = new RedisCache();
        $card_row = $redis->get_query_to_data($card_sql);
    }

    $cont_sql ="SELECT count(idx) as cnt FROM Gn_Iam_Contents WHERE req_data >'$today'";
    $cont_res = mysqli_query($self_con,$cont_sql) or die(mysqli_error($self_con));
    $cont_row = mysqli_fetch_array($cont_res);

    $mall_sql ="SELECT count(idx) as cnt FROM Gn_Iam_Mall WHERE reg_date >'$today'";
    $mall_res = mysqli_query($self_con,$mall_sql) or die(mysqli_error($self_con));
    $mall_row = mysqli_fetch_array($mall_res);

    $iam_service_sql ="SELECT count(idx) as cnt FROM Gn_Iam_Service WHERE regdate >'$today'";
    $iam_service_res = mysqli_query($self_con,$iam_service_sql) or die(mysqli_error($self_con));
    $iam_service_row = mysqli_fetch_array($iam_service_res);

    $provider_req_cnt = "SELECT count(*) as cnt FROM Gn_Iam_Contents_Gwc WHERE public_display='N' AND provider_req_prod='Y'";
    $res_provider = mysqli_query($self_con,$provider_req_cnt);
    $row_provider = mysqli_fetch_array($res_provider);

    $change_req_cnt = "SELECT count(*) as cnt FROM Gn_Gwc_Order WHERE prod_state!=0 AND prod_req_state=0";
    $res_change = mysqli_query($self_con,$change_req_cnt);
    $row_change = mysqli_fetch_array($res_change);

    $report_sql = "SELECT count(id) as cnt FROM gn_report_form WHERE reg_date >='$today' AND status = 0";
    $report_res = mysqli_query($self_con,$report_sql);
    $report_row = mysqli_fetch_array($report_res);

    $service_sql = "SELECT self,push FROM Gn_Service WHERE mem_id = '{$_SESSION['one_member_id']}'";
    $service_res = mysqli_query($self_con,$service_sql);
    $service_row = mysqli_fetch_array($service_res);
if ($_SESSION['one_member_id'] == "obmms02") {
    $service_row['self'] = 1;
    $service_row['push'] = 1;
}
?>
<script>
    function hide_left_bar()
    {
        $("body").addClass("sidebar-collapse");
    }
</script>
<aside class="main-sidebar" style="height:100%">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar1" style="height:100%;overflow-y: auto;">
        <!-- Sidebar user panel -->
        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">
            <li class="header">관리자 메뉴</li>
            <?php  if($_SESSION['one_member_id'] == "sungmheo"){?>
                <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>굿마켓정보관리</span></a>
                    <ul class="treeview-menu" style="display: block;">
                        <li <?=$fileName=="gwc_payment_list.php"?" class='active'":""?>>
                            <a href="/admin/gwc_payment_list.php"><i class="fa fa-circle-o"></i> <span>쇼핑결제정보관리</span></a>
                        </li>
                    </ul>
                </li>
            <?php  
            }else if($_SESSION['one_member_admin_id'] == "" && $_SESSION['one_member_subadmin_id'] != "" && $_SESSION['one_member_subadmin_domain'] == $_SERVER['HTTP_HOST'] ){
        	    if($_SESSION['one_member_subadmin_id'] == "wbmmaster"){?>
                    <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>기본정보관리</span></a>
                        <ul class="treeview-menu" style="display: block;">
                            <li <?=$fileName=="iam_alert_list.php"||$fileName=="iam_video_list.php"||$fileName=="selling_alert_list.php"||$fileName=="selling_video_list.php"||
                                $fileName=="iam_alert_write.php"||$fileName=="iam_video_write.php"||$fileName=="selling_alert_write.php"||$fileName=="selling_video_write.php"?" class='active'":""?> ><a href="/admin/iam_alert_list.php"><i class="fa fa-circle-o"></i> <span>이용안내관리</span></a></li>
                        </ul>
                    </li>
                <?php   }?>
                <li class="treeview"><a href="javascript:;;"><i class="fa fa-book"></i> <span>회원관리</span></a>
                    <ul class="treeview-menu" style="display: block;">
                        <li <?=$fileName=="member_list.php"||$fileName=="member_detail.php"?" class='active'":""?> >
                            <a href="/admin/member_list.php"><i class="fa fa-circle-o"></i> <span>회원정보관리</span></a>
                        </li>
                                <?php if(false){ // [Security] hide customers menu for franchise admin ?>
                                <li <?= $fileName == "customers_list.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/customers_list.php"><i class="fa fa-circle-o"></i> <span>고객정보관리</span></a>
                                </li>
                                <?php } ?>
                        <li <?= $fileName == "member_card_list.php" ? " class='active'" : "" ?>>
                            <a href="/admin/member_card_list.php"><i class="fa fa-circle-o"></i> <span>명함정보관리</span></a>
                        </li>
                        <li <?= ($fileName == "customer_reg_list.php" || $fileName == "customer_req_list.php" || $fileName == "customer_get_list.php") ? " class='active'" : "" ?>>
                            <a href="/admin/customer_reg_list.php"><i class="fa fa-circle-o"></i> <span>유료회원관리</span></a>
                        </li>
                    </ul>
                </li>
                <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>게시물관리</span></a>
                    <ul class="treeview-menu" style="display: block;">
                        <li <?=$fileName=="biz_notice_list.php"||$fileName=="biz_notice_detail.php"?" class='active'":""?>>
                            <a href="/admin/biz_notice_list.php"><i class="fa fa-circle-o"></i> <span>분양사공지사항</span></a>
                        </li>
                    </ul>
                </li>
                <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>분양사관리</span></a>
                    <ul class="treeview-menu" style="display: block;">
                        <li <?= $fileName == "app_home_menu.php" ? " class='active'" : "" ?>>
                            <a href="/admin/app_home_menu.php"><i class="fa fa-circle-o"></i> <span>앱홈관리</span></a>
                        </li>
                    </ul>
                </li>
            <?php   }else if($_SESSION['one_member_admin_id'] == "" && $_SESSION['iam_member_subadmin_id'] != "" && $_SESSION['iam_member_subadmin_domain'] == $_SERVER['HTTP_HOST']){
                    if($_SESSION['one_member_subadmin_id'] == "wbmmaster"){?>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>기본정보관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="iam_alert_list.php"||$fileName=="iam_video_list.php"||$fileName=="selling_alert_list.php"||$fileName=="selling_video_list.php"||
                                $fileName=="iam_alert_write.php"||$fileName=="iam_video_write.php"||$fileName=="selling_alert_write.php"||$fileName=="selling_video_write.php"?" class='active'":""?> ><a href="/admin/iam_alert_list.php"><i class="fa fa-circle-o"></i> <span>이용안내관리</span></a></li>
                            </ul>
                        </li>
                    <?php   }?>
                    <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>회원관리</span></a>
                        <ul class="treeview-menu" style="display: block;">
                            <li <?=$fileName=="member_list.php"||$fileName=="member_detail.php"?" class='active'":""?> >
                            <a href="/admin/member_list.php"><i class="fa fa-circle-o"></i> <span>회원정보관리</span></a>
                            </li>
                                <?php if(false){ // [Security] hide customers menu for franchise admin ?>
                                <li <?= $fileName == "customers_list.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/customers_list.php"><i class="fa fa-circle-o"></i> <span>고객정보관리</span></a>
                                </li>
                                <?php } ?>
                        </ul>
                    </li>
                    <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>게시물관리</span></a>
                        <ul class="treeview-menu" style="display: block;">
                            <li <?=$fileName=="iam_notice_list.php"||$fileName=="iam_notice_detail.php"?" class='active'":""?>>
                                <a href="/admin/iam_notice_list.php"><i class="fa fa-circle-o"></i> <span>아이엠공지사항</span></a>
                            </li>
                        </ul>
                    </li>
            <?php   }else if($_SESSION['one_member_admin_id'] != ""){
                if($_SESSION['one_member_id'] != "lecturem") {
                    if($_SESSION['one_member_admin_id'] == "emi0542" || $_SESSION['one_member_admin_id'] == "gwunki"){?>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>회원관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="member_list_con.php"||$fileName=="member_detail.php"?" class='active'":""?>>
                                    <a href="/admin/member_list_con.php"><i class="fa fa-circle-o"></i> <span>회원정보(상담용)</span></a>
                                </li>
                            </ul>
                        </li>
                    <?php   }else{
                        if($_SESSION['one_member_admin_id'] != "onlyonemaket"){?>
                            <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>기본정보관리</span></a>
                                <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="admin_manual.php"||$fileName=="manual_detail.php"?" class='active'":""?>>
                                    <a href="/admin/admin_manual.php"><i class="fa fa-circle-o"></i> <span>관리자 매뉴얼</span></a>
                                </li>
                                <li <?=$fileName=="map_manual.php"?" class='active'":""?>>
                                    <a href="/admin/map_manual.php"><i class="fa fa-robot"></i> <span>AI매뉴얼관리</span></a>
                                </li>
                                <li <?=$fileName=="admin_allowip_list.php"||$fileName=="admin_allowip_detail.php"?" class='active'":""?> >
                                    <a href="/admin/admin_allowip_list.php"><i class="fa fa-circle-o"></i> <span>허용아이피관리</span></a>
                                </li>
                                <li <?=$fileName=="mms_callback_list.php"||$fileName=="mms_callback_detail.php"?" class='active'":""?> >
                                    <a href="/admin/mms_callback_list.php"><i class="fa fa-circle-o"></i> <span>콜백메시지관리</span></a>
                                </li>
                                <li <?=$fileName=="autojoin_msg_list.php"||$fileName=="autojoin_msg_detail.php"?" class='active'":""?> >
                                    <a href="/admin/autojoin_msg_list.php"><i class="fa fa-circle-o"></i> <span>오토회원메시지관리</span></a>
                                </li>
                                <li <?=$fileName=="report_list.php"||$fileName=="report_edit.php"||$fileName=="report_reply.php"?" class='active'":""?> >
                                        <a href="/admin/report_list.php"><i class="fa fa-circle-o"></i> <span>모듈랜딩관리</span><?php   if (number_format($report_row['cnt']) != 0)
                                            echo "( " . number_format($report_row['cnt']) . " )";?></a>
                                </li>
                                <li <?=$fileName=="app_manage_list.php"||$fileName=="app_manage_detail.php"?" class='active'":""?> >
                                    <a href="/admin/app_manage_list.php"><i class="fa fa-circle-o"></i> <span>어플버전관리</span></a>
                                </li>
                                <li <?=$fileName=="iam_search_key.php"?" class='active'":""?> >
                                    <a href="/admin/iam_search_key.php"><i class="fa fa-circle-o"></i> <span>관리자입력정보</span></a>
                                </li>
                                <li <?=$fileName=="service_list.php"||$fileName=="service_detail.php"?" class='active'":""?> >
                                    <a href="/admin/service_list.php"><i class="fa fa-circle-o"></i> <span>솔루션분양관리</span></a>
                                </li>
                                <li <?=$fileName=="phone_check_list.php"||$fileName=="phone_check_detail.php"?" class='active'":""?> >
                                    <a href="/admin/phone_check_list.php"><i class="fa fa-circle-o"></i> <span>폰문자인증관리</span></a>
                                </li>
                                <li <?=$fileName=="service_Iam_list.php"||$fileName=="service_Iam_detail.php"||$fileName=="app_home_menu.php"||$fileName=="iam_menu.php"?" class='active'":""?> >
                                    <a href="/admin/service_Iam_list.php"><i class="fa fa-circle-o"></i> <span>아이엠분양관리</span>
                                    <?php  if(number_format($iam_service_row['cnt']) != 0)
                                            echo "( " . number_format($iam_service_row['cnt']) . " )";?>
                                    </a>
                                </li>
                                <li <?=$fileName=="iam_alert_list.php"||$fileName=="iam_video_list.php"||$fileName=="selling_alert_list.php"||$fileName=="selling_video_list.php"||
                                $fileName=="iam_alert_write.php"||$fileName=="iam_video_write.php"||$fileName=="selling_alert_write.php"||$fileName=="selling_video_write.php"?" class='active'":""?> ><a href="/admin/iam_alert_list.php"><i class="fa fa-circle-o"></i> <span>이용안내관리</span></a></li>
                                <li <?=$fileName=="admin_config.php"||$fileName=="admin_config.php"?" class='active'":""?> ><a href="/admin/admin_config.php"><i class="fa fa-circle-o"></i> <span>환경설정</span></a></li>
                                </ul>
                            </li>
                        <?php   }?>
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>회원관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="member_list.php"||$fileName=="member_detail.php"?" class='active'":""?>>
                                    <a href="/admin/member_list.php"><i class="fa fa-circle-o"></i> <span>회원정보관리</span>
                                        <?php  if(number_format($boardCnt_newMember['cnt']) != 0)
                                            echo "( " . number_format($boardCnt_newMember['cnt']) . " )";?>
                                    </a>
                                </li>
                                <li <?= $fileName == "customers_list.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/customers_list.php"><i class="fa fa-circle-o"></i> <span>고객정보관리</span></a>
                                </li>
                                <li <?= $fileName == "member_card_list.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/member_card_list.php"><i class="fa fa-circle-o"></i> <span>명함정보관리</span></a>
                                </li>
                                <li <?= ($fileName == "customer_reg_list.php" || $fileName == "customer_req_list.php" || $fileName == "customer_get_list.php") ? " class='active'" : "" ?>>
                                    <a href="/admin/customer_reg_list.php"><i class="fa fa-circle-o"></i> <span>유료회원관리</span></a>
                                </li>


                                <li <?=$fileName=="member_login_history.php"||$fileName=="member_login_history.php"?" class='active'":""?>>
                                    <a href="/admin/member_login_history.php"><i class="fa fa-circle-o"></i> <span>로그인이력관리</span></a>
                                </li>
                                <li <?=$fileName=="member_block_ip.php"||$fileName=="member_block_ip.php"?" class='active'":""?>>
                                    <a href="/admin/member_block_ip.php"><i class="fa fa-circle-o"></i> <span>차단아이피관리</span></a>
                                </li>
                            </ul>
                        </li>
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>AI 자동화관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?= $fileName == "gpt_prompt_settings.php" || $fileName == "gpt_prompt_settings.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/gpt_prompt_settings.php"><i class="fa fa-circle-o"></i> <span>AI-GPT모델설정</span></a>
                                </li>
                                <li <?= $fileName == "ai_message_settings.php" || $fileName == "ai_message_settings.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/ai_message_settings.php?reserv_type=1"><i class="fa fa-circle-o"></i> <span>AI퍼널설계설정</span></a>
                                </li>
                                <li <?= $fileName == "ai_message_cardsettings.php" || $fileName == "ai_message_cardsettings.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/ai_message_cardsettings.php?reserv_type=1"><i class="fa fa-circle-o"></i> <span>AI명함퍼널설계</span></a>
                                </li>
                                <li <?= $fileName == "chatbot_history_list.php" ? " class='active'" : "" ?>>
                                    <a href="/admin/chatbot_history_list.php"><i class="fa fa-circle-o"></i> <span>챗봇대화리스트</span></a>
                                </li>
                            </ul>
                        </li>
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>굿마켓정보관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="gwc_sync_state.php"||$fileName=="gwc_sync_state.php"?" class='active'":""?>>
                                    <a href="/admin/gwc_sync_state.php"><i class="fa fa-circle-o"></i> <span>굿마켓상품동기화</span></a>
                                </li>
                                <li <?=$fileName=="card_gwc_contents_list.php"?" class='active'":""?>>
                                    <a href="/admin/card_gwc_contents_list.php"><i class="fa fa-circle-o"></i> <span><?='굿마켓상품 관리'?></span></a>
                                </li>
                                <li <?=$fileName=="gwc_member_list.php"||$fileName=="gwc_member_list.php"?" class='active'":""?>>
                                    <a href="/admin/gwc_member_list.php"><i class="fa fa-circle-o"></i> <span>굿마켓회원정보관리</span></a>
                                </li>
                                <li <?=$fileName=="gwc_payment_list.php"?" class='active'":""?>>
                                    <a href="/admin/gwc_payment_list.php"><i class="fa fa-circle-o"></i> <span>쇼핑결제정보관리</span></a>
                                </li>
                                <li <?=$fileName=="gwc_payment_balance_list.php"?" class='active'":""?>>
                                    <a href="/admin/gwc_payment_balance_list.php"><i class="fa fa-circle-o"></i> <span>판매자정산정보관리</span></a>
                                </li>
                                <li <?=$fileName=="gwc_prod_order_change_list.php"||$fileName=="gwc_prod_order_change_list.php"?" class='active'":""?>>
                                    <a href="/admin/gwc_prod_order_change_list.php"><i class="fa fa-circle-o"></i> <span>취소/교환/환불관리</span><?php  if(number_format($row_change['cnt']) != 0)
                                            echo "( " . number_format($row_change['cnt']) . " )";?></a>
                                </li>
                                <li <?=$fileName=="card_gwc_contents_list_provider.php"||$fileName=="card_gwc_contents_list_provider.php"?" class='active'":""?>>
                                    <a href="/admin/card_gwc_contents_list_provider.php"><i class="fa fa-circle-o"></i> <span>공급사/상품등록관리</span><?php  if(number_format($row_provider['cnt']) != 0)
                                            echo "( " . number_format($row_provider['cnt']) . " )";?></a>
                                </li>
                            </ul>
                        </li>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>통합결제관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="payment_desc.php"?" class='active'":""?>>
                                    <a href="/admin/payment_desc.php"><i class="fa fa-circle-o"></i> <span>플랫폼결제구성</span></a>
                                </li>
                            <?php  if(number_format($boardCnt_settlement['cnt']) != 0){?>
                                <li <?=$fileName=="payment_list.php"||$fileName=="payment_detail.php"?" class='active'":""?>>
                                    <a href="/admin/payment_list.php"><i class="fa fa-circle-o"></i> <span>플랫폼결제관리</span> ( <?php   echo number_format($boardCnt_settlement['cnt']);?> )</a>
                                </li>
                            <?php   }else{?>
                                <li <?=$fileName=="payment_list.php"||$fileName=="payment_detail.php"?" class='active'":""?>>
                                    <a href="/admin/payment_list.php"><i class="fa fa-circle-o"></i> <span>플랫폼결제관리</span></a>
                                </li>
                            <?php   }?>
                            <?php  if(number_format($boardCnt_payItem['cnt']) != 0){?>
                                <li <?=$fileName=="payment_item_list.php"||$fileName=="payment_item_balance_list.php"?" class='active'":""?>>
                                    <a href="/admin/payment_item_list.php"><i class="fa fa-circle-o"></i> <span>상품결제관리</span> ( <?=number_format($boardCnt_payItem['cnt']);?> )</a>
                                </li>
                            <?php   }else{?>
                                <li <?=$fileName=="payment_item_list.php"||$fileName=="payment_item_balance_list.php"?" class='active'":""?>>
                                    <a href="/admin/payment_item_list.php"><i class="fa fa-circle-o"></i> <span>상품결제관리</span></a>
                                </li>
                           <?php   }?>
                                <li <?=$fileName=="member_manager_request_list3.php"?" class='active'":""?>>
                                    <a href="/admin/member_manager_request_list3.php"><i class="fa fa-circle-o"></i> <span>사업자배당관리</span></a>
                                </li>
                                <li <?=$fileName=="payment_balance_advance_list.php"||$fileName=="payment_balance_list_advance_detail.php"?" class='active'":""?>>
                                    <a href="/admin/payment_balance_advance_list.php"><i class="fa fa-circle-o"></i> <span>사업자정산관리</span></a>
                                </li>
                            <?php  if(number_format($boardCnt_pay_cancle['cnt']) != 0){?>
                                <li <?=$fileName=="payment_month_list.php"?" class='active'":""?>>
                                    <a href="/admin/payment_month_list.php"><i class="fa fa-circle-o"></i> <span>정기결제해지관리</span> ( <?php   echo number_format($boardCnt_pay_cancle['cnt']);?> )</a>
                                </li>
                            <?php   }else{?>
                                <li <?=$fileName=="payment_month_list.php"?" class='active'":""?>>
                                    <a href="/admin/payment_month_list.php"><i class="fa fa-circle-o"></i> <span>정기결제해지관리</span></a>
                                </li>
                            <?php   }?>
                                <li <?=$fileName=="payment_card_month_list.php"?" class='active'":""?>>
                                    <a href="/admin/payment_card_month_list.php"><i class="fa fa-circle-o"></i> <span>카드정기결제내역</span></a>
                                </li>
                                <li <?=$fileName=="point_manage_list_cash.php"?" class='active'":""?>>
                                    <a href="/admin/point_manage_list_cash.php"><i class="fa fa-circle-o"></i> <span>포인트정보/전환관리</span> <?php   if (number_format($moneyChange['cnt']) > 0) echo "( ".number_format($moneyChange['cnt'])." )";?></a>
                                </li>
                            </ul>
                        </li>
                        <?php include_once __DIR__ . '/onechat_left_menu.inc.php'; ?>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>문자발송관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="member_return_list_10.php"||$fileName=="member_return_detail.php"?" class='active'":""?>>
                                    <a href="/admin/member_return_list_10.php?upd=yes"><i class="fa fa-circle-o"></i> <span>메시지발신정보</span></a>
                                </li>
                                <li <?=$fileName=="addr_phone_list.php"?" class='active'":""?>>
                                    <a href="/admin/addr_phone_list.php"><i class="fa fa-circle-o"></i> <span>폰연락처관리</span></a>
                                </li>
                                <li <?=$fileName=="addr_iam_list.php"?" class='active'":""?>>
                                    <a href="/admin/addr_iam_list.php"><i class="fa fa-circle-o"></i> <span>IAM연락처관리</span></a>
                                </li>

                                <li <?=$fileName=="addr_add_iam_list.php"?" class='active'":""?>>
                                    <a href="/admin/addr_add_iam_list.php"><i class="fa fa-circle-o"></i> <span>연락처추가관리</span></a>
                                </li>

                                <li <?=$fileName=="addr_mms_list.php"?" class='active'":""?>>
                                    <a href="/admin/addr_mms_list.php"><i class="fa fa-circle-o"></i> <span>문자발송주소록</span></a>
                                </li>
                            </ul>
                        </li>
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>디비수집관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="crawler_data_list.php"?" class='active'":""?>>
                                    <a href="/admin/crawler_data_list.php"><i class="fa fa-circle-o"></i> <span>DB수집정보(~2204)</span></a>
                                </li>
                                <li <?=$fileName=="crawler_data_2022_list.php"?" class='active'":""?>>
                                    <a href="/admin/crawler_data_2022_list.php"><i class="fa fa-circle-o"></i> <span>DB수집정보(2204~)</span></a>
                                </li>
                                <li <?=$fileName=="crawler_supply_list.php"?" class='active'":""?>>
                                    <a href="/admin/crawler_supply_list.php"><i class="fa fa-circle-o"></i> <span>수집DB통합관리</span></a>
                                </li>
                                <li <?=$fileName=="crawler_member_list.php"||$fileName=="crawler_member_detail.php"?" class='active'":""?>>
                                    <a href="/admin/crawler_member_list.php"><i class="fa fa-circle-o"></i> <span>디버회원관리</span></a>
                                </li>
                            </ul>
                        </li>
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>아이엠관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="card_click_list.php"||$fileName=="card_group_list.php"?" class='active'":""?>>
                                    <a href="/admin/card_click_list.php"><i class="fa fa-circle-o"></i> <span><?='IAM카드관리'.($card_row[0] > 0 ? '('.$card_row[0].')':'')?></span></a>
                                </li>
                                <li <?=$fileName=="iam_mall_list.php"?" class='active'":""?>>
                                    <a href="/admin/iam_mall_list.php"><i class="fa fa-circle-o"></i> <span><?='IAM몰관리'.($mall_row[0] > 0 ? '('.$mall_row[0].')':'')?></span></a>
                                </li>
                                <li <?=$fileName=="card_contents_list.php"?" class='active'":""?>>
                                    <a href="/admin/card_contents_list.php"><i class="fa fa-circle-o"></i> <span><?='IAM컨텐츠관리'.($cont_row[0] > 0 ? '('.$cont_row[0].')':'')?></span></a>
                                </li>
                                <li <?=$fileName=="iam_report.php"?" class='active'":""?>>
                                    <a href="/admin/iam_report.php"><i class="fa fa-circle-o"></i> <span>콘텐츠 신고관리</span></a>
                                </li>
                                <li <?=$fileName=="iam_post.php" || $fileName=="iam_post_reply.php"?" class='active'":""?>>
                                    <a href="/admin/iam_post.php"><i class="fa fa-circle-o"></i> <span>콘텐츠댓글관리</span></a>
                                </li>
                                <li <?=$fileName=="ai_map_gmarket_making.php"?" class='active'":""?>>
                                    <a href="/admin/ai_map_gmarket_making.php"><i class="fa fa-circle-o"></i> <span>AI-IAM 자동생성</span></a>
                                </li>
                                <li <?=$fileName=="ai_map_gmarket_list.php"?" class='active'":""?>>
                                    <a href="/admin/ai_map_gmarket_list.php"><i class="fa fa-circle-o"></i> <span>자동생성리스트</span></a>
                                </li>
                                <li <?=$fileName=="iam_auto_lang.php"?" class='active'":""?>>
                                    <a href="/admin/iam_auto_lang.php"><i class="fa fa-circle-o"></i> <span>다국어IAM관리</span></a>
                                </li>
                                <li <?=$fileName=="people_iam_auto_get.php"?" class='active'":""?>>
                                    <a href="/admin/people_iam_auto_get.php"><i class="fa fa-circle-o"></i> <span>웹IAM자동생성</span></a>
                                </li>
                            </ul>
                        </li>  
                        <li><a href="javascript:;;"><i class="fa fa-book"></i> <span>콘텐츠사업관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="get_contents_manage.php"||$fileName=="get_contents_manage.php"?" class='active'":""?>>
                                    <a href="/admin/get_contents_manage.php"><i class="fa fa-circle-o"></i> <span>콘텐츠수집관리</span></a>
                                </li>
                                <li <?=$fileName=="allow_contents_manage.php"?" class='active'":""?>>
                                    <a href="/admin/allow_contents_manage.php"><i class="fa fa-circle-o"></i> <span>콘텐츠노출관리</span></a>
                                </li>
                                <li <?=$fileName=="share_contents_manage.php"?" class='active'":""?>>
                                    <a href="/admin/share_contents_manage.php"><i class="fa fa-circle-o"></i> <span>콘텐츠공급관리</span></a>
                                </li>
                            </ul>
                        </li>
                        <?php  if($_SESSION['one_member_admin_id'] != "onlyonemaket"){?>
                        <li <?=$fileName=="ad_list.php"||$fileName=="ad_detail.php"?" class='active'":""?>><a href="/admin/ad_list.php"><i class="fa fa-book"></i> <span>플랫폼광고관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                            <!--<li <?=$fileName=="ad_list.php"||$fileName=="ad_detail.php"?" class='active'":""?> ><a href="/admin/ad_list.php"><i class="fa fa-circle-o"></i> <span>문자 광고관리</span></a></li> 이 기능 중요하니까 지우지마세요 -->
                                <li <?=$fileName=="ad_pc_list.php"||$fileName=="ad_pc_detail.php"?" class='active'":""?>>
                                    <a href="/admin/ad_pc_list.php"><i class="fa fa-circle-o"></i> <span>디버광고관리</span></a>
                                </li>
                                <li <?=$fileName=="ad_mo_list.php"||$fileName=="ad_mo_detail.php"?" class='active'":""?>>
                                    <a href="/admin/ad_mo_list.php"><i class="fa fa-circle-o"></i> <span>홈피광고관리</span></a>
                                </li>
                            </ul>
                        </li>
                        <?php   }?>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>게시물관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="notice_list.php"||$fileName=="notice_detail.php"?" class='active'":""?>>
                                    <a href="/admin/notice_list.php"><i class="fa fa-circle-o"></i> <span>이용자공지사항</span></a>
                                </li>
                                <li <?=$fileName=="biz_notice_list.php"||$fileName=="biz_notice_detail.php"?" class='active'":""?>>
                                    <a href="/admin/biz_notice_list.php"><i class="fa fa-circle-o"></i> <span>분양사공지사항</span></a>
                                </li>
                                <li <?=$fileName=="iam_notice_list.php"||$fileName=="iam_notice_detail.php"?" class='active'":""?>>
                                    <a href="/admin/iam_notice_list.php"><i class="fa fa-circle-o"></i> <span>아이엠공지사항</span></a>
                                </li>
                                <?php  if($is_pay_version){?>
                                <li <?=$fileName=="faq_list.php"||$fileName=="faq_detail.php"?" class='active'":""?>>
                                    <a href="/admin/faq_list.php"><i class="fa fa-circle-o"></i> <span>FAQ</span></a>
                                </li>
                                <li <?=$fileName=="qna_list.php"||$fileName=="qna_detail.php"?" class='active'":""?>>
                                    <a href="/admin/qna_list.php"><i class="fa fa-circle-o"></i> <span>1:1 상담 </span> ( <?php   echo number_format($boardCnt['cnt']);?> )</a>
                                </li>
                                <?php   }?>
                            </ul>
                        </li>
                        <li <?=$fileName=="step_event_list.php"?" class='active'":""?> ><a href="javascript:;;"><i class="fa fa-book"></i> <span>퍼널문자관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                            <li><a href="step_event_list.php"><i class="fa fa-circle-o"></i> 신청페이지리스트</a></li>
                            <li><a href="step_landing_list.php"><i class="fa fa-circle-o"></i> 랜딩페이지리스트</a></li>
                            <li><a href="step_request_list.php"><i class="fa fa-circle-o"></i> 고객 신청리스트</a></li>
                            <li><a href="daily_msg_list_service.php"><i class="fa fa-circle-o"></i> 데일리메시지관리</a></li>
                                <li><a href="step_reservation_list.php"><i class="fa fa-circle-o"></i> 퍼널메시지발신내역</a></li>
                                <li><a href="step_wsend_list.php"><i class="fa fa-circle-o"></i> 메시지발신예정내역</a></li>
                                <li><a href="step_send_list.php"><i class="fa fa-circle-o"></i> 퍼널메시지발신결과</a></li>
                            </ul>
                        </li>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>코칭관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                            <?php  if(number_format($boardCnt_coaching['cnt']) != 0){?>
                                <li <?=$fileName=="member_manager_request_coach.php"||$fileName=="member_manager_request_detail.php"?" class='active'":""?> ><a href="/admin/member_manager_request_coach.php"><i class="fa fa-circle-o"></i> <span>코치신청관리</span> ( <?php   echo number_format($boardCnt_coaching['cnt']);?> )</a></li>
                            <?php   }else{?>
                                <li <?=$fileName=="member_manager_request_coach.php"||$fileName=="member_manager_request_detail.php"?" class='active'":""?> ><a href="/admin/member_manager_request_coach.php"><i class="fa fa-circle-o"></i> <span>코치신청관리</span></a></li>
                            <?php   }?>
                            <?php  if(number_format($boardCnt_coty['cnt']) != 0){?>
                                <li <?=$fileName=="member_manager_request_coty.php"||$fileName=="member_manager_request_coty.pp"?" class='active'":""?> ><a href="/admin/member_manager_request_coty.php"><i class="fa fa-circle-o"></i> <span>수강신청관리</span> ( <?php   echo number_format($boardCnt_coty['cnt']);?> )</a></li>
                            <?php   }else{?>
                                <li <?=$fileName=="member_manager_request_coty.php"||$fileName=="member_manager_request_coty.pp"?" class='active'":""?> ><a href="/admin/member_manager_request_coty.php"><i class="fa fa-circle-o"></i> <span>수강신청관리</span></a></li>
                            <?php   }?>
                                <li <?=$fileName=="coaching_list.php"||$fileName=="coaching_list.php"?" class='active'":""?> ><a href="/admin/coaching_list.php"><i class="fa fa-circle-o"></i> <span>교육정보관리</span></a></li>
                            </ul>
                        </li>
                        <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>강연관리</span></a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="lecture_list.php"||$fileName=="lecture_detail.php"?" class='active'":""?> ><a href="/admin/lecture_list.php"><i class="fa fa-circle-o"></i> <span>강연진행리스트보기</span></a></li>
                                <li <?=$fileName=="review_list.php"?" class='active'":""?> ><a href="/admin/review_list.php"><i class="fa fa-circle-o"></i> <span>강연리뷰리스트</span></a></li>
                                <li <?=$fileName=="lecture_img_list.php"?" class='active'":""?> ><a href="/admin/lecture_img_list.php"><i class="fa fa-circle-o"></i> <span>강연회이미지리스트</span></a></li>
                                <li <?=$fileName=="landing_dashboard.php"||$fileName=="landing_manage.php"||$fileName=="landing_stats_detail.php"?" class='active'":""?> ><a href="/admin/landing_dashboard.php"><i class="fa fa-line-chart"></i> <span>랜딩페이지 통계</span></a></li>
                            </ul>
                        </li>
                    <?php   }
                }else{?>
                    <li ><a href="javascript:;;"><i class="fa fa-book"></i> <span>강연관리</span></a>
                        <ul class="treeview-menu" style="display: block;">
                            <li <?=$fileName=="lecture_list.php"||$fileName=="lecture_detail.php"?" class='active'":""?> ><a href="/admin/lecture_list.php"><i class="fa fa-circle-o"></i> <span>강연진행리스트보기</span></a></li>
                            <li <?=$fileName=="review_list.php"?" class='active'":""?> ><a href="/admin/review_list.php"><i class="fa fa-circle-o"></i> <span>강연리뷰리스트보기</span></a></li>
                            <li <?=$fileName=="landing_dashboard.php"||$fileName=="landing_manage.php"||$fileName=="landing_stats_detail.php"?" class='active'":""?> ><a href="/admin/landing_dashboard.php"><i class="fa fa-line-chart"></i> <span>랜딩페이지 통계</span></a></li>
                        </ul>
                    </li>
                <?php   }
            }?>
                        <?php if(!empty($_SESSION['one_member_admin_id'])){ // [Security] location menu - main admin only ?>
                        <li><a href="javascript:;;"><i class="fa fa-map-marker"></i> <span>위치정보관리</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                            <ul class="treeview-menu" style="display: block;">
                                <li <?=$fileName=="location_consents.php"?" class='active'":""?>>
                                    <a href="/admin/location_consents.php"><i class="fa fa-circle-o"></i> 승인자 목록</a>
                                </li>
                                <li <?=$fileName=="location_logs.php"?" class='active'":""?>>
                                    <a href="/admin/location_logs.php"><i class="fa fa-circle-o"></i> 접근 로그</a>
                                </li>
                                <li <?=$fileName=="location_roles.php"?" class='active'":""?>>
                                    <a href="/admin/location_roles.php"><i class="fa fa-circle-o"></i> 권한 관리</a>
                                </li>
                                <li <?=$fileName=="location_settings.php"?" class='active'":""?>>
                                    <a href="/admin/location_settings.php"><i class="fa fa-circle-o"></i> 시스템 설정</a>
                                </li>
                                <li <?=$fileName=="location_export.php"?" class='active'":""?>>
                                    <a href="/admin/location_export.php"><i class="fa fa-circle-o"></i> 데이터 내보내기</a>
                                </li>
                                <li <?=$fileName=="location_api_keys.php"?" class='active'":""?>>
                                    <a href="/admin/location_api_keys.php"><i class="fa fa-circle-o"></i> API 키 관리</a>
                                </li>
                            </ul>
                        </li>
                        <?php } ?>
        </ul>
    </section>
    <!-- /.sidebar -->
</aside>
