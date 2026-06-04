<?php
/**
 * Git/GitHub 자동화 페이지
 * 자동 커밋, 태그, 푸시, 전체 릴리즈
 * 수정: 2026-05-03 아리아 (Phase 5 — Git/GitHub 자동화)
 */

$site_id = $current_site_id;
$site = $current_site;
$version = $site_id ? site_get_current_version($site_id) : ['major'=>1,'minor'=>0,'patch'=>0,'raw'=>'1.0.0'];
$doc_root = $site['document_root'] ?? '';

$git_exists = is_dir(rtrim($doc_root, '/') . '/.git');

$ENV_ICONS = ['production' => '🚀', 'development' => '🧪', 'staging' => '📋'];
$ENV_LABELS = ['production' => '운영', 'development' => '개발', 'staging' => '스테이징'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Git/GitHub 자동화 — <?= SYSTEM_NAME ?></title>
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
    <a href="remote?site=<?= $site_id ?>" class="vm-nav-link"><span>🖥️</span> 서버 관리</a>
    <a href="git?site=<?= $site_id ?>" class="vm-nav-link active"><span>📦</span> Git 자동화</a>
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
      <h1>📦 Git/GitHub 자동화</h1>
      <div class="vm-breadcrumb">
        <span>자동 커밋·태그·푸시·릴리즈</span>
      </div>
    </div>
    <div class="vm-topbar-right">
      <?php if (!$git_exists): ?>
        <span class="vm-tag vm-tag-pending">⚠️ Git 저장소 없음</span>
      <?php endif; ?>
    </div>
  </header>

  <!-- Git 저장소 상태 -->
  <div class="vm-section-title">📋 Git 저장소 상태</div>
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>저장소 정보</h2>
      <select class="vm-site-select" style="width:220px;" onchange="switchSite(this.value)">
        <?php foreach ($site_summaries as $sum): ?>
        <option value="<?= $sum['id'] ?>" <?= $sum['is_current'] ? 'selected' : '' ?>>
          <?= $ENV_ICONS[$sum['environment']] ?? '' ?> <?= htmlspecialchars($sum['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="gitStatus">
      <div class="vm-empty">⏳ Git 상태 확인 중...</div>
    </div>
  </div>

  <!-- 자동화 액션 -->
  <div class="vm-section-title">⚡ Git 자동화 명령</div>
  <div class="vm-cards">
    <!-- 자동 커밋 -->
    <div class="vm-panel" style="grid-column:span 2;">
      <div class="vm-panel-header">
        <h2>1️⃣ 자동 커밋</h2>
        <span class="vm-panel-desc">변경된 모든 파일을 스테이징하고 커밋합니다</span>
      </div>
      <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
          <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">커밋 메시지</label>
          <input type="text" id="commitMsg" class="vm-input" style="width:100%;" value="🔧 버전 관리 시스템 자동 업데이트">
        </div>
        <button class="vm-btn vm-btn-primary" onclick="doCommit()" id="commitBtn">💾 커밋</button>
      </div>
      <div id="commitResult" class="vm-result" style="display:none; margin-top:12px;"></div>
    </div>

    <!-- 태그 생성 -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>2️⃣ 태그 생성</h2>
        <span class="vm-panel-desc">현재 커밋에 버전 태그를 답니다</span>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">버전</label>
        <input type="text" id="tagVersion" class="vm-input" style="width:100%;" value="<?= $version['raw'] ?>">
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">태그 메시지</label>
        <input type="text" id="tagNote" class="vm-input" style="width:100%;" value="버전 <?= $version['raw'] ?> 릴리즈">
      </div>
      <button class="vm-btn vm-btn-primary" onclick="doTag()" id="tagBtn" style="width:100%;">🏷️ 태그 생성</button>
      <div id="tagResult" class="vm-result" style="display:none; margin-top:12px;"></div>
    </div>

    <!-- GitHub Push -->
    <div class="vm-panel">
      <div class="vm-panel-header">
        <h2>3️⃣ GitHub Push</h2>
        <span class="vm-panel-desc">커밋과 태그를 GitHub에 푸시합니다</span>
      </div>
      <div style="margin-bottom:12px; padding:10px; background:var(--bg); border-radius:8px; font-size:12px; color:var(--text3);">
        <strong>⚠️ 주의:</strong> 브랜치와 모든 태그를 함께 푸시합니다. 푸시 전 커밋과 태그가 준비되었는지 확인하세요.
      </div>
      <button class="vm-btn vm-btn-primary" onclick="doPush()" id="pushBtn" style="width:100%;">📤 GitHub Push</button>
      <div id="pushResult" class="vm-result" style="display:none; margin-top:12px;"></div>
    </div>
  </div>

  <!-- 전체 릴리즈 (원클릭) -->
  <div class="vm-section-title">🚀 원클릭 전체 릴리즈</div>
  <div class="vm-panel" style="background:rgba(59,130,246,0.05); border-color:var(--brand);">
    <div class="vm-panel-header">
      <h2>🎯 전체 자동 릴리즈</h2>
      <span class="vm-tag vm-tag-current">커밋 → 태그 → 푸시 한번에</span>
    </div>
    <div class="vm-panel-desc" style="margin-bottom:16px;">
      변경된 파일을 자동 커밋하고, 버전 태그를 생성하고, GitHub에 푸시하는 전체 과정을 한 번에 실행합니다.
    </div>
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
      <div style="flex:1; min-width:150px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">릴리즈 버전</label>
        <input type="text" id="fullRelVersion" class="vm-input" style="width:100%;" value="<?= $version['raw'] ?>">
      </div>
      <div style="flex:2; min-width:200px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">릴리즈 노트</label>
        <input type="text" id="fullRelNote" class="vm-input" style="width:100%;" value="버전 <?= $version['raw'] ?> 릴리즈 — 아리아 자동화">
      </div>
      <div style="flex:2; min-width:200px;">
        <label style="display:block; margin-bottom:4px; font-weight:bold; font-size:13px;">커밋 메시지</label>
        <input type="text" id="fullRelCommit" class="vm-input" style="width:100%;" value="🔖 릴리즈 v<?= $version['raw'] ?>">
      </div>
    </div>
    <button class="vm-btn vm-btn-primary vm-btn-lg" onclick="doFullRelease()" id="fullRelBtn">
      🚀 전체 릴리즈 실행 (커밋 → 태그 → 푸시)
    </button>
    <div id="fullRelResult" class="vm-result" style="display:none; margin-top:16px;"></div>
  </div>

  <!-- 자동화 로그 -->
  <div class="vm-section-title">📜 Git 자동화 로그</div>
  <div class="vm-panel">
    <div class="vm-panel-header">
      <h2>자동화 작업 기록</h2>
      <span class="vm-panel-desc">최근 20건의 Git 자동화 작업 내역</span>
    </div>
    <div id="autoLog">
      <div class="vm-empty">⏳ 로딩 중...</div>
    </div>
  </div>

</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadGitStatus();
    loadAutoLog();
});

function switchSite(siteId) {
    window.location.href = 'git?site=' + siteId;
}

// ── Git 상태 로드 ──
async function loadGitStatus() {
    const container = document.getElementById('gitStatus');
    container.innerHTML = '<div class="vm-empty">⏳ 확인 중...</div>';
    try {
        const res = await fetch('/vermanager/api/git?action=status&site=<?= $site_id ?>');
        const data = await res.json();
        if (!data.ok) { container.innerHTML = '<div class="vm-empty">❌ '+data.error+'</div>'; return; }

        const g = data.git || {};
        let h = '<div class="vm-config-table">';
        h += '<div class="vm-config-row"><div class="vm-config-label">Git 저장소</div><div class="vm-config-value">'+(g.is_repo ? '<span style="color:var(--green);">✅ 활성</span>' : '<span style="color:var(--red);">❌ 없음</span>')+'</div></div>';
        if (g.is_repo) {
            h += '<div class="vm-config-row"><div class="vm-config-label">브랜치</div><div class="vm-config-value"><strong>'+g.branch+'</strong></div></div>';
            h += '<div class="vm-config-row"><div class="vm-config-label">최근 커밋</div><div class="vm-config-value"><code>'+g.last_commit+'</code></div></div>';
            h += '<div class="vm-config-row"><div class="vm-config-label">변경 파일</div><div class="vm-config-value">'+(g.has_changes ? '<span style="color:var(--yellow);">⚠️ '+g.changed_count+'개 변경</span>' : '<span style="color:var(--green);">✅ 깨끗함</span>')+'</div></div>';
            if (g.recent_tags && g.recent_tags.length > 0) {
                h += '<div class="vm-config-row"><div class="vm-config-label">최근 태그</div><div class="vm-config-value">'+g.recent_tags.join(', ')+'</div></div>';
            }
            h += '<div class="vm-config-row"><div class="vm-config-label">리모트</div><div class="vm-config-value"><code style="font-size:11px;">'+(g.remote_url||'').substring(0,60)+'...</code></div></div>';
        }
        h += '</div>';

        if (data.recent_actions && data.recent_actions.length > 0) {
            h += '<div style="margin-top:12px; font-size:12px; color:var(--text3);">최근 자동화: '+data.recent_actions.length+'건</div>';
        }

        container.innerHTML = h;
    } catch(e) {
        container.innerHTML = '<div class="vm-empty">❌ '+e.message+'</div>';
    }
}

// ── 커밋 ──
async function doCommit() {
    const msg = document.getElementById('commitMsg').value.trim();
    if (!msg) { alert('커밋 메시지를 입력해주세요.'); return; }
    if (!confirm('💾 커밋하시겠습니까?\n\n메시지: '+msg)) return;

    const btn = document.getElementById('commitBtn'); btn.disabled = true; btn.textContent = '⏳...';
    try {
        const res = await fetch('/vermanager/api/git', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'commit', site:'<?= $site_id ?>', message:msg}) });
        const d = await res.json();
        const rd = document.getElementById('commitResult'); rd.style.display = 'block';
        if (d.ok) { rd.className = 'vm-result vm-result-success'; rd.innerHTML = '<h3>'+d.message+'</h3>'; loadGitStatus(); loadAutoLog(); }
        else { rd.className = 'vm-result vm-result-error'; rd.innerHTML = '<h3>❌ '+d.message+'</h3>'; }
    } catch(e) { document.getElementById('commitResult').style.display='block'; document.getElementById('commitResult').className='vm-result vm-result-error'; document.getElementById('commitResult').innerHTML='<h3>❌</h3><p>'+e.message+'</p>'; }
    finally { btn.disabled = false; btn.textContent = '💾 커밋'; }
}

// ── 태그 ──
async function doTag() {
    const ver = document.getElementById('tagVersion').value.trim();
    const note = document.getElementById('tagNote').value.trim();
    if (!ver) { alert('버전을 입력해주세요.'); return; }
    if (!confirm('🏷️ v'+ver+' 태그를 생성하시겠습니까?')) return;

    const btn = document.getElementById('tagBtn'); btn.disabled = true; btn.textContent = '⏳...';
    try {
        const res = await fetch('/vermanager/api/git', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'tag', site:'<?= $site_id ?>', version:ver, note:note}) });
        const d = await res.json();
        const rd = document.getElementById('tagResult'); rd.style.display = 'block';
        if (d.ok) { rd.className = 'vm-result vm-result-success'; rd.innerHTML = '<h3>'+d.message+'</h3>'; loadGitStatus(); loadAutoLog(); }
        else { rd.className = 'vm-result vm-result-error'; rd.innerHTML = '<h3>❌ '+d.message+'</h3><p>'+(d.error||'')+'</p>'; }
    } catch(e) { document.getElementById('tagResult').style.display='block'; document.getElementById('tagResult').className='vm-result vm-result-error'; document.getElementById('tagResult').innerHTML='<h3>❌</h3><p>'+e.message+'</p>'; }
    finally { btn.disabled = false; btn.textContent = '🏷️ 태그 생성'; }
}

// ── 푸시 ──
async function doPush() {
    if (!confirm('📤 GitHub에 푸시하시겠습니까?\n\n브랜치와 모든 태그가 함께 푸시됩니다.')) return;
    const btn = document.getElementById('pushBtn'); btn.disabled = true; btn.textContent = '⏳...';
    try {
        const res = await fetch('/vermanager/api/git', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'push', site:'<?= $site_id ?>'}) });
        const d = await res.json();
        const rd = document.getElementById('pushResult'); rd.style.display = 'block';
        if (d.ok) { rd.className = 'vm-result vm-result-success'; rd.innerHTML = '<h3>'+d.message+'</h3>'; loadGitStatus(); loadAutoLog(); }
        else { rd.className = 'vm-result vm-result-error'; rd.innerHTML = '<h3>❌ '+d.message+'</h3>'; }
    } catch(e) { document.getElementById('pushResult').style.display='block'; document.getElementById('pushResult').className='vm-result vm-result-error'; document.getElementById('pushResult').innerHTML='<h3>❌</h3><p>'+e.message+'</p>'; }
    finally { btn.disabled = false; btn.textContent = '📤 GitHub Push'; }
}

// ── 전체 릴리즈 ──
async function doFullRelease() {
    const ver = document.getElementById('fullRelVersion').value.trim();
    const note = document.getElementById('fullRelNote').value.trim();
    const commit = document.getElementById('fullRelCommit').value.trim();
    if (!ver) { alert('버전을 입력해주세요.'); return; }

    if (!confirm('🚀 v'+ver+' 전체 릴리즈를 실행하시겠습니까?\n\n1. 자동 커밋\n2. 태그 생성\n3. GitHub 푸시\n\n이 과정이 순차적으로 실행됩니다.')) return;

    const btn = document.getElementById('fullRelBtn'); btn.disabled = true; btn.textContent = '⏳ 릴리즈 진행 중...';

    try {
        const res = await fetch('/vermanager/api/git', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'full-release', site:'<?= $site_id ?>', version:ver, note:note, commit_message:commit}) });
        const d = await res.json();
        const rd = document.getElementById('fullRelResult'); rd.style.display = 'block';

        let stepHtml = (d.steps||[]).map(s => {
            let icon = s.status==='success' ? '✅' : s.status==='warning' ? '⚠️' : s.status==='skip' ? '⏭️' : '❌';
            return '<div style="padding:6px 0;">'+icon+' <strong>'+s.name+'</strong>: '+s.detail+'</div>';
        }).join('');

        if (d.ok) {
            rd.className = 'vm-result vm-result-success';
            rd.innerHTML = '<h3>✅ '+d.message+'</h3>' + stepHtml;
        } else {
            rd.className = 'vm-result vm-result-error';
            rd.innerHTML = '<h3>⚠️ '+d.message+'</h3>' + stepHtml;
        }

        loadGitStatus();
        loadAutoLog();
    } catch(e) {
        const rd = document.getElementById('fullRelResult'); rd.style.display = 'block';
        rd.className = 'vm-result vm-result-error';
        rd.innerHTML = '<h3>❌ 오류</h3><p>'+e.message+'</p>';
    } finally {
        btn.disabled = false; btn.textContent = '🚀 전체 릴리즈 실행 (커밋 → 태그 → 푸시)';
    }
}

// ── 자동화 로그 ──
async function loadAutoLog() {
    const container = document.getElementById('autoLog');
    try {
        const res = await fetch('/vermanager/api/git?action=automation-log&site=<?= $site_id ?>');
        const d = await res.json();
        if (!d.ok) { container.innerHTML = '<div class="vm-empty">❌</div>'; return; }
        if (d.logs.length === 0) { container.innerHTML = '<div class="vm-empty">아직 자동화 기록이 없습니다</div>'; return; }

        let h = '<div class="vm-timeline">';
        d.logs.slice(0,20).forEach(log => {
            h += '<div class="vm-timeline-item">' +
                '<div class="vm-timeline-icon">'+(log.status==='success'?'✅':'⚠️')+'</div>' +
                '<div class="vm-timeline-content">' +
                    '<div class="vm-timeline-header">' +
                        '<strong>'+log.action+'</strong>' +
                        '<span class="vm-tag '+(log.status==='success'?'vm-tag-current':'vm-tag-pending')+'">'+log.status+'</span>' +
                        '<span class="vm-timeline-date">'+log.timestamp.substring(0,19)+'</span>' +
                    '</div>' +
                    (log.message ? '<div style="font-size:12px; color:var(--text3);">'+log.message+'</div>' : '') +
                '</div>' +
            '</div>';
        });
        h += '</div>';
        container.innerHTML = h;
    } catch(e) { container.innerHTML = '<div class="vm-empty">❌ '+e.message+'</div>'; }
}
</script>
</body>
</html>