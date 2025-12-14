<?php
/**
 * User Logout API
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Get current user (if logged in)
    $userId = getCurrentUserId();
    
    if ($userId) {
        logError("User logged out", ['user_id' => $userId]);
    }
    
    // Destroy session
    destroyUserSession();
    
    // Return success response
    sendSuccess('Logout successful!');
    
} catch (Exception $e) {
    logError('Error in logout: ' . $e->getMessage());
    sendError('An error occurred during logout.', 500);
}
