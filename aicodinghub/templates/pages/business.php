<?php 
$page_title = "사업안내 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-[60vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-purple-900/20 to-gray-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400 text-transparent bg-clip-text">
                사업 안내
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-4">
                AI코딩 허브플랫폼 기반 핵심 사업
            </p>
            <p class="text-lg text-gray-400 max-w-3xl mx-auto">
                개발자, 기업, 교육기관을 연결하고 실전 프로젝트를 통해 수익을 창출하는 생태계
            </p>
        </div>
    </div>
</section>

<!-- Main Business Areas -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                주요 사업 영역
            </h2>
            <p class="text-gray-400 text-lg">AI코딩 허브플랫폼을 중심으로 한 4대 핵심 사업</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Business Card 1 -->
            <div class="group relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-purple-500/20 transition-all duration-300 border border-gray-700 hover:border-purple-500" data-aos="fade-up" data-aos-delay="0">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/10 to-pink-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-5xl mb-6">💼</div>
                    <h3 class="text-2xl font-bold text-white mb-4">AI코딩 허브플랫폼</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        개발자, 기업, 교육기관을 하나로 연결하는 통합 플랫폼입니다. 
                        AI 기반 매칭 시스템으로 최적의 파트너를 찾아드립니다.
                    </p>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>개발자 프로필 관리 및 포트폴리오 구축</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>기업 프로젝트 등록 및 인재 매칭</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>교육기관 수료생 취업 연계</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Business Card 2 -->
            <div class="group relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 border border-gray-700 hover:border-blue-500" data-aos="fade-up" data-aos-delay="100">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-purple-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-5xl mb-6">🎓</div>
                    <h3 class="text-2xl font-bold text-white mb-4">교육-실전 연계 프로그램</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        AI코딩 교육 수료생을 실전 프로젝트로 연결하여 
                        실무 경험과 수익 창출을 동시에 지원합니다.
                    </p>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>AI코딩 교육 커리큘럼 제공</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>실전 프로젝트 참여 기회 제공</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>멘토링 및 취업 지원 서비스</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Business Card 3 -->
            <div class="group relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-pink-500/20 transition-all duration-300 border border-gray-700 hover:border-pink-500" data-aos="fade-up" data-aos-delay="200">
                <div class="absolute inset-0 bg-gradient-to-r from-pink-600/10 to-purple-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-5xl mb-6">🤝</div>
                    <h3 class="text-2xl font-bold text-white mb-4">AI 기반 프로젝트 매칭</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        기업의 프로젝트 요구사항과 개발자의 역량을 
                        AI가 분석하여 최적의 매칭을 제공합니다.
                    </p>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>AI 기반 역량 분석 및 매칭</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>프로젝트 에스크로 시스템</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>계약 및 정산 관리 지원</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Business Card 4 -->
            <div class="group relative bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-green-500/20 transition-all duration-300 border border-gray-700 hover:border-green-500" data-aos="fade-up" data-aos-delay="300">
                <div class="absolute inset-0 bg-gradient-to-r from-green-600/10 to-blue-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-5xl mb-6">🎉</div>
                    <h3 class="text-2xl font-bold text-white mb-4">AI코딩 페스티벌</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        매주 열리는 네트워킹 세미나를 통해 
                        개발자, 기업, 학습자가 만나 협업 기회를 창출합니다.
                    </p>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                            <span>매주 주말 온/오프라인 개최</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                            <span>네트워킹 및 프로젝트 피칭</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                            <span>지역센터 확대 운영</span>
                        </li>
                    </ul>
                    <a href="/festival/" class="inline-block mt-4 px-6 py-2 bg-gradient-to-r from-green-500 to-blue-500 text-white rounded-full hover:shadow-lg hover:shadow-green-500/50 transition-all">
                        페스티벌 자세히 보기 <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Business Process -->
<section class="py-20 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-400 text-transparent bg-clip-text">
                사업 진행 프로세스
            </h2>
            <p class="text-gray-400 text-lg">간단한 3단계로 시작하는 AI코딩 생태계</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Step 1 -->
            <div class="relative" data-aos="fade-up" data-aos-delay="0">
                <div class="bg-gradient-to-br from-purple-900/50 to-gray-900 rounded-2xl p-8 border border-purple-500/30 hover:border-purple-500 transition-all">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 text-white text-2xl font-bold mb-4">
                            1
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">회원가입 및 프로필 작성</h3>
                        <p class="text-gray-400">
                            개발자, 기업, 학습자 유형에 맞는<br/>
                            프로필을 작성하고 검증받습니다
                        </p>
                    </div>
                </div>
                <!-- Arrow -->
                <div class="hidden md:block absolute top-1/2 -right-4 transform -translate-y-1/2 text-purple-500 text-3xl z-20">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="relative" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-gradient-to-br from-blue-900/50 to-gray-900 rounded-2xl p-8 border border-blue-500/30 hover:border-blue-500 transition-all">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 text-white text-2xl font-bold mb-4">
                            2
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">매칭 및 프로젝트 참여</h3>
                        <p class="text-gray-400">
                            AI 기반 매칭으로 최적의<br/>
                            프로젝트나 파트너를 찾습니다
                        </p>
                    </div>
                </div>
                <!-- Arrow -->
                <div class="hidden md:block absolute top-1/2 -right-4 transform -translate-y-1/2 text-blue-500 text-3xl z-20">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="relative" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-gradient-to-br from-green-900/50 to-gray-900 rounded-2xl p-8 border border-green-500/30 hover:border-green-500 transition-all">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-green-500 to-blue-500 text-white text-2xl font-bold mb-4">
                            3
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">협업 및 수익 창출</h3>
                        <p class="text-gray-400">
                            실전 프로젝트를 수행하고<br/>
                            수익을 창출합니다
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Business Support -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-pink-400 to-purple-400 text-transparent bg-clip-text">
                협회 지원 서비스
            </h2>
            <p class="text-gray-400 text-lg">성공적인 프로젝트 수행을 위한 전방위 지원</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-purple-500 transition-all" data-aos="fade-up" data-aos-delay="0">
                <div class="text-3xl mb-4">📚</div>
                <h3 class="text-xl font-bold text-white mb-2">교육 지원</h3>
                <p class="text-gray-400">AI코딩 실무 교육 및 자격증 취득 지원</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-blue-500 transition-all" data-aos="fade-up" data-aos-delay="100">
                <div class="text-3xl mb-4">💰</div>
                <h3 class="text-xl font-bold text-white mb-2">정산 지원</h3>
                <p class="text-gray-400">안전한 에스크로 및 자동 정산 시스템</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-green-500 transition-all" data-aos="fade-up" data-aos-delay="200">
                <div class="text-3xl mb-4">⚖️</div>
                <h3 class="text-xl font-bold text-white mb-2">법률 지원</h3>
                <p class="text-gray-400">계약서 검토 및 분쟁 조정 서비스</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-pink-500 transition-all" data-aos="fade-up" data-aos-delay="300">
                <div class="text-3xl mb-4">🎯</div>
                <h3 class="text-xl font-bold text-white mb-2">마케팅 지원</h3>
                <p class="text-gray-400">프로젝트 홍보 및 포트폴리오 제작</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-yellow-500 transition-all" data-aos="fade-up" data-aos-delay="400">
                <div class="text-3xl mb-4">🏢</div>
                <h3 class="text-xl font-bold text-white mb-2">공간 지원</h3>
                <p class="text-gray-400">지역센터 업무 공간 및 회의실 제공</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-red-500 transition-all" data-aos="fade-up" data-aos-delay="500">
                <div class="text-3xl mb-4">🤖</div>
                <h3 class="text-xl font-bold text-white mb-2">AI 도구 지원</h3>
                <p class="text-gray-400">최신 AI코딩 도구 및 API 크레딧 제공</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-purple-900/30 via-gray-900 to-blue-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-6 text-white">
                지금 바로 시작하세요!
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                AI코딩 생태계의 중심에서 여러분의 미래를 만들어가세요
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/?page=register" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-user-plus mr-2"></i>
                    회원가입하기
                </a>
                <a href="/festival/" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    페스티벌 참여하기
                </a>
                <a href="/?page=contact" class="px-8 py-4 border-2 border-gray-600 text-white rounded-full font-semibold hover:bg-gray-800 hover:border-purple-500 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-envelope mr-2"></i>
                    문의하기
                </a>
            </div>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
