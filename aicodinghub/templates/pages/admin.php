<?php
/**
 * Admin Dashboard Page
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

// Get statistics
$pdo = getDBConnection();

// Member statistics
$memberStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN member_type = 'individual' THEN 1 ELSE 0 END) as individual,
        SUM(CASE WHEN member_type = 'company' THEN 1 ELSE 0 END) as company,
        SUM(CASE WHEN member_type = 'education' THEN 1 ELSE 0 END) as education,
        SUM(CASE WHEN member_type = 'team' THEN 1 ELSE 0 END) as team,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
    FROM members
")->fetch();

// Board statistics
$boardStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN board_type = 'notice' THEN 1 ELSE 0 END) as notice,
        SUM(CASE WHEN board_type = 'news' THEN 1 ELSE 0 END) as news,
        SUM(CASE WHEN board_type = 'qna' THEN 1 ELSE 0 END) as qna,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM boards
")->fetch();

// Festival statistics
$festivalStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN DATE(registered_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM festival_registrations
")->fetch();

// Recent members (last 5)
$recentMembers = $pdo->query("
    SELECT member_id, name, email, member_type, status, created_at
    FROM members
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// Recent boards (last 5)
$recentBoards = $pdo->query("
    SELECT board_id, title, board_type, views, created_at
    FROM boards
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = '관리자 대시보드';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 한국AI코딩허브협회</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a href="/?page=admin" class="border-cyan-500 text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-home mr-2"></i> 대시보드
                        </a>
                        <a href="/?page=admin&section=members" class="border-transparent text-gray-300 hover:border-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
        
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">
                <i class="fas fa-chart-line text-cyan-400 mr-3"></i>대시보드
            </h1>
            <p class="text-gray-400">한국AI코딩허브협회 관리자 대시보드에 오신 것을 환영합니다.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Total Members Card -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-200 text-sm font-medium">전체 회원</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($memberStats['total']); ?></p>
                        <p class="text-blue-200 text-xs mt-2">
                            <i class="fas fa-arrow-up mr-1"></i>오늘 +<?php echo $memberStats['today']; ?>명
                        </p>
                    </div>
                    <div class="bg-blue-500 bg-opacity-50 rounded-full p-4">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Active Members Card -->
            <div class="bg-gradient-to-br from-green-600 to-green-800 rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-200 text-sm font-medium">활성 회원</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($memberStats['active']); ?></p>
                        <p class="text-green-200 text-xs mt-2">
                            <i class="fas fa-check-circle mr-1"></i>활동 중
                        </p>
                    </div>
                    <div class="bg-green-500 bg-opacity-50 rounded-full p-4">
                        <i class="fas fa-user-check text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Boards Card -->
            <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-200 text-sm font-medium">전체 게시글</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($boardStats['total']); ?></p>
                        <p class="text-purple-200 text-xs mt-2">
                            <i class="fas fa-arrow-up mr-1"></i>오늘 +<?php echo $boardStats['today']; ?>개
                        </p>
                    </div>
                    <div class="bg-purple-500 bg-opacity-50 rounded-full p-4">
                        <i class="fas fa-clipboard-list text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Festival Registrations Card -->
            <div class="bg-gradient-to-br from-orange-600 to-orange-800 rounded-lg shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-200 text-sm font-medium">페스티벌 신청</p>
                        <p class="text-white text-3xl font-bold mt-2"><?php echo number_format($festivalStats['total']); ?></p>
                        <p class="text-orange-200 text-xs mt-2">
                            <i class="fas fa-clock mr-1"></i>대기 <?php echo $festivalStats['pending']; ?>건
                        </p>
                    </div>
                    <div class="bg-orange-500 bg-opacity-50 rounded-full p-4">
                        <i class="fas fa-trophy text-white text-2xl"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <!-- Member Type Chart -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-chart-pie text-cyan-400 mr-2"></i>회원 유형 분포
                </h3>
                <canvas id="memberTypeChart" height="200"></canvas>
            </div>

            <!-- Board Type Chart -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-chart-bar text-cyan-400 mr-2"></i>게시글 유형 분포
                </h3>
                <canvas id="boardTypeChart" height="200"></canvas>
            </div>

        </div>

        <!-- Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Recent Members -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-user-plus text-cyan-400 mr-2"></i>최근 가입 회원
                    </h3>
                    <a href="/?page=admin&section=members" class="text-cyan-400 hover:text-cyan-300 text-sm">
                        전체보기 <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recentMembers as $member): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            </div>
                            <div>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($member['name']); ?></p>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($member['email']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
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
                            <p class="text-gray-400 text-xs mt-1"><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Boards -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-newspaper text-cyan-400 mr-2"></i>최근 게시글
                    </h3>
                    <a href="/?page=admin&section=boards" class="text-cyan-400 hover:text-cyan-300 text-sm">
                        전체보기 <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recentBoards as $board): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
                        <div class="flex-1">
                            <p class="text-white font-medium truncate"><?php echo htmlspecialchars($board['title']); ?></p>
                            <div class="flex items-center space-x-3 mt-1">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                        switch($board['board_type']) {
                                            case 'notice': echo 'bg-red-600 text-red-100'; break;
                                            case 'news': echo 'bg-blue-600 text-blue-100'; break;
                                            case 'qna': echo 'bg-green-600 text-green-100'; break;
                                        }
                                    ?>">
                                    <?php 
                                        $boardTypes = ['notice' => '공지', 'news' => '소식', 'qna' => '질문'];
                                        echo $boardTypes[$board['board_type']] ?? $board['board_type'];
                                    ?>
                                </span>
                                <span class="text-gray-400 text-xs">
                                    <i class="fas fa-eye mr-1"></i><?php echo number_format($board['views']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-gray-400 text-xs"><?php echo date('Y-m-d', strtotime($board['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- Charts JavaScript -->
    <script>
        // Member Type Chart
        const memberCtx = document.getElementById('memberTypeChart').getContext('2d');
        new Chart(memberCtx, {
            type: 'doughnut',
            data: {
                labels: ['개인 개발자', '기업', '교육기관', '팀'],
                datasets: [{
                    data: [
                        <?php echo $memberStats['individual']; ?>,
                        <?php echo $memberStats['company']; ?>,
                        <?php echo $memberStats['education']; ?>,
                        <?php echo $memberStats['team']; ?>
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(249, 115, 22, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(168, 85, 247, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(249, 115, 22, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Board Type Chart
        const boardCtx = document.getElementById('boardTypeChart').getContext('2d');
        new Chart(boardCtx, {
            type: 'bar',
            data: {
                labels: ['공지사항', '소식', '질문'],
                datasets: [{
                    label: '게시글 수',
                    data: [
                        <?php echo $boardStats['notice']; ?>,
                        <?php echo $boardStats['news']; ?>,
                        <?php echo $boardStats['qna']; ?>
                    ],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(34, 197, 94, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(75, 85, 99, 0.3)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(75, 85, 99, 0.3)'
                        }
                    }
                }
            }
        });
    </script>

    <script src="/js/admin.js"></script>
</body>
</html>
