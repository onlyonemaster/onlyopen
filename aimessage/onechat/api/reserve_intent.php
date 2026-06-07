<?php
/**
 * 예약 시스템 — 챗봇 Intent 게이트웨이
 * 
 * 챗봇이 고객 발화를 보내면 → intent 분류 → 액션 수행 → 봇 응답 텍스트 반환
 * ai_reply_onechat.php가 호출 (또는 클라이언트에서 직접)
 * 
 * POST {sms_idx, request_idx, text, customer_user_id?}
 * → {ok, intent, action_result, bot_reply, ui_hint?}
 * 
 * intent:
 *  - book.request    : "예약하고 싶어", "예약해줘"
 *  - book.lookup     : "내 예약 보여줘", "예약 조회"
 *  - book.cancel     : "예약 취소"
 *  - mood.signal     : 분위기 키워드 감지 → 트리거 발화 후보
 *  - interest.match  : 관심사 키워드 매칭
 *  - none            : 일반대화
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') onechat_json(['error' => 'POST only'], 405);
$b = reserve_body();
[$sms, $req] = reserve_owner_ctx($b);
$text = trim((string)($b['text'] ?? ''));
if ($text === '') onechat_json(['error' => 'text 필수'], 400);

// 트리거 로딩
$stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_trigger WHERE sms_idx=? AND request_idx=?");
$stmt->bind_param('ii', $sms, $req);
$stmt->execute();
$triggers = [];
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $triggers[$row['trigger_type']] = $row;
$stmt->close();

$cfg = reserve_get_or_create_config($db, $sms, $req);
$place = $cfg['place_name'] ?: '예약처';

/* ─── intent 분류 (단순 키워드 + 패턴) ─── */
$lower = mb_strtolower($text);
$intent = 'none';
$matched = ['kw' => null, 'trigger_type' => null];

// 1) book.lookup
if (preg_match('/(내\s*예약|예약\s*조회|내\s*예약\s*보여|예약\s*확인)/u', $text)) {
    $intent = 'book.lookup';
}
// 2) book.cancel
elseif (preg_match('/(예약\s*취소|취소해|취소할게)/u', $text)) {
    $intent = 'book.cancel';
}
// 3) book.request (manual trigger)
else {
    $manual = $triggers['manual'] ?? null;
    if ($manual && $manual['enabled']) {
        $kws = array_filter(array_map('trim', explode(',', $manual['keywords'] ?? '')));
        foreach ($kws as $kw) {
            if ($kw !== '' && mb_strpos($lower, mb_strtolower($kw)) !== false) {
                $intent = 'book.request';
                $matched['kw'] = $kw;
                $matched['trigger_type'] = 'manual';
                break;
            }
        }
    }
    // 4) mood.signal
    if ($intent === 'none' && !empty($triggers['mood']) && $triggers['mood']['enabled']) {
        $sigs = array_filter(array_map('trim', explode(',', $triggers['mood']['mood_signals'] ?? '')));
        foreach ($sigs as $sg) {
            if ($sg !== '' && mb_strpos($lower, mb_strtolower($sg)) !== false) {
                $intent = 'mood.signal';
                $matched['kw'] = $sg;
                $matched['trigger_type'] = 'mood';
                break;
            }
        }
    }
    // 5) interest.match
    if ($intent === 'none' && !empty($triggers['interest']) && $triggers['interest']['enabled']) {
        $tags = array_filter(array_map('trim', explode(',', $triggers['interest']['interest_tags'] ?? '')));
        foreach ($tags as $tag) {
            if ($tag !== '' && mb_strpos($lower, mb_strtolower($tag)) !== false) {
                $intent = 'interest.match';
                $matched['kw'] = $tag;
                $matched['trigger_type'] = 'interest';
                break;
            }
        }
    }
}

/* ─── 쿨다운 체크 (mood/interest 트리거는 자주 발동 방지) ─── */
function check_cooldown($db, $sms, $req, $login_id, $type, $hours) {
    if ($hours <= 0) return true;
    $stmt = $db->prepare("SELECT created_at FROM Gn_onechat_reserve_booking 
                          WHERE sms_idx=? AND request_idx=? AND customer_user_id=? AND trigger_type=?
                          ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('iiss', $sms, $req, $login_id, $type);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r) return true;
    return (time() - strtotime($r['created_at'])) >= ($hours * 3600);
}

/* ─── intent별 액션 ─── */
$reply = '';
$uiHint = null;
$action = null;

if ($intent === 'book.lookup') {
    $stmt = $db->prepare("SELECT b.*, c.place_name FROM Gn_onechat_reserve_booking b LEFT JOIN Gn_onechat_reserve_config c ON c.sms_idx=b.sms_idx AND c.request_idx=b.request_idx WHERE b.customer_user_id=? AND b.status IN ('confirmed','pending','done') ORDER BY b.slot_date ASC LIMIT 10");
    $stmt->bind_param('s', $login_id);
    $stmt->execute();
    $bks = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $bks[] = $row;
    $stmt->close();
    if (!$bks) {
        $reply = "아직 예약 내역이 없으세요. 예약하실래요?";
    } else {
        $reply = "📋 예약 내역\n";
        foreach ($bks as $bk) {
            $reply .= "• #{$bk['id']} · " . ($bk['place_name'] ?: '예약처') . " · {$bk['slot_date']} " . substr($bk['slot_time'],0,5) . " · {$bk['headcount']}명 · {$bk['status']}\n";
        }
    }
    $action = ['type' => 'lookup', 'count' => count($bks), 'bookings' => $bks];
    $uiHint = ['show_my_bookings' => true];
}
elseif ($intent === 'book.cancel') {
    $reply = "취소할 예약을 알려주세요. 예약번호(#숫자) 또는 날짜로 말씀해주시면 처리해드릴게요.";
    $uiHint = ['ask_cancel_target' => true];
    $action = ['type' => 'cancel_prompt'];
}
elseif ($intent === 'book.request') {
    // 가용한 다음 슬롯 7일치 조회
    $stmt = $db->prepare("SELECT slot_date, slot_time, capacity, booked FROM Gn_onechat_reserve_slot WHERE sms_idx=? AND request_idx=? AND status='open' AND slot_date >= CURDATE() ORDER BY slot_date ASC, slot_time ASC LIMIT 10");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $slots = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $slots[] = $row;
    $stmt->close();
    $reply = $cfg['bot_intro'] ?: "예약 도와드릴게요.";
    if ($slots) {
        $reply .= "\n\n📅 가능한 시간 (가까운 순)\n";
        foreach (array_slice($slots, 0, 5) as $s) {
            $reply .= "• {$s['slot_date']} " . substr($s['slot_time'],0,5) . " (잔여 " . ($s['capacity'] - $s['booked']) . "명)\n";
        }
        $reply .= "\n원하시는 날짜+시간을 알려주세요. 예: '내일 14:00 2명'";
    } else {
        $reply .= "\n현재 가능한 시간이 없어요. 추후 다시 시도해주세요.";
    }
    $uiHint = ['open_booking_form' => true, 'slots' => $slots];
    $action = ['type' => 'booking_form', 'matched_keyword' => $matched['kw']];
}
elseif ($intent === 'mood.signal') {
    $mood = $triggers['mood'];
    if (!check_cooldown($db, $sms, $req, $login_id, 'mood', (int)$mood['cooldown_hours'])) {
        $intent = 'none';
        $reply = '';
    } else {
        $reason = str_replace('{signal}', $matched['kw'], $mood['reason_template']);
        $msg    = str_replace('{place}',  $place, $mood['message_template']);
        $reply  = $msg . "\n\n💡 *왜 말 걸었나*: " . $reason;
        $uiHint = ['suggest_booking' => true, 'reason' => $reason];
        $action = ['type' => 'mood_suggest', 'reason' => $reason];
    }
}
elseif ($intent === 'interest.match') {
    $itr = $triggers['interest'];
    if (!check_cooldown($db, $sms, $req, $login_id, 'interest', (int)$itr['cooldown_hours'])) {
        $intent = 'none';
        $reply = '';
    } else {
        $reason = str_replace(['{tag}','{place}'], [$matched['kw'], $place], $itr['reason_template']);
        $msg    = str_replace(['{tag}','{place}'], [$matched['kw'], $place], $itr['message_template']);
        $reply  = $msg . "\n\n💡 *왜 말 걸었나*: " . $reason;
        $uiHint = ['suggest_booking' => true, 'reason' => $reason];
        $action = ['type' => 'interest_suggest', 'reason' => $reason];
    }
}

onechat_json([
    'ok' => true,
    'intent' => $intent,
    'matched' => $matched,
    'bot_reply' => $reply,
    'ui_hint' => $uiHint,
    'action' => $action,
]);
