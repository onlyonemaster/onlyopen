#!/usr/bin/env python3
"""Verify both PHP files for structural integrity."""
import re

files = {
    'reservation_list': '/disk/daily/home/kiam/mypage_reservation_list.php',
    'reservation_create': '/disk/daily/home/kiam/mypage_reservation_create.php',
}

for name, path in files.items():
    with open(path, 'r') as f:
        content = f.read()
    opens = len(re.findall(r'<div\b', content))
    closes = len(re.findall(r'</div>', content))
    has_1400 = '1400px' in content
    has_dark = 'nm-darkver2' in content or 'nm-dv2' in content
    has_big_div = 'class="big_div"' in content
    
    # Check light colors
    light_colors = []
    for p in ['#ddd', '#CCC', '#4a4a5a', 'background: white', 'color: black', '#fff"]', 'background: #fff']:
        if p in content:
            light_colors.append(p)
    
    print(f'=== {name} ===')
    print(f'  <div>: {opens}, </div>: {closes}, diff: {opens-closes}')
    print(f'  1400px: {has_1400}, dark: {has_dark}, big_div: {has_big_div}')
    if light_colors:
        print(f'  WARNING leftover light colors: {light_colors}')
    else:
        print(f'  No light colors found (OK)')
    print()

print('Verification complete.')