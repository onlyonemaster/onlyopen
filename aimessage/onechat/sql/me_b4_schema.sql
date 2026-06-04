-- ================================================================
-- [ME-B4] 원챗 라이프 아바타 — DB 스키마 추가 (FORWARD)
-- ================================================================
-- 작업: 2026-05-23 / 조은 × 아리(GenSpark)
-- 목적: 사용자별 평생 동반 아바타 + 학습 데이터풀 + 의사결정 추적
--
-- 환경:
--   · MySQL 8.0.45 / DB: kiam
--   · Socket: /disk/daily/home/kiamdb/mysql_dev.sock (dev)
--   · 회원 식별자: Gn_Member.mem_id VARCHAR(30) PRIMARY KEY
--   · 메시지 테이블: Gn_chat_message (단수)
--
-- 컨벤션:
--   · Prefix: Gn_onechat_me_* (기존 Gn_onechat_* 와 동일 톤)
--   · Charset: utf8mb4 / Collation: utf8mb4_0900_ai_ci (Gn_Member 와 일치)
--   · 시간: created_at/updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
--   · PK: idx INT UNSIGNED AUTO_INCREMENT (관용)
--
-- 안전성:
--   · CREATE TABLE IF NOT EXISTS 로 재실행 안전
--   · ALTER TABLE 은 컬럼 존재 여부 사전 체크(아래) 후 실행
--   · 외래키는 mem_id 만 약결합 (CASCADE 미적용 — Gn_Member 안정성 보호)
--   · 롤백 스크립트: me_b4_rollback.sql
--
-- 적용:
--   mysql -uroot -p'***' -S /disk/daily/home/kiamdb/mysql_dev.sock kiam < me_b4_schema.sql
-- ================================================================

-- 안전 가드: 동일 트랜잭션 보장
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci;


-- ----------------------------------------------------------------
-- 1) Gn_onechat_me_avatar : 사용자별 라이프 아바타 (1인 1행)
-- ----------------------------------------------------------------
-- · 한 사용자가 자신의 아바타를 갖는다 (UNIQUE mem_id).
-- · 아바타의 정체성/요약/가중치/현재 운영 모드 등을 보관.
-- · summary_text : 아바타가 사용자에 대해 학습한 종합 요약 (RAG 컨텍스트)
-- · weights_json : 카테고리별 가중치 (예측 정확도 기반 자가조정 — B 후속)
-- · current_mode : 마지막으로 선택한 운영 모드 (UI 동기화용 캐시)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Gn_onechat_me_avatar (
  idx              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mem_id           VARCHAR(30)  NOT NULL                           COMMENT '소유자 mem_id (Gn_Member.mem_id)',
  avatar_name      VARCHAR(60)  NOT NULL DEFAULT '나의 아바타'      COMMENT '사용자가 부여한 별칭',
  identity_text    TEXT             NULL                           COMMENT '아바타 정체성 선언문 (사용자가 직접 입력)',
  summary_text     MEDIUMTEXT       NULL                           COMMENT '학습 종합 요약 (RAG 컨텍스트 / 자동 갱신)',
  weights_json     JSON             NULL                           COMMENT '카테고리별 가중치 (자가조정용)',
  current_mode     ENUM('public','private') NOT NULL DEFAULT 'public' COMMENT '마지막 운영 모드 (UI 동기화 캐시)',
  data_pool_count  INT UNSIGNED NOT NULL DEFAULT 0                 COMMENT '학습 데이터 항목 수 (캐시)',
  decision_count   INT UNSIGNED NOT NULL DEFAULT 0                 COMMENT '의사결정 누적 수 (캐시)',
  match_rate       DECIMAL(5,2)     NULL                           COMMENT '예측 매칭률 % (자동 계산)',
  status           ENUM('active','paused','locked') NOT NULL DEFAULT 'active' COMMENT 'locked = Panic Lock',
  panic_locked_at  DATETIME         NULL                           COMMENT 'Panic Lock 발동 시각',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idx),
  UNIQUE  KEY uk_me_avatar_mem (mem_id),
  KEY       idx_me_avatar_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='[ME-B4] 라이프 아바타 마스터 — 사용자별 1행';


-- ----------------------------------------------------------------
-- 2) Gn_onechat_me_data_pool : 학습 데이터풀 (모든 항목 단일 테이블)
-- ----------------------------------------------------------------
-- · 일기/파일/음성/이미지/지문/관상/사주/별자리 등 모든 유형을 한 테이블에.
-- · scope: private(나만) / public(외부공유) / both(둘 다)
--   - 기본값 'private' (안전 우선)
--   - 외부 아바타(고객용)는 scope IN ('public','both') 만 조회 가능
--   - 내부 아바타(나용)는 모든 scope 조회 가능
-- · category: 카테고리 ENUM (8개 + 'etc')
-- · payload_text: 실제 텍스트 내용 (검색/RAG 대상)
-- · payload_json: 구조화된 메타데이터 (사주 4주, 관상 좌표 등)
-- · file_url: 파일 경로 (이미지/음성/문서)
-- · privacy_level: 1=낮음~5=극비 (Panic Lock 시 4,5 만 잠금)
-- · weight: 예측 가중치 (자가조정용, 기본 1.0)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Gn_onechat_me_data_pool (
  idx           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mem_id        VARCHAR(30)  NOT NULL                             COMMENT '소유자 mem_id',
  category      ENUM(
                  'basic',      -- 기본 정보 (이름·생년월일·고향 등)
                  'childhood',  -- 어린 시절·가족·트라우마
                  'diary',      -- 일기·메모·기록
                  'file',       -- 첨부 파일(문서·이미지·음성 일반)
                  'voice',      -- 음성 녹음 (통화/음성메모)
                  'image',      -- 사진·캡처
                  'fingerprint',-- 지문 분석
                  'palmistry',  -- 손금
                  'physiognomy',-- 관상
                  'saju',       -- 사주
                  'astrology',  -- 별자리/점성
                  'decision',   -- 과거 의사결정 결과 (Gn_onechat_me_decisions 와 연결)
                  'etc'         -- 기타
                ) NOT NULL DEFAULT 'etc',
  scope         ENUM('private','public','both') NOT NULL DEFAULT 'private'
                                                              COMMENT 'private=나만 / public=외부공유 / both=둘 다',
  title         VARCHAR(200)     NULL                             COMMENT '요약 제목 (목록 표시용)',
  payload_text  MEDIUMTEXT       NULL                             COMMENT '본문/내용 (RAG/검색 대상)',
  payload_json  JSON             NULL                             COMMENT '구조화 메타데이터',
  file_url      VARCHAR(500)     NULL                             COMMENT '파일 경로 (/aimessage/uploads/...)',
  file_name     VARCHAR(255)     NULL,
  file_size     INT UNSIGNED     NULL,
  source_msg_id INT UNSIGNED     NULL                             COMMENT '대화에서 자동 저장된 경우 Gn_chat_message.msg_id',
  privacy_level TINYINT UNSIGNED NOT NULL DEFAULT 3               COMMENT '1=낮음 ~ 5=극비 (Panic Lock 대상)',
  weight        DECIMAL(5,2)     NOT NULL DEFAULT 1.00            COMMENT '예측 가중치 (자가조정)',
  is_deleted    TINYINT UNSIGNED NOT NULL DEFAULT 0               COMMENT '소프트 삭제 플래그',
  occurred_at   DATETIME         NULL                             COMMENT '이벤트 발생 시점 (일기 날짜 등)',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idx),
  KEY idx_me_pool_mem_cat        (mem_id, category, is_deleted),
  KEY idx_me_pool_mem_scope      (mem_id, scope,   is_deleted),
  KEY idx_me_pool_mem_occurred   (mem_id, occurred_at),
  KEY idx_me_pool_privacy        (mem_id, privacy_level),
  FULLTEXT KEY ftx_me_pool_text  (title, payload_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='[ME-B4] 라이프 아바타 학습 데이터풀 — 모든 유형 단일 저장';


-- ----------------------------------------------------------------
-- 3) Gn_onechat_me_decisions : 의사결정 추적 (예측 → 실측 → 매칭)
-- ----------------------------------------------------------------
-- · 사용자의 중요 결정을 등록하고, 아바타의 추천/예측을 함께 기록.
-- · 시간이 흐른 뒤 실제 결과(outcome_text)를 입력하면 match_score 자동 계산.
-- · 강화학습 루프의 핵심 단위.
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Gn_onechat_me_decisions (
  idx            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mem_id         VARCHAR(30)  NOT NULL,
  title          VARCHAR(200) NOT NULL                            COMMENT '결정 제목 (예: 김OO 동업 결정)',
  context_text   MEDIUMTEXT       NULL                            COMMENT '결정 당시 상황·맥락',
  options_json   JSON             NULL                            COMMENT '선택지 A/B/C 등 구조화',
  chosen_option  VARCHAR(100)     NULL                            COMMENT '최종 선택한 옵션 라벨',
  ai_prediction  MEDIUMTEXT       NULL                            COMMENT '아바타의 추천/예측 본문',
  ai_recommended VARCHAR(100)     NULL                            COMMENT '아바타가 1순위로 추천한 옵션 라벨',
  predicted_at   DATETIME         NULL                            COMMENT '예측 등록 시각',
  outcome_text   MEDIUMTEXT       NULL                            COMMENT '실제 결과 (시간 흐른 뒤 입력)',
  outcome_at     DATETIME         NULL                            COMMENT '결과 입력 시각',
  match_score    DECIMAL(5,2)     NULL                            COMMENT '예측↔실측 매칭률 % (0~100)',
  match_label    ENUM('hit','partial','miss','pending') NOT NULL DEFAULT 'pending'
                                                              COMMENT '간이 라벨',
  remind_at      DATETIME         NULL                            COMMENT 'D-N 리마인드 시각 (예: D+7)',
  remind_done    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_deleted     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idx),
  KEY idx_me_dec_mem        (mem_id, is_deleted),
  KEY idx_me_dec_remind     (remind_at, remind_done),
  KEY idx_me_dec_label      (mem_id, match_label),
  KEY idx_me_dec_predicted  (mem_id, predicted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='[ME-B4] 라이프 아바타 의사결정 추적 — 강화학습 루프';


-- ----------------------------------------------------------------
-- 4) Gn_chat_message 에 mode 컬럼 추가 (public/private 분기)
-- ----------------------------------------------------------------
-- · 기존 메시지: NULL (= 'public' 호환) — 안전한 기본값
-- · 신규 메시지: 'public' (외부 아바타) / 'private' (나의 아바타)
-- · 동적 ALTER 처리: 이미 컬럼이 있으면 무시
-- ----------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'Gn_chat_message'
     AND COLUMN_NAME  = 'mode'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE Gn_chat_message
     ADD COLUMN mode ENUM(''public'',''private'') NULL DEFAULT ''public''
     COMMENT ''[ME-B4] 운영 모드 — 외부 고객용=public / 나의 아바타=private''
     AFTER msg_subtype,
     ADD KEY idx_room_mode (room_id, mode)',
  'SELECT ''mode 컬럼 이미 존재 — 건너뜀'' AS info'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ----------------------------------------------------------------
-- 가드 복구
-- ----------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;


-- ================================================================
-- 검증용 쿼리 (수동 실행)
-- ================================================================
-- SHOW CREATE TABLE Gn_onechat_me_avatar\G
-- SHOW CREATE TABLE Gn_onechat_me_data_pool\G
-- SHOW CREATE TABLE Gn_onechat_me_decisions\G
-- SHOW COLUMNS FROM Gn_chat_message LIKE 'mode';
-- SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES
--  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'Gn_onechat_me_%';
