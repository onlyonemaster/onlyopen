<?php
/**
 * Admin API - Delete Member
 * DELETE /api/admin/members/delete.php
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
    $memberId = $input['member_id'] ?? null;
    
    if (!$memberId) {
        sendError('Member ID is required', 400);
    }
    
    // Validate member ID
    if (!isValidMemberId($memberId)) {
        sendError('Invalid member ID', 404);
    }
    
    // Prevent admin from deleting themselves
    if ($memberId == $_SESSION['user_id']) {
        sendError('Cannot delete your own account', 403);
    }
    
    $pdo = getDBConnection();
    
    // Get member info before deletion
    $stmt = $pdo->prepare("SELECT name, email FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        sendError('Member not found', 404);
    }
    
    // Delete member (cascading delete will handle related records)
    $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    
    // Log action
    logAdminAction('delete_member', [
        'member_id' => $memberId,
        'name' => $member['name'],
        'email' => $member['email']
    ]);
    
    sendSuccess('Member deleted successfully', [
        'member_id' => $memberId,
        'name' => $member['name']
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin member delete: ' . $e->getMessage());
    sendError('Failed to delete member', 500);
} catch (Exception $e) {
    logError('Error in admin member delete: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
