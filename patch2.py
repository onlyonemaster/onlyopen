SRC = '/home/kiam/aimessage/onechat/scenario_plan.html'
with open(SRC, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. CSS 변수 추가
OLD = '  --r:14px;--r-sm:9px;--shadow:0 8px 32px rgba(0,0,0,.5);\n}'
NEW = '  --r:14px;--r-sm:9px;--shadow:0 8px 32px rgba(0,0,0,.5);\n  --bg-dark:#0f172a;--bg-card2:#1a2640;--bg-card3:#1e2d47;\n  --text-primary:#f1f5f9;--text-muted:#94a3b8;\n}'
if OLD in content and '--bg-dark' not in content:
    content = content.replace(OLD, NEW, 1)
    print("1. CSS 변수 추가 OK")
else:
    print("1. CSS 변수 SKIP")

with open(SRC, 'w', encoding='utf-8') as f:
    f.write(content)
print(f"저장: {len(content)} bytes")

# 검증
with open(SRC, 'r', encoding='utf-8') as f:
    c2 = f.read()
print("--bg-dark:", '--bg-dark' in c2)
print("menuBtn:", 'id="menuBtn"' in c2)
print("sidebarDrawer:", 'id="sidebarDrawer"' in c2)
print("openSidebar:", 'openSidebar' in c2)
print("USE_MOCK:", 'USE_MOCK = true' in c2)
