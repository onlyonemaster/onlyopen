<?php
/**
 * Admin API - Get Festival Registration Detail
 * GET /api/admin/festival/detail.php?id=123
 */

require_once __DIR__ . '/../admin_helper.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

try {
    // Get registration ID
    $registrationId = $_GET['id'] ?? null;
    
    if (!$registrationId) {
        sendError('Registration ID is required', 400);
    }
    
    // Validate registration ID
    if (!isValidRegistrationId($registrationId)) {
        sendError('Invalid registration ID', 404);
    }
    
    $pdo = getDBConnection();
    
    // Get registration detail
    $stmt = $pdo->prepare("
        SELECT 
            fr.*,
            f.title as festival_title,
            f.description as festival_description,
            f.start_date,
            f.end_date
        FROM festival_registrations fr
        LEFT JOIN festivals f ON fr.festival_id = f.festival_id
        WHERE fr.registration_id = ?
    ");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        sendError('Registration not found', 404);
    }
    
    // Log action
    logAdminAction('view_festival_registration_detail', ['registration_id' => $registrationId]);
    
    sendSuccess('Registration detail retrieved successfully', [
        'registration' => $registration
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin festival detail: ' . $e->getMessage());
    sendError('Failed to retrieve registration details', 500);
} catch (Exception $e) {
    logError('Error in admin festival detail: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
