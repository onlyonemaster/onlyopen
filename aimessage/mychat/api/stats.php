<?php
/**
 * 마이챗 통계 API (대시보드용)
 * GET /aimessage/mychat/api/stats.php
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);
$ym     = date('Y-m');

// 총 데이터 건수
$total = (int)($db->query("SELECT COUNT(*) AS c FROM mychat_data_pool WHERE mem_id='{$esc}' AND is_deleted=0")?->fetch_assoc()['c'] ?? 0);

// 채널별 건수
$catRows = $db->query("SELECT category, COUNT(*) AS c FROM mychat_data_pool WHERE mem_id='{$esc}' AND is_deleted=0 GROUP BY category");
$categories = [];
while ($r = $catRows?->fetch_assoc()) $categories[$r['category']] = (int)$r['c'];

// 아바타 매칭률
$av = $db->query("SELECT match_rate, data_count, decision_count FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
$matchRate   = $av ? (float)($av['match_rate'] ?? 0) : 0;
$decTotal    = (int)($db->query("SELECT COUNT(*) AS c FROM mychat_decisions WHERE mem_id='{$esc}'")?->fetch_assoc()['c'] ?? 0);

// 최근 의사결정 5건
$decRows = $db->query("SELECT situation, status, match_label, match_score, decided_at FROM mychat_decisions WHERE mem_id='{$esc}' ORDER BY id DESC LIMIT 5");
$recentDec = [];
while ($r = $decRows?->fetch_assoc()) $recentDec[] = $r;

// 이번 달 사용량
$usage = $db->query("SELECT * FROM mychat_usage WHERE mem_id='{$esc}' AND ym='{$ym}'")?->fetch_assoc() ?: [];

// 마지막 대화 시간
$lastChat = $db->query("SELECT last_chat_at FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc()['last_chat_at'] ?? null;

mychat_json([
    'ok'    => true,
    'stats' => [
        'data_total'       => $total,
        'categories'       => $categories,
        'match_rate'       => $matchRate / 100,  // 0.0~1.0
        'decision_total'   => $decTotal,
        'recent_decisions' => $recentDec,
        'last_chat_at'     => $lastChat,
    ],
    'usage' => $usage,
    'plan'  => [
        'plan_id' => $user['plan_id'],
        'limits'  => $user['limits'],
    ],
]);
