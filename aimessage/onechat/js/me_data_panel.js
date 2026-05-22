/*!
 * [ME-B6] 원챗 라이프 아바타 — 데이터 풀 패널 (Scope 토글 + me_data.php 연동)
 * --------------------------------------------------------------------------
 * · me/index.html 메뉴 시트의 [공개범위 관리] / [학습시키기] 진입점
 * · 모달 시트 형태로 동적 mount, 외부 me/index.html 본문은 일체 수정하지 않음
 * · me_data.php 와 GET/POST/DELETE 연동
 *
 * 공개 API (window.OneMeDataPanel):
 *   open([{ initialScope:'private'|'public'|'both'|'all', initialMode:'input'|'list' }])
 *   close()
 *   refresh()
 *
 * 의존: me_data_panel.css, OneMeMode (optional)
 * 생성: 2026-05-23
 */
(function () {
  'use strict';

  // ──────────────────────────────────────────────────────────────
  // 상수
  // ──────────────────────────────────────────────────────────────
  var API_URL  = '/aimessage/onechat/api/me_data.php';
  var CSS_HREF = '/aimessage/onechat/css/me_data_panel.css';
  var PANEL_ID = 'meDataPanelOverlay';
  var TOAST_ID = 'meDataPanelToast';
  var PAGE_SIZE = 30;

  var CATEGORIES = [
    { v:'basic',       label:'기본 정보',     icon:'👤' },
    { v:'childhood',   label:'어린 시절',     icon:'🧸' },
    { v:'diary',       label:'일기·메모',     icon:'📔' },
    { v:'file',        label:'파일 자료',     icon:'📁' },
    { v:'voice',       label:'음성 기록',     icon:'🎙️' },
    { v:'image',       label:'이미지',        icon:'🖼️' },
    { v:'fingerprint', label:'지문',          icon:'☝️' },
    { v:'palmistry',   label:'손금',          icon:'🖐️' },
    { v:'physiognomy', label:'관상',          icon:'👁️' },
    { v:'saju',        label:'사주',          icon:'🔮' },
    { v:'astrology',   label:'점성술',        icon:'⭐' },
    { v:'decision',    label:'의사결정',      icon:'⚖️' },
    { v:'etc',         label:'기타',          icon:'🗂️' }
  ];

  var SCOPES = [
    { v:'all',     label:'전체',  icon:'fa-layer-group' },
    { v:'private', label:'나만',  icon:'fa-lock' },
    { v:'public',  label:'외부',  icon:'fa-globe' },
    { v:'both',    label:'양쪽',  icon:'fa-circle-half-stroke' }
  ];

  var PRIVACY_LEVELS = [
    { v:1, label:'1 · 공개' },
    { v:2, label:'2 · 일반' },
    { v:3, label:'3 · 보통 (기본)' },
    { v:4, label:'4 · 민감 (Lock 시 차단)' },
    { v:5, label:'5 · 극민감 (Lock 시 차단)' }
  ];

  // ──────────────────────────────────────────────────────────────
  // 상태
  // ──────────────────────────────────────────────────────────────
  var state = {
    currentScope: 'all',     // 목록 필터 scope
    inputScope:   'private', // 입력 시 기본 scope
    items:        [],
    total:        0,
    offset:       0,
    loading:      false,
    locked:       false,
    avatarStatus: 'active'
  };

  // ──────────────────────────────────────────────────────────────
  // 유틸
  // ──────────────────────────────────────────────────────────────
  function $(id) { return document.getElementById(id); }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function fmtDate(s) {
    if (!s) return '';
    var d = new Date(s.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return s;
    var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
           ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }
  function categoryMeta(v) {
    for (var i = 0; i < CATEGORIES.length; i++) if (CATEGORIES[i].v === v) return CATEGORIES[i];
    return { v:v, label:v, icon:'🗂️' };
  }
  function scopeMeta(v) {
    for (var i = 0; i < SCOPES.length; i++) if (SCOPES[i].v === v) return SCOPES[i];
    return { v:v, label:v };
  }

  // ──────────────────────────────────────────────────────────────
  // CSS 자동 로드
  // ──────────────────────────────────────────────────────────────
  function ensureCSS() {
    var existing = document.querySelector('link[data-mod="me-data-panel"]');
    if (existing) return;
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = CSS_HREF + '?v=' + (window.__ME_DP_V || '0523a');
    link.setAttribute('data-mod', 'me-data-panel');
    document.head.appendChild(link);
  }

  // ──────────────────────────────────────────────────────────────
  // 토스트
  // ──────────────────────────────────────────────────────────────
  function toast(msg, type) {
    var el = $(TOAST_ID);
    if (!el) {
      el = document.createElement('div');
      el.id = TOAST_ID;
      el.className = 'me-dp-toast';
      document.body.appendChild(el);
    }
    el.className = 'me-dp-toast' + (type ? ' ' + type : '');
    el.textContent = msg;
    requestAnimationFrame(function () { el.classList.add('show'); });
    clearTimeout(toast._t);
    toast._t = setTimeout(function () { el.classList.remove('show'); }, 2400);
  }

  // ──────────────────────────────────────────────────────────────
  // DOM 빌더
  // ──────────────────────────────────────────────────────────────
  function buildPanel() {
    var overlay = document.createElement('div');
    overlay.id = PANEL_ID;
    overlay.className = 'me-dp-overlay';

    var html = ''
      + '<div class="me-dp-sheet" role="dialog" aria-modal="true" aria-label="데이터 풀 관리">'
      +   '<div class="me-dp-head">'
      +     '<h3><i class="fas fa-shield-alt"></i> 데이터 풀 · 공개범위 관리</h3>'
      +     '<button type="button" class="me-dp-close" id="meDpClose" aria-label="닫기">'
      +       '<i class="fas fa-times"></i>'
      +     '</button>'
      +   '</div>'

      +   '<div class="me-dp-scope" id="meDpScope">'
      +     SCOPES.map(function (s) {
              return '<button type="button" class="me-dp-scope-btn" data-scope="' + s.v + '">'
                   +   '<i class="fas ' + s.icon + '"></i> ' + s.label
                   + '</button>';
            }).join('')
      +   '</div>'

      +   '<div class="me-dp-banner" id="meDpBanner"></div>'

      +   '<div class="me-dp-body">'

      +     '<form class="me-dp-form" id="meDpForm" autocomplete="off">'
      +       '<div class="me-dp-form-row">'
      +         '<select class="me-dp-select" id="meDpInCategory">'
      +           CATEGORIES.map(function (c) {
                    return '<option value="' + c.v + '"' + (c.v === 'diary' ? ' selected' : '') + '>'
                         + c.icon + ' ' + c.label + '</option>';
                  }).join('')
      +         '</select>'
      +         '<select class="me-dp-select" id="meDpInScope">'
      +           '<option value="private">🔒 나만 (private)</option>'
      +           '<option value="public">🌐 외부 (public)</option>'
      +           '<option value="both">⚖️ 양쪽 (both)</option>'
      +         '</select>'
      +       '</div>'
      +       '<input type="text" class="me-dp-input" id="meDpInTitle" placeholder="제목 (선택)" maxlength="200">'
      +       '<textarea class="me-dp-textarea" id="meDpInText" rows="3" placeholder="내용을 자유롭게 적어주세요. 예) 오늘 회사에서 OO 결정을 했다…"></textarea>'
      +       '<div class="me-dp-form-bot">'
      +         '<div class="me-dp-privacy">'
      +           '<i class="fas fa-shield-alt"></i> 민감도'
      +           '<select id="meDpInPriv">'
      +             PRIVACY_LEVELS.map(function (p) {
                      return '<option value="' + p.v + '"' + (p.v === 3 ? ' selected' : '') + '>'
                           + esc(p.label) + '</option>';
                    }).join('')
      +           '</select>'
      +         '</div>'
      +         '<button type="submit" class="me-dp-submit" id="meDpSubmit">'
      +           '<i class="fas fa-plus"></i> 데이터 추가'
      +         '</button>'
      +       '</div>'
      +       '<div class="me-dp-status" id="meDpStatus"></div>'
      +     '</form>'

      +     '<div class="me-dp-list" id="meDpList"></div>'
      +     '<div class="me-dp-more" id="meDpMore" style="display:none">'
      +       '<button type="button" id="meDpMoreBtn">더 보기</button>'
      +     '</div>'

      +   '</div>'  /* body */
      + '</div>';   /* sheet */

    overlay.innerHTML = html;

    // 오버레이 클릭 시 닫기 (시트 내부 클릭은 제외)
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    return overlay;
  }

  // ──────────────────────────────────────────────────────────────
  // 렌더링
  // ──────────────────────────────────────────────────────────────
  function renderScopeTabs() {
    var btns = document.querySelectorAll('#meDpScope .me-dp-scope-btn');
    btns.forEach(function (b) {
      if (b.dataset.scope === state.currentScope) b.classList.add('active');
      else b.classList.remove('active');
    });
  }

  function renderBanner() {
    var el = $('meDpBanner');
    if (!el) return;
    if (state.locked) {
      el.className = 'me-dp-banner lock show';
      el.innerHTML = '<i class="fas fa-lock"></i> Panic Lock 상태입니다. 민감도 4·5 항목은 자동 숨김, 신규 입력이 차단됩니다.';
    } else {
      el.className = 'me-dp-banner';
      el.innerHTML = '';
    }
  }

  function renderList() {
    var listEl = $('meDpList');
    var moreEl = $('meDpMore');
    if (!listEl) return;

    if (state.loading && state.items.length === 0) {
      listEl.innerHTML = '<div class="me-dp-loading"><i class="fas fa-circle-notch"></i> 불러오는 중…</div>';
      moreEl.style.display = 'none';
      return;
    }

    if (!state.items.length) {
      listEl.innerHTML = ''
        + '<div class="me-dp-empty">'
        +   '<i class="fas fa-inbox"></i>'
        +   '아직 등록된 데이터가 없습니다.<br>'
        +   '<small>위 입력창으로 첫 데이터를 추가해보세요.</small>'
        + '</div>';
      moreEl.style.display = 'none';
      return;
    }

    listEl.innerHTML = state.items.map(function (it) {
      var sc = scopeMeta(it.scope);
      var cm = categoryMeta(it.category);
      var preview = esc(it.payload_preview || '');
      var title = esc(it.title || '(제목 없음)');
      var priv = parseInt(it.privacy_level, 10) || 3;
      var when = fmtDate(it.occurred_at || it.created_at);
      return ''
        + '<div class="me-dp-card" data-scope="' + esc(it.scope) + '" data-idx="' + it.idx + '">'
        +   '<div class="me-dp-card-top">'
        +     '<span class="me-dp-chip me-dp-chip-scope" data-scope="' + esc(it.scope) + '">'
        +       (it.scope === 'private' ? '🔒' : it.scope === 'public' ? '🌐' : '⚖️')
        +       ' ' + esc(sc.label)
        +     '</span>'
        +     '<span class="me-dp-chip me-dp-chip-cat">' + cm.icon + ' ' + esc(cm.label) + '</span>'
        +     (priv >= 4 ? '<span class="me-dp-chip me-dp-chip-priv">🔥 민감도 ' + priv + '</span>' : '')
        +   '</div>'
        +   (title !== '(제목 없음)' || !preview
              ? '<div class="me-dp-card-title">' + title + '</div>' : '')
        +   (preview ? '<div class="me-dp-card-body">' + preview + '</div>' : '')
        +   '<div class="me-dp-card-meta">'
        +     '<span><i class="far fa-clock"></i> ' + esc(when) + '</span>'
        +     '<button type="button" class="me-dp-card-del" data-idx="' + it.idx + '" title="삭제">'
        +       '<i class="fas fa-trash"></i>'
        +     '</button>'
        +   '</div>'
        + '</div>';
    }).join('');

    // 더 보기 버튼
    if (state.items.length < state.total) {
      moreEl.style.display = '';
      $('meDpMoreBtn').textContent = '더 보기 (' + state.items.length + ' / ' + state.total + ')';
    } else {
      moreEl.style.display = 'none';
    }

    // 삭제 버튼 바인딩
    listEl.querySelectorAll('.me-dp-card-del').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = parseInt(btn.dataset.idx, 10);
        handleDelete(idx);
      });
    });
  }

  // ──────────────────────────────────────────────────────────────
  // API 호출
  // ──────────────────────────────────────────────────────────────
  function apiGet(scope, offset) {
    var params = new URLSearchParams();
    params.set('scope', scope || 'all');
    params.set('limit', PAGE_SIZE);
    params.set('offset', offset || 0);
    return fetch(API_URL + '?' + params.toString(), {
      method: 'GET',
      credentials: 'include',
      headers: { 'Accept': 'application/json' }
    }).then(function (r) {
      return r.json().then(function (j) { return { code: r.status, body: j }; });
    });
  }

  function apiPost(payload) {
    return fetch(API_URL, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) {
      return r.json().then(function (j) { return { code: r.status, body: j }; });
    });
  }

  function apiDelete(id) {
    return fetch(API_URL + '?id=' + encodeURIComponent(id), {
      method: 'DELETE',
      credentials: 'include',
      headers: { 'Accept': 'application/json' }
    }).then(function (r) {
      return r.json().then(function (j) { return { code: r.status, body: j }; });
    });
  }

  // ──────────────────────────────────────────────────────────────
  // 액션
  // ──────────────────────────────────────────────────────────────
  function loadList(append) {
    state.loading = true;
    if (!append) {
      state.items = [];
      state.offset = 0;
    }
    renderList();

    apiGet(state.currentScope, state.offset).then(function (res) {
      state.loading = false;
      if (res.code === 401) {
        toast('로그인이 필요합니다.', 'error');
        close();
        return;
      }
      if (res.code !== 200 || !res.body || !res.body.ok) {
        var msg = (res.body && res.body.error && (res.body.error.message || res.body.error)) || ('서버 오류 (' + res.code + ')');
        toast(msg, 'error');
        return;
      }
      var b = res.body;
      state.locked = !!b.locked;
      state.avatarStatus = b.status || 'active';
      state.total = b.total || 0;
      var newItems = b.items || [];
      if (append) state.items = state.items.concat(newItems);
      else        state.items = newItems;
      state.offset = state.items.length;
      renderBanner();
      renderList();
      updateSubmitButton();
    }).catch(function (e) {
      state.loading = false;
      console.error('[me-dp] GET 실패:', e);
      toast('네트워크 오류', 'error');
    });
  }

  function handleSubmit(ev) {
    if (ev) ev.preventDefault();
    if (state.locked) {
      toast('Panic Lock 상태에서는 입력할 수 없습니다.', 'error');
      return;
    }
    var category = $('meDpInCategory').value;
    var scope    = $('meDpInScope').value;
    var title    = $('meDpInTitle').value.trim();
    var text     = $('meDpInText').value.trim();
    var priv     = parseInt($('meDpInPriv').value, 10) || 3;

    if (!title && !text) {
      setStatus('제목 또는 내용 중 하나는 입력해주세요.', 'error');
      $('meDpInText').focus();
      return;
    }

    var btn = $('meDpSubmit');
    btn.disabled = true;
    setStatus('저장 중…', 'info');

    apiPost({
      category: category,
      scope: scope,
      title: title || null,
      payload_text: text || null,
      privacy_level: priv
    }).then(function (res) {
      btn.disabled = false;
      if (res.code === 201 && res.body && res.body.ok) {
        setStatus('저장 완료 (idx ' + res.body.idx + ')', 'ok');
        toast('데이터가 추가되었습니다.', 'ok');
        $('meDpInTitle').value = '';
        $('meDpInText').value = '';
        // 현재 scope 필터와 입력 scope가 같거나 'all'이면 새 항목을 위에 끼움
        if (state.currentScope === 'all' || state.currentScope === scope) {
          if (res.body.item) {
            state.items.unshift(res.body.item);
            state.total += 1;
            state.offset += 1;
            renderList();
          } else {
            loadList(false);
          }
        } else {
          // 다른 scope면 카운터 안내만
          setStatus('현재 [' + scope + ']에 저장됨. 탭을 전환해 확인하세요.', 'info');
        }
      } else if (res.code === 423) {
        state.locked = true;
        renderBanner();
        updateSubmitButton();
        setStatus('Panic Lock 상태입니다. 입력이 차단됩니다.', 'error');
        toast('Panic Lock 상태', 'error');
      } else {
        var msg = (res.body && res.body.error && (res.body.error.message || res.body.error)) || ('서버 오류 (' + res.code + ')');
        setStatus(msg, 'error');
        toast(msg, 'error');
      }
    }).catch(function (e) {
      btn.disabled = false;
      console.error('[me-dp] POST 실패:', e);
      setStatus('네트워크 오류', 'error');
      toast('네트워크 오류', 'error');
    });
  }

  function handleDelete(idx) {
    if (!idx) return;
    if (!confirm('이 데이터를 삭제할까요?\n(soft-delete 되며 복구는 별도 작업이 필요합니다.)')) return;

    apiDelete(idx).then(function (res) {
      if (res.code === 200 && res.body && res.body.ok) {
        // 로컬 상태에서 제거
        state.items = state.items.filter(function (it) { return it.idx !== idx; });
        state.total = Math.max(0, state.total - 1);
        state.offset = state.items.length;
        renderList();
        toast(res.body.already_deleted ? '이미 삭제된 항목입니다.' : '삭제되었습니다.', 'ok');
      } else if (res.code === 404) {
        toast('이미 삭제된 항목입니다.', 'ok');
        state.items = state.items.filter(function (it) { return it.idx !== idx; });
        renderList();
      } else {
        var msg = (res.body && res.body.error && (res.body.error.message || res.body.error)) || ('삭제 실패 (' + res.code + ')');
        toast(msg, 'error');
      }
    }).catch(function (e) {
      console.error('[me-dp] DELETE 실패:', e);
      toast('네트워크 오류', 'error');
    });
  }

  // ──────────────────────────────────────────────────────────────
  // 헬퍼
  // ──────────────────────────────────────────────────────────────
  function setStatus(msg, type) {
    var el = $('meDpStatus');
    if (!el) return;
    el.className = 'me-dp-status' + (type ? ' ' + type : '');
    el.textContent = msg || '';
  }

  function updateSubmitButton() {
    var btn = $('meDpSubmit');
    if (!btn) return;
    if (state.locked) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-lock"></i> Panic Lock';
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plus"></i> 데이터 추가';
    }
  }

  // ──────────────────────────────────────────────────────────────
  // 이벤트 바인딩
  // ──────────────────────────────────────────────────────────────
  function bindEvents() {
    $('meDpClose').addEventListener('click', close);

    // ESC 키
    document.addEventListener('keydown', escHandler);

    // 스코프 탭
    document.querySelectorAll('#meDpScope .me-dp-scope-btn').forEach(function (b) {
      b.addEventListener('click', function () {
        var sc = b.dataset.scope;
        if (sc === state.currentScope) return;
        state.currentScope = sc;
        renderScopeTabs();
        loadList(false);
      });
    });

    // 입력 scope select 변경 시, 현재 탭과 다르면 안내
    $('meDpInScope').addEventListener('change', function () {
      state.inputScope = $('meDpInScope').value;
    });

    // 폼 제출
    $('meDpForm').addEventListener('submit', handleSubmit);

    // Ctrl/Cmd+Enter 단축키
    $('meDpInText').addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        handleSubmit();
      }
    });

    // 더 보기
    $('meDpMoreBtn').addEventListener('click', function () {
      loadList(true);
    });
  }

  function escHandler(e) {
    if (e.key === 'Escape') {
      var ov = $(PANEL_ID);
      if (ov && ov.classList.contains('open')) close();
    }
  }

  // ──────────────────────────────────────────────────────────────
  // 공개 API
  // ──────────────────────────────────────────────────────────────
  function open(opt) {
    ensureCSS();
    opt = opt || {};

    var ov = $(PANEL_ID);
    if (!ov) {
      ov = buildPanel();
      document.body.appendChild(ov);
      bindEvents();
    }

    // 초기 스코프
    if (opt.initialScope && ['all','private','public','both'].indexOf(opt.initialScope) >= 0) {
      state.currentScope = opt.initialScope;
    }
    // 입력 scope 기본값: OneMeMode가 private이면 private, 아니면 private (라이프 기본은 내부 저장)
    var defaultInputScope = 'private';
    if (window.OneMeMode && typeof window.OneMeMode.isPrivate === 'function') {
      defaultInputScope = window.OneMeMode.isPrivate() ? 'private' : 'public';
    }
    if (opt.inputScope) defaultInputScope = opt.inputScope;
    state.inputScope = defaultInputScope;
    var inScopeEl = $('meDpInScope');
    if (inScopeEl) inScopeEl.value = defaultInputScope;

    renderScopeTabs();
    ov.classList.add('open');
    document.body.style.overflow = 'hidden';

    // 첫 로드
    loadList(false);

    // 입력창 포커스
    setTimeout(function () {
      var t = $('meDpInText');
      if (t) t.focus();
    }, 250);
  }

  function close() {
    var ov = $(PANEL_ID);
    if (ov) ov.classList.remove('open');
    document.body.style.overflow = '';
  }

  function refresh() {
    loadList(false);
  }

  window.OneMeDataPanel = {
    open: open,
    close: close,
    refresh: refresh
  };

})();
