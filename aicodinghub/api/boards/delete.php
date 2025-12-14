<?php
/**
 * API: Delete Board
 * DELETE /api/boards/delete.php?id={board_id}
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

// Only accept DELETE or POST method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get board ID from query string or POST data
    $board_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$board_id) {
        throw new Exception('게시글 ID가 필요합니다.');
    }
    
    // Check if board exists
    $stmt = $pdo->prepare("SELECT board_id FROM boards WHERE board_id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch();
    
    if (!$board) {
        throw new Exception('게시글을 찾을 수 없습니다.');
    }
    
    // Soft delete - set status to 'deleted'
    $stmt = $pdo->prepare("
        UPDATE boards 
        SET status = 'deleted', updated_at = CURRENT_TIMESTAMP 
        WHERE board_id = ?
    ");
    $stmt->execute([$board_id]);
    
    echo json_encode([
        'success' => true,
        'message' => '게시글이 삭제되었습니다.',
        'board_id' => $board_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
