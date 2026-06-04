<?php
/**
 * 원챗(OneChat) 4-Layer Prompt Assembler v2.0
 * ─────────────────────────────────────────────
 * L1 Persona → L2 Policy → L3 Interaction → L4 Safety
 * 우선순위: L4 > L3 > L2 > L1 (상위 Layer가 하위를 override)
 *
 * GET  ?sms_idx=X                    → 최종 통합 프롬프트 조회
 * GET  ?sms_idx=X&preview=1          → 미리보기용 JSON (Layer별 분리)
 * GET  ?sms_idx=X&layer=L1           → 특정 Layer만 조회
 * POST {sms_idx, layer, content}     → 특정 Layer 업데이트 (단건)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$login_esc = $db->real_escape_string($login_id);

// ──────────────────────────────────────────────────────────────────
// 상수 정의
// ──────────────────────────────────────────────────────────────────
define('LAYER_TAGS', [
    'L1' => ['open' => '<PERSONA>',          'close' => '</PERSONA>'],
    'L2' => ['open' => '<POLICY_KNOWLEDGE>', 'close' => '</POLICY_KNOWLEDGE>'],
    'L3' => ['open' => '<INTERACTION_PROTOCOL>', 'close' => '</INTERACTION_PROTOCOL>'],
    'L4' => ['open' => '<SAFETY_GUARDRAILS>','close' => '</SAFETY_GUARDRAILS>'],
]);

define('LAYER_PRIORITY', ['L4', 'L3', 'L2', 'L1']);
define('LAYER_DB_MAP', [
    'L1' => 'prompt_l1_persona',
    'L2' => 'prompt_l2_policy',
    'L3' => 'prompt_l3_interaction',
    'L4' => 'prompt_l4_safety',
]);

// ──────────────────────────────────────────────────────────────────
// 헬퍼: Layer 태그 제거 (순수 컨텐츠 추출)
// ──────────────────────────────────────────────────────────────────
function strip_layer_tags(string $content, string $layer): string {
    $tags = LAYER_TAGS[$layer] ?? null;
    if (!$tags) return $content;

    $content = preg_replace('/^' . preg_quote($tags['open'], '/') . '\s*/', '', $content);
    $content = preg_replace('/\s*' . preg_quote($tags['close'], '/') . '$/', '', $content);
    return trim($content);
}

// ──────────────────────────────────────────────────────────────────
// 헬퍼: Layer 태그 감싸기
// ──────────────────────────────────────────────────────────────────
function wrap_layer_tags(string $content, string $layer): string {
    $tags = LAYER_TAGS[$layer] ?? null;
    if (!$tags) return $content;

    // 이미 태그로 감싸져 있으면 중복 방지
    if (str_contains($content, $tags['open'])) return $content;

    return $tags['open'] . "\n" . $content . "\n" . $tags['close'];
}

// ──────────────────────────────────────────────────────────────────
// 헬퍼: 변경 로그 기록
// ──────────────────────────────────────────────────────────────────
function log_prompt_change($db, int $sms_idx, string $customer_id, string $layer, ?string $old, string $new, string $change_type = 'manual'): void {
    $sms_esc     = (int)$sms_idx;
    $cid_esc     = $db->real_escape_string($customer_id);
    $layer_esc   = $db->real_escape_string($layer);
    $old_esc     = $old !== null ? $db->real_escape_string($old) : 'NULL';
    $new_esc     = $db->real_escape_string($new);
    $type_esc    = $db->real_escape_string($change_type);
    $cid_val     = $db->real_escape_string($customer_id);

    $db->query("
        INSERT INTO Gn_onechat_prompt_log
            (sms_idx, customer_id, layer, old_content, new_content, change_type, changed_by, created_at)
        VALUES
            ({$sms_esc}, '{$cid_esc}', '{$layer_esc}', " . ($old !== null ? "'{$old_esc}'" : "NULL") . ", '{$new_esc}', '{$type_esc}', '{$cid_val}', NOW())
    ");
}

// ──────────────────────────────────────────────────────────────────
// 헬퍼: DB에서 4-Layer 데이터 읽기
// ──────────────────────────────────────────────────────────────────
function load_layers($db, int $sms_idx, string $login_esc): array {
    $r = $db->query("
        SELECT prompt_l1_persona, prompt_l2_policy, prompt_l3_interaction, prompt_l4_safety,
               prompt_layer_active, prompt_version, chatbot_name,
               gpt_sysprompt, message_style, user_gpt_sysprompt
        FROM Gn_aievent_ms_info
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
        LIMIT 1
    ");
    if (!$r || !($row = $r->fetch_assoc())) {
        onechat_json(['success' => false, 'error' => '챗봇을 찾을 수 없습니다.'], 404);
    }

    $layers = [
        'L1' => $row['prompt_l1_persona'] ?? '',
        'L2' => $row['prompt_l2_policy'] ?? '',
        'L3' => $row['prompt_l3_interaction'] ?? '',
        'L4' => $row['prompt_l4_safety'] ?? '',
    ];

    // 마이그레이션 안 된 경우 구 데이터를 fallback으로 사용
    if (empty($layers['L1']) && !empty($row['gpt_sysprompt'])) {
        $layers['L1'] = wrap_layer_tags($row['gpt_sysprompt'], 'L1');
    }
    if (empty($layers['L3']) && !empty($row['message_style'])) {
        $layers['L3'] = wrap_layer_tags($row['message_style'], 'L3');
    }

    $active = array_map('intval', explode(',', $row['prompt_layer_active'] ?? '1,1,1,1'));
    // 인덱스 보정: 4개 미만이면 기본값으로 채움
    while (count($active) < 4) $active[] = 1;

    return [
        'layers'    => $layers,
        'active'    => [
            'L1' => (bool)($active[0] ?? 1),
            'L2' => (bool)($active[1] ?? 1),
            'L3' => (bool)($active[2] ?? 1),
            'L4' => (bool)($active[3] ?? 1),
        ],
        'version'   => (int)($row['prompt_version'] ?? 1),
        'bot_name'  => $row['chatbot_name'] ?? '',
    ];
}

// ──────────────────────────────────────────────────────────────────
// 핵심: 4-Layer → 최종 System Prompt 조립
// ──────────────────────────────────────────────────────────────────
function assemble_prompt(array $layers, array $active, string $bot_name): string {
    $sections = [];

    // 헤더
    $sections[] = "# SYSTEM PROMPT — {$bot_name} AI 아바타 | Generated: " . date('Y-m-d H:i:s');
    $sections[] = "# Layer Priority: L4 (Safety) > L3 (Interaction) > L2 (Policy) > L1 (Persona)";
    $sections[] = "";

    // 우선순위 역순으로 배치 (L1이 먼저 오고 L4가 마지막에 override)
    foreach (['L1', 'L2', 'L3', 'L4'] as $layer) {
        if (!$active[$layer]) {
            $sections[] = "=== {$layer}: [비활성화됨] ===";
            $sections[] = "";
            continue;
        }

        $content = strip_layer_tags($layers[$layer] ?? '', $layer);
        $label = match($layer) {
            'L1' => 'CORE PERSONA (기반)',
            'L2' => 'POLICY DOMAIN (지식)',
            'L3' => 'INTERACTION STYLE (대화전략)',
            'L4' => 'SAFETY & COMPLIANCE (최우선 가드레일)',
            default => $layer,
        };

        if (empty(trim($content))) {
            $sections[] = "=== {$layer}: {$label} [미설정] ===";
        } else {
            $sections[] = "=== {$layer}: {$label} ===";
            $sections[] = $content;
        }
        $sections[] = "";
    }

    return implode("\n", $sections);
}

// ──────────────────────────────────────────────────────────────────
// MAIN
// ──────────────────────────────────────────────────────────────────
$sms_idx = (int)($_GET['sms_idx'] ?? $_POST['sms_idx'] ?? 0);
if (!$sms_idx) {
    onechat_json(['success' => false, 'error' => 'sms_idx 필수'], 400);
}

// ── GET: 프롬프트 조회 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $layer_filter = $_GET['layer'] ?? '';
    $preview_mode = isset($_GET['preview']) && $_GET['preview'] == '1';

    $data = load_layers($db, $sms_idx, $login_esc);

    // 특정 Layer만 요청
    if ($layer_filter && isset(LAYER_DB_MAP[$layer_filter])) {
        onechat_json([
            'success' => true,
            'layer'   => $layer_filter,
            'content' => strip_layer_tags($data['layers'][$layer_filter], $layer_filter),
            'active'  => $data['active'][$layer_filter],
        ]);
    }

    // 미리보기 모드: Layer별 분리 + 통합본 둘다
    if ($preview_mode) {
        $cleaned = [];
        foreach (['L1', 'L2', 'L3', 'L4'] as $l) {
            $cleaned[$l] = [
                'content' => strip_layer_tags($data['layers'][$l], $l),
                'active'  => $data['active'][$l],
            ];
        }

        onechat_json([
            'success'  => true,
            'bot_name' => $data['bot_name'],
            'version'  => $data['version'],
            'layers'   => $cleaned,
            'assembled'=> assemble_prompt($data['layers'], $data['active'], $data['bot_name']),
            'char_count' => [
                'L1' => mb_strlen($cleaned['L1']['content']),
                'L2' => mb_strlen($cleaned['L2']['content']),
                'L3' => mb_strlen($cleaned['L3']['content']),
                'L4' => mb_strlen($cleaned['L4']['content']),
                'total' => mb_strlen(assemble_prompt($data['layers'], $data['active'], $data['bot_name'])),
            ],
        ]);
    }

    // 기본: 통합 프롬프트만
    $assembled = assemble_prompt($data['layers'], $data['active'], $data['bot_name']);

    onechat_json([
        'success'   => true,
        'bot_name'  => $data['bot_name'],
        'version'   => $data['version'],
        'assembled' => $assembled,
        'char_count'=> mb_strlen($assembled),
        'layers_active' => $data['active'],
    ]);
}

// ── POST: Layer 업데이트 ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $layer  = strtoupper(trim($input['layer'] ?? ''));
    $content= $input['content'] ?? null;
    $action = $input['action'] ?? 'update'; // update | toggle | assemble

    // 유효한 Layer인지 확인
    if (!isset(LAYER_DB_MAP[$layer]) && $action !== 'assemble' && $action !== 'toggle') {
        onechat_json(['success' => false, 'error' => '유효하지 않은 Layer: ' . $layer], 400);
    }

    // ── Toggle: Layer 활성화 ON/OFF
    if ($action === 'toggle') {
        $layer_idx = match($layer) { 'L1' => 0, 'L2' => 1, 'L3' => 2, 'L4' => 3, default => -1 };
        if ($layer_idx < 0) {
            onechat_json(['success' => false, 'error' => '유효하지 않은 Layer'], 400);
        }

        // 현재 활성화 상태 읽기
        $r = $db->query("SELECT prompt_layer_active FROM Gn_aievent_ms_info WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}' LIMIT 1");
        $row = $r->fetch_assoc();
        $active_arr = array_map('intval', explode(',', $row['prompt_layer_active'] ?? '1,1,1,1'));
        while (count($active_arr) < 4) $active_arr[] = 1;

        // 토글
        $active_arr[$layer_idx] = $active_arr[$layer_idx] ? 0 : 1;
        $new_active = implode(',', $active_arr);

        $db->query("UPDATE Gn_aievent_ms_info SET prompt_layer_active='{$new_active}' WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'");

        onechat_json([
            'success' => true,
            'layer'   => $layer,
            'active'  => (bool)$active_arr[$layer_idx],
            'active_map' => [
                'L1' => (bool)$active_arr[0],
                'L2' => (bool)$active_arr[1],
                'L3' => (bool)$active_arr[2],
                'L4' => (bool)$active_arr[3],
            ],
        ]);
    }

    // ── Assemble only: 저장 없이 조립만
    if ($action === 'assemble') {
        $data = load_layers($db, $sms_idx, $login_esc);
        $assembled = assemble_prompt($data['layers'], $data['active'], $data['bot_name']);
        onechat_json([
            'success'   => true,
            'assembled' => $assembled,
            'char_count'=> mb_strlen($assembled),
            'layers_active' => $data['active'],
        ]);
    }

    // ── Update: Layer 저장
    if ($content === null) {
        onechat_json(['success' => false, 'error' => 'content 필수'], 400);
    }

    $db_column = LAYER_DB_MAP[$layer];
    $raw_content = trim($content);

    // Layer 태그가 없으면 자동 감싸기
    $saved_content = wrap_layer_tags($raw_content, $layer);

    // 기존 내용 읽기 (변경 로그용)
    $old_row = $db->query("SELECT {$db_column} FROM Gn_aievent_ms_info WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}' LIMIT 1")->fetch_assoc();
    $old_content = $old_row[$db_column] ?? null;

    $esc_content = $db->real_escape_string($saved_content);
    $db->query("
        UPDATE Gn_aievent_ms_info
        SET {$db_column}='{$esc_content}',
            prompt_version = prompt_version + 1,
            prompt_updated_at = NOW()
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
    ");

    if ($db->affected_rows >= 0) {
        // 변경 로그 기록
        log_prompt_change($db, $sms_idx, $login_id, $layer, $old_content, $saved_content);

        // 전체 조립본도 함께 반환
        $data = load_layers($db, $sms_idx, $login_esc);
        $assembled = assemble_prompt($data['layers'], $data['active'], $data['bot_name']);

        onechat_json([
            'success'   => true,
            'layer'     => $layer,
            'content'   => strip_layer_tags($saved_content, $layer),
            'version'   => $data['version'],
            'assembled' => $assembled,
            'char_count'=> [
                'layer' => mb_strlen($raw_content),
                'total' => mb_strlen($assembled),
            ],
        ]);
    }

    onechat_json(['success' => false, 'error' => $db->error], 500);
}

// ── PUT: 4-Layer 일괄 저장 ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $updated = [];
    foreach (['L1', 'L2', 'L3', 'L4'] as $layer) {
        if (isset($input[$layer])) {
            $db_column = LAYER_DB_MAP[$layer];
            $content   = trim($input[$layer]);
            $saved     = wrap_layer_tags($content, $layer);
            $esc       = $db->real_escape_string($saved);

            $db->query("UPDATE Gn_aievent_ms_info SET {$db_column}='{$esc}' WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'");
            $updated[$layer] = true;
        }
    }

    // 활성화 상태도 함께
    if (isset($input['layer_active'])) {
        $act = $input['layer_active'];
        if (is_array($act)) {
            $act_str = implode(',', [
                $act['L1'] ? 1 : 0, $act['L2'] ? 1 : 0,
                $act['L3'] ? 1 : 0, $act['L4'] ? 1 : 0,
            ]);
        } else {
            $act_str = $db->real_escape_string($act);
        }
        $db->query("UPDATE Gn_aievent_ms_info SET prompt_layer_active='{$act_str}' WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'");
        $updated['active'] = true;
    }

    // 버전 증가
    $db->query("UPDATE Gn_aievent_ms_info SET prompt_version = prompt_version + 1, prompt_updated_at = NOW() WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'");

    $data = load_layers($db, $sms_idx, $login_esc);
    $assembled = assemble_prompt($data['layers'], $data['active'], $data['bot_name']);

    onechat_json([
        'success'   => true,
        'updated'   => $updated,
        'version'   => $data['version'],
        'assembled' => $assembled,
        'char_count'=> mb_strlen($assembled),
    ]);
}

onechat_json(['error' => 'Method not allowed'], 405);