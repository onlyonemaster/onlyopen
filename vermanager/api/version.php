<?php
/**
 * API: 버전 정보 조회 (다중 사이트 지원)
 * GET /vermanager/api/version?site=kiam-prod  → 특정 사이트
 * GET /vermanager/api/version?site=all         → 모든 사이트
 * GET /vermanager/api/version                  → 현재 선택 사이트 (기본: kiam-prod)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config.php';

$site_id = $_GET['site'] ?? null;

if ($site_id === 'all') {
    // ── 모든 사이트 버전 한번에 ──
    $sites = get_active_sites();
    $result = [];
    foreach ($sites as $id => $site) {
        $ver = site_get_current_version($id);
        $history = site_get_history($id);
        $last_date = !empty($history) ? (end($history)['date'] ?? null) : null;
        $result[] = [
            'site_id'        => $id,
            'site_name'      => $site['name'] ?? $id,
            'domain'         => $site['domain'] ?? '',
            'environment'    => $site['environment'] ?? 'unknown',
            'version'        => $ver,
            'last_release'   => $last_date,
            'display_string' => ($site['app_name'] ?? $site['name'] ?? $id) . ' v' . $ver['raw'],
        ];
    }
    echo json_encode([
        'ok'    => true,
        'sites' => $result,
        'total' => count($result),
        'stats' => get_system_stats(),
    ], JSON_UNESCAPED_UNICODE);
} elseif ($site_id && get_site($site_id)) {
    // ── 특정 사이트 ──
    $site = get_site($site_id);
    $ver = site_get_current_version($site_id);
    $pkg = site_get_package($site_id);
    $history = site_get_history($site_id);
    $lastRelease = !empty($history) ? end($history) : null;

    // 부모 사이트 정보
    $parent = null;
    if (!empty($site['parent_site_id'])) {
        $parent_site = get_site($site['parent_site_id']);
        if ($parent_site) {
            $parent = [
                'site_id'   => $parent_site['id'],
                'name'      => $parent_site['name'],
                'version'   => site_get_current_version($parent_site['id']),
            ];
        }
    }

    // 자식 사이트 목록
    $children = [];
    foreach (get_active_sites() as $id => $s) {
        if (($s['parent_site_id'] ?? null) === $site_id) {
            $children[] = [
                'site_id' => $id,
                'name'    => $s['name'],
                'version' => site_get_current_version($id),
            ];
        }
    }

    echo json_encode([
        'ok'             => true,
        'site_id'        => $site_id,
        'site_name'      => $site['name'] ?? $site_id,
        'domain'         => $site['domain'] ?? '',
        'environment'    => $site['environment'] ?? 'unknown',
        'version'        => $ver,
        'package'        => $pkg,
        'last_release'   => $lastRelease,
        'parent'         => $parent,
        'children'       => $children,
        'display_string' => ($site['app_name'] ?? $site['name'] ?? $site_id) . ' v' . $ver['raw'],
    ], JSON_UNESCAPED_UNICODE);
} else {
    // ── 사이트 미지정 → 사용 가능한 목록 ──
    $sites = get_active_sites();
    $default_id = !empty($sites) ? array_key_first($sites) : null;

    // 기본 사이트 정보도 같이 제공
    $default_info = null;
    if ($default_id) {
        $ds = get_site($default_id);
        $dv = site_get_current_version($default_id);
        $default_info = [
            'site_id'        => $default_id,
            'site_name'      => $ds['name'] ?? $default_id,
            'version'        => $dv,
            'display_string' => ($ds['app_name'] ?? $ds['name'] ?? $default_id) . ' v' . $dv['raw'],
        ];
    }

    echo json_encode([
        'ok'              => true,
        'message'         => 'site 파라미터를 지정하세요. 예: ?site=kiam-prod 또는 ?site=all',
        'available_sites' => array_values(array_map(fn($id) => [
            'id'   => $id,
            'name' => $sites[$id]['name'] ?? $id,
        ], array_keys($sites))),
        'default'         => $default_info,
    ], JSON_UNESCAPED_UNICODE);
}