<?php
/**
 * Board Detail API
 * GET /api/boards/detail.php?id=xxx - Get board detail
 * PUT /api/boards/detail.php?id=xxx - Update board
 * DELETE /api/boards/detail.php?id=xxx - Delete board
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Get board ID from query parameter
$boardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$boardId) {
    sendError('Board ID is required', 400);
}

// Route based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $boardId);
            break;
            
        case 'PUT':
            handlePut($pdo, $boardId);
            break;
            
        case 'DELETE':
            handleDelete($pdo, $boardId);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    logError('Error in board detail API: ' . $e->getMessage());
    sendError('An error occurred.', 500);
}

/**
 * GET - Get board detail
 */
function handleGet($pdo, $boardId) {
    try {
        // Get board detail
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.content,
                b.board_type,
                b.views,
                b.author_id,
                m.name as author_name,
                m.email as author_email,
                b.status,
                b.created_at,
                b.updated_at
            FROM boards b
            LEFT JOIN members m ON b.author_id = m.id
            WHERE b.id = ?
        ");
        $stmt->execute([$boardId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$board) {
            sendError('Board not found', 404);
        }
        
        // Increment view count
        $updateStmt = $pdo->prepare("UPDATE boards SET views = views + 1 WHERE id = ?");
        $updateStmt->execute([$boardId]);
        $board['views']++;
        
        // Get comments
        $commentStmt = $pdo->prepare("
            SELECT 
                c.id,
                c.content,
                c.author_id,
                m.name as author_name,
                c.created_at
            FROM comments c
            LEFT JOIN members m ON c.author_id = m.id
            WHERE c.board_id = ? AND c.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $commentStmt->execute([$boardId]);
        $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return response
        sendSuccess('Board retrieved successfully', [
            'board' => $board,
            'comments' => $comments
        ]);
        
    } catch (PDOException $e) {
        logError('Database error in GET board detail: ' . $e->getMessage());
        sendError('Failed to retrieve board', 500);
    }
}

/**
 * PUT - Update board
 */
function handlePut($pdo, $boardId) {
    // Check authentication
    $userId = checkAuth();
    
    try {
        // Check if board exists and user is author
        $stmt = $pdo->prepare("SELECT author_id FROM boards WHERE id = ?");
        $stmt->execute([$boardId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$board) {
            sendError('Board not found', 404);
        }
        
        if ($board['author_id'] != $userId) {
            sendError('You are not authorized to update this board', 403);
        }
        
        // Get input data
        $input = getJsonInput();
        
        // Sanitize input
        $title = isset($input['title']) ? sanitizeInput($input['title']) : null;
        $content = isset($input['content']) ? $input['content'] : null;
        
        // Build update query
        $updates = [];
        $params = [];
        
        if ($title) {
            $updates[] = "title = ?";
            $params[] = $title;
        }
        
        if ($content) {
            $updates[] = "content = ?";
            $params[] = $content;
        }
        
        if (empty($updates)) {
            sendError('No fields to update', 400);
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $boardId;
        
        // Update board
        $updateQuery = "UPDATE boards SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);
        
        // Get updated board
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.content,
                b.board_type,
                b.views,
                b.author_id,
                m.name as author_name,
                b.updated_at
            FROM boards b
            LEFT JOIN members m ON b.author_id = m.id
            WHERE b.id = ?
        ");
        $stmt->execute([$boardId]);
        $updatedBoard = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log activity
        logError("Board updated", ['user_id' => $userId, 'board_id' => $boardId]);
        
        // Return success response
        sendSuccess('Board updated successfully!', [
            'board' => $updatedBoard
        ]);
        
    } catch (PDOException $e) {
        logError('Database error in PUT board: ' . $e->getMessage());
        sendError('Failed to update board', 500);
    }
}

/**
 * DELETE - Delete board
 */
function handleDelete($pdo, $boardId) {
    // Check authentication
    $userId = checkAuth();
    
    try {
        // Check if board exists and user is author
        $stmt = $pdo->prepare("SELECT author_id FROM boards WHERE id = ?");
        $stmt->execute([$boardId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$board) {
            sendError('Board not found', 404);
        }
        
        if ($board['author_id'] != $userId) {
            sendError('You are not authorized to delete this board', 403);
        }
        
        // Soft delete (change status to 'deleted')
        $stmt = $pdo->prepare("UPDATE boards SET status = 'deleted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$boardId]);
        
        // Also soft delete comments
        $stmt = $pdo->prepare("UPDATE comments SET status = 'deleted' WHERE board_id = ?");
        $stmt->execute([$boardId]);
        
        // Log activity
        logError("Board deleted", ['user_id' => $userId, 'board_id' => $boardId]);
        
        // Return success response
        sendSuccess('Board deleted successfully!');
        
    } catch (PDOException $e) {
        logError('Database error in DELETE board: ' . $e->getMessage());
        sendError('Failed to delete board', 500);
    }
}
