#!/usr/bin/env python3
"""
Inject Version Script
Reads version from package.json and injects into HTML files
Also patches "AIvote" → "Onlyone" in menu labels
"""

import sys
import os
import re
import json
from datetime import datetime

PKG_PATH = os.path.join(os.path.dirname(__file__), '..', 'package.json')

if not os.path.exists(PKG_PATH):
    print("❌ package.json not found. Run release.py first.")
    sys.exit(1)

with open(PKG_PATH, 'r') as f:
    pkg = json.load(f)

VERSION = pkg['version']
APP_NAME = 'Onlyone OneChat'
VERSION_STRING = f"{APP_NAME} v{VERSION}"
BUILD_DATE = datetime.now().strftime('%Y-%m-%d')

print("")
print("┌─────────────────────────────────────────┐")
print("│   Onlyone OneChat - Version Injector    │")
print("├─────────────────────────────────────────┤")
print(f"│   {VERSION_STRING:<36}│")
print(f"│   Build: {BUILD_DATE:<30}│")
print("└─────────────────────────────────────────┘")
print("")


def patch_file(filepath):
    """파일 내 버전 placeholder 치환 + AIvote → Onlyone"""
    if not os.path.exists(filepath):
        print(f"⚠️  Skip (not found): {filepath}")
        return False

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    modified = False

    # 1. 버전 span placeholder 치환
    pattern_span = re.compile(r'<span[^>]*id=["\']app-version["\'][^>]*>.*?</span>', re.IGNORECASE | re.DOTALL)
    if pattern_span.search(content):
        content = pattern_span.sub(f'<span id="app-version">{VERSION_STRING}</span>', content)
        print(f"   ✅ Version span → {VERSION_STRING}")
        modified = True

    # 2. meta 태그 버전 치환
    pattern_meta = re.compile(r'<meta\s+name=["\']app-version["\']\s+content=["\'].*?["\']\s*/?>', re.IGNORECASE)
    if pattern_meta.search(content):
        content = pattern_meta.sub(f'<meta name="app-version" content="{VERSION}">', content)
        print(f"   ✅ Meta version → {VERSION}")
        modified = True

    # 3. "AIvote OneChat" → "Onlyone OneChat"
    aivote_count = content.count('AIvote')
    if aivote_count > 0:
        content = re.sub(r'AIvote', 'Onlyone', content)
        print(f"   ✅ AIvote → Onlyone ({aivote_count} occurrences)")
        modified = True

    # 4. display version in any visible text match
    pattern_display = re.compile(r'Onlyone\s+OneChat\s+v[\d.]+')
    matches = pattern_display.findall(content)
    if matches:
        for m in set(matches):
            print(f"   ℹ️  Found existing: {m}")

    if modified:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    else:
        print(f"   ℹ️  No changes needed")
        return False


def main():
    target_files = sys.argv[1:] if len(sys.argv) > 1 else []
    if not target_files:
        default_targets = [
            os.path.join(os.path.dirname(__file__), '..', 'demo', 'onechat-menu.html'),
        ]
        target_files = [os.path.abspath(f) for f in default_targets]

    patched = 0
    for f in target_files:
        print(f"📄 Processing: {f}")
        if patch_file(f):
            patched += 1
        print("")

    print(f"🎯 Done! {patched} file(s) patched.")
    print(f"   Version: {VERSION_STRING}")
    print("")


if __name__ == '__main__':
    main()