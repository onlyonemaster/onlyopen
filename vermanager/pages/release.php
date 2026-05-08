<?php
/**
 * 새 버전 릴리즈 페이지 (다중 사이트 지원)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$improvements = $site_id ? site_get_improvements($site_id) : [];
$unreleased = array_values(array_filter($improvements ?: [], fn($i) => empty($i['released_in'])));

$nextPatch = bump_version($version, 'patch');
$nextMinor = bump_version($version, 'minor');
$nextMajor = bump_version($version, 'major');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>새 릴리즈 — <?= SYSTEM_NAME ?></title>
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
    <div class="vm-site-selector-label">릴리즈 대상</div>
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
    <a href="release?site=<?= $site_id ?>" class="vm-nav-link active"><span>🚀</span> 새 릴리즈</a>
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
      <h1>🚀 새 버전 릴리즈</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span>현재: v<?= $version['raw'] ?></span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <span class="vm-pill"><?= htmlspecialchars($site['domain'] ?? '') ?></span>
    </div>
  </header>

  <!-- 1단계: 릴리즈 유형 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>1단계: 릴리즈 유형 선택</h2>
      <span class="vm-panel-desc">
        <?= htmlspecialchars($site['name'] ?? '') ?> (<?= htmlspecialchars($site['environment'] ?? '') ?>) — 현재 v<?= $version['raw'] ?>
      </span>
    </div>
    <div class="vm-release-options" id="releaseTypeSelector">
      <label class="vm-release-opt vm-radio-opt" id="optPatch">
        <input type="radio" name="releaseType" value="patch" checked>
        <div class="vm-opt-icon">🟢</div>
        <div class="vm-opt-info">
          <strong>PATCH</strong> — 버그 수정, 작은 변경
          <code>v<?= $version['raw'] ?> → v<?= $nextPatch['raw'] ?></code>
        </div>
      </label>
      <label class="vm-release-opt vm-radio-opt" id="optMinor">
        <input type="radio" name="releaseType" value="minor">
        <div class="vm-opt-icon">🟡</div>
        <div class="vm-opt-info">
          <strong>MINOR</strong> — 새 기능 추가 (하위호환)
          <code>v<?= $version['raw'] ?> → v<?= $nextMinor['raw'] ?></code>
        </div>
      </label>
      <label class="vm-release-opt vm-radio-opt" id="optMajor">
        <input type="radio" name="releaseType" value="major">
        <div class="vm-opt-icon">🔴</div>
        <div class="vm-opt-info">
          <strong>MAJOR</strong> — 대규모 변경 (하위호환 없음)
          <code>v<?= $version['raw'] ?> → v<?= $nextMajor['raw'] ?></code>
        </div>
      </label>
    </div>
  </div>

  <!-- 2단계: 소스 개선 연결 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>2단계: 소스 개선 내역 연결</h2>
      <span class="vm-panel-desc">이번 릴리즈에 포함할 소스 개선 항목을 선택하세요</span>
    </div>
    <?php if (empty($unreleased)): ?>
      <div class="vm-empty">🆗 연결할 미발행 개선 항목이 없습니다</div>
      <div class="vm-empty-hint">
        <a href="<?= SRC_IMPROVEMENTS_URL ?>" target="_blank">관리자 페이지</a>에서 소스 개선 항목을 등록하거나,
        <a href="improvements?site=<?= $site_id ?>">개선 목록</a>에서 직접 추가할 수 있습니다.
      </div>
    <?php else: ?>
      <div class="vm-improvements-checklist">
        <label class="vm-check-all">
          <input type="checkbox" id="checkAll" onchange="toggleAll(this)" checked>
          <strong>전체 선택 (<?= count($unreleased) ?>건)</strong>
        </label>
        <?php foreach ($unreleased as $imp): ?>
        <label class="vm-imp-check">
          <input type="checkbox" class="imp-checkbox" value="<?= $imp['id'] ?>" checked>
          <div class="vm-imp-check-body">
            <strong><?= htmlspecialchars(mb_substr($imp['title'] ?? $imp['content'] ?? '(제목없음)', 0, 80)) ?></strong>
            <div class="vm-imp-meta">
              <span class="vm-tag"><?= htmlspecialchars($imp['category'] ?? '기타') ?></span>
              <span><?= substr($imp['date'] ?? '', 0, 10) ?></span>
            </div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- 3단계: 릴리즈 노트 -->
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>3단계: 릴리즈 노트 (선택)</h2>
    </div>
    <textarea id="releaseNote" class="vm-textarea" placeholder="이번 릴리즈에 대한 설명을 입력하세요 (선택사항)&#10;예: 햄버거 메뉴 아이콘 추가, 채팅 속도 개선 등"></textarea>
  </div>

  <!-- 4단계: 실행 -->
  <div class="vm-panel">
    <button class="vm-btn vm-btn-primary vm-btn-lg" onclick="doRelease()">
      🚀 버전 릴리즈 실행
    </button>
    <span style="margin-left:12px; color:var(--text3); font-size:13px;">
      대상: <?= htmlspecialchars($site['name'] ?? '') ?> · <?= htmlspecialchars($site['domain'] ?? '') ?>
    </span>
  </div>

  <!-- 결과 -->
  <div id="releaseResult" class="vm-result" style="display:none;"></div>
</main>
</div>

<script>
function switchSite(siteId) {
    window.location.href = 'release?site=' + siteId;
}

function toggleAll(el) {
    document.querySelectorAll('.imp-checkbox').forEach(cb => cb.checked = el.checked);
}

function getCheckedImps() {
    return Array.from(document.querySelectorAll('.imp-checkbox:checked')).map(cb => cb.value);
}

async function doRelease() {
    const type = document.querySelector('input[name="releaseType"]:checked').value;
    const note = document.getElementById('releaseNote').value.trim();
    const linked = getCheckedImps();

    const msg = '🚀 "' + type.toUpperCase() + '" 버전으로 릴리즈하시겠습니까?\n\n' +
        '대상: <?= htmlspecialchars($site['name'] ?? '') ?> (<?= htmlspecialchars($site['domain'] ?? '') ?>)\n' +
        '연결된 소스 개선: ' + linked.length + '건\n' +
        '릴리즈 노트: ' + (note || '(없음)');

    if (!confirm(msg)) return;

    const btn = document.querySelector('.vm-btn-lg');
    btn.disabled = true;
    btn.textContent = '⏳ 처리 중...';

    try {
        const res = await fetch('/vermanager/api/release', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                site: '<?= $site_id ?>',
                type: type,
                note: note,
                linked_improvements: linked
            })
        });
        const data = await res.json();
        const resultDiv = document.getElementById('releaseResult');
        resultDiv.style.display = 'block';

        if (data.ok) {
            resultDiv.className = 'vm-result vm-result-success';
            resultDiv.innerHTML =
                '<h3>✅ ' + data.message + '</h3>' +
                '<p>연결된 소스 개선: ' + data.improvements_linked + '건</p>' +
                '<p>UI 표시: <code>' + (data.display_string || '') + '</code></p>' +
                '<a href="?site=<?= $site_id ?>" class="vm-btn vm-btn-primary">대시보드로 돌아가기</a>';
            setTimeout(function() { location.href = '?site=<?= $site_id ?>'; }, 2000);
        } else {
            resultDiv.className = 'vm-result vm-result-error';
            resultDiv.innerHTML = '<h3>❌ 실패</h3><p>' + data.error + '</p>';
        }
    } catch(e) {
        var resultDiv = document.getElementById('releaseResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'vm-result vm-result-error';
        resultDiv.innerHTML = '<h3>❌ 오류</h3><p>' + e.message + '</p>';
    } finally {
        btn.disabled = false;
        btn.textContent = '🚀 버전 릴리즈 실행';
    }
}
</script>
</body>
</html>