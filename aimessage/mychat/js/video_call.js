/**
 * 마이챗 화상 통화 모듈 (WebRTC + HeyGen 선택 연동)
 * window.MyChatVideo = { start(), stop(), toggle(), isActive() }
 */
(function(){
'use strict';
if (window.MyChatVideo) return;

var _active = false, _stream = null;

window.MyChatVideo = {
  isActive: function(){ return _active; },

  // 화상 시작
  start: function(containerId, opts){
    opts = opts || {};
    var el = document.getElementById(containerId);
    if (!el) return;

    if (opts.heygenAvatarId && opts.heygenApiKey) {
      this._startHeyGen(el, opts);
    } else {
      this._startLocal(el);
    }
  },

  // 로컬 카메라
  _startLocal: function(el){
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}, audio:false})
      .then(function(stream){
        _stream = stream;
        var v = document.createElement('video');
        v.srcObject = stream; v.autoplay = true; v.playsInline = true;
        v.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:12px';
        el.innerHTML = ''; el.appendChild(v);
        _active = true;
      })
      .catch(function(e){ console.error('[MyChatVideo] 카메라 오류:', e.message); });
  },

  // HeyGen Streaming Avatar
  _startHeyGen: function(el, opts){
    var ph = document.createElement('div');
    ph.style.cssText = 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1e293b;border-radius:12px;color:#c4b5fd;font-size:13px;flex-direction:column;gap:8px';
    ph.innerHTML = '<div style="font-size:36px">🎬</div><div>HeyGen 아바타 연결 중...</div>';
    el.innerHTML = ''; el.appendChild(ph);

    fetch('https://api.heygen.com/v1/streaming.new', {
      method: 'POST',
      headers: {'X-Api-Key': opts.heygenApiKey, 'Content-Type': 'application/json'},
      body: JSON.stringify({quality:'medium', avatar_id:opts.heygenAvatarId, voice:{voice_id: opts.heygenVoiceId||''}})
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.data && d.data.session_id) {
        ph.innerHTML = '<div style="font-size:36px">✅</div><div>화상 아바타 연결됨</div>';
        _active = true;
        window._heygenSessionId = d.data.session_id;
      } else {
        ph.innerHTML = '<div style="font-size:36px">❌</div><div>연결 실패</div>';
      }
    })
    .catch(function(){ ph.innerHTML = '<div style="font-size:36px">❌</div><div>HeyGen 연결 실패</div>'; });
  },

  // 화상 중지
  stop: function(){
    if (_stream) { _stream.getTracks().forEach(function(t){ t.stop(); }); _stream = null; }
    _active = false;
  },

  // 버튼 토글
  toggle: function(btnId, containerId, opts){
    var btn = document.getElementById(btnId);
    if (!_active) {
      this.start(containerId, opts || {});
      if (btn) btn.innerHTML = '<i class="fas fa-video-slash"></i>';
    } else {
      this.stop();
      var el = document.getElementById(containerId);
      if (el) el.innerHTML = '';
      if (btn) btn.innerHTML = '<i class="fas fa-video"></i>';
    }
  }
};

})();
