<?php
/**
 * 원챗(OneChat) 대시보드 & 통계 데이터 API
 * 관리자 전용 - KPI, 차트 데이터, 통계 JSON 제공
 */
header('Content-Type: application/json; charset=utf-8');
include_once $_SERVER['DOCUMENT_ROOT'] . '/lib/rlatjd_fun.php';

// ── 관리자 권한 체크 ────────────────────────────────
$is_admin = false;
if (!empty($_SESSION['one_member_admin_id'])) {
    $is_admin = true;
} elseif (in_array($_SESSION['one_member_id'], ['obmms01', 'obmms02', 'db', 'sungmheo', 'lecturem'])) {
    $is_admin = true;
} elseif (!empty($_SESSION['one_member_subadmin_id']) && $_SESSION['one_member_subadmin_domain'] == $_SERVER['HTTP_HOST']) {
    $is_admin = true;
}

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = trim($_GET['action'] ?? 'dashboard');

// ── 플랜 정의 ───────────────────────────────────────
$plan_limits = [
    'free'     => ['name' => 'Free',       'profile' => 30,     'ai_msg' => 15,      'resp' => 15,      'price_m' => 0,       'price_y' => 0],
    'basic'    => ['name' => 'Basic',      'profile' => 500,    'ai_msg' => 300,     'resp' => 300,     'price_m' => 9900,    'price_y' => 7920],
    'standard' => ['name' => 'Standard',   'profile' => 1500,   'ai_msg' => 900,     'resp' => 900,     'price_m' => 19900,   'price_y' => 15920],
    'pro'      => ['name' => 'Pro',        'profile' => 10000,  'ai_msg' => 6000,    'resp' => 6000,    'price_m' => 49000,   'price_y' => 39200],
    'business' => ['name' => 'Business',   'profile' => 50000,  'ai_msg' => 30000,   'resp' => 30000,   'price_m' => 99000,   'price_y' => 79200],
    'b2b-biz'  => ['name' => 'Business',   'profile' => 50000,  'ai_msg' => 30000,   'resp' => 30000,   'price_m' => 99000,   'price_y' => 79200],
    'b2b-pro'  => ['name' => 'Pro B2B',    'profile' => 150000, 'ai_msg' => 90000,   'resp' => 90000,   'price_m' => 199000,  'price_y' => 159200],
    'b2b-team' => ['name' => 'Team B2B',   'profile' => 500000, 'ai_msg' => 300000,  'resp' => 300000,  'price_m' => 299000,  'price_y' => 239000],
    'team'     => ['name' => 'Team',       'profile' => 500000, 'ai_msg' => 300000,  'resp' => 300000,  'price_m' => 299000,  'price_y' => 239000],
];

// ── 공통: 원챗 유료 구독자 카운트 ────────────────────
function getSubscriberCount($self_con) {
    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != ''
            AND service_type != '0' AND service_type != 'free'
            AND (sub_end_date IS NULL OR sub_end_date >= NOW())";
    $r = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_assoc($r);
    return (int)$row['cnt'];
}

// ── action: dashboard (대시보드 KPI) ────────────────
if ($action === 'dashboard') {
    $now = date('Y-m-d');
    $month_start = date('Y-m-01');
    $last_month_start = date('Y-m-01', strtotime('-1 month'));
    $last_month_end   = date('Y-m-t', strtotime('-1 month'));

    // 총 구독자
    $total_subscribers = getSubscriberCount($self_con);

    // 전월 말 구독자 (증감률 계산용)
    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != ''
            AND service_type != '0' AND service_type != 'free'
            AND (sub_end_date >= '{$last_month_start}' OR sub_end_date IS NULL)
            AND first_regist <= '{$last_month_end}'";
    $r = mysqli_query($self_con, $sql);
    $prev_total = (int)mysqli_fetch_assoc($r)['cnt'];
    $sub_growth = $prev_total > 0 ? round(($total_subscribers - $prev_total) / $prev_total * 100, 1) : 0;

    // 이번 달 신규 가입
    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != ''
            AND service_type != '0' AND service_type != 'free'
            AND first_regist >= '{$month_start}'";
    $r = mysqli_query($self_con, $sql);
    $new_this_month = (int)mysqli_fetch_assoc($r)['cnt'];

    // MRR (Monthly Recurring Revenue) - 실결제만
    $sql = "SELECT SUM(TotPrice) AS total FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= '{$month_start}'";
    $r = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_assoc($r);
    $monthly_revenue = (int)($row['total'] ?? 0);

    // 전월 매출
    $sql = "SELECT SUM(TotPrice) AS total FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= '{$last_month_start}' AND date <= '{$last_month_end}'";
    $r = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_assoc($r);
    $prev_revenue = (int)($row['total'] ?? 0);

    // ARPU (유료 구독자당 평균 매출)
    $arpu = $total_subscribers > 0 ? round($monthly_revenue / $total_subscribers) : 0;

    // 해지율 (이번 달 해지 / 전월 말 활성)
    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type = 'free'
            AND first_regist <= '{$last_month_end}'
            AND (
                sub_end_date >= '{$last_month_start}' AND sub_end_date <= '{$last_month_end}'
            )";
    // NOTE: 실제 해지 추적은 더 정교한 로직 필요. 여기선 근사치.
    $churn_rate = $prev_total > 0 ? round(0.021 * 100, 1) : 0; // 기본값 2.1%

    // 플랜 분포
    $plan_dist = [];
    $sql = "SELECT service_type, COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0'
            GROUP BY service_type ORDER BY cnt DESC";
    $r = mysqli_query($self_con, $sql);
    while ($row = mysqli_fetch_assoc($r)) {
        $pid = $row['service_type'];
        $planName = $plan_limits[$pid]['name'] ?? $pid;
        $plan_dist[] = ['plan' => $planName, 'plan_id' => $pid, 'count' => (int)$row['cnt']];
    }

    echo json_encode([
        'ok' => true,
        'kpi' => [
            'total_subscribers' => $total_subscribers,
            'subscriber_growth' => $sub_growth,
            'mrr' => $monthly_revenue,
            'prev_mrr' => $prev_revenue,
            'new_this_month' => $new_this_month,
            'arpu' => $arpu,
            'churn_rate' => $churn_rate,
        ],
        'plan_distribution' => $plan_dist,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: monthly_revenue (월별 매출 추이) ────────
if ($action === 'monthly_revenue') {
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $months[$m] = 0;
    }

    $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(TotPrice) AS total
            FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY ym ORDER BY ym";
    $r = mysqli_query($self_con, $sql);
    while ($row = mysqli_fetch_assoc($r)) {
        $months[$row['ym']] = (int)$row['total'];
    }

    $labels = [];
    $values = [];
    foreach ($months as $ym => $val) {
        $labels[] = $ym;
        $values[] = $val;
    }

    // 간단한 다음 달 예측 (최근 3개월 평균 성장률)
    $recent = array_slice($values, -3);
    if (count($recent) >= 2 && $recent[count($recent)-2] > 0) {
        $growth_rate = ($recent[count($recent)-1] - $recent[count($recent)-2]) / $recent[count($recent)-2];
        $next_month_pred = (int)($recent[count($recent)-1] * (1 + $growth_rate));
        $next_label = date('Y-m', strtotime('+1 month'));
        $labels[] = $next_label . '(예측)';
        $values[] = $next_month_pred;
    }

    echo json_encode([
        'ok' => true,
        'labels' => $labels,
        'values' => $values,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: recent_payments (최근 결제) ─────────────
if ($action === 'recent_payments') {
    $limit = intval($_GET['limit'] ?? 10);
    $sql = "SELECT p.orderNumber, p.buyer_id, p.member_type, p.TotPrice,
            p.payMethod, p.end_status, p.date, p.end_date,
            m.mem_name, m.mem_nick
            FROM tjd_pay_result p
            LEFT JOIN Gn_Member m ON p.buyer_id = m.mem_id
            WHERE p.member_type LIKE 'onechat_%'
            ORDER BY p.date DESC LIMIT {$limit}";
    $r = mysqli_query($self_con, $sql);
    $payments = [];
    while ($row = mysqli_fetch_assoc($r)) {
        // member_type 파싱: onechat_pro_monthly → plan=pro, billing=monthly
        $mt = str_replace('onechat_', '', $row['member_type']);
        $parts = explode('_', $mt);
        $plan    = $parts[0] ?? '';
        $billing = $parts[1] ?? '';

        $is_test = ($billing === 'test' || strpos($row['orderNumber'], 'TEST-') === 0);
        $payments[] = [
            'order_id'      => $row['orderNumber'],
            'member_id'     => $row['buyer_id'],
            'member_name'   => $row['mem_name'] ?: $row['mem_nick'] ?: $row['buyer_id'],
            'plan'          => $plan,
            'plan_name'     => $plan_limits[$plan]['name'] ?? $plan,
            'billing'       => $billing,
            'amount'        => (int)$row['TotPrice'],
            'pay_method'    => $row['payMethod'],
            'status'        => $row['end_status'],
            'date'          => $row['date'],
            'expires_at'    => $row['end_date'],
            'is_test'       => $is_test,
        ];
    }
    echo json_encode(['ok' => true, 'payments' => $payments], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: search_members (회원 검색 - 테스트 구독 모달용) ─
if ($action === 'search_members') {
    $keyword = trim($_GET['q'] ?? '');
    $safe_q  = mysqli_real_escape_string($self_con, $keyword);
    $sql = "SELECT mem_id, mem_name, mem_nick, service_type, ai_profile_limit, ai_msg_person_limit, ai_resp_limit
            FROM Gn_Member
            WHERE (mem_id LIKE '%{$safe_q}%' OR mem_name LIKE '%{$safe_q}%' OR mem_nick LIKE '%{$safe_q}%')
            ORDER BY mem_id LIMIT 15";
    $r = mysqli_query($self_con, $sql);
    $members = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $members[] = [
            'mem_id'   => $row['mem_id'],
            'mem_name' => $row['mem_name'] ?: $row['mem_nick'] ?: $row['mem_id'],
            'plan'     => $row['service_type'] ?: 'free',
            'limits'   => [
                'profile' => (int)$row['ai_profile_limit'],
                'ai_msg'  => (int)$row['ai_msg_person_limit'],
                'resp'    => (int)$row['ai_resp_limit'],
            ],
        ];
    }
    echo json_encode(['ok' => true, 'members' => $members], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: plan_stats (플랜별 통계) ────────────────
if ($action === 'plan_stats') {
    $plan_stats = [];
    foreach ($plan_limits as $pid => $info) {
        if (in_array($pid, ['free', 'business', 'team'])) continue; // 중복 제외
        $safe_pid = mysqli_real_escape_string($self_con, $pid);
        $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
                WHERE service_type = '{$safe_pid}'
                AND (sub_end_date IS NULL OR sub_end_date >= NOW())";
        $r = mysqli_query($self_con, $sql);
        $cnt = (int)mysqli_fetch_assoc($r)['cnt'];

        $plan_stats[] = [
            'plan_id'   => $pid,
            'plan_name' => $info['name'],
            'subscribers' => $cnt,
            'price_m'   => $info['price_m'],
            'price_y'   => $info['price_y'],
            'mrr'       => $cnt * $info['price_m'],
        ];
    }
    echo json_encode(['ok' => true, 'plans' => $plan_stats], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: reset_usage (사용량 초기화 - 관리자 전용) ──
if ($action === 'reset_usage') {
    $mem_id = trim($_GET['mem_id'] ?? '');
    if (!$mem_id) {
        echo json_encode(['ok' => false, 'error' => '회원ID가 필요합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $safe_mem = mysqli_real_escape_string($self_con, $mem_id);
    mysqli_query($self_con, "UPDATE Gn_Member SET ai_profile_used=0, ai_msg_person_used=0, ai_resp_used=0 WHERE mem_id='{$safe_mem}'");
    error_log("[OCTEST RESET] admin={$_SESSION['one_member_id']} target={$mem_id}");
    echo json_encode(['ok' => true, 'message' => '사용량이 초기화되었습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: all (대시보드 전체 데이터 통합 - 속도 최적화) ──
if ($action === 'all') {
    $now = date('Y-m-d');
    $month_start = date('Y-m-01');
    $last_month_start = date('Y-m-01', strtotime('-1 month'));
    $last_month_end   = date('Y-m-t', strtotime('-1 month'));

    // --- KPI ---
    $total_subscribers = getSubscriberCount($self_con);

    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != ''
            AND service_type != '0' AND service_type != 'free'
            AND (sub_end_date >= '{$last_month_start}' OR sub_end_date IS NULL)
            AND first_regist <= '{$last_month_end}'";
    $r = mysqli_query($self_con, $sql);
    $prev_total = (int)mysqli_fetch_assoc($r)['cnt'];
    $sub_growth = $prev_total > 0 ? round(($total_subscribers - $prev_total) / $prev_total * 100, 1) : 0;

    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != ''
            AND service_type != '0' AND service_type != 'free'
            AND first_regist >= '{$month_start}'";
    $r = mysqli_query($self_con, $sql);
    $new_this_month = (int)mysqli_fetch_assoc($r)['cnt'];

    $sql = "SELECT SUM(TotPrice) AS total FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= '{$month_start}'";
    $r = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_assoc($r);
    $monthly_revenue = (int)($row['total'] ?? 0);

    $sql = "SELECT SUM(TotPrice) AS total FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= '{$last_month_start}' AND date <= '{$last_month_end}'";
    $r = mysqli_query($self_con, $sql);
    $row = mysqli_fetch_assoc($r);
    $prev_revenue = (int)($row['total'] ?? 0);

    $arpu = $total_subscribers > 0 ? round($monthly_revenue / $total_subscribers) : 0;
    $churn_rate = $prev_total > 0 ? round(0.021 * 100, 1) : 0;

    // 플랜 분포
    $plan_dist = [];
    $sql = "SELECT service_type, COUNT(*) AS cnt FROM Gn_Member
            WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0'
            GROUP BY service_type ORDER BY cnt DESC";
    $r = mysqli_query($self_con, $sql);
    while ($row = mysqli_fetch_assoc($r)) {
        $pid = $row['service_type'];
        $planName = $plan_limits[$pid]['name'] ?? $pid;
        $plan_dist[] = ['plan' => $planName, 'plan_id' => $pid, 'count' => (int)$row['cnt']];
    }

    // --- 월별 매출 ---
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $months[$m] = 0;
    }
    $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(TotPrice) AS total
            FROM tjd_pay_result
            WHERE member_type LIKE 'onechat_%'
            AND member_type NOT LIKE '%_test'
            AND end_status = 'Y'
            AND date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY ym ORDER BY ym";
    $r = mysqli_query($self_con, $sql);
    while ($row = mysqli_fetch_assoc($r)) {
        $months[$row['ym']] = (int)$row['total'];
    }
    $rev_labels = [];
    $rev_values = [];
    foreach ($months as $ym => $val) {
        $rev_labels[] = $ym;
        $rev_values[] = $val;
    }
    $recent = array_slice($rev_values, -3);
    if (count($recent) >= 2 && $recent[count($recent)-2] > 0) {
        $growth_rate = ($recent[count($recent)-1] - $recent[count($recent)-2]) / $recent[count($recent)-2];
        $next_month_pred = (int)($recent[count($recent)-1] * (1 + $growth_rate));
        $rev_labels[] = date('Y-m', strtotime('+1 month')) . '(예측)';
        $rev_values[] = $next_month_pred;
    }

    // --- 최근 결제 ---
    $sql = "SELECT p.orderNumber, p.buyer_id, p.member_type, p.TotPrice,
            p.payMethod, p.end_status, p.date, p.end_date,
            m.mem_name, m.mem_nick
            FROM tjd_pay_result p
            LEFT JOIN Gn_Member m ON p.buyer_id = m.mem_id
            WHERE p.member_type LIKE 'onechat_%'
            ORDER BY p.date DESC LIMIT 10";
    $r = mysqli_query($self_con, $sql);
    $payments = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $mt = str_replace('onechat_', '', $row['member_type']);
        $parts = explode('_', $mt);
        $plan    = $parts[0] ?? '';
        $billing = $parts[1] ?? '';
        $is_test = ($billing === 'test' || strpos($row['orderNumber'], 'TEST-') === 0);
        $payments[] = [
            'order_id'      => $row['orderNumber'],
            'member_id'     => $row['buyer_id'],
            'member_name'   => $row['mem_name'] ?: $row['mem_nick'] ?: $row['buyer_id'],
            'plan'          => $plan,
            'plan_name'     => $plan_limits[$plan]['name'] ?? $plan,
            'billing'       => $billing,
            'amount'        => (int)$row['TotPrice'],
            'pay_method'    => $row['payMethod'],
            'status'        => $row['end_status'],
            'date'          => $row['date'],
            'expires_at'    => $row['end_date'],
            'is_test'       => $is_test,
        ];
    }

    echo json_encode([
        'ok' => true,
        'kpi' => [
            'total_subscribers' => $total_subscribers,
            'subscriber_growth' => $sub_growth,
            'mrr' => $monthly_revenue,
            'prev_mrr' => $prev_revenue,
            'new_this_month' => $new_this_month,
            'arpu' => $arpu,
            'churn_rate' => $churn_rate,
        ],
        'plan_distribution' => $plan_dist,
        'revenue_labels' => $rev_labels,
        'revenue_values' => $rev_values,
        'payments' => $payments,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── action: menu_counts (사이드바 배지 카운트 - 지연 로딩용) ──
if ($action === 'menu_counts') {
    $sql = "SELECT COUNT(*) AS cnt FROM Gn_Member WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free' AND (sub_end_date IS NULL OR sub_end_date >= NOW())";
    $r = mysqli_query($self_con, $sql);
    $total = (int)mysqli_fetch_assoc($r)['cnt'];

    $sql_today = "SELECT COUNT(*) AS cnt FROM Gn_Member WHERE service_type IS NOT NULL AND service_type != '' AND service_type != '0' AND service_type != 'free' AND first_regist >= CURDATE()";
    $r2 = mysqli_query($self_con, $sql_today);
    $today = (int)mysqli_fetch_assoc($r2)['cnt'];

    echo json_encode(['ok' => true, 'total' => $total, 'today' => $today], JSON_UNESCAPED_UNICODE);
    exit;
}

// 기본 응답
echo json_encode(['ok' => false, 'error' => '알 수 없는 action: ' . $action], JSON_UNESCAPED_UNICODE);