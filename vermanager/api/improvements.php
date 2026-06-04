<?php
/**
 * API: 소스 개선 항목 관리 (다중 사이트 지원)
 * GET    /vermanager/api/improvements?site=kiam-prod          → 목록 조회
 * POST   /vermanager/api/improvements                         → 새 항목 추가
 *         Body: {"site":"kiam-prod", "title":"...", "category":"...", "content":"..."}
 * DELETE /vermanager/api/improvements?site=kiam-prod&id=xxx   → 삭제
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

// ── 사이트 결정 ──
$site_id = $_GET['site'] ?? get_current_site_id();

// 사이트 검증 (POST/DELETE는 필수, GET은 all 가능)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $site_id === 'all') {
    // 모든 사이트의 개선 항목 통합 조회
    $sites = get_active_sites();
    $all_imps = [];
    foreach ($sites as $id => $s) {
        $imps = site_get_improvements($id);
        foreach ($imps as &$imp) {
            $imp['site_id'] = $id;
            $imp['site_name'] = $s['name'] ?? $id;
        }
        $all_imps = array_merge($all_imps, $imps);
    }
    usort($all_imps, fn($a, $b) => ($b['date'] ?? '') <=> ($a['date'] ?? ''));
    echo json_encode([
        'ok'    => true,
        'data'  => $all_imps,
        'total' => count($all_imps),
        'mode'  => 'all',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$site_id || !get_site($site_id)) {
    echo json_encode([
        'ok' => false,
        'error' => '사이트를 찾을 수 없습니다. site 파라미터를 확인하세요.',
        'available_sites' => array_values(array_map(fn($id) => [
            'id' => $id, 'name' => get_site($id)['name'] ?? $id
        ], array_keys(get_active_sites()))),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$site = get_site($site_id);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $improvements = site_get_improvements($site_id);
        // 미발행 개선 건수
        $unreleased = count(array_filter($improvements, fn($i) => empty($i['released_in'])));
        echo json_encode([
            'ok'         => true,
            'site_id'    => $site_id,
            'site_name'  => $site['name'] ?? $site_id,
            'data'       => array_reverse($improvements ?: []),
            'total'      => count($improvements),
            'unreleased' => $unreleased,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $input_site = $input['site'] ?? $site_id;

        if (!get_site($input_site)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '유효한 사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $improvements = site_get_improvements($input_site);
        $entry = [
            'id'          => uniqid('imp_'),
            'title'       => $input['title'] ?? '(제목없음)',
            'category'    => $input['category'] ?? '기타',
            'content'     => $input['content'] ?? '',
            'date'        => date('c'),
            'released_in' => null,
        ];
        $improvements[] = $entry;
        site_write_json($input_site, 'improvements.json', $improvements);

        echo json_encode([
            'ok'      => true,
            'site_id' => $input_site,
            'message' => '[{$site[\'name\']}] 소스 개선 항목이 추가되었습니다.',
            'entry'   => $entry,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'id가 필요합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $improvements = site_get_improvements($site_id);
        $new_improvements = array_values(array_filter($improvements, fn($i) => ($i['id'] ?? '') !== $id));

        if (count($new_improvements) === count($improvements)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => "ID '{$id}'를 찾을 수 없습니다."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        site_write_json($site_id, 'improvements.json', $new_improvements);
        echo json_encode([
            'ok'      => true,
            'site_id' => $site_id,
            'message' => '[{$site[\'name\']}] 삭제되었습니다.',
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed. GET/POST/DELETE만 지원됩니다.'], JSON_UNESCAPED_UNICODE);
}