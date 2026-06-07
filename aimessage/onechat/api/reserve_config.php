<?php
/**
 * 원챗 예약관리 — Config API
 * GET  ?sms_idx=&request_idx=               → 운영자 기본설정 조회 (없으면 자동 기본값 생성)
 * POST {sms_idx, request_idx, ...fields}    → 기본설정 저장 + 슬롯 자동 재생성
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    [$sms, $req] = reserve_owner_ctx();
    $cfg = reserve_get_or_create_config($db, $sms, $req);

    // blackout도 함께
    $stmt = $db->prepare("SELECT * FROM Gn_onechat_reserve_blackout WHERE sms_idx=? AND request_idx=? AND active=1 ORDER BY id ASC");
    $stmt->bind_param('ii', $sms, $req);
    $stmt->execute();
    $bos = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $bos[] = $row;
    $stmt->close();

    onechat_json(['ok' => true, 'config' => $cfg, 'blackouts' => $bos]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = reserve_body();
    [$sms, $req] = reserve_owner_ctx($body);
    reserve_get_or_create_config($db, $sms, $req); // 행 보장

    // 화이트리스트 필드만 업데이트
    $fields = [
        'enabled'           => 'i',
        'work_start'        => 's',
        'work_end'          => 's',
        'work_days'         => 's',
        'slot_minutes'      => 'i',
        'capacity'          => 'i',
        'lead_time_min'     => 'i',
        'max_advance_days'  => 'i',
        'cancel_until_hours'=> 'i',
        'bot_intro'         => 's',
        'owner_name'        => 's',
        'place_name'        => 's',
        'place_address'     => 's',
        'place_phone'       => 's',
    ];
    $sets = [];
    $types = '';
    $vals  = [];
    foreach ($fields as $f => $t) {
        if (!array_key_exists($f, $body)) continue;
        $sets[] = "`$f`=?";
        $types .= $t;
        $vals[] = $t === 'i' ? (int)$body[$f] : (string)$body[$f];
    }
    if ($sets) {
        $sql = "UPDATE Gn_onechat_reserve_config SET " . implode(',', $sets) . " WHERE sms_idx=? AND request_idx=?";
        $types .= 'ii';
        $vals[] = $sms; $vals[] = $req;
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt->close();
    }

    // 슬롯 재생성 (config 변경 시 향후 신규 슬롯 반영)
    $regen = reserve_generate_slots($db, $sms, $req, 30);

    $cfg = reserve_get_or_create_config($db, $sms, $req);
    onechat_json(['ok' => true, 'config' => $cfg, 'slot_regen' => $regen]);
}

onechat_json(['error' => 'METHOD_NOT_ALLOWED'], 405);
