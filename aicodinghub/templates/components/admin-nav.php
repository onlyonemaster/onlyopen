<?php
// Admin Navigation Component
// Usage: include with $currentSection variable set
$currentSection = $currentSection ?? 'dashboard';
?>
<!-- Admin Navigation -->
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Logo and Actions -->
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <i class="fas fa-shield-alt text-cyan-400 text-2xl mr-2 sm:mr-3"></i>
                <span class="text-lg sm:text-xl font-bold text-white">관리자</span>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="/" class="text-gray-300 hover:text-white p-2 rounded-md text-sm">
                    <i class="fas fa-external-link-alt"></i>
                    <span class="hidden sm:inline ml-2">사이트</span>
                </a>
                <a href="/?page=logout" class="text-gray-300 hover:text-white p-2 rounded-md text-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline ml-2">로그아웃</span>
                </a>
            </div>
        </div>
        
        <!-- Mobile Menu - Grid Layout (2x2) -->
        <div class="sm:hidden pb-4">
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
        <div class="hidden sm:flex sm:space-x-8 pb-4">
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
</nav>
