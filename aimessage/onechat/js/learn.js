// ===========================
// 아바타 학습 시스템 (서버 DB 연동 버전)
// ===========================

const LEARN_API = './api/learn.php';

// DOM
const learnScreen     = document.getElementById('learnScreen');
const learnBackBtn    = document.getElementById('learnBackBtn');
const learnSpinIcon   = document.getElementById('learnSpinIcon');
const learnCheckIcon  = document.getElementById('learnCheckIcon');
const learnSyncText   = document.getElementById('learnSyncText');
const learnTotalCount = document.getElementById('learnTotalCount');
const learnLastTime   = document.getElementById('learnLastTime');
const openLearnBtn    = document.getElementById('openLearnBtn');
const avatarDropdown  = document.getElementById('avatarDropdown');
const avatarBtn       = document.getElementById('avatarBtn');

// ── 서버 API 호출 ──────────────────────────────
async function learnApiGet(params = {}) {
  const url = new URL(LEARN_API, location.href);
  Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
  try {
    const res = await fetch(url, { credentials: 'include' });
    return await res.json();
  } catch(e) { console.error('[LEARN] GET:', e); return null; }
}

async function learnApiPost(body = {}) {
  try {
    const res = await fetch(LEARN_API, {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return await res.json();
  } catch(e) { console.error('[LEARN] POST:', e); return null; }
}

async function learnApiUpload(formData) {
  try {
    const res = await fetch(LEARN_API, {
      method: 'POST', credentials: 'include',
      body: formData
    });
    return await res.json();
  } catch(e) { console.error('[LEARN] UPLOAD:', e); return null; }
}

// 업로드 후 pending 항목의 완료를 자동 감지해서 상태 업데이트 (5초 간격, 최대 2분)
function pollItemStatus(itemEl, itemId) {
  let tries = 0;
  const timer = setInterval(async () => {
    tries++;
    if (tries > 24) { clearInterval(timer); return; } // 2분 후 포기
    const data = await learnApiGet({ id: itemId });
    if (!data?.list?.length) return;
    const st = data.list[0].stt_status;
    if (st === 'done') {
      clearInterval(timer);
      itemEl.querySelector('.fi-status').className = 'fi-status done';
      itemEl.querySelector('.fi-status').textContent = '학습 완료';
    } else if (st === 'error') {
      clearInterval(timer);
      itemEl.querySelector('.fi-status').className = 'fi-status error';
      itemEl.querySelector('.fi-status').textContent = '변환 실패';
    }
  }, 5000);
}

async function learnApiDelete(id) {
  try {
    const res = await fetch(LEARN_API + '?_method=DELETE&id=' + id, {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    return await res.json();
  } catch(e) { console.error('[LEARN] DELETE:', e); return null; }
}

// ── 통계 업데이트 ──────────────────────────────
async function updateLearnStats() {
  const data = await learnApiGet();
  if (!data || !data.success) return;
  learnTotalCount.textContent = data.total || 0;
  if (data.list && data.list.length > 0) {
    const last = new Date(data.list[0].created_at);
    learnLastTime.textContent = `최근: ${last.getMonth()+1}/${last.getDate()} ${last.getHours()}:${String(last.getMinutes()).padStart(2,'0')}`;
  } else {
    learnLastTime.textContent = '학습 기록 없음';
  }
}

// ── 학습 화면 열기/닫기 ────────────────────────
function openLearnScreen() {
  learnScreen.classList.add('active');
  avatarDropdown.classList.remove('open');
  document.body.style.overflow = 'hidden';
  updateLearnStats();
  renderDiaryDate();
  renderAllHistories();
}

function closeLearnScreen() {
  learnScreen.classList.remove('active');
  document.body.style.overflow = '';
}

learnBackBtn.addEventListener('click', closeLearnScreen);
openLearnBtn.addEventListener('click', openLearnScreen);

// 스와이프 오른쪽으로 닫기
let lsTouchStartX = 0, lsTouchStartY = 0, lsIsSwiping = false;

learnScreen.addEventListener('touchstart', (e) => {
  lsTouchStartX = e.touches[0].clientX;
  lsTouchStartY = e.touches[0].clientY;
  lsIsSwiping   = false;
}, { passive: true });

learnScreen.addEventListener('touchmove', (e) => {
  const dx = e.touches[0].clientX - lsTouchStartX;
  const dy = Math.abs(e.touches[0].clientY - lsTouchStartY);
  if (dx > 10 && dy < 60) {
    lsIsSwiping = true;
    learnScreen.style.transition = 'none';
    learnScreen.style.transform  = `translateX(${Math.max(0,dx)}px)`;
  }
}, { passive: true });

learnScreen.addEventListener('touchend', (e) => {
  const dx = e.changedTouches[0].clientX - lsTouchStartX;
  learnScreen.style.transition = '';
  learnScreen.style.transform  = '';
  if (lsIsSwiping && dx > 100) closeLearnScreen();
  lsIsSwiping = false;
});

// ── 탭 전환 ──────────────────────────────────
document.querySelectorAll('.learn-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.learn-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.learn-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const target = document.getElementById(`ltab-${btn.dataset.ltab}`);
    if (target) target.classList.add('active');
  });
});

// ── 업로드 버튼 로딩 표시 (공통) ─────────────
function setLearnBtnLoading(btn, isLoading) {
  if (isLoading) {
    btn.dataset.origHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> 저장 중...';
    btn.disabled = true;
    learnSpinIcon.style.display = 'inline-block';
    learnCheckIcon.style.display = 'none';
    learnSyncText.textContent = '저장 중...';
  } else {
    btn.innerHTML = btn.dataset.origHtml || btn.innerHTML;
    btn.disabled = false;
    learnSpinIcon.style.display = 'none';
    learnCheckIcon.style.display = 'inline-block';
    learnSyncText.textContent = '동기화됨';
  }
}

// ── ① 일기 탭 ────────────────────────────────
const diaryTextarea   = document.getElementById('diaryTextarea');
const diaryCharCount  = document.getElementById('diaryCharCount');
const diarySubmitBtn  = document.getElementById('diarySubmitBtn');
const diaryDateLabel  = document.getElementById('diaryDateLabel');
const diaryTemplateBtn= document.getElementById('diaryTemplateBtn');
const diaryHistory    = document.getElementById('diaryHistory');

const diaryTemplates = [
  '오늘은 [행사/미팅]이 있었다. 나는 항상 [나의 가치/원칙]을 기반으로 결정을 내렸고...',
  '요즘 가장 중요하게 생각하는 것은 [가치관]이다. 그 이유는...',
  '내가 가장 자랑스럽게 여기는 성취는 [업적]이다. 그 과정에서 배운 것은...',
];
let templateIdx = 0;

function renderDiaryDate() {
  const now = new Date();
  const days = ['일','월','화','수','목','금','토'];
  diaryDateLabel.textContent = `${now.getFullYear()}년 ${now.getMonth()+1}월 ${now.getDate()}일 (${days[now.getDay()]})`;
}

diaryTextarea.addEventListener('input', () => {
  diaryCharCount.textContent = `${diaryTextarea.value.length}자`;
});

diaryTemplateBtn.addEventListener('click', () => {
  diaryTextarea.value = diaryTemplates[templateIdx % diaryTemplates.length];
  diaryCharCount.textContent = `${diaryTextarea.value.length}자`;
  templateIdx++;
});

diarySubmitBtn.addEventListener('click', async () => {
  const text = diaryTextarea.value.trim();
  if (!text) { shakeEl(diaryTextarea); return; }
  setLearnBtnLoading(diarySubmitBtn, true);
  const result = await learnApiPost({ data_type: 'diary', category: '일기', content: text, source: 'chatbot' });
  setLearnBtnLoading(diarySubmitBtn, false);
  if (result && result.success) {
    prependHistoryItem(diaryHistory, {
      id: result.id, type: 'diary', text, category: '일기', createdAt: result.created_at
    });
    diaryTextarea.value = '';
    diaryCharCount.textContent = '0자';
    updateLearnStats();
  } else {
    alert('저장 실패: ' + (result?.error || '알 수 없는 오류'));
  }
});

// ── ② 텍스트 탭 ──────────────────────────────
const textTextarea   = document.getElementById('textTextarea');
const textCharCount  = document.getElementById('textCharCount');
const textSubmitBtn  = document.getElementById('textSubmitBtn');
const textHistory    = document.getElementById('textHistory');

let selectedCat = 'intro';
const catLabels = { intro:'자기소개', career:'경력/이력', value:'가치관/철학', etc:'기타' };

document.querySelectorAll('.learn-cat-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.learn-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedCat = btn.dataset.cat;
  });
});

textTextarea.addEventListener('input', () => {
  textCharCount.textContent = `${textTextarea.value.length}자`;
});

textSubmitBtn.addEventListener('click', async () => {
  const text = textTextarea.value.trim();
  if (!text) { shakeEl(textTextarea); return; }
  const catLabel = catLabels[selectedCat] || '기타';
  setLearnBtnLoading(textSubmitBtn, true);
  const result = await learnApiPost({ data_type: 'text', category: catLabel, content: text, source: 'chatbot' });
  setLearnBtnLoading(textSubmitBtn, false);
  if (result && result.success) {
    prependHistoryItem(textHistory, {
      id: result.id, type: 'text', text, category: catLabel, createdAt: result.created_at
    });
    textTextarea.value = '';
    textCharCount.textContent = '0자';
    updateLearnStats();
  } else {
    alert('저장 실패: ' + (result?.error || '알 수 없는 오류'));
  }
});

// ── ③ 음성 탭 ────────────────────────────────
const vrRecordBtn   = document.getElementById('vrRecordBtn');
const vrStopBtn     = document.getElementById('vrStopBtn');
const vrUploadBtn   = document.getElementById('vrUploadBtn');
const vrIdle        = document.getElementById('vrIdle');
const vrRecording   = document.getElementById('vrRecording');
const vrDone        = document.getElementById('vrDone');
const vrDoneText    = document.getElementById('vrDoneText');
const vrTimer       = document.getElementById('vrTimer');
const voiceHistory  = document.getElementById('voiceHistory');

let vrInterval = null, vrSeconds = 0, vrDuration = 0;
let vrMediaRecorder = null, vrChunks = [], vrBlob = null;

vrRecordBtn.addEventListener('click', async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    vrMediaRecorder = new MediaRecorder(stream);
    vrChunks = []; vrBlob = null;
    vrMediaRecorder.ondataavailable = e => { if (e.data.size > 0) vrChunks.push(e.data); };
    vrMediaRecorder.onstop = () => {
      vrBlob = new Blob(vrChunks, { type: 'audio/webm' });
      stream.getTracks().forEach(t => t.stop());
    };
    vrMediaRecorder.start();
  } catch(e) {
    alert('마이크 권한이 필요합니다.');
    return;
  }
  vrIdle.style.display = 'none'; vrRecording.style.display = 'flex';
  vrDone.style.display = 'none'; vrRecordBtn.style.display = 'none';
  vrStopBtn.style.display = 'flex'; vrUploadBtn.style.display = 'none';
  vrSeconds = 0;
  vrInterval = setInterval(() => {
    vrSeconds++;
    const m = String(Math.floor(vrSeconds/60)).padStart(2,'0');
    const s = String(vrSeconds%60).padStart(2,'0');
    vrTimer.textContent = `${m}:${s}`;
    if (vrSeconds >= 300) stopRecording();
  }, 1000);
});

function stopRecording() {
  clearInterval(vrInterval);
  vrDuration = vrSeconds;
  if (vrMediaRecorder && vrMediaRecorder.state !== 'inactive') vrMediaRecorder.stop();
  vrIdle.style.display = 'none'; vrRecording.style.display = 'none';
  vrDone.style.display = 'flex'; vrRecordBtn.style.display = 'none';
  vrStopBtn.style.display = 'none'; vrUploadBtn.style.display = 'flex';
  const m = String(Math.floor(vrDuration/60)).padStart(2,'0');
  const s = String(vrDuration%60).padStart(2,'0');
  vrDoneText.textContent = `${m}:${s} 녹음 완료`;
}

vrStopBtn.addEventListener('click', stopRecording);

vrUploadBtn.addEventListener('click', async () => {
  if (!vrBlob) { alert('녹음 파일이 없습니다.'); return; }
  setLearnBtnLoading(vrUploadBtn, true);
  const fd = new FormData();
  fd.append('data_type', 'voice');
  fd.append('category', '음성');
  const m = String(Math.floor(vrDuration/60)).padStart(2,'0');
  const s = String(vrDuration%60).padStart(2,'0');
  fd.append('voice_file', vrBlob, `voice_${Date.now()}.webm`);
  const result = await learnApiUpload(fd);
  setLearnBtnLoading(vrUploadBtn, false);
  if (result && result.success) {
    const saved = result.saved[0];
    prependHistoryItem(voiceHistory, {
      id: saved.id, type: 'voice', text: `음성 녹음 (${m}:${s})`, category: '음성',
      createdAt: new Date().toISOString()
    });
    if (saved.message) showLearnToast(saved.message);
    vrIdle.style.display = 'flex'; vrDone.style.display = 'none';
    vrRecordBtn.style.display = 'flex'; vrUploadBtn.style.display = 'none';
    vrBlob = null; vrSeconds = 0;
    updateLearnStats();
  } else {
    alert('업로드 실패: ' + (result?.error || '알 수 없는 오류'));
  }
});

// ── ④ 파일 탭 ────────────────────────────────
const fileDropzone = document.getElementById('fileDropzone');
const fileInput    = document.getElementById('fileInput');
const fileList     = document.getElementById('fileList');
const fileHistory  = document.getElementById('fileHistory');

fileDropzone.addEventListener('dragover', (e) => {
  e.preventDefault(); fileDropzone.classList.add('drag-over');
});
fileDropzone.addEventListener('dragleave', () => {
  fileDropzone.classList.remove('drag-over');
});
fileDropzone.addEventListener('drop', (e) => {
  e.preventDefault(); fileDropzone.classList.remove('drag-over');
  handleFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => {
  handleFiles(fileInput.files); fileInput.value = '';
});

async function handleFiles(files) {
  Array.from(files).forEach(async file => {
    const ext = file.name.split('.').pop().toUpperCase();
    const size = file.size < 1024*1024
      ? `${Math.round(file.size/1024)}KB`
      : `${(file.size/1024/1024).toFixed(1)}MB`;

    const item = document.createElement('div');
    item.className = 'file-item';
    item.innerHTML = `
      <span class="fi-icon"><i class="fas fa-file-${ext==='PDF'?'pdf':'alt'}"></i></span>
      <div class="fi-info"><div class="fi-name">${file.name}</div><div class="fi-size">${size}</div></div>
      <span class="fi-status uploading">업로드 중...</span>`;
    fileList.prepend(item);

    const fd = new FormData();
    fd.append('data_type', 'file');
    fd.append('category', ext);
    fd.append('learn_file', file);
    const result = await learnApiUpload(fd);

    if (result && result.success && result.saved && result.saved[0]) {
      const saved = result.saved[0];
      const isPending = saved.stt_status && saved.stt_status.startsWith('pending');
      item.querySelector('.fi-status').className = 'fi-status ' + (isPending ? 'pending' : 'done');
      item.querySelector('.fi-status').textContent = isPending ? '변환 중...' : '학습 완료';
      if (isPending) pollItemStatus(item, saved.id);
      prependHistoryItem(fileHistory, {
        id: saved.id, type: 'file', text: file.name, category: ext,
        createdAt: new Date().toISOString()
      });
      if (saved.message) showLearnToast(saved.message);
      updateLearnStats();
    } else {
      item.querySelector('.fi-status').className = 'fi-status error';
      item.querySelector('.fi-status').textContent = '실패';
    }
  });
}

// ── 기록 렌더링 ──────────────────────────────
const typeConfig = {
  diary: { icon: 'fas fa-book-open', bg: 'rgba(139,92,246,0.15)', color: '#8b5cf6' },
  text:  { icon: 'fas fa-file-alt',  bg: 'rgba(59,130,246,0.15)',  color: '#3b82f6' },
  voice: { icon: 'fas fa-microphone', bg: 'rgba(239,68,68,0.12)', color: '#ef4444' },
  file:  { icon: 'fas fa-file-pdf',  bg: 'rgba(245,158,11,0.12)', color: '#f59e0b' },
  url:   { icon: 'fas fa-globe',     bg: 'rgba(16,185,129,0.12)',  color: '#10b981' },
};

function prependHistoryItem(container, item) {
  container.prepend(buildHistoryItem(item));
}

function buildHistoryItem(item) {
  const conf = typeConfig[item.type] || typeConfig.text;
  const dt   = new Date(item.createdAt || item.created_at);
  const timeStr = `${dt.getMonth()+1}/${dt.getDate()} ${dt.getHours()}:${String(dt.getMinutes()).padStart(2,'0')}`;
  const rawText = item.text || item.content_preview || '';
  const preview = rawText.length > 60 ? rawText.slice(0,60)+'...' : rawText;

  const div = document.createElement('div');
  div.className = 'learn-history-item';
  div.dataset.lid = item.id;
  div.innerHTML = `
    <div class="lhi-icon" style="background:${conf.bg}">
      <i class="${conf.icon}" style="color:${conf.color}"></i>
    </div>
    <div class="lhi-body">
      <div class="lhi-text">${escapeLearnHtml(preview)}</div>
      <div class="lhi-meta">
        <span class="lhi-time">${timeStr}</span>
        <span class="lhi-badge">${item.category || item.type}</span>
      </div>
    </div>
    <button class="lhi-del" title="삭제"><i class="fas fa-times"></i></button>`;

  div.querySelector('.lhi-del').addEventListener('click', async (e) => {
    e.stopPropagation();
    const result = await learnApiDelete(item.id);
    if (result && result.success) {
      div.style.transition = 'opacity 0.25s, transform 0.25s';
      div.style.opacity = '0'; div.style.transform = 'translateX(20px)';
      setTimeout(() => { div.remove(); updateLearnStats(); }, 260);
    }
  });
  return div;
}

async function renderAllHistories() {
  [diaryHistory, textHistory, voiceHistory, fileHistory].forEach(c => c.innerHTML = '');
  const data = await learnApiGet();
  if (!data || !data.success) return;

  const containerMap = { diary: diaryHistory, text: textHistory, voice: voiceHistory, file: fileHistory, url: urlHistory };
  data.list.forEach(item => {
    const c = containerMap[item.data_type];
    if (c) c.appendChild(buildHistoryItem({
      id: item.id, type: item.data_type, text: item.content_preview,
      category: item.category, createdAt: item.created_at
    }));
  });
}

function escapeLearnHtml(text) {
  return (text||'').replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/\n/g,' ');
}

function shakeEl(el) {
  el.style.animation = 'none'; void el.offsetWidth;
  el.style.animation = 'shakeInput 0.4s ease';
  el.style.borderColor = '#ef4444';
  setTimeout(() => { el.style.borderColor = ''; el.style.animation = ''; }, 600);
}

const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
@keyframes shakeInput {
  0%,100% { transform: translateX(0); }
  20%     { transform: translateX(-6px); }
  40%     { transform: translateX(6px); }
  60%     { transform: translateX(-4px); }
  80%     { transform: translateX(4px); }
}`;
document.head.appendChild(shakeStyle);


// ── showLearnToast ─────────────────────────────────────────────────────
function showLearnToast(msg) {
  let toast = document.getElementById('learnToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'learnToast';
    Object.assign(toast.style, {
      position:'fixed', bottom:'80px', left:'50%', transform:'translateX(-50%)',
      background:'#1e293b', color:'#fff', padding:'12px 20px', borderRadius:'12px',
      fontSize:'14px', zIndex:'9999', maxWidth:'320px', textAlign:'center',
      boxShadow:'0 4px 20px rgba(0,0,0,0.4)', border:'1px solid rgba(255,255,255,0.1)',
      opacity:'0', transition:'opacity 0.3s', pointerEvents:'none'
    });
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.opacity = '1';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 3500);
}

// ── ⑤ URL/웹페이지 탭 ─────────────────────────────────────────────────
const urlInput     = document.getElementById('urlInput');
const urlSubmitBtn = document.getElementById('urlSubmitBtn');
const urlHistory   = document.getElementById('urlHistory');

if (urlInput) {
  urlInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') urlSubmitBtn && urlSubmitBtn.click();
  });
}

if (urlSubmitBtn) {
  urlSubmitBtn.addEventListener('click', async () => {
    const url = urlInput ? urlInput.value.trim() : '';
    if (!url) { if (urlInput) shakeEl(urlInput); return; }
    if (!/^https?:\/\//i.test(url)) {
      alert('http:// 또는 https://로 시작하는 주소를 입력해주세요.');
      return;
    }
    setLearnBtnLoading(urlSubmitBtn, true);
    const result = await learnApiPost({
      data_type: 'url', url: url, content: url, category: '', source: 'chatbot'
    });
    setLearnBtnLoading(urlSubmitBtn, false);
    if (result && result.success) {
      showLearnToast(result.message || '✅ 웹페이지 내용을 저장했어요!');
      let hostname = '';
      try { hostname = new URL(url).hostname; } catch(e) { hostname = url; }
      prependHistoryItem(urlHistory, {
        id: result.id, type: 'url', text: result.preview || url,
        category: result.category || hostname, createdAt: result.created_at
      });
      if (urlInput) urlInput.value = '';
      updateLearnStats();
    } else {
      alert('가져오기 실패: ' + (result?.error || '알 수 없는 오류'));
    }
  });
}

// 초기 실행
updateLearnStats();
