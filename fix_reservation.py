#!/usr/bin/env python3
"""Fix mypage_reservation_list.php for nm-darkver2 dark mode."""
import re

FILE = "/disk/daily/home/kiam/mypage_reservation_list.php"

with open(FILE, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Fix: close page-hero div after page-hero-inner (line 336-337 area)
old_hero = '''        </div>
    </div>

\t\t\t\t\t<div class="m_body">'''
new_hero = '''        </div>
    </div>
</div>

\t\t\t\t\t<div class="m_body">'''
content = content.replace(old_hero, new_hero, 1)

# 2. Fix href from /mypage_reservation_list.php to /mypage_reservation_create.php?reserv_type=0
old_link = 'href="/mypage_reservation_list.php">+ 새 퍼널 등록</a>'
new_link = 'href="/mypage_reservation_create.php?reserv_type=0">+ 새 퍼널 등록</a>'
content = content.replace(old_link, new_link, 1)

# 3. Fix broken script tag at line 745: </div>on("change"...
old_broken = '</div>on("change", function() {'
new_broken = '''</div>
<Script>
$(function() {
\t$('#allChk').on("change", function() {'''
content = content.replace(old_broken, new_broken, 1)

# 4. Fix closing of service_contents window function and add closing for $(function()
old_svc_close = '''\t// 서비스콘텐츠 팝업 닫기 함수
\twindow.hide_service_contents_popup = function() {
\t\t$("#service_contents_popup").hide();
\t\t$("#tutorial-loading").hide();
\t};
</script>'''
new_svc_close = '''\t// 서비스콘텐츠 팝업 닫기 함수
\twindow.hide_service_contents_popup = function() {
\t\t$("#service_contents_popup").hide();
\t\t$("#tutorial-loading").hide();
\t};
});
</Script>'''
content = content.replace(old_svc_close, new_svc_close, 1)

# 5. Fix 취소하기 button in tooltiptext_card_edit (send_step popup)
old_cancel1 = '''\t\t\t\t\t<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: #4a4a5a;border-radius: 3px;padding: 5px;">취소하기</a>
\t\t\t\t\t<a href="javascript:send_step_sms()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 3px;color: white;padding: 5px;">전송하기</a>'''
new_cancel1 = '''\t\t\t\t\t<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-card2);border-radius: 6px;padding: 7px 5px;color: var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);">취소하기</a>
\t\t\t\t\t<a href="javascript:send_step_sms()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 6px;color: #fff;padding: 7px 5px;font-weight: var(--fw-bold,700);">전송하기</a>'''
content = content.replace(old_cancel1, new_cancel1, 1)

# 6. Fix table border in step_mall_set
old_table_border = '<table class="table table-bordered" style="width: 97%;padding: 17px;border: 1px solid #ddd;">'
new_table_border = '<table class="table table-bordered" style="width: 97%;padding: 17px;border: 1px solid var(--nm-dv2-border);">'
content = content.replace(old_table_border, new_table_border, 1)

# 7. Fix iam_mall_desc textarea style
old_desc = 'id="iam_mall_desc" style="border:none;min-height:175px;overflow:auto;width:98%;resize: vertical;">'
new_desc = 'id="iam_mall_desc" style="border:1px solid var(--nm-dv2-border);min-height:175px;overflow:auto;width:98%;resize: vertical;background:var(--nm-dv2-input-bg);color:var(--nm-dv2-text);border-radius:6px;padding:8px;">'
content = content.replace(old_desc, new_desc, 1)

# 8. Fix 취소/등록 buttons in step_mall_set popup
old_cancel2 = '''\t\t\t\t\t<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: #4a4a5a;border-radius: 3px;padding: 5px 40px;">취소</a>
\t\t\t\t\t<a href="javascript:create_iam_mall(0)" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 3px;color: white;padding: 5px 40px;">등록</a>'''
new_cancel2 = '''\t\t\t\t\t<a href="javascript:cancel_set()" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-card2);border-radius: 6px;padding: 7px 40px;color: var(--nm-dv2-text);border:1px solid var(--nm-dv2-border);">취소</a>
\t\t\t\t\t<a href="javascript:create_iam_mall(0)" class="btn login_signup" style="width: 40%;background-color: var(--nm-dv2-accent);border-radius: 6px;color: #fff;padding: 7px 40px;font-weight: var(--fw-bold,700);">등록</a>'''
content = content.replace(old_cancel2, new_cancel2, 1)

# 9. Update CSS: add step_mall_set styles, sort-by, switch, etc.
old_css_section = '''/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 11px; }
    .list_table td, .list_table th { padding: 4px 3px; }
    .tooltiptext-bottom {
        width: 90% !important;
        left: 5% !important;
        max-width: 95vw;
    }
    #step_mall_set {
        width: 95% !important;
        left: 2.5% !important;
    }
    .button_app a.btn {
        width: auto !important;
        padding: 8px 16px !important;
    }
    .a1 { flex-direction: column !important; align-items: stretch !important; }
    #funnel_ch_wrap { margin-left: 0 !important; justify-content: flex-start !important; }
}'''

new_css_section = '''/* ── step_mall_set 팝업 ── */
#step_mall_set table.table-bordered {
    border-color: var(--nm-dv2-border) !important;
}
#step_mall_set tbody td {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border-color: var(--nm-dv2-border) !important;
    padding: 7px 8px;
    font-size: var(--fz-label, 14px);
}
#step_mall_set .bold {
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
}
#step_mall_set input[type=text],
#step_mall_set textarea,
#step_mall_set input[type=file] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
}
#step_mall_set textarea#iam_mall_desc {
    border: 1px solid var(--nm-dv2-border) !important;
}
#step_mall_set .input-wrap {
    color: var(--nm-dv2-muted);
}
#step_mall_set input[type=radio] {
    margin-right: 4px;
}
#step_mall_set input[type=checkbox] {
    accent-color: var(--nm-dv2-green);
}

/* ── 팝업 공통 버튼 영역 다크모드 ── */
.button_app {
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 14px 0;
}
.button_app a.btn {
    color: #fff !important;
    font-weight: var(--fw-semi, 600);
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-size: var(--fz-label, 14px);
    transition: opacity .18s;
}
.button_app a.btn:hover {
    opacity: 0.85;
}

/* ── sort-by 화살표 ── */
a.sort-by:before { border-bottom-color: var(--nm-dv2-muted) !important; }
a.sort-by:after  { border-top-color: var(--nm-dv2-muted) !important; }

/* ── switch 토글 ── */
.switch .slider {
    background-color: var(--nm-dv2-card2);
}
.switch input:checked + .slider {
    background-color: var(--nm-dv2-green);
}

/* ── service_contents 팝업 ── */
#service_contents_popup .modal-body {
    color: var(--nm-dv2-text);
}
#service_contents_popup .btn.login_signup {
    display: inline-block;
    text-align: center;
    font-weight: var(--fw-bold, 700);
    font-size: var(--fz-label, 14px);
    border-radius: 6px;
}

/* ── .bold 기본 다크모드 컬러 ── */
.bold {
    color: var(--nm-dv2-text) !important;
}

/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .mrl-wrap { padding: 10px 8px 60px; }
    .list_table { font-size: 11px; }
    .list_table td, .list_table th { padding: 4px 3px; }
    .tooltiptext-bottom {
        width: 90% !important;
        left: 5% !important;
        max-width: 95vw;
    }
    #step_mall_set {
        width: 95% !important;
        left: 2.5% !important;
    }
    .button_app a.btn {
        width: auto !important;
        padding: 8px 16px !important;
    }
    .a1 { flex-direction: column !important; align-items: stretch !important; }
    #funnel_ch_wrap { margin-left: 0 !important; justify-content: flex-start !important; }
}'''

content = content.replace(old_css_section, new_css_section, 1)

with open(FILE, "w", encoding="utf-8") as f:
    f.write(content)

print("All fixes applied successfully!")
print(f"File size: {len(content)} bytes")