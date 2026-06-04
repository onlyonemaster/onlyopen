<?php
/**
 * API: 사이트 등록부 관리 (CRUD)
 * GET    /vermanager/api/sites              → 전체 사이트 목록
 * GET    /vermanager/api/sites?id=kiam-prod  → 특정 사이트 상세
 * POST   /vermanager/api/sites               → 새 사이트 등록
 * PUT    /vermanager/api/sites               → 사이트 정보 수정
 * DELETE /vermanager/api/sites?id=kiam-prod  → 사이트 비활성화
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // ── 사이트 목록 / 상세 조회 ──
    case 'GET':
        $id = $_GET['id'] ?? null;

        if ($id) {
            $site = get_site($id);
            if (!$site) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => "사이트 '{$id}'를 찾을 수 없습니다."], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // 하위 사이트 목록 포함
            $children = [];
            foreach (get_all_sites() as $sid => $s) {
                if (($s['parent_site_id'] ?? null) === $id) {
                    $children[] = [
                        'id'          => $sid,
                        'name'        => $s['name'] ?? $sid,
                        'domain'      => $s['domain'] ?? '',
                        'environment' => $s['environment'] ?? 'unknown',
                        'version'     => site_get_current_version($sid),
                    ];
                }
            }
            echo json_encode([
                'ok'       => true,
                'site'     => $site,
                'version'  => site_get_current_version($id),
                'children' => $children,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $all = get_all_sites();
            $result = [];
            foreach ($all as $sid => $s) {
                $result[] = array_merge($s, [
                    'current_version' => site_get_current_version($sid),
                    'release_count'   => count(site_get_history($sid)),
                    'imp_count'       => count(site_get_improvements($sid)),
                ]);
            }
            $stats = get_system_stats();
            echo json_encode([
                'ok'    => true,
                'sites' => $result,
                'total' => count($result),
                'stats' => [
                    'production_sites'  => $stats['production_sites'],
                    'development_sites' => $stats['development_sites'],
                    'total_servers'     => $stats['total_servers'],
                ],
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ── 새 사이트 등록 ──
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '사이트 ID는 필수입니다. 예: {"id":"kiam-prod", "name":"운영환경", ...}'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (get_site($input['id'])) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => "사이트 ID '{$input['id']}'는 이미 존재합니다."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $new_site = [
            'id'              => $input['id'],
            'name'            => $input['name'] ?? $input['id'],
            'domain'          => $input['domain'] ?? '',
            'aliases'         => $input['aliases'] ?? [],
            'server_hostname' => $input['server_hostname'] ?? 'main-server',
            'server_ip'       => $input['server_ip'] ?? '127.0.0.1',
            'server_port'     => $input['server_port'] ?? 22,
            'environment'     => $input['environment'] ?? 'development',
            'document_root'   => $input['document_root'] ?? '',
            'app_name'        => $input['app_name'] ?? $input['name'] ?? '',
            'app_type'        => $input['app_type'] ?? 'php',
            'description'     => $input['description'] ?? '',
            'status'          => 'active',
            'parent_site_id'  => $input['parent_site_id'] ?? null,
        ];

        add_site($new_site);

        // 초기 데이터 파일 생성
        $init_pkg = [
            'name'        => $new_site['app_name'],
            'version'     => '1.0.0',
            'description' => $new_site['description'],
            'lastRelease' => null,
        ];
        site_write_json($new_site['id'], 'package.json', $init_pkg);
        site_write_json($new_site['id'], 'version_history.json', []);
        site_write_json($new_site['id'], 'improvements.json', []);
        site_write_json($new_site['id'], 'changelog.json', []);

        echo json_encode([
            'ok'      => true,
            'message' => "사이트 '{$new_site['name']}'가 등록되었습니다.",
            'site'    => $new_site,
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── 사이트 정보 수정 ──
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id || !get_site($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => "사이트 ID가 없거나 존재하지 않습니다."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        unset($input['id']); // ID는 변경 불가
        update_site($id, $input);

        echo json_encode([
            'ok'      => true,
            'message' => "사이트 '{$id}' 정보가 수정되었습니다.",
        ], JSON_UNESCAPED_UNICODE);
        break;

    // ── 사이트 비활성화 (soft delete) ──
    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id || !get_site($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => "사이트 ID가 없거나 존재하지 않습니다."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        deactivate_site($id);
        echo json_encode([
            'ok'      => true,
            'message' => "사이트 '{$id}'가 비활성화되었습니다. (데이터는 보존됨)",
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed. GET/POST/PUT/DELETE만 지원됩니다.'], JSON_UNESCAPED_UNICODE);
}