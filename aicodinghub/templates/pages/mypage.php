<?php 
// Check login BEFORE including header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

$page_title = "마이페이지 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php';

// Get real user data from database
require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /?page=login');
    exit;
}

// Get profile data based on member type
$profileData = null;
switch ($user['member_type']) {
    case 'individual':
        $stmt = $pdo->prepare("SELECT * FROM developer_profiles WHERE member_id = ?");
        $stmt->execute([$user['member_id']]);
        $profileData = $stmt->fetch();
        break;
    case 'company':
        $stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE member_id = ?");
        $stmt->execute([$user['member_id']]);
        $profileData = $stmt->fetch();
        break;
    case 'education':
        $stmt = $pdo->prepare("SELECT * FROM education_profiles WHERE member_id = ?");
        $stmt->execute([$user['member_id']]);
        $profileData = $stmt->fetch();
        break;
    case 'team':
        $stmt = $pdo->prepare("SELECT * FROM teams WHERE leader_member_id = ?");
        $stmt->execute([$user['member_id']]);
        $profileData = $stmt->fetch();
        break;
}
?>

<!-- Hero Section -->
<section class="relative min-h-[40vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-purple-900/20 to-gray-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/3 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/3 right-1/4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                마이페이지
            </h1>
            <p class="text-xl text-gray-300">
                내 활동과 프로필을 관리하세요
            </p>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Profile Card -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 border border-gray-700" data-aos="fade-right">
                        <div class="text-center mb-6">
                            <?php if (isset($user['profile_image']) && $user['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-purple-500">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full mx-auto mb-4 bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-3xl font-bold">
                                    <?php echo mb_substr($user['name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            <h2 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-gray-400 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                            
                            <!-- Rating -->
                            <div class="flex items-center justify-center gap-2 mb-4">
                                <div class="flex text-yellow-400">
                                    <?php 
                                    $rating = isset($user['rating']) ? $user['rating'] : 0;
                                    for ($i = 0; $i < 5; $i++): 
                                    ?>
                                        <i class="fas fa-star<?php echo $i < floor($rating) ? '' : ($i < $rating ? '-half-alt' : ' text-gray-600'); ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-white font-bold"><?php echo number_format($rating, 1); ?></span>
                            </div>

                            <!-- Badge -->
                            <span class="inline-block px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full text-sm font-semibold">
                                <?php 
                                $types = [
                                    'individual' => '개인 개발자',
                                    'company' => '기업',
                                    'education' => '교육기관',
                                    'team' => '팀'
                                ];
                                echo $types[$user['member_type']] ?? $user['member_type']; 
                                ?>
                            </span>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-900/50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-purple-400"><?php echo isset($user['completed_projects']) ? $user['completed_projects'] : 0; ?></div>
                                <div class="text-gray-400 text-sm">완료 프로젝트</div>
                            </div>
                            <div class="bg-gray-900/50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-green-400">₩<?php echo isset($user['total_earnings']) ? number_format($user['total_earnings'] / 1000000, 1) : '0.0'; ?>M</div>
                                <div class="text-gray-400 text-sm">총 수익</div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <a href="/?page=profile" class="block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all text-center">
                                <i class="fas fa-edit mr-2"></i>
                                프로필 수정
                            </a>
                            <a href="/?page=profile" class="block w-full px-4 py-3 bg-gray-700 text-white rounded-lg font-semibold hover:bg-gray-600 transition-all text-center">
                                <i class="fas fa-cog mr-2"></i>
                                설정
                            </a>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 border border-gray-700" data-aos="fade-right" data-aos-delay="100">
                        <nav class="space-y-2">
                            <a href="/?page=mypage" class="flex items-center px-4 py-3 bg-purple-600/20 border border-purple-500 text-purple-400 rounded-lg font-semibold transition-all">
                                <i class="fas fa-th-large mr-3 w-5"></i>
                                대시보드
                            </a>
                            <a href="/?page=projects" class="flex items-center px-4 py-3 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-project-diagram mr-3 w-5"></i>
                                내 프로젝트
                            </a>
                            <a href="/?page=applications" class="flex items-center px-4 py-3 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-file-alt mr-3 w-5"></i>
                                지원 현황
                            </a>
                            <a href="/?page=messages" class="flex items-center px-4 py-3 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-envelope mr-3 w-5"></i>
                                메시지
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">3</span>
                            </a>
                            <a href="/?page=portfolio" class="flex items-center px-4 py-3 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-briefcase mr-3 w-5"></i>
                                포트폴리오
                            </a>
                            <a href="/?page=payments" class="flex items-center px-4 py-3 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-all">
                                <i class="fas fa-wallet mr-3 w-5"></i>
                                결제·정산
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Overview Section -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-left">
                        <h2 class="text-2xl font-bold text-white mb-6">활동 현황</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="bg-gradient-to-br from-blue-900/30 to-gray-900 rounded-xl p-6 border border-blue-500/30">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-4xl">📊</div>
                                    <div class="text-blue-400 text-2xl">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold text-white mb-2">5</h3>
                                <p class="text-gray-400">진행 중 프로젝트</p>
                            </div>

                            <div class="bg-gradient-to-br from-purple-900/30 to-gray-900 rounded-xl p-6 border border-purple-500/30">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-4xl">✅</div>
                                    <div class="text-purple-400 text-2xl">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold text-white mb-2">12</h3>
                                <p class="text-gray-400">완료 프로젝트</p>
                            </div>

                            <div class="bg-gradient-to-br from-green-900/30 to-gray-900 rounded-xl p-6 border border-green-500/30">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-4xl">💰</div>
                                    <div class="text-green-400 text-2xl">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <h3 class="text-3xl font-bold text-white mb-2">₩5.2M</h3>
                                <p class="text-gray-400">이번 달 수익</p>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <h3 class="text-xl font-bold text-white mb-4">최근 활동</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold mb-1">AI 챗봇 개발 프로젝트 시작</h4>
                                    <p class="text-gray-400 text-sm">2시간 전</p>
                                </div>
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-sm">진행중</span>
                            </div>

                            <div class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-blue-500 flex items-center justify-center text-white">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold mb-1">웹사이트 리뉴얼 프로젝트 완료</h4>
                                    <p class="text-gray-400 text-sm">1일 전</p>
                                </div>
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm">완료</span>
                            </div>

                            <div class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg border border-gray-700">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold mb-1">₩2,500,000 정산 완료</h4>
                                    <p class="text-gray-400 text-sm">3일 전</p>
                                </div>
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-sm">정산</span>
                            </div>
                        </div>
                    </div>

                    <!-- Skills Section -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-left" data-aos-delay="100">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-white">보유 기술</h2>
                            <button class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-plus mr-2"></i>
                                추가
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <?php 
                            $skills = isset($user['skills']) ? (is_array($user['skills']) ? $user['skills'] : explode(',', $user['skills'])) : ['PHP', 'JavaScript', 'Python'];
                            foreach ($skills as $skill): 
                            ?>
                            <span class="px-4 py-2 bg-gradient-to-r from-purple-600/20 to-pink-600/20 border border-purple-500 text-purple-400 rounded-full font-semibold">
                                <?php echo htmlspecialchars(trim($skill)); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-lg font-bold text-white mb-4">기술 통계</h3>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-400">Python</span>
                                        <span class="text-purple-400 font-semibold">90%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" style="width: 90%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-400">JavaScript</span>
                                        <span class="text-blue-400 font-semibold">85%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: 85%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-400">React</span>
                                        <span class="text-green-400 font-semibold">80%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full" style="width: 80%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bio Section -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700" data-aos="fade-left" data-aos-delay="200">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-white">자기소개</h2>
                            <button class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all">
                                <i class="fas fa-edit mr-2"></i>
                                수정
                            </button>
                        </div>

                        <p class="text-gray-300 leading-relaxed mb-6">
                            <?php echo nl2br(htmlspecialchars(isset($user['bio']) ? $user['bio'] : 'AI 코딩 플랫폼에서 활동하고 있는 회원입니다.')); ?>
                        </p>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center text-gray-400">
                                <i class="fas fa-calendar mr-3 text-purple-400"></i>
                                가입일: <?php echo date('Y.m.d', strtotime($user['created_at'])); ?>
                            </div>
                            <div class="flex items-center text-gray-400">
                                <i class="fas fa-phone mr-3 text-purple-400"></i>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
