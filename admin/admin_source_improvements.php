<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

// 관리자 권한 확인
if (empty($_SESSION['one_member_admin_id']) || $_SESSION['one_member_admin_id'] == 'onlyonemaket') {
    echo '<script>alert("관리자 권한이 필요합니다.");location.href="/";</script>';
    exit;
}

// 필터 값
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$filter_project = isset($_GET['project']) ? $_GET['project'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// 페이지네이션
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 프로젝트 목록
$projects = array('아이엠플랫폼', 'SMS', 'ainote', 'nalara', '기타');
$statuses = array('개발중', '완료', '테스트중', '배포됨', '롤백');
$categories = array('버그 수정', '기능 추가', '성능 개선', '보안', '리팩토링', '문서화');

// 쿼리 조건 구성
$where = "WHERE 1=1";
if ($filter_date_from) $where .= " AND date >= '{$filter_date_from}'";
if ($filter_date_to) $where .= " AND date <= '{$filter_date_to}'";
if ($filter_project) $where .= " AND project = '{$filter_project}'";
if ($filter_status) $where .= " AND status = '{$filter_status}'";
if ($filter_search) $where .= " AND (title LIKE '%{$filter_search}%' OR description LIKE '%{$filter_search}%')";

// 전체 건수
$count_sql = "SELECT COUNT(*) as cnt FROM source_improvements {$where}";
$count_result = mysqli_query($self_con, $count_sql);
$count_row = mysqli_fetch_array($count_result);
$total = $count_row['cnt'];
$total_pages = ceil($total / $per_page);

// 대시보드 데이터
$today = date('Y-m-d');
$this_month_start = date('Y-m-01');

$dashboard_sql = "SELECT
    COUNT(*) as total,
    (SELECT COUNT(*) FROM source_improvements WHERE date >= '{$this_month_start}' AND date <= '{$today}') as this_month,
    (SELECT COUNT(*) FROM source_improvements WHERE date = '{$today}') as today,
    (SELECT COUNT(*) FROM source_improvements WHERE status = '개발중') as dev,
    (SELECT COUNT(*) FROM source_improvements WHERE status = '완료') as done,
    (SELECT COUNT(*) FROM source_improvements WHERE status = '테스트중') as testing,
    (SELECT COUNT(*) FROM source_improvements WHERE status = '배포됨') as deployed
FROM source_improvements";

$dashboard_result = mysqli_query($self_con, $dashboard_sql);
$dashboard = mysqli_fetch_array($dashboard_result);

// 목록 데이터
$list_sql = "SELECT SQL_CALC_FOUND_ROWS * FROM source_improvements {$where} ORDER BY date DESC, id DESC LIMIT {$offset}, {$per_page}";
$list_result = mysqli_query($self_con, $list_sql);

// 카테고리별 현황
$category_sql = "SELECT category, COUNT(*) as cnt FROM source_improvements WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY category";
$category_result = mysqli_query($self_con, $category_sql);
$categories_data = array();
while ($cat = mysqli_fetch_array($category_result)) {
    $categories_data[$cat['category']] = $cat['cnt'];
}

// 프로젝트별 현황
$project_sql = "SELECT project, COUNT(*) as cnt FROM source_improvements WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY project";
$project_result = mysqli_query($self_con, $project_sql);
$projects_data = array();
while ($proj = mysqli_fetch_array($project_result)) {
    $projects_data[$proj['project']] = $proj['cnt'];
}
?>
<style>
        .dashboard-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .dashboard-box h3 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .dashboard-box p {
            margin: 5px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .dashboard-box.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .dashboard-box.success { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
        .dashboard-box.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .dashboard-box.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .dashboard-box.danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

        .filter-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }
        .filter-row input,
        .filter-row select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        .btn-filter {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-filter:hover {
            background: #0056b3;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table thead {
            background: #e9ecef;
            position: sticky;
            top: 0;
        }
        table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        table tbody tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-dev { background: #cfe8fc; color: #004085; }
        .status-done { background: #c3e6cb; color: #155724; }
        .status-testing { background: #fff3cd; color: #856404; }
        .status-deployed { background: #d4edda; color: #155724; }
        .status-rollback { background: #f8d7da; color: #721c24; }

        .impact-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        .impact-low { background: #d4edda; color: #155724; }
        .impact-medium { background: #fff3cd; color: #856404; }
        .impact-high { background: #f8d7da; color: #721c24; }
        .impact-critical { background: #721c24; color: white; }

        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view { background: #17a2b8; color: white; }
        .btn-view:hover { background: #138496; }
        .btn-edit { background: #ffc107; color: black; }
        .btn-edit:hover { background: #e0a800; }
        .btn-notice { background: #28a745; color: white; }
        .btn-notice:hover { background: #218838; }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            display: inline-block;
        }
        .pagination a:hover {
            background: #ddd;
        }
        .pagination .active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .chart-box {
            background: white;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .chart-box h4 {
            margin-top: 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .chart-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .chart-item:last-child {
            border-bottom: none;
        }
        .chart-bar {
            height: 20px;
            background: #007bff;
            border-radius: 3px;
            display: inline-block;
            min-width: 1%;
        }
</style>
<div class="wrapper">
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header_menu.inc.php"; ?>
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_left_menu.inc.php"; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>소스 개선 관리<small>코드 개선 및 개발 이력 추적</small></h1>
                <ol class="breadcrumb">
                    <li><a href="three_config.php"><i class="fa fa-server"></i> 3종환경비교</a></li>
                    <li><a href="cron_config.php"><i class="fa fa-clock"></i> 크론잡관리</a></li>
                    <li><a href="admin_issue_dashboard.php"><i class="fa fa-exclamation-circle"></i> 이슈대시보드</a></li>
                    <li class="active">소스개발관리</li>
                </ol>
            </section>

            <section class="content">
                <!-- 새로 등록 버튼 -->
                <div style="margin-bottom: 20px;">
                    <a href="admin_source_improvements_form.php" class="btn btn-primary" style="padding: 10px 20px; font-size: 14px;">
                        <i class="fa fa-plus"></i> 새로 등록
                    </a>
                </div>

                <!-- 대시보드 -->
                <div class="row" style="margin-bottom: 30px;">
                    <div class="col-xs-12">
                        <h3 style="margin-top: 0; color: #333;">📊 개선 현황</h3>
                    </div>
                    <div class="col-xs-3">
                        <div class="dashboard-box primary">
                            <h3><?php echo $dashboard['total']; ?></h3>
                            <p>누적 개선 건수</p>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="dashboard-box success">
                            <h3><?php echo $dashboard['this_month']; ?></h3>
                            <p>이번 달 개선</p>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="dashboard-box info">
                            <h3><?php echo $dashboard['today']; ?></h3>
                            <p>오늘 개선</p>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="dashboard-box warning">
                            <h3><?php echo $dashboard['deployed']; ?></h3>
                            <p>배포됨</p>
                        </div>
                    </div>
                </div>

                <!-- 상태별 현황 -->
                <div class="row">
                    <div class="col-xs-6">
                        <div class="chart-box">
                            <h4>🔴 상태별 현황</h4>
                            <div class="chart-item">
                                <span>개발중</span>
                                <strong><?php echo $dashboard['dev']; ?>건</strong>
                            </div>
                            <div class="chart-item">
                                <span>완료</span>
                                <strong><?php echo $dashboard['done']; ?>건</strong>
                            </div>
                            <div class="chart-item">
                                <span>테스트중</span>
                                <strong><?php echo $dashboard['testing']; ?>건</strong>
                            </div>
                            <div class="chart-item">
                                <span>배포됨</span>
                                <strong><?php echo $dashboard['deployed']; ?>건</strong>
                            </div>
                        </div>
                    </div>

                    <!-- 프로젝트별 현황 -->
                    <div class="col-xs-6">
                        <div class="chart-box">
                            <h4>📁 프로젝트별 현황 (최근 30일)</h4>
                            <?php foreach ($projects as $p): ?>
                                <?php $cnt = isset($projects_data[$p]) ? $projects_data[$p] : 0; ?>
                                <div class="chart-item">
                                    <span><?php echo $p; ?></span>
                                    <strong><?php echo $cnt; ?>건</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 필터 -->
                <div class="filter-box" style="margin-top: 30px;">
                    <form method="get" id="filterForm">
                        <div class="filter-row">
                            <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>" placeholder="시작 날짜">
                            <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>" placeholder="종료 날짜">
                            <select name="project">
                                <option value="">프로젝트 (전체)</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo $filter_project == $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status">
                                <option value="">상태 (전체)</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $filter_status == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-filter">검색</button>
                        </div>
                        <div style="margin-top: 10px;">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="제목 또는 설명 검색" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </form>
                </div>

                <!-- 목록 -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>날짜</th>
                                <th>제목</th>
                                <th>프로젝트</th>
                                <th>카테고리</th>
                                <th>상태</th>
                                <th>영향도</th>
                                <th>파일</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_array($list_result)): ?>
                                <tr>
                                    <td><?php echo substr($row['date'], 5); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['title'], 0, 30)); ?></td>
                                    <td><?php echo $row['project']; ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace(array('개발중', '완료', '테스트중', '배포됨', '롤백'), array('dev', 'done', 'testing', 'deployed', 'rollback'), $row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="impact-badge impact-<?php echo str_replace(array('낮음', '중간', '높음', '긴급'), array('low', 'medium', 'high', 'critical'), $row['impact_level']); ?>">
                                            <?php echo $row['impact_level']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['files_changed']; ?>개</td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin_source_improvements_form.php?id=<?php echo $row['id']; ?>&action=view" class="btn-sm btn-view">보기</a>
                                            <a href="admin_source_improvements_form.php?id=<?php echo $row['id']; ?>&action=edit" class="btn-sm btn-edit">수정</a>
                                            <?php if (!$row['notice_id']): ?>
                                                <a href="notice_ai-write.php?source_id=<?php echo $row['id']; ?>" class="btn-sm btn-notice">공지</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&project=<?php echo $filter_project; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($filter_search); ?>">처음</a>
                        <a href="?page=<?php echo $page-1; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&project=<?php echo $filter_project; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($filter_search); ?>">이전</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&project=<?php echo $filter_project; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($filter_search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&project=<?php echo $filter_project; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($filter_search); ?>">다음</a>
                        <a href="?page=<?php echo $total_pages; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>&project=<?php echo $filter_project; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($filter_search); ?>">마지막</a>
                    <?php endif; ?>
                </div>
                <p style="text-align: center; color: #666; margin-top: 20px;">총 <?php echo $total; ?>건 (<?php echo $page; ?>/<?php echo $total_pages; ?>)</p>
            </section>
        </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php"; ?>
