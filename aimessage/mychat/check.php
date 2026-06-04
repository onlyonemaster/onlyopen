<?php
/**
 * 마이챗 진입점 — 로그인/구독 체크 후 리다이렉트
 * m/ 페이지 아이콘 클릭 시 이 파일로 진입
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../onechat/api/auth.php';

$mem_id = onechat_auth();
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

$sub = $db->query(
    "SELECT id FROM mychat_subscriptions
     WHERE mem_id='{$esc}' AND status='active'
       AND (expires_at IS NULL OR expires_at > NOW())
     LIMIT 1"
)?->fetch_assoc();

if ($sub) {
    header('Location: /aimessage/mychat/');
} else {
    header('Location: /aimessage/mychat/subscribe.html?from=m');
}
exit;
