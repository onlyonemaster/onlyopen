<?php
/**
 * 원챗(OneChat) Admin Auto-Orchestrator API v2.0
 * ─────────────────────────────────────────────────────────────────
 * 후보자 기본 정보 입력 → 3단계 AI 에이전트 자동 실행.
 *
 *  Agent 1: Web Research Agent — 웹 크롤링 + SNS 수집 + Knowledge Graph
 *  Agent 2: Prompt Generator  — 4-Layer (L1~L4) 자동 생성
 *  Agent 3: Data Injector      — RAG 파이프라인 구동 + 데이터 주입
 *
 * Endpoints:
 *   POST launch   → 오케스트레이터 전체 실행
 *   POST step     → 개별 단계 실행 (1=research, 2=prompt, 3=inject)
 *   GET  status   → 실행 상태 확인 (job_id 기준)
 *   GET  history  → 과거 실행 이력
 */

// 관리자 전용: admin session 체크 (필요시 주석 해제)
// if (!defined('__ADMIN__') && !isset($_SESSION['admin_id'])) {
//     http_response_code(403);
//     echo json_encode(['code'=>403,'message'=>'관리자 권한이 필요합니다.'], JSON_UNESCAPED_UNICODE);
//     exit;
// }

require_once __DIR__ . '/../../config/database.php';

define('ONECHAT_ORCHESTRATOR_VERSION', '2.0.0');
define('ORCHESTRATOR_LOG_DIR', __DIR__ . '/../logs');

// DB
$db = getDatabaseConnection();

// ── Helpers ────────────────────────────────────────────────────────

function ocw_json(int $code, string $msg, array $data = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['code' => $code, 'message' => $msg], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function ocw_log(string $jobId, string $step, string $msg, string $level = 'info'): void {
    $dir = ORCHESTRATOR_LOG_DIR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . '/orchestrator_' . date('Y-m-d') . '.log';
    $line = date('Y-m-d H:i:s') . " [{$level}] [{$jobId}] [{$step}] {$msg}\n";
    file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

function ocw_generate_job_id(): string {
    return 'ocw_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function ocw_save_job_status(string $jobId, array $status): void {
    $dir = ORCHESTRATOR_LOG_DIR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . '/jobs/' . $jobId . '.json';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    file_put_contents($path, json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function ocw_get_job_status(string $jobId): ?array {
    $path = ORCHESTRATOR_LOG_DIR . '/jobs/' . $jobId . '.json';
    if (!file_exists($path)) return null;
    return json_decode(file_get_contents($path), true);
}

function ocw_call_openai(string $system, string $user, string $model = 'gpt-4o'): string {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : getenv('OPENAI_API_KEY');
    $baseUrl = defined('OPENAI_BASE_URL') ? OPENAI_BASE_URL : (getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1');

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ],
        'temperature' => 0.7,
        'max_tokens' => 3000
    ]);

    $ch = curl_init($baseUrl . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) throw new RuntimeException('OpenAI API error: ' . $err);

    $data = json_decode($resp, true);
    return $data['choices'][0]['message']['content'] ?? '';
}

function ocw_fetch_url(string $url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'OneChat-Orchestrator/2.0'
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    // 간단 텍스트 추출
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return mb_substr($text, 0, 10000);
}


// ═══════════════════════════════════════════════════════════════════
// AGENT 1: Web Research Agent
// ═══════════════════════════════════════════════════════════════════

function agent_web_research(mysqli $db, array $data, string $jobId): array {
    $name = $data['name'] ?? '';
    $party = $data['party'] ?? '';
    $elecType = $data['election_type'] ?? '';
    $district = $data['district'] ?? '';
    $website = $data['website'] ?? '';
    $career = $data['career'] ?? '';
    $edu = $data['education'] ?? '';
    $slogan = $data['slogan'] ?? '';

    ocw_log($jobId, 'agent1', 'Web Research Agent 시작');

    $results = [
        'name' => $name,
        'searched_urls' => [],
        'extracted_info' => [],
        'knowledge_graph' => [],
        'candidate_profile' => [],
    ];

    // 1. 웹사이트 크롤링
    $urlsToCrawl = [];
    if ($website && filter_var($website, FILTER_VALIDATE_URL)) {
        $urlsToCrawl[] = $website;
    }

    // SNS URL 구성
    foreach (['instagram', 'facebook', 'twitter', 'blog'] as $sns) {
        $key = 'sns_' . $sns;
        $val = $data[$key] ?? '';
        if ($val) $results['searched_urls'][] = "sns:{$sns}:{$val}";
    }

    // YouTube 채널
    if (!empty($data['youtube'])) {
        $results['searched_urls'][] = "youtube:{$data['youtube']}";
    }

    // 웹사이트 크롤링
    foreach ($urlsToCrawl as $url) {
        try {
            $html = ocw_fetch_url($url);
            $results['searched_urls'][] = $url;
            $results['extracted_info']['website_text'] = mb_substr($html, 0, 5000);
        } catch (\Exception $e) {
            ocw_log($jobId, 'agent1', "크롤링 실패: {$url} — {$e->getMessage()}", 'warn');
        }
    }

    // 2. AI 기반 프로필 분석
    $profileText = "이름: {$name}\n";
    if ($party) $profileText .= "정당: {$party}\n";
    if ($elecType) $profileText .= "선거 유형: {$elecType}\n";
    if ($district) $profileText .= "선거구: {$district}\n";
    if ($edu) $profileText .= "학력: {$edu}\n";
    if ($career) $profileText .= "경력: {$career}\n";
    if ($slogan) $profileText .= "슬로건: {$slogan}\n";

    if (!empty($results['extracted_info']['website_text'])) {
        $profileText .= "\n웹사이트 내용: " . mb_substr($results['extracted_info']['website_text'], 0, 2000);
    }

    try {
        $system = "당신은 정치 후보자 프로필 분석 전문가입니다. 주어진 정보를 바탕으로 Knowledge Graph를 JSON 형식으로 생성하세요.";
        $user = "다음 후보자 정보를 분석하여 Knowledge Graph(핵심 가치관, 주요 정책 방향, 핵심 지지층, 소통 스타일, 강점·약점, 차별화 포인트)를 JSON으로 반환해주세요:\n\n{$profileText}\n\nJSON 형식으로만 응답하세요.";

        $aiResult = ocw_call_openai($system, $user);
        $kg = json_decode($aiResult, true);
        if ($kg) {
            $results['knowledge_graph'] = $kg;
        } else {
            $results['knowledge_graph'] = ['raw_analysis' => $aiResult];
        }
    } catch (\Exception $e) {
        ocw_log($jobId, 'agent1', "AI 분석 실패: {$e->getMessage()}", 'error');
        $results['knowledge_graph'] = ['error' => $e->getMessage()];
    }

    // 3. 후보자 프로필 요약
    $results['candidate_profile'] = [
        'name' => $name,
        'party' => $party,
        'election_type' => $elecType,
        'district' => $district,
        'slogan' => $slogan,
        'education' => $edu,
        'career' => array_filter(array_map('trim', explode(',', $career))),
        'analyzed_at' => date('Y-m-d H:i:s')
    ];

    ocw_log($jobId, 'agent1', "Web Research 완료 — " . count($results['searched_urls']) . "개 소스");

    return $results;
}


// ═══════════════════════════════════════════════════════════════════
// AGENT 2: Prompt Generator
// ═══════════════════════════════════════════════════════════════════

function agent_prompt_generator(mysqli $db, array $data, array $researchResult, string $jobId): array {
    ocw_log($jobId, 'agent2', 'Prompt Generator 시작');

    $name = $data['name'] ?? '후보';
    $party = $data['party'] ?? '';
    $elecType = $data['election_type'] ?? '';
    $district = $data['district'] ?? '';
    $slogan = $data['slogan'] ?? '';
    $career = $data['career'] ?? '';
    $edu = $data['education'] ?? '';
    $kg = $researchResult['knowledge_graph'] ?? [];

    // Knowledge Graph를 텍스트로 변환
    $kgText = json_encode($kg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $profileSummary = "후보자: {$name}\n";
    if ($party) $profileSummary .= "정당: {$party}\n";
    if ($elecType) $profileSummary .= "선거 유형: {$elecType}\n";
    if ($district) $profileSummary .= "선거구: {$district}\n";
    if ($slogan) $profileSummary .= "슬로건: {$slogan}\n";
    if ($edu) $profileSummary .= "학력: {$edu}\n";
    if ($career) $profileSummary .= "경력: {$career}\n";

    $system = file_get_contents(__DIR__ . '/prompt_templates/prompt_generator_system.txt') ?: "당신은 선거 후보자 AI 챗봇 프롬프트를 설계하는 전문가입니다.
주어진 후보자 정보를 바탕으로 4-Layer 시스템 프롬프트(L1 Persona, L2 Policy, L3 Interaction, L4 Safety)를 생성하세요.
각 Layer는 XML 태그로 감싸고, 구체적이고 실행 가능한 지시어로 작성하세요.
정치적 중립성을 유지하되 후보자의 입장을 사실적으로 반영하세요.";

    $user = "다음 후보자 정보와 AI 분석 결과를 바탕으로 4-Layer 프롬프트를 생성해주세요:

## 후보자 정보
{$profileSummary}

## AI Knowledge Graph 분석 결과
{$kgText}

## 요구사항
1. L1_PERSONA: 후보자의 핵심 정체성·가치관·말투·경계 설정
2. L2_POLICY: 5대 핵심 공약 + 예상 FAQ 10개 (구체적 수치 포함)
3. L3_INTERACTION: 7가지 대화 유형별 응대 전략 + 문체 가이드
4. L4_SAFETY: 공직선거법 준수, 금지 발언, 민감 질문 처리 규칙

다음 JSON 형식으로만 응답하세요:
{
  \"L1_PERSONA\": \"...\",
  \"L2_POLICY\": \"...\",
  \"L3_INTERACTION\": \"...\",
  \"L4_SAFETY\": \"...\"
}";

    try {
        $aiResult = ocw_call_openai($system, $user, 'gpt-4o');
    } catch (\Exception $e) {
        ocw_log($jobId, 'agent2', "프롬프트 생성 실패: {$e->getMessage()}", 'error');
        return ['error' => $e->getMessage(), 'layers' => null];
    }

    // JSON 추출
    $promptData = json_decode($aiResult, true);
    if (!$promptData || !isset($promptData['L1_PERSONA'])) {
        // JSON 파싱 실패 시 정규식으로 레이어 추출
        $promptData = [];
        foreach (['L1_PERSONA', 'L2_POLICY', 'L3_INTERACTION', 'L4_SAFETY'] as $layer) {
            if (preg_match('/"' . $layer . '"\s*:\s*"(.*?)"(?=\s*[,}])/s', $aiResult, $m)) {
                $promptData[$layer] = stripslashes($m[1]);
            }
        }
        if (empty($promptData)) {
            $promptData['raw_output'] = $aiResult;
        }
    }

    ocw_log($jobId, 'agent2', 'Prompt Generator 완료');
    return ['layers' => $promptData, 'generated_at' => date('Y-m-d H:i:s')];
}


// ═══════════════════════════════════════════════════════════════════
// AGENT 3: Data Injector
// ═══════════════════════════════════════════════════════════════════

function agent_data_injector(mysqli $db, array $data, array $researchResult, array $promptResult, string $jobId): array {
    ocw_log($jobId, 'agent3', 'Data Injector 시작');

    $name = $data['name'] ?? '';
    $website = $data['website'] ?? '';
    $youtube = $data['youtube'] ?? '';
    $career = $data['career'] ?? '';
    $slogan = $data['slogan'] ?? '';

    $stats = [
        'websites_crawled' => 0,
        'youtube_processed' => 0,
        'chunks_created' => 0,
        'errors' => []
    ];

    // RAG 서버 호출
    $ragHost = '127.0.0.1';
    $ragPort = 5100;
    $ragBase = "http://{$ragHost}:{$ragPort}";

    $tempSmsIdx = 0; // 실제 DB INSERT 후 할당된 sms_idx

    // 1. 웹사이트 URL ingest
    if ($website && filter_var($website, FILTER_VALIDATE_URL)) {
        try {
            $ch = curl_init($ragBase . '/ingest/url');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'sms_idx' => $tempSmsIdx,
                    'url' => $website,
                    'category' => '정책·공약'
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($resp, true);
            $stats['websites_crawled']++;
            $stats['chunks_created'] += $result['chunks_created'] ?? 0;
        } catch (\Exception $e) {
            $stats['errors'][] = "website: {$e->getMessage()}";
        }
    }

    // 2. YouTube URL ingest
    if ($youtube) {
        try {
            $ch = curl_init($ragBase . '/ingest/youtube');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'sms_idx' => $tempSmsIdx,
                    'url' => $youtube,
                    'category' => '발언·연설'
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($resp, true);
            $stats['youtube_processed']++;
            $stats['chunks_created'] += $result['chunks_created'] ?? 0;
        } catch (\Exception $e) {
            $stats['errors'][] = "youtube: {$e->getMessage()}";
        }
    }

    // 3. 경력 기반 텍스트 ingest
    if ($career || $slogan) {
        $careerText = '';
        if ($slogan) $careerText .= "## 슬로건\n{$slogan}\n\n";
        if ($career) $careerText .= "## 주요 경력\n{$career}";
        if (!empty($researchResult['extracted_info']['website_text'])) {
            $careerText .= "\n\n## 웹사이트 정보\n" . mb_substr($researchResult['extracted_info']['website_text'], 0, 3000);
        }

        try {
            $ch = curl_init($ragBase . '/ingest/text');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'sms_idx' => $tempSmsIdx,
                    'text' => $careerText,
                    'metadata' => ['category' => '인물·이력', 'source' => 'orchestrator']
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($resp, true);
            $stats['chunks_created'] += $result['chunks_created'] ?? 0;
        } catch (\Exception $e) {
            $stats['errors'][] = "text: {$e->getMessage()}";
        }
    }

    ocw_log($jobId, 'agent3', "Data Injector 완료 — {$stats['chunks_created']} 청크");
    return $stats;
}


// ═══════════════════════════════════════════════════════════════════
// MAIN: Orchestrator 실행
// ═══════════════════════════════════════════════════════════════════

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'launch') {
    // ── 전체 오케스트레이터 실행 ──────────────────────────────────
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $jobId = ocw_generate_job_id();

    $status = [
        'job_id' => $jobId,
        'started_at' => date('Y-m-d H:i:s'),
        'input' => array_intersect_key($input, array_flip(['name','party','election_type','district','slogan','website','youtube','education','career'])),
        'steps' => [],
        'current_step' => 0,
        'status' => 'running'
    ];
    ocw_save_job_status($jobId, $status + ['status' => 'initializing']);

    // Step 1: Web Research
    $status['current_step'] = 1;
    $status['steps']['research'] = ['status' => 'running', 'started_at' => date('Y-m-d H:i:s')];
    ocw_save_job_status($jobId, $status);

    try {
        $researchResult = agent_web_research($db, $input, $jobId);
        $status['steps']['research'] = [
            'status' => 'completed',
            'started_at' => $status['steps']['research']['started_at'],
            'completed_at' => date('Y-m-d H:i:s'),
            'result_summary' => [
                'searched_urls' => count($researchResult['searched_urls']),
                'has_knowledge_graph' => !empty($researchResult['knowledge_graph'])
            ]
        ];
    } catch (\Exception $e) {
        $status['steps']['research']['status'] = 'failed';
        $status['steps']['research']['error'] = $e->getMessage();
        $status['status'] = 'failed';
        ocw_save_job_status($jobId, $status);
        ocw_json(500, 'Research Agent 실패: ' . $e->getMessage());
    }
    ocw_save_job_status($jobId, $status);

    // Step 2: Prompt Generator
    $status['current_step'] = 2;
    $status['steps']['prompt'] = ['status' => 'running', 'started_at' => date('Y-m-d H:i:s')];
    ocw_save_job_status($jobId, $status);

    try {
        $promptResult = agent_prompt_generator($db, $input, $researchResult, $jobId);
        $status['steps']['prompt'] = [
            'status' => 'completed',
            'started_at' => $status['steps']['prompt']['started_at'],
            'completed_at' => date('Y-m-d H:i:s'),
            'result_summary' => [
                'layers_generated' => !empty($promptResult['layers']) ? count($promptResult['layers']) : 0,
                'layers' => $promptResult['layers']
            ]
        ];
        $status['prompt_layers'] = $promptResult['layers'];
    } catch (\Exception $e) {
        $status['steps']['prompt']['status'] = 'failed';
        $status['steps']['prompt']['error'] = $e->getMessage();
        $status['status'] = 'partial';
        ocw_save_job_status($jobId, $status);
    }
    ocw_save_job_status($jobId, $status);

    // Step 3: Data Injector
    $status['current_step'] = 3;
    $status['steps']['inject'] = ['status' => 'running', 'started_at' => date('Y-m-d H:i:s')];
    ocw_save_job_status($jobId, $status);

    try {
        $injectResult = agent_data_injector($db, $input, $researchResult, $promptResult, $jobId);
        $status['steps']['inject'] = [
            'status' => 'completed',
            'started_at' => $status['steps']['inject']['started_at'],
            'completed_at' => date('Y-m-d H:i:s'),
            'result_summary' => $injectResult
        ];
    } catch (\Exception $e) {
        $status['steps']['inject']['status'] = 'failed';
        $status['steps']['inject']['error'] = $e->getMessage();
    }
    $status['current_step'] = 5;
    $status['status'] = 'completed';
    $status['completed_at'] = date('Y-m-d H:i:s');
    ocw_save_job_status($jobId, $status);

    ocw_json(200, '오케스트레이터 실행 완료', $status);
}

elseif ($action === 'status') {
    $jobId = $_GET['job_id'] ?? '';
    if (!$jobId) ocw_json(400, 'job_id 필요');
    $status = ocw_get_job_status($jobId);
    if (!$status) ocw_json(404, '해당 job_id를 찾을 수 없습니다.');
    ocw_json(200, 'ok', ['job' => $status]);
}

elseif ($action === 'history') {
    $dir = ORCHESTRATOR_LOG_DIR . '/jobs/';
    $jobs = [];
    if (is_dir($dir)) {
        $files = glob($dir . 'ocw_*.json');
        rsort($files);
        $files = array_slice($files, 0, 20);
        foreach ($files as $f) {
            $j = json_decode(file_get_contents($f), true);
            if ($j) {
                $jobs[] = [
                    'job_id' => $j['job_id'] ?? basename($f, '.json'),
                    'candidate' => $j['input']['name'] ?? '',
                    'started_at' => $j['started_at'] ?? '',
                    'status' => $j['status'] ?? ''
                ];
            }
        }
    }
    ocw_json(200, 'ok', ['jobs' => $jobs, 'count' => count($jobs)]);
}

elseif ($action === 'db_init') {
    // 새 후보자 DB 레코드 생성 + AI 생성 프롬프트 저장
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $name = $db->real_escape_string($input['name'] ?? '');
    if (!$name) ocw_json(400, '이름은 필수입니다.');

    $party = $db->real_escape_string($input['party'] ?? '');
    $slogan = $db->real_escape_string($input['slogan'] ?? '');
    $elecType = $db->real_escape_string($input['election_type'] ?? '');
    $district = $db->real_escape_string($input['district'] ?? '');

    $l1 = $db->real_escape_string($input['prompt_l1_persona'] ?? '');
    $l2 = $db->real_escape_string($input['prompt_l2_policy'] ?? '');
    $l3 = $db->real_escape_string($input['prompt_l3_interaction'] ?? '');
    $l4 = $db->real_escape_string($input['prompt_l4_safety'] ?? '');

    $layerActive = json_encode(['L1' => true, 'L2' => true, 'L3' => true, 'L4' => true]);
    $bridgeEnabled = $input['context_bridge_enabled'] ? 1 : 0;

    // 챗봇명 생성
    $chatbotName = $name;
    if ($elecType) $chatbotName .= ' ' . $elecType;
    $chatbotName .= ' AI';

    // gpt_sysprompt에 L1 저장 (레거시 호환)
    $legacyPrompt = $db->real_escape_string($l1);

    $db->query("
        INSERT INTO Gn_aievent_ms_info (
            chatbot_name, gptmodel, gpt_sysprompt, message_style,
            ai_prompt,
            prompt_l1_persona, prompt_l2_policy, prompt_l3_interaction, prompt_l4_safety,
            prompt_layer_active, prompt_version, prompt_updated_at,
            context_bridge_enabled
        ) VALUES (
            '{$chatbotName}', 'deepseek-chat', '{$legacyPrompt}', 'balanced',
            'onechat2',
            '{$l1}', '{$l2}', '{$l3}', '{$l4}',
            '{$layerActive}', 1, NOW(),
            {$bridgeEnabled}
        )
    ");
    $newSmsIdx = $db->insert_id;

    ocw_json(200, 'DB 초기화 완료', ['sms_idx' => $newSmsIdx, 'chatbot_name' => $chatbotName]);
}

else {
    ocw_json(400, '알 수 없는 action입니다. (launch, status, history, db_init)');
}