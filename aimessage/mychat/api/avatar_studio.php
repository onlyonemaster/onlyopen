<?php
/**
 * 마이챗 아바타 스튜디오 API
 * - 얼굴 이미지 업로드·저장
 * - 목소리 프리셋 선택 / 녹음 클론
 * - HeyGen v3 API 연동 (선택·프로 플랜)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET 라우팅 ────────────────────────────────────────────────
if ($method === 'GET') {
    if ($action === 'heygen_status') { heygenStatus($db, $esc); }
    if ($action === 'video_status')  { videoStatus($db, $esc);  }
    mychat_json(['ok'=>false,'error'=>'Unknown action'], 400);
}

// ── POST 라우팅 ───────────────────────────────────────────────
if ($method === 'POST') {
    // multipart 판단
    $isMultipart = strpos($_SERVER['CONTENT_TYPE']??'', 'multipart') !== false;
    $b = $isMultipart ? [] : (json_decode(file_get_contents('php://input'), true) ?: []);
    $action = $b['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'generate_ai_face':  generateAiFace($db, $esc, $b, $user);   break;
        case 'save_face':          saveFace($db, $esc, $mem_id);           break;
        case 'save_voice_preset':  saveVoicePreset($db, $esc, $b);         break;
        case 'save_voice_clone':   saveVoiceClone($db, $esc, $mem_id);     break;
        case 'tts_preview':        ttsPreview($db, $esc, $b, $user);       break;
        case 'test_heygen':        testHeygen($db, $esc, $b);              break;
        case 'create_heygen_avatar': createHeygenAvatar($db,$esc,$b,$mem_id); break;
        case 'generate_video':     generateVideo($db, $esc, $b, $user);   break;
        case 'save_heygen':        saveHeygen($db, $esc, $b, $mem_id);     break;
        default: mychat_json(['ok'=>false,'error'=>'Unknown action'], 400);
    }
}


// ════════════════════════════════════════════════════════════════
// AI 얼굴 이미지 생성 (DALL-E 3 × 4장)
// ════════════════════════════════════════════════════════════════
function generateAiFace($db, $esc, $b, $user): void {
    $gender = in_array($b['gender']??'', ['woman','man','neutral']) ? $b['gender'] : 'woman';
    $age    = in_array($b['age']??'',    ['20s','30s','40s','50s']) ? $b['age']    : '30s';
    $style  = in_array($b['style']??'', ['photo','cartoon','3d'])   ? $b['style']  : 'photo';
    $extra  = mb_substr(trim($b['extra']??''), 0, 100);

    // 프롬프트 구성
    $genderKr = ['woman'=>'Korean woman','man'=>'Korean man','neutral'=>'Korean person'][$gender];
    $ageKr    = ['20s'=>'in their 20s','30s'=>'in their 30s','40s'=>'in their 40s','50s'=>'over 50'][$age];
    $styleMap = [
        'photo'   => 'photorealistic portrait, professional headshot, soft studio lighting',
        'cartoon' => 'webtoon illustration style, clean lines, bright colors, cute character',
        '3d'      => '3D rendered avatar, Pixar-style character, vibrant colors',
    ];
    $stylePrompt = $styleMap[$style] ?? $styleMap['photo'];

    $prompt = "{$genderKr} {$ageKr}, friendly smile, {$stylePrompt}, white background, square format";
    if ($extra) $prompt .= ", {$extra}";

    // OpenAI DALL-E 3 API 키
    $apiKey = '';
    // 1) 사용자 BYOK OpenAI 키 우선
    if ($user['api_type']==='user_key' && $user['api_prov']==='openai' && $user['api_key']) {
        $k   = hash('sha256', $user['mem_id'].'MYCHAT_ENC_SALT_2026', true);
        $raw = base64_decode($user['api_key']);
        $iv  = substr($raw, 0, 16);
        $apiKey = openssl_decrypt(substr($raw,16),'aes-256-cbc',$k,OPENSSL_RAW_DATA,$iv) ?: '';
    }
    // 2) 플랫폼 OpenAI 키
    if (!$apiKey) {
        if (!$apiKey) $apiKey = mychat_openai_key($user);
    }

    if (!$apiKey) {
        // OpenAI 키 없으면 DeepSeek으로 대체 안됨 → 프리셋 이미지 제공
        // 실제 Stable Diffusion 로컬 서버나 다른 API 연동 가능
        mychat_json(['ok'=>false,'error'=>'AI 이미지 생성을 위해 설정→자체 API키에서 OpenAI API키를 등록해 주세요.']);
    }

    // DALL-E 3는 1회 1장 → n=1로 4번 병렬 요청
    $images = [];
    $errors = [];
    for ($i = 0; $i < 4; $i++) {
        $payload = json_encode([
            'model'   => 'dall-e-3',
            'prompt'  => $prompt . " (variation " . ($i+1) . ")",
            'n'       => 1,
            'size'    => '1024x1024',
            'quality' => 'standard',
            'style'   => 'natural',
        ], JSON_UNESCAPED_UNICODE);
        $ctx = stream_context_create(['http'=>[
            'method'  => 'POST',
            'header'  => "Content-Type: application/json
Authorization: Bearer {$apiKey}
",
            'content' => $payload, 'timeout' => 60, 'ignore_errors' => true,
        ]]);
        $resp = @file_get_contents('https://api.openai.com/v1/images/generations', false, $ctx);
        $data = $resp ? json_decode($resp, true) : null;
        if (!empty($data['data'][0]['url'])) {
            // URL을 서버에 다운로드 저장 (임시 캐시)
            $imgData = @file_get_contents($data['data'][0]['url']);
            if ($imgData) {
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/aimessage/mychat/uploads/ai_faces/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'aigen_' . substr(md5($prompt.$i.time()), 0, 12) . '.jpg';
                file_put_contents($dir . $fname, $imgData);
                $images[] = '/aimessage/mychat/uploads/ai_faces/' . $fname;
            }
        } else {
            $errors[] = $data['error']['message'] ?? 'generation failed';
        }
    }

    if (empty($images)) {
        mychat_json(['ok'=>false,'error'=>'이미지 생성 실패: '.implode(', ',$errors)]);
    }
    mychat_json(['ok'=>true, 'images'=>$images, 'prompt'=>$prompt]);
}

// ════════════════════════════════════════════════════════════════
// 얼굴 저장
// ════════════════════════════════════════════════════════════════
function saveFace($db, $esc, $mem_id): void {
    $style = in_array($_POST['avatar_style']??'', ['photo','cartoon','3d']) ? $_POST['avatar_style'] : 'photo';
    $file  = $_FILES['face_image'] ?? null;

    // AI 생성 이미지 URL 처리 (사진 업로드 없을 때)
    $aiImageUrl = $_POST['ai_image_url'] ?? '';
    if ($aiImageUrl && !$file) {
        // AI 생성 이미지는 이미 서버에 저장됨 — URL만 업데이트
        $imgUrl  = $db->real_escape_string($aiImageUrl);
        $esc_sty = $db->real_escape_string($style);
        $db->query("INSERT INTO mychat_avatar (mem_id, face_image_url, avatar_style)
                    VALUES ('{$esc}','{$imgUrl}','{$esc_sty}')
                    ON DUPLICATE KEY UPDATE face_image_url='{$imgUrl}', avatar_style='{$esc_sty}'");
        mychat_json(['ok'=>true, 'image_url'=>$aiImageUrl]);
    }

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        mychat_json(['ok'=>false,'error'=>'이미지 파일 업로드 오류'], 400);
    }

    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) mychat_json(['ok'=>false,'error'=>'JPG·PNG·WEBP만 허용'], 400);
    if ($file['size'] > 10*1024*1024)       mychat_json(['ok'=>false,'error'=>'10MB 초과'], 400);

    // 저장 경로
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/aimessage/mychat/uploads/faces/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = 'face_' . $mem_id . '_' . time() . '.' . $ext;
    $savePath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $savePath)) {
        mychat_json(['ok'=>false,'error'=>'파일 저장 실패'], 500);
    }

    $imgUrl   = '/aimessage/mychat/uploads/faces/' . $filename;
    $esc_url  = $db->real_escape_string($imgUrl);
    $esc_sty  = $db->real_escape_string($style);
    $db->query("INSERT INTO mychat_avatar (mem_id, face_image_url, avatar_style)
                VALUES ('{$esc}','{$esc_url}','{$esc_sty}')
                ON DUPLICATE KEY UPDATE face_image_url='{$esc_url}', avatar_style='{$esc_sty}'");

    mychat_json(['ok'=>true, 'image_url'=>$imgUrl]);
}

// ════════════════════════════════════════════════════════════════
// 목소리 프리셋 저장 (OpenAI TTS)
// ════════════════════════════════════════════════════════════════
function saveVoicePreset($db, $esc, $b): void {
    $allowed = ['alloy','echo','fable','onyx','nova','shimmer'];
    $voice   = in_array($b['voice_id']??'', $allowed) ? $b['voice_id'] : 'nova';
    $esc_v   = $db->real_escape_string($voice);
    $db->query("INSERT INTO mychat_avatar (mem_id, voice_preset_id)
                VALUES ('{$esc}','{$esc_v}')
                ON DUPLICATE KEY UPDATE voice_preset_id='{$esc_v}', voice_clone_id=NULL");
    mychat_json(['ok'=>true, 'voice_id'=>$voice]);
}

// ════════════════════════════════════════════════════════════════
// TTS 미리 듣기
// ════════════════════════════════════════════════════════════════
function ttsPreview($db, $esc, $b, $user): void {
    $text    = mb_substr(trim($b['text']??'안녕하세요'), 0, 200);
    $voice   = $b['voice_id'] ?? 'nova';
    $apiKey  = getTTSKey($user);

    $payload = json_encode(['model'=>'tts-1','voice'=>$voice,'input'=>$text]);
    $ctx = stream_context_create(['http'=>[
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
        'content' => $payload, 'timeout' => 15, 'ignore_errors' => true,
    ]]);
    $audio = @file_get_contents('https://api.openai.com/v1/audio/speech', false, $ctx);
    if (!$audio) mychat_json(['ok'=>false,'error'=>'TTS 요청 실패'], 503);

    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    echo $audio;
    exit;
}

// ════════════════════════════════════════════════════════════════
// 목소리 클론 저장 (녹음 → OpenAI TTS 저장)
// ════════════════════════════════════════════════════════════════
function saveVoiceClone($db, $esc, $mem_id): void {
    $file = $_FILES['voice_audio'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        mychat_json(['ok'=>false,'error'=>'음성 파일 업로드 오류'], 400);
    }

    $dir = $_SERVER['DOCUMENT_ROOT'] . '/aimessage/mychat/uploads/voices/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'voice_' . $mem_id . '_' . time() . '.webm';
    move_uploaded_file($file['tmp_name'], $dir . $filename);

    // 목소리 파일 URL 저장 (향후 ElevenLabs 클론 확장 가능)
    $voiceId  = 'clone_' . $mem_id;
    $esc_vid  = $db->real_escape_string($voiceId);
    $db->query("INSERT INTO mychat_avatar (mem_id, voice_clone_id)
                VALUES ('{$esc}','{$esc_vid}')
                ON DUPLICATE KEY UPDATE voice_clone_id='{$esc_vid}', voice_preset_id=NULL");

    mychat_json(['ok'=>true, 'voice_id'=>$voiceId]);
}

// ════════════════════════════════════════════════════════════════
// HeyGen API 연결 테스트
// ════════════════════════════════════════════════════════════════
function testHeygen($db, $esc, $b): void {
    $apiKey = trim($b['api_key'] ?? '');
    if (!$apiKey) mychat_json(['ok'=>false,'error'=>'API키를 입력해 주세요'], 400);

    // HeyGen v1 - 계정 정보 조회 (빠른 테스트용)
    $resp = heygenGet('https://api.heygen.com/v1/user/remaining.quota', $apiKey);
    if (!$resp || isset($resp['error'])) {
        mychat_json(['ok'=>false,'error'=>'API키가 유효하지 않습니다']);
    }

    // 한국어 보이스 목록 조회
    $voicesResp = heygenGet('https://api.heygen.com/v2/voices', $apiKey);
    $voices = [];
    foreach (($voicesResp['data']['voices']??[]) as $v) {
        if (in_array($v['language']??'', ['Korean','ko','ko-KR'])) {
            $voices[] = ['voice_id'=>$v['voice_id'], 'display_name'=>$v['display_name']];
        }
    }
    // 한국어 없으면 상위 10개
    if (!$voices) {
        foreach (array_slice($voicesResp['data']['voices']??[], 0, 10) as $v) {
            $voices[] = ['voice_id'=>$v['voice_id'], 'display_name'=>$v['display_name']];
        }
    }

    mychat_json(['ok'=>true, 'credits'=>$resp['data']['remaining_quota']??'?', 'voices'=>$voices]);
}

// ════════════════════════════════════════════════════════════════
// HeyGen 아바타 생성 (Photo Avatar)
// ════════════════════════════════════════════════════════════════
function createHeygenAvatar($db, $esc, $b, $mem_id): void {
    $heygenKey = getHeygenKey($db, $esc);
    if (!$heygenKey) mychat_json(['ok'=>false,'error'=>'HeyGen API키를 먼저 등록해 주세요'], 403);

    // 얼굴 이미지 URL 조회
    $av = $db->query("SELECT face_image_url FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    if (!$av || !$av['face_image_url']) {
        mychat_json(['ok'=>false,'error'=>'먼저 얼굴 탭에서 사진을 업로드해 주세요'], 400);
    }

    $voiceId = $b['heygen_voice_id'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? 'onechat.kiam.kr';
    $fullUrl = 'https://' . $host . $av['face_image_url'];

    // HeyGen v2 Photo Avatar 생성
    $payload = json_encode([
        'image_url'    => $fullUrl,
        'name'         => '마이챗_' . $mem_id,
        'voice_id'     => $voiceId,
        'gender'       => 'neutral',
    ]);
    $resp = heygenPost('https://api.heygen.com/v2/photo_avatar', $heygenKey, $payload);

    if (isset($resp['data']['avatar_id'])) {
        $avatarId = $db->real_escape_string($resp['data']['avatar_id']);
        $esc_vid  = $db->real_escape_string($voiceId);
        $db->query("UPDATE mychat_avatar SET heygen_avatar_id='{$avatarId}', heygen_voice_id='{$esc_vid}' WHERE mem_id='{$esc}'");
        mychat_json(['ok'=>true, 'avatar_id'=>$resp['data']['avatar_id']]);
    } else {
        $errMsg = $resp['message'] ?? $resp['error'] ?? 'HeyGen 아바타 생성 실패';
        mychat_json(['ok'=>false, 'error'=>$errMsg]);
    }
}

// ════════════════════════════════════════════════════════════════
// 영상 생성 요청 (HeyGen v2 → 비동기)
// ════════════════════════════════════════════════════════════════
function generateVideo($db, $esc, $b, $user): void {
    $heygenKey = getHeygenKey($db, $esc);
    if (!$heygenKey) mychat_json(['ok'=>false,'error'=>'HeyGen API키 없음'], 403);

    $av = $db->query("SELECT heygen_avatar_id, heygen_voice_id FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    if (!$av || !$av['heygen_avatar_id']) {
        mychat_json(['ok'=>false,'error'=>'HeyGen 아바타를 먼저 생성해 주세요'], 400);
    }

    $text     = mb_substr(trim($b['text']??''), 0, 500);
    $avatarId = $b['avatar_id'] ?? $av['heygen_avatar_id'];
    $voiceId  = $av['heygen_voice_id'] ?? '';

    // HeyGen v2 영상 생성
    $payload = json_encode([
        'video_inputs' => [[
            'character' => ['type'=>'avatar','avatar_id'=>$avatarId,'avatar_style'=>'normal'],
            'voice'     => ['type'=>'text','input_text'=>$text,'voice_id'=>$voiceId,'speed'=>1.0],
        ]],
        'dimension' => ['width'=>1280,'height'=>720],
        'aspect_ratio' => '16:9',
    ]);
    $resp = heygenPost('https://api.heygen.com/v2/video/generate', $heygenKey, $payload);

    if (isset($resp['data']['video_id'])) {
        mychat_json(['ok'=>true, 'video_id'=>$resp['data']['video_id']]);
    } else {
        mychat_json(['ok'=>false, 'error'=>$resp['message']??'영상 생성 요청 실패']);
    }
}

// ════════════════════════════════════════════════════════════════
// 영상 상태 폴링
// ════════════════════════════════════════════════════════════════
function videoStatus($db, $esc): void {
    $videoId   = trim($_GET['video_id'] ?? '');
    if (!$videoId) mychat_json(['ok'=>false,'error'=>'video_id 필요'], 400);

    $heygenKey = getHeygenKey($db, $esc);
    if (!$heygenKey) mychat_json(['ok'=>false,'error'=>'HeyGen 키 없음'], 403);

    $resp = heygenGet("https://api.heygen.com/v1/video_status.get?video_id={$videoId}", $heygenKey);
    $status   = $resp['data']['status']    ?? 'pending';
    $videoUrl = $resp['data']['video_url'] ?? null;

    mychat_json(['ok'=>true, 'status'=>$status, 'video_url'=>$videoUrl]);
}

// ════════════════════════════════════════════════════════════════
// HeyGen 상태 조회
// ════════════════════════════════════════════════════════════════
function heygenStatus($db, $esc): void {
    $heygenKey = getHeygenKey($db, $esc);
    if (!$heygenKey) mychat_json(['ok'=>false,'connected'=>false]);

    $resp = heygenGet('https://api.heygen.com/v1/user/remaining.quota', $heygenKey);
    $av   = $db->query("SELECT heygen_avatar_id, heygen_voice_id FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();

    // 보이스 목록
    $voicesResp = heygenGet('https://api.heygen.com/v2/voices', $heygenKey);
    $voices = [];
    foreach (array_slice($voicesResp['data']['voices']??[], 0, 20) as $v) {
        $voices[] = ['voice_id'=>$v['voice_id'],'display_name'=>$v['display_name']];
    }

    mychat_json([
        'ok'        => true,
        'connected' => (bool)($resp['data']??null),
        'credits'   => $resp['data']['remaining_quota'] ?? '?',
        'avatar_id' => $av['heygen_avatar_id'] ?? null,
        'voices'    => $voices,
    ]);
}

// ════════════════════════════════════════════════════════════════
// HeyGen 설정 저장
// ════════════════════════════════════════════════════════════════
function saveHeygen($db, $esc, $b, $mem_id): void {
    $apiKey   = trim($b['api_key']         ?? '');
    $voiceId  = trim($b['heygen_voice_id'] ?? '');
    $avatarId = trim($b['heygen_avatar_id']?? '');

    // API키 암호화
    $encKey = '';
    if ($apiKey) {
        $k   = hash('sha256', $mem_id . 'MYCHAT_ENC_SALT_2026', true);
        $iv  = openssl_random_pseudo_bytes(16);
        $enc = openssl_encrypt($apiKey, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, $iv);
        $encKey = base64_encode($iv . $enc);
    }

    // mychat_subscriptions에 HeyGen 키 저장
    if ($encKey) {
        $esc_enc = $db->real_escape_string($encKey);
        $db->query("UPDATE mychat_subscriptions SET heygen_api_key_enc='{$esc_enc}' WHERE mem_id='{$esc}' AND status='active'");
    }

    // avatar 테이블에 avatar_id, voice_id 저장
    $esc_av = $db->real_escape_string($avatarId);
    $esc_vo = $db->real_escape_string($voiceId);
    $db->query("INSERT INTO mychat_avatar (mem_id, heygen_avatar_id, heygen_voice_id)
                VALUES ('{$esc}','{$esc_av}','{$esc_vo}')
                ON DUPLICATE KEY UPDATE heygen_avatar_id='{$esc_av}', heygen_voice_id='{$esc_vo}'");

    mychat_json(['ok'=>true]);
}

// ════════════════════════════════════════════════════════════════
// 헬퍼 함수
// ════════════════════════════════════════════════════════════════
function heygenGet(string $url, string $apiKey): array {
    $ctx = stream_context_create(['http'=>[
        'method'  => 'GET',
        'header'  => "X-Api-Key: {$apiKey}\r\nAccept: application/json\r\n",
        'timeout' => 15, 'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp ? (json_decode($resp, true) ?: []) : [];
}

function heygenPost(string $url, string $apiKey, string $payload): array {
    $ctx = stream_context_create(['http'=>[
        'method'  => 'POST',
        'header'  => "X-Api-Key: {$apiKey}\r\nContent-Type: application/json\r\n",
        'content' => $payload, 'timeout' => 30, 'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    return $resp ? (json_decode($resp, true) ?: []) : [];
}

function getHeygenKey($db, $esc): string {
    $row = $db->query("SELECT heygen_api_key_enc FROM mychat_subscriptions WHERE mem_id='{$esc}' AND status='active' ORDER BY id DESC LIMIT 1")?->fetch_assoc();
    if (!$row || !$row['heygen_api_key_enc']) return '';
    // 복호화
    global $mem_id;
    return mychat_decrypt_key($row['heygen_api_key_enc'], $mem_id);
}

function getTTSKey($user): string {
    return mychat_openai_key($user);
}
