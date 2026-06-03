<?php
/**
 * 마이챗 리포트 생성 API
 * GET  ?type=monthly|weekly|insight → 리포트 조회
 * POST action=generate             → 리포트 즉시 생성
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// 리포트 테이블 자동 생성
$db->query("CREATE TABLE IF NOT EXISTS mychat_reports (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    mem_id      VARCHAR(50) NOT NULL,
    report_type ENUM('monthly','weekly','insight','decision') DEFAULT 'monthly',
    period      VARCHAR(20) COMMENT '예: 2026-06 / 2026-W22',
    title       VARCHAR(200),
    summary     TEXT,
    content     LONGTEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mem  (mem_id),
    INDEX idx_type (report_type, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── GET: 리포트 조회 ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'monthly';
    $r    = $db->query(
        "SELECT id, report_type, period, title, summary, created_at
         FROM mychat_reports WHERE mem_id='{$esc}' AND report_type='{$db->real_escape_string($type)}'
         ORDER BY id DESC LIMIT 12"
    );
    $reports = [];
    while ($row = $r?->fetch_assoc()) $reports[] = $row;
    mychat_json(['ok'=>true, 'reports'=>$reports]);
}

// ── POST: 리포트 생성 ─────────────────────────────────────────
$b      = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $b['action'] ?? 'generate';
$type   = $b['type']   ?? 'monthly';

if ($action !== 'generate') mychat_json(['ok'=>false,'error'=>'Unknown action'], 400);

// 사용량 체크
if (!mychat_use($mem_id, 'report', $user['limits'])) {
    $db->query("UPDATE mychat_usage SET report_cnt=GREATEST(report_cnt-1,0) WHERE mem_id='{$esc}' AND ym='".date('Y-m')."'");
    mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED','msg'=>'이번 달 리포트 한도 초과'], 429);
}

// 데이터 수집
$period = $type === 'monthly' ? date('Y-m') : date('Y-\WW');
$since  = $type === 'monthly' ? date('Y-m-01') : date('Y-m-d', strtotime('monday this week'));

$stats = collectStats($db, $esc, $since);
$report = generateReport($db, $esc, $type, $period, $stats, $user);

// DB 저장
$esc_type    = $db->real_escape_string($type);
$esc_period  = $db->real_escape_string($period);
$esc_title   = $db->real_escape_string($report['title']);
$esc_summary = $db->real_escape_string($report['summary']);
$esc_content = $db->real_escape_string($report['content']);
$db->query(
    "INSERT INTO mychat_reports (mem_id, report_type, period, title, summary, content)
     VALUES ('{$esc}','{$esc_type}','{$esc_period}','{$esc_title}','{$esc_summary}','{$esc_content}')
     ON DUPLICATE KEY UPDATE title='{$esc_title}', summary='{$esc_summary}', content='{$esc_content}', created_at=NOW()"
);

mychat_json(['ok'=>true, 'report'=>$report, 'period'=>$period]);

// ── 데이터 수집 ───────────────────────────────────────────────
function collectStats($db, $esc, $since): array {
    // 데이터 추가 건수
    $dataR = $db->query("SELECT category, COUNT(*) AS cnt FROM mychat_data_pool WHERE mem_id='{$esc}' AND is_deleted=0 AND created_at>='{$since}' GROUP BY category");
    $categories = [];
    while ($r = $dataR?->fetch_assoc()) $categories[$r['category']] = (int)$r['cnt'];

    // 대화 건수
    $chatCnt = (int)($db->query("SELECT COUNT(*) AS c FROM mychat_chat_history WHERE mem_id='{$esc}' AND role='user' AND created_at>='{$since}'")?->fetch_assoc()['c']??0);

    // 의사결정
    $decR = $db->query("SELECT status, match_label, COUNT(*) AS cnt FROM mychat_decisions WHERE mem_id='{$esc}' AND created_at>='{$since}' GROUP BY status, match_label");
    $decisions = ['total'=>0,'hit'=>0,'partial'=>0,'miss'=>0];
    while ($r = $decR?->fetch_assoc()) {
        $decisions['total']++;
        if ($r['match_label']) $decisions[$r['match_label']] = ($decisions[$r['match_label']]??0) + (int)$r['cnt'];
    }

    // 아바타 정보
    $av = $db->query("SELECT match_rate, data_count FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();

    return compact('categories','chatCnt','decisions','av');
}

// ── 리포트 AI 생성 ────────────────────────────────────────────
function generateReport($db, $esc, $type, $period, $stats, $user): array {
    $typeKr   = $type === 'monthly' ? '월간' : '주간';
    $totalData = array_sum($stats['categories']);
    $matchRate = (float)($stats['av']['match_rate']??0);
    $chatCnt   = $stats['chatCnt'];
    $hitCnt    = $stats['decisions']['hit']??0;
    $totalDec  = $stats['decisions']['total'];

    // 기본 리포트 (AI 없어도 생성)
    $summaryLines = [
        "• {$period} 기간 대화 {$chatCnt}회",
        "• 학습 데이터 {$totalData}건 추가",
        "• 의사결정 정확도 {$matchRate}%",
    ];
    $baseSummary = implode("\n", $summaryLines);
    $baseTitle   = "{$typeKr} 리포트 — {$period}";

    if ($totalData === 0) {
        return ['title'=>$baseTitle, 'summary'=>$baseSummary, 'content'=>$baseSummary];
    }

    // 최근 일기 샘플
    $diaryR = $db->query("SELECT LEFT(content_text,200) AS t FROM mychat_data_pool WHERE mem_id='{$esc}' AND category='diary' AND is_deleted=0 ORDER BY id DESC LIMIT 3");
    $diaries = [];
    while ($r = $diaryR?->fetch_assoc()) $diaries[] = $r['t'];

    $catStr = implode(', ', array_map(fn($c,$n)=>"{$c}:{$n}건", array_keys($stats['categories']), $stats['categories']));

    $sys = "당신은 개인 AI 아바타입니다. 사용자의 데이터를 분석해서 {$typeKr} 리포트를 작성해주세요. 분량: 5~8문장. 따뜻하고 인사이트 있게 작성하되, 구체적 수치를 포함하세요.";
    $prompt = "기간: {$period}\n데이터 현황: {$catStr}\n대화 횟수: {$chatCnt}회\n의사결정 정확도: {$matchRate}%\n최근 일기 샘플:\n" . mb_substr(implode("\n", $diaries), 0, 400);

    $aiR = mychat_ai_reply($sys, $prompt, 400, $user);
    $content = $aiR['ok'] ? trim($aiR['content']) : $baseSummary;
    $firstLine = mb_substr($content, 0, 100);

    return ['title'=>$baseTitle, 'summary'=>$firstLine, 'content'=>$content];
}
