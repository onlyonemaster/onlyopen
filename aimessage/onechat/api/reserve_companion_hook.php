<?php
/**
 * AI Companion 후속대화 훅 조회/소진
 * GET ?booking_id=                                       → 특정 예약의 5단계 훅
 * GET ?due=1                                              → scheduled_at 도래한 훅 (cron용)
 * POST {action:'fire', id, result_message?}              → 훅 발화 처리
 * POST {action:'skip', id}                                → 훅 스킵
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    if (!empty($_GET['due'])) {
        $r = $db->query("SELECT * FROM Gn_onechat_reserve_companion_hook WHERE status='scheduled' AND scheduled_at <= NOW() ORDER BY scheduled_at ASC LIMIT 100");
        $rows = [];
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        onechat_json(['ok' => true, 'due' => $rows]);
    }
    $bid = (int)($_GET['booking_id'] ?? 0);
    if (!$bid) onechat_json(['error' => 'booking_id 필수'], 400);
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_companion_hook WHERE booking_id=? ORDER BY scheduled_at ASC");
    $stmt->bind_param('i', $bid);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    onechat_json(['ok' => true, 'hooks' => $rows]);
}

if ($m === 'POST') {
    $b = reserve_body();
    $a = $b['action'] ?? '';
    $id = (int)($b['id'] ?? 0);
    if (!$id) onechat_json(['error' => 'id 필수'], 400);
    if ($a === 'fire') {
        $msg = (string)($b['result_message'] ?? '');
        $up = $db->prepare("UPDATE Gn_onechat_reserve_companion_hook SET status='fired', fired_at=NOW(), result_message=? WHERE id=? AND status='scheduled'");
        $up->bind_param('si', $msg, $id);
        $up->execute();
        $aff = $up->affected_rows;
        $up->close();
        onechat_json(['ok' => true, 'fired' => $aff]);
    }
    if ($a === 'skip') {
        $up = $db->prepare("UPDATE Gn_onechat_reserve_companion_hook SET status='skipped' WHERE id=?");
        $up->bind_param('i', $id);
        $up->execute();
        $up->close();
        onechat_json(['ok' => true]);
    }
    onechat_json(['error' => 'unknown action'], 400);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
