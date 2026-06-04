<?php
/**
 * 버전 히스토리 페이지 (다중 사이트 지원)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$history = $site_id ? site_get_history($site_id) : [];
$history = array_reverse($history ?: []);

$type_filter = $_GET['type'] ?? '';
if ($type_filter && in_array($type_filter, ['major', 'minor', 'patch'])) {
    $history = array_values(array_filter($history, fn($h) => ($h['type'] ?? '') === $type_filter));
}

$TYPE_ICONS = ['major' => '🔴', 'minor' => '🟡', 'patch' => '🟢'];
$TYPE_LABELS = ['major' => 'MAJOR', 'minor' => 'MINOR', 'patch' => 'PATCH'];

// 통계
$type_counts = ['major'=>0, 'minor'=>0, 'patch'=>0];
foreach ($history as $h) {
    $t = $h['type'] ?? '';
    if (isset($type_counts[$t])) $type_counts[$t]++;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>버전 히스토리 — <?= SYSTEM_NAME ?></title>
<link rel="stylesheet" href="/vermanager/assets/css/style.css">
</head>
<body>
<div class="vm-layout">

<aside class="vm-sidebar">
  <div class="vm-brand">
    <div class="vm-brand-icon">🔢</div>
    <div class="vm-brand-text"><strong><?= SYSTEM_NAME ?></strong><small>다중 사이트·다중 서버</small></div>
  </div>
  <div class="vm-site-selector">
    <div class="vm-site-selector-label">조회 사이트</div>
    <select class="vm-site-select" onchange="switchSite(this.value)">
      <?php foreach ($site_summaries as $sum): ?>
      <option value="<?= $sum['id'] ?>" <?= $sum['is_current'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($sum['name']) ?> (v<?= $sum['version'] ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <nav class="vm-nav">
    <a href="?site=<?= $site_id ?>" class="vm-nav-link"><span>📊</span> 대시보드</a>
    <a href="release?site=<?= $site_id ?>" class="vm-nav-link"><span>🚀</span> 새 릴리즈</a>
    <a href="history?site=<?= $site_id ?>" class="vm-nav-link active"><span>📜</span> 버전 히스토리</a>
    <a href="improvements?site=<?= $site_id ?>" class="vm-nav-link"><span>🔧</span> 소스 개선 목록</a>
    <a href="changelog?site=<?= $site_id ?>" class="vm-nav-link"><span>📝</span> CHANGELOG</a>
    <a href="site-detail?site=<?= $site_id ?>" class="vm-nav-link"><span>🔍</span> 사이트 상세</a>
    <div class="vm-nav-divider"></div>
    <a href="sites" class="vm-nav-link"><span>🌐</span> 사이트 관리</a>
    <a href="remote?site=<?= $site_id ?>" class="vm-nav-link"><span>🖥️</span> 서버 관리</a>
    <a href="git?site=<?= $site_id ?>" class="vm-nav-link"><span>📦</span> Git 자동화</a>
  </nav>
  <div class="vm-sidebar-footer">
    <div class="vm-current-mini">
      <span class="vm-dot live"></span>
      <?= htmlspecialchars($site['name'] ?? '') ?>
      <small>v<?= $version['raw'] ?></small>
    </div>
  </div>
</aside>

<main class="vm-main">
  <header class="vm-topbar">
    <div>
      <h1>📜 버전 히스토리</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span>현재: v<?= $version['raw'] ?></span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <span class="vm-pill">총 <?= count($history) ?>건</span>
    </div>
  </header>

  <!-- 릴리즈 통계 -->
  <div class="vm-cards">
    <div class="vm-card">
      <div class="vm-card-icon">🔴</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $type_counts['major'] ?></div>
        <div class="vm-card-label">MAJOR</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🟡</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $type_counts['minor'] ?></div>
        <div class="vm-card-label">MINOR</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🟢</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $type_counts['patch'] ?></div>
        <div class="vm-card-label">PATCH</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">📦</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= array_sum($type_counts) ?></div>
        <div class="vm-card-label">전체 릴리즈</div>
      </div>
    </div>
  </div>

  <!-- 유형 필터 -->
  <div class="vm-panel">
    <div class="vm-filter-bar">
      <span>필터:</span>
      <a href="history?site=<?= $site_id ?>" class="vm-filter-tag <?= $type_filter === '' ? 'active' : '' ?>">전체</a>
      <a href="history?site=<?= $site_id ?>&type=major" class="vm-filter-tag <?= $type_filter === 'major' ? 'active' : '' ?>">🔴 MAJOR</a>
      <a href="history?site=<?= $site_id ?>&type=minor" class="vm-filter-tag <?= $type_filter === 'minor' ? 'active' : '' ?>">🟡 MINOR</a>
      <a href="history?site=<?= $site_id ?>&type=patch" class="vm-filter-tag <?= $type_filter === 'patch' ? 'active' : '' ?>">🟢 PATCH</a>
    </div>
  </div>

  <!-- 히스토리 목록 -->
  <div class="vm-panel">
    <?php if (empty($history)): ?>
      <div class="vm-empty">아직 릴리즈 기록이 없습니다</div>
    <?php else: ?>
    <div class="vm-timeline">
      <?php foreach ($history as $h):
        $icon = $TYPE_ICONS[$h['type']] ?? '⚪';
        $label = $TYPE_LABELS[$h['type']] ?? $h['type'];
        $date = substr($h['date'], 0, 10);
        $time = substr($h['date'], 11, 8);
      ?>
      <div class="vm-timeline-item">
        <div class="vm-timeline-icon"><?= $icon ?></div>
        <div class="vm-timeline-content">
          <div class="vm-timeline-header">
            <strong>v<?= $h['to'] ?></strong>
            <span class="vm-tag vm-tag-<?= $h['type'] ?>"><?= $label ?></span>
            <span class="vm-timeline-date"><?= $date ?> <?= $time ?></span>
          </div>
          <div class="vm-timeline-from">← v<?= $h['from'] ?></div>
          <?php if (!empty($h['note'])): ?>
          <div class="vm-timeline-note">💬 <?= htmlspecialchars($h['note']) ?></div>
          <?php endif; ?>
          <?php if (!empty($h['imp_summary'])): ?>
          <div class="vm-timeline-changes">
            <strong>포함된 개선 사항 (<?= $h['imp_count'] ?? count($h['imp_summary']) ?>건):</strong>
            <ul>
              <?php foreach ($h['imp_summary'] as $change): ?>
              <li>
                <span class="vm-tag"><?= htmlspecialchars($change['category'] ?? '기타') ?></span>
                <?= htmlspecialchars($change['title'] ?? $change['content'] ?? '(내용없음)') ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</main>
</div>

<script>
function switchSite(siteId) {
    window.location.href = 'history?site=' + siteId;
}
</script>
</body>
</html>