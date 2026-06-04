<?php
/**
 * 사이트 상세 페이지
 * 특정 사이트의 모든 정보를 한 화면에서 확인
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;

if (!$site) {
    // 사이트가 없으면 대시보드로 리다이렉트
    header('Location: /vermanager/');
    exit;
}

$version = site_get_current_version($site_id);
$pkg = site_get_package($site_id);
$history = site_get_history($site_id);
$history = array_reverse($history ?: []);
$improvements = site_get_improvements($site_id);
$improvements = array_reverse($improvements ?: []);
$changelog = site_get_changelog($site_id);

$unreleased = count(array_filter($improvements, fn($i) => empty($i['released_in'])));
$released = count(array_filter($improvements, fn($i) => !empty($i['released_in'])));

// 부모 사이트
$parent = null;
if (!empty($site['parent_site_id'])) {
    $parent = get_site($site['parent_site_id']);
}

// 자식 사이트 목록
$children = [];
foreach ($all_sites as $sid => $s) {
    if (($s['parent_site_id'] ?? null) === $site_id && ($s['status'] ?? 'active') === 'active') {
        $children[] = [
            'id'      => $sid,
            'name'    => $s['name'] ?? $sid,
            'domain'  => $s['domain'] ?? '',
            'version' => site_get_current_version($sid),
        ];
    }
}

$ENV_ICONS = ['production' => '🚀', 'development' => '🧪', 'staging' => '📋'];
$ENV_LABELS = ['production' => '운영', 'development' => '개발', 'staging' => '스테이징'];
$TYPE_ICONS = ['major' => '🔴', 'minor' => '🟡', 'patch' => '🟢'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>사이트 상세 — <?= htmlspecialchars($site['name'] ?? $site_id) ?></title>
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
    <a href="changelog?site=<?= $site_id ?>" class="vm-nav-link"><span>📝</span> CHANGELOG</a>
    <a href="site-detail?site=<?= $site_id ?>" class="vm-nav-link active"><span>🔍</span> 사이트 상세</a>
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
      <h1>🔍 사이트 상세 정보</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? $site_id) ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span class="vm-env-badge vm-env-<?= $site['environment'] ?? 'unknown' ?>">
          <?= $ENV_ICONS[$site['environment']] ?? '' ?> <?= htmlspecialchars($ENV_LABELS[$site['environment']] ?? $site['environment']) ?>
        </span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <a href="release?site=<?= $site_id ?>" class="vm-btn vm-btn-primary">🚀 새 릴리즈</a>
    </div>
  </header>

  <!-- 기본 정보 -->
  <div class="vm-section-title">📋 사이트 기본 정보</div>
  <div class="vm-panel">
    <div class="vm-detail-grid">
      <div class="vm-detail-item">
        <div class="vm-detail-label">사이트 ID</div>
        <div class="vm-detail-value"><code><?= $site_id ?></code></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">사이트명</div>
        <div class="vm-detail-value"><strong><?= htmlspecialchars($site['name'] ?? $site_id) ?></strong></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">도메인</div>
        <div class="vm-detail-value">
          <a href="https://<?= htmlspecialchars($site['domain'] ?? '') ?>" target="_blank">
            🔗 <?= htmlspecialchars($site['domain'] ?? '') ?>
          </a>
        </div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">별칭</div>
        <div class="vm-detail-value">
          <?php if (!empty($site['aliases'])): ?>
            <?php foreach ($site['aliases'] as $alias): ?>
              <code><?= htmlspecialchars($alias) ?></code>
            <?php endforeach; ?>
          <?php else: ?>
            <span style="color:var(--text3);">없음</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">환경</div>
        <div class="vm-detail-value">
          <span class="vm-env-badge vm-env-<?= $site['environment'] ?? 'unknown' ?>">
            <?= $ENV_ICONS[$site['environment']] ?? '' ?>
            <?= htmlspecialchars($ENV_LABELS[$site['environment']] ?? $site['environment']) ?>
          </span>
        </div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">앱 이름</div>
        <div class="vm-detail-value"><?= htmlspecialchars($site['app_name'] ?? '') ?></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">문서 루트</div>
        <div class="vm-detail-value"><code><?= htmlspecialchars($site['document_root'] ?? '') ?></code></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">서버</div>
        <div class="vm-detail-value">
          <?= htmlspecialchars($site['server_hostname'] ?? '') ?>
          <code>(<?= htmlspecialchars($site['server_ip'] ?? '') ?>)</code>
        </div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">상태</div>
        <div class="vm-detail-value">
          <?php if (($site['status'] ?? 'active') === 'active'): ?>
            <span class="vm-tag vm-tag-current">🟢 활성</span>
          <?php else: ?>
            <span class="vm-tag vm-tag-pending">🔴 비활성</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">설명</div>
        <div class="vm-detail-value"><?= htmlspecialchars($site['description'] ?? '없음') ?></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">등록일</div>
        <div class="vm-detail-value"><?= substr($site['created_at'] ?? '', 0, 10) ?></div>
      </div>
      <div class="vm-detail-item">
        <div class="vm-detail-label">최종 수정</div>
        <div class="vm-detail-value"><?= substr($site['updated_at'] ?? '', 0, 10) ?></div>
      </div>
    </div>
  </div>

  <!-- 관계 정보 -->
  <?php if ($parent || !empty($children)): ?>
  <div class="vm-section-title">🔗 사이트 관계</div>
  <div class="vm-grid-2">
    <?php if ($parent): ?>
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>⬆️ 부모 사이트</h2>
      </div>
      <div class="vm-relation-card">
        <div style="font-size:24px;">🚀</div>
        <div>
          <strong><?= htmlspecialchars($parent['name'] ?? '') ?></strong>
          <br><code><?= htmlspecialchars($parent['domain'] ?? '') ?></code>
          <br><small>v<?= site_get_current_version($parent['id'])['raw'] ?></small>
        </div>
        <a href="site-detail?site=<?= $parent['id'] ?>" class="vm-btn vm-btn-sm vm-btn-outline">바로가기</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($children)): ?>
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>⬇️ 하위 사이트 (<?= count($children) ?>개)</h2>
      </div>
      <div class="vm-list">
        <?php foreach ($children as $child): ?>
        <div class="vm-list-row">
          <span class="vm-list-icon">🧪</span>
          <div class="vm-list-content">
            <strong><?= htmlspecialchars($child['name']) ?></strong>
            <small><?= htmlspecialchars($child['domain']) ?></small>
          </div>
          <span>v<?= $child['version']['raw'] ?></span>
          <a href="site-detail?site=<?= $child['id'] ?>" class="vm-btn vm-btn-sm vm-btn-outline">상세</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- 버전 정보 -->
  <div class="vm-section-title">🏷️ 버전 정보</div>
  <div class="vm-cards">
    <div class="vm-card">
      <div class="vm-card-icon">📌</div>
      <div class="vm-card-body">
        <div class="vm-card-value">v<?= $version['raw'] ?></div>
        <div class="vm-card-label">현재 버전</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">📦</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= count($history) ?></div>
        <div class="vm-card-label">총 릴리즈</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">⏳</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $unreleased ?></div>
        <div class="vm-card-label">미발행 개선</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">✅</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $released ?></div>
        <div class="vm-card-label">발행된 개선</div>
      </div>
    </div>
  </div>

  <!-- 최근 릴리즈 -->
  <div class="vm-section-title">📜 최근 릴리즈 (최근 5건)</div>
  <div class="vm-panel">
    <?php if (empty($history)): ?>
      <div class="vm-empty">아직 릴리즈 기록이 없습니다</div>
    <?php else: ?>
    <div class="vm-list">
      <?php foreach (array_slice($history, 0, 5) as $h):
        $icon = $TYPE_ICONS[$h['type']] ?? '⚪';
      ?>
      <div class="vm-list-row">
        <span class="vm-list-icon"><?= $icon ?></span>
        <div class="vm-list-content">
          <strong>v<?= $h['to'] ?></strong>
          <small>← v<?= $h['from'] ?> · <?= $h['type'] ?></small>
          <?php if (!empty($h['note'])): ?>
            <small style="color:var(--text3);"><?= htmlspecialchars(mb_substr($h['note'], 0, 60)) ?></small>
          <?php endif; ?>
        </div>
        <span class="vm-list-meta"><?= substr($h['date'], 0, 10) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:right; margin-top:12px;">
      <a href="history?site=<?= $site_id ?>" class="vm-link">전체 히스토리 보기 →</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- 패키지 정보 -->
  <div class="vm-section-title">📦 package.json</div>
  <div class="vm-panel">
    <div class="vm-code-block">
<pre><?= json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
    </div>
  </div>

</main>
</div>

<script>
function switchSite(siteId) {
    window.location.href = 'site-detail?site=' + siteId;
}
</script>
</body>
</html>