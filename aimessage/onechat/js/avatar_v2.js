/**
 * 원챗(OneChat) Avatar Training v2.0 JS
 * ────────────────────────────────────────────────────────────
 * 8채널 RAG 기반 아바타 학습 프론트엔드 로직.
 * IIFE 패턴, window.OCAvatarV2 공개 API 제공.
 */
(function () {
  'use strict';

  var RAG_BASE = '/aimessage/onechat/api/rag_proxy.php'; // 실제 RAG proxy endpoint
  var BRIDGE_API = '/aimessage/onechat/api/context_bridge.php';
  var currentSmsIdx = 0;
  var urlQueue = [];
  var fileQueue = [];

  // ── DOM refs ──────────────────────────────────────────────────
  function $(id) { return document.getElementById(id); }
  function qs(sel) { return document.querySelector(sel); }
  function qsa(sel) { return document.querySelectorAll(sel); }

  // ── API helpers ───────────────────────────────────────────────
  function ragPost(path, body) {
    return fetch(RAG_BASE + path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(body)
    }).then(function (r) { return r.json(); });
  }

  function bridgeGet(action, smsIdx) {
    return fetch(BRIDGE_API + '?action=' + encodeURIComponent(action) + '&sms_idx=' + smsIdx, {
      credentials: 'include'
    }).then(function (r) { return r.json(); });
  }

  function bridgePost(action, body) {
    var formBody = new URLSearchParams();
    formBody.append('action', action);
    if (body) {
      for (var k in body) { formBody.append(k, body[k]); }
    }
    return fetch(BRIDGE_API, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formBody.toString()
    }).then(function (r) { return r.json(); });
  }

  function setStatus(id, msg, type) {
    var el = $(id);
    if (!el) return;
    el.textContent = msg;
    el.className = 'avatv2-status avatv2-' + (type || 'info');
  }

  // ── Init ──────────────────────────────────────────────────────
  function init(smsIdx) {
    currentSmsIdx = smsIdx || (window._onechat_current_sms_idx || 0);
    bindTabs();
    bindDropzones();
    bindVoiceBtn();
    bindFileInput();
    bindSnsConnects();
    if (currentSmsIdx > 0) refreshStats();
  }

  function bindTabs() {
    qsa('.avatv2-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = this.dataset.tab;
        qsa('.avatv2-tab').forEach(function (t) { t.classList.remove('active'); });
        this.classList.add('active');
        qsa('.avatv2-panel').forEach(function (p) { p.classList.remove('active'); });
        var panel = document.getElementById('panel-' + target);
        if (panel) panel.classList.add('active');
      });
    });
    // Template buttons
    qsa('.avatv2-tpl-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        loadTemplate(this.dataset.tpl);
      });
    });
  }

  function bindDropzones() {
    ['file-dropzone', 'qa-dropzone'].forEach(function (id) {
      var zone = $(id);
      if (!zone) return;
      zone.addEventListener('click', function () {
        var inputId = id === 'file-dropzone' ? 'file-input' : 'qa-file';
        var input = $(inputId);
        if (input) input.click();
      });
      ['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('avatv2-dragover'); });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('avatv2-dragover'); });
      });
    });
  }

  function bindFileInput() {
    var input = $('file-input');
    if (!input) return;
    input.addEventListener('change', function () {
      fileQueue = Array.from(this.files);
      renderFileList();
    });
  }

  function bindVoiceBtn() {
    var btn = $('voice-record-btn');
    if (!btn) return;
    var mediaRecorder, chunks = [];
    var recording = false;

    btn.addEventListener('click', function () {
      if (!recording) {
        navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
          mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
          mediaRecorder.ondataavailable = function (e) { chunks.push(e.data); };
          mediaRecorder.onstop = function () {
            var blob = new Blob(chunks, { type: 'audio/webm' });
            uploadVoiceBlob(blob);
            chunks = [];
          };
          mediaRecorder.start();
          recording = true;
          btn.querySelector('span:last-child').textContent = '녹음 중지';
          btn.classList.add('avatv2-recording');
        }).catch(function () {
          setStatus('voice-status', '마이크 접근이 거부되었습니다.', 'error');
        });
      } else {
        mediaRecorder.stop();
        recording = false;
        btn.querySelector('span:last-child').textContent = '녹음 시작';
        btn.classList.remove('avatv2-recording');
      }
    });

    var fileInput = $('voice-file');
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        if (this.files[0]) uploadVoiceBlob(this.files[0]);
      });
    }
  }

  function bindSnsConnects() {
    qsa('.avatv2-sns-connect').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var platform = this.dataset.platform;
        var idMap = { instagram: 'sns-instagram', facebook: 'sns-facebook', twitter: 'sns-twitter', blog: 'sns-blog' };
        var account = $(idMap[platform]) ? $(idMap[platform]).value.trim() : '';
        if (!account) { setStatus('sns-status', '계정명을 입력하세요.', 'error'); return; }

        this.textContent = '연동 중...';
        this.disabled = true;

        ragPost('/ingest/sns', { sms_idx: currentSmsIdx, platform: platform, account: account, category: '유권자소통' })
          .then(function (r) {
            btn.textContent = '연동 완료';
            btn.classList.add('avatv2-connected');
            var stEl = document.getElementById('sns-status-' + platform);
            if (stEl) stEl.textContent = '수집 중...';
            setStatus('sns-status', platform + ' 연동 완료', 'success');
          })
          .catch(function (e) {
            btn.textContent = '연동 실패';
            btn.disabled = false;
            setStatus('sns-status', '연동 실패: ' + e.message, 'error');
          });
      });
    });
  }

  // ── Templates ─────────────────────────────────────────────────
  function loadTemplate(tpl) {
    var templates = {
      'camp-briefing': '📅 날짜: \n📍 장소: \n👥 참석자: \n\n📋 주요 내용:\n- \n- \n\n💡 인사이트:\n- ',
      'meeting-note': '📋 회의 제목: \n👥 참석자: \n⏰ 시간: \n\n📝 회의 내용:\n\n✅ 결정 사항:\n- \n\n📌 후속 조치:\n- ',
      'voter-talk': '👤 유권자: (익명)\n📍 장소: \n\n💬 주요 대화:\n\n📊 유형: 지지 / 질문 / 비판 / 제안\n\n🔑 핵심 키워드: '
    };
    var content = templates[tpl] || '';
    var ta = $('diary-content');
    if (ta) ta.value = content;
  }

  // ── 채널 1: Diary ─────────────────────────────────────────────
  function ingestDiary() {
    var title = ($('diary-title') || {}).value || '';
    var content = ($('diary-content') || {}).value || '';
    var category = ($('diary-category') || {}).value || '유권자소통';
    if (!content.trim()) { setStatus('diary-status', '내용을 입력하세요.', 'error'); return; }

    var text = '## ' + title + '\n\n' + content;
    setStatus('diary-status', 'RAG 학습 중...', 'info');

    ragPost('/ingest/text', { sms_idx: currentSmsIdx, text: text, metadata: { category: category, source: 'diary', title: title } })
      .then(function (r) {
        setStatus('diary-status', '완료! ' + r.chunks_created + ' 청크 생성됨', 'success');
        if ($('diary-content')) $('diary-content').value = '';
        if ($('diary-title')) $('diary-title').value = '';
        refreshStats();
      })
      .catch(function (e) { setStatus('diary-status', '실패: ' + e.message, 'error'); });
  }

  // ── 채널 2: Text ──────────────────────────────────────────────
  function ingestText() {
    var source = ($('text-source') || {}).value || '';
    var category = ($('text-category') || {}).value || '발언·연설';
    var content = ($('text-content') || {}).value || '';
    var bulk = ($('text-bulk') || {}).checked || false;

    if (!content.trim()) { setStatus('text-status', '텍스트를 입력하세요.', 'error'); return; }

    var texts = bulk ? content.split(/\n---\n/).filter(function (t) { return t.trim(); }) : [content];
    setStatus('text-status', 'RAG 학습 중 (' + texts.length + '개 문서)...', 'info');

    var promises = texts.map(function (t) {
      return ragPost('/ingest/text', { sms_idx: currentSmsIdx, text: t.trim(), metadata: { category: category, source: source || 'manual-text' } });
    });

    Promise.all(promises)
      .then(function (results) {
        var total = results.reduce(function (s, r) { return s + (r.chunks_created || 0); }, 0);
        setStatus('text-status', '완료! ' + total + ' 청크 생성됨', 'success');
        if ($('text-content')) $('text-content').value = '';
        refreshStats();
      })
      .catch(function (e) { setStatus('text-status', '실패: ' + e.message, 'error'); });
  }

  // ── 채널 3: Voice ─────────────────────────────────────────────
  function uploadVoiceBlob(blob) {
    var source = ($('voice-source') || {}).value || '';
    var category = ($('voice-category') || {}).value || '발언·연설';
    setStatus('voice-status', '음성 변환 중...', 'info');

    var formData = new FormData();
    formData.append('audio', blob, 'recording.webm');
    formData.append('sms_idx', currentSmsIdx);
    formData.append('category', category);
    formData.append('source', source || 'voice-recording');

    fetch(RAG_BASE + '/ingest/voice', { method: 'POST', credentials: 'include', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        setStatus('voice-status', '완료! ' + (r.chunks_created || 0) + ' 청크 생성됨', 'success');
        refreshStats();
      })
      .catch(function (e) { setStatus('voice-status', '실패: ' + e.message, 'error'); });
  }

  // ── 채널 4: File ──────────────────────────────────────────────
  function renderFileList() {
    var list = $('file-list');
    if (!list) return;
    list.innerHTML = fileQueue.map(function (f, i) {
      return '<div class="avatv2-file-item"><span>' + f.name + '</span> <span class="avatv2-file-size">(' +
        (f.size > 1e6 ? (f.size / 1e6).toFixed(1) + 'MB' : (f.size / 1024).toFixed(1) + 'KB') +
        ')</span><button onclick="window.OCAvatarV2.removeFile(' + i + ')">✕</button></div>';
    }).join('');
    if (fileQueue.length > 0) {
      ingestFiles();
    }
  }

  function removeFile(idx) {
    fileQueue.splice(idx, 1);
    renderFileList();
  }

  function ingestFiles() {
    if (fileQueue.length === 0) return;
    var category = ($('file-category') || {}).value || '정책·공약';
    setStatus('file-status', '파일 업로드 및 학습 중 (' + fileQueue.length + '개)...', 'info');

    var formData = new FormData();
    formData.append('sms_idx', currentSmsIdx);
    formData.append('category', category);
    fileQueue.forEach(function (f) { formData.append('files[]', f); });

    fetch(RAG_BASE + '/ingest/files', { method: 'POST', credentials: 'include', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        setStatus('file-status', '완료! ' + (r.chunks_created || r.total_chunks || 0) + ' 청크 생성됨', 'success');
        fileQueue = [];
        renderFileList();
        refreshStats();
      })
      .catch(function (e) { setStatus('file-status', '실패: ' + e.message, 'error'); });
  }

  // ── 채널 5: URL ───────────────────────────────────────────────
  function addUrl() {
    var input = $('url-input');
    if (!input || !input.value.trim()) return;
    urlQueue.push(input.value.trim());
    input.value = '';
    renderUrlList();
  }

  function removeUrl(idx) {
    urlQueue.splice(idx, 1);
    renderUrlList();
  }

  function renderUrlList() {
    var list = $('url-list');
    if (!list) return;
    list.innerHTML = urlQueue.map(function (url, i) {
      return '<div class="avatv2-url-item"><span>' + url + '</span><button onclick="window.OCAvatarV2.removeUrl(' + i + ')">✕</button></div>';
    }).join('');
  }

  function ingestUrls() {
    var bulkText = ($('url-bulk') || {}).value || '';
    if (bulkText.trim()) {
      bulkText.split('\n').forEach(function (line) {
        var u = line.trim();
        if (u && /^https?:\/\//.test(u)) urlQueue.push(u);
      });
      if ($('url-bulk')) $('url-bulk').value = '';
    }

    if (urlQueue.length === 0 && !bulkText.trim()) {
      setStatus('url-status', 'URL을 추가하거나 입력하세요.', 'error'); return;
    }

    var category = ($('url-category') || {}).value || '언론·평가';
    setStatus('url-status', 'URL ' + urlQueue.length + '건 크롤링 및 학습 중...', 'info');

    var promises = urlQueue.map(function (url) {
      return ragPost('/ingest/url', { sms_idx: currentSmsIdx, url: url, category: category });
    });

    Promise.all(promises)
      .then(function (results) {
        var total = results.reduce(function (s, r) { return s + (r.chunks_created || 0); }, 0);
        setStatus('url-status', '완료! ' + total + ' 청크 생성됨', 'success');
        urlQueue = [];
        renderUrlList();
        refreshStats();
      })
      .catch(function (e) { setStatus('url-status', '일부 실패: ' + e.message, 'error'); });
  }

  // ── 채널 6: YouTube ───────────────────────────────────────────
  function ingestYoutube() {
    var url = ($('youtube-url') || {}).value || '';
    var category = ($('youtube-category') || {}).value || '발언·연설';

    if (!url.trim()) { setStatus('youtube-status', 'YouTube URL을 입력하세요.', 'error'); return; }
    if (!/youtu\.?be/.test(url)) { setStatus('youtube-status', '올바른 YouTube URL이 아닙니다.', 'error'); return; }

    setStatus('youtube-status', '자막 추출 및 학습 중...', 'info');

    ragPost('/ingest/youtube', { sms_idx: currentSmsIdx, url: url, category: category })
      .then(function (r) {
        setStatus('youtube-status', '완료! ' + r.chunks_created + ' 청크 생성됨', 'success');
        if ($('youtube-url')) $('youtube-url').value = '';
        refreshStats();
      })
      .catch(function (e) { setStatus('youtube-status', '실패: ' + e.message, 'error'); });
  }

  // ── 채널 8: Q&A ───────────────────────────────────────────────
  function addQaRow() {
    var list = $('qa-manual-list');
    if (!list) return;
    var row = document.createElement('div');
    row.className = 'avatv2-qa-row';
    row.innerHTML = '<input type="text" placeholder="질문" class="avatv2-input avatv2-qa-q"><input type="text" placeholder="답변" class="avatv2-input avatv2-qa-a"><button class="avatv2-qa-remove" onclick="this.parentElement.remove()">✕</button>';
    list.appendChild(row);
  }

  function ingestQA() {
    var category = ($('qa-category') || {}).value || '정책·공약';
    var qaFile = $('qa-file');
    var manualRows = qsa('.avatv2-qa-row');

    // 파일 업로드 처리
    if (qaFile && qaFile.files.length > 0) {
      setStatus('qa-status', 'QA 파일 업로드 및 학습 중...', 'info');
      var formData = new FormData();
      formData.append('sms_idx', currentSmsIdx);
      formData.append('category', category);
      formData.append('file', qaFile.files[0]);
      fetch(RAG_BASE + '/ingest/qa-file', { method: 'POST', credentials: 'include', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          setStatus('qa-status', '완료! ' + (r.chunks_created || 0) + ' 청크 생성됨', 'success');
          refreshStats();
        })
        .catch(function (e) { setStatus('qa-status', '실패: ' + e.message, 'error'); });
      return;
    }

    // 수동 입력 처리
    var pairs = [];
    manualRows.forEach(function (row) {
      var q = row.querySelector('.avatv2-qa-q');
      var a = row.querySelector('.avatv2-qa-a');
      if (q && a && q.value.trim() && a.value.trim()) {
        pairs.push({ question: q.value.trim(), answer: a.value.trim() });
      }
    });

    if (pairs.length === 0) { setStatus('qa-status', '질문-답변을 입력하거나 파일을 업로드하세요.', 'error'); return; }

    setStatus('qa-status', 'Q&A ' + pairs.length + '쌍 학습 중...', 'info');

    var promises = pairs.map(function (p) {
      var text = 'Q: ' + p.question + '\nA: ' + p.answer;
      return ragPost('/ingest/text', { sms_idx: currentSmsIdx, text: text, metadata: { category: category, source: 'qa-bulk', question: p.question } });
    });

    Promise.all(promises)
      .then(function (results) {
        var total = results.reduce(function (s, r) { return s + (r.chunks_created || 0); }, 0);
        setStatus('qa-status', '완료! ' + total + ' 청크 생성됨', 'success');
        // Clear rows
        if ($('qa-manual-list')) {
          $('qa-manual-list').innerHTML = '<div class="avatv2-qa-row"><input type="text" placeholder="질문" class="avatv2-input avatv2-qa-q"><input type="text" placeholder="답변" class="avatv2-input avatv2-qa-a"><button class="avatv2-qa-remove" onclick="this.parentElement.remove()">✕</button></div>';
        }
        refreshStats();
      })
      .catch(function (e) { setStatus('qa-status', '실패: ' + e.message, 'error'); });
  }

  // ── Stats ─────────────────────────────────────────────────────
  function refreshStats() {
    if (!currentSmsIdx) return;
    bridgeGet('quality_report', currentSmsIdx)
      .then(function (r) {
        if (r.stats) {
          var s = r.stats;
          var statEls = { 'stat-chunks': s.total_chunks, 'stat-categories': Object.keys(s.categories || {}).length, 'stat-feedback': s.total_feedback, 'stat-rating': s.avg_rating };
          for (var id in statEls) {
            var el = $(id);
            if (el) el.textContent = statEls[id];
          }
          var badge = $('avatv2-chunk-count');
          if (badge) badge.textContent = s.total_chunks + ' chunks';
        }
      })
      .catch(function () {});
  }

  function syncBridge() {
    if (!currentSmsIdx) return;
    bridgePost('bridge_update', { sms_idx: String(currentSmsIdx) })
      .then(function (r) {
        var toast = document.createElement('div');
        toast.className = 'avatv2-toast avatv2-toast-success';
        toast.textContent = 'Context Bridge 동기화 완료!';
        document.body.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 2500);
      })
      .catch(function () {});
  }

  // ── Public API ────────────────────────────────────────────────
  window.OCAvatarV2 = {
    init: init,
    ingestDiary: ingestDiary,
    ingestText: ingestText,
    ingestUrls: ingestUrls,
    ingestYoutube: ingestYoutube,
    ingestQA: ingestQA,
    ingestFiles: ingestFiles,
    addUrl: addUrl,
    removeUrl: removeUrl,
    removeFile: removeFile,
    addQaRow: addQaRow,
    refreshStats: refreshStats,
    syncBridge: syncBridge,
    setSmsIdx: function (idx) { currentSmsIdx = idx; refreshStats(); }
  };

  // Auto-init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(); });
  } else {
    init();
  }
})();