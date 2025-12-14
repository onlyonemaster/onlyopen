<?php
/**
 * Boards API
 * GET /api/boards/index.php - List boards
 * POST /api/boards/index.php - Create board
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Route based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
            
        case 'POST':
            handlePost($pdo);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    logError('Error in boards API: ' . $e->getMessage());
    sendError('An error occurred.', 500);
}

/**
 * GET - List boards with pagination and filters
 */
function handleGet($pdo) {
    try {
        // Get query parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;
        
        $boardType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'active';
        
        // Build query
        $where = ["status = ?"];
        $params = [$status];
        
        if ($boardType) {
            $where[] = "board_type = ?";
            $params[] = $boardType;
        }
        
        if ($search) {
            $where[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE $whereClause");
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();
        
        // Get boards
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.content,
                b.board_type,
                b.views,
                b.author_id,
                m.name as author_name,
                b.created_at,
                b.updated_at,
                (SELECT COUNT(*) FROM comments WHERE board_id = b.id AND status = 'active') as comment_count
            FROM boards b
            LEFT JOIN members m ON b.author_id = m.id
            WHERE $whereClause
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $boards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return response
        sendSuccess('Boards retrieved successfully', [
            'boards' => $boards,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]);
        
    } catch (PDOException $e) {
        logError('Database error in GET boards: ' . $e->getMessage());
        sendError('Failed to retrieve boards', 500);
    }
}

/**
 * POST - Create new board
 */
function handlePost($pdo) {
    // Check authentication
    $userId = checkAuth();
    
    // Rate limiting
    if (!checkRateLimit("create_board_$userId", 10, 3600)) {
        sendError('Too many posts. Please try again later.', 429);
    }
    
    try {
        // Get input data
        $input = getJsonInput();
        
        // Validate required fields
        $requiredFields = ['title', 'content', 'board_type'];
        $errors = validateRequired($input, $requiredFields);
        
        if (!empty($errors)) {
            sendError('Validation failed', 400, $errors);
        }
        
        // Sanitize input
        $title = sanitizeInput($input['title']);
        $content = $input['content']; // Don't sanitize content too much (allow HTML if needed)
        $boardType = sanitizeInput($input['board_type']);
        
        // Validate board type
        $allowedTypes = ['notice', 'news', 'qna'];
        if (!in_array($boardType, $allowedTypes)) {
            sendError('Invalid board type', 400);
        }
        
        // Insert new board
        $stmt = $pdo->prepare("
            INSERT INTO boards (
                title,
                content,
                board_type,
                author_id,
                status,
                views,
                created_at
            ) VALUES (?, ?, ?, ?, 'active', 0, NOW())
        ");
        
        $stmt->execute([
            $title,
            $content,
            $boardType,
            $userId
        ]);
        
        $boardId = $pdo->lastInsertId();
        
        // Get created board
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.content,
                b.board_type,
                b.views,
                b.author_id,
                m.name as author_name,
                b.created_at
            FROM boards b
            LEFT JOIN members m ON b.author_id = m.id
            WHERE b.id = ?
        ");
        $stmt->execute([$boardId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log activity
        logError("Board created: $title", ['user_id' => $userId, 'board_id' => $boardId]);
        
        // Return success response
        sendSuccess('Board created successfully!', [
            'board' => $board
        ], 201);
        
    } catch (PDOException $e) {
        logError('Database error in POST boards: ' . $e->getMessage());
        sendError('Failed to create board', 500);
    }
}
