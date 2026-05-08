#!/usr/bin/env python3
"""Apply nm-darkver2 dark mode to mypage_pop_message_list_for_copylist.php."""
import re

FILE = "/disk/daily/home/kiam/mypage_pop_message_list_for_copylist.php"

with open(FILE, "r", encoding="utf-8") as f:
    content = f.read()

# ── 1. Replace the <style> block ──
old_style = """<style>
.pop_right {
    position: relative;
    right: 2px;
    display: inline;
    margin-bottom: 6px;
    width: 5px;
}    
</style>"""

new_style = """<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/css/page-hero.css">
<link rel="stylesheet" href="/css/nm-darkver2-typography.css">
<style>
/* ============================================
   nm-darkver2 디자인 시스템 — 퍼널예약 메시지 복사 팝업
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

/* ── 페이지 배경 ── */
body, html {
    background: var(--nm-dv2-bg) !important;
    color: var(--nm-dv2-text) !important;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}
.big_sub, .m_body {
    background: var(--nm-dv2-bg) !important;
    color: var(--nm-dv2-text) !important;
    padding: 20px 24px;
    max-width: 1400px;
    margin: 0 auto;
    box-sizing: border-box;
}

/* ── 타이틀 ── */
.a1 {
    color: var(--nm-dv2-text) !important;
    font-size: var(--fz-step, 17px);
    font-weight: var(--fw-bold, 700);
    padding: 4px 0 12px;
}
.a1 li {
    color: var(--nm-dv2-text) !important;
    list-style: none;
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
.p1 select, .p1 input[type=text], select, input[type=text] {
    background: var(--nm-dv2-input-bg) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: var(--fz-input, 14px);
}
input[type=text]:focus {
    border-color: var(--nm-dv2-green) !important;
    outline: none;
    box-shadow: 0 0 0 2px rgba(130,200,54,0.18);
}

/* ── 검색 아이콘 다크모드 ── */
.p1 a img {
    filter: brightness(0) invert(1);
    width: 60px;
    height: 30px;
    vertical-align: middle;
    border-radius: 4px;
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

/* ── 리스트 테이블 ── */
.list_table {
    border-collapse: collapse;
    width: 100%;
    font-size: var(--fz-small, 13px);
}
.list_table tr, .list_table td, .list_table th {
    background: var(--nm-dv2-card) !important;
    color: var(--nm-dv2-text) !important;
    border: 1px solid var(--nm-dv2-border) !important;
    padding: 7px 8px;
    vertical-align: middle;
}
.list_table tr:first-child td, .list_table thead th {
    background: var(--nm-dv2-card2) !important;
    color: var(--nm-dv2-green) !important;
    font-weight: var(--fw-bold, 700);
}
.list_table tr:hover td {
    background: var(--nm-dv2-hover) !important;
}
.list_table a {
    color: var(--nm-dv2-accent);
    text-decoration: none;
}
.list_table a:hover {
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

/* ── 반응형 ── */
@media (max-width: 768px) {
    .p1 { flex-direction: column; align-items: stretch; }
    .big_sub, .m_body { padding: 10px 8px; }
    .list_table { font-size: 11px; }
}
</style>"""

content = content.replace(old_style, new_style, 1)

with open(FILE, "w", encoding="utf-8") as f:
    f.write(content)

print(f"Done! File size: {len(content)} bytes")
print("copylist popup updated to nm-darkver2 dark mode")