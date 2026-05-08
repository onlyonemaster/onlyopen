#!/usr/bin/env python3
"""
Release Script - Bump version with Semantic Versioning
Usage: python3 scripts/release.py [patch|minor|major] [--note="description"]
"""

import sys
import os
from datetime import datetime
from version_utils import (
    get_current_version, bump_version, write_version,
    append_version_log, PKG_PATH, VERSION_LOG_PATH
)

# package.json이 없으면 기본값 생성
if not os.path.exists(PKG_PATH):
    import json
    default_pkg = {"name": "onlyone-onechat", "version": "1.0.0",
                   "description": "Onlyone OneChat - Version Management"}
    os.makedirs(os.path.dirname(PKG_PATH), exist_ok=True)
    with open(PKG_PATH, 'w') as f:
        json.dump(default_pkg, f, indent=2)
        f.write('\n')
    print("📦 Created default package.json")

args = sys.argv[1:]
bump_type = 'patch'
note = ''

for arg in args:
    if arg in ('major', 'minor', 'patch'):
        bump_type = arg
    elif arg.startswith('--note='):
        note = arg.replace('--note=', '')
    elif arg.startswith('--type='):
        bump_type = arg.replace('--type=', '')

if bump_type not in ('major', 'minor', 'patch'):
    print(f"❌ Invalid bump type: {bump_type}")
    print("   Usage: python3 scripts/release.py [patch|minor|major] [--note=description]")
    sys.exit(1)


def generate_changelog_entry(from_ver, to_ver, btype, note_text):
    date = datetime.now().strftime('%Y-%m-%d')
    labels = {'major': '🔴 MAJOR', 'minor': '🟡 MINOR', 'patch': '🟢 PATCH'}
    lines = [
        f"## [{to_ver}] - {date}",
        "",
        f"**{labels.get(btype, btype)}** - v{from_ver} → v{to_ver}",
        "",
    ]
    if note_text:
        lines.append(f"- {note_text}")
    lines.append("")
    lines.append("")
    return '\n'.join(lines)


def append_changelog(entry):
    changelog_path = os.path.join(os.path.dirname(__file__), '..', 'CHANGELOG.md')
    header = (
        "# Onlyone OneChat Changelog\n\n"
        "All notable changes to this project will be documented here.\n\n"
    )
    if os.path.exists(changelog_path):
        with open(changelog_path, 'r') as f:
            content = f.read()
    else:
        content = header

    # Insert after header
    if content.startswith('# '):
        lines = content.split('\n')
        # Find first ## line
        insert_idx = None
        for i, line in enumerate(lines):
            if line.startswith('## ') and i > 1:
                insert_idx = i
                break
        if insert_idx:
            lines.insert(insert_idx, entry.rstrip('\n'))
        else:
            lines.append(entry.rstrip('\n'))
        content = '\n'.join(lines)

    with open(changelog_path, 'w') as f:
        f.write(content)
    print("📄 CHANGELOG.md updated")


def main():
    current = get_current_version()
    next_ver = bump_version(current, bump_type)

    print("")
    print("╔══════════════════════════════════════╗")
    print("║   Onlyone OneChat - Release       ║")
    print("╠══════════════════════════════════════╣")
    print(f"║   Previous : v{current['raw']:<19}║")
    print(f"║   New      : v{next_ver['raw']:<19}║")
    print(f"║   Type     : {bump_type:<21}║")
    print(f"║   Date     : {datetime.now().strftime('%Y-%m-%d'):<17}║")
    print("╚══════════════════════════════════════╝")
    print("")

    # 1. Update package.json
    write_version(next_ver)

    # 2. Append version log
    append_version_log(current['raw'], next_ver['raw'], bump_type, note)

    # 3. Update changelog
    entry = generate_changelog_entry(current['raw'], next_ver['raw'], bump_type, note)
    append_changelog(entry)

    # 4. Display UI string
    print(f"🎨 UI Version String: Onlyone OneChat v{next_ver['raw']}")
    print("✅ Release complete!")
    print("")
    print("💡 Next steps:")
    print(f"   python3 scripts/inject_version.py  → Update HTML with new version")
    print(f"   git add . && git commit -m \"chore(release): v{next_ver['raw']}\"")
    print(f"   git tag v{next_ver['raw']}")
    print("")


if __name__ == '__main__':
    main()