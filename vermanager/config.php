<?php
/**
 * Onlyone 통합 버전 관리 시스템
 * 다중 사이트·다중 서버 지원 설정
 * 수정: 2026-05-03 아리아 (Phase 2)
 */

// ── 기본 정보 ──
define('SYSTEM_NAME', 'Onlyone 통합 버전 관리');
define('SYSTEM_SLUG', 'vermanager');
define('DATA_DIR', __DIR__ . '/data');
define('SITES_DATA_DIR', DATA_DIR . '/sites');
define('REGISTRY_FILE', DATA_DIR . '/site-registry.json');

// ── 데이터 폴더 자동 생성 ──
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!is_dir(SITES_DATA_DIR)) mkdir(SITES_DATA_DIR, 0755, true);

// ════════════════════════════════════════════════════════
// 사이트 등록부 관리
// ════════════════════════════════════════════════════════

function get_registry(): array {
    if (!file_exists(REGISTRY_FILE)) return ['sites' => []];
    return json_decode(file_get_contents(REGISTRY_FILE), true) ?: ['sites' => []];
}

function save_registry(array $registry): bool {
    $registry['last_updated'] = date('c');
    return file_put_contents(
        REGISTRY_FILE,
        json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// ════════════════════════════════════════════════════════
// 사이트별 데이터 경로
// ════════════════════════════════════════════════════════

function site_data_dir(string $site_id): string {
    $dir = SITES_DATA_DIR . '/' . $site_id;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function site_data_path(string $site_id, string $file): string {
    return site_data_dir($site_id) . '/' . $file;
}

// ════════════════════════════════════════════════════════
// 사이트별 JSON 읽기/쓰기
// ════════════════════════════════════════════════════════

function site_read_json(string $site_id, string $file): array {
    $path = site_data_path($site_id, $file);
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?: [];
}

function site_write_json(string $site_id, string $file, array $data): bool {
    $path = site_data_path($site_id, $file);
    return file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// ════════════════════════════════════════════════════════
// 사이트 목록 조회
// ════════════════════════════════════════════════════════

function get_all_sites(): array {
    $registry = get_registry();
    return $registry['sites'] ?? [];
}

function get_active_sites(): array {
    return array_filter(get_all_sites(), fn($s) => ($s['status'] ?? 'active') === 'active');
}

function get_site(string $site_id): ?array {
    $sites = get_all_sites();
    return $sites[$site_id] ?? null;
}

// ════════════════════════════════════════════════════════
// 사이트 등록/수정/삭제
// ════════════════════════════════════════════════════════

function add_site(array $site): bool {
    $registry = get_registry();
    if (!isset($site['id'])) return false;
    $site['created_at'] = $site['created_at'] ?? date('c');
    $site['updated_at'] = date('c');
    $registry['sites'][$site['id']] = $site;
    site_data_dir($site['id']); // 데이터 폴더 자동 생성
    return save_registry($registry);
}

function update_site(string $site_id, array $data): bool {
    $registry = get_registry();
    if (!isset($registry['sites'][$site_id])) return false;
    $registry['sites'][$site_id] = array_merge(
        $registry['sites'][$site_id],
        $data,
        ['updated_at' => date('c')]
    );
    return save_registry($registry);
}

function deactivate_site(string $site_id): bool {
    return update_site($site_id, ['status' => 'inactive']);
}

// ════════════════════════════════════════════════════════
// 사이트별 버전 정보
// ════════════════════════════════════════════════════════

function site_get_package(string $site_id): array {
    return site_read_json($site_id, 'package.json');
}

function site_get_current_version(string $site_id): array {
    $pkg = site_get_package($site_id);
    if (empty($pkg)) return ['major' => 1, 'minor' => 0, 'patch' => 0, 'raw' => '1.0.0'];
    $v = $pkg['version'] ?? '1.0.0';
    $parts = explode('.', $v);
    return [
        'major' => (int)($parts[0] ?? 1),
        'minor' => (int)($parts[1] ?? 0),
        'patch' => (int)($parts[2] ?? 0),
        'raw'   => $v,
    ];
}

// ════════════════════════════════════════════════════════
// 사이트별 데이터 조회
// ════════════════════════════════════════════════════════

function site_get_history(string $site_id): array {
    return site_read_json($site_id, 'version_history.json');
}

function site_get_improvements(string $site_id): array {
    return site_read_json($site_id, 'improvements.json');
}

function site_get_changelog(string $site_id): array {
    return site_read_json($site_id, 'changelog.json');
}

// ════════════════════════════════════════════════════════
// SemVer 도구
// ════════════════════════════════════════════════════════

function parse_semver(string $v): array {
    $parts = explode('.', $v);
    return [
        'major' => (int)($parts[0] ?? 0),
        'minor' => (int)($parts[1] ?? 0),
        'patch' => (int)($parts[2] ?? 0),
    ];
}

function bump_version(array $current, string $type): array {
    $next = [
        'major' => $current['major'],
        'minor' => $current['minor'],
        'patch' => $current['patch'],
    ];
    switch ($type) {
        case 'major': $next['major']++; $next['minor'] = 0; $next['patch'] = 0; break;
        case 'minor': $next['minor']++; $next['patch'] = 0; break;
        case 'patch': $next['patch']++; break;
    }
    $next['raw'] = "{$next['major']}.{$next['minor']}.{$next['patch']}";
    return $next;
}

// ════════════════════════════════════════════════════════
// 전체 통계
// ════════════════════════════════════════════════════════

function get_system_stats(): array {
    $sites = get_active_sites();
    $prod_sites = array_filter($sites, fn($s) => ($s['environment'] ?? '') === 'production');
    $dev_sites  = array_filter($sites, fn($s) => ($s['environment'] ?? '') === 'development');
    $servers = [];
    foreach ($sites as $s) {
        $servers[$s['server_hostname'] ?? 'unknown'] = true;
    }

    return [
        'total_sites'       => count($sites),
        'production_sites'  => count($prod_sites),
        'development_sites' => count($dev_sites),
        'total_servers'     => count($servers),
        'sites'             => $sites,
    ];
}

// ════════════════════════════════════════════════════════
// 현재 선택된 사이트 (URL 파라미터 또는 기본값)
// ════════════════════════════════════════════════════════

function get_current_site_id(): ?string {
    $id = $_GET['site'] ?? null;
    if ($id && get_site($id)) return $id;

    // site 파라미터 없으면 첫 번째 활성 사이트
    $sites = get_active_sites();
    if (!empty($sites)) {
        $first = array_key_first($sites);
        return $first;
    }
    return null;
}

// ════════════════════════════════════════════════════════
// 하위호환 래퍼 함수 (옛날 코드가 새 구조에서도 동작하도록)
// 기존 함수명 → site_* 함수로 자동 연결
// 현재 선택된 사이트(get_current_site_id()) 기준으로 동작
// ════════════════════════════════════════════════════════

/** @deprecated use site_get_package(get_current_site_id()) instead */
function get_package(): array {
    $sid = get_current_site_id();
    return $sid ? site_get_package($sid) : [];
}

/** @deprecated use site_get_current_version(get_current_site_id()) instead */
function get_current_version(): array {
    $sid = get_current_site_id();
    return $sid ? site_get_current_version($sid) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
}

/** @deprecated use site_get_history(get_current_site_id()) instead */
function get_version_history(): array {
    $sid = get_current_site_id();
    return $sid ? site_get_history($sid) : [];
}

/** @deprecated use site_get_improvements(get_current_site_id()) instead */
function get_improvements(): array {
    $sid = get_current_site_id();
    return $sid ? site_get_improvements($sid) : [];
}

/** @deprecated use site_get_changelog(get_current_site_id()) instead */
function get_changelog(): array {
    $sid = get_current_site_id();
    return $sid ? site_get_changelog($sid) : [];
}

/** @deprecated use site_write_json(get_current_site_id(), ...) instead */
function write_json(string $file, array $data): bool {
    $sid = get_current_site_id();
    if (!$sid) return false;
    return site_write_json($sid, $file, $data);
}

/** @deprecated use site_read_json(get_current_site_id(), ...) instead */
function read_json(string $file): array {
    $sid = get_current_site_id();
    return $sid ? site_read_json($sid, $file) : [];
}

// 이전 config.php에서 사용되던 상수들 (호환성 유지)
if (!defined('APP_NAME')) define('APP_NAME', 'Onlyone OneChat');
if (!defined('APP_SLUG')) define('APP_SLUG', 'vermanager');
if (!defined('SRC_IMPROVEMENTS_URL')) define('SRC_IMPROVEMENTS_URL', 'https://kiam.kr/admin/admin_source_improvements.php');