<?php
$page_title = "AI 코딩 페스티벌 - 한국AI코딩허브협회";

// 세션 확인 (header.php에서도 확인하지만 여기서도 확인)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 확인 (member_id == 1인 경우 관리자)
$is_admin_festival = isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1;

// Extract styles from the festival page
$festivalStyles = <<<'STYLES'
<style>
/* Enhanced Dark Mode System */
:root {
    --bg-primary: #0a0a0a;
    --bg-secondary: #1a1a1a;
    --bg-tertiary: #252525;
    --bg-glass: rgba(26, 26, 26, 0.7);
    --text-primary: #ffffff;
    --text-secondary: #a0a0a0;
    --text-accent: #00d4ff;
    --accent-purple: #8b5cf6;
    --accent-blue: #3b82f6;
    --accent-green: #10b981;
    --accent-orange: #f59e0b;
    --accent-yellow: #fbbf24;
    --border-color: #333333;
    --border-glow: rgba(139, 92, 246, 0.3);
}

.gradient-bg { 
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%); 
}

.card-hover { 
    transition: all 0.3s ease; 
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
}

.card-hover:hover { 
    transform: translateY(-10px); 
    box-shadow: 0 20px 40px var(--border-glow);
    border-color: var(--accent-purple);
}

.floating { 
    animation: floating 3s ease-in-out infinite; 
}

@keyframes floating { 
    0%, 100% { transform: translateY(0px); } 
    50% { transform: translateY(-10px); } 
}

.hero-overlay { 
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
}

.text-gradient {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.glass-effect {
    background: var(--bg-glass);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.dark-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.dark-card:hover {
    border-color: var(--accent-purple);
    box-shadow: 0 10px 30px var(--border-glow);
}

.cyber-glow {
    text-shadow: 0 0 20px var(--accent-purple), 0 0 40px var(--accent-blue);
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, transparent, rgba(139, 92, 246, 0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover::before {
    opacity: 1;
}

.timeline-item {
    position: relative;
    padding-left: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 2px;
    height: 100%;
    background: linear-gradient(to bottom, var(--accent-purple), var(--accent-blue));
}

.timeline-dot {
    position: absolute;
    left: -6px;
    top: 0.5rem;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--accent-purple);
    box-shadow: 0 0 10px var(--accent-purple);
}

/* Responsive Badge Layout - Auto wrap to 2-3 lines on mobile */
@media (max-width: 640px) {
    .flex-wrap > span {
        flex: 0 0 calc(50% - 0.5rem); /* 2 items per row on small mobile */
        min-width: 0;
    }
}

@media (min-width: 641px) and (max-width: 768px) {
    .flex-wrap > span {
        flex: 0 0 calc(50% - 0.5rem); /* 2 items per row on medium mobile */
    }
}

@media (min-width: 769px) {
    .flex-wrap > span {
        flex: 0 0 auto; /* Auto layout on desktop */
    }
}
</style>
STYLES;

// Include header
include dirname(__DIR__) . '/components/header.php';

// Add festival-specific styles
echo $festivalStyles;
?>
    <!-- Navigation -->
    <nav class="fixed w-full bg-black/90 backdrop-blur-lg z-50 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- 로고 -->
                <div class="flex items-center">
                    <i class="fas fa-rocket text-3xl text-purple-400 mr-3 cyber-glow"></i>
                    <span class="text-xl font-bold text-white hidden sm:inline">AI 코더와 수요자 매칭 페스티벌</span>
                    <span class="text-lg font-bold text-white sm:hidden">AI 코딩</span>
                </div>
                
                <!-- 데스크톱 메뉴 (hidden md:block) -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#home" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">홈</a>
                        <a href="/?page=about" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">협회</a>
                        <a href="#about" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">소개</a>
                        
                        <!-- 대상 드롭다운 메뉴 -->
                        <div class="relative group">
                            <button class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800 flex items-center">
                                대상
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="absolute left-0 mt-2 w-64 bg-gray-900 border border-gray-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                                <a href="coder.html" class="block px-4 py-3 text-gray-300 hover:bg-purple-900/50 hover:text-white transition-colors border-b border-gray-800">
                                    <i class="fas fa-code text-purple-400 mr-2"></i>AI로 코딩하는 개발자
                                </a>
                                <a href="enterprise.html" class="block px-4 py-3 text-gray-300 hover:bg-blue-900/50 hover:text-white transition-colors border-b border-gray-800">
                                    <i class="fas fa-building text-blue-400 mr-2"></i>AI코딩이 필요한 업체
                                </a>
                                <a href="learner.html" class="block px-4 py-3 text-gray-300 hover:bg-green-900/50 hover:text-white transition-colors border-b border-gray-800">
                                    <i class="fas fa-graduation-cap text-green-400 mr-2"></i>AI코딩을 배우려는 분
                                </a>
                                <a href="tech-trends.html" class="block px-4 py-3 text-gray-300 hover:bg-orange-900/50 hover:text-white transition-colors border-b border-gray-800">
                                    <i class="fas fa-chart-line text-orange-400 mr-2"></i>AI코딩을 알고 싶어요
                                </a>
                                <a href="regional-center.html" class="block px-4 py-3 text-gray-300 hover:bg-cyan-900/50 hover:text-white transition-colors rounded-b-lg">
                                    <i class="fas fa-map-marker-alt text-cyan-400 mr-2"></i>지역센터 운영할래요
                                </a>
                            </div>
                        </div>
                        
                        <a href="#schedule" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">일정</a>
                        <a href="#contact" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">문의</a>
                    </div>
                    
                    <?php if ($is_admin_festival): ?>
                    <!-- 페스티벌 관리자 버튼 (관리자만 표시) -->
                    <a href="/?page=admin&tab=festival" class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-user-shield mr-2"></i>페스티벌 관리
                    </a>
                    <?php endif; ?>
                    
                    <!-- 데스크톱 지역센터 개설 버튼 (강조) -->
                    <a href="regional-center.html" class="bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-map-marker-alt mr-2"></i>지역센터 개설
                    </a>
                </div>

                <!-- 모바일 메뉴 (md:hidden) - 조은님 요청: [문의] [대상▼] [지역센터개설] [☰] 순서 -->
                <div class="md:hidden flex items-center space-x-2">
                    <?php if ($is_admin_festival): ?>
                    <!-- 페스티벌 관리자 버튼 (모바일, 관리자만 표시) -->
                    <a href="/?page=admin&tab=festival" class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-2 py-2 rounded-lg text-xs font-bold transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-user-shield"></i>
                    </a>
                    <?php endif; ?>
                    
                    <!-- 1. 문의 버튼 -->
                    <a href="#contact" class="text-gray-300 hover:text-purple-400 px-2 py-2 rounded-md text-xs font-medium transition-all hover:bg-gray-800">
                        문의
                    </a>

                    <!-- 2. 대상 드롭다운 버튼 -->
                    <div class="relative">
                        <button id="targetBtn" class="text-gray-300 hover:text-purple-400 px-2 py-2 rounded-md text-xs font-medium transition-all hover:bg-gray-800 flex items-center">
                            대상
                            <i class="fas fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <!-- 대상 드롭다운 메뉴 (4개 항목만) -->
                        <div id="targetMenu" class="hidden absolute right-0 mt-2 w-64 bg-gray-900 border border-gray-700 rounded-lg shadow-xl z-50">
                            <a href="coder.html" class="block px-4 py-3 text-gray-300 hover:bg-purple-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-code text-purple-400 mr-2"></i>AI로 코딩하는 개발자
                            </a>
                            <a href="enterprise.html" class="block px-4 py-3 text-gray-300 hover:bg-blue-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-building text-blue-400 mr-2"></i>AI코딩이 필요한 업체
                            </a>
                            <a href="learner.html" class="block px-4 py-3 text-gray-300 hover:bg-green-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-graduation-cap text-green-400 mr-2"></i>AI코딩을 배우려는 분
                            </a>
                            <a href="tech-trends.html" class="block px-4 py-3 text-gray-300 hover:bg-orange-900/50 hover:text-white transition-colors rounded-b-lg">
                                <i class="fas fa-chart-line text-orange-400 mr-2"></i>AI코딩을 알고 싶어요
                            </a>
                        </div>
                    </div>

                    <!-- 3. 지역센터개설 버튼 (강조 스타일) -->
                    <a href="regional-center.html" class="bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white px-3 py-2 rounded-lg text-xs font-bold transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-map-marker-alt mr-1"></i>지역센터
                    </a>

                    <!-- 4. 햄버거 메뉴 버튼 -->
                    <div class="relative">
                        <button id="hamburgerBtn" class="text-gray-300 hover:text-purple-400 p-2 rounded-md transition-all hover:bg-gray-800">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <!-- 햄버거 드롭다운 (4개 항목) -->
                        <div id="hamburgerMenu" class="hidden absolute right-0 mt-2 w-56 bg-gray-900 border border-gray-700 rounded-lg shadow-xl z-50">
                            <a href="#home" class="block px-4 py-3 text-gray-300 hover:bg-purple-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-home text-purple-400 mr-2"></i>홈으로가기
                            </a>
                            <a href="/?page=about" class="block px-4 py-3 text-gray-300 hover:bg-indigo-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-building text-indigo-400 mr-2"></i>협회
                            </a>
                            <a href="#about" class="block px-4 py-3 text-gray-300 hover:bg-blue-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-info-circle text-blue-400 mr-2"></i>페스티벌소개
                            </a>
                            <a href="#schedule" class="block px-4 py-3 text-gray-300 hover:bg-green-900/50 hover:text-white transition-colors border-b border-gray-800">
                                <i class="fas fa-calendar text-green-400 mr-2"></i>일정볼까요?
                            </a>
                            <a href="regional-center.html" class="block px-4 py-3 text-gray-300 hover:bg-cyan-900/50 hover:text-white transition-colors rounded-b-lg">
                                <i class="fas fa-map-marker-alt text-cyan-400 mr-2"></i>지역센터개설
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen flex items-center justify-center gradient-bg overflow-hidden">
        <div class="hero-overlay absolute inset-0"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/20 via-transparent to-blue-900/20"></div>
        <div class="relative z-10 text-center text-white px-4 max-w-6xl mx-auto">
            <div data-aos="fade-up" class="mb-8">
                <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight cyber-glow">
                    <span class="italic bg-gradient-to-r from-purple-400 to-blue-400 bg-clip-text text-transparent">AI로 만드는</span><br>
                    <span class="text-yellow-300">미래의 코드</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90 max-w-3xl mx-auto leading-relaxed">
                    🎯 매주 다른 주제로 펼쳐지는 AI 코딩의 축제! <br>
                    기업가, 개발자, 학습자가 함께 만들어가는 네트워킹 현장
                </p>
            </div>
            
            <div data-aos="fade-up" data-aos-delay="200" class="mb-12">
                <div class="flex flex-wrap justify-center gap-4 text-sm md:text-base">
                    <span class="bg-gradient-to-r from-pink-500 to-purple-600 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30 transform hover:scale-105 transition-all whitespace-nowrap">
                        <i class="fas fa-calendar mr-2"></i>매주 정기 세미나 진행
                    </span>
                    <span class="bg-gradient-to-r from-blue-500 to-cyan-600 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30 transform hover:scale-105 transition-all whitespace-nowrap">
                        <i class="fas fa-users mr-2"></i>즉석 팀 매칭
                    </span>
                    <span class="bg-gradient-to-r from-green-500 to-teal-600 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30 transform hover:scale-105 transition-all whitespace-nowrap">
                        <i class="fas fa-trophy mr-2"></i>현장 계약 시스템
                    </span>
                    <span class="bg-gradient-to-r from-orange-500 to-red-600 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30 transform hover:scale-105 transition-all whitespace-nowrap">
                        <i class="fas fa-gift mr-2"></i>1주일 내 실무 적용
                    </span>
                </div>
            </div>

            <div data-aos="fade-up" data-aos-delay="400">
                <button onclick="scrollToSection('target')" class="bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white font-bold py-4 px-8 rounded-full text-lg transition-all transform hover:scale-105 shadow-lg floating">
                    <i class="fas fa-rocket mr-2"></i>지금 AI 전사 되기
                </button>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 section-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-aos="fade-up" class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4 cyber-glow">🎪 페스티벌 세미나란?</h2>
                <p class="text-xl text-gray-300 max-w-4xl mx-auto leading-relaxed">
                    전통적인 일방향 강의가 아닌, 참가자 모두가 주인공이 되는 쌍방향 네트워킹 축제입니다. 
                    매주 다른 산업군의 기업들이 참여하여 실제 비즈니스 문제를 공유하고, AI 개발자들이 즉석에서 솔루션을 제안하는 
                    <strong class="text-purple-400">"실시간 매칭 시스템"</strong>을 운영합니다.
                </p>
            </div>
            
            <!-- 세미나 형식 설명 -->
            <div data-aos="fade-up" class="glass-effect rounded-2xl p-8 mb-16">
                <h3 class="text-2xl font-bold text-center text-white mb-8 cyber-glow">🚀 3단계 진행 방식</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center dark-card rounded-xl p-6">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold neon-border">1</div>
                        <h4 class="text-lg font-bold text-white mb-2">🎯 문제 제안</h4>
                        <p class="text-gray-300 text-sm">기업들이 실제 업무 중遇到的 문제를 5분 lightning talk으로 발표</p>
                    </div>
                    <div class="text-center dark-card rounded-xl p-6">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold neon-border">2</div>
                        <h4 class="text-lg font-bold text-white mb-2">⚡ 즉석 해커톤</h4>
                        <p class="text-gray-300 text-sm">개발자들이 즉시 팀을 구성하여 2시간 내 MVP 솔루션 개발</p>
                    </div>
                    <div class="text-center dark-card rounded-xl p-6">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold neon-border">3</div>
                        <h4 class="text-lg font-bold text-white mb-2">🤝 현장 계약</h4>
                        <p class="text-gray-300 text-sm">기업이 마음에 든 솔루션을 현장에서 계약, 1주일 내 실무 적용</p>
                    </div>
                </div>
            </div>

            <div data-aos="fade-up" class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">4 in 1 페스티벌</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    하나의 행사로 네 가지 이상의 목적을 동시에 달성하는 혁신적인 플랫폼
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div data-aos="fade-up" data-aos-delay="100" class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl card-hover">
                    <i class="fas fa-exhibition text-4xl text-purple-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">AI 코딩 페스티벌</h3>
                    <p class="text-gray-600">최신 AI 기술과 솔루션을 한눈에 만나보세요</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="200" class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl card-hover">
                    <i class="fas fa-network-wired text-4xl text-blue-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">실시간 매칭</h3>
                    <p class="text-gray-600">기업과 개발자가 즉시 연결되는 네트워킹</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="300" class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl card-hover">
                    <i class="fas fa-graduation-cap text-4xl text-green-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">교육 쇼케이스</h3>
                    <p class="text-gray-600">AI 코딩 부트캠프와 학습 기회 소개</p>
                </div>
                
                <div data-aos="fade-up" data-aos-delay="400" class="text-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl card-hover">
                    <i class="fas fa-microchip text-4xl text-orange-600 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">AI 기술 페스티벌</h3>
                    <p class="text-gray-600">차세대 AI 기술 트렌드 체험</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Target Audience Section -->
    <section id="target" class="py-20 bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-aos="fade-up" class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-100 mb-4 neon-text">🎯 나는 어떤 AI 전사?</h2>
                <p class="text-xl text-slate-300">각자의 목적에 맞춘 맞춤형 경험을 제공합니다</p>
                <div class="bg-slate-800 border-l-4 border-blue-500 p-4 mt-6 max-w-2xl mx-auto">
                    <p class="text-blue-300 font-medium">💡 <strong>번개처럼 빠른 매칭!</strong> 클릭하면 즉시 맞춤형 정보가 제공됩니다</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- 기업체 -->
                <div data-aos="fade-up" data-aos-delay="100" class="dark-card rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('enterprise')">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white neon-border">
                        <i class="fas fa-building text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">기업체</h3>
                        <p class="opacity-90">AI 솔루션을 찾는 기업들을 위한 공간</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-300">
                            <li class="flex items-center"><i class="fas fa-check text-green-400 mr-3"></i>AI 기술자 매칭</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-400 mr-3"></i>업무 자동화 컨설팅</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-400 mr-3"></i>비용 견적 상담</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-400 mr-3"></i>실제 데모 체험</li>
                        </ul>
                        <button class="w-full mt-6 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 rounded-lg transition-all glow-button">
                            기업 솔루션 보기
                        </button>
                    </div>
                </div>
                        </button>
                    </div>
                </div>

                <!-- AI 코더 -->
                <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('coder')">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white">
                        <i class="fas fa-code text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">AI 개발자</h3>
                        <p class="opacity-90">프로젝트와 네트워킹을 원하는 개발자들</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>프로젝트 매칭</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>기업 협업 기회</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>기술 트렌드 공유</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>네트워킹 공간</li>
                        </ul>
                        <button class="w-full mt-6 bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            개발자 공간 가기
                        </button>
                    </div>
                </div>

                <!-- AI 학습자 -->
                <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('learner')">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
                        <i class="fas fa-graduation-cap text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">AI 학습자</h3>
                        <p class="opacity-90">AI 코딩을 배우고 싶은 분들을 위한 공간</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>부트캠프 정보</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>무료 세미나</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>실습 체험존</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>멘토링 프로그램</li>
                        </ul>
                        <button class="w-full mt-6 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            학습 공간 보기
                        </button>
                    </div>
                </div>

                <!-- AI 기술 트렌드 -->
                <div data-aos="fade-up" data-aos-delay="400" class="bg-white rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('tech')">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                        <i class="fas fa-chart-line text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">AI 기술 트렌드</h3>
                        <p class="opacity-90">최신 AI 기술 동향을 알고 싶은 분들</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>신기술 소개</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>전문가 강연</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>실무 적용 사례</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>미래 전망</li>
                        </ul>
                        <button class="w-full mt-6 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            기술 트렌드 보기
                        </button>
                    </div>
                </div>

                <!-- 슈퍼개발자 -->
                <div data-aos="fade-up" data-aos-delay="500" class="bg-white rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('superdev')">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 text-white">
                        <i class="fas fa-rocket text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">시니어 개발자</h3>
                        <p class="opacity-90">고급 기술과 리더십을 갖춘 개발자들</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>기술 리더십</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>멘토링 기회</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>고급 세미나</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>네트워킹</li>
                        </ul>
                        <button class="w-full mt-6 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            시니어 공간 가기
                        </button>
                    </div>
                </div>

                <!-- 일반인 -->
                <div data-aos="fade-up" data-aos-delay="600" class="bg-white rounded-xl shadow-lg overflow-hidden card-hover cursor-pointer" onclick="showDetails('general')">
                    <div class="bg-gradient-to-r from-teal-500 to-teal-600 p-6 text-white">
                        <i class="fas fa-user-friends text-4xl mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">일반인</h3>
                        <p class="opacity-90">AI에 관심 있는 모든 분들을 위한 공간</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>체험존</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>입문 강의</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>상담 코너</li>
                            <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i>쇼케이스</li>
                        </ul>
                        <button class="w-full mt-6 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            체험 공간 보기
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Section -->
    <section id="schedule" class="py-20 bg-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-aos="fade-up" class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-100 mb-4 neon-text">🗓️ 지속가능한 축제</h2>
                <p class="text-xl text-slate-300">매주 새로운 주제, 새로운 기업, 새로운 개발자들이 만나는 생태계</p>
                
                <!-- 재미 요소 추가 -->
                <div class="bg-gradient-to-r from-slate-700 to-slate-600 rounded-2xl p-6 mt-8 max-w-4xl mx-auto">
                    <h3 class="text-2xl font-bold text-center text-slate-100 mb-8">🎮 게이미피케이션 요소</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                        <div class="bg-slate-800 rounded-lg p-4 shadow-md border border-slate-600">
                            <i class="fas fa-medal text-3xl text-yellow-500 mb-2"></i>
                            <h4 class="font-bold text-gray-900">월간 MVP</h4>
                            <p class="text-sm text-gray-600">최고의 솔루션을 만든 팀에게 인센티브</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-md">
                            <i class="fas fa-fire text-3xl text-red-500 mb-2"></i>
                            <h4 class="font-bold text-gray-900">연승 보너스</h4>
                            <p class="text-sm text-gray-600">연속 참가팀에게 특별 혜택</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-md">
                            <i class="fas fa-star text-3xl text-purple-500 mb-2"></i>
                            <h4 class="font-bold text-gray-900">레벨 시스템</h4>
                            <p class="text-sm text-gray-600">참가 횟수에 따른 등급 부여</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-2xl p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div data-aos="fade-up" data-aos-delay="100" class="bg-white rounded-xl p-6 shadow-lg">
                        <i class="fas fa-calendar-alt text-4xl text-purple-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">매주 정기 세미나</h3>
                        <p class="text-gray-600">정기 메인 이벤트</p>
                        <p class="text-sm text-gray-500 mt-2">14:00 - 20:00</p>
                    </div>
                    
                    <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-xl p-6 shadow-lg">
                        <i class="fas fa-bolt text-4xl text-blue-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">번개 모임</h3>
                        <p class="text-gray-600">정기세미나 외 특별 이벤트</p>
                        <p class="text-sm text-gray-500 mt-2">상시 진행</p>
                    </div>
                    
                    <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-xl p-6 shadow-lg">
                        <i class="fas fa-globe text-4xl text-green-600 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">온라인 연결</h3>
                        <p class="text-gray-600">온라인 참가도 가능</p>
                        <p class="text-sm text-gray-500 mt-2">실시간 스트리밍</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-slate-900 text-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-aos="fade-up" class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4 neon-text">함께 하세요</h2>
                <p class="text-xl text-slate-300">AI 코딩의 미래를 함께 만들어갈 분들을 기다립니다</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div data-aos="fade-right">
                    <h3 class="text-2xl font-bold mb-6">문의하기</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-purple-400 mr-4"></i>
                            <span><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="8bbafbeaeee9e4e4e0cbe5eafdeef9a5e8e4e6">[email&#160;protected]</a></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-purple-400 mr-4"></i>
                            <span>031-764-1883</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-purple-400 mr-4"></i>
                            <span>장소 : 서울벤처대학원대학교</span>
                        </div>
                    </div>
                </div>
                
                <div data-aos="fade-left">
                    <h3 class="text-2xl font-bold mb-6">소셜 미디어</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-purple-600 hover:bg-purple-700 p-4 rounded-full transition-colors">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="bg-blue-600 hover:bg-blue-700 p-4 rounded-full transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="bg-blue-800 hover:bg-blue-900 p-4 rounded-full transition-colors">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                        <a href="#" class="bg-red-600 hover:bg-red-700 p-4 rounded-full transition-colors">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black border-t border-gray-800 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex justify-center items-center mb-4">
                <i class="fas fa-rocket text-2xl text-purple-400 mr-2 cyber-glow"></i>
                <span class="text-lg font-bold text-white">AI 코더와 수요자 매칭 페스티벌</span>
            </div>
            <p class="text-gray-400 mb-2">이제 비즈 컨셉만 있으면 직접 AI로 서비스하는 시대</p>
            <div class="flex justify-center space-x-6 my-4">
                <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors"><i class="fab fa-linkedin"></i></a>
                <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors"><i class="fab fa-github"></i></a>
                <a href="#" class="text-gray-400 hover:text-purple-400 transition-colors"><i class="fab fa-youtube"></i></a>
            </div>
            <p class="text-gray-500 text-sm">&copy; 2025 한국AI코딩허브협회. 모든 권리 보유.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Smooth scrolling
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            element.scrollIntoView({ behavior: 'smooth' });
        }

        // Show details for different user types
        function showDetails(userType) {
            // Store user type for personalized experience
            localStorage.setItem('userType', userType);
            
            // Create personalized message
            const messages = {
                'enterprise': '기업 맞춤형 솔루션 페이지로 이동합니다.',
                'coder': 'AI 개발자 전용 공간으로 이동합니다.',
                'learner': 'AI 학습자를 위한 공간으로 이동합니다.',
                'tech': '최신 AI 기술 정보로 이동합니다.',
                'superdev': '시니어 개발자 커뮤니티로 이동합니다.',
                'general': '일반인을 위한 AI 체험존으로 이동합니다.'
            };
            
            alert(messages[userType] || '맞춤형 페이지로 이동합니다.');
            
            // In a real implementation, this would navigate to a personalized page
            // window.location.href = `${userType}.html`;
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-white/98');
            } else {
                nav.classList.remove('bg-white/98');
            }
        });

        // Mobile Dropdown Toggle - 햄버거 메뉴
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const targetBtn = document.getElementById('targetBtn');
            const targetMenu = document.getElementById('targetMenu');

            // 햄버거 메뉴 토글
            if (hamburgerBtn && hamburgerMenu) {
                hamburgerBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hamburgerMenu.classList.toggle('hidden');
                    // 대상 메뉴 닫기
                    if (targetMenu) targetMenu.classList.add('hidden');
                });
            }

            // 대상 메뉴 토글
            if (targetBtn && targetMenu) {
                targetBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    targetMenu.classList.toggle('hidden');
                    // 햄버거 메뉴 닫기
                    if (hamburgerMenu) hamburgerMenu.classList.add('hidden');
                });
            }

            // 외부 클릭 시 메뉴 닫기
            document.addEventListener('click', function(e) {
                if (hamburgerMenu && !hamburgerMenu.contains(e.target) && e.target !== hamburgerBtn) {
                    hamburgerMenu.classList.add('hidden');
                }
                if (targetMenu && !targetMenu.contains(e.target) && e.target !== targetBtn) {
                    targetMenu.classList.add('hidden');
                }
            });
        });

        // Countdown timer (optional)
        function updateCountdown() {
            // Implementation for countdown to next event
            const now = new Date();
            const nextWednesday = new Date();
            nextWednesday.setDate(now.getDate() + (3 - now.getDay() + 7) % 7);
            nextWednesday.setHours(14, 0, 0, 0);
            
            if (nextWednesday <= now) {
                nextWednesday.setDate(nextWednesday.getDate() + 7);
            }
            
            const diff = nextWednesday - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            
            // Update countdown display if exists
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                countdownElement.innerHTML = `다음 행사까지 ${days}일 ${hours}시간`;
            }
        }

        // Update countdown every minute
        setInterval(updateCountdown, 60000);
        updateCountdown();
    </script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
