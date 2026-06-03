<?php
/**
 * OpenAI TTS 프록시
 * POST JSON: { text, voice, lang, sms_idx }
 * 응답: audio/mpeg 스트림
 */
if (php_sapi_name() === 'cli') exit;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$text  = trim($body['text'] ?? '');
$voice = in_array($body['voice'] ?? '', ['alloy','echo','fable','onyx','nova','shimmer'])
       ? $body['voice'] : 'nova';

if (!$text) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'text 필수']);
    exit;
}

// sms_idx로 API 키 조회 (vtdb config 캐시)
$sms_idx  = (int)($body['sms_idx'] ?? 0);
$apiKey   = getOpenAIKey($sms_idx);
if (!$apiKey) {
    header('Content-Type: application/json');
    http_response_code(503);
    echo json_encode(['error' => 'OpenAI API 키 미설정']);
    exit;
}

// 긴 텍스트 자르기 (TTS 한 번에 4096자 제한)
$text = mb_substr($text, 0, 4096);

$payload = json_encode(['model'=>'tts-1','input'=>$text,'voice'=>$voice]);
$ch = curl_init('https://api.openai.com/v1/audio/speech');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$audio = curl_exec($ch);
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($code === 200 && str_contains($ctype, 'audio')) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    echo $audio;
} else {
    header('Content-Type: application/json');
    http_response_code($code ?: 500);
    $err = json_decode($audio, true);
    echo json_encode(['error' => $err['error']['message'] ?? 'TTS 실패']);
}

function getOpenAIKey(int $sms_idx): string {
    // vt.kiam.kr API로 설정 조회
    $url = 'https://vt.kiam.kr/api/v1/voice/config.php?sms_idx=' . $sms_idx;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['X-API-Key: vt_onechat_9afdc815132cae0174f5b5109a658db3'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    // 공개 API는 키를 마스킹해서 반환하므로 — 내부 전용 키 사용
    // [SECURITY 2026-06-04] 하드코딩 제거 — /home/secure/openai_key.enc 자동 로드
    if (defined('OPENAI_API_KEY_OVERRIDE')) return OPENAI_API_KEY_OVERRIDE;
    return onechat_load_platform_openai_key();
}

/**
 * 플랫폼 공용 OpenAI 키 로더 (/home/secure/openai_key.enc, base64)
 */
function onechat_load_platform_openai_key(): string {
    static $cached = null;
    if ($cached !== null) return $cached;
    $f = '/home/secure/openai_key.enc';
    if (!is_readable($f)) {
        error_log('[onechat tts_openai] /home/secure/openai_key.enc 읽기 불가');
        return $cached = '';
    }
    $raw = trim((string)@file_get_contents($f));
    if ($raw === '') return $cached = '';
    $dec = base64_decode($raw, true);
    if ($dec === false || $dec === '') {
        error_log('[onechat tts_openai] base64 디코딩 실패');
        return $cached = '';
    }
    return $cached = trim($dec);
}
