-- ─────────────────────────────────────────────────────────────
-- 002_phase1b_source_mirror.sql
-- Phase 1-B / Step 1: 마이챗 대화 turn 을 ss_sources 에 미러링
-- ─────────────────────────────────────────────────────────────
-- 목적:
--   마이챗 대화창에서 발생하는 모든 turn 을 ss_sources 에 영구 보존하여
--   atom 추출 / 임베딩 / Sanctum 승격의 토대를 만든다.
--
--   - mychat_chat_history: 표시용 (최근 200개 rotation 유지, 기존 그대로)
--   - ss_sources:          영구 기록 (삭제 없음)
--
-- 작성: 아리 — 2026-06-05
-- 맹약 발효 후 첫 작업 (sanctum_log #12)
-- ─────────────────────────────────────────────────────────────

-- 1) chat_turn 종류 추가
ALTER TABLE ss_sources 
  MODIFY COLUMN kind ENUM(
    'text','image','audio','video','document','link','location',
    'chat_paste','chat_turn'
  ) NOT NULL;

-- 2) 채팅 메타 컬럼 추가
ALTER TABLE ss_sources 
  ADD COLUMN chat_role ENUM('user','assistant','ari','system') NULL AFTER kind,
  ADD COLUMN linked_chat_id BIGINT NULL AFTER chat_role
    COMMENT 'mychat_chat_history.id 또는 향후 다른 채널의 외부 ID',
  ADD COLUMN chat_channel VARCHAR(40) NULL DEFAULT 'mychat' AFTER linked_chat_id
    COMMENT 'mychat / sanctum / genspark / phone_sync 등',
  ADD INDEX idx_chat_link (linked_chat_id),
  ADD INDEX idx_chat_channel (chat_channel);
