import re

with open('/home/webapp/ma_live.php', 'r', encoding='utf-8') as f:
    content = f.read()

# ── 1. 파일 최상단에 PHP 세션/슈퍼관리자 판별 코드 삽입 ──
php_header = '''<?php
// ============================================
// 세션 시작 & 슈퍼관리자 판별
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 여부 & 사용자 정보
$isLoggedIn = isset($_SESSION['mb_id']) && $_SESSION['mb_id'] !== '';
$mb_id      = $isLoggedIn ? $_SESSION['mb_id']               : '';
$mb_name    = $isLoggedIn ? ($_SESSION['mb_name'] ?? $mb_id) : '';

// 슈퍼관리자 판별 — 아이디 직접 지정 (가장 확실)
$superAdminIds = ['onlyonedev'];
$isSuper = false;
if ($isLoggedIn) {
    if (in_array($mb_id, $superAdminIds, true)) {
        $isSuper = true;
    }
    // 보조: mb_level >= 10 또는 is_super == 1
    $mb_level = isset($_SESSION['mb_level']) ? (int)$_SESSION['mb_level'] : 0;
    $is_super = isset($_SESSION['is_super'])  ? (int)$_SESSION['is_super']  : 0;
    if ($mb_level >= 10 || $is_super === 1) {
        $isSuper = true;
    }
}
?>
'''

if not content.startswith('<?php'):
    content = php_header + content
    print("✅ 1. PHP 헤더 삽입 완료")
else:
    print("⚠️  1. PHP 헤더 이미 존재 — 스킵")

# ── 2. 관리자 버튼 전용 CSS 삽입 ──
admin_css = '''
/* 슈퍼관리자 전용 버튼 */
.mh-btn-admin {
    background: linear-gradient(135deg, #ff6b35 0%, #f7c948 100%);
    border: 1.5px solid rgba(255,180,60,0.8);
    color: #1a1a1a !important;
    font-weight: 700;
    letter-spacing: -0.01em;
    box-shadow: 0 0 12px rgba(255,160,40,0.4);
}
.mh-btn-admin:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
    box-shadow: 0 0 20px rgba(255,160,40,0.7);
    color: #000 !important;
}

/* 플로팅 버튼 그룹 */'''

old_float = '/* 플로팅 버튼 그룹 */'
if '.mh-btn-admin' not in content:
    content = content.replace(old_float, admin_css, 1)
    print("✅ 2. 관리자 CSS 삽입 완료")
else:
    print("⚠️  2. 관리자 CSS 이미 존재 — 스킵")

# ── 3. 모바일 액션 버튼 교체 ──
old_mobile = '''            <!-- 모바일 전용: 우측 액션 버튼 + 햄버거 (오른쪽 끝) -->
            <div class="mh-mobile-actions">
                                    <a href="javascript:void(0)" onclick="openModal(\'loginModal\')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal(\'signupModal\')" class="btn mh-btn mh-btn-outline">회원가입</a>
                                <!-- 햄버거 버튼 — 오른쪽 끝 -->'''

new_mobile = '''            <!-- 모바일 전용: 우측 액션 버튼 + 햄버거 (오른쪽 끝) -->
            <div class="mh-mobile-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isSuper): ?>
                        <span class="mh-user-greeting"><?= htmlspecialchars($mb_name) ?>님</span>
                        <a href="/admin" class="btn mh-btn mh-btn-admin">🛡️ 관리자</a>
                        <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                    <?php else: ?>
                        <span class="mh-user-greeting"><?= htmlspecialchars($mb_name) ?>님</span>
                        <a href="/mypage.php" class="btn mh-btn mh-btn-mypage">마이페이지</a>
                        <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="openModal(\'loginModal\')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal(\'signupModal\')" class="btn mh-btn mh-btn-outline">회원가입</a>
                <?php endif; ?>
                <!-- 햄버거 버튼 — 오른쪽 끝 -->'''

if old_mobile in content:
    content = content.replace(old_mobile, new_mobile, 1)
    print("✅ 3. 모바일 액션 버튼 교체 완료")
else:
    print("❌ 3. 모바일 액션 버튼 대상 미발견 — 확인 필요")

# ── 4. PC 네비 액션 버튼 교체 ──
old_pc = '''            <!-- PC 전용: 회원가입/로그인/테마 버튼 -->
            <div class="mh-nav-actions">
                                    <a href="javascript:void(0)" onclick="openModal(\'loginModal\')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal(\'signupModal\')" class="btn mh-btn mh-btn-outline">회원가입</a>
                
            </div>'''

new_pc = '''            <!-- PC 전용: 회원가입/로그인/테마 버튼 -->
            <div class="mh-nav-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isSuper): ?>
                        <span class="mh-user-greeting"><?= htmlspecialchars($mb_name) ?>님</span>
                        <a href="/admin" class="btn mh-btn mh-btn-admin">🛡️ 관리자페이지</a>
                        <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                    <?php else: ?>
                        <span class="mh-user-greeting"><?= htmlspecialchars($mb_name) ?>님</span>
                        <a href="/mypage.php" class="btn mh-btn mh-btn-mypage">마이페이지</a>
                        <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="openModal(\'loginModal\')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal(\'signupModal\')" class="btn mh-btn mh-btn-outline">회원가입</a>
                <?php endif; ?>
            </div>'''

if old_pc in content:
    content = content.replace(old_pc, new_pc, 1)
    print("✅ 4. PC 액션 버튼 교체 완료")
else:
    print("❌ 4. PC 액션 버튼 대상 미발견 — 확인 필요")

# ── 저장 ──
with open('/home/webapp/ma_live.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("\n✅✅ 전체 패치 완료 → ma_live.php 저장됨")
