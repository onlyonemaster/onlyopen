<?php
// 사이트 기본 설정
define('SITE_NAME', '한국AI코딩허브협회');
define('SITE_URL', 'https://open.kiam.kr');
define('SITE_EMAIL', 'contact@open.kiam.kr');

// 경로 설정
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');

// 세션 설정
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 오류 표시 설정 (개발 환경)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// 데이터베이스 로드
require_once ROOT_PATH . '/config/database.php';

// 유틸리티 함수들
function redirectTo($url) {
    header("Location: " . SITE_URL . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['member_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    return $stmt->fetch();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('Y년 m월 d일', strtotime($date));
}
