<?php
/**
 * 예약 트리거 AI 자동 생성 API
 * GET ?sms_idx=X&request_idx=Y → 학습데이터+챗봇설정 기반 트리거 키워드 추천
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../lib/deepseek_api.php';
require_once __DIR__ . '/reserve_common.php';

onechat_cors();
$login_id = onechat_auth();
$db = getDatabaseConnection();
$le = $db->real_escape_string($login_id);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') onechat_json(['error' => 'GET only'], 405);

[$sms, $req] = reserve_owner_ctx($_GET);

// ── 챗봇 설정 읽기 ──────────────────────────────────────────────
$cfg_row = null;
$r = $db->query("SELECT chatbot_name, chatbot_prompt, user_gpt_sysprompt FROM Gn_aievent_ms_info WHERE sms_idx={$sms} AND customer_id='{$le}' LIMIT 1");
if ($r) $cfg_row = $r->fetch_assoc();

$chatbot_name   = trim($cfg_row['chatbot_name']    ?? '');
$chatbot_prompt = trim($cfg_row['chatbot_prompt']   ?? '');
$sys_prompt     = trim($cfg_row['user_gpt_sysprompt'] ?? '');

// ── 예약 설정 읽기 (업종·장소명) ────────────────────────────────
$rsvCfg = reserve_get_or_create_config($db, $sms, $req);
$place  = $rsvCfg['place_name'] ?? '';

// ── 학습 데이터 읽기 (최대 60항목) ─────────────────────────────
$LEARN_FILE = __DIR__ . '/../uploads/learn/' . $sms . '/learn_data.json';
$learn_items = [];
if (file_exists($LEARN_FILE)) {
    $raw = json_decode(file_get_contents($LEARN_FILE), true) ?: [];
    $learn_items = array_slice($raw, 0, 60);
}

// 학습 데이터 텍스트 요약
$learn_text = '';
foreach ($learn_items as $item) {
    $q = trim($item['question'] ?? $item['title'] ?? '');
    $a = trim($item['answer']   ?? $item['content'] ?? '');
    if ($q || $a) $learn_text .= "Q: {$q}\nA: {$a}\n\n";
}

// ── 컨텍스트 조합 ────────────────────────────────────────────────
$context_parts = [];
if ($chatbot_name) $context_parts[] = "챗봇 이름: {$chatbot_name}";
if ($place)        $context_parts[] = "가게/장소: {$place}";
if ($chatbot_prompt) $context_parts[] = "챗봇 소개:\n{$chatbot_prompt}";
if ($sys_prompt)   $context_parts[] = "시스템 지시:\n{$sys_prompt}";
if ($learn_text)   $context_parts[] = "학습 데이터 (Q&A):\n{$learn_text}";

$context = implode("\n\n", $context_parts);
if (!$context) {
    onechat_json(['ok' => false, 'error' => '챗봇 설정이나 학습 데이터가 없어 추천이 어렵습니다. 먼저 챗봇 설정과 학습 데이터를 등록해주세요.']);
}

// ── DeepSeek API 호출 ────────────────────────────────────────────
$system_prompt = <<<SYS
당신은 챗봇 예약 시스템의 트리거 키워드 전문가입니다.
아래 챗봇 정보를 분석해서 4가지 트리거에 맞는 최적의 키워드/태그를 추천하세요.

결과는 반드시 다음 JSON 형식만 출력하세요 (다른 텍스트 없이):
{
  "manual_keywords": ["키워드1", "키워드2", "키워드3", "키워드4", "키워드5"],
  "mood_signals": ["분위기표현1", "분위기표현2", "분위기표현3", "분위기표현4"],
  "interest_tags": ["관심태그1", "관심태그2", "관심태그3", "관심태그4"],
  "reason_template": {
    "manual": "예약 키워드를 말씀하셔서",
    "mood": "여유롭게 쉬고 싶으신 것 같아서",
    "companion": "정기적으로 방문해주셔서",
    "interest": "관심사와 잘 맞는 것 같아서"
  },
  "message_template": {
    "manual": "예약을 도와드릴게요! 원하시는 날짜를 알려주세요.",
    "mood": "딱 맞는 곳을 알고 있어요. 예약해드릴까요?",
    "companion": "또 뵙게 되어 반가워요! 이번엔 언제 오실 건가요?",
    "interest": "관심 있으실 것 같아서 안내드려요. 예약해드릴까요?"
  }
}

각 필드 설명:
- manual_keywords: 고객이 "예약"을 직접 요청할 때 쓰는 자연스러운 한국어 표현 5개 (쉼표 없이 배열로)
- mood_signals: 방문·외출·휴식 등 긍정적 분위기를 나타내는 표현 4개
- interest_tags: 이 가게/서비스의 특성과 관련된 관심 태그 4개
- reason_template: 각 트리거가 발동한 이유를 고객에게 설명하는 짧은 문장
- message_template: 트리거 발동 시 고객에게 보낼 자연스러운 예약 안내 메시지
SYS;

$user_prompt = "다음 챗봇 정보를 분석해서 트리거 키워드를 추천해주세요:\n\n{$context}";

try {
    $ds = new DeepSeekAPI();
    $ds->set_max_tokens(800);
    $result = $ds->generate_manual(['system' => $system_prompt, 'content' => $user_prompt]);
    $raw_text = trim($result['content'] ?? '');

    // JSON 파싱 (마크다운 코드블록 제거)
    $json_str = preg_replace('/^```(?:json)?\s*/i', '', $raw_text);
    $json_str = preg_replace('/\s*```$/i', '', $json_str);
    $parsed = json_decode(trim($json_str), true);

    if (!$parsed) {
        onechat_json(['ok' => false, 'error' => 'AI 응답 파싱 실패: ' . substr($raw_text, 0, 200)]);
    }

    onechat_json([
        'ok'      => true,
        'result'  => $parsed,
        'context' => ['chatbot_name' => $chatbot_name, 'place' => $place, 'learn_count' => count($learn_items)],
    ]);
} catch (Exception $e) {
    onechat_json(['ok' => false, 'error' => $e->getMessage()]);
}
