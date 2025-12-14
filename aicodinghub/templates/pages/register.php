<?php 
$page_title = "회원가입 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-blue-900/20 to-gray-900 py-20">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto">
            <!-- Register Card -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 md:p-12 border border-gray-700 shadow-2xl" data-aos="fade-up">
                <!-- Logo/Title -->
                <div class="text-center mb-8">
                    <div class="inline-block p-4 bg-gradient-to-br from-blue-500 to-purple-500 rounded-2xl mb-4">
                        <i class="fas fa-user-plus text-4xl text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold mb-2 bg-gradient-to-r from-blue-400 to-purple-400 text-transparent bg-clip-text">
                        회원가입
                    </h1>
                    <p class="text-gray-400">AI코딩 허브플랫폼의 회원이 되어보세요</p>
                </div>

                <!-- Step Indicator -->
                <div class="flex justify-center mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                1
                            </div>
                            <span class="ml-2 text-white font-semibold">기본정보</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-700"></div>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 font-bold">
                                2
                            </div>
                            <span class="ml-2 text-gray-500 font-semibold">상세정보</span>
                        </div>
                    </div>
                </div>

                <!-- Register Form -->
                <form method="post" action="/api/register.php" class="space-y-6" id="registerForm">
                    <!-- Member Type -->
                    <div>
                        <label class="block text-white font-semibold mb-3">
                            <i class="fas fa-users mr-2 text-blue-400"></i>
                            회원 유형 <span class="text-red-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="member_type" value="individual" required class="peer sr-only">
                                <div class="p-4 bg-gray-700 border-2 border-gray-600 rounded-lg text-center peer-checked:border-blue-500 peer-checked:bg-blue-500/20 transition-all hover:border-gray-500">
                                    <div class="text-3xl mb-2">👨‍💻</div>
                                    <p class="text-white font-semibold text-sm">개인 개발자</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="member_type" value="company" required class="peer sr-only">
                                <div class="p-4 bg-gray-700 border-2 border-gray-600 rounded-lg text-center peer-checked:border-purple-500 peer-checked:bg-purple-500/20 transition-all hover:border-gray-500">
                                    <div class="text-3xl mb-2">🏢</div>
                                    <p class="text-white font-semibold text-sm">기업</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="member_type" value="education" required class="peer sr-only">
                                <div class="p-4 bg-gray-700 border-2 border-gray-600 rounded-lg text-center peer-checked:border-pink-500 peer-checked:bg-pink-500/20 transition-all hover:border-gray-500">
                                    <div class="text-3xl mb-2">🎓</div>
                                    <p class="text-white font-semibold text-sm">교육기관</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="member_type" value="team" required class="peer sr-only">
                                <div class="p-4 bg-gray-700 border-2 border-gray-600 rounded-lg text-center peer-checked:border-green-500 peer-checked:bg-green-500/20 transition-all hover:border-gray-500">
                                    <div class="text-3xl mb-2">👥</div>
                                    <p class="text-white font-semibold text-sm">팀</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                <i class="fas fa-user mr-2 text-blue-400"></i>
                                이름 <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                placeholder="홍길동"
                            >
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                <i class="fas fa-phone mr-2 text-blue-400"></i>
                                연락처 <span class="text-gray-500 text-sm">(선택)</span>
                            </label>
                            <input 
                                type="tel" 
                                name="phone" 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                placeholder="010-0000-0000"
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-white font-semibold mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-400"></i>
                            이메일 <span class="text-red-400">*</span>
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            required 
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                            placeholder="email@example.com"
                        >
                        <p class="mt-2 text-sm text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            이메일 인증이 필요합니다
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Password -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                <i class="fas fa-lock mr-2 text-blue-400"></i>
                                비밀번호 <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    required 
                                    minlength="8"
                                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    placeholder="8자 이상"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('password', 'toggleIcon1')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-400 transition-colors"
                                >
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-white font-semibold mb-2">
                                <i class="fas fa-lock mr-2 text-blue-400"></i>
                                비밀번호 확인 <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="password_confirm" 
                                    id="password_confirm"
                                    required 
                                    minlength="8"
                                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    placeholder="비밀번호 재입력"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('password_confirm', 'toggleIcon2')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-400 transition-colors"
                                >
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="space-y-3 bg-gray-900/50 p-4 rounded-lg border border-gray-700">
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                id="agree_all" 
                                class="mt-1 w-5 h-5 text-blue-600 bg-gray-900 border-gray-700 rounded focus:ring-blue-500"
                            >
                            <label for="agree_all" class="text-white font-semibold">
                                전체 동의
                            </label>
                        </div>
                        <hr class="border-gray-700">
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                id="agree_terms" 
                                name="agree_terms" 
                                required 
                                class="mt-1 w-5 h-5 text-blue-600 bg-gray-900 border-gray-700 rounded focus:ring-blue-500"
                            >
                            <label for="agree_terms" class="text-gray-300 text-sm">
                                <span class="text-red-400">*</span> 
                                이용약관에 동의합니다 
                                <a href="#" class="text-blue-400 hover:text-blue-300">보기</a>
                            </label>
                        </div>
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                id="agree_privacy" 
                                name="agree_privacy" 
                                required 
                                class="mt-1 w-5 h-5 text-blue-600 bg-gray-900 border-gray-700 rounded focus:ring-blue-500"
                            >
                            <label for="agree_privacy" class="text-gray-300 text-sm">
                                <span class="text-red-400">*</span> 
                                개인정보 처리방침에 동의합니다 
                                <a href="#" class="text-blue-400 hover:text-blue-300">보기</a>
                            </label>
                        </div>
                        <div class="flex items-start gap-3">
                            <input 
                                type="checkbox" 
                                id="agree_marketing" 
                                name="agree_marketing" 
                                class="mt-1 w-5 h-5 text-blue-600 bg-gray-900 border-gray-700 rounded focus:ring-blue-500"
                            >
                            <label for="agree_marketing" class="text-gray-300 text-sm">
                                마케팅 정보 수신에 동의합니다 (선택)
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-lg hover:shadow-lg hover:shadow-blue-500/50 transform hover:-translate-y-1 transition-all"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        가입하기
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-8 text-center">
                    <p class="text-gray-400">
                        이미 계정이 있으신가요? 
                        <a href="?page=login" class="text-blue-400 hover:text-blue-300 font-semibold transition-colors">
                            로그인
                        </a>
                    </p>
                </div>
            </div>

            <!-- Benefits -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700 text-center">
                    <div class="text-3xl mb-3">🎯</div>
                    <h3 class="text-white font-semibold mb-2">AI 기반 매칭</h3>
                    <p class="text-gray-400 text-sm">최적의 프로젝트 자동 추천</p>
                </div>
                <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700 text-center">
                    <div class="text-3xl mb-3">💰</div>
                    <h3 class="text-white font-semibold mb-2">수익 창출</h3>
                    <p class="text-gray-400 text-sm">실전 프로젝트로 수익 획득</p>
                </div>
                <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700 text-center">
                    <div class="text-3xl mb-3">🛡️</div>
                    <h3 class="text-white font-semibold mb-2">안전 거래</h3>
                    <p class="text-gray-400 text-sm">에스크로 시스템 보장</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Toggle Password Visibility
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Agree All Checkbox
document.getElementById('agree_all')?.addEventListener('change', function() {
    const checkboxes = ['agree_terms', 'agree_privacy', 'agree_marketing'];
    checkboxes.forEach(id => {
        document.getElementById(id).checked = this.checked;
    });
});

// Form Validation
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Password match validation
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    if (password !== passwordConfirm) {
        alert('비밀번호가 일치하지 않습니다.');
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>처리 중...';
    
    try {
        // Get form data
        const formData = new FormData(this);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            member_type: formData.get('member_type'),
            phone: formData.get('phone'),
            agree_terms: formData.get('agree_terms') === 'on',
            agree_privacy: formData.get('agree_privacy') === 'on',
            agree_marketing: formData.get('agree_marketing') === 'on'
        };
        
        // Call API
        const response = await fetch('/api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            alert('회원가입 성공! 이메일 인증 후 로그인해주세요.\n(개발 환경에서는 자동으로 활성화됩니다)');
            
            // Redirect to login page
            window.location.href = '/?page=login';
        } else {
            // Show error message
            const errorMsg = result.message || '회원가입에 실패했습니다.';
            const errors = result.errors ? '\n\n' + Object.values(result.errors).join('\n') : '';
            alert(errorMsg + errors);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Register error:', error);
        alert('회원가입 중 오류가 발생했습니다. 다시 시도해주세요.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
