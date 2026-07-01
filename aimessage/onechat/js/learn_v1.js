// ===========================
// 아바타 학습 시스템
// ===========================

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

// 학습 데이터 저장소 (localStorage)
const STORAGE_KEY = 'avatarLearnData';

function loadLearnData() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
  } catch { return []; }
}

function saveLearnData(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  updateLearnStats();
}

function addLearnItem(item) {
  const data = loadLearnData();
  data.unshift({ ...item, id: Date.now(), createdAt: new Date().toISOString() });
  saveLearnData(data);
  return data[0];
}

function deleteLearnItem(id) {
  const data = loadLearnData().filter(d => d.id !== id);
  saveLearnData(data);
}

// 통계 업데이트
function updateLearnStats() {
  const data = loadLearnData();
  learnTotalCount.textContent = data.length;
  if (data.length > 0) {
    const last = new Date(data[0].createdAt);
    learnLastTime.textContent = `최근: ${last.getMonth()+1}/${last.getDate()} ${last.getHours()}:${String(last.getMinutes()).padStart(2,'0')}`;
  } else {
    learnLastTime.textContent = '학습 기록 없음';
  }
}

// 학습 화면 열기/닫기
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

// ===========================
// 탭 전환
// ===========================
document.querySelectorAll('.learn-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.learn-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.learn-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const target = document.getElementById(`ltab-${btn.dataset.ltab}`);
    if (target) target.classList.add('active');
  });
});

// ===========================
// 업로드 애니메이션 (공통)
// ===========================
function simulateUpload(btn, onDone) {
  btn.classList.add('loading');
  const origHTML = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> 학습 중...';

  // 상단 배지 로딩 표시
  learnSpinIcon.style.display = 'inline-block';
  learnCheckIcon.style.display = 'none';
  learnSyncText.textContent = '학습 중...';

  setTimeout(() => {
    btn.classList.remove('loading');
    btn.innerHTML = '<i class="fas fa-check"></i> 완료!';
    learnSpinIcon.style.display = 'none';
    learnCheckIcon.style.display = 'inline-block';
    learnSyncText.textContent = '동기화됨';
    onDone();
    setTimeout(() => { btn.innerHTML = origHTML; }, 1500);
  }, 1800);
}

// ===========================
// ① 일기 탭
// ===========================
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

diarySubmitBtn.addEventListener('click', () => {
  const text = diaryTextarea.value.trim();
  if (!text) { shakeEl(diaryTextarea); return; }
  simulateUpload(diarySubmitBtn, () => {
    const item = addLearnItem({ type: 'diary', text, category: '일기' });
    prependHistoryItem(diaryHistory, item);
    diaryTextarea.value = '';
    diaryCharCount.textContent = '0자';
  });
});

// ===========================
// ② 텍스트 탭
// ===========================
const textTextarea   = document.getElementById('textTextarea');
const textCharCount  = document.getElementById('textCharCount');
const textSubmitBtn  = document.getElementById('textSubmitBtn');
const textHistory    = document.getElementById('textHistory');

let selectedCat = 'intro';

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

textSubmitBtn.addEventListener('click', () => {
  const text = textTextarea.value.trim();
  if (!text) { shakeEl(textTextarea); return; }
  const catLabels = { intro:'자기소개', career:'경력/이력', value:'가치관/철학', etc:'기타' };
  simulateUpload(textSubmitBtn, () => {
    const item = addLearnItem({ type: 'text', text, category: catLabels[selectedCat] });
    prependHistoryItem(textHistory, item);
    textTextarea.value = '';
    textCharCount.textContent = '0자';
  });
});

// ===========================
// ③ 음성 탭
// ===========================
const vrRecordBtn   = document.getElementById('vrRecordBtn');
const vrStopBtn     = document.getElementById('vrStopBtn');
const vrUploadBtn   = document.getElementById('vrUploadBtn');
const vrIdle        = document.getElementById('vrIdle');
const vrRecording   = document.getElementById('vrRecording');
const vrDone        = document.getElementById('vrDone');
const vrDoneText    = document.getElementById('vrDoneText');
const vrTimer       = document.getElementById('vrTimer');
const voiceHistory  = document.getElementById('voiceHistory');

let vrInterval = null;
let vrSeconds  = 0;
let vrDuration = 0;

vrRecordBtn.addEventListener('click', () => {
  vrIdle.style.display      = 'none';
  vrRecording.style.display = 'flex';
  vrDone.style.display      = 'none';
  vrRecordBtn.style.display = 'none';
  vrStopBtn.style.display   = 'flex';
  vrUploadBtn.style.display = 'none';
  vrSeconds = 0;

  vrInterval = setInterval(() => {
    vrSeconds++;
    const m = String(Math.floor(vrSeconds/60)).padStart(2,'0');
    const s = String(vrSeconds%60).padStart(2,'0');
    vrTimer.textContent = `${m}:${s}`;
    if (vrSeconds >= 300) stopRecording(); // 5분 제한
  }, 1000);
});

function stopRecording() {
  clearInterval(vrInterval);
  vrDuration = vrSeconds;
  vrIdle.style.display      = 'none';
  vrRecording.style.display = 'none';
  vrDone.style.display      = 'flex';
  vrRecordBtn.style.display = 'none';
  vrStopBtn.style.display   = 'none';
  vrUploadBtn.style.display = 'flex';
  const m = String(Math.floor(vrDuration/60)).padStart(2,'0');
  const s = String(vrDuration%60).padStart(2,'0');
  vrDoneText.textContent = `${m}:${s} 녹음 완료`;
}

vrStopBtn.addEventListener('click', stopRecording);

vrUploadBtn.addEventListener('click', () => {
  simulateUpload(vrUploadBtn, () => {
    const m = String(Math.floor(vrDuration/60)).padStart(2,'0');
    const s = String(vrDuration%60).padStart(2,'0');
    const item = addLearnItem({ type: 'voice', text: `음성 녹음 (${m}:${s})`, category: '음성' });
    prependHistoryItem(voiceHistory, item);
    // 초기화
    vrIdle.style.display      = 'flex';
    vrDone.style.display      = 'none';
    vrRecordBtn.style.display = 'flex';
    vrUploadBtn.style.display = 'none';
    vrSeconds = 0;
  });
});

// ===========================
// ④ 파일 탭
// ===========================
const fileDropzone = document.getElementById('fileDropzone');
const fileInput    = document.getElementById('fileInput');
const fileList     = document.getElementById('fileList');
const fileHistory  = document.getElementById('fileHistory');

fileDropzone.addEventListener('dragover', (e) => {
  e.preventDefault();
  fileDropzone.classList.add('drag-over');
});

fileDropzone.addEventListener('dragleave', () => {
  fileDropzone.classList.remove('drag-over');
});

fileDropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  fileDropzone.classList.remove('drag-over');
  handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', () => {
  handleFiles(fileInput.files);
  fileInput.value = '';
});

function handleFiles(files) {
  Array.from(files).forEach(file => {
    const item = document.createElement('div');
    item.className = 'file-item';
    const ext = file.name.split('.').pop().toUpperCase();
    const size = file.size < 1024*1024
      ? `${Math.round(file.size/1024)}KB`
      : `${(file.size/1024/1024).toFixed(1)}MB`;

    item.innerHTML = `
      <span class="fi-icon"><i class="fas fa-file-${ext==='PDF'?'pdf':'alt'}"></i></span>
      <div class="fi-info">
        <div class="fi-name">${file.name}</div>
        <div class="fi-size">${size}</div>
      </div>
      <span class="fi-status uploading">처리 중...</span>
    `;
    fileList.prepend(item);

    setTimeout(() => {
      item.querySelector('.fi-status').className = 'fi-status done';
      item.querySelector('.fi-status').textContent = '학습 완료';
      const learnItem = addLearnItem({ type: 'file', text: file.name, category: ext });
      prependHistoryItem(fileHistory, learnItem);
    }, 2000 + Math.random()*1000);
  });
}

// ===========================
// 기록 아이템 렌더링
// ===========================
const typeConfig = {
  diary: { icon: 'fas fa-book-open', bg: 'rgba(139,92,246,0.15)', color: '#8b5cf6' },
  text:  { icon: 'fas fa-file-alt',  bg: 'rgba(59,130,246,0.15)',  color: '#3b82f6' },
  voice: { icon: 'fas fa-microphone', bg: 'rgba(239,68,68,0.12)', color: '#ef4444' },
  file:  { icon: 'fas fa-file-pdf',  bg: 'rgba(245,158,11,0.12)', color: '#f59e0b' },
};

function prependHistoryItem(container, item) {
  const el = buildHistoryItem(item);
  container.prepend(el);
}

function buildHistoryItem(item) {
  const conf = typeConfig[item.type] || typeConfig.text;
  const dt   = new Date(item.createdAt);
  const timeStr = `${dt.getMonth()+1}/${dt.getDate()} ${dt.getHours()}:${String(dt.getMinutes()).padStart(2,'0')}`;
  const preview = item.text.length > 60 ? item.text.slice(0,60)+'...' : item.text;

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
    <button class="lhi-del" title="삭제"><i class="fas fa-times"></i></button>
  `;

  div.querySelector('.lhi-del').addEventListener('click', (e) => {
    e.stopPropagation();
    deleteLearnItem(item.id);
    div.style.animation = 'none';
    div.style.opacity = '0';
    div.style.transform = 'translateX(20px)';
    div.style.transition = 'opacity 0.25s, transform 0.25s';
    setTimeout(() => div.remove(), 260);
  });

  return div;
}

function renderAllHistories() {
  const all = loadLearnData();
  [diaryHistory, textHistory, voiceHistory, fileHistory].forEach(c => c.innerHTML = '');

  all.forEach(item => {
    const conf = {
      diary: diaryHistory,
      text:  textHistory,
      voice: voiceHistory,
      file:  fileHistory,
    }[item.type];
    if (conf) conf.appendChild(buildHistoryItem(item));
  });
}

function escapeLearnHtml(text) {
  return text
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/\n/g,' ');
}

function shakeEl(el) {
  el.style.animation = 'none';
  void el.offsetWidth;
  el.style.animation = 'shakeInput 0.4s ease';
  el.style.borderColor = '#ef4444';
  setTimeout(() => { el.style.borderColor = ''; el.style.animation = ''; }, 600);
}

// shakeInput 키프레임 동적 추가
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

// 초기 실행
updateLearnStats();
