<?php
/**
 * Modern Header Component
 * 현대적인 헤더 + 드롭다운 메뉴 + 테마 전환 + 모달
 *
 * 사용법: include 'includes/modern_header.php';
 * 버전: 2.1.0 (모바일 최적화)
 *
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * ⚠️  링크 작성 규칙 (반드시 준수)
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 이 파일은 루트(/)와 하위 디렉토리(/iam/ 등) 양쪽에서
 * include 됩니다. 상대경로를 쓰면 디렉토리에 따라
 * 경로가 달라져 404 오류가 발생합니다.
 *
 * ❌ 잘못된 예시 (상대경로 - 절대 사용 금지!)
 *    href="ma.php"
 *    href="sub_8.php"
 *    href="mypage.php"
 *
 * ✅ 올바른 예시 (절대경로 - 항상 /로 시작)
 *    href="/ma.php"
 *    href="/sub_8.php"
 *    href="/mypage.php"
 *    href="/iam/pay_nm_v01.php"
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 */

// 세션 및 사용자 정보 (이미 _head.php에서 처리됨)
$is_logged_in = isset($_SESSION['one_member_id']);
$user_name = $is_logged_in ? $member_1['mem_name'] : '';
?>

<!-- 다크 테마 고정 -->
<script>
document.documentElement.setAttribute('data-theme', 'dark');
</script>

<style>
/* ============================================
   Modern Header v2.1.0 - 모바일 최적화
   캐시 무효화: 2026-04-08
   ============================================ */
/* ============================================
   라이트 테마 (Light Theme)
   ============================================ */
:root,
:root[data-theme="light"] {
    /* 배경색 */
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --bg-tertiary: #e9ecef;
    --bg-overlay: rgba(0, 0, 0, 0.8);
    
    /* 텍스트 색상 */
    --text-primary: #2c3e50;
    --text-secondary: #666666;
    --text-tertiary: #999999;
    --text-inverse: #ffffff;
    
    /* 강조 색상 */
    --primary-color: #82c736;
    --secondary-color: #ffd700;
    --accent-color: #24303e;
    --accent-hover: #6ba82a;
    
    /* 그라데이션 */
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-hero: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
    
    /* 카드/박스 */
    --card-bg: #ffffff;
    --card-border: #e0e0e0;
    --card-shadow: 0 4px 16px rgba(0,0,0,0.12);
    --card-shadow-hover: 0 8px 32px rgba(0,0,0,0.15);
    
    /* 모달 */
    --modal-bg: #ffffff;
    --modal-header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --modal-overlay: rgba(0, 0, 0, 0.8);
    
    /* 입력 필드 */
    --input-bg: #ffffff;
    --input-border: #dee2e6;
    --input-focus-border: #667eea;
    --input-text: #2c3e50;
    
    /* 버튼 */
    --btn-primary-bg: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --btn-outline-border: #2c3e50;
    --btn-outline-text: #2c3e50;
    --btn-outline-hover-bg: #2c3e50;
    --btn-outline-hover-text: #ffffff;
    
    /* 헤더 */
    --header-bg: linear-gradient(to right, #1a1a2e, #16213e);
    --header-text: #ffffff;
    --header-top-bg: rgba(255,255,255,0.1);
    
    /* 섀도우 */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.15);
}

/* ============================================
   다크 테마 (Dark Theme)
   ============================================ */
:root[data-theme="dark"] {
    /* 배경색 */
    --bg-primary: #1a1a2e;
    --bg-secondary: #16213e;
    --bg-tertiary: #0f3460;
    --bg-overlay: rgba(0, 0, 0, 0.95);
    
    /* 텍스트 색상 */
    --text-primary: #e9ecef;
    --text-secondary: #adb5bd;
    --text-tertiary: #6c757d;
    --text-inverse: #1a1a2e;
    
    /* 강조 색상 */
    --primary-color: #82c736;
    --secondary-color: #ffd700;
    --accent-color: #4facfe;
    --accent-hover: #9fe84d;
    
    /* 그라데이션 */
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-hero: linear-gradient(135deg, #0a0a0f, #1a1a2e, #16213e);
    
    /* 카드/박스 */
    --card-bg: #16213e;
    --card-border: #0f3460;
    --card-shadow: 0 4px 16px rgba(0,0,0,0.5);
    --card-shadow-hover: 0 8px 32px rgba(0,0,0,0.7);
    
    /* 모달 */
    --modal-bg: #16213e;
    --modal-header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --modal-overlay: rgba(0, 0, 0, 0.95);
    
    /* 입력 필드 */
    --input-bg: #0f3460;
    --input-border: #1a1a2e;
    --input-focus-border: #667eea;
    --input-text: #e9ecef;
    
    /* 버튼 */
    --btn-primary-bg: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --btn-outline-border: #e9ecef;
    --btn-outline-text: #e9ecef;
    --btn-outline-hover-bg: #e9ecef;
    --btn-outline-hover-text: #1a1a2e;
    
    /* 헤더 */
    --header-bg: linear-gradient(to right, #0a0a0f, #1a1a2e);
    --header-text: #e9ecef;
    --header-top-bg: rgba(255,255,255,0.05);
    
    /* 섀도우 */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.5);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.7);
}

/* 테마 전환 애니메이션 */
body {
    transition: background-color 0.3s ease, color 0.3s ease;
}



/* ============================================
   Modern Header Styles
   ============================================ */
.modern-header {
    background: var(--header-bg);
    padding: 0;
    position: fixed !important;   /* body max-width 제한 무시 */
    top: 0 !important;
    left: 0 !important;           /* 화면 왼쪽 끝 기준 */
    right: 0 !important;          /* 화면 오른쪽 끝 기준 */
    width: 100vw !important;      /* viewport 전체 폭 — body 제한 완전 무시 */
    z-index: 1000;
    box-shadow: var(--shadow-md);
    transition: background 0.3s ease;
    box-sizing: border-box !important;
}

/* ============================================
   2단 헤더 레이아웃 (v3.0)
   Row 1: .mh-top     — 유틸리티 바 (매뉴얼/공지 | 회원가입/로그인/다크모드)
   Row 2: .mh-main    — 메인 네비   (로고 | 메뉴)
   ============================================ */

/* ─── 공통 컨테이너 ─── */
.mh-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    width: 100%;
    box-sizing: border-box;
}

/* ─── 광고 배너 ─── */
.mh-ad-banner {
    position: relative;
    width: 100%;
    height: 36px;
    background: linear-gradient(90deg, #1a1050 0%, #2d1b6e 40%, #1e1460 70%, #0f0a3c 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-sizing: border-box;
}
.mh-ad-content {
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1;
    white-space: nowrap;
}
.mh-ad-badge {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 20px;
    letter-spacing: 0.03em;
}
.mh-ad-text {
    color: rgba(255,255,255,0.92);
    font-size: 12.5px;
    font-weight: 400;
    letter-spacing: 0.01em;
}
.mh-ad-text strong {
    color: #fff;
    font-weight: 700;
}
.mh-ad-btn {
    background: linear-gradient(135deg, #5b4de8, #7b6cf8);
    color: #fff !important;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 20px;
    text-decoration: none;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.mh-ad-btn:hover { opacity: 0.85; }
/* 별 장식 */
.mh-ad-star {
    position: absolute;
    color: rgba(255,255,255,0.55);
    font-size: 10px;
    pointer-events: none;
    animation: mh-twinkle 2.5s infinite alternate;
}
.mh-ad-star.s1 { top: 5px;  left: 4%;  font-size: 8px; animation-delay: 0s; }
.mh-ad-star.s2 { top: 20px; left: 12%; font-size: 11px; animation-delay: 0.4s; }
.mh-ad-star.s3 { top: 6px;  left: 85%; font-size: 9px;  animation-delay: 0.8s; }
.mh-ad-star.s4 { top: 22px; left: 92%; font-size: 12px; animation-delay: 1.2s; }
.mh-ad-star.s5 { top: 8px;  left: 55%; font-size: 7px;  animation-delay: 0.6s; }
@keyframes mh-twinkle {
    from { opacity: 0.3; transform: scale(0.8); }
    to   { opacity: 0.9; transform: scale(1.2); }
}
/* 모바일: 배너 숨김 */
@media (max-width: 1024px) {
    .mh-ad-banner { display: none !important; }
}

/* ─── Row 1: 유틸리티 바 ─── */
.mh-top {
    background: var(--header-top-bg);
    border-bottom: 1px solid rgba(255,255,255,0.18);
    transition: background 0.3s ease;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    min-height: 40px;
    padding: 8px 40px;   /* 좌우 동일 40px 고정 여백 */
    box-sizing: border-box;
    gap: 0;
}

/* 좌측 링크 그룹 (매뉴얼, 공지사항) */
.mh-top-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-shrink: 0;
}

/* 우측 액션 그룹 (회원가입, 로그인, 다크모드) */
.mh-top-right {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    flex-shrink: 0;
    /* margin-left: auto 제거 — space-between이 자동 처리 */
}

/* ─── 메인 네비 우측 액션 (회원가입/로그인/테마토글) ─── */
.mh-nav-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
    margin-left: auto;       /* 로고 다음 공간을 밀어서 우측 고정 */
    position: relative;      /* z-index 효과를 위해 stacking context 확보 */
    z-index: 1;
}

/* PC에서 .mh-mobile-actions 숨김 */
.mh-mobile-actions {
    display: none !important;
}

/* ─── Row 2: 메인 네비 ─── */
.mh-main {
    width: 100%;
}

.mh-main .mh-container {
    height: 68px;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    gap: 0;
    padding: 0 5%;           /* 유틸리티 바와 동일한 5% 여백 */
    max-width: none;
    margin: 0;
    box-sizing: border-box;
    position: relative;      /* 메뉴 절대중앙 기준점 */
}

/* ─── 유틸리티 링크 공통 ─── */
.mh-top-menu {
    display: flex;
    gap: 0.75rem;     /* 0.5 → 0.75: 메뉴 링크 간격 여유 */
    font-size: 0.85rem;
    align-items: center;
}

.mh-top-menu a {
    color: var(--header-text);
    opacity: 0.8;
    text-decoration: none;
    transition: color 0.3s ease, opacity 0.3s ease;
}

.mh-top-menu a:hover {
    color: var(--primary-color);
    opacity: 1;
}

.mh-top-menu .divider {
    color: rgba(255,255,255,0.5);
}

.user-info {
    background-color: #43515e;
    padding: 2px 20px 2px 5px;
    border-radius: 4px;
    color: rgba(255,255,255,0.9);
}


.logo {
    text-decoration: none;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    position: relative;      /* z-index 효과를 위한 stacking context */
    z-index: 1;
}
.logo img {
    height: 36px;
    width: auto;
    display: block;
    mix-blend-mode: screen;  /* 검정 배경 투명 처리 */
    transition: opacity 0.25s ease;
}
.logo:hover img {
    opacity: 0.85;
}

/* 네비게이션 컨테이너 - 가운데 확장 */
.mh-container > nav,
.mh-main .mh-container > nav {
    flex: 1;
    display: flex;
    justify-content: center;
    min-width: 0;
    overflow: visible;          /* 드롭다운이 아래로 나올 수 있도록 visible 유지 */
}

/* 네비게이션 메뉴 */
.mh-nav-menu {
    display: flex;
    flex-wrap: nowrap;          /* 절대 두줄 금지 — JS가 overflow 감지해 햄버거로 전환 */
    gap: 0.5rem;
    list-style: none;
    justify-content: center;
    align-items: center;
    overflow: visible;          /* 드롭다운이 아래로 나올 수 있도록 visible — JS가 scrollWidth로 넘침 감지 */
}

.mh-nav-menu > li {
    position: relative;
}

.mh-nav-menu > li > a {
    color: var(--header-text);
    text-decoration: none;
    font-weight: 600;
    font-size: 1.05rem;
    padding: 1rem 1.2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: block;
    white-space: nowrap;        /* 글자 줄바꿈 완전 차단 — 넘치면 JS가 햄버거로 전환 */
}

.mh-nav-menu > li > a:hover,
.mh-nav-menu > li > a.active {
    background: rgba(255,255,255,0.1);
    color: var(--primary-color);
}

/* ─── 메가메뉴 ─── */
/* 헤더에 position:relative 보장 */
.modern-header { position: relative; }

/* 메가메뉴 패널: 헤더 하단 전체 너비로 펼쳐짐 */
/* ━━━ mh-nav-wrapper: nav바 + 메가패널을 묶는 컨테이너 ━━━ */
.mh-nav-wrapper {
    display: flex;
    align-items: center;
    /* grid 방식에서는 position 불필요 — 중앙 컬럼이 자동으로 가운데 */
}

/* ━━━ 공통 메가메뉴 패널 (.mh-mega-panel) ━━━
   어느 메뉴에 hover해도 전체 8개 카테고리가 한 번에 표시 */
.mh-mega-panel {
    display: none;
    position: fixed;
    left: 0;
    right: 0;
    width: 100%;
    top: 68px;               /* JS로 동적 갱신 */
    background: var(--bg-secondary);
    border-top: 2px solid #82c736;
    border-bottom: 1px solid var(--card-border);
    box-shadow: var(--shadow-lg);
    z-index: 9000;
    padding: 28px 5% 24px;
    box-sizing: border-box;
    flex-direction: row;
    gap: 24px;
    align-items: flex-start;
    flex-wrap: wrap;
}

/* nav 영역에 hover하면 패널 표시 */
.mh-nav-wrapper:hover .mh-mega-panel,
.mh-mega-panel:hover {
    display: flex;
    animation: dropdown-appear 0.2s ease;
}

/* 메가메뉴 안 링크 세로 목록 */
.mh-mega-panel ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* 각 카테고리 컬럼 */
.mh-mega-col {
    min-width: 100px;
    flex: 1 0 auto;
}

/* ─── 결제 메뉴 강조 버튼 ─── */
.mh-pay-menu { margin-left: 0.5rem; }
.mh-pay-btn {
    display: inline-flex !important;
    align-items: center;
    gap: 4px;
    background: transparent !important;
    color: rgba(255,255,255,0.9) !important;
    padding: 0.45rem 1.1rem !important;
    border-radius: 20px !important;
    font-weight: 600 !important;
    font-size: 0.95rem !important;
    transition: all 0.25s ease !important;
    border: none;
    box-shadow: none;
}
.mh-pay-btn:hover,
.mh-pay-btn.active {
    background: transparent !important;
    color: #82c736 !important;
    box-shadow: none;
}

/* 메가메뉴 컬럼 타이틀 (1단계 메뉴명 표시) */
.mh-mega-col-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-secondary, #9ca3af);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--card-border);
    white-space: nowrap;
    transition: color 0.2s;
}
/* hover된 메뉴에 대응하는 컬럼 타이틀만 연두색 강조 */
.mh-mega-col-title.mh-col-active {
    color: #82c736 !important;
}

/* 메가메뉴 앱홈 바로가기 컬럼 */
.mh-mega-apphome {
    min-width: 120px;
    padding-right: 32px;
    border-right: 1px solid var(--card-border);
    margin-right: 8px;
    flex-shrink: 0;
}
.mh-mega-apphome a {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary) !important;
    text-decoration: none;
    padding: 8px 0;
    white-space: nowrap;
}
.mh-mega-apphome a:hover { color: #82c736 !important; }

/* 메가메뉴 각 링크 */
.mh-mega-panel a {
    display: block;
    padding: 7px 0;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    transition: color 0.15s;
    line-height: 1.5;
}

.mh-mega-panel a:hover {
    color: #82c736;
    background: none;
    padding-left: 0;
}

@keyframes dropdown-appear {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 헤더 액션 버튼 */
.mh-header-actions {
    display: flex;
    gap: 2.5rem;
    align-items: center;
    margin-left: auto;
    flex-shrink: 0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-block;
    font-size: 0.9rem;
}

.btn-primary {
    background: var(--btn-primary-bg);
    color: #000000;
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
    color: #000000;
}

.btn-outline {
    background: transparent;
    border: 2px solid rgba(255,255,255,0.3);
    color: #ffffff !important; /* 흰색 강제 적용 */
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: var(--btn-outline-hover-bg);
    color: var(--btn-outline-hover-text);
}



/* ============================================
   모달 시스템 (Modal System)
   ============================================ */

/* 모달 배경 */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: var(--modal-overlay);
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease;
}

.modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* 모달 컨텐츠 */
.modal-content {
    background: var(--modal-bg);
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    animation: slideUp 0.3s ease;
    position: relative;
    border: 1px solid var(--card-border);
}

/* 모달 헤더 */
.modal-header {
    background: #ffffff;
    color: #111111;
    padding: 1.1rem 1.5rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e0e0e0;
    position: relative;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #111111;
}

/* 닫기 버튼 */
.modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: none;
    color: #111111;
    font-size: 1.4rem;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    line-height: 1;
}

.modal-close:hover {
    background: #f0f0f0;
    transform: rotate(90deg);
}

/* 모달 바디 */
.modal-body {
    padding: 0;
    max-height: calc(90vh - 58px);
    overflow-y: hidden;
    background: var(--modal-bg);
}

.modal-body iframe {
    width: 100%;
    height: 560px;
    border: none;
    display: block;
    overflow: hidden;
}

/* 모달 애니메이션 */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* 모달 스크롤바 스타일 */
.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: var(--bg-secondary);
}

.modal-body::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: var(--accent-hover);
}

/* ============================================
   모바일 햄버거 메뉴
   ============================================ */

/* 햄버거 버튼 */
.mh-mobile-toggle {
    display: none;
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    cursor: pointer;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 0;
}

.mh-mobile-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background: var(--header-text);
    border-radius: 2px;
    transition: all 0.3s ease;
}

.mh-mobile-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(8px, 8px);
}

.mh-mobile-toggle.active span:nth-child(2) {
    opacity: 0;
}

.mh-mobile-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(8px, -8px);
}

/* 모바일 메뉴 오버레이 */
.mh-mobile-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mh-mobile-overlay.active {
    display: block;   /* ← display none 해제 */
    opacity: 1;
}

/* 모바일 메뉴 사이드바 */
.mh-mobile-menu {
    display: block;           /* ← 항상 block, 위치로만 숨김 */
    position: fixed;
    top: 0;
    left: -100%;             /* 화면 밖으로 숨김 */
    width: 80%;
    max-width: 320px;
    height: 100%;
    background: var(--bg-secondary);
    z-index: 9999;
    overflow-y: auto;
    transition: left 0.3s ease;
    box-shadow: 2px 0 20px rgba(0,0,0,0.3);
    visibility: hidden;       /* 화면 밖일 때 포커스/스크롤 차단 */
}

.mh-mobile-menu.active {
    left: 0;                  /* 화면 안으로 슬라이드 */
    visibility: visible;
}

/* 모바일 메뉴 헤더 */
.mh-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--header-bg);
    border-bottom: 1px solid var(--card-border);
}

.mh-mobile-header h3 {
    color: var(--text-primary);
    font-size: 1.2rem;
    margin: 0;
}

.mh-mobile-close {
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    color: var(--text-primary);
    font-size: 2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

/* 모바일 메뉴 아이템 */
.mh-mobile-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mh-mobile-item {
    border-bottom: 1px solid var(--card-border);
}

.mh-mobile-item > a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.3s ease;
    min-height: 48px;
}

.mh-mobile-item > a:hover {
    background: var(--bg-tertiary);
    color: var(--primary-color);
}

/* 서브메뉴 토글 화살표 */
.mh-submenu-toggle {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.mh-mobile-item.active .mh-submenu-toggle {
    transform: rotate(180deg);
}

/* 모바일 서브메뉴 */
.mh-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: var(--bg-tertiary);
}

.mh-mobile-item.active .mh-submenu {
    max-height: 500px;
}

.mh-submenu li {
    list-style: none;
}

.mh-submenu a {
    display: block;
    padding: 0.75rem 1.5rem 0.75rem 2.5rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    min-height: 44px;
}

.mh-submenu a:hover {
    background: rgba(255,255,255,0.05);
    color: var(--primary-color);
    padding-left: 3rem;
}

/* 모바일 하단 메뉴 */
.mh-mobile-footer {
    padding: 1rem 1.5rem;
    background: var(--bg-tertiary);
    border-top: 1px solid var(--card-border);
}

.mh-mobile-footer a {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    border-bottom: 1px solid var(--card-border);
    transition: all 0.3s ease;
}

.mh-mobile-footer a:last-child {
    border-bottom: none;
}

.mh-mobile-footer a:hover {
    background: rgba(255,255,255,0.05);
    color: var(--primary-color);
}

/* =====================================================
   JS 동적 햄버거 전환 — .use-hamburger 클래스 기반
   메뉴가 한 줄을 초과할 때 JS가 헤더에 이 클래스 추가
   → 픽셀 브레이크포인트 없이 실제 레이아웃 기반 전환
   ===================================================== */

/* 햄버거 모드 진입 시: PC 메뉴/액션 숨김 */
.modern-header.use-hamburger .mh-nav-wrapper    { display: none !important; }
.modern-header.use-hamburger .mh-nav-menu       { display: none !important; }
.modern-header.use-hamburger .mh-nav-actions    { display: none !important; }
.modern-header.use-hamburger .mh-top            { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; }

/* 햄버거 모드 진입 시: 햄버거 버튼 + 모바일 액션 표시 */
.modern-header.use-hamburger .mh-mobile-toggle  { display: flex !important; }
.modern-header.use-hamburger .mh-mobile-actions { display: flex !important; align-items: center; gap: 0.5rem; }

/* PC 모드 유지 시: 햄버거/모바일액션 숨김 (기본값 보강) */
.modern-header:not(.use-hamburger) .mh-mobile-toggle  { display: none !important; }
.modern-header:not(.use-hamburger) .mh-mobile-actions { display: none !important; }

/* ============================================
   반응형 브레이크포인트 3단계
   모바일  : ≤ 600px
   태블릿  : 601px ~ 1024px
   PC      : ≥ 1025px
   모바일+태블릿 공통 (≤ 1024px): 햄버거 모드
   ============================================ */

/* ─── 모바일 + 태블릿 공통: 햄버거 모드 (≤1024px) ─── */
@media (max-width: 1024px) {
    /* 유틸리티 바 숨김 */
    .mh-top {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    /* PC 전용 nav 숨김 */
    .mh-nav-wrapper { display: none !important; }
    .mh-nav-menu  { display: none !important; }
    .mh-nav-actions { display: none !important; }

    /* 햄버거 버튼 + 모바일 액션 항상 표시 */
    .mh-mobile-toggle,
    .modern-header:not(.use-hamburger) .mh-mobile-toggle {
        display: flex !important;
    }
    /* 모바일(≤1024px)에서 use-hamburger 상태에서만 mh-mobile-actions 표시 */
    .modern-header.use-hamburger .mh-mobile-actions {
        display: flex !important;
        align-items: center;
        gap: 0.5rem;
        margin-left: auto !important;
        flex-shrink: 0 !important;
    }

    /* 헤더 컨테이너: 로고 왼쪽, 액션+햄버거 오른쪽 */
    .mh-main .mh-container {
        justify-content: flex-start !important;
    }
    .mh-main .mh-container nav {
        display: none !important;
    }

    /* 모바일 슬라이드 메뉴/오버레이: display 제어 안 함 (기본 CSS 사용) */
    .mh-mobile-menu    { left: -100% !important; visibility: hidden !important; }
    .mh-mobile-menu.active { left: 0 !important; visibility: visible !important; }
    .mh-mobile-overlay { display: none; }
    .mh-mobile-overlay.active { display: block; }

    .modal-content  { width: 95%; max-width: none; border-radius: 14px; max-height: 95vh; }
    .modal-header   { padding: 0.85rem 1.2rem; }
    .modal-body iframe { height: 500px; }
}

/* ─── 태블릿 전용 조정 (601px ~ 1024px) ─── */
@media (min-width: 601px) and (max-width: 1024px) {
    .mh-main .mh-container {
        padding: 0 3% !important;
    }
    .logo img   { height: 32px !important; }
    .mh-btn     { padding: 0.3rem 0.8rem !important; font-size: 0.78rem !important; }
    .mh-mobile-toggle {
        width: 44px !important;
        height: 44px !important;
        order: 99;
    }
}

/* ─── 모바일 전용 조정 (≤600px) ─── */
@media (max-width: 600px) {
    .logo img   { height: 28px !important; }
    .mh-btn     { padding: 0.2rem 0.55rem !important; font-size: 0.72rem !important; }
    .mh-mobile-toggle {
        width: 40px !important;
        height: 40px !important;
        order: 99;
    }
    .mh-main .mh-container {
        padding: 0 5% !important;
    }
    .mh-header-actions { gap: 0.5rem; }
    .btn { padding: 0.5rem 1rem; font-size: 0.85rem; }
}

/* ============================================
   플로팅 버튼 그룹 (우측 하단 고정)
   ============================================ */
/* header-actions는 더 이상 사용 안 함 (top-right로 통합) */
.mh-header-actions {
    display: none;
}

/* 상단 바 내 액션 버튼 (소형) */
.mh-btn {
    padding: 0.38rem 1.1rem;     /* 상하 여유 ↑, 좌우 여유 ↑ */
    border-radius: 50px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    border: none;
    line-height: 1.4;
}

.mh-btn-outline {
    background: rgba(255,255,255,0.15);  /* 선명한 배경으로 버튼 실체감 */
    border: 1.5px solid rgba(255,255,255,0.9);
    color: #ffffff !important;            /* a:link 전역룰 덮어쓰기 */
    padding: 0.42rem 1.3rem;
    text-shadow: none;
}

.mh-btn-outline:hover {
    background: rgba(255,255,255,0.28);
    border-color: #fff;
    color: #fff;
}

.mh-btn-primary {
    background: var(--primary-color);
    color: #000;
}

.mh-btn-primary:hover {
    filter: brightness(1.12);
    transform: translateY(-1px);
    color: #000;
}

.mh-btn-mypage {
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.4);
    color: rgba(255,255,255,0.9) !important;
}

.mh-btn-mypage:hover {
    background: rgba(255,255,255,0.22);
    color: #fff;
}

/* 로그인 사용자 이름 표시 */
.mh-user-greeting {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.75);
    white-space: nowrap;
    letter-spacing: -0.01em;
}

/* 로그아웃 버튼 */
.mh-btn-logout {
    background: transparent;
    border: 1.5px solid rgba(255,255,255,0.35);
    color: rgba(255,255,255,0.7) !important;
    font-size: 0.78rem;
}

.mh-btn-logout:hover {
    background: rgba(255,80,80,0.15);
    border-color: rgba(255,100,100,0.7);
    color: #ff8080;
}

/* 플로팅 버튼 그룹 */
.mh-float-group {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

/* 공통 플로팅 버튼 */
.mh-float-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    text-decoration: none;
    filter: drop-shadow(0 4px 16px rgba(0,0,0,0.35));
    transition: transform 0.25s ease, filter 0.25s ease;
}

.mh-float-btn:hover {
    transform: translateY(-4px) scale(1.07);
    filter: drop-shadow(0 8px 24px rgba(0,0,0,0.45));
}

.mh-float-inner {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
}

.mh-float-inner svg,
.mh-float-inner img {
    width: 32px;
    height: 32px;
}

.mh-float-label {
    background: rgba(0,0,0,0.65);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 20px;
    white-space: nowrap;
    backdrop-filter: blur(4px);
}

/* 앱설치 버튼 — 이미지 아이콘 */
.mh-float-app .mh-float-inner {
    background: #fff;
    border: none;
    overflow: hidden;
    padding: 0;
}

.mh-float-app .mh-float-inner img {
    width: 56px;
    height: 56px;
    object-fit: cover;
    border-radius: 50%;
}

.mh-float-app:hover .mh-float-inner {
    background: #f1f3f4;
}

/* 카톡 버튼 고유 색상 */
.mh-float-kakao .mh-float-inner {
    background: #FEE500;
}

/* 하위 호환: 기존 클래스명 별칭 */
.mh-kakao-old { /* 더 이상 position:fixed 아님 — float-group이 담당 */ }

@media (max-width: 1024px) {
    .mh-float-group {
        bottom: 1.2rem;
        right: 1.2rem;
        gap: 0.6rem;
    }
    .mh-float-inner {
        width: 48px;
        height: 48px;
    }
    .mh-float-inner svg,
    .mh-float-inner img {
        width: 26px;
        height: 26px;
    }
}
</style>

<!-- Modern Header v3.0 — 2단 레이아웃
     Row 1: .mh-top  — 유틸리티 바 (매뉴얼/공지 | 회원가입/로그인/다크모드)
     Row 2: .mh-main — 메인 네비   (로고 | 메뉴)
-->
<header class="modern-header ma-header">

    <!-- ══ Row 1: 광고 배너 ══ -->
    <div class="mh-ad-banner">
        <span class="mh-ad-star s1">✦</span>
        <span class="mh-ad-star s2">✦</span>
        <span class="mh-ad-star s3">✦</span>
        <span class="mh-ad-star s4">✦</span>
        <span class="mh-ad-star s5">✦</span>
        <div class="mh-ad-content">
            <span class="mh-ad-badge">🎤 특별강연</span>
            <span class="mh-ad-text">AI 마이더스 특별강연 &mdash; <strong>송조은 교수</strong>와 함께하는 AI 실전 활용법 완전 정복</span>
            <a href="/cliente_list.php?status=1" class="mh-ad-btn">신청하기 &rsaquo;</a>
        </div>
    </div><!-- /.mh-ad-banner -->

    <!-- ══ Row 2: 메인 네비게이션 ══ -->
    <div class="mh-main">
        <div class="mh-container">

            <!-- 로고 -->
            <a href="/ma.php" class="logo">
                <img src="/iam/img/common/logo-2.png" alt="IAM PRO" />
            </a>

            <!-- 모바일 전용: 우측 액션 버튼 + 햄버거 (오른쪽 끝) -->
            <div class="mh-mobile-actions">
                <?php if($is_logged_in): ?>
                    <a href="/mypage.php" class="btn mh-btn mh-btn-mypage">마이페이지</a>
                    <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="openModal('loginModal')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal('signupModal')" class="btn mh-btn mh-btn-outline">회원가입</a>
                <?php endif; ?>
                <!-- 햄버거 버튼 — 오른쪽 끝 -->
                <button class="mh-mobile-toggle" id="mhNavToggle" aria-label="메뉴 열기">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- 데스크톱 메가메뉴 wrapper: hover 영역이 nav바 + 패널 전체를 커버 -->
            <div class="mh-nav-wrapper">
                <nav>
                    <ul class="mh-nav-menu">
                        <li data-col="명함·인맥"><a href="/sub_10_v01.php">명함·인맥</a></li>
                        <li data-col="디비수집"><a href="/sub_2_v01.php">디비</a></li>
                        <li data-col="콜백문자"><a href="/sub_11_v01.php">콜백</a></li>
                        <li data-col="랜딩페이지"><a href="/sub_12_v01.php">랜딩</a></li>
                        <li data-col="통합문자"><a href="/sub_1_v01.php">통합문자</a></li>
                        <li data-col="퍼널관리"><a href="/mypage_reservation_list.php">퍼널</a></li>
                        <li data-col="리소스"><a href="#">리소스</a></li>
                        <li class="mh-pay-menu" data-col="결제"><a href="/iam/pay_nm_v01.php" class="mh-pay-btn">결제</a></li>
                    </ul>
                </nav>

                <!-- ▼ 공통 메가메뉴 패널: 어느 메뉴 hover 시에도 전체 8개 컬럼 표시 -->
                <div class="mh-mega-panel" id="mhMegaPanel">
                    <!-- 앱홈 컬럼 -->
                    <div class="mh-mega-apphome">
                        <div class="mh-mega-col-title">앱홈</div>
                        <a href="/m_v01/">앱홈 바로가기 →</a>
                    </div>

                    <!-- 명함·인맥 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">명함·인맥</div>
                        <ul>
                            <li><a href="/sub_10_v01.php">명함인맥소개</a></li>
                            <li><a href="/?hC13PteUeP8502" target="_blank">디지털명함</a></li>
                            <li><a href="javascript:void(0)" onclick="mhGoAICard()">AI명함등록</a></li>
                        </ul>
                    </div>

                    <!-- 디비수집 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">디비수집</div>
                        <ul>
                            <li><a href="/sub_2_v01.php">디비수집</a></li>
                            <li><a href="/diver_install.php">디비설치</a></li>
                        </ul>
                    </div>

                    <!-- 콜백문자 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">콜백문자</div>
                        <ul>
                            <li><a href="/sub_11_v01.php">콜백문자</a></li>
                            <li><a href="https://tinyurl.com/3teh9ez5" target="_blank">무료이용</a></li>
                        </ul>
                    </div>

                    <!-- 랜딩페이지 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">랜딩페이지</div>
                        <ul>
                            <li><a href="/sub_12_v01.php">랜딩제작</a></li>
                            <li><a href="/mypage_landing_list.php">인포랜딩</a></li>
                            <li><a href="/iam/mypage_report.php" target="_blank">모듈랜딩</a></li>
                        </ul>
                    </div>

                    <!-- 통합문자 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">통합문자</div>
                        <ul>
                            <li><a href="/sub_1_v01.php">통합문자소개</a></li>
                            <li><a href="/sub_6a.php">문자발송</a></li>
                            <li><a href="/sub_4_return_.php">수발신내역</a></li>
                            <li><a href="/sub_daily_intro_v01.php">데일리소개</a></li>
                            <li><a href="/daily_list.php">데일리발송</a></li>
                        </ul>
                    </div>

                    <!-- 퍼널관리 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">퍼널관리</div>
                        <ul>
                            <li><a href="/sub_12_v01.php">퍼널문자</a></li>
                            <li><a href="/mypage_request_list.php">신청관리</a></li>
                            <li><a href="/mypage_request_list.php">고객관리</a></li>
                            <li><a href="/mypage_reservation_list.php">퍼널관리</a></li>
                        </ul>
                    </div>

                    <!-- 리소스 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">리소스</div>
                        <ul>
                            <li><a href="/cliente_list.php?status=1">공지사항</a></li>
                            <li><a href="https://sites.google.com/view/onlyselling2/%ED%99%88" target="_blank">이용매뉴얼</a></li>
                        </ul>
                    </div>

                    <!-- 결제 -->
                    <div class="mh-mega-col">
                        <div class="mh-mega-col-title">결제</div>
                        <ul>
                            <li><a href="/iam/pay_nm_v01.php">요금제 안내</a></li>
                        </ul>
                    </div>
                </div><!-- /.mh-mega-panel -->
            </div><!-- /.mh-nav-wrapper -->

            <!-- PC 전용: 회원가입/로그인/테마 버튼 -->
            <div class="mh-nav-actions">
                <?php if($is_logged_in): ?>
                    <span class="mh-user-greeting">👤 <?= htmlspecialchars($user_name) ?>님</span>
                    <a href="/mypage.php" class="btn mh-btn mh-btn-mypage">마이페이지</a>
                    <a href="javascript:void(0)" onclick="logout()" class="btn mh-btn mh-btn-logout">로그아웃</a>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="openModal('loginModal')" class="btn mh-btn mh-btn-primary">로그인</a>
                    <a href="javascript:void(0)" onclick="openModal('signupModal')" class="btn mh-btn mh-btn-outline">회원가입</a>
                <?php endif; ?>

            </div>
        
        </div><!-- /.mh-container (header-main) -->
    </div><!-- /.mh-main -->

</header><!-- /.modern-header -->

<!-- 모바일 메뉴 오버레이 -->
<div class="mh-mobile-overlay" id="mhNavOverlay"></div>

<!-- 모바일 메뉴 -->
<nav class="mh-mobile-menu" id="mhSideMenu">
    <div class="mh-mobile-header">
        <h3>메뉴</h3>
        <button class="mh-mobile-close" id="mhNavClose" aria-label="메뉴 닫기">&times;</button>
    </div>
    
    <!-- 앱홈 바로가기 -->
    <div class="mh-mobile-apphome">
        <a href="/m_v01/">📱 앱홈 바로가기</a>
    </div>

    <ul class="mh-mobile-list">

        <!-- ① 명함·인맥 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                명함·인맥
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_10_v01.php">명함인맥소개</a></li>
                <li><a href="/?hC13PteUeP8502" target="_blank">디지털명함</a></li>
                <li><a href="javascript:void(0)" onclick="mhGoAICard()">AI명함등록</a></li>
            </ul>
        </li>

        <!-- ② 디비 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                디비
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_2_v01.php">디비수집</a></li>
                <li><a href="/diver_install.php">디비설치</a></li>
            </ul>
        </li>

        <!-- ③ 콜백 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                콜백
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_11_v01.php">콜백문자</a></li>
                <li><a href="https://tinyurl.com/3teh9ez5" target="_blank">무료이용</a></li>
            </ul>
        </li>

        <!-- ④ 랜딩 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                랜딩
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_12_v01.php">랜딩제작</a></li>
                <li><a href="/mypage_landing_list.php">인포랜딩</a></li>
                <li><a href="/iam/mypage_report.php" target="_blank">모듈랜딩</a></li>
            </ul>
        </li>

        <!-- ⑤ 통합문자 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                통합문자
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_1_v01.php">통합문자소개</a></li>
                <li><a href="/sub_6a.php">문자발송</a></li>
                <li><a href="/sub_4_return_.php">수발신내역</a></li>
                <li><a href="/sub_daily_intro_v01.php">데일리소개</a></li>
                <li><a href="/daily_list.php">데일리발송</a></li>
            </ul>
        </li>

        <!-- ⑥ 퍼널 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                퍼널
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/sub_12_v01.php">퍼널문자</a></li>
                <li><a href="/mypage_request_list.php">신청관리</a></li>
                <li><a href="/mypage_request_list.php">고객관리</a></li>
                <li><a href="/mypage_reservation_list.php">퍼널관리</a></li>
            </ul>
        </li>

        <!-- ⑦ 리소스 -->
        <li class="mh-mobile-item">
            <a href="javascript:void(0)" onclick="toggleMobileSubmenu(this)">
                리소스
                <span class="mh-submenu-toggle">▼</span>
            </a>
            <ul class="mh-submenu">
                <li><a href="/cliente_list.php?status=1">공지사항</a></li>
                <li><a href="https://sites.google.com/view/onlyselling2/%ED%99%88" target="_blank">이용매뉴얼</a></li>
            </ul>
        </li>

        <!-- ⑧ 결제 -->
        <li class="mh-mobile-item mh-mobile-pay">
            <a href="/iam/pay_nm_v01.php">💳 결제</a>
        </li>

    </ul>

    <div class="mh-mobile-footer">
        <a href="/mypage.php">👤 마이페이지</a>
        <a href="/cliente_list.php?status=1">📢 공지사항</a>
        <a href="https://sites.google.com/view/onlyselling2/%ED%99%88" target="_blank">📖 이용매뉴얼</a>
    </div>
</nav>

<!-- 플로팅 버튼 그룹 (우측 하단 고정) -->
<div class="mh-float-group">
    <!-- 앱설치 버튼 (위) -->
    <a href="https://play.google.com/store/apps/details?id=mms.onepagebook.com.onlyonesms" target="_blank" class="mh-float-btn mh-float-app" title="IAM 앱 설치" aria-label="앱 설치">
        <div class="mh-float-inner">
            <img src="/images/googleplay_icon.png" alt="Google Play" />
        </div>
        <div class="mh-float-label">앱 설치</div>
    </a>
    <!-- 카톡상담 버튼 (아래) -->
    <a href="https://pf.kakao.com/_jVafC/chat" target="_blank" class="mh-float-btn mh-float-kakao" title="카톡 상담하기" aria-label="카톡 상담">
        <div class="mh-float-inner">
            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                <ellipse cx="18" cy="16" rx="13" ry="10.5" fill="#3B1E08"/>
                <path d="M10.5 26.5c0.4-1.8 1-3.8 1.3-5C8.2 19.2 5.5 17.7 5.5 14.5 5.5 8.7 11.2 4 18 4s12.5 4.7 12.5 10.5S24.8 25 18 25a14 14 0 0 1-4.5-.78L5.5 31l5-4.5z" fill="#3B1E08"/>
                <circle cx="12" cy="15" r="1.6" fill="#FEE500"/>
                <circle cx="18" cy="15" r="1.6" fill="#FEE500"/>
                <circle cx="24" cy="15" r="1.6" fill="#FEE500"/>
            </svg>
        </div>
        <div class="mh-float-label">카톡 상담</div>
    </a>
</div>

<!-- ============================================
     모달 시스템 (Modals)
     ============================================ -->

<!-- 로그인 모달 -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>로그인</h2>
            <button class="modal-close" aria-label="닫기">&times;</button>
        </div>
        <div class="modal-body">
            <iframe src="/iam/login_popup.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" title="로그인"></iframe>
        </div>
    </div>
</div>

<!-- 회원가입 모달 -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>회원가입</h2>
            <button class="modal-close" aria-label="닫기">&times;</button>
        </div>
        <div class="modal-body">
            <iframe src="/iam/signup_proxy.php" title="회원가입"></iframe>
        </div>
    </div>
</div>

<script>


// ============================================
// 모달 관리 시스템 (Modal Manager)
// ============================================

// 모달 열기
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// 모달 닫기
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// 모든 모달 닫기
function closeAllModals() {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = 'auto';
}

// 페이지 로드 후 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 닫기 버튼 이벤트
    const closeButtons = document.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // 모달 배경 클릭 시 닫기
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            closeAllModals();
        }
    });
    
    // 현재 페이지 활성화 표시
    const currentPage = window.location.pathname;
    // 메뉴별 active 판단 맵: 최상위 메뉴 href → 해당 메뉴가 active 되어야 할 페이지 패턴 목록
    const menuActiveMap = {
        '/sub_10_v01.php':            ['/sub_10_v01.php'],
        '/sub_2_v01.php':             ['/sub_2_v01.php', '/cliente_list.php'],
        '/sub_11_v01.php':            ['/sub_11_v01.php'],
        '/sub_12_v01.php':            ['/sub_12_v01.php', '/mypage_landing_list.php', '/iam/mypage_report.php'],
        '/sub_1_v01.php':             ['/sub_1_v01.php', '/sub_6a.php', '/sub_4_return_.php', '/sub_daily_intro_v01.php', '/daily_list.php'],
        '/mypage_reservation_list.php': ['/mypage_reservation_list.php', '/mypage_request_list.php'],
        '#':                          [],
        '/iam/pay_nm_v01.php':        ['/iam/pay_nm_v01.php']
    };
    document.querySelectorAll('.mh-nav-menu > li > a').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;
        const patterns = menuActiveMap[href] || [href];
        const isActive = patterns.some(p => currentPage === p || currentPage.startsWith(p + '?'));
        if (isActive) link.classList.add('active');
    });
    
    // ============================================
    // 로그인 필요 페이지 체크 시스템
    // ============================================
    
    // 로그인이 필요한 페이지 목록
    const loginRequiredPages = [
        'mypage.php',
        'sub_5.php',           // 휴대폰등록
        'sub_6a.php',          // 문자발송
        'sub_4_return_.php',   // 발신내역
        'sub_4.php',           // 수신내역, 수신여부
        'mypage_landing_list.php', // 인포랜딩
        'mypage_report.php',   // 모듈랜딩
        'mypage_request_list.php', // 신청관리, 고객관리
        'mypage_reservation_list.php', // 퍼널관리
        'daily_list.php'       // 데일리
    ];
    
    // 현재 로그인 상태 확인
    function isLoggedIn() {
        // PHP 세션과 연동 필요
        // 여기서는 간단히 false로 설정 (로그인 필요 시에만 모달 표시 테스트)
        <?php echo !empty($_SESSION['one_member_id']) ? 'return true;' : 'return false;'; ?>
    }
    
    // 모든 링크에 로그인 체크 적용
    function setupLoginCheck() {
        const links = document.querySelectorAll('.mh-nav-menu a, .mh-mega-panel a, .mh-mobile-menu a, .mh-top-menu a[href*=".php"]');
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // 외부 링크나 앵커, javascript는 체크 안 함
                if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('javascript:')) {
                    return;
                }
                
                // 로그인이 필요한 페이지인지 확인
                const needsLogin = loginRequiredPages.some(page => href.includes(page));
                
                if (needsLogin && !isLoggedIn()) {
                    e.preventDefault();
                    openModal('loginModal');
                    console.log('🔒 로그인이 필요한 페이지:', href);
                    
                    // 로그인 후 이동할 페이지 저장
                    sessionStorage.setItem('redirectAfterLogin', href);
                }
            });
        });
    }
    
    setupLoginCheck();
    console.log('🔐 로그인 체크 시스템 초기화 완료');
    
    // ============================================
    // 모바일 메뉴 시스템
    // ============================================
    
    const mobileMenuToggle = document.getElementById('mhNavToggle');
    const mobileMenu = document.getElementById('mhSideMenu');
    const mobileMenuOverlay = document.getElementById('mhNavOverlay');
    const mobileMenuClose = document.getElementById('mhNavClose');
    
    // 모바일 메뉴 열기
    function openMobileMenu() {
        mobileMenuToggle.classList.add('active');
        mobileMenu.classList.add('active');
        mobileMenuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // 모바일 메뉴 닫기
    function closeMobileMenu() {
        mobileMenuToggle.classList.remove('active');
        mobileMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    
    // 이벤트 리스너 — 토글 (열기/닫기)
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            if (mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }
    
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    }
    
    // 모바일 메뉴 링크 클릭 시 메뉴 닫기
    document.querySelectorAll('#mhSideMenu a[href]:not([href="javascript:void(0)"])').forEach(link => {
        link.addEventListener('click', function() {
            closeMobileMenu();
        });
    });
    
    console.log('📱 모바일 메뉴 시스템 초기화 완료');
    
    // ============================================
    // 메가메뉴 드롭다운 top 위치 동적 설정
    // ============================================
    function updateDropdownTop() {
        var header = document.querySelector('.modern-header');
        if (!header) return;
        var headerBottom = header.getBoundingClientRect().bottom + window.scrollY;
        var headerHeight = header.offsetHeight;
        // position:fixed이므로 scrollY 무관, 헤더 높이만큼 top 설정
        var topVal = headerHeight + 'px';
        // 공통 메가패널 top 갱신
        var panel = document.getElementById('mhMegaPanel');
        if (panel) panel.style.top = topVal;
    }
    updateDropdownTop();
    window.addEventListener('resize', updateDropdownTop);
    window.addEventListener('scroll', updateDropdownTop);

    console.log('🎨 Modern Header System 초기화 완료');

    // ============================================
    // 메가메뉴 상단 메뉴 hover → 해당 컬럼 타이틀 연두색 강조
    // + hover 중에는 기존 페이지 active 메뉴를 dim 처리
    //   (두 개가 동시에 연두색이 되는 문제 방지)
    // ============================================
    (function() {
        var navItems = document.querySelectorAll('.mh-nav-menu > li[data-col]');
        var colTitles = document.querySelectorAll('.mh-mega-col-title, .mh-mega-apphome .mh-mega-col-title');
        var navWrapper = document.querySelector('.mh-nav-wrapper');

        function clearColHighlight() {
            colTitles.forEach(function(t) { t.classList.remove('mh-col-active'); });
            // hover 해제 시 페이지 active 복원
            document.querySelectorAll('.mh-nav-menu > li > a.was-active').forEach(function(a) {
                a.classList.add('active');
                a.classList.remove('was-active');
            });
        }

        navItems.forEach(function(li) {
            li.addEventListener('mouseenter', function() {
                var colName = li.getAttribute('data-col');

                // ① 기존 페이지 active 메뉴를 was-active로 바꿔 dim 처리
                document.querySelectorAll('.mh-nav-menu > li > a.active').forEach(function(a) {
                    a.classList.remove('active');
                    a.classList.add('was-active');
                });

                // ② 컬럼 타이틀 강조 초기화 후 해당 컬럼만 강조
                clearColHighlight();
                // clearColHighlight 안에서 was-active를 active로 돌리므로
                // 순서 조정: 타이틀만 따로 클리어
                colTitles.forEach(function(t) { t.classList.remove('mh-col-active'); });

                // ③ hover된 li의 a 태그에 active 스타일 부여
                var hoverLink = li.querySelector('a');
                if (hoverLink) hoverLink.classList.add('active');

                // ④ 대응하는 컬럼 타이틀 강조
                colTitles.forEach(function(t) {
                    if (t.textContent.trim() === colName) {
                        t.classList.add('mh-col-active');
                    }
                });
            });
        });

        // nav wrapper 밖으로 마우스 이탈 시 강조 제거 및 원래 active 복원
        if (navWrapper) {
            navWrapper.addEventListener('mouseleave', function() {
                // hover로 붙은 active 제거
                document.querySelectorAll('.mh-nav-menu > li > a.active').forEach(function(a) {
                    // was-active가 있으면 이건 hover용이므로 제거
                    a.classList.remove('active');
                });
                // 페이지 active 복원
                document.querySelectorAll('.mh-nav-menu > li > a.was-active').forEach(function(a) {
                    a.classList.add('active');
                    a.classList.remove('was-active');
                });
                // 컬럼 타이틀 강조 제거
                colTitles.forEach(function(t) { t.classList.remove('mh-col-active'); });
            });
        }
    })();

    /* ====================================================
       동적 햄버거 전환 — 메뉴가 한 줄 초과 시 즉시 전환
       ResizeObserver: 헤더/창 크기 변화 실시간 감지
       ==================================================== */
    (function() {
        var header  = document.querySelector('.modern-header');
        var navMenu = document.querySelector('.mh-nav-menu');
        if (!header || !navMenu) return;

        function checkOverflow() {
            // ── 모바일 + 태블릿(1024px 이하)은 항상 햄버거 모드 강제 적용 ──
            if (window.innerWidth <= 1024) {
                header.classList.add('use-hamburger');
                document.body.style.paddingTop = header.offsetHeight + 'px';
                return;
            }

            // mh-main 컨테이너 기준 (메뉴가 들어있는 row)
            var container = header.querySelector('.mh-main .mh-container');
            if (!container) return;

            // use-hamburger 없는 상태에서 측정해야 정확함
            var wasHamburger = header.classList.contains('use-hamburger');
            if (wasHamburger) {
                header.classList.remove('use-hamburger');
            }

            // nav의 실제 콘텐츠 너비 vs 컨테이너 가용 너비 비교
            var navWrapper = container.querySelector('.mh-nav-wrapper');
            var nav = navWrapper ? navWrapper.querySelector('nav') : container.querySelector('nav');
            if (!nav) {
                if (wasHamburger) header.classList.add('use-hamburger');
                return;
            }

            // li 항목들의 총 offsetWidth 합계 vs nav 가용 너비로 비교
            var totalMenuWidth = 0;
            navMenu.querySelectorAll(':scope > li').forEach(function(li) {
                totalMenuWidth += li.offsetWidth;
            });
            // navWrapper가 position:absolute이면 clientWidth가 부정확 →
            // 컨테이너 전체 너비에서 로고·우측버튼 영역을 빼서 가용 너비 계산
            var logoEl     = container.querySelector('.logo');
            var actionsEl  = container.querySelector('.mh-nav-actions');
            var logoW      = logoEl     ? logoEl.offsetWidth     : 120;
            var actionsW   = actionsEl  ? actionsEl.offsetWidth  : 200;
            var containerW = container.clientWidth;
            var navWidth   = containerW - logoW - actionsW - 80; /* 80px = 여유 여백 */
            var needsHamburger = totalMenuWidth > navWidth;

            if (needsHamburger) {
                header.classList.add('use-hamburger');
                document.body.style.paddingTop = header.offsetHeight + 'px';
            } else {
                header.classList.remove('use-hamburger');
                document.body.style.paddingTop = header.offsetHeight + 'px';
            }
        }

        // 초기 실행
        checkOverflow();

        // ResizeObserver: 헤더 크기 변화 감지 (창 크기 변경, 폰트 로드 등)
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function() {
                checkOverflow();
            });
            ro.observe(header);
        }

        // 추가 보험: resize 이벤트
        window.addEventListener('resize', checkOverflow);
        // 폰트 로드 완료 후 재측정
        window.addEventListener('load', checkOverflow);
    })();
});

// AI명함등록: PC → 알림, 모바일 Android → 앱 실행
function mhGoAICard() {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.indexOf('android') > -1) {
        try {
            AppScript.goCallbackCamerapApp('');
        } catch(e) {
            // 앱 미설치 시 인텐트로 실행
            if (ua.match(/chrome/)) {
                location.href = 'intent://onlyone#Intent;scheme=onlyoneapp;package=mms5.onepagebook.com.onlyonesms;end';
            } else {
                var iframe = document.createElement('iframe');
                iframe.style.visibility = 'hidden';
                iframe.src = 'onlyone://onlyoneapp';
                document.body.appendChild(iframe);
                document.body.removeChild(iframe);
            }
        }
    } else {
        alert('휴대폰에서 이용해주세요.');
    }
}

// logout 함수 (기존 시스템과 호환)
function logout() {
    if(confirm('로그아웃 하시겠습니까?')) {
        location.href = '/iam/ajax/logout.php';
    }
}

// 모바일 서브메뉴 토글
function toggleMobileSubmenu(element) {
    const parentLi = element.closest('.mh-mobile-item');
    const isActive = parentLi.classList.contains('active');
    
    // 모든 서브메뉴 닫기
    document.querySelectorAll('.mh-mobile-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // 클릭한 메뉴만 열기 (이미 열려있었으면 닫힘)
    if (!isActive) {
        parentLi.classList.add('active');
    }
}
</script>

<!-- 반응형 CSS: 2단 헤더 레이아웃 v3.0 -->
<style id="responsive-header-override">
/* ========================================
   공통 (모든 화면)
======================================== */
body {
    background: var(--bg-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* ========================================
   PC 모드 (769px 이상) — 2단 헤더
   Row1: .mh-top  (40px) — 유틸리티 바
   Row2: .mh-main (64px) — 로고 + 메뉴
======================================== */
@media (min-width: 1025px) {

    /* ── Row 1: 유틸리티 바 ── */
    .mh-top {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        min-height: 40px !important;
        padding: 8px 40px !important;   /* 좌우 동일 40px 고정 여백 */
        box-sizing: border-box !important;
        gap: 0 !important;
        visibility: visible !important;
        background: rgba(0,0,0,0.22) !important;
        border-bottom: 1px solid rgba(255,255,255,0.18) !important;
    }

    /* .mh-top 자체가 flex 컨테이너이므로 별도 inner 불필요 */

    /* 좌측 — 매뉴얼/공지사항 */
    .mh-top-left {
        display: flex !important;
        align-items: center !important;
        gap: 20px !important;
        flex-shrink: 0 !important;
        margin-left: 0 !important;       /* 로고 좌측점과 일치 */
    }

    /* 우측 — 회원가입/로그인/다크모드 */
    .mh-top-right {
        display: flex !important;
        align-items: center !important;
        gap: 1.2rem !important;
        flex-shrink: 0 !important;
        /* margin-left: auto 제거 — space-between이 자동 처리 */
    }

    /* ── PC 유틸리티 바 링크 스타일 ── */
    .mh-nav-link .icon { display: none !important; }
    .mh-nav-link .text {
        display: inline !important;
        font-size: 0.82rem !important;
        color: rgba(255,255,255,0.85) !important;
    }
    .mh-nav-link {
        padding: 0.2rem 0 !important;
        background: transparent !important;
        border-radius: 0 !important;
        width: auto !important;
        height: auto !important;
        text-decoration: none !important;
        transition: color 0.2s ease !important;
    }
    .mh-nav-link:hover { color: #fff !important; background: transparent !important; transform: none !important; }
    .mh-nav-link:hover .text { color: #fff !important; }
    .divider { display: inline !important; color: rgba(255,255,255,0.4) !important; margin: 0 0.2rem !important; }
    .user-info { display: inline !important; font-size: 0.82rem !important; color: rgba(255,255,255,0.8) !important; }

    /* ── Row 2: 메인 네비 ── */
    .mh-main {
        display: block !important;
    }

    /* ──────────────────────────────────────────
       PC Row2: 3-column Grid
       [로고(왼쪽고정)] [메뉴(중앙)] [버튼(오른쪽고정)]
    ────────────────────────────────────────── */
    .mh-main .mh-container {
        height: 68px !important;
        display: grid !important;
        grid-template-columns: auto 1fr auto !important;  /* 로고|메뉴|버튼 */
        align-items: center !important;
        padding: 0 40px !important;
        max-width: none !important;
        width: 100vw !important;
        margin: 0 !important;
        gap: 0 !important;
        box-sizing: border-box !important;
        position: static !important;   /* absolute 포지셔닝 불필요 */
    }

    /* 로고: 1번째 컬럼, 왼쪽 정렬 */
    .mh-main .mh-container .logo {
        grid-column: 1 !important;
        justify-self: start !important;
        z-index: 1 !important;
    }

    /* 메뉴 wrapper: 2번째 컬럼, 가로 중앙 정렬 */
    .mh-main .mh-container .mh-nav-wrapper {
        grid-column: 2 !important;
        justify-self: stretch !important;  /* 1fr 컬럼 전체 채우기 */
        display: flex !important;
        justify-content: center !important; /* 내부 nav를 가운데 정렬 */
        align-items: center !important;
        position: static !important;
        transform: none !important;
        left: auto !important;
        max-width: 100% !important;
        width: 100% !important;
        overflow: visible !important;
    }

    /* nav-wrapper 안의 nav도 중앙 정렬 */
    .mh-main .mh-container .mh-nav-wrapper > nav {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        flex: none !important;
    }

    /* 우측 버튼 그룹: 3번째 컬럼, 오른쪽 정렬 */
    .mh-main .mh-container .mh-nav-actions {
        grid-column: 3 !important;
        justify-self: end !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        flex-shrink: 0 !important;
        margin-left: 0 !important;     /* grid에서는 margin-left:auto 불필요 */
    }

    /* 모바일 햄버거/mobile-actions: PC에서 완전 숨김 */
    .mh-mobile-toggle { display: none !important; }
    .mh-mobile-actions,
    .modern-header.use-hamburger .mh-mobile-actions {
        display: none !important;
    }
}

/* ========================================
   모바일 모드 (768px 이하)
   헤더 = 단일 바: [햄버거] [로고] [회원가입/로그인/다크모드]
   유틸리티 바(.mh-top) 는 숨김
======================================== */
@media (max-width: 1024px) {
    html {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; overflow-x: hidden !important;
    }
    body {
        margin: 0 !important; padding: 0 !important;
        padding-top: 68px !important; /* 모바일 헤더 단일 바 높이 */
        width: 100% !important; overflow-x: hidden !important;
    }

    /* 헤더 고정 */
    .modern-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;   /* viewport 전체 폭 — body max-width 완전 무시 */
        margin: 0 !important;
        padding: 0 !important;
        z-index: 1000 !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3) !important;
        box-sizing: border-box !important;
    }

    /* 유틸리티 바 완전 숨김 (모바일) */
    .mh-top {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    /* 메인 네비만 표시 — 단일 바 */
    .mh-main {
        display: block !important;
    }

    .mh-main .mh-container {
        height: 68px !important;
        padding: 0 5% !important;          /* 유틸리티 바와 동일한 5% 여백 */
        margin: 0 !important;
        max-width: none !important;
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 0 !important;
        box-sizing: border-box !important;
    }

    /* 로고 */
    .logo { flex-shrink: 0 !important; }
    .logo img { height: 28px !important; }

    /* 햄버거 표시 — 오른쪽 끝 (mh-mobile-actions 내 마지막 아이템) */
    .mh-mobile-toggle {
        display: flex !important;
        width: 40px !important; height: 40px !important;
        order: 99;  /* 오른쪽 끝 */
        flex-shrink: 0 !important;
    }

    /* 모바일 우측 액션: 회원가입/로그인/다크모드 — 항상 far-right */
    .mh-top-right {
        display: flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        flex-shrink: 0 !important;
        /* margin-left: auto 제거 — space-between이 자동 처리 */
    }

    /* 모바일에서 유틸리티 링크는 숨김 (header-top-left) */
    .mh-top-left { display: none !important; }

    /* 모바일: 버튼 소형화 */
    .mh-btn {
        padding: 0.2rem 0.55rem !important;
        font-size: 0.72rem !important;
    }

    /* 모달 */
    .modal-content { width: 95%; max-width: none; border-radius: 16px; max-height: 95vh; }
    .modal-header { padding: 1rem 1.5rem; }
    .modal-header h2 { font-size: 1.5rem; }
    .modal-body iframe { height: 560px; }

    /* 데스크톱 메뉴 숨김 */
    .mh-nav-wrapper { display: none !important; }
    .mh-nav-menu { display: none !important; }

    /* 컨텐츠 여백 */
    .container, .main-content, .content-wrapper, .wrap, .sub_4_1 {
        width: 100% !important; max-width: 100% !important;
        padding-left: 0.75rem !important; padding-right: 0.75rem !important;
        margin-left: 0 !important; margin-right: 0 !important;
        box-sizing: border-box !important;
    }
    img, iframe, video { max-width: 100% !important; height: auto !important; }

    /* 모바일 메뉴 오버레이 */
    .mh-mobile-overlay { display: none !important; opacity: 0 !important; }
    .mh-mobile-overlay.active { display: block !important; opacity: 1 !important; }

    /* ── 모바일 이중 버튼 처리 ──
       PC: .header-top-right(Row1)에 버튼 표시, .header-mobile-actions(Row2) 숨김
       Mobile: .header-top-right(Row1) 전체 숨김, .header-mobile-actions(Row2)만 표시
    */
    /* 모바일: Row2 내 .mh-mobile-actions 표시 (오른쪽 끝 정렬) */
    .mh-mobile-actions {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-left: auto !important;  /* 로고 다음 오른쪽으로 밀기 */
        flex-shrink: 0 !important;
    }
    /* 모바일 컨테이너: 로고 왼쪽, 나머지 오른쪽 */
    .mh-main .mh-container {
        justify-content: flex-start !important;
    }
    .mh-main .mh-container nav {
        display: none !important;
    }
}

/* PC: Row2 내 중복 버튼(.mh-mobile-actions) 숨김 */
@media (min-width: 1025px) {
    .mh-mobile-actions {
        display: none !important;
    }
    /* PC: Row1 .mh-top-right 표시 (이미 위에서 정의됨) */
}
</style>

<!-- 공통 페이지 CSS: page-ma, page-sub 동일 적용 -->
<style id="sub-page-styles">
/* PC 모드: 텍스트 표시, 아이콘 숨김 */
@media (min-width: 1025px) {
    .page-ma .mh-nav-link .icon,
    .page-sub .mh-nav-link .icon {
        display: none;
    }
    
    .page-ma .mh-nav-link .text,
    .page-sub .mh-nav-link .text {
        display: inline;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.9);
    }
    
    .page-ma .divider,
    .page-sub .divider {
        display: inline;
        color: rgba(255,255,255,0.5);
        margin: 0 0.25rem;
    }
    
    .page-ma .mh-nav-link,
    .page-sub .mh-nav-link {
        width: auto;
        height: auto;
        background: transparent;
        border-radius: 0;
        padding: 0;
    }
}

/* 모바일 모드: 아이콘만 표시, 텍스트 숨김 */
@media (max-width: 1024px) {
    .page-ma .mh-nav-link .text,
    .page-sub .mh-nav-link .text {
        display: none;
    }
    
    .page-ma .mh-nav-link .icon,
    .page-sub .mh-nav-link .icon {
        display: block;
        font-size: 1rem;
    }
    
    .page-ma .divider,
    .page-sub .divider {
        display: none;
    }
    
    .page-ma .mh-nav-link,
    .page-sub .mh-nav-link {
        width: 28px;
        height: 28px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>

<style id="modern-footer-style">
/* ============================================
   Modern Footer Styles
   ============================================ */
.modern-footer {
    background: var(--header-bg, #1a1a2e);
    color: var(--header-text, rgba(255,255,255,0.85));
    padding: 3rem 2rem 1.5rem;
    transition: background 0.3s ease;
    margin-top: 3rem;
}

.modern-footer .footer-content {
    max-width: 1400px;
    margin: 0 auto;
}

.modern-footer .footer-links {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.modern-footer .footer-links a {
    color: var(--header-text, rgba(255,255,255,0.85));
    opacity: 0.8;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease, opacity 0.3s ease;
}

.modern-footer .footer-links a:hover {
    color: var(--primary-color, #82c736);
    opacity: 1;
}

.modern-footer .footer-info {
    font-size: 0.85rem;
    line-height: 1.9;
    opacity: 0.65;
}

@media (max-width: 1024px) {
    .modern-footer {
        padding: 2rem 1rem 1rem;
    }
    .modern-footer .footer-links {
        gap: 1rem;
        flex-direction: column;
    }
    .modern-footer .footer-info {
        font-size: 0.78rem;
    }
}

/* Hide old footer styles when modern footer is present */
.big_h_f, .foot_2 {
    display: none !important;
}
</style>

<!-- ============================================================
     작업3: 외부 CSS 충돌 방어 블록
     - responsive.css: header { display:block } 태그 선택자 방어
     - main.css: .header-gnb 등 구 헤더 선택자는 .modern-header 안에 없어 실제 충돌 없음
     - 모든 mh- 클래스는 이 블록에서만 정의되어 외부 CSS와 이름 충돌 없음
============================================================ -->
<style id="mh-defense-styles">
/* ── responsive.css의 'header { display:block }' 태그 선택자 방어 ── */
.modern-header {
    display: block !important;
    box-sizing: border-box !important;
}

/* ── 모든 mh- 하위 요소의 box-sizing 통일 ── */
.modern-header *,
.modern-header *::before,
.modern-header *::after {
    box-sizing: border-box;
}

/* ── 구 헤더 CSS 무력화 (main.css, responsive.css의 #header, .header-gnb 등) ──
   이 선택자들은 modern-header 안에 존재하지 않으므로 실제 영향 없음
   하지만 혹시 모를 상속 방어용으로 명시 */
.modern-header #header,
.modern-header .header-gnb,
.modern-header .ad_header,
.modern-header .head_right,
.modern-header .head_left,
.modern-header #mhSideMenu.mobile-gnb {
    all: unset;
    display: none !important;
}

/* ── 플로팅 그룹 z-index 보장 ── */
.mh-float-group {
    z-index: 9997 !important;
}

/* ── 모달 z-index 보장 (다른 스크립트가 덮지 않도록) ── */
.modal {
    z-index: 10000 !important;
}

/* ══════════════════════════════════════════
   ▣ 최우선 규칙 — 모든 style 블록보다 나중에 선언
   ══════════════════════════════════════════ */

/* ── 1. 헤더 컨테이너: 로고 왼쪽 / 액션+햄버거 오른쪽 ── */
@media (max-width: 1024px) {
    .mh-main .mh-container {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 0 4% !important;
        height: 60px !important;
        box-sizing: border-box !important;
    }
    /* 로고 왼쪽 고정 */
    .mh-main .mh-container .logo {
        flex-shrink: 0 !important;
        order: 0 !important;
    }
    /* nav 데스크톱 메뉴 숨김 */
    .mh-main .mh-container > nav {
        display: none !important;
    }
    /* 액션+햄버거 오른쪽 끝 */
    .mh-mobile-actions {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 8px !important;
        order: 1 !important;
        flex-shrink: 0 !important;
        margin-left: auto !important;
    }
    /* 햄버거 버튼 오른쪽 끝 */
    #mhNavToggle {
        display: flex !important;
        order: 99 !important;
        width: 44px !important;
        height: 44px !important;
        flex-shrink: 0 !important;
    }
    /* 로그인/회원가입 버튼 크기 */
    .mh-btn {
        font-size: 13px !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        white-space: nowrap !important;
    }
    /* 로고 이미지 크기 */
    .logo img {
        height: 30px !important;
    }
}

/* ── 2. 사이드 메뉴 — 폰트 & 터치 최적화 ── */
#mhSideMenu {
    display: block !important;
    position: fixed !important;
    top: 0 !important;
    left: -100% !important;
    width: min(320px, 85vw) !important;   /* 최대 320px, 화면 85% 이하 */
    height: 100dvh !important;            /* dynamic viewport height */
    z-index: 99999 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    visibility: hidden !important;
    transition: left 0.28s ease, visibility 0.28s ease !important;
    background: #1a1a2e !important;
    box-shadow: 4px 0 24px rgba(0,0,0,0.55) !important;
}
#mhSideMenu.active {
    left: 0 !important;
    visibility: visible !important;
}

/* 메뉴 헤더 */
#mhSideMenu .mh-mobile-header {
    padding: 16px 20px !important;
    background: #111827 !important;
    border-bottom: 1px solid rgba(255,255,255,0.12) !important;
}
#mhSideMenu .mh-mobile-header h3 {
    font-size: 18px !important;
    font-weight: 700 !important;
    color: #fff !important;
    margin: 0 !important;
}
#mhSideMenu .mh-mobile-close {
    width: 40px !important;
    height: 40px !important;
    font-size: 28px !important;
    color: rgba(255,255,255,0.8) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: transparent !important;
    border: none !important;
    cursor: pointer !important;
    border-radius: 6px !important;
}
#mhSideMenu .mh-mobile-close:hover {
    background: rgba(255,255,255,0.1) !important;
    color: #fff !important;
}

/* 1단계 메뉴 아이템 */
#mhSideMenu .mh-mobile-list {
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
}
#mhSideMenu .mh-mobile-item {
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
}
#mhSideMenu .mh-mobile-item > a {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 15px 20px !important;
    font-size: 16px !important;            /* ← 폰트 크게 */
    font-weight: 600 !important;
    color: rgba(255,255,255,0.92) !important;
    text-decoration: none !important;
    min-height: 52px !important;           /* 터치 영역 확보 */
    transition: background 0.2s !important;
}
#mhSideMenu .mh-mobile-item > a:hover,
#mhSideMenu .mh-mobile-item > a:active {
    background: rgba(255,255,255,0.07) !important;
    color: #82c736 !important;
}

/* 화살표 토글 */
#mhSideMenu .mh-submenu-toggle {
    font-size: 14px !important;
    opacity: 0.6 !important;
    transition: transform 0.25s !important;
    flex-shrink: 0 !important;
}
#mhSideMenu .mh-mobile-item.active .mh-submenu-toggle {
    transform: rotate(180deg) !important;
    opacity: 1 !important;
}

/* 2단계 서브메뉴 */
#mhSideMenu .mh-submenu {
    max-height: 0 !important;
    overflow: hidden !important;
    transition: max-height 0.3s ease !important;
    background: rgba(0,0,0,0.2) !important;
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
}
#mhSideMenu .mh-mobile-item.active .mh-submenu {
    max-height: 600px !important;
}
#mhSideMenu .mh-submenu li {
    list-style: none !important;
}
#mhSideMenu .mh-submenu a {
    display: block !important;
    padding: 12px 20px 12px 32px !important;
    font-size: 15px !important;            /* ← 서브메뉴도 충분히 크게 */
    font-weight: 400 !important;
    color: rgba(255,255,255,0.72) !important;
    text-decoration: none !important;
    min-height: 46px !important;
    transition: all 0.2s !important;
    display: flex !important;
    align-items: center !important;
    border-left: 2px solid transparent !important;
}
#mhSideMenu .mh-submenu a:hover,
#mhSideMenu .mh-submenu a:active {
    background: rgba(130,199,54,0.1) !important;
    color: #82c736 !important;
    border-left-color: #82c736 !important;
    padding-left: 36px !important;
}

/* 하단 푸터 영역 */
#mhSideMenu .mh-mobile-footer {
    padding: 16px 20px !important;
    background: rgba(0,0,0,0.15) !important;
    border-top: 1px solid rgba(255,255,255,0.1) !important;
}
#mhSideMenu .mh-mobile-footer a {
    display: block !important;
    padding: 11px 0 !important;
    font-size: 14px !important;
    color: rgba(255,255,255,0.6) !important;
    text-decoration: none !important;
    border-bottom: 1px solid rgba(255,255,255,0.07) !important;
    min-height: 44px !important;
    display: flex !important;
    align-items: center !important;
}
#mhSideMenu .mh-mobile-footer a:last-child {
    border-bottom: none !important;
}
#mhSideMenu .mh-mobile-footer a:hover {
    color: #82c736 !important;
}

/* ── 3. 오버레이 ── */
#mhNavOverlay {
    display: none !important;
    position: fixed !important;
    inset: 0 !important;
    background: rgba(0,0,0,0.6) !important;
    z-index: 99998 !important;
    backdrop-filter: blur(2px) !important;
}
#mhNavOverlay.active {
    display: block !important;
}

/* ── 4. 태블릿(601~1024px) 메뉴 너비 확장 ── */
@media (min-width: 601px) and (max-width: 1024px) {
    #mhSideMenu {
        width: min(380px, 75vw) !important;
    }
    #mhSideMenu .mh-mobile-item > a {
        font-size: 17px !important;
        padding: 16px 24px !important;
        min-height: 56px !important;
    }
    #mhSideMenu .mh-submenu a {
        font-size: 15px !important;
        padding: 13px 24px 13px 40px !important;
    }
    #mhSideMenu .mh-mobile-header h3 {
        font-size: 20px !important;
    }
    .mh-btn {
        font-size: 14px !important;
        padding: 7px 14px !important;
    }
    .logo img {
        height: 32px !important;
    }
}
</style>