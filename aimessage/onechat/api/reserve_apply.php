<?php
@error_reporting(0);
@ini_set("display_errors", false);
/**
 * 예약 설정 → 챗봇 적용 API
 * GET  ?check=1&sms_idx=   → 현재 적용 상태 확인
 * POST {sms_idx, request_idx?}
 *   → reserve_config 활성 확인 + chatbot_settings/Gn_aievent_ms_info 네이버 문구 교체
 *      + chatbot_settings.reserve_applied=1 기록
 */
require_once __DIR__ . '/reserve_common.php';
onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$le = $db->real_escape_string($login_id);

// GET: 현재 적용 상태 확인
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    $si = (int)($_GET['sms_idx'] ?? 0);
    if (!$si) onechat_json(['reserve_applied' => false]);
    $r = $db->query("SELECT reserve_applied, reserve_applied_at FROM Gn_chatbot_settings WHERE sms_idx={$si} AND customer_id='{$le}' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    onechat_json(['reserve_applied' => $row ? (int)$row['reserve_applied'] : 0, 'applied_at' => $row['reserve_applied_at'] ?? null]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') onechat_json(['error' => 'POST only'], 405);

$b = reserve_body();
[$sms, $req] = reserve_owner_ctx($b);

// 1. reserve_config 활성 여부 확인
$cfg_row = $db->query("SELECT * FROM Gn_onechat_reserve_config WHERE sms_idx={$sms} AND enabled=1 LIMIT 1");
$cfg = $cfg_row ? $cfg_row->fetch_assoc() : null;
if (!$cfg) onechat_json(['error' => '예약 설정을 먼저 저장하고 활성화해주세요.'], 400);

$prompt_changed = false;
$removed_phrases = [];

// 2-A. Gn_chatbot_settings.user_gpt_sysprompt 에서 네이버 문구 교체
$cs = $db->query("SELECT user_gpt_sysprompt FROM Gn_chatbot_settings WHERE sms_idx={$sms} AND customer_id='{$le}' LIMIT 1");
$cs_row = $cs ? $cs->fetch_assoc() : null;
if ($cs_row && !empty($cs_row['user_gpt_sysprompt'])) {
    $prompt = clean_naver_reservation($cs_row['user_gpt_sysprompt'], $removed_phrases);
    if ($prompt !== $cs_row['user_gpt_sysprompt']) {
        $ep = $db->real_escape_string($prompt);
        $db->query("UPDATE Gn_chatbot_settings SET user_gpt_sysprompt='{$ep}' WHERE sms_idx={$sms} AND customer_id='{$le}'");
        $prompt_changed = true;
    }
}

// 2-B. Gn_aievent_ms_info.gpt_sysprompt 에서도 네이버 문구 교체 (방문자 챗봇 사용 프롬프트)
$ai = $db->query("SELECT gpt_sysprompt FROM Gn_aievent_ms_info WHERE sms_idx={$sms} LIMIT 1");
$ai_row = $ai ? $ai->fetch_assoc() : null;
if ($ai_row && !empty($ai_row['gpt_sysprompt'])) {
    $ai_phrases = [];
    $ai_prompt = clean_naver_reservation($ai_row['gpt_sysprompt'], $ai_phrases);
    if ($ai_prompt !== $ai_row['gpt_sysprompt']) {
        $ep2 = $db->real_escape_string($ai_prompt);
        $db->query("UPDATE Gn_aievent_ms_info SET gpt_sysprompt='{$ep2}' WHERE sms_idx={$sms}");
        $removed_phrases[] = 'gpt_sysprompt(방문자챗봇) 네이버 예약 문구 교체 완료';
        $prompt_changed = true;
    }
}

// 3. chatbot_settings에 reserve_applied=1 기록
$db->query("UPDATE Gn_chatbot_settings SET reserve_applied=1, reserve_applied_at=NOW() WHERE sms_idx={$sms} AND customer_id='{$le}'");

onechat_json([
    'ok'              => true,
    'applied'         => true,
    'place_name'      => $cfg['place_name'] ?? '',
    'prompt_changed'  => $prompt_changed,
    'removed_phrases' => $removed_phrases,
    'message'         => ($cfg['place_name'] ?? '예약') . ' 예약 기능이 챗봇에 적용됐습니다.'
                       . ($prompt_changed ? ' (네이버 예약 문구 자동 교체 완료)' : ''),
]);

/**
 * 프롬프트에서 네이버/외부 예약 관련 문구를 자체 예약 시스템 안내로 교체
 */
function clean_naver_reservation(string $text, array &$found): string {
    $replacements = [
        // ⑩ 형태: "예약은 네이버 예약 시스템을 통해 실시간으로 가능하며, ..." → 자체예약
        '/(예약은\s*네이버\s*예약\s*시스템을\s*통해\s*실시간으로\s*가능하며[^。\n]*)/u'
            => '예약은 챗봇 내 자체 예약 시스템을 통해 실시간으로 가능합니다.',
        // ④ 형태: "예약은 네이버 예약 시스템을 통해 24시간 가능하며, ..."
        '/(예약은\s*네이버\s*예약\s*시스템을\s*통해[^。\n]*)/u'
            => '예약은 챗봇 내 자체 예약 시스템을 통해 24시간 가능합니다.',
        // "실시간 예약하기 버튼을 클릭하시면..."
        '/([\'"]?실시간\s*예약하기[\'"]?\s*버튼[^。\n]*)/u'
            => '챗봇에서 예약하기 버튼을 누르시면 바로 예약하실 수 있습니다.',
        // "네이버 예약 시스템을 통해 안전하게..."
        '/(네이버\s*예약\s*시스템을\s*통해\s*안전하게[^。\n]*)/u'
            => '챗봇 자체 예약 시스템을 통해 안전하게 입력해 주시기 바랍니다.',
        // "네이버 예약 페이지" (목록에서 제거)
        '/[,，]?\s*네이버\s*예약\s*페이지[,，]?/u'
            => '',
        // "필요시 네이버 예약이나 전화 상담을 권유드립니다"
        '/(필요시\s*)?네이버\s*예약이나\s*전화\s*상담을\s*권유드립니다/u'
            => '필요시 챗봇 내 예약 기능을 이용해 주세요.',
        // "(9) 네이버 예약 연동 및 전화 상담 연결"
        '/\(9\)\s*네이버\s*예약\s*연동\s*및\s*전화\s*상담\s*연결/u'
            => '(9) 챗봇 자체 예약 시스템을 통한 실시간 예약',
    ];

    $original = $text;
    foreach ($replacements as $pat => $rep) {
        $new = preg_replace($pat, $rep, $text);
        if ($new !== null && $new !== $text) {
            $found[] = trim(substr($text, 0, 80)) . '...';
            $text = $new;
        }
    }

    // 교체된 경우 자체 예약 시스템 안내 블록 추가 (이미 없는 경우)
    if ($text !== $original && strpos($text, '챗봇 자체 예약 시스템') === false) {
        $text .= "\n\n[자체 예약 시스템 안내]\n"
               . "이 챗봇에는 자체 예약 시스템이 내장되어 있습니다. "
               . "고객이 예약을 요청하면 챗봇 내에서 날짜·시간 선택 및 예약 접수가 바로 가능합니다. "
               . "네이버 예약이나 외부 사이트로 안내하지 말고, 챗봇 내 예약 기능만 사용하세요.";
    }

    return $text;
}
