<?php
/**
 * 원챗 회원 발신자 프로필 API
 * GET  /api/profile.php          → 발신자 정보 조회 (me.php에 통합됨, 호환용)
 * POST /api/profile.php          → 발신자 정보 UPDATE (Gn_Member)
 *
 * 처리 필드 (18개):
 *   mem_name, mem_phone, mem_email, mem_job, mem_status_msg, mem_company (기본 정보)
 *   sender_name, sender_position, sender_affiliation,
 *   sender_companyaddress, sender_homeaddress, sender_introduction,
 *   vendor_name, vendor_industry,
 *   sender_introfile, sender_companyfile, sender_productfile, sender_servicefile
 *
 * 파일 업로드는 별도 처리 (FormData) — 향후 추가
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();

$login_id  = onechat_auth();
$db        = getDatabaseConnection();
$login_esc = $db->real_escape_string($login_id);

// ── GET ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $r = $db->query("
        SELECT mem_id, mem_name, mem_phone, mem_email, mem_job, mem_status_msg, mem_company,
               sender_name, sender_position, sender_affiliation,
               sender_companyaddress, sender_homeaddress, sender_introduction,
               vendor_name, vendor_industry,
               sender_introfile, sender_companyfile, sender_productfile, sender_servicefile
        FROM Gn_Member
        WHERE mem_id='{$login_esc}'
        LIMIT 1
    ");
    if (!$r || !($row = $r->fetch_assoc())) {
        onechat_json(['success' => false, 'error' => '회원을 찾을 수 없습니다.'], 404);
    }
    onechat_json([
        'success' => true,
        'profile' => [
            'mem_id'         => $row['mem_id'],
            'basic'          => [
                'name'       => $row['mem_name']       ?? '',
                'phone'      => $row['mem_phone']      ?? '',
                'email'      => $row['mem_email']      ?? '',
                'job'        => $row['mem_job']        ?? '',
                'status_msg' => $row['mem_status_msg'] ?? '',
                'company'    => $row['mem_company']    ?? '',
            ],
            'sender'         => [
                'name'           => $row['sender_name']           ?? '',
                'position'       => $row['sender_position']       ?? '',
                'affiliation'    => $row['sender_affiliation']    ?? '',
                'companyaddress' => $row['sender_companyaddress'] ?? '',
                'homeaddress'    => $row['sender_homeaddress']    ?? '',
                'introduction'   => $row['sender_introduction']   ?? '',
                'introfile'      => $row['sender_introfile']      ?? '',
                'companyfile'    => $row['sender_companyfile']    ?? '',
                'productfile'    => $row['sender_productfile']    ?? '',
                'servicefile'    => $row['sender_servicefile']    ?? '',
            ],
            'vendor'         => [
                'name'     => $row['vendor_name']     ?? '',
                'industry' => $row['vendor_industry'] ?? '',
            ],
        ],
    ]);
}

// ── POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    // 발신자 객체 + vendor 객체 + 기본 정보 받기
    $sender = is_array($input['sender'] ?? null) ? $input['sender'] : [];
    $vendor = is_array($input['vendor'] ?? null) ? $input['vendor'] : [];
    $basic  = is_array($input['basic']  ?? null) ? $input['basic']  : [];

    // 입력값 정리 (있을 때만 UPDATE — partial update 지원)
    $fields = [];

    $field_map = [
        // 기본 회원 정보
        'mem_name'  => isset($basic['name'])  && $basic['name']  !== null ? mb_substr(trim($basic['name']),  0, 30) : null,
        'mem_phone' => isset($basic['phone']) && $basic['phone'] !== null ? preg_replace('/[^0-9\-+]/', '', mb_substr(trim($basic['phone']), 0, 15)) : null,
        'mem_email' => isset($basic['email']) && $basic['email'] !== null ? mb_substr(trim($basic['email']), 0, 150) : null,
        'mem_job'        => isset($basic['job'])        && $basic['job']        !== null ? mb_substr(trim($basic['job']),        0, 50)  : null,
        'mem_status_msg' => isset($basic['status_msg']) && $basic['status_msg'] !== null ? mb_substr(trim($basic['status_msg']), 0, 200) : null,
        'mem_company'    => isset($basic['company'])    && $basic['company']    !== null ? mb_substr(trim($basic['company']),    0, 100) : null,
        // 발신자 정보
        'sender_name'           => $sender['name']           ?? null,
        'sender_position'       => $sender['position']       ?? null,
        'sender_affiliation'    => $sender['affiliation']    ?? null,
        'sender_companyaddress' => $sender['companyaddress'] ?? null,
        'sender_homeaddress'    => $sender['homeaddress']    ?? null,
        'sender_introduction'   => $sender['introduction']   ?? null,
        'vendor_name'           => $vendor['name']           ?? null,
        'vendor_industry'       => $vendor['industry']       ?? null,
        'sender_introfile'      => $sender['introfile']      ?? null,
        'sender_companyfile'    => $sender['companyfile']    ?? null,
        'sender_productfile'    => $sender['productfile']    ?? null,
        'sender_servicefile'    => $sender['servicefile']    ?? null,
    ];

    foreach ($field_map as $col => $val) {
        if ($val !== null) {
            // 빈 문자열은 NULL로 저장
            $val_trimmed = is_string($val) ? trim($val) : '';
            if ($val_trimmed === '') {
                $fields[] = "{$col}=NULL";
            } else {
                $val_esc = $db->real_escape_string($val_trimmed);
                $fields[] = "{$col}='{$val_esc}'";
            }
        }
    }

    if (empty($fields)) {
        onechat_json(['success' => false, 'error' => '변경 사항 없음'], 400);
    }

    $sql = "UPDATE Gn_Member SET " . implode(', ', $fields) . " WHERE mem_id='{$login_esc}'";
    $result = $db->query($sql);

    if (!$result) {
        onechat_json(['success' => false, 'error' => 'DB 오류: ' . $db->error], 500);
    }

    // 저장 후 최신 데이터 반환
    $r = $db->query("
        SELECT mem_name, mem_phone, mem_email, mem_job, mem_status_msg, mem_company, sender_name, sender_position, sender_affiliation,
               sender_companyaddress, sender_homeaddress, sender_introduction,
               vendor_name, vendor_industry,
               sender_introfile, sender_companyfile, sender_productfile, sender_servicefile
        FROM Gn_Member WHERE mem_id='{$login_esc}' LIMIT 1
    ");
    $row = $r ? $r->fetch_assoc() : null;

    onechat_json([
        'success' => true,
        'message' => '프로필 저장 완료',
        'profile' => $row ? [
            'basic' => [
                'name'       => $row['mem_name']       ?? '',
                'phone'      => $row['mem_phone']      ?? '',
                'email'      => $row['mem_email']      ?? '',
                'job'        => $row['mem_job']        ?? '',
                'status_msg' => $row['mem_status_msg'] ?? '',
                'company'    => $row['mem_company']    ?? '',
            ],
            'sender' => [
                'name'           => $row['sender_name']           ?? '',
                'position'       => $row['sender_position']       ?? '',
                'affiliation'    => $row['sender_affiliation']    ?? '',
                'companyaddress' => $row['sender_companyaddress'] ?? '',
                'homeaddress'    => $row['sender_homeaddress']    ?? '',
                'introduction'   => $row['sender_introduction']   ?? '',
                'introfile'      => $row['sender_introfile']      ?? '',
                'companyfile'    => $row['sender_companyfile']    ?? '',
                'productfile'    => $row['sender_productfile']    ?? '',
                'servicefile'    => $row['sender_servicefile']    ?? '',
            ],
            'vendor' => [
                'name'     => $row['vendor_name']     ?? '',
                'industry' => $row['vendor_industry'] ?? '',
            ],
        ] : null,
    ]);
}

onechat_json(['success' => false, 'error' => '지원하지 않는 메소드'], 405);
