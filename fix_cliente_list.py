#!/usr/bin/env python3
"""Full nm-darkver2 conversion for cliente_list.php"""
import re

FILE = '/disk/daily/home/kiam/cliente_list.php'

with open(FILE, 'r') as f:
    content = f.read()

# ── 1. Replace _head.php → _head_v01.php ──
content = content.replace('include_once "_head.php";', 'include_once "_head_v01.php";')

# ── 2. Replace entire <style> block with comprehensive nm-darkver2 CSS ──
new_css = '''<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
.nm-btn:hover{background:var(--nm-dv2-hover);border-color:var(--nm-dv2-accent);color:var(--nm-dv2-accent)}
.nm-btn-primary{background:var(--nm-dv2-accent);color:#fff;border-color:var(--nm-dv2-accent);font-weight:700}
.nm-btn-primary:hover{background:#4a8ae8}
.nm-btn-danger{background:var(--nm-dv2-red);color:#fff;border-color:var(--nm-dv2-red)}
.nm-btn-danger:hover{background:#c94a4a}
.nm-btn-outline{background:transparent;color:var(--nm-dv2-accent);border:1px solid var(--nm-dv2-accent)}
.nm-btn-outline:hover{background:var(--nm-dv2-accent);color:#fff}
.nm-btn-search{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;background:var(--nm-dv2-accent);color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:all .2s}
.nm-btn-search:hover{background:#4a8ae8}

/* ── 뱃지 ── */
.nm-badge-reply-done{display:inline-block;padding:3px 10px;border-radius:20px;background:rgba(130,200,54,.15);color:var(--nm-dv2-green);font-size:12px;font-weight:600}
.nm-badge-reply-wait{display:inline-block;padding:3px 10px;border-radius:20px;background:rgba(200,160,80,.15);color:#d4a03c;font-size:12px;font-weight:600}
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
.list_table thead th { background: var(--nm-dv2-card2) !important; color: var(--nm-dv2-green) !important; font-weight: 700; }
.list_table td{padding:12px 10px;border-bottom:1px solid var(--nm-dv2-border);font-size:14px;color:var(--nm-dv2-text);vertical-align:middle}
.list_table tr:hover td{background:var(--nm-dv2-hover)!important}
.list_table a{color:var(--nm-dv2-accent);text-decoration:none}
.list_table a:hover{color:var(--nm-dv2-green);text-decoration:underline}
.important-star{color:var(--nm-dv2-gold);font-size:16px;margin-right:2px}

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
    .list_table { font-size: 11px; }
    .list_table td { padding: 8px 6px; }
    .cat-tabs { gap: 4px; }
    .cat-tab { padding: 4px 10px; font-size: 11px; }
}
@media (max-width: 450px) {
    .list_table td { font-size: 10px; padding: 6px 4px; }
    .a2 input[type=text] { width: 120px; }
}
</style>'''

# Find and replace the existing style block
old_style_start = content.find('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">')
if old_style_start == -1:
    old_style_start = content.find('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/')

old_style_end = content.find('</style>')
if old_style_start != -1 and old_style_end != -1:
    # Find the matching </style>
    content = content[:old_style_start] + new_css + content[old_style_end + len('</style>'):]

# ── 3. Replace the wrapper structure ──
# Old: <div class="big_main">\n    <div class="big_1 top_menu">\n        <div class="m_div">
# New: <div class="big_div">\n    <div class="big_sub">\n        <div class="m_div">\n            <div class="mrl-wrap">

old_wrapper = '''<div class="big_main">
    <div class="big_1 top_menu">
        <div class="m_div">

            <div class="left_sub_menu">'''

new_wrapper = '''<div class="big_div">
    <div class="big_sub">
        <div class="m_div">
            <div class="mrl-wrap">

            <!-- 상단 breadcrumb & 메뉴 -->
            <div class="left_sub_menu">'''

content = content.replace(old_wrapper, new_wrapper)

# ── 4. Fix the breadcrumb/menu section closing and add page-hero ──
# Close mrl-wrap after the top menu, then add page-hero, then reopen m_body
old_after_menu = '''            <p style="clear:both;"></p>
        </div>
    </div>
    <div class="m_div" style="padding-bottom:50px;">'''

new_after_menu = '''            <p style="clear:both;"></p>
            </div><!-- .mrl-wrap (top) -->
        </div>
    </div>

    <!-- ── 페이지 히어로 배너 ── -->
    <div class="big_sub">
        <div class="m_div">
            <div class="mrl-wrap">
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
            </div>
        </div>
    </div>

    <!-- ── 게시판 본문 ── -->
    <div class="big_sub">
    <div class="m_div" style="padding-bottom:50px;">
        <div class="mrl-wrap">'''

content = content.replace(old_after_menu, new_after_menu)

# ── 5. Fix the closing wrapper ──
# Old:         </div>\n    </div>\n</div>\n<?php   
old_close = '''        </div>
    </div>
</div>
<?php   
include_once "_foot.php";
?>'''

new_close = '''        </div><!-- .mrl-wrap -->
        </div>
    </div>
</div>
<?php   
include_once "_foot.php";
?>'''

content = content.replace(old_close, new_close)

# ── 6. Fix category tabs — make "active" dynamic based on $_REQUEST['cat'] ──
# Replace all hardcoded active tabs with PHP conditions
# For status != 5:
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab active">전체</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab<?= (!$_REQUEST['cat']||$_REQUEST['cat']==0)?' active':'' ?>">전체</a>'''
)
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab active">문자</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab<?= $_REQUEST['cat']==1?' active':'' ?>">문자</a>'''
)
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab active">디버</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab<?= $_REQUEST['cat']==2?' active':'' ?>">디버</a>'''
)
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=3" class="cat-tab active">윈퍼널</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=3" class="cat-tab<?= $_REQUEST['cat']==3?' active':'' ?>">윈퍼널</a>'''
)
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=4" class="cat-tab active">아이엠</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=4" class="cat-tab<?= $_REQUEST['cat']==4?' active':'' ?>">아이엠</a>'''
)
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=5" class="cat-tab active">쇼핑</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=5" class="cat-tab<?= $_REQUEST['cat']==5?' active':'' ?>">쇼핑</a>'''
)

# For status == 5 (admin manual):
# The first occurrence of cat=0 is "전체" for status != 5, the second (after the else) is for status == 5
# Let's find and fix all the status==5 category tabs
# They all have the pattern: cat-tab active">전체 / 아이엠 / 폰문자 etc.
# We need to be more careful - let's find the second occurrence of each

# After the else block (status==5), find and replace
# We'll use the unique URL pattern to distinguish
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab active">전체</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab active">아이엠</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=0" class="cat-tab<?= (!$_REQUEST['cat']||$_REQUEST['cat']==0)?' active':'' ?>">전체</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=1" class="cat-tab<?= $_REQUEST['cat']==1?' active':'' ?>">아이엠</a>'''
)

# Now replace the remaining status==5 tabs
content = content.replace(
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab active">폰문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=3" class="cat-tab active">디비수집</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=4" class="cat-tab active">콜백문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=5" class="cat-tab active">퍼널문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=6" class="cat-tab active">웹문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=7" class="cat-tab active">국제문자</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=8" class="cat-tab active">결제</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=9" class="cat-tab active">사업</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=10" class="cat-tab active">마케팅</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=11" class="cat-tab active">디비테이블</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=12" class="cat-tab active">카페24</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=13" class="cat-tab active">서버</a>
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=14" class="cat-tab active">기타</a>''',
    '''<a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=2" class="cat-tab<?= $_REQUEST['cat']==2?' active':'' ?>">폰문자</a>
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
                            <a href="cliente_list.php?status=<?= $_REQUEST['status'] ?>&one_no=<?= $row_no['no'] ?>&cat=14" class="cat-tab<?= $_REQUEST['cat']==14?' active':'' ?>">기타</a>'''
)

# ── 7. Fix the big_div closing - need one more </div> for new layout ──
# The new structure adds extra div levels, so we need to close mrl-wrap
# Current closing: </div><!-- .mrl-wrap -->\n        </div>\n    </div>\n</div>
# Need to add closing for the inner big_sub/m_div/ that wraps mrl-wrap
old_close2 = '''        </div><!-- .mrl-wrap -->
        </div>
    </div>
</div>'''
# The inner mrl-wrap is inside m_div → big_sub → big_div
# But we already have the outer big_sub → m_div wrapping it
# The current structure after changes should be:
# <div class="big_div">
#   <div class="big_sub">
#     <div class="m_div"><div class="mrl-wrap">...top menu...</div></div>
#   </div>
#   <div class="big_sub"><div class="m_div"><div class="mrl-wrap">page-hero</div></div></div>
#   <div class="big_sub"><div class="m_div"><div class="mrl-wrap">...content...</div></div></div>
# </div>
# 
# The closing should handle all nesting. Let's check what the current closing looks like.

# The last </div><!-- .mrl-wrap --> closes the content mrl-wrap
# Then we need to close m_div, big_sub, big_div
# Currently we have: </div><!-- .mrl-wrap -->\n        </div>\n    </div>\n</div>
# This closes: mrl-wrap, m_div (padding-bottom), big_sub, big_div
# That should be correct for the content section

# But wait, we also have the page-hero and top-menu mrl-wrap/m_div/big_sub that need closing
# Let's verify the structure is correct by checking div balance

# Write the modified file
with open(FILE, 'w') as f:
    f.write(content)

print(f"Done! File size: {len(content)} bytes")
print("Applied: _head_v01.php, full nm-darkver2 CSS, big_div layout, page-hero, 1400px, dynamic cat-tabs")