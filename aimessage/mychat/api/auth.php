<?php
/**
 * 마이챗(MyChat) 인증 미들웨어
 * - kiam 세션 로그인 체크
 * - 마이챗 구독 체크
 * - 슈퍼관리자 법적 열람 게이트
 */

// ── onechat auth 재사용 (세션 공유) ──────────────────────────
if (!function_exists('onechat_auth')) {
    require_once __DIR__ . '/../../onechat/api/auth.php';
}
if (!function_exists('getDatabaseConnection')) {
    require_once __DIR__ . '/../../config/database.php';
}

// ── CORS ─────────────────────────────────────────────────────
function mychat_cors(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = ['.kiam.kr', 'localhost'];
    foreach ($allowed as $a) {
        if (str_contains($origin, $a)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type,Authorization');
            break;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}

// ── JSON 응답 ─────────────────────────────────────────────────
function mychat_json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── 구독 플랜별 한도 ──────────────────────────────────────────
function mychat_plan_limits(string $plan): array {
    return [
        'starter'  => ['chat'=>300,  'voice'=>50,   'video'=>10,  'data'=>200,  'decision'=>10,  'report'=>1,  'companion'=>5],
        'standard' => ['chat'=>1000, 'voice'=>300,  'video'=>50,  'data'=>1000, 'decision'=>50,  'report'=>3,  'companion'=>30],
        'pro'      => ['chat'=>3000, 'voice'=>1000, 'video'=>200, 'data'=>5000, 'decision'=>999, 'report'=>999,'companion'=>100],
        'byok'     => ['chat'=>9999, 'voice'=>9999, 'video'=>9999,'data'=>9999, 'decision'=>9999,'report'=>9999,'companion'=>9999],
    ][$plan] ?? ['chat'=>300,'voice'=>50,'video'=>10,'data'=>200,'decision'=>10,'report'=>1,'companion'=>5];
}

// ── 마이챗 인증 + 구독 체크 ───────────────────────────────────
// 반환: ['mem_id'=>..., 'plan_id'=>..., 'api_type'=>..., 'limits'=>[...]]
function mychat_auth(bool $require_sub = true): array {
    $mem_id = onechat_auth(); // 로그인 체크 (미로그인 시 exit)
    $db = getDatabaseConnection();
    $esc = $db->real_escape_string($mem_id);

    $sub = $db->query(
        "SELECT plan_id, api_type, user_api_key_enc, user_api_provider, user_api_model,
                status, expires_at
         FROM mychat_subscriptions
         WHERE mem_id='{$esc}' AND status='active'
           AND (expires_at IS NULL OR expires_at > NOW())
         ORDER BY id DESC LIMIT 1"
    )?->fetch_assoc();

    if ($require_sub && !$sub) {
        mychat_json(['ok'=>false,'error'=>'SUBSCRIPTION_REQUIRED','redirect'=>'/aimessage/mychat/subscribe.html'], 403);
    }

    $plan = $sub['plan_id'] ?? 'free';
    return [
        'mem_id'   => $mem_id,
        'plan_id'  => $plan,
        'api_type' => $sub['api_type'] ?? 'platform',
        'api_key'  => $sub['user_api_key_enc'] ?? null,
        'api_prov' => $sub['user_api_provider'] ?? 'deepseek',
        'api_model'=> $sub['user_api_model'] ?? 'deepseek-chat',
        'limits'   => mychat_plan_limits($plan),
        'has_sub'  => (bool)$sub,
    ];
}

// ── 사용량 체크 및 증가 ───────────────────────────────────────
function mychat_use(string $mem_id, string $type, array $limits): bool {
    $db = getDatabaseConnection();
    $esc = $db->real_escape_string($mem_id);
    $ym  = date('Y-m');
    $col = $type . '_cnt';

    // upsert
    $db->query("INSERT INTO mychat_usage (mem_id, ym, {$col}) VALUES ('{$esc}','{$ym}',1)
                ON DUPLICATE KEY UPDATE {$col}={$col}+1");

    // 한도 체크
    $row = $db->query("SELECT {$col} FROM mychat_usage WHERE mem_id='{$esc}' AND ym='{$ym}'")?->fetch_assoc();
    $used = (int)($row[$col] ?? 0);
    $limit = (int)($limits[$type] ?? 9999);

    return ($used <= $limit);
}

// ── 슈퍼관리자 법적 열람 게이트 ──────────────────────────────
// 국가기관 요청 시 슈퍼관리자 전용 마스터 패스워드로 접근
// 접근 즉시 감사 로그 기록 + 대상자 이메일 통보
define('MYCHAT_LEGAL_MASTER_HASH',
    hash('sha256', 'MC_LEGAL_' . (getenv('MYCHAT_LEGAL_SALT') ?: 'OnlyLegalAccess2026!@#'))
);

function mychat_legal_gate(string $master_pw, string $target_mem, string $legal_doc, string $reason): bool {
    $input_hash = hash('sha256', 'MC_LEGAL_' . $master_pw);
    if (!hash_equals(MYCHAT_LEGAL_MASTER_HASH, $input_hash)) {
        error_log('[MYCHAT-LEGAL] 마스터키 불일치 시도 — IP: ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        return false;
    }

    $db  = getDatabaseConnection();
    $esc_admin  = $db->real_escape_string($_SESSION['iam_member_id'] ?? 'superadmin');
    $esc_mem    = $db->real_escape_string($target_mem);
    $esc_doc    = $db->real_escape_string($legal_doc);
    $esc_reason = $db->real_escape_string($reason);
    $ip         = $db->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');

    // 감사 로그 기록 (영구 보관)
    $db->query("INSERT INTO mychat_legal_access_log
                (admin_id, mem_id, legal_doc_no, reason, ip_address)
                VALUES ('{$esc_admin}','{$esc_mem}','{$esc_doc}','{$esc_reason}','{$ip}')");

    error_log("[MYCHAT-LEGAL] 법적 열람 — admin={$esc_admin} target={$esc_mem} doc={$esc_doc}");
    return true;
}
