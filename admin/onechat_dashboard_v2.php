<?php
/**
 * 원챗(OneChat) 관리자 대시보드 v2.0
 * ─────────────────────────────────────────────────────────────────
 * Phase 3: AI 자동 프롬프팅 & 데이터 주입을 포함한 확장 대시보드.
 * 기존 onechat_dashboard.php를 확장합니다.
 *
 * 포함 기능:
 *   - KPI 패널 (챗봇 수, 총 대화량, 평균 만족도, RAG 청크 수)
 *   - 후보자 현황 테이블 (4-Layer 설정 상태, RAG 학습 현황)
 *   - AI 오케스트레이터 바로가기
 *   - Context Bridge 동기화 상태
 *   - 시스템 건강도 체크
 */

require_once __DIR__ . '/include/admin_check.php';
require_once __DIR__ . '/../config/database.php';

$db = getDatabaseConnection();

// ── KPI 데이터 수집 ─────────────────────────────────────────────────

// 총 챗봇 수
$totalBots = 0;
$r = $db->query("SELECT COUNT(*) AS cnt FROM Gn_aievent_ms_info WHERE ai_prompt IN ('onechat','onechat2')");
if ($r && $row = $r->fetch_assoc()) $totalBots = (int)$row['cnt'];

// Context Bridge 활성화 수
$bridgeActive = 0;
$r = $db->query("SELECT COUNT(*) AS cnt FROM Gn_aievent_ms_info WHERE ai_prompt IN ('onechat','onechat2') AND context_bridge_enabled=1");
if ($r && $row = $r->fetch_assoc()) $bridgeActive = (int)$row['cnt'];

// 4-Layer 설정 완료 수 (prompt_version > 0)
$layerComplete = 0;
$r = $db->query("SELECT COUNT(*) AS cnt FROM Gn_aievent_ms_info WHERE ai_prompt IN ('onechat','onechat2') AND prompt_version > 0");
if ($r && $row = $r->fetch_assoc()) $layerComplete = (int)$row['cnt'];

// 총 대화량 (가상: chat_log 테이블이 있을 경우)
$totalChats = 0;
$chatTableCheck = $db->query("SHOW TABLES LIKE 'Gn_onechat_chat_log'");
if ($chatTableCheck && $chatTableCheck->num_rows > 0) {
    $r = $db->query("SELECT COUNT(*) AS cnt FROM Gn_onechat_chat_log");
    if ($r && $row = $r->fetch_assoc()) $totalChats = (int)$row['cnt'];
} else {
    $totalChats = '-';
}

// 평균 만족도
$avgRating = 0;
if ($chatTableCheck && $chatTableCheck->num_rows > 0) {
    $r = $db->query("SELECT AVG(feedback_score) AS avg_score FROM Gn_onechat_chat_log WHERE feedback_score IS NOT NULL");
    if ($r && $row = $r->fetch_assoc()) $avgRating = round((float)$row['avg_score'], 2);
} else {
    $avgRating = '-';
}

// RAG 총 청크 수 (근사치: 각 후보자별 context_bridge_config JSON 합산)
$totalChunks = 0;
$r = $db->query("SELECT context_bridge_config FROM Gn_aievent_ms_info WHERE ai_prompt IN ('onechat','onechat2') AND context_bridge_config IS NOT NULL");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $config = json_decode($row['context_bridge_config'], true);
        if ($config && isset($config['total_analyzed'])) {
            $totalChunks += (int)$config['total_analyzed'];
        }
    }
}
if ($totalChunks === 0) $totalChunks = '-';

// ── 후보자 리스트 ───────────────────────────────────────────────────

$candidates = [];
$r = $db->query("
    SELECT sms_idx, chatbot_name, gptmodel, prompt_l1_persona, prompt_l2_policy,
           prompt_l3_interaction, prompt_l4_safety, prompt_layer_active,
           prompt_version, prompt_updated_at,
           context_bridge_enabled, context_bridge_config,
           rag_provider, rag_collection_name
    FROM Gn_aievent_ms_info
    WHERE ai_prompt IN ('onechat','onechat2')
    ORDER BY sms_idx DESC
    LIMIT 50
");

if ($r) {
    while ($row = $r->fetch_assoc()) {
        $candidates[] = $row;
    }
}

/**
 * Layer 설정 상태 체크
 */
function layerStatus(array $c): array {
    $layers = ['L1' => 'prompt_l1_persona', 'L2' => 'prompt_l2_policy', 'L3' => 'prompt_l3_interaction', 'L4' => 'prompt_l4_safety'];
    $active = json_decode($c['prompt_layer_active'] ?? '{}', true) ?: [];

    $result = [];
    foreach ($layers as $key => $col) {
        $has = !empty($c[$col]);
        $on = $active[$key] ?? true;
        $result[$key] = $has ? ($on ? 'on' : 'off') : 'empty';
    }
    return $result;
}

function layerStatusBadge(string $status): string {
    if ($status === 'on') return '<span class="odv2-badge odv2-badge-on">ON</span>';
    if ($status === 'off') return '<span class="odv2-badge odv2-badge-off">OFF</span>';
    return '<span class="odv2-badge odv2-badge-empty">Empty</span>';
}

// ── 시스템 건강도 ───────────────────────────────────────────────────

$healthChecks = [
    'DB 연결' => $db ? ['status' => 'ok', 'msg' => '정상'] : ['status' => 'error', 'msg' => '연결 실패'],
    'Gn_aievent_ms_info' => ['status' => 'ok', 'msg' => "{$totalBots}개 챗봇"],
    'Gn_onechat_prompt_log' => ['status' => 'ok', 'msg' => '테이블 존재'],
    '4-Layer 마이그레이션' => $layerComplete > 0 ? ['status' => 'ok', 'msg' => "{$layerComplete}개 완료"] : ['status' => 'warn', 'msg' => '설정 필요'],
    'Context Bridge' => $bridgeActive > 0 ? ['status' => 'ok', 'msg' => "{$bridgeActive}개 활성"] : ['status' => 'warn', 'msg' => '활성화된 브릿지 없음'],
    'RAG 서버' => ['status' => 'info', 'msg' => '별도 체크 필요 (port 5100)'],
];

// 로그 테이블 존재 여부 확인
$logTableCheck = $db->query("SHOW TABLES LIKE 'Gn_onechat_prompt_log'");
if ($logTableCheck->num_rows === 0) {
    $healthChecks['Gn_onechat_prompt_log'] = ['status' => 'warn', 'msg' => '테이블 미존재 (마이그레이션 필요)'];
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>원챗(OneChat) 관리자 대시보드 v2.0</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* ── Reset + Base ─────────────────────────────── */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; color: #111827; }
    .odv2-wrap { max-width: 1280px; margin: 0 auto; padding: 24px 28px; }

    /* ── Header ──────────────────────────────────── */
    .odv2-header { margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; }
    .odv2-title { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .odv2-title-icon { font-size: 30px; }
    .odv2-version { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 12px; font-weight: 600; }

    /* ── KPI Panel ───────────────────────────────── */
    .odv2-kpi { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px; }
    .odv2-kpi-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 16px; text-align: center; transition: transform .15s; }
    .odv2-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .odv2-kpi-value { font-size: 28px; font-weight: 800; color: #4f46e5; }
    .odv2-kpi-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .odv2-kpi-icon { font-size: 24px; display: block; margin-bottom: 6px; }

    /* ── Action Bar ──────────────────────────────── */
    .odv2-actions { display: flex; gap: 10px; margin-bottom: 24px; }
    .odv2-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .2s; }
    .odv2-btn-primary { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; }
    .odv2-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,.3); }
    .odv2-btn-outline { background: #fff; color: #374151; border: 1.5px solid #d1d5db; }
    .odv2-btn-outline:hover { background: #f9fafb; border-color: #9ca3af; }

    /* ── Grid Columns ────────────────────────────── */
    .odv2-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 20px; margin-bottom: 24px; }
    @media (max-width: 960px) { .odv2-grid { grid-template-columns: 1fr; } }

    .odv2-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .odv2-card-header { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
    .odv2-card-header h2 { font-size: 16px; font-weight: 700; }
    .odv2-card-header span { font-size: 12px; color: #9ca3af; }

    /* ── Candidate Table ─────────────────────────── */
    .odv2-tbl-wrap { overflow-x: auto; }
    .odv2-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
    .odv2-tbl th { background: #f9fafb; font-size: 12px; font-weight: 700; color: #6b7280; padding: 10px 12px; text-align: left; white-space: nowrap; border-bottom: 2px solid #e5e7eb; }
    .odv2-tbl td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
    .odv2-tbl tr:hover td { background: #fafafe; }
    .odv2-tbl a { color: #4f46e5; text-decoration: none; font-weight: 600; }
    .odv2-tbl a:hover { text-decoration: underline; }

    .odv2-badge { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 700; }
    .odv2-badge-on { background: #d1fae5; color: #065f46; }
    .odv2-badge-off { background: #fef3c7; color: #92400e; }
    .odv2-badge-empty { background: #f3f4f6; color: #9ca3af; }
    .odv2-badge-active { background: #dbeafe; color: #1e40af; }

    /* ── Health Panel ────────────────────────────── */
    .odv2-health-list { padding: 12px 20px; }
    .odv2-health-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .odv2-health-item:last-child { border-bottom: none; }
    .odv2-health-name { font-weight: 600; }
    .odv2-health-status { font-size: 12px; }
    .odv2-status-ok { color: #059669; }
    .odv2-status-warn { color: #d97706; }
    .odv2-status-error { color: #dc2626; }
    .odv2-status-info { color: #6366f1; }

    /* ── Empty ────────────────────────────────────── */
    .odv2-empty { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 14px; }

    /* ── Responsive ──────────────────────────────── */
    @media (max-width: 768px) {
      .odv2-kpi { grid-template-columns: repeat(3, 1fr); }
      .odv2-header { flex-direction: column; gap: 12px; align-items: flex-start; }
      .odv2-actions { flex-wrap: wrap; }
    }
    @media (max-width: 500px) {
      .odv2-kpi { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<div class="odv2-wrap"><!-- odv2 = Onechat Dashboard V2 namespace -->

  <!-- ── 헤더 ───────────────────────────────────────────────────── -->
  <div class="odv2-header">
    <h1 class="odv2-title">
      <span class="odv2-title-icon">📊</span>
      원챗(OneChat) 대시보드
      <span class="odv2-version">v2.0</span>
    </h1>
    <div class="odv2-actions">
      <a href="onechat_candidate_wizard.html" class="odv2-btn odv2-btn-primary" target="_blank">
        🤖 AI 오케스트레이터
      </a>
      <a href="onechat_bot_manager.php" class="odv2-btn odv2-btn-outline">
        ⚙️ 챗봇 관리
      </a>
      <a href="onechat_ai_analytics.php" class="odv2-btn odv2-btn-outline">
        📈 AI 분석
      </a>
    </div>
  </div>

  <!-- ── KPI 패널 ───────────────────────────────────────────────── -->
  <div class="odv2-kpi">
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">🤖</span>
      <div class="odv2-kpi-value"><?= $totalBots ?></div>
      <div class="odv2-kpi-label">총 챗봇</div>
    </div>
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">🧩</span>
      <div class="odv2-kpi-value"><?= $layerComplete ?></div>
      <div class="odv2-kpi-label">4-Layer 완료</div>
    </div>
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">💬</span>
      <div class="odv2-kpi-value"><?= $totalChats ?></div>
      <div class="odv2-kpi-label">총 대화</div>
    </div>
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">⭐</span>
      <div class="odv2-kpi-value"><?= $avgRating ?></div>
      <div class="odv2-kpi-label">평균 만족도</div>
    </div>
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">🔗</span>
      <div class="odv2-kpi-value"><?= $bridgeActive ?></div>
      <div class="odv2-kpi-label">Bridge 활성</div>
    </div>
    <div class="odv2-kpi-card">
      <span class="odv2-kpi-icon">📦</span>
      <div class="odv2-kpi-value"><?= $totalChunks ?></div>
      <div class="odv2-kpi-label">RAG 청크</div>
    </div>
  </div>

  <!-- ── Grid: 후보자 테이블 + 건강도 ─────────────────────────────── -->
  <div class="odv2-grid">
    <!-- 왼쪽: 후보자 현황 -->
    <div class="odv2-card">
      <div class="odv2-card-header">
        <h2>🎯 후보자 현황 (<?= count($candidates) ?>명)</h2>
        <span>4-Layer 상태 · 클릭 시 상세</span>
      </div>
      <?php if (empty($candidates)): ?>
        <div class="odv2-empty">등록된 후보자 챗봇이 없습니다.<br>AI 오케스트레이터로 첫 후보자를 등록하세요.</div>
      <?php else: ?>
      <div class="odv2-tbl-wrap">
        <table class="odv2-tbl">
          <thead>
            <tr>
              <th>No</th>
              <th>챗봇명</th>
              <th>L1 Persona</th>
              <th>L2 Policy</th>
              <th>L3 Interaction</th>
              <th>L4 Safety</th>
              <th>Ver</th>
              <th>Bridge</th>
              <th>최종 수정</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($candidates as $c):
              $ls = layerStatus($c);
              $bridgeOn = (int)($c['context_bridge_enabled'] ?? 0);
              $ver = (int)($c['prompt_version'] ?? 0);
              $updated = $c['prompt_updated_at'] ?? '-';
            ?>
            <tr>
              <td style="color:#9ca3af;"><?= $no++ ?></td>
              <td>
                <a href="onechat_bot_manager.php?sms_idx=<?= $c['sms_idx'] ?>" target="_blank">
                  <?= htmlspecialchars($c['chatbot_name'] ?? '무제') ?>
                </a>
              </td>
              <td><?= layerStatusBadge($ls['L1']) ?></td>
              <td><?= layerStatusBadge($ls['L2']) ?></td>
              <td><?= layerStatusBadge($ls['L3']) ?></td>
              <td><?= layerStatusBadge($ls['L4']) ?></td>
              <td style="font-weight:600;<?= $ver>0?'color:#4f46e5;':'color:#9ca3af;' ?>">v<?= $ver ?></td>
              <td>
                <?php if ($bridgeOn): ?>
                  <span class="odv2-badge odv2-badge-on">ON</span>
                <?php else: ?>
                  <span class="odv2-badge odv2-badge-off">OFF</span>
                <?php endif; ?>
              </td>
              <td style="color:#9ca3af; font-size:11px;"><?= $updated ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- 오른쪽: 건강도 -->
    <div class="odv2-card">
      <div class="odv2-card-header">
        <h2>🩺 시스템 건강도</h2>
        <span>실시간</span>
      </div>
      <div class="odv2-health-list">
        <?php foreach ($healthChecks as $name => $h): ?>
        <div class="odv2-health-item">
          <span class="odv2-health-name"><?= $name ?></span>
          <span class="odv2-health-status odv2-status-<?= $h['status'] ?>"><?= $h['msg'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── 하단 액션 ───────────────────────────────────────────────── -->
  <div class="odv2-actions" style="justify-content:center;">
    <a href="onechat_dashboard.php" class="odv2-btn odv2-btn-outline">📊 레거시 대시보드</a>
    <a href="onechat_system_dashboard.php" class="odv2-btn odv2-btn-outline">⚙️ 시스템 설정</a>
    <button class="odv2-btn odv2-btn-outline" onclick="location.reload()">🔄 새로고침</button>
  </div>

</div><!-- /odv2-wrap -->

</body>
</html>