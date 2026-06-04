import re
with open('/home/kiam/aimessage/onechat/scenario_plan.html') as f:
    c = f.read()

script_start = c.find('<script>')
script_end   = c.rfind('</script>')
js = c[script_start+8:script_end]

opens  = js.count('{')
closes = js.count('}')
match  = "OK" if opens==closes else "FAIL"
print(f'중괄호: open={opens}, close={closes} -> {match}')

fns = re.findall(r'^function (\w+)', js, re.MULTILINE)
from collections import Counter
dup = [(n,ct) for n,ct in Counter(fns).items() if ct > 1]
print(f'함수 선언: {len(fns)}개, 중복: {dup if dup else "없음"}')

checks = [
    ('style.css 없음',    'css/style.css' not in c),
    ('사이드바 CSS 인라인','sidebar-drawer {' in c),
    ('z-index 9101',      '9101' in c),
    ('USE_MOCK=true',     'USE_MOCK = true' in c),
    ('menuBtn',           'id="menuBtn"' in c),
    ('sidebarDrawer',     'id="sidebarDrawer"' in c),
    ('FontAwesome CDN',   'fontawesome' in c),
]
print()
for label, ok in checks:
    print(f'  {"OK" if ok else "FAIL"} {label}')
print(f'  파일크기: {len(c):,} bytes')
