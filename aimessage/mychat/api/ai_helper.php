<?php
/**
 * 마이챗 공용 AI 헬퍼
 * - 플랫폼 DeepSeek: /home/kiam/lib/deepseek_api.php 의 DeepSeekAPI 클래스 사용
 *   (키는 /home/secure/deepseek_key.enc 에서 클래스가 자동 로드. .env 사용 안 함)
 * - BYOK: 사용자 등록 키(OpenAI/DeepSeek/Claude/Gemini) 직접 호출
 * - OpenAI 키(TTS/Whisper/DALL-E): onechat 하드코딩 키 재사용 또는 BYOK
 *
 * 모든 AI 호출은 이 헬퍼를 경유한다. (.env 의존 전면 제거)
 */

require_once __DIR__ . '/../../../lib/deepseek_api.php';  // DeepSeekAPI 클래스 (/home/kiam/lib/)

if (!defined('MYCHAT_ENC_SALT')) define('MYCHAT_ENC_SALT', 'MYCHAT_ENC_SALT_2026');

/**
 * BYOK 키 복호화 (AES-256-CBC, salt = mem_id + MYCHAT_ENC_SALT)
 */
function mychat_decrypt_key(?string $enc, string $mem_id): string {
    if (!$enc) return '';
    $key  = hash('sha256', $mem_id . MYCHAT_ENC_SALT, true);
    $data = base64_decode($enc);
    if ($data === false || strlen($data) < 17) return '';
    $iv  = substr($data, 0, 16);
    $out = openssl_decrypt(substr($data, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $out ?: '';
}

/**
 * BYOK 키 암호화
 */
function mychat_encrypt_key(string $raw, string $mem_id): string {
    $key = hash('sha256', $mem_id . MYCHAT_ENC_SALT, true);
    $iv  = openssl_random_pseudo_bytes(16);
    $enc = openssl_encrypt($raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}

/**
 * OpenAI 키 조달 (TTS/Whisper/DALL-E 용)
 * - $user 가 BYOK openai 이면 복호화 키 우선
 * - 아니면 플랫폼 공용 키 (/home/secure/openai_key.enc, base64 인코딩)
 *
 * [SECURITY 2026-06-04] 하드코딩 제거 — /home/secure/openai_key.enc 자동 로드.
 *   관리자 페이지(admin/api/secure_keys.php)에서 키 갱신 가능.
 *   .env 의존 없음. 권한 600 (daemon:daemon).
 */
function mychat_openai_key(?array $user = null): string {
    // 1) BYOK 우선
    if ($user && ($user['api_type'] ?? '') === 'user_key'
        && ($user['api_prov'] ?? '') === 'openai' && !empty($user['api_key'])) {
        $k = mychat_decrypt_key($user['api_key'], $user['mem_id']);
        if ($k) return $k;
    }
    // 2) 코드 정의 override (개발용 — 정의 안 함이 정상)
    if (defined('OPENAI_API_KEY_OVERRIDE')) return OPENAI_API_KEY_OVERRIDE;
    // 3) 플랫폼 공용 키 (/home/secure/openai_key.enc — base64 디코딩)
    return mychat_load_platform_openai_key();
}

/**
 * 플랫폼 공용 OpenAI 키 로더 (정적 캐시)
 * @return string  실패 시 '' (호출 측에서 빈 키 처리)
 */
function mychat_load_platform_openai_key(): string {
    static $cached = null;
    if ($cached !== null) return $cached;
    $f = '/home/secure/openai_key.enc';
    if (!is_readable($f)) {
        error_log('[mychat_openai_key] /home/secure/openai_key.enc 읽기 불가');
        return $cached = '';
    }
    $raw = trim((string)@file_get_contents($f));
    if ($raw === '') return $cached = '';
    $dec = base64_decode($raw, true);
    if ($dec === false || $dec === '') {
        error_log('[mychat_openai_key] base64 디코딩 실패');
        return $cached = '';
    }
    return $cached = trim($dec);
}

/**
 * 핵심: AI 답변 생성 (단일 진입점)
 *
 * @param string     $system   시스템 프롬프트
 * @param string     $content  유저 메시지 (필요 시 대화 이력을 system에 직조해 전달)
 * @param int        $maxTokens
 * @param array|null $user     mychat_auth() 반환 배열 (BYOK 분기용). null이면 플랫폼.
 * @return array ['ok'=>bool, 'content'=>string, 'error'=>string|null]
 */
function mychat_ai_reply(string $system, string $content, int $maxTokens = 800, ?array $user = null): array {
    if ($content === '') return ['ok'=>false, 'content'=>'', 'error'=>'빈 입력'];

    $apiType = $user['api_type'] ?? 'platform';

    // ── BYOK 경로 ──────────────────────────────────────────────
    if ($apiType === 'user_key' && !empty($user['api_key'])) {
        $prov  = $user['api_prov']  ?? 'deepseek';
        $model = $user['api_model'] ?? 'deepseek-chat';
        $key   = mychat_decrypt_key($user['api_key'], $user['mem_id']);
        if (!$key) return ['ok'=>false, 'content'=>'', 'error'=>'BYOK_KEY_INVALID'];
        return mychat_byok_call($prov, $model, $key, $system, $content, $maxTokens);
    }

    // ── 플랫폼 경로: DeepSeekAPI 클래스 ────────────────────────
    try {
        $deepseek = new DeepSeekAPI();
        if (method_exists($deepseek, 'set_max_tokens')) $deepseek->set_max_tokens($maxTokens);
        $res = $deepseek->generate_manual(['system' => $system, 'content' => $content]);
        $text = $res['content'] ?? '';
        if ($text === '') return ['ok'=>false, 'content'=>'', 'error'=>'AI 응답 없음'];
        return ['ok'=>true, 'content'=>$text, 'error'=>null];
    } catch (Throwable $e) {
        error_log('[mychat_ai_reply] DeepSeek 오류: ' . $e->getMessage());
        return ['ok'=>false, 'content'=>'', 'error'=>'AI 호출 실패: ' . $e->getMessage()];
    }
}

/**
 * BYOK provider별 호출
 */
function mychat_byok_call(string $prov, string $model, string $key, string $system, string $content, int $maxTokens): array {
    if ($prov === 'claude') {
        // Anthropic Messages API
        $url = 'https://api.anthropic.com/v1/messages';
        $payload = json_encode([
            'model'      => $model ?: 'claude-sonnet-4-6',
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => [['role'=>'user','content'=>$content]],
        ], JSON_UNESCAPED_UNICODE);
        $headers = ["x-api-key: {$key}", 'anthropic-version: 2023-06-01', 'Content-Type: application/json'];
        $resp = mychat_http_post($url, $headers, $payload, 40);
        $d = json_decode($resp, true);
        $text = $d['content'][0]['text'] ?? '';
        return $text ? ['ok'=>true,'content'=>$text,'error'=>null]
                     : ['ok'=>false,'content'=>'','error'=>'Claude 응답 파싱 실패'];
    }

    if ($prov === 'gemini') {
        $m = $model ?: 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key={$key}";
        $payload = json_encode([
            'system_instruction' => ['parts'=>[['text'=>$system]]],
            'contents' => [['role'=>'user','parts'=>[['text'=>$content]]]],
            'generationConfig' => ['maxOutputTokens'=>$maxTokens],
        ], JSON_UNESCAPED_UNICODE);
        $resp = mychat_http_post($url, ['Content-Type: application/json'], $payload, 40);
        $d = json_decode($resp, true);
        $text = $d['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $text ? ['ok'=>true,'content'=>$text,'error'=>null]
                     : ['ok'=>false,'content'=>'','error'=>'Gemini 응답 파싱 실패'];
    }

    // openai / deepseek (OpenAI 호환 chat/completions)
    $url = ($prov === 'openai')
        ? 'https://api.openai.com/v1/chat/completions'
        : 'https://api.deepseek.com/v1/chat/completions';
    $payload = json_encode([
        'model'       => $model ?: ($prov==='openai' ? 'gpt-4o-mini' : 'deepseek-chat'),
        'messages'    => [['role'=>'system','content'=>$system],['role'=>'user','content'=>$content]],
        'max_tokens'  => $maxTokens,
        'temperature' => 0.7,
    ], JSON_UNESCAPED_UNICODE);
    $headers = ["Authorization: Bearer {$key}", 'Content-Type: application/json'];
    $resp = mychat_http_post($url, $headers, $payload, 40);
    $d = json_decode($resp, true);
    $text = $d['choices'][0]['message']['content'] ?? '';
    return $text ? ['ok'=>true,'content'=>$text,'error'=>null]
                 : ['ok'=>false,'content'=>'','error'=>'BYOK 응답 파싱 실패'];
}

/**
 * 공용 HTTP POST (curl)
 */
function mychat_http_post(string $url, array $headers, string $payload, int $timeout = 30): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) error_log('[mychat_http_post] ' . $err);
    return $resp ?: '';
}
