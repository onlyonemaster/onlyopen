<?php
/**
 * Admin API - Reject Festival Registration
 * POST /api/admin/festival/reject.php
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
    $registrationId = $input['registration_id'] ?? null;
    $reason = $input['reason'] ?? '';
    
    if (!$registrationId) {
        sendError('Registration ID is required', 400);
    }
    
    if (empty($reason)) {
        sendError('Rejection reason is required', 400);
    }
    
    // Validate registration ID
    if (!isValidRegistrationId($registrationId)) {
        sendError('Invalid registration ID', 404);
    }
    
    $pdo = getDBConnection();
    
    // Get current registration info
    $stmt = $pdo->prepare("
        SELECT name, email, status
        FROM festival_registrations
        WHERE registration_id = ?
    ");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        sendError('Registration not found', 404);
    }
    
    // Check if already rejected
    if ($registration['status'] === 'rejected') {
        sendError('Registration is already rejected', 400);
    }
    
    // Update status to rejected
    $stmt = $pdo->prepare("
        UPDATE festival_registrations
        SET status = 'rejected',
            rejected_at = NOW(),
            rejected_by = ?,
            rejection_reason = ?,
            updated_at = NOW()
        WHERE registration_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $reason, $registrationId]);
    
    // Log action
    logAdminAction('reject_festival_registration', [
        'registration_id' => $registrationId,
        'name' => $registration['name'],
        'email' => $registration['email'],
        'reason' => $reason
    ]);
    
    // Get updated registration
    $stmt = $pdo->prepare("
        SELECT registration_id, name, email, status, rejected_at, rejection_reason
        FROM festival_registrations
        WHERE registration_id = ?
    ");
    $stmt->execute([$registrationId]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess('Registration rejected successfully', [
        'registration' => $updated
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin festival reject: ' . $e->getMessage());
    sendError('Failed to reject registration', 500);
} catch (Exception $e) {
    logError('Error in admin festival reject: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
