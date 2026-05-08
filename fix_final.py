#!/usr/bin/env python3
"""
scenario_plan.html 최종 수정:
1. style.css 링크 제거 (모바일 앱 CSS - overflow:hidden, max-width:480px 충돌)
2. 사이드바 CSS 인라인 직접 삽입 (변수 충돌 없이)
"""

SRC = '/home/kiam/aimessage/onechat/scenario_plan.html'

with open(SRC, 'r', encoding='utf-8') as f:
    content = f.read()

print(f"원본: {len(content)} bytes")

# ──────────────────────────────────────────
# 1. style.css 링크 제거
# ──────────────────────────────────────────
OLD_CSS_LINK = '\n<link rel="stylesheet" href="css/style.css?v=0421a">'
if OLD_CSS_LINK in content:
    content = content.replace(OLD_CSS_LINK, '', 1)
    print("✅ 1. style.css 링크 제거")
else:
    # 다른 패턴
    import re
    content, n = re.subn(r'\n?<link[^>]+css/style\.css[^>]*>', '', content, count=1)
    print(f"✅ 1. style.css 링크 제거 (regex, {n}개)")

# ──────────────────────────────────────────
# 2. 사이드바 전용 CSS를 <style> 태그 끝 바로 앞에 추가
# ──────────────────────────────────────────
SIDEBAR_CSS = """
/* ═══════════════════════════════════════
   사이드바 드로어 CSS (인라인)
   ═══════════════════════════════════════ */
.sidebar-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.55);
  z-index: 9100;
  opacity: 0; pointer-events: none;
  transition: opacity 0.25s ease;
  backdrop-filter: blur(2px);
}
.sidebar-overlay.open { opacity: 1; pointer-events: all; }

.sidebar-drawer {
  position: fixed; top: 0; left: 0;
  width: 280px; height: 100%;
  background: #0f172a;
  z-index: 9101;
  transform: translateX(-100%);
  transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
  display: flex; flex-direction: column;
  overflow-y: auto;
  box-shadow: 4px 0 24px rgba(0,0,0,0.5);
}
.sidebar-drawer.open { transform: translateX(0); }

.sidebar-header {
  display: flex; align-items: center;
  justify-content: space-between;
  padding: 18px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.sidebar-logo {
  display: flex; align-items: center; gap: 10px;
  font-size: 18px; font-weight: 800; color: #f97316;
}
.sidebar-logo i { font-size: 20px; }
.sidebar-close-btn {
  background: none; border: none; color: #94a3b8;
  font-size: 18px; cursor: pointer;
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
}
.sidebar-close-btn:hover { background: rgba(255,255,255,0.08); color: #f1f5f9; }

.sidebar-profile {
  display: flex; align-items: center; gap: 12px;
  padding: 16px; flex-shrink: 0;
}
.sidebar-profile-avatar {
  width: 46px; height: 46px; border-radius: 50%;
  background: #f97316; color: #fff;
  font-size: 18px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.sidebar-profile-info { display: flex; flex-direction: column; gap: 3px; overflow: hidden; }
.sidebar-profile-name { font-size: 15px; font-weight: 700; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-profile-role { font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.sidebar-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 0 12px 8px; }

.sidebar-menu-item {
  width: calc(100% - 16px); background: none; border: none;
  display: flex; align-items: center; gap: 14px;
  padding: 12px 16px; cursor: pointer; text-align: left;
  transition: background 0.15s; border-radius: 12px; margin: 2px 8px;
  color: inherit;
}
.sidebar-menu-item:hover { background: rgba(255,255,255,0.06); }
.sidebar-menu-item:active { background: rgba(255,255,255,0.1); }
.sidebar-menu-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 17px; flex-shrink: 0;
}
.sidebar-menu-text { display: flex; flex-direction: column; gap: 2px; flex: 1; overflow: hidden; }
.sidebar-menu-title { font-size: 14px; font-weight: 600; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-menu-desc { font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-menu-arrow { color: #64748b; font-size: 12px; flex-shrink: 0; }

.sidebar-sync-badge {
  display: flex; align-items: center; gap: 4px;
  background: rgba(34,197,94,0.15); color: #22c55e;
  border-radius: 12px; padding: 4px 8px;
  font-size: 10px; font-weight: 600; flex-shrink: 0;
}
.sidebar-theme-toggle { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.stt-label { font-size: 11px; font-weight: 600; color: #94a3b8; min-width: 22px; text-align: right; }
.stt-switch {
  width: 42px; height: 24px; background: #1a2640;
  border-radius: 12px; position: relative; cursor: pointer;
  transition: background 0.25s; border: 1px solid rgba(255,255,255,0.1);
}
.stt-switch.light-on { background: #f97316; border-color: transparent; }
.stt-knob {
  width: 18px; height: 18px; background: #94a3b8;
  border-radius: 50%; position: absolute; top: 2px; left: 3px;
  transition: transform 0.25s, background 0.25s;
}
.stt-switch.light-on .stt-knob { transform: translateX(18px); background: #fff; }

.sidebar-footer {
  margin-top: auto; padding: 16px; text-align: center;
  font-size: 11px; color: #64748b;
  border-top: 1px solid rgba(255,255,255,0.07);
}
"""

# <style> 태그 내 맨 끝, </style> 바로 앞에 삽입
STYLE_CLOSE = '</style>'
first_style_close = content.find(STYLE_CLOSE)
if first_style_close != -1 and '사이드바 드로어 CSS' not in content:
    content = content[:first_style_close] + SIDEBAR_CSS + '\n' + content[first_style_close:]
    print("✅ 2. 사이드바 CSS 인라인 삽입")
elif '사이드바 드로어 CSS' in content:
    print("ℹ️  2. 사이드바 CSS 이미 있음")
else:
    print("⚠️  2. 삽입 실패")

# ──────────────────────────────────────────
# 3. 저장
# ──────────────────────────────────────────
with open(SRC, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"\n✅ 저장 완료: {len(content):,} bytes")

# 검증
checks = [
    ('style.css 링크 없음', 'css/style.css' not in content),
    ('사이드바 CSS 인라인', '사이드바 드로어 CSS' in content),
    ('sidebar-overlay CSS', '.sidebar-overlay {' in content),
    ('sidebar-drawer CSS', '.sidebar-drawer {' in content),
    ('z-index:9101 (충돌 없음)', '9101' in content),
    ('overflow 미정의 (body에)', 'overflow: hidden' not in content.split('<style')[1].split('</style>')[0]),
]

print("\n=== 최종 검증 ===")
for label, ok in checks:
    print(f"  {'✅' if ok else '❌'} {label}")
