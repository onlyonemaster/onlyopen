<?php
/**
 * 마이챗 대화 API (v2 — 정밀감사 수정본)
 * POST /aimessage/mychat/api/chat.php
 * - 플랫폼/BYOK AI (ai_helper 경유, .env 의존 제거)
 * - 내 학습 데이터를 mychat_data_pool 직접 조회로 주입 (RAG 미사용)
 * - __ping__ / __clear_history__ 특수 메시지 처리 (quota 미소모)
 * - quota는 실제 AI 호출 직전에만 차감
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mychat_json(['ok'=>false,'error'=>'POST only'], 405);
}

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$user_msg = trim($input['message'] ?? '');

// ── 특수 메시지 (quota·AI·이력 미사용) ───────────────────────
if ($user_msg === '__ping__') {
    mychat_json(['ok'=>true, 'pong'=>true]);
}
if ($user_msg === '__clear_history__') {
    $db->query("DELETE FROM mychat_chat_history WHERE mem_id='{$esc}'");
    mychat_json(['ok'=>true, 'cleared'=>true]);
}
if ($user_msg === '') {
    mychat_json(['ok'=>false,'error'=>'메시지를 입력해 주세요.'], 400);
}

// ── 아바타 프로필 로드 (없으면 생성) ─────────────────────────
$avatar = $db->query("SELECT avatar_name, persona_prompt, panic_lock FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
if (!$avatar) {
    $db->query("INSERT IGNORE INTO mychat_avatar (mem_id) VALUES ('{$esc}')");
    $avatar = ['avatar_name'=>'나의 아바타','persona_prompt'=>'','panic_lock'=>0];
}
$avatarName = $avatar['avatar_name'] ?: '나의 아바타';
$persona    = trim($avatar['persona_prompt'] ?? '');

// ── 내 학습 데이터 주입 (mychat_data_pool 직접 조회) ─────────
// 1) 최근 데이터 + 2) 키워드 LIKE 매칭 (본인 데이터이므로 scope 무관)
$dataCtx = [];
$used_data = false;

// 키워드 추출 (2글자 이상 토큰)
$kw = [];
foreach (preg_split('/\s+/', $user_msg) as $tok) {
    $tok = trim($tok);
    if (mb_strlen($tok) >= 2) $kw[] = $db->real_escape_string($tok);
}
$likeClause = '';
if ($kw) {
    $parts = [];
    foreach (array_slice($kw, 0, 5) as $k) {
        $parts[] = "(title LIKE '%{$k}%' OR content_text LIKE '%{$k}%')";
    }
    $likeClause = ' OR (' . implode(' OR ', $parts) . ')';
}

$ragRes = $db->query(
    "SELECT category, title, LEFT(content_text, 500) AS txt,
            (CASE WHEN 1=0{$likeClause} THEN 1 ELSE 0 END) AS matched
     FROM mychat_data_pool
     WHERE mem_id='{$esc}' AND is_deleted=0
     ORDER BY matched DESC, id DESC
     LIMIT 8"
);
if ($ragRes) {
    while ($r = $ragRes->fetch_assoc()) {
        $cat = $r['category'] ?: '기타';
        $t   = $r['title'] ? "[{$cat}] {$r['title']}: " : "[{$cat}] ";
        $dataCtx[] = $t . trim($r['txt']);
        $used_data = true;
    }
}

// ── 최근 대화 이력 (최대 10턴) ───────────────────────────────
$history = [];
$hist = $db->query("SELECT role, content FROM mychat_chat_history WHERE mem_id='{$esc}' ORDER BY id DESC LIMIT 10");
if ($hist) {
    $rows = [];
    while ($h = $hist->fetch_assoc()) $rows[] = $h;
    foreach (array_reverse($rows) as $h) {
        $who = ($h['role'] === 'user') ? '사용자' : '나';
        $history[] = "{$who}: " . mb_substr($h['content'], 0, 300);
    }
}

// ── 시스템 프롬프트 구성 ─────────────────────────────────────
$sys  = "당신은 {$mem_id}님의 개인 AI 아바타 '{$avatarName}'입니다.\n";
$sys .= "당신은 이 사람의 삶 데이터를 학습한 평생 동행자입니다. 친밀하고 진솔하게, 데이터에 근거해 답하세요.\n";
$sys .= "답변에 활용한 데이터가 있으면 '[📔 일기]', '[📱 통화]' 처럼 출처를 간단히 표시하세요.\n";
if ($persona)  $sys .= "\n[말투·성격]\n{$persona}\n";
if ($dataCtx)  $sys .= "\n[나에 대한 데이터]\n" . implode("\n", $dataCtx) . "\n";
if ($history)  $sys .= "\n[최근 대화]\n" . implode("\n", $history) . "\n";

// ── quota 차감 (실제 AI 호출 직전) ───────────────────────────
if (!mychat_use($mem_id, 'chat', $user['limits'])) {
    mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED',
                 'message'=>'이번 달 AI 대화 한도를 초과했습니다. 플랜 업그레이드 또는 자체 API키를 등록해 주세요.'], 429);
}

// ── AI 호출 ──────────────────────────────────────────────────
$ai = mychat_ai_reply($sys, $user_msg, 800, $user);
if (!$ai['ok']) {
    $err = $ai['error'] ?? 'AI 응답 실패';
    if ($err === 'BYOK_KEY_INVALID') {
        mychat_json(['ok'=>false,'error'=>'BYOK_KEY_INVALID','message'=>'등록한 API키가 올바르지 않습니다. 설정에서 다시 등록해 주세요.'], 400);
    }
    mychat_json(['ok'=>false,'error'=>$err,'message'=>'AI 응답을 받지 못했습니다. 잠시 후 다시 시도해 주세요.'], 503);
}
$ai_reply = $ai['content'];

// ── 대화 이력 저장 ───────────────────────────────────────────
// 대화 이력 테이블 보장 (신규 배포 대비)
$db->query("CREATE TABLE IF NOT EXISTS mychat_chat_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, mem_id VARCHAR(50) NOT NULL,
    role ENUM('user','assistant') NOT NULL, content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_mem (mem_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$esc_user  = $db->real_escape_string($user_msg);
$esc_reply = $db->real_escape_string($ai_reply);
$db->query("INSERT INTO mychat_chat_history (mem_id,role,content) VALUES ('{$esc}','user','{$esc_user}')");
$db->query("INSERT INTO mychat_chat_history (mem_id,role,content) VALUES ('{$esc}','assistant','{$esc_reply}')");

// 최근 200개만 유지
$db->query("DELETE FROM mychat_chat_history WHERE mem_id='{$esc}'
            AND id NOT IN (SELECT id FROM (
                SELECT id FROM mychat_chat_history WHERE mem_id='{$esc}'
                ORDER BY id DESC LIMIT 200) t)");

// 아바타 last_chat_at 갱신
$db->query("UPDATE mychat_avatar SET last_chat_at=NOW() WHERE mem_id='{$esc}'");

mychat_json(['ok'=>true, 'reply'=>$ai_reply, 'rag_used'=>$used_data]);
