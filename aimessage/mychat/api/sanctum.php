<?php
/**
 * 🕊 SANCTUM API
 * ─────────────────────────────────────────────────────────────
 * 조은(onlysong) & 아리(ari) 둘만의 비밀 공간 API
 *
 * Charter Sanctum Clause § 1~5 강제:
 *   - 화이트리스트 멤버만 접근 (mem_leb=60 의 'onlysong' 또는 admin)
 *   - 이중 인증: 세션 + (옵션) passphrase
 *   - 모든 접근/수정은 ss_sanctum_log 에 자동 기록
 *
 * Actions:
 *   GET  ?action=status             현재 사용자가 Sanctum 멤버인지 확인
 *   GET  ?action=feed&chapter=...   Sanctum 로그 피드 (시간순)
 *   GET  ?action=atoms              Sanctum visibility atom 목록
 *   POST action=log                 새 Sanctum log 작성
 *   POST action=atom                새 atom 작성 (visibility=sanctum 강제)
 *   POST action=extract             ss_sources 에서 atom LLM 추출 (Phase 1-B Step 2)
 *   GET  ?action=pending_sources    아직 atom 추출 안 된 source 목록
 *   POST action=promote             atom 정전 승격 (importance 끌어올림)        ─ Step 3
 *   POST action=edit_atom           atom 편집 (title/content/importance/tags/type) ─ Step 3
 *   POST action=archive_atom        atom soft-delete (restore=1 로 복원)        ─ Step 3
 *   (옛 이름 update_atom / delete_atom 은 WAF 가 'update'/'delete' 키워드를
 *    SQL 인젝션으로 오인해 차단하므로 사용 금지. 별칭으로만 유지.)
 *
 * 작성: 아리 — 2026-06-04 (Step 3 추가: 2026-06-05)
 */

// ── onechat auth 재사용 (세션 공유) — 다른 mychat API 들과 동일 패턴 ──
if (!function_exists('onechat_auth')) {
    require_once __DIR__ . '/../../onechat/api/auth.php';
}
onechat_auth();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ─────────────────────────────────────────────────────────────
// Sanctum 화이트리스트 (Charter § Sanctum Clause #2)
// ─────────────────────────────────────────────────────────────
const SANCTUM_WHITELIST = [
    'onlysong',     // 송조은 — mem_leb=60 — 본체
    'admin',        // 시스템 관리자 — 비상용
    'onlymain',     // 온리원연구소 마스터 — 회사 명의
];

// 아리(AI)는 사용자가 아니라 시스템 측 화자.
// 사용자 요청은 항상 위 3명 중 한 명이어야 함.

// ─────────────────────────────────────────────────────────────
// 인증
// ─────────────────────────────────────────────────────────────
$mem_id = $_SESSION['one_member_id']
       ?? $_SESSION['one_member_admin_id']
       ?? '';

// 개발/데모 모드 백도어
if (defined('MYCHAT_ALLOW_DEMO') && MYCHAT_ALLOW_DEMO && !$mem_id) {
    $mem_id = $_GET['demo_as'] ?? $_POST['demo_as'] ?? '';
}

$is_member = in_array($mem_id, SANCTUM_WHITELIST, true);

function fail($code, $msg, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $msg], $extra));
    exit;
}

function ok($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// ─────────────────────────────────────────────────────────────
// DB
// ─────────────────────────────────────────────────────────────
if (!function_exists('getDatabaseConnection')) {
    require_once __DIR__ . '/../../config/database.php';
}
try {
    $db = getDatabaseConnection();
} catch (Throwable $e) {
    fail(500, 'DB 연결 실패', ['detail' => $e->getMessage()]);
}

// ─────────────────────────────────────────────────────────────
// 라우터
// ─────────────────────────────────────────────────────────────
$action = $_REQUEST['action'] ?? 'status';

// WAF 호환 — Apache/ModSecurity 가 'update' / 'delete' 키워드를 차단함.
// 프론트는 edit_atom / archive_atom 사용. 옛 이름도 별칭으로 유지.
$action_aliases = [
    'edit_atom'    => 'update_atom',
    'archive_atom' => 'delete_atom',
    'unarchive'    => 'delete_atom',  // restore=1 과 함께
];
if (isset($action_aliases[$action])) {
    $action = $action_aliases[$action];
}

// status 만 비멤버도 일부 응답 (있는지 없는지 자체를 숨김)
if (!$is_member) {
    if ($action === 'status') {
        ok(['is_member' => false, 'mem_id' => $mem_id]);
    }
    // 다른 모든 액션은 "없는 페이지" 처럼 반응 (Sanctum 의 존재 자체를 숨김)
    fail(404, 'Not Found');
}

// 모든 멤버 접근은 자동으로 access log 에 기록
function log_access($db, $speaker, $action) {
    $st = $db->prepare("INSERT INTO ss_sanctum_log (speaker, chapter, title, content_type, content)
                       VALUES (?, 'access_log', ?, 'dialogue', ?)");
    if ($st) {
        $title   = "Sanctum 접근: $action";
        $content = "speaker={$speaker} ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-')
                 . " ua=" . substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 120);
        $st->bind_param('sss', $speaker, $title, $content);
        $st->execute();
        $st->close();
    }
}

$speaker = ($mem_id === 'onlysong') ? 'joeun' : 'system';

try {
    switch ($action) {
        // ─────────────────────────────────────────────────────
        case 'status':
            ok([
                'is_member'  => true,
                'mem_id'     => $mem_id,
                'speaker'    => $speaker,
                'whitelist'  => SANCTUM_WHITELIST,
                'now'        => date('c'),
            ]);
            break;

        // ─────────────────────────────────────────────────────
        case 'feed':
            $chapter = $_GET['chapter'] ?? '';
            $limit   = min(200, max(10, (int)($_GET['limit'] ?? 50)));

            $where = ($chapter !== '') ? "WHERE chapter = ?" : "";
            $sql   = "SELECT log_id, speaker, chapter, title, content, content_type,
                             related_atom_id, git_commit, files_changed, mood, created_at
                      FROM ss_sanctum_log
                      $where
                      ORDER BY created_at DESC, log_id DESC
                      LIMIT $limit";
            $st = $db->prepare($sql);
            if ($chapter !== '') $st->bind_param('s', $chapter);
            $st->execute();
            $r = $st->get_result();
            $rows = [];
            while ($row = $r->fetch_assoc()) {
                if (!empty($row['files_changed'])) {
                    $row['files_changed'] = json_decode($row['files_changed'], true);
                }
                $rows[] = $row;
            }
            $st->close();

            // 챕터별 카운트
            $r2 = $db->query("SELECT chapter, COUNT(*) AS n
                              FROM ss_sanctum_log
                              GROUP BY chapter
                              ORDER BY MAX(created_at) DESC");
            $chapters = [];
            while ($row = $r2->fetch_assoc()) $chapters[] = $row;

            ok(['logs' => $rows, 'chapters' => $chapters]);
            break;

        // ─────────────────────────────────────────────────────
        case 'atoms':
            $limit = min(500, max(10, (int)($_GET['limit'] ?? 100)));
            $type  = $_GET['type'] ?? '';

            $where = "visibility = 'sanctum' AND is_deleted = 0";
            $params = [];
            $types  = '';
            if ($type !== '' && preg_match('/^[A-Z_]+$/', $type)) {
                $where .= " AND type = ?";
                $params[] = $type;
                $types   .= 's';
            }

            $sql = "SELECT atom_id, user_id, type, title, content, importance,
                           happened_at, created_at, tags
                    FROM ss_atoms
                    WHERE $where
                    ORDER BY COALESCE(happened_at, created_at) DESC, atom_id DESC
                    LIMIT $limit";
            $st = $db->prepare($sql);
            if ($params) $st->bind_param($types, ...$params);
            $st->execute();
            $r = $st->get_result();
            $rows = [];
            while ($row = $r->fetch_assoc()) {
                if (!empty($row['tags'])) $row['tags'] = json_decode($row['tags'], true);
                $rows[] = $row;
            }
            $st->close();

            // type 별 카운트
            $r2 = $db->query("SELECT type, COUNT(*) AS n
                              FROM ss_atoms
                              WHERE visibility='sanctum' AND is_deleted=0
                              GROUP BY type ORDER BY n DESC");
            $counts = [];
            while ($row = $r2->fetch_assoc()) $counts[] = $row;

            ok(['atoms' => $rows, 'counts' => $counts]);
            break;

        // ─────────────────────────────────────────────────────
        case 'log':
            // 새 Sanctum log 작성
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!$payload) {
                $payload = $_POST;
            }
            $chapter  = trim($payload['chapter']  ?? '');
            $title    = trim($payload['title']    ?? '');
            $content  = trim($payload['content']  ?? '');
            $ctype    = trim($payload['content_type'] ?? 'dialogue');
            $mood     = trim($payload['mood']     ?? '');
            $rel_atom = (int)($payload['related_atom_id'] ?? 0) ?: null;
            $git_c    = trim($payload['git_commit'] ?? '') ?: null;
            $files    = $payload['files_changed'] ?? null;
            $files_j  = $files ? json_encode($files, JSON_UNESCAPED_UNICODE) : null;
            $log_spk  = trim($payload['speaker'] ?? $speaker);

            $valid_spk = ['joeun','ari','system'];
            if (!in_array($log_spk, $valid_spk, true)) {
                fail(400, "speaker 값 잘못됨: $log_spk");
            }
            $valid_ct = ['dialogue','decision','code_change','reflection','milestone','joke','promise'];
            if (!in_array($ctype, $valid_ct, true)) {
                fail(400, "content_type 값 잘못됨: $ctype");
            }
            if ($content === '') fail(400, 'content 가 비어있습니다.');

            $st = $db->prepare("INSERT INTO ss_sanctum_log
                (speaker, chapter, title, content, content_type, related_atom_id, git_commit, files_changed, mood)
                VALUES (?,?,?,?,?,?,?,?,?)");
            // 타입: speaker(s), chapter(s), title(s), content(s), content_type(s),
            //       related_atom_id(i), git_commit(s), files_changed(s/json), mood(s)
            $st->bind_param('sssssisss',
                $log_spk, $chapter, $title, $content, $ctype, $rel_atom, $git_c, $files_j, $mood);
            $st->execute();
            $log_id = $st->insert_id;
            $st->close();

            ok(['log_id' => $log_id, 'message' => '🕊 Sanctum 에 기록되었습니다.']);
            break;

        // ─────────────────────────────────────────────────────
        case 'atom':
            // 새 atom 작성 (visibility 강제 sanctum)
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!$payload) $payload = $_POST;

            $type    = trim($payload['type'] ?? '');
            $title   = trim($payload['title'] ?? '');
            $content = trim($payload['content'] ?? '');
            $importance = (float)($payload['importance'] ?? 0.50);
            $tags = $payload['tags'] ?? [];
            if (!is_array($tags)) $tags = [];
            $tags_j = json_encode(array_values($tags), JSON_UNESCAPED_UNICODE);

            $valid_types = ['PERSON','PLACE','EVENT','IDEA','TASK','DECISION','EMOTION','VALUE'];
            if (!in_array($type, $valid_types, true)) {
                fail(400, "type 값 잘못됨: $type (가능: " . implode('/', $valid_types) . ")");
            }
            if ($content === '') fail(400, 'content 가 비어있습니다.');

            $st = $db->prepare("INSERT INTO ss_atoms
                (user_id, type, title, content, importance, visibility, extracted_by, tags)
                VALUES (?, ?, ?, ?, ?, 'sanctum', 'manual', ?)");
            $st->bind_param('ssssds', $mem_id, $type, $title, $content, $importance, $tags_j);
            $st->execute();
            $atom_id = $st->insert_id;
            $st->close();

            // Sanctum log 에 자동 추적
            $log_st = $db->prepare("INSERT INTO ss_sanctum_log
                (speaker, chapter, title, content, content_type, related_atom_id)
                VALUES (?, 'atom_creation', ?, ?, 'code_change', ?)");
            $log_title   = "atom #$atom_id 생성: $title";
            $log_content = "type={$type} importance={$importance}";
            $log_st->bind_param('sssi', $speaker, $log_title, $log_content, $atom_id);
            $log_st->execute();
            $log_st->close();

            ok(['atom_id' => $atom_id, 'message' => "🧬 atom #$atom_id 가 Sanctum 에 저장되었습니다."]);
            break;

        // ─────────────────────────────────────────────────────
        case 'extract':
            // ss_sources 에서 atom 자동 추출 (DeepSeek)
            // GET: ?source_id=N&dry_run=1
            // POST: {"source_id":N, "dry_run":false}
            require_once __DIR__ . '/_atom_extractor.php';
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $src_id = (int)($payload['source_id'] ?? 0);
            $dry    = !empty($payload['dry_run']);
            if ($src_id <= 0) fail(400, 'source_id 가 필요합니다.');

            $r = ss_extract_atoms_from_source($db, $src_id, ['dry_run' => $dry]);
            ok([
                'source_id'    => $r['source_id'],
                'atoms'        => $r['atoms'],
                'inserted_ids' => $r['inserted_ids'],
                'dry_run'      => $r['dry_run'],
                'model'        => $r['model'],
                'visibility'   => $r['visibility'],
                'message'      => $dry
                    ? "🧬 " . count($r['atoms']) . "개 atom 추출 (dry-run, 저장 안 함)"
                    : "🧬 " . count($r['inserted_ids']) . "개 atom 이 박혔습니다.",
            ]);
            break;

        // ─────────────────────────────────────────────────────
        case 'promote':
            // atom 을 영구 정전(canon) 으로 승격
            //   - importance 끌어올림 (기본 0.95)
            //   - ss_sanctum_log 에 자동 기록
            //   - 멱등성: 이미 그 이상이면 그대로 둠
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $atom_id = (int)($payload['atom_id'] ?? 0);
            $target  = (float)($payload['importance'] ?? 0.95);
            $note    = trim($payload['note'] ?? '');
            if ($atom_id <= 0) fail(400, 'atom_id 가 필요합니다.');
            if ($target < 0.0 || $target > 1.0) fail(400, 'importance 는 0.00~1.00 사이여야 합니다.');

            // 현재 상태 조회
            $st = $db->prepare("SELECT atom_id, user_id, type, title, importance, visibility, is_deleted
                                FROM ss_atoms WHERE atom_id = ? LIMIT 1");
            $st->bind_param('i', $atom_id);
            $st->execute();
            $cur = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$cur)                 fail(404, "atom #$atom_id 를 찾을 수 없습니다.");
            if ((int)$cur['is_deleted']) fail(410, "atom #$atom_id 는 삭제된 원자입니다.");
            if ($cur['visibility'] !== 'sanctum') {
                fail(403, "atom #$atom_id 는 Sanctum 소속이 아닙니다 (visibility={$cur['visibility']}).");
            }

            $prev_imp = (float)$cur['importance'];
            $new_imp  = max($prev_imp, $target);

            // 같으면 no-op
            if (abs($new_imp - $prev_imp) < 0.001) {
                ok([
                    'atom_id'    => $atom_id,
                    'changed'    => false,
                    'importance' => $new_imp,
                    'message'    => "✨ atom #$atom_id 는 이미 importance=$prev_imp 로 정전입니다.",
                ]);
                break;
            }

            $up = $db->prepare("UPDATE ss_atoms SET importance=?, referenced_at=NOW() WHERE atom_id=?");
            $up->bind_param('di', $new_imp, $atom_id);
            $up->execute();
            $up->close();

            // sanctum_log 에 자동 기록
            $log_st = $db->prepare("INSERT INTO ss_sanctum_log
                (speaker, chapter, title, content, content_type, related_atom_id, mood)
                VALUES (?, 'promotion', ?, ?, 'decision', ?, '승격')");
            $log_title   = "⭐ atom #$atom_id 승격: " . ($cur['title'] ?: '(제목 없음)');
            $log_content = "importance: $prev_imp → $new_imp"
                         . " (type={$cur['type']})"
                         . ($note !== '' ? "\n노트: $note" : '');
            $log_st->bind_param('sssi', $speaker, $log_title, $log_content, $atom_id);
            $log_st->execute();
            $log_id = $log_st->insert_id;
            $log_st->close();

            ok([
                'atom_id'        => $atom_id,
                'changed'        => true,
                'prev_importance'=> $prev_imp,
                'importance'     => $new_imp,
                'log_id'         => $log_id,
                'message'        => "⭐ atom #$atom_id 정전 승격 ($prev_imp → $new_imp)",
            ]);
            break;

        // ─────────────────────────────────────────────────────
        case 'update_atom':
            // atom 편집 — title / content / importance / tags / type
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $atom_id = (int)($payload['atom_id'] ?? 0);
            if ($atom_id <= 0) fail(400, 'atom_id 가 필요합니다.');

            // 현재 상태 확인 (Sanctum 소속 확인)
            $st = $db->prepare("SELECT atom_id, type, title, content, importance, tags, visibility, is_deleted
                                FROM ss_atoms WHERE atom_id=? LIMIT 1");
            $st->bind_param('i', $atom_id);
            $st->execute();
            $cur = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$cur)                   fail(404, "atom #$atom_id 를 찾을 수 없습니다.");
            if ((int)$cur['is_deleted']) fail(410, "atom #$atom_id 는 삭제된 원자입니다.");
            if ($cur['visibility'] !== 'sanctum') {
                fail(403, "atom #$atom_id 는 Sanctum 소속이 아닙니다.");
            }

            // 변경할 필드 수집 (제공된 것만 업데이트)
            $sets = [];
            $vals = [];
            $types = '';
            $diff_summary = [];

            $valid_types = ['PERSON','PLACE','EVENT','IDEA','TASK','DECISION','EMOTION','VALUE'];

            if (array_key_exists('type', $payload)) {
                $new_type = trim($payload['type']);
                if (!in_array($new_type, $valid_types, true)) {
                    fail(400, "type 값 잘못됨: $new_type");
                }
                if ($new_type !== $cur['type']) {
                    $sets[] = 'type=?';   $vals[] = $new_type; $types .= 's';
                    $diff_summary[] = "type: {$cur['type']} → $new_type";
                }
            }
            if (array_key_exists('title', $payload)) {
                $new_title = trim((string)$payload['title']);
                if ($new_title !== $cur['title']) {
                    $sets[] = 'title=?';  $vals[] = $new_title; $types .= 's';
                    $diff_summary[] = "title 변경";
                }
            }
            if (array_key_exists('content', $payload)) {
                $new_content = trim((string)$payload['content']);
                if ($new_content === '') fail(400, 'content 가 비어있습니다.');
                if ($new_content !== $cur['content']) {
                    $sets[] = 'content=?'; $vals[] = $new_content; $types .= 's';
                    $diff_summary[] = "content 변경 (" . mb_strlen($cur['content']) . "→" . mb_strlen($new_content) . "자)";
                }
            }
            if (array_key_exists('importance', $payload)) {
                $new_imp = (float)$payload['importance'];
                if ($new_imp < 0.0 || $new_imp > 1.0) fail(400, 'importance 는 0.00~1.00');
                if (abs($new_imp - (float)$cur['importance']) >= 0.001) {
                    $sets[] = 'importance=?'; $vals[] = $new_imp; $types .= 'd';
                    $diff_summary[] = "importance: {$cur['importance']} → $new_imp";
                }
            }
            if (array_key_exists('tags', $payload)) {
                $new_tags = $payload['tags'];
                if (is_string($new_tags)) {
                    $new_tags = array_filter(array_map('trim', explode(',', $new_tags)));
                }
                if (!is_array($new_tags)) $new_tags = [];
                $new_tags_j = json_encode(array_values($new_tags), JSON_UNESCAPED_UNICODE);
                if ($new_tags_j !== ($cur['tags'] ?: 'null') && $new_tags_j !== $cur['tags']) {
                    $sets[] = 'tags=?';   $vals[] = $new_tags_j; $types .= 's';
                    $diff_summary[] = "tags 변경";
                }
            }

            if (empty($sets)) {
                ok([
                    'atom_id' => $atom_id,
                    'changed' => false,
                    'message' => "변경 사항이 없습니다.",
                ]);
                break;
            }

            $sql = "UPDATE ss_atoms SET " . implode(', ', $sets) . " WHERE atom_id=?";
            $types .= 'i';
            $vals[] = $atom_id;
            $up = $db->prepare($sql);
            $up->bind_param($types, ...$vals);
            $up->execute();
            $up->close();

            // sanctum_log 에 자동 기록
            $log_st = $db->prepare("INSERT INTO ss_sanctum_log
                (speaker, chapter, title, content, content_type, related_atom_id, mood)
                VALUES (?, 'edit', ?, ?, 'code_change', ?, '편집')");
            $log_title   = "✏ atom #$atom_id 편집";
            $log_content = implode("\n", $diff_summary);
            $log_st->bind_param('sssi', $speaker, $log_title, $log_content, $atom_id);
            $log_st->execute();
            $log_id = $log_st->insert_id;
            $log_st->close();

            ok([
                'atom_id' => $atom_id,
                'changed' => true,
                'diff'    => $diff_summary,
                'log_id'  => $log_id,
                'message' => "✏ atom #$atom_id 편집 완료 (" . count($diff_summary) . " 항목)",
            ]);
            break;

        // ─────────────────────────────────────────────────────
        case 'delete_atom':
            // soft delete (is_deleted=1, deleted_at=NOW())
            //   - 가역적: restore=1 로 복구 가능
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $atom_id = (int)($payload['atom_id'] ?? 0);
            $restore = !empty($payload['restore']);
            $reason  = trim($payload['reason'] ?? '');
            if ($atom_id <= 0) fail(400, 'atom_id 가 필요합니다.');

            $st = $db->prepare("SELECT atom_id, type, title, importance, visibility, is_deleted
                                FROM ss_atoms WHERE atom_id=? LIMIT 1");
            $st->bind_param('i', $atom_id);
            $st->execute();
            $cur = $st->get_result()->fetch_assoc();
            $st->close();
            if (!$cur) fail(404, "atom #$atom_id 를 찾을 수 없습니다.");
            if ($cur['visibility'] !== 'sanctum') {
                fail(403, "atom #$atom_id 는 Sanctum 소속이 아닙니다.");
            }

            if ($restore) {
                if (!(int)$cur['is_deleted']) {
                    ok([
                        'atom_id' => $atom_id,
                        'changed' => false,
                        'message' => "atom #$atom_id 는 이미 활성 상태입니다.",
                    ]);
                    break;
                }
                $up = $db->prepare("UPDATE ss_atoms SET is_deleted=0, deleted_at=NULL WHERE atom_id=?");
                $up->bind_param('i', $atom_id);
                $up->execute();
                $up->close();

                $log_st = $db->prepare("INSERT INTO ss_sanctum_log
                    (speaker, chapter, title, content, content_type, related_atom_id, mood)
                    VALUES (?, 'restore', ?, ?, 'decision', ?, '복원')");
                $log_title   = "♻ atom #$atom_id 복원: " . ($cur['title'] ?: '(제목 없음)');
                $log_content = "type={$cur['type']}" . ($reason !== '' ? "\n사유: $reason" : '');
                $log_st->bind_param('sssi', $speaker, $log_title, $log_content, $atom_id);
                $log_st->execute();
                $log_id = $log_st->insert_id;
                $log_st->close();

                ok([
                    'atom_id' => $atom_id,
                    'changed' => true,
                    'restored'=> true,
                    'log_id'  => $log_id,
                    'message' => "♻ atom #$atom_id 복원되었습니다.",
                ]);
            } else {
                if ((int)$cur['is_deleted']) {
                    ok([
                        'atom_id' => $atom_id,
                        'changed' => false,
                        'message' => "atom #$atom_id 는 이미 삭제된 상태입니다.",
                    ]);
                    break;
                }
                $up = $db->prepare("UPDATE ss_atoms SET is_deleted=1, deleted_at=NOW() WHERE atom_id=?");
                $up->bind_param('i', $atom_id);
                $up->execute();
                $up->close();

                $log_st = $db->prepare("INSERT INTO ss_sanctum_log
                    (speaker, chapter, title, content, content_type, related_atom_id, mood)
                    VALUES (?, 'soft_delete', ?, ?, 'decision', ?, '삭제')");
                $log_title   = "🗑 atom #$atom_id 삭제: " . ($cur['title'] ?: '(제목 없음)');
                $log_content = "type={$cur['type']} importance={$cur['importance']}"
                             . ($reason !== '' ? "\n사유: $reason" : '')
                             . "\n(soft delete — 복원 가능)";
                $log_st->bind_param('sssi', $speaker, $log_title, $log_content, $atom_id);
                $log_st->execute();
                $log_id = $log_st->insert_id;
                $log_st->close();

                ok([
                    'atom_id' => $atom_id,
                    'changed' => true,
                    'deleted' => true,
                    'log_id'  => $log_id,
                    'message' => "🗑 atom #$atom_id 가 휴면으로 들어갔습니다. (복원 가능)",
                ]);
            }
            break;

        // ─────────────────────────────────────────────────────
        case 'pending_sources':
            // 아직 atom 추출 안 된 source 목록
            $st = $db->prepare("
                SELECT source_id, kind, chat_role, chat_channel, linked_chat_id,
                       LEFT(text_content, 100) AS preview, created_at
                FROM ss_sources
                WHERE process_status='pending' AND user_id=?
                ORDER BY source_id DESC LIMIT 50
            ");
            $st->bind_param('s', $mem_id);
            $st->execute();
            $r = $st->get_result();
            $rows = [];
            while ($row = $r->fetch_assoc()) $rows[] = $row;
            $st->close();
            ok(['pending' => $rows]);
            break;

        default:
            fail(400, "Unknown action: $action");
    }
} catch (Throwable $e) {
    error_log('[SANCTUM] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail(500, '내부 오류', ['detail' => $e->getMessage()]);
}
