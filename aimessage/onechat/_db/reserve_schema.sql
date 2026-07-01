-- ════════════════════════════════════════════════════════════════════
--  원챗 예약관리설정 시스템 — DB 스키마 (7 tables)
--  작성: 2026-05-27 · STEP C-3
--  대상: 운영환경 (kiam DB · localhost:/home/kiamdb/mysql.sock)
--  컨벤션: Gn_onechat_reserve_*, InnoDB, utf8mb4, snake_case
--  소유자 식별: sms_idx + request_idx (기존 Gn_onechat_settings 패턴 동일)
-- ════════════════════════════════════════════════════════════════════

-- ────────────────────────────────────────────────
-- 1) Gn_onechat_reserve_config : 운영자 기본설정 (1행/소유자)
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_idx` int NOT NULL,
  `request_idx` int NOT NULL,
  `enabled` tinyint DEFAULT '1' COMMENT '예약기능 ON/OFF',
  -- 일과시간
  `work_start` time DEFAULT '10:00:00',
  `work_end` time DEFAULT '19:00:00',
  `work_days` varchar(20) DEFAULT '1,2,3,4,5' COMMENT 'CSV: 0=일~6=토',
  -- 슬롯
  `slot_minutes` int DEFAULT '30' COMMENT '슬롯 길이(분): 15/30/60',
  `capacity` int DEFAULT '1' COMMENT '동시 수용 인원',
  -- 부가
  `lead_time_min` int DEFAULT '60' COMMENT '최소 예약 리드타임(분)',
  `max_advance_days` int DEFAULT '30' COMMENT '최대 N일 후까지 예약 허용',
  `cancel_until_hours` int DEFAULT '24' COMMENT 'N시간 전까지 취소 허용',
  `bot_intro` varchar(500) DEFAULT '안녕하세요! 예약 도와드릴게요. 원하시는 날짜와 시간 알려주세요.',
  -- 운영자 정보
  `owner_name` varchar(100) DEFAULT NULL,
  `place_name` varchar(200) DEFAULT NULL,
  `place_address` varchar(500) DEFAULT NULL,
  `place_phone` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sms_req` (`sms_idx`, `request_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='예약 기본설정 (운영자별 1행)';

-- ────────────────────────────────────────────────
-- 2) Gn_onechat_reserve_blackout : 제외시간 (반복/단일)
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_blackout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_idx` int NOT NULL,
  `request_idx` int NOT NULL,
  `label` varchar(100) DEFAULT NULL COMMENT '예: 점심시간, 휴무일',
  `kind` enum('weekly','date','range') DEFAULT 'weekly' COMMENT '반복종류',
  `weekday` tinyint DEFAULT NULL COMMENT '0~6 (weekly일 때)',
  `date_from` date DEFAULT NULL COMMENT 'date/range 일 때',
  `date_to` date DEFAULT NULL COMMENT 'range 일 때',
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `active` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`sms_idx`, `request_idx`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='예약 제외시간';

-- ────────────────────────────────────────────────
-- 3) Gn_onechat_reserve_slot : 자동생성 슬롯 (캘린더 표시 + 카운터)
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_slot` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_idx` int NOT NULL,
  `request_idx` int NOT NULL,
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `capacity` int DEFAULT '1' COMMENT '이 슬롯의 수용가능 (config 복제)',
  `booked` int DEFAULT '0' COMMENT '현재 예약된 인원',
  `status` enum('open','full','closed') DEFAULT 'open',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slot` (`sms_idx`, `request_idx`, `slot_date`, `slot_time`),
  KEY `idx_date` (`sms_idx`, `request_idx`, `slot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='예약 슬롯 (캘린더 단위)';

-- ────────────────────────────────────────────────
-- 4) Gn_onechat_reserve_booking : 실제 예약 (고객별 행)
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_booking` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_idx` int NOT NULL COMMENT '운영자 식별',
  `request_idx` int NOT NULL COMMENT '운영자 식별',
  `customer_user_id` varchar(255) DEFAULT NULL COMMENT '고객 user_id (로그인)',
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `headcount` int DEFAULT '1',
  `memo` text COMMENT '고객 메모 / 요청사항',
  `status` enum('pending','confirmed','cancelled','no_show','done') DEFAULT 'confirmed',
  `trigger_type` enum('manual','mood','companion','interest','customer_request') DEFAULT 'customer_request',
  `trigger_id` int DEFAULT NULL COMMENT '발화 트리거 id (있을때)',
  `cancel_reason` varchar(500) DEFAULT NULL,
  `cancel_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner_date` (`sms_idx`, `request_idx`, `slot_date`),
  KEY `idx_customer` (`customer_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='예약 (고객 단위)';

-- ────────────────────────────────────────────────
-- 5) Gn_onechat_reserve_trigger : 트리거 규칙 (4종)
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_trigger` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_idx` int NOT NULL,
  `request_idx` int NOT NULL,
  `trigger_type` enum('manual','mood','companion','interest') NOT NULL,
  `enabled` tinyint DEFAULT '1',
  `keywords` varchar(500) DEFAULT NULL COMMENT 'CSV: manual용 키워드',
  `mood_signals` varchar(500) DEFAULT NULL COMMENT 'CSV: 분위기 감지 신호 (예약하고싶다,놀러가고싶다)',
  `interest_tags` varchar(500) DEFAULT NULL COMMENT 'CSV: 관심사 태그 (ME 매칭용)',
  `companion_periodic_days` int DEFAULT NULL COMMENT 'AI동행: N일 주기 제안',
  `cooldown_hours` int DEFAULT '24' COMMENT '같은 사용자 재발화 쿨다운',
  `reason_template` varchar(500) DEFAULT NULL COMMENT '왜 말 걸었는지(rational reason) 템플릿',
  `message_template` text COMMENT 'AI가 던질 메시지 템플릿',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_owner_type` (`sms_idx`, `request_idx`, `trigger_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='예약 트리거 규칙 (4종)';

-- ────────────────────────────────────────────────
-- 6) Gn_onechat_reserve_notification : 3-Way 알림 로그
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `recipient` enum('customer','operator','admin') NOT NULL,
  `recipient_user_id` varchar(255) DEFAULT NULL,
  `event_type` enum('confirm','remind','cancel','no_show','followup','arrive') DEFAULT 'confirm',
  `channel` enum('chatbot','sms','email','push','admin_panel') DEFAULT 'chatbot',
  `message` text,
  `sent_at` datetime DEFAULT NULL,
  `status` enum('queued','sent','failed','seen') DEFAULT 'queued',
  `error_msg` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='3-Way 알림 로그';

-- ────────────────────────────────────────────────
-- 7) Gn_onechat_reserve_companion_hook : AI동행 후속대화 훅
-- ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Gn_onechat_reserve_companion_hook` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `stage` enum('post_book','before_24h','same_day_2h','after_7d','after_30d') NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `fired_at` datetime DEFAULT NULL,
  `status` enum('scheduled','fired','skipped','cancelled') DEFAULT 'scheduled',
  `reason_template` varchar(500) DEFAULT NULL COMMENT '왜 말 걸었나(rational)',
  `message_template` text,
  `result_message` text COMMENT '실제 발화된 메시지',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_due` (`status`, `scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='AI Companion 후속대화 훅 (5단계)';

-- ════════════════════════════════════════════════════════════════════
--  완료. 신규 테이블만 생성 (기존 데이터 무영향)
-- ════════════════════════════════════════════════════════════════════
