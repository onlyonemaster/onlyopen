<?php
/**
 * 예약 제외시간 (blackout) — CRUD
 * GET    ?sms_idx=&request_idx=
 * POST   {sms_idx, request_idx, label, kind, weekday?, date_from?, date_to?, time_from, time_to}
 * DELETE ?id=&sms_idx=&request_idx=
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    [$sms, $req] = reserve_owner_ctx();
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_blackout WHERE sms_idx=? AND request_idx=? ORDER BY id DESC");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    onechat_json(['ok' => true, 'blackouts' => $rows]);
}

if ($m === 'POST') {
    $b = reserve_body();
    [$sms, $req] = reserve_owner_ctx($b);
    $label  = (string)($b['label'] ?? '');
    $kind   = in_array($b['kind'] ?? 'weekly', ['weekly','date','range'], true) ? $b['kind'] : 'weekly';
    $weekday = isset($b['weekday']) ? (int)$b['weekday'] : null;
    $dateFrom = !empty($b['date_from']) ? $b['date_from'] : null;
    $dateTo   = !empty($b['date_to'])   ? $b['date_to']   : null;
    $timeFrom = !empty($b['time_from']) ? $b['time_from'] : null;
    $timeTo   = !empty($b['time_to'])   ? $b['time_to']   : null;
    $stmt = $db->prepare("INSERT INTO Gn_onechat_reserve_blackout (sms_idx, request_idx, label, kind, weekday, date_from, date_to, time_from, time_to, active) VALUES (?,?,?,?,?,?,?,?,?,1)");
    $stmt->bind_param('iississss', $sms, $req, $label, $kind, $weekday, $dateFrom, $dateTo, $timeFrom, $timeTo);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    // 슬롯 재생성
    $regen = reserve_generate_slots($db, $sms, $req, 30);
    onechat_json(['ok' => true, 'id' => $id, 'slot_regen' => $regen]);
}

if ($m === 'DELETE') {
    $b = array_merge($_GET ?? [], reserve_body());
    [$sms, $req] = reserve_owner_ctx($b);
    $id = (int)($b['id'] ?? 0);
    if (!$id) onechat_json(['error' => 'id 필수'], 400);
    $stmt = $db->prepare("DELETE FROM Gn_onechat_reserve_blackout WHERE id=? AND sms_idx=? AND request_idx=?");
    $stmt->bind_param('iii', $id, $sms, $req);
    $stmt->execute();
    $aff = $stmt->affected_rows;
    $stmt->close();
    onechat_json(['ok' => true, 'deleted' => $aff]);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
