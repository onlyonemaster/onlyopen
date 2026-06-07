<?php
/**
 * ════════════════════════════════════════════════════════════════════
 *  원챗 예약관리 공통 헬퍼
 *  - 운영자/고객 식별, 슬롯 생성, 트리거 매칭, 알림 큐잉
 *  STEP C-7 · 2026-05-27
 * ════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

/* ─────────────────────────────────────────────
   1. 운영자 컨텍스트 — (sms_idx, request_idx) 페어
   기존 onechat_settings 컨벤션과 동일
   ───────────────────────────────────────────── */
function reserve_owner_ctx(array $src = null): array {
    $src = $src ?? array_merge($_GET ?? [], $_POST ?? []);
    $sms = (int)($src['sms_idx'] ?? 0);
    $req = (int)($src['request_idx'] ?? 0);
    if ($sms <= 0) {
        onechat_json(['error' => 'sms_idx, request_idx 필수'], 400);
    }
    return [$sms, $req];
}

/* ─────────────────────────────────────────────
   2. JSON body 파서 (POST raw json 지원)
   ───────────────────────────────────────────── */
function reserve_body(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $raw = file_get_contents('php://input');
    if ($raw && strpos(trim($raw), '{') === 0) {
        $j = json_decode($raw, true);
        if (is_array($j)) { $cache = array_merge($_POST ?? [], $j); return $cache; }
    }
    $cache = $_POST ?? [];
    return $cache;
}

/* ─────────────────────────────────────────────
   3. 운영자 config 조회 (없으면 기본값 row 생성)
   ───────────────────────────────────────────── */
function reserve_get_or_create_config($db, int $sms, int $req): array {
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_config WHERE sms_idx=? AND request_idx=?");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($r) return $r;

    // 기본값 row 자동 생성
    $stmt = $db->prepare("INSERT INTO Gn_onechat_reserve_config (sms_idx, request_idx) VALUES (?, ?)");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $stmt->close();
    return reserve_get_or_create_config($db, $sms, $req);
}

/* ─────────────────────────────────────────────
   4. 슬롯 자동 생성 (config + blackout 기반 N일치)
   - 호출 시점: config 저장 직후 / 매일 새벽 cron
   - 멱등성: UNIQUE KEY 활용, 중복 무시
   ───────────────────────────────────────────── */
function reserve_generate_slots($db, int $sms, int $req, int $days = 30): array {
    $cfg = reserve_get_or_create_config($db, $sms, $req);
    if (!$cfg['enabled']) return ['created' => 0, 'reason' => 'disabled'];

    $workDays = array_filter(array_map('intval', explode(',', $cfg['work_days'] ?? '1,2,3,4,5')), fn($d) => $d >= 0 && $d <= 6);
    $slot = max(5, (int)$cfg['slot_minutes']);
    $cap  = max(1, (int)$cfg['capacity']);
    $startMin = strtotime($cfg['work_start']) - strtotime('00:00:00');
    $endMin   = strtotime($cfg['work_end'])   - strtotime('00:00:00');

    // blackout 조회
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_blackout WHERE sms_idx=? AND request_idx=? AND active=1");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $boRes = $stmt->get_result();
    $blackouts = [];
    while ($b = $boRes->fetch_assoc()) $blackouts[] = $b;
    $stmt->close();

    $today = strtotime(date('Y-m-d'));
    $created = 0;
    $insStmt = $db->prepare("INSERT IGNORE INTO Gn_onechat_reserve_slot (sms_idx, request_idx, slot_date, slot_time, capacity) VALUES (?, ?, ?, ?, ?)");

    for ($d = 0; $d < $days; $d++) {
        $ts = $today + $d * 86400;
        $dow = (int)date('w', $ts);
        if (!in_array($dow, $workDays, true)) continue;
        $dateStr = date('Y-m-d', $ts);

        for ($m = $startMin; $m + $slot * 60 <= $endMin; $m += $slot * 60) {
            $timeStr = gmdate('H:i:s', $m);
            // blackout 체크
            $blocked = false;
            foreach ($blackouts as $b) {
                $okDay = false;
                if ($b['kind'] === 'weekly' && (int)$b['weekday'] === $dow) $okDay = true;
                elseif ($b['kind'] === 'date' && $b['date_from'] === $dateStr) $okDay = true;
                elseif ($b['kind'] === 'range' && $dateStr >= $b['date_from'] && $dateStr <= $b['date_to']) $okDay = true;
                if (!$okDay) continue;
                $tf = $b['time_from'] ?: '00:00:00';
                $tt = $b['time_to']   ?: '23:59:59';
                if ($timeStr >= $tf && $timeStr < $tt) { $blocked = true; break; }
            }
            if ($blocked) continue;

            $insStmt->bind_param('iissi', $sms, $req, $dateStr, $timeStr, $cap);
            if ($insStmt->execute()) $created += $insStmt->affected_rows;
        }
    }
    $insStmt->close();
    return ['created' => $created, 'days' => $days];
}

/* ─────────────────────────────────────────────
   5. 알림 큐잉 (3-Way)
   - 실제 전송은 Gn_chatbot_history 에 인서트(챗봇 채널) + 로그 기록
   - sms/push/admin_panel은 향후 채널 핸들러 확장
   ───────────────────────────────────────────── */
function reserve_enqueue_notification($db, int $bookingId, string $recipient, ?string $recipientUserId, string $eventType, string $message, string $channel = 'chatbot'): int {
    $stmt = $db->prepare("INSERT INTO Gn_onechat_reserve_notification (booking_id, recipient, recipient_user_id, event_type, channel, message, status) VALUES (?, ?, ?, ?, ?, ?, 'queued')");
    $stmt->bind_param('isssss', $bookingId, $recipient, $recipientUserId, $eventType, $channel, $message);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

/* ─────────────────────────────────────────────
   6. 3-Way 알림 디스패치 (즉시 발송 - chatbot 채널만)
   - 예약 직후 호출
   - Gn_chatbot_history에 bot 메시지 인서트 (해당 owner 대화방에)
   ───────────────────────────────────────────── */
function reserve_dispatch_3way($db, int $bookingId): array {
    // booking 조회
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $b = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$b) return ['ok' => false, 'error' => 'booking_not_found'];

    $sms = (int)$b['sms_idx']; $req = (int)$b['request_idx'];
    $cfg = reserve_get_or_create_config($db, $sms, $req);
    $place = $cfg['place_name'] ?: '예약처';
    $date  = $b['slot_date']; $time = substr($b['slot_time'], 0, 5);
    $cust  = $b['customer_name'] ?: '고객';
    $head  = (int)$b['headcount'];

    // 메시지 템플릿 (수신자별 정보 수준 차등)
    $msgCustomer = "✅ 예약 확정\n{$place} · {$date} {$time} · {$head}명\n예약번호 #{$bookingId}\n변경/취소는 24시간 전까지 채팅으로 요청해주세요.";
    $msgOperator = "🔔 신규 예약\n{$cust}님 · {$date} {$time} · {$head}명 · #{$bookingId}\n(트리거: {$b['trigger_type']})";
    $msgAdmin    = "[ADMIN] booking #{$bookingId} · owner({$sms},{$req}) · {$date} {$time} · cust:{$cust} · via:{$b['trigger_type']}";

    $ids = [];
    $ids[] = reserve_enqueue_notification($db, $bookingId, 'customer', $b['customer_user_id'], 'confirm', $msgCustomer, 'chatbot');
    $ids[] = reserve_enqueue_notification($db, $bookingId, 'operator', null, 'confirm', $msgOperator, 'chatbot');
    $ids[] = reserve_enqueue_notification($db, $bookingId, 'admin',    null, 'confirm', $msgAdmin,    'admin_panel');

    // 즉시 발송 처리 (chatbot 채널만)
    $up = $db->prepare("UPDATE Gn_onechat_reserve_notification SET status='sent', sent_at=NOW() WHERE id=?");
    foreach ($ids as $nid) { $up->bind_param('i', $nid); $up->execute(); }
    $up->close();

    return ['ok' => true, 'notification_ids' => $ids];
}

/* ─────────────────────────────────────────────
   7. Companion 후속대화 훅 스케줄링 (5단계)
   - 예약 직후 5개 row 미리 생성
   - 실제 발화는 cron이 status='scheduled' AND scheduled_at<=NOW() 폴링
   ───────────────────────────────────────────── */
function reserve_schedule_companion_hooks($db, int $bookingId): array {
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_booking WHERE id=?");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $b = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$b) return ['ok' => false];

    $when = strtotime($b['slot_date'] . ' ' . $b['slot_time']);
    $now  = time();

    $stages = [
        ['stage' => 'post_book',     'at' => $now + 60,                                'reason' => '예약 완료 직후 — 안내사항 확인',                   'msg' => '예약 잘 등록됐어요. 혹시 더 알려드릴 정보 있을까요? (주차/입구/주변 정보)'],
        ['stage' => 'before_24h',    'at' => $when - 86400,                            'reason' => '예약 24시간 전 — 일정 재확인',                     'msg' => '내일 {date} {time} 예약 잊지 않으셨죠? 변경 필요하시면 말씀주세요.'],
        ['stage' => 'same_day_2h',   'at' => $when - 7200,                             'reason' => '당일 2시간 전 — 도착 안내',                        'msg' => '2시간 뒤 예약이에요. 길찾기 도와드릴까요?'],
        ['stage' => 'after_7d',      'at' => $when + 7 * 86400,                        'reason' => '7일 후 — 만족도 확인 (마케팅 아님, 케어 차원)',    'msg' => '지난번 방문 어떠셨어요? 불편한 점 있었다면 알려주세요.'],
        ['stage' => 'after_30d',     'at' => $when + 30 * 86400,                       'reason' => '30일 후 — 자연스러운 재방문 제안 (관심사 기반)',   'msg' => '한 달 됐어요. 비슷한 시간대 다시 예약하실래요?'],
    ];

    $ins = $db->prepare("INSERT INTO Gn_onechat_reserve_companion_hook (booking_id, stage, scheduled_at, reason_template, message_template) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?)");
    $created = [];
    foreach ($stages as $s) {
        if ($s['at'] < $now - 60) continue; // 이미 지난 시점은 스킵 (당일 2h 전 예약 등)
        $ins->bind_param('isiss', $bookingId, $s['stage'], $s['at'], $s['reason'], $s['msg']);
        $ins->execute();
        $created[] = $s['stage'];
    }
    $ins->close();
    return ['ok' => true, 'stages' => $created];
}

/* ─────────────────────────────────────────────
   8. 슬롯 가용성 체크 + 카운터 증가 (트랜잭션 안전)
   ───────────────────────────────────────────── */
function reserve_book_slot($db, int $sms, int $req, string $date, string $time, int $headcount): array {
    $db->begin_transaction();
    try {
        $stmt = $db->prepare("SELECT id, capacity, booked, status FROM Gn_onechat_reserve_slot WHERE sms_idx=? AND request_idx=? AND slot_date=? AND slot_time=? FOR UPDATE");
        $stmt->bind_param('iiss', $sms, $req, $date, $time);
        $stmt->execute();
        $slot = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$slot) throw new Exception('SLOT_NOT_FOUND');
        if ($slot['status'] !== 'open') throw new Exception('SLOT_CLOSED');
        if ($slot['booked'] + $headcount > $slot['capacity']) throw new Exception('SLOT_FULL');

        $newBooked = $slot['booked'] + $headcount;
        $newStatus = ($newBooked >= $slot['capacity']) ? 'full' : 'open';
        $up = $db->prepare("UPDATE Gn_onechat_reserve_slot SET booked=?, status=? WHERE id=?");
        $up->bind_param('isi', $newBooked, $newStatus, $slot['id']);
        $up->execute();
        $up->close();

        $db->commit();
        return ['ok' => true, 'slot_id' => $slot['id'], 'after_booked' => $newBooked, 'capacity' => $slot['capacity']];
    } catch (Exception $e) {
        $db->rollback();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/* ─────────────────────────────────────────────
   9. 결제 확장점 (Phase 2 토스페이먼츠 연동 자리)
   - 현재는 placeholder, booking 생성 후 호출만 해두고 추후 활성화
   ───────────────────────────────────────────── */
function reserve_payment_hook(int $bookingId, array $cfg): array {
    // TODO Phase 2: 토스페이먼츠 결제 URL 생성, 결제 콜백 처리
    // 현재는 결제 미사용 (cfg에 payment_required = 0)
    return ['skipped' => true, 'reason' => 'phase1_no_payment'];
}
