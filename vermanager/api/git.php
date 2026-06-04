<?php
/**
 * API: Git/GitHub 자동화
 * 자동 커밋, 태그 생성, GitHub 릴리즈, 웹훅 처리
 *
 * GET    /vermanager/api/git?action=status&site=kiam-prod
 * GET    /vermanager/api/git?action=log&site=kiam-prod&limit=10
 * POST   /vermanager/api/git
 *         Body: {"action":"commit","site":"kiam-prod","message":"릴리즈 v1.0.2"}
 * POST   /vermanager/api/git
 *         Body: {"action":"tag","site":"kiam-prod","version":"1.0.2"}
 * POST   /vermanager/api/git
 *         Body: {"action":"push","site":"kiam-prod"}
 * POST   /vermanager/api/git
 *         Body: {"action":"full-release","site":"kiam-prod","version":"1.0.2","note":"..."}
 *
 * 수정: 2026-05-03 아리아 (Phase 5 — Git/GitHub 자동화)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── 헬퍼: 사이트 문서루트의 Git 상태 확인 ──
function git_status(string $doc_root): array {
    $git_dir = rtrim($doc_root, '/') . '/.git';
    if (!is_dir($git_dir)) {
        return ['is_repo' => false, 'error' => 'Git 저장소가 아닙니다.'];
    }

    $cwd = getcwd();
    chdir($doc_root);

    // 현재 브랜치
    $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?? 'unknown');
    // 최신 커밋
    $last_commit = trim(shell_exec('git log -1 --format="%h %s (%ci)" 2>&1') ?? '없음');
    // 변경 상태
    $status = trim(shell_exec('git status --porcelain 2>&1') ?? '');
    $changed_files = $status ? array_filter(explode("\n", $status)) : [];
    // 태그 목록 (최근 5개)
    $tags = trim(shell_exec('git tag --sort=-creatordate | head -5 2>&1') ?? '');
    $tag_list = $tags ? array_filter(explode("\n", $tags)) : [];
    // 리모트 URL
    $remote = trim(shell_exec('git remote get-url origin 2>&1') ?? '없음');

    chdir($cwd);

    return [
        'is_repo'       => true,
        'branch'        => $branch,
        'last_commit'   => $last_commit,
        'has_changes'   => count($changed_files) > 0,
        'changed_files' => array_values($changed_files),
        'changed_count' => count($changed_files),
        'recent_tags'   => array_values($tag_list),
        'remote_url'    => $remote,
    ];
}

// ── 헬퍼: Git 커밋 ──
function git_commit(string $doc_root, string $message): array {
    $cwd = getcwd();
    chdir($doc_root);

    // git add
    $add_output = shell_exec('git add -A 2>&1');
    // git commit
    $commit_cmd = 'git commit -m ' . escapeshellarg($message) . ' 2>&1';
    $commit_output = shell_exec($commit_cmd);
    $commit_success = strpos($commit_output, 'nothing to commit') === false
                   && strpos($commit_output, 'error:') === false
                   && strpos($commit_output, 'fatal:') === false;

    // 새 커밋 해시
    $new_hash = trim(shell_exec('git log -1 --format="%h" 2>&1') ?? '');

    chdir($cwd);
    return [
        'success'        => $commit_success,
        'output'         => trim($commit_output),
        'new_commit'     => $new_hash,
        'was_empty'      => strpos($commit_output, 'nothing to commit') !== false,
    ];
}

// ── 헬퍼: Git 태그 ──
function git_tag(string $doc_root, string $version, string $note = ''): array {
    $cwd = getcwd();
    chdir($doc_root);

    $tag_name = 'v' . $version;
    // 이미 있는 태그 확인
    $existing = trim(shell_exec("git tag -l '{$tag_name}' 2>&1") ?? '');
    if ($existing === $tag_name) {
        chdir($cwd);
        return ['success' => false, 'error' => "태그 '{$tag_name}'은(는) 이미 존재합니다.", 'already_exists' => true];
    }

    $tag_cmd = 'git tag -a ' . escapeshellarg($tag_name) . ' -m ' . escapeshellarg($note ?: "버전 {$version} 릴리즈") . ' 2>&1';
    $tag_output = shell_exec($tag_cmd);

    $tag_success = strpos($tag_output, 'error:') === false && strpos($tag_output, 'fatal:') === false;

    chdir($cwd);
    return [
        'success'  => $tag_success,
        'tag'      => $tag_name,
        'output'   => trim($tag_output),
    ];
}

// ── 헬퍼: Git Push ──
function git_push(string $doc_root): array {
    $cwd = getcwd();
    chdir($doc_root);

    $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?? 'main');
    $push_cmd = "git push origin {$branch} --tags 2>&1";
    $push_output = shell_exec($push_cmd);
    $push_success = strpos($push_output, 'error:') === false
                 && strpos($push_output, 'fatal:') === false
                 && strpos($push_output, 'rejected') === false;

    chdir($cwd);
    return [
        'success' => $push_success,
        'output'  => trim($push_output),
        'branch'  => $branch,
    ];
}

// ── 헬퍼: 자동화 로그 기록 ──
function log_git_action(string $site_id, string $action, string $status, array $details = []): void {
    $log_file = site_data_dir($site_id) . '/git_automation_log.json';
    $logs = [];
    if (file_exists($log_file)) {
        $logs = json_decode(file_get_contents($log_file), true) ?: [];
    }
    $logs[] = array_merge([
        'id'        => uniqid('git_'),
        'site_id'   => $site_id,
        'action'    => $action,
        'status'    => $status,
        'timestamp' => date('c'),
    ], $details);
    if (count($logs) > 100) $logs = array_slice($logs, -100);
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ════════════════════════════════════════════════════════════
// GET 요청 처리
// ════════════════════════════════════════════════════════════
if ($method === 'GET') {
    $site_id = $_GET['site'] ?? get_current_site_id();
    $action = $_GET['action'] ?? null;

    if (!$site_id || !get_site($site_id)) {
        echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $site = get_site($site_id);
    $doc_root = $site['document_root'] ?? '';

    // ── Git 상태 ──
    if ($action === 'status') {
        $git_info = git_status($doc_root);
        $version = site_get_current_version($site_id);

        // 자동화 로그
        $log_file = site_data_dir($site_id) . '/git_automation_log.json';
        $recent_logs = [];
        if (file_exists($log_file)) {
            $all_logs = json_decode(file_get_contents($log_file), true) ?: [];
            $recent_logs = array_slice(array_reverse($all_logs), 0, 10);
        }

        echo json_encode([
            'ok'              => true,
            'site_id'         => $site_id,
            'site_name'       => $site['name'] ?? $site_id,
            'document_root'   => $doc_root,
            'current_version' => $version,
            'git'             => $git_info,
            'recent_actions'  => $recent_logs,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Git 로그 ──
    if ($action === 'log') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $cwd = getcwd();
        chdir($doc_root);
        $log_output = shell_exec("git log --oneline -{$limit} 2>&1") ?? '';
        $log_lines = array_filter(explode("\n", trim($log_output)));
        chdir($cwd);

        echo json_encode([
            'ok'       => true,
            'site_id'  => $site_id,
            'commits'  => array_values($log_lines),
            'count'    => count($log_lines),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 자동화 로그 ──
    if ($action === 'automation-log') {
        $log_file = site_data_dir($site_id) . '/git_automation_log.json';
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

    echo json_encode([
        'ok' => true,
        'usage' => [
            'GET  ?action=status&site=kiam-prod  → Git 저장소 상태',
            'GET  ?action=log&site=kiam-prod     → 커밋 로그',
            'GET  ?action=automation-log&site=... → 자동화 작업 로그',
            'POST {"action":"commit",...} → 자동 커밋',
            'POST {"action":"tag",...}    → 태그 생성',
            'POST {"action":"push",...}   → GitHub Push',
            'POST {"action":"full-release",...} → 전체 릴리즈 (커밋+태그+푸시)',
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════════
// POST 요청 처리
// ════════════════════════════════════════════════════════════
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
    $site_id = $input['site'] ?? get_current_site_id();

    if (!$site_id || !get_site($site_id)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => '사이트를 지정해주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $site = get_site($site_id);
    $doc_root = $site['document_root'] ?? '';

    // ── Git 리포 확인 ──
    $git_info = git_status($doc_root);

    // ── 자동 커밋 ──
    if ($action === 'commit') {
        $message = $input['message'] ?? '버전 관리 시스템 자동 커밋';
        $result = git_commit($doc_root, $message);
        log_git_action($site_id, 'commit', $result['success'] ? 'success' : 'failed', [
            'message' => $message,
            'commit'  => $result['new_commit'] ?? null,
        ]);

        echo json_encode(array_merge([
            'ok'      => $result['success'] || $result['was_empty'],
            'site_id' => $site_id,
            'message' => $result['success']
                ? "✅ 커밋 완료: {$result['new_commit']}"
                : ($result['was_empty'] ? 'ℹ️ 커밋할 변경사항이 없습니다.' : '❌ 커밋 실패'),
        ], $result), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 태그 생성 ──
    if ($action === 'tag') {
        $version = $input['version'] ?? site_get_current_version($site_id)['raw'];
        $note = $input['note'] ?? "버전 {$version} 릴리즈 — 아리아 자동화";
        $result = git_tag($doc_root, $version, $note);
        log_git_action($site_id, 'tag', $result['success'] ? 'success' : 'failed', [
            'tag' => $result['tag'] ?? null,
        ]);

        echo json_encode(array_merge([
            'ok'      => $result['success'],
            'site_id' => $site_id,
            'version' => $version,
            'message' => $result['success']
                ? "✅ 태그 생성 완료: v{$version}"
                : ($result['already_exists'] ?? false ? '⚠️ 이미 존재하는 태그입니다.' : '❌ 태그 생성 실패'),
        ], $result), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── GitHub Push ──
    if ($action === 'push') {
        $result = git_push($doc_root);
        log_git_action($site_id, 'push', $result['success'] ? 'success' : 'failed', [
            'branch' => $result['branch'] ?? null,
        ]);

        echo json_encode(array_merge([
            'ok'      => $result['success'],
            'site_id' => $site_id,
            'message' => $result['success'] ? '✅ GitHub Push 완료!' : '❌ Push 실패',
        ], $result), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 전체 릴리즈 (커밋 + 태그 + 푸시) ──
    if ($action === 'full-release') {
        $version = $input['version'] ?? site_get_current_version($site_id)['raw'];
        $note = $input['note'] ?? "버전 {$version} 릴리즈";
        $commit_msg = $input['commit_message'] ?? "🔖 릴리즈 v{$version}";

        $steps = [];
        $all_ok = true;

        // 1. 커밋
        $commit_result = git_commit($doc_root, $commit_msg);
        $steps[] = [
            'step' => 1, 'name' => 'Git 커밋',
            'status' => $commit_result['success'] ? 'success' : ($commit_result['was_empty'] ? 'skip' : 'error'),
            'detail' => $commit_result['success'] ? "커밋: {$commit_result['new_commit']}" : ($commit_result['was_empty'] ? '변경사항 없음 (건너뜀)' : $commit_result['output']),
        ];
        if (!$commit_result['success'] && !$commit_result['was_empty']) $all_ok = false;

        // 2. 태그
        $tag_result = git_tag($doc_root, $version, $note);
        $steps[] = [
            'step' => 2, 'name' => 'Git 태그',
            'status' => $tag_result['success'] ? 'success' : 'warning',
            'detail' => $tag_result['success'] ? "태그: v{$version}" : ($tag_result['already_exists'] ?? false ? "이미 존재함: v{$version}" : $tag_result['error'] ?? '실패'),
        ];

        // 3. 푸시
        $push_result = git_push($doc_root);
        $steps[] = [
            'step' => 3, 'name' => 'GitHub Push',
            'status' => $push_result['success'] ? 'success' : 'error',
            'detail' => $push_result['success'] ? "브랜치 {$push_result['branch']} 푸시 완료" : $push_result['output'],
        ];
        if (!$push_result['success']) $all_ok = false;

        log_git_action($site_id, 'full-release', $all_ok ? 'success' : 'partial', [
            'version' => $version,
            'steps'   => $steps,
        ]);

        echo json_encode([
            'ok'      => $all_ok,
            'site_id' => $site_id,
            'version' => $version,
            'message' => $all_ok
                ? "✅ 전체 릴리즈 완료: v{$version} (커밋→태그→푸시)"
                : "⚠️ 릴리즈 일부 실패. 로그를 확인하세요.",
            'steps'   => $steps,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'action을 지정해주세요. (commit, tag, push, full-release)'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed. GET/POST만 지원됩니다.'], JSON_UNESCAPED_UNICODE);