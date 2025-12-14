<?php
/**
 * API: Update Board
 * POST /api/boards/update.php
 * Body: {board_id, title, content, board_type, status}
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
    
    $board_id = $data['board_id'] ?? null;
    $title = $data['title'] ?? null;
    $content = $data['content'] ?? null;
    $board_type = $data['board_type'] ?? null;
    $status = $data['status'] ?? 'active';
    
    // Validate required fields
    if (!$board_id) {
        throw new Exception('게시글 ID가 필요합니다.');
    }
    
    if (!$title || strlen(trim($title)) < 2) {
        throw new Exception('제목은 최소 2자 이상이어야 합니다.');
    }
    
    if (!$content || strlen(trim($content)) < 10) {
        throw new Exception('내용은 최소 10자 이상이어야 합니다.');
    }
    
    // Validate board_type
    $valid_types = ['notice', 'news', 'qna', 'free', 'project_recruit', 'study'];
    if ($board_type && !in_array($board_type, $valid_types)) {
        throw new Exception('유효하지 않은 게시글 유형입니다.');
    }
    
    // Check if board exists and user has permission
    $stmt = $pdo->prepare("SELECT author_member_id FROM boards WHERE board_id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch();
    
    if (!$board) {
        throw new Exception('게시글을 찾을 수 없습니다.');
    }
    
    // Only admin or author can update
    if ($_SESSION['user_id'] != 1 && $board['author_member_id'] != $_SESSION['user_id']) {
        throw new Exception('게시글을 수정할 권한이 없습니다.');
    }
    
    // Build update query dynamically
    $updates = ['title = ?', 'content = ?', 'updated_at = CURRENT_TIMESTAMP'];
    $params = [$title, $content];
    
    if ($board_type) {
        $updates[] = 'board_type = ?';
        $params[] = $board_type;
    }
    
    if ($_SESSION['user_id'] == 1 && $status) {
        $updates[] = 'status = ?';
        $params[] = $status;
    }
    
    $params[] = $board_id;
    
    // Update board
    $sql = "UPDATE boards SET " . implode(', ', $updates) . " WHERE board_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Get updated board
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as author_name
        FROM boards b
        LEFT JOIN members m ON b.author_member_id = m.member_id
        WHERE b.board_id = ?
    ");
    $stmt->execute([$board_id]);
    $updatedBoard = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => '게시글이 수정되었습니다.',
        'board' => $updatedBoard
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
