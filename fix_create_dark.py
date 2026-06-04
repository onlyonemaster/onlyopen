#!/usr/bin/env python3
"""Apply nm-darkver2 dark mode to mypage_reservation_create.php (1400px width)."""
import re

FILE = "/disk/daily/home/kiam/mypage_reservation_create.php"

with open(FILE, "r", encoding="utf-8") as f:
    content = f.read()

# ── 1. Replace entire <style> block with nm-darkver2 CSS ──
old_style_start = """<style>
    .w200 {
        width: 200px;
    }

    .list_table1 tr:first-child td {
        border-top: 1px solid #CCC;
    }

    .list_table1 tr:first-child th {
        border-top: 1px solid #CCC;
    }

    .list_table1 td {
        height: 40px;
        border-bottom: 1px solid #CCC;
    }

    .list_table1 th {
        height: 40px;
        border-bottom: 1px solid #CCC;
    }

    .list_table1 input[type=text] {
        width: 600px;
        height: 30px;
    }

    .info_box_table input[type=text] {
        width: 600px;
        height: 30px;
    }

    .info_box_table th {
        height: 40px;
        border-bottom: 1px solid #CCC;
        width: 200px !important;
    }

    .get_type_name {
        /* column-count: 6; */
        text-align: left;
        display: flex;
    }

    .iam_table {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 3px;
        text-align: center;
    }

    .zoom {
        transition: transform .2s;
        /* Animation */
    }

    .zoom:hover {
        transform: scale(4);
        /* (150% zoom - Note: if the zoom is too large, it will go outside of the viewport) */
        border: 1px solid #0087e0;
        box-shadow: 1px 1px 1px 0px rgba(0, 0, 0, 0.5);
    }

    .zoom-2x {
        transition: transform .2s;
        /* Animation */
    }

    .zoom-2x:hover {
        transform: scale(2);
        /* (150% zoom - Note: if the zoom is too large, it will go outside of the viewport) */
        border: 1px solid #0087e0;
        box-shadow: 1px 1px 1px 0px rgba(0, 0, 0, 0.5);
    }

    .del_img_btn {
        border: 1px solid;
        border-radius: 3px;
        background-color: #efefef;
        padding: 5px;
    }

    .popup_holder {
        position: relative;
    }

    .popupbox {
        z-index: 1;
        text-align: left;
        font-size: 12px;
        font-weight: normal;
        background: white;
        border-radius: 3px;
        padding: 10px;
        border: none;
        position: absolute;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
    }

    .ad_layer4 {
        width: 903px;
        height: auto;
        background-color: #fff;
        border: 2px solid #24303e;
        position: relative;
        box-sizing: border-box;
        padding: 30px 30px 50px 30px;
        display: none;
    }

    #ajax-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9000;
        text-align: center;
        display: none;
        background-color: #fff;
        opacity: 0.8;
    }

    #ajax-loading img {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 120px;
        height: 120px;
        margin: -60px 0 0 -60px;
    }

    .chat_btn {
        color: white;
        border-radius: 7px;
        background-color: red;
        font-size: 12px;
        border-color: red;
        padding: 4px 0px;
        margin-right: 3px;
        position: absolute;
        right: 45px;
    }

    #answer_side,
    #answer_side1,
    #answer_side2 {
        width: 90%;
        height: 400px;
        background-color: white;
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
        background-color: #fff;
        outline-width: 0;
        box-shadow: 0 5px 10px -5px rgb(0 0 0 / 30%);
        -webkit-transition: border-color 1000ms ease-out;
        transition: border-color 1000ms ease-out;
    }

    .send_ask {
        position: absolute;
        top: 0;
        right: 60px;
        width: 58px;
        height: 100%;
        background-color: white;
        border-radius: 20px;
        border: none;
    }

    #gpt_req_list_title {
        float: left;
        padding: 7px;
        margin-left: 40px;
        background-color: #f18484;
        border-radius: 10px;
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
        background-color: black;
        color: white !important;
        padding: 4px;
        border-radius: 10px;
        position: absolute;
        top: 5px;
        right: 80px;
    }

    @media only screen and (max-width: 720px) {
"""

# Check if we can find this pattern
if old_style_start in content:
    print("Found style block start - applying dark mode CSS")
else:
    print("Style block start NOT found - trying alternative...")
    # Try to find just the beginning
    idx = content.find('<style>')
    print(f"<style> tag at position: {idx}")

# ── Instead of replacing the whole block, let's do targeted replacements ──

# 1. Replace the style tag content - inject nm-darkver2 CSS after <style>
style_tag = '<style>'
nm_css = """<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    color: #000 !important;
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
    color: #000 !important;
}
#gpt_chat_modal .modal-header {
    background: var(--nm-dv2-green) !important;
}
#gpt_chat_modal .modal-header .login_bold {
    color: #000 !important;
}
#gpt_chat_modal .modal-header a {
    color: #000 !important;
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
.a1 a[href*=\"mypage_chatbot_list\"] {
    background: var(--nm-dv2-accent) !important;
    color: #fff !important;
    border-radius: 6px;
    padding: 4px 12px;
    font-weight: var(--fw-semi, 600);
}

/* ── 반응형 ── */
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
"""

# Find the <style> block and replace everything between <style> and the next major content
style_idx = content.find('<style>')
if style_idx >= 0:
    # Find where the style block ends (before <div class="big_sub">)
    end_marker = '<div class="big_sub">'
    end_idx = content.find(end_marker, style_idx)
    if end_idx >= 0:
        # Check for </style> tag before the big_sub
        style_close = content.find('</style>', style_idx)
        if style_close > 0 and style_close < end_idx:
            # Replace from <style> to just after </style>
            old_style = content[style_idx:style_close + 8]
            new_style = nm_css + '\n'
            content = content.replace(old_style, new_style, 1)
            print(f"Replaced style block ({len(old_style)} -> {len(new_style)} bytes)")
        else:
            print(f"Could not find </style> before big_sub")
    else:
        print(f"Could not find big_sub marker")
else:
    print("Could not find <style> tag")

# ── 2. Wrap m_body in big_div structure (already partial, need to add big_div) ──
# Current: <div class="big_sub"> ... no big_div wrapper
# Target: <div class="big_div"><div class="big_sub"> ... matching landing page pattern

# Check if big_div is already present
if '<div class="big_div">' not in content:
    big_sub_idx = content.find('<div class="big_sub">')
    if big_sub_idx > 0:
        content = content[:big_sub_idx] + '<div class="big_div">\n\t' + content[big_sub_idx:]
        print("Added big_div wrapper")
        # Also add closing </div> before the closing structure
        # Find </div> at the end (before _foot.php)
        foot_idx = content.rfind('include_once "_foot.php"')
        if foot_idx > 0:
            # Find the </div> before it
            search_area = content[foot_idx - 200:foot_idx]
            last_div = search_area.rfind('</div>')
            if last_div > 0:
                insert_pos = foot_idx - 200 + last_div + 6
                content = content[:insert_pos] + '\n</div><!-- big_div -->' + content[insert_pos:]
                print("Added big_div closing tag")

# ── 3. Fix textarea content style (inline) ──
old_textarea_style = 'style="background-color: rgb(200, 237, 252);width:300px;height:200px;"'
new_textarea_style = 'style="background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);border-radius:6px;width:300px;height:200px;padding:8px;"'
content = content.replace(old_textarea_style, new_textarea_style, 1)

# ── 4. Fix modal-body inline style ──
old_modal_body = 'background-color:#e5e3e3;'
content = content.replace(old_modal_body, 'background-color:var(--nm-dv2-bg);', 1)

# ── 5. Fix modal-footer inline style ──
old_modal_footer = 'text-align: center;background-color: #e5e3e3;padding:7px;'
content = content.replace(old_modal_footer, 'text-align: center;background-color: var(--nm-dv2-bg);padding:7px;', 1)

# ── 6. Fix send_chat button style ──
old_send_chat = 'style="width:50%;background:#82C836;color:white;padding:10px 0px;border: none;"'
new_send_chat = 'style="width:50%;background:var(--nm-dv2-green);color:#000;padding:10px 0px;border:none;border-radius:6px;font-weight:var(--fw-bold,700);"'
content = content.replace(old_send_chat, new_send_chat, 1)

# ── 7. Fix iam_table border-bottom-color: white ──
content = content.replace('border-bottom-color: white;', 'border-bottom-color: var(--nm-dv2-border);')
content = content.replace('border-top-color: white;', 'border-top-color: var(--nm-dv2-border);')

# ── 8. Fix modal-body inline styles for date inputs ──
content = content.replace('border:1px solid;', 'border:1px solid var(--nm-dv2-border);')

# ── 9. Fix "검색된 내용이 없습니다" row background ──
# Already handled by .list_table styles

# ── 10. Fix page-hero link inline style ──
old_hero_link = 'style="background: #4a90d9;'
new_hero_link = 'style="background: var(--nm-dv2-accent);'
content = content.replace(old_hero_link, new_hero_link, 1)

# ── Write output ──
with open(FILE, "w", encoding="utf-8") as f:
    f.write(content)

print(f"\nDone! File size: {len(content)} bytes")
print("Changes applied: nm-darkver2 dark mode + 1400px max-width")