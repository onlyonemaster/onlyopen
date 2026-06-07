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
    // 1순위: 외부 비공개 설정 파일(깃 추적 제외) → OPENAI_API_KEY_OVERRIDE 상수
    if (!defined('OPENAI_API_KEY_OVERRIDE')) {
        $keyFile = __DIR__ . '/openai_key.php';
        if (is_file($keyFile)) { include $keyFile; }
    }

    // 2순위: sms_idx별 DB 키 (챗봇과 동일 소스: Gn_aievent_ms_info.apikey)
    if ($sms_idx > 0) {
        try {
            $dbFile = __DIR__ . '/../../config/database.php';
            if (is_file($dbFile)) {
                require_once $dbFile;
                if (function_exists('getDatabaseConnection')) {
                    $db = getDatabaseConnection();
                    if ($db) {
                        $stmt = $db->prepare('SELECT apikey FROM Gn_aievent_ms_info WHERE sms_idx = ?');
                        if ($stmt) {
                            $stmt->bind_param('i', $sms_idx);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $row = $res ? $res->fetch_assoc() : null;
                            $stmt->close();
                            if ($row && !empty($row['apikey'])) {
                                return $row['apikey'];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[tts_openai] key lookup failed: ' . $e->getMessage());
        }
    }

    // 3순위: 외부 설정 파일이 제공한 공용 기본 키
    return defined('OPENAI_API_KEY_OVERRIDE') ? OPENAI_API_KEY_OVERRIDE : '';
}
