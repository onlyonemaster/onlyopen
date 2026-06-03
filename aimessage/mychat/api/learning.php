<?php
/**
 * 마이챗 자가 학습 엔진
 * GET  → 현재 학습 상태 조회 (가중치, 요약, 인사이트)
 * POST ?action=learn → 즉시 학습 실행
 *
 * 학습 루프:
 *   1) 의사결정 match_label(hit/partial/miss) 집계
 *   2) 카테고리별 가중치 조정 (0.5~1.5 클램프)
 *   3) 성향 요약 텍스트 AI 자동 생성
 *   4) mychat_avatar.match_rate, weights_json 갱신
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// ── GET: 학습 상태 조회 ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $av = $db->query(
        "SELECT weights_json, match_rate, decision_count, data_count, persona_prompt, updated_at
         FROM mychat_avatar WHERE mem_id='{$esc}'"
    )?->fetch_assoc();

    $weights = $av ? json_decode($av['weights_json']??'{}', true) : [];
    $insights = generateInsights($weights, (float)($av['match_rate']??0), (int)($av['decision_count']??0));

    mychat_json([
        'ok'             => true,
        'weights'        => $weights,
        'match_rate'     => (float)($av['match_rate']??0),
        'decision_count' => (int)($av['decision_count']??0),
        'data_count'     => (int)($av['data_count']??0),
        'persona_prompt' => $av['persona_prompt']??'',
        'insights'       => $insights,
        'updated_at'     => $av['updated_at']??null,
    ]);
}

// ── POST: 학습 실행 ───────────────────────────────────────────
$b      = json_decode(file_get_contents('php://input'), true) ?: [];
$action = strtolower(trim($_GET['action'] ?? $b['action'] ?? 'learn'));

if ($action !== 'learn') mychat_json(['ok'=>false,'error'=>'action=learn 필요'], 400);

// 1. 의사결정 데이터 집계
$decStats = $db->query(
    "SELECT
       COUNT(*) AS total,
       SUM(CASE WHEN match_label='hit'     THEN 1 ELSE 0 END) AS hits,
       SUM(CASE WHEN match_label='partial' THEN 1 ELSE 0 END) AS partials,
       SUM(CASE WHEN match_label='miss'    THEN 1 ELSE 0 END) AS misses,
       AVG(CASE WHEN match_score IS NOT NULL THEN match_score ELSE NULL END) AS avg_score
     FROM mychat_decisions
     WHERE mem_id='{$esc}' AND status='reflected'"
)?->fetch_assoc();

$total    = (int)($decStats['total']??0);
$hits     = (int)($decStats['hits']??0);
$partials = (int)($decStats['partials']??0);
$misses   = (int)($decStats['misses']??0);
$avgScore = (float)($decStats['avg_score']??0);
$matchRate = $total > 0 ? round($avgScore * 100) : 0;

// 2. 데이터 카테고리별 집계 → 가중치 계산
$catStats = $db->query(
    "SELECT category, COUNT(*) AS cnt
     FROM mychat_data_pool
     WHERE mem_id='{$esc}' AND is_deleted=0
     GROUP BY category"
);
$catCounts = [];
while ($r = $catStats?->fetch_assoc()) $catCounts[$r['category']] = (int)$r['cnt'];
$totalData = array_sum($catCounts);

// 3. 가중치 계산 (데이터 많을수록 해당 카테고리 신뢰도 ↑)
$weights = [];
if ($totalData > 0) {
    foreach ($catCounts as $cat => $cnt) {
        $ratio = $cnt / $totalData;
        // 0.5 ~ 1.5 범위로 클램프
        $weight = max(0.5, min(1.5, 0.8 + $ratio * 2.0));
        $weights[$cat] = round($weight, 2);
    }
}

// 4. 의사결정 결과 반영 (hit 많을수록 가중치 보정 ↑)
if ($total >= 3) {
    $hitRatio = $hits / $total;
    $bonus    = ($hitRatio - 0.5) * 0.2; // -0.1 ~ +0.1
    foreach ($weights as $cat => &$w) {
        $w = max(0.5, min(1.5, $w + $bonus));
    }
    unset($w);
}

// 5. AI 성향 요약 생성
$summary = generateAISummary($db, $esc, $weights, $matchRate, $catCounts, $user);

// 6. DB 갱신
$wJson     = $db->real_escape_string(json_encode($weights, JSON_UNESCAPED_UNICODE));
$summaryEs = $db->real_escape_string(mb_substr($summary, 0, 500));
$totalDec  = (int)($db->query("SELECT COUNT(*) AS c FROM mychat_decisions WHERE mem_id='{$esc}'")?->fetch_assoc()['c']??0);

$db->query(
    "INSERT INTO mychat_avatar (mem_id, weights_json, match_rate, decision_count, data_count)
     VALUES ('{$esc}','{$wJson}',{$matchRate},{$totalDec},{$totalData})
     ON DUPLICATE KEY UPDATE
       weights_json='{$wJson}', match_rate={$matchRate},
       decision_count={$totalDec}, data_count={$totalData},
       persona_prompt=COALESCE(NULLIF('{$summaryEs}',''), persona_prompt),
       updated_at=NOW()"
);

mychat_json([
    'ok'          => true,
    'match_rate'  => $matchRate,
    'weights'     => $weights,
    'data_total'  => $totalData,
    'dec_total'   => $totalDec,
    'hits'        => $hits,
    'misses'      => $misses,
    'summary'     => $summary,
    'insights'    => generateInsights($weights, $matchRate, $total),
]);

// ── 헬퍼 ─────────────────────────────────────────────────────
function generateInsights(array $weights, float $matchRate, int $decTotal): array {
    // matchRate 는 0~100 (퍼센트) 기준
    $pct = (int)round($matchRate);
    $insights = [];
    if ($pct >= 70) $insights[] = "의사결정 정확도가 {$pct}%로 매우 높습니다. 직관이 발달해 있습니다.";
    elseif ($pct >= 50) $insights[] = "의사결정 정확도 {$pct}%. 데이터를 더 쌓으면 개선됩니다.";
    elseif ($decTotal > 0) $insights[] = "의사결정 {$decTotal}건 추적 중. 회고를 통해 패턴을 발견하세요.";

    $strongCats = array_filter($weights, fn($w) => $w >= 1.2);
    if ($strongCats) {
        $catNames = ['basic'=>'기본정보','diary'=>'일기','health'=>'건강','phone'=>'통화'];
        $names = implode(', ', array_map(fn($c)=>$catNames[$c]??$c, array_keys($strongCats)));
        $insights[] = "가장 풍부한 데이터: {$names}";
    }

    $weakCats = array_filter($weights, fn($w) => $w < 0.7);
    if ($weakCats && count($weights) > 3) {
        $catNames = ['fingerprint'=>'지문','palmistry'=>'손금','saju'=>'사주','astrology'=>'점성술'];
        $names = implode(', ', array_map(fn($c)=>$catNames[$c]??$c, array_keys($weakCats)));
        $insights[] = "데이터가 부족한 채널: {$names}";
    }
    return $insights;
}

function generateAISummary($db, $esc, array $weights, int $matchRate, array $catCounts, array $user): string {
    if (array_sum($catCounts) < 3) return '';

    // 최근 일기 내용 샘플
    $sample = $db->query(
        "SELECT LEFT(content_text, 300) AS txt FROM mychat_data_pool
         WHERE mem_id='{$esc}' AND category='diary' AND is_deleted=0
         ORDER BY id DESC LIMIT 3"
    );
    $diaries = [];
    while ($r = $sample?->fetch_assoc()) $diaries[] = $r['txt'];

    if (empty($diaries)) return '';

    $catStr = implode(', ', array_keys($catCounts));
    $sampleText = implode("\n---\n", $diaries);
    $sys = "사용자의 학습 데이터를 분석해서 성향·말투·가치관을 2~3문장으로 요약해주세요. AI 아바타의 페르소나 설정에 사용됩니다.";
    $prompt = "데이터 카테고리: {$catStr}\n의사결정 정확도: {$matchRate}%\n\n최근 일기 샘플:\n{$sampleText}";

    $aiR = mychat_ai_reply($sys, $prompt, 200, $user);
    return $aiR['ok'] ? trim($aiR['content']) : '';
}
