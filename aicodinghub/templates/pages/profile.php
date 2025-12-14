<?php 
// Check login BEFORE including header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

$page_title = "프로필 설정 - 한국AI코딩허브협회"; 
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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_basic') {
        // Update basic info
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if ($name && $phone) {
            $stmt = $pdo->prepare("UPDATE members SET name = ?, phone = ? WHERE member_id = ?");
            if ($stmt->execute([$name, $phone, $user['member_id']])) {
                $success_message = '기본 정보가 업데이트되었습니다.';
                $_SESSION['name'] = $name; // Update session
                $user['name'] = $name;
                $user['phone'] = $phone;
            } else {
                $error_message = '업데이트 중 오류가 발생했습니다.';
            }
        }
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password === $confirm_password) {
            // Verify current password
            if (password_verify($current_password, $user['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE members SET password_hash = ? WHERE member_id = ?");
                if ($stmt->execute([$new_hash, $user['member_id']])) {
                    $success_message = '비밀번호가 변경되었습니다.';
                } else {
                    $error_message = '비밀번호 변경 중 오류가 발생했습니다.';
                }
            } else {
                $error_message = '현재 비밀번호가 일치하지 않습니다.';
            }
        } else {
            $error_message = '새 비밀번호가 일치하지 않습니다.';
        }
    }
}
?>

<!-- Hero Section -->
<section class="relative min-h-[30vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-purple-900/20 to-gray-900">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/3 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/3 right-1/4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                프로필 설정
            </h1>
            <p class="text-xl text-gray-300">
                내 정보를 관리하고 보안 설정을 변경하세요
            </p>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            
            <?php if ($success_message): ?>
            <div class="mb-6 bg-green-500/20 border border-green-500 text-green-400 px-6 py-4 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="mb-6 bg-red-500/20 border border-red-500 text-red-400 px-6 py-4 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Profile Picture Section -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700 mb-8" data-aos="fade-up">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-user-circle text-purple-400 mr-3"></i>
                    프로필 사진
                </h2>
                
                <div class="flex items-center gap-6">
                    <div id="profile-image-container">
                        <?php if (isset($user['profile_image']) && $user['profile_image']): ?>
                            <img id="profile-image" src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="w-32 h-32 rounded-full border-4 border-purple-500 object-cover">
                        <?php else: ?>
                            <div id="profile-image" class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-5xl font-bold">
                                <?php echo mb_substr($user['name'], 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <p class="text-gray-300 mb-4">JPG, PNG 또는 GIF 형식, 최대 5MB</p>
                        <input type="file" id="avatar-upload" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                        <button id="upload-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg transition-all">
                            <i class="fas fa-upload mr-2"></i>사진 업로드
                        </button>
                        <div id="upload-status" class="mt-2 text-sm"></div>
                    </div>
                </div>
            </div>
            
            <!-- Basic Information -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700 mb-8" data-aos="fade-up" data-aos-delay="100">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-id-card text-purple-400 mr-3"></i>
                    기본 정보
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_basic">
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">이름</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none transition-all" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">이메일</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-gray-400 cursor-not-allowed" disabled>
                        <p class="text-sm text-gray-500 mt-2">이메일은 변경할 수 없습니다.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">전화번호</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">회원 유형</label>
                        <input type="text" value="<?php 
                            $types = ['individual' => '개인', 'company' => '기업', 'education' => '교육기관', 'team' => '팀'];
                            echo $types[$user['member_type']] ?? $user['member_type'];
                        ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-gray-400 cursor-not-allowed" disabled>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-8 py-3 rounded-lg font-bold transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-save mr-2"></i>저장하기
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Password Change -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-gray-700 mb-8" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-lock text-purple-400 mr-3"></i>
                    비밀번호 변경
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">현재 비밀번호</label>
                        <input type="password" name="current_password" 
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none transition-all" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">새 비밀번호</label>
                        <input type="password" name="new_password" 
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none transition-all" required>
                        <p class="text-sm text-gray-500 mt-2">최소 8자 이상, 영문, 숫자, 특수문자 포함</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">새 비밀번호 확인</label>
                        <input type="password" name="confirm_password" 
                               class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:outline-none transition-all" required>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white px-8 py-3 rounded-lg font-bold transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-key mr-2"></i>비밀번호 변경
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Actions -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 border border-red-500/30 mb-8" data-aos="fade-up" data-aos-delay="300">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                    계정 관리
                </h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="text-white font-bold mb-1">마이페이지로 이동</h3>
                            <p class="text-gray-400 text-sm">내 활동과 통계를 확인하세요</p>
                        </div>
                        <a href="/?page=mypage" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-all">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-gray-900 rounded-lg border border-red-500/30">
                        <div>
                            <h3 class="text-red-400 font-bold mb-1">계정 탈퇴</h3>
                            <p class="text-gray-400 text-sm">계정을 영구적으로 삭제합니다</p>
                        </div>
                        <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-all" onclick="alert('계정 탈퇴 기능은 준비 중입니다.')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<script>
// Profile image upload
document.getElementById('upload-btn').addEventListener('click', function() {
    document.getElementById('avatar-upload').click();
});

document.getElementById('avatar-upload').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('JPG, PNG, GIF 또는 WEBP 형식만 업로드 가능합니다.');
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('파일 크기는 5MB를 초과할 수 없습니다.');
        return;
    }
    
    const statusDiv = document.getElementById('upload-status');
    const uploadBtn = document.getElementById('upload-btn');
    
    // Show loading
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>업로드 중...';
    statusDiv.innerHTML = '<span class="text-yellow-400">업로드 중...</span>';
    
    try {
        const formData = new FormData();
        formData.append('avatar', file);
        
        const response = await fetch('/api/profile/avatar.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            statusDiv.innerHTML = '<span class="text-green-400"><i class="fas fa-check-circle mr-1"></i>업로드 성공!</span>';
            
            // Update profile image
            const imgContainer = document.getElementById('profile-image-container');
            imgContainer.innerHTML = `<img id="profile-image" src="${data.data.avatar_url}" alt="Profile" class="w-32 h-32 rounded-full border-4 border-purple-500 object-cover">`;
            
            // Reload page after 1 second
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '업로드 실패');
        }
    } catch (error) {
        console.error('Upload error:', error);
        statusDiv.innerHTML = '<span class="text-red-400"><i class="fas fa-times-circle mr-1"></i>' + error.message + '</span>';
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>사진 업로드';
    }
});
</script>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
