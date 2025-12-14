<?php
// 세션 시작 (이미 시작되지 않았다면)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 상태 확인
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? '';
$user_member_id = $_SESSION['user_id'] ?? 0;
$is_admin = ($user_member_id == 1); // member_id가 1인 경우 관리자
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? '한국AI코딩허브협회'; ?> - AI코딩으로 일하고, 연결되고, 수익을 만드는</title>
    <meta name="description" content="한국AI코딩허브협회 - AI코딩 생태계의 중심, 교육-실전-수익을 하나로 연결합니다">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Dark Theme CSS -->
    <link rel="stylesheet" href="/css/dark-theme.css">
    
    <style>
        /* Additional Custom Styles */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Mobile Menu Toggle */
        #mobile-menu {
            display: none;
        }
        
        #mobile-menu.show {
            display: block;
        }
        
        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* User Menu Dropdown */
        .user-menu-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            min-width: 200px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            z-index: 100;
        }
        
        .user-menu-dropdown.show {
            display: block;
        }
        
        .user-menu-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #334155;
            transition: background-color 0.2s;
        }
        
        .user-menu-item:hover {
            background-color: #334155;
        }
        
        .user-menu-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Navigation -->
    <nav class="fixed w-full bg-black/90 backdrop-blur-lg z-50 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/?page=home" class="flex items-center">
                        <i class="fas fa-rocket text-3xl text-purple-400 mr-3 cyber-glow"></i>
                        <span class="text-xl font-bold text-white hidden sm:inline">한국AI코딩허브협회</span>
                        <span class="text-lg font-bold text-white sm:hidden">AI코딩허브</span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/?page=home" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        홈
                    </a>
                    <a href="/?page=about" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        협회소개
                    </a>
                    <a href="/?page=business" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        사업안내
                    </a>
                    <a href="/?page=platform" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        허브플랫폼
                    </a>
                    <a href="/festival/" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        페스티벌
                    </a>
                    <a href="/?page=board" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        게시판
                    </a>
                    <a href="/?page=contact" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        문의
                    </a>
                    
                    <?php if ($is_logged_in): ?>
                        <!-- 관리자 메뉴 (관리자만 표시) -->
                        <?php if ($is_admin): ?>
                            <a href="/?page=admin" class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all transform hover:scale-105 shadow-lg">
                                <i class="fas fa-user-shield mr-2"></i>관리자
                            </a>
                        <?php endif; ?>
                        
                        <!-- 사용자 메뉴 -->
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center space-x-2 text-gray-300 hover:text-white px-3 py-2 rounded-md transition-all hover:bg-gray-800">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($user_name); ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <!-- 드롭다운 메뉴 -->
                            <div id="user-menu-dropdown" class="user-menu-dropdown">
                                <a href="/?page=mypage" class="user-menu-item block text-gray-300 hover:text-white">
                                    <i class="fas fa-user mr-2"></i>마이페이지
                                </a>
                                <a href="/?page=profile" class="user-menu-item block text-gray-300 hover:text-white">
                                    <i class="fas fa-id-card mr-2"></i>프로필 설정
                                </a>
                                <a href="/api/auth/logout.php" class="user-menu-item block text-red-400 hover:text-red-300">
                                    <i class="fas fa-sign-out-alt mr-2"></i>로그아웃
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- 로그인 버튼 -->
                        <a href="/?page=login" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>로그인
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-300 hover:text-white">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden bg-gray-900 border-t border-gray-800">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/?page=home" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    홈
                </a>
                <a href="/?page=about" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    협회소개
                </a>
                <a href="/?page=business" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    사업안내
                </a>
                <a href="/?page=platform" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    허브플랫폼
                </a>
                <a href="/festival/" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    페스티벌
                </a>
                <a href="/?page=board" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    게시판
                </a>
                <a href="/?page=contact" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    문의
                </a>
                
                <?php if ($is_logged_in): ?>
                    <!-- 관리자 메뉴 (모바일) -->
                    <?php if ($is_admin): ?>
                        <a href="/?page=admin" class="bg-gradient-to-r from-red-600 to-pink-600 text-white block px-3 py-2 rounded-md text-base font-medium text-center">
                            <i class="fas fa-user-shield mr-2"></i>관리자 페이지
                        </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-700 my-2"></div>
                    
                    <div class="text-gray-400 px-3 py-2 text-sm">
                        <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($user_name); ?>님
                    </div>
                    <a href="/?page=mypage" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-user mr-2"></i>마이페이지
                    </a>
                    <a href="/?page=profile" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-id-card mr-2"></i>프로필 설정
                    </a>
                    <a href="/api/auth/logout.php" class="text-red-400 hover:text-red-300 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                        <i class="fas fa-sign-out-alt mr-2"></i>로그아웃
                    </a>
                <?php else: ?>
                    <a href="/?page=login" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white block px-3 py-2 rounded-md text-base font-medium text-center">
                        로그인
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('show');
        });
        
        // User menu dropdown toggle
        document.getElementById('user-menu-btn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('user-menu-dropdown').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('user-menu-dropdown');
            const button = document.getElementById('user-menu-btn');
            if (dropdown && !dropdown.contains(e.target) && !button?.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
    
    <main class="pt-16">
