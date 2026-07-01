<?php
/**
 * 예약 슬롯 (캘린더용)
 * GET ?sms_idx=&request_idx=&from=YYYY-MM-DD&to=YYYY-MM-DD   → 슬롯 목록
 * POST {sms_idx, request_idx, regen:1}                       → 슬롯 강제 재생성
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    [$sms, $req] = reserve_owner_ctx();
    $from = $_GET['from'] ?? date('Y-m-d');
    $to   = $_GET['to']   ?? date('Y-m-d', strtotime('+30 days'));
    $stmt = $db->prepare("SELECT id, slot_date, slot_time, capacity, booked, status FROM Gn_onechat_reserve_slot WHERE sms_idx=? AND request_idx=? AND slot_date BETWEEN ? AND ? ORDER BY slot_date ASC, slot_time ASC");
    $stmt->bind_param('iiss', $sms, $req, $from, $to);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();

    // 날짜별 요약
    $byDate = [];
    foreach ($rows as $s) {
        $d = $s['slot_date'];
        if (!isset($byDate[$d])) $byDate[$d] = ['total' => 0, 'booked' => 0, 'full' => 0, 'open' => 0];
        $byDate[$d]['total']++;
        $byDate[$d]['booked'] += (int)$s['booked'];
        if ($s['status'] === 'full') $byDate[$d]['full']++; else $byDate[$d]['open']++;
    }
    onechat_json(['ok' => true, 'from' => $from, 'to' => $to, 'slots' => $rows, 'by_date' => $byDate]);
}

if ($m === 'POST') {
    $b = reserve_body();
    [$sms, $req] = reserve_owner_ctx($b);
    $days = max(1, min(90, (int)($b['days'] ?? 30)));
    $regen = reserve_generate_slots($db, $sms, $req, $days);
    onechat_json(['ok' => true, 'regen' => $regen]);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
