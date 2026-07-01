<?php
/**
 * 예약 (booking) — CRUD + 내예약 조회
 * 
 * GET  ?sms_idx=&request_idx=&from=&to=&status=     → 운영자: 예약 목록
 * GET  ?mine=1                                       → 고객: 내 예약 전체 (로그인 user_id 기준)
 * POST {sms_idx, request_idx, slot_date, slot_time, headcount?, customer_name?, customer_phone?, memo?, trigger_type?, trigger_id?}
 *      → 신규 예약 (슬롯 차감 + 3-Way 알림 + Companion 훅)
 * POST {action:'cancel', id, reason?}                → 예약 취소 (슬롯 복원 + 알림)
 * POST {action:'update_status', id, status}          → 운영자: 상태 변경
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();

/* ── 고객 챗봇 대화창에 공지 메시지 삽입 헬퍼 ── */
function reserve_notify_chatbot($db, $customer_user_id, $sms_idx, $message) {
    if (empty($customer_user_id) || $sms_idx <= 0) return;
    $vid = addslashes($customer_user_id);
    $msg = addslashes($message);
    $db->query("INSERT INTO Gn_visitor_chat_log (visitor_id, sms_idx, role, message, created_at) VALUES ('{$vid}', {$sms_idx}, 'assistant', '{$msg}', NOW())");
}
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') {
    // 내 예약 조회
    if (!empty($_GET['mine'])) {
        $stmt = $db->prepare("SELECT b.*, c.place_name, c.place_address, c.place_phone 
                              FROM Gn_onechat_reserve_booking b
                              LEFT JOIN Gn_onechat_reserve_config c ON c.sms_idx=b.sms_idx AND c.request_idx=b.request_idx
                              WHERE b.customer_user_id=? AND b.status IN ('confirmed','pending','done')
                              ORDER BY b.slot_date DESC, b.slot_time DESC LIMIT 50");
        $stmt->bind_param('s', $login_id);
        $stmt->execute();
        $rows = [];
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        onechat_json(['ok' => true, 'mine' => $rows]);
    }

    // 운영자: 예약 목록
    [$sms, $req] = reserve_owner_ctx();
    $from   = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
    $to     = $_GET['to']   ?? date('Y-m-d', strtotime('+60 days'));
    $status = $_GET['status'] ?? '';
    $sql = "SELECT * FROM Gn_onechat_reserve_booking WHERE sms_idx=? AND request_idx=? AND slot_date BETWEEN ? AND ?";
    $types = 'iiss'; $vals = [$sms, $req, $from, $to];
    if ($status) { $sql .= " AND status=?"; $types .= 's'; $vals[] = $status; }
    $sql .= " ORDER BY slot_date ASC, slot_time ASC LIMIT 500";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();

    // 통계
    $stat = ['total' => count($rows), 'confirmed' => 0, 'cancelled' => 0, 'no_show' => 0, 'done' => 0];
    foreach ($rows as $r) $stat[$r['status']] = ($stat[$r['status']] ?? 0) + 1;

    onechat_json(['ok' => true, 'bookings' => $rows, 'stat' => $stat]);
}

if ($m === 'POST') {
    $b = reserve_body();
    $action = $b['action'] ?? 'create';

    /* ───── 취소 ───── */
    if ($action === 'cancel') {
        $id = (int)($b['id'] ?? 0);
        $reason = (string)($b['reason'] ?? '');
        if (!$id) onechat_json(['error' => 'id 필수'], 400);
        // booking 조회
        $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$bk) onechat_json(['error' => '예약을 찾을 수 없'], 404);
        // 본인 또는 운영자만 취소 가능 (간단히 customer_user_id 일치 OR sms_idx 본인은 운영자로 가정)
        if ($bk['customer_user_id'] !== $login_id) {
            // 운영자 취소도 허용 (실제로는 권한 체크 필요)
        }
        if ($bk['status'] === 'cancelled') onechat_json(['ok' => true, 'already' => true]);
        // 상태 변경
        $up = $db->prepare("UPDATE Gn_onechat_reserve_booking SET status='cancelled', cancel_reason=?, cancel_at=NOW() WHERE id=?");
        $up->bind_param('si', $reason, $id);
        $up->execute();
        $up->close();
        // 슬롯 복원
        $sd = $bk['slot_date']; $st = $bk['slot_time']; $hc = (int)$bk['headcount'];
        $sql = "UPDATE Gn_onechat_reserve_slot SET booked=GREATEST(0,booked-?), status='open' WHERE sms_idx=? AND request_idx=? AND slot_date=? AND slot_time=?";
        $upS = $db->prepare($sql);
        $upS->bind_param('iiiss', $hc, $bk['sms_idx'], $bk['request_idx'], $sd, $st);
        $upS->execute();
        $upS->close();
        // companion 훅 일괄 취소
        $upH = $db->prepare("UPDATE Gn_onechat_reserve_companion_hook SET status='cancelled' WHERE booking_id=? AND status='scheduled'");
        $upH->bind_param('i', $id);
        $upH->execute();
        $upH->close();
        // 알림
        $cfg = reserve_get_or_create_config($db, (int)$bk['sms_idx'], (int)$bk['request_idx']);
        $place = $cfg['place_name'] ?: '예약처';
        $msgC = "❎ 예약 취소됨\n{$place} · {$sd} " . substr($st,0,5) . "\n사유: " . ($reason ?: '미기재');
        $msgO = "🔕 예약 취소\n#{$id} · {$sd} " . substr($st,0,5) . " · 사유: " . ($reason ?: '미기재');
        reserve_enqueue_notification($db, $id, 'customer', $bk['customer_user_id'], 'cancel', $msgC);
        reserve_enqueue_notification($db, $id, 'operator', null, 'cancel', $msgO);
        reserve_enqueue_notification($db, $id, 'admin',    null, 'cancel', "[ADMIN] cancel #{$id} reason:{$reason}", 'admin_panel');
        $up = $db->prepare("UPDATE Gn_onechat_reserve_notification SET status='sent', sent_at=NOW() WHERE booking_id=? AND event_type='cancel' AND status='queued'");
        $up->bind_param('i', $id);
        $up->execute();
        $up->close();
        // 고객 챗봇에 취소 공지 (chat_log 직접 삽입)
        $_cfg_c = reserve_get_or_create_config($db, (int)$bk['sms_idx'], (int)$bk['request_idx']);
        $_place_c = $_cfg_c['place_name'] ?: '예약처';
        $_cancel_msg = "🚫 예약 취소 안내\n{$_place_c} · {$bk['slot_date']} ".substr($bk['slot_time'],0,5)."\n예약번호 #{$id}\n취소 사유: ".($reason?:'미기재')."\n예약을 취소했습니다. 재예약을 원하시면 말씀해주세요.";
        reserve_notify_chatbot($db, $bk['customer_user_id'], (int)$bk['sms_idx'], $_cancel_msg);
        onechat_json(['ok' => true, 'cancelled' => true]);
    }

    /* ───── 상태변경 (운영자) ───── */
    if ($action === 'update_status') {
        $id = (int)($b['id'] ?? 0);
        $st = $b['status'] ?? '';
        if (!in_array($st, ['pending','confirmed','done','no_show'], true)) onechat_json(['error' => '잘못된 상태'], 400);
        $up = $db->prepare("UPDATE Gn_onechat_reserve_booking SET status=? WHERE id=?");
        $up->bind_param('si', $st, $id);
        $up->execute();
        $up->close();
        onechat_json(['ok' => true, 'updated_status' => $st]);
    }

    /* ───── 예약 날짜/시간 이동 (드래그앤드롭) ───── */
    if ($action === 'move') {
        $id       = (int)($b['id'] ?? 0);
        $new_date = trim($b['new_date'] ?? '');
        $new_time = trim($b['new_time'] ?? '');
        if (!$id || !$new_date) onechat_json(['error' => 'id, new_date 필수'], 400);
        $s0 = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $s0->bind_param('i', $id); $s0->execute();
        $bk = $s0->get_result()->fetch_assoc(); $s0->close();
        if (!$bk) onechat_json(['error' => '예약을 찾을 수 없음'], 404);
        $old_date = $bk['slot_date']; $old_time = $bk['slot_time'];
        $use_time = $new_time ?: $old_time;
        $use_time = strlen($use_time)===5 ? $use_time.':00' : $use_time;
        if ($old_date===$new_date && $use_time===$old_time) onechat_json(['ok'=>true,'moved'=>false]);
        // 기존 슬롯 복원
        $db->query("UPDATE Gn_onechat_reserve_slot SET booked=GREATEST(0,booked-{$bk['headcount']}) WHERE sms_idx={$bk['sms_idx']} AND request_idx={$bk['request_idx']} AND slot_date='{$old_date}' AND slot_time='{$old_time}'");
        // 새 슬롯 확인
        $new_slot = null;
        $s1 = $db->prepare("SELECT id,capacity,booked FROM Gn_onechat_reserve_slot WHERE sms_idx=? AND request_idx=? AND slot_date=? AND slot_time=? AND status='open' LIMIT 1");
        if ($s1) { $s1->bind_param('iiss',$bk['sms_idx'],$bk['request_idx'],$new_date,$use_time); $s1->execute(); $new_slot=$s1->get_result()->fetch_assoc(); $s1->close(); }
        if ($new_slot && $new_slot['booked'] >= $new_slot['capacity']) {
            // 슬롯 마감 → 기존 슬롯 복원 취소
            $db->query("UPDATE Gn_onechat_reserve_slot SET booked=booked+{$bk['headcount']} WHERE sms_idx={$bk['sms_idx']} AND request_idx={$bk['request_idx']} AND slot_date='{$old_date}' AND slot_time='{$old_time}'");
            onechat_json(['error' => '해당 시간대가 마감됐습니다.'], 400);
        }
        // 예약 업데이트
        $up = $db->prepare("UPDATE Gn_onechat_reserve_booking SET slot_date=?,slot_time=? WHERE id=?");
        $up->bind_param('ssi',$new_date,$use_time,$id); $up->execute(); $up->close();
        // 새 슬롯 차감
        if ($new_slot) $db->query("UPDATE Gn_onechat_reserve_slot SET booked=booked+{$bk['headcount']} WHERE id={$new_slot['id']}");
        // 변경된 예약 반환
        $s2=$db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $s2->bind_param('i',$id); $s2->execute();
        $moved=$s2->get_result()->fetch_assoc(); $s2->close();
        // 고객 챗봇에 변경 공지
        $_dt_old = new DateTime($old_date); $_dt_new = new DateTime($new_date);
        $_days_k = ['일','월','화','수','목','금','토'];
        $_old_disp = $_dt_old->format('Y년 n월 j일').' ('.$_days_k[$_dt_old->format('w')].') '.substr($old_time,0,5);
        $_new_disp = $_dt_new->format('Y년 n월 j일').' ('.$_days_k[$_dt_new->format('w')].') '.substr($use_time,0,5);
        $_change_msg = "🔔 예약 변경 안내\n기존: {$_old_disp}\n변경: {$_new_disp}\n예약번호 #{$id}\n변경·취소는 채팅으로 말씀해주세요.";
        reserve_notify_chatbot($db, $bk['customer_user_id'], (int)$bk['sms_idx'], $_change_msg);
        onechat_json(['ok'=>true,'moved'=>true,'booking'=>$moved]);
    }

    /* ───── 상세조회 (단건) ───── */
    if ($action === 'get') {
        $id = (int)($b['id'] ?? 0);
        if (!$id) onechat_json(['error' => 'id 필수'], 400);
        $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$bk) onechat_json(['error' => '예약을 찾을 수 없음'], 404);
        onechat_json(['ok' => true, 'booking' => $bk]);
    }

    /* ───── 예약 수정 (운영자) ───── */
    if ($action === 'update') {
        $id = (int)($b['id'] ?? 0);
        if (!$id) onechat_json(['error' => 'id 필수'], 400);
        $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$bk) onechat_json(['error' => '예약을 찾을 수 없음'], 404);

        $cname  = isset($b['customer_name'])  ? (string)$b['customer_name']  : $bk['customer_name'];
        $cphone = isset($b['customer_phone']) ? (string)$b['customer_phone'] : $bk['customer_phone'];
        $memo   = isset($b['memo'])           ? (string)$b['memo']           : $bk['memo'];
        $st     = isset($b['status']) && in_array($b['status'], ['pending','confirmed','done','no_show','cancelled'], true)
                  ? $b['status'] : $bk['status'];
        $head   = isset($b['headcount']) ? max(1, (int)$b['headcount']) : (int)$bk['headcount'];

        $up = $db->prepare("UPDATE Gn_onechat_reserve_booking SET customer_name=?, customer_phone=?, memo=?, status=?, headcount=? WHERE id=?");
        $up->bind_param('ssssi i', $cname, $cphone, $memo, $st, $head, $id);
        // bind_param 공백 제거
        $up->close();
        $up2 = $db->prepare("UPDATE Gn_onechat_reserve_booking SET customer_name=?, customer_phone=?, memo=?, status=?, headcount=? WHERE id=?");
        $up2->bind_param('ssssii', $cname, $cphone, $memo, $st, $head, $id);
        $up2->execute();
        $up2->close();

        // 수정된 예약 반환
        $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $updated = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        onechat_json(['ok' => true, 'booking' => $updated]);
    }

    /* ───── 운영자 직접 예약 추가 (슬롯 검증 우회) ───── */
    if ($action === 'admin_add') {
        [$sms, $req] = reserve_owner_ctx($b);
        $date   = $b['slot_date']  ?? '';
        $time   = $b['slot_time']  ?? '';
        $head   = max(1, (int)($b['headcount'] ?? 1));
        $cname  = (string)($b['customer_name']  ?? '');
        $cphone = (string)($b['customer_phone'] ?? '');
        $memo   = (string)($b['memo']           ?? '');
        $st     = in_array($b['status']??'', ['pending','confirmed','done','no_show','cancelled'], true)
                  ? $b['status'] : 'confirmed';
        if (!$date || !$time) onechat_json(['error' => 'slot_date, slot_time 필수'], 400);
        $st_time = strlen($time) === 5 ? $time . ':00' : $time;
        $stmt = $db->prepare("INSERT INTO Gn_onechat_reserve_booking (sms_idx, request_idx, customer_user_id, customer_name, customer_phone, slot_date, slot_time, headcount, memo, status, trigger_type) VALUES (?,?,?,?,?,?,?,?,?,?,'manual')");
        $stmt->bind_param('iisssssisss', $sms, $req, $login_id, $cname, $cphone, $date, $st_time, $head, $memo, $st);
        $stmt->execute();
        $bookingId = $stmt->insert_id;
        $stmt->close();
        // 추가된 예약 반환
        $s2 = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
        $s2->bind_param('i', $bookingId);
        $s2->execute();
        $bkRow = $s2->get_result()->fetch_assoc();
        $s2->close();
        onechat_json(['ok' => true, 'booking' => $bkRow]);
    }

    /* ───── 신규 예약 ───── */
    [$sms, $req] = reserve_owner_ctx($b);
    $date  = $b['slot_date']  ?? '';
    $time  = $b['slot_time']  ?? '';
    $head  = max(1, (int)($b['headcount'] ?? 1));
    $cname = (string)($b['customer_name'] ?? '');
    $cphone= (string)($b['customer_phone'] ?? '');
    $memo  = (string)($b['memo'] ?? '');
    $trigType = in_array($b['trigger_type'] ?? '', ['manual','mood','companion','interest','customer_request'], true) ? $b['trigger_type'] : 'customer_request';
    $trigId   = isset($b['trigger_id']) && $b['trigger_id'] !== '' ? (int)$b['trigger_id'] : null;
    if (!$date || !$time) onechat_json(['error' => 'slot_date, slot_time 필수'], 400);

    // config 검증 (lead_time, max_advance, enabled)
    $cfg = reserve_get_or_create_config($db, $sms, $req);
    if (!$cfg['enabled']) onechat_json(['error' => '예약 기능이 꺼져있습니다.'], 400);
    $whenTs = strtotime("$date $time");
    if ($whenTs < time() + ($cfg['lead_time_min'] * 60)) onechat_json(['error' => '리드타임 이내 예약은 불가합니다.'], 400);
    if ($whenTs > time() + ($cfg['max_advance_days'] * 86400)) onechat_json(['error' => '예약 가능 기간을 벗어났습니다.'], 400);

    // 슬롯 가용성 + 차감 (트랜잭션)
    $slotR = reserve_book_slot($db, $sms, $req, $date, substr($time,0,8) === substr($time,0,8) ? (strlen($time)===5?$time.':00':$time) : $time, $head);
    if (!$slotR['ok']) onechat_json(['error' => '슬롯 예약 실패: ' . $slotR['error']], 400);

    // booking 인서트
    // 컬럼: sms_idx(i), request_idx(i), customer_user_id(s), customer_name(s), customer_phone(s),
    //       slot_date(s), slot_time(s), headcount(i), memo(s), trigger_type(s), trigger_id(i)
    // 타입 문자열: "iisssssissi" (11자)
    $st_time = strlen($time) === 5 ? $time . ':00' : $time;
    $stmt = $db->prepare("INSERT INTO Gn_onechat_reserve_booking (sms_idx, request_idx, customer_user_id, customer_name, customer_phone, slot_date, slot_time, headcount, memo, status, trigger_type, trigger_id) VALUES (?,?,?,?,?,?,?,?,?, 'confirmed', ?, ?)");
    $stmt->bind_param('iisssssissi', $sms, $req, $login_id, $cname, $cphone, $date, $st_time, $head, $memo, $trigType, $trigId);
    $stmt->execute();
    $bookingId = $stmt->insert_id;
    $stmt->close();

    // 3-Way 알림 + Companion 훅
    $notif = reserve_dispatch_3way($db, $bookingId);
    $hook  = reserve_schedule_companion_hooks($db, $bookingId);

    // 결제 확장점 (현재 placeholder)
    $pay = reserve_payment_hook($bookingId, $cfg);

    onechat_json([
        'ok' => true,
        'booking_id' => $bookingId,
        'slot' => $slotR,
        'notification' => $notif,
        'companion_hook' => $hook,
        'payment' => $pay,
        'summary' => [
            'place'    => $cfg['place_name'] ?: '예약처',
            'date'     => $date,
            'time'     => substr($st_time, 0, 5),
            'headcount'=> $head,
        ],
    ]);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
