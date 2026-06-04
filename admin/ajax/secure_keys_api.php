<?php
/**
 * Secure API Keys Management
 * --------------------------------------------------
 * /home/secure/{provider}_key.enc 파일을 관리자 화면에서 직접 갱신할 수 있도록 하는
 * 백엔드 핸들러. 모든 키는 base64 인코딩으로 디스크에 저장되며, /home/secure 디렉터리는
 * 웹에서 직접 접근 불가능한 위치이다.
 *
 * 지원 provider:
 *   - openai    → /home/secure/openai_key.enc       (mychat·onechat 음성/이미지)
 *   - deepseek  → /home/secure/deepseek_key.enc     (LLM 백엔드)
 *   - claude    → /home/secure/claude_key.enc       (LLM 백엔드)
 *   - pexels    → /home/secure/pexels_key.enc       (이미지 라이브러리)
 *
 * 액션:
 *   - get_status : 모든 provider의 키 존재 여부 + 마지막 수정시각 + 마스킹된 미리보기
 *   - save_key   : 새 키를 base64 인코딩 후 .enc 로 원자적 저장 (mv 방식)
 *   - test_key   : 저장된 키로 외부 API 호출하여 실제 동작 확인 (openai만 지원)
 *
 * 보안:
 *   - $_SESSION['one_member_admin_id'] 필수
 *   - 키 형식 사전 검증 (provider별 prefix / 길이)
 *   - 저장 후 chmod 600, chown daemon:daemon (가능한 경우)
 *   - 응답에서는 항상 마스킹된 값만 노출
 *
 * 작성일: 2026-06-04 by 아리
 */

include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";

header('Content-Type: application/json; charset=utf-8');

// ───── 인증 ─────
if (!isset($_SESSION['one_member_admin_id']) || empty($_SESSION['one_member_admin_id'])) {
    echo json_encode(['success' => false, 'message' => '관리자 로그인이 필요합니다.']);
    exit;
}

// ───── 설정 ─────
$SECURE_DIR = '/home/secure';
$PROVIDERS = [
    'openai' => [
        'label'      => 'OpenAI',
        'desc'       => 'TTS·이미지·임베딩 등 OpenAI 플랫폼 공용 키',
        'file'       => $SECURE_DIR . '/openai_key.enc',
        'prefixes'   => ['sk-', 'sk-proj-', 'sk-svcacct-'],
        'min_length' => 40,
    ],
    'deepseek' => [
        'label'      => 'DeepSeek',
        'desc'       => '대화·요약 LLM (저렴·빠름)',
        'file'       => $SECURE_DIR . '/deepseek_key.enc',
        'prefixes'   => ['sk-'],
        'min_length' => 30,
    ],
    'claude' => [
        'label'      => 'Claude',
        'desc'       => '고품질 대화·분석 LLM',
        'file'       => $SECURE_DIR . '/claude_key.enc',
        'prefixes'   => ['sk-ant-'],
        'min_length' => 40,
    ],
    'pexels' => [
        'label'      => 'Pexels',
        'desc'       => '무료 이미지 라이브러리',
        'file'       => $SECURE_DIR . '/pexels_key.enc',
        'prefixes'   => [],     // 자유 형식
        'min_length' => 20,
    ],
];

$mode = $_POST['mode'] ?? $_GET['mode'] ?? '';

try {
    switch ($mode) {
        case 'get_status':
            api_get_status($PROVIDERS);
            break;
        case 'save_key':
            api_save_key($PROVIDERS);
            break;
        case 'test_key':
            api_test_key($PROVIDERS);
            break;
        default:
            throw new Exception('알 수 없는 mode: ' . $mode);
    }
} catch (Exception $e) {
    error_log('[secure_keys_api] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ════════════════════════════════════════════════════
//  핸들러
// ════════════════════════════════════════════════════

function api_get_status(array $PROVIDERS): void {
    $out = [];
    foreach ($PROVIDERS as $pid => $cfg) {
        $info = [
            'id'         => $pid,
            'label'      => $cfg['label'],
            'desc'       => $cfg['desc'],
            'file'       => $cfg['file'],
            'exists'     => false,
            'size'       => 0,
            'mtime'      => null,
            'mtime_str'  => '-',
            'preview'    => '',
            'decodable'  => false,
        ];
        if (is_file($cfg['file'])) {
            $info['exists'] = true;
            $info['size']   = filesize($cfg['file']);
            $info['mtime']  = filemtime($cfg['file']);
            $info['mtime_str'] = date('Y-m-d H:i:s', $info['mtime']);

            $raw = @file_get_contents($cfg['file']);
            if ($raw !== false) {
                $dec = base64_decode(trim($raw), true);
                if ($dec !== false && $dec !== '') {
                    $info['decodable'] = true;
                    $info['preview']   = mask_key($dec);
                }
            }
        }
        $out[] = $info;
    }
    echo json_encode(['success' => true, 'providers' => $out]);
}

function api_save_key(array $PROVIDERS): void {
    $pid = $_POST['provider'] ?? '';
    $key = trim((string)($_POST['api_key'] ?? ''));

    if (!isset($PROVIDERS[$pid])) {
        throw new Exception('지원하지 않는 provider: ' . $pid);
    }
    if ($key === '') {
        throw new Exception('API 키를 입력하세요.');
    }
    $cfg = $PROVIDERS[$pid];

    // 형식 검증
    if (strlen($key) < $cfg['min_length']) {
        throw new Exception("키 길이가 너무 짧습니다. (최소 {$cfg['min_length']}자)");
    }
    if (!empty($cfg['prefixes'])) {
        $ok = false;
        foreach ($cfg['prefixes'] as $p) {
            if (strpos($key, $p) === 0) { $ok = true; break; }
        }
        if (!$ok) {
            $allowed = implode(', ', $cfg['prefixes']);
            throw new Exception("{$cfg['label']} 키 형식이 올바르지 않습니다. (접두어: {$allowed})");
        }
    }

    // 원자적 저장: tmp 작성 → chmod → rename
    $target = $cfg['file'];
    $tmp    = $target . '.tmp_' . bin2hex(random_bytes(6));
    $b64    = base64_encode($key);

    if (@file_put_contents($tmp, $b64) === false) {
        throw new Exception('키 파일 쓰기 실패: ' . $target . ' (디렉터리 권한 확인)');
    }
    @chmod($tmp, 0600);
    // 가능한 경우 daemon 소유로 (실패해도 무시 — 일부 환경에서 권한 부족)
    @chown($tmp, 'daemon');
    @chgrp($tmp, 'daemon');

    // 백업 (기존 키가 있으면)
    if (is_file($target)) {
        $bk = $target . '.bak_' . date('Ymd_His');
        @copy($target, $bk);
        @chmod($bk, 0600);
    }
    if (!@rename($tmp, $target)) {
        @unlink($tmp);
        throw new Exception('키 파일 교체 실패: ' . $target);
    }
    @chmod($target, 0600);

    // 검증: 다시 읽어서 base64 디코드 시 원본과 일치
    $verify_raw = @file_get_contents($target);
    $verify_dec = ($verify_raw !== false) ? base64_decode(trim($verify_raw), true) : false;
    $verified   = ($verify_dec === $key);

    error_log("[secure_keys_api] save_key OK provider={$pid} admin={$_SESSION['one_member_admin_id']} len=" . strlen($key));

    echo json_encode([
        'success'  => true,
        'message'  => $cfg['label'] . ' 키가 저장되었습니다.',
        'provider' => $pid,
        'preview'  => mask_key($key),
        'verified' => $verified,
        'mtime_str'=> date('Y-m-d H:i:s'),
    ]);
}

function api_test_key(array $PROVIDERS): void {
    $pid = $_POST['provider'] ?? '';
    if (!isset($PROVIDERS[$pid])) {
        throw new Exception('지원하지 않는 provider: ' . $pid);
    }
    $cfg = $PROVIDERS[$pid];
    if (!is_file($cfg['file'])) {
        throw new Exception('키 파일이 없습니다. 먼저 저장하세요.');
    }
    $raw = @file_get_contents($cfg['file']);
    $key = ($raw !== false) ? base64_decode(trim($raw), true) : false;
    if (!$key) {
        throw new Exception('키 파일 디코딩 실패. 다시 저장해주세요.');
    }

    switch ($pid) {
        case 'openai':
            test_openai($key);
            break;
        case 'deepseek':
            test_deepseek($key);
            break;
        case 'claude':
            test_claude($key);
            break;
        case 'pexels':
            test_pexels($key);
            break;
        default:
            throw new Exception('테스트 미지원 provider: ' . $pid);
    }
}

// ════════════════════════════════════════════════════
//  외부 API 테스트
// ════════════════════════════════════════════════════

function test_openai(string $key): void {
    $ch = curl_init('https://api.openai.com/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        $data = json_decode($body, true);
        $n = is_array($data['data'] ?? null) ? count($data['data']) : 0;
        echo json_encode(['success' => true, 'message' => "OpenAI OK (model 목록 {$n}개 응답)"]);
    } else {
        $err = '';
        $j = json_decode($body, true);
        if (isset($j['error']['message'])) $err = ' - ' . $j['error']['message'];
        echo json_encode(['success' => false, 'message' => "OpenAI 응답 HTTP {$code}{$err}"]);
    }
}

function test_deepseek(string $key): void {
    $ch = curl_init('https://api.deepseek.com/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        echo json_encode(['success' => true, 'message' => 'DeepSeek OK (model 목록 응답)']);
    } else {
        echo json_encode(['success' => false, 'message' => "DeepSeek 응답 HTTP {$code}"]);
    }
}

function test_claude(string $key): void {
    // Claude 는 /v1/messages 만 공개되어 있음 → 최소 페이로드로 ping
    $payload = json_encode([
        'model'      => 'claude-3-haiku-20240307',
        'max_tokens' => 5,
        'messages'   => [['role' => 'user', 'content' => 'ping']],
    ]);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        echo json_encode(['success' => true, 'message' => 'Claude OK (ping 응답)']);
    } else {
        $err = '';
        $j = json_decode($body, true);
        if (isset($j['error']['message'])) $err = ' - ' . $j['error']['message'];
        echo json_encode(['success' => false, 'message' => "Claude 응답 HTTP {$code}{$err}"]);
    }
}

function test_pexels(string $key): void {
    $ch = curl_init('https://api.pexels.com/v1/search?query=cat&per_page=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $key],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        echo json_encode(['success' => true, 'message' => 'Pexels OK']);
    } else {
        echo json_encode(['success' => false, 'message' => "Pexels 응답 HTTP {$code}"]);
    }
}

// ════════════════════════════════════════════════════
//  유틸
// ════════════════════════════════════════════════════

function mask_key(string $k): string {
    $len = strlen($k);
    if ($len <= 12) return str_repeat('*', $len);
    return substr($k, 0, 8) . '...' . substr($k, -4) . " (len={$len})";
}
