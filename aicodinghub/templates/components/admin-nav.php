<?php
// Admin Navigation Component with Mobile Support
// Usage: include with $currentSection variable set
$currentSection = $currentSection ?? 'dashboard';

// User info
$user_name = $_SESSION['user_name'] ?? '관리자';
$is_logged_in = isset($_SESSION['user_id']);
?>

<style>
    /* Mobile Menu Toggle */
    #admin-mobile-menu {
        display: none;
    }
    
    #admin-mobile-menu.show {
        display: block !important;
    }
    
    /* Mobile User Menu */
    #admin-mobile-user-menu {
        display: none;
    }
    
    #admin-mobile-user-menu.show {
        display: block !important;
    }
    
    /* Smooth transitions */
    #admin-mobile-menu, #admin-mobile-user-menu {
        transition: all 0.3s ease-in-out;
    }
</style>

<!-- Admin Navigation -->
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Logo and Mobile Icons -->
        <div class="flex justify-between items-center h-16">
            <!-- Left: Logo -->
            <div class="flex items-center">
                <i class="fas fa-shield-alt text-cyan-400 text-2xl mr-2 sm:mr-3"></i>
                <span class="text-lg sm:text-xl font-bold text-white">관리자</span>
            </div>
            
            <!-- Right: Desktop Links (Hidden on Mobile) -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="/" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-external-link-alt mr-2"></i>사이트
                </a>
                <a href="/api/auth/logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i>로그아웃
                </a>
            </div>
            
            <!-- Right: Mobile Icons (Hidden on Desktop) -->
            <div class="flex md:hidden items-center space-x-3">
                <!-- Admin Icon (Always visible) -->
                <a href="/?page=admin" class="text-red-400 hover:text-red-300 p-2">
                    <i class="fas fa-shield-alt text-xl"></i>
                </a>
                
                <!-- User Account Icon -->
                <?php if ($is_logged_in): ?>
                <button id="admin-mobile-user-btn" type="button" class="text-gray-300 hover:text-white p-2 focus:outline-none">
                    <i class="fas fa-user-circle text-xl"></i>
                </button>
                <?php endif; ?>
                
                <!-- Hamburger Menu Button -->
                <button id="admin-mobile-menu-btn" type="button" class="text-gray-300 hover:text-white p-2 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Admin Menu Sections (2x2 Grid) -->
        <div class="md:hidden pb-4">
            <div class="grid grid-cols-2 gap-2">
                <a href="/?page=admin" 
                   class="<?php echo $currentSection === 'dashboard' ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium text-center flex items-center justify-center">
                    <i class="fas fa-home mr-2"></i>대시보드
                </a>
                <a href="/?page=admin&section=members" 
                   class="<?php echo $currentSection === 'members' ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium text-center flex items-center justify-center">
                    <i class="fas fa-users mr-2"></i>회원
                </a>
                <a href="/?page=admin&section=boards" 
                   class="<?php echo $currentSection === 'boards' ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium text-center flex items-center justify-center">
                    <i class="fas fa-clipboard-list mr-2"></i>게시판
                </a>
                <a href="/?page=admin&section=festival" 
                   class="<?php echo $currentSection === 'festival' ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium text-center flex items-center justify-center">
                    <i class="fas fa-trophy mr-2"></i>페스티벌
                </a>
            </div>
        </div>
        
        <!-- Desktop Menu -->
        <div class="hidden md:flex md:space-x-8 pb-4">
            <a href="/?page=admin" 
               class="<?php echo $currentSection === 'dashboard' ? 'border-cyan-500 text-white' : 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                <i class="fas fa-home mr-2"></i> 대시보드
            </a>
            <a href="/?page=admin&section=members" 
               class="<?php echo $currentSection === 'members' ? 'border-cyan-500 text-white' : 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                <i class="fas fa-users mr-2"></i> 회원 관리
            </a>
            <a href="/?page=admin&section=boards" 
               class="<?php echo $currentSection === 'boards' ? 'border-cyan-500 text-white' : 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                <i class="fas fa-clipboard-list mr-2"></i> 게시판 관리
            </a>
            <a href="/?page=admin&section=festival" 
               class="<?php echo $currentSection === 'festival' ? 'border-cyan-500 text-white' : 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                <i class="fas fa-trophy mr-2"></i> 페스티벌 관리
            </a>
        </div>
    </div>
    
    <!-- Mobile Full Menu Dropdown (Hamburger) -->
    <div id="admin-mobile-menu" class="md:hidden bg-gray-900 border-t border-gray-800">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-home mr-2"></i>홈
            </a>
            <a href="/?page=about" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-info-circle mr-2"></i>협회소개
            </a>
            <a href="/?page=business" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-briefcase mr-2"></i>사업안내
            </a>
            <a href="/?page=platform" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-cubes mr-2"></i>허브플랫폼
            </a>
            <a href="/?page=festival" target="_blank" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-trophy mr-2"></i>페스티벌
            </a>
            <a href="/?page=board" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-clipboard mr-2"></i>게시판
            </a>
            <a href="/?page=contact" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-envelope mr-2"></i>문의
            </a>
        </div>
    </div>
    
    <!-- Mobile User Menu Dropdown (Account Icon) - Right Aligned -->
    <?php if ($is_logged_in): ?>
    <div id="admin-mobile-user-menu" class="md:hidden absolute right-0 mt-1 w-56 bg-gray-900 border border-gray-700 rounded-lg shadow-xl z-50" style="top: 4rem;">
        <div class="py-2">
            <div class="text-gray-400 px-4 py-2 text-sm border-b border-gray-700">
                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($user_name); ?>님
            </div>
            <a href="/?page=mypage" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-4 py-2 text-sm">
                <i class="fas fa-user mr-2"></i>마이페이지
            </a>
            <a href="/?page=profile" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-4 py-2 text-sm">
                <i class="fas fa-id-card mr-2"></i>프로필 설정
            </a>
            <a href="/api/auth/logout.php" class="text-red-400 hover:text-red-300 hover:bg-gray-800 block px-4 py-2 text-sm rounded-b-lg">
                <i class="fas fa-sign-out-alt mr-2"></i>로그아웃
            </a>
        </div>
    </div>
    <?php endif; ?>
</nav>

<script>
// Admin Mobile Menu JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Admin nav JavaScript loaded');
    
    // Get elements
    const adminMobileMenuBtn = document.getElementById('admin-mobile-menu-btn');
    const adminMobileMenu = document.getElementById('admin-mobile-menu');
    const adminMobileUserBtn = document.getElementById('admin-mobile-user-btn');
    const adminMobileUserMenu = document.getElementById('admin-mobile-user-menu');
    
    console.log('📱 Admin Elements found:', {
        menuBtn: !!adminMobileMenuBtn,
        menu: !!adminMobileMenu,
        userBtn: !!adminMobileUserBtn,
        userMenu: !!adminMobileUserMenu
    });
    
    // Hamburger menu toggle
    if (adminMobileMenuBtn && adminMobileMenu) {
        adminMobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🍔 Hamburger clicked');
            
            adminMobileMenu.classList.toggle('show');
            
            // Close user menu
            if (adminMobileUserMenu) {
                adminMobileUserMenu.classList.remove('show');
            }
            
            console.log('Menu show class:', adminMobileMenu.classList.contains('show'));
        });
    }
    
    // User menu toggle
    if (adminMobileUserBtn && adminMobileUserMenu) {
        adminMobileUserBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('👤 User icon clicked');
            
            adminMobileUserMenu.classList.toggle('show');
            
            // Close hamburger menu
            if (adminMobileMenu) {
                adminMobileMenu.classList.remove('show');
            }
            
            console.log('User menu show class:', adminMobileUserMenu.classList.contains('show'));
        });
    }
    
    // Close menus when clicking outside
    document.addEventListener('click', function(e) {
        if (adminMobileMenu && adminMobileMenuBtn && 
            !adminMobileMenu.contains(e.target) && 
            !adminMobileMenuBtn.contains(e.target)) {
            adminMobileMenu.classList.remove('show');
        }
        
        if (adminMobileUserMenu && adminMobileUserBtn && 
            !adminMobileUserMenu.contains(e.target) && 
            !adminMobileUserBtn.contains(e.target)) {
            adminMobileUserMenu.classList.remove('show');
        }
    });
    
    console.log('✅ Admin nav event listeners attached');
});
</script>
