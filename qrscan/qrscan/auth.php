<?php
session_start();
require_once 'db.php';

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($user = $db->loginUser($username, $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        header('Location: login.php?error=1');
        exit;
    }
}

// 로그아웃
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 로그인 체크
if (!isset($_SESSION['user_id']) && !strpos($_SERVER['REQUEST_URI'], 'login.php')) {
    header('Location: login.php');
    exit;
}
?> 