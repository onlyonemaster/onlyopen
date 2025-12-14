<?php
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM festivals WHERE status IN ('upcoming', 'open') ORDER BY event_date ASC LIMIT 1");
$festival = $stmt->fetch();
?>

<div class="hero" style="padding: 60px 0;">
    <div class="container">
        <h1 style="text-align: center;">AI코딩 페스티벌</h1>
        <p style="text-align: center; font-size: 20px;">전국 최대 규모의 AI코딩 행사</p>
    </div>
</div>

<section>
    <div class="container">
        <?php if ($festival): ?>
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo htmlspecialchars($festival['title']); ?></h2>
            <p style="font-size: 18px; line-height: 1.8; margin-bottom: 30px;">
                <?php echo nl2br(htmlspecialchars($festival['description'])); ?>
            </p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                <div>
                    <strong>📅 일시:</strong> <?php echo formatDate($festival['event_date']); ?>
                </div>
                <div>
                    <strong>📍 장소:</strong> <?php echo htmlspecialchars($festival['location']); ?>
                </div>
                <div>
                    <strong>👥 정원:</strong> <?php echo number_format($festival['capacity']); ?>명
                </div>
                <div>
                    <strong>📝 신청:</strong> <?php echo formatDate($festival['registration_start']); ?> ~ <?php echo formatDate($festival['registration_end']); ?>
                </div>
            </div>
            <a href="?page=register" class="btn btn-primary" style="width: 100%; text-align: center; padding: 15px;">지금 신청하기</a>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center;">
            <h3>현재 예정된 페스티벌이 없습니다.</h3>
            <p>곧 새로운 페스티벌 소식을 전해드리겠습니다!</p>
        </div>
        <?php endif; ?>
    </div>
</section>
