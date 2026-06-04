<?php
/**
 * 소스 개선 목록 페이지 (다중 사이트 지원)
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$improvements = $site_id ? site_get_improvements($site_id) : [];
$improvements = array_reverse($improvements ?: []);

$unreleased = array_values(array_filter($improvements, fn($i) => empty($i['released_in'])));
$released = array_values(array_filter($improvements, fn($i) => !empty($i['released_in'])));

$filter = $_GET['filter'] ?? 'all';
if ($filter === 'unreleased') $improvements = $unreleased;
elseif ($filter === 'released') $improvements = $released;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>소스 개선 목록 — <?= SYSTEM_NAME ?></title>
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
    <a href="improvements?site=<?= $site_id ?>" class="vm-nav-link active"><span>🔧</span> 소스 개선 목록</a>
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
      <h1>🔧 소스 개선 목록</h1>
      <div class="vm-breadcrumb">
        <span><?= htmlspecialchars($site['name'] ?? '') ?></span>
        <span class="vm-breadcrumb-sep">›</span>
        <span>미발행 <?= count($unreleased) ?>건 / 발행됨 <?= count($released) ?>건</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <button class="vm-btn vm-btn-primary" onclick="showAddForm()">➕ 새 개선 항목</button>
    </div>
  </header>

  <!-- 필터 -->
  <div class="vm-panel">
    <div class="vm-filter-bar">
      <span>필터:</span>
      <a href="improvements?site=<?= $site_id ?>" class="vm-filter-tag <?= $filter === 'all' ? 'active' : '' ?>">전체 (<?= count($improvements) ?>)</a>
      <a href="improvements?site=<?= $site_id ?>&filter=unreleased" class="vm-filter-tag <?= $filter === 'unreleased' ? 'active' : '' ?>">⏳ 미발행 (<?= count($unreleased) ?>)</a>
      <a href="improvements?site=<?= $site_id ?>&filter=released" class="vm-filter-tag <?= $filter === 'released' ? 'active' : '' ?>">✅ 발행됨 (<?= count($released) ?>)</a>
    </div>
    <div style="margin-top:12px;">
      <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="syncFromAdmin()">🔄 관리자 페이지에서 동기화</button>
      <span style="color:var(--text3); font-size:12px; margin-left:8px;"><?= SRC_IMPROVEMENTS_URL ?></span>
    </div>
  </div>

  <!-- 추가 폼 (숨김) -->
  <div class="vm-panel" id="addFormPanel" style="display:none;">
    <div class="vm-panel-header">
      <h2>➕ 새 소스 개선 항목 추가</h2>
      <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="hideAddForm()">✕ 닫기</button>
    </div>
    <div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold;">제목</label>
        <input type="text" id="newTitle" class="vm-input" placeholder="개선 내용을 간단히 설명해주세요" style="width:100%;">
      </div>
      <div style="display:flex; gap:12px; margin-bottom:12px;">
        <div style="flex:1;">
          <label style="display:block; margin-bottom:4px; font-weight:bold;">분류</label>
          <select id="newCategory" class="vm-site-select" style="width:100%;">
            <option value="기능">기능</option>
            <option value="UI">UI</option>
            <option value="보안">보안</option>
            <option value="성능">성능</option>
            <option value="버그수정">버그 수정</option>
            <option value="기타">기타</option>
          </select>
        </div>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold;">상세 설명</label>
        <textarea id="newContent" class="vm-textarea" placeholder="변경 사항을 상세히 설명해주세요" style="width:100%; height:80px;"></textarea>
      </div>
      <button class="vm-btn vm-btn-primary" onclick="addImprovement()">저장</button>
    </div>
  </div>

  <!-- 개선 목록 -->
  <div class="vm-panel">
    <?php if (empty($improvements)): ?>
      <div class="vm-empty">아직 소스 개선 내역이 없습니다</div>
    <?php else: ?>
    <div class="vm-timeline">
      <?php foreach ($improvements as $imp):
        $is_released = !empty($imp['released_in']);
      ?>
      <div class="vm-timeline-item">
        <div class="vm-timeline-icon"><?= $is_released ? '✅' : '⏳' ?></div>
        <div class="vm-timeline-content">
          <div class="vm-timeline-header">
            <strong><?= htmlspecialchars(mb_substr($imp['title'] ?? $imp['content'] ?? '(제목없음)', 0, 100)) ?></strong>
            <span class="vm-tag"><?= htmlspecialchars($imp['category'] ?? '기타') ?></span>
            <?php if ($is_released): ?>
              <span class="vm-tag vm-tag-released">v<?= $imp['released_in'] ?></span>
            <?php else: ?>
              <span class="vm-tag vm-tag-pending">미발행</span>
            <?php endif; ?>
            <span style="color:var(--text3); font-size:12px;"><?= substr($imp['date'] ?? '', 0, 10) ?></span>
          </div>
          <?php if (!empty($imp['content'])): ?>
          <div style="color:var(--text2); margin-top:4px; font-size:14px;">
            <?= htmlspecialchars($imp['content']) ?>
          </div>
          <?php endif; ?>
          <?php if (!$is_released): ?>
          <div style="margin-top:8px;">
            <button class="vm-btn vm-btn-sm vm-btn-red" onclick="deleteImp('<?= $imp['id'] ?>')">🗑 삭제</button>
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
    window.location.href = 'improvements?site=' + siteId;
}

function showAddForm() {
    document.getElementById('addFormPanel').style.display = 'block';
    document.getElementById('newTitle').focus();
}

function hideAddForm() {
    document.getElementById('addFormPanel').style.display = 'none';
}

async function addImprovement() {
    const title = document.getElementById('newTitle').value.trim();
    const category = document.getElementById('newCategory').value;
    const content = document.getElementById('newContent').value.trim();

    if (!title) { alert('제목을 입력해주세요.'); return; }

    try {
        const res = await fetch('/vermanager/api/improvements', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({site: '<?= $site_id ?>', title, category, content})
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

async function deleteImp(id) {
    if (!confirm('이 개선 항목을 삭제하시겠습니까?')) return;
    try {
        const res = await fetch('/vermanager/api/improvements?site=<?= $site_id ?>&id=' + id, {
            method: 'DELETE'
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

function syncFromAdmin() {
    window.open('<?= SRC_IMPROVEMENTS_URL ?>', '_blank');
}
</script>
</body>
</html>