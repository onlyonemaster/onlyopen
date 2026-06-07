<?php
/**
 * 3-Way 알림 로그 조회 + 발송 처리
 * GET ?booking_id=&recipient=                 → 알림 로그 조회
 * GET ?queued=1                                → 대기중 알림 큐 (cron용)
 * POST {action:'mark_sent', ids:[...]}        → 발송 완료 처리
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    if (!empty($_GET['queued'])) {
        $r = $db->query("SELECT * FROM Gn_onechat_reserve_notification WHERE status='queued' ORDER BY id ASC LIMIT 200");
        $rows = [];
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        onechat_json(['ok' => true, 'queued' => $rows]);
    }
    $bid = (int)($_GET['booking_id'] ?? 0);
    $rcp = (string)($_GET['recipient'] ?? '');
    $sql = "SELECT * FROM Gn_onechat_reserve_notification WHERE 1=1";
    $types = ''; $vals = [];
    if ($bid) { $sql .= " AND booking_id=?"; $types .= 'i'; $vals[] = $bid; }
    if ($rcp) { $sql .= " AND recipient=?"; $types .= 's'; $vals[] = $rcp; }
    $sql .= " ORDER BY id DESC LIMIT 200";
    $stmt = $db->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    onechat_json(['ok' => true, 'notifications' => $rows]);
}

if ($m === 'POST') {
    $b = reserve_body();
    $action = $b['action'] ?? '';
    if ($action === 'mark_sent') {
        $ids = $b['ids'] ?? [];
        if (!is_array($ids) || !$ids) onechat_json(['error' => 'ids 필수'], 400);
        $ids = array_map('intval', $ids);
        $in  = implode(',', $ids);
        $db->query("UPDATE Gn_onechat_reserve_notification SET status='sent', sent_at=NOW() WHERE id IN ($in) AND status='queued'");
        onechat_json(['ok' => true, 'marked' => $db->affected_rows]);
    }
    onechat_json(['error' => 'unknown action'], 400);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
