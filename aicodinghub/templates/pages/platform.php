<?php 
$page_title = "허브플랫폼 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-[70vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-blue-900/20 to-gray-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/3 left-1/3 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/3 right-1/3 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 text-transparent bg-clip-text">
                AI코딩 허브플랫폼
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-4">
                개발자와 기업을 연결하는 스마트 매칭 플랫폼
            </p>
            <p class="text-lg text-gray-400 max-w-3xl mx-auto mb-8">
                AI 기반 매칭 시스템으로 최적의 파트너를 찾고, 실전 프로젝트를 통해 수익을 창출하세요
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="?page=register" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-rocket mr-2"></i>
                    지금 시작하기
                </a>
                <a href="#how-it-works" class="px-8 py-4 border-2 border-gray-600 text-white rounded-full font-semibold hover:bg-gray-800 hover:border-blue-500 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-info-circle mr-2"></i>
                    작동 방식 보기
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Platform Stats -->
<section class="py-16 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-5xl mx-auto">
            <div class="text-center" data-aos="fade-up" data-aos-delay="0">
                <div class="text-4xl md:text-5xl font-bold text-blue-400 mb-2">1,234+</div>
                <div class="text-gray-400">등록 개발자</div>
            </div>
            <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="text-4xl md:text-5xl font-bold text-purple-400 mb-2">567+</div>
                <div class="text-gray-400">기업 회원</div>
            </div>
            <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="text-4xl md:text-5xl font-bold text-pink-400 mb-2">892+</div>
                <div class="text-gray-400">완료 프로젝트</div>
            </div>
            <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="text-4xl md:text-5xl font-bold text-green-400 mb-2">₩156M+</div>
                <div class="text-gray-400">총 거래액</div>
            </div>
        </div>
    </div>
</section>

<!-- User Types -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-400 text-transparent bg-clip-text">
                누구를 위한 플랫폼인가요?
            </h2>
            <p class="text-gray-400 text-lg">다양한 참여자가 함께 만드는 AI코딩 생태계</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Developer Card -->
            <div class="group relative bg-gradient-to-br from-blue-900/50 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-blue-500/30 transition-all duration-300 border border-gray-700 hover:border-blue-500" data-aos="fade-up" data-aos-delay="0">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-purple-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-6xl mb-6">👨‍💻</div>
                    <h3 class="text-2xl font-bold text-white mb-4">개발자</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        AI코딩 역량을 등록하고 다양한 프로젝트에 참여하여 수익을 창출하세요.
                    </p>
                    <ul class="space-y-3 text-gray-400 mb-6">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>AI 기반 최적 프로젝트 추천</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>포트폴리오 자동 생성</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>안전한 에스크로 결제</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-400 mt-1 mr-3"></i>
                            <span>교육 및 멘토링 지원</span>
                        </li>
                    </ul>
                    <a href="?page=register" class="inline-block w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/50 transition-all">
                        개발자로 등록하기 <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <!-- Company Card -->
            <div class="group relative bg-gradient-to-br from-purple-900/50 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-purple-500/30 transition-all duration-300 border border-gray-700 hover:border-purple-500" data-aos="fade-up" data-aos-delay="100">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-600/10 to-pink-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-6xl mb-6">🏢</div>
                    <h3 class="text-2xl font-bold text-white mb-4">기업</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        프로젝트를 등록하고 검증된 AI코더를 빠르게 매칭받으세요.
                    </p>
                    <ul class="space-y-3 text-gray-400 mb-6">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>AI 기반 개발자 매칭</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>프로젝트 진행 관리</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>품질 보증 시스템</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-purple-400 mt-1 mr-3"></i>
                            <span>계약 및 정산 자동화</span>
                        </li>
                    </ul>
                    <a href="?page=register" class="inline-block w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-center rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all">
                        기업으로 등록하기 <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <!-- Educational Institution Card -->
            <div class="group relative bg-gradient-to-br from-pink-900/50 to-gray-900 rounded-2xl p-8 hover:shadow-2xl hover:shadow-pink-500/30 transition-all duration-300 border border-gray-700 hover:border-pink-500" data-aos="fade-up" data-aos-delay="200">
                <div class="absolute inset-0 bg-gradient-to-r from-pink-600/10 to-purple-600/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="text-6xl mb-6">🎓</div>
                    <h3 class="text-2xl font-bold text-white mb-4">교육기관</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        수료생을 플랫폼에 연결하고 실전 프로젝트 기회를 제공하세요.
                    </p>
                    <ul class="space-y-3 text-gray-400 mb-6">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>수료생 취업 연계</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>실전 프로젝트 제공</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>교육 성과 추적</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-pink-400 mt-1 mr-3"></i>
                            <span>파트너십 혜택</span>
                        </li>
                    </ul>
                    <a href="?page=register" class="inline-block w-full px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 text-white text-center rounded-full font-semibold hover:shadow-lg hover:shadow-pink-500/50 transition-all">
                        파트너 등록하기 <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-20 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                플랫폼 작동 방식
            </h2>
            <p class="text-gray-400 text-lg">AI 기반 스마트 매칭으로 5분 만에 시작하세요</p>
        </div>

        <div class="max-w-5xl mx-auto">
            <!-- Step 1 -->
            <div class="relative mb-12" data-aos="fade-right">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-4xl font-bold shadow-lg shadow-blue-500/50">
                            1
                        </div>
                    </div>
                    <div class="flex-1 bg-gradient-to-br from-blue-900/30 to-gray-900 rounded-2xl p-8 border border-blue-500/30">
                        <h3 class="text-2xl font-bold text-white mb-3">회원가입 및 프로필 작성</h3>
                        <p class="text-gray-300 leading-relaxed">
                            간단한 회원가입 후 개발자, 기업, 교육기관 중 유형을 선택하고 
                            상세 프로필을 작성합니다. AI가 자동으로 역량을 분석합니다.
                        </p>
                    </div>
                </div>
                <div class="hidden md:block absolute left-12 top-24 w-0.5 h-12 bg-gradient-to-b from-blue-500 to-purple-500"></div>
            </div>

            <!-- Step 2 -->
            <div class="relative mb-12" data-aos="fade-left">
                <div class="flex flex-col md:flex-row-reverse items-center gap-8">
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-4xl font-bold shadow-lg shadow-purple-500/50">
                            2
                        </div>
                    </div>
                    <div class="flex-1 bg-gradient-to-br from-purple-900/30 to-gray-900 rounded-2xl p-8 border border-purple-500/30">
                        <h3 class="text-2xl font-bold text-white mb-3">AI 기반 스마트 매칭</h3>
                        <p class="text-gray-300 leading-relaxed">
                            프로젝트 요구사항과 개발자 역량을 AI가 분석하여 
                            최적의 매칭을 제안합니다. 양쪽 모두 승인 시 매칭이 확정됩니다.
                        </p>
                    </div>
                </div>
                <div class="hidden md:block absolute right-12 top-24 w-0.5 h-12 bg-gradient-to-b from-purple-500 to-pink-500"></div>
            </div>

            <!-- Step 3 -->
            <div class="relative mb-12" data-aos="fade-right">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-pink-500 to-red-500 flex items-center justify-center text-white text-4xl font-bold shadow-lg shadow-pink-500/50">
                            3
                        </div>
                    </div>
                    <div class="flex-1 bg-gradient-to-br from-pink-900/30 to-gray-900 rounded-2xl p-8 border border-pink-500/30">
                        <h3 class="text-2xl font-bold text-white mb-3">계약 및 프로젝트 시작</h3>
                        <p class="text-gray-300 leading-relaxed">
                            플랫폼에서 제공하는 표준 계약서를 기반으로 계약을 체결하고, 
                            에스크로 시스템을 통해 안전하게 대금을 예치합니다.
                        </p>
                    </div>
                </div>
                <div class="hidden md:block absolute left-12 top-24 w-0.5 h-12 bg-gradient-to-b from-pink-500 to-green-500"></div>
            </div>

            <!-- Step 4 -->
            <div class="relative" data-aos="fade-left">
                <div class="flex flex-col md:flex-row-reverse items-center gap-8">
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-green-500 to-blue-500 flex items-center justify-center text-white text-4xl font-bold shadow-lg shadow-green-500/50">
                            4
                        </div>
                    </div>
                    <div class="flex-1 bg-gradient-to-br from-green-900/30 to-gray-900 rounded-2xl p-8 border border-green-500/30">
                        <h3 class="text-2xl font-bold text-white mb-3">프로젝트 완료 및 정산</h3>
                        <p class="text-gray-300 leading-relaxed">
                            프로젝트를 완료하고 검수가 완료되면 자동으로 정산이 진행됩니다. 
                            양측의 평가와 피드백으로 신뢰도가 쌓입니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Platform Features -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-green-400 text-transparent bg-clip-text">
                플랫폼 핵심 기능
            </h2>
            <p class="text-gray-400 text-lg">성공적인 프로젝트를 위한 모든 것</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-blue-500 transition-all hover:shadow-lg hover:shadow-blue-500/20" data-aos="fade-up" data-aos-delay="0">
                <div class="text-4xl mb-4">🤖</div>
                <h3 class="text-xl font-bold text-white mb-2">AI 역량 분석</h3>
                <p class="text-gray-400">개발자의 기술 스택과 프로젝트 경험을 AI가 자동 분석</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-purple-500 transition-all hover:shadow-lg hover:shadow-purple-500/20" data-aos="fade-up" data-aos-delay="100">
                <div class="text-4xl mb-4">🎯</div>
                <h3 class="text-xl font-bold text-white mb-2">스마트 매칭</h3>
                <p class="text-gray-400">프로젝트와 개발자를 AI가 최적으로 매칭</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-pink-500 transition-all hover:shadow-lg hover:shadow-pink-500/20" data-aos="fade-up" data-aos-delay="200">
                <div class="text-4xl mb-4">💰</div>
                <h3 class="text-xl font-bold text-white mb-2">에스크로 결제</h3>
                <p class="text-gray-400">안전한 대금 예치 및 자동 정산 시스템</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-green-500 transition-all hover:shadow-lg hover:shadow-green-500/20" data-aos="fade-up" data-aos-delay="300">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-xl font-bold text-white mb-2">프로젝트 관리</h3>
                <p class="text-gray-400">일정, 마일스톤, 산출물 관리 도구 제공</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-yellow-500 transition-all hover:shadow-lg hover:shadow-yellow-500/20" data-aos="fade-up" data-aos-delay="400">
                <div class="text-4xl mb-4">⭐</div>
                <h3 class="text-xl font-bold text-white mb-2">평가 시스템</h3>
                <p class="text-gray-400">양방향 평가로 신뢰도 관리 및 포트폴리오 구축</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-red-500 transition-all hover:shadow-lg hover:shadow-red-500/20" data-aos="fade-up" data-aos-delay="500">
                <div class="text-4xl mb-4">🛡️</div>
                <h3 class="text-xl font-bold text-white mb-2">분쟁 조정</h3>
                <p class="text-gray-400">협회의 전문가가 공정하게 분쟁을 중재</p>
            </div>
        </div>
    </div>
</section>

<!-- Success Stories -->
<section class="py-20 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-pink-400 to-purple-400 text-transparent bg-clip-text">
                성공 사례
            </h2>
            <p class="text-gray-400 text-lg">플랫폼을 통해 성장한 회원들의 이야기</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Success Story 1 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-up" data-aos-delay="0">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-2xl font-bold mr-4">
                        김
                    </div>
                    <div>
                        <h4 class="text-white font-bold">김민수 개발자</h4>
                        <p class="text-gray-400 text-sm">AI 백엔드 전문</p>
                    </div>
                </div>
                <p class="text-gray-300 leading-relaxed mb-4">
                    "6개월 만에 12개 프로젝트를 완료하고 월 500만원 이상의 수익을 창출했습니다. 
                    AI 매칭 시스템 덕분에 제 역량에 맞는 프로젝트만 받을 수 있었어요."
                </p>
                <div class="flex items-center text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <span class="ml-2 text-gray-400">5.0 평점</span>
                </div>
            </div>

            <!-- Success Story 2 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-2xl font-bold mr-4">
                        A
                    </div>
                    <div>
                        <h4 class="text-white font-bold">A 스타트업</h4>
                        <p class="text-gray-400 text-sm">핀테크 기업</p>
                    </div>
                </div>
                <p class="text-gray-300 leading-relaxed mb-4">
                    "MVP를 3주 만에 완성했습니다. 검증된 개발자를 빠르게 매칭받아 
                    개발 비용을 40% 절감하면서도 높은 품질을 유지할 수 있었습니다."
                </p>
                <div class="flex items-center text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <span class="ml-2 text-gray-400">5.0 평점</span>
                </div>
            </div>

            <!-- Success Story 3 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-pink-500 to-red-500 flex items-center justify-center text-white text-2xl font-bold mr-4">
                        이
                    </div>
                    <div>
                        <h4 class="text-white font-bold">이지훈 교육생</h4>
                        <p class="text-gray-400 text-sm">AI 부트캠프 수료</p>
                    </div>
                </div>
                <p class="text-gray-300 leading-relaxed mb-4">
                    "교육 수료 직후 플랫폼을 통해 첫 프로젝트를 받았고, 
                    3개월 만에 주니어 개발자로 취업에 성공했습니다. 실전 경험이 큰 도움이 됐어요."
                </p>
                <div class="flex items-center text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <span class="ml-2 text-gray-400">5.0 평점</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-blue-900/30 via-gray-900 to-purple-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-bold mb-6 text-white">
                지금 바로 시작하세요!
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                AI코딩 허브플랫폼에서 새로운 기회를 만나보세요
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="?page=register" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-user-plus mr-2"></i>
                    무료 회원가입
                </a>
                <a href="/festival/" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    페스티벌 참여
                </a>
                <a href="?page=contact" class="px-8 py-4 border-2 border-gray-600 text-white rounded-full font-semibold hover:bg-gray-800 hover:border-purple-500 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-headset mr-2"></i>
                    상담 문의
                </a>
            </div>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
