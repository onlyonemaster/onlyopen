<?php
/**
 * 사이트 관리 페이지
 * 사이트 등록, 수정, 삭제를 한곳에서 관리
 * 수정: 2026-05-03 아리아 (Phase 3)
 */

$all_sites_data = get_all_sites();
$active_sites = get_active_sites();
$stats = get_system_stats();

$ENV_ICONS = ['production' => '🚀', 'development' => '🧪', 'staging' => '📋'];
$ENV_LABELS = ['production' => '운영', 'development' => '개발', 'staging' => '스테이징'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>사이트 관리 — <?= SYSTEM_NAME ?></title>
<link rel="stylesheet" href="/vermanager/assets/css/style.css">
</head>
<body>
<div class="vm-layout">

<aside class="vm-sidebar">
  <div class="vm-brand">
    <div class="vm-brand-icon">🔢</div>
    <div class="vm-brand-text"><strong><?= SYSTEM_NAME ?></strong><small>다중 사이트·다중 서버</small></div>
  </div>
  <nav class="vm-nav">
    <a href="?site=<?= $current_site_id ?>" class="vm-nav-link"><span>📊</span> 대시보드</a>
    <a href="release?site=<?= $current_site_id ?>" class="vm-nav-link"><span>🚀</span> 새 릴리즈</a>
    <a href="history?site=<?= $current_site_id ?>" class="vm-nav-link"><span>📜</span> 버전 히스토리</a>
    <a href="improvements?site=<?= $current_site_id ?>" class="vm-nav-link"><span>🔧</span> 소스 개선 목록</a>
    <a href="changelog?site=<?= $current_site_id ?>" class="vm-nav-link"><span>📝</span> CHANGELOG</a>
    <a href="site-detail?site=<?= $current_site_id ?>" class="vm-nav-link"><span>🔍</span> 사이트 상세</a>
    <div class="vm-nav-divider"></div>
    <a href="sites" class="vm-nav-link active"><span>🌐</span> 사이트 관리</a>
  </nav>
  <div class="vm-sidebar-footer">
    <div class="vm-current-mini">
      <span class="vm-dot live"></span> <?= SYSTEM_NAME ?>
    </div>
  </div>
</aside>

<main class="vm-main">
  <header class="vm-topbar">
    <div>
      <h1>🌐 사이트 관리</h1>
      <div class="vm-breadcrumb">
        <span>전체 <?= count($active_sites) ?>개 활성 / <?= count($all_sites_data) ?>개 등록</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <button class="vm-btn vm-btn-primary" onclick="showAddSiteForm()">➕ 새 사이트 등록</button>
    </div>
  </header>

  <!-- 통계 -->
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

  <!-- 등록 폼 (숨김) -->
  <div class="vm-panel" id="addSiteForm" style="display:none;">
    <div class="vm-panel-header">
      <h2>➕ 새 사이트 등록</h2>
      <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="hideAddSiteForm()">✕ 닫기</button>
    </div>
    <div class="vm-form-grid">
      <div class="vm-form-group">
        <label>사이트 ID <span style="color:var(--red);">*</span></label>
        <input type="text" id="newSiteId" class="vm-input" placeholder="예: kiam-prod" style="width:100%;">
        <small style="color:var(--text3);">영문, 숫자, 하이픈(-)만 사용. 변경 불가</small>
      </div>
      <div class="vm-form-group">
        <label>사이트 이름 <span style="color:var(--red);">*</span></label>
        <input type="text" id="newSiteName" class="vm-input" placeholder="예: KIAM 운영환경" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>도메인</label>
        <input type="text" id="newSiteDomain" class="vm-input" placeholder="예: kiam.kr" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>별칭 (콤마 구분)</label>
        <input type="text" id="newSiteAliases" class="vm-input" placeholder="예: www.example.com, api.example.com" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>환경</label>
        <select id="newSiteEnv" class="vm-site-select" style="width:100%;">
          <option value="production">🚀 운영 (production)</option>
          <option value="development" selected>🧪 개발 (development)</option>
          <option value="staging">📋 스테이징 (staging)</option>
        </select>
      </div>
      <div class="vm-form-group">
        <label>문서 루트</label>
        <input type="text" id="newSiteDocRoot" class="vm-input" placeholder="예: /home/kiam" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>앱 이름</label>
        <input type="text" id="newSiteAppName" class="vm-input" placeholder="예: Onlyone OneChat" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>서버 호스트명</label>
        <input type="text" id="newSiteServer" class="vm-input" value="main-server" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>서버 IP</label>
        <input type="text" id="newSiteIp" class="vm-input" value="127.0.0.1" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>부모 사이트 (선택)</label>
        <select id="newSiteParent" class="vm-site-select" style="width:100%;">
          <option value="">없음 (최상위)</option>
          <?php foreach ($active_sites as $sid => $s): ?>
          <option value="<?= $sid ?>"><?= htmlspecialchars($s['name'] ?? $sid) ?></option>
          <?php endforeach; ?>
        </select>
        <small style="color:var(--text3);">예: kiam-dev의 부모는 kiam-prod</small>
      </div>
      <div class="vm-form-group" style="grid-column:1/-1;">
        <label>설명</label>
        <textarea id="newSiteDesc" class="vm-textarea" placeholder="사이트에 대한 설명을 입력하세요" style="width:100%; height:60px;"></textarea>
      </div>
    </div>
    <button class="vm-btn vm-btn-primary vm-btn-lg" onclick="addSite()" style="margin-top:16px;">사이트 등록</button>
    <div id="addSiteResult" class="vm-result" style="display:none; margin-top:12px;"></div>
  </div>

  <!-- 사이트 목록 테이블 -->
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
            <th>서버</th>
            <th>상태</th>
            <th>관리</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_sites_data as $sid => $s):
            $sv = site_get_current_version($sid);
            $sh = site_get_history($sid);
            $is_active = ($s['status'] ?? 'active') === 'active';
          ?>
          <tr class="<?= !$is_active ? 'vm-row-inactive' : '' ?>">
            <td>
              <strong><?= htmlspecialchars($s['name'] ?? $sid) ?></strong>
              <?php if ($s['parent_site_id']): ?>
                <br><small style="color:var(--text3);">← <?= htmlspecialchars(get_site($s['parent_site_id'])['name'] ?? $s['parent_site_id']) ?></small>
              <?php endif; ?>
            </td>
            <td><code><?= htmlspecialchars($s['domain'] ?? '') ?></code></td>
            <td>
              <span class="vm-env-badge vm-env-<?= $s['environment'] ?? 'unknown' ?>">
                <?= $ENV_ICONS[$s['environment']] ?? '📌' ?> <?= htmlspecialchars($ENV_LABELS[$s['environment']] ?? $s['environment'] ?? '') ?>
              </span>
            </td>
            <td><strong>v<?= $sv['raw'] ?></strong></td>
            <td><?= count($sh) ?>회</td>
            <td><?= htmlspecialchars($s['server_hostname'] ?? '') ?></td>
            <td>
              <?php if ($is_active): ?>
                <span class="vm-tag vm-tag-current">활성</span>
              <?php else: ?>
                <span class="vm-tag vm-tag-pending">비활성</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="site-detail?site=<?= $sid ?>" class="vm-btn vm-btn-sm vm-btn-outline">상세</a>
              <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="editSite('<?= $sid ?>')">✏️</button>
              <?php if ($is_active): ?>
              <button class="vm-btn vm-btn-sm vm-btn-red" onclick="deactivateSite('<?= $sid ?>')">비활성화</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 편집 폼 (숨김) -->
  <div class="vm-panel" id="editSiteForm" style="display:none;">
    <div class="vm-panel-header">
      <h2>✏️ 사이트 정보 수정 — <span id="editSiteName"></span></h2>
      <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="hideEditSiteForm()">✕ 닫기</button>
    </div>
    <input type="hidden" id="editSiteId">
    <div class="vm-form-grid">
      <div class="vm-form-group">
        <label>사이트 이름</label>
        <input type="text" id="editName" class="vm-input" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>도메인</label>
        <input type="text" id="editDomain" class="vm-input" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>환경</label>
        <select id="editEnv" class="vm-site-select" style="width:100%;">
          <option value="production">🚀 운영</option>
          <option value="development">🧪 개발</option>
          <option value="staging">📋 스테이징</option>
        </select>
      </div>
      <div class="vm-form-group">
        <label>문서 루트</label>
        <input type="text" id="editDocRoot" class="vm-input" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>앱 이름</label>
        <input type="text" id="editAppName" class="vm-input" style="width:100%;">
      </div>
      <div class="vm-form-group">
        <label>서버 호스트명</label>
        <input type="text" id="editServer" class="vm-input" style="width:100%;">
      </div>
    </div>
    <button class="vm-btn vm-btn-primary" onclick="saveSiteEdit()" style="margin-top:16px;">저장</button>
    <div id="editSiteResult" class="vm-result" style="display:none; margin-top:12px;"></div>
  </div>

</main>
</div>

<script>
// 사이트 등록 폼
function showAddSiteForm() { document.getElementById('addSiteForm').style.display = 'block'; }
function hideAddSiteForm() {
    document.getElementById('addSiteForm').style.display = 'none';
    document.getElementById('addSiteResult').style.display = 'none';
}

async function addSite() {
    const id = document.getElementById('newSiteId').value.trim();
    const name = document.getElementById('newSiteName').value.trim();
    if (!id) { alert('사이트 ID를 입력해주세요.'); return; }
    if (!name) { alert('사이트 이름을 입력해주세요.'); return; }
    if (!/^[a-z0-9-]+$/.test(id)) { alert('사이트 ID는 영문 소문자, 숫자, 하이픈(-)만 사용 가능합니다.'); return; }

    const aliasesRaw = document.getElementById('newSiteAliases').value.trim();
    const aliases = aliasesRaw ? aliasesRaw.split(',').map(a => a.trim()).filter(a => a) : [];

    const body = {
        id: id, name: name,
        domain: document.getElementById('newSiteDomain').value.trim(),
        aliases: aliases,
        environment: document.getElementById('newSiteEnv').value,
        document_root: document.getElementById('newSiteDocRoot').value.trim(),
        app_name: document.getElementById('newSiteAppName').value.trim(),
        server_hostname: document.getElementById('newSiteServer').value.trim(),
        server_ip: document.getElementById('newSiteIp').value.trim(),
        parent_site_id: document.getElementById('newSiteParent').value || null,
        description: document.getElementById('newSiteDesc').value.trim(),
    };

    try {
        const res = await fetch('/vermanager/api/sites', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        });
        const data = await res.json();
        const rd = document.getElementById('addSiteResult');
        rd.style.display = 'block';
        if (data.ok) {
            rd.className = 'vm-result vm-result-success';
            rd.innerHTML = '<h3>✅ ' + data.message + '</h3>';
            setTimeout(() => location.reload(), 1500);
        } else {
            rd.className = 'vm-result vm-result-error';
            rd.innerHTML = '<h3>❌ 실패</h3><p>' + data.error + '</p>';
        }
    } catch(e) {
        const rd = document.getElementById('addSiteResult');
        rd.style.display = 'block';
        rd.className = 'vm-result vm-result-error';
        rd.innerHTML = '<h3>❌ 오류</h3><p>' + e.message + '</p>';
    }
}

// 사이트 편집
async function editSite(siteId) {
    try {
        const res = await fetch('/vermanager/api/sites?id=' + siteId);
        const data = await res.json();
        if (!data.ok) { alert('❌ ' + data.error); return; }

        const s = data.site;
        document.getElementById('editSiteId').value = siteId;
        document.getElementById('editSiteName').textContent = s.name;
        document.getElementById('editName').value = s.name;
        document.getElementById('editDomain').value = s.domain || '';
        document.getElementById('editEnv').value = s.environment || 'development';
        document.getElementById('editDocRoot').value = s.document_root || '';
        document.getElementById('editAppName').value = s.app_name || '';
        document.getElementById('editServer').value = s.server_hostname || '';
        document.getElementById('editSiteForm').style.display = 'block';
        document.getElementById('editSiteResult').style.display = 'none';
    } catch(e) {
        alert('❌ 오류: ' + e.message);
    }
}

function hideEditSiteForm() {
    document.getElementById('editSiteForm').style.display = 'none';
}

async function saveSiteEdit() {
    const id = document.getElementById('editSiteId').value;
    const body = {
        id: id,
        name: document.getElementById('editName').value.trim(),
        domain: document.getElementById('editDomain').value.trim(),
        environment: document.getElementById('editEnv').value,
        document_root: document.getElementById('editDocRoot').value.trim(),
        app_name: document.getElementById('editAppName').value.trim(),
        server_hostname: document.getElementById('editServer').value.trim(),
    };

    try {
        const res = await fetch('/vermanager/api/sites', {
            method: 'PUT', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        });
        const data = await res.json();
        const rd = document.getElementById('editSiteResult');
        rd.style.display = 'block';
        if (data.ok) {
            rd.className = 'vm-result vm-result-success';
            rd.innerHTML = '<h3>✅ ' + data.message + '</h3>';
            setTimeout(() => location.reload(), 1500);
        } else {
            rd.className = 'vm-result vm-result-error';
            rd.innerHTML = '<h3>❌ 실패</h3><p>' + data.error + '</p>';
        }
    } catch(e) {
        const rd = document.getElementById('editSiteResult');
        rd.style.display = 'block';
        rd.className = 'vm-result vm-result-error';
        rd.innerHTML = '<h3>❌ 오류</h3><p>' + e.message + '</p>';
    }
}

// 사이트 비활성화
async function deactivateSite(siteId) {
    if (!confirm('⚠️ "' + siteId + '" 사이트를 비활성화하시겠습니까?\n\n데이터는 보존되며, 대시보드에서 숨겨집니다.')) return;
    try {
        const res = await fetch('/vermanager/api/sites?id=' + siteId, { method: 'DELETE' });
        const data = await res.json();
        if (data.ok) { alert(data.message); location.reload(); }
        else { alert('❌ ' + data.error); }
    } catch(e) { alert('❌ 오류: ' + e.message); }
}
</script>
</body>
</html>