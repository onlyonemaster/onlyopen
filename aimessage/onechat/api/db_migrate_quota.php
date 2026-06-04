<?php
/**
 * DB 마이그레이션: quota 관련 컬럼 추가
 * 한 번만 실행하면 됩니다 (이미 있으면 무시)
 * 
 * 추가 컬럼:
 *   Gn_Member.ai_resp_limit      INT DEFAULT 0   (챗봇 응답자 월 한도)
 *   Gn_Member.ai_resp_used       INT DEFAULT 0   (챗봇 응답자 월 사용량)
 *   Gn_Member.bot_count          INT DEFAULT 0   (보유 챗봇 개수)
 * 
 * 실행: php db_migrate_quota.php
 */

require_once __DIR__ . '/../../config/database.php';

$db = getDatabaseConnection();
$results = [];

// 컬럼 존재 여부 확인 후 추가
$migrations = [
    "ai_resp_limit"      => "ALTER TABLE Gn_Member ADD COLUMN ai_resp_limit INT NOT NULL DEFAULT 0",
    "ai_resp_used"       => "ALTER TABLE Gn_Member ADD COLUMN ai_resp_used INT NOT NULL DEFAULT 0",
    "bot_count"          => "ALTER TABLE Gn_Member ADD COLUMN bot_count INT NOT NULL DEFAULT 0",
];

foreach ($migrations as $col => $sql) {
    $check = $db->query("SHOW COLUMNS FROM Gn_Member LIKE '{$col}'");
    if ($check && $check->num_rows > 0) {
        $results[$col] = "이미 존재함 (skip)";
    } else {
        try {
            $db->query($sql);
            $results[$col] = $db->error ? "실패: {$db->error}" : "추가 완료";
        } catch (Exception $e) {
            $results[$col] = "예외: {$e->getMessage()}";
        }
    }
}

// 기존 회원 중 ai_resp_limit=0 인 경우 service_type 기준 기본값 설정
$db->query("UPDATE Gn_Member SET 
    ai_resp_limit = CASE 
        WHEN service_type = 'pro' OR service_type = 'Pro' THEN 6000
        WHEN service_type = 'standard' OR service_type = 'Standard' THEN 900
        WHEN service_type = 'basic' OR service_type = 'Basic' THEN 300
        ELSE 15
    END
    WHERE ai_resp_limit = 0");

$results['backfill'] = "기존 회원 ai_resp_limit 기본값 설정 완료 (affected: {$db->affected_rows})";

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";