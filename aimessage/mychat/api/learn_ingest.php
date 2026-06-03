<?php
/**
 * ================================================================
 *  [MYCHAT-API] /aimessage/mychat/api/learn_ingest.php
 *  ----------------------------------------------------------------
 *  통합 학습 데이터 수집 엔드포인트 (스텁 + 라우터)
 *
 *  지원 액션:
 *   - transcribe       : multipart FormData(audio) → STT 변환
 *   - upload_file      : multipart FormData(file)  → 텍스트 추출 + 벡터 인덱싱
 *   - phone_counts     : GET ?range=today|7d|30d|all → 폰 소스별 카운트
 *   - phone_pull       : POST {sources, range, mode, category} → 폰 데이터 통합 텍스트
 *   - web_stats        : GET → 웹수집 통계 + 최종 크롤시간
 *   - add_web_source   : POST {value, category} → 수집 소스 등록
 *
 *  본 파일은 **컨트랙트와 라우팅만** 정의합니다. 실제 STT/문서파싱/임베딩은
 *  서버팀이 OpenAI/Whisper/Tika 등으로 추후 구현합니다. 미구현 액션은
 *  ok:false + error:'not_implemented' 를 돌려 프론트가 graceful fallback 합니다.
 *
 *  생성: 2026-06-04 / 조은 × 아리(GenSpark)
 * ================================================================
 */

declare(strict_types=1);

// ── 공통 응답 헤더 ───────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── 세션 / 인증 (기존 마이챗 세션 컨텍스트 가정) ──────────
// 실제 환경에서는 /aimessage/mychat/api/_bootstrap.php 가 있다면 그걸 require
@session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  http_response_code(401);
  echo json_encode(['ok'=>false, 'error'=>'unauthorized'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ── 액션 분기 ────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = '';
$body   = [];

if ($method === 'POST') {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'multipart/form-data') !== false) {
    $action = $_POST['action'] ?? '';
    $body   = $_POST;
  } else {
    $raw  = file_get_contents('php://input') ?: '';
    $body = json_decode($raw, true) ?: [];
    $action = $body['action'] ?? '';
  }
} else {
  $action = $_GET['action'] ?? '';
  $body   = $_GET;
}

// ── 라우팅 ───────────────────────────────────────────────
try {
  switch ($action) {

    case 'transcribe':
      // 음성 → 텍스트 변환
      // 미구현 폴백: 빈 텍스트로 응답해 프론트가 수동입력 모드로 전환
      echo json_encode([
        'ok'    => false,
        'error' => 'not_implemented',
        'hint'  => 'STT 엔드포인트를 OpenAI Whisper 또는 ElevenLabs Scribe로 연결해주세요. 입력: POST multipart "audio" (audio/webm)',
      ], JSON_UNESCAPED_UNICODE);
      break;

    case 'upload_file':
      // 파일 → 텍스트 추출 + 임베딩
      if (empty($_FILES['file'])) {
        echo json_encode(['ok'=>false, 'error'=>'no_file'], JSON_UNESCAPED_UNICODE);
        break;
      }
      $f = $_FILES['file'];
      // 미구현 폴백 — 파일을 받아만 두고 ok 응답 (실제 텍스트 추출은 서버팀 작업)
      echo json_encode([
        'ok'       => true,
        'stored'   => false,
        'filename' => basename($f['name']),
        'size'     => (int)$f['size'],
        'chars'    => 0,
        'hint'     => '파일 파싱(Apache Tika / pdf-parser / Tesseract OCR) 연결 필요',
      ], JSON_UNESCAPED_UNICODE);
      break;

    case 'phone_counts':
      // 폰 데이터 소스별 카운트 (range: today/7d/30d/all)
      // 실데이터 없을 때 — 데모 카운트 (앱 연동 전 UX 확인용)
      $range = $body['range'] ?? 'today';
      $demo  = ['today'=>[5,12,28,6,1,14,1,4], '7d'=>[42,98,210,38,4,87,7,28], '30d'=>[180,420,950,160,15,360,30,120], 'all'=>[1800,5200,12000,2100,180,4800,400,1500]];
      $vals  = $demo[$range] ?? $demo['today'];
      $keys  = ['call','sms','kakao','email','meeting','photo','health','cal'];
      $counts = array_combine($keys, $vals);
      echo json_encode(['ok'=>true, 'counts'=>$counts, 'demo'=>true], JSON_UNESCAPED_UNICODE);
      break;

    case 'phone_pull':
      // 선택된 소스 데이터를 텍스트로 통합해서 반환
      // 미구현 — 프론트가 데모 텍스트로 폴백하도록 ok:false 반환
      echo json_encode([
        'ok'    => false,
        'error' => 'not_implemented',
        'hint'  => 'iamapp 모바일 앱이 /api/phone_sync.php 로 데이터를 push한 후, 여기서 sources/range/mode 별 통합 텍스트를 생성하세요.'
      ], JSON_UNESCAPED_UNICODE);
      break;

    case 'web_stats':
      // 웹수집 통계
      echo json_encode([
        'ok'=>true,
        'stats'=>['news'=>0,'youtube'=>0,'blog'=>0,'web'=>0],
        'last_crawl'=>null,
        'demo'=>true,
      ], JSON_UNESCAPED_UNICODE);
      break;

    case 'add_web_source':
      // 웹 수집 소스 등록
      // 미구현 폴백
      echo json_encode([
        'ok'    => false,
        'error' => 'not_implemented',
        'hint'  => '크롤러 큐(/cron/web_crawl.php)에 추가하는 로직 필요'
      ], JSON_UNESCAPED_UNICODE);
      break;

    default:
      echo json_encode(['ok'=>false, 'error'=>'unknown_action', 'received'=>$action], JSON_UNESCAPED_UNICODE);
  }
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'error'=>'server_error',
    'detail'=> (getenv('APP_DEBUG') ? $e->getMessage() : 'internal')
  ], JSON_UNESCAPED_UNICODE);
}
