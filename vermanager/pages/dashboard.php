<?php
/**
 * 다중 사이트 대시보드
 * 모든 사이트 현황을 한눈에 보여주는 메인 화면
 * 수정: 2026-05-03 아리아 (Phase 3 — 다중 사이트)
 */

$stats = get_system_stats();
$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$history = $site_id ? site_get_history($site_id) : [];
$history = array_reverse($history ?: []);
$improvements = $site_id ? site_get_improvements($site_id) : [];
$changelog = $site_id ? site_get_changelog($site_id) : [];

$totalReleases = count($history);
$lastRelease = $totalReleases > 0 ? $history[0] : null;
$totalImprovements = count($improvements);
$unreleasedImps = count(array_filter($improvements, fn($i) => empty($i['released_in'])));

// 사이트별 요약 정보
$site_summaries = [];
foreach ($all_sites as $sid => $s) {
    $sv = site_get_current_version($sid);
    $sh = site_get_history($sid);
    $site_summaries[] = [
        'id'          => $sid,
        'name'        => $s['name'] ?? $sid,
        'domain'      => $s['domain'] ?? '',
        'environment' => $s['environment'] ?? 'unknown',
        'version'     => $sv['raw'],
        'releases'    => count($sh),
        'is_current'  => $sid === $site_id,
    ];
}

$TYPE_ICONS = ['major' => '🔴', 'minor' => '🟡', 'patch' => '🟢'];
$TYPE_LABELS = ['major' => 'MAJOR', 'minor' => 'MINOR', 'patch' => 'PATCH'];
$ENV_ICONS = ['production' => '🚀', 'development' => '🧪', 'staging' => '📋'];
$ENV_LABELS = ['production' => '운영', 'development' => '개발', 'staging' => '스테이징'];

// 다음 버전 미리보기
$nextPatch = bump_version($version, 'patch');
$nextMinor = bump_version($version, 'minor');
$nextMajor = bump_version($version, 'major');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>대시보드 — <?= SYSTEM_NAME ?></title>
<link rel="stylesheet" href="/vermanager/assets/css/style.css">
</head>
<body>
<div class="vm-layout">

<!-- 사이드바 -->
<aside class="vm-sidebar">
  <div class="vm-brand">
    <div class="vm-brand-icon">🔢</div>
    <div class="vm-brand-text">
      <strong><?= SYSTEM_NAME ?></strong>
      <small>다중 사이트·다중 서버</small>
    </div>
  </div>

  <!-- 사이트 선택기 -->
  <div class="vm-site-selector">
    <div class="vm-site-selector-label">현재 사이트</div>
    <select class="vm-site-select" onchange="switchSite(this.value)">
      <?php foreach ($site_summaries as $sum): ?>
      <option value="<?= $sum['id'] ?>" <?= $sum['is_current'] ? 'selected' : '' ?>>
        <?= $ENV_ICONS[$sum['environment']] ?? '📌' ?> <?= htmlspecialchars($sum['name']) ?> (<?= $sum['version'] ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <nav class="vm-nav">
    <a href="?site=<?= $site_id ?>" class="vm-nav-link active">
      <span>📊</span> 대시보드
    </a>
    <a href="release?site=<?= $site_id ?>" class="vm-nav-link">
      <span>🚀</span> 새 릴리즈
    </a>
    <a href="history?site=<?= $site_id ?>" class="vm-nav-link">
      <span>📜</span> 버전 히스토리
    </a>
    <a href="improvements?site=<?= $site_id ?>" class="vm-nav-link">
      <span>🔧</span> 소스 개선 목록
    </a>
    <a href="changelog?site=<?= $site_id ?>" class="vm-nav-link">
      <span>📝</span> CHANGELOG
    </a>
    <a href="site-detail?site=<?= $site_id ?>" class="vm-nav-link">
      <span>🔍</span> 사이트 상세
    </a>
    <div class="vm-nav-divider"></div>
    <a href="sites" class="vm-nav-link">
      <span>🌐</span> 사이트 관리
    </a>
  </nav>

  <div class="vm-sidebar-footer">
    <div class="vm-current-mini">
      <span class="vm-dot live"></span>
      <?= htmlspecialchars($site['name'] ?? '선택된 사이트 없음') ?>
      <small>v<?= $version['raw'] ?></small>
    </div>
  </div>
</aside>

<!-- 메인 콘텐츠 -->
<main class="vm-main">

  <!-- 상단 바 -->
  <header class="vm-topbar">
    <div>
      <h1>📊 버전 관리 대시보드</h1>
      <div class="vm-breadcrumb">
        <span><?= $ENV_ICONS[$site['environment'] ?? ''] ?? '📌' ?></span>
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span class="vm-breadcrumb-env"><?= htmlspecialchars($ENV_LABELS[$site['environment'] ?? ''] ?? $site['environment'] ?? '') ?></span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <span class="vm-pill vm-pill-<?= $site['environment'] ?? 'default' ?>"><?= htmlspecialchars($site['domain'] ?? '') ?></span>
      <a href="release?site=<?= $site_id ?>" class="vm-btn vm-btn-primary">🚀 새 릴리즈</a>
    </div>
  </header>

  <!-- 전체 통계 카드 -->
  <div class="vm-section-title">📈 전체 현황</div>
  <div class="vm-cards">
    <div class="vm-card">
      <div class="vm-card-icon">🌐</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $stats['total_sites'] ?></div>
        <div class="vm-card-label">전체 사이트</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🚀</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $stats['production_sites'] ?></div>
        <div class="vm-card-label">운영 환경</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🧪</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $stats['development_sites'] ?></div>
        <div class="vm-card-label">개발 환경</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🖥️</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $stats['total_servers'] ?></div>
        <div class="vm-card-label">서버 대수</div>
      </div>
    </div>
  </div>

  <!-- 사이트 목록 테이블 -->
  <div class="vm-section-title">🌐 사이트별 버전 현황</div>
  <div class="vm-panel">
    <div class="vm-table-wrap">
      <table class="vm-table">
        <thead>
          <tr>
            <th>사이트명</th>
            <th>도메인</th>
            <th>환경</th>
            <th>현재 버전</th>
            <th>릴리즈</th>
            <th>바로가기</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($site_summaries as $sum): ?>
          <tr class="<?= $sum['is_current'] ? 'vm-row-active' : '' ?>">
            <td>
              <strong><?= htmlspecialchars($sum['name']) ?></strong>
              <?php if ($sum['is_current']): ?><span class="vm-tag vm-tag-current">현재</span><?php endif; ?>
            </td>
            <td><code><?= htmlspecialchars($sum['domain']) ?></code></td>
            <td>
              <span class="vm-env-badge vm-env-<?= $sum['environment'] ?>">
                <?= $ENV_ICONS[$sum['environment']] ?? '' ?> <?= htmlspecialchars($ENV_LABELS[$sum['environment']] ?? $sum['environment']) ?>
              </span>
            </td>
            <td><strong>v<?= $sum['version'] ?></strong></td>
            <td><?= $sum['releases'] ?>회</td>
            <td>
              <a href="?site=<?= $sum['id'] ?>" class="vm-btn vm-btn-sm vm-btn-outline">선택</a>
              <a href="site-detail?site=<?= $sum['id'] ?>" class="vm-btn vm-btn-sm vm-btn-outline">상세</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 현재 사이트 상세 통계 -->
  <div class="vm-section-title">📍 <?= htmlspecialchars($site['name'] ?? '선택된 사이트') ?> 상세</div>
  <div class="vm-cards">
    <div class="vm-card">
      <div class="vm-card-icon">🏷️</div>
      <div class="vm-card-body">
        <div class="vm-card-value">v<?= $version['raw'] ?></div>
        <div class="vm-card-label">현재 버전</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">📦</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $totalReleases ?></div>
        <div class="vm-card-label">총 릴리즈</div>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🔧</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $unreleasedImps ?></div>
        <div class="vm-card-label">미발행 개선</div>
        <small style="color:var(--text3);">전체 <?= $totalImprovements ?>건</small>
      </div>
    </div>
    <div class="vm-card">
      <div class="vm-card-icon">🕐</div>
      <div class="vm-card-body">
        <div class="vm-card-value"><?= $lastRelease ? substr($lastRelease['date'], 0, 10) : '없음' ?></div>
        <div class="vm-card-label">마지막 릴리즈</div>
      </div>
    </div>
  </div>

  <!-- 빠른 릴리즈 패널 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>⚡ 빠른 릴리즈 — <?= htmlspecialchars($site['name'] ?? '') ?></h2>
      <span class="vm-panel-desc">소스 개선 내역을 반영하여 새 버전을 발행합니다</span>
    </div>
    <div class="vm-release-options">
      <div class="vm-release-opt" onclick="quickRelease('patch')">
        <div class="vm-opt-icon">🟢</div>
        <div class="vm-opt-info">
          <strong>PATCH</strong> — 버그 수정, 작은 변경
          <code>v<?= $version['raw'] ?> → v<?= $nextPatch['raw'] ?></code>
        </div>
        <button class="vm-btn vm-btn-sm vm-btn-green">PATCH</button>
      </div>
      <div class="vm-release-opt" onclick="quickRelease('minor')">
        <div class="vm-opt-icon">🟡</div>
        <div class="vm-opt-info">
          <strong>MINOR</strong> — 새 기능 추가 (하위호환)
          <code>v<?= $version['raw'] ?> → v<?= $nextMinor['raw'] ?></code>
        </div>
        <button class="vm-btn vm-btn-sm vm-btn-yellow">MINOR</button>
      </div>
      <div class="vm-release-opt" onclick="quickRelease('major')">
        <div class="vm-opt-icon">🔴</div>
        <div class="vm-opt-info">
          <strong>MAJOR</strong> — 대규모 변경 (하위호환 없음)
          <code>v<?= $version['raw'] ?> → v<?= $nextMajor['raw'] ?></code>
        </div>
        <button class="vm-btn vm-btn-sm vm-btn-red">MAJOR</button>
      </div>
    </div>
  </div>

  <!-- 최근 릴리즈 / 소스 개선 -->
  <div class="vm-grid-2">
    <!-- 최근 릴리즈 -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>📜 최근 릴리즈</h2>
        <a href="history?site=<?= $site_id ?>" class="vm-link">전체보기 →</a>
      </div>
      <?php if (empty($history)): ?>
        <div class="vm-empty">아직 릴리즈 기록이 없습니다</div>
      <?php else: ?>
      <div class="vm-list">
        <?php foreach (array_slice($history, 0, 5) as $h):
          $icon = $TYPE_ICONS[$h['type']] ?? '⚪';
          $label = $TYPE_LABELS[$h['type']] ?? $h['type'];
        ?>
        <div class="vm-list-row">
          <span class="vm-list-icon"><?= $icon ?></span>
          <div class="vm-list-content">
            <strong>v<?= $h['to'] ?></strong>
            <small>← v<?= $h['from'] ?> · <?= $label ?></small>
          </div>
          <span class="vm-list-meta"><?= substr($h['date'], 0, 10) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- 소스 개선 -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>🔧 소스 개선 내역</h2>
        <a href="improvements?site=<?= $site_id ?>" class="vm-link">전체보기 →</a>
      </div>
      <?php if (empty($improvements)): ?>
        <div class="vm-empty">아직 개선 내역이 없습니다</div>
      <?php else: ?>
      <div class="vm-list">
        <?php foreach (array_slice(array_reverse($improvements), 0, 5) as $imp): ?>
        <div class="vm-list-row">
          <span class="vm-list-icon">🔧</span>
          <div class="vm-list-content">
            <strong><?= htmlspecialchars(mb_substr($imp['title'] ?? $imp['content'] ?? '(제목없음)', 0, 50)) ?></strong>
            <small>
              <?= htmlspecialchars($imp['category'] ?? '') ?>
              <?php if (!empty($imp['released_in'])): ?>
                <span class="vm-tag vm-tag-released">✅ v<?= $imp['released_in'] ?></span>
              <?php else: ?>
                <span class="vm-tag vm-tag-pending">⏳ 미발행</span>
              <?php endif; ?>
            </small>
          </div>
          <span class="vm-list-meta"><?= substr($imp['date'] ?? '', 0, 10) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</main>
</div>

<script>
// 사이트 전환
function switchSite(siteId) {
    window.location.href = '?site=' + siteId;
}

// 빠른 릴리즈
async function quickRelease(type) {
    if (!confirm('⚠️ "' + type.toUpperCase() + '" 버전으로 릴리즈하시겠습니까?\n\n소스 개선 내역이 CHANGELOG에 자동 반영됩니다.')) return;
    try {
        const res = await fetch('/vermanager/api/release', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({site: '<?= $site_id ?>', type: type})
        });
        const data = await res.json();
        if (data.ok) {
            alert(data.message);
            location.reload();
        } else {
            alert('❌ ' + data.error);
        }
    } catch(e) {
        alert('❌ 오류: ' + e.message);
    }
}
</script>
</body>
</html>