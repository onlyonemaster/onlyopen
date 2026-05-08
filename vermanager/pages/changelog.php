<?php
/**
 * CHANGELOG 페이지 (다중 사이트 지원)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$changelog = $site_id ? site_get_changelog($site_id) : [];
$changelog = array_reverse($changelog ?: []);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CHANGELOG — <?= SYSTEM_NAME ?></title>
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
    <a href="history?site=<?= $site_id ?>" class="vm-nav-link"><span>📜</span> 버전 히스토리</a>
    <a href="improvements?site=<?= $site_id ?>" class="vm-nav-link"><span>🔧</span> 소스 개선 목록</a>
    <a href="changelog?site=<?= $site_id ?>" class="vm-nav-link active"><span>📝</span> CHANGELOG</a>
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
      <h1>📝 CHANGELOG</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span>총 <?= count($changelog) ?>건의 변경 기록</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <span class="vm-pill"><?= htmlspecialchars($site['app_name'] ?? $site['name'] ?? '') ?></span>
    </div>
  </header>

  <?php if (empty($changelog)): ?>
  <div class="vm-panel">
    <div class="vm-empty">아직 CHANGELOG 기록이 없습니다</div>
    <div class="vm-empty-hint">
      <a href="release?site=<?= $site_id ?>" class="vm-btn vm-btn-primary">새 릴리즈 만들기 →</a>
    </div>
  </div>
  <?php else: ?>
  <?php foreach ($changelog as $entry): ?>
  <div class="vm-panel vm-changelog-entry">
    <div class="vm-changelog-header">
      <h2>
        <span class="vm-version-badge">v<?= htmlspecialchars($entry['version']) ?></span>
      </h2>
      <div class="vm-changelog-meta">
        <span>📅 <?= htmlspecialchars($entry['date'] ?? '') ?></span>
        <?php
        $type_label = ['major'=>'🔴 MAJOR', 'minor'=>'🟡 MINOR', 'patch'=>'🟢 PATCH'][$entry['type'] ?? 'patch'] ?? $entry['type'] ?? '';
        ?>
        <span class="vm-tag"><?= $type_label ?></span>
      </div>
    </div>

    <?php if (!empty($entry['note'])): ?>
    <div class="vm-changelog-note">
      💬 <?= htmlspecialchars($entry['note']) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($entry['changes'])): ?>
    <div class="vm-changelog-changes">
      <strong>변경 사항 (<?= count($entry['changes']) ?>건):</strong>
      <ul>
        <?php foreach ($entry['changes'] as $change): ?>
        <li>
          <span class="vm-tag"><?= htmlspecialchars(is_array($change) ? ($change['category'] ?? '기타') : '기타') ?></span>
          <?= htmlspecialchars(is_array($change) ? ($change['title'] ?? '(내용없음)') : (string)$change) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</main>
</div>

<script>
function switchSite(siteId) {
    window.location.href = 'changelog?site=' + siteId;
}
</script>
</body>
</html>