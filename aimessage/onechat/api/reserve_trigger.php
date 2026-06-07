<?php
/**
 * 예약 트리거 규칙 (4종)
 * GET  ?sms_idx=&request_idx=                    → 4개 row (없으면 자동 생성)
 * POST {sms_idx, request_idx, triggers:[...]}    → 일괄 업데이트
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$m = $_SERVER['REQUEST_METHOD'];

$DEFAULTS = [
    'manual'    => ['enabled' => 1, 'keywords' => '예약,예약하고싶어,예약가능', 'cooldown_hours' => 0,
                    'reason_template' => '고객님이 "{kw}"라고 직접 요청하셨습니다.',
                    'message_template' => '{place} 예약 도와드릴게요. 원하시는 날짜와 시간 알려주세요.'],
    'mood'      => ['enabled' => 1, 'mood_signals' => '놀러가고싶다,바람쐬고싶다,쉬고싶다,가고싶다,심심해',
                    'cooldown_hours' => 24,
                    'reason_template' => '대화에서 "{signal}" 분위기가 감지됐어요.',
                    'message_template' => '혹시 {place} 한번 어떠세요? 편하실 때 예약 도와드려요.'],
    'companion' => ['enabled' => 0, 'companion_periodic_days' => 14, 'cooldown_hours' => 72,
                    'reason_template' => '지난 방문 {days}일 됐어요. AI동행이 알려드리는 자연스러운 타이밍.',
                    'message_template' => '오랜만이에요. 이번 주말 시간 어떠세요?'],
    'interest'  => ['enabled' => 1, 'interest_tags' => '카페,공방,체험,모임', 'cooldown_hours' => 48,
                    'reason_template' => '관심사 "{tag}"와 {place}가 매칭됐어요.',
                    'message_template' => '{tag} 좋아하시는 분께 {place} 추천드려요. 예약 도와드릴까요?'],
];

function ensure_trigger_defaults($db, $sms, $req, $DEFAULTS) {
    $stmt = $db->prepare("SELECT trigger_type FROM Gn_onechat_reserve_trigger WHERE sms_idx=? AND request_idx=?");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $exists = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $exists[] = $row['trigger_type'];
    $stmt->close();
    foreach ($DEFAULTS as $type => $d) {
        if (in_array($type, $exists, true)) continue;
        $ins = $db->prepare("INSERT INTO Gn_onechat_reserve_trigger (sms_idx, request_idx, trigger_type, enabled, keywords, mood_signals, interest_tags, companion_periodic_days, cooldown_hours, reason_template, message_template) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $en = (int)($d['enabled'] ?? 1);
        $kw = $d['keywords'] ?? null;
        $ms = $d['mood_signals'] ?? null;
        $it = $d['interest_tags'] ?? null;
        $pd = isset($d['companion_periodic_days']) ? (int)$d['companion_periodic_days'] : null;
        $cd = (int)($d['cooldown_hours'] ?? 24);
        $rt = $d['reason_template']  ?? '';
        $mt = $d['message_template'] ?? '';
        $ins->bind_param('iisisssiiss', $sms, $req, $type, $en, $kw, $ms, $it, $pd, $cd, $rt, $mt);
        $ins->execute();
        $ins->close();
    }
}

if ($m === 'GET') {
    [$sms, $req] = reserve_owner_ctx();
    ensure_trigger_defaults($db, $sms, $req, $DEFAULTS);
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_trigger WHERE sms_idx=? AND request_idx=? ORDER BY FIELD(trigger_type,'manual','mood','companion','interest')");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $rows = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    onechat_json(['ok' => true, 'triggers' => $rows]);
}

if ($m === 'POST') {
    $b = reserve_body();
    [$sms, $req] = reserve_owner_ctx($b);
    ensure_trigger_defaults($db, $sms, $req, $DEFAULTS);
    $list = $b['triggers'] ?? [];
    if (!is_array($list)) onechat_json(['error' => 'triggers 배열 필수'], 400);
    $updated = 0;
    foreach ($list as $t) {
        if (empty($t['trigger_type'])) continue;
        $type = $t['trigger_type'];
        if (!isset($DEFAULTS[$type])) continue;
        $stmt = $db->prepare("UPDATE Gn_onechat_reserve_trigger SET enabled=?, keywords=?, mood_signals=?, interest_tags=?, companion_periodic_days=?, cooldown_hours=?, reason_template=?, message_template=? WHERE sms_idx=? AND request_idx=? AND trigger_type=?");
        $en = (int)($t['enabled'] ?? 0);
        $kw = $t['keywords']      ?? null;
        $ms = $t['mood_signals']  ?? null;
        $it = $t['interest_tags'] ?? null;
        $pd = isset($t['companion_periodic_days']) && $t['companion_periodic_days'] !== '' ? (int)$t['companion_periodic_days'] : null;
        $cd = (int)($t['cooldown_hours'] ?? 24);
        $rt = $t['reason_template']  ?? '';
        $mt = $t['message_template'] ?? '';
        $stmt->bind_param('isssiissiis', $en, $kw, $ms, $it, $pd, $cd, $rt, $mt, $sms, $req, $type);
        $stmt->execute();
        $updated += $stmt->affected_rows;
        $stmt->close();
    }
    onechat_json(['ok' => true, 'updated' => $updated]);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
