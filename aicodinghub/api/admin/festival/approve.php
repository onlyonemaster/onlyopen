<?php
/**
 * Admin API - Approve Festival Registration
 * POST /api/admin/festival/approve.php
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
    $note = $input['note'] ?? '';
    
    if (!$registrationId) {
        sendError('Registration ID is required', 400);
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
    
    // Check if already approved
    if ($registration['status'] === 'approved') {
        sendError('Registration is already approved', 400);
    }
    
    // Update status to approved
    $stmt = $pdo->prepare("
        UPDATE festival_registrations
        SET status = 'approved',
            approved_at = NOW(),
            approved_by = ?,
            admin_note = ?,
            updated_at = NOW()
        WHERE registration_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $note, $registrationId]);
    
    // Log action
    logAdminAction('approve_festival_registration', [
        'registration_id' => $registrationId,
        'name' => $registration['name'],
        'email' => $registration['email']
    ]);
    
    // Get updated registration
    $stmt = $pdo->prepare("
        SELECT registration_id, name, email, status, approved_at
        FROM festival_registrations
        WHERE registration_id = ?
    ");
    $stmt->execute([$registrationId]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess('Registration approved successfully', [
        'registration' => $updated
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin festival approve: ' . $e->getMessage());
    sendError('Failed to approve registration', 500);
} catch (Exception $e) {
    logError('Error in admin festival approve: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
