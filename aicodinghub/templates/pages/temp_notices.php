<?php
// 최신 공지사항 가져오기
$notices = safeDBCall(function($pdo) {
    $stmt = $pdo->query("SELECT * FROM boards WHERE board_type = 'notice' AND status = 'active' ORDER BY created_at DESC LIMIT 3");
    return $stmt->fetchAll();
}, []);
?>
