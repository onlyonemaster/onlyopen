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
 *
 * 작성: 아리 — 2026-06-04
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
            $st->bind_param('sssssiSss',
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

        default:
            fail(400, "Unknown action: $action");
    }
} catch (Throwable $e) {
    error_log('[SANCTUM] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail(500, '내부 오류', ['detail' => $e->getMessage()]);
}
