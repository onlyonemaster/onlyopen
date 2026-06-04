<?php
/**
 * 마이챗 AI 동행 (예약 재접촉) API
 * GET  → 동행 일정 목록 조회
 * POST action=schedule → 동행 예약 등록
 * POST action=cancel   → 예약 취소
 * POST action=trigger  → 즉시 동행 메시지 생성 (cron에서도 호출)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();

// cron 호출(내부 토큰) 또는 사용자 호출
$cronSecret = trim($_GET['cron'] ?? '');
$validCron  = ($cronSecret === (getenv('MYCHAT_CRON_SECRET') ?: 'MC_CRON_2026'));

if ($validCron) {
    // cron 실행 — 기한 도래한 동행 메시지 생성
    $db = getDatabaseConnection();
    runCompanionCron($db);
    exit;
}

$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// 동행 테이블 자동 생성
$db->query("CREATE TABLE IF NOT EXISTS mychat_companion (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    mem_id     VARCHAR(50) NOT NULL,
    title      VARCHAR(200) NOT NULL COMMENT '동행 제목/주제',
    trigger_at DATETIME NOT NULL COMMENT '발송 예정 일시',
    message    TEXT COMMENT 'AI가 생성한 동행 메시지',
    context    TEXT COMMENT '동행 컨텍스트 (연관 결정·데이터 요약)',
    status     ENUM('pending','sent','cancelled') DEFAULT 'pending',
    sent_at    DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mem    (mem_id),
    INDEX idx_status (status, trigger_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── GET: 동행 목록 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $r = $db->query(
        "SELECT id, title, trigger_at, message, status, sent_at, created_at
         FROM mychat_companion WHERE mem_id='{$esc}'
         ORDER BY trigger_at DESC LIMIT 50"
    );
    $list = [];
    while ($row = $r?->fetch_assoc()) $list[] = $row;
    mychat_json(['ok'=>true, 'companions'=>$list]);
}

// ── POST ──────────────────────────────────────────────────────
$b      = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $b['action'] ?? 'schedule';

if ($action === 'schedule') {
    $title     = mb_substr(trim($b['title']      ?? ''), 0, 200);
    $triggerAt = trim($b['trigger_at'] ?? '');
    $context   = mb_substr(trim($b['context']    ?? ''), 0, 2000);

    if (!$title || !$triggerAt) mychat_json(['ok'=>false,'error'=>'title, trigger_at 필수'], 400);
    if (strtotime($triggerAt) < time()) mychat_json(['ok'=>false,'error'=>'과거 시간은 설정 불가'], 400);

    // 사용량 체크
    if (!mychat_use($mem_id, 'companion', $user['limits'])) {
        $db->query("UPDATE mychat_usage SET companion_cnt=GREATEST(companion_cnt-1,0) WHERE mem_id='{$esc}' AND ym='".date('Y-m')."'");
        mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED','msg'=>'이번 달 AI 동행 한도 초과'], 429);
    }

    $esc_t   = $db->real_escape_string($title);
    $esc_ta  = $db->real_escape_string($triggerAt);
    $esc_ctx = $db->real_escape_string($context);
    $db->query(
        "INSERT INTO mychat_companion (mem_id, title, trigger_at, context)
         VALUES ('{$esc}','{$esc_t}','{$esc_ta}','{$esc_ctx}')"
    );
    mychat_json(['ok'=>true, 'id'=>$db->insert_id, 'trigger_at'=>$triggerAt]);
}

if ($action === 'cancel') {
    $id = (int)($b['id'] ?? 0);
    $db->query("UPDATE mychat_companion SET status='cancelled' WHERE id={$id} AND mem_id='{$esc}'");
    mychat_json(['ok'=>true]);
}

if ($action === 'trigger') {
    $id = (int)($b['id'] ?? 0);
    $row = $db->query("SELECT * FROM mychat_companion WHERE id={$id} AND mem_id='{$esc}'")?->fetch_assoc();
    if (!$row) mychat_json(['ok'=>false,'error'=>'동행 항목 없음'], 404);
    $msg = generateCompanionMessage($db, $esc, $row, $user);
    $esc_msg = $db->real_escape_string($msg);
    $db->query("UPDATE mychat_companion SET message='{$esc_msg}', status='sent', sent_at=NOW() WHERE id={$id}");
    mychat_json(['ok'=>true, 'message'=>$msg]);
}

mychat_json(['ok'=>false,'error'=>'Unknown action'], 400);

// ── 동행 메시지 AI 생성 ───────────────────────────────────────
function generateCompanionMessage($db, $esc, array $row, array $user): string {
    // 관련 데이터 로드
    $relData = $db->query(
        "SELECT category, LEFT(content_text,200) AS txt FROM mychat_data_pool
         WHERE mem_id='{$esc}' AND is_deleted=0 ORDER BY id DESC LIMIT 5"
    );
    $context = $row['context'] ?? '';
    while ($r = $relData?->fetch_assoc()) {
        $context .= "\n[{$r['category']}] " . $r['txt'];
    }

    $sys = "당신은 사용자의 개인 AI 동행자입니다. 예약된 시점에 자연스럽게 말을 건네는 짧은 메시지(3~5문장)를 작성하세요. 강요하지 않고, 진심 어린 관심을 담아 부드럽게 시작하세요.";
    $prompt = "동행 주제: {$row['title']}\n\n관련 배경:\n" . mb_substr($context, 0, 500);

    $aiR = mychat_ai_reply($sys, $prompt, 200, $user);
    return $aiR['ok'] ? trim($aiR['content'])
                      : "🧠 [{$row['title']}] 시간이 됐습니다. 잠깐 이야기 나눠볼까요?";
}

// ── cron 처리 ─────────────────────────────────────────────────
function runCompanionCron($db): void {
    $db->query("CREATE TABLE IF NOT EXISTS mychat_companion (
        id BIGINT AUTO_INCREMENT PRIMARY KEY, mem_id VARCHAR(50) NOT NULL,
        title VARCHAR(200) NOT NULL, trigger_at DATETIME NOT NULL,
        message TEXT, context TEXT, status ENUM('pending','sent','cancelled') DEFAULT 'pending',
        sent_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_mem (mem_id), INDEX idx_status (status, trigger_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $now = date('Y-m-d H:i:s');
    $r   = $db->query(
        "SELECT c.*, s.user_api_key_enc, s.user_api_provider, s.api_type, s.plan_id
         FROM mychat_companion c
         JOIN mychat_subscriptions s ON s.mem_id=c.mem_id AND s.status='active'
         WHERE c.status='pending' AND c.trigger_at <= '{$now}'
         LIMIT 20"
    );
    $processed = 0;
    while ($row = $r?->fetch_assoc()) {
        $esc    = $db->real_escape_string($row['mem_id']);
        $fakeUser = ['mem_id'=>$row['mem_id'],'api_type'=>$row['api_type'],'api_prov'=>$row['user_api_provider'],'api_key'=>$row['user_api_key_enc'],'limits'=>[]];
        $msg    = generateCompanionMessage($db, $esc, $row, $fakeUser);
        $esc_msg = $db->real_escape_string($msg);
        // 마이챗 대화 이력에 동행 메시지 삽입
        $db->query("INSERT INTO mychat_chat_history (mem_id, role, content) VALUES ('{$esc}','assistant','{$esc_msg}')");
        $db->query("UPDATE mychat_companion SET message='{$esc_msg}', status='sent', sent_at=NOW() WHERE id={$row['id']}");
        $processed++;
    }
    echo json_encode(['ok'=>true,'processed'=>$processed]);
}
