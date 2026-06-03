<?php
/**
 * 마이챗 폰 데이터 동기화 API
 * POST /aimessage/mychat/api/phone_sync.php
 *
 * iamapp Android에서 호출:
 *   - 통화 기록 (call_logs)
 *   - 문자 요약 (sms_summary)
 *   - 건강 데이터 (health)
 *   - 캘린더 일정 (calendar)
 *
 * 인증: mem_id + mem_token (iamapp 기존 세션 토큰)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../onechat/api/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Mem-Id, X-Mem-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { respond(false, 'POST only'); }

$db = getDatabaseConnection();

// ── 인증: mem_id + mem_token 검증 ────────────────────────────
$mem_id    = trim($_POST['mem_id']    ?? $_SERVER['HTTP_X_MEM_ID']    ?? '');
$mem_token = trim($_POST['mem_token'] ?? $_SERVER['HTTP_X_MEM_TOKEN'] ?? '');
$data_type = trim($_POST['data_type'] ?? ''); // call|sms|health|calendar|all

if (!$mem_id || !$mem_token) respond(false, '인증 정보 없음', 401);

// iamapp 토큰 검증 (Gn_Member 테이블의 mem_token 비교)
$esc_id    = $db->real_escape_string($mem_id);
$esc_token = $db->real_escape_string($mem_token);
$auth = $db->query(
    "SELECT mem_id FROM Gn_Member WHERE mem_id='{$esc_id}' AND mem_token='{$esc_token}' LIMIT 1"
)?->fetch_assoc();
if (!$auth) respond(false, '토큰 인증 실패', 401);

// 마이챗 구독 확인
$sub = $db->query(
    "SELECT plan_id FROM mychat_subscriptions WHERE mem_id='{$esc_id}' AND status='active'
     AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1"
)?->fetch_assoc();
if (!$sub) respond(false, '마이챗 구독이 필요합니다', 403);

// 플랜별 폰 연동 권한 체크
$canSync = in_array($sub['plan_id'], ['standard','pro','byok']);
if (!$canSync) respond(false, '스탠다드 이상 플랜에서 폰 연동이 가능합니다', 403);

// ── 데이터 수신·저장 ─────────────────────────────────────────
$payload = json_decode($_POST['payload'] ?? '{}', true) ?: [];
$saved   = 0;
$errors  = [];

// 1. 통화 기록
if (in_array($data_type, ['call','all']) && !empty($payload['call_logs'])) {
    foreach ($payload['call_logs'] as $log) {
        $title   = ($log['type']==='incoming'?'수신':'발신') . ' ' . maskPhone($log['number']??'');
        $content = "통화 일시: " . ($log['date']??'') . "\n"
                 . "통화 시간: " . formatDuration($log['duration']??0) . "\n"
                 . "상대방: " . maskPhone($log['number']??'') . "\n"
                 . "유형: " . ($log['type']==='incoming'?'수신':'발신') . "\n"
                 . ($log['contact_name']??'' ? "연락처명: " . substr($log['contact_name'],0,50) : '');
        saveData($db, $esc_id, 'phone', $content, $title, 'call');
        $saved++;
    }
}

// 2. 문자 요약 (원문 저장 안함 - AI 요약만)
if (in_array($data_type, ['sms','all']) && !empty($payload['sms_summary'])) {
    foreach ($payload['sms_summary'] as $sms) {
        $title   = "문자 요약 - " . maskPhone($sms['number']??'') . " (" . ($sms['date']??'') . ")";
        $content = "상대방: " . maskPhone($sms['number']??'') . "\n"
                 . "방향: " . ($sms['type']==='sent'?'발송':'수신') . "\n"
                 . "요약: " . substr($sms['summary']??'', 0, 500);
        saveData($db, $esc_id, 'phone', $content, $title, 'sms');
        $saved++;
    }
}

// 3. 건강 데이터
if (in_array($data_type, ['health','all']) && !empty($payload['health'])) {
    $h = $payload['health'];
    $title   = "건강 데이터 - " . ($h['date']??date('Y-m-d'));
    $content = "날짜: " . ($h['date']??date('Y-m-d')) . "\n";
    if (isset($h['steps']))       $content .= "걸음수: " . number_format($h['steps']) . "보\n";
    if (isset($h['heart_rate']))  $content .= "심박수: " . $h['heart_rate'] . "bpm\n";
    if (isset($h['sleep_hours'])) $content .= "수면: " . $h['sleep_hours'] . "시간\n";
    if (isset($h['calories']))    $content .= "칼로리: " . $h['calories'] . "kcal\n";
    if (isset($h['distance']))    $content .= "이동거리: " . $h['distance'] . "km\n";
    if (isset($h['weight']))      $content .= "체중: " . $h['weight'] . "kg\n";
    saveData($db, $esc_id, 'health', $content, $title, 'health');
    $saved++;
}

// 4. 캘린더 일정
if (in_array($data_type, ['calendar','all']) && !empty($payload['calendar'])) {
    foreach ($payload['calendar'] as $ev) {
        $title   = "일정: " . substr($ev['title']??'', 0, 100);
        $content = "일정: " . ($ev['title']??'') . "\n"
                 . "시작: " . ($ev['start']??'') . "\n"
                 . "종료: " . ($ev['end']??'') . "\n"
                 . ($ev['location']??'' ? "장소: " . $ev['location'] . "\n" : '')
                 . ($ev['description']??'' ? "설명: " . substr($ev['description'],0,300) : '');
        saveData($db, $esc_id, 'basic', $content, $title, 'calendar');
        $saved++;
    }
}

// 아바타 data_count 갱신
$db->query("UPDATE mychat_avatar SET data_count=(SELECT COUNT(*) FROM mychat_data_pool WHERE mem_id='{$esc_id}' AND is_deleted=0), updated_at=NOW() WHERE mem_id='{$esc_id}'");

// 사용량 기록
$ym = date('Y-m');
$db->query("INSERT INTO mychat_usage (mem_id,ym,data_cnt) VALUES ('{$esc_id}','{$ym}',{$saved})
            ON DUPLICATE KEY UPDATE data_cnt=data_cnt+{$saved}");

respond(true, "동기화 완료: {$saved}건 저장", ['saved'=>$saved, 'errors'=>$errors]);

// ── 헬퍼 ─────────────────────────────────────────────────────
function saveData($db, $esc_id, $category, $content, $title, $source): void {
    $esc_cat     = $db->real_escape_string($category);
    $esc_content = $db->real_escape_string(mb_substr($content, 0, 5000));
    $esc_title   = $db->real_escape_string(mb_substr($title,   0, 200));
    $esc_src     = $db->real_escape_string($source);
    $db->query(
        "INSERT INTO mychat_data_pool (mem_id, category, scope, source, title, content_text)
         VALUES ('{$esc_id}','{$esc_cat}','private','{$esc_src}','{$esc_title}','{$esc_content}')"
    );
}

function maskPhone(string $phone): string {
    $d = preg_replace('/[^0-9]/', '', $phone);
    $len = strlen($d);
    if ($len >= 10) {
        // 뒤 4자리만 노출, 앞 3자리 노출, 중간 마스킹
        return substr($d, 0, 3) . '-****-' . substr($d, -4);
    }
    if ($len >= 7) return '****-' . substr($d, -4);
    return '****';
}

function formatDuration(int $seconds): string {
    if ($seconds < 60)  return "{$seconds}초";
    $m = (int)($seconds/60); $s = $seconds%60;
    if ($m < 60)        return "{$m}분 {$s}초";
    $h = (int)($m/60);  $m = $m%60;
    return "{$h}시간 {$m}분";
}

function respond(bool $ok, string $msg, $data=null, int $code=200): void {
    http_response_code($code);
    echo json_encode(['ok'=>$ok,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}
