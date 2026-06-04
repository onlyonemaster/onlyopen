/* ===========================
   kiam.kr 매뉴얼 공통 스크립트
   =========================== */

// ── 다크모드 ──
const themeBtn = document.getElementById('themeBtn');
function applyTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  if (themeBtn) themeBtn.textContent = t === 'dark' ? '☀️' : '🌙';
}
const savedTheme = localStorage.getItem('manualTheme') || 'light';
applyTheme(savedTheme);

function toggleTheme() {
  const cur = document.documentElement.getAttribute('data-theme');
  const next = cur === 'dark' ? 'light' : 'dark';
  applyTheme(next);
  localStorage.setItem('manualTheme', next);
}
if (themeBtn) themeBtn.addEventListener('click', toggleTheme);

// ── 햄버거 (모바일 사이드바) ──
const sidebar   = document.querySelector('.m-sidebar');
const hamburger = document.getElementById('hamburgerBtn');
const overlay   = document.getElementById('sidebarOverlay');

function openSidebar()  { sidebar?.classList.add('open'); overlay?.classList.add('show'); }
function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('show'); }

if (hamburger) hamburger.addEventListener('click', openSidebar);
if (overlay)   overlay.addEventListener('click', closeSidebar);

// ── 사이드바 그룹 토글 ──
document.querySelectorAll('.sb-group-title').forEach(el => {
  el.addEventListener('click', () => {
    el.closest('.sb-group').classList.toggle('open');
  });
});

// ── 현재 페이지 사이드바 활성화 ──
const curPath = location.pathname;
document.querySelectorAll('.sb-link').forEach(a => {
  if (a.getAttribute('href') === curPath || a.getAttribute('href') === curPath + 'index.html') {
    a.classList.add('active');
    const group = a.closest('.sb-group');
    if (group) group.classList.add('open');
  }
});

// ── 우측 TOC 스크롤 추적 ──
const tocLinks = document.querySelectorAll('.toc-list a');
const headings = document.querySelectorAll('.m-content h2, .m-content h3');

if (tocLinks.length && headings.length) {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        tocLinks.forEach(a => a.classList.remove('active'));
        const active = document.querySelector(`.toc-list a[href="#${entry.target.id}"]`);
        if (active) active.classList.add('active');
      }
    });
  }, { rootMargin: '-80px 0px -60% 0px' });
  headings.forEach(h => { if (h.id) observer.observe(h); });
}

/* =============================================
   ── 검색 기능 (실시간 드롭다운 + 페이지 이동) ──
   ============================================= */

let searchIndex = null;   // 검색 색인 캐시
let searchDropdown = null; // 현재 열린 드롭다운 엘리먼트

// 검색 색인 로드 (한 번만)
async function loadSearchIndex() {
  if (searchIndex) return searchIndex;
  try {
    const res = await fetch('/manual/assets/search-index.json');
    searchIndex = await res.json();
  } catch (e) {
    searchIndex = [];
  }
  return searchIndex;
}

// 쿼리로 색인 검색 (관련도 점수 계산)
function doSearch(query, index) {
  const q = query.trim().toLowerCase();
  if (!q) return [];

  // 검색어를 공백으로 분리 (다중 단어 지원)
  const terms = q.split(/\s+/).filter(t => t.length > 0);

  const scored = index.map(page => {
    const titleL    = (page.title    || '').toLowerCase();
    const h1L       = (page.h1       || '').toLowerCase();
    const sectionsL = (page.sections || '').toLowerCase();
    const descL     = (page.desc     || '').toLowerCase();
    const keywordsL = (page.keywords || '').toLowerCase();
    const categoryL = (page.category || '').toLowerCase();

    let score = 0;
    let matched = 0;

    for (const term of terms) {
      let termScore = 0;
      if (titleL.includes(term))    { termScore += 100; matched++; }
      if (h1L.includes(term))       { termScore += 80;  matched++; }
      if (categoryL.includes(term)) { termScore += 60;  matched++; }
      if (sectionsL.includes(term)) { termScore += 40;  matched++; }
      if (descL.includes(term))     { termScore += 30;  matched++; }
      if (keywordsL.includes(term)) { termScore += 20;  matched++; }
      score += termScore;
    }

    // 모든 단어가 일치하면 보너스
    if (matched >= terms.length && terms.length > 1) score += 50;

    return { page, score };
  })
  .filter(x => x.score > 0)
  .sort((a, b) => b.score - a.score)
  .slice(0, 8)
  .map(x => x.page);

  return scored;
}

// 검색어에서 매칭 부분 하이라이트
function highlight(text, query) {
  if (!text || !query) return text || '';
  const terms = query.trim().split(/\s+/).filter(t => t.length > 0);
  let result = text;
  for (const term of terms) {
    const re = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');
    result = result.replace(re, '<mark>$1</mark>');
  }
  return result;
}

// 카테고리별 색상 뱃지
const CATEGORY_COLORS = {
  '폰문자 SMS': '#2563eb',
  '원퍼널문자': '#7c3aed',
  '원챗': '#059669',
  '원챗 (OneChat)': '#059669',
  '원챗 매뉴얼': '#059669',
  'AI 메시지': '#dc2626',
  'AI 아바타': '#ea580c',
  'AI 아바타 학습': '#ea580c',
  '명함 관리': '#0891b2',
  '계정 관리': '#4f46e5',
  '고객센터': '#64748b',
  'DB 수집': '#16a34a',
  '대시보드': '#b45309',
};
function getCategoryColor(cat) {
  for (const [k, v] of Object.entries(CATEGORY_COLORS)) {
    if (cat && cat.includes(k.substring(0,4))) return v;
  }
  return '#64748b';
}

// 드롭다운 생성/업데이트
function showDropdown(inputEl, results, query) {
  removeDropdown();
  if (!results.length) {
    showNoResult(inputEl, query);
    return;
  }

  const isHero = inputEl.closest('.hero-search') !== null;

  const wrap = document.createElement('div');
  wrap.id = 'searchDropdown';
  wrap.style.cssText = `
    position:absolute; z-index:9999;
    background:var(--bg); border:1px solid var(--border);
    border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.15);
    overflow:hidden; min-width:320px; max-width:520px; width:100%;
  `;

  const header = document.createElement('div');
  header.style.cssText = 'padding:10px 16px 8px; font-size:11px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border);';
  header.textContent = `🔍 검색 결과 ${results.length}건 — "${query}"`;
  wrap.appendChild(header);

  const list = document.createElement('ul');
  list.style.cssText = 'list-style:none; padding:0; margin:0; max-height:400px; overflow-y:auto;';

  results.forEach((page, i) => {
    const li = document.createElement('li');
    li.style.cssText = 'border-bottom:1px solid var(--border);';
    if (i === results.length - 1) li.style.borderBottom = 'none';

    const a = document.createElement('a');
    a.href = page.url;
    a.style.cssText = `
      display:flex; align-items:flex-start; gap:12px;
      padding:12px 16px; text-decoration:none; transition:.15s;
    `;
    a.onmouseover = () => a.style.background = 'var(--bg2)';
    a.onmouseout  = () => a.style.background = 'transparent';

    // 카테고리 뱃지
    const catColor = getCategoryColor(page.category);
    const badge = `<span style="display:inline-block;background:${catColor}22;color:${catColor};border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;flex-shrink:0;margin-top:2px;">${page.category || '매뉴얼'}</span>`;

    // 제목
    const titleHl = highlight(page.title || page.h1, query);

    // 설명 (첫 100자)
    const descShort = (page.desc || page.sections || '').replace(/\s+/g,' ').substring(0, 100);
    const descHl = descShort ? highlight(descShort, query) + (descShort.length >= 100 ? '…' : '') : '';

    a.innerHTML = `
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
          <span style="font-size:14px;font-weight:600;color:var(--text);">${titleHl}</span>
          ${badge}
        </div>
        ${descHl ? `<div style="font-size:12px;color:var(--text2);line-height:1.5;">${descHl}</div>` : ''}
        <div style="font-size:11px;color:var(--text3);margin-top:3px;">📄 ${page.url}</div>
      </div>
      <span style="font-size:16px;color:var(--text3);margin-top:2px;">›</span>
    `;

    // 엔터/클릭 시 해당 페이지로 이동
    a.addEventListener('click', (e) => {
      e.preventDefault();
      removeDropdown();
      window.location.href = page.url;
    });

    li.appendChild(a);
    list.appendChild(li);
  });

  wrap.appendChild(list);

  // 더 많은 결과 안내
  const footer = document.createElement('div');
  footer.style.cssText = 'padding:8px 16px; font-size:11px; color:var(--text3); background:var(--bg2); border-top:1px solid var(--border); text-align:center;';
  footer.textContent = '↑↓ 방향키로 이동 · Enter로 선택 · Esc로 닫기';
  wrap.appendChild(footer);

  // 위치 결정
  positionDropdown(wrap, inputEl);

  document.body.appendChild(wrap);
  searchDropdown = wrap;

  // 키보드 네비게이션 설정
  setupKeyNav(wrap, inputEl);
}

// 결과 없음 드롭다운
function showNoResult(inputEl, query) {
  const wrap = document.createElement('div');
  wrap.id = 'searchDropdown';
  wrap.style.cssText = `
    position:absolute; z-index:9999;
    background:var(--bg); border:1px solid var(--border);
    border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.15);
    padding:20px 20px; text-align:center; min-width:280px;
  `;
  wrap.innerHTML = `
    <div style="font-size:24px;margin-bottom:8px;">🔍</div>
    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px;">"${query}"에 대한 결과 없음</div>
    <div style="font-size:12px;color:var(--text2);">다른 키워드로 검색해 보세요</div>
    <div style="margin-top:12px;font-size:12px;color:var(--text3);">예: 발송, 예약, 코칭, 결제, 퍼널</div>
  `;
  positionDropdown(wrap, inputEl);
  document.body.appendChild(wrap);
  searchDropdown = wrap;
}

// 드롭다운 위치 계산
function positionDropdown(wrap, inputEl) {
  const rect = inputEl.getBoundingClientRect();
  const scrollY = window.scrollY || document.documentElement.scrollTop;
  const scrollX = window.scrollX || document.documentElement.scrollLeft;

  let top = rect.bottom + scrollY + 6;
  let left = rect.left + scrollX;

  // 우측 화면 넘침 방지
  const dropW = Math.max(320, rect.width);
  if (left + dropW > window.innerWidth - 16) {
    left = window.innerWidth - dropW - 16;
  }
  if (left < 8) left = 8;

  wrap.style.top  = top + 'px';
  wrap.style.left = left + 'px';
  wrap.style.width = Math.max(rect.width, 320) + 'px';
}

// 드롭다운 제거
function removeDropdown() {
  if (searchDropdown) {
    searchDropdown.remove();
    searchDropdown = null;
  }
  const old = document.getElementById('searchDropdown');
  if (old) old.remove();
}

// 키보드 네비게이션 (↑↓ Enter Esc)
function setupKeyNav(wrap, inputEl) {
  const items = wrap.querySelectorAll('a');
  let idx = -1;

  function highlight_item(i) {
    items.forEach((a, j) => {
      a.style.background = j === i ? 'var(--bg2)' : 'transparent';
    });
  }

  inputEl._keyNavHandler = function(e) {
    if (!searchDropdown) return;
    if (e.key === 'Escape') {
      removeDropdown();
      inputEl.blur();
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      idx = Math.min(idx + 1, items.length - 1);
      highlight_item(idx);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      idx = Math.max(idx - 1, 0);
      highlight_item(idx);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (idx >= 0 && items[idx]) {
        const href = items[idx].getAttribute('href');
        removeDropdown();
        window.location.href = href;
      }
    }
  };

  // 기존 핸들러 제거 후 재등록
  inputEl.removeEventListener('keydown', inputEl._keyNavHandler);
  inputEl.addEventListener('keydown', inputEl._keyNavHandler);
}

// 검색 입력 이벤트 바인딩 (모든 .m-search-input)
async function initSearch() {
  const index = await loadSearchIndex();

  document.querySelectorAll('.m-search-input').forEach(input => {
    let debounceTimer = null;

    // 실시간 입력 → 드롭다운
    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const q = input.value.trim();
      if (!q) { removeDropdown(); return; }

      debounceTimer = setTimeout(() => {
        const results = doSearch(q, index);
        showDropdown(input, results, q);
      }, 180);
    });

    // 포커스 시 기존 값이 있으면 바로 검색
    input.addEventListener('focus', () => {
      const q = input.value.trim();
      if (q.length >= 1) {
        const results = doSearch(q, index);
        showDropdown(input, results, q);
      }
    });

    // Enter 키 → 검색 실행 (드롭다운 첫 결과로 이동)
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        const q = input.value.trim();
        if (!q) return;
        if (searchDropdown) {
          // 키 네비 핸들러가 처리 (setupKeyNav)
          return;
        }
        // 드롭다운이 없으면 직접 검색
        const results = doSearch(q, index);
        if (results.length > 0) {
          removeDropdown();
          window.location.href = results[0].url;
        } else {
          showNoResult(input, q);
        }
      }
    });
  });
}

// 외부 클릭 시 드롭다운 닫기
document.addEventListener('click', e => {
  if (!e.target.closest('#searchDropdown') && !e.target.closest('.m-search-input')) {
    removeDropdown();
  }
});

// 스크롤 시 드롭다운 닫기
window.addEventListener('scroll', removeDropdown, { passive: true });

// 검색 초기화 실행
initSearch();

/* mark 하이라이트 스타일 인라인 삽입 */
(function() {
  const s = document.createElement('style');
  s.textContent = `
    #searchDropdown mark {
      background: #fde68a;
      color: #92400e;
      border-radius: 2px;
      padding: 0 1px;
    }
    [data-theme="dark"] #searchDropdown mark {
      background: #854d0e;
      color: #fef3c7;
    }
    #searchDropdown::-webkit-scrollbar { width: 4px; }
    #searchDropdown::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
    .m-search-input:focus { border-color: var(--brand) !important; outline: none; }
  `;
  document.head.appendChild(s);
})();
