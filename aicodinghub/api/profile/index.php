<?php
/**
 * Profile API
 * GET /api/profile/index.php - Get current user profile
 * PUT /api/profile/index.php - Update profile
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

// Enable CORS and set JSON header
enableCORS();
setJsonHeader();

// Check authentication
$userId = checkAuth();

// Route based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $userId);
            break;
            
        case 'PUT':
            handlePut($pdo, $userId);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    logError('Error in profile API: ' . $e->getMessage());
    sendError('An error occurred.', 500);
}

/**
 * GET - Get user profile
 */
function handleGet($pdo, $userId) {
    try {
        // Get user basic info
        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                email,
                member_type,
                phone,
                profile_image,
                status,
                email_verified_at,
                created_at,
                last_login_at
            FROM members
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        // Get profile based on member type
        $profile = null;
        
        if ($user['member_type'] === 'individual') {
            $stmt = $pdo->prepare("
                SELECT 
                    bio,
                    skills,
                    experience_years,
                    github_url,
                    portfolio_url,
                    hourly_rate
                FROM developer_profiles
                WHERE member_id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Parse skills JSON
            if ($profile && $profile['skills']) {
                $profile['skills'] = json_decode($profile['skills'], true);
            }
        } elseif ($user['member_type'] === 'company') {
            $stmt = $pdo->prepare("
                SELECT 
                    company_name,
                    business_number,
                    industry,
                    company_size,
                    website_url,
                    description
                FROM company_profiles
                WHERE member_id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get statistics
        $stats = [];
        
        // Count projects
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
            FROM projects
            WHERE (client_id = ? OR EXISTS (
                SELECT 1 FROM project_matches pm 
                WHERE pm.project_id = projects.id AND pm.developer_id = ?
            ))
        ");
        $stmt->execute([$userId, $userId]);
        $stats['projects'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Count boards
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE author_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $stats['boards'] = $stmt->fetchColumn();
        
        // Return response
        sendSuccess('Profile retrieved successfully', [
            'user' => $user,
            'profile' => $profile,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        logError('Database error in GET profile: ' . $e->getMessage());
        sendError('Failed to retrieve profile', 500);
    }
}

/**
 * PUT - Update profile
 */
function handlePut($pdo, $userId) {
    // Rate limiting
    if (!checkRateLimit("update_profile_$userId", 20, 3600)) {
        sendError('Too many update attempts. Please try again later.', 429);
    }
    
    try {
        // Get input data
        $input = getJsonInput();
        
        // Get user member type
        $stmt = $pdo->prepare("SELECT member_type FROM members WHERE id = ?");
        $stmt->execute([$userId]);
        $memberType = $stmt->fetchColumn();
        
        if (!$memberType) {
            sendError('User not found', 404);
        }
        
        // Update basic member info
        $memberUpdates = [];
        $memberParams = [];
        
        if (isset($input['name'])) {
            $memberUpdates[] = "name = ?";
            $memberParams[] = sanitizeInput($input['name']);
        }
        
        if (isset($input['phone'])) {
            $memberUpdates[] = "phone = ?";
            $memberParams[] = sanitizeInput($input['phone']);
        }
        
        if (!empty($memberUpdates)) {
            $memberParams[] = $userId;
            $updateQuery = "UPDATE members SET " . implode(', ', $memberUpdates) . " WHERE id = ?";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($memberParams);
        }
        
        // Update profile based on member type
        if ($memberType === 'individual' && isset($input['profile'])) {
            $profile = $input['profile'];
            $profileUpdates = [];
            $profileParams = [];
            
            if (isset($profile['bio'])) {
                $profileUpdates[] = "bio = ?";
                $profileParams[] = sanitizeInput($profile['bio']);
            }
            
            if (isset($profile['skills'])) {
                $profileUpdates[] = "skills = ?";
                $profileParams[] = json_encode($profile['skills']);
            }
            
            if (isset($profile['experience_years'])) {
                $profileUpdates[] = "experience_years = ?";
                $profileParams[] = intval($profile['experience_years']);
            }
            
            if (isset($profile['github_url'])) {
                $profileUpdates[] = "github_url = ?";
                $profileParams[] = sanitizeInput($profile['github_url']);
            }
            
            if (isset($profile['portfolio_url'])) {
                $profileUpdates[] = "portfolio_url = ?";
                $profileParams[] = sanitizeInput($profile['portfolio_url']);
            }
            
            if (isset($profile['hourly_rate'])) {
                $profileUpdates[] = "hourly_rate = ?";
                $profileParams[] = intval($profile['hourly_rate']);
            }
            
            if (!empty($profileUpdates)) {
                $profileParams[] = $userId;
                $updateQuery = "UPDATE developer_profiles SET " . implode(', ', $profileUpdates) . " WHERE member_id = ?";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute($profileParams);
            }
        } elseif ($memberType === 'company' && isset($input['profile'])) {
            $profile = $input['profile'];
            $profileUpdates = [];
            $profileParams = [];
            
            if (isset($profile['company_name'])) {
                $profileUpdates[] = "company_name = ?";
                $profileParams[] = sanitizeInput($profile['company_name']);
            }
            
            if (isset($profile['industry'])) {
                $profileUpdates[] = "industry = ?";
                $profileParams[] = sanitizeInput($profile['industry']);
            }
            
            if (isset($profile['company_size'])) {
                $profileUpdates[] = "company_size = ?";
                $profileParams[] = sanitizeInput($profile['company_size']);
            }
            
            if (isset($profile['website_url'])) {
                $profileUpdates[] = "website_url = ?";
                $profileParams[] = sanitizeInput($profile['website_url']);
            }
            
            if (isset($profile['description'])) {
                $profileUpdates[] = "description = ?";
                $profileParams[] = sanitizeInput($profile['description']);
            }
            
            if (!empty($profileUpdates)) {
                $profileParams[] = $userId;
                $updateQuery = "UPDATE company_profiles SET " . implode(', ', $profileUpdates) . " WHERE member_id = ?";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute($profileParams);
            }
        }
        
        // Log activity
        logError("Profile updated", ['user_id' => $userId]);
        
        // Return updated profile
        handleGet($pdo, $userId);
        
    } catch (PDOException $e) {
        logError('Database error in PUT profile: ' . $e->getMessage());
        sendError('Failed to update profile', 500);
    }
}
