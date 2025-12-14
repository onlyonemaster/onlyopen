<?php
/**
 * User Logout API
 * GET/POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();

// Allow both GET and POST methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    setJsonHeader();
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
    
    // If GET request, redirect to home page
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: /?page=home&logout=success');
        exit;
    }
    
    // If POST request, return JSON response
    setJsonHeader();
    sendSuccess('Logout successful!');
    
} catch (Exception $e) {
    logError('Error in logout: ' . $e->getMessage());
    
    // If GET request, redirect with error
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: /?page=home&logout=error');
        exit;
    }
    
    // If POST request, return JSON error
    setJsonHeader();
    sendError('An error occurred during logout.', 500);
}
