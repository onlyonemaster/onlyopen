<?php 
$page_title = "홈"; 
include dirname(__DIR__) . '/components/header-dark.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900/20 to-slate-900">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgxMzksOTIsMjQ2LDAuMSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent via-purple-900/10 to-slate-900"></div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10" data-aos="fade-up">
        <div class="text-center max-w-5xl mx-auto">
            <!-- Main Title -->
            <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-cyan-400 text-transparent bg-clip-text cyber-glow">
                    AI코딩으로 일하고,
                </span>
                <br>
                <span class="bg-gradient-to-r from-cyan-400 via-blue-400 to-purple-400 text-transparent bg-clip-text">
                    연결되고, 수익을 만드는
                </span>
            </h1>
            
            <p class="text-xl md:text-2xl text-gray-300 mb-8 leading-relaxed">
                교육-개발자-기업-프로젝트-수익을 하나의 <span class="text-purple-400 font-semibold">AI코딩 허브플랫폼</span>으로 연결합니다
            </p>
            
            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16">
                <a href="/?page=register" class="group bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-8 py-4 rounded-xl text-lg font-bold transition-all transform hover:scale-105 shadow-lg hover:shadow-purple-500/50 flex items-center gap-2">
                    <i class="fas fa-rocket"></i>
                    지금 시작하기
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
                <a href="/?page=platform" class="bg-slate-800/50 border border-purple-500/50 hover:bg-slate-800 hover:border-purple-500 text-white px-8 py-4 rounded-xl text-lg font-bold transition-all flex items-center gap-2">
                    <i class="fas fa-play-circle"></i>
                    플랫폼 둘러보기
                </a>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text mb-2">
                        1,234+
                    </div>
                    <div class="text-gray-400 text-sm md:text-base">등록 회원</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-cyan-400 to-blue-400 text-transparent bg-clip-text mb-2">
                        567+
                    </div>
                    <div class="text-gray-400 text-sm md:text-base">진행 프로젝트</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black bg-gradient-to-r from-green-400 to-emerald-400 text-transparent bg-clip-text mb-2">
                        ₩123M+
                    </div>
                    <div class="text-gray-400 text-sm md:text-base">수익 창출</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <i class="fas fa-chevron-down text-purple-400 text-2xl"></i>
    </div>
</section>

<!-- Core Values Section -->
<section class="py-20 bg-slate-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r from-purple-400 to-cyan-400 text-transparent bg-clip-text">
                협회 핵심 가치
            </h2>
            <p class="text-xl text-gray-400">한국AI코딩허브협회가 지향하는 5가지 핵심 가치</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <!-- Value Card 1 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-purple-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/20" data-aos="fade-up" data-aos-delay="100">
                <div class="text-5xl mb-4">
                    <i class="fas fa-link text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">연결 (Connection)</h3>
                <p class="text-gray-400 leading-relaxed">사람과 기술, 교육과 일, 프로젝트와 수익을 끊김 없이 연결합니다.</p>
            </div>
            
            <!-- Value Card 2 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-blue-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2 hover:shadow-xl hover:shadow-blue-500/20" data-aos="fade-up" data-aos-delay="200">
                <div class="text-5xl mb-4">
                    <i class="fas fa-bolt text-blue-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">실전 (Execution)</h3>
                <p class="text-gray-400 leading-relaxed">이론이 아닌 실제 결과물과 성과 중심의 AI코딩 생태계를 만듭니다.</p>
            </div>
            
            <!-- Value Card 3 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-cyan-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2 hover:shadow-xl hover:shadow-cyan-500/20" data-aos="fade-up" data-aos-delay="300">
                <div class="text-5xl mb-4">
                    <i class="fas fa-globe text-cyan-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">개방 (Open)</h3>
                <p class="text-gray-400 leading-relaxed">누구나 참여할 수 있는 열린 플랫폼과 투명한 운영을 지향합니다.</p>
            </div>
            
            <!-- Value Card 4 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-green-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2 hover:shadow-xl hover:shadow-green-500/20" data-aos="fade-up" data-aos-delay="400">
                <div class="text-5xl mb-4">
                    <i class="fas fa-users text-green-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">협력 (Collaboration)</h3>
                <p class="text-gray-400 leading-relaxed">개발자, 기업, 교육기관이 함께 성장하는 협력 생태계를 구축합니다.</p>
            </div>
            
            <!-- Value Card 5 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-orange-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2 hover:shadow-xl hover:shadow-orange-500/20" data-aos="fade-up" data-aos-delay="500">
                <div class="text-5xl mb-4">
                    <i class="fas fa-lightbulb text-orange-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">혁신 (Innovation)</h3>
                <p class="text-gray-400 leading-relaxed">AI 기술을 활용한 새로운 가치 창출과 산업 혁신을 선도합니다.</p>
            </div>
        </div>
    </div>
</section>

<!-- Main Services Section -->
<section class="py-20 bg-gradient-to-b from-slate-900 to-slate-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r from-cyan-400 to-blue-400 text-transparent bg-clip-text">
                주요 사업
            </h2>
            <p class="text-xl text-gray-400">한국AI코딩허브협회의 4대 핵심 사업</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
            <!-- Service 1 -->
            <div class="group bg-gradient-to-br from-purple-900/20 to-slate-800 border border-gray-700 hover:border-purple-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2" data-aos="fade-right">
                <div class="text-6xl mb-6">
                    <i class="fas fa-graduation-cap text-purple-400"></i>
                </div>
                <h3 class="text-3xl font-bold text-white mb-4">교육-실전 연계</h3>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    AI코딩 교육이 실제 프로젝트와 수익으로 연결됩니다. 배운 내용을 즉시 실전에 적용하고 수익을 창출하세요.
                </p>
                <a href="/?page=business" class="inline-flex items-center text-purple-400 hover:text-purple-300 font-semibold group-hover:gap-3 gap-2 transition-all">
                    자세히 보기
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Service 2 -->
            <div class="group bg-gradient-to-br from-blue-900/20 to-slate-800 border border-gray-700 hover:border-blue-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2" data-aos="fade-left">
                <div class="text-6xl mb-6">
                    <i class="fas fa-handshake text-blue-400"></i>
                </div>
                <h3 class="text-3xl font-bold text-white mb-4">프로젝트 매칭</h3>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    기업의 요구와 개발자의 역량을 AI 기반으로 매칭합니다. 최적의 파트너를 찾아 프로젝트를 성공시키세요.
                </p>
                <a href="/?page=platform" class="inline-flex items-center text-blue-400 hover:text-blue-300 font-semibold group-hover:gap-3 gap-2 transition-all">
                    자세히 보기
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Service 3 -->
            <div class="group bg-gradient-to-br from-pink-900/20 to-slate-800 border border-gray-700 hover:border-pink-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2" data-aos="fade-right">
                <div class="text-6xl mb-6">
                    <i class="fas fa-party-horn text-pink-400"></i>
                </div>
                <h3 class="text-3xl font-bold text-white mb-4">AI코딩 페스티벌</h3>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    전국 단위 AI코딩 페스티벌로 인재 발굴과 매칭이 동시에 일어납니다. 네트워킹의 장을 경험하세요.
                </p>
                <a href="/?page=festival" class="inline-flex items-center text-pink-400 hover:text-pink-300 font-semibold group-hover:gap-3 gap-2 transition-all">
                    자세히 보기
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Service 4 -->
            <div class="group bg-gradient-to-br from-green-900/20 to-slate-800 border border-gray-700 hover:border-green-500 rounded-2xl p-8 transition-all hover:transform hover:-translate-y-2" data-aos="fade-left">
                <div class="text-6xl mb-6">
                    <i class="fas fa-certificate text-green-400"></i>
                </div>
                <h3 class="text-3xl font-bold text-white mb-4">인증 & 표준</h3>
                <p class="text-gray-300 mb-6 leading-relaxed">
                    AI코딩 역량 기준과 등급 체계를 수립하고 인증합니다. 표준화된 평가로 실력을 인정받으세요.
                </p>
                <a href="/?page=business" class="inline-flex items-center text-green-400 hover:text-green-300 font-semibold group-hover:gap-3 gap-2 transition-all">
                    자세히 보기
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Target Audience Section -->
<section class="py-20 bg-slate-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r from-orange-400 to-pink-400 text-transparent bg-clip-text">
                참여 대상
            </h2>
            <p class="text-xl text-gray-400">누구나 참여할 수 있는 개방형 플랫폼</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
            <!-- Target 1 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-purple-500 rounded-2xl p-6 text-center transition-all hover:transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="100">
                <div class="text-6xl mb-4">
                    <i class="fas fa-code text-purple-400"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">개인 개발자</h3>
                <p class="text-gray-400 text-sm mb-4">역량을 등록하고 프로젝트를 매칭받아 수익을 창출하세요.</p>
                <a href="/?page=register" class="inline-block bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    가입하기
                </a>
            </div>
            
            <!-- Target 2 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-blue-500 rounded-2xl p-6 text-center transition-all hover:transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="200">
                <div class="text-6xl mb-4">
                    <i class="fas fa-building text-blue-400"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">기업</h3>
                <p class="text-gray-400 text-sm mb-4">프로젝트를 등록하고 최적의 개발자를 찾으세요.</p>
                <a href="/?page=register" class="inline-block bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    가입하기
                </a>
            </div>
            
            <!-- Target 3 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-green-500 rounded-2xl p-6 text-center transition-all hover:transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="300">
                <div class="text-6xl mb-4">
                    <i class="fas fa-school text-green-400"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">교육기관</h3>
                <p class="text-gray-400 text-sm mb-4">수료생을 플랫폼에 연결하고 실전 경험을 제공하세요.</p>
                <a href="/?page=register" class="inline-block bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    파트너 등록
                </a>
            </div>
            
            <!-- Target 4 -->
            <div class="bg-slate-800/50 border border-gray-700 hover:border-orange-500 rounded-2xl p-6 text-center transition-all hover:transform hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="400">
                <div class="text-6xl mb-4">
                    <i class="fas fa-users text-orange-400"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">팀/센터</h3>
                <p class="text-gray-400 text-sm mb-4">팀을 구성하고 대규모 프로젝트에 도전하세요.</p>
                <a href="/?page=register" class="inline-block bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    가입하기
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Latest News Section -->
<?php
$notices = safeDBCall(function($pdo) {
    $stmt = $pdo->query("SELECT * FROM boards WHERE board_type = 'notice' AND status = 'active' ORDER BY created_at DESC LIMIT 3");
    return $stmt->fetchAll();
}, []);
?>

<?php if (!empty($notices)): ?>
<section class="py-20 bg-gradient-to-b from-slate-800 to-slate-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r from-yellow-400 to-orange-400 text-transparent bg-clip-text">
                최신 소식
            </h2>
            <p class="text-xl text-gray-400">협회의 최신 소식을 확인하세요</p>
        </div>
        
        <div class="max-w-4xl mx-auto space-y-4">
            <?php foreach ($notices as $notice): ?>
            <div class="bg-slate-800/50 border border-gray-700 hover:border-purple-500 rounded-xl p-6 transition-all hover:transform hover:-translate-x-2" data-aos="fade-left">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <span class="inline-block bg-purple-600 text-white px-3 py-1 rounded-full text-xs font-bold mb-3">
                            공지
                        </span>
                        <h3 class="text-xl font-bold text-white mb-2 hover:text-purple-400 transition-colors">
                            <a href="/?page=board&id=<?php echo $notice['board_id']; ?>">
                                <?php echo htmlspecialchars($notice['title']); ?>
                            </a>
                        </h3>
                        <p class="text-gray-400 text-sm">
                            <?php echo formatDate($notice['created_at']); ?>
                        </p>
                    </div>
                    <a href="/?page=board&id=<?php echo $notice['board_id']; ?>" class="ml-4 bg-slate-700 hover:bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                        읽기
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12" data-aos="fade-up">
            <a href="/?page=board" class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all transform hover:scale-105">
                더 많은 소식 보기
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-purple-900/30 to-blue-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="zoom-in">
            <h2 class="text-4xl md:text-5xl font-black mb-6 text-white">
                지금 바로 시작하세요!
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                한국AI코딩허브협회와 함께 AI코딩의 미래를 만들어가세요
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/?page=register" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white px-10 py-4 rounded-xl text-lg font-bold transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i>
                    회원가입
                </a>
                <a href="/?page=contact" class="bg-slate-800 border border-purple-500 hover:bg-slate-700 text-white px-10 py-4 rounded-xl text-lg font-bold transition-all">
                    <i class="fas fa-envelope mr-2"></i>
                    문의하기
                </a>
            </div>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/components/footer-dark.php'; ?>
