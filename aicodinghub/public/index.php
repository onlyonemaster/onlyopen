<?php
require_once dirname(__DIR__) . '/config/config.php';

// 간단한 라우팅
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'about', 'business', 'platform', 'festival', 'board', 'contact', 'login', 'register', 'mypage', 'member'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

$page_file = dirname(__DIR__) . '/templates/pages/' . $page . '.php';

if (file_exists($page_file)) {
    include $page_file;
} else {
    http_response_code(404);
    echo "<h1>404 - 페이지를 찾을 수 없습니다</h1>";
}
