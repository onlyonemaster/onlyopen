<?php
/**
 * API: 버전 히스토리 조회 (다중 사이트 지원)
 * GET /vermanager/api/history?site=kiam-prod           → 특정 사이트
 * GET /vermanager/api/history?site=kiam-prod&type=major → 유형 필터
 * GET /vermanager/api/history?site=kiam-prod&limit=5   → 개수 제한
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

// ── 사이트 결정 ──
$site_id = $_GET['site'] ?? get_current_site_id();

// 사이트 목록만 필요한 경우
if ($site_id === 'all') {
    $sites = get_active_sites();
    $all_history = [];
    foreach ($sites as $id => $s) {
        $h = site_get_history($id);
        foreach ($h as &$entry) {
            $entry['site_id'] = $id;
            $entry['site_name'] = $s['name'] ?? $id;
        }
        $all_history = array_merge($all_history, $h);
    }
    // 날짜 기준 최신순 정렬
    usort($all_history, fn($a, $b) => ($b['date'] ?? '') <=> ($a['date'] ?? ''));

    $type = $_GET['type'] ?? null;
    if ($type && in_array($type, ['major', 'minor', 'patch'])) {
        $all_history = array_values(array_filter($all_history, fn($h) => ($h['type'] ?? '') === $type));
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
    if ($limit > 0) $all_history = array_slice($all_history, 0, $limit);

    echo json_encode([
        'ok'      => true,
        'data'    => $all_history,
        'total'   => count($all_history),
        'mode'    => 'all',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 특정 사이트 검증 ──
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
$history = site_get_history($site_id);

// ── 유형 필터 ──
$type = $_GET['type'] ?? null;
if ($type && in_array($type, ['major', 'minor', 'patch'])) {
    $history = array_values(array_filter($history, fn($h) => ($h['type'] ?? '') === $type));
}

// ── 최신순 정렬 ──
$history = array_reverse($history ?: []);

// ── 개수 제한 ──
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
if ($limit > 0) {
    $history = array_slice($history, 0, $limit);
}

$current = site_get_current_version($site_id);

echo json_encode([
    'ok'              => true,
    'site_id'         => $site_id,
    'site_name'       => $site['name'] ?? $site_id,
    'environment'     => $site['environment'] ?? 'unknown',
    'data'            => $history,
    'total'           => count($history),
    'current_version' => $current,
], JSON_UNESCAPED_UNICODE);