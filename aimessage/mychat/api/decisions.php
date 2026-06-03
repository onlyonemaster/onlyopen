<?php
/**
 * 마이챗 의사결정 API
 * GET  → 목록 조회
 * POST → action: predict | decide | reflect
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ai_helper.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// ── GET: 목록 조회 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $r = $db->query(
        "SELECT id, situation, ai_options, ai_prediction, chosen_option,
                outcome_text, match_score, match_label, status, decided_at, created_at
         FROM mychat_decisions WHERE mem_id='{$esc}' ORDER BY id DESC LIMIT 100"
    );
    $decs = [];
    while ($row = $r?->fetch_assoc()) {
        if (!empty($row['ai_options'])) { $j=json_decode($row['ai_options'],true); if($j!==null) $row['ai_options']=$j; }
        $decs[] = $row;
    }
    mychat_json(['ok'=>true, 'decisions'=>$decs]);
}

// ── POST ──────────────────────────────────────────────────────
$b = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $b['action'] ?? '';

// PREDICT — 상황 입력 → AI 분석 + 선택지 생성
if ($action === 'predict') {
    $situation = mb_substr(trim($b['situation'] ?? ''), 0, 1000);
    if (!$situation) mychat_json(['ok'=>false,'error'=>'상황을 입력해 주세요'], 400);

    // 아바타 컨텍스트 로드
    $av = $db->query("SELECT persona_prompt, weights_json FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
    $persona = $av['persona_prompt'] ?? '';

    // 관련 과거 결정 조회
    $past = [];
    $pr = $db->query("SELECT situation, chosen_option, match_label FROM mychat_decisions WHERE mem_id='{$esc}' AND status='reflected' ORDER BY id DESC LIMIT 5");
    while ($row = $pr?->fetch_assoc()) $past[] = $row;

    $sys = "당신은 {$mem_id}님의 개인 AI 아바타입니다. 의사결정 분석 전문가 역할을 합니다.\n";
    if ($persona) $sys .= "사용자 성향: {$persona}\n";
    if ($past) {
        $sys .= "\n[과거 의사결정 패턴]\n";
        foreach ($past as $p) {
            $sys .= "- 상황: ".$p['situation']." / 선택: ".$p['chosen_option']." / 결과: ".($p['match_label']??'추적중')."\n";
        }
    }
    $sys .= "\n[출력 형식 — JSON만 출력, 코드블록 없이]\n";
    $sys .= '{"ai_prediction":"상황 분석 및 핵심 고려사항 (200자 이내)","options":[{"key":"A","label":"선택지 A 설명 (50자 이내)"},{"key":"B","label":"선택지 B 설명"},{"key":"C","label":"선택지 C 설명"}]}';

    // AI 호출
    $aiR = mychat_ai_reply($sys, "다음 상황을 분석해 주세요:\n\n{$situation}", 600, $user);
    $resp = $aiR['ok'] ? $aiR['content'] : '';
    // JSON 블록 추출
    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $resp, $mm)) $resp = $mm[1];
    $parsed = json_decode(trim($resp), true);

    if (!$parsed || empty($parsed['options'])) {
        // 폴백 선택지
        $parsed = [
            'ai_prediction' => $resp ?: '분석 중 오류가 발생했습니다.',
            'options' => [['key'=>'A','label'=>'진행한다'],['key'=>'B','label'=>'보류한다'],['key'=>'C','label'=>'다른 방법을 찾는다']]
        ];
    }

    // DB 저장
    $esc_sit  = $db->real_escape_string($situation);
    $esc_pred = $db->real_escape_string($parsed['ai_prediction'] ?? '');
    $esc_opts = $db->real_escape_string(json_encode($parsed['options'], JSON_UNESCAPED_UNICODE));
    $db->query("INSERT INTO mychat_decisions (mem_id, situation, ai_prediction, ai_options, status)
                VALUES ('{$esc}','{$esc_sit}','{$esc_pred}','{$esc_opts}','pending')");
    $dec_id = $db->insert_id;

    $dec = $db->query("SELECT * FROM mychat_decisions WHERE id={$dec_id}")?->fetch_assoc();
    mychat_json(['ok'=>true, 'decision'=>$dec]);
}

// DECIDE — 선택지 확정
if ($action === 'decide') {
    $id     = (int)($b['id'] ?? 0);
    $chosen = $db->real_escape_string($b['chosen_option'] ?? '');
    if (!$id || !$chosen) mychat_json(['ok'=>false,'error'=>'id, chosen_option 필수'], 400);

    $db->query("UPDATE mychat_decisions SET chosen_option='{$chosen}', status='decided', decided_at=NOW()
                WHERE id={$id} AND mem_id='{$esc}'");
    // 30일 후 회고 알림 설정
    $db->query("UPDATE mychat_decisions SET reflect_remind=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id={$id} AND mem_id='{$esc}'");
    mychat_json(['ok'=>true]);
}

// REFLECT — 회고 입력 → 가중치 자동 업데이트
if ($action === 'reflect') {
    $id      = (int)($b['id'] ?? 0);
    $outcome = $db->real_escape_string(mb_substr($b['outcome_text'] ?? '', 0, 2000));
    $label   = in_array($b['match_label']??'', ['hit','partial','miss']) ? $b['match_label'] : 'miss';
    $scoreMap = ['hit'=>1.0,'partial'=>0.5,'miss'=>0.0];
    $score = isset($b['match_score']) ? (float)$b['match_score'] : ($scoreMap[$label] ?? 0.0);

    $db->query("UPDATE mychat_decisions
                SET outcome_text='{$outcome}', match_label='{$label}', match_score={$score}, status='reflected'
                WHERE id={$id} AND mem_id='{$esc}'");

    // 아바타 match_rate, decision_count 갱신
    $stats = $db->query("SELECT COUNT(*) AS tot, AVG(match_score) AS avg_score
                          FROM mychat_decisions WHERE mem_id='{$esc}' AND status='reflected'")?->fetch_assoc();
    $newRate  = round(((float)($stats['avg_score']??0))*100);
    $decCount = (int)($stats['tot']??0);
    $db->query("UPDATE mychat_avatar SET match_rate={$newRate}, decision_count={$decCount} WHERE mem_id='{$esc}'");

    mychat_json(['ok'=>true, 'new_match_rate'=>$newRate]);
}

mychat_json(['ok'=>false,'error'=>'알 수 없는 action'], 400);

// ── 헬퍼 (AI 호출은 ai_helper.php 의 mychat_ai_reply 사용) ──

