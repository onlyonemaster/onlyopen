#!/usr/bin/env python3
"""
Version Utility Functions
Onlyone OneChat - Semantic Versioning Helper
"""

import json
import re
import os
from datetime import datetime

PKG_PATH = os.path.join(os.path.dirname(__file__), '..', 'package.json')
VERSION_LOG_PATH = os.path.join(os.path.dirname(__file__), '..', 'VERSION_LOG.json')


def get_current_version():
    """현재 버전 읽기"""
    with open(PKG_PATH, 'r') as f:
        pkg = json.load(f)
    parsed = parse_semver(pkg['version'])
    parsed['raw'] = pkg['version']
    return parsed


def parse_semver(version_str):
    """SemVer 파싱"""
    m = re.match(r'^(\d+)\.(\d+)\.(\d+)(?:-(.+))?(?:\+(.+))?$', version_str)
    if not m:
        raise ValueError(f"Invalid SemVer: {version_str}")
    return {
        'major': int(m.group(1)),
        'minor': int(m.group(2)),
        'patch': int(m.group(3)),
        'pre_release': m.group(4) or None,
        'build_meta': m.group(5) or None,
    }


def bump_version(current, bump_type):
    """버전 증가"""
    next_ver = dict(current)
    if bump_type == 'major':
        next_ver['major'] += 1
        next_ver['minor'] = 0
        next_ver['patch'] = 0
    elif bump_type == 'minor':
        next_ver['minor'] += 1
        next_ver['patch'] = 0
    elif bump_type == 'patch':
        next_ver['patch'] += 1
    else:
        raise ValueError(f"Unknown bump type: {bump_type}. Use major/minor/patch")

    next_ver['raw'] = f"{next_ver['major']}.{next_ver['minor']}.{next_ver['patch']}"
    return next_ver


def write_version(new_version):
    """package.json 쓰기"""
    with open(PKG_PATH, 'r') as f:
        pkg = json.load(f)
    pkg['version'] = new_version['raw']
    with open(PKG_PATH, 'w') as f:
        json.dump(pkg, f, indent=2)
        f.write('\n')
    print(f"📦 package.json: {new_version['raw']}")


def append_version_log(previous, next_ver, bump_type, note=''):
    """버전 히스토리 기록"""
    log = []
    if os.path.exists(VERSION_LOG_PATH):
        with open(VERSION_LOG_PATH, 'r') as f:
            log = json.load(f)

    log.append({
        'from': previous,
        'to': next_ver,
        'type': bump_type,
        'date': datetime.now().isoformat(),
        'note': note,
    })

    with open(VERSION_LOG_PATH, 'w') as f:
        json.dump(log, f, indent=2)
    print("📝 VERSION_LOG.json updated")


def read_version_log():
    """버전 히스토리 읽기"""
    if not os.path.exists(VERSION_LOG_PATH):
        return []
    with open(VERSION_LOG_PATH, 'r') as f:
        return json.load(f)