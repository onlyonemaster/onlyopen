<?php
/**
 * Admin Members Management Page
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
$memberType = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];

if ($memberType !== 'all') {
    $where[] = 'member_type = ?';
    $params[] = $memberType;
}

if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE {$whereClause}");
$countStmt->execute($params);
$totalMembers = $countStmt->fetchColumn();
$totalPages = ceil($totalMembers / $perPage);

// Get members
$stmt = $pdo->prepare("
    SELECT member_id, name, email, phone, member_type, status, created_at, updated_at
    FROM members
    WHERE {$whereClause}
    ORDER BY created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$members = $stmt->fetchAll();

$pageTitle = '회원 관리';
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
                        <a href="/?page=admin&section=members" class="border-cyan-500 text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-users mr-2"></i> 회원 관리
                        </a>
                        <a href="/?page=admin&section=boards" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-clipboard-list mr-2"></i> 게시판 관리
                        </a>
                        <a href="/?page=admin&section=festival" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
                <i class="fas fa-users text-cyan-400 mr-3"></i>회원 관리
            </h1>
            <p class="text-gray-400">전체 회원: <?php echo number_format($totalMembers); ?>명</p>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <form method="GET" action="/" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="section" value="members">
                
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-2">검색</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="이름, 이메일, 전화번호 검색..."
                            class="w-full px-4 py-2 pl-10 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Member Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">회원 유형</label>
                    <select name="type" class="w-full px-4 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                        <option value="all" <?php echo $memberType === 'all' ? 'selected' : ''; ?>>전체</option>
                        <option value="individual" <?php echo $memberType === 'individual' ? 'selected' : ''; ?>>개인 개발자</option>
                        <option value="company" <?php echo $memberType === 'company' ? 'selected' : ''; ?>>기업</option>
                        <option value="education" <?php echo $memberType === 'education' ? 'selected' : ''; ?>>교육기관</option>
                        <option value="team" <?php echo $memberType === 'team' ? 'selected' : ''; ?>>팀</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">상태</label>
                    <select name="status" class="w-full px-4 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-cyan-500">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>전체</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>활성</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-4 flex space-x-2">
                    <button type="submit" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>필터 적용
                    </button>
                    <a href="/?page=admin&section=members" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>초기화
                    </a>
                </div>
            </form>
        </div>

        <!-- Members Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">회원 정보</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">유형</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">연락처</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">상태</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">가입일</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($members as $member): ?>
                        <tr class="hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                #<?php echo $member['member_id']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($member['name']); ?></div>
                                        <div class="text-sm text-gray-400"><?php echo htmlspecialchars($member['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                        switch($member['member_type']) {
                                            case 'individual': echo 'bg-blue-600 text-blue-100'; break;
                                            case 'company': echo 'bg-purple-600 text-purple-100'; break;
                                            case 'education': echo 'bg-green-600 text-green-100'; break;
                                            case 'team': echo 'bg-orange-600 text-orange-100'; break;
                                        }
                                    ?>">
                                    <?php 
                                        $types = ['individual' => '개발자', 'company' => '기업', 'education' => '교육기관', 'team' => '팀'];
                                        echo $types[$member['member_type']] ?? $member['member_type'];
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <i class="fas fa-phone mr-2 text-gray-400"></i><?php echo htmlspecialchars($member['phone']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                        switch($member['status']) {
                                            case 'active': echo 'bg-green-600 text-green-100'; break;
                                            case 'pending': echo 'bg-yellow-600 text-yellow-100'; break;
                                            case 'inactive': echo 'bg-red-600 text-red-100'; break;
                                        }
                                    ?>">
                                    <?php 
                                        $statuses = ['active' => '활성', 'pending' => '대기', 'inactive' => '비활성'];
                                        echo $statuses[$member['status']] ?? $member['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo date('Y-m-d', strtotime($member['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="viewMember(<?php echo $member['member_id']; ?>)" 
                                    class="text-cyan-400 hover:text-cyan-300 mr-3" title="상세보기">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editMember(<?php echo $member['member_id']; ?>)" 
                                    class="text-yellow-400 hover:text-yellow-300 mr-3" title="수정">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteMember(<?php echo $member['member_id']; ?>)" 
                                    class="text-red-400 hover:text-red-300" title="삭제">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-600 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="?page=admin&section=members&type=<?php echo $memberType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&p=<?php echo $page - 1; ?>" 
                        class="relative inline-flex items-center px-4 py-2 border border-gray-600 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700">
                        이전
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=admin&section=members&type=<?php echo $memberType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&p=<?php echo $page + 1; ?>" 
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-600 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700">
                        다음
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-300">
                            <span class="font-medium"><?php echo ($offset + 1); ?></span>
                            -
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalMembers); ?></span>
                            / 전체
                            <span class="font-medium"><?php echo number_format($totalMembers); ?></span>
                            명
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=admin&section=members&type=<?php echo $memberType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&p=<?php echo $i; ?>" 
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
        function viewMember(id) {
            alert('회원 상세보기 기능 (ID: ' + id + ')');
            // TODO: Implement member detail view
        }

        function editMember(id) {
            alert('회원 수정 기능 (ID: ' + id + ')');
            // TODO: Implement member edit
        }

        function deleteMember(id) {
            if (confirm('정말 이 회원을 삭제하시겠습니까?')) {
                alert('회원 삭제 기능 (ID: ' + id + ')');
                // TODO: Implement member delete
            }
        }
    </script>

    <script src="/js/admin.js"></script>
</body>
</html>
