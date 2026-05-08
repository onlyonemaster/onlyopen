#!/usr/bin/env python3
"""Verify structural integrity of mypage_reservation_list.php."""
import re

FILE = "/disk/daily/home/kiam/mypage_reservation_list.php"

with open(FILE, "r", encoding="utf-8") as f:
    content = f.read()

# Count opening and closing divs
opens = len(re.findall(r'<div\b', content))
closes = len(re.findall(r'</div>', content))
print(f'<div>: {opens}, </div>: {closes}, diff: {opens - closes}')

# Count <span> / </span>
span_opens = len(re.findall(r'<span\b', content))
span_closes = len(re.findall(r'</span>', content))
print(f'<span>: {span_opens}, </span>: {span_closes}, diff: {span_opens - span_closes}')

# Check for broken script pattern
if '</div>on(' in content:
    print('WARNING: broken script pattern found!')
else:
    print('OK: No broken script pattern')

# Check for $(function() presence
if '$(function()' in content:
    print('OK: $(function() found')
    # Count occurrences
    cnt = content.count('$(function()')
    print(f'    $(function() count: {cnt}')
    # Count }); to close
    closes_jq = content.count('});')
    print(f'    }}); count: {closes_jq}')
else:
    print('WARNING: $(function() not found')

# Check <Script> / </Script>
script_opens = len(re.findall(r'<Script>', content, re.IGNORECASE))
script_closes = len(re.findall(r'</Script>', content, re.IGNORECASE))
print(f'<Script>: {script_opens}, </Script>: {script_closes}')

# Check PHP tags
php_opens = len(re.findall(r'<\?php', content))
php_closes = len(re.findall(r'\?>', content))
print(f'<?php: {php_opens}, ?>: {php_closes}')

# Check for <?= short tags
short_tags = len(re.findall(r'<\?=', content))
print(f'<?= : {short_tags}')

# Check for any remaining light-mode colors
light_patterns = [
    '#ddd', '#b5b5b5', '#4a4a5a', '#f5f5f5', '#ffffff',
    'background-color: white', 'color: black',
    'background: white', 'background: #fff'
]
for p in light_patterns:
    if p in content:
        print(f'WARNING: light-mode color found: {p}')

print('\nDone!')