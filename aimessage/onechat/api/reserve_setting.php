<?php
/**
 * 원챗(OneChat) 예약 관리 설정 API
 * ─────────────────────────────────────────────────────────────────
 * GET  ?sms_idx=X   → 예약 설정 조회
 * POST {sms_idx, ...} → 예약 설정 저장
 *
 * 설정은 Gn_aievent_ms_info 테이블의 reserve_config (JSON) 컬럼에 저장합니다.
 * 컬럼이 없으면 자동으로 ALTER TABLE로 추가합니다.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();

$login_id = onechat_auth();
$db = getDatabaseConnection();
$login_esc = $db->real_escape_string($login_id);

// reserve_config 컬럼이 없으면 추가
$colCheck = $db->query("SHOW COLUMNS FROM Gn_aievent_ms_info LIKE 'reserve_config'");
if ($colCheck && $colCheck->num_rows === 0) {
    $db->query("ALTER TABLE Gn_aievent_ms_info ADD COLUMN reserve_config TEXT DEFAULT NULL");
}

// ── GET ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sms_idx = (int)($_GET['sms_idx'] ?? 0);

    // sms_idx가 0이면 첫 번째 챗봇을 자동 선택
    if ($sms_idx <= 0) {
        $listR = $db->query("
            SELECT sms_idx, chatbot_name
            FROM Gn_aievent_ms_info
            WHERE customer_id='{$login_esc}'
            ORDER BY sms_idx DESC
            LIMIT 1
        ");
        if ($listR && ($listRow = $listR->fetch_assoc())) {
            $sms_idx = (int)$listRow['sms_idx'];
        }
    }

    if ($sms_idx <= 0) {
        onechat_json(['success' => false, 'error' => '등록된 챗봇이 없습니다.'], 404);
    }

    $r = $db->query("
        SELECT sms_idx, chatbot_name, reserve_config
        FROM Gn_aievent_ms_info
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
        LIMIT 1
    ");
    if (!$r || !($row = $r->fetch_assoc())) {
        onechat_json(['success' => false, 'error' => '챗봇을 찾을 수 없습니다.'], 404);
    }

    $config = json_decode($row['reserve_config'] ?? '{}', true);
    if (!is_array($config)) $config = [];

    // 기본값 설정
    $defaults = [
        'is_enabled'          => false,
        'auto_confirm'        => false,
        'max_per_slot'        => 1,
        'slot_duration'       => 30,
        'open_hour'           => 9,
        'close_hour'          => 18,
        'lunch_start'         => 12,
        'lunch_end'           => 13,
        'lunch_enabled'       => true,
        'work_days'           => [1,2,3,4,5],
        'cancel_notice_hours' => 24,
        'noti_new_reserve'    => true,
        'noti_cancel'         => true,
        'noti_remind'         => true,
        'remind_hours'        => 1,
        'reserve_guide'       => '',
        'reserve_name'        => '예약',
    ];
    foreach ($defaults as $k => $v) {
        if (!array_key_exists($k, $config)) {
            $config[$k] = $v;
        }
    }

    onechat_json([
        'success'  => true,
        'sms_idx'  => (int)$row['sms_idx'],
        'bot_name' => $row['chatbot_name'] ?? '',
        'config'   => $config,
    ]);
}

// ── POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        onechat_json(['success' => false, 'error' => '잘못된 요청입니다.'], 400);
    }

    $sms_idx = (int)($body['sms_idx'] ?? 0);
    if ($sms_idx <= 0) {
        onechat_json(['success' => false, 'error' => 'sms_idx가 필요합니다.'], 400);
    }

    // 소유 확인
    $r = $db->query("
        SELECT sms_idx FROM Gn_aievent_ms_info
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
        LIMIT 1
    ");
    if (!$r || !$r->fetch_assoc()) {
        onechat_json(['success' => false, 'error' => '권한이 없습니다.'], 403);
    }

    // config 추출 (sms_idx 제외)
    $config = $body;
    unset($config['sms_idx']);

    $configJson = $db->real_escape_string(json_encode($config, JSON_UNESCAPED_UNICODE));

    $upd = $db->query("
        UPDATE Gn_aievent_ms_info
        SET reserve_config = '{$configJson}'
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
    ");

    if ($upd) {
        onechat_json(['success' => true, 'message' => '저장되었습니다.']);
    } else {
        onechat_json(['success' => false, 'error' => 'DB 저장 실패: ' . $db->error], 500);
    }
}
