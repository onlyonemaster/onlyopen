<?php
/**
 * 마이챗 설정 API
 * GET  → 현재 설정 조회
 * POST → 저장 (action: save_all | pin | cancel_sub)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

mychat_cors();
$user = mychat_auth(false); // 구독 없어도 조회 가능
$mem_id = $user['mem_id'];
$db = getDatabaseConnection();
$esc = $db->real_escape_string($mem_id);

// ── GET: 설정 조회 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $avatar = $db->query("SELECT avatar_name, persona_prompt, data_storage_mode, status FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    $sub    = $db->query("SELECT plan_id, api_type, user_api_provider, user_api_model, status, expires_at FROM mychat_subscriptions WHERE mem_id='{$esc}' AND status='active' ORDER BY id DESC LIMIT 1")?->fetch_assoc();

    mychat_json(['ok'=>true, 'avatar'=>$avatar, 'subscription'=>$sub]);
}

// ── POST: 설정 저장 ───────────────────────────────────────────
$b = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $b['action'] ?? 'save_all';

// PIN 변경
if ($action === 'pin') {
    $pin = preg_replace('/\D/', '', $b['pin'] ?? '');
    if (strlen($pin) !== 4) mychat_json(['ok'=>false,'error'=>'PIN은 4자리여야 합니다'], 400);
    $hash = hash('sha256', $mem_id . $pin . 'MYCHAT_PIN_2026');
    $db->query("UPDATE mychat_avatar SET pin_hash='{$db->real_escape_string($hash)}' WHERE mem_id='{$esc}'");
    if (!$db->affected_rows) {
        $db->query("INSERT IGNORE INTO mychat_avatar (mem_id, pin_hash) VALUES ('{$esc}','{$db->real_escape_string($hash)}')");
    }
    mychat_json(['ok'=>true]);
}

// Panic Lock — 모든 외부 공개 데이터 즉시 비공개 전환
if ($action === 'panic_lock') {
    $db->query("UPDATE mychat_data_pool SET scope='private' WHERE mem_id='{$esc}' AND scope != 'private'");
    $db->query("UPDATE mychat_avatar SET panic_lock=1, updated_at=NOW() WHERE mem_id='{$esc}'");
    mychat_json(['ok'=>true, 'msg'=>'모든 외부 공개 데이터가 비공개 처리되었습니다.']);
}
if ($action === 'panic_unlock') {
    $db->query("UPDATE mychat_avatar SET panic_lock=0, updated_at=NOW() WHERE mem_id='{$esc}'");
    mychat_json(['ok'=>true]);
}

// 구독 해지

if ($action === "cancel_sub") {
    $db->query("UPDATE mychat_subscriptions SET status='cancelled' WHERE mem_id='{$esc}' AND status='active'");
    mychat_json(['ok'=>true]);
}

// 전체 저장 (save_all)
$name    = mb_substr(trim($b['avatar_name']      ?? '나의 아바타'), 0, 30);
$persona = mb_substr(trim($b['persona_prompt']   ?? ''), 0, 2000);
$storage = in_array($b['data_storage_mode'] ?? '', ['device','server','hybrid']) ? $b['data_storage_mode'] : 'server';
$apiKey  = trim($b['api_key']     ?? '');
$apiProv = trim($b['api_provider'] ?? 'deepseek');
$apiMdl  = trim($b['api_model']   ?? 'deepseek-chat');

// 아바타 upsert
$esc_name    = $db->real_escape_string($name);
$esc_persona = $db->real_escape_string($persona);
$esc_storage = $db->real_escape_string($storage);
$db->query("INSERT INTO mychat_avatar (mem_id, avatar_name, persona_prompt, data_storage_mode)
            VALUES ('{$esc}','{$esc_name}','{$esc_persona}','{$esc_storage}')
            ON DUPLICATE KEY UPDATE
              avatar_name='{$esc_name}', persona_prompt='{$esc_persona}',
              data_storage_mode='{$esc_storage}'");

// API키 업데이트 (있을 때만)
if ($apiKey) {
    $encKey  = mychat_encrypt_key($apiKey, $mem_id);
    $esc_enc = $db->real_escape_string($encKey);
    $esc_prov= $db->real_escape_string($apiProv);
    $esc_mdl = $db->real_escape_string($apiMdl);
    $db->query("UPDATE mychat_subscriptions
                SET api_type='user_key', user_api_key_enc='{$esc_enc}',
                    user_api_provider='{$esc_prov}', user_api_model='{$esc_mdl}'
                WHERE mem_id='{$esc}' AND status='active'");
}

mychat_json(['ok'=>true]);

// ── 암호화 헬퍼 ──────────────────────────────────────────────
function mychat_encrypt_key(string $raw, string $mem_id): string {
    $key = hash('sha256', $mem_id . 'MYCHAT_ENC_SALT_2026', true);
    $iv  = openssl_random_pseudo_bytes(16);
    $enc = openssl_encrypt($raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}
