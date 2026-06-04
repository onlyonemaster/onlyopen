/* [ME-C2] OneChat Life Avatar — Decisions Panel
 * ----------------------------------------------------
 * "결정 강화학습 루프" UI
 *   PREDICT  → POST  /api/me_decisions.php (title + context + options + ai_prediction)
 *   DECIDE   → PUT   /api/me_decisions.php?id=N (chosen_option)
 *   REFLECT  → PUT   /api/me_decisions.php?id=N (outcome_text + match_score + match_label)
 *
 * 외부 노출:
 *   window.OneMeDecisionsPanel = { open(opts?), close(), refresh() }
 *
 * 의존:
 *   - /aimessage/onechat/api/me_decisions.php
 *   - /aimessage/onechat/css/me_decisions_panel.css (자동 로드)
 *
 * 형제 모듈: me_data_panel(.js), me_dashboard(.js)
 * dm.js / main.js / avatar_v2.html 비건드림.
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23 (C-2)
 */

(function () {
  'use strict';
  if (window.OneMeDecisionsPanel) return;

  // ───────── Config ─────────
  var API_URL  = '/aimessage/onechat/api/me_decisions.php';
  var CSS_HREF = '/aimessage/onechat/css/me_decisions_panel.css';

  var STATE_LABEL = { pending: '대기', decided: '결정함', reflected: '회고완료' };
  var MATCH_LABEL = { hit: '적중', partial: '부분', miss: '빗나감', pending: '대기' };

  function ensureCss() {
    if (document.querySelector('link[data-me-dc-css]')) return;
    var link = document.createElement('link');
    link.rel  = 'stylesheet';
    link.href = CSS_HREF + '?v=0523c2';
    link.setAttribute('data-me-dc-css', '1');
    document.head.appendChild(link);
  }

  // ───────── State ─────────
  var state = {
    mounted: false,
    open: false,
    loading: false,
    filter: 'all',
    items: [],
    summary: null,
    locked: false,
    pendingOpts: [],   // 신규 등록 시 임시 옵션 배열
  };

  // ───────── Utils ─────────
  function esc(s) {
    s = (s == null) ? '' : String(s);
    return s.replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }
  function el(tag, attrs, html) {
    var e = document.createElement(tag);
    if (attrs) for (var k in attrs) {
      if (k === 'class') e.className = attrs[k];
      else if (k === 'style') e.style.cssText = attrs[k];
      else if (k.indexOf('on') === 0) e[k] = attrs[k];
      else e.setAttribute(k, attrs[k]);
    }
    if (html != null) e.innerHTML = html;
    return e;
  }

  // ───────── Mount ─────────
  function ensureMounted() {
    if (state.mounted) return;
    ensureCss();

    var ov = el('div', { class: 'me-dc-overlay', id: 'me-dc-overlay' });
    var sh = el('div', { class: 'me-dc-sheet', id: 'me-dc-sheet',
                          role: 'dialog', 'aria-modal': 'true',
                          'aria-labelledby': 'me-dc-title' });

    sh.innerHTML =
      '<div class="me-dc-head">' +
        '<h2 id="me-dc-title"><i class="fas fa-scale-balanced"></i> 의사결정 추적</h2>' +
        '<span class="lock-badge" id="me-dc-lock"><i class="fas fa-lock"></i> 잠김</span>' +
        '<button type="button" class="me-dc-close" id="me-dc-close" aria-label="닫기"><i class="fas fa-times"></i></button>' +
      '</div>' +
      '<div class="me-dc-summary" id="me-dc-summary">' +
        '<div class="stat"><div class="num" id="ms-total">0</div><div class="lbl">전체</div></div>' +
        '<div class="stat pending"><div class="num" id="ms-pending">0</div><div class="lbl">대기</div></div>' +
        '<div class="stat decided"><div class="num" id="ms-decided">0</div><div class="lbl">결정함</div></div>' +
        '<div class="stat rate"><div class="num" id="ms-rate">–</div><div class="lbl">매칭률</div></div>' +
      '</div>' +
      '<div class="me-dc-tabs" id="me-dc-tabs">' +
        '<button data-f="all" class="active">전체</button>' +
        '<button data-f="pending">대기</button>' +
        '<button data-f="decided">결정함</button>' +
        '<button data-f="reflected">회고</button>' +
      '</div>' +
      '<div class="me-dc-body" id="me-dc-body">' +
        '<div class="me-dc-loading">불러오는 중...</div>' +
      '</div>' +
      '<div class="me-dc-foot">' +
        '<span id="me-dc-meta">—</span>' +
        '<button type="button" class="primary" id="me-dc-new"><i class="fas fa-plus"></i> 새 결정 등록</button>' +
      '</div>';

    document.body.appendChild(ov);
    document.body.appendChild(sh);

    ov.addEventListener('click', close);
    sh.querySelector('#me-dc-close').addEventListener('click', close);

    // 탭 필터
    sh.querySelectorAll('#me-dc-tabs button').forEach(function (b) {
      b.addEventListener('click', function () {
        var f = b.getAttribute('data-f');
        if (state.filter === f) return;
        state.filter = f;
        sh.querySelectorAll('#me-dc-tabs button').forEach(function (x) {
          x.classList.toggle('active', x === b);
        });
        load();
      });
    });

    // 새 결정 등록
    sh.querySelector('#me-dc-new').addEventListener('click', function () {
      var body = document.getElementById('me-dc-body');
      var card = body.querySelector('.me-dc-newcard');
      if (!card) return;
      card.open = true;
      card.scrollIntoView({ behavior: 'smooth', block: 'start' });
      var t = card.querySelector('input[name="title"]');
      if (t) setTimeout(function () { t.focus(); }, 200);
    });

    document.addEventListener('keydown', function (e) {
      if (state.open && e.key === 'Escape') close();
    });

    state.mounted = true;
  }

  // ───────── Render: summary strip ─────────
  function renderSummary(s) {
    if (!s) return;
    var $ = function (id) { return document.getElementById(id); };
    if ($('ms-total'))    $('ms-total').textContent   = s.total || 0;
    if ($('ms-pending'))  $('ms-pending').textContent = s.pending || 0;
    if ($('ms-decided'))  $('ms-decided').textContent = s.decided || 0;
    if ($('ms-rate')) {
      if (s.match_rate === null || s.match_rate === undefined) {
        $('ms-rate').textContent = '–';
      } else {
        $('ms-rate').textContent = Math.round(s.match_rate) + '%';
      }
    }
  }

  // ───────── Render: body ─────────
  function renderError(msg) {
    var body = document.getElementById('me-dc-body');
    if (!body) return;
    body.innerHTML = '<div class="me-dc-error"><i class="fas fa-exclamation-triangle"></i> ' + esc(msg) + '</div>';
  }

  function renderBody() {
    var body = document.getElementById('me-dc-body');
    if (!body) return;

    var html = '';
    html += renderNewCard();

    if (state.items.length === 0) {
      html += '<div class="me-dc-empty">' +
                '<p>등록된 결정이 없습니다.</p>' +
                '<p class="hint">"새 결정 등록"으로 첫 결정을 추가해보세요.</p>' +
              '</div>';
    } else {
      html += '<div class="me-dc-list" id="me-dc-list">';
      state.items.forEach(function (it) { html += renderItem(it); });
      html += '</div>';
    }

    body.innerHTML = html;

    bindNewCard();
    bindItems();
  }

  function renderNewCard() {
    var disabled = state.locked ? ' disabled' : '';
    var note = state.locked
      ? '<p style="color:#b91c1c;font-size:11.5px;margin:4px 0 0;"><i class="fas fa-lock"></i> 잠금 상태 — 결정 추가 불가</p>'
      : '';
    return '' +
      '<details class="me-dc-newcard" id="me-dc-newcard">' +
        '<summary><i class="fas fa-lightbulb"></i> 새 결정 등록 (Predict 단계)</summary>' +
        '<div class="fields">' +
          '<label>결정 제목 <span style="color:#ef4444;">*</span></label>' +
          '<input type="text" name="title" placeholder="예: 동업 제안 수락 여부" maxlength="200"' + disabled + '>' +
          '<label>상황 / 맥락</label>' +
          '<textarea name="context_text" placeholder="결정 당시의 상황, 정보, 감정 등"' + disabled + '></textarea>' +
          '<label>선택지 (Enter 또는 + 로 추가)</label>' +
          '<div class="me-dc-opts" id="me-dc-newopts"></div>' +
          '<div class="row">' +
            '<input type="text" id="me-dc-newopt-input" placeholder="선택지 라벨 입력 후 엔터"' + disabled + '>' +
            '<button type="button" class="narrow" id="me-dc-newopt-add"' + disabled + '>＋ 추가</button>' +
          '</div>' +
          '<label>AI 추천 옵션 (선택지 중 1순위)</label>' +
          '<input type="text" name="ai_recommended" placeholder="예: 수락"' + disabled + '>' +
          '<label>AI 예측 / 근거</label>' +
          '<textarea name="ai_prediction" placeholder="아바타가 추천한 이유, 예측되는 결과 등"' + disabled + '></textarea>' +
          note +
          '<div class="me-dc-form-actions">' +
            '<button type="button" id="me-dc-newcancel">취소</button>' +
            '<button type="button" class="primary" id="me-dc-newsubmit"' + disabled + '>' +
              '<i class="fas fa-save"></i> 등록' +
            '</button>' +
          '</div>' +
        '</div>' +
      '</details>';
  }

  function renderOpts(opts) {
    if (!opts || !opts.length) return '<span style="color:#9ca3af;font-size:11.5px;">선택지 없음</span>';
    return opts.map(function (o) {
      var label = (typeof o === 'string') ? o : (o && o.label ? o.label : '');
      return '<span class="chip" style="cursor:default;"><span>' + esc(label) + '</span></span>';
    }).join('');
  }

  function renderItem(it) {
    var stateLabel = STATE_LABEL[it.state] || it.state;
    var matchLabel = MATCH_LABEL[it.match_label] || it.match_label;
    var optsLine = '';
    if (it.options && it.options.length) {
      var opts = it.options.map(function (o) {
        var label = (typeof o === 'string') ? o : (o && o.label ? o.label : '');
        var chosen = it.chosen_option && (label === it.chosen_option);
        var aiPick = it.ai_recommended && (label === it.ai_recommended);
        var tag = '';
        if (chosen) tag += ' <i class="fas fa-check-circle" style="color:#10b981;"></i>';
        if (aiPick) tag += ' <i class="fas fa-robot" style="color:#a855f7;"></i>';
        return esc(label) + tag;
      });
      optsLine = '<div class="row2"><span><i class="fas fa-list" style="opacity:0.6;"></i></span> ' +
                 opts.join(' &middot; ') +
                 '</div>';
    }

    var matchHtml = '';
    if (it.state === 'reflected') {
      var scoreTxt = (it.match_score !== null && it.match_score !== undefined)
        ? Math.round(it.match_score) + '%' : '';
      matchHtml = '<span class="me-dc-match ' + esc(it.match_label || 'pending') + '">' +
                    esc(matchLabel) + (scoreTxt ? ' ' + scoreTxt : '') +
                  '</span>';
    }

    var ctxLine = it.context_text
      ? '<div class="ctx">' + esc(it.context_text) + '</div>'
      : '';

    var aiLine = '';
    if (it.ai_prediction) {
      aiLine = '<div class="ctx" style="background:#faf5ff;border-left:3px solid #c4b5fd;padding-left:8px;border-radius:0 6px 6px 0;">' +
                 '<i class="fas fa-robot" style="color:#a855f7;"></i> ' + esc(it.ai_prediction) +
               '</div>';
    }

    var outcomeLine = '';
    if (it.outcome_text) {
      outcomeLine = '<div class="ctx" style="background:#ecfdf5;border-left:3px solid #6ee7b7;padding-left:8px;border-radius:0 6px 6px 0;">' +
                      '<i class="fas fa-flag-checkered" style="color:#10b981;"></i> ' + esc(it.outcome_text) +
                    '</div>';
    }

    // actions per state
    var actions = '';
    if (!state.locked) {
      if (it.state === 'pending') {
        actions += '<button data-act="decide" data-id="' + it.idx + '"><i class="fas fa-check"></i> 결정함</button>';
      }
      if (it.state === 'decided' || it.state === 'pending') {
        actions += '<button data-act="reflect" data-id="' + it.idx + '" class="primary"><i class="fas fa-flag-checkered"></i> 결과 입력</button>';
      }
      actions += '<button data-act="delete" data-id="' + it.idx + '" class="danger"><i class="fas fa-trash"></i> 삭제</button>';
    }

    return '' +
      '<div class="me-dc-item state-' + esc(it.state) + '" data-id="' + it.idx + '">' +
        '<div class="row1">' +
          '<div class="ttl">' + esc(it.title) + '</div>' +
          '<div class="ts">' + esc(it.created_at || '') + '</div>' +
        '</div>' +
        '<div class="row2">' +
          '<span class="me-dc-pill ' + esc(it.state) + '">' + esc(stateLabel) + '</span>' +
          matchHtml +
        '</div>' +
        optsLine +
        ctxLine +
        aiLine +
        outcomeLine +
        (actions ? '<div class="me-dc-actions">' + actions + '</div>' : '') +
        '<div class="me-dc-form" id="me-dc-form-' + it.idx + '"></div>' +
      '</div>';
  }

  // ───────── New decision card bindings ─────────
  function bindNewCard() {
    var card = document.getElementById('me-dc-newcard');
    if (!card) return;

    state.pendingOpts = []; // reset every render

    var optInput = card.querySelector('#me-dc-newopt-input');
    var addBtn   = card.querySelector('#me-dc-newopt-add');
    var optsBox  = card.querySelector('#me-dc-newopts');

    function rerenderOpts() {
      if (!optsBox) return;
      optsBox.innerHTML = state.pendingOpts.map(function (o, i) {
        return '<span class="chip">' + esc(o) +
                 ' <button type="button" data-i="' + i + '" aria-label="삭제">&times;</button>' +
               '</span>';
      }).join('');
      optsBox.querySelectorAll('button[data-i]').forEach(function (b) {
        b.addEventListener('click', function () {
          var i = parseInt(b.getAttribute('data-i'), 10);
          state.pendingOpts.splice(i, 1);
          rerenderOpts();
        });
      });
    }

    function addOpt() {
      var v = (optInput.value || '').trim();
      if (!v) return;
      if (state.pendingOpts.indexOf(v) >= 0) { optInput.value = ''; return; }
      if (state.pendingOpts.length >= 8) {
        alert('선택지는 최대 8개까지 등록 가능합니다.');
        return;
      }
      state.pendingOpts.push(v);
      optInput.value = '';
      rerenderOpts();
    }

    if (addBtn) addBtn.addEventListener('click', addOpt);
    if (optInput) optInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); addOpt(); }
    });

    var cancel = card.querySelector('#me-dc-newcancel');
    if (cancel) cancel.addEventListener('click', function () {
      card.open = false;
      state.pendingOpts = [];
      rerenderOpts();
      card.querySelectorAll('input, textarea').forEach(function (x) { x.value = ''; });
    });

    var submit = card.querySelector('#me-dc-newsubmit');
    if (submit) submit.addEventListener('click', function () {
      if (state.locked) { alert('잠금 상태에서는 결정을 추가할 수 없습니다.'); return; }
      var title = (card.querySelector('input[name="title"]').value || '').trim();
      if (!title) { alert('제목을 입력해주세요.'); return; }
      var payload = {
        title: title,
        context_text:   card.querySelector('textarea[name="context_text"]').value || '',
        ai_recommended: (card.querySelector('input[name="ai_recommended"]').value || '').trim(),
        ai_prediction:  card.querySelector('textarea[name="ai_prediction"]').value || '',
        options: state.pendingOpts.slice()
      };
      submit.disabled = true;
      apiCreate(payload).then(function (resp) {
        if (resp && resp.ok) {
          // reset & reload
          state.pendingOpts = [];
          card.open = false;
          card.querySelectorAll('input, textarea').forEach(function (x) { x.value = ''; });
          load();
        } else {
          alert('등록 실패: ' + ((resp && resp.message) || '서버 오류'));
        }
      }).catch(function (err) {
        alert('등록 실패: ' + (err && err.message || '네트워크 오류'));
      }).then(function () {
        submit.disabled = false;
      });
    });
  }

  // ───────── Item action bindings ─────────
  function bindItems() {
    document.querySelectorAll('.me-dc-item button[data-act]').forEach(function (b) {
      b.addEventListener('click', function () {
        var act = b.getAttribute('data-act');
        var id  = parseInt(b.getAttribute('data-id'), 10);
        if (act === 'decide')  showDecideForm(id);
        else if (act === 'reflect') showReflectForm(id);
        else if (act === 'delete')  handleDelete(id);
      });
    });
  }

  function findItem(id) {
    for (var i = 0; i < state.items.length; i++) if (state.items[i].idx === id) return state.items[i];
    return null;
  }

  function showDecideForm(id) {
    var item = findItem(id);
    if (!item) return;
    var formBox = document.getElementById('me-dc-form-' + id);
    if (!formBox) return;

    var optsHtml = '';
    if (item.options && item.options.length) {
      optsHtml = '<select name="chosen_select"><option value="">— 선택 —</option>';
      item.options.forEach(function (o) {
        var label = (typeof o === 'string') ? o : (o && o.label ? o.label : '');
        if (!label) return;
        optsHtml += '<option value="' + esc(label) + '">' + esc(label) + '</option>';
      });
      optsHtml += '</select>';
    }

    formBox.innerHTML =
      '<label>선택한 옵션</label>' +
      (optsHtml || '') +
      '<input type="text" name="chosen_other" placeholder="(선택지 외 직접 입력)" maxlength="100">' +
      '<div class="me-dc-form-actions">' +
        '<button type="button" data-x="cancel">취소</button>' +
        '<button type="button" class="primary" data-x="save"><i class="fas fa-check"></i> 저장</button>' +
      '</div>';
    formBox.classList.add('show');

    formBox.querySelector('[data-x="cancel"]').addEventListener('click', function () {
      formBox.classList.remove('show');
      formBox.innerHTML = '';
    });
    formBox.querySelector('[data-x="save"]').addEventListener('click', function () {
      var sel = formBox.querySelector('select[name="chosen_select"]');
      var oth = formBox.querySelector('input[name="chosen_other"]');
      var chosen = (oth && oth.value.trim()) || (sel && sel.value) || '';
      if (!chosen) { alert('선택한 옵션을 입력해주세요.'); return; }
      apiUpdate(id, { chosen_option: chosen }).then(function (resp) {
        if (resp && resp.ok) load();
        else alert('저장 실패: ' + ((resp && resp.message) || '서버 오류'));
      }).catch(function (err) {
        alert('저장 실패: ' + (err && err.message || '네트워크 오류'));
      });
    });
  }

  function showReflectForm(id) {
    var item = findItem(id);
    if (!item) return;
    var formBox = document.getElementById('me-dc-form-' + id);
    if (!formBox) return;

    formBox.innerHTML =
      '<label>실제 결과</label>' +
      '<textarea name="outcome_text" placeholder="실제로 어떻게 되었는가? 어떤 결과를 얻었는가?"></textarea>' +
      '<div class="row">' +
        '<div>' +
          '<label>매칭 라벨</label>' +
          '<select name="match_label">' +
            '<option value="hit">적중 (예측대로)</option>' +
            '<option value="partial" selected>부분 (일부만 맞음)</option>' +
            '<option value="miss">빗나감</option>' +
          '</select>' +
        '</div>' +
        '<div class="narrow">' +
          '<label>매칭 점수 (0~100)</label>' +
          '<input type="number" name="match_score" min="0" max="100" step="1" value="50">' +
        '</div>' +
      '</div>' +
      '<div class="me-dc-form-actions">' +
        '<button type="button" data-x="cancel">취소</button>' +
        '<button type="button" class="primary" data-x="save"><i class="fas fa-flag-checkered"></i> 회고 저장</button>' +
      '</div>';
    formBox.classList.add('show');

    var labelSel = formBox.querySelector('select[name="match_label"]');
    var scoreIn  = formBox.querySelector('input[name="match_score"]');
    // label → score 추천값 자동 설정 (사용자가 손대지 않은 경우)
    var scoreTouched = false;
    if (scoreIn) scoreIn.addEventListener('input', function () { scoreTouched = true; });
    if (labelSel) labelSel.addEventListener('change', function () {
      if (scoreTouched) return;
      var v = labelSel.value;
      if (v === 'hit')         scoreIn.value = 90;
      else if (v === 'partial') scoreIn.value = 50;
      else if (v === 'miss')    scoreIn.value = 10;
    });

    formBox.querySelector('[data-x="cancel"]').addEventListener('click', function () {
      formBox.classList.remove('show');
      formBox.innerHTML = '';
    });
    formBox.querySelector('[data-x="save"]').addEventListener('click', function () {
      var outcome = (formBox.querySelector('textarea[name="outcome_text"]').value || '').trim();
      if (!outcome) { alert('실제 결과를 입력해주세요.'); return; }
      var matchLabel = labelSel.value;
      var matchScore = parseFloat(scoreIn.value);
      if (isNaN(matchScore)) matchScore = (matchLabel === 'hit' ? 90 : matchLabel === 'miss' ? 10 : 50);

      apiUpdate(id, {
        outcome_text: outcome,
        match_label:  matchLabel,
        match_score:  matchScore
      }).then(function (resp) {
        if (resp && resp.ok) load();
        else alert('저장 실패: ' + ((resp && resp.message) || '서버 오류'));
      }).catch(function (err) {
        alert('저장 실패: ' + (err && err.message || '네트워크 오류'));
      });
    });
  }

  function handleDelete(id) {
    var item = findItem(id);
    if (!item) return;
    if (!confirm('이 결정을 삭제할까요?\n\n"' + (item.title || '') + '"\n\n(soft delete — 복구 가능)')) return;
    apiDelete(id).then(function (resp) {
      if (resp && resp.ok) load();
      else alert('삭제 실패: ' + ((resp && resp.message) || '서버 오류'));
    }).catch(function (err) {
      alert('삭제 실패: ' + (err && err.message || '네트워크 오류'));
    });
  }

  // ───────── API ─────────
  function apiList() {
    var url = API_URL + '?state=' + encodeURIComponent(state.filter) + '&limit=50';
    return fetch(url, { method: 'GET', credentials: 'include' })
      .then(handleResp);
  }
  function apiCreate(payload) {
    return fetch(API_URL, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(handleResp);
  }
  function apiUpdate(id, payload) {
    return fetch(API_URL + '?_method=PUT&id=' + id, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(handleResp);
  }
  function apiDelete(id) {
    // Apache가 빈 POST body에 400을 반환하는 경우가 있어 빈 JSON body 명시
    return fetch(API_URL + '?_method=DELETE&id=' + id, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: '{}'
    }).then(handleResp);
  }

  function handleResp(res) {
    return res.json().then(function (j) {
      if (res.status === 401) return { ok: false, message: '로그인이 필요합니다.' };
      if (res.status === 423) {
        state.locked = true;
        updateLockBadge();
        return { ok: false, message: (j && j.error && j.error.message) || '잠금 상태입니다.' };
      }
      if (!res.ok || (j && j.ok === false)) {
        var msg = (j && j.error && (j.error.message || j.error)) || ('HTTP ' + res.status);
        return { ok: false, message: msg };
      }
      return j;
    });
  }

  function updateLockBadge() {
    var b = document.getElementById('me-dc-lock');
    if (!b) return;
    b.classList.toggle('show', !!state.locked);
  }

  // ───────── Load ─────────
  function load() {
    if (state.loading) return;
    state.loading = true;
    var body = document.getElementById('me-dc-body');
    if (body && !state.items.length) {
      body.innerHTML = '<div class="me-dc-loading"><i class="fas fa-spinner fa-spin"></i> 불러오는 중...</div>';
    }

    apiList().then(function (resp) {
      if (!resp || resp.ok === false) {
        renderError(resp && resp.message ? resp.message : '불러오기 실패');
        return;
      }
      state.items   = resp.items || [];
      state.summary = resp.summary || null;
      state.locked  = !!resp.locked;
      updateLockBadge();
      renderSummary(state.summary);
      renderBody();
      var meta = document.getElementById('me-dc-meta');
      if (meta) meta.textContent = '총 ' + (resp.total || state.items.length) + '건 · 필터 ' + state.filter;
    }).catch(function (err) {
      renderError('네트워크 오류: ' + (err && err.message || '알 수 없음'));
    }).then(function () { state.loading = false; });
  }

  // ───────── Public ─────────
  function open(opts) {
    ensureMounted();
    var ov = document.getElementById('me-dc-overlay');
    var sh = document.getElementById('me-dc-sheet');
    if (ov) ov.classList.add('show');
    if (sh) sh.classList.add('show');
    state.open = true;
    if (opts && opts.filter && ['all','pending','decided','reflected'].indexOf(opts.filter) >= 0) {
      state.filter = opts.filter;
      sh.querySelectorAll('#me-dc-tabs button').forEach(function (b) {
        b.classList.toggle('active', b.getAttribute('data-f') === opts.filter);
      });
    }
    load();
  }
  function close() {
    var ov = document.getElementById('me-dc-overlay');
    var sh = document.getElementById('me-dc-sheet');
    if (ov) ov.classList.remove('show');
    if (sh) sh.classList.remove('show');
    state.open = false;
  }
  function refresh() { if (state.open) load(); }

  window.OneMeDecisionsPanel = { open: open, close: close, refresh: refresh, _state: state };
})();
