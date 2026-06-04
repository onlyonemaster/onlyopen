/* ============================================================
 * [ME-B1] 원챗 라이프 아바타 — 운영 모드 전환 로직
 * ------------------------------------------------------------
 * 외부 아바타(public, 고객용) ↔ 나의 아바타(private, 개인용)
 *
 * 저장: localStorage['oneMeMode'] = 'public' | 'private'
 * 이벤트: window dispatchEvent('oneMeModeChanged', {detail:{mode}})
 *
 * 모드는 어디서든 다음으로 읽을 수 있음:
 *   window.OneMeMode.get()      → 'public' | 'private'
 *   window.OneMeMode.isPrivate() → boolean
 *   window.OneMeMode.set('private')
 *
 * 작성: 2026-05-23
 * 의존: 없음 (vanilla JS, 최상위 IIFE로 격리)
 * 충돌방지: 본 파일은 main.js / dm.js / learn.js 와 분리됨
 * ============================================================ */
(function () {
  'use strict';

  var KEY = 'oneMeMode';
  var DEFAULT_MODE = 'public'; // 기본은 외부 모드 (기존 사용자 영향 최소화)
  var MODE_HINTS = {
    'public': '고객·후원자와 대화하는 공개 아바타입니다',
    'private': '나만 보는 아바타 — 인생 데이터 기반 의사결정 동행자'
  };

  // ────────────────────────────────────────────────
  // 공개 API
  // ────────────────────────────────────────────────
  var OneMeMode = {
    get: function () {
      try {
        var m = localStorage.getItem(KEY);
        return (m === 'private' || m === 'public') ? m : DEFAULT_MODE;
      } catch (e) {
        return DEFAULT_MODE;
      }
    },
    isPrivate: function () { return this.get() === 'private'; },
    isPublic:  function () { return this.get() === 'public';  },
    set: function (mode) {
      if (mode !== 'public' && mode !== 'private') return;
      try { localStorage.setItem(KEY, mode); } catch (e) {}
      applyMode(mode, /*notify*/true);
    }
  };
  window.OneMeMode = OneMeMode;

  // ────────────────────────────────────────────────
  // UI 동기화
  // ────────────────────────────────────────────────
  function applyMode(mode, notify) {
    // body class 토글 (CSS 분기용)
    document.body.classList.toggle('one-mode-private', mode === 'private');
    document.body.classList.toggle('one-mode-public',  mode === 'public');

    // 사이드바 버튼 active 토글
    var btnPub = document.getElementById('sbModePublicBtn');
    var btnMe  = document.getElementById('sbModePrivateBtn');
    if (btnPub && btnMe) {
      btnPub.classList.toggle('active', mode === 'public');
      btnMe .classList.toggle('active', mode === 'private');
    }

    // 힌트 텍스트
    var hint = document.getElementById('sbModeHint');
    if (hint) hint.textContent = MODE_HINTS[mode] || '';

    if (notify) {
      try {
        window.dispatchEvent(new CustomEvent('oneMeModeChanged', { detail: { mode: mode } }));
      } catch (e) {
        // IE 등 폴리필 미지원 환경
        var ev = document.createEvent('CustomEvent');
        ev.initCustomEvent('oneMeModeChanged', false, false, { mode: mode });
        window.dispatchEvent(ev);
      }
    }
  }

  // ────────────────────────────────────────────────
  // 이벤트 바인딩
  // ────────────────────────────────────────────────
  function init() {
    // 초기 상태 적용
    applyMode(OneMeMode.get(), /*notify*/false);

    // 버튼 클릭 핸들러
    var card = document.getElementById('sbModeCard');
    if (!card) return; // 사이드바에 카드가 아직 없으면 패스

    card.addEventListener('click', function (e) {
      var btn = e.target.closest('.sb-mode-btn');
      if (!btn) return;
      var mode = btn.getAttribute('data-mode');
      if (!mode) return;
      e.preventDefault();
      e.stopPropagation();
      OneMeMode.set(mode);
    });

    // 디버그 로그
    if (window.console && console.log) {
      console.log('[ME-B1] 운영 모드 토글 활성 — 현재 모드:', OneMeMode.get());
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
