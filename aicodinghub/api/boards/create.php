<?php
/**
 * API: Create Board
 * POST /api/boards/create.php
 * Body: {title, content, board_type}
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// Only accept POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $title = $data['title'] ?? null;
    $content = $data['content'] ?? null;
    $board_type = $data['board_type'] ?? 'free';
    
    // Validate required fields
    if (!$title || strlen(trim($title)) < 2) {
        throw new Exception('제목은 최소 2자 이상이어야 합니다.');
    }
    
    if (!$content || strlen(trim($content)) < 10) {
        throw new Exception('내용은 최소 10자 이상이어야 합니다.');
    }
    
    // Validate board_type
    $valid_types = ['notice', 'news', 'qna', 'free', 'project_recruit', 'study'];
    if (!in_array($board_type, $valid_types)) {
        throw new Exception('유효하지 않은 게시글 유형입니다.');
    }
    
    // Only admin can create notice
    if ($board_type === 'notice' && $_SESSION['user_id'] != 1) {
        throw new Exception('공지사항은 관리자만 작성할 수 있습니다.');
    }
    
    // Insert new board
    $stmt = $pdo->prepare("
        INSERT INTO boards (board_type, title, content, author_member_id, views, likes, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 0, 0, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$board_type, $title, $content, $_SESSION['user_id']]);
    
    $board_id = $pdo->lastInsertId();
    
    // Get created board
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as author_name
        FROM boards b
        LEFT JOIN members m ON b.author_member_id = m.member_id
        WHERE b.board_id = ?
    ");
    $stmt->execute([$board_id]);
    $newBoard = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => '게시글이 작성되었습니다.',
        'board' => $newBoard
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
