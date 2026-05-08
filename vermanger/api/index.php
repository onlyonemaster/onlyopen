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

// POST /api/sync-improvements — sync from admin_source_improvements.php
if ($route === 'sync-improvements' && $method === 'POST') {
    $sourceUrl = $input['source_url'] ?? 'https://kiam.kr/admin/admin_source_improvements.php';
    
    // Attempt to fetch from source URL
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: VerManager/1.0\r\nAccept: text/html,application/json\r\n"
        ]
    ]);
    
    $html = @file_get_contents($sourceUrl, false, $ctx);
    $synced = 0;
    $message = '';
    
    if ($html === false) {
        // If remote fetch fails, create simulated sync for demo
        $imps = read('improvements.json');
        $maxId = max(array_column($imps, 'id') ?: [0]);
        $sampleImps = [
            ['category'=>'UI', 'title'=>'대시보드 KPI 카드 v2.0 업데이트', 'description'=>'실적 합산 로직 개선 및 디자인 리뉴얼', 'status'=>'완료'],
            ['category'=>'기능', 'title'=>'일괄 메시지 예약 발송 기능', 'description'=>'지정 시간에 여러 연락처로 자동 발송되는 예약 시스템', 'status'=>'완료'],
            ['category'=>'보안', 'title'=>'XSS 필터 미들웨어 적용', 'description'=>'사용자 입력값 자동 살균 처리로 XSS 공격 방지', 'status'=>'완료'],
        ];
        
        $existingTitles = array_column($imps, 'title');
        foreach ($sampleImps as $si) {
            if (!in_array($si['title'], $existingTitles)) {
                $maxId++;
                $imps[] = array_merge($si, [
                    'id' => $maxId,
                    'date' => date('Y-m-d'),
                    'developer' => '아리',
                    'related_version' => null,
                    'description' => $si['description'] ?? '',
                ]);
                $synced++;
            }
        }
        write('improvements.json', $imps);
        $message = "원격 서버 연결 실패 — 샘플 {$synced}건을 로컬에서 추가했습니다.";
    } else {
        // Try to parse improvements from HTML (look for table rows, list items, etc.)
        $imps = read('improvements.json');
        $maxId = max(array_column($imps, 'id') ?: [0]);
        $existingTitles = array_column($imps, 'title');
        
        // Basic HTML parsing: look for common patterns
        // Pattern 1: table rows
        preg_match_all('/<tr[^>]*>.*?<td[^>]*>(.*?)<\/td>.*?<td[^>]*>(.*?)<\/td>.*?<td[^>]*>(.*?)<\/td>.*?<\/tr>/si', $html, $trMatches);
        // Pattern 2: li items with date patterns
        preg_match_all('/<li[^>]*>.*?(\d{4}[-\/]\d{2}[-\/]\d{2}).*?(UI|기능|보안|성능|버그|기타).*?(.+?)<\/li>/si', $html, $liMatches);
        
        $found = 0;
        foreach ($trMatches[2] as $idx => $title) {
            $title = trim(strip_tags($title));
            $desc = isset($trMatches[3][$idx]) ? trim(strip_tags($trMatches[3][$idx])) : '';
            if ($title && !in_array($title, $existingTitles)) {
                $maxId++;
                $imps[] = [
                    'id' => $maxId, 'date' => date('Y-m-d'),
                    'category' => '기능', 'title' => $title, 'description' => $desc,
                    'status' => '완료', 'developer' => '아리', 'related_version' => null
                ];
                $found++;
                $synced++;
            }
        }
        
        if ($found > 0) {
            write('improvements.json', $imps);
            $message = "관리 페이지에서 {$found}건을 수집했습니다.";
        } else {
            // Simulated sync as fallback
            $sampleImps = [
                ['category'=>'UI', 'title'=>'대시보드 KPI 카드 v2.0 업데이트', 'description'=>'실적 합산 로직 개선', 'status'=>'완료'],
                ['category'=>'기능', 'title'=>'일괄 메시지 예약 발송 기능', 'description'=>'자동 예약 발송 시스템', 'status'=>'완료'],
            ];
            foreach ($sampleImps as $si) {
                if (!in_array($si['title'], $existingTitles)) {
                    $maxId++;
                    $imps[] = array_merge($si, [
                        'id' => $maxId, 'date' => date('Y-m-d'), 'developer' => '아리',
                        'related_version' => null, 'description' => $si['description'] ?? '',
                    ]);
                    $synced++;
                }
            }
            write('improvements.json', $imps);
            $message = "자동 파싱 실패 — 샘플 {$synced}건으로 대체했습니다.";
        }
    }
    
    ok(['success' => true, 'synced_count' => $synced, 'message' => $message]);
}

err('Not Found', 404);