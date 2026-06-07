# git clean -fd / 리버전 피해 — 전체 복구 대상 리스트
> 조사일: 2026-06-08 · 기준: 운영서버 kiam.kr (LIVE) vs 로컬 작업트리/git
> 복구 방법: 아래 "운영경로/백업경로"에서 파일을 가져와 "로컬 복원경로"에 덮어쓰기

## ⚠️ 핵심 결론
`git clean -fd` 는 **추적되지 않은(untracked) 파일을 영구 삭제**하므로 git 에는 기록이 남지 않는다.
또한 `git reset` + 운영본 재동기화 과정에서 **로컬이 과거 버전으로 되돌아간(reverted)** 파일이 다수 발견됨.
**다행히 운영서버(kiam.kr)에는 최신본이 살아있어, 운영본 또는 일별 백업에서 복구 가능.**

- git 에서 직접 복구 가능한 삭제 파일: **0개** (tracked 파일은 모두 정상)
- 운영에는 있으나 로컬에서 사라지거나 구버전으로 되돌아간 항목: **아래 전부**

---

## [그룹 1] 🔴 예약관리 시스템 — 백엔드 API 8개 (로컬 완전 소실)
운영 main.js 가 호출하지만 로컬 `aimessage/onechat/api/` 에 **전혀 없음**. 운영은 HTTP 401(=존재).

| # | 파일 | 운영경로 | 로컬 복원경로 |
|---|------|----------|---------------|
| 1 | reserve_apply.php | /aimessage/onechat/api/reserve_apply.php | aimessage/onechat/api/ |
| 2 | reserve_blackout.php | /aimessage/onechat/api/reserve_blackout.php | aimessage/onechat/api/ |
| 3 | reserve_booking.php | /aimessage/onechat/api/reserve_booking.php | aimessage/onechat/api/ |
| 4 | reserve_config.php | /aimessage/onechat/api/reserve_config.php | aimessage/onechat/api/ |
| 5 | reserve_schedule.php | /aimessage/onechat/api/reserve_schedule.php | aimessage/onechat/api/ |
| 6 | reserve_slot.php | /aimessage/onechat/api/reserve_slot.php | aimessage/onechat/api/ |
| 7 | reserve_trigger.php | /aimessage/onechat/api/reserve_trigger.php | aimessage/onechat/api/ |
| 8 | reserve_trigger_ai.php | /aimessage/onechat/api/reserve_trigger_ai.php | aimessage/onechat/api/ |

> ※ 로컬의 `reserve_setting.php` 는 **구버전**이며 운영에서는 404(이미 제거됨) → 위 8개로 대체된 것.

## [그룹 2] 🔴 예약관리 시스템 — DB 스키마 SQL (로컬 소실)
| 파일 | 운영경로(403=존재) | 로컬 복원경로 |
|------|--------------------|---------------|
| reserve.sql | /aimessage/onechat/sql/reserve.sql | aimessage/onechat/sql/ |
| reserve_schema.sql | /aimessage/onechat/sql/reserve_schema.sql | aimessage/onechat/sql/ |

## [그룹 3] 🔴 원챗 앱 프론트엔드 — 과거버전으로 되돌아감(reverted)
운영본이 로컬보다 훨씬 크고 최신. **운영본으로 통째 교체 필요**.

| 파일 | 운영 크기 | 로컬 크기 | 차이 | 비고 |
|------|-----------|-----------|------|------|
| aimessage/onechat/js/main.js | 315,785 | 243,704 | **+72,081** | 예약 캘린더 함수 23개 누락 |
| aimessage/onechat/index.html | 128,307 | 112,523 | **+15,784** | 예약 UI 일부 누락 |
| aimessage/onechat/js/ocw.js | 16,587 | 7,477 | **+9,110** | |
| aimessage/onechat/css/style.css | 182,765 | 182,765 | 0 | (동일 — 복구 불필요) |

**로컬 main.js 에서 사라진 예약 함수 23개**(운영에만 존재):
`rsCalMonthView, rsCalWeekView, rsCalDayView, rsCalYearView, rsCalToolbar, shiftCalPeriod,
rsOpenNewBk, rsCloseBkEdit, rsOpenBkEdit, rsOpenNewSchedule, rsOpenScEdit, rsCloseScEdit,
rsCloseNewBk, rsApplyToBot, rsUpdateApplyStatus, rsDndMove, rsShowDayPopup, rsHideDayPopup,
rsDateStr, openBoForm, closeBoForm, submitBoForm, doSave`

## [그룹 4] 🔴 매뉴얼 — 18개 섹션 전체 소실 (manual/)
로컬 `manual/` 에는 6개 섹션만 존재(callback, diver, papercard, sharecallback, source-improvements, vermanager).
운영에는 아래 **18개 섹션이 추가로 존재**하나 로컬에 전혀 없음 → 통째 복구 필요.

소실 섹션: `account, aiip, aimessage, ainote, avatar, dashboard, funnel, mandata, namecard,
onechat, sms, support, vag, voicegen, mychat, aimatch, qrscan, aicodinghub`

- 섹션 인덱스 페이지: **18개** (`/manual/<섹션>/index.html`)
- 하위 상세 페이지: **106개** (목록은 `docs/recovery_manual_pages.txt` 참조)
- 추가: `/manual/onechat/reserve.html` (예약관리 매뉴얼 — 운영엔 있으나 목차 링크 끊김)
- **매뉴얼 합계 약 125개 파일** (운영 `/manual/<섹션>/` 디렉터리 통째 복구가 가장 확실)

> 복구 권장: 운영서버 `/manual/` 디렉터리 전체를 일별 백업에서 가져와 로컬 `manual/` 에 병합.

## [그룹 5] 🟡 마이챗(mychat) — 동기화 불일치 (검토 필요)
| 파일 | 운영(remote_fetch 기준) | 로컬 | 비고 |
|------|------------------------|------|------|
| aimessage/mychat/index.html | 28,487 | 34,990 | 로컬이 더 큼 — 방향 확인 필요 |
| mychat avatar_studio/learn 등 | 운영 최신본 존재 | 로컬 구버전 | 개별 확인 권장 |

---

## ✅ 복구 절차 권장안
1. **일별 백업에서 아래 디렉터리를 통째로 복원**하는 것이 가장 안전:
   - `aimessage/onechat/api/` (reserve_*.php 8개)
   - `aimessage/onechat/sql/` (reserve*.sql)
   - `aimessage/onechat/js/main.js`, `index.html`, `js/ocw.js`
   - `manual/` 전체 (18개 섹션)
2. 복원 후 git 에 커밋하여 **다시는 사라지지 않도록 추적 시작** (현재 4중 방어막이 untracked 삭제는 막지만, 추적 등록이 근본 대책).
3. `manual/onechat/index.html` 목차에 `reserve.html` 링크 추가(끊긴 링크 복구).

## 참고 — 운영서버 직접 확인 명령
```bash
# 공개 정적파일은 직접 다운로드 가능 (예: main.js)
curl -s https://kiam.kr/aimessage/onechat/js/main.js -o main.js
# 매뉴얼 페이지도 공개 (인증 불필요)
curl -s https://kiam.kr/manual/onechat/reserve.html -o reserve.html
# PHP/SQL 은 401/403 → 일별 백업에서 복원해야 함
```
