/* ============================================================
 * [ME-B2] 원챗 라이프 아바타 — 채팅 리스트 최상단 '나의 아바타' 핀 카드
 * ------------------------------------------------------------
 * - dm.js의 loadDMRooms() 가 #dmRoomList 를 innerHTML 재작성할 때마다
 *   첫 번째 자식으로 '🧠 나의 아바타' 카드를 자동 삽입한다.
 * - dm.js / main.js 는 절대 건드리지 않는다 (격리 원칙)
 * - MutationObserver 로 #dmRoomList 의 자식 변화를 감시
 * - 카드 클릭 → 현재 운영 모드가 무엇이든 me/ 페이지로 이동
 * - body.one-mode-private 일 때 카드가 보라/금색으로 강조됨 (CSS [ME-B2])
 *
 * 의존: 없음 (OneMeMode 가 있으면 모드별 톤 보정에 활용)
 * 안전성: IIFE, 외부 노출 = window.OneMePinCard 하나뿐
 *
 * 생성: 2026-05-23 / 조은(원챗) × 아리(GenSpark AI Developer)
 * 다음 단계(B-3): me/ 페이지를 실제 개인 아바타 채팅 화면으로 교체
 * ============================================================ */

(function () {
  'use strict';

  var TARGET_ID  = 'dmRoomList';
  var CARD_ID    = 'mePinCard';
  var CARD_HREF  = '/aimessage/onechat/me/';   // B-3 에서 실 채팅 페이지로 교체될 경로
  var DEBUG      = false;

  function log() {
    if (!DEBUG) return;
    try { console.log.apply(console, ['[ME-B2]'].concat([].slice.call(arguments))); } catch (e) {}
  }

  // ── 핀 카드 DOM 생성 ──────────────────────────────────────
  function buildPinCard() {
    var card = document.createElement('div');
    card.className   = 'dm-room-card me-pin-card';
    card.id          = CARD_ID;
    card.setAttribute('data-me-pin', '1');
    card.setAttribute('role', 'button');
    card.setAttribute('tabindex', '0');
    card.setAttribute('aria-label', '나의 아바타 (개인용)');

    card.innerHTML =
      '<div class="dm-room-avatar me-pin-avatar" style="overflow:hidden;position:relative;">' +
        '<i class="fas fa-brain" style="font-size:20px;"></i>' +
        '<span class="me-pin-dot" title="개인 전용"></span>' +
      '</div>' +
      '<div class="dm-room-info">' +
        '<div class="dm-room-name">' +
          '<i class="fas fa-thumbtack dm-pin-icon" style="margin-right:4px;font-size:10px;"></i>' +
          '나의 아바타' +
          '<span class="me-pin-badge">ME</span>' +
        '</div>' +
        '<div class="dm-room-preview me-pin-preview">' +
          '나와의 비공개 대화 · 인생 결정 도우미' +
        '</div>' +
      '</div>' +
      '<div class="dm-room-meta">' +
        '<span class="dm-room-time me-pin-time">항상 옆</span>' +
      '</div>';

    // 클릭 → me 페이지로 이동
    card.addEventListener('click', function () {
      try {
        // private 모드가 아니면 자동 전환 (선택적 — 사용자가 의도해서 클릭한 것이므로)
        if (window.OneMeMode && typeof window.OneMeMode.set === 'function') {
          window.OneMeMode.set('private');
        }
      } catch (e) {}
      // B-3 전까지는 me/ 목업으로 이동, 이후엔 실 채팅 화면으로 자동 교체
      location.href = CARD_HREF;
    });
    // 키보드 접근성
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
    return card;
  }

  // ── #dmRoomList 첫 자식으로 핀 카드 보장 ────────────────────
  function ensurePinCard() {
    var listEl = document.getElementById(TARGET_ID);
    if (!listEl) return;

    var existing = document.getElementById(CARD_ID);

    // 빈 상태 (.dm-loading 만 있을 때) 도 핀 카드는 표시
    // → loading 카드 앞에 핀 카드를 prepend
    if (!existing) {
      var card = buildPinCard();
      // listEl 이 비어있거나 firstChild 가 있으면 그 앞에 삽입
      if (listEl.firstChild) {
        listEl.insertBefore(card, listEl.firstChild);
      } else {
        listEl.appendChild(card);
      }
      log('핀 카드 신규 삽입');
      return;
    }

    // 이미 있는데 첫 자식이 아니면 맨 앞으로 옮긴다 (dm.js 가 innerHTML='' 후 다시 그릴 때 보호)
    if (listEl.firstChild !== existing) {
      listEl.insertBefore(existing, listEl.firstChild);
      log('핀 카드 위치 보정 → 첫 자식으로');
    }
  }

  // ── MutationObserver: #dmRoomList 자식 변화 감시 ───────────
  var observer = null;
  function startObserver() {
    var listEl = document.getElementById(TARGET_ID);
    if (!listEl) return false;

    if (observer) observer.disconnect();

    observer = new MutationObserver(function (mutations) {
      // 핀 카드 자신의 변경은 무시 (무한 루프 방지)
      var meaningful = mutations.some(function (m) {
        if (m.type !== 'childList') return false;
        // 추가/제거된 노드 중 우리 핀 카드만 있는 경우는 무시
        var nodes = [].concat([].slice.call(m.addedNodes), [].slice.call(m.removedNodes));
        return nodes.some(function (n) {
          return !(n.nodeType === 1 && n.id === CARD_ID);
        });
      });
      if (meaningful) ensurePinCard();
    });
    observer.observe(listEl, { childList: true });
    log('MutationObserver 시작');
    return true;
  }

  // ── 초기화 ──────────────────────────────────────────────
  function init() {
    // 1) 즉시 한 번 보장
    ensurePinCard();
    // 2) 감시 시작 (실패 시 짧은 폴링 후 재시도)
    if (!startObserver()) {
      var tries = 0;
      var timer = setInterval(function () {
        tries++;
        if (startObserver() || tries > 20) {
          // 시작 성공 또는 10초 후 포기
          ensurePinCard();
          clearInterval(timer);
        }
      }, 500);
    }
    // 3) 모드 전환 시에도 카드 위치 재보장
    try {
      window.addEventListener('oneMeModeChanged', function () {
        ensurePinCard();
      });
    } catch (e) {}
  }

  // 외부 노출 (수동 강제 보정 / 디버그용)
  window.OneMePinCard = {
    ensure: ensurePinCard,
    rebuild: function () {
      var old = document.getElementById(CARD_ID);
      if (old && old.parentNode) old.parentNode.removeChild(old);
      ensurePinCard();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
