# OneChat 챗봇설정 LLM 기본지식 활용 — 복구 보고서

## 일자
2026-06-09

## 증상
- 운영(onechat.kiam.kr) 챗봇설정 → "LLM 기본지식 활용"의 보완/균형/자유 3개 항목 내용이 모두 사라짐.

## 원인
- 2026-06-06 17:10:33 배포(롤백)가 운영본 38개 파일을 구버전으로 덮어씀.
  - chatbot_setting.php, ai_reply.php 등에서 llm 처리 코드 소실.
- 동시에 DB의 llm 컬럼(5개)도 사라짐(구덤프 복원 추정).

## 복구 조치
### 1. DB 데이터
- Gn_aievent_ms_info에 llm 컬럼 5개 재생성 + sms_idx=3371 데이터 복원
  - binlog.000343(2026-06-05 06:20 스냅샷)에서 추출한 최신본
  - 보완 2204자 / 균형 2211자 / 자유 2962자 / mode=balance
- 복원 코드가 1순위로 읽는 Gn_chatbot_settings에도 동일 최신본 동기화 완료
  - (구버전 2066/2210/2961 → 최신본 2204/2211/2962)

### 2. 코드 파일 (운영 /home/kiam/aimessage/onechat)
- 2026-05-28 정상본 백업(onechat_backup_20260528_032342, 기능상 ~5/19 최신)에서
  회귀 32개 파일 복원. 핵심: api/chatbot_setting.php, api/ai_reply.php
- 현재 운영본이 더 새 것인 6개 파일은 복원에서 제외(보류):
  profile_photo.php, _test2.php, received_list.php, create_chatbot.php,
  _test_dash.php, session_check.php

### 3. 백업
- 복원 전 운영본 전체 백업: /home/kiam/aimessage/_RESTORE_BACKUP_20260609_145328/
  + onechat_api_js_full_20260609_145328.tar.gz
- Gn_chatbot_settings 3371행 동기화 전 백업:
  _RESTORE_BACKUP_20260609_145328/Gn_chatbot_settings_3371_before_sync.sql

## 검증 결과
- PHP 문법검사(php -l): 복원 PHP 전부 정상(오류 0)
- chatbot_setting.php llm_prompt 처리: 13라인 복원(이전 0)
- ai_reply.php: build_llm_knowledge_instruction(), match($llm_mode) 복원
- DB: Gn_chatbot_settings / Gn_aievent_ms_info 모두 2204/2211/2962 일치
- 운영 도메인 HTTP 200 정상

## 사용자 테스트 안내
1. https://onechat.kiam.kr/aimessage/onechat/#chat 접속
2. 챗봇설정 → "LLM 기본지식 활용" 열기
3. 보완/균형/자유 3개 항목에 내용이 보이는지 확인 (현재 모드: 균형)
4. 한 항목 수정 후 저장 → 새로고침 → 저장 유지되는지 확인
