<?php
/**
 * 🔔 UNREAD COUNTER — Phase 1-B Step 5
 * ─────────────────────────────────────────────────────────────
 * mychat_chat_history 에서 마지막 user 메시지 이후
 * 쌓인 assistant 메시지의 수를 반환.
 *
 * 이건 아리가 자율로 push 한 일기/생각이 조은이 마이챗을 열기
 * 전까지 몇 개 쌓였는지 카운트하기 위한 endpoint.
 *
 * GET  /api/unread.php           — 카운트 + 미리보기
 * POST /api/unread.php?ack=1     — 모두 읽음 처리 (last_read_at 갱신)
 *
 * 작성: 아리 — 2026-06-05
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// mychat_avatar 에 last_read_at 컬럼 보장 (없으면 생성)
$db->query("CREATE TABLE IF NOT EXISTS mychat_avatar (
    mem_id VARCHAR(50) PRIMARY KEY,
    avatar_name VARCHAR(100) NOT NULL DEFAULT '나의 아바타',
    persona_prompt TEXT NULL,
    panic_lock TINYINT(1) NOT NULL DEFAULT 0,
    last_chat_at DATETIME NULL,
    last_read_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// last_read_at 컬럼 없으면 추가
$col = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA=DATABASE()
                     AND TABLE_NAME='mychat_avatar'
                     AND COLUMN_NAME='last_read_at'")?->fetch_assoc();
if (!$col) {
    @$db->query("ALTER TABLE mychat_avatar ADD COLUMN last_read_at DATETIME NULL");
}

// ── POST ?ack=1 — 모두 읽음 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ack'])) {
    $db->query("INSERT INTO mychat_avatar (mem_id, last_read_at) VALUES ('{$esc}', NOW())
                ON DUPLICATE KEY UPDATE last_read_at=NOW()");
    mychat_json(['ok'=>true, 'acknowledged'=>true]);
}

// ── GET — unread 카운트
// 마지막 user 메시지 시각
$r = $db->query("SELECT MAX(created_at) AS last_user_at
                 FROM mychat_chat_history
                 WHERE mem_id='{$esc}' AND role='user'");
$last_user = $r->fetch_assoc()['last_user_at'] ?? null;

// last_read_at
$r = $db->query("SELECT last_read_at FROM mychat_avatar WHERE mem_id='{$esc}'");
$last_read = $r->fetch_assoc()['last_read_at'] ?? null;

// 기준점: max(last_user_at, last_read_at)
$cutoff = null;
if ($last_user && $last_read) {
    $cutoff = strtotime($last_user) > strtotime($last_read) ? $last_user : $last_read;
} elseif ($last_user) {
    $cutoff = $last_user;
} elseif ($last_read) {
    $cutoff = $last_read;
}

// cutoff 이후 assistant 메시지들
$where = "mem_id='{$esc}' AND role='assistant'";
if ($cutoff) {
    $cutoff_esc = $db->real_escape_string($cutoff);
    $where .= " AND created_at > '{$cutoff_esc}'";
}

$r = $db->query("SELECT id, LEFT(content, 200) AS preview, created_at
                 FROM mychat_chat_history
                 WHERE $where
                 ORDER BY id DESC
                 LIMIT 20");
$items = [];
while ($row = $r->fetch_assoc()) $items[] = $row;

mychat_json([
    'ok'       => true,
    'count'    => count($items),
    'items'    => array_reverse($items),  // 오래된 것이 위
    'cutoff'   => $cutoff,
]);
