<?php
/**
 * VerManager API Router
 * All routes: /vermanger/api/{resource}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route  = trim(str_replace('/vermanger/api/', '', $uri), '/');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

define('DATA_DIR', __DIR__ . '/../data/');

function read($file) {
    $p = DATA_DIR . $file;
    return file_exists($p) ? json_decode(file_get_contents($p), true) ?? [] : [];
}
function write($file, $data) {
    file_put_contents(DATA_DIR . $file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function ok($data, $c = 200) { http_response_code($c); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $c = 400) { ok(['error' => $msg], $c); }

// ── ROUTES ──

// GET /api/version
if ($route === 'version' && $method === 'GET') {
    ok(read('version.json'));
}

// POST /api/release — new release
if ($route === 'release' && $method === 'POST') {
    $type = $input['type'] ?? 'patch';
    if (!in_array($type, ['major','minor','patch'])) err('Invalid type');

    $note   = $input['note'] ?? '';
    $impids = $input['improvement_ids'] ?? [];
    $ver    = read('version.json');
    $cur    = $ver['current_version'] ?? '1.0.0';
    [$maj, $min, $pat] = array_map('intval', explode('.', $cur));

    if ($type === 'major')      { $maj++; $min=0; $pat=0; }
    elseif ($type === 'minor')  { $min++; $pat=0; }
    else                        { $pat++; }

    $new = "$maj.$min.$pat";
    $rel = ['version' => $new, 'type' => $type, 'date' => date('Y-m-d H:i:s'),
            'note' => $note, 'improvement_ids' => $impids, 'previous_version' => $cur];

    $ver['current_version'] = $new;
    $ver['last_release']    = $rel['date'];
    $ver['releases'][]      = $rel;
    write('version.json', $ver);

    // Link improvements to version
    if ($impids) {
        $imps = read('improvements.json');
        foreach ($imps as &$i) {
            if (in_array($i['id'], $impids)) $i['related_version'] = $new;
        }
        write('improvements.json', $imps);
    }

    // Changelog
    $labels = ['major'=>'🔴 MAJOR','minor'=>'🟡 MINOR','patch'=>'🟢 PATCH'];
    $entry  = "## [$new] - " . date('Y-m-d') . "\n\n";
    $entry .= "**{$labels[$type]}** - v$cur → v$new\n\n";
    if ($note) $entry .= "- $note\n";
    if ($impids) {
        $imps = read('improvements.json');
        foreach ($imps as $i) {
            if (in_array($i['id'], $impids)) $entry .= "- [{$i['category']}] {$i['title']}\n";
        }
    }
    $entry .= "\n";
    $cl = DATA_DIR . 'CHANGELOG.md';
    $hdr = "# Onlyone OneChat CHANGELOG\n\n모든 변경사항을 기록합니다.\n\n";
    $old = file_exists($cl) ? file_get_contents($cl) : $hdr;
    $lines = explode("\n", $old);
    $pos = count($lines);
    foreach ($lines as $idx => $l) { if ($idx > 1 && strpos($l, '## ') === 0) { $pos = $idx; break; } }
    array_splice($lines, $pos, 0, explode("\n", rtrim($entry)));
    file_put_contents($cl, implode("\n", $lines));

    ok(['success'=>true, 'previous_version'=>$cur, 'new_version'=>$new, 'type'=>$type,
        'display_string'=>"Onlyone OneChat v$new", 'release'=>$rel]);
}

// GET /api/improvements
if ($route === 'improvements' && $method === 'GET') {
    $imps = read('improvements.json');
    if ($c = $_GET['category'] ?? null) $imps = array_values(array_filter($imps, fn($i)=>($i['category']??'')===$c));
    if ($s = $_GET['status'] ?? null)   $imps = array_values(array_filter($imps, fn($i)=>($i['status']??'')===$s));
    if (($u = $_GET['unversioned'] ?? null) !== null)
        $imps = array_values(array_filter($imps, fn($i)=> $u==='true' ? empty($i['related_version']) : !empty($i['related_version'])));
    ok($imps);
}

// POST /api/improvements
if ($route === 'improvements' && $method === 'POST') {
    $imps = read('improvements.json');
    $max  = max(array_column($imps, 'id') ?: [0]);
    $item = ['id'=>$max+1, 'date'=>$input['date']??date('Y-m-d'), 'category'=>$input['category']??'기타',
             'title'=>$input['title']??'', 'description'=>$input['description']??'',
             'status'=>$input['status']??'완료', 'developer'=>$input['developer']??'아리',
             'related_version'=>$input['related_version']??null];
    if (!$item['title']) err('Title required');
    $imps[] = $item;
    write('improvements.json', $imps);
    ok($item, 201);
}

// PUT /api/improvements/{id}
if (preg_match('#^improvements/(\d+)$#', $route, $m) && $method === 'PUT') {
    $id = (int)$m[1]; $imps = read('improvements.json'); $found = false;
    foreach ($imps as &$i) { if ($i['id'] === $id) { $i = array_merge($i, $input); $i['id'] = $id; $found = true; break; } }
    if (!$found) err('Not found', 404);
    write('improvements.json', $imps);
    ok($i);
}

// DELETE /api/improvements/{id}
if (preg_match('#^improvements/(\d+)$#', $route, $m) && $method === 'DELETE') {
    $id = (int)$m[1]; $imps = read('improvements.json');
    write('improvements.json', array_values(array_filter($imps, fn($i)=>$i['id']!==$id)));
    ok(['deleted'=>$id]);
}

// GET /api/stats
if ($route === 'stats' && $method === 'GET') {
    $ver  = read('version.json'); $imps = read('improvements.json');
    $wk   = date('Y-m-d', strtotime('-7 days'));
    ok(['current_version'=>$ver['current_version'], 'total_releases'=>count($ver['releases']??[]),
        'total_improvements'=>count($imps), 'this_week'=>count(array_filter($imps, fn($i)=>($i['date']??'')>=$wk)),
        'unversioned'=>count(array_filter($imps, fn($i)=>empty($i['related_version'])))]);
}

// GET /api/changelog
if ($route === 'changelog' && $method === 'GET') {
    $p = DATA_DIR . 'CHANGELOG.md';
    ok(['content'=>file_exists($p)?file_get_contents($p):'']);
}

err('Not Found', 404);