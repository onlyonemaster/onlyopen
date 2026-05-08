<?php
/**
 * 원챗(OneChat) 챗봇 설정 API v2.0 — 4-Layer Prompt 지원
 * ─────────────────────────────────────────────────────────────────
 * 기존 chatbot_setting.php의 확장 버전입니다.
 * v2 추가 기능:
 *   - 4-Layer 필드 GET/POST (prompt_l1_persona ~ prompt_l4_safety)
 *   - Layer 활성화 토글 (prompt_layer_active)
 *   - Context Bridge 설정
 *   - 프롬프트 버전 관리
 *
 * GET  ?sms_idx=X           → 챗봇 설정 조회 (4-Layer 포함)
 * GET  (no param)           → 내 챗봇 목록 조회
 * POST {sms_idx, ...}       → 설정 저장
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * 텍스트 내의 구 챗봇 이름을 신 이름으로 치환하는 헬퍼
 */
function replace_chatbot_name_in_text_v2(string $text, string $old_name, string $new_name): string {
    if ($old_name === '' || $old_name === $new_name || $text === '') return $text;
    return str_replace($old_name, $new_name, $text);
}

onechat_cors();

$login_id = onechat_auth();
$db = getDatabaseConnection();
$login_esc = $db->real_escape_string($login_id);

// ── GET ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sms_idx = (int)($_GET['sms_idx'] ?? 0);

    if ($sms_idx > 0) {
        // 특정 챗봇 설정 조회 (소유 확인)
        $r = $db->query("
            SELECT sms_idx, chatbot_name, gptmodel,
                   gpt_sysprompt, message_style, user_gpt_sysprompt,
                   ai_prompt,
                   prompt_l1_persona, prompt_l2_policy,
                   prompt_l3_interaction, prompt_l4_safety,
                   prompt_layer_active, prompt_version,
                   prompt_updated_at,
                   context_bridge_enabled, context_bridge_config,
                   rag_provider, rag_collection_name
            FROM Gn_aievent_ms_info
            WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
            LIMIT 1
        ");
        if (!$r || !($row = $r->fetch_assoc())) {
            onechat_json(['success' => false, 'error' => '챗봇을 찾을 수 없습니다.'], 404);
        }

        // Layer 활성화 상태 파싱
        $active_raw = array_map('intval', explode(',', $row['prompt_layer_active'] ?? '1,1,1,1'));
        while (count($active_raw) < 4) $active_raw[] = 1;

        onechat_json([
            'success' => true,
            'setting' => [
                // 기존 필드
                'sms_idx'            => (int)$row['sms_idx'],
                'chatbot_name'       => $row['chatbot_name'] ?? '',
                'gptmodel'           => 'deepseek-chat',
                'gpt_sysprompt'      => $row['gpt_sysprompt'] ?? '',
                'message_style'      => $row['message_style'] ?? '',
                'user_gpt_sysprompt' => $row['user_gpt_sysprompt'] ?? '',
                'ai_prompt'          => $row['ai_prompt'] ?? '',

                // v2: 4-Layer 프롬프트
                'prompt_l1_persona'       => $row['prompt_l1_persona'] ?? '',
                'prompt_l2_policy'        => $row['prompt_l2_policy'] ?? '',
                'prompt_l3_interaction'   => $row['prompt_l3_interaction'] ?? '',
                'prompt_l4_safety'        => $row['prompt_l4_safety'] ?? '',

                // v2: Layer 활성화
                'prompt_layer_active'     => [
                    'L1' => (bool)$active_raw[0],
                    'L2' => (bool)$active_raw[1],
                    'L3' => (bool)$active_raw[2],
                    'L4' => (bool)$active_raw[3],
                ],
                'prompt_layer_active_raw' => $row['prompt_layer_active'] ?? '1,1,1,1',
                'prompt_version'          => (int)($row['prompt_version'] ?? 1),
                'prompt_updated_at'       => $row['prompt_updated_at'] ?? null,

                // v2: Context Bridge & RAG
                'context_bridge_enabled'  => (bool)($row['context_bridge_enabled'] ?? true),
                'context_bridge_config'   => $row['context_bridge_config'] ?? null,
                'rag_provider'            => $row['rag_provider'] ?? 'milvus',
                'rag_collection_name'     => $row['rag_collection_name'] ?? null,
            ],
        ]);
    }

    // 내 챗봇 목록 반환
    $r = $db->query("
        SELECT sms_idx, chatbot_name, gptmodel, prompt_version, prompt_updated_at
        FROM Gn_aievent_ms_info
        WHERE customer_id='{$login_esc}'
        ORDER BY sms_idx ASC
    ");
    $bots = [];
    while ($row = $r->fetch_assoc()) {
        $bots[] = [
            'sms_idx'          => (int)$row['sms_idx'],
            'chatbot_name'     => $row['chatbot_name'] ?? '',
            'gptmodel'         => 'deepseek-chat',
            'prompt_version'   => (int)($row['prompt_version'] ?? 1),
            'prompt_updated_at'=> $row['prompt_updated_at'] ?? null,
        ];
    }
    onechat_json(['success' => true, 'bots' => $bots]);
}

// ── POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $sms_idx            = (int)($input['sms_idx'] ?? 0);
    $chatbot_name       = trim($input['chatbot_name'] ?? '');

    // 기존 필드
    $gpt_sysprompt      = $input['gpt_sysprompt'] ?? null;
    $message_style      = $input['message_style'] ?? null;
    $user_gpt_sysprompt = $input['user_gpt_sysprompt'] ?? null;

    // v2: 4-Layer 필드
    $l1_persona         = $input['prompt_l1_persona'] ?? null;
    $l2_policy          = $input['prompt_l2_policy'] ?? null;
    $l3_interaction     = $input['prompt_l3_interaction'] ?? null;
    $l4_safety          = $input['prompt_l4_safety'] ?? null;
    $layer_active       = $input['prompt_layer_active'] ?? null;

    // v2: Context Bridge
    $cb_enabled         = $input['context_bridge_enabled'] ?? null;
    $cb_config          = $input['context_bridge_config'] ?? null;

    if (!$sms_idx) {
        onechat_json(['success' => false, 'error' => 'sms_idx 필요'], 400);
    }

    // 소유 확인
    $chk = $db->query("
        SELECT sms_idx, chatbot_name, chatbot_prompt, user_gpt_sysprompt
        FROM Gn_aievent_ms_info
        WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'
        LIMIT 1
    ");
    if (!$chk || !($old_row = $chk->fetch_assoc())) {
        onechat_json(['success' => false, 'error' => '권한 없음'], 403);
    }
    $old_chatbot_name = trim($old_row['chatbot_name'] ?? '');

    // 업데이트할 필드 동적 구성
    $fields = [];
    $name_changed = ($chatbot_name !== '' && $chatbot_name !== $old_chatbot_name);

    if ($chatbot_name !== '') {
        $fields[] = "chatbot_name='" . $db->real_escape_string($chatbot_name) . "'";
    }
    // AI 모델 고정: DeepSeek
    $fields[] = "gptmodel='deepseek-chat'";

    // ── 이름 변경 시: 기존 gpt_sysprompt 치환 ──
    if ($name_changed) {
        $prompt_to_update = $gpt_sysprompt !== null
            ? $gpt_sysprompt
            : trim($old_row['chatbot_prompt'] ?? '');
        $gpt_sysprompt = replace_chatbot_name_in_text_v2($prompt_to_update, $old_chatbot_name, $chatbot_name);

        $user_sys_to_update = $user_gpt_sysprompt !== null
            ? $user_gpt_sysprompt
            : trim($old_row['user_gpt_sysprompt'] ?? '');
        $user_gpt_sysprompt = replace_chatbot_name_in_text_v2($user_sys_to_update, $old_chatbot_name, $chatbot_name);
    }

    if ($gpt_sysprompt !== null && $gpt_sysprompt !== '') {
        $fields[] = "gpt_sysprompt='" . $db->real_escape_string($gpt_sysprompt) . "'";
        if ($name_changed) {
            $fields[] = "chatbot_prompt='" . $db->real_escape_string($gpt_sysprompt) . "'";
        }
    }
    if ($message_style !== null && $message_style !== '') {
        $fields[] = "message_style='" . $db->real_escape_string($message_style) . "'";
    }
    if ($user_gpt_sysprompt !== null) {
        $fields[] = "user_gpt_sysprompt='" . $db->real_escape_string($user_gpt_sysprompt) . "'";
    }

    // ── v2: 4-Layer 프롬프트 저장 ──
    if ($l1_persona !== null) {
        $fields[] = "prompt_l1_persona='" . $db->real_escape_string($l1_persona) . "'";
    }
    if ($l2_policy !== null) {
        $fields[] = "prompt_l2_policy='" . $db->real_escape_string($l2_policy) . "'";
    }
    if ($l3_interaction !== null) {
        $fields[] = "prompt_l3_interaction='" . $db->real_escape_string($l3_interaction) . "'";
    }
    if ($l4_safety !== null) {
        $fields[] = "prompt_l4_safety='" . $db->real_escape_string($l4_safety) . "'";
    }

    // ── v2: Layer 활성화 상태 ──
    if ($layer_active !== null) {
        if (is_array($layer_active)) {
            $active_str = implode(',', [
                ($layer_active['L1'] ?? true) ? 1 : 0,
                ($layer_active['L2'] ?? true) ? 1 : 0,
                ($layer_active['L3'] ?? true) ? 1 : 0,
                ($layer_active['L4'] ?? true) ? 1 : 0,
            ]);
        } else {
            $active_str = $db->real_escape_string($layer_active);
        }
        $fields[] = "prompt_layer_active='" . $active_str . "'";
    }

    // ── v2: Context Bridge ──
    if ($cb_enabled !== null) {
        $fields[] = "context_bridge_enabled=" . ($cb_enabled ? 1 : 0);
    }
    if ($cb_config !== null) {
        $cfg_json = is_string($cb_config) ? $cb_config : json_encode($cb_config, JSON_UNESCAPED_UNICODE);
        $fields[] = "context_bridge_config='" . $db->real_escape_string($cfg_json) . "'";
    }

    // 프롬프트 버전 증가 + 수정 시각 갱신
    $fields[] = "prompt_version = prompt_version + 1";
    $fields[] = "prompt_updated_at = NOW()";

    if (empty($fields)) {
        onechat_json(['success' => false, 'error' => '변경 사항 없음'], 400);
    }

    $sql = "UPDATE Gn_aievent_ms_info SET " . implode(', ', $fields) . " WHERE sms_idx={$sms_idx} AND customer_id='{$login_esc}'";
    $result = $db->query($sql);

    if ($result) {
        // ── 챗봇 이름 변경 시: 공유 링크, 구독 테이블, 학습 데이터 동기화 ──
        if ($chatbot_name !== '') {
            $db->query("
                UPDATE Gn_onechat_shared_link
                SET chatbot_name='" . $db->real_escape_string($chatbot_name) . "'
                WHERE sms_idx={$sms_idx} AND mem_code='{$login_esc}'
            ");
        }

        if ($name_changed && $chatbot_name !== '') {
            $sl_row = $db->query("
                SELECT short_code FROM Gn_onechat_shared_link
                WHERE sms_idx={$sms_idx} AND mem_code='{$login_esc}' LIMIT 1
            ")->fetch_assoc();
            if ($sl_row && !empty($sl_row['short_code'])) {
                $sc_esc = $db->real_escape_string($sl_row['short_code']);
                $cn_esc = $db->real_escape_string($chatbot_name);
                $db->query("
                    UPDATE Gn_visitor_subscriptions
                    SET chatbot_name='{$cn_esc}'
                    WHERE short_code='{$sc_esc}'
                ");
            }
        }

        if ($name_changed && $old_chatbot_name !== '') {
            $learn_file = __DIR__ . '/../uploads/learn/' . $sms_idx . '/learn_data.json';
            if (file_exists($learn_file)) {
                $raw_learn = file_get_contents($learn_file);
                $new_raw   = str_replace($old_chatbot_name, $chatbot_name, $raw_learn);
                if ($new_raw !== $raw_learn) {
                    file_put_contents($learn_file, $new_raw);
                }
            }
        }

        onechat_json([
            'success'      => true,
            'affected'     => $db->affected_rows,
            'name_changed' => $name_changed,
            'old_name'     => $old_chatbot_name,
            'new_name'     => $chatbot_name,
        ]);
    } else {
        onechat_json(['success' => false, 'error' => $db->error], 500);
    }
}