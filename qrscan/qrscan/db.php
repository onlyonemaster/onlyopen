<?php
class Database {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3('/home/open/qrscan/qr_codes.db');
        $this->createTables();
    }
    
    private function createTables() {
        // 사용자 테이블 생성
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // QR 코드 테이블에 user_id 컬럼 추가
        $this->db->exec("CREATE TABLE IF NOT EXISTS qr_codes_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT,
            memo TEXT,
            link TEXT,
            category TEXT,
            qr_image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        
        // 기존 데이터 마이그레이션
        $this->db->exec("INSERT OR IGNORE INTO qr_codes_new (title, memo, link, category, qr_image, created_at)
                        SELECT title, memo, link, category, qr_image, created_at FROM qr_codes");
        
        // 기존 테이블 교체
        $this->db->exec("DROP TABLE IF EXISTS qr_codes");
        $this->db->exec("ALTER TABLE qr_codes_new RENAME TO qr_codes");
    }
    
    public function registerUser($username, $password) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        return $stmt->execute();
    }
    
    public function loginUser($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    public function addQRCode($userId, $title, $memo, $link, $category, $qrImage) {
        $stmt = $this->db->prepare("INSERT INTO qr_codes (user_id, title, memo, link, category, qr_image) 
                                   VALUES (:user_id, :title, :memo, :link, :category, :qr_image)");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':memo', $memo, SQLITE3_TEXT);
        $stmt->bindValue(':link', $link, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':qr_image', $qrImage, SQLITE3_TEXT);
        return $stmt->execute();
    }
    
    public function getQRCodesByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM qr_codes WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $qrCodes = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $qrCodes[] = $row;
        }
        return $qrCodes;
    }
    
    // 기존 메소드들 수정
    public function updateQRCode($id, $userId, $title, $memo, $link, $category, $qrImage) {
        $stmt = $this->db->prepare("UPDATE qr_codes 
                                   SET title = :title, memo = :memo, link = :link, 
                                       category = :category, qr_image = :qr_image 
                                   WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':memo', $memo, SQLITE3_TEXT);
        $stmt->bindValue(':link', $link, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':qr_image', $qrImage, SQLITE3_TEXT);
        return $stmt->execute();
    }
    
    public function deleteQRCode($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM qr_codes WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        return $stmt->execute();
    }
    
    public function searchQRCodes($userId, $searchTerm) {
        $searchTerm = "%$searchTerm%";
        $stmt = $this->db->prepare("SELECT * FROM qr_codes 
                                   WHERE user_id = :user_id 
                                   AND (title LIKE :term OR memo LIKE :term OR category LIKE :term)
                                   ORDER BY created_at DESC");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':term', $searchTerm, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $qrCodes = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $qrCodes[] = $row;
        }
        return $qrCodes;
    }
}
?> 