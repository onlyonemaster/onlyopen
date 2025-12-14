<?php
/**
 * Admin API - Update Board
 * PUT /api/admin/boards/update.php
 */

require_once __DIR__ . '/../admin_helper.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow PUT/POST method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

try {
    // Get input data
    $input = getJsonInput();
    
    // Validate required fields
    $boardId = $input['board_id'] ?? null;
    
    if (!$boardId) {
        sendError('Board ID is required', 400);
    }
    
    // Validate board ID
    if (!isValidBoardId($boardId)) {
        sendError('Invalid board ID', 404);
    }
    
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Prepare update fields
    $updateFields = [];
    $params = [];
    
    if (isset($input['title'])) {
        $updateFields[] = 'title = ?';
        $params[] = sanitizeInput($input['title']);
    }
    
    if (isset($input['content'])) {
        $updateFields[] = 'content = ?';
        $params[] = $input['content']; // Keep HTML
    }
    
    if (isset($input['type']) && in_array($input['type'], ['notice', 'news', 'qna'])) {
        $updateFields[] = 'type = ?';
        $params[] = $input['type'];
    }
    
    if (isset($input['status']) && in_array($input['status'], ['active', 'inactive'])) {
        $updateFields[] = 'status = ?';
        $params[] = $input['status'];
    }
    
    // Update board
    if (!empty($updateFields)) {
        $updateFields[] = 'updated_at = NOW()';
        $params[] = $boardId;
        
        $sql = "UPDATE boards SET " . implode(', ', $updateFields) . " WHERE board_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    $pdo->commit();
    
    // Log action
    logAdminAction('update_board', [
        'board_id' => $boardId,
        'fields' => array_keys($input)
    ]);
    
    // Get updated board info
    $stmt = $pdo->prepare("
        SELECT board_id, title, type, status, views, created_at, updated_at
        FROM boards
        WHERE board_id = ?
    ");
    $stmt->execute([$boardId]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess('Board updated successfully', ['board' => $board]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    logError('Database error in admin board update: ' . $e->getMessage());
    sendError('Failed to update board', 500);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    logError('Error in admin board update: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
