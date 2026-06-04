<?php
/**
 * [ME-C4] 원챗 라이프 아바타 — 자가 학습 엔진 v1
 *
 * 엔드포인트:
 *   GET  /aimessage/onechat/api/me_learning.php
 *        → 현재 학습 상태 (weights_json, summary_text, 인사이트) 조회
 *
 *   POST /aimessage/onechat/api/me_learning.php?action=learn
 *        body(JSON, optional): {}
 *        → 즉시 학습 실행 (의사결정 분석 → weights/summary 갱신)
 *
 * 학습 루프:
 *   1) 사용자 의사결정에서 match_label(hit/partial/miss) 집계
 *   2) 카테고리별 가중치 자동 조정 (보수적, 0.5~1.5 클램프)
 *   3) summary_text 단문 자동 생성 (한국어, 인사이트 요약)
 *   4) match_rate(전체) avatar 캐시 컬럼 갱신
 *
 * 가중치 공식:
 *   - 베이스 1.00
 *   - hit_rate ≥ 70%: weight = min(1.5, 1.0 + (rate-70)/100)
 *   - hit_rate ≤ 30%: weight = max(0.5, 1.0 - (30-rate)/100)
 *   - 그 사이: 1.00 근접 (선형 보간)
 *
 * 인증/보안:
 *   - onechat_auth() 세션 인증
 *   - Panic Lock 잠금 상태에서 학습 실행 차단 (423 LOCKED)
 *   - 조회(GET)는 잠금 무관 허용
 *
 * 의존: Gn_onechat_me_avatar, Gn_onechat_me_decisions, Gn_onechat_me_data_pool
 *
 * Author : OneChat Life Avatar team (Ari)
 * Created: 2026-05-23 (C-4 v1)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();

$login_id = onechat_auth();
$db       = getDatabaseConnection();
if (!$db) { onechat_json(['error' => 'DB 연결 실패'], 500); }
@$db->set_charset('utf8mb4');

// ────────────────────────────────────────────────────────────
// 공통 유틸
// ────────────────────────────────────────────────────────────
function mel_esc($db, $v) { return $db->real_escape_string((string)$v); }

/** 아바타 행 조회 (없으면 자동 생성). 단일 행 배열 또는 null 반환 */
function mel_load_avatar($db, $login_id) {
    $le = mel_esc($db, $login_id);
    $sql = "SELECT idx, mem_id, avatar_name, identity_text,
                   summary_text, weights_json,
                   data_pool_count, decision_count, match_rate,
                   status, panic_locked_at,
                   created_at, updated_at
            FROM Gn_onechat_me_avatar
            WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
            LIMIT 1";
    $r = $db->query($sql);
    if ($r && $r->num_rows > 0) {
        return $r->fetch_assoc();
    }
    // 자동 생성
    $ins = "INSERT IGNORE INTO Gn_onechat_me_avatar (mem_id, avatar_name, status)
            VALUES ('{$le}', '나의 아바타', 'active')";
    @$db->query($ins);
    $r2 = $db->query($sql);
    return ($r2 && $r2->num_rows > 0) ? $r2->fetch_assoc() : null;
}

/**
 * 카테고리별 히트율 집계
 *
 * 의사결정의 category 정보가 me_decisions 자체에는 없으므로,
 * me_data_pool에서 category='decision' + 같은 mem_id의 결정 메타와 연결한다.
 * v1은 단순화하여 전체 결정의 match_label을 카운트하고,
 * data_pool의 카테고리 분포를 기반으로 카테고리별 가중치를 부여한다.
 *
 * 반환:
 *  [
 *    'overall' => ['hit','partial','miss','pending','total','match_rate'],
 *    'categories' => [
 *       'basic' => ['volume', 'avg_weight']
 *       ...
 *    ],
 *    'recent_misses' => [..decisions..]  // 최근 miss 결정 3건
 *  ]
 */
function mel_aggregate($db, $login_id) {
    $le = mel_esc($db, $login_id);

    // 1) 전체 의사결정 매칭 집계
    $q = $db->query("
        SELECT
          SUM(CASE WHEN match_label = 'hit'     THEN 1 ELSE 0 END) AS hit_cnt,
          SUM(CASE WHEN match_label = 'partial' THEN 1 ELSE 0 END) AS partial_cnt,
          SUM(CASE WHEN match_label = 'miss'    THEN 1 ELSE 0 END) AS miss_cnt,
          SUM(CASE WHEN match_label = 'pending' THEN 1 ELSE 0 END) AS pending_cnt,
          COUNT(*) AS total_cnt,
          AVG(CASE WHEN match_score IS NOT NULL THEN match_score ELSE NULL END) AS avg_score
        FROM Gn_onechat_me_decisions
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
          AND is_deleted = 0
    ");
    $row = $q ? $q->fetch_assoc() : null;
    $hit     = (int)($row['hit_cnt']     ?? 0);
    $partial = (int)($row['partial_cnt'] ?? 0);
    $miss    = (int)($row['miss_cnt']    ?? 0);
    $pending = (int)($row['pending_cnt'] ?? 0);
    $total   = (int)($row['total_cnt']   ?? 0);
    $reflected = $hit + $partial + $miss;  // pending 제외

    // match_rate: (hit*1.0 + partial*0.5) / reflected * 100
    $match_rate = null;
    if ($reflected > 0) {
        $match_rate = round((($hit * 1.0) + ($partial * 0.5)) / $reflected * 100, 2);
    }
    $avg_score = isset($row['avg_score']) && $row['avg_score'] !== null
                  ? round((float)$row['avg_score'], 2) : null;

    // 2) data_pool 카테고리 분포 (is_deleted=0)
    $cats = [];
    $cq = $db->query("
        SELECT category, COUNT(*) AS c, AVG(weight) AS aw
        FROM Gn_onechat_me_data_pool
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
          AND is_deleted = 0
        GROUP BY category
    ");
    if ($cq) {
        while ($r = $cq->fetch_assoc()) {
            $cats[$r['category']] = [
                'volume'     => (int)$r['c'],
                'avg_weight' => round((float)$r['aw'], 2),
            ];
        }
    }

    // 3) 최근 miss 결정 3건 (학습 약점 추적용)
    $misses = [];
    $mq = $db->query("
        SELECT idx, title, match_score, outcome_at
        FROM Gn_onechat_me_decisions
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
          AND is_deleted = 0
          AND match_label = 'miss'
        ORDER BY outcome_at DESC, idx DESC
        LIMIT 3
    ");
    if ($mq) {
        while ($r = $mq->fetch_assoc()) {
            $misses[] = [
                'idx'         => (int)$r['idx'],
                'title'       => $r['title'],
                'match_score' => $r['match_score'] !== null ? (float)$r['match_score'] : null,
                'outcome_at'  => $r['outcome_at'],
            ];
        }
    }

    return [
        'overall' => [
            'hit'         => $hit,
            'partial'     => $partial,
            'miss'        => $miss,
            'pending'     => $pending,
            'total'       => $total,
            'reflected'   => $reflected,
            'match_rate'  => $match_rate,
            'avg_score'   => $avg_score,
        ],
        'categories'    => $cats,
        'recent_misses' => $misses,
    ];
}

/**
 * 카테고리별 가중치 계산
 *
 * v1 정책:
 *  - 전체 match_rate가 데이터 학습 신뢰도의 1차 신호
 *  - 카테고리별 volume이 많을수록 그 카테고리에 가중치 부여 (학습 데이터가 풍부)
 *  - 0.5 ~ 1.5 범위로 클램프
 *
 * 공식:
 *   base = 1.0
 *   trust = match_rate 기반 보정 (-0.3 ~ +0.5)
 *     - rate >= 70: bonus = (rate-70)/100   (최대 +0.30)
 *     - rate <= 30: penalty = (30-rate)/100 (최대 -0.30)
 *     - 그 사이: 0
 *   volume_bonus = 카테고리 volume / max_volume * 0.2  (최대 +0.20)
 *   weight = clamp(base + trust + volume_bonus, 0.5, 1.5)
 *
 *   pending 위주(reflected==0)이면 모든 weight=1.0 (학습 미시작)
 */
function mel_compute_weights($agg) {
    $cats = $agg['categories'];
    $rate = $agg['overall']['match_rate'];   // null 가능
    $reflected = $agg['overall']['reflected'];

    // 학습 신호가 없으면 중립 1.0
    if ($reflected === 0 || $rate === null) {
        $out = ['version' => 1, 'updated_at' => date('Y-m-d H:i:s'), 'categories' => []];
        foreach ($cats as $cat => $info) {
            $out['categories'][$cat] = [
                'weight'     => 1.0,
                'volume'     => $info['volume'],
                'avg_weight' => $info['avg_weight'],
                'note'       => 'no-signal',
            ];
        }
        $out['overall'] = $agg['overall'];
        return $out;
    }

    // trust 보정
    $trust = 0.0;
    if ($rate >= 70) {
        $trust = min(0.30, ($rate - 70) / 100.0);
    } elseif ($rate <= 30) {
        $trust = max(-0.30, -1.0 * (30 - $rate) / 100.0);
    }

    // max_volume
    $max_v = 0;
    foreach ($cats as $info) { if ($info['volume'] > $max_v) $max_v = $info['volume']; }

    $out_cats = [];
    foreach ($cats as $cat => $info) {
        $vol_bonus = ($max_v > 0) ? min(0.20, ($info['volume'] / $max_v) * 0.20) : 0.0;
        $w = 1.0 + $trust + $vol_bonus;
        if ($w < 0.5) $w = 0.5;
        if ($w > 1.5) $w = 1.5;
        $out_cats[$cat] = [
            'weight'     => round($w, 2),
            'volume'     => $info['volume'],
            'avg_weight' => $info['avg_weight'],
        ];
    }

    return [
        'version'    => 1,
        'updated_at' => date('Y-m-d H:i:s'),
        'overall'    => $agg['overall'],
        'categories' => $out_cats,
    ];
}

/**
 * 단문 요약 생성 (한국어 템플릿 기반, LLM 없이도 의미 있는 인사이트)
 */
function mel_compute_summary($agg, $weights) {
    $o = $agg['overall'];
    $reflected = $o['reflected'];
    $total     = $o['total'];

    if ($total === 0) {
        return '아직 학습할 의사결정이 없습니다. 결정을 기록하고 결과를 반영하면 아바타가 학습을 시작합니다.';
    }
    if ($reflected === 0) {
        return "기록된 의사결정 {$total}건이 모두 결과 대기 중입니다. 결과를 입력하면 학습이 시작됩니다.";
    }

    $rate = $o['match_rate'];
    $tone = '';
    if ($rate >= 70)      $tone = '강한 신호';
    elseif ($rate >= 50)  $tone = '안정';
    elseif ($rate >= 30)  $tone = '학습 중';
    else                  $tone = '재조정 필요';

    // 상위 가중치 카테고리 1~2개
    $cats = $weights['categories'] ?? [];
    uasort($cats, function($a,$b){ return ($b['weight'] <=> $a['weight']); });
    $top_names = [];
    $i = 0;
    foreach ($cats as $name => $info) {
        if ($info['weight'] >= 1.0 && $info['volume'] > 0) {
            $top_names[] = $name;
            $i++;
            if ($i >= 2) break;
        }
    }
    $top_txt = $top_names ? (' 강점 영역: ' . implode(', ', $top_names) . '.') : '';

    // 약점 (최근 miss)
    $miss_cnt = $o['miss'];
    $weak_txt = '';
    if ($miss_cnt > 0) {
        $weak_txt = " 최근 {$miss_cnt}건의 예측이 빗나갔습니다. 결정 맥락을 더 자세히 기록해 보세요.";
    }

    return "[{$tone}] 반영 {$reflected}건 / 전체 {$total}건, 매칭률 {$rate}%.{$top_txt}{$weak_txt}";
}

// ────────────────────────────────────────────────────────────
// 핸들러: GET (조회)
// ────────────────────────────────────────────────────────────
function handle_get($db, $login_id) {
    $avatar = mel_load_avatar($db, $login_id);
    if (!$avatar) onechat_json(['error' => '아바타 로딩 실패'], 500);

    $agg = mel_aggregate($db, $login_id);
    $weights_db = null;
    if (!empty($avatar['weights_json'])) {
        $j = json_decode($avatar['weights_json'], true);
        if (is_array($j)) $weights_db = $j;
    }

    onechat_json([
        'ok'           => true,
        'locked'       => ($avatar['status'] === 'locked'),
        'avatar' => [
            'mem_id'         => $avatar['mem_id'],
            'avatar_name'    => $avatar['avatar_name'],
            'match_rate'     => $avatar['match_rate'] !== null ? (float)$avatar['match_rate'] : null,
            'data_pool_count'=> (int)$avatar['data_pool_count'],
            'decision_count' => (int)$avatar['decision_count'],
            'summary_text'   => $avatar['summary_text'],
            'updated_at'     => $avatar['updated_at'],
        ],
        'aggregate'    => $agg,
        'weights'      => $weights_db,    // 저장된 weights_json (학습 전이면 null)
        'meta' => [
            'engine'  => 'me_learning_v1',
            'version' => 1,
        ],
    ]);
}

// ────────────────────────────────────────────────────────────
// 핸들러: POST?action=learn (즉시 학습)
// ────────────────────────────────────────────────────────────
function handle_learn($db, $login_id) {
    $avatar = mel_load_avatar($db, $login_id);
    if (!$avatar) onechat_json(['error' => '아바타 로딩 실패'], 500);

    // Panic Lock 차단
    if (($avatar['status'] ?? '') === 'locked') {
        onechat_json([
            'error' => ['code' => 'LOCKED', 'message' => '잠금 상태에서는 학습을 실행할 수 없습니다.']
        ], 423);
    }

    // 집계
    $agg = mel_aggregate($db, $login_id);

    // 가중치 계산
    $weights = mel_compute_weights($agg);

    // 단문 요약
    $summary = mel_compute_summary($agg, $weights);

    // 저장
    $le = mel_esc($db, $login_id);
    $wj = mel_esc($db, json_encode($weights, JSON_UNESCAPED_UNICODE));
    $st = mel_esc($db, $summary);
    $mr = $agg['overall']['match_rate'];
    $mr_sql = ($mr === null) ? 'NULL' : (string)((float)$mr);

    $sql = "UPDATE Gn_onechat_me_avatar
            SET weights_json = '{$wj}',
                summary_text = '{$st}',
                match_rate   = {$mr_sql},
                decision_count = (
                  SELECT COUNT(*) FROM Gn_onechat_me_decisions
                  WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}' AND is_deleted = 0
                ),
                updated_at = NOW()
            WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
            LIMIT 1";
    if (!$db->query($sql)) {
        onechat_json(['error' => '학습 결과 저장 실패: ' . $db->error], 500);
    }

    onechat_json([
        'ok'        => true,
        'learned'   => true,
        'aggregate' => $agg,
        'weights'   => $weights,
        'summary'   => $summary,
        'meta' => [
            'engine'  => 'me_learning_v1',
            'version' => 1,
        ],
    ]);
}

// ────────────────────────────────────────────────────────────
// 라우팅
// ────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    handle_get($db, $login_id);
} elseif ($method === 'POST') {
    $action = isset($_GET['action']) ? strtolower((string)$_GET['action']) : 'learn';
    if ($action === 'learn') {
        handle_learn($db, $login_id);
    } else {
        onechat_json(['error' => ['code' => 'UNKNOWN_ACTION', 'message' => "지원하지 않는 action: {$action}"]], 400);
    }
} else {
    onechat_json(['error' => 'Method Not Allowed'], 405);
}
