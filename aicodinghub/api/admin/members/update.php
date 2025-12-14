<?php
/**
 * Admin API - Update Member
 * PUT /api/admin/members/update.php
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
    $memberId = $input['member_id'] ?? null;
    
    if (!$memberId) {
        sendError('Member ID is required', 400);
    }
    
    // Validate member ID
    if (!isValidMemberId($memberId)) {
        sendError('Invalid member ID', 404);
    }
    
    // Prevent admin from modifying themselves
    if ($memberId == $_SESSION['user_id']) {
        sendError('Cannot modify your own account through this endpoint', 403);
    }
    
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Prepare update fields
    $updateFields = [];
    $params = [];
    
    // Update basic fields
    if (isset($input['name'])) {
        $updateFields[] = 'name = ?';
        $params[] = sanitizeInput($input['name']);
    }
    
    if (isset($input['email'])) {
        $email = sanitizeInput($input['email']);
        if (!validateEmail($email)) {
            $pdo->rollBack();
            sendError('Invalid email format', 400);
        }
        $updateFields[] = 'email = ?';
        $params[] = $email;
    }
    
    if (isset($input['phone'])) {
        $updateFields[] = 'phone = ?';
        $params[] = sanitizeInput($input['phone']);
    }
    
    if (isset($input['status']) && in_array($input['status'], ['active', 'pending', 'inactive'])) {
        $updateFields[] = 'status = ?';
        $params[] = $input['status'];
    }
    
    // Update member table
    if (!empty($updateFields)) {
        $updateFields[] = 'updated_at = NOW()';
        $params[] = $memberId;
        
        $sql = "UPDATE members SET " . implode(', ', $updateFields) . " WHERE member_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    $pdo->commit();
    
    // Log action
    logAdminAction('update_member', [
        'member_id' => $memberId,
        'fields' => array_keys($input)
    ]);
    
    // Get updated member info
    $stmt = $pdo->prepare("
        SELECT member_id, name, email, phone, member_type, status, created_at, updated_at
        FROM members
        WHERE member_id = ?
    ");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess('Member updated successfully', ['member' => $member]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    logError('Database error in admin member update: ' . $e->getMessage());
    sendError('Failed to update member', 500);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    logError('Error in admin member update: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
