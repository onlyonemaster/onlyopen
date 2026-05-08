<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
@error_reporting(0);
@ini_set("error_reporting", "0");
@ini_set("display_errors", "0");
$date_today = date("Y-m-d");
if ($_SESSION['one_member_id']) {
    // 세션 캐시: 1분 동안 쿼리 결과 저장 (결제 상태 변경 빠른 반영)
    if (!isset($_SESSION['pay_data_cache']) || !isset($_SESSION['pay_data_cache_time']) || (time() - $_SESSION['pay_data_cache_time']) > 60) {
        $sql = "SELECT * FROM tjd_pay_result WHERE buyer_id='{$_SESSION['one_member_id']}' AND end_status in ('Y','A')  AND gwc_cont_pay=0 AND
            ((member_type = 'business' OR member_type LIKE '%professional' OR member_type like 'basic%' or member_type = 'enterprise') or
            (((iam_pay_type = '' or iam_pay_type = '0' or iam_pay_type = '전문가') AND member_type != '포인트충전')) or member_type='베스트상품') AND payMethod <> 'POINT' order by end_date desc";
        /*$sql = "SELECT * FROM tjd_pay_result WHERE buyer_id='{$_SESSION['one_member_id']}' AND gwc_cont_pay=0 AND
            ((member_type like '%business%' or member_type like '%professional' or member_type like '%enterprise%') or
            (((iam_pay_type = '' or iam_pay_type = '0' or iam_pay_type = '전문가') AND member_type != '포인트충전')) or member_type='베스트상품') AND payMethod <> 'POINT' order by end_date desc";*/
        $res_result = mysqli_query($self_con, $sql);
        $_SESSION['pay_data_cache'] = mysqli_fetch_array($res_result);
        $_SESSION['pay_data_cache_time'] = time();
    }
    $pay_data = $_SESSION['pay_data_cache'];
}
$mem_type = $member_1['mem_type'];
$site = $member_1['site_iam'];
$iam_type = $member_1['iam_type'];
if ($site) {
    if ($site == "kiam")
        $href = "/";
    else
        $href = "https://" . $site . ".kiam.kr/";
} else {
    $href = "/";
}
//$use_domain = false;
$sub_domain = false;
if ($_SERVER['HTTP_HOST'] == "kiam.kr")
    $host = "www.kiam.kr";
else
    $host = $_SERVER['HTTP_HOST'];
$query = "SELECT * FROM Gn_Service WHERE sub_domain like '%{$host}%'";
$res = mysqli_query($self_con, $query);
$domainData = mysqli_fetch_array($res);
if ($_SERVER['HTTP_HOST'] != "kiam.kr") {
    if ($domainData['idx'] != "") {
        $sub_domain = true;
        if ($_SERVER['REQUEST_URI'] == '/' && $domainData['main_default_yn'] == "L") {
            header('Location: ' . $domainData['main_url']);
        }
        $curdate = strtotime(date('Y-m-d', time()));
        $startdate = strtotime($domainData['contract_start_date']);
        $enddate = strtotime($domainData['contract_end_date']);
    }
}
if ($domainData['status'] == "N") { ?>
    <script>
        if (Gesi_getCookie('Memo1') != 'done')
            newpop1('payment_pop.php?index=' + '<?= $pay_data['orderNumber'] ?>');
    </script>
<?php   }
$sql = "SELECT * FROM Gn_Ad_Manager WHERE ad_position = 'H' AND use_yn ='Y'";
$res_result = mysqli_query($self_con, $sql);
$ad_data = mysqli_fetch_array($res_result);
if ($domainData['status'] == "N") {
    $join_link = "ma.php";
} else if ($_REQUEST['mem_code']) {
    $sql_recom_id = "SELECT mem_id FROM Gn_Member WHERE mem_code='{$_REQUEST['mem_code']}'";
    $res_recom_id = mysqli_query($self_con, $sql_recom_id);
    $row_recom_id = mysqli_fetch_array($res_recom_id);
    if ($_SERVER['HTTP_HOST'] != "kiam.kr") {
        $join_link = get_join_link("https://" . $_SERVER['HTTP_HOST'], $row_recom_id['mem_id'], "");
    } else {
        $join_link = get_join_link("https://www.kiam.kr", $row_recom_id['mem_id'], "");
    }
} else {
    if ($_SERVER['HTTP_HOST'] != "kiam.kr") {
        $join_link = get_join_link("https://" . $_SERVER['HTTP_HOST'], "", "");
    } else {
        $join_link = get_join_link("https://www.kiam.kr", "", "");
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if ($sub_domain == true && !empty($domainData['site_name'])) { ?>
        <title><?= htmlspecialchars($domainData['site_name'], ENT_QUOTES); ?> | AI 마케팅 자동화, CRM & 문자 홍보 솔루션</title>
        <meta name="description" content="AI 기반 마케팅 자동화 솔루션, 모바일/종이명함 통합 관리, 퍼널 메시징 및 CRM 관리. 고객 관리와 신규 유입을 손쉽게 관리하세요!" />
        <meta name="keywords" content="AI 마케팅, CRM 솔루션, 문자 홍보, 모바일 명함, 퍼널 메시징, 고객 관리, 신규 고객 유입, 설문조사, 랜딩 제작" />
        <meta name="naver-site-verification" content="9bdee333e435ebfebaa34042ad96ed608842c206" />
    <?php   } else { ?>
        <title>아이엠프로(IAMPRO) - 타겟 마케팅 자동화 솔루션</title>
        <meta name="description" content="아이엠프로(IAMPRO) | AI 퍼널 메시징, CRM 및 문자 홍보 솔루션 제공. 기존 고객과 신규 고객 관리 자동화 시스템." />
        <meta name="keywords" content="아이엠프로(IAMPRO), 타겟 마케팅, CRM 솔루션, 문자 자동화, 고객 관리, 신규 고객 유입, AI 마케팅, 랜딩 제작, 설문조사" />
        <meta name="naver-site-verification" content="9bdee333e435ebfebaa34042ad96ed608842c206" />
        <meta name="naver-site-verification" content="97974eb9f5255cf63e5e23f8dee9613403f66ec8" />

    <?php   } ?>
    <!-- Pretendard 웹폰트 (CDN) - 모든 환경에서 동일한 폰트 적용 -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <!--<link href='<?= $path ?>css/nanumgothic.css' rel='stylesheet' type='text/css'/>-->
    <link href='<?= $path ?>css/main.css' rel='stylesheet' type='text/css' />
    <link href='/css/sub_4_re.css' rel='stylesheet' type='text/css' />
    <link href='<?= $path ?>css/responsive.css' rel='stylesheet' type='text/css' /><!-- 2019.11 반응형 CSS -->
    <link href='<?= $path ?>css/font-awesome.min.css' rel='stylesheet' type='text/css' /><!-- 2019.11 반응형 CSS -->
    <script language="javascript" src="<?= $path ?>js/jquery-2.1.0.js"></script>
    <script language="javascript" src="<?= $path ?>js/jquery.carouFredSel-6.2.1-packed.js"></script>
    <script type="text/javascript" src="/jquery.lightbox_me.js"></script>
    <script type="text/javascript" src="/jquery.cookie.js"></script>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <!--<script async src="https://www.googletagmanager.com/gtag/js?id=G-3E40Q09QGE"></script>-->
    <script>
        window.dataLayer = window.dataLayer || [];

        function newpop1(str) {
            window.open(str, "notice_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=600,height=350");
        }

        function Gesi_getCookie(name) {
            var nameOfCookie = name + '=';
            var x = 0;
            while (x <= document.cookie.length) {
                var y = (x + nameOfCookie.length);
                if (document.cookie.substring(x, y) == nameOfCookie) {
                    if ((endOfCookie = document.cookie.indexOf(';', y)) == -1)
                        endOfCookie = document.cookie.length;
                    return unescape(document.cookie.substring(y, endOfCookie));
                }
                x = document.cookie.indexOf(' ', x) + 1;
                if (x == 0)
                    break;
            }
            return '';
        }

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-3E40Q09QGE');
        $(function() {
            $('.sub_menu').hide();
            $('.main_link').on("hover", function() {
                $('.sub_menu').hide();
                var index = $(".main_link").index(this);
                $(".main_link:eq(" + index + ")").parent().find("ul").show();
            });
            $('.head_right_2').on("mouseout", function() {
                //$('.sub_menu').delay(5000).hide(0);
            });
            $('.main_link').on("click", function() {
                $('.sub_menu').hide();
                var index = $(".main_link").index(this);
                $(".main_link:eq(" + index + ")").parent().find("ul").show();
            });
            $('.head_left, .head_right_1, .container').on("mouseover", function() {
                $('.sub_menu').hide();
            });
            $('.sub_menu').on("mouseleave", function() {
                $('.sub_menu').hide();
            });
            $('.b_exit').on("click", function() {
                $('.ad_header').hide();
            });
        });

        function parent_alert(msg) {
            alert(msg);
        }
    </script>
    <style>
    /* 브레드크럼 서브메뉴 다크모드 호환 (2026-05-03)
       main.css에 .right_sub_menu a / .left_sub_menu a 기본 color 없어서
       다크모드 페이지에서 검정색으로 안 보이는 문제 해결 */
    .head_breadcrumb .left_sub_menu a,
    .head_breadcrumb .right_sub_menu a {
        color: #e9ecef;                     /* 밝은 회색 (다크 배경 위) */
        font-weight: 500;
    }
    .head_breadcrumb .left_sub_menu a:hover,
    .head_breadcrumb .right_sub_menu a:hover {
        color: #FACC2E;                     /* 노란색 강조 */
    }
    /* 라이트 배경 페이지 보호 — 배경 밝은 경우 */
    .big_sub:not([class*="nm-dv2"]) .head_breadcrumb .left_sub_menu a,
    .big_sub:not([class*="nm-dv2"]) .head_breadcrumb .right_sub_menu a,
    body:not(.big_div):not([class*="dark"]) .head_breadcrumb .left_sub_menu a,
    body:not(.big_div):not([class*="dark"]) .head_breadcrumb .right_sub_menu a {
        color: #495057;                     /* 어두운 회색 (라이트 배경 위) */
    }
    </style>
</head>

<body class="page-sub" <?= isset($_GET['popup']) ? 'style="padding-top:0px"' : '' ?>>
    <?php if (!isset($_GET['popup'])) { 
        // Modern Header 적용 (테마 전환 + 모달 + 드롭다운 메뉴)
        include 'includes/modern_header_v01.php';
    } ?>
    <?php if (!isset($_GET['popup'])) { ?>
    <script>
    /* ── modern-header fixed 높이 동적 감지 → body padding-top 자동 조정 (전체 페이지 공통) ── */
    (function() {
      function adjustForHeader() {
        var header = document.querySelector('.modern-header');
        if (header) {
          document.body.style.paddingTop = header.offsetHeight + 'px';
        }
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', adjustForHeader);
      } else {
        adjustForHeader();
      }
      window.addEventListener('resize', adjustForHeader);
      window.addEventListener('load', adjustForHeader);
    })();
    </script>
    <?php } ?>
    <div class="big_1 head_breadcrumb" style="display:none;width:100%;position:absolute;">
        <div class="m_head_div" id="sub1" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_1.php">온리원문자</a> >
                <a href="?status=<?= $_REQUEST['status'] ?>"><?= $left_str ?></a>
            </div>
            <div style="position:absolute;left:450px;">
                <a href="sub_1.php">폰문자소개</a> ㅣ
                <a href="sub_5.php">휴대폰등록</a> ㅣ
                <?php if ($pay_data['onestep1'] != "ON" || $pay_data['stop_yn'] == "Y"/*&& $iam_type != 2*/) { ?>
                    <a onclick="alert('결제 후 사용가능합니다.');">문자발송</a> |
                <?php   } else { ?>
                    <a href="sub_6.php">문자발송</a> ㅣ
                <?php   } ?>
                <?php if ($mem_type == "V") { ?>
                    <a href="sub_6_elc.php">선거문자</a> ㅣ
                <?php   } ?>
                <a href="sub_4_unit_.php">수발신내역</a>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sub10" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_10.php">아이엠</a>
            </div>
            <div style="position:absolute;left:200px;">
                <a href="sub_10.php">아이엠소개</a> ㅣ
                <a href="/?cur_win=best_sample" target="_blank">아이엠샘플</a> ㅣ
                <a href="https://play.google.com/store/apps/details?id=mms.onepagebook.com.onlyonesms&pli=1" target="_blank">아이엠설치</a> ㅣ
                <a href="<?= $href ?>" target="_blank">아이엠접속</a> ㅣ
                <a href="https://tinyurl.com/557nca2b" target="_blank">아이엠매뉴얼</a> |
                <?php
                $sql_chk = "SELECT count(a.mem_code) as cnt FROM Gn_Member a inner join Gn_Iam_Service b on a.mem_id=b.mem_id WHERE a.service_type>=2 AND a.mem_id='{$_SESSION['one_member_id']}'";
                $res_chk = mysqli_query($self_con, $sql_chk);
                $row_chk = mysqli_fetch_array($res_chk);
                if ($row_chk[0] || $_SESSION['one_member_id'] == 'obmms02') {
                ?>
                    <a href="calliya.php">콜이야</a>
                <?php   } ?>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sub2" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_2.php">온리원디버</a> >
                <a href="?status=<?= $_REQUEST['status'] ?>"><?= $left_str ?></a>
            </div>
            <div style="position:absolute;left:320px;">
                <a href="sub_2.php">디버알아보기</a> |
                <a href="/cliente_list.php?status=1&one_no=85">디버설치하기</a> |
                <a href="https://tinyurl.com/2p8ehzsm" target="_blank">디버수집하기</a>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sub15" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_11.php">온리원콜백</a> >
                <a href="?status=<?= $_REQUEST['status'] ?>"><?= $left_str ?></a>
            </div>
            <div style="position:absolute;left:430px;">
                <a href="sub_11.php">콜백알아보기</a> ㅣ <a href="https://play.google.com/store/apps/details?id=mms.onepagebook.com.onlyonesms&pli=1" target="_biank">콜백설치하기</a>ㅣ <a href="https://tinyurl.com/3teh9ez5" target="_blank">콜백이용하기</a>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sub12">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="/sub_12.php">퍼널발송</a>
            </div>
            <div class="right_sub_menu">&nbsp;
                <a href="/sub_12.php">퍼널소개</a> |
                <?php if ($pay_data['onestep1'] != "ON" || $pay_data['stop_yn'] == "Y"/*&& $iam_type != 2*/) { ?>
                    <a onclick="alert('결제 후 사용가능합니다.');">인포랜딩</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">모듈랜딩</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">신청관리</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">고객관리</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">퍼널관리</a>
                    <!-- <a onclick="alert('결제 후 사용가능합니다.');">기존고객관리</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">발송예정내역</a> |
                    <a onclick="alert('결제 후 사용가능합니다.');">발송결과내역</a> -->

                <?php   } else { ?>
                    <a href="/mypage_landing_list.php">인포랜딩</a> |
                    <a href="/iam/mypage_report.php" target="_blank">모듈랜딩</a> |
                    <a href="/mypage_link_list.php">신청관리</a> |
                    <a href="/mypage_request_list.php">고객관리</a> |
                    <a href="/mypage_reservation_list.php">퍼널관리</a>
                    <!-- <a href="/mypage_oldrequest_list.php">기존고객관리</a> |
                    <a href="/mypage_wsend_list.php">발송예정내역</a> |
                    <a href="/mypage_send_list.php">발송결과내역</a> -->
                <?php   } ?>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sub13">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_13.php">온리원쇼핑</a>
            </div>
            <div class="right_sub_menu">
                <a href="http://onlyonemall.net/">온리원쇼핑몰</a> |
                <a href="sub_13.php">국내제휴쇼핑</a> ㅣ
                <a href="/?cCT6LD1no7">해외제휴쇼핑</a> |
                <a href="http://kims3925.onlyonemall.net/">바자회쇼핑몰</a>
            </div>
            <p style="clear:both;"></p>
        </div>
        <!--
        <div class="m_head_div" id="sub11" style="position:relative;">
			<div class="left_sub_menu">
			    <a href="./">홈</a> >
			    <a href="sub_11.php">온리원콜백</a>
			</div>
			<div style="position:absolute;left:430px;">
			    <a href="sub_11.php">콜백알아보기</a> ㅣ <a href="https://play.google.com/store/apps/details?id=mms.onepagebook.com.onlyonesms&pli=1" target="_biank">콜백설치하기</a> ㅣ <a href="https://tinyurl.com/4j8ez8x3" target="_blank">콜백이용하기</a>
			</div>
			<p style="clear:both;"></p>
        </div> -->
        <div class="m_head_div" id="sub8" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="sub_8.php">솔루션소개</a> >
                <a href="?status=<?= $_REQUEST['status'] ?>"><?= $left_str ?></a>
            </div>
            <div style="position:absolute;left:140px;">
                <a href="sub_8.php#Introduce">셀링솔루션소개</a> ㅣ
                <a href="https://tinyurl.com/hb2pp6n2" target="_blank">매뉴얼따라하기</a>
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="sms" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="https://rcs.kiam.kr" target="_balnk">웹문자</a> >
            </div>
            <!--div style="position:absolute;left:770px;">
                <a href="/sub_16.php">웹문자소개</a> ㅣ <a href="/link.php" target="_blank">웹문자접속</a>
            </div-->
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="isms" style="position:relative;">
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="http://www.smsallline.com/home/login">국제문자</a> >
            </div>
            <p style="clear:both;"></p>
        </div>
        <div class="m_head_div" id="head_pay" style="position:relative;">
            <?php if ($is_pay_version) { ?>
                <div class="left_sub_menu">
                    <a href="./">홈</a> >
                    <a href="pay.php">결제안내</a>
                </div>
            <?php   } ?>
            <p style="clear:both;"></p>
        </div>
    </div>
    <script>
        //배너 처리부분
        var i = 0;
        while (1) {
            if (document.getElementById("banner_" + i)) {
                document.getElementById("banner_" + i).style.left = 1000 * i + "px";
                document.getElementById("banner_" + i).style.width = 1000 + "px";
            } else
                break;
            i++;
        }

        function pop_hide() {
            $(".popupbox").hide();
            //clearTimeout(tid);
            $('.big_1').show();
            $('.m_head_div').hide();
        }
        $('#mpay').mouseover(function() {
            pop_hide();
            $('#head_pay').show();
        });

        $('#msub12').mouseover(function() {
            pop_hide();
            $('#sub12').show();
        });
        $('#msub13').mouseover(function() {
            pop_hide();
            $('#sub13').show();
        });
        $('#msub15').mouseover(function() {
            pop_hide();
            $('#sub15').show();
        });
        $('#msub1').mouseover(function() {
            pop_hide();
            $('#sub1').show();
        });
        $('#msub10').mouseover(function() {
            pop_hide();
            $('#sub10').show();
        });
        $('#msub8').mouseover(function() {
            pop_hide();
            $('#sub8').show();
        });
        $('#msub2').mouseover(function() {
            pop_hide();
            $('#sub2').show();
        });
        $('#msub_daily').mouseover(function() {
            pop_hide();
            $('#sub_daily').show();
        });
        $('#msms').mouseover(function() {
            pop_hide();
            $('#sms').show();
        });
        $('#misms').mouseover(function() {
            pop_hide();
            $('#isms').show();
        });
        $('.big_1').mouseleave(function() {
            $('.big_1').hide();
            $('.top_menu').show();
        });
        var inter = 0;
        var top_banner_speed = 30;
        setInterval(function() {
            var i = 0;
            var image_size = 1000;
            while (1) {
                if (document.getElementById("banner_" + i)) {
                    var temp = image_size * i - inter;
                    document.getElementById("banner_" + i).style.left = temp + "px";
                } else
                    break;
                i++;
            }
            inter += 2;
            if (inter >= image_size * (i / 2))
                inter = 0;
        }, top_banner_speed);
    </script>
