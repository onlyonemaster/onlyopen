<?php
/**
 * Onlyone 통합 버전 관리 시스템 — 메인 라우터
 * 다중 사이트·다중 서버 지원
 * 접속: kiam.kr/vermanager
 *
 * 지원 환경:
 *   - Apache + mod_rewrite (.htaccess → ?route=xxx)
 *   - PHP 내장 서버 (REQUEST_URI 자동 분석)
 *
 * 사이트 선택:
 *   - URL 파라미터: ?site=kiam-prod
 *   - 없으면 첫 번째 활성 사이트 자동 선택
 *
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

require_once __DIR__ . '/config.php';

// ── 라우트 결정 ──
$route = $_GET['route'] ?? null;

if (!$route) {
    // PHP 내장 서버: REQUEST_URI에서 경로 추출
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = parse_url($uri, PHP_URL_PATH);
    $uri = rtrim($uri, '/');

    // /vermanager 접두사 제거
    $uri = preg_replace('#^/vermanager#', '', $uri);
    $uri = ltrim($uri, '/');
    $route = $uri ?: 'dashboard';
}

// index.php/xxx 패턴 정리 (PHP 내장 서버 흔적)
$route = preg_replace('#^index\.php/?#', '', $route);

// ── 현재 사이트 결정 ──
$current_site_id = get_current_site_id();
$current_site = $current_site_id ? get_site($current_site_id) : null;

// 템플릿에서 사용할 변수
$all_sites = get_active_sites();
$system_name = SYSTEM_NAME;
$app_slug = SYSTEM_SLUG;

// ── 라우팅 ──
switch ($route) {
    // ─── 페이지 ───
    case 'dashboard':
    case '':
        require __DIR__ . '/pages/dashboard.php';
        break;

    case 'release':
        require __DIR__ . '/pages/release.php';
        break;

    case 'history':
        require __DIR__ . '/pages/history.php';
        break;

    case 'improvements':
        require __DIR__ . '/pages/improvements.php';
        break;

    case 'changelog':
        require __DIR__ . '/pages/changelog.php';
        break;

    case 'settings':
        require __DIR__ . '/pages/settings.php';
        break;

    case 'sites':
        require __DIR__ . '/pages/sites.php';
        break;

    case 'site-detail':
        require __DIR__ . '/pages/site-detail.php';
        break;

    // ─── API ───
    case 'api/version':
        require __DIR__ . '/api/version.php';
        break;

    case 'api/release':
        require __DIR__ . '/api/release.php';
        break;

    case 'api/improvements':
        require __DIR__ . '/api/improvements.php';
        break;

    case 'api/history':
        require __DIR__ . '/api/history.php';
        break;

    case 'api/sites':
        require __DIR__ . '/api/sites.php';
        break;

    case 'api/remote':
        require __DIR__ . '/api/remote.php';
        break;

    case 'remote':
        require __DIR__ . '/pages/remote.php';
        break;

    case 'git':
        require __DIR__ . '/pages/git.php';
        break;

    case 'api/git':
        require __DIR__ . '/api/git.php';
        break;

    // ─── 404 ───
    default:
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — <?= SYSTEM_NAME ?></title>
<link rel="stylesheet" href="/vermanager/assets/css/style.css">
</head>
<body>
<div class="vm-layout">
<main class="vm-main" style="margin-left:0; display:flex; align-items:center; justify-content:center; min-height:100vh;">
<div style="text-align:center;">
  <div style="font-size:72px;">🔢</div>
  <h1 style="font-size:48px; margin:16px 0;">404</h1>
  <p style="color:var(--text2);">요청하신 페이지를 찾을 수 없습니다</p>
  <code style="background:var(--bg-card); padding:8px 16px; border-radius:8px; display:inline-block; margin:12px 0;"><?= htmlspecialchars($route) ?></code>
  <br>
  <a href="/vermanager/" class="vm-btn vm-btn-primary" style="margin-top:16px;">대시보드로 돌아가기</a>
</div>
</main>
</div>
</body>
</html>
        <?php
        break;
}