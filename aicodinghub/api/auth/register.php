<?php
/**
 * User Registration API
 * POST /api/auth/register.php
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
if (!checkRateLimit('register_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 300)) {
    sendError('Too many registration attempts. Please try again later.', 429);
}

try {
    // Get input data
    $input = getJsonInput();
    
    // Validate required fields
    $requiredFields = ['name', 'email', 'password', 'member_type'];
    $errors = validateRequired($input, $requiredFields);
    
    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }
    
    // Sanitize input
    $name = sanitizeInput($input['name']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $memberType = sanitizeInput($input['member_type']);
    $phone = isset($input['phone']) ? sanitizeInput($input['phone']) : null;
    
    // Validate email
    if (!validateEmail($email)) {
        sendError('Invalid email format', 400);
    }
    
    // Validate password strength
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) {
        sendError('Password validation failed', 400, ['password' => $passwordErrors]);
    }
    
    // Validate member type
    $allowedTypes = ['individual', 'company', 'education', 'team'];
    if (!in_array($memberType, $allowedTypes)) {
        sendError('Invalid member type', 400);
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        sendError('Email already registered', 400);
    }
    
    // Hash password
    $passwordHash = hashPassword($password);
    
    // Generate verification token
    $verificationToken = generateToken();
    
    // Insert new member
    $stmt = $pdo->prepare("
        INSERT INTO members (
            name, 
            email, 
            password, 
            member_type, 
            phone, 
            email_verification_token,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $passwordHash,
        $memberType,
        $phone,
        $verificationToken
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Create profile based on member type
    if ($memberType === 'individual') {
        $stmt = $pdo->prepare("
            INSERT INTO developer_profiles (member_id, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$userId]);
    } elseif ($memberType === 'company') {
        $stmt = $pdo->prepare("
            INSERT INTO company_profiles (member_id, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$userId]);
    } elseif ($memberType === 'team') {
        $stmt = $pdo->prepare("
            INSERT INTO teams (name, leader_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$name, $userId]);
    }
    
    // TODO: Send verification email
    // For now, we'll just return the token (in production, send via email)
    
    // Log activity
    logError("New user registered: $email", ['user_id' => $userId, 'member_type' => $memberType]);
    
    // Return success response
    sendSuccess('Registration successful! Please check your email to verify your account.', [
        'user_id' => $userId,
        'name' => $name,
        'email' => $email,
        'member_type' => $memberType,
        'verification_required' => true,
        // In development only - remove in production
        'verification_token' => $verificationToken,
        'verification_url' => SITE_URL . "/api/auth/verify-email.php?token=$verificationToken"
    ], 201);
    
} catch (PDOException $e) {
    logError('Database error in registration: ' . $e->getMessage());
    sendError('Registration failed. Please try again later.', 500);
} catch (Exception $e) {
    logError('Error in registration: ' . $e->getMessage());
    sendError('An error occurred during registration.', 500);
}
