<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Handle different endpoints
switch ($method) {
    case 'GET':
        if (isset($_GET['search'])) {
            $qrCodes = $db->searchQRCodes($userId, $_GET['search']);
        } else {
            $qrCodes = $db->getQRCodesByUser($userId);
        }
        echo json_encode($qrCodes);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $db->addQRCode(
            $userId,
            $data['title'],
            $data['memo'],
            $data['link'],
            $data['category'],
            $data['qr_image']
        );
        echo json_encode(['status' => $result ? 'success' : 'error']);
        break;
        
    case 'PUT':
        if (preg_match('/\/qr_codes\/(\d+)/', $path, $matches)) {
            $id = $matches[1];
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $db->updateQRCode(
                $id,
                $userId,
                $data['title'],
                $data['memo'],
                $data['link'],
                $data['category'],
                $data['qr_image']
            );
            echo json_encode(['status' => $result ? 'success' : 'error']);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/\/qr_codes\/(\d+)/', $path, $matches)) {
            $id = $matches[1];
            $result = $db->deleteQRCode($id, $userId);
            echo json_encode(['status' => $result ? 'success' : 'error']);
        }
        break;
}
?> 