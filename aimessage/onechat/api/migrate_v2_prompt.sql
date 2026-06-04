-- ============================================================
-- 원챗(OneChat) 4-Layer Prompt Architecture DB 마이그레이션
-- 대상: Gn_aievent_ms_info 테이블 확장
-- 설계: Multi-Layer Prompt (L1 Persona / L2 Policy / L3 Interaction / L4 Safety)
-- 버전: v2.0 | 일자: 2026-05-08
-- ============================================================

-- ── Step 1: 4-Layer 프롬프트 컬럼 추가 ──
ALTER TABLE `Gn_aievent_ms_info`
    ADD COLUMN IF NOT EXISTS `prompt_l1_persona`       MEDIUMTEXT NULL COMMENT 'L1: 핵심 페르소나 (정체성·가치관·경계설정)',
    ADD COLUMN IF NOT EXISTS `prompt_l2_policy`         MEDIUMTEXT NULL COMMENT 'L2: 정책 도메인 (공약·지역현안·FAQ)',
    ADD COLUMN IF NOT EXISTS `prompt_l3_interaction`    MEDIUMTEXT NULL COMMENT 'L3: 상호작용 스타일 (말투·대화전략·종료처리)',
    ADD COLUMN IF NOT EXISTS `prompt_l4_safety`         MEDIUMTEXT NULL COMMENT 'L4: 안전·준수 가드레일 (선거법·차별금지·비상대응)';

-- ── Step 2: Layer 활성화 플래그 (관리자가 각 Layer ON/OFF 가능) ──
ALTER TABLE `Gn_aievent_ms_info`
    ADD COLUMN IF NOT EXISTS `prompt_layer_active`      VARCHAR(20)  NULL DEFAULT '1,1,1,1' COMMENT 'Layer 활성화 플래그 (L1,L2,L3,L4 순서 1=on 0=off)',
    ADD COLUMN IF NOT EXISTS `prompt_version`           INT          NULL DEFAULT 1     COMMENT '프롬프트 구성 버전 (변경 시 증가)',
    ADD COLUMN IF NOT EXISTS `prompt_updated_at`        DATETIME     NULL DEFAULT NULL  COMMENT '프롬프트 최종 수정 시각';

-- ── Step 3: 아바타 학습 ↔ 프롬프트 연동 (Context Bridge) 메타데이터 ──
ALTER TABLE `Gn_aievent_ms_info`
    ADD COLUMN IF NOT EXISTS `context_bridge_enabled`   TINYINT(1)   NULL DEFAULT 1     COMMENT 'Context Bridge 활성화 여부',
    ADD COLUMN IF NOT EXISTS `context_bridge_config`    JSON         NULL DEFAULT NULL  COMMENT 'Context Bridge 설정 (연동항목·갱신주기 등)',
    ADD COLUMN IF NOT EXISTS `rag_provider`             VARCHAR(50)  NULL DEFAULT 'milvus' COMMENT 'RAG 벡터DB 제공자 (milvus/pinecone/local)',
    ADD COLUMN IF NOT EXISTS `rag_collection_name`      VARCHAR(128) NULL DEFAULT NULL  COMMENT 'RAG 컬렉션 이름';

-- ── Step 4: 기존 gpt_sysprompt 데이터 → L1으로 마이그레이션 ──
-- 기존 사용자의 시스템 프롬프트를 L1 Persona로 이관 (데이터 보존)
UPDATE `Gn_aievent_ms_info`
SET `prompt_l1_persona` = CONCAT(
    '<PERSONA>\n',
    COALESCE(`gpt_sysprompt`, ''),
    '\n</PERSONA>'
)
WHERE `prompt_l1_persona` IS NULL
  AND `gpt_sysprompt` IS NOT NULL
  AND `gpt_sysprompt` != '';

-- 기존 message_style 데이터 → L3 Interaction으로 이관
UPDATE `Gn_aievent_ms_info`
SET `prompt_l3_interaction` = CONCAT(
    '<INTERACTION_PROTOCOL>\n',
    COALESCE(`message_style`, ''),
    '\n</INTERACTION_PROTOCOL>'
)
WHERE `prompt_l3_interaction` IS NULL
  AND `message_style` IS NOT NULL
  AND `message_style` != '';

-- 기존 user_gpt_sysprompt 데이터 → L1에 병합 (개인 추가 지시)
UPDATE `Gn_aievent_ms_info`
SET `prompt_l1_persona` = CONCAT(
    COALESCE(`prompt_l1_persona`, ''),
    '\n\n<!-- 기존 개인 추가 지시 -->\n',
    COALESCE(`user_gpt_sysprompt`, '')
)
WHERE `user_gpt_sysprompt` IS NOT NULL
  AND `user_gpt_sysprompt` != ''
  AND `prompt_l1_persona` IS NOT NULL;

-- ── Step 5: 인덱스 추가 ──
CREATE INDEX IF NOT EXISTS `idx_prompt_updated` ON `Gn_aievent_ms_info` (`prompt_updated_at`);
CREATE INDEX IF NOT EXISTS `idx_rag_collection` ON `Gn_aievent_ms_info` (`rag_collection_name`);

-- ── Step 6: 마이그레이션 로그 테이블 ──
CREATE TABLE IF NOT EXISTS `Gn_onechat_prompt_log` (
    `log_idx`       BIGINT       NOT NULL AUTO_INCREMENT,
    `sms_idx`       INT          NOT NULL COMMENT '챗봇 식별자',
    `customer_id`   VARCHAR(64)  NOT NULL COMMENT '사용자 ID',
    `layer`         VARCHAR(20)  NOT NULL COMMENT '변경된 Layer (L1/L2/L3/L4/ASSEMBLY)',
    `old_content`   MEDIUMTEXT   NULL     COMMENT '변경 전 내용',
    `new_content`   MEDIUMTEXT   NULL     COMMENT '변경 후 내용',
    `change_type`   VARCHAR(30)  NOT NULL DEFAULT 'manual' COMMENT '변경 유형 (manual/admin_auto/context_bridge/rag_sync)',
    `changed_by`    VARCHAR(64)  NULL     COMMENT '변경자 (customer_id or admin)',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_idx`),
    INDEX `idx_plog_sms`     (`sms_idx`),
    INDEX `idx_plog_layer`   (`layer`),
    INDEX `idx_plog_time`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='원챗 프롬프트 변경 이력 로그';

-- ── 완료 확인 ──
SELECT '✅ 4-Layer Prompt 마이그레이션 완료' AS status;