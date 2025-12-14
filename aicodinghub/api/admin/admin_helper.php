<?php
/**
 * Admin Helper Functions
 * Common utilities for admin API endpoints
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1;
}

/**
 * Require admin authentication
 * Sends error response and exits if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        sendError('Unauthorized. Admin access required.', 403);
    }
}

/**
 * Get admin user info
 * @return array|null
 */
function getAdminInfo() {
    if (!isAdmin()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT member_id, name, email, member_type
        FROM members
        WHERE member_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Log admin action
 * @param string $action Action description
 * @param array $data Additional data
 */
function logAdminAction($action, $data = []) {
    if (!isAdmin()) {
        return;
    }
    
    $pdo = getDBConnection();
    $adminId = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, data, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $adminId,
            $action,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Log table might not exist, just log to error log
        error_log("Admin action log failed: " . $e->getMessage());
    }
}

/**
 * Validate member ID
 * @param int $memberId
 * @return bool
 */
function isValidMemberId($memberId) {
    if (!is_numeric($memberId) || $memberId <= 0) {
        return false;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Validate board ID
 * @param int $boardId
 * @return bool
 */
function isValidBoardId($boardId) {
    if (!is_numeric($boardId) || $boardId <= 0) {
        return false;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE board_id = ?");
    $stmt->execute([$boardId]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Validate festival registration ID
 * @param int $registrationId
 * @return bool
 */
function isValidRegistrationId($registrationId) {
    if (!is_numeric($registrationId) || $registrationId <= 0) {
        return false;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM festival_registrations WHERE registration_id = ?");
    $stmt->execute([$registrationId]);
    
    return $stmt->fetchColumn() > 0;
}
