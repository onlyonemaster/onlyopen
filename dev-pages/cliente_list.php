<?php   
$path = "./";
include_once "_head_v01.php";

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ============================================
   nm-darkver2 — cliente_list.php (고객센터)
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
    --nm-dv2-gold:      #ffd54f;
}

/* ── 페이지 래퍼 ── */
.big_div  { background: var(--nm-dv2-bg); color: var(--nm-dv2-text); min-height: 100vh; }
.big_sub  { background: var(--nm-dv2-bg); }
.m_div    { background: var(--nm-dv2-bg); display: flex; align-items: flex-start; max-width: 1400px; margin: 0 auto; }
.mrl-wrap { max-width: 1400px; width: 100%; margin: 0 auto; padding: 20px 20px 80px; box-sizing: border-box; flex: 1; min-width: 0; }

/* ── 기존 body/.big_main → 다크모드 ── */
body,.big_main,.big_1{background:var(--nm-dv2-bg)!important;color:var(--nm-dv2-text)!important}

/* ── 타이틀 영역 ── */
.a1 { color: var(--nm-dv2-text) !important; font-size: 17px; font-weight: 700; padding: 4px 0 12px; }

/* ── 버튼 시스템 ── */
.nm-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;border:1px solid var(--nm-dv2-border);color:var(--nm-dv2-text);background:var(--nm-dv2-input-bg);cursor:pointer;text-decoration:none;transition:all .2s}
.nm-btn:hover{background:var(--nm-dv2-hover);border-color:var(--nm-dv2-accent);color:var(--nm-dv2-text)}
.nm-btn-primary{background:var(--nm-dv2-accent);color:#fff;border-color:var(--nm-dv2-accent);font-weight:700}
.nm-btn-primary:hover{background:#6a9fff;color:#fff}
.nm-btn-danger{background:var(--nm-dv2-red);color:#fff;border-color:var(--nm-dv2-red)}
.nm-btn-danger:hover{background:#d46868;color:#fff}
.nm-btn-outline{background:transparent;color:var(--nm-dv2-accent);border:1px solid var(--nm-dv2-accent)}
.nm-btn-outline:hover{background:var(--nm-dv2-accent);color:#fff}
.nm-btn-search{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;background:var(--nm-dv2-accent);color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:all .2s}
.nm-btn-search:hover{background:#6a9fff;color:#fff}

/* ── 뱃지 ── */
.nm-badge-reply-done{display:inline-block;padding:3px 10px;border-radius:20px;background:rgba(130,200,54,.15);color:var(--nm-dv2-green);font-size:12px;font-weight:600}
.nm-badge-reply-wait{display:inline-block;padding:3px 10px;border-radius:20px;background:rgba(200,160,80,.15);color:var(--nm-dv2-gold);font-size:12px;font-weight:600}
.nm-badge-secret{display:inline-block;padding:2px 8px;border-radius:12px;background:rgba(224,90,90,.12);color:var(--nm-dv2-red);font-size:12px;font-weight:600}

/* ── 카테고리 탭 ── */
.cat-tabs{display:flex;flex-wrap:wrap;gap:6px}
.cat-tab{padding:6px 14px;border-radius:20px;font-size:13px;font-weight:500;background:var(--nm-dv2-input-bg);color:var(--nm-dv2-muted);text-decoration:none;transition:all .2s;border:1px solid transparent}
.cat-tab:hover,.cat-tab.active{background:var(--nm-dv2-accent);color:#fff;border-color:var(--nm-dv2-accent)}

/* ── 검색 영역 ── */
.a2{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding:16px 0;margin:16px 0;border-bottom:1px solid var(--nm-dv2-border)}
.a2 select,.a2 input[type=text]{padding:8px 12px;border-radius:8px;border:1px solid var(--nm-dv2-border);background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);font-size:14px}
.a2 input[type=text]{width:200px}
.a2 input[type=text]::placeholder{color:var(--nm-dv2-muted)}

/* ── 폼 요소 ── */
select, input[type=text], textarea {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
}
input[type=text]:focus, textarea:focus, select:focus {
    border-color: var(--nm-dv2-green) !important;
    outline: none;
    box-shadow: 0 0 0 2px rgba(130,200,54,0.18);
}
input[type=checkbox]{accent-color:var(--nm-dv2-accent)}

/* ── 리스트 테이블 ── */
.list_table{width:100%;border-collapse:collapse;background:var(--nm-dv2-card);border-radius:12px;overflow:hidden;box-shadow:var(--nm-dv2-shadow)}
.list_table tr:first-child td,
.list_table thead th { background: var(--nm-dv2-card2) !important; color: var(--nm-dv2-green) !important; font-weight: 700; text-align: left !important; }
.list_table td{padding:12px 10px;border-bottom:1px solid var(--nm-dv2-border);font-size:14px !important;color:var(--nm-dv2-text) !important;vertical-align:middle;text-align:left !important}
.list_table tr:hover td{background:var(--nm-dv2-hover)!important}
.list_table a{color:var(--nm-dv2-accent);font-size:14px !important;text-decoration:none}
.list_table a:hover{color:var(--nm-dv2-green);text-decoration:underline}
.important-star{color:var(--nm-dv2-gold);font-size:16px;margin-right:2px}
.list_table label{font-size:14px !important;color:var(--nm-dv2-text) !important}

/* ── 상세보기 테이블 ── */
.view_table_1{width:100%;border-collapse:collapse;background:var(--nm-dv2-card);border-radius:12px;box-shadow:var(--nm-dv2-shadow);margin-bottom:24px;overflow:hidden}
.view_table_1 td{padding:16px;border-bottom:1px solid var(--nm-dv2-border);color:var(--nm-dv2-text);font-size:14px;line-height:1.7}

/* ── 액션 영역 ── */
.a3{display:flex;gap:10px;justify-content:flex-end;padding:16px 0;flex-wrap:wrap}

/* ── 상단 메뉴 ── */
.left_sub_menu a,.right_sub_menu a{color:var(--nm-dv2-muted);text-decoration:none}
.left_sub_menu a:hover,.right_sub_menu a:hover{color:var(--nm-dv2-accent)}
.top_menu{padding:12px 0;border-bottom:1px solid var(--nm-dv2-border)}

/* ── 페이지네이션 ── */
.page_div a,.page_f a,.page_f span {
    color: var(--nm-dv2-muted) !important;
    background: var(--nm-dv2-card2) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 14px;
    text-decoration: none;
}
.page_div a:hover,.page_div a.on,.page_f a:hover,.page_f span.on {
    color: var(--nm-dv2-green) !important;
    border-color: var(--nm-dv2-green) !important;
}

/* ── 팝업/툴팁 ── */
.popupbox {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 8px;
    box-shadow: var(--nm-dv2-shadow);
}

/* ── 레이블 ── */
label { color: var(--nm-dv2-muted); font-size: 14px; }

/* ── 반응형 ── */
@media (max-width: 768px) {
    .a2 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 13px !important; }
    .list_table td { padding: 8px 6px; font-size: 13px !important; }
    .cat-tabs { gap: 4px; }
    .cat-tab { padding: 4px 10px; font-size: 11px; }
}
@media (max-width: 450px) {
    .list_table td { font-size: 12px !important; padding: 6px 4px; }
    .a2 input[type=text] { width: 120px; }
}

/* ── page-hero 버튼 다크모드 오버라이드 ── */
.page-hero__btn--secondary { color: #fff !important; border-color: rgba(255,255,255,0.5) !important; }
.page-hero__btn--secondary:hover { background: rgba(255,255,255,0.12) !important; color: #fff !important; }

</style>

<?php
switch ($_REQUEST['status']) {
    case 1:
        $left_str = "공지사항";
        break;
    case 2:
        $left_str = "1:1상담";
        break;
    case 3:
        $left_str = "사용후기";
        break;
    case 5:
        $left_str = "관리자 매뉴얼";
        break;
}
if ($_SESSION['one_member_admin_id'] != "") {
    $btn_go = '<a href="/admin/admin_manual.php">관리자 매뉴얼</a>';
}
$sql_serch = "1=1 ";
// [보안패치] status intval 처리
$_safe_status = intval($_REQUEST['status'] ?? 0);
if ($_safe_status >= 1 && $_safe_status <= 9)
    $sql_serch .= "and category='{$_safe_status}' ";
if ($_REQUEST['cat'] && $_REQUEST['cat'] != 0 && $_REQUEST['status'] != 5) {
    switch ($_REQUEST['cat']) {
        case 1:
            $sql_serch .= "and (fl='문자')";
            break;
        case 2:
            $sql_serch .= "and (fl='디버')";
            break;
        case 3:
            $sql_serch .= "and (fl='윈퍼널')";
            break;
        case 4:
            $sql_serch .= "and (fl='아이엠')";
            break;
        case 5:
            $sql_serch .= "and (fl='쇼핑')";
            break;
    }
} else if ($_REQUEST['cat'] && $_REQUEST['cat'] != 0 && $_REQUEST['status'] == 5) {
    switch ($_REQUEST['cat']) {
        case 1:
            $sql_serch .= "and (fl='아이엠')";
            break;
        case 2:
            $sql_serch .= "and (fl='폰문자')";
            break;
        case 3:
            $sql_serch .= "and (fl='디비수집')";
            break;
        case 4:
            $sql_serch .= "and (fl='콜백문자')";
            break;
        case 5:
            $sql_serch .= "and (fl='퍼널문자')";
            break;
        case 6:
            $sql_serch .= "and (fl='웹문자')";
            break;
        case 7:
            $sql_serch .= "and (fl='국제문자')";
            break;
        case 8:
            $sql_serch .= "and (fl='결제')";
            break;
        case 9:
            $sql_serch .= "and (fl='사업')";
            break;
        case 10:
            $sql_serch .= "and (fl='마케팅')";
            break;
        case 11:
            $sql_serch .= "and (fl='디비테이블')";
            break;
        case 12:
            $sql_serch .= "and (fl='카페24')";
            break;
        case 13:
            $sql_serch .= "and (fl='서버')";
            break;
        case 14:
            $sql_serch .= "and (fl='기타')";
            break;
    }
}
if ($_REQUEST['lms_text'] && $_REQUEST['lms_select']) {
    if ($_REQUEST['lms_select'] == "title_content")
        $sql_serch .= " AND (title like '%{$_REQUEST['lms_text']}%' or content like '%{$_REQUEST['lms_text']}%') ";
    else
        $sql_serch .= " AND {$_REQUEST['lms_select']} like '{$_REQUEST['lms_text']}%' ";
}
$sql = "SELECT count(no) as cnt FROM tjd_board WHERE $sql_serch ";
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
    $order_name = "no";
$intPageCount = (int)(($intRowCount + $intPageSize - 1) / $intPageSize);
$sql = "SELECT * FROM tjd_board WHERE $sql_serch AND important_yn = 'Y' order by $order_name $order_status limit $int,$intPageSize";
$result = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
$sql = "SELECT * FROM tjd_board WHERE $sql_serch AND important_yn != 'Y' order by $order_name $order_status limit $int,$intPageSize";
$result2 = mysqli_query($self_con, $sql) or die(mysqli_error($self_con));
?>
<div class="big_div">
    <div class="big_sub">
        <div class="m_div">
            <div class="mrl-wrap">

            <!-- 상단 breadcrumb & 메뉴 -->
            <div class="left_sub_menu">
                <a href="./">홈</a> >
                <a href="cliente_list.php?status=1">고객센터</a> >
                <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>"><?= $left_str ?></a>
            </div>
            <div class="right_sub_menu">
                <a href="cliente_list.php?status=1">공지사항</a> &nbsp;|&nbsp;
                <a href="cliente_list.php?status=2">1:1상담</a> &nbsp;|&nbsp;
                <?= $btn_go ?>
            </div>

            <p style="clear:both;"></p>

            <!-- ── 페이지 히어로 배너 ── -->
                <div class="page-hero page-hero--action" style="margin-bottom:20px;">
                    <div class="page-hero-inner">
                        <div class="page-hero-content">
                            <span class="page-hero-badge">고객센터</span>
                            <h1 class="page-hero-title"><?= $left_str ?> <em>관리</em></h1>
                            <p class="page-hero-desc">고객센터 <?= $left_str ?> 게시글을 확인하고 관리합니다.</p>
                        </div>
                        <div class="page-hero-actions">
                            <?php if ($_REQUEST['status'] == 1) { if ($member_1['level'] == 9) { ?>
                            <a class="page-hero__btn page-hero__btn--secondary" href="cliente_write.php?status=<?=$_safe_status?>">+ 글쓰기</a>
                            <?php } } else { if ($_SESSION['one_member_id']) { ?>
                            <a class="page-hero__btn page-hero__btn--secondary" href="cliente_write.php?status=<?=$_safe_status?>">+ 글쓰기</a>
                            <?php } else { ?>
                            <a class="page-hero__btn page-hero__btn--secondary" href="javascript:void(0)" onclick="alert('로그인하세요')">+ 글쓰기</a>
                            <?php } } ?>
                        </div>
                    </div>
                </div>

            <!-- ── 게시판 본문 ── -->
        <div class="client">
            <form name="board_list_form" action="" method="post">
                <input type="hidden" name="page" value="<?php   echo $page; ?>">
                <input type="hidden" name="page2" value="<?php   echo $page; ?>">
                <?php  
                if ($_REQUEST['one_no'] && strlen($_REQUEST['one_no']) < 4) {
                    $sql_no = "SELECT * FROM tjd_board WHERE no='{$_REQUEST['one_no']}'";
                    $res_no = mysqli_query($self_con, $sql_no);
                    $row_no = mysqli_fetch_array($res_no);
                    //if(!$_SESSION['one_member_id']){
                    //    echo "<script>alert('로그인후 이용해 주세요.');history.go(-1);</script>";
                    //    exit;
                    //}
                    if ($row_no['id'] != $_SESSION['one_member_id'] && $row_no['status_1'] == "Y") {
                        echo "<script>location.replace('/mypage.php?msg=" . urlencode('비밀글입니다. 로그인후 이용해 주세요.') . "');</script>";
                        exit;
                    }
                ?>
                    <table class="view_table_1" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:90%;"><?= htmlspecialchars_decode($row_no['title']) ?></td>
                            <td style="text-align:right;"><?= substr($row_no['date'], 0, 10) ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"><?= htmlspecialchars_decode($row_no['content']) ?></td>
                        </tr>
                        <?php   if ($row_no['reply']) { ?>
                            <tr>
                                <td colspan="2">
                                    <h2>답변입니다</h2><BR><?= htmlspecialchars_decode($row_no['reply']) ?>
                                </td>
                            </tr>
                        <?php   } ?>
                        <?php  
                        if ($row_no['adjunct_2']) {
                            $file_1_arr = explode("\n", $row_no['adjunct_1']);
                            $file_2_arr = explode("\n", $row_no['adjunct_2']);
                            $img_arr = array();
                            $order_1_arr = array();
                            $order_2_arr = array();
                            foreach ($file_2_arr as $key => $v) {
                                $extr = explode(".", $v);
                                if (in_array(strtolower($extr[count($extr) - 1]), $fileTypes))
                                    array_push($img_arr, $v);
                                else {
                                    array_push($order_1_arr, $file_1_arr[$key]);
                                    array_push($order_2_arr, $v);
                                }
                            }
                            if (count($img_arr)) {
                        ?>
                                <tr>
                                    <td colspan="2">
                                        <div style="margin:5px 0 0 5px;">
                                            <?php   if (strstr($img_arr[0], 'pdf')) { ?>
                                            <?php   } else { ?>
                                                <img id='view_main_img' src="adjunct/board/thum1/<?= $row_no['up_path'] ?>/<?= $img_arr[0] ?>" />
                                            <?php   } ?>
                                        </div>
                                        <div style="margin:5px 0 0 5px;">
                                            <?php  
                                            for ($i = 0; $i < count($img_arr); $i++) {
                                                if (strstr($img_arr[$i], 'pdf')) {
                                            ?>
                                                    <a href="adjunct/board/thum/<?= $row_no['up_path'] ?>/<?= $img_arr[$i] ?>" target="_blank" /><?php   echo $file_1_arr[$i]; ?></a>
                                                <?php  
                                                } else {
                                                ?>
                                                    <a href="javascript:void(0)" onmouseover="$('#view_main_img').attr('src','adjunct/board/thum1/<?= $row_no['up_path'] ?>/<?= $img_arr[$i] ?>')"><img src="adjunct/board/thum2/<?= $row_no['up_path'] ?>/<?= $img_arr[$i] ?>" width="50" height="50" /></a>
                                            <?php  
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php  
                            }
                            if (count($order_1_arr)) {
                            ?>
                                <tr>
                                    <td colspan="2">
                                        <div>
                                            <?php  
                                            for ($i = 0; $i < count($order_1_arr); $i++) {
                                            ?>
                                                <a href="javascript:void(0)"><?= $order_1_arr[$i] ?></a>
                                            <?php  
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                        <?php  
                            }
                        } ?>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <a href="cliente_list.php?status=<?=$_safe_status?>" class="nm-btn nm-btn-outline"><i class="fas fa-list"></i> 목록</a>
                                <?php  
                                if ($member_1['mem_id'] == $row_no['id']) {
                                ?>
                                    <a href="cliente_write.php?status=<?=$_safe_status?>&one_no=<?=$row_no['no']?>" class="nm-btn nm-btn-outline"><i class="fas fa-edit"></i> 수정</a>
                                    <a href="javascript:void(0)" onclick="board_del('<?=$row_no['no']?>','<?=$_safe_status?>')" class="nm-btn nm-btn-danger"><i class="fas fa-trash-alt"></i> 삭제</a>
                                <?php  
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                <?php   } ?>
                <div class="a2">
                    <?php   if ($_REQUEST['status'] != 5) { ?>
                        <div class="cat-tabs">
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab<?= (!$_REQUEST['cat']||$_REQUEST['cat']==0)?' active':'' ?>">전체</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab<?= $_REQUEST['cat']==1?' active':'' ?>">문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab<?= $_REQUEST['cat']==2?' active':'' ?>">디버</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=3" class="cat-tab<?= $_REQUEST['cat']==3?' active':'' ?>">윈퍼널</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=4" class="cat-tab<?= $_REQUEST['cat']==4?' active':'' ?>">아이엠</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=5" class="cat-tab<?= $_REQUEST['cat']==5?' active':'' ?>">쇼핑</a>
                        </div>
                    <?php   } else { ?>
                        <div class="cat-tabs">
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab<?= (!$_REQUEST['cat']||$_REQUEST['cat']==0)?' active':'' ?>">전체</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab<?= $_REQUEST['cat']==1?' active':'' ?>">아이엠</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab<?= $_REQUEST['cat']==2?' active':'' ?>">폰문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=3" class="cat-tab<?= $_REQUEST['cat']==3?' active':'' ?>">디비수집</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=4" class="cat-tab<?= $_REQUEST['cat']==4?' active':'' ?>">콜백문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=5" class="cat-tab<?= $_REQUEST['cat']==5?' active':'' ?>">퍼널문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=6" class="cat-tab<?= $_REQUEST['cat']==6?' active':'' ?>">웹문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=7" class="cat-tab<?= $_REQUEST['cat']==7?' active':'' ?>">국제문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=8" class="cat-tab<?= $_REQUEST['cat']==8?' active':'' ?>">결제</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=9" class="cat-tab<?= $_REQUEST['cat']==9?' active':'' ?>">사업</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=10" class="cat-tab<?= $_REQUEST['cat']==10?' active':'' ?>">마케팅</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=11" class="cat-tab<?= $_REQUEST['cat']==11?' active':'' ?>">디비테이블</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=12" class="cat-tab<?= $_REQUEST['cat']==12?' active':'' ?>">카페24</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=13" class="cat-tab<?= $_REQUEST['cat']==13?' active':'' ?>">서버</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=14" class="cat-tab<?= $_REQUEST['cat']==14?' active':'' ?>">기타</a>
                        </div>
                    <?php   } ?>
                    <select name="lms_select">
                        <?php  
                        $select_lms_arr = array("title_content" => "제목+내용");
                        foreach ($select_lms_arr as $key => $v) {
                            $selected = $_REQUEST['lms_select'] == $key ? "selected" : "";
                        ?>
                            <option value="<?= $key ?>" <?= $selected ?>><?= $v ?></option>
                        <?php  
                        }
                        ?>
                    </select>
                    <input type="text" name="lms_text" value="<?= $_REQUEST['lms_text'] ?>" />
                    <a href="javascript:void(0)" onclick="board_list_form.submit();" class="nm-btn-search"><i class="fas fa-search"></i> 검색</a>
                </div>
                <div>
                    <table class="list_table" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:5%;"><label><input type="checkbox" onclick="check_all(this,'no_box')" />번호</label></td>
                            <?php   if ($_REQUEST['status'] == 2) { ?>
                                <td style="width:10%;">분류</td>
                                <td style="width:10%;">답변여부</td>
                            <?php   } else { ?>
                                <td style="width:10%;">서비스</td>
                            <?php   } ?>
                            <td style="width:<?= $_REQUEST['status'] == 2 ? "60%" : "70%" ?>;text-align:left">제목</td>
                            <td style="width:10%;">등록일자</td>
                            <?php   if ($_REQUEST['status'] != 2 && $_REQUEST['status'] != 4) { ?>
                                <td style="width:10%;">조회수</td>
                            <?php   } ?>
                        </tr>
                        <?php  
                        if ($intRowCount) {
                            while ($row = mysqli_fetch_array($result)) {
                        ?>
                                <tr style="<?= $row['important_yn'] == 'Y' ? 'background:rgba(255,213,79,0.10)' : '' ?>">
                                    <td><label><input type="checkbox" value="<?= $row['no'] ?>" name="no_box" /><?= $sort_no ?></label></td>
                                    <?php   if ($_REQUEST['status'] == 2) { ?>
                                        <td><?= $fl_arr[$row['fl']] ?></td>
                                        <td><?php if($row['reply']): ?><span class="nm-badge-reply-done"><i class="fas fa-check-circle"></i> 답변완료</span><?php else: ?><span class="nm-badge-reply-wait"><i class="fas fa-clock"></i> 문의접수</span><?php endif; ?></td>
                                    <?php   } else { ?>
                                        <td><?= $row['fl'] ?></td>
                                    <?php   } ?>
                                    <td style="text-align:left">
                                        <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row['no'] ?>"><?= $row['title'] ?></a> <?php if($row['status_1']=="Y"): ?><span class="nm-badge-secret"><i class="fas fa-lock"></i> 비밀글</span><?php endif; ?>
                                    </td>
                                    <td><?= substr($row['date'], 0, 10) ?></td>
                                    <?php   if ($_REQUEST['status'] != 2 && $_REQUEST['status'] != 4) { ?>
                                        <td><?= $row['view_cnt'] ?></td>
                                    <?php   } ?>
                                </tr>
                            <?php  
                                $sort_no--;
                            }
                            while ($row = mysqli_fetch_array($result2)) { ?>
                                <tr style="<?= $row['important_yn'] == 'Y' ? 'background:rgba(255,213,79,0.10)' : '' ?>">
                                    <td><label><input type="checkbox" value="<?= $row['no'] ?>" name="no_box" /><?= $sort_no ?></label></td>
                                    <?php   if ($_REQUEST['status'] == 2) { ?>
                                        <td><?= $fl_arr[$row['fl']] ?></td>
                                        <td><?php if($row['reply']): ?><span class="nm-badge-reply-done"><i class="fas fa-check-circle"></i> 답변완료</span><?php else: ?><span class="nm-badge-reply-wait"><i class="fas fa-clock"></i> 문의접수</span><?php endif; ?></td>
                                    <?php   } else { ?>
                                        <td><?= $row['fl'] ?></td>
                                    <?php   } ?>
                                    <td style="text-align:left">
                                        <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row['no'] ?>"><?= $row['title'] ?></a> <?php if($row['status_1']=="Y"): ?><span class="nm-badge-secret"><i class="fas fa-lock"></i> 비밀글</span><?php endif; ?>
                                    </td>
                                    <td><?= substr($row['date'], 0, 10) ?></td>
                                    <?php   if ($_REQUEST['status'] != 2 && $_REQUEST['status'] != 4) { ?>
                                        <td><?= $row['view_cnt'] ?></td>
                                    <?php   } ?>
                                </tr>
                            <?php  
                                $sort_no--;
                            }
                            ?>

                            <tr>
                                <td colspan="5">
                                    <?php  
                                    page_f($page, $page2, $intPageCount, "board_list_form");
                                    ?>
                                </td>
                            </tr>
                        <?php  
                        } else {
                        ?>
                            <tr>
                                <td colspan="5"><i class="fas fa-search"></i> 검색된 내용이 없습니다.</td>
                            </tr>
                        <?php  
                        }
                        ?>
                    </table>
                </div>
                <div class="a3">
                    <?php  
                    if ($_REQUEST['status'] == 1) {
                        if ($member_1['level'] == 9) {
                    ?>
                            <a href="cliente_write.php?status=<?=$_safe_status?>" class="nm-btn nm-btn-primary"><i class="fas fa-pen"></i> 글쓰기</a>
                        <?php  
                        }
                    } else {
                        if ($_SESSION['one_member_id']) {
                        ?>
                            <a href="cliente_write.php?status=<?=$_safe_status?>" class="nm-btn nm-btn-primary"><i class="fas fa-pen"></i> 글쓰기</a>
                        <?php  
                        } else {
                        ?>
                            <a href="javascript:void(0)" onclick="alert('로그인하세요')" class="nm-btn nm-btn-primary"><i class="fas fa-pen"></i> 글쓰기</a>
                        <?php  
                        }
                    }
                    if ($member_1['level'] == 9) {
                        ?>
                        <a href="javascript:void(0)" onclick="board_del('','<?=$_safe_status?>')" class="nm-btn nm-btn-danger"><i class="fas fa-trash-alt"></i> 선택삭제</a>
                    <?php  
                    }
                    ?>
                </div>
            </form>
        </div>
    </div><!-- .mrl-wrap -->
</div><!-- .m_div -->
</div><!-- .big_sub -->
</div><!-- .big_div -->
<?php  
include_once "_foot.php";
?>