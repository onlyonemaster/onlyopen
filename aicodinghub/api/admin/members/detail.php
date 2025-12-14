<?php
/**
 * Admin API - Get Member Detail
 * GET /api/admin/members/detail.php?id=123
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
    // Get member ID
    $memberId = $_GET['id'] ?? null;
    
    if (!$memberId) {
        sendError('Member ID is required', 400);
    }
    
    // Validate member ID
    if (!isValidMemberId($memberId)) {
        sendError('Invalid member ID', 404);
    }
    
    $pdo = getDBConnection();
    
    // Get member basic info
    $stmt = $pdo->prepare("
        SELECT 
            member_id,
            member_type,
            name,
            email,
            phone,
            status,
            created_at,
            updated_at
        FROM members
        WHERE member_id = ?
    ");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        sendError('Member not found', 404);
    }
    
    // Get profile based on member type
    $profile = null;
    
    switch ($member['member_type']) {
        case 'individual':
            $stmt = $pdo->prepare("
                SELECT *
                FROM developer_profiles
                WHERE member_id = ?
            ");
            $stmt->execute([$memberId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'company':
            $stmt = $pdo->prepare("
                SELECT *
                FROM company_profiles
                WHERE member_id = ?
            ");
            $stmt->execute([$memberId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'education':
            $stmt = $pdo->prepare("
                SELECT *
                FROM education_profiles
                WHERE member_id = ?
            ");
            $stmt->execute([$memberId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'team':
            $stmt = $pdo->prepare("
                SELECT *
                FROM teams
                WHERE leader_member_id = ?
            ");
            $stmt->execute([$memberId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
    }
    
    // Log action
    logAdminAction('view_member_detail', ['member_id' => $memberId]);
    
    // Return response
    sendSuccess('Member detail retrieved successfully', [
        'member' => $member,
        'profile' => $profile
    ]);
    
} catch (PDOException $e) {
    logError('Database error in admin member detail: ' . $e->getMessage());
    sendError('Failed to retrieve member details', 500);
} catch (Exception $e) {
    logError('Error in admin member detail: ' . $e->getMessage());
    sendError('An error occurred', 500);
}
