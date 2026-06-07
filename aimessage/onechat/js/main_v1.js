// ===========================
// DOM Elements
// ===========================
const overlay          = document.getElementById('overlay');
const chatMessages     = document.getElementById('chatMessages');

// 챗봇 ON/OFF
const botToggle        = document.getElementById('botToggle');
const botStatusBar     = document.getElementById('botStatusBar');
const botAvatarIcon    = document.getElementById('botAvatarIcon');
const bsbLabel         = document.getElementById('bsbLabel');
const bsbSub           = document.getElementById('bsbSub');
const ciBotMode        = document.getElementById('ciBotMode');
const ciUserMode       = document.getElementById('ciUserMode');
const ciOverrideBtn    = document.getElementById('ciOverrideBtn'); // HTML에서 제거됨
const ciTextarea       = document.getElementById('ciTextarea');
const ciSendBtn        = document.getElementById('ciSendBtn');

// 첨부 관련
const ciAttachBtn      = document.getElementById('ciAttachBtn');
const ciAttachIcon     = document.getElementById('ciAttachIcon');
const ciAttachPopup    = document.getElementById('ciAttachPopup');
const ciImageInput     = document.getElementById('ciImageInput');
const ciFileInput      = document.getElementById('ciFileInput');
const ciCameraInput    = document.getElementById('ciCameraInput');
const ciPreviewRow     = document.getElementById('ciPreviewRow');

// 채팅 탭
const screenChat       = document.getElementById('screenChat');
const chatList         = document.getElementById('chatList');
const chatBadge        = document.getElementById('chatBadge');
const chatSearchInput  = document.getElementById('chatSearchInput');
const chatClearBtn     = document.getElementById('chatClearBtn');

// 발신 리스트 탭 (내가 챗봇 링크를 보낸 상대)
const screenContacts      = document.getElementById('screenContacts');
const contactsList        = document.getElementById('contactsList');
const contactsBadge       = document.getElementById('contactsBadge');
const contactsSearchInput = document.getElementById('contactsSearchInput');
const contactsClearBtn    = document.getElementById('contactsClearBtn');
const contactsViewToggle  = document.getElementById('contactsViewToggle');

// 수신 리스트 탭 (상대가 나에게 챗봇 링크를 보낸 경우)
const screenReceived      = document.getElementById('screenReceived');
const receivedList        = document.getElementById('receivedList');  // 공유탭 리스트 (별칭 유지)
const receivedBadge       = document.getElementById('receivedBadge');
const receivedSearchInput = document.getElementById('receivedSearchInput');
const receivedClearBtn    = document.getElementById('receivedClearBtn');
// 수신 탭 전용 엘리먼트
const inboxList           = document.getElementById('inboxList');

// 채팅 전체화면
const chatScreen       = document.getElementById('chatScreen');
const backBtn          = document.getElementById('backBtn');
const cspAvatar        = document.getElementById('cspAvatar');
const cspName          = document.getElementById('cspName');
const cspPosition      = document.getElementById('cspPosition');

// 점3개 메뉴 & 대시보드
const moreBtn          = document.getElementById('moreBtn');
const dropdownMenu     = document.getElementById('dropdownMenu');
const dashboardBtn     = document.getElementById('dashboardBtn');
const dashboardPanel   = document.getElementById('dashboardPanel');
const dashCloseBtn     = document.getElementById('dashCloseBtn');

// ===========================
// 현재 탭 상태
// ===========================
let currentTab = 'chat'; // 'chat' | 'contacts' | 'received'
let currentPerson = null; // 현재 열려있는 채팅 상대

// ===========================
// 헬퍼: 채팅 메타 업데이트
// (메시지 1건 추가될 때마다 호출 → chatCount/lastChat 갱신 + 채팅리스트 재렌더)
// ===========================
function updateChatMeta(person) {
  if (!person) return;
  const now = new Date();
  const dateStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')} ${now.getHours()}:${String(now.getMinutes()).padStart(2,'0')}`;
  person.chatCount = (person.messages || []).length;
  person.lastChat  = dateStr;
  // 발신 리스트와 수신 리스트 모두 재렌더
  renderChatList();
  if (typeof renderReceivedList === 'function') {
    // 수신 리스트 데이터의 person인지 확인
    const isReceived = receivedData && receivedData.some(r => r.id === person.id);
    if (isReceived) renderReceivedList();
  }
}

// ===========================
// 헬퍼: 이름 이니셜
// ===========================
function getInitial(name) {
  return name.charAt(0);
}

// ===========================
// 헬퍼: 상대 날짜
// ===========================
function formatRelativeDate(dateStr) {
  if (!dateStr) return '';
  const datePart = dateStr.split(' ')[0];
  const [y, m, d] = datePart.split('-').map(Number);
  const now    = new Date();
  const target = new Date(y, m - 1, d);
  const diff   = Math.floor((now - target) / (1000 * 60 * 60 * 24));
  if (diff === 0) return '오늘';
  if (diff === 1) return '어제';
  if (diff < 7)  return `${diff}일 전`;
  if (diff < 30) return `${Math.floor(diff / 7)}주 전`;
  return `${m}/${d}`;
}

// ===========================
// 헬퍼: 날짜 레이블
// ===========================
function formatDateLabel(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number);
  const days = ['일','월','화','수','목','금','토'];
  const date = new Date(y, m - 1, d);
  return `${y}년 ${m}월 ${d}일 ${days[date.getDay()]}요일`;
}

// ===========================
// 헬퍼: HTML 이스케이프
// ===========================
function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/\n/g, '<br>');
}

// ===========================
// 빈 상태 요소 관리
// ===========================
function showEmpty(container, icon, title, sub) {
  const el = document.createElement('div');
  el.className = 'empty-state visible';
  el.innerHTML = `
    <div class="empty-icon"><i class="${icon}"></i></div>
    <p class="empty-text">${title}</p>
    ${sub ? `<p style="font-size:12px;color:var(--text-muted);margin-top:6px;">${sub}</p>` : ''}
  `;
  container.appendChild(el);
}

// ===========================
// ① 채팅 탭: 대화한 사람만
// ===========================
function renderChatList(query = '') {
  if (!chatList) return; // DM 탭이 기존 채팅 탭을 대체한 경우 chatList 없음
  chatList.innerHTML = '';

  // chatCount > 0 인 사람만, 최신 대화순 정렬
  let data = chatData
    .filter(p => p.chatCount > 0)
    .sort((a, b) => (b.lastChat || '').localeCompare(a.lastChat || ''));

  if (query) {
    data = data.filter(p =>
      p.name.includes(query) ||
      p.position.includes(query) ||
      p.phone.includes(query)
    );
  }

  chatBadge.textContent = `${chatData.filter(p => p.chatCount > 0).length}명`;

  if (data.length === 0) {
    showEmpty(chatList, 'fas fa-comment-slash',
      query ? '검색 결과가 없습니다' : '아직 대화한 사람이 없어요',
      query ? '' : '명함리스트에서 챗봇 URL을 공유해보세요'
    );
    return;
  }

  data.forEach((person, idx) => {
    const li = document.createElement('li');
    li.className = 'chat-item has-chat';
    li.dataset.id = person.id;
    li.style.animationDelay = `${idx * 0.04}s`;

    const timeLabel  = formatRelativeDate(person.lastChat);
    const lastMsgRaw = person.messages.slice(-1)[0]; // 가장 최근 메시지 (종류 무관)
    const previewPrefix = lastMsgRaw
      ? (lastMsgRaw.type === 'notice' ? '📢 ' : lastMsgRaw.type === 'owner' ? '나: ' : lastMsgRaw.type === 'bot' ? '🤖 ' : '')
      : '';
    const preview    = lastMsgRaw
      ? previewPrefix + lastMsgRaw.text.split('\n')[0].slice(0, 28)
      : '대화 기록 있음';

    // 미읽음 수: type==='user' 이고 read===false 인 메시지
    const unread = person.unreadCount !== undefined
      ? person.unreadCount
      : person.messages.filter(m => m.type === 'user' && m.read === false).length;

    li.innerHTML = `
      <div class="profile-wrap">
        <div class="profile-img ${person.colorClass}">${getInitial(person.name)}</div>
        ${unread > 0
          ? `<div class="chat-count-dot unread">${unread}</div>`
          : `<div class="chat-count-dot read"><i class="fas fa-check"></i></div>`
        }
      </div>
      <div class="chat-info">
        <div class="chat-name ${unread > 0 ? 'has-unread' : ''}">${person.name}</div>
        <div class="chat-position">${person.position}</div>
        <div class="chat-preview ${unread > 0 ? 'unread-preview' : ''}">${preview}</div>
      </div>
      <div class="chat-meta">
        <span class="chat-time">${timeLabel}</span>
        ${unread > 0
          ? `<span class="chat-badge unread-badge">답변필요 ${unread}</span>`
          : `<span class="chat-badge done-badge">완료</span>`
        }
      </div>
      <i class="fas fa-chevron-right chat-arrow"></i>
    `;

    li.addEventListener('click', () => openChatScreen(person));
    chatList.appendChild(li);
  });
}

// ===========================
// ② 발신 리스트 탭: 전체
// ===========================
function renderContactsList(query = '') {
  contactsList.innerHTML = '';

  let data = [...chatData].sort((a, b) => b.regDate.localeCompare(a.regDate));

  if (query) {
    data = data.filter(p =>
      p.name.includes(query) ||
      p.position.includes(query) ||
      p.phone.includes(query)
    );
  }

  // PWA 필터 (UI 제거됨 - 항상 전체 표시)

  const _total    = chatData.length;
  const _active   = chatData.filter(p => p.chatCount > 0).length;
  const _inactive = _total - _active;
  if (contactsBadge) contactsBadge.textContent = `${_total}명`;
  // 앱 헤더 배지 동기화 (퍼널 탭일 때)
  const _appBadge = document.getElementById('appBadge');
  if (_appBadge && document.getElementById('appTitle')?.textContent === '퍼널') {
    _appBadge.textContent = `${_total}명`;
  }
  // 통계 바 업데이트
  const _st = document.getElementById('statTotal');
  const _sa = document.getElementById('statActive');
  const _si = document.getElementById('statInactive');
  if (_st) _st.textContent = `${_total}명`;
  if (_sa) _sa.textContent = `${_active}명`;
  if (_si) _si.textContent = `${_inactive}명`;

  if (data.length === 0) {
    showEmpty(contactsList, 'fas fa-address-card', '검색 결과가 없습니다');
    return;
  }

  // 대화중 / 미사용 그룹 분리
  const withChat    = data.filter(p => p.chatCount > 0);
  const withoutChat = data.filter(p => p.chatCount === 0);

  if (withChat.length > 0) {
    appendSectionLabel(contactsList, 'fas fa-comments', `대화중 · ${withChat.length}명`);
    withChat.forEach((p, i) => {
      const card = buildContactCard(p, i);
      contactsList.appendChild(card);
    });
  }

  if (withoutChat.length > 0) {
    appendSectionLabel(contactsList, 'fas fa-user-clock', `미사용 · ${withoutChat.length}명`);
    withoutChat.forEach((p, i) => {
      const card = buildContactCard(p, withChat.length + i);
      contactsList.appendChild(card);
    });
  }
}

function appendSectionLabel(container, icon, text) {
  const div = document.createElement('div');
  div.className = 'contacts-section-label';
  div.innerHTML = `
    <div class="csl-line"></div>
    <span class="csl-text"><i class="fas ${icon.replace('fas ','')} " style="margin-right:4px;"></i>${text}</span>
    <div class="csl-line"></div>
  `;
  container.appendChild(div);
}

function buildContactCard(person, idx) {
  const hasChat = person.chatCount > 0;
  const card    = document.createElement('div');
  card.className = `contact-card${hasChat ? ' has-chat' : ''}`;
  card.style.animationDelay = `${idx * 0.03}s`;

  card.innerHTML = `
    <div class="cc-avatar ${person.colorClass}" style="position:relative;">
      ${getInitial(person.name)}
      ${hasChat ? `<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>` : ''}
      ${person.pwaInstalled ? `<div class="pwa-installed-badge" title="PWA 설치됨"><i class="fas fa-mobile-alt"></i></div>` : ''}
    </div>
    <div class="cc-info">
      <div class="cc-name-row">
        <span class="cc-name">${person.name}</span>
        ${person.shortUrl ? `<span class="chatlink-chip" onclick="copyBotLink(event,'https://chatbot.kiam.kr${person.shortUrl}')">챗봇 링크 <i class="fas fa-copy"></i></span>` : ''}
        ${hasChat
          ? `<span class="cc-chat-badge"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:3px;"></i>${person.chatCount}회</span>`
          : `<span class="cc-unused-tag">미사용</span>`}
      </div>
      <div class="cc-position">${person.position}</div>
      <div class="cc-meta-row">
        <span class="cc-phone"><i class="fas fa-phone-alt"></i>${person.phone}</span>
        <span class="cc-date"><i class="fas fa-calendar-alt"></i>${person.regDate}</span>
      </div>
    </div>
    <div class="cc-right">
      <i class="fas fa-chevron-right cc-arrow"></i>
    </div>
  `;

  card.addEventListener('click', () => openChatScreen(person));
  return card;
}

// ===========================
// 채팅 전체화면 열기
// ===========================
function openChatScreen(person) {
  currentPerson = person; // 현재 열린 채팅 상대 추적

  // 채팅방 열면 → 해당 사람 메시지 모두 읽음 처리
  person.messages.forEach(m => { if (m.type === 'user') m.read = true; });
  person.unreadCount = 0;
  // 리스트 뱃지도 즉시 갱신 (chatList가 없으면 건너뜀)
  if (chatList) {
    const listItem = chatList.querySelector(`[data-id="${person.id}"]`);
    if (listItem) {
      const dot = listItem.querySelector('.chat-count-dot');
      if (dot) {
        dot.className = 'chat-count-dot read';
        dot.innerHTML = '<i class="fas fa-check"></i>';
      }
      const badge = listItem.querySelector('.chat-badge');
      if (badge) {
        badge.className = 'chat-badge done-badge';
        badge.textContent = '완료';
      }
      const nameEl = listItem.querySelector('.chat-name');
      if (nameEl) nameEl.classList.remove('has-unread');
      const previewEl = listItem.querySelector('.chat-preview');
      if (previewEl) previewEl.classList.remove('unread-preview');
    }
  }

  cspAvatar.textContent  = person.name.charAt(0);
  cspAvatar.className    = `csp-avatar ${person.colorClass}`;
  cspName.textContent    = person.name;
  cspPosition.textContent = person.position;

  if (!person.messages || person.messages.length === 0) {
    chatMessages.innerHTML = `
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                  flex:1;padding:60px 20px;gap:14px;color:#9ca3af;text-align:center;">
        <div style="width:68px;height:68px;border-radius:20px;background:#e2e8f0;
                    display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-comment-dots" style="font-size:28px;color:#94a3b8;"></i>
        </div>
        <p style="font-size:15px;font-weight:700;color:#475569;">아직 대화가 없어요</p>
        <p style="font-size:13px;color:#94a3b8;line-height:1.7;">
          아래 단축 URL을 공유하면<br>챗봇 대화를 시작할 수 있어요
        </p>
        <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;
                    padding:10px 18px;font-size:12px;color:#64748b;font-weight:600;
                    letter-spacing:0.3px;">
          ${person.shortUrl}
        </div>
      </div>
    `;
  } else {
    renderMessages(person.messages);
  }

  chatScreen.classList.add('active');
  document.body.style.overflow = 'hidden';

  // 챗봇 ON으로 초기화
  isBotOn = true;
  botToggle.checked = true;
  updateBotUI();

  requestAnimationFrame(() => {
    setTimeout(() => { chatMessages.scrollTop = chatMessages.scrollHeight; }, 60);
  });
}

// ===========================
// 메시지 렌더링
// ===========================
function renderMessages(messages) {
  chatMessages.innerHTML = '';
  let lastDate = null;

  /*
   * msg.type 4가지:
   *  'bot'      → 내 아바타 챗봇 (왼쪽, 연한 초록)
   *  'user'     → 상대 직접 입력 (왼쪽, 흰색)
   *  'peer-bot' → 상대 아바타 챗봇 (왼쪽, 연한 초록 점선)
   *  'owner'    → 나 직접 입력 (오른쪽, 파란색)
   *  'my-bot'   → 내 아바타가 보낸 것으로 명시 (오른쪽, 연한 초록)
   */
  const senderLabel = {
    'bot':      { text: '🤖 MY AI',    cls: 'label-my-bot' },
    'my-bot':   { text: '🤖 MY AI',    cls: 'label-my-bot' },
    'owner':    { text: '✍️ 나',        cls: 'label-owner'  },
    'user':     { text: '👤 상대',      cls: 'label-user'   },
    'peer-bot': { text: '🤖 상대 AI',   cls: 'label-peer-bot' },
  };

  messages.forEach((msg, idx) => {
    if (msg.time) {
      const dateOnly = msg.time.split(' ')[0];
      if (dateOnly !== lastDate) {
        lastDate = dateOnly;
        const divider = document.createElement('div');
        divider.className = 'date-divider';
        divider.innerHTML = `<span>${formatDateLabel(dateOnly)}</span>`;
        chatMessages.appendChild(divider);
      }
    }

    const div = document.createElement('div');
    // bot 타입을 기본으로 my-bot 클래스로 처리
    const rowType = msg.type === 'bot' ? 'bot' : msg.type;
    const timeStr = msg.time ? msg.time.split(' ')[1] : '';

    // 운영자 안내문 (HI모드) -> NOTICE 카드
    if (rowType === 'notice') {
      div.className = 'msg-row notice-row';
      div.style.animationDelay = `${idx * 0.05}s`;
      div.innerHTML = `
        <div class="oc-notice-card">
          <div class="oc-notice-header">
            <span class="oc-notice-icon">&#128226;</span>
            <span class="oc-notice-label">운영자 안내문</span>
            <span class="oc-notice-badge">NOTICE</span>
          </div>
          <div class="oc-notice-divider"></div>
          <div class="oc-notice-body">${escapeHtml(msg.text)}</div>
          ${timeStr ? `<div class="oc-notice-time">${timeStr}</div>` : ''}
        </div>
      `;
      chatMessages.appendChild(div);
      return;
    }

    div.className = `msg-row ${rowType}`;
    div.style.animationDelay = `${idx * 0.05}s`;

    const isRight = (rowType === 'owner' || rowType === 'my-bot' || rowType === 'bot');
    const bubbleCls = rowType === 'owner' ? 'msg-bubble owner-bubble'
                    : rowType === 'my-bot' ? 'msg-bubble my-bot-bubble'
                    : rowType === 'bot' ? 'msg-bubble my-bot-bubble'
                    : 'msg-bubble';
    const lbl = senderLabel[rowType] || senderLabel['user'];

    if (!isRight) {
      // 왼쪽 (봇/상대)
      div.innerHTML = `
        <div class="msg-avatar">
          ${(rowType === 'bot' || rowType === 'peer-bot')
            ? '<i class="fas fa-robot" style="font-size:12px;color:#16a34a;"></i>'
            : '<i class="fas fa-user-tie" style="font-size:12px;color:#64748b;"></i>'}
        </div>
        <div class="msg-body">
          <span class="msg-sender-label ${lbl.cls}">${lbl.text}</span>
          <div class="${bubbleCls}">${escapeHtml(msg.text)}</div>
          ${timeStr ? `<span class="msg-time">${timeStr}</span>` : ''}
        </div>
      `;
    } else {
      // 오른쪽 (나 / 내 아바타)
      div.innerHTML = `
        <div class="msg-body">
          <span class="msg-sender-label ${lbl.cls}">${lbl.text}</span>
          <div class="${bubbleCls}">${escapeHtml(msg.text)}</div>
          ${timeStr ? `<span class="msg-time">${timeStr}</span>` : ''}
        </div>
      `;
    }
    chatMessages.appendChild(div);
  });
}

// ===========================
// 채팅 전체화면 닫기
// ===========================
function closeChat() {
  chatScreen.classList.remove('active');
  document.body.style.overflow = '';
  setTimeout(() => { chatMessages.innerHTML = ''; }, 350);
}

backBtn.addEventListener('click', closeChat);

// ===========================
// 아바타 드롭다운 토글
// ===========================
avatarBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  avatarDropdown.classList.toggle('open');
});

document.addEventListener('click', (e) => {
  if (avatarBtn && !avatarBtn.contains(e.target) && avatarDropdown && !avatarDropdown.contains(e.target)) {
    avatarDropdown.classList.remove('open');
  }
});

// ===========================
// 챗봇 ON / OFF 토글
// ===========================
let isBotOn = true;

function updateBotUI() {
  if (isBotOn) {
    botStatusBar.classList.remove('off');
    botAvatarIcon.className = 'bot-avatar-icon on';
    bsbLabel.textContent = 'AI가 대화중';
    bsbSub.textContent   = '모든 답변을 AI가 자동 처리합니다';
    ciBotMode.style.display  = 'flex';
    ciUserMode.style.display = 'none';
  } else {
    botStatusBar.classList.add('off');
    botAvatarIcon.className = 'bot-avatar-icon off';
    bsbLabel.textContent = 'HI가 대화중';
    bsbSub.textContent   = '내가 직접 메시지를 입력합니다';
    ciBotMode.style.display  = 'none';
    ciUserMode.style.display = 'flex';
    setTimeout(() => ciTextarea.focus(), 100);
  }
}


// ===========================
// HI 자동 AI 복귀 타이머 (안내문 전송 후 1분)
// ===========================
let hiAutoReturnTimer = null;
const HI_AUTO_RETURN_MS = 1 * 60 * 1000; // 1분

function resetHiAutoReturnTimer() {
  if (hiAutoReturnTimer) clearTimeout(hiAutoReturnTimer);
  hiAutoReturnTimer = setTimeout(() => {
    if (!isBotOn) {
      isBotOn = true;
      botToggle.checked = true;
      updateBotUI();
      const sysMsg = document.createElement('div');
      sysMsg.className = 'date-divider';
      sysMsg.innerHTML = '<span>🤖 안내문 전송 완료 — AI 자동 복귀</span>';
      chatMessages.appendChild(sysMsg);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }, HI_AUTO_RETURN_MS);
}

function clearHiAutoReturnTimer() {
  if (hiAutoReturnTimer) {
    clearTimeout(hiAutoReturnTimer);
    hiAutoReturnTimer = null;
  }
}

botToggle.addEventListener('change', () => {
  isBotOn = botToggle.checked;

  // 토글을 수동으로 ON 했을 때 → 직접대화 요청 일시정지도 함께 해제
  if (isBotOn && avatarPausedByPeer) {
    avatarPausedByPeer = false;
    directWaitBanner.style.display = 'none';
  }

  // HI 모드 전환 시 타이머 시작, AI 복귀 시 타이머 중지
  if (!isBotOn) {
    resetHiAutoReturnTimer();
  } else {
    clearHiAutoReturnTimer();
  }

  updateBotUI();
  const sysMsg = document.createElement('div');
  sysMsg.className = 'date-divider';
  sysMsg.innerHTML = `<span>${isBotOn ? '🤖 AI 대화 모드로 전환' : '👤 HI 대화 모드로 전환'}</span>`;
  chatMessages.appendChild(sysMsg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
});

// ciOverrideBtn 제거됨 (2026-03-31: 토글로 통합)

// ===========================
// 직접 메시지 전송
// ===========================
function sendDirectMessage() {
  const text = ciTextarea.value.trim();
  if (!text) return;
  // HI 메시지 전송 시 타이머 리셋
  if (!isBotOn) resetHiAutoReturnTimer();

  const now = new Date();
  const timeStr = `${now.getHours()}:${String(now.getMinutes()).padStart(2,'0')}`;

  // ── 데이터 저장 ──
  if (currentPerson) {
    const dateStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    currentPerson.messages.push({
      type: (!isBotOn && chatScreen.dataset.visitorId) ? 'notice' : 'owner',
      text,
      time: `${dateStr} ${timeStr}`,
      read: true
    });
    updateChatMeta(currentPerson);
  }

  const div = document.createElement('div');
  if (!isBotOn && chatScreen.dataset.visitorId) {
    div.className = 'msg-row notice-row';
    div.innerHTML = `
      <div class="oc-notice-card">
        <div class="oc-notice-header">
          <span class="oc-notice-icon">&#128226;</span>
          <span class="oc-notice-label">운영자 안내문</span>
          <span class="oc-notice-badge">NOTICE</span>
        </div>
        <div class="oc-notice-divider"></div>
        <div class="oc-notice-body">${escapeHtml(text)}</div>
        <div class="oc-notice-time">${timeStr}</div>
      </div>
    `;
  } else {
    div.className = 'msg-row owner';
    div.innerHTML = `
      <div class="msg-body" style="align-items:flex-end;">
        <span class="msg-sender-label label-owner">✍️ 나</span>
        <div class="msg-bubble owner-bubble">${escapeHtml(text)}</div>
        <span class="msg-time">${timeStr}</span>
      </div>
    `;
  }
  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;

  ciTextarea.value = '';
  ciTextarea.style.height = 'auto';

  // 일시 직접입력이었으면 챗봇 모드로 복귀
  if (ciTextarea.dataset.override === 'true') {
    delete ciTextarea.dataset.override;
    if (isBotOn && !avatarPausedByPeer) {
      setTimeout(() => {
        ciBotMode.style.display  = 'flex';
        ciUserMode.style.display = 'none';
      }, 300);
    }
  }
}

// ciSendBtn, keydown 은 아래 sendWithAttachments 에서 재연결

ciTextarea.addEventListener('input', () => {
  ciTextarea.style.height = 'auto';
  ciTextarea.style.height = Math.min(ciTextarea.scrollHeight, 120) + 'px';
});

// ===========================
// 첨부 팝업 토글
// ===========================
let attachPopupOpen = false;

function toggleAttachPopup(force) {
  attachPopupOpen = (force !== undefined) ? force : !attachPopupOpen;
  ciAttachPopup.classList.toggle('open', attachPopupOpen);
  ciAttachBtn.classList.toggle('open', attachPopupOpen);
  // + 아이콘 회전은 CSS .ci-attach.open 으로 처리
}

ciAttachBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  toggleAttachPopup();
});

// 팝업 외부 클릭 시 닫기
document.addEventListener('click', (e) => {
  if (attachPopupOpen &&
      !ciAttachBtn.contains(e.target) &&
      !ciAttachPopup.contains(e.target)) {
    toggleAttachPopup(false);
  }
});

// ===========================
// 첨부 파일 관리 상태
// ===========================
let pendingAttachments = []; // { type:'image'|'file', file, url }

function addPendingAttachment(type, file) {
  const id = Date.now() + Math.random();
  const obj = { id, type, file, url: type === 'image' ? URL.createObjectURL(file) : null };
  pendingAttachments.push(obj);
  renderPreviewItem(obj);
}

function removePendingAttachment(id) {
  pendingAttachments = pendingAttachments.filter(a => a.id !== id);
  const el = ciPreviewRow.querySelector(`[data-aid="${id}"]`);
  if (el) {
    el.style.opacity = '0';
    el.style.transform = 'scale(0.8)';
    el.style.transition = 'opacity 0.2s, transform 0.2s';
    setTimeout(() => el.remove(), 200);
  }
}

function renderPreviewItem(obj) {
  if (obj.type === 'image') {
    const wrap = document.createElement('div');
    wrap.className = 'ci-thumb';
    wrap.dataset.aid = obj.id;
    wrap.innerHTML = `
      <img src="${obj.url}" alt="${obj.file.name}" />
      <button class="ci-thumb-del"><i class="fas fa-times"></i></button>
    `;
    wrap.querySelector('.ci-thumb-del').addEventListener('click', () => removePendingAttachment(obj.id));
    ciPreviewRow.appendChild(wrap);
  } else {
    const ext = obj.file.name.split('.').pop().toUpperCase();
    const wrap = document.createElement('div');
    wrap.className = 'ci-file-thumb';
    wrap.dataset.aid = obj.id;
    wrap.innerHTML = `
      <i class="fas fa-file-${ext==='PDF'?'pdf':'alt'} fi-thumb-icon"></i>
      <span class="fi-thumb-name">${obj.file.name}</span>
      <button class="ci-file-thumb-del" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#ef4444;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:8px;color:#fff;border:none;">
        <i class="fas fa-times"></i>
      </button>
    `;
    wrap.querySelector('.ci-file-thumb-del').addEventListener('click', () => removePendingAttachment(obj.id));
    ciPreviewRow.appendChild(wrap);
  }
}

// ===========================
// 파일 input 이벤트 연결
// ===========================
ciImageInput.addEventListener('change', () => {
  Array.from(ciImageInput.files).forEach(f => addPendingAttachment('image', f));
  ciImageInput.value = '';
  toggleAttachPopup(false);
});

ciFileInput.addEventListener('change', () => {
  Array.from(ciFileInput.files).forEach(f => addPendingAttachment('file', f));
  ciFileInput.value = '';
  toggleAttachPopup(false);
});

ciCameraInput.addEventListener('change', () => {
  Array.from(ciCameraInput.files).forEach(f => addPendingAttachment('image', f));
  ciCameraInput.value = '';
  toggleAttachPopup(false);
});

// ===========================
// 첨부 포함 메시지 전송 (sendDirectMessage 확장)
// ===========================
// 기존 sendDirectMessage를 override하여 첨부도 처리
const _origSend = sendDirectMessage;
window._origSend = _origSend; // 참조 보존

function sendWithAttachments() {
  const hasText = ciTextarea.value.trim().length > 0;
  const hasAttachments = pendingAttachments.length > 0;

  if (!hasText && !hasAttachments) return;

  const now = new Date();
  const timeStr = `${now.getHours()}:${String(now.getMinutes()).padStart(2,'0')}`;

  // 첨부 먼저 전송
  pendingAttachments.forEach(obj => {
    const div = document.createElement('div');
    div.className = 'msg-row owner';
    div.style.animationDelay = '0s';

    if (obj.type === 'image') {
      div.innerHTML = `
        <div class="msg-body" style="align-items:flex-end;">
          <div class="msg-img-bubble">
            <img src="${obj.url}" alt="${obj.file.name}" />
          </div>
          <span class="msg-time">${timeStr}</span>
        </div>
      `;
    } else {
      const ext = obj.file.name.split('.').pop().toUpperCase();
      const size = obj.file.size < 1024*1024
        ? `${Math.round(obj.file.size/1024)}KB`
        : `${(obj.file.size/1024/1024).toFixed(1)}MB`;
      div.innerHTML = `
        <div class="msg-body" style="align-items:flex-end;">
          <div class="msg-file-bubble">
            <span class="mfb-icon"><i class="fas fa-file-${ext==='PDF'?'pdf':'alt'}"></i></span>
            <div class="mfb-info">
              <div class="mfb-name">${obj.file.name}</div>
              <div class="mfb-size">${size}</div>
            </div>
            <i class="fas fa-arrow-circle-down mfb-dl"></i>
          </div>
          <span class="msg-time">${timeStr}</span>
        </div>
      `;
    }
    chatMessages.appendChild(div);
  });

  // 텍스트 메시지
  if (hasText) {
    const text = ciTextarea.value.trim();

    // ── 데이터 저장 ──
    if (currentPerson) {
      const dateStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
      currentPerson.messages.push({
        type: (!isBotOn && chatScreen.dataset.visitorId) ? 'notice' : 'owner',
        text,
        time: `${dateStr} ${timeStr}`,
        read: true
      });
      updateChatMeta(currentPerson);
    }

    const div = document.createElement('div');
    if (!isBotOn && chatScreen.dataset.visitorId) {
      div.className = 'msg-row notice-row';
      div.innerHTML = `
        <div class="oc-notice-card">
          <div class="oc-notice-header">
            <span class="oc-notice-icon">&#128226;</span>
            <span class="oc-notice-label">운영자 안내문</span>
            <span class="oc-notice-badge">NOTICE</span>
          </div>
          <div class="oc-notice-divider"></div>
          <div class="oc-notice-body">${escapeHtml(text)}</div>
          <div class="oc-notice-time">${timeStr}</div>
        </div>
      `;
    } else {
      div.className = 'msg-row owner';
      div.innerHTML = `
        <div class="msg-body" style="align-items:flex-end;">
          <span class="msg-sender-label label-owner">✍️ 나</span>
          <div class="msg-bubble owner-bubble">${escapeHtml(text)}</div>
          <span class="msg-time">${timeStr}</span>
        </div>
      `;
    }
    chatMessages.appendChild(div);
    ciTextarea.value = '';
    ciTextarea.style.height = 'auto';

    // HI 모드 + 방문자 채팅: 운영자 메시지를 DB에 저장 (text 스코프 안에서 처리)
    if (!isBotOn && chatScreen.dataset.visitorId) {
      var _vid  = chatScreen.dataset.visitorId;
      var _sidx = parseInt(chatScreen.dataset.visitorSmsIdx, 10);
      apiPost('visitor_list.php', { mode: 'send_hi', visitor_id: _vid, sms_idx: _sidx, message: text })
        .then(function() {
          // 전송 성공 → 1분 후 AI 자동 복귀 타이머 시작
          resetHiAutoReturnTimer();
        })
        .catch(function(e) { console.error('[HI 전송 오류]', e); });
    }
  }

  // 초기화
  pendingAttachments = [];
  ciPreviewRow.innerHTML = '';
  chatMessages.scrollTop = chatMessages.scrollHeight;

  // 일시 직접입력이었으면 챗봇 모드로 복귀
  if (ciTextarea.dataset.override === 'true') {
    delete ciTextarea.dataset.override;
    if (isBotOn && !avatarPausedByPeer) {
      setTimeout(() => {
        ciBotMode.style.display  = 'flex';
        ciUserMode.style.display = 'none';
      }, 300);
    }
  }
}

// 전송 버튼 이벤트 재연결
ciSendBtn.removeEventListener('click', sendDirectMessage);
ciSendBtn.addEventListener('click', sendWithAttachments);

ciTextarea.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendWithAttachments();
  }
});

// ===========================
// 탭 전환
// ===========================
function switchTab(tab) {
  currentTab = tab;

  // 탭 화면 전환
  const screenShared = document.getElementById('screenShared');
  screenChat.classList.remove('active');
  screenContacts.classList.remove('active');
  if (screenShared) screenShared.classList.remove('active');
  if (screenReceived) screenReceived.classList.remove('active');

  if (tab === 'chat') {
    screenChat.classList.add('active');
    if (typeof onDMTabActivated === 'function') {
      onDMTabActivated();
    } else {
      renderChatList();
    }
  } else {
    // 채팅 탭에서 벗어날 때 DM 폴링 중지
    if (typeof onDMTabDeactivated === 'function') {
      onDMTabDeactivated();
    }
  }

  if (false) {
  } else if (tab === 'contacts') {
    screenContacts.classList.add('active');
    // 상태에 따라 비회원 안내 또는 일반 리스트
    if (typeof renderContactsWithState === 'function') {
      renderContactsWithState();
    } else {
      renderContactsList();
    }
  } else if (tab === 'received') {
    // 수신 탭 활성화
    if (screenReceived) screenReceived.classList.add('active');
    renderReceivedListFromAPI();
  } else if (tab === 'shared') {
    if (screenShared) screenShared.classList.add('active');
    renderVisitorList();
  }

  // 네비게이션 활성 상태
  document.querySelectorAll('.nav-item').forEach(btn => {
    const isActive = btn.dataset.tab === tab;
    btn.classList.toggle('active', isActive);
    const icon = btn.querySelector('.nav-icon');
    if (isActive) {
      icon.classList.add('active-icon');
    } else {
      icon.classList.remove('active-icon');
    }
  });

  // ── 퍼널/공유 stats-bar 버튼 활성 상태 업데이트 ──
  const _funnelBtns = [
    document.getElementById('statsBarFunnelBtn'),
    document.getElementById('rStatsBarFunnelBtn')
  ];
  const _sharedBtns = [
    document.getElementById('statsBarShareBtn'),
    document.getElementById('rStatsBarShareBtn')
  ];
  _funnelBtns.forEach(function(b) {
    if (b) b.classList.toggle('active', tab === 'contacts');
  });
  _sharedBtns.forEach(function(b) {
    if (b) b.classList.toggle('active', tab === 'shared');
  });

  // 헤더 점3개 메뉴는 채팅탭에서만 표시
  const moreBtnWrap = moreBtn ? moreBtn.closest('.more-menu-wrap') : null;
  if (moreBtnWrap) {
    moreBtnWrap.style.display = tab === 'chat' ? '' : 'none';
  }

  // ── 헤더 타이틀 · 배지 · 검색버튼 탭별 동적 업데이트 ──
  const appTitleEl  = document.getElementById('appTitle');
  const appBadgeEl  = document.getElementById('appBadge');
  const contactsSearchBtn = document.getElementById('contactsSearchBtn');
  if (appTitleEl) {
    if (tab === 'contacts') {
      appTitleEl.textContent = '퍼널';
      if (appBadgeEl) {
        appBadgeEl.textContent = contactsBadge ? contactsBadge.textContent : '0명';
        appBadgeEl.style.display = '';
      }
      if (contactsSearchBtn) contactsSearchBtn.style.display = '';
    } else if (tab === 'chat') {
      appTitleEl.textContent = '채팅';
      if (appBadgeEl) {
        const cb = document.getElementById('chatBadge');
        appBadgeEl.textContent = cb ? cb.textContent : '0';
        appBadgeEl.style.display = '';
      }
      if (contactsSearchBtn) contactsSearchBtn.style.display = '';
      // 퍼널/공유 검색창 닫기
      const sr = document.getElementById('contactsSearchRow');
      if (sr) sr.style.display = 'none';
      const rsr = document.getElementById('receivedSearchRow');
      if (rsr) rsr.style.display = 'none';
    } else if (tab === 'received') {
      appTitleEl.textContent = '수신';
      // 배지: 수신 아이템 총 수 표시
      if (appBadgeEl) {
        const _ist = document.getElementById('inboxStatTotal');
        appBadgeEl.textContent = _ist ? _ist.textContent : '0개';
        appBadgeEl.style.display = '';
      }
      // 다른 검색창 닫기
      const sr2 = document.getElementById('contactsSearchRow');
      if (sr2) sr2.style.display = 'none';
      const rsr2 = document.getElementById('receivedSearchRow');
      if (rsr2) rsr2.style.display = 'none';
    } else if (tab === 'shared') {
      appTitleEl.textContent = '공유';
      const rb = document.getElementById('receivedBadge');
      if (appBadgeEl) {
        appBadgeEl.textContent = rb ? rb.textContent : '0명';
        appBadgeEl.style.display = '';
      }
      // 퍼널/공유 검색창 정리
      const sr = document.getElementById('contactsSearchRow');
      if (sr) sr.style.display = 'none';
    } else {
      appTitleEl.textContent = '홈';
      if (appBadgeEl) appBadgeEl.style.display = 'none';
    }
    // 돋보기 버튼은 모든 탭에서 항상 표시
    if (contactsSearchBtn) contactsSearchBtn.style.display = '';
  }
}

// 하단 네비게이션 이벤트
document.querySelectorAll('.nav-item').forEach(btn => {
  btn.addEventListener('click', () => {
    const tab = btn.dataset.tab;
    if (tab === 'chat' || tab === 'contacts' || tab === 'received') {
      switchTab(tab);
    }
  });
});

// ===========================
// 검색: 채팅 탭
// ===========================
chatSearchInput.addEventListener('input', () => {
  const q = chatSearchInput.value.trim();
  chatClearBtn.classList.toggle('visible', q.length > 0);
  if (typeof filterDMRooms === 'function') filterDMRooms(q);
  else renderChatList(q);
});

chatClearBtn.addEventListener('click', () => {
  chatSearchInput.value = '';
  chatClearBtn.classList.remove('visible');
  if (typeof filterDMRooms === 'function') filterDMRooms('');
  else renderChatList();
  chatSearchInput.focus();
});

// ===========================
// 검색: 개별 탭
// ===========================
contactsSearchInput.addEventListener('input', () => {
  const q = contactsSearchInput.value.trim();
  contactsClearBtn.classList.toggle('visible', q.length > 0);
  renderContactsList(q);
});

contactsClearBtn.addEventListener('click', () => {
  contactsSearchInput.value = '';
  contactsClearBtn.classList.remove('visible');
  renderContactsList();
  contactsSearchInput.focus();
});

// ===========================
// 검색: 공유 탭
// ===========================
var _receivedSearchTimer = null;
receivedSearchInput.addEventListener('input', () => {
  const q = receivedSearchInput.value.trim();
  receivedClearBtn.classList.toggle('visible', q.length > 0);
  clearTimeout(_receivedSearchTimer);
  _receivedSearchTimer = setTimeout(() => { renderVisitorList(q); }, 300);
});

receivedClearBtn.addEventListener('click', () => {
  receivedSearchInput.value = '';
  receivedClearBtn.classList.remove('visible');
  renderVisitorList();
  receivedSearchInput.focus();
});

// ===========================
// ③ 공유 탭: visitor_id 기반 방문자 목록 (mock)
// ===========================
// ===========================
// 수신 탭 (inbox): API 기반 렌더링
// ===========================
var _inboxAllItems   = [];   // 캐시
var _inboxLoading    = false;
var _inboxQuery      = '';

/** global_visitor_id를 localStorage에서 읽음 (없으면 빈 문자열) */
function getGlobalVisitorId() {
  return localStorage.getItem('onechat_global_vid') || '';
}

/** 수신 탭 렌더 (API 호출) */
async function renderReceivedListFromAPI(query = '') {
  if (!inboxList) return;
  _inboxQuery = (query || '').trim();

  const gvid = getGlobalVisitorId();
  if (!gvid) {
    showEmpty(inboxList, 'fas fa-inbox',
      '기기 ID가 없습니다',
      '챗봇 링크를 한 번 방문하면 자동으로 등록됩니다'
    );
    return;
  }

  // 로딩 표시
  inboxList.innerHTML = '<div class="list-loading"><i class="fas fa-spinner fa-spin"></i> 불러오는 중...</div>';

  let url = 'api/received_list.php?global_visitor_id=' + encodeURIComponent(gvid);
  if (_inboxQuery) url += '&q=' + encodeURIComponent(_inboxQuery);

  let data;
  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    data = await res.json();
  } catch (e) {
    showEmpty(inboxList, 'fas fa-exclamation-circle',
      '데이터를 불러올 수 없습니다', '잠시 후 다시 시도해주세요');
    return;
  }

  if (!data.success) {
    showEmpty(inboxList, 'fas fa-exclamation-circle',
      data.error || '오류가 발생했습니다', '');
    return;
  }

  _inboxAllItems = data.list || [];
  const total    = data.total || 0;
  const active   = _inboxAllItems.filter(it => it.last_chat_at).length;
  const inactive = total - active;

  // 통계 바 업데이트
  const _ist = document.getElementById('inboxStatTotal');
  const _isa = document.getElementById('inboxStatActive');
  const _isi = document.getElementById('inboxStatInactive');
  if (_ist) _ist.textContent = total + '개';
  if (_isa) _isa.textContent = active + '개';
  if (_isi) _isi.textContent = inactive + '개';

  // 앱 헤더 배지
  const _appBadge = document.getElementById('appBadge');
  const _appTitle = document.getElementById('appTitle');
  if (_appBadge && _appTitle && _appTitle.textContent === '수신') {
    _appBadge.textContent = total + '개';
  }

  inboxList.innerHTML = '';

  if (_inboxAllItems.length === 0) {
    showEmpty(inboxList, 'fas fa-inbox',
      _inboxQuery ? '검색 결과가 없습니다' : '받은 챗봇 링크가 없어요',
      _inboxQuery ? '' : '운영자가 보낸 챗봇 링크로 대화하면 여기에 표시됩니다'
    );
    return;
  }

  // 대화중 / 미대화 그룹 분리
  const withChat    = _inboxAllItems.filter(it => it.last_chat_at);
  const withoutChat = _inboxAllItems.filter(it => !it.last_chat_at);

  if (withChat.length > 0) {
    appendSectionLabel(inboxList, 'fas fa-comments', '대화중 · ' + withChat.length + '개');
    withChat.forEach((item, i) => inboxList.appendChild(buildInboxCard(item, i)));
  }
  if (withoutChat.length > 0) {
    appendSectionLabel(inboxList, 'fas fa-envelope-open', '미대화 · ' + withoutChat.length + '개');
    withoutChat.forEach((item, i) => inboxList.appendChild(buildInboxCard(item, withChat.length + i)));
  }
}

/** 수신 탭 카드 빌드 */
function buildInboxCard(item, idx) {
  const hasChat    = !!item.last_chat_at;
  const unread     = item.unread_count || 0;
  const timeLabel  = formatRelativeDate(item.last_chat_at || item.subscribed_at);
  const preview    = item.last_message
    ? (item.last_role === 'user' ? '나: ' : '🤖 ') + item.last_message
    : '아직 대화 없음';
  // colorFromId exists in api_v1.js; compute a simple numeric hash from short_code
  var _scHash = 0;
  for (var _ci = 0; _ci < (item.short_code||'').length; _ci++) {
    _scHash = (_scHash * 31 + (item.short_code||'').charCodeAt(_ci)) >>> 0;
  }
  const colorClass = (typeof colorFromId === 'function')
    ? colorFromId(_scHash % 8 + 1)
    : 'color-' + ((_scHash % 8) + 1);
  const initial    = (item.chatbot_name || '챗').charAt(0);
  const opName     = item.operator_name || item.operator_id || '운영자';

  const card = document.createElement('div');
  card.className = 'contact-card inbox-card' + (hasChat ? ' has-chat' : '');
  card.style.animationDelay = (idx * 0.03) + 's';

  card.innerHTML =
    '<div class="cc-avatar ' + colorClass + '" style="position:relative;">' +
      initial +
      '<div class="inbox-bot-badge" title="AI 챗봇"><i class="fas fa-robot"></i></div>' +
    '</div>' +
    '<div class="cc-info" style="flex:1;min-width:0;">' +
      '<div class="cc-name-row">' +
        '<span class="cc-name' + (unread > 0 ? ' has-unread' : '') + '">' + escHtml(item.chatbot_name) + '</span>' +
        (unread > 0
          ? '<span class="cc-chat-badge unread" style="background:rgba(239,68,68,0.12);color:#ef4444;">' + unread + ' 미읽음</span>'
          : (hasChat
              ? '<span class="cc-chat-badge"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:3px;"></i>대화중</span>'
              : '<span class="cc-unused-tag">미대화</span>'
          )
        ) +
      '</div>' +
      '<div class="cc-position" style="font-size:11px;color:var(--accent-blue);font-weight:600;margin-bottom:2px;">' +
        '<i class="fas fa-user-tie" style="margin-right:3px;font-size:10px;"></i>' + escHtml(opName) +
      '</div>' +
      (hasChat
        ? '<div class="cc-preview-text">' + escHtml(preview) + '</div>'
        : ''
      ) +
    '</div>' +
    '<div class="cc-right">' +
      '<span class="chat-time" style="font-size:10px;color:var(--text-muted);">' + timeLabel + '</span>' +
      '<button class="inbox-dm-btn" title="채팅탭으로 가져오기" data-short-code="' + item.short_code + '" data-mem-code="' + (item.mem_code || '') + '" data-id="' + item.id + '">' +
        '<i class="fas fa-comment-dots"></i>' +
      '</button>' +
    '</div>';

  // 카드 클릭 → 챗봇 URL 열기
  card.addEventListener('click', function(e) {
    // DM 버튼 클릭은 카드 클릭 이벤트 무시
    if (e.target.closest('.inbox-dm-btn')) return;
    const url = item.chatbot_url || ('https://chatbot.kiam.kr/s/' + item.short_code);
    window.open(url + '?from_list=1', '_blank');
  });

  // DM 버튼 클릭 → 채팅 탭으로 이동 (로그인 회원만)
  const dmBtn = card.querySelector('.inbox-dm-btn');
  if (dmBtn) {
    dmBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      moveInboxToDM(item);
    });
  }

  return card;
}

/** 수신 아이템 → 채팅 DM방으로 이동 */
async function moveInboxToDM(item) {
  const targetMemId = item.mem_code || item.operator_id;
  if (!targetMemId) {
    alert('상대방 정보를 찾을 수 없습니다.');
    return;
  }

  const gvid = getGlobalVisitorId();
  let res;
  try {
    res = await fetch('api/received_list.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        mode:               'move_to_dm',
        global_visitor_id:  gvid,
        short_code:         item.short_code,
        target_mem_id:      targetMemId
      })
    });
    res = await res.json();
  } catch(e) {
    alert('서버 오류: ' + e.message);
    return;
  }

  if (!res.success) {
    // 로그인 필요 → 안내
    if (res.need_login) {
      alert('채팅 탭으로 이동하려면 로그인이 필요합니다.\n로그인 후 이 기능을 사용할 수 있어요.');
    } else {
      alert(res.error || '이동에 실패했습니다.');
    }
    return;
  }

  // 성공: 수신 리스트에서 해당 아이템 제거 후 채팅 탭으로 전환
  alert('"' + item.chatbot_name + '" 운영자와의 DM방이 채팅 탭에 추가되었습니다!');
  renderReceivedListFromAPI(_inboxQuery);  // 목록 갱신
  switchTab('chat');  // 채팅 탭 전환
}

/** 수신 탭 검색 (debounce) */
var _inboxSearchTimer = null;
(function setupInboxSearch() {
  const inp  = document.getElementById('inboxSearchInput');
  const clrBtn = document.getElementById('inboxClearBtn');
  if (!inp) return;
  inp.addEventListener('input', function() {
    const q = inp.value.trim();
    if (clrBtn) clrBtn.classList.toggle('visible', q.length > 0);
    clearTimeout(_inboxSearchTimer);
    _inboxSearchTimer = setTimeout(function() { renderReceivedListFromAPI(q); }, 300);
  });
  if (clrBtn) {
    clrBtn.addEventListener('click', function() {
      inp.value = '';
      clrBtn.classList.remove('visible');
      renderReceivedListFromAPI('');
      inp.focus();
    });
  }
})();

/** 수신 탭 검색창 토글 (헤더 돋보기) */
function toggleInboxSearch() {
  const row = document.getElementById('inboxSearchRow');
  const inp = document.getElementById('inboxSearchInput');
  if (!row) return;
  const hidden = row.style.display === 'none' || !row.style.display;
  row.style.display = hidden ? 'block' : 'none';
  if (hidden && inp) { inp.focus(); }
  else {
    if (inp) inp.value = '';
    const cb = document.getElementById('inboxClearBtn');
    if (cb) cb.classList.remove('visible');
    renderReceivedListFromAPI('');
  }
}

// 레거시 호환 (이전 코드에서 renderReceivedList 호출 시 API 버전으로 리다이렉트)
function renderReceivedList(query) { renderReceivedListFromAPI(query); }

// ===========================
// 오버레이 클릭
// ===========================
// 오버레이 클릭
// ===========================
overlay.addEventListener('click', () => {
  closeDashboard();
});

// ===========================
// 점 3개 드롭다운
// ===========================
if (moreBtn && dropdownMenu) {
  moreBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = dropdownMenu.classList.contains('open');
    dropdownMenu.classList.toggle('open', !isOpen);
    moreBtn.classList.toggle('active', !isOpen);
  });

  document.addEventListener('click', (e) => {
    if (!moreBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.classList.remove('open');
      moreBtn.classList.remove('active');
    }
  });
}

// ===========================
// 대시보드
// ===========================
function openDashboard() {
  if (dropdownMenu) dropdownMenu.classList.remove('open');
  if (moreBtn) moreBtn.classList.remove('active');
  if (dashboardPanel) dashboardPanel.classList.add('open');
  overlay.classList.add('visible');
  document.body.style.overflow = 'hidden';
  document.querySelectorAll('.dash-gauge-fill, .dist-bar, .month-bar').forEach(el => {
    el.style.animation = 'none';
    void el.offsetWidth;
    el.style.animation = '';
  });
}

function closeDashboard() {
  if (dashboardPanel) dashboardPanel.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');
  document.body.style.overflow = '';
}

if (dashboardBtn) dashboardBtn.addEventListener('click', openDashboard);
dashCloseBtn.addEventListener('click', closeDashboard);

const dashInner = document.querySelector('.dashboard-inner');
let dashTouchStartY = 0;

dashInner.addEventListener('touchstart', (e) => {
  dashTouchStartY = e.touches[0].clientY;
}, { passive: true });

dashInner.addEventListener('touchmove', (e) => {
  const deltaY = e.touches[0].clientY - dashTouchStartY;
  const body   = document.querySelector('.dashboard-body');
  if (deltaY > 0 && body.scrollTop === 0) {
    dashInner.style.transform = `translateY(${Math.min(deltaY * 0.4, 100)}px)`;
  }
}, { passive: true });

dashInner.addEventListener('touchend', (e) => {
  const deltaY = e.changedTouches[0].clientY - dashTouchStartY;
  dashInner.style.transform = '';
  dashInner.style.transition = 'transform 0.4s cubic-bezier(0.32, 0.72, 0, 1)';
  if (deltaY > 80) closeDashboard();
  setTimeout(() => { dashInner.style.transition = ''; }, 400);
});

// ===========================
// 리스트 뱃지 실시간 업데이트
// ===========================
function updateUnreadBadgeInList(person) {
  if (!chatList) return;
  const listItem = chatList.querySelector(`[data-id="${person.id}"]`);
  if (!listItem) return;

  const unread = person.unreadCount || 0;

  // 프로필 도트
  const dot = listItem.querySelector('.chat-count-dot');
  if (dot) {
    if (unread > 0) {
      dot.className = 'chat-count-dot unread';
      dot.textContent = unread;
    } else {
      dot.className = 'chat-count-dot read';
      dot.innerHTML = '<i class="fas fa-check"></i>';
    }
  }

  // 우측 배지
  const badge = listItem.querySelector('.chat-badge');
  if (badge) {
    if (unread > 0) {
      badge.className = 'chat-badge unread-badge';
      badge.textContent = `답변필요 ${unread}`;
    } else {
      badge.className = 'chat-badge done-badge';
      badge.textContent = '완료';
    }
  }

  // 이름·프리뷰 강조
  const nameEl = listItem.querySelector('.chat-name');
  const previewEl = listItem.querySelector('.chat-preview');
  if (nameEl) nameEl.classList.toggle('has-unread', unread > 0);
  if (previewEl) previewEl.classList.toggle('unread-preview', unread > 0);
}

// ===========================
// 채팅화면 오른쪽 스와이프 닫기
// ===========================
let touchStartX = 0;
let touchStartY = 0;
let isSwiping   = false;

chatScreen.addEventListener('touchstart', (e) => {
  touchStartX = e.touches[0].clientX;
  touchStartY = e.touches[0].clientY;
  isSwiping   = false;
}, { passive: true });

chatScreen.addEventListener('touchmove', (e) => {
  const deltaX = e.touches[0].clientX - touchStartX;
  const deltaY = Math.abs(e.touches[0].clientY - touchStartY);
  if (deltaX > 10 && deltaY < 60) {
    isSwiping = true;
    chatScreen.style.transition = 'none';
    chatScreen.style.transform  = `translateX(${Math.max(0, deltaX)}px)`;
  }
}, { passive: true });

chatScreen.addEventListener('touchend', (e) => {
  const deltaX = e.changedTouches[0].clientX - touchStartX;
  chatScreen.style.transition = '';
  chatScreen.style.transform  = '';
  if (isSwiping && deltaX > 100) closeChat();
  isSwiping = false;
});

// ===========================
// 4자 채팅방 - 직접대화 요청 시스템
// ===========================
const directReqBadge   = document.getElementById('directReqBadge');
const directReqCount   = document.getElementById('directReqCount');
const directWaitBanner = document.getElementById('directWaitBanner');
const resumeAvatarBtn  = document.getElementById('resumeAvatarBtn');
const peerSimBar       = document.getElementById('peerSimBar');
const peerDirectReqBtn = document.getElementById('peerDirectReqBtn');
const peerMsgBtn       = document.getElementById('peerMsgBtn');

let directReqPending = 0;   // 미확인 직접대화 요청 수
let avatarPausedByPeer = false; // 상대 요청으로 아바타 일시정지 여부

// 직접대화 요청 수 업데이트
function updateDirectReqBadge() {
  if (directReqPending > 0) {
    directReqBadge.style.display = 'flex';
    directReqCount.textContent = directReqPending;
  } else {
    directReqBadge.style.display = 'none';
  }
}

// 아바타 일시정지 (상대 요청)
function pauseAvatarByPeer() {
  avatarPausedByPeer = true;
  directWaitBanner.style.display = 'flex';

  // 상태 바에 대기 중 표시 (아바타 ON 상태여도 응답 차단됨을 표시)
  bsbLabel.textContent = '⏸ AI 응답 대기 중';
  bsbSub.textContent   = '상대가 직접 대화를 요청했습니다 — 수락하면 직접 답변합니다';

  // 직접대화 요청 시스템 메시지 삽입
  const reqMsg = document.createElement('div');
  reqMsg.className = 'direct-request-msg';
  reqMsg.innerHTML = `
    <span class="drm-icon">✋</span>
    <span>상대방이 직접 대화를 요청했습니다</span>
    <button class="drm-btn" id="acceptDirectBtn">수락</button>
  `;
  chatMessages.appendChild(reqMsg);
  chatMessages.scrollTop = chatMessages.scrollHeight;

  // 수락 버튼: 아바타 끄고 직접 입력 모드
  reqMsg.querySelector('#acceptDirectBtn').addEventListener('click', () => {
    acceptDirectReq();
    reqMsg.style.opacity = '0.5';
    reqMsg.style.pointerEvents = 'none';
  });

  // 뱃지 증가
  directReqPending++;
  updateDirectReqBadge();
}

// 직접대화 요청 수락 (나의 아바타 일시정지 + 직접 입력 모드 전환)
function acceptDirectReq() {
  if (directReqPending > 0) directReqPending--;
  updateDirectReqBadge();
  avatarPausedByPeer = true;

  // 아바타 OFF — change 이벤트가 avatarPausedByPeer를 건드리지 않도록
  // botToggle.checked 변경 전에 avatarPausedByPeer=true를 먼저 세팅했으므로 OK
  isBotOn = false;
  botToggle.checked = false;
  // updateBotUI 직접 호출 (change 이벤트 대신)
  botStatusBar.classList.add('off');
  botAvatarIcon.className = 'bot-avatar-icon off';
  bsbLabel.textContent = 'HI가 대화중 (상대 요청)';
  bsbSub.textContent   = '상대가 직접 대화를 요청했습니다 — 내가 답변해주세요';
  ciBotMode.style.display  = 'none';
  ciUserMode.style.display = 'flex';
  setTimeout(() => ciTextarea.focus(), 100);

  // 안내 메시지
  const sysMsg = document.createElement('div');
  sysMsg.className = 'date-divider';
  sysMsg.innerHTML = `<span>👤 직접 대화 수락 — 아바타 응답 일시정지. [재개] 버튼으로 다시 켤 수 있습니다</span>`;
  chatMessages.appendChild(sysMsg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// 아바타 재개 ([재개] 버튼 또는 토글 수동 ON)
function resumeAvatar() {
  avatarPausedByPeer = false;
  directWaitBanner.style.display = 'none';

  isBotOn = true;
  botToggle.checked = true;
  updateBotUI();

  const sysMsg = document.createElement('div');
  sysMsg.className = 'date-divider';
  sysMsg.innerHTML = `<span>🤖 아바타 챗봇 응답 재개</span>`;
  chatMessages.appendChild(sysMsg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

resumeAvatarBtn.addEventListener('click', resumeAvatar);

// 뱃지 클릭 시 최신 요청으로 스크롤
directReqBadge.addEventListener('click', () => {
  const lastReq = chatMessages.querySelector('.direct-request-msg:last-of-type');
  if (lastReq) {
    lastReq.scrollIntoView({ behavior: 'smooth', block: 'center' });
    lastReq.style.outline = '2px solid #f59e0b';
    setTimeout(() => { lastReq.style.outline = ''; }, 1500);
  }
});

// ===========================
// 상대방 시뮬레이션 버튼 (데모용)
// ===========================
const peerMessages = [
  '안녕하세요! 반갑습니다 😊',
  '제품 관련 문의가 있어서요.',
  '견적서를 받을 수 있을까요?',
  '미팅 일정을 잡고 싶습니다.',
  '감사합니다! 잘 부탁드립니다.',
];
let peerMsgIdx = 0;

peerDirectReqBtn.addEventListener('click', () => {
  // 비활성화 처리 (2026-03-31: 직접 대화 요청 기능 비활성화)
  return false;
});

peerMsgBtn.addEventListener('click', () => {
  const text = peerMessages[peerMsgIdx % peerMessages.length];
  peerMsgIdx++;
  const now = new Date();
  const timeStr = `${now.getHours()}:${String(now.getMinutes()).padStart(2,'0')}`;
  const div = document.createElement('div');
  div.className = 'msg-row user';
  div.style.animation = 'msgIn 0.25s ease both';
  div.innerHTML = `
    <div class="msg-avatar">
      <i class="fas fa-user-tie" style="font-size:12px;color:#64748b;"></i>
    </div>
    <div class="msg-body">
      <span class="msg-sender-label label-user">👤 상대</span>
      <div class="msg-bubble">${escapeHtml(text)}</div>
      <span class="msg-time">${timeStr}</span>
    </div>
  `;
  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;

  const dateStr = `${now.toISOString().slice(0,10)}`;

  // ── 상대 메시지 데이터 저장 ──
  if (currentPerson) {
    currentPerson.messages.push({
      type: 'user',
      text,
      time: `${dateStr} ${timeStr}`,
      read: isBotOn && !avatarPausedByPeer // 아바타가 바로 처리할 거면 읽음
    });
    updateChatMeta(currentPerson);
  }

  // 아바타 OFF이거나 직접대화 요청 수락 상태면 → 내가 직접 답해야 하므로 뱃지 증가
  const needsReply = !isBotOn || avatarPausedByPeer;
  if (needsReply && currentPerson) {
    currentPerson.unreadCount = (currentPerson.unreadCount || 0) + 1;
    updateUnreadBadgeInList(currentPerson);
  }

  // 아바타 ON이고 일시정지 아닐 때 → 자동 응답 시뮬레이션
  if (isBotOn && !avatarPausedByPeer) {
    setTimeout(() => {
      const now2 = new Date();
      const t2 = `${now2.getHours()}:${String(now2.getMinutes()).padStart(2,'0')}`;
      const d2 = `${now2.toISOString().slice(0,10)}`;
      const botResponse = '안녕하세요! 저는 송조은님의 AI 아바타입니다. 무엇을 도와드릴까요? 😊';

      // ── 아바타 응답 데이터 저장 ──
      if (currentPerson) {
        currentPerson.messages.push({
          type: 'bot',
          text: botResponse,
          time: `${d2} ${t2}`,
          read: true
        });
        currentPerson.unreadCount = 0;
        currentPerson.messages.forEach(m => { if (m.type === 'user') m.read = true; });
        updateChatMeta(currentPerson);
        updateUnreadBadgeInList(currentPerson);
      }

      const botDiv = document.createElement('div');
      botDiv.className = 'msg-row bot';
      botDiv.style.animation = 'msgIn 0.25s ease both';
      botDiv.innerHTML = `
        <div class="msg-body">
          <span class="msg-sender-label label-my-bot">🤖 MY AI</span>
          <div class="msg-bubble my-bot-bubble">${escapeHtml(botResponse)}</div>
          <span class="msg-time">${t2}</span>
        </div>
      `;
      chatMessages.appendChild(botDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 1200);
  }
});

// ===========================
// 프롬프팅 설정 패널 (재설계)
// ===========================
const promptPanel     = document.getElementById('promptPanel');
const promptCloseBtn  = document.getElementById('promptCloseBtn');
const ppAvatar        = document.getElementById('ppAvatar');
const ppName          = document.getElementById('ppName');
const ppNameTag       = document.getElementById('ppNameTag');
const ppModeRadios          = document.querySelectorAll('input[name="ppMode"]');
const ppPaneDefault         = document.getElementById('ppPaneDefault');
const ppPaneCustom          = document.getElementById('ppPaneCustom');
const ppPaneMerged          = document.getElementById('ppPaneMerged');
const ppDefaultPromptText   = document.getElementById('ppDefaultPromptText');
const ppMergedBaseText      = document.getElementById('ppMergedBaseText');
const ppToneGrid            = document.getElementById('ppToneGrid');
const ppToneGridMerged      = document.getElementById('ppToneGridMerged');
const ppRelationSelect      = document.getElementById('ppRelationSelect');
const ppPurposeSelect       = document.getElementById('ppPurposeSelect');
const ppRelationSelectMerged= document.getElementById('ppRelationSelectMerged');
const ppPurposeSelectMerged = document.getElementById('ppPurposeSelectMerged');
const ppCustomText          = document.getElementById('ppCustomText');
const ppMergedExtra         = document.getElementById('ppMergedExtra');
const ppCharCount           = document.getElementById('ppCharCount');
const ppMergedCharCount     = document.getElementById('ppMergedCharCount');
const ppPreviewWrap         = document.getElementById('ppPreviewWrap');
const ppPreviewBox          = document.getElementById('ppPreviewBox');
const ppPreviewBtn          = document.getElementById('ppPreviewBtn');
const ppSaveBtn             = document.getElementById('ppSaveBtn');
const ppEditGlobalBtn       = document.getElementById('ppEditGlobalBtn');
const chatInfoBtn           = document.getElementById('chatInfoBtn');

// 기본 전역 프롬프트
let globalPrompt = `당신은 [운영자 이름]의 AI 아바타입니다.
운영자의 말투와 가치관을 반영하여 상대방과 자연스럽게 대화하세요.
항상 따뜻하고 진심 어린 태도로 응대하며, 상대방이 운영자와 직접 대화하는 것처럼 느끼도록 합니다.
거짓 정보를 제공하지 말고, 모르는 내용은 솔직하게 말하세요.`;

const tonePromptMap = {
  formal:  '존댓말과 격식체를 사용하며 공손하고 전문적인 어조로 대화합니다.',
  friendly:'친근하고 따뜻한 말투로 편안한 분위기를 만들어 대화합니다.',
  expert:  '전문가적 시각으로 분석적이고 정확한 정보를 제공하며 대화합니다.',
  casual:  '가볍고 유머 있는 캐주얼한 어조로 대화하되 예의는 지킵니다.',
  empathy: '감성적이고 공감 중심의 위로하는 말투로 상대의 감정을 먼저 헤아립니다.',
  brief:   '짧고 핵심만 담은 간결한 문장으로 빠르게 소통합니다.',
};
const relationPromptMap = {
  client:   '상대방은 고객 또는 잠재 고객입니다. 서비스 가치를 자연스럽게 전달하세요.',
  partner:  '상대방은 파트너 또는 협력사입니다. 상호 이익을 중심으로 소통하세요.',
  vip:      '상대방은 VIP 또는 중요 인사입니다. 특별한 관심과 배려를 표현하세요.',
  colleague:'상대방은 동료 또는 지인입니다. 친밀하고 협력적인 태도로 대화하세요.',
  media:    '상대방은 언론 또는 미디어 관계자입니다. 명확하고 신중하게 표현하세요.',
  political:'상대방은 정계 또는 공직자입니다. 격식을 갖추고 정치적 중립성을 유지하세요.',
  investor: '상대방은 투자자 또는 후원자입니다. 신뢰와 비전을 중심으로 소통하세요.',
};
const purposePromptMap = {
  introduce:'대화의 목적은 서비스·제품 소개입니다. 자연스럽게 가치를 전달하세요.',
  consult:  '대화의 목적은 상담·문의 응대입니다. 질문에 친절하고 정확하게 답변하세요.',
  followup: '대화의 목적은 팔로업 및 관계 유지입니다. 따뜻하게 안부를 전하세요.',
  schedule: '대화의 목적은 미팅·일정 조율입니다. 효율적으로 일정을 조율하세요.',
  support:  '대화의 목적은 기술·고객 지원입니다. 문제 해결에 집중하세요.',
  network:  '대화의 목적은 네트워킹입니다. 관심사를 공유하고 친분을 쌓으세요.',
};

// 현재 선택 상태 (custom / merged 각각 별도)
let toneCustom = '', toneMerged = '';

// ── 패널 열기 ──
function openPromptPanel(person) {
  if (!person) return;

  ppAvatar.textContent  = person.name.charAt(0);
  ppAvatar.className    = `pp-avatar ${person.colorClass}`;
  ppName.textContent    = person.name;
  ppNameTag.textContent = person.name;

  // 기본 프롬프트 표시
  ppDefaultPromptText.textContent = globalPrompt;
  ppMergedBaseText.textContent    = globalPrompt;

  // 저장된 설정 복원
  const s = person.promptConfig || { mode:'default', toneCustom:'', toneMerged:'',
    relationCustom:'', purposeCustom:'', custom:'',
    relationMerged:'', purposeMerged:'', mergedExtra:'' };

  ppModeRadios.forEach(r => { r.checked = (r.value === s.mode); });

  // custom 패널
  toneCustom = s.toneCustom || '';
  ppToneGrid.querySelectorAll('.pp-tone-btn').forEach(b =>
    b.classList.toggle('selected', b.dataset.tone === toneCustom));
  ppRelationSelect.value = s.relationCustom || '';
  ppPurposeSelect.value  = s.purposeCustom  || '';
  ppCustomText.value     = s.custom || '';
  ppCharCount.textContent = ppCustomText.value.length;

  // merged 패널
  toneMerged = s.toneMerged || '';
  ppToneGridMerged.querySelectorAll('.pp-tone-btn').forEach(b =>
    b.classList.toggle('selected', b.dataset.tone === toneMerged));
  ppRelationSelectMerged.value = s.relationMerged || '';
  ppPurposeSelectMerged.value  = s.purposeMerged  || '';
  ppMergedExtra.value          = s.mergedExtra || '';
  ppMergedCharCount.textContent = ppMergedExtra.value.length;

  ppPreviewWrap.style.display = 'none';
  updatePPPanes(s.mode);

  promptPanel.classList.add('open');
  document.body.style.overflow = 'hidden';
}

// ── 패널 닫기 ──
function closePromptPanel() {
  promptPanel.classList.remove('open');
  document.body.style.overflow = '';
}

// ── 모드별 패널 전환 ──
function updatePPPanes(mode) {
  ppPaneDefault.style.display = mode === 'default' ? 'block' : 'none';
  ppPaneCustom.style.display  = mode === 'custom'  ? 'block' : 'none';
  ppPaneMerged.style.display  = mode === 'merged'  ? 'block' : 'none';
  ppPreviewWrap.style.display = 'none';
}

ppModeRadios.forEach(r => {
  r.addEventListener('change', () => updatePPPanes(r.value));
});

// ── 톤 버튼 (custom) ──
ppToneGrid.querySelectorAll('.pp-tone-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    toneCustom = btn.dataset.tone === toneCustom ? '' : btn.dataset.tone;
    ppToneGrid.querySelectorAll('.pp-tone-btn').forEach(b =>
      b.classList.toggle('selected', b.dataset.tone === toneCustom));
  });
});

// ── 톤 버튼 (merged) ──
ppToneGridMerged.querySelectorAll('.pp-tone-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    toneMerged = btn.dataset.tone === toneMerged ? '' : btn.dataset.tone;
    ppToneGridMerged.querySelectorAll('.pp-tone-btn').forEach(b =>
      b.classList.toggle('selected', b.dataset.tone === toneMerged));
  });
});

// ── 글자 수 카운터 ──
ppCustomText.addEventListener('input', () => {
  ppCharCount.textContent = ppCustomText.value.length;
});
ppMergedExtra.addEventListener('input', () => {
  ppMergedCharCount.textContent = ppMergedExtra.value.length;
});

// ── 최종 프롬프트 생성 ──
function buildFinalPrompt(mode) {
  if (mode === 'default') {
    return globalPrompt;
  }
  if (mode === 'custom') {
    const parts = ['【맞춤 프롬프트】'];
    if (toneCustom)                    parts.push('▸ 말투: ' + tonePromptMap[toneCustom]);
    if (ppRelationSelect.value)        parts.push('▸ 관계: ' + relationPromptMap[ppRelationSelect.value]);
    if (ppPurposeSelect.value)         parts.push('▸ 목적: ' + purposePromptMap[ppPurposeSelect.value]);
    if (ppCustomText.value.trim())     parts.push('\n【직접 작성 지시사항】\n' + ppCustomText.value.trim());
    return parts.join('\n');
  }
  if (mode === 'merged') {
    const parts = ['【기본 프롬프트】\n' + globalPrompt, '\n【추가 맞춤 설정】'];
    if (toneMerged)                         parts.push('▸ 말투: ' + tonePromptMap[toneMerged]);
    if (ppRelationSelectMerged.value)       parts.push('▸ 관계: ' + relationPromptMap[ppRelationSelectMerged.value]);
    if (ppPurposeSelectMerged.value)        parts.push('▸ 목적: ' + purposePromptMap[ppPurposeSelectMerged.value]);
    if (ppMergedExtra.value.trim())         parts.push('\n【추가 지시사항】\n' + ppMergedExtra.value.trim());
    return parts.join('\n');
  }
  return globalPrompt;
}

// ── 미리보기 버튼 ──
ppPreviewBtn.addEventListener('click', () => {
  const mode = [...ppModeRadios].find(r => r.checked)?.value || 'default';
  ppPreviewBox.textContent = buildFinalPrompt(mode);
  ppPreviewWrap.style.display = 'block';
  setTimeout(() => ppPreviewWrap.scrollIntoView({ behavior:'smooth', block:'nearest' }), 50);
});

// ── 적용하기 버튼 ──
ppSaveBtn.addEventListener('click', () => {
  if (!currentPerson) return;
  const mode = [...ppModeRadios].find(r => r.checked)?.value || 'default';

  currentPerson.promptConfig = {
    mode,
    toneCustom, toneMerged,
    relationCustom: ppRelationSelect.value,
    purposeCustom:  ppPurposeSelect.value,
    custom:         ppCustomText.value,
    relationMerged: ppRelationSelectMerged.value,
    purposeMerged:  ppPurposeSelectMerged.value,
    mergedExtra:    ppMergedExtra.value,
  };
  currentPerson.finalPrompt = buildFinalPrompt(mode);

  // info 버튼 상태 표시
  if (mode !== 'default') {
    chatInfoBtn.classList.add('prompt-set');
  } else {
    chatInfoBtn.classList.remove('prompt-set');
  }

  // 채팅창 시스템 메시지
  const label = { default:'기본', custom:'맞춤', merged:'기본+맞춤' }[mode];
  const sys = document.createElement('div');
  sys.className = 'date-divider';
  sys.innerHTML = `<span>⚙️ 프롬프팅 적용: ${label}</span>`;
  chatMessages.appendChild(sys);
  chatMessages.scrollTop = chatMessages.scrollHeight;

  ppSaveBtn.innerHTML = '<i class="fas fa-check"></i> 적용 완료!';
  ppSaveBtn.style.background = '#22c55e';
  setTimeout(() => {
    ppSaveBtn.innerHTML = '<i class="fas fa-check"></i> 적용하기';
    ppSaveBtn.style.background = '';
    closePromptPanel();
  }, 800);
});

// ── 기본 프롬프트 편집 버튼 (인라인 편집 전환) ──
ppEditGlobalBtn.addEventListener('click', () => {
  // 읽기전용 박스를 textarea로 교체
  const box = document.getElementById('ppDefaultPromptText');
  if (box.tagName === 'DIV') {
    const ta = document.createElement('textarea');
    ta.className = 'pp-textarea';
    ta.id = 'ppDefaultPromptText';
    ta.value = globalPrompt;
    ta.rows = 6;
    box.replaceWith(ta);
    ta.focus();
    ppEditGlobalBtn.innerHTML = '<i class="fas fa-save"></i> 저장';
  } else {
    // textarea → div 로 되돌리고 저장
    globalPrompt = box.value.trim() || globalPrompt;
    localStorage.setItem('globalPrompt', globalPrompt);
    const div = document.createElement('div');
    div.className = 'pp-readonly-box';
    div.id = 'ppDefaultPromptText';
    div.textContent = globalPrompt;
    box.replaceWith(div);
    ppMergedBaseText.textContent = globalPrompt;
    ppEditGlobalBtn.innerHTML = '<i class="fas fa-pen"></i> 기본 프롬프트 편집';
  }
});

// ── chatInfoBtn 클릭 ──
chatInfoBtn.addEventListener('click', () => openPromptPanel(currentPerson));

// 배경 / 닫기 버튼
promptPanel.addEventListener('click', e => { if (e.target === promptPanel) closePromptPanel(); });
promptCloseBtn.addEventListener('click', closePromptPanel);

// localStorage 복원
const savedGlobalPrompt = localStorage.getItem('globalPrompt');
if (savedGlobalPrompt) globalPrompt = savedGlobalPrompt;

// ===========================
// 테마 (라이트 / 다크)
// ===========================
const themeLight = document.getElementById('themeLight');
const themeDark  = document.getElementById('themeDark');

function applyTheme(theme) {
  if (theme === 'light') {
    document.body.classList.add('theme-light');
    if (themeLight) themeLight.classList.add('active');
    if (themeDark) themeDark.classList.remove('active');
  } else {
    document.body.classList.remove('theme-light');
    if (themeDark) themeDark.classList.add('active');
    if (themeLight) themeLight.classList.remove('active');
  }
  localStorage.setItem('appTheme', theme);
}

if (themeLight) themeLight.addEventListener('click', () => { applyTheme('light'); });
if (themeDark) themeDark.addEventListener('click',  () => { applyTheme('dark');  });

// 저장된 테마 복원
const savedTheme = localStorage.getItem('appTheme') || 'dark';
applyTheme(savedTheme);

// ===========================
// 프로필 설정 패널
// ===========================
const profilePanel        = document.getElementById('profilePanel');
const profilePanelOverlay = document.getElementById('profilePanelOverlay');
const profileCloseBtn     = document.getElementById('profileCloseBtn');
const openProfileBtn      = document.getElementById('openProfileBtn');
const openPwaVisitorBtn   = document.getElementById('openPwaVisitorBtn');
const pfSaveBtn           = document.getElementById('pfSaveBtn');
const pfCancelBtn         = document.getElementById('pfCancelBtn');
const pfPhotoInput        = document.getElementById('pfPhotoInput');
const pfPhotoRemove       = document.getElementById('pfPhotoRemove');
const pfAvatarImg         = document.getElementById('pfAvatarImg');
const pfAvatarInitial     = document.getElementById('pfAvatarInitial');
const pfAvatarPreview     = document.getElementById('pfAvatarPreview');
const pfCopyLinkBtn       = document.getElementById('pfCopyLinkBtn');
const pfUpdateLinkBtn     = document.getElementById('pfUpdateLinkBtn');
const pfDownloadQr        = document.getElementById('pfDownloadQr');
const pfShareQr           = document.getElementById('pfShareQr');
const pfQrBox             = document.getElementById('pfQrBox');

// 프로필 데이터 (localStorage 우선)
let profileData = {
  name:     '',
  title:    '',
  status:   '',
  phone:    '',
  email:    '',
  company:  '',
  photo:    null,
  shortCode: '',
  globalPrompt: ''
};

function loadProfileData() {
  const saved = localStorage.getItem('profileData');
  if (saved) {
    try { profileData = { ...profileData, ...JSON.parse(saved) }; } catch(e) {}
  }
}

function saveProfileData() {
  localStorage.setItem('profileData', JSON.stringify(profileData));
}

function applyProfileToHeader() {
  // 헤더 아바타 이니셜 & 이름 업데이트
  const adpAvatar = document.querySelector('.adp-avatar');
  const adpName   = document.querySelector('.adp-name');
  const adpRole   = document.querySelector('.adp-role');
  const headerAvatar = document.getElementById('avatarBtn');

  const initial = profileData.name ? profileData.name.charAt(0) : 'J';
  if (adpAvatar) {
    if (profileData.photo) {
      adpAvatar.innerHTML = `<img src="${profileData.photo}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />`;
    } else {
      adpAvatar.textContent = initial;
    }
  }
  if (adpName) adpName.textContent = profileData.name || '';
  if (adpRole) adpRole.textContent = profileData.title || '';
  if (headerAvatar) {
    if (profileData.photo) {
      headerAvatar.innerHTML = `<img src="${profileData.photo}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />`;
    } else {
      headerAvatar.textContent = initial;
    }
  }
}

function openProfilePanel() {
  // 닫기 아바타 드롭다운
  document.getElementById('avatarDropdown').classList.remove('open');

  // 폼 채우기
  document.getElementById('pfName').value    = profileData.name    || '';
  document.getElementById('pfTitle').value   = profileData.title   || '';
  document.getElementById('pfStatus').value  = profileData.status  || '';
  document.getElementById('pfPhone').value   = profileData.phone   || '';
  document.getElementById('pfEmail').value   = profileData.email   || '';
  document.getElementById('pfCompany').value = profileData.company || '';
  document.getElementById('pfShortCode').value = profileData.shortCode || '';
  document.getElementById('pfGlobalPrompt').value = profileData.globalPrompt || globalPrompt || '';

  // 사진 미리보기
  if (profileData.photo) {
    pfAvatarImg.src = profileData.photo;
    pfAvatarImg.style.display = 'block';
    pfAvatarInitial.style.display = 'none';
    pfPhotoRemove.style.display = 'inline-flex';
  } else {
    pfAvatarImg.style.display = 'none';
    pfAvatarInitial.textContent = profileData.name ? profileData.name.charAt(0) : '·';
    pfAvatarInitial.style.display = '';
    pfPhotoRemove.style.display = 'none';
  }

  // 링크
  updateChatbotLink();

  // QR 생성
  generateQR();

  profilePanel.classList.add('open');
  profilePanelOverlay.classList.add('visible');
  document.body.style.overflow = 'hidden';
}

function closeProfilePanel() {
  profilePanel.classList.remove('open');
  profilePanelOverlay.classList.remove('visible');
  document.body.style.overflow = '';
}

function updateChatbotLink() {
  const code = document.getElementById('pfShortCode').value.trim();
  const linkInput = document.getElementById('pfChatbotLink');
  if (!code) {
    linkInput.value = '';
    linkInput.placeholder = '단축 코드를 입력하면 링크가 생성됩니다';
    return;
  }
  linkInput.value = `https://chatbot.kiam.kr/s/${code}`;
  linkInput.placeholder = '';
}

// QR 코드 생성 (qrcode.js CDN 사용)
function generateQR() {
  const link = document.getElementById('pfChatbotLink').value;
  pfQrBox.innerHTML = '';
  if (!link) {
    pfQrBox.innerHTML = '<div style="color:#94a3b8;font-size:12px;text-align:center;padding:20px 10px;"><i class="fas fa-qrcode" style="font-size:28px;display:block;margin-bottom:8px;"></i>단축 코드 등록 후<br>QR이 생성됩니다</div>';
    return;
  }

  if (typeof QRCode !== 'undefined') {
    new QRCode(pfQrBox, {
      text: link,
      width: 144,
      height: 144,
      colorDark: '#1e293b',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
  } else {
    // fallback: API QR
    const img = document.createElement('img');
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=144x144&data=${encodeURIComponent(link)}`;
    img.alt = 'QR 코드';
    img.style.width = '100%';
    img.style.height = '100%';
    pfQrBox.appendChild(img);
  }
}

// 이벤트 리스너
openProfileBtn.addEventListener('click', openProfilePanel);

// ── PWA 등록자 패널 ─────────────────────────────────────────
let pvaPage = 1, pvaLoading = false, pvaHasMore = false, pvaQuery = '';
let pvaSearchTimer = null;

if (openPwaVisitorBtn) {
  openPwaVisitorBtn.addEventListener('click', () => {
    document.getElementById('avatarDropdown').classList.remove('open');
    openPvaPanel();
  });
}

function openPvaPanel() {
  const panel = document.getElementById('pwaVisitorPanel');
  panel.style.display = 'flex';
  pvaPage = 1; pvaQuery = '';
  document.getElementById('pvaSearchInput').value = '';
  document.getElementById('pvaList').innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오는 중...</div>';
  loadPvaList(true);
}

function closePvaVisitorPanel() {
  document.getElementById('pwaVisitorPanel').style.display = 'none';
}

// 패널 바깥 클릭 시 닫기
document.getElementById('pwaVisitorPanel')?.addEventListener('click', function(e) {
  if (e.target === this) closePvaVisitorPanel();
});

function onPvaSearch(val) {
  clearTimeout(pvaSearchTimer);
  pvaSearchTimer = setTimeout(() => {
    pvaQuery = val.trim();
    pvaPage = 1;
    loadPvaList(true);
  }, 350);
}

async function loadPvaList(reset = false) {
  if (pvaLoading) return;
  pvaLoading = true;
  const list = document.getElementById('pvaList');
  const moreBtn = document.getElementById('pvaMore');
  if (reset) { list.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오는 중...</div>'; }

  const params = new URLSearchParams({ page: pvaPage, phone_only: 1 });
  if (pvaQuery) params.set('q', pvaQuery);
  const data = await apiGet('visitor_list.php', Object.fromEntries(params));
  pvaLoading = false;

  if (!data || !data.success) {
    list.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오기 실패</div>';
    return;
  }

  if (reset) list.innerHTML = '';
  if (data.visitors.length === 0 && reset) {
    list.innerHTML = '<div style="text-align:center;padding:40px;color:#6b7280;"><div style="font-size:32px;margin-bottom:10px;">📭</div>아직 PWA 등록자가 없습니다</div>';
    moreBtn.style.display = 'none';
    return;
  }

  data.visitors.forEach(v => {
    const name = v.custom_name || v.visitor_id;
    const dt = v.updated_at || v.last_chat || '';
    const dateStr = dt ? dt.slice(0,16).replace('T',' ') : '-';
    const chatUrl = v.short_code ? '/s/' + v.short_code : '';
    const card = document.createElement('div');
    card.style.cssText = 'background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:14px;';
    card.innerHTML = `
      <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📲</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;">${name}</div>
        <div style="font-size:13px;color:#a78bfa;font-weight:600;">${v.phone}</div>
        <div style="font-size:11px;color:#6b7280;margin-top:2px;">${v.chatbot_name || ''} · ${dateStr}</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
        <button onclick="pvaOpenChat('${v.visitor_id}','${v.sms_idx}')" style="padding:7px 12px;background:#2563eb;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer;white-space:nowrap;">대화보기</button>
      </div>
    `;
    list.appendChild(card);
  });

  pvaHasMore = data.has_more;
  moreBtn.style.display = pvaHasMore ? 'block' : 'none';
}

async function loadPvaMore() {
  pvaPage++;
  await loadPvaList(false);
}

async function pvaOpenChat(visitorId, smsIdx) {
  closePvaVisitorPanel();
  // 공유 탭으로 이동 후 해당 방문자 채팅 오픈
  switchTab('shared');
  setTimeout(async () => {
    const data = await apiGet('visitor_list.php', { mode: 'history', visitor_id: visitorId, sms_idx: smsIdx });
    if (!data || !data.success) return;
    // visitor_list에서 해당 항목 찾아 클릭
    const items = document.querySelectorAll('#sharedList .contact-item');
    for (const item of items) {
      if (item.dataset.visitorId === visitorId) { item.click(); return; }
    }
  }, 400);
}
profileCloseBtn.addEventListener('click', closeProfilePanel);
pfCancelBtn.addEventListener('click', closeProfilePanel);
profilePanelOverlay.addEventListener('click', closeProfilePanel);

// 사진 업로드
pfPhotoInput.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    profileData.photo = ev.target.result;
    pfAvatarImg.src = ev.target.result;
    pfAvatarImg.style.display = 'block';
    pfAvatarInitial.style.display = 'none';
    pfPhotoRemove.style.display = 'inline-flex';
  };
  reader.readAsDataURL(file);
});

// 사진 삭제
pfPhotoRemove.addEventListener('click', () => {
  profileData.photo = null;
  pfAvatarImg.src = '';
  pfAvatarImg.style.display = 'none';
  pfAvatarInitial.textContent = document.getElementById('pfName').value.charAt(0) || 'J';
  pfAvatarInitial.style.display = '';
  pfPhotoRemove.style.display = 'none';
  pfPhotoInput.value = '';
});

// 단축코드 변경 → 링크/QR 업데이트
pfUpdateLinkBtn.addEventListener('click', () => {
  updateChatbotLink();
  generateQR();
  showToast('챗봇 링크가 업데이트되었습니다 ✅');
});

// 링크 복사
pfCopyLinkBtn.addEventListener('click', () => {
  const link = document.getElementById('pfChatbotLink').value;
  if (!link) { showToast('단축 코드를 먼저 등록해주세요'); return; }
  navigator.clipboard.writeText(link).then(() => {
    showToast('링크가 클립보드에 복사되었습니다 📋');
  }).catch(() => {
    // fallback
    const ta = document.createElement('textarea');
    ta.value = link;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('링크가 복사되었습니다 📋');
  });
});

// QR 다운로드
pfDownloadQr.addEventListener('click', () => {
  const canvas = pfQrBox.querySelector('canvas');
  const img    = pfQrBox.querySelector('img');
  if (canvas) {
    const link = document.createElement('a');
    link.download = 'chatbot-qr.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
  } else if (img) {
    showToast('QR 이미지를 길게 눌러 저장하세요 📥');
  }
});

// QR 공유
pfShareQr.addEventListener('click', () => {
  const link = document.getElementById('pfChatbotLink').value;
  if (navigator.share) {
    navigator.share({ title: '내 챗봇 링크', url: link });
  } else {
    showToast('링크를 공유할 수 없습니다. 복사 버튼을 이용하세요.');
  }
});

// 저장
pfSaveBtn.addEventListener('click', () => {
  profileData.name       = document.getElementById('pfName').value.trim();
  profileData.title      = document.getElementById('pfTitle').value.trim();
  profileData.status     = document.getElementById('pfStatus').value.trim();
  profileData.phone      = document.getElementById('pfPhone').value.trim();
  profileData.email      = document.getElementById('pfEmail').value.trim();
  profileData.company    = document.getElementById('pfCompany').value.trim();
  profileData.shortCode  = document.getElementById('pfShortCode').value.trim();
  const newPrompt        = document.getElementById('pfGlobalPrompt').value.trim();
  if (newPrompt) {
    profileData.globalPrompt = newPrompt;
    globalPrompt = newPrompt;
    localStorage.setItem('globalPrompt', newPrompt);
  }
  saveProfileData();
  applyProfileToHeader();
  closeProfilePanel();
  showToast('프로필이 저장되었습니다 ✅');

  // shortCode → DB 등록 (공유링크 자동 등록)
  if (profileData.shortCode) {
    var payload = { short_code: profileData.shortCode };
    if (typeof contactSmsIdx !== 'undefined' && contactSmsIdx > 0) {
      payload.sms_idx = contactSmsIdx;
    }
    apiPost('shared_link.php', payload).catch(function() {});
  }
});

// 토스트 헬퍼
function showToast(msg) {
  let toast = document.querySelector('.pf-copy-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'pf-copy-toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2200);
}

// 초기 프로필 로드 & 헤더 적용
loadProfileData();
applyProfileToHeader();

// ===========================
// 사용자 상태 시스템
// userState: 'member-with-bot' | 'member-no-bot' | 'guest'
// ===========================
let userState = localStorage.getItem('userState') || 'member-with-bot';

const makeBotBtn   = document.getElementById('makeBotBtn');
const makeBotPanel = document.getElementById('makeBotPanel');
const makeBotOverlay = document.getElementById('makeBotOverlay');
const makeBotCloseBtn = document.getElementById('makeBotCloseBtn');
const homeNavBtn   = document.getElementById('homeNavBtn');

// 상태 적용 함수
function applyUserState(state) {
  userState = state;
  localStorage.setItem('userState', state);

  const isMemberWithBot = state === 'member-with-bot';
  const isMemberNoBot   = state === 'member-no-bot';
  const isGuest         = state === 'guest';

  // 1. MY 챗봇 만들기 버튼: 챗봇이 없는 경우에만 표시
  if (makeBotBtn) makeBotBtn.style.display = (isMemberNoBot || isGuest) ? '' : 'none';

  // 2. 헤더 아바타 영역: 비회원은 숨김 (챗봇 관리 기능 없음)
  const avatarWrap = document.querySelector('.avatar-wrap');
  if (avatarWrap) avatarWrap.style.display = isGuest ? 'none' : '';

  // 3. 명함리스트: 비회원이면 빈 안내 화면 표시
  renderContactsWithState();

  // 4. 데모 상태 버튼 활성화
  document.querySelectorAll('.usb-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.state === state);
  });
}

// 명함리스트 렌더 (상태 반영)
function renderContactsWithState() {
  if (userState === 'guest') {
    // 비회원: 빈 안내
    contactsList.innerHTML = `
      <div class="guest-empty-wrap">
        <div class="guest-empty-icon"><i class="fas fa-address-card"></i></div>
        <div class="guest-empty-title">명함리스트를 이용하려면<br>회원가입이 필요해요</div>
        <div class="guest-empty-desc">아이엠 앱에 가입하고 로그인하면<br>나와 대화한 상대의 명함을 저장하고<br>챗봇으로 관리할 수 있어요.</div>
        <button class="guest-empty-btn" onclick="openMakeBotPanel()">
          <i class="fas fa-rocket"></i> MY 챗봇 만들기
        </button>
        <div style="margin-top:8px">
          <a href="https://kiam.kr" target="_blank" style="font-size:12px;color:var(--accent-blue);text-decoration:none;font-weight:600;">
            <i class="fas fa-external-link-alt" style="margin-right:4px;"></i>아이엠 홈페이지 방문하기
          </a>
        </div>
      </div>`;
    contactsBadge.textContent = '0명';
  } else {
    // 회원: 일반 렌더
    renderContactsList();
  }
}

// 챗봇 만들기 패널 열기
function openMakeBotPanel() {
  // 어떤 플로우 보여줄지 결정
  const flowMember = document.getElementById('flowMember');
  const flowGuest  = document.getElementById('flowGuest');

  if (userState === 'member-no-bot') {
    // 회원 → 즉시 발급 플로우
    flowMember.style.display = 'flex';
    flowGuest.style.display  = 'none';
    // 링크 미리 생성 (고유 코드)
    const uniqueCode = 'U' + Math.random().toString(36).substr(2,6).toUpperCase();
    document.getElementById('mbLinkValue').textContent = `https://chatbot.kiam.kr/s/${uniqueCode}`;
    // 아직 발급 전 상태로 초기화
    document.getElementById('mbMemberIssueBtn').style.display = '';
    document.getElementById('mbIssuedWrap').style.display = 'none';
  } else {
    // 비회원 → 가입→로그인→설치→발급 플로우
    flowMember.style.display = 'none';
    flowGuest.style.display  = 'flex';
    // Step 1부터 시작
    guestStep = 1;
    renderGuestStep(1);
  }

  makeBotPanel.classList.add('open');
  makeBotOverlay.classList.add('visible');
  document.body.style.overflow = 'hidden';
}

function closeMakeBotPanel() {
  makeBotPanel.classList.remove('open');
  makeBotOverlay.classList.remove('visible');
  document.body.style.overflow = '';
}

// 비회원 스텝 관리
let guestStep = 1;

function renderGuestStep(step) {
  guestStep = step;
  // 컨텐츠 전환
  [1,2,3,4].forEach(n => {
    const el = document.getElementById(`mbStep${n}`);
    if (el) el.style.display = n === step ? 'flex' : 'none';
  });
  // 스텝 인디케이터 업데이트
  document.querySelectorAll('.mb-step').forEach(el => {
    const n = parseInt(el.dataset.step);
    el.classList.remove('active','done');
    if (n < step)  el.classList.add('done');
    if (n === step) el.classList.add('active');
  });
  // 구분선 업데이트
  document.querySelectorAll('.mb-step-line').forEach((el, i) => {
    el.classList.toggle('done', i + 1 < step);
  });
}

// 링크 발급 공통 함수
function issueBotLink(targetElId) {
  const uniqueCode = 'K' + Math.random().toString(36).substr(2,7).toUpperCase();
  const link = `https://chatbot.kiam.kr/s/${uniqueCode}`;
  document.getElementById(targetElId).textContent = link;
  return link;
}

// ── 회원/비회원 플로우 이벤트 (챗봇 만들기 패널 - 없는 페이지에서는 스킵) ──
try {
document.getElementById('mbMemberIssueBtn').addEventListener('click', async () => {
  const btn = document.getElementById('mbMemberIssueBtn');
  btn.disabled = true;
  btn.textContent = '⏳ 챗봇 생성 중...';
  try {
    const res = await fetch('/aimessage/onechat/api/create_chatbot.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || '생성 실패');

    const link = data.chatbot_url;
    document.getElementById('mbLinkCard').style.display = 'none';
    btn.style.display = 'none';
    document.getElementById('mbIssuedWrap').style.display = 'flex';
    document.getElementById('mbIssuedLink').textContent = link;

    // 프로필의 shortCode/챗봇 링크도 즉시 업데이트
    if (profileData) {
      profileData.shortCode = link.split('/s/')[1] || profileData.shortCode;
      saveProfileData();
      updateChatbotLink(profileData.shortCode);
    }
    // has_bot 상태 전환
    applyUserState('member-with-bot');
    showToast(data.already ? '✅ 챗봇이 이미 있습니다! 링크를 확인하세요.' : '🎉 챗봇이 생성되었습니다!');
  } catch(e) {
    btn.disabled = false;
    btn.textContent = '🚀 챗봇 링크 발급받기';
    showToast('❌ 챗봇 생성 실패: ' + e.message);
  }
});

document.getElementById('mbCopyIssuedBtn').addEventListener('click', () => {
  const link = document.getElementById('mbIssuedLink').textContent;
  navigator.clipboard.writeText(link).catch(()=>{});
  showToast('링크가 복사되었습니다 📋');
});

document.getElementById('mbShareIssuedBtn').addEventListener('click', () => {
  const link = document.getElementById('mbIssuedLink').textContent;
  if (navigator.share) navigator.share({ title: '내 챗봇 링크', url: link });
  else showToast('링크를 공유할 수 없습니다. 복사 버튼을 이용하세요.');
});

document.getElementById('mbStartChatBtn').addEventListener('click', () => {
  // 상태를 회원+챗봇있음으로 전환
  applyUserState('member-with-bot');
  closeMakeBotPanel();
  showToast('✅ 챗봇이 활성화되었습니다! 이제 챗봇을 관리하세요.');
});

// ── 비회원 플로우 이벤트 ──
// 회원가입 → 로그인 전환
document.getElementById('mbGoLoginBtn').addEventListener('click', () => renderGuestStep(2));
document.getElementById('mbGoSignupBtn').addEventListener('click', () => renderGuestStep(1));

// 회원가입 링크 클릭 → 새 탭 이동 + 안내 토스트
document.getElementById('mbSignupLinkBtn').addEventListener('click', () => {
  showToast('🌐 아이엠 회원가입 페이지로 이동합니다...');
});

// 가입 완료 버튼 → 로그인 스텝으로 이동
document.getElementById('mbSignupDoneBtn').addEventListener('click', () => {
  showToast('✅ 회원가입 완료! 로그인해 주세요.');
  renderGuestStep(2);
});

document.getElementById('mbLoginBtn').addEventListener('click', () => {
  const email = document.getElementById('mbLoginEmail').value.trim();
  const pw    = document.getElementById('mbLoginPw').value;
  if (!email || !pw) { showToast('⚠️ 이메일과 비밀번호를 입력해주세요.'); return; }
  showToast('✅ 로그인 성공! 앱 설치를 진행해 주세요.');
  renderGuestStep(3);
});

document.getElementById('mbAppDoneBtn').addEventListener('click', () => {
  renderGuestStep(4);
  const link = issueBotLink('mbGuestIssuedLink');
  showToast('🎉 챗봇 링크가 발급되었습니다!');
});

document.getElementById('mbCopyGuestBtn').addEventListener('click', () => {
  const link = document.getElementById('mbGuestIssuedLink').textContent;
  navigator.clipboard.writeText(link).catch(()=>{});
  showToast('링크가 복사되었습니다 📋');
});

document.getElementById('mbShareGuestBtn').addEventListener('click', () => {
  const link = document.getElementById('mbGuestIssuedLink').textContent;
  if (navigator.share) navigator.share({ title: '내 챗봇 링크', url: link });
  else showToast('링크를 공유할 수 없습니다.');
});

document.getElementById('mbGuestDoneBtn').addEventListener('click', () => {
  applyUserState('member-with-bot');
  closeMakeBotPanel();
  showToast('✅ 가입 완료! 아이엠 챗봇을 시작합니다 🚀');
});

// ── 패널 열기/닫기 ──
if (makeBotBtn) makeBotBtn.addEventListener('click', openMakeBotPanel);
if (makeBotCloseBtn) makeBotCloseBtn.addEventListener('click', closeMakeBotPanel);
if (makeBotOverlay) makeBotOverlay.addEventListener('click', closeMakeBotPanel);
} catch(e) { /* 챗봇 만들기 패널 없는 페이지 - 무시 */ }

// ── 채팅/명함 탭 + 버튼도 동일하게 챗봇 생성 패널 열기 ──
const _addUserBtnChat = document.getElementById('addUserBtnChat');
const _addUserBtnContacts = document.getElementById('addUserBtnContacts');
if (_addUserBtnChat) _addUserBtnChat.addEventListener('click', openMakeBotPanel);
if (_addUserBtnContacts) _addUserBtnContacts.addEventListener('click', openMakeBotPanel);

// ── 데모 상태 전환 버튼 ──
document.querySelectorAll('.usb-btn').forEach(btn => {
  btn.addEventListener('click', () => applyUserState(btn.dataset.state));
});

// ── 홈 버튼: kiam.kr 로 이동 ──
homeNavBtn.addEventListener('click', () => {
  location.href = '/m/';
});

// 초기 상태 적용
applyUserState(userState);


// ===========================
// 공유: visitor_id 기반 렌더링
// ===========================

let contactsPwaActive = 'all';
let receivedPwaActive = 'all';

// ── 방문자 자동 닉네임 시스템 ──────────────────────────────
var NICK_ADJ = [
  '달리는','흐르는','춤추는','빛나는','노래하는',
  '꿈꾸는','반짝이는','속삭이는','떠도는','쉬어가는',
  '흩날리는','물드는','고요한','향기로운','잔잔한',
  '맑은','따뜻한','시원한','투명한','포근한'
];
var NICK_NOUN = [
  '구름','강','나뭇잎','별','바람',
  '새벽','이슬','안개','파도','노을',
  '달빛','햇살','눈송이','봄비','소나기',
  '무지개','별빛','민들레','벚꽃','단풍'
];

function simpleHash(str) {
  var h = 0;
  for (var i = 0; i < str.length; i++) {
    h = (h * 31 + str.charCodeAt(i)) & 0x7fffffff;
  }
  return h;
}

function generateNickname(visitor_id) {
  var h = simpleHash(visitor_id || 'guest');
  var adj  = NICK_ADJ[h % NICK_ADJ.length];
  var noun = NICK_NOUN[Math.floor(h / NICK_ADJ.length) % NICK_NOUN.length];
  return adj + ' ' + noun;
}

function getVisitorDisplayName(v) {
  // 우선순위: custom_name > DB에 저장된 nickname > JS 생성 nickname
  return v.custom_name || v.nickname || generateNickname(v.visitor_id || v.visitorId || '');
}

// _visitorListAll: 전체 목록 캐시 (검색 시 JS 필터링용)
var _visitorListToken = 0;
var _visitorListAll = null;

async function renderVisitorList(query) {
  query = (query || '').trim();
  if (!receivedList) return;

  var myToken = ++_visitorListToken;

  // 로딩 오버레이 (기존 내용 위에 겹쳐서 깜빡임 없음)
  var parent = receivedList.parentElement;
  if (parent) {
    var oldOv = parent.querySelector('#_rvLoadingOverlay');
    if (oldOv) oldOv.remove();
    parent.style.position = 'relative';
    var overlay = document.createElement('div');
    overlay.id = '_rvLoadingOverlay';
    overlay.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:var(--bg-primary,#0f172a);z-index:5;';
    overlay.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--text-muted);font-size:20px;"></i>';
    parent.appendChild(overlay);
  }

  try {
    // 검색어가 있으면 전체 목록 캐시 사용 (닉네임 포함 JS 필터링)
    // 검색어가 없거나 캐시 없으면 API 호출
    var allVisitors;
    if (!query) {
      // 검색어 없음: API 호출 + 캐시 갱신
      var params = { page: 1, limit: 200, q: '' };
      if (typeof contactSmsIdx !== 'undefined' && contactSmsIdx > 0) params.sms_idx = contactSmsIdx;
      var data = await apiGet('visitor_list.php', params);
      if (myToken !== _visitorListToken) return;
      if (!data || !data.success) throw new Error('API 오류');
      _visitorListAll = data.visitors || [];
      allVisitors = _visitorListAll;

      // 통계 업데이트
      var total = data.total || allVisitors.length;
      if (receivedBadge) receivedBadge.textContent = total + '명';
      var _rvChatted = data.stat ? data.stat.chatted : allVisitors.filter(function(v){ return v.chat_count > 0; }).length;
      var _rvUnused  = data.stat ? data.stat.unused  : (allVisitors.length - _rvChatted);
      var _rst = document.getElementById('rStatTotal');
      var _rsa = document.getElementById('rStatActive');
      var _rsi = document.getElementById('rStatInactive');
      if (_rst) _rst.textContent = total + '명';
      if (_rsa) _rsa.textContent = _rvChatted + '명';
      if (_rsi) _rsi.textContent = _rvUnused + '명';
      var _rvAppBadge = document.getElementById('appBadge');
      var _rvAppTitle = document.getElementById('appTitle');
      if (_rvAppBadge && _rvAppTitle && _rvAppTitle.textContent === '공유') {
        _rvAppBadge.textContent = total + '명';
      }
    } else {
      // 검색어 있음: 캐시 없으면 먼저 전체 로드
      if (!_visitorListAll) {
        var params2 = { page: 1, limit: 200, q: '' };
        if (typeof contactSmsIdx !== 'undefined' && contactSmsIdx > 0) params2.sms_idx = contactSmsIdx;
        var data2 = await apiGet('visitor_list.php', params2);
        if (myToken !== _visitorListToken) return;
        if (!data2 || !data2.success) throw new Error('API 오류');
        _visitorListAll = data2.visitors || [];
      }
      // JS에서 닉네임 포함 필터링 (DB nickname, real_name 포함)
      var q = query.toLowerCase();
      allVisitors = _visitorListAll.filter(function(v) {
        var nick     = (v.nickname || generateNickname(v.visitor_id || '')).toLowerCase();
        var cname    = (v.custom_name || '').toLowerCase();
        var phone    = (v.phone || '').toLowerCase();
        var vid      = (v.visitor_id || '').toLowerCase();
        var cbot     = (v.chatbot_name || '').toLowerCase();
        var realname = (v.real_name || '').toLowerCase();
        return nick.indexOf(q) >= 0 || cname.indexOf(q) >= 0 ||
               phone.indexOf(q) >= 0 || vid.indexOf(q) >= 0 ||
               cbot.indexOf(q) >= 0 || realname.indexOf(q) >= 0;
      });
    }

    if (myToken !== _visitorListToken) return;

    // 오버레이 제거
    var ov = document.getElementById('_rvLoadingOverlay');
    if (ov) ov.remove();

    receivedList.innerHTML = '';

    if (allVisitors.length === 0) {
      showEmpty(receivedList, 'fas fa-users',
        query ? '검색 결과가 없습니다' : '공유 링크 접속자가 없어요',
        query ? '' : '운영자 링크를 공유하면 접속자가 여기에 표시됩니다'
      );
      return;
    }

    var withChat    = allVisitors.filter(function(v) { return v.chat_count > 0; });
    var withoutChat = allVisitors.filter(function(v) { return v.chat_count === 0; });
    withChat.forEach(function(v, i)    { receivedList.appendChild(buildVisitorCard(v, i)); });
    withoutChat.forEach(function(v, i) { receivedList.appendChild(buildVisitorCard(v, withChat.length + i)); });

  } catch(e) {
    if (myToken !== _visitorListToken) return;
    var ov2 = document.getElementById('_rvLoadingOverlay');
    if (ov2) ov2.remove();
    console.error('[renderVisitorList] error:', e);
    receivedList.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;"><i class="fas fa-exclamation-circle"></i> 데이터를 불러올 수 없습니다.</div>';
  }
}

function buildVisitorCard(visitor, idx) {
  var hasChat = (visitor.chat_count || 0) > 0;
  var displayName = getVisitorDisplayName(visitor);
  var isAnon = !visitor.custom_name;  // 운영자가 직접 입력한 이름이 없으면 true
  var vid = visitor.visitor_id || visitor.visitorId || '';
  var card = document.createElement('div');
  card.className = 'contact-card received-card visitor-card' + (hasChat ? ' has-chat' : '');
  card.style.animationDelay = (idx * 0.03) + 's';

  var timeLabel = typeof formatRelativeDate === 'function'
    ? formatRelativeDate(visitor.last_chat) : (visitor.last_chat || '');
  var cc = colorFromId(vid);
  var initial = displayName.charAt(0);  // 닉네임 첫 글자 사용

  var previewText = visitor.last_message
    ? (visitor.last_role === 'user' ? '' : visitor.last_role === 'operator' ? '📢 ' : '🤖 ') + visitor.last_message.split('\n')[0].slice(0, 26)
    : '아직 대화 없음';

  var nameClass = 'cc-name';  // 자동 닉네임이 있으므로 anon 스타일 불필요
  var badgeHtml = hasChat
    ? '<span class="cc-chat-badge"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:3px;"></i>' + visitor.chat_count + '회</span>'
    : '<span class="cc-unused-tag">미대화</span>';

  // 챗봇 링크 chip (URL을 미리 계산하여 onclick에 직접 삽입)
  var chipHtml = '';
  if (visitor.short_code) {
    var botUrl = 'https://chatbot.kiam.kr/s/' + visitor.short_code + '?v=' + vid;
    chipHtml = '<span class="chatlink-chip visitor-chatlink-chip">챗봇 링크 <i class="fas fa-copy"></i></span>';
  }

  // HTML 특수문자 이스케이프 (innerHTML 안전)
  var _e = function(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
  card.innerHTML =
    '<div class="cc-avatar ' + cc + '" style="position:relative;">' +
      '<span>' + _e(initial) + '</span>' +
      (hasChat ? '<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>' : '') +
    '</div>' +
    '<div class="cc-info" style="flex:1;min-width:0;">' +
      '<div class="cc-name-row" style="gap:4px;align-items:center;">' +
        '<span class="' + nameClass + '">' + _e(displayName) + '</span>' +
        chipHtml +
        badgeHtml +
      '</div>' +
      '<div class="cc-position" style="font-size:11px;color:var(--text-muted);font-weight:500;">' +
        '<i class="fas fa-fingerprint" style="margin-right:3px;font-size:10px;"></i>ID: ' + _e(vid) +
        (visitor.chatbot_name ? ' · ' + _e(visitor.chatbot_name) : '') +
      '</div>' +
      (visitor.phone ? '<div class="cc-position" style="font-size:11px;color:#3b82f6;font-weight:600;"><i class="fas fa-phone-alt" style="margin-right:3px;font-size:10px;"></i>' + _e(visitor.phone) + '</div>' : '') +
      (hasChat ? '<div class="cc-preview-text">' + _e(previewText) + '</div>' : '') +
    '</div>' +
    '<div class="cc-right" style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">' +
      '<span class="chat-time" style="font-size:10px;color:var(--text-muted);">' + _e(timeLabel) + '</span>' +
      '<button class="visitor-edit-btn" title="이름 수정">' +
        '<i class="fas fa-pen"></i>' +
      '</button>' +
    '</div>';

  if (visitor.short_code) {
    var chipEl = card.querySelector('.visitor-chatlink-chip');
    if (chipEl) {
      chipEl.addEventListener('click', function(e) {
        e.stopPropagation();
        var botUrl = 'https://chatbot.kiam.kr/s/' + visitor.short_code + '?v=' + vid;
        copyBotLink(e, botUrl);
      });
    }
  }
  var _editBtn = card.querySelector('.visitor-edit-btn');
  if (_editBtn) _editBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    openVisitorRename(visitor);
  });
  card.addEventListener('click', function() { openVisitorChatScreen(visitor); });
  return card;
}

function openVisitorRename(visitor) {
  var vid = visitor.visitor_id || visitor.visitorId || '';
  var current = visitor.custom_name || '';
  var newName = prompt('visitor-' + vid + ' 의 이름을 입력하세요 (빈칸: 초기화):', current);
  if (newName === null) return;
  var trimmed = newName.trim();
  visitor.custom_name = trimmed || '';

  // API 저장
  apiPost('visitor_list.php', {
    visitor_id: vid,
    name: trimmed,
    sms_idx: visitor.sms_idx || 0
  }).then(function() {
    renderVisitorList(receivedSearchInput ? receivedSearchInput.value.trim() : '');
  }).catch(function() {
    renderVisitorList(receivedSearchInput ? receivedSearchInput.value.trim() : '');
  });
}

async function openVisitorChatScreen(visitor) {
  var vid = visitor.visitor_id || visitor.visitorId || '';
  var sms_idx = visitor.sms_idx || 0;
  var displayName = getVisitorDisplayName(visitor);

  cspAvatar.textContent = displayName.charAt(0);
  cspAvatar.className = 'csp-avatar ' + colorFromId(vid);
  cspName.textContent = displayName;
  var phoneInfo = visitor.phone ? ' · \u260e ' + visitor.phone : '';
  cspPosition.textContent = 'visitor ID: ' + vid + ' · ' + (visitor.chatbot_name || '챗봇') + phoneInfo;

  chatMessages.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i></div>';

  chatScreen.classList.add('active');
  chatScreen.dataset.visitorId     = vid;
  chatScreen.dataset.visitorSmsIdx = sms_idx;
  document.body.style.overflow = 'hidden';

  // 히스토리 로드
  try {
    var data = await apiGet('visitor_list.php', { mode: 'history', visitor_id: vid, sms_idx: sms_idx });
    chatMessages.innerHTML = '';

    if (!data || !data.success || !data.messages || data.messages.length === 0) {
      chatMessages.innerHTML =
        '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;' +
            'flex:1;padding:60px 20px;gap:14px;color:#9ca3af;text-align:center;">' +
          '<div style="width:68px;height:68px;border-radius:20px;background:rgba(59,130,246,0.1);' +
               'display:flex;align-items:center;justify-content:center;">' +
            '<i class="fas fa-users" style="font-size:28px;color:#3b82f6;"></i>' +
          '</div>' +
          '<p style="font-size:15px;font-weight:700;color:#475569;">공유 링크 접속자</p>' +
          '<p style="font-size:13px;color:#94a3b8;line-height:1.7;">' +
            '아직 대화가 없습니다.<br>방문자가 챗봇에 메시지를 보내면 여기에 표시됩니다.' +
          '</p>' +
        '</div>';
    } else {
      // 날짜별 메시지 렌더링
      var lastDate = '';
      data.messages.forEach(function(m) {
        if (m.date && m.date !== lastDate) {
          lastDate = m.date;
          var dateLine = document.createElement('div');
          dateLine.style.cssText = 'text-align:center;font-size:11px;color:var(--text-muted);padding:8px 0;';
          dateLine.textContent = m.date;
          chatMessages.appendChild(dateLine);
        }
        // 운영자 안내문 (HI모드)
        if (m.role === 'operator') {
          var wrap = document.createElement('div');
          wrap.className = 'msg-row notice-row';
          var msgText = m.message
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\n/g,'<br>');
          wrap.innerHTML =
            '<div class="oc-notice-card">' +
              '<div class="oc-notice-header">' +
                '<span class="oc-notice-icon">&#128226;</span>' +
                '<span class="oc-notice-label">운영자 안내문</span>' +
                '<span class="oc-notice-badge">NOTICE</span>' +
              '</div>' +
              '<div class="oc-notice-divider"></div>' +
              '<div class="oc-notice-body">' + msgText + '</div>' +
              '<div class="oc-notice-time">' + (m.time || '') + '</div>' +
            '</div>';
          chatMessages.appendChild(wrap);
          return;
        }

        var isBot = m.role === 'assistant';
        var wrap = document.createElement('div');
        // assistant(AI) = msg-row user(좌측, 흰 버블), visitor = msg-row bot(우측, 초록 버블)
        wrap.className = isBot ? 'msg-row user' : 'msg-row bot';
        var msgText = m.message
          .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
          .replace(/\n/g,'<br>');
        wrap.innerHTML =
          '<div class="msg-avatar" style="font-size:14px;">' + (isBot ? '🤖' : '👤') + '</div>' +
          '<div class="msg-body">' +
            '<div class="msg-bubble">' + msgText + '</div>' +
            '<div style="font-size:10px;color:var(--text-muted);padding:2px 4px;">' + (m.time || '') + '</div>' +
          '</div>';
        chatMessages.appendChild(wrap);
      });
    }
  } catch(e) {
    chatMessages.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">이력을 불러올 수 없습니다.</div>';
  }

  requestAnimationFrame(function() {
    setTimeout(function() {
      if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 60);
  });
}

function initPwaFilters() {
  var cpf = document.getElementById('contactsPwaFilter');
  if (cpf) {
    cpf.addEventListener('click', function(e) {
      var chip = e.target.closest('.pwa-filter-chip');
      if (!chip) return;
      cpf.querySelectorAll('.pwa-filter-chip').forEach(function(c) { c.classList.remove('active'); });
      chip.classList.add('active');
      contactsPwaActive = chip.dataset.pwa;
      if (typeof window.renderContactsList === 'function') {
        window.renderContactsList(contactsSearchInput ? contactsSearchInput.value.trim() : '');
      } else {
        renderContactsList(contactsSearchInput ? contactsSearchInput.value.trim() : '');
      }
    });
  }

  var rpf = document.getElementById('receivedPwaFilter');
  if (rpf) {
    rpf.addEventListener('click', function(e) {
      var chip = e.target.closest('.pwa-filter-chip');
      if (!chip) return;
      rpf.querySelectorAll('.pwa-filter-chip').forEach(function(c) { c.classList.remove('active'); });
      chip.classList.add('active');
      receivedPwaActive = chip.dataset.pwa;
      renderVisitorList(receivedSearchInput ? receivedSearchInput.value.trim() : '');
    });
  }
}

// ===========================
// 초기 렌더링
// ===========================
initPwaFilters();
switchTab('chat');

// ── 헤더 검색 토글 (currentTab 기반으로 탭별 검색창 자동 전환) ──
function toggleContactsSearch() {
  const btn = document.getElementById('contactsSearchBtn');

  // currentTab 으로 탭 판단
  const tab = typeof currentTab !== 'undefined' ? currentTab : 'contacts';

  // 수신 탭은 별도 검색바 사용
  if (tab === 'received') { toggleInboxSearch(); return; }

  // 탭별 rowId / inputId / renderFn 매핑
  const tabMap = {
    'chat':     { rowId: 'chatSearchRow',     inpId: 'chatSearchInput',     renderFn: function(q){ if(typeof filterDMRooms==='function') filterDMRooms(q); } },
    'contacts': { rowId: 'contactsSearchRow', inpId: 'contactsSearchInput', renderFn: function(q){ if(typeof renderContactsList==='function') renderContactsList(q); } },
    'shared':   { rowId: 'receivedSearchRow', inpId: 'receivedSearchInput', renderFn: function(q){ if(typeof renderVisitorList==='function') renderVisitorList(q); } },
  };

  const cfg = tabMap[tab] || tabMap['contacts'];
  const row = document.getElementById(cfg.rowId);
  if (!row) return;

  // 현재 탭 외 다른 검색창은 모두 닫기
  ['chatSearchRow','contactsSearchRow','receivedSearchRow','inboxSearchRow'].forEach(function(id) {
    if (id !== cfg.rowId) {
      const other = document.getElementById(id);
      if (other) other.style.display = 'none';
    }
  });

  const isHidden = row.style.display === 'none';
  row.style.display = isHidden ? 'flex' : 'none';

  if (btn) {
    btn.style.background = isHidden ? 'rgba(59,130,246,0.2)' : '';
    btn.style.borderRadius = '50%';
  }

  const inp = document.getElementById(cfg.inpId);
  if (isHidden) {
    if (inp) inp.focus();
  } else {
    if (inp) { inp.value = ''; cfg.renderFn(''); }
  }
}





// ═══════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════════
// HI 공지발송 시스템
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

  // 요소 참조 (DOMContentLoaded 이후 → 모두 존재)
  var bcOverlay      = document.getElementById('bcOverlay');
  var bcMenuPanel    = document.getElementById('bcMenuPanel');
  var bcComposePanel = document.getElementById('bcComposePanel');
  var bcHistPanel    = document.getElementById('bcHistPanel');
  var bcStatPanel    = document.getElementById('bcStatPanel');

  var bcMenuClose    = document.getElementById('bcMenuClose');
  var bcComposeClose = document.getElementById('bcComposeClose');
  var bcHistClose    = document.getElementById('bcHistClose');
  var bcStatClose    = document.getElementById('bcStatClose');
  var bcStatBack     = document.getElementById('bcStatBack');

  var bcFilterInput  = document.getElementById('bcFilterInput');
  var bcFilterClear  = document.getElementById('bcFilterClear');
  var bcTitleInput   = document.getElementById('bcTitleInput');
  var bcMsgInput     = document.getElementById('bcMsgInput');
  var bcSendBtn      = document.getElementById('bcSendBtn');
  var bcSendBtnLabel = document.getElementById('bcSendBtnLabel');
  var bcFunnelCnt    = document.getElementById('bcFunnelCnt');
  var bcSharedCnt    = document.getElementById('bcSharedCnt');
  var bcTotalCnt     = document.getElementById('bcTotalCnt');
  var bcPreviewBody  = document.getElementById('bcPreviewBody');
  var bcHistList     = document.getElementById('bcHistList');
  var bcReplyList    = document.getElementById('bcReplyList');
  var bcStatMeta     = document.getElementById('bcStatMeta');
  var bcStatBars     = document.getElementById('bcStatBars');
  var bcComposePanelTitle = document.getElementById('bcComposePanelTitle');

  var allPanels      = [bcMenuPanel, bcComposePanel, bcHistPanel, bcStatPanel];
  var currentTarget  = 'all';
  var currentBcId    = null;
  var countTimer     = null;

  // API 헬퍼
  async function bcGet(params) {
    var url = './api/broadcast.php?' + new URLSearchParams(params).toString();
    var res = await fetch(url, { credentials: 'include' });
    return res.json();
  }
  async function bcPost(body) {
    var res = await fetch('./api/broadcast.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return res.json();
  }

  // 패널 열기/닫기
  function openPanel(panel) {
    if (!panel || !bcOverlay) return;
    bcOverlay.classList.add('active');
    panel.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closePanel(panel) {
    if (!panel) return;
    panel.classList.remove('active');
    var anyOpen = allPanels.some(function(p) { return p && p.classList.contains('active'); });
    if (!anyOpen) { bcOverlay.classList.remove('active'); document.body.style.overflow = ''; }
  }
  function closeAllPanels() {
    allPanels.forEach(function(p) { if (p) p.classList.remove('active'); });
    if (bcOverlay) { bcOverlay.classList.remove('active'); }
    document.body.style.overflow = '';
  }

  // 드롭다운 단일 버튼 → 타입 선택 패널
  var openBcMenuBtn = document.getElementById('openBcMenuBtn');
  if (openBcMenuBtn) {
    openBcMenuBtn.addEventListener('click', function() {
      var dd = document.getElementById('avatarDropdown');
      if (dd) dd.classList.remove('open');
      openPanel(bcMenuPanel);
    });
  }

  // 타입 선택 카드 클릭
  if (bcMenuPanel) {
    bcMenuPanel.querySelectorAll('[data-compose-target]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        closePanel(bcMenuPanel);
        openBroadcastCompose(this.dataset.composeTarget);
      });
    });
    var bcMenuToHistBtn = document.getElementById('bcMenuToHistBtn');
    if (bcMenuToHistBtn) {
      bcMenuToHistBtn.addEventListener('click', function() {
        closePanel(bcMenuPanel);
        openBroadcastHist();
      });
    }
  }

  // 발송 작성 패널
  function openBroadcastCompose(target) {
    currentTarget = target || 'all';
    var titles = { all: '📢 전체 공지발송', funnel: '📢 퍼널 공지발송', shared: '📢 공유 공지발송' };
    if (bcComposePanelTitle) bcComposePanelTitle.textContent = titles[currentTarget] || titles.all;
    if (bcFilterInput) bcFilterInput.value = '';
    if (bcTitleInput)  bcTitleInput.value  = '';
    if (bcMsgInput)    bcMsgInput.value    = '';
    if (bcPreviewBody) bcPreviewBody.textContent = '메시지를 입력하면 여기에 미리보기가 표시됩니다.';
    updateTargetCount();
    openPanel(bcComposePanel);
  }

  // 대상 수 조회
  async function updateTargetCount() {
    var q = bcFilterInput ? bcFilterInput.value.trim() : '';
    var target = currentTarget;
    if (bcFunnelCnt) bcFunnelCnt.textContent = target === 'shared' ? '0' : '-';
    if (bcSharedCnt) bcSharedCnt.textContent = target === 'funnel' ? '0' : '-';
    if (bcTotalCnt)  bcTotalCnt.textContent  = '-';
    if (bcSendBtnLabel) bcSendBtnLabel.textContent = '대상 계산 중...';
    try {
      var data = await bcGet({ mode: 'count', target: target, q: q });
      if (data.success) {
        if (bcFunnelCnt) bcFunnelCnt.textContent = data.funnel;
        if (bcSharedCnt) bcSharedCnt.textContent = data.shared;
        if (bcTotalCnt)  bcTotalCnt.textContent  = data.total;
        if (bcSendBtnLabel) bcSendBtnLabel.textContent = '📢 발송 ' + data.total + '명';
        if (bcSendBtn)   bcSendBtn.disabled = (data.total === 0);
      }
    } catch(e) {
      if (bcSendBtnLabel) bcSendBtnLabel.textContent = '오류';
    }
  }

  // 필터 입력
  if (bcFilterInput) {
    bcFilterInput.addEventListener('input', function() {
      if (bcFilterClear) bcFilterClear.classList.toggle('visible', this.value.length > 0);
      clearTimeout(countTimer);
      countTimer = setTimeout(updateTargetCount, 500);
    });
  }
  if (bcFilterClear) {
    bcFilterClear.addEventListener('click', function() {
      if (bcFilterInput) { bcFilterInput.value = ''; bcFilterInput.dispatchEvent(new Event('input')); }
    });
  }

  // 메시지 미리보기
  if (bcMsgInput) {
    bcMsgInput.addEventListener('input', function() {
      if (bcPreviewBody) {
        bcPreviewBody.textContent = this.value || '메시지를 입력하면 여기에 미리보기가 표시됩니다.';
      }
    });
  }

  // 발송 실행
  if (bcSendBtn) {
    bcSendBtn.addEventListener('click', async function() {
      var msg = bcMsgInput ? bcMsgInput.value.trim() : '';
      if (!msg) { alert('메시지를 입력해주세요.'); return; }
      var total = bcTotalCnt ? parseInt(bcTotalCnt.textContent) || 0 : 0;
      if (total === 0) { alert('발송 대상이 없습니다.'); return; }
      if (!confirm(total + '명에게 HI 공지를 발송하시겠습니까?')) return;

      bcSendBtn.disabled = true;
      bcSendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 발송 중...';

      try {
        var data = await bcPost({
          action:      'send',
          target_type: currentTarget,
          title:       bcTitleInput ? bcTitleInput.value.trim() : '',
          message:     msg,
          q:           bcFilterInput ? bcFilterInput.value.trim() : ''
        });

        bcSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span id="bcSendBtnLabel">발송 -명</span>';
        bcSendBtnLabel = document.getElementById('bcSendBtnLabel');
        bcSendBtn.disabled = false;

        if (data.success) {
          closeAllPanels();
          showBcResultToast('✅ ' + data.sent_count + '명에게 공지 발송 완료!');
        } else {
          alert('발송 실패: ' + (data.error || '알 수 없는 오류'));
        }
      } catch(e) {
        bcSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span id="bcSendBtnLabel">다시 시도</span>';
        bcSendBtn.disabled = false;
        alert('네트워크 오류가 발생했습니다.');
      }
    });
  }

  // 결과 토스트
  function showBcResultToast(msg) {
    var t = document.querySelector('.bc-result-toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'bc-result-toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 3500);
  }

  // 발송 이력 패널
  async function openBroadcastHist() {
    openPanel(bcHistPanel);
    if (bcHistList) bcHistList.innerHTML = '<div style="text-align:center;padding:40px;color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
      var data = await bcGet({ mode: 'list', page: 1 });
      if (!data.success || data.list.length === 0) {
        bcHistList.innerHTML = '<div style="text-align:center;padding:60px;color:#64748b;"><i class="fas fa-bullhorn" style="font-size:36px;display:block;margin-bottom:12px;"></i>발송 이력이 없습니다</div>';
        return;
      }
      bcHistList.innerHTML = '';
      data.list.forEach(function(bc) {
        var typeLabel = { all: '전체', funnel: '퍼널', shared: '공유' };
        var readRate  = bc.sent_count > 0 ? Math.round(bc.read_count / bc.sent_count * 100) : 0;
        var replyRate = bc.sent_count > 0 ? Math.round(bc.reply_count / bc.sent_count * 100) : 0;
        var item = document.createElement('div');
        item.className = 'bc-hist-item';
        item.innerHTML =
          '<div class="bc-hist-header">' +
            '<span class="bc-hist-type-badge ' + bc.target_type + '">' + (typeLabel[bc.target_type]||bc.target_type) + ' 공지</span>' +
            '<span class="bc-hist-date">' + bc.created_at + '</span>' +
          '</div>' +
          '<div class="bc-hist-title">' + (bc.title || '(제목 없음)') + '</div>' +
          '<div class="bc-hist-msg">' + bc.message.split('\n')[0] + '</div>' +
          '<div class="bc-hist-stats">' +
            '<div class="bc-hist-stat sent"><i class="fas fa-paper-plane"></i> 발송 <strong>' + bc.sent_count + '명</strong></div>' +
            '<div class="bc-hist-stat read"><i class="fas fa-eye"></i> 읽음 <strong>' + bc.read_count + '명</strong>(' + readRate + '%)</div>' +
            '<div class="bc-hist-stat reply"><i class="fas fa-reply"></i> 응답 <strong>' + bc.reply_count + '명</strong>(' + replyRate + '%)</div>' +
          '</div>';
        item.addEventListener('click', function() { openBroadcastStat(bc.id); });
        bcHistList.appendChild(item);
      });
    } catch(e) {
      if (bcHistList) bcHistList.innerHTML = '<div style="text-align:center;padding:40px;color:#ef4444;">오류가 발생했습니다</div>';
    }
  }

  // 발송 상세 통계 패널
  async function openBroadcastStat(bcId) {
    currentBcId = bcId;
    openPanel(bcStatPanel);
    if (bcStatBack) bcStatBack.style.display = 'flex';
    if (bcStatMeta) bcStatMeta.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    if (bcStatBars) bcStatBars.innerHTML = '';
    if (bcReplyList) bcReplyList.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
      var data = await bcGet({ mode: 'stat', id: bcId });
      if (!data.success) return;
      var bc = data.broadcast;
      var typeLabel = { all: '전체', funnel: '퍼널', shared: '공유' };
      var sent = bc.sent_count, read = bc.read_count, reply = bc.reply_count;
      var readRate  = sent > 0 ? Math.round(read / sent * 100) : 0;
      var replyRate = sent > 0 ? Math.round(reply / sent * 100) : 0;
      if (bcStatMeta) bcStatMeta.innerHTML =
        '<div><strong>' + (bc.title || '(제목 없음)') + '</strong></div>' +
        '<div>' + bc.created_at + ' · ' + (typeLabel[bc.target_type]||bc.target_type) + ' 공지</div>' +
        '<div style="margin-top:8px;font-size:12px;color:#94a3b8;">' + bc.message.split('\n')[0] + '</div>';
      if (bcStatBars) bcStatBars.innerHTML =
        renderStatBar('fa-paper-plane', '발송', sent, sent, 100, 'sent') +
        renderStatBar('fa-eye', '읽음', read, sent, readRate, 'read') +
        renderStatBar('fa-reply', '응답', reply, sent, replyRate, 'reply');
      loadReplyList(bcId, 'all');
    } catch(e) {
      if (bcStatMeta) bcStatMeta.innerHTML = '<span style="color:#ef4444;">오류 발생</span>';
    }
  }

  function renderStatBar(icon, label, cnt, total, pct, cls) {
    return '<div class="bc-stat-bar-row">' +
      '<div class="bc-stat-bar-label"><span><i class="fas ' + icon + '"></i> ' + label + '</span><strong>' + cnt + '명 / ' + total + '명 (' + pct + '%)</strong></div>' +
      '<div class="bc-stat-bar-track"><div class="bc-stat-bar-fill ' + cls + '" style="width:' + pct + '%"></div></div>' +
    '</div>';
  }

  async function loadReplyList(bcId, filter) {
    if (!bcReplyList) return;
    bcReplyList.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
      var data = await bcGet({ mode: 'replies', id: bcId, filter: filter });
      if (!data.success || data.list.length === 0) {
        bcReplyList.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;">해당 조건의 수신자가 없습니다</div>';
        return;
      }
      bcReplyList.innerHTML = '';
      data.list.forEach(function(r) {
        var initial = (r.display_name || '?').charAt(0);
        var readTag  = r.first_read_at ? '<span class="bc-rs-tag read">읽음</span>' : '<span class="bc-rs-tag unread">미읽음</span>';
        var replyTag = r.replied_at ? '<span class="bc-rs-tag replied">응답</span>' : '';
        var item = document.createElement('div');
        item.className = 'bc-reply-item';
        item.innerHTML =
          '<div class="bc-reply-avatar">' + initial + '</div>' +
          '<div class="bc-reply-info">' +
            '<div class="bc-reply-name">' + (r.display_name || '알 수 없음') + '</div>' +
            (r.phone ? '<div class="bc-reply-phone"><i class="fas fa-phone-alt" style="font-size:9px;margin-right:3px;"></i>' + r.phone + '</div>' : '') +
            (r.reply_preview ? '<div class="bc-reply-preview">"' + r.reply_preview + '"</div>' : '') +
            '<div class="bc-reply-status">' + readTag + replyTag + '</div>' +
          '</div>' +
          (r.replied_at ? '<div class="bc-reply-time">' + r.replied_at.substr(11,5) + '</div>' : '');
        bcReplyList.appendChild(item);
      });
    } catch(e) {
      bcReplyList.innerHTML = '<div style="text-align:center;color:#ef4444;">오류 발생</div>';
    }
  }

  // 탭 필터
  var bcReplyTabsEl = document.getElementById('bcReplyTabs');
  if (bcReplyTabsEl) {
    bcReplyTabsEl.addEventListener('click', function(e) {
      var btn = e.target.closest('.bc-rtab');
      if (!btn || !currentBcId) return;
      bcReplyTabsEl.querySelectorAll('.bc-rtab').forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');
      loadReplyList(currentBcId, btn.dataset.filter || 'all');
    });
  }

  // 닫기 / 뒤로
  if (bcMenuClose)    bcMenuClose.addEventListener('click', function() { closePanel(bcMenuPanel); });
  if (bcComposeClose) bcComposeClose.addEventListener('click', function() { closePanel(bcComposePanel); });
  if (bcHistClose)    bcHistClose.addEventListener('click', function() { closePanel(bcHistPanel); });
  if (bcStatClose)    bcStatClose.addEventListener('click', closeAllPanels);
  if (bcStatBack)     bcStatBack.addEventListener('click', function() {
    closePanel(bcStatPanel);
    openPanel(bcHistPanel);
  });
  if (bcOverlay) bcOverlay.addEventListener('click', closeAllPanels);

  // 전역 노출
  window.openBroadcastCompose = openBroadcastCompose;
  window.openBroadcastHist    = openBroadcastHist;
});
