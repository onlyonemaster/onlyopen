<?php   
$path = "./";
include_once "_head_v01.php";
extract($_REQUEST);
if (!$_SESSION['one_member_id']) {
?>
    <script language="javascript">
        location.replace('/ma.php');
    </script>
<?php  
    exit;
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<script>
// Base URL 결정 함수
function getEditorBaseUrl() {
    const path = document.getElementById('landing_path').value;
    if (path && path !== '') {
        return window.location.origin + path + '/';
    }
    return window.location.origin + '/';
}
</script>
<?php
$sql = "SELECT * FROM Gn_landing  WHERE landing_idx='{$landing_idx}'";
$sresul_num = mysqli_query($self_con, $sql);
$row = mysqli_fetch_array($sresul_num);
?>
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
        })

    });
    //gpt chat script
    function show_chat(api) {
        $("#gpt_chat_modal").show();
        $("#tutorial-loading").show();
        $('body,html').animate({
            scrollTop: 250,
        }, 100);
    }

    var contextarray = [];

    $(document).ready(function() {
        var textarea = document.getElementById("question");
        var limit = 100; //height limit
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
    //             $("#question").css("height", "50px");
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
        var detail1 = '';
        var arr = detail.split("\n");
        for (var i = 0; i < arr.length; i++) {
            var str = '<p>' + arr[i].replace("\n", "") + '</p>';
            detail1 += str;
        }
        if (title == "") {
            alert('질문해주세요.');
            return;
        }
        window.editor1.setData(detail1);
        $("input[name=title]").val(title);
        // $("#description").val(detail);
        $("#gpt_chat_modal").hide();
        $("#tutorial-loading").hide();
    }

    function hide_gpt_box() {
        $("#gpt_chat_modal").hide();
        $("#tutorial-loading").hide();
    }
</script>
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 랜딩페이지 만들기/수정
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

/* ── 폼 영역 (p1) ── */
.p1 {
    background: var(--nm-dv2-card);
    border: 1px solid var(--nm-dv2-border);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 14px;
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

/* ── 테이블 (list_table1 → nm-darkver2) ── */
.list_table1 {
    border-collapse: collapse;
    width: 100%;
    font-size: var(--fz-small, 13px);
}
.list_table1 td,
.list_table1 th {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    padding: 8px 10px;
    vertical-align: middle;
}
.list_table1 tr:first-child td,
.list_table1 tr:first-child th {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
    border-top: 1px solid var(--nm-dv2-border) !important;
    border-bottom: 1px solid var(--nm-dv2-border) !important;
}
.list_table1 input[type=text] {
    width: 100%;
    max-width: 600px;
    height: 34px;
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 4px 10px;
}

/* ── 라디오 버튼 ── */
input[type=radio] {
    accent-color: var(--nm-dv2-green);
    margin: 0 4px 0 10px;
}

/* ── w200 ── */
.w200 { width: 200px; }

/* ── 팝업 박스 ── */
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

/* ── GPT 채팅 모달 (tooltiptext-bottom2) ── */
.tooltiptext-bottom2 {
    width: 720px;
    font-size: 15px;
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    text-align: left;
    position: absolute;
    z-index: 200;
    top: 400px;
    left: 30%;
    border: 1px solid var(--nm-dv2-border);
    border-radius: 10px;
    box-shadow: var(--nm-dv2-shadow);
}
.title_app {
    text-align: center;
    background: var(--nm-dv2-green) !important;
    padding: 10px;
    font-size: var(--fz-step, 17px);
    font-weight: var(--fw-bold, 700);
    color: #000 !important;
    border-radius: 10px 10px 0 0;
}
.desc_app {
    padding: 15px;
}
.button_app {
    text-align: center;
    padding: 10px;
}

/* ── 로딩 오버레이 ── */
#ajax-loading {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 9000;
    text-align: center;
    display: none;
    background: rgba(24,23,47,0.80);
}
#ajax-loading img {
    position: absolute;
    top: 50%; left: 50%;
    width: 120px; height: 120px;
    margin: -60px 0 0 -60px;
}
#tutorial-loading {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 150;
    text-align: center;
    display: none;
    background: rgba(24,23,47,0.80);
}

/* ── GPT 채팅 요소 ── */
.chat_btn {
    color: #fff !important;
    border-radius: 7px;
    background: var(--nm-dv2-accent) !important;
    font-size: var(--fz-label, 14px);
    font-weight: var(--fw-semi, 600);
    float: right;
    border: 1px solid var(--nm-dv2-accent);
    padding: 5px 14px;
    margin-right: 3px;
    cursor: pointer;
    transition: background .18s;
}
.chat_btn:hover {
    background: var(--nm-dv2-green) !important;
    border-color: var(--nm-dv2-green);
    color: #000 !important;
}

#answer_side,
#answer_side1,
#answer_side2 {
    width: 90%;
    height: 400px;
    background: var(--nm-dv2-card) !important;
    margin-right: auto;
    margin-left: auto;
    border-radius: 10px;
    margin-top: 12px;
    padding: 35px 30px 10px 30px;
    overflow: auto;
    text-align: left;
    position: relative;
}

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
    border-width: 0;
    border-radius: 15px;
    font-size: 15px;
    border: 2px solid transparent;
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    outline-width: 0;
    box-shadow: 0 5px 10px -5px rgb(0 0 0 / 30%);
    transition: border-color 1000ms ease-out;
}
.search_keyword textarea:focus {
    border-color: var(--nm-dv2-green) !important;
}
.send_ask {
    position: absolute;
    top: 0;
    right: 60px;
    width: 58px;
    height: 100%;
    background: var(--nm-dv2-card2) !important;
    border-radius: 20px;
    border: none;
    cursor: pointer;
}
.send_ask:hover {
    background: var(--nm-dv2-green) !important;
}

#gpt_req_list_title {
    float: left;
    padding: 7px;
    margin-left: 40px;
    background: var(--nm-dv2-red) !important;
    border-radius: 10px;
    color: #fff !important;
}
.history {
    position: absolute;
    top: 5px;
    left: 80px;
}
.gpt_act {
    position: relative;
    height: 35px;
}
.newpane,
.newpane:hover {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    padding: 4px 8px;
    border-radius: 10px;
    position: absolute;
    top: 5px;
    right: 80px;
    text-decoration: none;
    font-size: var(--fz-small, 13px);
}

/* ── GPT 채팅 내부 ── */
.chat_answer {
    word-break: break-all;
    word-wrap: break-word;
    white-space: pre-wrap;
}
.article-title {
    border-bottom: 1px solid var(--nm-dv2-border);
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
.hided { display: none; }
.copy_msg {
    position: absolute;
    right: 10px;
    top: 10px;
}

/* ── CKEditor 다크모드 보정 ── */
.ck.ck-editor__main > .ck-editor__editable {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
}
.ck.ck-toolbar {
    background: var(--nm-dv2-card2) !important;
    border-color: var(--nm-dv2-border) !important;
}
.ck.ck-toolbar .ck-button {
    color: var(--nm-dv2-text) !important;
}
.ck.ck-toolbar .ck-button:hover {
    background: var(--nm-dv2-hover) !important;
}
.ck.ck-dropdown__panel {
    background: var(--nm-dv2-card) !important;
    border-color: var(--nm-dv2-border) !important;
}

/* ── 파일 input ── */
input[type=file] {
    color: var(--nm-dv2-text) !important;
}
input[type=file]::file-selector-button {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 4px;
    padding: 4px 10px;
}

/* ── container ── */
.container { background: transparent !important; }

/* ── page-hero 덮어쓰기 (main.css 대응) ── */
.page-hero-title,
.page-hero-title em,
.page-hero-desc,
.page-hero__btn--secondary,
.page-hero__btn--primary {
    /* main.css가 글자색을 검정으로 바꾸는 것을 방지 */
}
.page-hero-title     { color: #f0f4ff !important; }
.page-hero-title em  { color: #82c836 !important; }
.page-hero-desc      { color: rgba(255,255,255,0.52) !important; }
.page-hero__btn--secondary { color: rgba(255,255,255,0.85) !important; background: rgba(255,255,255,0.07) !important; border: 1px solid rgba(255,255,255,0.22) !important; }
.page-hero__btn--primary   { color: #0d1f0d !important; background: #82c836 !important; }

/* ── 반응형 ── */
@media (max-width: 768px) {
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table1 input[type=text] { width: 100%; max-width: 100%; }
}

@media only screen and (max-width: 720px) {
    .send_ask { right: 60px; width: 50px; }
}
@media only screen and (max-width: 600px) {
    .send_ask { right: 60px; width: 50px; }
}
@media only screen and (max-width: 450px) {
    .send_ask { right: 60px; width: 50px; height: 98%; }
    .history { left: 35px; }
    .newpane { right: 40px; }
    .tooltiptext-bottom2 { width: 90%; left: 5%; }
}
</style>
<script language="javascript" src="/ckeditor5/build/ckeditor.min.js" charset="utf-8"></script>
<script language="javascript" src="/ckeditor5/build/myuploader.js" charset="utf-8"></script>
<script src="/iam/js/layer.min.js" type="application/javascript"></script>
<script src="/iam/js/chat.js"></script>
<link rel="stylesheet" type="text/css" href="/ckeditor5/build/styles.css">
<div class="big_div">
    <div class="big_sub">
        <div class="m_div">
            <?php   include "mypage_left_menu.php"; ?>
            <div class="mrl-wrap" style="flex:1;min-width:0;">
                <!-- ── 페이지 히어로 배너 ── -->
                <div class="page-hero page-hero--action" style="margin-bottom:20px;">
                    <div class="page-hero-inner">
                        <div class="page-hero-content">
                            <span class="page-hero-badge">랜딩페이지</span>
                            <h1 class="page-hero-title">랜딩페이지 <em>만들기</em></h1>
                            <p class="page-hero-desc">자신의 이벤트 상품이나 서비스를 소개하는 랜딩페이지를 제작합니다.</p>
                        </div>
                        <div class="page-hero-actions">
                            <a class="page-hero__btn page-hero__btn--secondary" href="mypage_landing_list.php">목록으로</a>
                        </div>
                    </div>
                </div>
                <div class="m_body">
                <button onclick="show_chat('<?= $member_1['gpt_chat_api_key'] ?>')" class="chat_btn">AI와 대화하기</button>
                <form name="sform" id="sform" action="mypage.proc.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="<?= $landing_idx ? 'land_updat' : 'land_save'; ?>" />
                    <input type="hidden" name="landing_idx" value="<?= $landing_idx; ?>" />
                    <input type="hidden" name="path" value="<?= htmlspecialchars($row['path'] ?? '') ?>" id="landing_path" />
                    <div class="a1" style="margin-bottom:15px">
                        <div class="popup_holder popup_text">랜딩페이지 만들기
                            <div class="popupbox" style="display:none; height: 56px;width: 274px;left: 155px;top: -37px;">자신의 이벤트 상품이나 서비스를 소개하거나 상세페이지로 보여줄수 있도록 제작하는 기능입니다. <br><br>
                                <a class="detail_view" style="color: var(--nm-dv2-green);" href="https://url.kr/dcpqin" target="_blank">[자세히 보기]</a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="p1">
                            <table class="list_table1" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <th class="w200">랜딩페이지 제목</th>
                                    <td><input type="text" name="title" placeholder="" id="title" value="<?= $row['title'] ?>" /> </td>
                                </tr>
                                <tr>
                                    <th class="w200">랜딩페이지 설명</th>
                                    <td><input type="text" name="description" placeholder="" id="description" value="<?= $row['description'] ?>" /> </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <textarea name="ir1" id="ir1" rows="10" cols="100" style="width:100%; height:400px; min-width:645px; display:none;"><?= $row['content'] ?></textarea>
                                        <div class="editor" id="ckeditor1"><?= $row['content'] ?></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="w200">썸네일</th>
                                    <td>
                                        <input type="file" name="thumbnail" placeholder="" id="thumbnail" />
                                        <a href="javascript:reset('thumbnail')">X</a>
                                        <input type="text" name="thumb_name" id="thumb_name" value="<?= str_replace("\/upload\/", "", $row['img_thumb']) ?>" placeholder="등록된 파일을 삭제하시려면 파일명을 삭제해주세요.">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="w200">첨부파일</th>
                                    <td>
                                        <input type="file" name="file" placeholder="" id="file" />
                                        <a href="javascript:reset('file')" style="margin-left:20px;">X</a>
                                        <input type="text" name="file_name" id="file_name" value="<?= str_replace("\/upload\/", "", $row['file']) ?>" placeholder="등록된 파일을 삭제하시려면 파일명을 삭제해주세요.">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="w200">제출모드</th>
                                    <td>
                                        <?php  
                                        $sql = "SELECT event_title FROM Gn_event WHERE pcode='{$row['pcode']}'";
                                        $eres = mysqli_query($self_con, $sql);
                                        $erow = mysqli_fetch_array($eres);
                                        ?>
                                        <input type="radio" name="request_yn" id="request_y" value="Y" <?= $row['request_yn'] == "Y" ? "checked" : "" ?>>사용
                                        <input type="radio" name="request_yn" id="request_n" value="N" <?= $row['request_yn'] == "N" || $row['request_yn'] == "" ? "checked" : "" ?>>사용안함
                                        <input type="text" name="event_title" placeholder="" id="event_title" value="<?= $erow['event_title'] ?>" readonly style="width:100px;" />
                                        <input type="hidden" name="pcode" id="pcode" value="<?= $row['pcode'] ?>" />
                                        <input type="button" value="조회" class="button" id="searchBtn">
                                    </td>
                                </tr>
                                <?php   if ($_SESSION['one_member_admin_id'] != "" || $member_1['mem_leb'] == 21 || $member_1['mem_leb'] == 60) { ?>
                                    <tr>
                                        <th class="w200">강연신청창 자동삽입</th>
                                        <td>
                                            <input type="radio" name="lecture_yn" id="lecture_y" value="Y" <?php   echo $row['lecture_yn'] == "Y" ? "checked" : "" ?>>사용
                                            <input type="radio" name="lecture_yn" id="lecture_n" value="N" <?php   echo $row['lecture_yn'] == "N" || $row['lecture_yn'] == "" ? "checked" : "" ?>>사용 안함
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="w200">강연신청창 푸터</th>
                                        <td colspan="2">
                                            <textarea name="ir2" id="ir2" rows="10" cols="100" style="width:100%; height:400px; min-width:645px; display:none;"><?= $row['footer_content'] ?></textarea>
                                            <div class="editor" id="ckeditor2"><?= $row['footer_content'] ?></div>
                                        </td>
                                    </tr>
                                <?php   } else { ?>
                                    <tr style="display:none">
                                        <th class="w200">강연신청창 푸터</th>
                                        <td colspan="2">
                                            <input type="hidden" name="lecture_yn" value="<?php   echo $row['lecture_yn']; ?>">
                                            <textarea name="ir2" id="ir2" rows="10" cols="100" style="width:100%; height:400px; min-width:645px; display:none;"><?= $row['footer_content'] ?></textarea>
                                        </td>
                                    </tr>
                                <?php   } ?>
                            </table>
                        </div>
                        <div class="p1" style="text-align:center;margin-top:20px;">
                            <input type="button" value="취소" class="button" id="cancleBtn">
                            <input type="button" value="저장" class="button" id="saveBtn">
                        </div>
                    </div>
                </form>
                </div><!-- .mrl-wrap -->
            </div>
        </div>
    </div>
    <span class="tooltiptext-bottom2" id="gpt_chat_modal" style="display:none;">
        <p class="title_app">콘텐츠 창작AI 알지(ALJI) <span onclick="hide_gpt_box()" style="float:right;cursor:pointer;">X</span></p>
        <div class="container" style="text-align: center;padding: 10px;background: var(--nm-dv2-card);">
            <p style="color: var(--nm-dv2-text);"><img src="/iam/img/arji_intro_title.png" style="width: 22px;margin-right: 3px;">"알지(ALJI)" 인공지능에게 무엇이든 물어보세요.<br>구체적으로 질문할수록 "알지 AI" 답변이 정교해집니다.</p>
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
        <div style="background: var(--nm-dv2-card2);text-align: center;padding: 7px;border-radius: 0 0 10px 10px;">
            <button type="button" style="width:50%;background:var(--nm-dv2-green);color:#000;padding:10px 0px;border:none;font-weight:var(--fw-bold,700);border-radius:6px;cursor:pointer;" onclick="send_chat()">보내기</button>
        </div>
    </span>
</div>
<div id="tutorial-loading"></div>
<div id="ajax-loading"><img src="/iam/img/ajax-loader.gif"></div>
<script>
    function MyCustomUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new MyUploadAdapter(loader);
        };
    }
    ClassicEditor
        .create(document.querySelector('#ckeditor1'), {
            extraPlugins: [MyCustomUploadAdapterPlugin],
            fontFamily: {
                options: [
                    'default',
                    '나눔고딕',
                    '맑은고딕',
                    '굴림체',
                    '명조체',
                    '바탕체',
                    '돋움체',
                    '궁서체',
                ],
                supportAllValues: true
            },
            fontSize: {
                options: [
                    10,
                    11,
                    12,
                    14,
                    16,
                    'default',
                    20,
                    22,
                    24,
                    26,
                    28,
                    32,
                    36
                ],
                supportAllValues: true
            },
            mediaEmbed: {
                previewsInData: true
            },
        })
        .then(editor => {
            window.editor1 = editor;

        })
        .catch(err => {
            console.error(err.stack);
        });
    if (document.querySelector('#ckeditor2')) {
        ClassicEditor
            .create(document.querySelector('#ckeditor2'), {
                extraPlugins: [MyCustomUploadAdapterPlugin],
                fontFamily: {
                    options: [
                        'default',
                        '나눔고딕',
                        '맑은고딕',
                        '굴림체',
                        '명조체',
                        '바탕체',
                        '돋움체',
                        '궁서체',
                    ],
                    supportAllValues: true
                },
                fontSize: {
                    options: [
                        10,
                        11,
                        12,
                        14,
                        16,
                        'default',
                        20,
                        22,
                        24,
                        26,
                        28,
                        32,
                        36
                    ],
                    supportAllValues: true
                },
                mediaEmbed: {
                    previewsInData: true
                },
            })
            .then(editor => {
                window.editor2 = editor;

            })
            .catch(err => {
                console.error(err.stack);
            });
    }

    function newpop() {
        var win = window.open("mypage_pop_event_list.php", "event_pop", "toolbar=yes,scrollbars=yes,resizable=yes,top=200,left=200,width=1000,height=1000");
    }
    $(function() {
        $('#searchBtn').on("click", function() {
            newpop();
        });
    })
    $(function() {
        $('#cancleBtn').on("click", function() {
            location = "mypage_landing_list.php";
        });

        $('#saveBtn').on("click", function() {
            if ($('#title').val() == "") {
                alert('제목을 입력해주세요.');
                return;
            }

            if ($('#description').val() == "") {
                alert('설명을 입력해주세요.');
                return;
            }

            $('#ir1').val(window.editor1.getData());
            <?php   if ($_SESSION['one_member_admin_id'] != "") { ?>
                $('#ir2').val(window.editor2.getData());
            <?php   } ?>
            $('#sform').submit();
            $(this).prop('disabled', true).text('처리중');
        });
    })

    function reset(type) {
        if (type == "thumbnail") {
            $("#thumbnail").val("");
        } else {
            $("#file").val("");
        }
    }

</script>
<style>
    .ck-color-ui-dropdown {
        --ck-color-grid-tile-size: 20px
    }

    .ck-color-ui-dropdown .ck-color-grid {
        grid-gap: 1px
    }

    .ck-color-ui-dropdown .ck-color-grid .ck-button {
        border-radius: 0
    }

    .ck-color-ui-dropdown .ck-color-grid__tile:focus:not(.ck-disabled),
    .ck-color-ui-dropdown .ck-color-grid__tile:hover:not(.ck-disabled) {
        z-index: 1;
        transform: scale(1.3)
    }
</style>

<?php  
include_once "_foot.php";
?>