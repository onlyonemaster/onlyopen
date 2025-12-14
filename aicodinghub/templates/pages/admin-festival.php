<?php
/**
 * Admin Festival Management Page
 */

// Check if user is logged in and is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header('Location: /?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$pdo = getDBConnection();

// Handle filters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[] = 'fr.status = ?';
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = '(fr.name LIKE ? OR fr.email LIKE ? OR fr.organization LIKE ?)';
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM festival_registrations fr WHERE {$whereClause}");
$countStmt->execute($params);
$totalRegistrations = $countStmt->fetchColumn();
$totalPages = ceil($totalRegistrations / $perPage);

// Get registrations
$stmt = $pdo->prepare("
    SELECT fr.*, f.title as festival_title
    FROM festival_registrations fr
    LEFT JOIN festivals f ON fr.festival_id = f.festival_id
    WHERE {$whereClause}
    ORDER BY fr.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Get statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM festival_registrations
")->fetch();

$pageTitle = '페스티벌 관리';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 한국AI코딩허브협회</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="bg-gray-900 text-gray-100">
    
    <!-- Admin Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-shield-alt text-cyan-400 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-white">관리자 페이지</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/?page=admin" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-home mr-2"></i> 대시보드
                        </a>
                        <a href="/?page=admin&section=members" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-users mr-2"></i> 회원 관리
                        </a>
                        <a href="/?page=admin&section=boards" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-2"></i> 게시판 관리
                        </a>
                        <a href="/?page=admin&section=festival" class="border-cyan-500 text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-trophy mr-2"></i> 페스티벌 관리
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="/" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-external-link-alt mr-2"></i>사이트로 이동
                    </a>
                    <a href="/?page=logout" class="ml-4 text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>로그아웃
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">
                <i class="fas fa-trophy text-cyan-400 mr-3"></i>페스티벌 신청 관리
            </h1>
            <p class="text-gray-400">전체 신청: <?php echo number_format($totalRegistrations); ?>건</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm font-medium">전체 신청</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($stats['total']); ?></p>
                    </div>
                    <i class="fas fa-clipboard-check text-blue-300 text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-200 text-sm font-medium">대기 중</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($stats['pending']); ?></p>
                    </div>
                    <i class="fas fa-clock text-yellow-300 text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm font-medium">승인 완료</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($stats['approved']); ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-300 text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-200 text-sm font-medium">반려</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($stats['rejected']); ?></p>
                    </div>
                    <i class="fas fa-times-circle text-red-300 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" action="/" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="section" value="festival">
                
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">검색</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="이름, 이메일, 소속 검색..."
                            class="w-full px-4 py-2 pl-10 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">상태</label>
                    <select name="status" class="w-full px-4 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>전체</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기 중</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>승인 완료</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>반려</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-3 flex space-x-2">
                    <button type="submit" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>필터 적용
                    </button>
                    <a href="/?page=admin&section=festival" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>초기화
                    </a>
                </div>
            </form>
        </div>

        <!-- Registrations Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">신청자 정보</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">소속</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">연락처</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">상태</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">신청일</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                신청 내역이 없습니다.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registrations as $reg): ?>
                        <tr class="hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                #<?php echo $reg['registration_id']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($reg['name']); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo htmlspecialchars($reg['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo htmlspecialchars($reg['organization'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <i class="fas fa-phone mr-2 text-gray-400"></i><?php echo htmlspecialchars($reg['phone']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                        switch($reg['status']) {
                                            case 'pending': echo 'bg-yellow-600 text-yellow-100'; break;
                                            case 'approved': echo 'bg-green-600 text-green-100'; break;
                                            case 'rejected': echo 'bg-red-600 text-red-100'; break;
                                        }
                                    ?>">
                                    <?php 
                                        $statuses = ['pending' => '대기', 'approved' => '승인', 'rejected' => '반려'];
                                        echo $statuses[$reg['status']] ?? $reg['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo date('Y-m-d', strtotime($reg['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="viewRegistration(<?php echo $reg['registration_id']; ?>)" 
                                    class="text-cyan-400 hover:text-cyan-300 mr-3" title="상세보기">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($reg['status'] === 'pending'): ?>
                                <button onclick="approveRegistration(<?php echo $reg['registration_id']; ?>)" 
                                    class="text-green-400 hover:text-green-300 mr-3" title="승인">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="rejectRegistration(<?php echo $reg['registration_id']; ?>)" 
                                    class="text-red-400 hover:text-red-300" title="반려">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-600 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-300">
                            <span class="font-medium"><?php echo ($offset + 1); ?></span>
                            -
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalRegistrations); ?></span>
                            / 전체
                            <span class="font-medium"><?php echo number_format($totalRegistrations); ?></span>
                            건
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=admin&section=festival&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&p=<?php echo $i; ?>" 
                                class="relative inline-flex items-center px-4 py-2 border border-gray-600 text-sm font-medium 
                                    <?php echo $i === $page ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- JavaScript -->
    <script>
        function viewRegistration(id) {
            alert('신청서 상세보기 (ID: ' + id + ')');
            // TODO: Implement registration detail view
        }

        function approveRegistration(id) {
            if (confirm('이 신청을 승인하시겠습니까?')) {
                alert('승인 처리 (ID: ' + id + ')');
                // TODO: Implement approval
            }
        }

        function rejectRegistration(id) {
            if (confirm('이 신청을 반려하시겠습니까?')) {
                alert('반려 처리 (ID: ' + id + ')');
                // TODO: Implement rejection
            }
        }
    </script>

    <script src="/js/admin.js"></script>
</body>
</html>
