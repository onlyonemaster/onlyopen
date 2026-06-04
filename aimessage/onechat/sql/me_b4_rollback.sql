-- ================================================================
-- [ME-B4] 원챗 라이프 아바타 — DB 스키마 추가 (ROLLBACK)
-- ================================================================
-- 작업: 2026-05-23 / 조은 × 아리(GenSpark)
-- 목적: me_b4_schema.sql 의 모든 변경을 되돌린다.
--
-- ⚠️  주의: 이 스크립트는 DATA LOSS 를 동반합니다.
--     - 신규 3개 테이블(Gn_onechat_me_*) 의 모든 데이터 삭제
--     - Gn_chat_message.mode 컬럼 제거
--   실행 전 반드시 .backup_me/sql/Gn_chat_message.full.*.sql 로 복원 가능한지 확인하세요.
--
-- 적용:
--   mysql -uroot -p'***' -S /disk/daily/home/kiamdb/mysql_dev.sock kiam < me_b4_rollback.sql
-- ================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Gn_chat_message.mode 컬럼 제거 (있을 때만)
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'Gn_chat_message'
     AND COLUMN_NAME  = 'mode'
);
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'Gn_chat_message'
     AND INDEX_NAME   = 'idx_room_mode'
);
SET @ddl1 := IF(@idx_exists > 0,
  'ALTER TABLE Gn_chat_message DROP INDEX idx_room_mode',
  'SELECT ''idx_room_mode 없음 — 건너뜀'' AS info');
PREPARE s1 FROM @ddl1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @ddl2 := IF(@col_exists > 0,
  'ALTER TABLE Gn_chat_message DROP COLUMN mode',
  'SELECT ''mode 컬럼 없음 — 건너뜀'' AS info');
PREPARE s2 FROM @ddl2; EXECUTE s2; DEALLOCATE PREPARE s2;

-- 2) 라이프 아바타 테이블 3종 제거
DROP TABLE IF EXISTS Gn_onechat_me_decisions;
DROP TABLE IF EXISTS Gn_onechat_me_data_pool;
DROP TABLE IF EXISTS Gn_onechat_me_avatar;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- 검증
-- SHOW TABLES LIKE 'Gn_onechat_me_%';   -- 결과 없어야 함
-- SHOW COLUMNS FROM Gn_chat_message LIKE 'mode';   -- 결과 없어야 함
