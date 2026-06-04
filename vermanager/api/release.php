<?php
/**
 * API: 새 버전 릴리즈 (다중 사이트 지원)
 * POST /vermanager/api/release
 * Body: {
 *   "site": "kiam-prod",       ← 사이트 ID (필수)
 *   "type": "patch|minor|major",
 *   "note": "릴리즈 설명 (선택)",
 *   "linked_improvements": ["imp_4", "imp_5"]
 * }
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. POST만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// ── 사이트 확인 ──
$site_id = $input['site'] ?? get_current_site_id();
if (!$site_id || !get_site($site_id)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => '사이트를 지정해주세요. 예: {"site":"kiam-prod", ...}',
        'available_sites' => array_values(array_map(fn($id) => [
            'id' => $id, 'name' => get_site($id)['name'] ?? $id
        ], array_keys(get_active_sites()))),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$site = get_site($site_id);

// ── 릴리즈 유형 확인 ──
$type = $input['type'] ?? 'patch';
$note = $input['note'] ?? '';

if (!in_array($type, ['major', 'minor', 'patch'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => '올바르지 않은 릴리즈 유형입니다. major, minor, patch 중 선택하세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 현재 버전 조회 ──
$current = site_get_current_version($site_id);

// ── 버전 올리기 (bump) ──
$next = bump_version($current, $type);

// ── package.json 업데이트 ──
$pkg = site_get_package($site_id);
if (empty($pkg)) {
    $pkg = [
        'name'        => $site['app_name'] ?? $site['name'] ?? 'unknown',
        'version'     => '1.0.0',
        'description' => ($site['app_name'] ?? $site['name'] ?? '') . ' 버전 관리',
    ];
}
$pkg['version'] = $next['raw'];
$pkg['lastRelease'] = date('c');
$pkg['lastReleaseBy'] = '아리아 (AI 자동)';
site_write_json($site_id, 'package.json', $pkg);

// ── 연결된 소스 개선 항목 처리 ──
$improvements = site_get_improvements($site_id);
$linkedImps = $input['linked_improvements'] ?? [];
$impNotes = [];

if (!empty($linkedImps)) {
    foreach ($improvements as &$imp) {
        if (in_array($imp['id'] ?? '', $linkedImps)) {
            $imp['released_in'] = $next['raw'];
            $imp['released_at'] = date('c');
        }
    }
    unset($imp);
    site_write_json($site_id, 'improvements.json', $improvements);

    // CHANGELOG용 설명 수집
    foreach ($improvements as $imp) {
        if (in_array($imp['id'] ?? '', $linkedImps)) {
            $impNotes[] = [
                'title'    => $imp['title'] ?? '(제목없음)',
                'category' => $imp['category'] ?? '기타',
                'content'  => $imp['content'] ?? '',
            ];
        }
    }
}

// ── 버전 히스토리 기록 ──
$history = site_get_history($site_id);
$entry = [
    'id'                  => uniqid('rel_'),
    'from'                => $current['raw'],
    'to'                  => $next['raw'],
    'type'                => $type,
    'date'                => date('c'),
    'note'                => $note,
    'linked_improvements' => $linkedImps,
    'imp_count'           => count($linkedImps),
    'imp_summary'         => $impNotes,
];
$history[] = $entry;
site_write_json($site_id, 'version_history.json', $history);

// ── CHANGELOG 업데이트 ──
$changelog = site_get_changelog($site_id);
$changelogEntry = [
    'id'       => uniqid('cl_'),
    'version'  => $next['raw'],
    'date'     => date('Y-m-d'),
    'type'     => $type,
    'note'     => $note,
    'changes'  => $impNotes,
    'imp_count' => count($linkedImps),
];
$changelog[] = $changelogEntry;
site_write_json($site_id, 'changelog.json', $changelog);

// ── 성공 응답 ──
echo json_encode([
    'ok'              => true,
    'site_id'         => $site_id,
    'site_name'       => $site['name'] ?? $site_id,
    'environment'     => $site['environment'] ?? 'unknown',
    'message'         => "✅ [{$site['name']}] v{$current['raw']} → v{$next['raw']} ({$type}) 릴리즈 완료!",
    'version'         => $next,
    'type'            => $type,
    'history_entry'   => $entry,
    'improvements_linked' => count($linkedImps),
    'display_string'  => ($site['app_name'] ?? $site['name'] ?? $site_id) . ' v' . $next['raw'],
], JSON_UNESCAPED_UNICODE);