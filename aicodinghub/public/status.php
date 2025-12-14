<?php
echo "<h1>한국AI코딩허브협회 - 시스템 상태</h1>";
echo "<h2>✅ 서버 상태: 정상</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>Server Time: " . date('Y-m-d H:i:s') . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "</ul>";

echo "<h2>파일 구조</h2>";
$base_dir = dirname(__DIR__);
echo "<ul>";
echo "<li>Base Directory: $base_dir</li>";
echo "<li>Config exists: " . (file_exists($base_dir . '/config/config.php') ? 'YES ✅' : 'NO ❌') . "</li>";
echo "<li>Templates exist: " . (is_dir($base_dir . '/templates') ? 'YES ✅' : 'NO ❌') . "</li>";
echo "<li>Public exists: " . (is_dir($base_dir . '/public') ? 'YES ✅' : 'NO ❌') . "</li>";
echo "</ul>";

echo "<h2>MySQL 연결</h2>";
try {
    require_once dirname(__DIR__) . '/config/config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        echo "<p style='color: green;'>✅ MySQL 연결 성공!</p>";
        $stmt = $pdo->query("SELECT DATABASE()");
        $db = $stmt->fetchColumn();
        echo "<p>현재 데이터베이스: <strong>$db</strong></p>";
    } else {
        echo "<p style='color: orange;'>⚠️  MySQL 연결 실패 (DB 미생성 또는 권한 부족)</p>";
        echo "<p>사이트는 정상 작동하지만 DB 기능은 사용할 수 없습니다.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>페이지 테스트 링크</h2>";
echo "<ul>";
echo "<li><a href='/'>홈페이지</a></li>";
echo "<li><a href='/?page=about'>협회소개</a></li>";
echo "<li><a href='/?page=business'>사업안내</a></li>";
echo "<li><a href='/?page=platform'>허브플랫폼</a></li>";
echo "<li><a href='/?page=festival'>페스티벌</a></li>";
echo "</ul>";
?>
