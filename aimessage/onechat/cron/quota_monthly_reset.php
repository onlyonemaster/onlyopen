<?php
/**
 * 월간 Quota 리셋 Cron (매월 1일 00:00 실행)
 * 
 * 실행: php /home/kiam/aimessage/onechat/cron/quota_monthly_reset.php
 * 
 * 모든 Gn_Member의 월간 사용량(ai_profile_used, ai_msg_person_used, ai_resp_used)을 0으로 초기화
 * 단, 구독이 만료된 회원은 Free 한도로 다운그레이드
 */

require_once '/home/kiam/aimessage/config/database.php';

$db = getDatabaseConnection();
$now = date('Y-m-d H:i:s');

// ── 1. 만료된 구독 Free로 다운그레이드 ─────────────────
$sql_expire = "UPDATE Gn_Member SET 
    service_type = 'free',
    sub_end_date = NULL,
    ai_profile_limit = 30,
    ai_msg_person_limit = 15
    WHERE service_type != 'free' 
      AND sub_end_date IS NOT NULL 
      AND sub_end_date < NOW()";

$db->query($sql_expire);
$expired = $db->affected_rows;

// ── 2. 모든 회원 월간 사용량 리셋 ───────────────────
$sql_reset = "UPDATE Gn_Member SET 
    ai_profile_used = 0, 
    ai_msg_person_used = 0";

$db->query($sql_reset);
$reset = $db->affected_rows;

// ── 3. ai_resp_used 리셋 (컬럼이 있으면) ───────────
$check = $db->query("SHOW COLUMNS FROM Gn_Member LIKE 'ai_resp_used'");
if ($check && $check->num_rows > 0) {
    $db->query("UPDATE Gn_Member SET ai_resp_used = 0");
}

echo json_encode([
    'ok'      => true,
    'date'    => $now,
    'expired' => $expired,
    'reset'   => $reset,
    'message' => "{$expired} accounts expired, {$reset} usage counters reset"
], JSON_UNESCAPED_UNICODE) . "\n";
