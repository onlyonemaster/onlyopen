<?php
/**
 * Admin API - Delete Board
 * DELETE /api/admin/boards/delete.php
 */

require_once __DIR__ . '/../admin_helper.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow DELETE/POST method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Get board info before deletion
    $stmt = $pdo->prepare("SELECT title, type FROM boards WHERE board_id = ?");
    $stmt->execute([$boardId]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$board) {
        sendError('Board not found', 404);
    }
    
    // Delete board (cascading delete will handle comments)
    $stmt = $pdo->prepare("DELETE FROM boards WHERE board_id = ?");
    $stmt->execute([$boardId]);
    
    // Log action
    logAdminAction('delete_board', [
        'board_id' => $boardId,
        'title' => $board['title'],
        'type' => $board['type']
    ]);
    
    sendSuccess('Board deleted successfully', [
        'board_id' => $boardId,
        'title' => $board['title']
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin board delete: ' . $e->getMessage());
    sendError('Failed to delete board', 500);
} catch (Exception $e) {
    logError('Error in admin board delete: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
