<?php 
$page_title = "협회소개"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- About Hero Section -->
<section class="relative min-h-[60vh] flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900/20 to-slate-900">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgxMzksOTIsMjQ2LDAuMSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
    
    <div class="container mx-auto px-4 relative z-10" data-aos="fade-up">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-black mb-6 bg-gradient-to-r from-purple-400 to-cyan-400 text-transparent bg-clip-text">
                협회 소개
            </h1>
            <p class="text-xl text-gray-300">
                한국AI코딩허브협회는 AI코딩 생태계의 중심입니다
            </p>
        </div>
    </div>
</section>

<!-- Vision & Mission Section -->
<section class="py-20 bg-slate-900">
    <div class="container mx-auto px-4">
        <!-- Vision -->
        <div class="max-w-5xl mx-auto mb-20" data-aos="fade-up">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-black mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                    비전 (Vision)
                </h2>
            </div>
            <div class="bg-gradient-to-br from-purple-900/30 to-blue-900/30 border border-purple-500/50 rounded-2xl p-10 text-center">
                <p class="text-3xl md:text-4xl font-bold text-white leading-relaxed">
                    "AI코딩으로 일하고, 연결되고,<br>수익을 만드는 대한민국 <span class="text-purple-400">AI코딩 허브 생태계</span>의 중심이 된다."
                </p>
            </div>
        </div>
        
        <!-- Mission -->
        <div class="max-w-6xl mx-auto" data-aos="fade-up">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-black mb-4 bg-gradient-to-r from-cyan-400 to-blue-400 text-transparent bg-clip-text">
                    미션 (Mission)
                </h2>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-slate-800/50 border border-gray-700 hover:border-purple-500 rounded-xl p-8 transition-all hover:transform hover:-translate-y-2">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl text-purple-400">
                            <i class="fas fa-link"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">단절 해소</h3>
                            <p class="text-gray-400 leading-relaxed">
                                AI코딩 교육, 실전, 수익 창출 사이의 단절을 해소하고 통합 생태계를 구축합니다.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-800/50 border border-gray-700 hover:border-blue-500 rounded-xl p-8 transition-all hover:transform hover:-translate-y-2">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl text-blue-400">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">개방형 생태계</h3>
                            <p class="text-gray-400 leading-relaxed">
                                개방적이고 투명한 AI코딩 생태계를 만들어 누구나 참여할 수 있는 환경을 조성합니다.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-800/50 border border-gray-700 hover:border-green-500 rounded-xl p-8 transition-all hover:transform hover:-translate-y-2">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl text-green-400">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">프로젝트 중심</h3>
                            <p class="text-gray-400 leading-relaxed">
                                프로젝트 중심의 AI코딩 산업 구조를 정착시켜 실전 중심의 생태계를 만듭니다.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-800/50 border border-gray-700 hover:border-orange-500 rounded-xl p-8 transition-all hover:transform hover:-translate-y-2">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl text-orange-400">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-3">공정한 중개</h3>
                            <p class="text-gray-400 leading-relaxed">
                                허브플랫폼을 기반으로 한 공정한 중개, 매칭, 정산 시스템을 구축합니다.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Business Section -->
<section class="py-20 bg-gradient-to-b from-slate-900 to-slate-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-black mb-4 bg-gradient-to-r from-yellow-400 to-orange-400 text-transparent bg-clip-text">
                핵심 사업 영역
            </h2>
            <p class="text-xl text-gray-400">한국AI코딩허브협회의 4대 핵심 사업</p>
        </div>
        
        <div class="max-w-6xl mx-auto grid gap-8">
            <div class="bg-slate-800/50 border border-gray-700 hover:border-purple-500 rounded-2xl p-8 transition-all" data-aos="fade-right">
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-5xl">
                        <i class="fas fa-network-wired text-purple-400"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white">AI코딩 허브 플랫폼</h3>
                </div>
                <p class="text-gray-300 leading-relaxed text-lg">
                    개발자, 기업, 교육기관을 연결하는 통합 플랫폼을 운영합니다. 프로젝트 매칭, 포트폴리오 관리, 역량 인증 등 모든 서비스가 하나의 플랫폼에서 제공됩니다.
                </p>
            </div>
            
            <div class="bg-slate-800/50 border border-gray-700 hover:border-blue-500 rounded-2xl p-8 transition-all" data-aos="fade-left">
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-5xl">
                        <i class="fas fa-graduation-cap text-blue-400"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white">교육-실전-수익 연계</h3>
                </div>
                <p class="text-gray-300 leading-relaxed text-lg">
                    AI코딩 교육을 실제 프로젝트와 연결하고, 수익 창출까지 이어지는 통합 시스템을 구축합니다. 배움이 곧 수익으로 연결되는 선순환 구조를 만듭니다.
                </p>
            </div>
            
            <div class="bg-slate-800/50 border border-gray-700 hover:border-pink-500 rounded-2xl p-8 transition-all" data-aos="fade-right">
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-5xl">
                        <i class="fas fa-calendar-star text-pink-400"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white">AI코딩 페스티벌</h3>
                </div>
                <p class="text-gray-300 leading-relaxed text-lg">
                    전국 단위의 AI코딩 페스티벌을 정기적으로 개최합니다. 네트워킹, 인재 발굴, 프로젝트 매칭, 교육 쇼케이스가 한 자리에서 이루어지는 축제의 장을 만듭니다.
                </p>
            </div>
            
            <div class="bg-slate-800/50 border border-gray-700 hover:border-green-500 rounded-2xl p-8 transition-all" data-aos="fade-left">
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-5xl">
                        <i class="fas fa-award text-green-400"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-white">표준 관리 및 인증</h3>
                </div>
                <p class="text-gray-300 leading-relaxed text-lg">
                    AI코딩 역량 기준과 등급 체계를 수립하고, 공정한 인증 시스템을 운영합니다. 산업 표준을 만들어가는 중심 기관으로서의 역할을 수행합니다.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Organization Structure Section -->
<section class="py-20 bg-slate-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-black mb-4 bg-gradient-to-r from-purple-400 to-cyan-400 text-transparent bg-clip-text">
                조직 구조
            </h2>
            <p class="text-xl text-gray-400">투명하고 효율적인 운영 체계</p>
        </div>
        
        <div class="max-w-4xl mx-auto">
            <div class="bg-slate-800/50 border border-gray-700 rounded-2xl p-8" data-aos="zoom-in">
                <div class="text-center mb-8">
                    <div class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg font-bold text-xl">
                        이사회
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-slate-900/50 border border-gray-600 rounded-xl p-6 text-center">
                        <div class="text-4xl mb-3">
                            <i class="fas fa-user-tie text-purple-400"></i>
                        </div>
                        <h4 class="text-lg font-bold text-white mb-2">사무국</h4>
                        <p class="text-gray-400 text-sm">운영 및 행정</p>
                    </div>
                    
                    <div class="bg-slate-900/50 border border-gray-600 rounded-xl p-6 text-center">
                        <div class="text-4xl mb-3">
                            <i class="fas fa-laptop-code text-blue-400"></i>
                        </div>
                        <h4 class="text-lg font-bold text-white mb-2">플랫폼팀</h4>
                        <p class="text-gray-400 text-sm">기술 개발 및 운영</p>
                    </div>
                    
                    <div class="bg-slate-900/50 border border-gray-600 rounded-xl p-6 text-center">
                        <div class="text-4xl mb-3">
                            <i class="fas fa-users text-green-400"></i>
                        </div>
                        <h4 class="text-lg font-bold text-white mb-2">사업팀</h4>
                        <p class="text-gray-400 text-sm">기획 및 마케팅</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-purple-900/30 to-blue-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="zoom-in">
            <h2 class="text-4xl font-black mb-6 text-white">
                함께 만들어가는 AI코딩 생태계
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                한국AI코딩허브협회와 함께하세요
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

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
