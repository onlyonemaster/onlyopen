<?php
// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_NAME', 'korea_aihub');
define('DB_USER', 'root');
define('DB_PASS', 'onlyOpen12dbdb');
define('DB_CHARSET', 'utf8mb4');

/**
 * 데이터베이스 연결 함수
 * @return PDO|null 연결 성공시 PDO 객체, 실패시 null 반환
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // DB 연결 실패시 null 반환 (개발 초기에는 DB 없이도 페이지 표시)
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * 안전한 DB 호출 함수
 * @param callable $callback DB 작업 콜백 함수
 * @param mixed $default DB 연결 실패시 반환할 기본값
 * @return mixed
 */
function safeDBCall(callable $callback, $default = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return $default;
    }
    
    try {
        return $callback($pdo);
    } catch (Exception $e) {
        error_log("DB Query Error: " . $e->getMessage());
        return $default;
    }
}
