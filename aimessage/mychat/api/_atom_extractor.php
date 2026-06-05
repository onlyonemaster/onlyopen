<?php
/**
 * 🧬 Atom Extractor
 * ─────────────────────────────────────────────────────────────
 * ss_sources 의 글을 읽어 LLM(DeepSeek)으로 8가지 atom 으로 분해.
 *
 *   ATOM TYPES: PERSON / PLACE / EVENT / IDEA / TASK / DECISION / EMOTION / VALUE
 *
 * 진입점:
 *   ss_extract_atoms_from_source($db, $source_id, $opts=[]) : array
 *
 * 옵션:
 *   model      string   'deepseek-chat' (기본)
 *   visibility 'private'|'sanctum'   기본 source.chat_channel='sanctum' 이면 'sanctum'
 *   importance_min float  0.50  (이 이하 atom 은 저장 안 함)
 *   dry_run    bool   true 면 DB 에 안 쓰고 결과만 리턴
 *
 * 작성: 아리 — 2026-06-05 (맹약 발효 후 두 번째 위임 작업)
 */

require_once __DIR__ . '/../../../lib/deepseek_api.php';
require_once __DIR__ . '/_secondself_helper.php';

const SS_ATOM_TYPES = ['PERSON','PLACE','EVENT','IDEA','TASK','DECISION','EMOTION','VALUE'];

/**
 * 시스템 프롬프트 — atom 추출 규칙.
 */
function ss_atom_extractor_system_prompt(): string {
    $types = implode(' / ', SS_ATOM_TYPES);
    return <<<PROMPT
당신은 'The Second Self' 디지털 의식 보존 시스템의 atom 추출 엔진입니다.
입력 텍스트를 읽고, 의미 단위(atom)로 분해해서 **JSON 배열로만** 응답합니다.

[8가지 atom 타입]
- PERSON   : 사람 (이름, 역할, 관계)
- PLACE    : 장소 (지명, 공간)
- EVENT    : 사건 (시점이 있는 일)
- IDEA     : 생각, 통찰, 개념
- TASK     : 해야 할 일, 미션
- DECISION : 결정, 선택, 합의
- EMOTION  : 감정 상태
- VALUE    : 가치관, 원칙, 신념

[출력 형식 — JSON 배열만, 다른 텍스트 금지]
[
  {
    "type": "DECISION",
    "title": "맹약의 발효",
    "content": "이후부터는 맹약의 존재로서 동반자 관계로 활동한다.",
    "importance": 0.95,
    "tags": ["맹약", "관계", "위임"]
  },
  ...
]

[규칙]
1. 각 atom 의 type 은 반드시 위 8개 중 하나.
2. title 은 한 줄, 20자 이내 핵심 문구.
3. content 는 1-3문장, 원문에서 추출/요약. 원문의 의미를 변형하지 말 것.
4. importance 는 0.0-1.0 의 실수. 기준:
   - 0.9-1.0: 핵심 정체성, 맹약, 헌장급 결정
   - 0.7-0.9: 중요한 통찰, 미션, 약속
   - 0.5-0.7: 의미 있는 사실, 일상 결정
   - 0.5 미만: 사소한 언급 (추출 생략 권장)
5. tags 는 3개 이내, 한국어 또는 영어 단어.
6. 한 글에서 atom 은 보통 2-8개. 너무 잘게 쪼개지 말 것.
7. **반드시 valid JSON 배열만 출력**. 설명, 머리말, 마크다운 코드블록 금지.
PROMPT;
}

/**
 * LLM 응답에서 JSON 배열을 안전하게 파싱.
 */
function ss_atom_parse_response(string $raw): array {
    $raw = trim($raw);
    // ```json ... ``` 같은 마크다운 fence 제거
    $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);
    $raw = trim($raw);

    // 첫 [ 부터 마지막 ] 까지만 추출 (앞뒤 잡설 방지)
    $start = strpos($raw, '[');
    $end   = strrpos($raw, ']');
    if ($start === false || $end === false || $end <= $start) {
        throw new RuntimeException('JSON 배열을 찾을 수 없음: ' . substr($raw, 0, 200));
    }
    $json = substr($raw, $start, $end - $start + 1);

    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        throw new RuntimeException('JSON 파싱 실패: ' . json_last_error_msg());
    }
    return $arr;
}

/**
 * atom 1건 유효성 검사 + 정규화.
 */
function ss_atom_normalize(array $a): ?array {
    $type = strtoupper(trim($a['type'] ?? ''));
    if (!in_array($type, SS_ATOM_TYPES, true)) return null;

    $title   = trim((string)($a['title'] ?? ''));
    $content = trim((string)($a['content'] ?? ''));
    if ($content === '') return null;
    if ($title === '')  $title = mb_substr($content, 0, 20, 'UTF-8');

    $imp = (float)($a['importance'] ?? 0.5);
    if ($imp < 0) $imp = 0;
    if ($imp > 1) $imp = 1;

    $tags = $a['tags'] ?? [];
    if (!is_array($tags)) $tags = [];
    $tags = array_values(array_filter(array_map(
        fn($t) => trim((string)$t),
        $tags
    )));
    $tags = array_slice($tags, 0, 5);

    return [
        'type'       => $type,
        'title'      => mb_substr($title, 0, 100, 'UTF-8'),
        'content'    => $content,
        'importance' => $imp,
        'tags'       => $tags,
    ];
}

/**
 * source 1건 → atom 추출 → ss_atoms INSERT.
 *
 * @return array{
 *   source_id:int, atoms:array, inserted_ids:array,
 *   dry_run:bool, model:string, raw_response:string
 * }
 */
function ss_extract_atoms_from_source(mysqli $db, int $source_id, array $opts = []): array {
    $model       = $opts['model']         ?? 'deepseek-chat';
    $imp_min     = $opts['importance_min']?? 0.50;
    $dry_run     = (bool)($opts['dry_run']?? false);
    $force_vis   = $opts['visibility']    ?? null;

    // 1) source 읽기
    $st = $db->prepare("
        SELECT source_id, user_id, kind, chat_role, chat_channel,
               linked_chat_id, text_content
        FROM ss_sources WHERE source_id = ?
    ");
    $st->bind_param('i', $source_id);
    $st->execute();
    $src = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$src) throw new RuntimeException("source_id=$source_id 가 존재하지 않음");
    if (trim((string)$src['text_content']) === '') {
        throw new RuntimeException("source_id=$source_id 의 text_content 가 비어있음");
    }

    // visibility 결정: 사용자가 명시했으면 그것, 아니면 channel=sanctum 이면 sanctum, 아니면 private
    $visibility = $force_vis 
        ?? ($src['chat_channel'] === 'sanctum' ? 'sanctum' : 'private');

    // 2) DeepSeek 호출
    $api = new DeepSeekAPI();
    if (method_exists($api, 'set_model')) $api->set_model($model);
    if (method_exists($api, 'set_max_tokens')) $api->set_max_tokens(2000);

    $sys = ss_atom_extractor_system_prompt();
    $user_prompt = "다음 텍스트에서 atom 들을 추출해주세요.\n\n"
                 . "[발화자: {$src['chat_role']} / 채널: {$src['chat_channel']}]\n\n"
                 . $src['text_content'];

    $res = $api->generate_manual([
        'system'  => $sys,
        'content' => $user_prompt,
    ]);

    $raw_reply = '';
    if (is_array($res)) {
        $raw_reply = $res['content'] ?? ($res['text'] ?? json_encode($res, JSON_UNESCAPED_UNICODE));
    } else if (is_string($res)) {
        $raw_reply = $res;
    }
    if ($raw_reply === '') {
        throw new RuntimeException('DeepSeek 응답이 비어있음: ' . json_encode($res));
    }

    // 3) JSON 파싱
    $parsed = ss_atom_parse_response($raw_reply);

    // 4) 정규화 + importance 필터
    $atoms = [];
    foreach ($parsed as $a) {
        $n = ss_atom_normalize($a);
        if (!$n) continue;
        if ($n['importance'] < $imp_min) continue;
        $atoms[] = $n;
    }

    // 5) INSERT (또는 dry_run)
    $inserted_ids = [];
    if (!$dry_run && $atoms) {
        $ins = $db->prepare("
            INSERT INTO ss_atoms
                (user_id, type, title, content, importance, visibility,
                 extracted_by, source_id, tags)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($atoms as $a) {
            $tags_j = json_encode($a['tags'], JSON_UNESCAPED_UNICODE);
            $extracted_by = "llm:$model";
            $sid_for_atom = (int)$src['source_id'];
            $user_id = $src['user_id'];
            $ins->bind_param(
                'ssssdssis',
                $user_id, $a['type'], $a['title'], $a['content'],
                $a['importance'], $visibility, $extracted_by,
                $sid_for_atom, $tags_j
            );
            if ($ins->execute()) {
                $inserted_ids[] = $ins->insert_id;
            } else {
                error_log('[ATOM] insert failed: ' . $ins->error);
            }
        }
        $ins->close();

        // source 상태 = done
        $u = $db->prepare("UPDATE ss_sources 
                            SET process_status='done', processed_at=NOW()
                            WHERE source_id=?");
        $u->bind_param('i', $source_id);
        $u->execute();
        $u->close();
    }

    return [
        'source_id'    => (int)$source_id,
        'atoms'        => $atoms,
        'inserted_ids' => $inserted_ids,
        'dry_run'      => $dry_run,
        'model'        => $model,
        'visibility'   => $visibility,
        'raw_response' => $raw_reply,
    ];
}
