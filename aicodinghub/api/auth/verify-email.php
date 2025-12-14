<?php
/**
 * Email Verification API
 * GET /api/auth/verify-email.php?token=xxx
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Get token from query parameter
    $token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : null;
    
    if (!$token) {
        sendError('Verification token is required', 400);
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Find user by verification token
    $stmt = $pdo->prepare("
        SELECT id, name, email, status, email_verified_at 
        FROM members 
        WHERE email_verification_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        sendError('Invalid or expired verification token', 400);
    }
    
    // Check if already verified
    if ($user['email_verified_at']) {
        sendSuccess('Email already verified!', [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'verified_at' => $user['email_verified_at']
            ]
        ]);
    }
    
    // Update user status
    $stmt = $pdo->prepare("
        UPDATE members 
        SET 
            email_verified_at = NOW(),
            email_verification_token = NULL,
            status = 'active'
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Log activity
    logError("Email verified: {$user['email']}", ['user_id' => $user['id']]);
    
    // Return success response
    sendSuccess('Email verified successfully! You can now login.', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ],
        'redirect_url' => SITE_URL . '/?page=login'
    ]);
    
} catch (PDOException $e) {
    logError('Database error in email verification: ' . $e->getMessage());
    sendError('Verification failed. Please try again later.', 500);
} catch (Exception $e) {
    logError('Error in email verification: ' . $e->getMessage());
    sendError('An error occurred during verification.', 500);
}
