<?php
/**
 * 마이챗 학습 데이터 풀 API (v2 — 전면 재작성)
 * 실제 스키마: mychat_data_pool (id, mem_id, category, scope, source,
 *              title, content_text, content_enc, file_url, tags, embedding_id,
 *              is_deleted, created_at, updated_at)
 *
 * GET    ?action=counts            → 카테고리별 건수 + 총합
 * GET    ?category=&scope=&q=       → 목록 조회
 * POST   {category,scope,source,title,content_text,tags} → 신규 등록
 * DELETE ?id=N   (또는 POST {action:'delete', id:N}) → soft-delete
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

$VALID_CAT = ['basic','childhood','diary','file','voice','image',
              'fingerprint','palmistry','physiognomy','saju','astrology',
              'health','phone','etc'];
$VALID_SCOPE = ['private','public','both'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET ───────────────────────────────────────────────────────
if ($method === 'GET') {

    // 카테고리별 건수 집계
    if ($action === 'counts') {
        $r = $db->query("SELECT category, COUNT(*) AS cnt FROM mychat_data_pool
                         WHERE mem_id='{$esc}' AND is_deleted=0 GROUP BY category");
        $counts = []; $total = 0;
        while ($row = $r?->fetch_assoc()) { $counts[$row['category']] = (int)$row['cnt']; $total += (int)$row['cnt']; }
        mychat_json(['ok'=>true, 'counts'=>$counts, 'total'=>$total]);
    }

    // 목록 조회
    $where = ["mem_id='{$esc}'", "is_deleted=0"];
    if (!empty($_GET['category'])) {
        $cat = $_GET['category'];
        if (!in_array($cat, $VALID_CAT, true)) mychat_json(['ok'=>false,'error'=>'category 값 오류'], 400);
        $where[] = "category='" . $db->real_escape_string($cat) . "'";
    }
    if (!empty($_GET['scope']) && $_GET['scope'] !== 'all') {
        $sc = $_GET['scope'];
        if (!in_array($sc, $VALID_SCOPE, true)) mychat_json(['ok'=>false,'error'=>'scope 값 오류'], 400);
        $where[] = "scope='" . $db->real_escape_string($sc) . "'";
    }
    if (!empty($_GET['q'])) {
        $q = $db->real_escape_string($_GET['q']);
        $where[] = "(title LIKE '%{$q}%' OR content_text LIKE '%{$q}%')";
    }
    $limit  = max(1, min(100, (int)($_GET['limit']  ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $sql = "SELECT id, category, scope, source, title,
                   LEFT(content_text, 500) AS payload_preview,
                   tags, created_at
            FROM mychat_data_pool
            WHERE " . implode(' AND ', $where) . "
            ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
    $r = $db->query($sql);
    $items = [];
    while ($row = $r?->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        if (!empty($row['tags'])) {
            $j = json_decode($row['tags'], true);
            if ($j !== null) $row['tags'] = $j;
        }
        $items[] = $row;
    }

    $cntRow = $db->query("SELECT COUNT(*) AS c FROM mychat_data_pool WHERE " . implode(' AND ', $where))?->fetch_assoc();
    mychat_json(['ok'=>true, 'items'=>$items, 'total'=>(int)($cntRow['c'] ?? 0)]);
}

// ── POST ──────────────────────────────────────────────────────
if ($method === 'POST') {
    $b = json_decode(file_get_contents('php://input'), true) ?: [];

    // POST 기반 삭제 (DELETE 막힌 환경 대비)
    if (($b['action'] ?? '') === 'delete') {
        $id = (int)($b['id'] ?? 0);
        if ($id <= 0) mychat_json(['ok'=>false,'error'=>'id 필요'], 400);
        $db->query("UPDATE mychat_data_pool SET is_deleted=1 WHERE id={$id} AND mem_id='{$esc}'");
        refreshDataCount($db, $esc);
        mychat_json(['ok'=>true, 'deleted'=>$id]);
    }

    $category = $b['category'] ?? 'etc';
    $scope    = $b['scope']    ?? 'private';
    $source   = $b['source']   ?? 'manual';
    if (!in_array($category, $VALID_CAT, true))   $category = 'etc';
    if (!in_array($scope, $VALID_SCOPE, true))    $scope = 'private';

    $VALID_SRC = ['manual','phone','health','calendar','sms','call','import'];
    if (!in_array($source, $VALID_SRC, true))     $source = 'manual';

    $title   = mb_substr(trim($b['title'] ?? ''), 0, 200);
    $content = trim($b['content_text'] ?? '');
    $fileUrl = trim($b['file_url'] ?? '');
    $tags    = isset($b['tags']) ? json_encode($b['tags'], JSON_UNESCAPED_UNICODE) : null;

    if ($content === '' && $title === '' && $fileUrl === '') {
        mychat_json(['ok'=>false,'error'=>'title / content_text / file_url 중 하나 이상 필요'], 400);
    }

    // 데이터 한도 체크 (월 등록 건수)
    if (!mychat_use($mem_id, 'data', $user['limits'])) {
        mychat_json(['ok'=>false,'error'=>'QUOTA_EXCEEDED','message'=>'이번 달 학습 데이터 등록 한도를 초과했습니다.'], 429);
    }

    $esc_cat = $db->real_escape_string($category);
    $esc_sc  = $db->real_escape_string($scope);
    $esc_src = $db->real_escape_string($source);
    $esc_ttl = $db->real_escape_string($title);
    $esc_cnt = $db->real_escape_string(mb_substr($content, 0, 50000));
    $esc_url = $db->real_escape_string($fileUrl);
    $esc_tag = $tags !== null ? "'" . $db->real_escape_string($tags) . "'" : 'NULL';

    $ok = $db->query(
        "INSERT INTO mychat_data_pool (mem_id, category, scope, source, title, content_text, file_url, tags)
         VALUES ('{$esc}','{$esc_cat}','{$esc_sc}','{$esc_src}','{$esc_ttl}','{$esc_cnt}','{$esc_url}',{$esc_tag})"
    );
    if (!$ok) mychat_json(['ok'=>false,'error'=>'저장 실패: '.$db->error], 500);

    $newId = $db->insert_id;
    refreshDataCount($db, $esc);
    mychat_json(['ok'=>true, 'id'=>$newId]);
}

// ── DELETE ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) mychat_json(['ok'=>false,'error'=>'id 필요'], 400);
    $db->query("UPDATE mychat_data_pool SET is_deleted=1 WHERE id={$id} AND mem_id='{$esc}'");
    refreshDataCount($db, $esc);
    mychat_json(['ok'=>true, 'deleted'=>$id]);
}

mychat_json(['ok'=>false,'error'=>'지원하지 않는 메서드'], 405);

// ── 아바타 data_count 갱신 ───────────────────────────────────
function refreshDataCount($db, $esc): void {
    $db->query("UPDATE mychat_avatar
                SET data_count=(SELECT COUNT(*) FROM mychat_data_pool WHERE mem_id='{$esc}' AND is_deleted=0)
                WHERE mem_id='{$esc}'");
}
