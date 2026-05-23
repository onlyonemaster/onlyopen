<?php
/**
 * [ME-C3] 원챗 라이프 아바타 — 대시보드 통계 API
 *
 * 엔드포인트:
 *   GET /aimessage/onechat/api/me_stats.php
 *       → 한 번 호출로 대시보드 6개 위젯 데이터 일괄 반환
 *
 * 반환 구조:
 * {
 *   ok: true,
 *   avatar: { status, panic_locked_at, data_pool_count, ... },
 *   total: { all, active(=non_deleted), deleted },
 *   scope: { private, public, both },
 *   privacy: { "1":N, "2":N, "3":N, "4":N, "5":N },
 *   category: [{ key, count, scope_private, scope_public, scope_both }, ...] // 13개 모두
 *   timeline: [{ date:"YYYY-MM-DD", count:N }, ...]  // 최근 30일 (오늘 포함)
 *   recent: [{ idx, category, scope, title, privacy_level, created_at }, ...] // 최근 5개 (panic_lock 시 privacy>=4 제외)
 *   health: { score:0~100, breakdown:{volume, balance, privacy, recency}, message }
 *   locked: bool   // 현재 lock 상태
 * }
 *
 * 인증/보안:
 *   - onechat_auth() 세션 인증 필수
 *   - panic_lock 상태: recent 목록에서 privacy_level >= 4 자동 제외 (B-5 정책 일치)
 *   - 다른 사용자 데이터 노출 불가 (mem_id COLLATE 일관 적용)
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23 (C-3)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();

$db = getDatabaseConnection();
if (!$db) { onechat_json(['error' => 'DB 연결 실패'], 500); }
@$db->set_charset('utf8mb4');

// ────────────────────────────────────────────────────────────
// 공통 유틸
// ────────────────────────────────────────────────────────────
function mes_esc($db, $v) { return $db->real_escape_string((string)$v); }

function mes_q1($db, $sql) {
    $r = $db->query($sql);
    if (!$r) return null;
    $row = $r->fetch_assoc();
    return $row;
}

function mes_qall($db, $sql) {
    $r = $db->query($sql);
    if (!$r) return [];
    $out = [];
    while ($row = $r->fetch_assoc()) { $out[] = $row; }
    return $out;
}

// ────────────────────────────────────────────────────────────
// 1) 아바타 상태 (없으면 자동 생성)
// ────────────────────────────────────────────────────────────
$le  = mes_esc($db, $login_id);
$row = mes_q1($db, "
    SELECT idx, mem_id, avatar_name, identity_text,
           data_pool_count, decision_count, match_rate,
           status, panic_locked_at, current_mode,
           created_at, updated_at
    FROM Gn_onechat_me_avatar
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
    LIMIT 1
");
if (!$row) {
    $db->query("INSERT IGNORE INTO Gn_onechat_me_avatar (mem_id, status, created_at, updated_at)
                VALUES ('{$le}', 'active', NOW(), NOW())");
    $row = mes_q1($db, "
        SELECT idx, mem_id, avatar_name, identity_text,
               data_pool_count, decision_count, match_rate,
               status, panic_locked_at, current_mode,
               created_at, updated_at
        FROM Gn_onechat_me_avatar
        WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
        LIMIT 1
    ");
}
$avatar = $row ?: [
    'status' => 'active', 'panic_locked_at' => null, 'data_pool_count' => 0,
];
$is_locked = ($avatar['status'] === 'locked');

// ────────────────────────────────────────────────────────────
// 2) 전체 합계 (활성/삭제)
// ────────────────────────────────────────────────────────────
$cnt = mes_q1($db, "
    SELECT
        SUM(CASE WHEN is_deleted=0 THEN 1 ELSE 0 END) AS active_cnt,
        SUM(CASE WHEN is_deleted=1 THEN 1 ELSE 0 END) AS deleted_cnt,
        COUNT(*) AS all_cnt
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
");
$total = [
    'all'     => (int)($cnt['all_cnt']     ?? 0),
    'active'  => (int)($cnt['active_cnt']  ?? 0),
    'deleted' => (int)($cnt['deleted_cnt'] ?? 0),
];

// ────────────────────────────────────────────────────────────
// 3) Scope 분포
// ────────────────────────────────────────────────────────────
$scope_rows = mes_qall($db, "
    SELECT scope, COUNT(*) AS c
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
      AND is_deleted = 0
    GROUP BY scope
");
$scope = ['private' => 0, 'public' => 0, 'both' => 0];
foreach ($scope_rows as $r) {
    $k = $r['scope'];
    if (isset($scope[$k])) $scope[$k] = (int)$r['c'];
}

// ────────────────────────────────────────────────────────────
// 4) Privacy 분포 (1~5)
// ────────────────────────────────────────────────────────────
$priv_rows = mes_qall($db, "
    SELECT privacy_level, COUNT(*) AS c
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
      AND is_deleted = 0
    GROUP BY privacy_level
");
$privacy = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
foreach ($priv_rows as $r) {
    $lv = (string)(int)$r['privacy_level'];
    if (isset($privacy[$lv])) $privacy[$lv] = (int)$r['c'];
}

// ────────────────────────────────────────────────────────────
// 5) Category 분포 (13개 전체 + scope breakdown)
// ────────────────────────────────────────────────────────────
$CATS = ['basic','childhood','diary','file','voice','image',
         'fingerprint','palmistry','physiognomy','saju','astrology',
         'decision','etc'];

$cat_rows = mes_qall($db, "
    SELECT category, scope, COUNT(*) AS c
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
      AND is_deleted = 0
    GROUP BY category, scope
");
$cat_map = [];
foreach ($CATS as $k) {
    $cat_map[$k] = ['key' => $k, 'count' => 0, 'scope_private' => 0, 'scope_public' => 0, 'scope_both' => 0];
}
foreach ($cat_rows as $r) {
    $k = $r['category']; $s = $r['scope']; $c = (int)$r['c'];
    if (!isset($cat_map[$k])) continue;
    $cat_map[$k]['count'] += $c;
    if ($s === 'private')      $cat_map[$k]['scope_private'] = $c;
    else if ($s === 'public')  $cat_map[$k]['scope_public']  = $c;
    else if ($s === 'both')    $cat_map[$k]['scope_both']    = $c;
}
// count 내림차순 정렬 (위젯에서 상위 N + 기타 묶음에 활용)
$category = array_values($cat_map);
usort($category, function($a, $b) { return $b['count'] - $a['count']; });

// ────────────────────────────────────────────────────────────
// 6) 30일 타임라인 (오늘 포함, 누락된 날짜는 0으로 채움)
// ────────────────────────────────────────────────────────────
$tl_rows = mes_qall($db, "
    SELECT DATE(created_at) AS d, COUNT(*) AS c
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
      AND is_deleted = 0
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
");
$tl_map = [];
foreach ($tl_rows as $r) { $tl_map[$r['d']] = (int)$r['c']; }

$timeline = [];
$today = new DateTimeImmutable('today');
for ($i = 29; $i >= 0; $i--) {
    $d  = $today->modify("-{$i} day")->format('Y-m-d');
    $timeline[] = ['date' => $d, 'count' => $tl_map[$d] ?? 0];
}

// ────────────────────────────────────────────────────────────
// 7) 최근 5건 (panic_lock 시 privacy_level >= 4 자동 제외)
// ────────────────────────────────────────────────────────────
$recent_where_lock = $is_locked ? "AND privacy_level < 4" : "";
$recent = mes_qall($db, "
    SELECT idx, category, scope, title, privacy_level,
           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
    FROM Gn_onechat_me_data_pool
    WHERE mem_id COLLATE utf8mb4_0900_ai_ci = '{$le}'
      AND is_deleted = 0
      {$recent_where_lock}
    ORDER BY idx DESC
    LIMIT 5
");
// 정수형 변환
foreach ($recent as &$rr) {
    $rr['idx'] = (int)$rr['idx'];
    $rr['privacy_level'] = (int)$rr['privacy_level'];
}
unset($rr);

// ────────────────────────────────────────────────────────────
// 8) Health Score (0~100)
//    - volume   (0~30): 활성 데이터 개수 (≥20 만점)
//    - balance  (0~25): private/public 양쪽 존재 + 균형도
//    - privacy  (0~25): 1~5 단계 분포가 한쪽으로 쏠리지 않을수록 가산
//    - recency  (0~20): 최근 7일 이내 추가 활동
// ────────────────────────────────────────────────────────────
$active_cnt = $total['active'];

// volume
$volume = (int) round(min($active_cnt, 20) / 20 * 30);

// balance: private/public(또는 both) 모두 ≥1이면 기본 점, 비율 균형도로 추가
$has_priv = ($scope['private'] + $scope['both']) > 0;
$has_pub  = ($scope['public']  + $scope['both']) > 0;
$balance  = 0;
if ($active_cnt > 0) {
    if ($has_priv && $has_pub) {
        $p_eff = $scope['private'] + $scope['both'];
        $b_eff = $scope['public']  + $scope['both'];
        $minv  = min($p_eff, $b_eff);
        $maxv  = max($p_eff, $b_eff);
        $ratio = $maxv > 0 ? ($minv / $maxv) : 0; // 0~1
        $balance = 10 + (int) round($ratio * 15); // 10~25
    } else if ($has_priv || $has_pub) {
        $balance = 6;
    }
}

// privacy: 사용된 단계 수(distinct) × 5점 (최대 25)
$priv_used = 0;
foreach ($privacy as $v) { if ($v > 0) $priv_used++; }
$privacy_sc = min($priv_used * 5, 25);

// recency: 최근 7일 내 추가 합
$recent_sum = 0;
for ($i = count($timeline) - 7; $i < count($timeline); $i++) {
    if ($i < 0) continue;
    $recent_sum += $timeline[$i]['count'];
}
$recency = (int) round(min($recent_sum, 7) / 7 * 20);

$health_score = $volume + $balance + $privacy_sc + $recency;
if ($active_cnt === 0) $health_score = 0;

// 메시지
if ($active_cnt === 0) {
    $hmsg = '아직 데이터가 없습니다. 첫 한 줄을 기록해보세요.';
} else if ($health_score >= 80) {
    $hmsg = '균형 잡힌 아바타로 성장하고 있습니다.';
} else if ($health_score >= 50) {
    $hmsg = '꾸준히 자라고 있어요. 다양한 영역을 채워보세요.';
} else if ($health_score >= 25) {
    $hmsg = '시작 단계입니다. Scope와 Privacy를 다양하게 시도해보세요.';
} else {
    $hmsg = '데이터를 조금만 더 추가하면 활용도가 크게 올라갑니다.';
}

$health = [
    'score'     => $health_score,
    'breakdown' => [
        'volume'  => $volume,    // /30
        'balance' => $balance,   // /25
        'privacy' => $privacy_sc,// /25
        'recency' => $recency,   // /20
    ],
    'message'   => $hmsg,
];

// ────────────────────────────────────────────────────────────
// 9) 응답 정리
// ────────────────────────────────────────────────────────────
$avatar_pub = [
    'status'          => $avatar['status'] ?? 'active',
    'panic_locked_at' => $avatar['panic_locked_at'] ?? null,
    'avatar_name'     => $avatar['avatar_name'] ?? null,
    'identity_text'   => $avatar['identity_text'] ?? null,
    'data_pool_count' => (int)($avatar['data_pool_count'] ?? 0),
    'decision_count'  => (int)($avatar['decision_count']  ?? 0),
    'match_rate'      => isset($avatar['match_rate']) ? (float)$avatar['match_rate'] : 0,
    'created_at'      => $avatar['created_at'] ?? null,
    'updated_at'      => $avatar['updated_at'] ?? null,
];

onechat_json([
    'ok'       => true,
    'locked'   => $is_locked,
    'avatar'   => $avatar_pub,
    'total'    => $total,
    'scope'    => $scope,
    'privacy'  => $privacy,
    'category' => $category,
    'timeline' => $timeline,
    'recent'   => $recent,
    'health'   => $health,
    'meta'     => [
        'generated_at' => date('Y-m-d H:i:s'),
        'version'      => 'C-3.1',
    ],
], 200);
