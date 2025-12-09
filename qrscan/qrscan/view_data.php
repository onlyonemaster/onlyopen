<?php
require_once 'db.php';

$db = new Database();
$qrCodes = $db->getAllQRCodes();

echo "<h2>저장된 QR 코드 목록</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>제목</th><th>메모</th><th>링크</th><th>카테고리</th><th>생성일시</th></tr>";

foreach ($qrCodes as $qr) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($qr['id']) . "</td>";
    echo "<td>" . htmlspecialchars($qr['title']) . "</td>";
    echo "<td>" . htmlspecialchars($qr['memo']) . "</td>";
    echo "<td>" . htmlspecialchars($qr['link']) . "</td>";
    echo "<td>" . htmlspecialchars($qr['category']) . "</td>";
    echo "<td>" . htmlspecialchars($qr['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 