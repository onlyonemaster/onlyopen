<?php
/**
 * 설정 페이지 (다중 사이트 지원)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$pkg = $site_id ? site_get_package($site_id) : [];
$stats = get_system_stats();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>설정 — <?= SYSTEM_NAME ?></title>
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
    <div class="vm-site-selector-label">설정 대상 사이트</div>
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
      <h1>⚙️ 시스템 설정</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span>설정</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <span class="vm-pill">v<?= $version['raw'] ?></span>
    </div>
  </header>

  <!-- 시스템 정보 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>📋 시스템 정보</h2>
    </div>
    <div class="vm-config-table">
      <div class="vm-config-row">
        <div class="vm-config-label">시스템 이름</div>
        <div class="vm-config-value"><strong><?= SYSTEM_NAME ?></strong></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">시스템 슬러그</div>
        <div class="vm-config-value"><code><?= SYSTEM_SLUG ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">데이터 디렉터리</div>
        <div class="vm-config-value"><code><?= DATA_DIR ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">사이트 데이터 디렉터리</div>
        <div class="vm-config-value"><code><?= SITES_DATA_DIR ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">레지스트리 파일</div>
        <div class="vm-config-value"><code><?= REGISTRY_FILE ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">소스 개선 관리 URL</div>
        <div class="vm-config-value">
          <a href="<?= SRC_IMPROVEMENTS_URL ?>" target="_blank"><?= SRC_IMPROVEMENTS_URL ?></a>
        </div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">전체 사이트 수</div>
        <div class="vm-config-value"><strong><?= $stats['total_sites'] ?></strong>개 (운영 <?= $stats['production_sites'] ?>개, 개발 <?= $stats['development_sites'] ?>개)</div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">관리 서버 수</div>
        <div class="vm-config-value"><strong><?= $stats['total_servers'] ?></strong>대</div>
      </div>
    </div>
  </div>

  <!-- 현재 사이트 설정 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>📍 <?= htmlspecialchars($site['name'] ?? '') ?> 설정</h2>
    </div>
    <div class="vm-config-table">
      <div class="vm-config-row">
        <div class="vm-config-label">사이트 ID</div>
        <div class="vm-config-value"><code><?= $site_id ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">도메인</div>
        <div class="vm-config-value"><strong><?= htmlspecialchars($site['domain'] ?? '') ?></strong></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">환경</div>
        <div class="vm-config-value"><?= htmlspecialchars($site['environment'] ?? '') ?></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">문서 루트</div>
        <div class="vm-config-value"><code><?= htmlspecialchars($site['document_root'] ?? '') ?></code></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">서버</div>
        <div class="vm-config-value"><?= htmlspecialchars($site['server_hostname'] ?? '') ?> (<?= htmlspecialchars($site['server_ip'] ?? '') ?>)</div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">앱 이름</div>
        <div class="vm-config-value"><?= htmlspecialchars($site['app_name'] ?? '') ?></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">부모 사이트</div>
        <div class="vm-config-value"><?= $site['parent_site_id'] ? htmlspecialchars(get_site($site['parent_site_id'])['name'] ?? $site['parent_site_id']) : '없음 (최상위)' ?></div>
      </div>
      <div class="vm-config-row">
        <div class="vm-config-label">현재 버전</div>
        <div class="vm-config-value"><strong>v<?= $version['raw'] ?></strong></div>
      </div>
    </div>
  </div>

  <!-- 디렉터리 구조 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>📁 데이터 디렉터리 구조</h2>
    </div>
    <div class="vm-directory-tree">
<pre><?= SYSTEM_SLUG ?>/
├── config.php          ← 시스템 설정 (다중 사이트)
├── index.php           ← 메인 라우터
├── .htaccess           ← Apache 설정
├── api/
│   ├── version.php     ← 버전 조회 API
│   ├── release.php     ← 릴리즈 API
│   ├── history.php     ← 히스토리 API
│   ├── improvements.php← 개선 항목 API
│   └── sites.php       ← 사이트 관리 API
├── pages/
│   ├── dashboard.php   ← 대시보드
│   ├── release.php     ← 릴리즈 페이지
│   ├── history.php     ← 히스토리 페이지
│   ├── improvements.php← 개선 목록 페이지
│   ├── changelog.php   ← CHANGELOG 페이지
│   ├── settings.php    ← 설정 페이지
│   ├── sites.php       ← 사이트 관리 페이지
│   └── site-detail.php ← 사이트 상세 페이지
├── assets/
│   └── css/style.css   ← 스타일시트
└── data/
    ├── site-registry.json         ← 사이트 등록부
    └── sites/
        ├── kiam-prod/             ← KIAM 운영환경 데이터
        │   ├── package.json
        │   ├── version_history.json
        │   ├── improvements.json
        │   └── changelog.json
        └── kiam-dev/              ← KIAM 개발환경 데이터
            ├── package.json
            ├── version_history.json
            ├── improvements.json
            └── changelog.json</pre>
    </div>
  </div>

</main>
</div>

<script>
function switchSite(siteId) {
    window.location.href = 'settings?site=' + siteId;
}
</script>
</body>
</html>