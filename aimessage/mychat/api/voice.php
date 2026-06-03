<?php
/**
 * 마이챗 음성 API (v2 — include 충돌 제거, 키 헬퍼화)
 * action=stt  : 음성→텍스트 (Whisper)
 * action=tts  : 텍스트→음성 (OpenAI TTS)
 * action=chat : 음성/텍스트 → AI → 음성 (한 요청에서 STT+AI+TTS)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'tts');

// ── 아바타 목소리 ────────────────────────────────────────────
function mychat_voice_id($db, $esc): string {
    $av = $db->query("SELECT voice_preset_id FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    $v = $av['voice_preset_id'] ?? 'nova';
    return in_array($v, ['alloy','echo','fable','onyx','nova','shimmer'], true) ? $v : 'nova';
}

// ── Whisper STT ──────────────────────────────────────────────
function mychat_whisper($tmpPath, $mime, $apiKey): string {
    if (!$apiKey) return '';
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_POSTFIELDS     => [
            'file'     => new CURLFile($tmpPath, $mime ?: 'audio/webm', 'voice.webm'),
            'model'    => 'whisper-1',
            'language' => 'ko',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($resp, true);
    return $d['text'] ?? '';
}

// ── OpenAI TTS ───────────────────────────────────────────────
function mychat_tts_audio($text, $voice, $apiKey): string {
    if (!$apiKey) return '';
    $payload = json_encode(['model'=>'tts-1','voice'=>$voice,'input'=>mb_substr($text,0,1000),'response_format'=>'mp3']);
    $resp = mychat_http_post('https://api.openai.com/v1/audio/speech',
        ["Authorization: Bearer {$apiKey}", 'Content-Type: application/json'], $payload, 25);
    // mychat_http_post 는 본문 문자열 반환 (오디오 바이너리)
    return $resp;
}

// ════════════════════════════════════════════════════════════
// STT
// ════════════════════════════════════════════════════════════
if ($action === 'stt') {
    $file = $_FILES['audio'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) mychat_json(['ok'=>false,'error'=>'음성 파일 없음'], 400);
    if (!mychat_use($mem_id, 'voice', $user['limits'])) mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED'], 429);

    $apiKey = mychat_openai_key($user);
    $text   = mychat_whisper($file['tmp_name'], $file['type'], $apiKey);
    if ($text === '') mychat_json(['ok'=>false,'error'=>'STT 실패','fallback'=>'webkitSpeechRecognition']);
    mychat_json(['ok'=>true, 'text'=>$text]);
}

// ════════════════════════════════════════════════════════════
// TTS
// ════════════════════════════════════════════════════════════
if ($action === 'tts') {
    $text = mb_substr(trim($_POST['text'] ?? ''), 0, 1000);
    if ($text === '') mychat_json(['ok'=>false,'error'=>'텍스트 없음'], 400);

    $apiKey = mychat_openai_key($user);
    $voice  = mychat_voice_id($db, $esc);
    $audio  = mychat_tts_audio($text, $voice, $apiKey);
    if ($audio === '') mychat_json(['ok'=>false,'error'=>'TTS 생성 실패'], 503);

    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    header('Cache-Control: no-cache');
    echo $audio;
    exit;
}

// ════════════════════════════════════════════════════════════
// CHAT 파이프라인: (음성 or 텍스트) → AI → 음성
// ════════════════════════════════════════════════════════════
if ($action === 'chat') {
    // 1) 입력 텍스트 확보 (음성이면 STT)
    $userText  = '';
    $audioFile = $_FILES['audio'] ?? null;
    $openaiKey = mychat_openai_key($user);

    if ($audioFile && $audioFile['error'] === UPLOAD_ERR_OK) {
        $userText = mychat_whisper($audioFile['tmp_name'], $audioFile['type'], $openaiKey);
    } else {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $userText = trim($body['text'] ?? $_POST['text'] ?? '');
    }
    if ($userText === '') mychat_json(['ok'=>false,'error'=>'음성 또는 텍스트 입력 없음'], 400);

    // 2) quota (voice)
    if (!mychat_use($mem_id, 'voice', $user['limits'])) mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED'], 429);

    // 3) 아바타 + 데이터 컨텍스트 (chat.php와 동일 로직 간소판)
    $av = $db->query("SELECT avatar_name, persona_prompt FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    $avatarName = $av['avatar_name'] ?? '나의 아바타';
    $persona    = trim($av['persona_prompt'] ?? '');

    $dataCtx = [];
    $rr = $db->query("SELECT category, title, LEFT(content_text,400) AS txt FROM mychat_data_pool
                      WHERE mem_id='{$esc}' AND is_deleted=0 ORDER BY id DESC LIMIT 5");
    while ($r = $rr?->fetch_assoc()) $dataCtx[] = "[{$r['category']}] " . ($r['title']?$r['title'].': ':'') . trim($r['txt']);

    $sys  = "당신은 {$mem_id}님의 개인 AI 아바타 '{$avatarName}'입니다. 친밀하고 간결하게(3문장 이내) 음성 대화하세요.\n";
    if ($persona) $sys .= "[말투]\n{$persona}\n";
    if ($dataCtx) $sys .= "[내 데이터]\n" . implode("\n", $dataCtx) . "\n";

    // 4) AI 호출
    $ai = mychat_ai_reply($sys, $userText, 400, $user);
    if (!$ai['ok']) mychat_json(['ok'=>false,'error'=>$ai['error'] ?? 'AI 응답 실패'], 503);
    $aiReply = $ai['content'];

    // 5) 이력 저장
    $eu = $db->real_escape_string($userText);
    $er = $db->real_escape_string($aiReply);
    $db->query("INSERT INTO mychat_chat_history (mem_id,role,content) VALUES ('{$esc}','user','{$eu}')");
    $db->query("INSERT INTO mychat_chat_history (mem_id,role,content) VALUES ('{$esc}','assistant','{$er}')");

    // 6) TTS (실패해도 텍스트는 반환)
    $voice = mychat_voice_id($db, $esc);
    $audio = mychat_tts_audio($aiReply, $voice, $openaiKey);
    mychat_json([
        'ok'           => true,
        'input_text'   => $userText,
        'text'         => $aiReply,
        'audio_base64' => $audio !== '' ? base64_encode($audio) : null,
    ]);
}

mychat_json(['ok'=>false,'error'=>'Unknown action'], 400);
