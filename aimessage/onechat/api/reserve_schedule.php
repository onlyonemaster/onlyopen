<?php
/**
 * 운영자 캘린더 일정 (schedule) — CRUD
 *
 * GET  ?sms_idx=&request_idx=&from=&to=   → 일정 목록
 * POST {action:'add',    ...}              → 일정 추가
 * POST {action:'update', id, ...}          → 일정 수정
 * POST {action:'delete', id}              → 일정 삭제
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    [$sms, $req] = reserve_owner_ctx();
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-t', strtotime('+2 months'));
    $stmt = $db->prepare(
        "SELECT * FROM Gn_onechat_schedule WHERE sms_idx=? AND request_idx=? AND start_date BETWEEN ? AND ? AND status='active' ORDER BY start_date ASC, start_time ASC LIMIT 500"
    );
    $stmt->bind_param('iiss', $sms, $req, $from, $to);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    onechat_json(['ok' => true, 'schedules' => $rows]);
}

if ($m === 'POST') {
    $b = reserve_body();
    $action = $b['action'] ?? 'add';
    [$sms, $req] = reserve_owner_ctx($b);

    if ($action === 'add') {
        $title      = trim($b['title'] ?? '');
        if (!$title) onechat_json(['error' => '제목 필수'], 400);
        $event_type = in_array($b['event_type'] ?? '', ['booking','schedule','event','personal'], true) ? $b['event_type'] : 'schedule';
        $color      = preg_match('/^#[0-9a-fA-F]{3,6}$/', $b['color'] ?? '') ? $b['color'] : '#3b82f6';
        $start_date = $b['start_date']  ?? '';
        $start_time = $b['start_time']  ?? null;
        $end_date   = $b['end_date']    ?? $start_date;
        $end_time   = $b['end_time']    ?? null;
        $all_day    = empty($b['all_day']) ? 0 : 1;
        $att_name   = $b['attendee_name']  ?? null;
        $att_phone  = $b['attendee_phone'] ?? null;
        $location   = $b['location']    ?? null;
        $desc       = $b['description'] ?? null;
        if (!$start_date) onechat_json(['error' => 'start_date 필수'], 400);
        $stmt = $db->prepare("INSERT INTO Gn_onechat_schedule (sms_idx,request_idx,event_type,color,title,start_date,start_time,end_date,end_time,all_day,attendee_name,attendee_phone,location,description,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iisssssssisssss', $sms, $req, $event_type, $color, $title, $start_date, $start_time, $end_date, $end_time, $all_day, $att_name, $att_phone, $location, $desc, $login_id);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        $s2 = $db->prepare("SELECT * FROM Gn_onechat_schedule WHERE id=?");
        $s2->bind_param('i', $newId);
        $s2->execute();
        $sc = $s2->get_result()->fetch_assoc();
        $s2->close();
        onechat_json(['ok' => true, 'schedule' => $sc]);
    }

    if ($action === 'update') {
        $id = (int)($b['id'] ?? 0);
        if (!$id) onechat_json(['error' => 'id 필수'], 400);
        $s0 = $db->prepare("SELECT * FROM Gn_onechat_schedule WHERE id=? AND sms_idx=?");
        $s0->bind_param('ii', $id, $sms);
        $s0->execute();
        $sc = $s0->get_result()->fetch_assoc();
        $s0->close();
        if (!$sc) onechat_json(['error' => '일정을 찾을 수 없음'], 404);
        $title      = isset($b['title'])      ? trim($b['title'])      : $sc['title'];
        $event_type = isset($b['event_type']) && in_array($b['event_type'], ['booking','schedule','event','personal'], true) ? $b['event_type'] : $sc['event_type'];
        $color      = isset($b['color']) && preg_match('/^#[0-9a-fA-F]{3,6}$/', $b['color']) ? $b['color'] : $sc['color'];
        $start_date = $b['start_date']  ?? $sc['start_date'];
        $start_time = array_key_exists('start_time',  $b) ? $b['start_time']  : $sc['start_time'];
        $end_date   = $b['end_date']    ?? $sc['end_date'];
        $end_time   = array_key_exists('end_time',    $b) ? $b['end_time']    : $sc['end_time'];
        $all_day    = array_key_exists('all_day',     $b) ? (int)$b['all_day'] : (int)$sc['all_day'];
        $att_name   = array_key_exists('attendee_name',  $b) ? $b['attendee_name']  : $sc['attendee_name'];
        $att_phone  = array_key_exists('attendee_phone', $b) ? $b['attendee_phone'] : $sc['attendee_phone'];
        $location   = array_key_exists('location',    $b) ? $b['location']    : $sc['location'];
        $desc       = array_key_exists('description', $b) ? $b['description'] : $sc['description'];
        $up = $db->prepare("UPDATE Gn_onechat_schedule SET event_type=?,color=?,title=?,start_date=?,start_time=?,end_date=?,end_time=?,all_day=?,attendee_name=?,attendee_phone=?,location=?,description=? WHERE id=?");
        $up->bind_param('ssssssssisssi', $event_type, $color, $title, $start_date, $start_time, $end_date, $end_time, $all_day, $att_name, $att_phone, $desc, $id);
        $up->execute();
        $up->close();
        $s2 = $db->prepare("SELECT * FROM Gn_onechat_schedule WHERE id=?");
        $s2->bind_param('i', $id);
        $s2->execute();
        $updated = $s2->get_result()->fetch_assoc();
        $s2->close();
        onechat_json(['ok' => true, 'schedule' => $updated]);
    }

    if ($action === 'delete') {
        $id = (int)($b['id'] ?? 0);
        if (!$id) onechat_json(['error' => 'id 필수'], 400);
        $up = $db->prepare("UPDATE Gn_onechat_schedule SET status='cancelled' WHERE id=? AND sms_idx=?");
        $up->bind_param('ii', $id, $sms);
        $up->execute();
        $up->close();
        onechat_json(['ok' => true, 'deleted' => $id]);
    }
}
onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
