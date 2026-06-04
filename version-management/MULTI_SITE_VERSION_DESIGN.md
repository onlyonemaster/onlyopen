# 🏢 회사 전체 통합 버전 관리 시스템 — 상세 설계서

> **설명 수준**: 초등학생도 이해할 수 있게!
> **작성일**: 2026-05-02
> **작성자**: 아리아 (AI 개발자)
> **대상 서버**: 1대 ~ 5대, 각 서버 안에 여러 개의 홈페이지

---

## 📖 목차

1. [비유로 이해하기: 우리 동네 문구점 이야기](#1-비유로-이해하기)
2. [현재 시스템 분석: 지금은 어떻게 되어 있나?](#2-현재-시스템-분석)
3. [문제점: 왜 지금 구조로는 안 될까?](#3-문제점)
4. [새로운 설계: 어떻게 바꿔야 할까?](#4-새로운-설계)
5. [데이터 구조: 컴퓨터는 정보를 어떻게 저장할까?](#5-데이터-구조)
6. [화면 설계: 사용자는 무엇을 보게 될까?](#6-화면-설계)
7. [단계별 수정 계획: 무엇부터 고칠까?](#7-단계별-수정-계획)
8. [코드 수정 상세: 실제로 어떤 파일을 어떻게 바꿀까?](#8-코드-수정-상세)

---

## 1. 비유로 이해하기

### 1.1 🏪 우리 동네 문구점 이야기

**상황**: 당신은 동네 문구점 5개를 운영하는 사장님입니다.

| 가게 이름 | 위치 | 종류 |
|-----------|------|------|
| 🏪 KIAM 본점 | 서울 강남 | 실제 영업 매장 (운영환경) |
| 🏪 KIAM 실험실 | 서울 강남 2층 | 새 상품 테스트하는 곳 (개발환경) |
| 🏪 챗봇 매장 | 서울 강남 별관 | 채팅 서비스만 파는 곳 |
| 🏪 슈퍼챗봇 매장 | 서울 강남 별관 2층 | 최신 채팅 서비스 |
| 🏪 결제 전용 매장 | 서울 강남 지하 | 결제만 처리하는 곳 |

**지금 방식 (❌ 문제 있음)**:
- 각 가게에서 팔고 있는 "상품 버전"을 **따로따로** 수첩에 적고 있음
- 본점 수첩에는 "지우개 v1.0.1"이라고 적혀 있고
- 실험실 수첩은 아예 없음 (누가 적는지 모름)
- 사장님이 "우리 가게들 지금 몇 버전이지?" 하고 물어보면... 😰 **알 수가 없음!**

**바꿔야 할 방식 (✅ 새 설계)**:
- 사장님 책상에 **"통합 장부" 한 권**을 둠
- 모든 가게의 상품 버전을 이 장부 하나에 기록
- "본점은 v1.0.1, 실험실은 v1.0.1-dev, 챗봇은 v2.0.0..."
- 한눈에 모든 가게의 버전을 볼 수 있음!

### 1.2 🖥️ 컴퓨터 용어로 번역하면

| 문구점 비유 | 실제 컴퓨터 용어 |
|-------------|-----------------|
| 가게 (매장) | **사이트 (Site)** = 홈페이지 하나 |
| 사장님 책상 | **중앙 서버** = 버전 관리 시스템이 설치된 곳 |
| 통합 장부 | **데이터베이스(JSON 파일들)** = 정보를 저장하는 파일 |
| 상품 버전 | **소프트웨어 버전** = MAJOR.MINOR.PATCH |
| 가게 위치 (강남, 별관) | **서버** = 실제 컴퓨터가 있는 물리적 장소 |

---

## 2. 현재 시스템 분석

### 2.1 지금 운영 중인 사이트들

현재 이 서버 안에는 **최소 7개**의 사이트가 돌아가고 있어:

| # | 도메인 | 문서 루트 | 환경 | 용도 |
|---|--------|----------|------|------|
| 1 | `kiam.kr` | `/home/kiam` | 🟢 운영 | 메인 사이트 (www, api 포함) |
| 2 | `dev.kiam.kr` | `/disk/daily/home/kiam` | 🟡 개발 | 개발/테스트 환경 |
| 3 | `chatbot.kiam.kr` | `/home/kiam/aimessage/chatbot/frontend` | 🟢 운영 | 로컬 챗봇 서비스 |
| 4 | `superchatbot.kiam.kr` | FastAPI 프록시 (포트 8000) | 🟢 운영 | 슈퍼챗봇 v3.0 |
| 5 | `spscenter.net` | `/home/btss` | 🟢 운영 | SPS 센터 |
| 6 | `work.kiam.kr` | `/home/kiamwork/www` | 🔴 주석처리 | 업무 포털 (비활성) |
| 7 | `api.payment.local` | `/payment-api/app` (포트 8888) | 🟢 운영 | 결제 API |

### 2.2 현재 버전 관리 시스템 구조

```
/home/vermanager/          ← 버전 관리 시스템이 설치된 폴더
├── index.php              ← 메인 페이지 (라우터 역할)
├── config.php             ← 설정 파일 (앱 이름, 데이터 경로)
├── .htaccess              ← Apache 규칙
├── api/
│   ├── version.php        ← 버전 정보 알려주는 API
│   ├── release.php        ← 새 버전 출시하는 API
│   ├── history.php        ← 버전 변경 기록 API
│   └── improvements.php   ← 개선사항 API
├── pages/
│   ├── dashboard.php      ← 대시보드 화면
│   ├── release.php        ← 버전 출시 화면
│   ├── history.php        ← 변경 기록 화면
│   ├── improvements.php   ← 개선사항 관리 화면
│   ├── changelog.php      ← 체인지로그 화면
│   └── settings.php       ← 설정 화면
├── data/                  ← 정보를 저장하는 JSON 파일들
│   ├── package.json       ← 현재 버전 정보 (v1.0.1)
│   ├── version_history.json  ← 버전 변경 기록
│   ├── changelog.json     ← 변경 내용 기록
│   └── improvements.json  ← 개선 요청 목록
├── css/                   ← 디자인 파일
├── js/                    ← 자바스크립트 파일
└── assets/                ← 이미지, 아이콘 등
```

### 2.3 현재 config.php 의 문제

```php
// 현재 config.php — 딱 하나의 앱만 관리할 수 있음!
define('APP_NAME', 'Onlyone OneChat');  // ← 하나만!!!
define('APP_SLUG', 'vermanager');
define('DATA_DIR', __DIR__ . '/data');
```

**무엇이 문제일까?**
- `APP_NAME`이 딱 하나만 정해져 있음
- 사이트가 여러 개여도 "Onlyone OneChat" 하나만 보여줌
- `data/` 폴더에 버전 정보를 저장하는데, 이것도 한 세트만 있음

---

## 3. 문제점

### 3.1 현재 구조로는 안 되는 이유

```
지금 구조:
  앱 1개 → 버전 1개 → 한 세트의 데이터

필요한 구조:
  서버 여러 대 → 각 서버에 사이트 여러 개 → 각 사이트마다 버전 따로 관리
```

**구체적인 문제 5가지**:

| 번호 | 문제 | 설명 |
|------|------|------|
| ❌ 1 | **사이트 구분 없음** | kiam.kr 인지 dev.kiam.kr 인지 구분해서 버전을 저장하지 않음 |
| ❌ 2 | **서버 구분 없음** | 다른 물리 서버에 있는 사이트들의 버전을 한곳에 모을 방법이 없음 |
| ❌ 3 | **데이터 한 세트만** | `data/package.json`이 딱 하나라서 여러 사이트 정보를 담을 수 없음 |
| ❌ 4 | **운영 vs 개발 분리 안 됨** | 같은 소프트웨어라도 운영은 v1.0.1, 개발은 v1.0.2-dev 일 수 있는데 구분 불가 |
| ❌ 5 | **확장 불가능** | 새 사이트가 생길 때마다 새 폴더를 통째로 복사해야 함 |

---

## 4. 새로운 설계

### 4.1 핵심 개념: "사이트 등록부" 패턴

모든 것은 **사이트(Site)** 단위로 관리한다.

```
세상에서 가장 간단한 구조:
  ┌─────────────────────────────────────┐
  │        버전 관리 중앙 시스템         │
  │         (kiam.kr/vermanager)         │
  │                                     │
  │   📋 사이트 등록부                   │
  │   ├── KIAM 운영 (kiam.kr)           │
  │   │   └── 버전: 1.0.1              │
  │   ├── KIAM 개발 (dev.kiam.kr)       │
  │   │   └── 버전: 1.0.2-dev          │
  │   ├── 챗봇 (chatbot.kiam.kr)        │
  │   │   └── 버전: 2.1.0              │
  │   └── ...                          │
  │                                     │
  │   각 사이트마다:                     │
  │   ├── 자체 버전 이력                │
  │   ├── 자체 체인지로그               │
  │   └── 자체 개선사항                 │
  └─────────────────────────────────────┘
```

### 4.2 새로운 폴더 구조

```
/home/vermanager/                  ← 버전 관리 시스템 (그대로 유지)
│
├── index.php                      ← 🔧 수정: 사이트 선택 가능하게
├── config.php                     ← 🔧 수정: 다중 사이트 지원
├── .htaccess                      ← ✅ 그대로
│
├── api/
│   ├── version.php                ← 🔧 수정: 사이트별 버전 반환
│   ├── release.php                ← 🔧 수정: 특정 사이트에 릴리즈
│   ├── history.php                ← 🔧 수정: 사이트별 이력
│   ├── improvements.php           ← 🔧 수정: 사이트별 개선사항
│   └── sites.php                  ← ✨ 신규: 사이트 목록/추가/삭제 API
│
├── pages/
│   ├── dashboard.php              ← 🔧 수정: 여러 사이트 한눈에
│   ├── site-detail.php            ← ✨ 신규: 사이트 상세 페이지
│   ├── release.php                ← 🔧 수정: 사이트 선택 후 릴리즈
│   ├── history.php                ← 🔧 수정: 사이트별 필터링
│   ├── improvements.php           ← 🔧 수정: 사이트별 필터링
│   ├── changelog.php              ← 🔧 수정: 사이트별 필터링
│   └── settings.php               ← 🔧 수정: 사이트 관리
│
├── data/
│   ├── site-registry.json         ← ✨ 신규: 모든 사이트 목록 (등록부)
│   └── sites/                     ← ✨ 신규: 사이트별 데이터 폴더
│       ├── kiam-prod/             ←   KIAM 운영환경 데이터
│       │   ├── package.json
│       │   ├── version_history.json
│       │   ├── changelog.json
│       │   └── improvements.json
│       ├── kiam-dev/              ←   KIAM 개발환경 데이터
│       │   ├── package.json
│       │   ├── version_history.json
│       │   ├── changelog.json
│       │   └── improvements.json
│       └── chatbot/               ←   챗봇 데이터
│           ├── package.json
│           ├── version_history.json
│           ├── changelog.json
│           └── improvements.json
│
├── css/                           ← ✅ 그대로
├── js/                            ← 🔧 수정: 사이트 전환 기능 추가
└── assets/                        ← ✅ 그대로
```

### 4.3 사이트 등록부 (site-registry.json) 상세 설계

```json
{
    "registry_version": "1.0.0",
    "last_updated": "2026-05-02T15:00:00+09:00",
    "sites": {
        "kiam-prod": {
            "id": "kiam-prod",
            "name": "KIAM 운영환경",
            "domain": "kiam.kr",
            "aliases": ["www.kiam.kr", "api.kiam.kr"],
            "server_hostname": "main-server",
            "server_ip": "192.168.1.10",
            "server_port": 22,
            "environment": "production",
            "document_root": "/home/kiam",
            "app_name": "Onlyone OneChat",
            "app_type": "php",
            "description": "KIAM 메인 운영 사이트",
            "status": "active",
            "created_at": "2026-05-02T10:00:00+09:00",
            "updated_at": "2026-05-02T10:48:25+09:00"
        },
        "kiam-dev": {
            "id": "kiam-dev",
            "name": "KIAM 개발환경",
            "domain": "dev.kiam.kr",
            "aliases": ["*.dev.kiam.kr"],
            "server_hostname": "main-server",
            "server_ip": "192.168.1.10",
            "server_port": 22,
            "environment": "development",
            "document_root": "/disk/daily/home/kiam",
            "app_name": "Onlyone OneChat (개발)",
            "app_type": "php",
            "description": "KIAM 개발/테스트 환경",
            "status": "active",
            "parent_site_id": "kiam-prod",
            "created_at": "2026-05-02T15:00:00+09:00",
            "updated_at": "2026-05-02T15:00:00+09:00"
        },
        "chatbot": {
            "id": "chatbot",
            "name": "챗봇 서비스",
            "domain": "chatbot.kiam.kr",
            "aliases": [],
            "server_hostname": "main-server",
            "server_ip": "192.168.1.10",
            "server_port": 22,
            "environment": "production",
            "document_root": "/home/kiam/aimessage/chatbot/frontend",
            "app_name": "KIAM Chatbot",
            "app_type": "php",
            "description": "로컬 챗봇 서비스 (삼자대화 + PWA)",
            "status": "active",
            "created_at": "2026-05-02T15:00:00+09:00",
            "updated_at": "2026-05-02T15:00:00+09:00"
        },
        "superchatbot": {
            "id": "superchatbot",
            "name": "슈퍼챗봇",
            "domain": "superchatbot.kiam.kr",
            "aliases": [],
            "server_hostname": "main-server",
            "server_ip": "192.168.1.10",
            "server_port": 22,
            "environment": "production",
            "document_root": "프록시 (127.0.0.1:8000)",
            "app_name": "SuperChatbot v3.0",
            "app_type": "python-fastapi",
            "description": "Python FastAPI 기반 슈퍼챗봇",
            "status": "active",
            "created_at": "2026-05-02T15:00:00+09:00",
            "updated_at": "2026-05-02T15:00:00+09:00"
        }
    }
}
```

### 4.4 사이트 간 관계: 부모-자식 연결

```
                    KIAM 운영 (kiam.kr)
                    parent_site_id: null
                           │
                           │ "이 사이트의 개발버전이야"
                           ▼
                    KIAM 개발 (dev.kiam.kr)
                    parent_site_id: "kiam-prod"
                    
                    
                    챗봇 (chatbot.kiam.kr)
                    parent_site_id: null
                           │
                           ▼
                    슈퍼챗봇 (superchatbot.kiam.kr)
                    parent_site_id: "chatbot"
```

**이렇게 연결하면 좋은 점**:
- "운영은 v1.0.1 인데 개발은 몇이지?" 한 번에 비교 가능
- 운영에 배포할 때 "개발에서 테스트 완료된 버전"인지 확인 가능

### 4.5 다중 서버 지원: 원격 사이트 연동

회사에 서버가 5대라면:

```
서버 1 (192.168.1.10) - 메인 서버
├── kiam.kr (운영)
├── dev.kiam.kr (개발)
├── chatbot.kiam.kr
├── superchatbot.kiam.kr
└── 📍 버전 관리 시스템 여기 설치!

서버 2 (192.168.1.20) - 데이터베이스 서버
├── db-admin.kr
└── ... (버전 관리 시스템에 "등록"만 해둠)

서버 3 (192.168.1.30) - 파일 서버
├── files.kiam.kr
└── ... (버전 관리 시스템에 "등록"만 해둠)

서버 4 (192.168.1.40) - 채팅 서버
├── chat2.kiam.kr
└── ... (버전 관리 시스템에 "등록"만 해둠)

서버 5 (192.168.1.50) - 백업 서버
├── backup.kiam.kr
└── ... (버전 관리 시스템에 "등록"만 해둠)
```

**다른 서버에 있는 사이트의 버전은 어떻게 알 수 있을까?**

```
방법 1: 각 서버에 작은 버전 알리미 설치
  ┌──────────────┐       HTTP 요청        ┌──────────────────┐
  │  서버 1      │ ◄──────────────────► │  서버 2           │
  │  버전 관리   │  GET /api/version     │  버전 알리미      │
  │  중앙 시스템  │  ──────────────────► │  (작은 PHP 파일)  │
  └──────────────┘                       └──────────────────┘

방법 2: 사람이 직접 입력 (간단한 방식)
  - 각 서버 담당자가 버전 관리 시스템에 접속해서
  - "지금 우리 서버 v2.1.0 이야!" 하고 직접 입력

방법 3: Git/GitHub 연동 (가장 자동화된 방식)
  - GitHub에 코드가 올라갈 때마다
  - 자동으로 버전 관리 시스템에 알려줌
```

---

## 5. 데이터 구조

### 5.1 핵심 데이터: 사이트(Site)

```php
// 사이트 한 개의 정보 구조 (PHP 배열로 표현)
$site = [
    'id'              => 'kiam-prod',        // 고유번호 (영문, 숫자, 하이픈만)
    'name'            => 'KIAM 운영환경',     // 보여주는 이름 (한글 OK)
    'domain'          => 'kiam.kr',           // 도메인 주소
    'aliases'         => ['www.kiam.kr'],     // 다른 도메인 이름들
    'server_hostname' => 'main-server',       // 서버 이름
    'server_ip'       => '192.168.1.10',     // 서버 IP 주소
    'server_port'     => 22,                  // SSH 포트 (원격접속용)
    'environment'     => 'production',        // 'production' 또는 'development'
    'document_root'   => '/home/kiam',        // 소스코드 경로
    'app_name'        => 'Onlyone OneChat',   // 앱 이름
    'app_type'        => 'php',               // 'php', 'python', 'nodejs' 등
    'description'     => '메인 운영 사이트',   // 설명
    'status'          => 'active',            // 'active' 또는 'inactive'
    'parent_site_id'  => null,                // 부모 사이트 (개발→운영 연결)
    'created_at'      => '2026-05-02T10:00:00+09:00',
    'updated_at'      => '2026-05-02T10:48:25+09:00',
];
```

### 5.2 각 사이트별 데이터: 버전 이력

```php
// 버전 이력 한 개의 구조
$version_entry = [
    'id'                  => 'rel_69f557e96c558',
    'site_id'             => 'kiam-prod',     // ← 중요! 어떤 사이트인지
    'from'                => '1.0.0',
    'to'                  => '1.0.1',
    'type'                => 'patch',          // major / minor / patch
    'date'                => '2026-05-02T10:48:25+09:00',
    'note'                => '메시지 발송 속도 개선',
    'linked_improvements' => ['imp_4', 'imp_5'],
    'imp_count'           => 2,
    'released_by'         => '아리아',          // 누가 릴리즈했는지
];
```

### 5.3 config.php 개편안

```php
<?php
/**
 * Onlyone OneChat — 통합 버전 관리 시스템
 * 다중 사이트·다중 서버 지원 설정
 */

// ── 기본 정보 ──
define('SYSTEM_NAME', 'Onlyone 통합 버전 관리');
define('SYSTEM_SLUG', 'vermanager');
define('DATA_DIR', __DIR__ . '/data');
define('SITES_DATA_DIR', DATA_DIR . '/sites');
define('REGISTRY_FILE', DATA_DIR . '/site-registry.json');

// ── 데이터 폴더 자동 생성 ──
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!is_dir(SITES_DATA_DIR)) mkdir(SITES_DATA_DIR, 0755, true);

// ── 사이트 등록부 읽기/쓰기 ──
function get_registry(): array {
    if (!file_exists(REGISTRY_FILE)) return ['sites' => []];
    return json_decode(file_get_contents(REGISTRY_FILE), true) ?: ['sites' => []];
}

function save_registry(array $registry): bool {
    $registry['last_updated'] = date('c');
    return file_put_contents(
        REGISTRY_FILE,
        json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// ── 특정 사이트의 데이터 경로 ──
function site_data_dir(string $site_id): string {
    $dir = SITES_DATA_DIR . '/' . $site_id;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function site_data_path(string $site_id, string $file): string {
    return site_data_dir($site_id) . '/' . $file;
}

// ── 사이트별 데이터 읽기/쓰기 ──
function site_read_json(string $site_id, string $file): array {
    $path = site_data_path($site_id, $file);
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?: [];
}

function site_write_json(string $site_id, string $file, array $data): bool {
    $path = site_data_path($site_id, $file);
    return file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// ── 모든 사이트 목록 ──
function get_all_sites(): array {
    $registry = get_registry();
    return $registry['sites'] ?? [];
}

function get_active_sites(): array {
    return array_filter(get_all_sites(), fn($s) => ($s['status'] ?? 'active') === 'active');
}

function get_site(string $site_id): ?array {
    $sites = get_all_sites();
    return $sites[$site_id] ?? null;
}

// ── 사이트 추가 ──
function add_site(array $site): bool {
    $registry = get_registry();
    if (!isset($site['id'])) return false;
    $site['created_at'] = $site['created_at'] ?? date('c');
    $site['updated_at'] = date('c');
    $registry['sites'][$site['id']] = $site;
    // 데이터 폴더 생성
    site_data_dir($site['id']);
    return save_registry($registry);
}

// ── 사이트별 버전 정보 ──
function site_get_package(string $site_id): array {
    return site_read_json($site_id, 'package.json');
}

function site_get_current_version(string $site_id): array {
    $pkg = site_get_package($site_id);
    if (empty($pkg)) return ['major' => 1, 'minor' => 0, 'patch' => 0, 'raw' => '1.0.0'];
    $v = $pkg['version'] ?? '1.0.0';
    $parts = explode('.', $v);
    return [
        'major' => (int)($parts[0] ?? 1),
        'minor' => (int)($parts[1] ?? 0),
        'patch' => (int)($parts[2] ?? 0),
        'raw'   => $v,
    ];
}

// ── 사이트별 버전 이력 ──
function site_get_history(string $site_id): array {
    return site_read_json($site_id, 'version_history.json');
}

// ── 사이트별 개선사항 ──
function site_get_improvements(string $site_id): array {
    return site_read_json($site_id, 'improvements.json');
}

// ── 사이트별 체인지로그 ──
function site_get_changelog(string $site_id): array {
    return site_read_json($site_id, 'changelog.json');
}

// ── SemVer 도구 (공통) ──
function parse_semver(string $v): array {
    $parts = explode('.', $v);
    return [
        'major' => (int)($parts[0] ?? 0),
        'minor' => (int)($parts[1] ?? 0),
        'patch' => (int)($parts[2] ?? 0),
    ];
}

function bump_version(array $current, string $type): array {
    $next = [
        'major' => $current['major'],
        'minor' => $current['minor'],
        'patch' => $current['patch'],
    ];
    switch ($type) {
        case 'major': $next['major']++; $next['minor'] = 0; $next['patch'] = 0; break;
        case 'minor': $next['minor']++; $next['patch'] = 0; break;
        case 'patch': $next['patch']++; break;
    }
    $next['raw'] = "{$next['major']}.{$next['minor']}.{$next['patch']}";
    return $next;
}

// ── 전체 통계 ──
function get_system_stats(): array {
    $sites = get_active_sites();
    $prod_sites = array_filter($sites, fn($s) => ($s['environment'] ?? '') === 'production');
    $dev_sites  = array_filter($sites, fn($s) => ($s['environment'] ?? '') === 'development');

    return [
        'total_sites'      => count($sites),
        'production_sites' => count($prod_sites),
        'development_sites'=> count($dev_sites),
        'total_servers'    => count(array_unique(array_column($sites, 'server_hostname'))),
        'sites'            => $sites,
    ];
}
```

---

## 6. 화면 설계

### 6.1 메인 대시보드 (dashboard.php) — 가장 중요한 화면!

```
┌──────────────────────────────────────────────────────────┐
│  🔷 Onlyone 통합 버전 관리 시스템                         │
│  ────────────────────────────────────────────────────── │
│  전체 사이트: 7개 | 운영: 5개 | 개발: 1개 | 서버: 1대    │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  📊 전체 사이트 버전 현황                                  │
│  ┌─────────────────────────────────────────────────┐    │
│  │ 사이트           환경     현재 버전    최근 릴리즈      │    │
│  ├─────────────────────────────────────────────────┤    │
│  │ 🟢 kiam.kr       운영     v1.0.1     2026-05-02 │    │
│  │ 🟡 dev.kiam.kr   개발     v1.0.2-dev 2026-05-01 │    │
│  │ 🟢 chatbot.kr    운영     v2.1.0     2026-04-28 │    │
│  │ 🟢 superchatbot  운영     v3.0.1     2026-04-25 │    │
│  │ 🟢 spscenter.net 운영     v1.5.0     2026-04-20 │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  [+ 새 사이트 등록]                                       │
│                                                          │
├──────────────────────────────────────────────────────────┤
│  🕐 최근 릴리즈 활동                                      │
│  ┌─────────────────────────────────────────────────┐    │
│  │ 05/02 14:30  [kiam.kr] v1.0.0 → v1.0.1 (patch) │    │
│  │ 05/01 10:15  [dev.kiam.kr] v1.0.1 → v1.0.2-dev  │    │
│  │ 04/28 16:00  [chatbot] v2.0.0 → v2.1.0 (minor) │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### 6.2 사이트 상세 페이지 (site-detail.php)

```
┌──────────────────────────────────────────────────────────┐
│  🔷 KIAM 운영환경 (kiam.kr)                    [← 뒤로]   │
│  ────────────────────────────────────────────────────── │
│  환경: 🟢 운영 | 서버: main-server | 앱: Onlyone OneChat │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  📦 현재 버전: v1.0.1                                    │
│  ┌─────────────────────────────────────────────────┐    │
│  │         [🔴 Major]  [🟡 Minor]  [🟢 Patch]      │    │
│  │              새 버전 출시 버튼                     │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ── 연결된 개발환경 ──                                    │
│  🟡 dev.kiam.kr → v1.0.2-dev  [배포 가능 여부: ✅ OK]   │
│                                                          │
│  ── 버전 이력 ──                                         │
│  v1.0.1 (2026-05-02) patch — 메시지 발송 속도 개선       │
│  v1.0.0 (2026-04-01) — 최초 릴리즈                       │
│                                                          │
│  ── 체인지로그 ──                                        │
│  1.0.1: 메시지 발송 속도 최적화, 브랜드 변경              │
│                                                          │
│  ── 개선사항 (4건 대기중) ──                              │
│  ☐ 로그인 속도 개선                                       │
│  ☐ 다크모드 추가                                          │
│  ...                                                      │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### 6.3 사이트 등록 화면 (새로 추가)

```
┌──────────────────────────────────────────────────────────┐
│  🔷 새 사이트 등록                                        │
│  ────────────────────────────────────────────────────── │
│                                                          │
│  사이트 ID:   [________________] (영문, 숫자, 하이픈만)    │
│  사이트 이름: [________________]                         │
│  도메인:      [________________]                         │
│  서버:        [________________]                         │
│  서버 IP:     [___.___.___.___]                          │
│  환경:        (●) 운영  ( ) 개발                          │
│  문서 루트:   [________________]                         │
│  앱 이름:     [________________]                         │
│  앱 종류:     [PHP ▼]                                    │
│                                                          │
│  부모 사이트: [없음 ▼]  ← 개발환경이면 운영환경 선택       │
│                                                          │
│  설명:                                                   │
│  [____________________________________________]         │
│                                                          │
│  [등록하기]  [취소]                                       │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 7. 단계별 수정 계획

### 전체 로드맵

```
1단계: 데이터 구조 변경       ← 가장 먼저! (오늘)
2단계: 설정 파일 개편           ← 그 다음 (오늘~내일)
3단계: API 수정                 ← (내일)
4단계: 화면(UI) 수정            ← (2~3일)
5단계: 원격 서버 연동           ← (1주)
6단계: Git/GitHub 자동화        ← (2주)
```

### 1단계: 데이터 구조 변경 (가장 먼저 할 일)

```bash
# Step 1: data 폴더 백업
cp -r /home/vermanager/data /home/vermanager/data.backup_20260502

# Step 2: 사이트별 데이터 폴더 만들기
mkdir -p /home/vermanager/data/sites/kiam-prod
mkdir -p /home/vermanager/data/sites/kiam-dev

# Step 3: 기존 데이터를 운영환경 데이터로 이동
cp /home/vermanager/data/package.json /home/vermanager/data/sites/kiam-prod/
cp /home/vermanager/data/version_history.json /home/vermanager/data/sites/kiam-prod/
cp /home/vermanager/data/changelog.json /home/vermanager/data/sites/kiam-prod/
cp /home/vermanager/data/improvements.json /home/vermanager/data/sites/kiam-prod/

# Step 4: 사이트 등록부 생성
# (site-registry.json 파일을 새로 만든다)
```

### 2단계: config.php 완전히 새로 쓰기

기존 `config.php`를 위의 "5.3 config.php 개편안" 내용으로 교체.

### 3단계: API 파일들 수정

```
변경 전: GET /api/version → 항상 같은 데이터 반환
변경 후: GET /api/version?site=kiam-prod → 해당 사이트 버전만 반환
         GET /api/version?site=all → 모든 사이트 버전 한번에 반환
```

### 4단계: 대시보드 화면 개편

기존에는 한 사이트의 정보만 보여줬지만, 이제는:

1. 왼쪽 사이드바: 사이트 목록 (클릭해서 선택)
2. 중앙: 선택된 사이트의 상세 정보
3. 상단: 전체 통계 요약 (총 사이트 수, 서버 수 등)

---

## 8. 코드 수정 상세

### 8.1 index.php 수정

```php
<?php
/**
 * Onlyone 통합 버전 관리 — 메인 라우터
 * 변경: 사이트 선택 파라미터(?site=xxx) 추가
 */
require_once __DIR__ . '/config.php';

// ── 현재 선택된 사이트 ──
$current_site_id = $_GET['site'] ?? null;

// ── 경로 결정 ──
$route = $_GET['route'] ?? null;

if (!$route) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = parse_url($uri, PHP_URL_PATH);
    $uri = rtrim($uri, '/');
    $uri = preg_replace('#^/vermanager#', '', $uri);
    $uri = ltrim($uri, '/');
    $route = $uri ?: 'dashboard';
}

$route = preg_replace('#^index\.php/?#', '', $route);

// ── 라우터 ──
switch ($route) {
    case 'dashboard':
    case '':
        require __DIR__ . '/pages/dashboard.php';
        break;

    case 'site-detail':
        // 사이트가 지정되지 않았으면 대시보드로
        if (!$current_site_id) {
            header('Location: /vermanager/');
            exit;
        }
        require __DIR__ . '/pages/site-detail.php';
        break;

    case 'release':
        require __DIR__ . '/pages/release.php';
        break;

    case 'history':
        require __DIR__ . '/pages/history.php';
        break;

    case 'improvements':
        require __DIR__ . '/pages/improvements.php';
        break;

    case 'changelog':
        require __DIR__ . '/pages/changelog.php';
        break;

    case 'settings':
    case 'site-register':
        require __DIR__ . '/pages/settings.php';
        break;

    // ── API ──
    case 'api/version':
        require __DIR__ . '/api/version.php';
        break;

    case 'api/release':
        require __DIR__ . '/api/release.php';
        break;

    case 'api/improvements':
        require __DIR__ . '/api/improvements.php';
        break;

    case 'api/history':
        require __DIR__ . '/api/history.php';
        break;

    case 'api/sites':
        require __DIR__ . '/api/sites.php';
        break;

    default:
        http_response_code(404);
        echo '<h1>404</h1><p>페이지를 찾을 수 없습니다: ' . htmlspecialchars($route) . '</p>';
}
```

### 8.2 api/version.php 수정

```php
<?php
/**
 * API: 버전 정보 조회
 * GET /vermanager/api/version?site=kiam-prod  → 특정 사이트
 * GET /vermanager/api/version?site=all         → 모든 사이트
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$site_id = $_GET['site'] ?? null;

if ($site_id === 'all') {
    // 모든 사이트 버전 한번에
    $sites = get_active_sites();
    $result = [];
    foreach ($sites as $id => $site) {
        $ver = site_get_current_version($id);
        $history = site_get_history($id);
        $result[] = [
            'site_id'      => $id,
            'site_name'    => $site['name'],
            'domain'       => $site['domain'],
            'environment'  => $site['environment'],
            'version'      => $ver,
            'last_release' => !empty($history) ? end($history)['date'] ?? null : null,
            'display_string' => ($site['app_name'] ?? $site['name']) . ' v' . $ver['raw'],
        ];
    }
    echo json_encode(['ok' => true, 'sites' => $result, 'total' => count($result)]);
} elseif ($site_id && get_site($site_id)) {
    // 특정 사이트
    $site = get_site($site_id);
    $ver = site_get_current_version($site_id);
    $pkg = site_get_package($site_id);
    $history = site_get_history($site_id);
    $lastRelease = !empty($history) ? end($history) : null;

    echo json_encode([
        'ok'             => true,
        'site_id'        => $site_id,
        'site_name'      => $site['name'],
        'domain'         => $site['domain'],
        'environment'    => $site['environment'],
        'version'        => $ver,
        'package'        => $pkg,
        'last_release'   => $lastRelease,
        'display_string' => ($site['app_name'] ?? $site['name']) . ' v' . $ver['raw'],
    ]);
} else {
    // 사이트 미지정 → 사용 가능한 목록과 함께 안내
    $sites = get_active_sites();
    echo json_encode([
        'ok'           => true,
        'message'      => 'site 파라미터를 지정하세요. 예: ?site=kiam-prod 또는 ?site=all',
        'available_sites' => array_keys($sites),
        'first_site'   => !empty($sites) ? array_key_first($sites) : null,
    ]);
}
```

### 8.3 api/release.php 수정

```php
<?php
/**
 * API: 새 버전 릴리즈
 * POST /vermanager/api/release
 * Body: {
 *   "site": "kiam-prod",
 *   "type": "patch|minor|major",
 *   "note": "설명"
 * }
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST 요청만 가능합니다']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$site_id = $input['site'] ?? null;
$type = $input['type'] ?? 'patch';
$note = $input['note'] ?? '';

// ── 검증 ──
if (!$site_id || !get_site($site_id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '올바른 site_id를 지정하세요']);
    exit;
}

if (!in_array($type, ['major', 'minor', 'patch'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '릴리즈 종류: major, minor, patch']);
    exit;
}

// ── 버전 올리기 ──
$current = site_get_current_version($site_id);
$next = bump_version($current, $type);

// ── package.json 업데이트 ──
$pkg = site_get_package($site_id);
if (empty($pkg)) {
    $site = get_site($site_id);
    $pkg = [
        'name'    => $site_id,
        'version' => '1.0.0',
        'description' => ($site['app_name'] ?? $site['name']) . ' 버전 관리',
    ];
}
$pkg['version'] = $next['raw'];
$pkg['lastRelease'] = date('c');
site_write_json($site_id, 'package.json', $pkg);

// ── 개선사항 연결 ──
$linkedImps = $input['linked_improvements'] ?? [];
$impNotes = [];
if (!empty($linkedImps)) {
    $improvements = site_get_improvements($site_id);
    foreach ($improvements as &$imp) {
        if (in_array($imp['id'] ?? '', $linkedImps)) {
            $imp['released_in'] = $next['raw'];
        }
    }
    unset($imp);
    site_write_json($site_id, 'improvements.json', $improvements);
    foreach ($improvements as $imp) {
        if (in_array($imp['id'] ?? '', $linkedImps)) {
            $impNotes[] = ($imp['title'] ?? '(내용없음)')
                . ' [' . ($imp['category'] ?? '기타') . ']';
        }
    }
}

// ── 버전 이력 추가 ──
$history = site_get_history($site_id);
$history[] = [
    'id'                  => uniqid('rel_'),
    'site_id'             => $site_id,
    'from'                => $current['raw'],
    'to'                  => $next['raw'],
    'type'                => $type,
    'date'                => date('c'),
    'note'                => $note,
    'linked_improvements' => $linkedImps,
    'imp_count'           => count($linkedImps),
];
site_write_json($site_id, 'version_history.json', $history);

// ── 체인지로그 추가 ──
$changelog = site_get_changelog($site_id);
$changelog[] = [
    'id'      => uniqid('cl_'),
    'version' => $next['raw'],
    'date'    => date('Y-m-d'),
    'type'    => $type,
    'note'    => $note,
    'changes' => $impNotes,
];
site_write_json($site_id, 'changelog.json', $changelog);

echo json_encode([
    'ok'      => true,
    'site_id' => $site_id,
    'message' => "✅ v{$current['raw']} → v{$next['raw']} ({$type}) 릴리즈 완료!",
    'version' => $next,
    'type'    => $type,
]);
```

### 8.4 api/sites.php (신규 파일)

```php
<?php
/**
 * API: 사이트 관리
 * GET  /vermanager/api/sites           → 사이트 목록
 * POST /vermanager/api/sites           → 새 사이트 등록
 * PUT  /vermanager/api/sites/{id}      → 사이트 수정
 * DELETE /vermanager/api/sites/{id}    → 사이트 삭제
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// URL에서 사이트 ID 추출: /api/sites/kiam-prod
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
// parts = ['vermanager', 'api', 'sites', 'kiam-prod']
$target_id = $parts[3] ?? null;

switch ($method) {
    case 'GET':
        if ($target_id) {
            $site = get_site($target_id);
            if (!$site) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => '사이트를 찾을 수 없습니다']);
                exit;
            }
            $ver = site_get_current_version($target_id);
            echo json_encode(['ok' => true, 'site' => $site, 'current_version' => $ver]);
        } else {
            $sites = get_all_sites();
            echo json_encode(['ok' => true, 'sites' => $sites, 'total' => count($sites)]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['id']) || empty($input['name'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'id와 name은 필수입니다']);
            exit;
        }
        if (get_site($input['id'])) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => '이미 존재하는 사이트 ID입니다']);
            exit;
        }
        $site = array_merge([
            'aliases'         => [],
            'server_hostname' => 'unknown',
            'server_ip'       => '',
            'server_port'     => 22,
            'environment'     => 'production',
            'document_root'   => '',
            'app_name'        => $input['name'],
            'app_type'        => 'php',
            'description'     => '',
            'status'          => 'active',
            'parent_site_id'  => null,
        ], $input);

        if (add_site($site)) {
            http_response_code(201);
            echo json_encode(['ok' => true, 'site' => $site, 'message' => '사이트가 등록되었습니다']);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => '등록에 실패했습니다']);
        }
        break;

    case 'PUT':
        if (!$target_id || !get_site($target_id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => '사이트를 찾을 수 없습니다']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $registry = get_registry();
        $registry['sites'][$target_id] = array_merge(
            $registry['sites'][$target_id],
            $input,
            ['updated_at' => date('c')]
        );
        save_registry($registry);
        echo json_encode(['ok' => true, 'site' => $registry['sites'][$target_id]]);
        break;

    case 'DELETE':
        if (!$target_id || !get_site($target_id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => '사이트를 찾을 수 없습니다']);
            exit;
        }
        $registry = get_registry();
        // 삭제 대신 비활성화 (데이터 보존)
        $registry['sites'][$target_id]['status'] = 'inactive';
        $registry['sites'][$target_id]['updated_at'] = date('c');
        save_registry($registry);
        echo json_encode(['ok' => true, 'message' => '사이트가 비활성화되었습니다']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => '지원하지 않는 메서드입니다']);
}
```

---

## 9. 요약: 한눈에 비교

### 변경 전 vs 변경 후

| 항목 | 변경 전 (현재) | 변경 후 (새 설계) |
|------|---------------|-----------------|
| **관리 대상** | 앱 1개만 | 사이트 여러 개 + 서버 여러 대 |
| **버전 저장** | `data/package.json` 1개 | `data/sites/{사이트ID}/package.json` 각각 |
| **사이트 구분** | 없음 | `site-registry.json` 등록부로 관리 |
| **운영/개발 분리** | 안 됨 | `environment: production/development` 필드로 구분 |
| **새 사이트 추가** | 폴더 통째로 복사 | 대시보드에서 [+ 새 사이트 등록] 버튼 클릭 |
| **API** | 사이트 구분 없는 단일 응답 | `?site=xxx` 파라미터로 사이트별 응답 |
| **대시보드** | 사이트 1개 정보만 표시 | 모든 사이트 한눈에 비교 |
| **확장성** | ❌ 없음 | ✅ 무한 확장 가능 |

---

## 10. 마무리

이 설계대로 수정하면:

1. **오늘**: kiam.kr 과 dev.kiam.kr 두 사이트의 버전을 따로 관리할 수 있게 됨
2. **내일**: API 수정으로 사이트별 버전 조회 가능
3. **이번 주**: 대시보드에서 모든 사이트 한눈에 보기
4. **다음 주**: 다른 물리 서버에도 버전 알리미 설치해서 진짜 "회사 전체 통합 버전 관리" 완성!

가장 중요한 건 **지금 당장 할 수 있는 1단계(데이터 구조 변경)** 부터 시작하는 거야. 한 단계씩 차근차근 올라가면, 언젠가 회사 전체의 모든 소프트웨어 버전을 한 화면에서 관리할 수 있게 될 거야! 🚀