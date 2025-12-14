<?php 
$page_title = "로그인 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 
?>

<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-purple-900/20 to-gray-900 py-20">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-md mx-auto">
            <!-- Login Card -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 md:p-12 border border-gray-700 shadow-2xl" data-aos="fade-up">
                <!-- Logo/Title -->
                <div class="text-center mb-8">
                    <div class="inline-block p-4 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl mb-4">
                        <i class="fas fa-sign-in-alt text-4xl text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold mb-2 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                        로그인
                    </h1>
                    <p class="text-gray-400">AI코딩 허브플랫폼에 오신 것을 환영합니다</p>
                </div>

                <!-- Login Form -->
                <form method="post" action="/api/login.php" class="space-y-6" id="loginForm">
                    <!-- Email -->
                    <div>
                        <label class="block text-white font-semibold mb-2">
                            <i class="fas fa-envelope mr-2 text-purple-400"></i>
                            이메일
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            required 
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                            placeholder="email@example.com"
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-white font-semibold mb-2">
                            <i class="fas fa-lock mr-2 text-purple-400"></i>
                            비밀번호
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                required 
                                class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all"
                                placeholder="••••••••"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-purple-400 transition-colors"
                            >
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="w-4 h-4 text-purple-600 bg-gray-900 border-gray-700 rounded focus:ring-purple-500"
                            >
                            <span class="ml-2 text-gray-400 text-sm">로그인 상태 유지</span>
                        </label>
                        <a href="?page=forgot-password" class="text-purple-400 hover:text-purple-300 text-sm transition-colors">
                            비밀번호 찾기
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-bold text-lg hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        로그인
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-gray-800 text-gray-400">또는</span>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="space-y-3">
                    <button class="w-full px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-semibold transition-all flex items-center justify-center">
                        <i class="fab fa-google mr-3 text-xl"></i>
                        Google로 로그인
                    </button>
                    <button class="w-full px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-semibold transition-all flex items-center justify-center">
                        <i class="fab fa-github mr-3 text-xl"></i>
                        GitHub로 로그인
                    </button>
                </div>

                <!-- Sign Up Link -->
                <div class="mt-8 text-center">
                    <p class="text-gray-400">
                        계정이 없으신가요? 
                        <a href="?page=register" class="text-purple-400 hover:text-purple-300 font-semibold transition-colors">
                            회원가입
                        </a>
                    </p>
                </div>
            </div>

            <!-- Benefits -->
            <div class="mt-8 grid grid-cols-3 gap-4" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-gray-800/50 rounded-lg p-4 text-center border border-gray-700">
                    <div class="text-2xl mb-2">🚀</div>
                    <p class="text-gray-400 text-sm">빠른 매칭</p>
                </div>
                <div class="bg-gray-800/50 rounded-lg p-4 text-center border border-gray-700">
                    <div class="text-2xl mb-2">💰</div>
                    <p class="text-gray-400 text-sm">수익 창출</p>
                </div>
                <div class="bg-gray-800/50 rounded-lg p-4 text-center border border-gray-700">
                    <div class="text-2xl mb-2">🤝</div>
                    <p class="text-gray-400 text-sm">안전 거래</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Toggle Password Visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
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

// Form Submission
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>로그인 중...';
    
    try {
        // Get form data
        const formData = new FormData(this);
        const data = {
            email: formData.get('email'),
            password: formData.get('password'),
            remember: formData.get('remember') === 'on'
        };
        
        // Call API
        const response = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            alert('로그인 성공! 메인 페이지로 이동합니다.');
            
            // Redirect to homepage
            window.location.href = '/';
        } else {
            // Show error message
            alert(result.message || '로그인에 실패했습니다.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('로그인 중 오류가 발생했습니다. 다시 시도해주세요.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
