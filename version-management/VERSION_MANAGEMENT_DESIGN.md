# Onlyone OneChat 버전 관리 시스템 설계

> 작성일: 2026-05-02
> 대상: Onlyone OneChat (구 AIvote OneChat)

---

## 1. 개요

본 문서는 Onlyone OneChat의 버전 관리 시스템 설계를 다룹니다. 기업에서 일반적으로 사용하는 버전 관리 방식(Semantic Versioning + Conventional Commits + CI/CD 자동화)을 기반으로, OneChat에 최적화된 버전 관리 체계를 제안합니다.

---

## 2. 버전 체계: Semantic Versioning (SemVer)

### 2.1 형식

```
MAJOR.MINOR.PATCH[-pre-release][+build-metadata]

예시:
  1.0.0          # 최초 정식 릴리즈
  1.2.5          # MAJOR=1, MINOR=2, PATCH=5
  2.0.0-beta.1   # 메이저 업데이트 사전 테스트
  1.0.0+20260502 # 빌드 메타데이터 포함
```

### 2.2 버전 증가 규칙

| 변경 유형 | 버전 증가 | 예시 |
|-----------|----------|------|
| **BREAKING CHANGE** (API/UI 완전 변경, 하위호환 없음) | **MAJOR** +1, MINOR/PATCH=0 | 1.2.3 → 2.0.0 |
| **기능 추가** (하위호환 유지, 신규 기능) | **MINOR** +1, PATCH=0 | 1.2.3 → 1.3.0 |
| **버그 수정** (패치, 핫픽스) | **PATCH** +1 | 1.2.3 → 1.2.4 |
| 문서/리팩토링/스타일 | 변경 없음 | 1.2.3 → 1.2.3 |

### 2.3 Pre-release 식별자

```
-alpha.1   # 내부 개발 테스트
-beta.1    # 외부 일부 사용자 테스트  
-rc.1      # Release Candidate (출시 후보)
```

---

## 3. Git 전략: Conventional Commits + Trunk-Based Development

### 3.1 Commit 메시지 규칙 (Conventional Commits)

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

#### 타입(type) 목록

| 타입 | 설명 | 버전 영향 |
|------|------|-----------|
| `feat` | 새로운 기능 추가 | **MINOR** ↑ |
| `fix` | 버그 수정 | **PATCH** ↑ |
| `BREAKING CHANGE` | 하위호환성 깨짐 (본문/footer에 표기) | **MAJOR** ↑ |
| `docs` | 문서 변경 | 없음 |
| `style` | 코드 스타일 (포맷팅, 세미콜론 등) | 없음 |
| `refactor` | 리팩토링 (기능 변경 없음) | 없음 |
| `perf` | 성능 개선 | PATCH ↑ |
| `test` | 테스트 코드 추가/수정 | 없음 |
| `chore` | 빌드/패키지/설정 변경 | 없음 |
| `ci` | CI/CD 설정 변경 | 없음 |
| `build` | 빌드 시스템 변경 | 없음 |

#### 예시

```bash
feat(chat): 실시간 메시지 알림 기능 추가
fix(auth): 로그인 세션 만료 버그 수정
feat(api)!: REST API v2로 마이그레이션 (BREAKING CHANGE)
docs(readme): 설치 가이드 업데이트
```

### 3.2 브랜치 전략

```
main (또는 master)
  ├── feature/chat-realtime    # 신규 기능 개발
  ├── fix/login-session        # 버그 수정  
  ├── release/2.0.0            # 릴리즈 준비
  └── hotfix/1.2.5             # 긴급 패치

main ← PR Merge ← feature/* (Squash Merge 권장)
main ← hotfix/* (긴급 패치 적용)
```

---

## 4. 자동화 파이프라인 (CI/CD)

### 4.1 전체 플로우

```
[Developer Commit]
       │
       ▼
[GitHub/GitLab Repository]
       │
       ▼
[CI Pipeline Triggered]
       │
       ├─ 1. Lint & Test
       ├─ 2. Build
       ├─ 3. semantic-release 분석
       │      ├─ Commit 메시지 파싱
       │      ├─ 다음 버전 결정
       │      ├─ CHANGELOG.md 생성
       │      ├─ Git Tag 생성 (v1.2.3)
       │      └─ GitHub Release 생성
       │
       ▼
[CD Pipeline]
       │
       ├─ Deploy to Staging (자동)
       └─ Deploy to Production (승인 후)
```

### 4.2 GitHub Actions 예시

```yaml
# .github/workflows/release.yml
name: Auto Release

on:
  push:
    branches: [main]

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0  # 전체 히스토리 필요

      - uses: actions/setup-node@v4
        with:
          node-version: '20'

      - run: npm ci
      - run: npm test

      - name: Semantic Release
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: npx semantic-release
```

### 4.3 semantic-release 설정

```javascript
// .releaserc.js
module.exports = {
  branches: ['main'],
  plugins: [
    '@semantic-release/commit-analyzer',   // 커밋 분석 → 버전 결정
    '@semantic-release/release-notes-generator', // 릴리즈 노트 생성
    '@semantic-release/changelog',         // CHANGELOG.md 업데이트
    ['@semantic-release/npm', { npmPublish: false }], // package.json 버전 업데이트
    ['@semantic-release/git', {
      assets: ['package.json', 'CHANGELOG.md'],
      message: 'chore(release): ${nextRelease.version}\n\n${nextRelease.notes}'
    }],
    '@semantic-release/github',            // GitHub Release 생성
  ]
};
```

---

## 5. 버전 정보 표시 자동화

### 5.1 접근 방식

OneChat 웹앱의 햄버거 메뉴 하단 등에 표시되는 버전 정보를 자동으로 동기화하는 방법:

#### 방법 1: Build-time Injection (권장)

```javascript
// build.js - 빌드 시 버전 주입
const fs = require('fs');
const { version } = require('./package.json');

// HTML 파일에 버전 치환
let html = fs.readFileSync('index.html', 'utf8');
html = html.replace(
  /<span id="app-version">.*?<\/span>/,
  `<span id="app-version">Onlyone OneChat v${version}</span>`
);
fs.writeFileSync('dist/index.html', html);
```

#### 방법 2: Runtime API

```javascript
// version.js - 런타임에 버전 API 호출
async function loadVersion() {
  const res = await fetch('/api/version');
  const { version, buildDate } = await res.json();
  document.getElementById('app-version').textContent = 
    `Onlyone OneChat v${version}`;
}
```

#### 방법 3: Meta Tag 방식

```html
<!-- index.html -->
<meta name="app-version" content="1.2.3">
<meta name="app-name" content="Onlyone OneChat">

<script>
  const version = document.querySelector('meta[name="app-version"]').content;
  document.getElementById('app-version').textContent = 
    `Onlyone OneChat v${version}`;
</script>
```

### 5.2 기업 사례별 접근법 비교

| 기업 | 방식 | 특징 |
|------|------|------|
| **Slack** | Build-time Injection | CI에서 `$npm_package_version` 주입 |
| **Figma** | Runtime API | `/api/version` → 서버에서 동적 반환 |
| **VSCode** | Git Tag 기반 | `git describe --tags` → 빌드 시 결정 |
| **Notion** | 환경변수 기반 | `REACT_APP_VERSION` → 빌드 시 고정 |
| **GitHub** | Release Tag 기반 | GitHub Release = 버전 |

---

## 6. OneChat 권장 구성

### 6.1 디렉토리 구조

```
onechat/
├── .github/
│   └── workflows/
│       ├── ci.yml          # PR 체크 (lint, test, build)
│       └── release.yml     # 머지 → 자동 릴리즈
├── .husky/
│   ├── commit-msg          # 커밋 메시지 검증
│   └── pre-commit          # 린트 체크
├── commitlint.config.js    # 커밋 컨벤션 규칙
├── .releaserc.js           # semantic-release 설정
├── package.json            # 버전 필드 포함
├── CHANGELOG.md            # 자동 생성
├── src/
│   ├── version.js          # 버전 로드 유틸
│   └── ...
└── public/
    └── index.html          # <span id="app-version"> 치환 대상
```

### 6.2 햄버거 메뉴 수정 코드

```html
<!-- 햄버거 드롭다운 하단 버전 정보 -->
<div class="sidebar-footer">
  <span id="app-version">Onlyone OneChat v1.0.0</span>
</div>
```

### 6.3 버전 자동 업데이트 스크립트

```javascript
// scripts/inject-version.js
const fs = require('fs');
const path = require('path');
const pkg = require('../package.json');

const APP_NAME = 'Onlyone OneChat';
const VERSION = pkg.version;
const BUILD_DATE = new Date().toISOString().split('T')[0];
const VERSION_STRING = `${APP_NAME} v${VERSION}`;

// 1. HTML 파일들 치환
['index.html', 'dist/index.html'].forEach(file => {
  const filePath = path.join(__dirname, '..', file);
  if (fs.existsSync(filePath)) {
    let content = fs.readFileSync(filePath, 'utf8');
    content = content.replace(
      /<span id="app-version">.*?<\/span>/g,
      `<span id="app-version">${VERSION_STRING}</span>`
    );
    content = content.replace(
      /<meta name="app-version" content=".*?">/g,
      `<meta name="app-version" content="${VERSION}">`
    );
    fs.writeFileSync(filePath, content);
  }
});

console.log(`✅ Version injected: ${VERSION_STRING} (${BUILD_DATE})`);
```

### 6.4 package.json 스크립트

```json
{
  "name": "onlyone-onechat",
  "version": "1.0.0",
  "scripts": {
    "build": "node scripts/inject-version.js && vite build",
    "release": "standard-version",
    "release:minor": "standard-version --release-as minor",
    "release:major": "standard-version --release-as major",
    "release:patch": "standard-version --release-as patch",
    "prepare": "husky install"
  }
}
```

---

## 7. 운영 프로세스

### 7.1 일상 개발 흐름

```
1. Branch 생성: feature/신규기능
2. 개발 + Conventional Commits으로 커밋
3. PR 생성 → 코드 리뷰 → Squash Merge to main
4. CI에서 semantic-release 실행
5. 버전 자동 결정 → Tag 생성 → Release 노트 생성
6. CD 파이프라인에서 빌드/배포
```

### 7.2 핫픽스 흐름

```
1. hotfix/버그명 브랜치 생성
2. fix(scope): 설명 → 커밋
3. PR → 머지
4. PATCH 버전 자동 증가 → v1.2.3 → v1.2.4
5. 긴급 배포
```

### 7.3 버전 정책

| 상황 | 액션 |
|------|------|
| **사소한 텍스트/스타일 변경** | commit type: `style` → 버전 변동 없음 |
| **버그 수정** | commit type: `fix` → PATCH +1 |
| **신규 기능 추가** | commit type: `feat` → MINOR +1 |
| **주요 UI/API 변경** | `BREAKING CHANGE:` footer 포함 → MAJOR +1 |

---

## 8. 요약

| 항목 | 선택 |
|------|------|
| **버전 체계** | Semantic Versioning (MAJOR.MINOR.PATCH) |
| **커밋 규칙** | Conventional Commits (feat/fix/...) |
| **자동화 도구** | semantic-release + GitHub Actions |
| **릴리즈 노트** | CHANGELOG.md 자동 생성 |
| **버전 표시** | Build-time Injection (HTML 치환) |
| **브랜치 전략** | Trunk-Based (main + feature branches) |
| **배포** | CI/CD 자동화 (Staging 자동, Production 승인) |

---

## 9. 도입 로드맵

```
Phase 1 (1주차): Conventional Commits 도입 + commitlint 설정
Phase 2 (2주차): semantic-release 설정 + CHANGELOG 자동화
Phase 3 (3주차): CI/CD 파이프라인 구축 (GitHub Actions)
Phase 4 (4주차): Build-time 버전 주입 + UI 자동 표시
Phase 5 (5주차): Pre-release 채널 (alpha/beta/rc) 도입
```