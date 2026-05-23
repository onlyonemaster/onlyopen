/* [ME-C3] OneChat Life Avatar — Dashboard Module
 * ----------------------------------------------------
 * "Life Avatar 상태 거울" 한 화면에 6개 위젯
 *
 * 외부 노출:
 *   window.OneMeDashboard = {
 *     open(),      // 패널 열고 데이터 로드
 *     close(),
 *     refresh(),   // 데이터 다시 로드 (열려있을 때만 의미)
 *   };
 *
 * 의존:
 *   - /aimessage/onechat/api/me_stats.php (GET)
 *   - /aimessage/onechat/css/me_dashboard.css
 *
 * 형제 모듈: me_data_panel (열기 버튼으로 위임 가능)
 * 의도적 격리: dm.js / main.js / avatar_v2.html 미수정.
 *
 * Author : OneChat Life Avatar team
 * Created: 2026-05-23 (C-3)
 */

(function () {
  'use strict';

  if (window.OneMeDashboard) return; // 중복 로드 방지

  // ───────── Config ─────────
  var STATS_API = '/aimessage/onechat/api/me_stats.php';
  var CSS_HREF  = '/aimessage/onechat/css/me_dashboard.css';

  // CSS 자동 로드 (me_data_panel과 동일 패턴)
  function ensureCss() {
    if (document.querySelector('link[data-me-db-css]')) return;
    var link = document.createElement('link');
    link.rel  = 'stylesheet';
    link.href = CSS_HREF + '?v=0523c3';
    link.setAttribute('data-me-db-css', '1');
    document.head.appendChild(link);
  }
  var CAT_LABEL = {
    basic:       '기본',
    childhood:   '유년기',
    diary:       '일기',
    file:        '파일',
    voice:       '음성',
    image:       '이미지',
    fingerprint: '지문',
    palmistry:   '손금',
    physiognomy: '관상',
    saju:        '사주',
    astrology:   '점성',
    decision:    '결정',
    etc:         '기타'
  };
  var SCOPE_LABEL = { private: '비공개', public: '공개', both: '양쪽' };
  var STATUS_LABEL = { active: '활동중', paused: '일시중지', locked: '잠김' };

  // ───────── State ─────────
  var state = {
    mounted: false,
    open: false,
    loading: false,
    data: null,
  };

  // ───────── Utilities ─────────
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
  function esc(s) {
    s = (s == null) ? '' : String(s);
    return s.replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }
  function pct(n, total) {
    if (!total) return 0;
    return Math.round((n / total) * 1000) / 10;
  }

  // ───────── Mount DOM ─────────
  function ensureMounted() {
    if (state.mounted) return;
    ensureCss();

    var ov = el('div', { class: 'me-db-overlay', id: 'me-db-overlay' });
    var sh = el('div', { class: 'me-db-sheet', id: 'me-db-sheet',
                         role: 'dialog', 'aria-modal': 'true',
                         'aria-labelledby': 'me-db-title' });

    sh.innerHTML =
      '<div class="me-db-head">' +
        '<h2 id="me-db-title"><i class="fas fa-chart-pie"></i> 내 아바타 대시보드</h2>' +
        '<span class="me-db-status active" id="me-db-status">활동중</span>' +
        '<button type="button" class="me-db-close" id="me-db-close" aria-label="닫기">' +
          '<i class="fas fa-times"></i>' +
        '</button>' +
      '</div>' +
      '<div class="me-db-body" id="me-db-body">' +
        '<div class="me-db-loading">불러오는 중...</div>' +
      '</div>' +
      '<div class="me-db-foot">' +
        '<div class="left">' +
          '<span id="me-db-meta">—</span>' +
        '</div>' +
        '<div class="right">' +
          '<button type="button" class="ghost" id="me-db-refresh"><i class="fas fa-sync"></i> 새로고침</button>' +
          '<button type="button" id="me-db-open-pool"><i class="fas fa-database"></i> 데이터 풀</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(ov);
    document.body.appendChild(sh);

    ov.addEventListener('click', close);
    sh.querySelector('#me-db-close').addEventListener('click', close);
    sh.querySelector('#me-db-refresh').addEventListener('click', function () {
      load(true);
    });
    sh.querySelector('#me-db-open-pool').addEventListener('click', function () {
      close();
      if (window.OneMeDataPanel && typeof window.OneMeDataPanel.open === 'function') {
        setTimeout(function () { window.OneMeDataPanel.open(); }, 260);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (state.open && e.key === 'Escape') close();
    });

    state.mounted = true;
  }

  // ───────── Render ─────────
  function renderError(msg) {
    var body = document.getElementById('me-db-body');
    if (!body) return;
    body.innerHTML = '<div class="me-db-error"><i class="fas fa-exclamation-triangle"></i> ' + esc(msg) + '</div>';
  }

  function renderEmpty() {
    var body = document.getElementById('me-db-body');
    if (!body) return;
    body.innerHTML =
      '<div class="me-db-empty">' +
        '<p style="font-size:14px;color:#4b5563;margin:0 0 6px;">아직 학습된 데이터가 없습니다.</p>' +
        '<p style="font-size:12px;">"데이터 풀" 버튼을 눌러 첫 한 줄을 기록해보세요.</p>' +
      '</div>';
  }

  function render(d) {
    var body = document.getElementById('me-db-body');
    var statusBadge = document.getElementById('me-db-status');
    var metaEl = document.getElementById('me-db-meta');
    if (!body) return;

    // status badge
    var st = (d.avatar && d.avatar.status) || 'active';
    statusBadge.className = 'me-db-status ' + st;
    statusBadge.textContent = STATUS_LABEL[st] || st;

    // footer meta
    if (metaEl) {
      var when = (d.meta && d.meta.generated_at) ? d.meta.generated_at : '';
      metaEl.textContent = '갱신 ' + when;
    }

    var activeCnt = (d.total && d.total.active) || 0;

    if (activeCnt === 0 && !d.locked) {
      // 잠금 상태가 아니고 진짜 0이면 empty
      renderEmpty();
      return;
    }

    var h = d.health || { score: 0, breakdown:{}, message:'' };
    var scope = d.scope || { private:0, public:0, both:0 };
    var privacy = d.privacy || {};
    var cats = (d.category || []);
    var timeline = (d.timeline || []);
    var recent = (d.recent || []);

    var html = '';

    // ── HERO (Health Ring) ──
    html += heroHtml(h, d);

    // ── Grid cards ──
    html += '<div class="me-db-grid">';
    html += scopeCardHtml(scope, activeCnt);
    html += privacyCardHtml(privacy, activeCnt, !!d.locked);
    html += categoryCardHtml(cats, activeCnt);
    html += sparkCardHtml(timeline);
    html += recentCardHtml(recent, !!d.locked);
    html += '</div>';

    body.innerHTML = html;

    // animate width-driven bars after paint
    requestAnimationFrame(function () {
      var bars = body.querySelectorAll('.me-db-catrow .bar[data-w]');
      bars.forEach(function (b) { b.style.width = b.getAttribute('data-w'); });
      var pbars = body.querySelectorAll('.me-db-privbar > span[data-w]');
      pbars.forEach(function (b) { b.style.width = b.getAttribute('data-w'); });
      var ring = body.querySelector('.ring-fg[data-off]');
      if (ring) ring.setAttribute('stroke-dashoffset', ring.getAttribute('data-off'));
    });
  }

  function heroHtml(h, d) {
    var score = Math.max(0, Math.min(100, h.score || 0));
    var R = 42, C = 2 * Math.PI * R;
    var off = C * (1 - score / 100);
    var br = h.breakdown || {};
    var total = (d.total && d.total.active) || 0;

    var lockBadge = d.locked
      ? ' <span style="display:inline-flex;align-items:center;gap:3px;color:#b91c1c;font-weight:700;font-size:11px;"><i class="fas fa-lock"></i> Panic Lock</span>'
      : '';

    return '' +
      '<div class="me-db-hero">' +
        '<div class="me-db-hero-ring">' +
          '<svg viewBox="0 0 100 100" aria-hidden="true">' +
            '<defs>' +
              '<linearGradient id="me-db-grad" x1="0" y1="0" x2="1" y2="1">' +
                '<stop offset="0%" stop-color="#a855f7"/>' +
                '<stop offset="100%" stop-color="#3b82f6"/>' +
              '</linearGradient>' +
            '</defs>' +
            '<circle class="ring-bg" cx="50" cy="50" r="' + R + '"></circle>' +
            '<circle class="ring-fg" cx="50" cy="50" r="' + R + '" ' +
                    'stroke-dasharray="' + C.toFixed(2) + '" ' +
                    'stroke-dashoffset="' + C.toFixed(2) + '" ' +
                    'data-off="' + off.toFixed(2) + '"></circle>' +
          '</svg>' +
          '<div class="me-db-hero-score">' +
            '<div class="num">' + score + '</div>' +
            '<div class="lbl">HEALTH</div>' +
          '</div>' +
        '</div>' +
        '<div class="me-db-hero-text">' +
          '<div class="title">아바타 형성 진행도' + lockBadge + '</div>' +
          '<div class="msg">' + esc(h.message || '') + '</div>' +
          '<div class="me-db-hero-meta">' +
            '<span>📦 <b>' + total + '</b>개 데이터</span>' +
            '<span>📊 양 <b>' + (br.volume||0) + '</b>/30</span>' +
            '<span>⚖️ 균형 <b>' + (br.balance||0) + '</b>/25</span>' +
            '<span>🛡️ 분포 <b>' + (br.privacy||0) + '</b>/25</span>' +
            '<span>⏱️ 최근 <b>' + (br.recency||0) + '</b>/20</span>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function scopeCardHtml(scope, total) {
    var p = scope.private || 0;
    var b = scope.public  || 0;
    var d = scope.both    || 0;
    var sum = p + b + d;

    // donut: r=34, C=2πr
    var R = 34, C = 2 * Math.PI * R;
    function seg(value, color, prevSum) {
      if (sum === 0) return '';
      var len = (value / sum) * C;
      var offset = -((prevSum / sum) * C);
      return '<circle r="' + R + '" cx="48" cy="48" fill="none" ' +
                'stroke="' + color + '" stroke-width="16" ' +
                'stroke-dasharray="' + len.toFixed(2) + ' ' + (C - len).toFixed(2) + '" ' +
                'stroke-dashoffset="' + offset.toFixed(2) + '"></circle>';
    }

    var donutInner;
    if (sum === 0) {
      donutInner = '<circle r="' + R + '" cx="48" cy="48" fill="none" stroke="#e5e7eb" stroke-width="16"></circle>';
    } else {
      donutInner =
        seg(p, '#a855f7', 0) +
        seg(b, '#3b82f6', p) +
        seg(d, '#fbbf24', p + b);
    }

    return '' +
      '<div class="me-db-card">' +
        '<div class="me-db-card-head">' +
          '<h3><i class="fas fa-circle-half-stroke"></i> Scope 분포</h3>' +
          '<span class="sum">' + sum + '건</span>' +
        '</div>' +
        '<div class="me-db-donut">' +
          '<svg viewBox="0 0 96 96" aria-hidden="true">' + donutInner + '</svg>' +
          '<div class="legend">' +
            '<div class="item"><span class="sw private"></span><span class="nm">비공개</span><span class="c">' + p + ' (' + pct(p, sum) + '%)</span></div>' +
            '<div class="item"><span class="sw public"></span><span class="nm">공개</span><span class="c">' + b + ' (' + pct(b, sum) + '%)</span></div>' +
            '<div class="item"><span class="sw both"></span><span class="nm">양쪽</span><span class="c">' + d + ' (' + pct(d, sum) + '%)</span></div>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function privacyCardHtml(priv, total, locked) {
    var sum = 0;
    [1,2,3,4,5].forEach(function (k) { sum += (priv[String(k)] || priv[k] || 0); });

    var seg = '';
    [1,2,3,4,5].forEach(function (k) {
      var v = priv[String(k)] || priv[k] || 0;
      var w = sum > 0 ? (v / sum) * 100 : 0;
      if (w > 0) {
        seg += '<span class="p' + k + '" data-w="' + w.toFixed(2) + '%" style="width:0">' +
                 (w >= 7 ? v : '') +
               '</span>';
      }
    });
    if (sum === 0) {
      seg = '<span class="p3" style="width:100%;color:#6b7280;background:#e5e7eb;">데이터 없음</span>';
    }

    var lockNote = locked
      ? '<div style="margin-top:6px;font-size:10.5px;color:#b91c1c;"><i class="fas fa-lock"></i> 잠금 중: 4·5단계 항목은 목록에서 숨겨집니다 (집계는 유지)</div>'
      : '';

    return '' +
      '<div class="me-db-card">' +
        '<div class="me-db-card-head">' +
          '<h3><i class="fas fa-shield-halved"></i> Privacy 분포</h3>' +
          '<span class="sum">1~5단계</span>' +
        '</div>' +
        '<div class="me-db-privbar">' + seg + '</div>' +
        '<div class="me-db-privlegend">' +
          '<span class="it"><span class="dt p1"></span>1 공개가능 ' + (priv['1']||priv[1]||0) + '</span>' +
          '<span class="it"><span class="dt p2"></span>2 약공개 ' + (priv['2']||priv[2]||0) + '</span>' +
          '<span class="it"><span class="dt p3"></span>3 보통 ' + (priv['3']||priv[3]||0) + '</span>' +
          '<span class="it"><span class="dt p4"></span>4 민감 ' + (priv['4']||priv[4]||0) + '</span>' +
          '<span class="it"><span class="dt p5"></span>5 최고민감 ' + (priv['5']||priv[5]||0) + '</span>' +
        '</div>' +
        lockNote +
      '</div>';
  }

  function categoryCardHtml(cats, total) {
    // 0이 아닌 것만, count desc로 정렬되어 옴
    var nonZero = cats.filter(function (c) { return c.count > 0; });
    if (nonZero.length === 0) {
      return '' +
        '<div class="me-db-card full">' +
          '<div class="me-db-card-head">' +
            '<h3><i class="fas fa-tags"></i> 카테고리 분포</h3>' +
          '</div>' +
          '<div style="font-size:12px;color:#6b7280;padding:6px 0;">분류된 데이터가 아직 없습니다.</div>' +
        '</div>';
    }
    var top = nonZero.slice(0, 7);
    var rest = nonZero.slice(7);
    var maxV = top[0].count || 1;

    var rows = top.map(function (c) {
      var w = (c.count / maxV) * 100;
      return '<div class="me-db-catrow">' +
                '<span class="nm">' + esc(CAT_LABEL[c.key] || c.key) + '</span>' +
                '<span class="bar-wrap"><span class="bar" data-w="' + w.toFixed(2) + '%"></span></span>' +
                '<span class="c">' + c.count + '</span>' +
             '</div>';
    }).join('');

    var restNote = '';
    if (rest.length > 0) {
      var restSum = rest.reduce(function (s, c) { return s + c.count; }, 0);
      restNote = '<div style="font-size:10.5px;color:#6b7280;margin-top:6px;">+ 그 외 ' + rest.length + '개 카테고리 ' + restSum + '건</div>';
    }

    return '' +
      '<div class="me-db-card full">' +
        '<div class="me-db-card-head">' +
          '<h3><i class="fas fa-tags"></i> 카테고리 분포 (상위 ' + top.length + ')</h3>' +
          '<span class="sum">총 ' + total + '건</span>' +
        '</div>' +
        '<div class="me-db-catlist">' + rows + '</div>' +
        restNote +
      '</div>';
  }

  function sparkCardHtml(timeline) {
    if (!timeline || timeline.length === 0) return '';
    var W = 540, H = 60, PAD = 4;
    var maxV = 0, sum = 0, days7 = 0;
    timeline.forEach(function (t, i) {
      if (t.count > maxV) maxV = t.count;
      sum += t.count;
      if (i >= timeline.length - 7) days7 += t.count;
    });
    if (maxV === 0) maxV = 1;

    var stepX = (W - PAD * 2) / Math.max(1, timeline.length - 1);
    var pts = timeline.map(function (t, i) {
      var x = PAD + stepX * i;
      var y = H - PAD - (t.count / maxV) * (H - PAD * 2);
      return x.toFixed(2) + ',' + y.toFixed(2);
    });
    var lineD = 'M' + pts.join(' L');
    var areaD = lineD + ' L' + (PAD + stepX * (timeline.length - 1)).toFixed(2) + ',' + (H - PAD) +
                ' L' + PAD + ',' + (H - PAD) + ' Z';

    // last point dot
    var last = timeline[timeline.length - 1];
    var lastX = PAD + stepX * (timeline.length - 1);
    var lastY = H - PAD - (last.count / maxV) * (H - PAD * 2);

    return '' +
      '<div class="me-db-card full">' +
        '<div class="me-db-card-head">' +
          '<h3><i class="fas fa-chart-line"></i> 최근 30일 활동</h3>' +
          '<span class="sum">총 ' + sum + '건</span>' +
        '</div>' +
        '<svg class="me-db-spark" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" aria-hidden="true">' +
          '<defs>' +
            '<linearGradient id="me-db-spark-grad" x1="0" y1="0" x2="0" y2="1">' +
              '<stop offset="0%" stop-color="#a855f7" stop-opacity="0.32"/>' +
              '<stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>' +
            '</linearGradient>' +
          '</defs>' +
          '<path d="' + areaD + '" fill="url(#me-db-spark-grad)"></path>' +
          '<path d="' + lineD + '" fill="none" stroke="#a855f7" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"></path>' +
          '<circle cx="' + lastX.toFixed(2) + '" cy="' + lastY.toFixed(2) + '" r="3" fill="#a855f7"></circle>' +
        '</svg>' +
        '<div class="me-db-spark-meta">' +
          '<span>30일 전</span>' +
          '<span>최근 7일 <b>' + days7 + '</b>건</span>' +
          '<span>오늘 <b>' + (last.count || 0) + '</b>건</span>' +
        '</div>' +
      '</div>';
  }

  function recentCardHtml(recent, locked) {
    if (!recent || recent.length === 0) {
      if (locked) {
        return '<div class="me-db-card full">' +
                 '<div class="me-db-card-head"><h3><i class="fas fa-clock-rotate-left"></i> 최근 기록</h3></div>' +
                 '<div style="font-size:12px;color:#b91c1c;"><i class="fas fa-lock"></i> 잠금 상태에서는 민감(4·5) 항목이 숨겨집니다.</div>' +
               '</div>';
      }
      return '';
    }
    var rows = recent.map(function (r) {
      return '<div class="row">' +
                '<span class="dot s-' + (r.scope || 'private') + '"></span>' +
                '<span class="ttl">' + esc(r.title || '(제목 없음)') + ' ' +
                  '<span style="color:#9ca3af;font-size:10.5px;">· ' + esc(CAT_LABEL[r.category] || r.category) + '</span>' +
                '</span>' +
                '<span class="meta">' + esc(r.created_at) + '</span>' +
             '</div>';
    }).join('');

    return '' +
      '<div class="me-db-card full">' +
        '<div class="me-db-card-head">' +
          '<h3><i class="fas fa-clock-rotate-left"></i> 최근 기록</h3>' +
          '<span class="sum">' + recent.length + '건</span>' +
        '</div>' +
        '<div class="me-db-recent">' + rows + '</div>' +
      '</div>';
  }

  // ───────── Network ─────────
  function load(force) {
    if (state.loading) return;
    state.loading = true;

    var body = document.getElementById('me-db-body');
    if (body && (!state.data || force)) {
      body.innerHTML = '<div class="me-db-loading"><i class="fas fa-spinner fa-spin"></i> 불러오는 중...</div>';
    }

    fetch(STATS_API, { method: 'GET', credentials: 'include' })
      .then(function (res) {
        if (res.status === 401) {
          throw new Error('AUTH');
        }
        return res.json().then(function (j) { return { status: res.status, json: j }; });
      })
      .then(function (resp) {
        if (resp.status >= 400 || !resp.json || resp.json.ok !== true) {
          var msg = (resp.json && (resp.json.message || resp.json.error)) || ('HTTP ' + resp.status);
          throw new Error(msg);
        }
        state.data = resp.json;
        render(resp.json);
      })
      .catch(function (err) {
        if (err && err.message === 'AUTH') {
          renderError('로그인이 필요합니다.');
        } else {
          renderError('대시보드를 불러오지 못했습니다: ' + (err && err.message || '알 수 없는 오류'));
        }
      })
      .then(function () {
        state.loading = false;
      });
  }

  // ───────── Public API ─────────
  function open() {
    ensureMounted();
    var ov = document.getElementById('me-db-overlay');
    var sh = document.getElementById('me-db-sheet');
    if (!ov || !sh) return;
    ov.classList.add('show');
    sh.classList.add('show');
    state.open = true;
    load(false);
  }
  function close() {
    var ov = document.getElementById('me-db-overlay');
    var sh = document.getElementById('me-db-sheet');
    if (ov) ov.classList.remove('show');
    if (sh) sh.classList.remove('show');
    state.open = false;
  }
  function refresh() {
    if (state.open) load(true);
  }

  window.OneMeDashboard = {
    open: open,
    close: close,
    refresh: refresh,
    _state: state // 디버그 전용
  };
})();
