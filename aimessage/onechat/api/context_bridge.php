<?php
/**
 * 원챗(OneChat) Context Bridge API v2.0
 * ─────────────────────────────────────────────────────────────────
 * 아바타 학습 데이터 ↔ 챗봇 설정(프롬프트) 간 자동 연계 시스템.
 *
 * 기능:
 *   1. 유권자 질문 빈도 분석 → L2 Policy Domain 우선순위 재조정
 *   2. 자주 묻는 질문 TOP10 → L2 FAQ 항목 자동 추가
 *   3. 부정 감정 트리거 키워드 → L3 대화 전략에 주의 태그
 *   4. 신규 정책 업데이트 감지 → L2 Differential Update
 *   5. 학습 데이터 품질 리포트 → Admin 알림
 *
 * Endpoints:
 *   GET  ?action=insights        &sms_idx=X        → 유권자 인사이트 분석
 *   GET  ?action=faq             &sms_idx=X        → 자주 묻는 질문 TOP10
 *   GET  ?action=sentiment       &sms_idx=X        → 감정 분석 키워드
 *   GET  ?action=quality_report  &sms_idx=X        → 학습 품질 리포트
 *   POST ?action=bridge_update   &sms_idx=X        → Context Bridge 수동 갱신
 *   POST ?action=auto_sync       &sms_idx=X        → 자동 동기화 트리거
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$login_esc = $db->real_escape_string($login_id);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$sms_idx = (int)($_GET['sms_idx'] ?? ($_POST['sms_idx'] ?? 0));
$method = $_SERVER['REQUEST_METHOD'];

// ── HELPERS ────────────────────────────────────────────────────────

function onechat_json_v2(int $code, string $msg, array $data = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['code' => $code, 'message' => $msg], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function verify_ownership(mysqli $db, int $sms_idx, string $login_id): array {
    $s = $db->real_escape_string($login_id);
    $result = $db->query("SELECT sms_idx, chatbot_name FROM Gn_aievent_ms_info WHERE sms_idx = {$sms_idx} AND mb_id = '{$s}'");
    if (!$result || $result->num_rows === 0) {
        onechat_json_v2(403, '접근 권한이 없습니다.');
    }
    return $result->fetch_assoc();
}

// ── CHAT LOG TABLE (가상: 실제 로그 테이블에 맞게 조정 필요) ────────
// 아래 쿼리들은 Gn_onechat_chat_log 테이블이 존재한다고 가정합니다.
// 실제 환경에 맞게 테이블명과 컬럼명을 조정하세요.

function get_chat_logs(mysqli $db, int $sms_idx, int $days = 30): array {
    $table = 'Gn_onechat_chat_log'; // 실제 테이블명으로 변경
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // fallback: chat_log 테이블이 없으면 빈 배열 반환
    $check = $db->query("SHOW TABLES LIKE '{$table}'");
    if ($check->num_rows === 0) {
        return [];
    }

    $result = $db->query("
        SELECT question, answer, feedback_score, created_at
        FROM {$table}
        WHERE sms_idx = {$sms_idx}
          AND created_at >= '{$cutoff}'
        ORDER BY created_at DESC
    ");
    if (!$result) return [];

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    return $logs;
}

// ── INSIGHTS: 유권자 인사이트 분석 ──────────────────────────────────

if ($action === 'insights') {
    verify_ownership($db, $sms_idx, $login_id);
    $days = (int)($_GET['days'] ?? 30);
    $logs = get_chat_logs($db, $sms_idx, $days);

    if (empty($logs)) {
        onechat_json_v2(200, '아직 충분한 대화 데이터가 없습니다.', [
            'insights' => [],
            'total_questions' => 0,
            'period_days' => $days
        ]);
    }

    // 카테고리별 / 키워드별 질문 빈도 분석
    $categories = [
        '공약·정책' => ['공약', '정책', '약속', '계획', '추진', '예산'],
        '인물·이력' => ['경력', '학력', '가족', '이력', '출신', '경험'],
        '지역현안' => ['지역', '개발', '교통', '주택', '환경', '복지'],
        '선거·투표' => ['투표', '선거', '후보', '지지', '경쟁', '여론'],
        '비판·의혹' => ['비판', '의혹', '논란', '거짓', '팩트', '해명'],
        '기타' => []
    ];

    $catCounts = [];
    foreach ($categories as $cat => $keywords) {
        $catCounts[$cat] = 0;
    }

    $keywordCounts = [];
    $totalQuestions = count($logs);

    foreach ($logs as $log) {
        $q = $log['question'] ?? '';
        $matched = false;
        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($q, $kw) !== false) {
                    $catCounts[$cat]++;
                    $matched = true;
                    break 2;
                }
            }
        }
        if (!$matched) {
            $catCounts['기타']++;
        }

        // 키워드 추출 (간단: 2글자 이상 단어)
        $words = preg_split('/[\s,.\?!]+/u', $q);
        foreach ($words as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 2) {
                $keywordCounts[$w] = ($keywordCounts[$w] ?? 0) + 1;
            }
        }
    }

    arsort($catCounts);
    arsort($keywordCounts);
    $topKeywords = array_slice($keywordCounts, 0, 30, true);

    // 인기 공약 TOP5
    $topCategories = array_slice($catCounts, 0, 5, true);

    onechat_json_v2(200, 'ok', [
        'insights' => [
            'total_questions' => $totalQuestions,
            'period_days' => $days,
            'top_categories' => $topCategories,
            'top_keywords' => $topKeywords,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

// ── FAQ: 자주 묻는 질문 TOP10 ──────────────────────────────────────

elseif ($action === 'faq') {
    verify_ownership($db, $sms_idx, $login_id);
    $days = (int)($_GET['days'] ?? 30);
    $logs = get_chat_logs($db, $sms_idx, $days);

    if (empty($logs)) {
        onechat_json_v2(200, 'FAQ 데이터가 아직 충분하지 않습니다.', ['faqs' => []]);
    }

    // 유사 질문 클러스터링 (간단: 정규화 후 그룹화)
    $normalized = [];
    foreach ($logs as $log) {
        $q = trim($log['question'] ?? '');
        if (mb_strlen($q) < 5) continue;

        // 정규화: 끝의 ?! 제거, 공백 통일
        $norm = preg_replace('/[?!！？]+$/u', '', $q);
        $norm = preg_replace('/\s+/u', ' ', $norm);
        $norm = mb_strtolower($norm);

        if (!isset($normalized[$norm])) {
            $normalized[$norm] = [
                'question' => $q,
                'count' => 0,
                'avg_score' => 0,
                'scores' => []
            ];
        }
        $normalized[$norm]['count']++;
        if (isset($log['feedback_score'])) {
            $normalized[$norm]['scores'][] = (float)$log['feedback_score'];
        }
    }

    // 카운트 기준 정렬
    uasort($normalized, function ($a, $b) { return $b['count'] - $a['count']; });

    $faqs = [];
    foreach (array_slice($normalized, 0, 10) as $item) {
        $avgScore = count($item['scores']) > 0
            ? round(array_sum($item['scores']) / count($item['scores']), 2)
            : 0;
        $faqs[] = [
            'question' => $item['question'],
            'count' => $item['count'],
            'avg_feedback_score' => $avgScore
        ];
    }

    onechat_json_v2(200, 'ok', ['faqs' => $faqs, 'generated_at' => date('Y-m-d H:i:s')]);
}

// ── SENTIMENT: 감정 분석 키워드 ─────────────────────────────────────

elseif ($action === 'sentiment') {
    verify_ownership($db, $sms_idx, $login_id);
    $days = (int)($_GET['days'] ?? 30);
    $logs = get_chat_logs($db, $sms_idx, $days);

    if (empty($logs)) {
        onechat_json_v2(200, '감정 데이터가 충분하지 않습니다.', [
            'negative_triggers' => [],
            'positive_triggers' => []
        ]);
    }

    // 부정 감정 사전 (매칭용)
    $negativeWords = [
        '싫다', '별로', '실망', '거짓말', '사기', '허위', '의심',
        '불만', '화나', '열받', '짜증', '답답', '비판', '욕',
        '못한다', '안된다', '부족', '미흡', '헛점', '구멍',
        '무능', '무책임', '거짓', '속았다', '배신', '기만'
    ];

    $positiveWords = [
        '좋다', '기대', '응원', '지지', '감사', '멋지다', '훌륭',
        '최고', '잘한다', '믿음', '신뢰', '든든', '희망', '변화',
        '지원', '도움', '감동', '자랑', '존경'
    ];

    $negTriggers = [];
    $posTriggers = [];

    foreach ($logs as $log) {
        $q = $log['question'] ?? '';
        $a = $log['answer'] ?? '';
        $combined = $q . ' ' . $a;

        foreach ($negativeWords as $w) {
            if (mb_stripos($combined, $w) !== false) {
                $negTriggers[$w] = ($negTriggers[$w] ?? 0) + 1;
            }
        }
        foreach ($positiveWords as $w) {
            if (mb_stripos($combined, $w) !== false) {
                $posTriggers[$w] = ($posTriggers[$w] ?? 0) + 1;
            }
        }
    }

    arsort($negTriggers);
    arsort($posTriggers);

    onechat_json_v2(200, 'ok', [
        'negative_triggers' => array_slice($negTriggers, 0, 15, true),
        'positive_triggers' => array_slice($posTriggers, 0, 15, true),
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

// ── QUALITY REPORT: 학습 데이터 품질 리포트 ────────────────────────

elseif ($action === 'quality_report') {
    verify_ownership($db, $sms_idx, $login_id);

    // RAG 서버에서 통계 가져오기
    $ragHost = '127.0.0.1';
    $ragPort = 5100;

    $stats = ['total_chunks' => 0, 'categories' => [], 'total_feedback' => 0, 'avg_rating' => 0];

    $ch = curl_init("http://{$ragHost}:{$ragPort}/stats/{$sms_idx}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $resp) {
        $data = json_decode($resp, true);
        if (isset($data['stats'])) {
            $stats = $data['stats'];
        }
    }

    // 저품질 청크 경고
    $warnings = [];
    if ($stats['total_chunks'] < 10) {
        $warnings[] = ['level' => 'danger', 'msg' => '학습 데이터가 매우 부족합니다. (10건 미만)'];
    } elseif ($stats['total_chunks'] < 100) {
        $warnings[] = ['level' => 'warning', 'msg' => '학습 데이터가 부족합니다. (100건 미만 — 500건 이상 권장)'];
    }

    if ($stats['avg_rating'] < 0.5 && $stats['total_feedback'] > 10) {
        $warnings[] = ['level' => 'warning', 'msg' => '유저 피드백 평점이 낮습니다. (' . $stats['avg_rating'] . '/1.0)'];
    }

    // 카테고리별 진단
    $catCheck = $stats['categories'] ?? [];
    $requiredCats = ['정책·공약', '인물·이력', '발언·연설'];
    foreach ($requiredCats as $rc) {
        if (!isset($catCheck[$rc]) || $catCheck[$rc] < 50) {
            $warnings[] = ['level' => 'info', 'msg' => "'{$rc}' 카테고리 데이터 보강 필요"];
        }
    }

    onechat_json_v2(200, 'ok', [
        'stats' => $stats,
        'warnings' => $warnings,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

// ── BRIDGE UPDATE: Context Bridge 수동 갱신 ────────────────────────

elseif ($action === 'bridge_update') {
    if ($method !== 'POST') onechat_json_v2(405, 'POST only');
    $owner = verify_ownership($db, $sms_idx, $login_id);

    // 3가지 인사이트 동시 수집 → bridge_json 생성
    $bridge = [
        'updated_at' => date('Y-m-d H:i:s'),
        'sms_idx' => $sms_idx,
        'chatbot_name' => $owner['chatbot_name'],
    ];

    // 1) FAQ
    ob_start();
    $_GET['action'] = 'faq';
    $_GET['sms_idx'] = ''; // trick: run inline
    // 간단히 쿼리 직접 실행
    $logs = get_chat_logs($db, $sms_idx, 30);

    $normalized = [];
    foreach ($logs as $log) {
        $q = trim($log['question'] ?? '');
        if (mb_strlen($q) < 5) continue;
        $norm = preg_replace('/[?!！？]+$/u', '', $q);
        $norm = preg_replace('/\s+/u', ' ', $norm);
        $norm = mb_strtolower($norm);
        $normalized[$norm] = ($normalized[$norm] ?? 0) + 1;
    }
    uasort($normalized, function ($a, $b) { return $b - $a; });
    $bridge['faq_top10'] = array_slice(array_keys($normalized), 0, 10);

    // 2) Sentiment
    $negTriggers = [];
    $negWords = ['싫다', '별로', '실망', '거짓말', '사기', '불만', '화나', '비판', '욕', '못한다', '안된다'];
    foreach ($logs as $log) {
        $combined = ($log['question'] ?? '') . ' ' . ($log['answer'] ?? '');
        foreach ($negWords as $w) {
            if (mb_stripos($combined, $w) !== false) {
                $negTriggers[$w] = ($negTriggers[$w] ?? 0) + 1;
            }
        }
    }
    arsort($negTriggers);
    $bridge['negative_triggers'] = array_slice(array_keys($negTriggers), 0, 10);

    // 3) Insights
    $catCounts = [];
    $catMap = [
        '공약·정책' => ['공약', '정책', '약속', '계획', '추진'],
        '인물·이력' => ['경력', '학력', '가족', '이력'],
        '지역현안' => ['지역', '개발', '교통', '주택'],
        '선거·투표' => ['투표', '선거', '후보', '지지'],
    ];
    foreach ($catMap as $cat => $kws) $catCounts[$cat] = 0;
    foreach ($logs as $log) {
        $q = $log['question'] ?? '';
        foreach ($catMap as $cat => $kws) {
            foreach ($kws as $kw) {
                if (mb_strpos($q, $kw) !== false) { $catCounts[$cat]++; break 2; }
            }
        }
    }
    arsort($catCounts);
    $bridge['popular_categories'] = array_keys(array_slice($catCounts, 0, 3));

    // DB 저장
    $bridgeJson = $db->real_escape_string(json_encode($bridge, JSON_UNESCAPED_UNICODE));
    $db->query("UPDATE Gn_aievent_ms_info SET context_bridge_config='{$bridgeJson}', context_bridge_enabled=1 WHERE sms_idx={$sms_idx}");

    onechat_json_v2(200, 'Context Bridge 갱신 완료', ['bridge' => $bridge]);
}

// ── AUTO SYNC: 자동 동기화 트리거 (cron) ───────────────────────────

elseif ($action === 'auto_sync') {
    // cron에서 호출: 모든 활성 후보자에 대해 bridge_update 실행
    $secret = $_SERVER['HTTP_X_ONECHAT_CRON_SECRET'] ?? '';
    $expected = defined('ONECHAT_CRON_SECRET') ? ONECHAT_CRON_SECRET : '';

    if ($secret !== $expected) {
        onechat_json_v2(403, '크론 전용 액션입니다.');
    }

    $result = $db->query("
        SELECT sms_idx FROM Gn_aievent_ms_info
        WHERE ai_prompt IN ('onechat','onechat2') AND context_bridge_enabled=1
    ");
    if (!$result) onechat_json_v2(500, '쿼리 실패');

    $synced = [];
    while ($row = $result->fetch_assoc()) {
        $idx = (int)$row['sms_idx'];
        // bridge_update 로직 재사용 (간략 버전)
        $logs = get_chat_logs($db, $idx, 30);
        if (empty($logs)) {
            $synced[] = ['sms_idx' => $idx, 'status' => 'skipped (no logs)'];
            continue;
        }

        $bridge = [
            'updated_at' => date('Y-m-d H:i:s'),
            'sms_idx' => $idx,
            'total_analyzed' => count($logs)
        ];

        $normMap = [];
        foreach ($logs as $log) {
            $q = trim($log['question'] ?? '');
            if (mb_strlen($q) < 5) continue;
            $norm = preg_replace('/[?!！？]+$/u', '', $q);
            $normMap[$norm] = ($normMap[$norm] ?? 0) + 1;
        }
        uasort($normMap, function($a,$b){return $b-$a;});
        $bridge['faq_top10'] = array_slice(array_keys($normMap), 0, 10);

        $bridgeJson = $db->real_escape_string(json_encode($bridge, JSON_UNESCAPED_UNICODE));
        $db->query("UPDATE Gn_aievent_ms_info SET context_bridge_config='{$bridgeJson}' WHERE sms_idx={$idx}");
        $synced[] = ['sms_idx' => $idx, 'status' => 'synced', 'faq_count' => count($bridge['faq_top10'] ?? [])];
    }

    onechat_json_v2(200, '자동 동기화 완료', ['synced' => $synced, 'total' => count($synced)]);
}

// ── DEFAULT ────────────────────────────────────────────────────────

else {
    onechat_json_v2(400, '알 수 없는 action입니다. (insights, faq, sentiment, quality_report, bridge_update, auto_sync)');
}