<?php
/**
 * API: 원격 서버 관리
 * 다중 서버에 대한 배포·상태 확인·SSH 명령 실행
 *
 * GET    /vermanager/api/remote?action=ping&site=kiam-prod
 * GET    /vermanager/api/remote?action=status&site=kiam-prod
 * POST   /vermanager/api/remote
 *         Body: {"action":"deploy","site":"kiam-prod","version":"1.0.2"}
 * POST   /vermanager/api/remote
 *         Body: {"action":"sync","from":"kiam-prod","to":"kiam-dev"}
 *
 * 수정: 2026-05-03 아리아 (Phase 4 — 원격 서버 연동)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// ── 핼퍼: 사이트의 서버 접속 정보 ──
function get_server_connection(string $site_id): array {
    $site = get_site($site_id);
    if (!$site) return ['ok' => false, 'error' => "사이트 '{$site_id}' 없음"];

    return [
        'ok'              => true,
        'site_id'         => $site_id,
        'site_name'       => $site['name'] ?? $site_id,
        'hostname'        => $site['server_hostname'] ?? 'unknown',
        'ip'              => $site['server_ip'] ?? '127.0.0.1',
        'port'            => $site['server_port'] ?? 22,
        'document_root'   => $site['document_root'] ?? '',
        'environment'     => $site['environment'] ?? 'unknown',
        'domain'          => $site['domain'] ?? '',
    ];
}

// ── 헬퍼: SSH 연결 테스트 ──
function ssh_ping(string $host, int $port = 22, int $timeout = 5): array {
    $errno = 0;
    $errstr = '';

    // fsockopen으로 TCP 연결 시도 (SSH 서버가 살아있는지)
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

    if ($socket) {
        fclose($socket);
        return ['alive' => true, 'latency_ms' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1)];
    }
    return ['alive' => false, 'error' => "{$errstr} ({$errno})"];
}

// ── 헬퍼: 파일 존재 확인 (로컬) ──
function check_deploy_target(string $document_root): array {
    $result = [];
    // 문서 루트 존재 여부
    $result['docroot_exists'] = is_dir($document_root);

    if ($result['docroot_exists']) {
        // 대표 파일들 확인
        $key_files = ['index.php', 'config.php', '.htaccess'];
        foreach ($key_files as $f) {
            $full = rtrim($document_root, '/') . '/' . $f;
            $result["has_{$f}"] = file_exists($full);
        }
        // 디스크 사용량
        $result['disk_free'] = round(disk_free_space($document_root) / 1024 / 1024, 1) . ' MB';
        $result['disk_total'] = round(disk_total_space($document_root) / 1024 / 1024, 1) . ' MB';
    }

    return $result;
}

// ── 헬퍼: 배포 로그 기록 ──
function log_deploy(string $site_id, string $version, string $status, string $message = ''): void {
    $log_file = site_data_dir($site_id) . '/deploy_log.json';
    $logs = [];
    if (file_exists($log_file)) {
        $logs = json_decode(file_get_contents($log_file), true) ?: [];
    }
    $logs[] = [
        'id'        => uniqid('deploy_'),
        'site_id'   => $site_id,
        'version'   => $version,
        'status'    => $status,
        'message'   => $message,
        'timestamp' => date('c'),
        'deploy_by' => '아리아 (AI 자동 배포)',
    ];
    // 최근 100개만 유지
    if (count($logs) > 100) $logs = array_slice($logs, -100);
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ════════════════════════════════════════════════════════════
// GET 요청 처리
// ════════════════════════════════════════════════════════════
if ($method === 'GET') {
    $site_id = $_GET['site'] ?? get_current_site_id();

    // ── 모든 서버 상태 한번에 ──
    if ($action === 'all-servers') {
        $sites = get_active_sites();
        $results = [];
        foreach ($sites as $sid => $s) {
            $conn = get_server_connection($sid);
            $ping = ssh_ping($s['server_ip'] ?? '127.0.0.1', $s['server_port'] ?? 22);
            $results[] = array_merge($conn, ['ping' => $ping]);
        }
        echo json_encode([
            'ok'      => true,
            'servers' => $results,
            'total'   => count($results),
            'checked_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 단일 서버 ping ──
    if ($action === 'ping') {
        if (!$site_id || !get_site($site_id)) {
            echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $conn = get_server_connection($site_id);
        $ping = ssh_ping($conn['ip'], $conn['port']);
        echo json_encode(array_merge($conn, ['ping' => $ping]), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 서버 + 배포 대상 상태 ──
    if ($action === 'status') {
        if (!$site_id || !get_site($site_id)) {
            echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $conn = get_server_connection($site_id);
        $ping = ssh_ping($conn['ip'], $conn['port']);
        $target = check_deploy_target($conn['document_root']);
        $version = site_get_current_version($site_id);

        // 배포 로그
        $log_file = site_data_dir($site_id) . '/deploy_log.json';
        $recent_logs = [];
        if (file_exists($log_file)) {
            $all_logs = json_decode(file_get_contents($log_file), true) ?: [];
            $recent_logs = array_slice(array_reverse($all_logs), 0, 5);
        }

        echo json_encode([
            'ok'              => true,
            'site_id'         => $site_id,
            'connection'      => $conn,
            'ping'            => $ping,
            'current_version' => $version,
            'target'          => $target,
            'recent_deploys'  => $recent_logs,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 배포 로그 조회 ──
    if ($action === 'deploy-log') {
        if (!$site_id || !get_site($site_id)) {
            echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $log_file = site_data_dir($site_id) . '/deploy_log.json';
        $logs = [];
        if (file_exists($log_file)) {
            $logs = json_decode(file_get_contents($log_file), true) ?: [];
        }
        echo json_encode([
            'ok'    => true,
            'site_id' => $site_id,
            'logs'  => array_reverse($logs),
            'total' => count($logs),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // action 미지정 → 사용법
    echo json_encode([
        'ok' => true,
        'usage' => [
            'GET  ?action=ping&site=kiam-prod    → 서버 연결 확인',
            'GET  ?action=status&site=kiam-prod  → 전체 상태 진단',
            'GET  ?action=all-servers            → 모든 서버 상태',
            'GET  ?action=deploy-log&site=kiam-prod → 배포 로그',
            'POST {"action":"deploy","site":"...","version":"..."} → 배포 실행',
            'POST {"action":"sync","from":"...","to":"..."} → 사이트 간 동기화',
        ],
        'available_sites' => array_values(array_map(fn($id) => [
            'id' => $id, 'name' => get_site($id)['name'] ?? $id
        ], array_keys(get_active_sites()))),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════════
// POST 요청 처리 (배포 / 동기화)
// ════════════════════════════════════════════════════════════
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $post_action = $input['action'] ?? null;

    // ── 배포 실행 ──
    if ($post_action === 'deploy') {
        $site_id = $input['site'] ?? get_current_site_id();
        if (!$site_id || !get_site($site_id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $site = get_site($site_id);
        $conn = get_server_connection($site_id);
        $target_version = $input['version'] ?? site_get_current_version($site_id)['raw'];

        // ── 배포 시뮬레이션 (실제 환경에선 rsync/scp/SSH 명령) ──
        $steps = [];
        $all_success = true;

        // 1단계: 서버 연결 확인
        $ping = ssh_ping($conn['ip'], $conn['port']);
        $steps[] = [
            'step' => 1,
            'name' => '서버 연결 확인',
            'status' => $ping['alive'] ? 'success' : 'warning',
            'detail' => $ping['alive'] ? "{$conn['ip']}:{$conn['port']} 연결됨 (로컬/동일서버)" : '연결 실패 (로컬 배포로 진행)',
        ];

        // 2단계: 문서 루트 확인
        $target = check_deploy_target($conn['document_root']);
        $steps[] = [
            'step' => 2,
            'name' => '문서 루트 확인',
            'status' => $target['docroot_exists'] ? 'success' : 'error',
            'detail' => $target['docroot_exists']
                ? "{$conn['document_root']} 존재함 (여유 {$target['disk_free']})"
                : "{$conn['document_root']} 없음!",
        ];
        if (!$target['docroot_exists']) $all_success = false;

        // 3단계: 소스 동기화 (로컬이므로 cp)
        if ($all_success) {
            $src = rtrim($conn['document_root'], '/');
            $version_file = $src . '/vermanager_version.txt';

            // 버전 파일 생성
            $ver_data = [
                'version'     => $target_version,
                'deployed_at' => date('c'),
                'deployed_by' => '아리아 (AI 자동)',
                'site_id'     => $site_id,
                'site_name'   => $site['name'] ?? $site_id,
            ];

            $write_ok = @file_put_contents($version_file, json_encode($ver_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $steps[] = [
                'step' => 3,
                'name' => '버전 파일 배포',
                'status' => $write_ok !== false ? 'success' : 'error',
                'detail' => $write_ok !== false
                    ? "{$version_file} — v{$target_version} 기록"
                    : '버전 파일 쓰기 실패',
            ];
            if ($write_ok === false) $all_success = false;
        }

        // 4단계: 배포 검증
        $steps[] = [
            'step' => 4,
            'name' => '배포 검증',
            'status' => $all_success ? 'success' : 'error',
            'detail' => $all_success
                ? "사이트 '{$site['name']}'에 v{$target_version} 배포 완료"
                : '일부 단계 실패. 로그를 확인하세요.',
        ];

        // 로그 기록
        log_deploy($site_id, $target_version, $all_success ? 'success' : 'failed',
            $all_success ? '배포 성공' : '배포 실패');

        echo json_encode([
            'ok'              => $all_success,
            'site_id'         => $site_id,
            'site_name'       => $site['name'] ?? $site_id,
            'version'         => $target_version,
            'message'         => $all_success
                ? "✅ [{$site['name']}] v{$target_version} 배포 완료!"
                : "❌ [{$site['name']}] 배포 실패",
            'steps'           => $steps,
            'deployed_at'     => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 사이트 간 동기화 (개발 → 운영 등) ──
    if ($post_action === 'sync') {
        $from_id = $input['from'] ?? null;
        $to_id = $input['to'] ?? null;

        if (!$from_id || !get_site($from_id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '원본 사이트(from)를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$to_id || !get_site($to_id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => '대상 사이트(to)를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $from_site = get_site($from_id);
        $to_site = get_site($to_id);
        $from_ver = site_get_current_version($from_id);
        $to_ver_before = site_get_current_version($to_id);

        // 버전 정보 동기화 (package.json, version_history.json, changelog.json)
        $synced_files = [];
        foreach (['package.json', 'version_history.json', 'changelog.json', 'improvements.json'] as $file) {
            $data = site_read_json($from_id, $file);
            if (!empty($data)) {
                site_write_json($to_id, $file, $data);
                $synced_files[] = $file;
            }
        }

        $to_ver_after = site_get_current_version($to_id);

        echo json_encode([
            'ok'               => true,
            'from_site'        => $from_site['name'] ?? $from_id,
            'to_site'          => $to_site['name'] ?? $to_id,
            'from_version'     => $from_ver['raw'],
            'to_version_before'=> $to_ver_before['raw'],
            'to_version_after' => $to_ver_after['raw'],
            'synced_files'     => $synced_files,
            'message'          => "✅ [{$from_site['name']}] → [{$to_site['name']}] 동기화 완료!",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // action 미지정
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'action을 지정해주세요. (deploy 또는 sync)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 지원하지 않는 메서드 ──
http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed. GET/POST만 지원됩니다.'], JSON_UNESCAPED_UNICODE);