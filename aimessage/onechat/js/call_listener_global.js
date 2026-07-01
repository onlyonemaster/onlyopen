/* call_listener_global.js
 * 사이트 전체에서 원챗 통화 초대 알림을 수신하는 글로벌 리스너
 * - 모든 페이지에서 동작 (원챗 페이지 / 통화방 페이지는 제외 — 자체 알림이 있음)
 * - 로그인 사용자만 폴링 (첫 401/403 응답 시 자동 중단)
 * - 신호음 + Browser Notification (tag로 중복 방지)
 * - 알림 클릭 → 통화 페이지 새 탭으로 열기
 * - 10종 내장 벨소리 + 업로드 음성파일 지원 (localStorage 'onechat_ringtone_id'로 선택)
 * - 음성파일: 5초 간격 3회 재생 / 내장 벨소리: 최대 10사이클 반복
 */
(function() {
  // 중복 로드 방지
  if (window._callListenerGlobalLoaded) return;
  window._callListenerGlobalLoaded = true;
  console.log('[call-global] 로드됨', location.pathname);

  // 가드는 RINGTONES 등록 후로 이동 — 설정 페이지에서도 데이터 사용 가능

  // ── 오디오 컨텍스트 ───────────────────────────────────────
  var _audioCtx = null;
  function _ensureAudio() {
    if (_audioCtx) return _audioCtx;
    try { _audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) {}
    return _audioCtx;
  }
  function _unlock() {
    _ensureAudio();
    // 사용자 첫 제스처 시 알림 권한 요청 (default 상태에서만)
    try {
      if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
      }
    } catch(e) {}
    document.removeEventListener('click', _unlock, true);
    document.removeEventListener('touchstart', _unlock, true);
  }
  document.addEventListener('click', _unlock, true);
  document.addEventListener('touchstart', _unlock, true);

  // ── 폴링/알림 가드: RINGTONES 데이터·미리듣기는 어디서든 사용 가능하지만
  //   실제 신호음/Notification 폴링은 원챗·통화방 페이지에서는 스킵
  //   (각자 자체 알림이 있음)
  var _skipPolling = (
    location.pathname.indexOf('/aimessage/onechat/') === 0 ||
    location.pathname.indexOf('/admin/onechat/video_room.php') === 0
  );

  // ── 10종 벨소리 패턴 (Web Audio 합성) ────────────────────
  // 각 패턴은 ctx를 받아서 한 사이클을 재생하는 함수
  // 반환값: 다음 사이클까지의 ms (반복 간격)
  // 음악적 노트 재생 (ADSR + 화음 배음으로 마림바·차임벨 같은 부드러운 음색)
  function _toneAt(ctx, freq, vol, startOffset, duration, type) {
    var osc = ctx.createOscillator(), gain = ctx.createGain();
    osc.type = type || 'triangle'; osc.frequency.value = freq;
    osc.connect(gain); gain.connect(ctx.destination);
    var t = ctx.currentTime + startOffset;
    // ADSR envelope: 짧은 attack + 자연스러운 decay (음악적 느낌)
    gain.gain.setValueAtTime(0, t);
    gain.gain.linearRampToValueAtTime(vol, t + 0.015);
    gain.gain.exponentialRampToValueAtTime(Math.max(vol * 0.4, 0.001), t + duration * 0.5);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + duration);
    osc.start(t); osc.stop(t + duration + 0.02);
  }

  // 화음(배음) 노트: 기본음 + 5도 + 옥타브로 풍부한 음색
  function _noteAt(ctx, freq, vol, startOffset, duration) {
    _toneAt(ctx, freq,        vol * 0.65, startOffset, duration, 'triangle');
    _toneAt(ctx, freq * 2,    vol * 0.25, startOffset, duration * 0.7, 'sine');     // 옥타브
    _toneAt(ctx, freq * 1.5,  vol * 0.15, startOffset, duration * 0.6, 'sine');     // 5도
  }

  // 음표(MIDI 노트 번호 → 주파수)
  var N = {
    C4: 261.63, D4: 293.66, E4: 329.63, F4: 349.23, G4: 392.00, A4: 440.00, B4: 493.88,
    C5: 523.25, D5: 587.33, E5: 659.25, F5: 698.46, G5: 783.99, A5: 880.00, B5: 987.77,
    C6: 1046.5, D6: 1174.7, E6: 1318.5,
    // 반음 (#)
    'Cs5': 554.37, 'Ds5': 622.25, 'Fs5': 739.99, 'Gs5': 830.61, 'As5': 932.33
  };

  var RINGTONES = [
    { id: 'chime',      name: '차임벨',     desc: '도-미-솔 화음 (디폴트)', interval: 2200,
      play: function(ctx) {
        _noteAt(ctx, N.C5, 0.30, 0,    0.55);
        _noteAt(ctx, N.E5, 0.28, 0.18, 0.55);
        _noteAt(ctx, N.G5, 0.30, 0.36, 0.75);
      }
    },
    { id: 'twinkle',    name: '트윙클',     desc: '작은 별이 반짝',         interval: 3200,
      play: function(ctx) {
        // 도 도 솔 솔 라 라 솔
        var ns = [N.C5, N.C5, N.G5, N.G5, N.A5, N.A5, N.G5];
        ns.forEach(function(n, i) { _noteAt(ctx, n, 0.26, i * 0.32, 0.30); });
      }
    },
    { id: 'ode',        name: '환희의 송가', desc: '베토벤 9번 모티프',     interval: 3000,
      play: function(ctx) {
        // 미-미-파-솔-솔-파-미-레
        var ns = [N.E5, N.E5, N.F5, N.G5, N.G5, N.F5, N.E5, N.D5];
        ns.forEach(function(n, i) { _noteAt(ctx, n, 0.24, i * 0.28, 0.26); });
      }
    },
    { id: 'fur_elise',  name: 'Für Elise', desc: '베토벤 엘리제를 위하여', interval: 3000,
      play: function(ctx) {
        // E-D#-E-D#-E-B-D-C-A
        var ns = [N.E5, N.Ds5, N.E5, N.Ds5, N.E5, N.B4, N.D5, N.C5, N.A4];
        ns.forEach(function(n, i) { _noteAt(ctx, n, 0.22, i * 0.20, 0.22); });
      }
    },
    { id: 'canon',      name: '카논',       desc: '파헬벨 카논 도입부',     interval: 3400,
      play: function(ctx) {
        // 도-솔-라-미 (D Major 변형)
        var ns = [N.D5, N.A4, N.B4, N.Fs5, N.G5, N.D5, N.G5, N.A4];
        ns.forEach(function(n, i) { _noteAt(ctx, n, 0.24, i * 0.32, 0.30); });
      }
    },
    { id: 'music_box',  name: '오르골',     desc: '꿈꾸는 듯한 오르골',     interval: 3200,
      play: function(ctx) {
        // 도-미-솔-도(높)-솔-미-도
        _noteAt(ctx, N.C5,  0.24, 0,    0.30);
        _noteAt(ctx, N.E5,  0.24, 0.30, 0.30);
        _noteAt(ctx, N.G5,  0.24, 0.60, 0.30);
        _noteAt(ctx, N.C6,  0.30, 0.90, 0.50);
        _noteAt(ctx, N.G5,  0.20, 1.45, 0.25);
        _noteAt(ctx, N.E5,  0.20, 1.70, 0.25);
        _noteAt(ctx, N.C5,  0.24, 1.95, 0.45);
      }
    },
    { id: 'cute',       name: '귀여운 팝',  desc: '도-라-솔-도 빠른 팝',    interval: 2200,
      play: function(ctx) {
        _noteAt(ctx, N.C5, 0.26, 0,    0.18);
        _noteAt(ctx, N.A4, 0.26, 0.20, 0.18);
        _noteAt(ctx, N.G4, 0.26, 0.40, 0.20);
        _noteAt(ctx, N.C5, 0.30, 0.65, 0.40);
      }
    },
    { id: 'bell',       name: '성당 종',    desc: '저음 종 울림',           interval: 3000,
      play: function(ctx) {
        // 종소리: 풍부한 배음 + 긴 decay
        _toneAt(ctx, N.C4, 0.35, 0,    1.6, 'sine');
        _toneAt(ctx, N.G4, 0.20, 0,    1.4, 'sine');
        _toneAt(ctx, N.E5, 0.12, 0,    1.0, 'triangle');
        _toneAt(ctx, N.C4, 0.30, 1.0,  1.6, 'sine');
        _toneAt(ctx, N.G4, 0.18, 1.0,  1.4, 'sine');
      }
    },
    { id: 'pop',        name: '팝 멜로디',  desc: '경쾌한 팝 시퀀스',       interval: 2800,
      play: function(ctx) {
        var ns = [N.G4, N.C5, N.E5, N.G5, N.E5, N.C5];
        ns.forEach(function(n, i) { _noteAt(ctx, n, 0.24, i * 0.22, 0.24); });
      }
    },
    { id: 'soft_jazz',  name: '부드러운 재즈', desc: '재즈 화음',         interval: 2800,
      play: function(ctx) {
        // Maj7 화음 분산
        _noteAt(ctx, N.C5, 0.22, 0,    0.5);
        _noteAt(ctx, N.E5, 0.22, 0.18, 0.5);
        _noteAt(ctx, N.G5, 0.22, 0.36, 0.5);
        _noteAt(ctx, N.B5, 0.24, 0.54, 0.7);
      }
    }
  ];

  window.ONECHAT_RINGTONES = RINGTONES; // 설정 UI에서 참조 가능

  function _getCurrentRingtone() {
    var saved = null;
    try { saved = localStorage.getItem('onechat_ringtone_id'); } catch(e) {}
    var rt = RINGTONES.find(function(r) { return r.id === saved; });
    return rt || RINGTONES.find(function(r) { return r.id === 'chime'; }) || RINGTONES[0];
  }

  // ── 파일 기반 알림음 (음성파일) 헬퍼 ──────────────────────
  function _isFileRingtone(id) {
    return typeof id === 'string' && id.indexOf('file:') === 0;
  }
  function _getFileUrl(id) {
    // id = "file:10_원챗전화왔어요.mp3" → URL 생성
    var bn = id.replace(/^file:/, '');
    return '/aimessage/onechat/sounds/' + encodeURIComponent(bn);
  }

  var _voiceAudio   = null; // 현재 재생 중인 Audio 객체
  var _voiceTimer   = null; // 재생 간격 타이머
  var _voiceCount   = 0;    // 재생 횟수
  var _VOICE_MAX    = 3;    // 최대 3회
  var _VOICE_GAP_MS = 5000; // 재생 간격 5초

  function _stopVoice() {
    if (_voiceTimer)  { clearTimeout(_voiceTimer); _voiceTimer = null; }
    if (_voiceAudio)  { try { _voiceAudio.pause(); _voiceAudio.currentTime = 0; } catch(e) {} _voiceAudio = null; }
    _voiceCount = 0;
  }

  function _playVoiceFile(url) {
    _stopVoice();
    _voiceCount = 0;
    function _once() {
      if (_voiceCount >= _VOICE_MAX) { _stopVoice(); return; }
      _voiceCount++;
      var audio = new Audio(url);
      _voiceAudio = audio;
      audio.volume = 1.0;
      audio.play().catch(function(e) { console.warn('[call-global] 음성파일 재생 실패', e); });
      audio.onended = function() {
        _voiceAudio = null;
        if (_voiceCount < _VOICE_MAX) {
          _voiceTimer = setTimeout(_once, _VOICE_GAP_MS);
        } else {
          _stopVoice();
        }
      };
      audio.onerror = function() {
        console.warn('[call-global] 음성파일 로드 실패:', url);
        _stopVoice();
      };
    }
    _once();
  }

  // ── 신호음 재생 ───────────────────────────────────────────
  var _ringTimer = null;
  var _ringCount = 0;
  var _MAX_RINGS = 10;

  function _playRingCycle(ctx, rt, mode) {
    if (!ctx || _ringCount >= _MAX_RINGS) { _stopRing(); return; }
    _ringCount++;
    try { rt.play(ctx, mode); } catch(e) { console.log('[call-global] ring play error', e); }
    _ringTimer = setTimeout(function() { _playRingCycle(ctx, rt, mode); }, rt.interval || 1600);
  }

  function _stopRing() {
    if (_ringTimer) { clearTimeout(_ringTimer); _ringTimer = null; }
    _stopVoice(); // 음성파일도 함께 중단
  }
  window.stopGlobalCallRingtone = _stopRing;
  window.playGlobalCallRingtone = _playRing;  // dm.js에서도 선택 알림음 사용 가능 (2026-05-16)

  function _playRing(mode) {
    _stopRing();
    _ringCount = 0;
    var savedId = null;
    try { savedId = localStorage.getItem('onechat_ringtone_id'); } catch(e) {}
    // 개인 설정 없으면 서버 기본 알림음 사용 (2026-05-16)
    if (!savedId) savedId = 'file:원챗에서전화왔어요.mp3';

    if (_isFileRingtone(savedId)) {
      // 음성파일: 5초 간격 3회 재생
      var url = _getFileUrl(savedId);
      _playVoiceFile(url);
    } else {
      // 내장 Web Audio 벨소리
      var ctx = _ensureAudio();
      if (!ctx) return;
      var rt = _getCurrentRingtone();
      var start = function() { _playRingCycle(ctx, rt, mode); };
      if (ctx.state === 'suspended') { ctx.resume().then(start).catch(function(){}); }
      else { start(); }
    }
    if (navigator.vibrate) navigator.vibrate([600, 300, 600, 300, 600]);
  }
  window.previewGlobalRingtone = function(rtId, mode) {
    // 설정 UI에서 미리듣기용
    if (_isFileRingtone(rtId)) {
      // 음성파일 미리듣기: 1회만 재생
      _stopVoice();
      var url = _getFileUrl(rtId);
      var audio = new Audio(url);
      _voiceAudio = audio;
      audio.volume = 1.0;
      audio.play().catch(function(e) { console.warn('[call-global] 미리듣기 실패', e); });
      audio.onended = function() { _voiceAudio = null; };
      return;
    }
    var rt = RINGTONES.find(function(r) { return r.id === rtId; });
    if (!rt) return;
    var ctx = _ensureAudio();
    if (!ctx) return;
    var start = function() { try { rt.play(ctx, mode || 'video'); } catch(e) {} };
    if (ctx.state === 'suspended') { ctx.resume().then(start).catch(function(){}); }
    else { start(); }
  };
  window.stopGlobalRingtonePreview = function() {
    _stopVoice();
    _stopRing();
  };

  // ── Browser Notification 팝업 ───────────────────────────
  function _showNotificationActual(mode, callUrl) {
    var label = mode === 'voice' ? '음성통화' : '화상통화';
    try {
      var n = new Notification(label + ' 초대 (DM)', {
        body: '운영자가 ' + label + '에 초대했습니다. 클릭하여 입장하세요.',
        icon: '/favicon.ico',
        tag: 'onechat-call-invite',
        renotify: false,
        requireInteraction: true
      });
      n.onclick = function(ev) {
        try { if (ev && ev.preventDefault) ev.preventDefault(); } catch(e) {}
        _stopRing();
        try { window.focus(); } catch(e) {}
        if (callUrl) {
          var _a = document.createElement('a');
          _a.href = callUrl;
          _a.target = '_blank';
          _a.rel = 'noopener';
          document.body.appendChild(_a);
          _a.click();
          setTimeout(function() { try { document.body.removeChild(_a); } catch(e) {} }, 100);
        }
        try { n.close(); } catch(e) {}
      };
    } catch(e) {}
  }

  function _showNotification(mode, callUrl) {
    // 반환값: true = Chrome 알림 표시됨, false = 표시 안 됨 (배너 fallback 필요, 2026-05-16)
    if (!('Notification' in window)) return false;
    if (Notification.permission === 'granted') {
      _showNotificationActual(mode, callUrl);
      return true;
    }
    if (Notification.permission === 'default') {
      // 권한 요청 → granted 되면 즉시 표시 (비동기라 배너는 별도 처리)
      try {
        var p = Notification.requestPermission();
        if (p && typeof p.then === 'function') {
          p.then(function(perm) {
            if (perm === 'granted') _showNotificationActual(mode, callUrl);
          });
        }
      } catch(e) {}
      return false;
    }
    return false; // denied
  }

  // ── 폴링: 미응답 통화 초대 감지 ──────────────────────────
  var _pollInterval = null;
  var _seenKeys = new Map(); // key → timestamp (Opus 2026-05-16: 메모리 누수 방지)
  // sessionStorage에서 이미 처리한 키 복원 → 페이지 이동 후 재알림 방지 (2026-05-16)
  (function() {
    try {
      var _stored = sessionStorage.getItem('_callSeenKeys');
      if (_stored) {
        JSON.parse(_stored).forEach(function(item) { _seenKeys.set(item[0], item[1]); });
      }
    } catch(e) {}
  })();
  function _addSeenKey(key) {
    _seenKeys.set(key, Date.now());
    try {
      var _arr = [];
      _seenKeys.forEach(function(ts, k) { _arr.push([k, ts]); });
      sessionStorage.setItem('_callSeenKeys', JSON.stringify(_arr));
    } catch(e) {}
  }

  // 24시간 지난 key 자동 정리 (1시간마다)
  setInterval(function() {
    var cutoff = Date.now() - 24 * 60 * 60 * 1000;
    var removed = 0;
    _seenKeys.forEach(function(ts, k) {
      if (ts < cutoff) { _seenKeys.delete(k); removed++; }
    });
    if (removed > 0) console.log('[call-global] _seenKeys 정리:', removed, '개 제거');
  }, 60 * 60 * 1000);

  async function _checkPendingCalls() {
    try {
      var res = await fetch('/aimessage/onechat/api/dm/dm_rooms.php', {
        credentials: 'include',
        cache: 'no-cache'
      });
      if (res.status === 401 || res.status === 403) {
        console.log('[call-global] 비로그인 → 폴링 중단');
        if (_pollInterval) { clearInterval(_pollInterval); _pollInterval = null; }
        return false;
      }
      if (!res.ok) { console.log('[call-global] dm_rooms HTTP', res.status); return true; }
      var data = await res.json();
      if (!data || !data.success || !Array.isArray(data.rooms)) {
        console.log('[call-global] 응답 데이터 비정상', data);
        return true;
      }
      var now = Date.now();
      for (var i = 0; i < data.rooms.length; i++) {
        var r = data.rooms[i];
        if (!r.unread_count || r.unread_count <= 0) continue;
        if (!/(음성통화|화상통화)\s*참여하기:|(음성|화상)\s*대화실:/.test(r.last_msg || '')) continue;
        var t = new Date((r.last_msg_at || '').replace(' ', 'T')).getTime();
        if (isNaN(t)) t = new Date(r.last_msg_at || 0).getTime();
        // key: call_room_uuid 우선 (방마다 고유) → 없으면 URL → 없으면 room_id 폴백
        var key;
        if (r.call_room_uuid) {
          key = 'vcall_uuid_' + r.call_room_uuid;
        } else {
          var _urlM2 = (r.last_msg || '').match(/참여하기:\s*(\S+)/);
          key = _urlM2 ? ('vcall_' + _urlM2[1]) : ('vcall_rid_' + String(r.room_id));
        }
        if (now - t < 5 * 60 * 1000 && !_seenKeys.has(key)) {
          _addSeenKey(key);
          var callUrl, mode;
          if (r.call_room_uuid && r.room_status !== 'ended') {
            mode    = r.call_mode || 'video';
            callUrl = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(r.call_room_uuid) + '&mode=' + mode;
          } else {
            var urlMatch = (r.last_msg || '').match(/참여하기:\s*(\S+)/);
            callUrl = urlMatch ? urlMatch[1] : ('/aimessage/onechat/?room=' + encodeURIComponent(r.room_id));
            var msgText = r.last_msg || '';
            mode = (msgText.indexOf('음성통화') !== -1 || msgText.indexOf('음성 대화실') !== -1) ? 'voice' : 'video';
          }
          console.log('[call-global] 통화 초대 감지', { mode: mode, url: callUrl, room: r.room_id });
          _playRing(mode);
          // Chrome 알림 없으면 배너만 표시 (중복 방지, 2026-05-16)
          if (!_showNotification(mode, callUrl)) _showInPageBanner(mode, callUrl, r);
          break;
        }
      }
      return true;
    } catch(e) { console.log('[call-global] 체크 중 오류', e); return true; }
  }

  // ── 페이지 내 배너 (알림 클릭이 안 되는 경우의 안전망) ──────────────
  function _showInPageBanner(mode, callUrl, room) {
    var existing = document.getElementById('_call_invite_banner');
    if (existing) existing.remove();
    var label = mode === 'voice' ? '🎙️ 음성통화' : '📹 화상통화';
    var fromName = (room && room.other && room.other.mem_name) ? room.other.mem_name : '상대방';
    var div = document.createElement('div');
    div.id = '_call_invite_banner';
    div.style.cssText = [
      'position:fixed','top:20px','right:20px','background:linear-gradient(135deg,#4caf50,#45a049)',
      'color:#fff','padding:14px 40px 14px 18px','border-radius:10px',
      'box-shadow:0 8px 28px rgba(0,0,0,.4)','z-index:2147483647','cursor:pointer',
      'max-width:320px','font-family:-apple-system,BlinkMacSystemFont,sans-serif',
      'border:2px solid rgba(255,255,255,.25)','animation:_callBannerIn .3s ease-out'
    ].join(';') + ';';
    div.innerHTML =
      '<div style="font-size:15px;font-weight:bold;margin-bottom:4px">' + label + ' 초대</div>' +
      '<div style="font-size:13px;opacity:.95;margin-bottom:2px">' + fromName + '님이 호출 중</div>' +
      '<div style="font-size:11px;opacity:.85">👆 클릭하여 입장</div>' +
      '<button class="_cib_close" style="position:absolute;top:6px;right:8px;background:rgba(0,0,0,.2);border:none;color:#fff;font-size:18px;cursor:pointer;padding:0;line-height:1;width:24px;height:24px;border-radius:12px">×</button>';
    div.addEventListener('click', function(e) {
      if (e.target && e.target.classList && e.target.classList.contains('_cib_close')) {
        e.stopPropagation();
        div.remove();
        return;
      }
      _stopRing();
      if (callUrl) {
        var _a = document.createElement('a');
        _a.href = callUrl;
        _a.target = '_blank';
        _a.rel = 'noopener';
        document.body.appendChild(_a);
        _a.click();
        setTimeout(function() { try { document.body.removeChild(_a); } catch(e) {} }, 100);
      }
      div.remove();
    });
    // CSS 애니메이션 추가 (1회)
    if (!document.getElementById('_call_banner_style')) {
      var st = document.createElement('style');
      st.id = '_call_banner_style';
      st.textContent = '@keyframes _callBannerIn { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }';
      document.head.appendChild(st);
    }
    document.body.appendChild(div);
    // 60초 후 자동 제거
    setTimeout(function() { if (div.parentNode) div.remove(); }, 60000);
  }
  window._showInPageBanner = _showInPageBanner;

  // 폴링 스킵 페이지면 데이터·미리듣기만 노출하고 종료
  if (_skipPolling) return;

  // ── SSE 우선 + 폴링 폴백 ────────────────────────────────────────
  var _sse = null;
  var _sseConnected = false;
  var _sseTimeout = null;

  function _handleInvitePayload(r) {
    // 폴링과 SSE에서 공통 처리 — _seenKeys로 중복 방지
    if (!r || !r.last_msg) return;
    // key: call_room_uuid 우선 (방마다 고유) → 없으면 URL → 없으면 room_id 폴백
    var key;
    if (r.call_room_uuid) {
      key = 'vcall_uuid_' + r.call_room_uuid;
    } else {
      var _urlM3 = (r.last_msg || '').match(/참여하기:\s*(\S+)/);
      key = _urlM3 ? ('vcall_' + _urlM3[1]) : ('vcall_rid_' + String(r.room_id));
    }
    if (_seenKeys.has(key)) return;
    _addSeenKey(key);
    var callUrl, mode;
    if (r.call_room_uuid && r.room_status !== 'ended') {
      mode    = r.call_mode || 'video';
      callUrl = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(r.call_room_uuid) + '&mode=' + mode;
    } else {
      var urlMatch = r.last_msg.match(/참여하기:\s*(\S+)/);
      callUrl = urlMatch ? urlMatch[1] : ('/aimessage/onechat/?room=' + encodeURIComponent(r.room_id));
      mode    = (r.last_msg.indexOf('음성통화') !== -1 || r.last_msg.indexOf('음성 대화실') !== -1) ? 'voice' : 'video';
    }
    console.log('[call-global] 통화 초대 감지', { mode: mode, url: callUrl, room: r.room_id, src: 'sse' });
    _playRing(mode);
    // Chrome 알림 없으면 배너만 표시 (중복 방지, 2026-05-16)
    if (!_showNotification(mode, callUrl)) _showInPageBanner(mode, callUrl, r);
  }

  function _startPolling() {
    if (_pollInterval) return;
    console.log('[call-global] 폴링 시작 (3초 간격)');
    _pollInterval = setInterval(_checkPendingCalls, 3000);
  }

  function _stopPolling() {
    if (_pollInterval) {
      clearInterval(_pollInterval);
      _pollInterval = null;
      console.log('[call-global] 폴링 중단 (SSE 사용 중)');
    }
  }

  function _startSSE() {
    if (typeof EventSource === 'undefined') return false;
    try {
      _sse = new EventSource('/aimessage/onechat/sse_call_invite.php');
      _sse.addEventListener('open', function() {
        _sseConnected = true;
        if (_sseTimeout) { clearTimeout(_sseTimeout); _sseTimeout = null; }
        _stopPolling();
        console.log('[call-global] SSE 연결됨 (실시간 알림 활성화)');
      });
      _sse.addEventListener('call_invite', function(ev) {
        try { _handleInvitePayload(JSON.parse(ev.data)); } catch(e) {}
      });
      _sse.addEventListener('auth_required', function() {
        console.log('[call-global] SSE 인증 실패 → 폴링도 중단');
        if (_sse) { _sse.close(); _sse = null; }
        _stopPolling();
        _sseConnected = false;
      });
      _sse.onerror = function() {
        if (!_sseConnected) {
          // 첫 연결 실패 (네트워크/401/CORS 등) — 자동 재연결 중단 + 폴링 영구 폴백
          console.warn('[call-global] SSE 연결 실패 → 폴링 폴백 (재연결 중단)');
          try { if (_sse) _sse.close(); } catch(e) {}
          _sse = null;
          if (_sseTimeout) { clearTimeout(_sseTimeout); _sseTimeout = null; }
          _startPolling();
        } else {
          // 이미 연결됐다가 60초 후 정상 종료 → EventSource 자동 재연결에 위임
          console.warn('[call-global] SSE 일시적 끊김 (자동 재연결 시도)');
          _sseConnected = false;
        }
      };
      // 5초 내 onopen 발생 안 하면 폴링 폴백
      _sseTimeout = setTimeout(function() {
        if (!_sseConnected) {
          console.warn('[call-global] SSE 5초 무응답 → 폴링 폴백');
          _startPolling();
        }
      }, 5000);
      return true;
    } catch(e) {
      console.warn('[call-global] SSE 시작 오류', e);
      return false;
    }
  }

  // 시작: 1초 후 첫 폴링 1회 (즉시 확인) + SSE 연결 시도
  setTimeout(async function() {
    var ok = await _checkPendingCalls();
    if (!ok) {
      console.log('[call-global] 비로그인 → 시작 안 함');
      return;
    }
    if (!_startSSE()) {
      console.log('[call-global] SSE 미지원 환경 → 폴링만 사용');
      _startPolling();
    }
  }, 1000);
})();
