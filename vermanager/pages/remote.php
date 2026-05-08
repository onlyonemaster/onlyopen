<?php
/**
 * 원격 서버 관리 페이지
 * 서버 연결 상태, 배포, 사이트 간 동기화
 * 수정: 2026-05-03 아리아 (Phase 4 — 원격 서버 연동)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];

$ENV_ICONS = ['production' => '🚀', 'development' => '🧪', 'staging' => '📋'];
$ENV_LABELS = ['production' => '운영', 'development' => '개발', 'staging' => '스테이징'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>원격 서버 관리 — <?= SYSTEM_NAME ?></title>
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
    <a href="?site=<?= $site_id ?>" class="vm-nav-link"><span>📊</span> 대시보드</a>
    <a href="release?site=<?= $site_id ?>" class="vm-nav-link"><span>🚀</span> 새 릴리즈</a>
    <a href="history?site=<?= $site_id ?>" class="vm-nav-link"><span>📜</span> 버전 히스토리</a>
    <a href="improvements?site=<?= $site_id ?>" class="vm-nav-link"><span>🔧</span> 소스 개선 목록</a>
    <a href="changelog?site=<?= $site_id ?>" class="vm-nav-link"><span>📝</span> CHANGELOG</a>
    <a href="site-detail?site=<?= $site_id ?>" class="vm-nav-link"><span>🔍</span> 사이트 상세</a>
    <div class="vm-nav-divider"></div>
    <a href="sites" class="vm-nav-link"><span>🌐</span> 사이트 관리</a>
    <a href="remote?site=<?= $site_id ?>" class="vm-nav-link active"><span>🖥️</span> 서버 관리</a>
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
      <h1>🖥️ 원격 서버 관리</h1>
      <div class="vm-breadcrumb">
        <span>서버 연결·배포·동기화 관리</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <button class="vm-btn vm-btn-primary" onclick="checkAllServers()">🔄 전체 상태 확인</button>
    </div>
  </header>

  <!-- 전체 서버 상태 카드 -->
  <div class="vm-section-title">🌐 전체 서버 현황</div>
  <div id="allServersStatus" class="vm-cards">
    <div class="vm-card" style="grid-column:1/-1;">
      <div class="vm-card-icon">⏳</div>
      <div class="vm-card-body">
        <div class="vm-card-value" style="font-size:16px;">로딩 중...</div>
        <div class="vm-card-label">서버 상태 확인 중</div>
      </div>
    </div>
  </div>

  <!-- 선택 사이트 배포 -->
  <div class="vm-section-title">📦 배포 관리 — <?= htmlspecialchars($site['name'] ?? '') ?></div>
  <div class="vm-grid-2">
    <!-- 배포 실행 -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>🚀 버전 배포</h2>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">배포 대상 사이트</label>
        <select id="deploySite" class="vm-site-select" style="width:100%;" onchange="loadDeployStatus()">
          <?php foreach ($site_summaries as $sum): ?>
          <option value="<?= $sum['id'] ?>" <?= $sum['is_current'] ? 'selected' : '' ?>>
            <?= $ENV_ICONS[$sum['environment']] ?? '' ?> <?= htmlspecialchars($sum['name']) ?> (<?= $sum['version'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">배포할 버전</label>
        <input type="text" id="deployVersion" class="vm-input" style="width:100%;" value="v<?= $version['raw'] ?>" placeholder="예: 1.0.2">
      </div>
      <button class="vm-btn vm-btn-primary vm-btn-lg" onclick="executeDeploy()" id="deployBtn">
        🚀 배포 실행
      </button>
      <div id="deployResult" class="vm-result" style="display:none; margin-top:16px;"></div>
    </div>

    <!-- 사이트 상태 진단 -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>🔍 사이트 상태 진단</h2>
        <button class="vm-btn vm-btn-sm vm-btn-outline" onclick="loadDeployStatus()">🔄 새로고침</button>
      </div>
      <div id="deployStatus">
        <div class="vm-empty">사이트를 선택하고 새로고침을 눌러주세요</div>
      </div>
    </div>
  </div>

  <!-- 사이트 간 동기화 -->
  <div class="vm-section-title">🔄 사이트 간 동기화</div>
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>📋 데이터 동기화</h2>
      <span class="vm-panel-desc">한 사이트의 버전 데이터를 다른 사이트로 복사합니다 (개발 → 운영 등)</span>
    </div>
    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
      <div style="flex:1; min-width:180px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">원본 사이트</label>
        <select id="syncFrom" class="vm-site-select" style="width:100%;">
          <?php foreach ($site_summaries as $sum): ?>
          <option value="<?= $sum['id'] ?>" <?= $sum['environment']==='development' ? 'selected' : '' ?>>
            <?= $ENV_ICONS[$sum['environment']] ?? '' ?> <?= htmlspecialchars($sum['name']) ?> (<?= $sum['version'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="font-size:24px; color:var(--text3); padding-top:18px;">→</div>
      <div style="flex:1; min-width:180px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">대상 사이트</label>
        <select id="syncTo" class="vm-site-select" style="width:100%;">
          <?php foreach ($site_summaries as $sum): ?>
          <option value="<?= $sum['id'] ?>" <?= $sum['environment']==='production' ? 'selected' : '' ?>>
            <?= $ENV_ICONS[$sum['environment']] ?? '' ?> <?= htmlspecialchars($sum['name']) ?> (<?= $sum['version'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="padding-top:18px;">
        <button class="vm-btn vm-btn-primary" onclick="executeSync()" id="syncBtn">🔄 동기화 실행</button>
      </div>
    </div>
    <div id="syncResult" class="vm-result" style="display:none; margin-top:16px;"></div>
  </div>

  <!-- 배포 로그 -->
  <div class="vm-section-title">📜 배포 로그</div>
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>최근 배포 내역</h2>
      <select id="logSite" class="vm-site-select" style="width:200px;" onchange="loadDeployLog()">
        <?php foreach ($site_summaries as $sum): ?>
        <option value="<?= $sum['id'] ?>" <?= $sum['is_current'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($sum['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="deployLog">
      <div class="vm-empty">로딩 중...</div>
    </div>
  </div>

</main>
</div>

<script>
// ── 페이지 로드 시 초기화 ──
document.addEventListener('DOMContentLoaded', function() {
    checkAllServers();
    loadDeployStatus();
    loadDeployLog();
});

// ── 전체 서버 상태 확인 ──
async function checkAllServers() {
    const container = document.getElementById('allServersStatus');
    container.innerHTML = '<div class="vm-card" style="grid-column:1/-1;"><div class="vm-card-icon">⏳</div><div class="vm-card-body"><div class="vm-card-value" style="font-size:16px;">확인 중...</div><div class="vm-card-label">서버 연결 확인 중</div></div></div>';

    try {
        const res = await fetch('/vermanager/api/remote?action=all-servers');
        const data = await res.json();
        if (!data.ok) { container.innerHTML = '<div class="vm-card" style="grid-column:1/-1;"><div class="vm-card-icon">❌</div><div class="vm-card-body"><div class="vm-card-value">오류</div><div class="vm-card-label">'+data.error+'</div></div></div>'; return; }

        let html = '';
        data.servers.forEach(s => {
            const alive = s.ping.alive;
            const envIcon = {'production':'🚀','development':'🧪','staging':'📋'}[s.environment] || '📌';
            html += '<div class="vm-card" style="border-left:3px solid '+(alive ? 'var(--green)' : 'var(--red)')+';">' +
                '<div class="vm-card-icon">' + envIcon + '</div>' +
                '<div class="vm-card-body">' +
                    '<div class="vm-card-value" style="font-size:16px;">' + s.site_name + '</div>' +
                    '<div class="vm-card-label">' + s.ip + ':' + s.port + ' | ' + s.domain +
                    (alive ? ' <span style="color:var(--green);">● 연결됨</span>' : ' <span style="color:var(--red);">● 끊김</span>') +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<div class="vm-card" style="grid-column:1/-1;"><div class="vm-card-icon">❌</div><div class="vm-card-body"><div class="vm-card-value">오류</div><div class="vm-card-label">'+e.message+'</div></div></div>';
    }
}

// ── 사이트 상태 진단 ──
async function loadDeployStatus() {
    const siteId = document.getElementById('deploySite').value;
    document.getElementById('deployVersion').value = ''; // 현재 버전은 API 응답에서 채움
    const container = document.getElementById('deployStatus');
    container.innerHTML = '<div class="vm-empty">⏳ 확인 중...</div>';

    try {
        const res = await fetch('/vermanager/api/remote?action=status&site=' + siteId);
        const data = await res.json();
        if (!data.ok) { container.innerHTML = '<div class="vm-empty">❌ '+data.error+'</div>'; return; }

        document.getElementById('deployVersion').value = data.current_version.raw;
        const conn = data.connection;
        const ping = data.ping;
        const target = data.target || {};

        let html = '<div class="vm-config-table">';
        html += '<div class="vm-config-row"><div class="vm-config-label">서버</div><div class="vm-config-value">'+conn.hostname+' ('+conn.ip+':'+conn.port+')</div></div>';
        html += '<div class="vm-config-row"><div class="vm-config-label">연결 상태</div><div class="vm-config-value">'+(ping.alive ? '<span style="color:var(--green);">🟢 정상</span> ('+ping.latency_ms+'ms)' : '<span style="color:var(--red);">🔴 끊김</span>')+'</div></div>';
        html += '<div class="vm-config-row"><div class="vm-config-label">문서 루트</div><div class="vm-config-value"><code>'+conn.document_root+'</code> '+(target.docroot_exists ? '<span style="color:var(--green);">✅</span>' : '<span style="color:var(--red);">❌ 없음</span>')+'</div></div>';
        html += '<div class="vm-config-row"><div class="vm-config-label">현재 버전</div><div class="vm-config-value"><strong>v'+data.current_version.raw+'</strong></div></div>';
        html += '<div class="vm-config-row"><div class="vm-config-label">디스크 여유</div><div class="vm-config-value">'+(target.disk_free || 'N/A')+'</div></div>';
        html += '<div class="vm-config-row"><div class="vm-config-label">도메인</div><div class="vm-config-value">'+conn.domain+'</div></div>';
        html += '</div>';

        if (data.recent_deploys && data.recent_deploys.length > 0) {
            html += '<div style="margin-top:12px; font-size:12px; color:var(--text3);">최근 배포: '+data.recent_deploys.length+'건</div>';
        }

        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<div class="vm-empty">❌ '+e.message+'</div>';
    }
}

// ── 배포 실행 ──
async function executeDeploy() {
    const siteId = document.getElementById('deploySite').value;
    const version = document.getElementById('deployVersion').value.trim();
    if (!version) { alert('배포할 버전을 입력해주세요.'); return; }

    const siteName = document.getElementById('deploySite').selectedOptions[0].text;
    if (!confirm('🚀 ['+siteName+']에 v'+version+'을 배포하시겠습니까?')) return;

    const btn = document.getElementById('deployBtn');
    btn.disabled = true; btn.textContent = '⏳ 배포 중...';

    try {
        const res = await fetch('/vermanager/api/remote', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'deploy', site: siteId, version: version})
        });
        const data = await res.json();
        const rd = document.getElementById('deployResult');
        rd.style.display = 'block';

        if (data.ok) {
            rd.className = 'vm-result vm-result-success';
            let stepHtml = data.steps.map(s =>
                '<div style="padding:6px 0;">' +
                (s.status === 'success' ? '✅' : s.status === 'warning' ? '⚠️' : '❌') +
                ' <strong>' + s.name + '</strong>: ' + s.detail +
                '</div>'
            ).join('');
            rd.innerHTML = '<h3>✅ '+data.message+'</h3>' + stepHtml;
            loadDeployStatus();
            loadDeployLog();
        } else {
            rd.className = 'vm-result vm-result-error';
            let stepHtml = (data.steps||[]).map(s =>
                '<div style="padding:6px 0;">' +
                (s.status === 'success' ? '✅' : s.status === 'warning' ? '⚠️' : '❌') +
                ' <strong>' + s.name + '</strong>: ' + s.detail +
                '</div>'
            ).join('');
            rd.innerHTML = '<h3>❌ '+data.message+'</h3>' + stepHtml;
        }
    } catch(e) {
        const rd = document.getElementById('deployResult');
        rd.style.display = 'block';
        rd.className = 'vm-result vm-result-error';
        rd.innerHTML = '<h3>❌ 오류</h3><p>'+e.message+'</p>';
    } finally {
        btn.disabled = false; btn.textContent = '🚀 배포 실행';
    }
}

// ── 사이트 간 동기화 ──
async function executeSync() {
    const fromId = document.getElementById('syncFrom').value;
    const toId = document.getElementById('syncTo').value;

    if (fromId === toId) { alert('원본과 대상이 같습니다. 다른 사이트를 선택해주세요.'); return; }

    const fromName = document.getElementById('syncFrom').selectedOptions[0].text;
    const toName = document.getElementById('syncTo').selectedOptions[0].text;

    if (!confirm('🔄 ['+fromName+'] → ['+toName+'] 데이터를 동기화하시겠습니까?\n\npackage.json, version_history.json, changelog.json, improvements.json 파일이 복사됩니다.')) return;

    const btn = document.getElementById('syncBtn');
    btn.disabled = true; btn.textContent = '⏳ 동기화 중...';

    try {
        const res = await fetch('/vermanager/api/remote', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'sync', from: fromId, to: toId})
        });
        const data = await res.json();
        const rd = document.getElementById('syncResult');
        rd.style.display = 'block';

        if (data.ok) {
            rd.className = 'vm-result vm-result-success';
            rd.innerHTML =
                '<h3>✅ '+data.message+'</h3>' +
                '<p>버전: v'+data.from_version+' → v'+data.to_version_after+' (원래 v'+data.to_version_before+')</p>' +
                '<p>동기화된 파일: ' + (data.synced_files||[]).join(', ') + '</p>';
        } else {
            rd.className = 'vm-result vm-result-error';
            rd.innerHTML = '<h3>❌ 실패</h3><p>'+data.error+'</p>';
        }
    } catch(e) {
        const rd = document.getElementById('syncResult');
        rd.style.display = 'block';
        rd.className = 'vm-result vm-result-error';
        rd.innerHTML = '<h3>❌ 오류</h3><p>'+e.message+'</p>';
    } finally {
        btn.disabled = false; btn.textContent = '🔄 동기화 실행';
    }
}

// ── 배포 로드 ──
async function loadDeployLog() {
    const siteId = document.getElementById('logSite').value;
    const container = document.getElementById('deployLog');
    container.innerHTML = '<div class="vm-empty">⏳ 로딩 중...</div>';

    try {
        const res = await fetch('/vermanager/api/remote?action=deploy-log&site=' + siteId);
        const data = await res.json();
        if (!data.ok) { container.innerHTML = '<div class="vm-empty">'+data.error+'</div>'; return; }

        if (data.logs.length === 0) {
            container.innerHTML = '<div class="vm-empty">아직 배포 기록이 없습니다</div>';
            return;
        }

        let html = '<div class="vm-timeline">';
        data.logs.slice(0, 20).forEach(log => {
            const icon = log.status === 'success' ? '✅' : '❌';
            html += '<div class="vm-timeline-item">' +
                '<div class="vm-timeline-icon">' + icon + '</div>' +
                '<div class="vm-timeline-content">' +
                    '<div class="vm-timeline-header">' +
                        '<strong>v' + log.version + '</strong>' +
                        '<span class="vm-tag '+(log.status==='success'?'vm-tag-current':'vm-tag-pending')+'">'+log.status+'</span>' +
                        '<span class="vm-timeline-date">'+log.timestamp.substring(0,16)+'</span>' +
                    '</div>' +
                    '<div style="font-size:12px; color:var(--text3);">'+log.message+'</div>' +
                    '<div style="font-size:11px; color:var(--text3); margin-top:2px;">by '+log.deploy_by+'</div>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<div class="vm-empty">❌ '+e.message+'</div>';
    }
}
</script>
</body>
</html>