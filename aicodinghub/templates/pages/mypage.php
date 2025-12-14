<?php
if (!isLoggedIn()) {
    redirectTo('?page=login');
}
$user = getCurrentUser();
?>

<div class="hero" style="padding: 60px 0;">
    <div class="container">
        <h1 style="text-align: center;">마이페이지</h1>
    </div>
</div>

<section>
    <div class="container">
        <div class="card">
            <h3>내 정보</h3>
            <p><strong>이름:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>이메일:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>회원 유형:</strong> <?php echo htmlspecialchars($user['member_type']); ?></p>
            <p><strong>가입일:</strong> <?php echo formatDate($user['created_at']); ?></p>
        </div>
    </div>
</section>
