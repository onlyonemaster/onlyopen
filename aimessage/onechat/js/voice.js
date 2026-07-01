/* ============================================================
   voice.js  -  onechat 음성 기능 (STT + TTS)  범용 버전
   2026-04-30  init(options)으로 어느 채팅창에도 적용 가능
   ============================================================ */
(function () {
  'use strict';

  /* ── 기본 옵션 (onechat 운영자용 ID) ── */
  var defaults = {
    inputId:     'ciTextarea',
    micBtnId:    'ciMicBtn',
    voiceBarId:  'ciVoiceBar',
    cancelBtnId: 'ciVoiceCancel',
    ttsBtnId:    'voiceTtsToggle',
    smsIdx:      0,
  };

  /* ── 상태 ── */
  var cfg = Object.assign({}, defaults);
  var state = {
    voiceEnabled: false,
    ttsEnabled:   false,
    recording:    false,
    mediaRec:     null,
    audioChunks:  [],
    ttsAudio:     null,
  };

  /* ── DOM 헬퍼 ── */
  function dom(id) { return document.getElementById(id); }
  function el(key) { return dom(cfg[key]); }

  /* ── 외부 초기화 (수신자 페이지에서 호출) ── */
  function init(options) {
    cfg = Object.assign({}, defaults, options || {});
    bindEvents();
    if (cfg.smsIdx) loadConfig(cfg.smsIdx);
  }

  /* ── vt.kiam.kr 설정 로드 ── */
  function loadConfig(smsIdx) {
    if (!smsIdx) return;
    cfg.smsIdx = smsIdx;
    state.voiceEnabled = false;
    state.ttsEnabled   = false;
    hideTtsBtn();

    fetch('https://vt.kiam.kr/api/v1/voice/config.php?sms_idx=' + smsIdx, {
      headers: { 'X-API-Key': 'vt_onechat_9afdc815132cae0174f5b5109a658db3' }
    })
    .then(function(r) { return r.ok ? r.json() : null; })
    .then(function(resp) {
      // API 응답 구조: { success:true, data: { voice_enabled, tts_enabled, ... } }
      var data = (resp && resp.data) ? resp.data : resp;
      if (!data || !data.voice_enabled) return;
      state.voiceEnabled = true;
      showMicBtn();
      showTtsBtn(data.tts_enabled || false);
    })
    .catch(function() {});
  }

  /* ── 마이크 버튼 표시 ── */
  function showMicBtn() {
    var btn = el('micBtnId');
    if (btn) btn.style.display = '';
  }

  /* ── TTS 버튼 표시/숨김 ── */
  function showTtsBtn(ttsOn) {
    var btn = el('ttsBtnId');
    if (!btn) return;
    btn.style.display = '';
    state.ttsEnabled = !!ttsOn;
    refreshTtsBtn();
  }
  function hideTtsBtn() {
    var btn = el('ttsBtnId');
    if (btn) btn.style.display = 'none';
  }
  function refreshTtsBtn() {
    var btn = el('ttsBtnId');
    if (!btn) return;
    var icon = btn.querySelector('i');
    if (state.ttsEnabled) {
      btn.classList.add('tts-on');
      btn.title = 'TTS ON — 클릭하면 OFF';
      if (icon) icon.className = 'fas fa-volume-high';
    } else {
      btn.classList.remove('tts-on');
      btn.title = 'TTS OFF — 클릭하면 ON';
      if (icon) icon.className = 'fas fa-volume-xmark';
    }
  }

  /* ── TTS 토글 클릭 ── */
  function onTtsToggle() {
    state.ttsEnabled = !state.ttsEnabled;
    refreshTtsBtn();
    if (!state.ttsEnabled && state.ttsAudio) {
      state.ttsAudio.pause();
      state.ttsAudio = null;
    }
  }

  /* ── 마이크 버튼 클릭 ── */
  function onMicClick() {
    if (!state.voiceEnabled) return;
    if (state.recording) stopRecording();
    else startRecording();
  }

  /* ── 녹음 시작 ── */
  function startRecording() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('이 브라우저는 마이크를 지원하지 않습니다.');
      return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true })
    .then(function(stream) {
      state.audioChunks = [];
      state.mediaRec = new MediaRecorder(stream);
      state.mediaRec.ondataavailable = function(e) {
        if (e.data.size > 0) state.audioChunks.push(e.data);
      };
      state.mediaRec.onstop = function() {
        stream.getTracks().forEach(function(t) { t.stop(); });
        sendSTT();
      };
      state.mediaRec.start();
      state.recording = true;
      setMicUI(true);
    })
    .catch(function(err) {
      alert('마이크 접근 권한이 필요합니다.\n(' + err.message + ')');
    });
  }

  /* ── 녹음 중지 ── */
  function stopRecording() {
    if (state.mediaRec && state.recording) {
      state.mediaRec.stop();
      state.recording = false;
      setMicUI(false);
    }
  }

  /* ── 마이크 UI 전환 ── */
  function setMicUI(on) {
    var micBtn   = el('micBtnId');
    var voiceBar = el('voiceBarId');
    if (micBtn)   on ? micBtn.classList.add('recording') : micBtn.classList.remove('recording');
    if (voiceBar) voiceBar.style.display = on ? 'flex' : 'none';
  }

  /* ── STT 전송 ── */
  function sendSTT() {
    if (!state.audioChunks.length) return;
    var blob  = new Blob(state.audioChunks, { type: 'audio/webm' });
    var fd    = new FormData();
    fd.append('audio', blob, 'voice.webm');
    var input = el('inputId');

    if (input) { input.placeholder = '변환 중...'; input.disabled = true; }

    fetch('/aimessage/onechat/api/stt_proxy.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (input) { input.disabled = false; input.placeholder = '메시지를 입력하세요...'; }
      if (data && data.text) {
        if (input) {
          input.value = data.text;
          input.dispatchEvent(new Event('input'));
          input.focus();
        }
      }
    })
    .catch(function() {
      if (input) { input.disabled = false; input.placeholder = '메시지를 입력하세요...'; }
    });
  }

  /* ── TTS 재생 ── */
  function speakText(text) {
    if (!state.ttsEnabled || !text) return;
    if (state.ttsAudio) { state.ttsAudio.pause(); state.ttsAudio = null; }

    fetch('/aimessage/onechat/api/tts_openai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: text })
    })
    .then(function(r) { return r.ok ? r.blob() : null; })
    .then(function(blob) {
      if (!blob) return;
      var url = URL.createObjectURL(blob);
      state.ttsAudio = new Audio(url);
      state.ttsAudio.onended = function() { URL.revokeObjectURL(url); state.ttsAudio = null; };
      state.ttsAudio.play();
    })
    .catch(function() {});
  }

  /* ── 이벤트 바인딩 ── */
  function bindEvents() {
    var micBtn    = el('micBtnId');
    var ttsBtn    = el('ttsBtnId');
    var cancelBtn = el('cancelBtnId');

    if (micBtn && !micBtn._vcBound) {
      micBtn.addEventListener('click', onMicClick);
      micBtn._vcBound = true;
    }
    if (ttsBtn && !ttsBtn._vcBound) {
      ttsBtn.addEventListener('click', onTtsToggle);
      ttsBtn._vcBound = true;
    }
    if (cancelBtn && !cancelBtn._vcBound) {
      cancelBtn.addEventListener('click', function() { stopRecording(); });
      cancelBtn._vcBound = true;
    }
  }

  /* ── onechat 운영자용 hook (채팅창 열릴 때 자동 연결) ── */
  function hookVoiceLoad() {
    function wrapFn(fnName) {
      var orig = window[fnName];
      if (typeof orig !== 'function' || orig._vcHooked) return;
      window[fnName] = function() {
        var result = orig.apply(this, arguments);
        setTimeout(function() {
          bindEvents();
          var idx = window.currentChatInfo && window.currentChatInfo.sms_idx;
          if (idx) loadConfig(idx);
        }, 400);
        return result;
      };
      window[fnName]._vcHooked = true;
    }
    wrapFn('openChatScreen');
    wrapFn('openReceivedChatScreen');
  }

  /* ── 공개 API ── */
  window.voiceChat = {
    init:       init,
    loadConfig: loadConfig,
    speakText:  speakText,
    bindEvents: bindEvents,
  };

  /* ── onechat 페이지에서만 hook 자동 실행 ── */
  if (document.getElementById('ciTextarea') !== null ||
      document.getElementById('chatScreen') !== null) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', hookVoiceLoad);
    } else {
      hookVoiceLoad();
    }
  }

})();
