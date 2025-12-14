<?php
/**
 * Profile Avatar Upload API
 * POST /api/profile/avatar.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();

// Check authentication
$userId = checkAuth();

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setJsonHeader();
    sendError('Method not allowed', 405);
}

// Rate limiting
if (!checkRateLimit("upload_avatar_$userId", 10, 3600)) {
    setJsonHeader();
    sendError('Too many upload attempts. Please try again later.', 429);
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        setJsonHeader();
        sendError('No file uploaded or upload error', 400);
    }
    
    $file = $_FILES['avatar'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $uploadResult = uploadFile($file, $allowedTypes, $maxSize);
    
    if (!$uploadResult['success']) {
        setJsonHeader();
        sendError($uploadResult['error'], 400);
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get old avatar to delete
    $stmt = $pdo->prepare("SELECT profile_image FROM members WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();
    
    // Update profile image in database
    $stmt = $pdo->prepare("UPDATE members SET profile_image = ? WHERE id = ?");
    $stmt->execute([$uploadResult['path'], $userId]);
    
    // Delete old avatar file if exists
    if ($oldAvatar) {
        $oldAvatarPath = __DIR__ . '/../../public' . $oldAvatar;
        if (file_exists($oldAvatarPath)) {
            @unlink($oldAvatarPath);
        }
    }
    
    // Log activity
    logError("Profile avatar updated", ['user_id' => $userId, 'filename' => $uploadResult['filename']]);
    
    // Return success response
    setJsonHeader();
    sendSuccess('Avatar uploaded successfully!', [
        'avatar_url' => $uploadResult['path']
    ]);
    
} catch (PDOException $e) {
    setJsonHeader();
    logError('Database error in avatar upload: ' . $e->getMessage());
    sendError('Failed to update avatar', 500);
} catch (Exception $e) {
    setJsonHeader();
    logError('Error in avatar upload: ' . $e->getMessage());
    sendError('An error occurred during upload.', 500);
}
