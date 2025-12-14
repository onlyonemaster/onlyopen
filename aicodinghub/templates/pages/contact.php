<?php 
$page_title = "문의하기 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-[50vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-pink-900/20 to-gray-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/3 left-1/4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/3 right-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-pink-400 via-purple-400 to-blue-400 text-transparent bg-clip-text">
                문의하기
            </h1>
            <p class="text-xl text-gray-300 mb-4">
                궁금하신 점이 있으시면 언제든 문의해주세요
            </p>
            <p class="text-lg text-gray-400">
                전문 상담팀이 빠르게 답변해드립니다
            </p>
        </div>
    </div>
</section>

<!-- Contact Info & Form -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
            <!-- Left: Contact Info -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Contact Card 1 -->
                <div class="bg-gradient-to-br from-pink-900/50 to-gray-900 rounded-2xl p-8 border border-pink-500/30 hover:border-pink-500 transition-all" data-aos="fade-right">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white text-2xl">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">이메일</h3>
                            <p class="text-gray-400 text-sm mb-2">평일 09:00 - 18:00</p>
                            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="text-pink-400 hover:text-pink-300 transition-colors">
                                <?php echo SITE_EMAIL; ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Card 2 -->
                <div class="bg-gradient-to-br from-purple-900/50 to-gray-900 rounded-2xl p-8 border border-purple-500/30 hover:border-purple-500 transition-all" data-aos="fade-right" data-aos-delay="100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center text-white text-2xl">
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">전화 문의</h3>
                            <p class="text-gray-400 text-sm mb-2">평일 09:00 - 18:00</p>
                            <a href="tel:1588-0000" class="text-purple-400 hover:text-purple-300 transition-colors text-xl font-bold">
                                1588-0000
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Card 3 -->
                <div class="bg-gradient-to-br from-blue-900/50 to-gray-900 rounded-2xl p-8 border border-blue-500/30 hover:border-blue-500 transition-all" data-aos="fade-right" data-aos-delay="200">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center text-white text-2xl">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white mb-2">오시는 길</h3>
                            <p class="text-gray-400 text-sm mb-2">평일 09:00 - 18:00</p>
                            <p class="text-blue-400">
                                서울시 강남구 테헤란로<br/>
                                삼성역 5번 출구
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SNS Links -->
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-right" data-aos-delay="300">
                    <h3 class="text-xl font-bold text-white mb-4">SNS 채널</h3>
                    <div class="flex gap-4">
                        <a href="#" class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white hover:scale-110 transition-transform">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white hover:scale-110 transition-transform">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-red-500 flex items-center justify-center text-white hover:scale-110 transition-transform">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center text-white hover:scale-110 transition-transform">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right: Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 md:p-12 border border-gray-700" data-aos="fade-left">
                    <h2 class="text-3xl font-bold text-white mb-2">온라인 문의</h2>
                    <p class="text-gray-400 mb-8">빠른 답변을 위해 상세히 작성해주세요</p>

                    <form method="post" action="/api/contact.php" class="space-y-6" id="contactForm">
                        <!-- Name -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                이름 <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                                placeholder="홍길동"
                            >
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                이메일 <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                required 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                                placeholder="email@example.com"
                            >
                        </div>

                        <!-- Phone (Optional) -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                연락처 <span class="text-gray-500 text-sm">(선택)</span>
                            </label>
                            <input 
                                type="tel" 
                                name="phone" 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                                placeholder="010-0000-0000"
                            >
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                문의 유형 <span class="text-red-400">*</span>
                            </label>
                            <select 
                                name="category" 
                                required 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                            >
                                <option value="">선택해주세요</option>
                                <option value="membership">회원가입/로그인</option>
                                <option value="platform">플랫폼 이용</option>
                                <option value="project">프로젝트 매칭</option>
                                <option value="festival">페스티벌 참여</option>
                                <option value="education">교육 문의</option>
                                <option value="partnership">제휴/파트너십</option>
                                <option value="other">기타 문의</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                문의 내용 <span class="text-red-400">*</span>
                            </label>
                            <textarea 
                                name="message" 
                                required 
                                rows="6" 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all resize-none"
                                placeholder="문의하실 내용을 상세히 작성해주세요..."
                            ></textarea>
                        </div>

                        <!-- Privacy Agreement -->
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                id="privacy" 
                                name="privacy" 
                                required 
                                class="mt-1 w-5 h-5 text-purple-600 bg-gray-900 border-gray-700 rounded focus:ring-purple-500"
                            >
                            <label for="privacy" class="text-gray-400 text-sm">
                                개인정보 수집 및 이용에 동의합니다. 
                                <a href="#" class="text-purple-400 hover:text-purple-300">자세히 보기</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-bold text-lg hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all"
                        >
                            <i class="fas fa-paper-plane mr-2"></i>
                            문의하기
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                자주 묻는 질문
            </h2>
            <p class="text-gray-400 text-lg">빠른 답변이 필요하신가요? FAQ를 확인해보세요</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl border border-gray-700 overflow-hidden" data-aos="fade-up" data-aos-delay="0">
                <button class="w-full px-8 py-6 flex items-center justify-between text-left hover:bg-gray-800/50 transition-all">
                    <span class="text-lg font-semibold text-white">
                        <i class="fas fa-question-circle text-purple-400 mr-3"></i>
                        회원가입은 어떻게 하나요?
                    </span>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>
                <div class="px-8 py-6 bg-gray-900/50 border-t border-gray-700 hidden">
                    <p class="text-gray-300 leading-relaxed">
                        상단의 "회원가입" 버튼을 클릭하고, 개발자/기업/교육기관 중 유형을 선택하신 후 
                        필요한 정보를 입력하시면 됩니다. 이메일 인증 후 바로 이용 가능합니다.
                    </p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl border border-gray-700 overflow-hidden" data-aos="fade-up" data-aos-delay="100">
                <button class="w-full px-8 py-6 flex items-center justify-between text-left hover:bg-gray-800/50 transition-all">
                    <span class="text-lg font-semibold text-white">
                        <i class="fas fa-question-circle text-purple-400 mr-3"></i>
                        플랫폼 이용료는 얼마인가요?
                    </span>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>
                <div class="px-8 py-6 bg-gray-900/50 border-t border-gray-700 hidden">
                    <p class="text-gray-300 leading-relaxed">
                        기본 회원가입과 프로필 등록은 무료입니다. 프로젝트 매칭 성사 시 
                        거래 금액의 5~10% 수수료가 발생하며, 자세한 요금표는 회원가입 후 확인 가능합니다.
                    </p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl border border-gray-700 overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <button class="w-full px-8 py-6 flex items-center justify-between text-left hover:bg-gray-800/50 transition-all">
                    <span class="text-lg font-semibold text-white">
                        <i class="fas fa-question-circle text-purple-400 mr-3"></i>
                        페스티벌은 언제 열리나요?
                    </span>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>
                <div class="px-8 py-6 bg-gray-900/50 border-t border-gray-700 hidden">
                    <p class="text-gray-300 leading-relaxed">
                        AI코딩 페스티벌은 매주 주말(토/일)에 온라인과 오프라인으로 동시 진행됩니다. 
                        자세한 일정과 장소는 <a href="/festival/" class="text-purple-400 hover:text-purple-300">페스티벌 페이지</a>에서 확인하실 수 있습니다.
                    </p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl border border-gray-700 overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                <button class="w-full px-8 py-6 flex items-center justify-between text-left hover:bg-gray-800/50 transition-all">
                    <span class="text-lg font-semibold text-white">
                        <i class="fas fa-question-circle text-purple-400 mr-3"></i>
                        결제는 안전한가요?
                    </span>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>
                <div class="px-8 py-6 bg-gray-900/50 border-t border-gray-700 hidden">
                    <p class="text-gray-300 leading-relaxed">
                        네, 협회의 에스크로 시스템을 통해 안전하게 거래됩니다. 프로젝트 완료 및 검수 확인 후 
                        자동으로 정산이 진행되므로 안심하고 이용하실 수 있습니다.
                    </p>
                </div>
            </div>
        </div>

        <!-- More FAQs Link -->
        <div class="text-center mt-12" data-aos="fade-up">
            <a href="?page=board&category=qna" class="inline-flex items-center px-6 py-3 border-2 border-gray-700 text-white rounded-full hover:bg-gray-800 hover:border-purple-500 transition-all">
                더 많은 FAQ 보기
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Quick Contact CTA -->
<section class="py-20 bg-gradient-to-br from-purple-900/30 via-gray-900 to-pink-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="fade-up">
            <div class="text-6xl mb-6">💬</div>
            <h2 class="text-4xl font-bold mb-6 text-white">
                더 궁금하신 점이 있으신가요?
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                전문 상담팀이 친절하게 답변해드립니다
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="tel:1588-0000" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-phone mr-2"></i>
                    전화 상담
                </a>
                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="px-8 py-4 border-2 border-gray-600 text-white rounded-full font-semibold hover:bg-gray-800 hover:border-purple-500 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-envelope mr-2"></i>
                    이메일 문의
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ Toggle
document.querySelectorAll('.bg-gradient-to-br.from-gray-800 button').forEach(button => {
    button.addEventListener('click', function() {
        const content = this.nextElementSibling;
        const icon = this.querySelector('.fa-chevron-down');
        
        // Toggle content
        content.classList.toggle('hidden');
        
        // Toggle icon
        icon.classList.toggle('rotate-180');
    });
});

// Form Validation
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>전송 중...';
    
    // Simulate form submission
    setTimeout(() => {
        alert('문의가 성공적으로 전송되었습니다!\n빠른 시일 내에 답변드리겠습니다.');
        this.reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }, 2000);
});
</script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
