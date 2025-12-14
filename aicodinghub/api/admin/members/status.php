<?php
/**
 * Admin API - Change Member Status
 * POST /api/admin/members/status.php
 */

require_once __DIR__ . '/../admin_helper.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

try {
    // Get input data
    $input = getJsonInput();
    
    // Validate required fields
    $memberId = $input['member_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$memberId || !$status) {
        sendError('Member ID and status are required', 400);
    }
    
    // Validate status
    if (!in_array($status, ['active', 'pending', 'inactive'])) {
        sendError('Invalid status. Must be: active, pending, or inactive', 400);
    }
    
    // Validate member ID
    if (!isValidMemberId($memberId)) {
        sendError('Invalid member ID', 404);
    }
    
    // Prevent admin from changing their own status
    if ($memberId == $_SESSION['user_id']) {
        sendError('Cannot change your own account status', 403);
    }
    
    $pdo = getDBConnection();
    
    // Update status
    $stmt = $pdo->prepare("
        UPDATE members 
        SET status = ?, updated_at = NOW()
        WHERE member_id = ?
    ");
    $stmt->execute([$status, $memberId]);
    
    // Log action
    logAdminAction('change_member_status', [
        'member_id' => $memberId,
        'new_status' => $status
    ]);
    
    // Get updated member info
    $stmt = $pdo->prepare("
        SELECT member_id, name, email, status
        FROM members
        WHERE member_id = ?
    ");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess('Member status updated successfully', ['member' => $member]);
    
} catch (PDOException $e) {
    logError('Database error in admin member status: ' . $e->getMessage());
    sendError('Failed to update member status', 500);
} catch (Exception $e) {
    logError('Error in admin member status: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
