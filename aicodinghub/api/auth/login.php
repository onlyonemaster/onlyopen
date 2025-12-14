<?php
/**
 * User Login API
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Rate limiting
if (!checkRateLimit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 300)) {
    sendError('Too many login attempts. Please try again later.', 429);
}

try {
    // Get input data
    $input = getJsonInput();
    
    // Validate required fields
    $requiredFields = ['email', 'password'];
    $errors = validateRequired($input, $requiredFields);
    
    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }
    
    // Sanitize input
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $remember = isset($input['remember']) && $input['remember'] == true;
    
    // Validate email
    if (!validateEmail($email)) {
        sendError('Invalid email format', 400);
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Find user by email
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            email, 
            password, 
            member_type, 
            phone,
            status,
            email_verified_at,
            created_at
        FROM members 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        sendError('Invalid email or password', 401);
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        sendError('Invalid email or password', 401);
    }
    
    // Check if account is active
    if ($user['status'] !== 'active' && $user['status'] !== 'pending') {
        sendError('Account is inactive. Please contact support.', 403);
    }
    
    // Check if email is verified (allow pending users to login but notify)
    $emailVerified = !empty($user['email_verified_at']);
    
    // Create session
    setUserSession($user['id'], [
        'email' => $user['email'],
        'name' => $user['name'],
        'member_type' => $user['member_type']
    ]);
    
    // Generate session token (for API-based auth)
    $sessionToken = generateToken();
    
    // Update last login time
    $stmt = $pdo->prepare("UPDATE members SET last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log activity
    logError("User logged in: $email", ['user_id' => $user['id']]);
    
    // Remove sensitive data
    unset($user['password']);
    
    // Return success response
    sendSuccess('Login successful!', [
        'user' => $user,
        'session_token' => $sessionToken,
        'email_verified' => $emailVerified,
        'remember' => $remember
    ]);
    
} catch (PDOException $e) {
    logError('Database error in login: ' . $e->getMessage());
    sendError('Login failed. Please try again later.', 500);
} catch (Exception $e) {
    logError('Error in login: ' . $e->getMessage());
    sendError('An error occurred during login.', 500);
}
