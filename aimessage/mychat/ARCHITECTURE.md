# 마이챗(MyChat) — 또 하나의 나, 진짜 나의 아바타

> "당신보다 당신을 더 잘 아는 또 하나의 당신을, 당신이 100% 통제합니다."

---

## 비전 (Vision)

마이챗은 사용자의 **모든 라이프 데이터**를 사용자 본인의 통제 아래 모아,
**나보다 나를 더 잘 기억하는 아바타**가 다음 두 가지 가치를 제공하는 시스템입니다.

1. **삶의 의사결정 동반자** — 매 결정 순간 옆에서 상의·조언
2. **선제적 기억 관리자** — 앞으로 해야 할 일·돌봐야 할 사람들을 먼저 챙김

핵심 가치 선언:

- **데이터 주권**: 저장 위치를 사용자가 결정 (서버↔온디바이스↔하이브리드)
- **BYO API Key**: AI 호출 비용은 사용자의 API 키로 직접 — 우리는 마진 없음
- **광고 절대 없음**: 학습 데이터를 마케팅·외부 모델 학습에 사용하지 않음
- **이주·삭제 자유**: 언제든 전체 내보내기·다른 기기 이주·완전 삭제 가능

---

## 시스템 구조 (Architecture)

```
┌────────────────────────────────────────────────────────────────────┐
│                        MYCHAT FRONT-END                            │
│                                                                    │
│  /aimessage/mychat/index.html             (메인 채팅 + 메뉴 허브)   │
│  /aimessage/mychat/learn-v2.html       ★ 통합 학습 (5×14)           │
│  /aimessage/mychat/avatar-studio.html     (얼굴·목소리·HeyGen)     │
│  /aimessage/mychat/storage-settings.html  ★ 저장소·BYO API키       │
│  /aimessage/mychat/phone-sync-settings.html  ★ 폰 자동 동기화 정책 │
│  /aimessage/mychat/life-inbox.html        ★ 라이프 인박스          │
│  /aimessage/mychat/decision-assistant.html ★ Predict→Decide→Reflect│
│                                              + 한 장 의사결정 카드 │
│  /aimessage/mychat/daily-sync.html        ★ 매일의 나 (Daily Self) │
│  /aimessage/mychat/anomaly-watchdog.html  ★ 위험 신호 워치독       │
│  /aimessage/mychat/usage-guard.html       ★ API 사용량 가드        │
│  /aimessage/mychat/digital-legacy.html    ★ 디지털 유산 모드       │
│  /aimessage/mychat/assets/mychat.{css,js}  공통 디자인+로직 모듈   │
│                                                                    │
└──────────────────────────────┬─────────────────────────────────────┘
                               │ HTTPS / JSON
                               ▼
┌────────────────────────────────────────────────────────────────────┐
│                        MYCHAT API LAYER                            │
│                                                                    │
│  /api/settings.php       — 사용자 설정/저장소모드/API키/플래그     │
│  /api/data.php           — 학습 데이터 CRUD (14 채널)              │
│  /api/learn_ingest.php   ★ 통합 수집 (STT/파일/웹/폰)              │
│  /api/chat.php           — 아바타 채팅 (RAG)                       │
│  /api/phone_sync.php     — 모바일 앱 → 폰 데이터 수신 (예정)       │
│  /api/avatar_studio.php  — 얼굴 AI / 음성 클론 / HeyGen            │
│                                                                    │
└──────────────────────────────┬─────────────────────────────────────┘
                               │
       ┌───────────────────────┼──────────────────────┐
       ▼                       ▼                      ▼
┌──────────────┐      ┌──────────────────┐    ┌────────────────────┐
│  데이터베이스 │      │  벡터 DB (RAG)    │    │  외부 AI 프로바이더  │
│              │      │                  │    │  (사용자 API 키)    │
│ • users      │      │ • 채널별 콘텐츠   │    │ • OpenAI            │
│ • avatars    │      │ • 메타 (scope,    │    │ • Anthropic         │
│ • mychat_pool│      │    storage_mode)  │    │ • Google Gemini     │
│ • api_keys   │      │ • 임베딩          │    │ • Custom (Ollama 등) │
│   (봉인)     │      │                  │    │                    │
└──────────────┘      └──────────────────┘    └────────────────────┘
                               ▲
                               │ optional bypass
                               │
┌────────────────────────────────────────────────────────────────────┐
│            온디바이스 모드: IndexedDB / localStorage                │
│   (서버 통과 안 함, 단일 기기 한정, 사용자 동의 시에만)             │
└────────────────────────────────────────────────────────────────────┘
                               ▲
                               │
┌────────────────────────────────────────────────────────────────────┐
│                MOBILE APP (iamapp) — 데이터 소스                   │
│                                                                    │
│  네이티브 권한:                                                    │
│   • 통화기록 (READ_CALL_LOG)                                       │
│   • 문자 (READ_SMS) / 카카오톡 알림접근                            │
│   • 이메일 (Gmail/Outlook API)                                     │
│   • 회의 녹음 / 음성메모                                           │
│   • 사진 갤러리 (캡션·OCR)                                         │
│   • 헬스 (HealthKit / GoogleFit)                                   │
│   • 캘린더 (READ_CALENDAR)                                         │
│                                                                    │
│  주기적 sync → /api/phone_sync.php (사용자 정책 기반 요약·익명화) │
└────────────────────────────────────────────────────────────────────┘
```

---

## 14개 인생 채널 (Life Channels)

마이챗의 데이터 모델은 14개 채널로 구성됩니다.

| 코드 | 이름 | 아이콘 | 주 입력 방식 | 자동수집 소스 |
|------|------|--------|-------------|---------------|
| basic       | 기본 정보   | 👤  | 텍스트, 일기 | 프로필 설정 |
| childhood   | 어린 시절   | 🧸  | 일기, 음성, 파일 | - |
| diary       | 일기·메모   | 📔  | 일기, 음성 | 매일 알림 |
| health      | 건강 기록   | 🏃  | 일기, 파일 | 폰(헬스킷) |
| phone       | 통화·문자   | 📱  | 일기, 텍스트 | 폰(통화/SMS/카톡) |
| file        | 파일 자료   | 📁  | 파일 | - |
| voice       | 음성 기록   | 🎙️  | 음성, 텍스트 | - |
| image       | 이미지      | 🖼️  | 파일, 텍스트 | 폰(사진) |
| web         | 웹·뉴스     | 🌐  | 웹 자동수집 | 크롤러 |
| meeting     | 회의·미팅   | 🎤  | 음성, 파일, 일기 | 폰(녹음/캘린더) |
| email       | 이메일      | ✉️  | 파일, 텍스트, 일기 | 이메일 API |
| fingerprint | 지문        | ☝️  | 파일, 텍스트 | 외부 분석기 |
| palmistry   | 손금·관상   | 🖐️  | 파일, 텍스트 | 외부 분석기 |
| saju        | 사주·점성   | 🔮  | 텍스트 | 외부 API |
| etc         | 기타        | 📝  | 전체 | - |

---

## 5가지 입력 방식 (Input Methods)

| 방식 | 설명 | 처리 파이프 |
|------|------|------------|
| **일기/자유** (diary) | 자유로운 텍스트 입력. 말투·가치관 학습 | 텍스트 → 청크 → 임베딩 |
| **텍스트** (text) | 구조화 텍스트(자기소개/경력/가치관) | 카테고리화 → 임베딩 |
| **음성** (voice) | 녹음 → STT → 텍스트 | MediaRecorder → Whisper → 임베딩 |
| **파일** (file) | PDF/DOC/이미지 업로드 | Tika/OCR → 텍스트 → 임베딩 |
| **웹 자동수집** (web) | URL/키워드 등록 → 자동 크롤 | 크롤러 → 요약 → 임베딩 |

채널 × 입력방식 매트릭스(`METHOD_MATRIX`)로 어떤 채널이 어떤 방식을 지원하는지 정의.

---

## 공개범위 (Scope) — 3택

| 코드 | 라벨 | 의미 |
|------|------|------|
| `private` | 🔒 나만 | 본인 + 본인의 아바타만 접근 |
| `public`  | 🌐 외부도 | 외부 사용자가 이 아바타와 대화할 때 사용 가능 |
| `both`    | ⚖️ 둘 다 | 사적 대화 + 외부 대화 모두 사용 |

**적용 단위**:
- **채널 기본값** (채널 카드 우측 상단 미니 토글) — 새 데이터의 디폴트
- **개별 데이터** (학습 페이지 입력 폼 인라인 토글) — 데이터별 명시 지정
- **사후 변경** (히스토리 아이템에서 직접 수정)

UX 원칙: **별도 "공개범위 관리" 메뉴 없음.** 모든 scope 조작은 데이터를 보는 그 자리에서.

---

## 저장소 모드 (Storage Mode)

| 모드 | 설명 | 적용 시점 |
|------|------|----------|
| `server` (기본) | 마이챗 서버 암호화 저장. 다중 기기 동기화, 자동 백업 | 기본값 — 분실 우려 시 권장 |
| `device` | 이 기기 IndexedDB/localStorage에만 저장. 서버는 모름 | 최고 프라이버시 원할 때 |
| `hybrid` | 채널별 따로 — 민감 채널은 device, 공개용은 server | 균형 추구 |

`E2EE` 토글 — 서버 저장 모드라도 클라이언트에서 암호화 후 업로드 (서버는 내용 못 봄).

---

## BYO API Key (Bring-Your-Own)

지원 프로바이더:
- **OpenAI** (gpt-4o-mini / gpt-4o / o1-mini, text-embedding-3-small/large)
- **Anthropic** (Claude 3.5 Sonnet / Haiku)
- **Google** (Gemini 1.5 Pro / Flash)
- **Custom** (OpenAI 호환 base_url — Ollama, vLLM, Together, Groq 등)

키 저장:
- 서버: AES-256-GCM 봉인 (사용자별 키)
- 응답시 항상 마스킹 (`sk-***...abcd`)
- 키 자체는 응답하지 않음, `key_present:true` + `key_mask` 만 회신

---

## 파일 구조 (현재 작업분)

```
aimessage/mychat/
├── ARCHITECTURE.md           ← 이 문서
├── index.html                메인 채팅 (메뉴 정리 완료)
├── learn.html                레거시 학습 페이지 (인라인 scope 토글 추가)
├── learn-v2.html             ★ 신규 통합 학습 페이지 (5×14)
├── storage-settings.html     ★ 신규 저장소·API키 설정
├── avatar-studio.html        아바타 스튜디오 (P0 SyntaxError 수정 완료)
└── api/
    └── learn_ingest.php      ★ 신규 통합 수집 엔드포인트 (스텁)
```

---

## 향후 작업 (Roadmap)

### Phase 1 — 토대 (완료된 부분)
- [x] avatar-studio 페이지 부활 (P0 SyntaxError 수정)
- [x] '공개범위 관리' 메뉴 제거, 인라인 scope 토글로 통합
- [x] 통합 학습 페이지 v2 (learn-v2.html)
- [x] 저장소 모드 + BYO API Key 설정 (storage-settings.html)
- [x] learn_ingest.php 컨트랙트 정의

### Phase 2 — 데이터 인입 (다음 단계)
- [ ] 모바일 앱 → /api/phone_sync.php (PUSH)
- [ ] STT 백엔드 (Whisper / ElevenLabs Scribe)
- [ ] 파일 파싱 (Tika / pdf-parse / Tesseract OCR)
- [ ] 웹 크롤러 큐 (cron + 자동 임베딩)
- [ ] phone-sync-settings.html (자동수집 정책 UI)

### Phase 3 — 라이프 인박스
- [ ] 통화/문자/카톡/이메일/회의/사진/헬스 통합 수신함
- [ ] 자동 분류·요약 (사용자 API 키)
- [ ] 채널별 필터 + 검색

### Phase 4 — 의사결정 어시스턴트
- [ ] Predict → Decide → Reflect 루프
- [ ] 선제적 알림 (앞으로 챙겨야 할 것)
- [ ] 의사결정 추적 (decisions.html)

### Phase 5 — 데이터 주권 도구
- [ ] 전체 데이터 내보내기 (암호화 zip)
- [ ] QR 페어링 기기 이주
- [ ] 영구 삭제 + 증명서 발급

---

생성: 2026-06-04 / 조은 × 아리(GenSpark)
