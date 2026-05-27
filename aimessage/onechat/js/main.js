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
const receivedList        = document.getElementById('receivedList');
const receivedBadge       = document.getElementById('receivedBadge');
const receivedSearchInput = document.getElementById('receivedSearchInput');
const receivedClearBtn    = document.getElementById('receivedClearBtn');

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
    const isReceived = (typeof receivedData !== 'undefined') && receivedData && receivedData.some(r => r.id === person.id);
    if (isReceived) renderReceivedList();
  }
}

// ===========================
// 헬퍼: 이름 이니셜
// ===========================
function getInitial(name) {
  return name.charAt(0);
}

// DiceBear: 이름 기반 성별 추정 (여성/남성/중립 3단계)
// visitor 자동 닉네임 키워드 → 이모지 매핑
function _nicknameToEmoji(name) {
  if (!name) return '✨';
  var map = [
    ['별빛','✨'],
    ['별','⭐'],
    ['달빛','🌙'],
    ['구름','☁️'],
    ['강','💧'],
    ['나뭇잎','🍃'],
    ['바람','💨'],
    ['새벽','🌄'],
    ['이슬','💧'],
    ['안개','🌫'],
    ['파도','🌊'],
    ['노을','🌅'],
    ['햇살','☀️'],
    ['눈송이','❄️'],
    ['봄비','🌧'],
    ['소나기','🌦'],
    ['무지개','🌈'],
    ['민들레','🌼'],
    ['벚꽃','🌸'],
    ['단풍','🍁'],
    ['빛나','✨'],
    ['반짝','✨']
  ];
  for (var i = 0; i < map.length; i++) {
    if (name.indexOf(map[i][0]) >= 0) return map[i][1];
  }
  return '🌟';
}

function _genderFromName(name) {
  if (!name) return 'n';
  if (name.indexOf(' ') >= 0) return 'n';       // 공백 포함 = 자동 닉네임 → 중립
  var fem  = '희미영연은아나선혜란화순숙자이하주린리유빈가라설경';
  var male = '준철호강석훈규형태혁동성재기남봉열범표';
  var last = name.charAt(name.length - 1);
  if (fem.indexOf(last)  >= 0) return 'f';
  if (male.indexOf(last) >= 0) return 'm';
  return 'n';                                    // 진/현/수/정 등 애매한 이름 → 중립
}
function getDiceBearUrl(seed, name) {
  var s = String(seed || 'user');
  var h = 0;
  for (var i = 0; i < s.length; i++) { h = ((h << 5) - h) + s.charCodeAt(i); h = h & h; }
  var g = name ? _genderFromName(name) : 'n';
  var styles = g === 'f'
    ? ['lorelei', 'adventurer', 'notionists']          // 여성: 두상+머리카락+귀 등 여성 캐릭터
    : g === 'm'
      ? ['avataaars', 'personas', 'micah']              // 남성: 두상+헤어+안경 등 남성 캐릭터
      : ['fun-emoji', 'big-smile', 'croodles-neutral']; // 중립: 두상 없이 표정/이모지만
  return 'https://api.dicebear.com/7.x/' + styles[Math.abs(h) % styles.length] + '/svg?seed=' + encodeURIComponent(s);
}
function setDiceBearAvatar(el, seed, name) {
  if (!el) return;
  el.style.position = 'relative';
  el.style.overflow = 'hidden';
  el.textContent = name ? name.charAt(0) : '';
  var img = document.createElement('img');
  img.src = getDiceBearUrl(seed, name);
  img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;';
  img.onerror = function() { this.remove(); };
  el.insertBefore(img, el.firstChild);
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
        <div class="profile-img ${person.colorClass}">
          ${(person.profile||'').trim() ? `<img class="profile-photo" src="https://kiam.kr${(person.profile||'').trim()}" alt="${getInitial(person.name)}" onerror="this.style.display='none'">` : ''}${(person.profile||'').trim() ? '' : getInitial(person.name)}
        </div>
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

  // PWA 필터 적용
  const activePwaChip = document.querySelector('#contactsPwaFilter .pwa-filter-chip.active');
  const pwaMode = activePwaChip ? activePwaChip.dataset.pwa : 'all';
  if (pwaMode === 'installed') {
    data = data.filter(p => p.pwaInstalled);
  }

  contactsBadge.textContent = `${chatData.length}명`;

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

  const _cp1 = person.profile ? person.profile.trim() : '';
  const _ph1 = _cp1
    ? `<img class="cc-avatar-photo" src="https://kiam.kr${_cp1}" alt="${getInitial(person.name)}" onerror="this.src='${getDiceBearUrl(person.mem_id||person.name, person.name)}';">`
    : `<img class="cc-avatar-photo" src="${getDiceBearUrl(person.mem_id||person.name, person.name)}" alt="${getInitial(person.name)}" onerror="this.remove()">`;
  card.innerHTML = `
    <div class="cc-avatar ${person.colorClass}" style="position:relative;overflow:hidden;">
      ${_ph1}
      ${hasChat ? `<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>` : ''}
      ${person.pwaInstalled ? `<div class="pwa-installed-badge" title="PWA 설치됨"><i class="fas fa-mobile-alt"></i></div>` : ''}
    </div>
    <div class="cc-info">
      <div class="cc-name-row">
        <span class="cc-name">${person.name}</span>
        ${person.shortUrl ? `<span class="chatlink-chip" onclick="copyBotLink(event,'https://${CHATBOT_DOMAIN}${person.shortUrl}')">챗봇 링크 <i class="fas fa-copy"></i></span>` : ''}
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

  // 클릭 이벤트는 api.js의 openChatScreenFromAPI에서 등록 (DB 메시지 로드 포함)
  // buildContactCard 자체에서는 등록하지 않음
  return card;
}

// ===========================
// 채팅 전체화면 열기
// ===========================
function openChatScreen(person) {
  console.log('[MAIN] openChatScreen', person.name, 'chatScreen:', !!chatScreen);
  currentPerson = person; // 현재 열린 채팅 상대 추적

  // 채팅방 열면 → 해당 사람 메시지 모두 읽음 처리
  if (person.messages) person.messages.forEach(m => { if (m.type === 'user') m.read = true; });
  person.unreadCount = 0;

  // 리스트 뱃지 갱신 (chatList가 없는 탭에서도 chatScreen은 열려야 함)
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

  if (!chatScreen) return; // chatScreen DOM이 없으면 종료

  if (cspAvatar) { setDiceBearAvatar(cspAvatar, person.mem_id || person.name, person.name); cspAvatar.className = `csp-avatar ${person.colorClass}`; }
  if (cspName)     cspName.textContent     = person.name;
  if (cspPosition) cspPosition.textContent = person.position;

  if (!person.messages || person.messages.length === 0) {
    if (chatMessages) chatMessages.innerHTML = `
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                  flex:1;padding:60px 20px;gap:14px;color:#9ca3af;text-align:center;">
        <div style="width:68px;height:68px;border-radius:20px;background:#e2e8f0;
                    display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#94a3b8;"></i>
        </div>
        <p style="font-size:15px;font-weight:700;color:#475569;">대화 이력을 불러오는 중...</p>
        <p style="font-size:13px;color:#94a3b8;line-height:1.7;">잠시만 기다려주세요</p>
      </div>
    `;
  } else {
    renderMessages(person.messages);
  }

  console.log('[MAIN] chatScreen.active 추가 전 classes:', chatScreen.className);
  chatScreen.classList.add('active');
  console.log('[MAIN] chatScreen.active 추가 후 classes:', chatScreen.className);
  document.body.style.overflow = 'hidden';

  // 챗봇 ON으로 초기화
  isBotOn = true;
  if (botToggle) botToggle.checked = true;
  if (typeof updateBotUI === 'function') updateBotUI();

  requestAnimationFrame(() => {
    setTimeout(() => { if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight; }, 60);
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
    'peer-bot':  { text: '🤖 상대 AI',   cls: 'label-peer-bot' },
    'companion': { text: '🤝 AI 동행',   cls: 'label-companion'},
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

    // ── AI 동행 메시지 → 특별 카드 형태
    if (rowType === 'companion') {
      div.className = 'msg-row companion-row';
      div.style.animationDelay = `${idx * 0.05}s`;
      div.innerHTML = `
        <div class="msg-companion">
          <div class="msg-companion-badge"><i class="fas fa-heart"></i> AI 동행</div>
          <div class="msg-companion-text">${escapeHtml(msg.text)}</div>
          ${timeStr ? `<div class="msg-companion-time">${timeStr}</div>` : ''}
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
      var _peerSeed = (currentPerson && (currentPerson.mem_id || currentPerson.name)) || 'peer';
      var _peerProfilePath = (currentPerson && currentPerson.profile) ? currentPerson.profile.trim() : '';
      var _peerIcon = (rowType === 'bot' || rowType === 'peer-bot')
        ? '<i class="fas fa-robot" style="font-size:12px;color:#16a34a;"></i>'
        : _peerProfilePath
          ? '<img src="https://kiam.kr' + _peerProfilePath + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.remove()">'
          : '<img src="' + getDiceBearUrl(_peerSeed, currentPerson && currentPerson.name) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.remove()">';
      div.innerHTML = `
        <div class="msg-avatar" style="overflow:hidden;border-radius:50%;">
          ${_peerIcon}
        </div>
        <div class="msg-body">
          <span class="msg-sender-label ${lbl.cls}">${lbl.text}</span>
          <div class="${bubbleCls}">${escapeHtml(msg.text)}</div>
          ${timeStr ? `<span class="msg-time">${timeStr}</span>` : ''}
        </div>
      `;
    } else {
      // 오른쪽 (나 / 내 아바타)
      // owner 타입만 프로필 사진 아바타 표시 (bot/my-bot은 로봇 아이콘 유지)
      const ownerAvatar = rowType === 'owner'
        ? `<div class="msg-avatar">${getMyAvatarHtml()}</div>`
        : '';
      div.innerHTML = `
        ${ownerAvatar}
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
  // 수신탭에서 열었으면 닫을 때 수신탭으로 복귀
  if (chatScreen.dataset.callerTab === 'received') {
    chatScreen.dataset.callerTab = '';
    if (typeof switchTab === 'function') switchTab('received');
  }
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
      // ★ visitor 채팅이면 봇 ON/OFF 무관하게 항상 notice
      type: chatScreen.dataset.visitorId ? 'notice' : 'owner',
      text,
      time: `${dateStr} ${timeStr}`,
      read: true
    });
    updateChatMeta(currentPerson);
  }

  const div = document.createElement('div');
  // ★ visitor 채팅이면 봇 ON/OFF 무관하게 항상 notice 카드
  if (chatScreen.dataset.visitorId) {
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
        // ★ visitor 채팅이면 봇 ON/OFF 무관하게 항상 notice
        type: chatScreen.dataset.visitorId ? 'notice' : 'owner',
        text,
        time: `${dateStr} ${timeStr}`,
        read: true
      });
      updateChatMeta(currentPerson);
    }

    const div = document.createElement('div');
    // ★ visitor 채팅이면 봇 ON/OFF 무관하게 항상 notice 카드
    if (chatScreen.dataset.visitorId) {
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

    // 방문자 채팅: HI 모드 + 봇 ON 모드 모두 DB에 저장 (중복 방지: api.js는 visitor 저장 안 함)
    if (chatScreen.dataset.visitorId) {
      var _vid  = chatScreen.dataset.visitorId;
      var _sidx = parseInt(chatScreen.dataset.visitorSmsIdx, 10);
      apiPost('visitor_list.php', { mode: 'send_hi', visitor_id: _vid, sms_idx: _sidx, message: text })
        .then(function() {
          // HI 모드일 때만 AI 자동 복귀 타이머 리셋
          if (!isBotOn) resetHiAutoReturnTimer();
        })
        .catch(function(e) { console.error('[운영자 메시지 저장 오류]', e); });
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
// ★ 간접 호출: api.js가 window.sendWithAttachments를 패치해도 항상 최신 버전이 호출됨
ciSendBtn.removeEventListener('click', sendDirectMessage);
ciSendBtn.addEventListener('click', () => window.sendWithAttachments());

ciTextarea.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    window.sendWithAttachments();
  }
});

// ===========================
// 탭 전환
// ===========================
// 발신 탭 내 "퍼널" 버튼 → funnel 리스트
function statsBarSwitchToFunnel() {
  const funnelBtn  = document.getElementById('statsBarFunnelBtn');
  const shareBtn   = document.getElementById('statsBarShareBtn');
  const filterTabs = document.getElementById('contactsVisitorFilterTabs');
  const statFunnel = document.getElementById('contactsStatsFunnelText');
  const statVis    = document.getElementById('contactsStatsVisitorText');

  if (funnelBtn)  funnelBtn.classList.add('active');
  if (shareBtn)   shareBtn.classList.remove('active');
  if (filterTabs) filterTabs.style.display = 'none';
  if (statFunnel) statFunnel.style.display = '';
  if (statVis)    statVis.style.display    = 'none';

  if (typeof setContactFilter === 'function') setContactFilter('funnel');
  if (typeof renderContactsListFromAPI === 'function') renderContactsListFromAPI('', true);
}

// 발신 탭 내 "공유" 버튼 → visitor(링크 방문자) 리스트 (수신탭 이동 X)
function statsBarSwitchToReceived() {
  const funnelBtn  = document.getElementById('statsBarFunnelBtn');
  const shareBtn   = document.getElementById('statsBarShareBtn');
  const filterTabs = document.getElementById('contactsVisitorFilterTabs');
  const statFunnel = document.getElementById('contactsStatsFunnelText');
  const statVis    = document.getElementById('contactsStatsVisitorText');

  if (funnelBtn)  funnelBtn.classList.remove('active');
  if (shareBtn)   shareBtn.classList.add('active');
  if (filterTabs) filterTabs.style.display = 'flex';
  if (statFunnel) statFunnel.style.display = 'none';
  if (statVis)    statVis.style.display    = '';

  // 공유 필터 탭 초기화 (전체로 리셋)
  document.querySelectorAll('#contactsVisitorFilterTabs .rfTab').forEach(b => b.classList.remove('active'));
  const allTab = document.querySelector('#contactsVisitorFilterTabs .rfTab[data-filter="all"]');
  if (allTab) allTab.classList.add('active');

  if (typeof setContactFilter === 'function') setContactFilter('visitor');
  if (typeof renderContactsListFromAPI === 'function') renderContactsListFromAPI('', true);
}

// 발신 탭 공유 서브필터 (전체/대화중/미대화)
function contactsVisitorFilter(btn, filter) {
  document.querySelectorAll('#contactsVisitorFilterTabs .rfTab').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const filterMap = { all: 'visitor', chatted: 'visitor_chatted', unused: 'visitor_unused' };  // visitor 전용 서브필터
  if (typeof setContactFilter === 'function') setContactFilter(filterMap[filter] || 'visitor');
  if (typeof renderContactsListFromAPI === 'function') renderContactsListFromAPI('', true);
}

function switchTab(tab) {
  currentTab = tab;
  // ── 해시 라우팅: 탭 전환 시 URL 주소창 업데이트 ──
  var hashMap = { chat: '#chat', contacts: '#send', received: '#receive' };
  if (hashMap[tab] && location.hash !== hashMap[tab]) {
    history.pushState({ tab: tab }, '', hashMap[tab]);
  }

  // ── 헤더 타이틀 동적 변경 ──────────────────────────────────
  const titleMap = { chat: '채팅관리', contacts: '발신관리', received: '수신관리' };
  const appTitle = document.getElementById('appTitle');
  if (appTitle && titleMap[tab]) appTitle.textContent = titleMap[tab];

  // ── 헤더 돋보기: 탭 전환 시 검색창 닫기 + 버튼 초기화 ─────
  ['chatSearchRow','contactsSearchRow','inboxSearchRow'].forEach(function(rowId) {
    const row = document.getElementById(rowId);
    if (row) row.style.display = 'none';
  });
  const headerSearchBtn = document.getElementById('headerSearchBtn');
  if (headerSearchBtn) headerSearchBtn.classList.remove('search-toggle-active');

  // 탭 화면 전환
  screenChat.classList.remove('active');
  screenContacts.classList.remove('active');
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
    // 발신탭 전환 시 공유 버튼 active 상태 (기본: 공유 탭)
    const _fBtn     = document.getElementById('statsBarFunnelBtn');
    const _sBtn     = document.getElementById('statsBarShareBtn');
    const _fTabs    = document.getElementById('contactsVisitorFilterTabs');
    const _statF    = document.getElementById('contactsStatsFunnelText');
    const _statV    = document.getElementById('contactsStatsVisitorText');
    if (_fBtn)  _fBtn.classList.remove('active');
    if (_sBtn)  _sBtn.classList.add('active');
    if (_fTabs) _fTabs.style.display = 'flex';
    if (_statF) _statF.style.display = 'none';
    if (_statV) _statV.style.display = '';
    document.querySelectorAll('#contactsVisitorFilterTabs .rfTab').forEach(b => b.classList.remove('active'));
    const _allTab = document.querySelector('#contactsVisitorFilterTabs .rfTab[data-filter="all"]');
    if (_allTab) _allTab.classList.add('active');
    if (typeof setContactFilter === 'function') setContactFilter('visitor');
    // 상태에 따라 비회원 안내 또는 일반 리스트
    if (typeof renderContactsWithState === 'function') {
      renderContactsWithState();
    } else {
      renderContactsList();
    }
  } else if (tab === 'received') {
    if (screenReceived) screenReceived.classList.add('active');
    if (typeof renderInboxFromAPI === 'function') {
      renderInboxFromAPI('', true);
    } else if (typeof renderVisitorList === 'function') {
      renderVisitorList();
    }
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

  // 헤더 점3개 메뉴는 채팅탭에서만 표시
  const moreBtnWrap = moreBtn ? moreBtn.closest('.more-menu-wrap') : null;
  if (moreBtnWrap) {
    moreBtnWrap.style.display = tab === 'chat' ? '' : 'none';
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
// 헤더 단일 돋보기 버튼 — 탭에 따라 검색창 연결
// ===========================
(function() {
  const headerSearchBtn = document.getElementById('headerSearchBtn');
  if (!headerSearchBtn) return;

  // 탭별 검색 설정 맵
  const searchMap = {
    chat:      { rowId: 'chatSearchRow',     inputId: 'chatSearchInput'     },
    contacts:  { rowId: 'contactsSearchRow', inputId: 'contactsSearchInput' },
    received:  { rowId: 'inboxSearchRow',    inputId: 'inboxSearchInput'    },
  };

  headerSearchBtn.addEventListener('click', function() {
    const cfg = searchMap[currentTab];
    if (!cfg) return;
    const row   = document.getElementById(cfg.rowId);
    const input = document.getElementById(cfg.inputId);
    if (!row) return;

    const isOpen = row.style.display !== 'none';
    if (isOpen) {
      // 닫기
      row.style.display = 'none';
      headerSearchBtn.classList.remove('search-toggle-active');
      if (input) { input.value = ''; input.dispatchEvent(new Event('input')); }
    } else {
      // 열기
      row.style.display = '';
      headerSearchBtn.classList.add('search-toggle-active');
      if (input) setTimeout(() => input.focus(), 50);
    }
  });

  // 외부에서 호출 가능하도록 노출
  window._closeHeaderSearch = function() {
    const cfg = searchMap[currentTab];
    if (cfg) {
      const row   = document.getElementById(cfg.rowId);
      const input = document.getElementById(cfg.inputId);
      if (row) row.style.display = 'none';
      if (input) { input.value = ''; input.dispatchEvent(new Event('input')); }
    }
    headerSearchBtn.classList.remove('search-toggle-active');
  };
})();

// ===========================
// 검색: 채팅 탭 (dm.js의 loadDMRooms에 위임)
// ===========================
chatSearchInput.addEventListener('input', () => {
  const q = chatSearchInput.value.trim();
  chatClearBtn.classList.toggle('visible', q.length > 0);
  // dm.js가 dmRoomList를 담당하므로 dm 검색 함수 호출
  if (typeof loadDMRoomsWithQuery === 'function') {
    loadDMRoomsWithQuery(q);
  } else if (typeof loadDMRooms === 'function') {
    loadDMRooms();
  }
});

chatClearBtn.addEventListener('click', () => {
  chatSearchInput.value = '';
  chatClearBtn.classList.remove('visible');
  if (typeof loadDMRooms === 'function') loadDMRooms();
  chatSearchInput.focus();
});

// ===========================
// 검색: 개별리스트 탭
// ===========================
contactsSearchInput.addEventListener('input', () => {
  const q = contactsSearchInput.value.trim();
  contactsClearBtn.classList.toggle('visible', q.length > 0);
  if (typeof renderContactsListFromAPI === 'function') {
    renderContactsListFromAPI(q, true);
  } else {
    renderContactsList(q);
  }
});

contactsClearBtn.addEventListener('click', () => {
  contactsSearchInput.value = '';
  contactsClearBtn.classList.remove('visible');
  if (typeof renderContactsListFromAPI === 'function') {
    renderContactsListFromAPI('', true);
  } else {
    renderContactsList();
  }
  contactsSearchInput.focus();
});

// ===========================
// 검색: 수신 탭 (inboxSearchInput)
// ===========================
document.addEventListener('DOMContentLoaded', function() {
  const inboxSearchInput = document.getElementById('inboxSearchInput');
  const inboxClearBtn    = document.getElementById('inboxClearBtn');
  if (inboxSearchInput) {
    inboxSearchInput.addEventListener('input', () => {
      const q = inboxSearchInput.value.trim();
      if (inboxClearBtn) inboxClearBtn.classList.toggle('visible', q.length > 0);
      if (typeof renderInboxFromAPI === 'function') renderInboxFromAPI(q, true);
    });
  }
  if (inboxClearBtn) {
    inboxClearBtn.addEventListener('click', () => {
      if (inboxSearchInput) { inboxSearchInput.value = ''; inboxSearchInput.focus(); }
      inboxClearBtn.classList.remove('visible');
      if (typeof renderInboxFromAPI === 'function') renderInboxFromAPI('', true);
    });
  }
});

// ===========================
// ③ 공유리스트 탭: visitor_id 기반 방문자 목록 (mock)
// ===========================
function renderReceivedList(query = '') {
  if (!receivedList) return;
  receivedList.innerHTML = '';

  let data = [...receivedData].sort((a, b) =>
    (b.lastChat || b.receivedDate || '').localeCompare(a.lastChat || a.receivedDate || '')
  );

  if (query) {
    data = data.filter(p =>
      p.name.includes(query) ||
      p.botName.includes(query) ||
      p.company.includes(query) ||
      p.phone.includes(query)
    );
  }

  receivedBadge.textContent = `${receivedData.length}명`;

  if (data.length === 0) {
    showEmpty(receivedList, 'fas fa-inbox',
      query ? '검색 결과가 없습니다' : '수신된 챗봇 링크가 없어요',
      query ? '' : '누군가에게 챗봇 링크를 받으면 여기에 표시됩니다'
    );
    return;
  }

  // 대화중 / 미대화 그룹 분리
  const withChat    = data.filter(p => p.chatCount > 0);
  const withoutChat = data.filter(p => p.chatCount === 0);

  if (withChat.length > 0) {
    appendSectionLabel(receivedList, 'fas fa-comments', `대화중 · ${withChat.length}명`);
    withChat.forEach((p, i) => {
      const card = buildReceivedCard(p, i);
      receivedList.appendChild(card);
    });
  }

  if (withoutChat.length > 0) {
    appendSectionLabel(receivedList, 'fas fa-envelope-open', `미대화 · ${withoutChat.length}명`);
    withoutChat.forEach((p, i) => {
      const card = buildReceivedCard(p, withChat.length + i);
      receivedList.appendChild(card);
    });
  }
}

function buildReceivedCard(person, idx) {
  const hasChat = person.chatCount > 0;
  const card    = document.createElement('div');
  card.className = `contact-card received-card${hasChat ? ' has-chat' : ''}`;
  card.style.animationDelay = `${idx * 0.03}s`;

  const timeLabel = formatRelativeDate(person.lastChat || person.receivedDate);
  const lastMsg   = person.messages.slice(-1)[0];
  const previewText = lastMsg
    ? (lastMsg.type === 'user' ? '나: ' : '🤖 ') + lastMsg.text.split('\n')[0].slice(0, 26)
    : '아직 대화 없음';

  const unread = person.unreadCount || 0;

  const _cp2 = person.profile ? person.profile.trim() : '';
  const _ph2 = _cp2
    ? `<img class="cc-avatar-photo" src="https://kiam.kr${_cp2}" alt="${getInitial(person.name)}" onerror="this.src='${getDiceBearUrl(person.mem_id||person.name, person.name)}';">`
    : `<img class="cc-avatar-photo" src="${getDiceBearUrl(person.mem_id||person.name, person.name)}" alt="${getInitial(person.name)}" onerror="this.remove()">`;
  card.innerHTML = `
    <div class="cc-avatar ${person.colorClass}" style="position:relative;overflow:hidden;">
      ${_ph2}
      <div class="received-bot-badge" title="AI 챗봇"><i class="fas fa-robot"></i></div>
      ${hasChat ? `<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>` : ''}
    </div>
    <div class="cc-info" style="flex:1;min-width:0;">
      <div class="cc-name-row">
        <span class="cc-name${unread > 0 ? ' has-unread' : ''}">${person.name}</span>
        ${unread > 0
          ? `<span class="cc-chat-badge unread" style="background:rgba(239,68,68,0.12);color:#ef4444;">${unread}개 미읽음</span>`
          : hasChat
            ? `<span class="cc-chat-badge"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:3px;"></i>${person.chatCount}회</span>`
            : `<span class="cc-unused-tag">미대화</span>`
        }
      </div>
      <div class="cc-position" style="font-size:11px;color:var(--accent-blue);font-weight:600;margin-bottom:2px;">
        <i class="fas fa-robot" style="margin-right:3px;font-size:10px;"></i>${person.botName}
      </div>
      <div class="cc-position">${person.position} · ${person.company}</div>
      ${hasChat ? `<div class="cc-preview-text">${previewText}</div>` : ''}
    </div>
    <div class="cc-right">
      <span class="chat-time" style="font-size:10px;color:var(--text-muted);">${timeLabel}</span>
      <i class="fas fa-chevron-right cc-arrow" style="margin-top:6px;"></i>
    </div>
  `;

  card.addEventListener('click', () => openReceivedChatScreen(person));
  return card;
}

// 수신 챗봇 채팅 화면 열기
function openReceivedChatScreen(person) {
  currentPerson = person;

  // 읽음 처리
  person.messages.forEach(m => { if (m.type === 'user') m.read = true; });
  person.unreadCount = 0;

  // 채팅 헤더 업데이트
  setDiceBearAvatar(cspAvatar, person.mem_id || person.name, person.name);
  cspAvatar.className    = `csp-avatar ${person.colorClass}`;
  cspName.textContent    = person.name;
  cspPosition.textContent = `${person.position} · ${person.company}`;

  // 메시지 렌더
  if (!person.messages || person.messages.length === 0) {
    chatMessages.innerHTML = `
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                  flex:1;padding:60px 20px;gap:14px;color:#9ca3af;text-align:center;">
        <div style="width:68px;height:68px;border-radius:20px;background:rgba(59,130,246,0.1);
                    display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-robot" style="font-size:28px;color:#3b82f6;"></i>
        </div>
        <p style="font-size:15px;font-weight:700;color:#475569;">${person.botName}</p>
        <p style="font-size:13px;color:#94a3b8;line-height:1.7;">
          아직 대화를 시작하지 않았어요.<br>메시지를 보내 대화를 시작해보세요!
        </p>
        <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:10px;
                    padding:8px 14px;font-size:11px;color:#3b82f6;font-weight:600;">
          <i class="fas fa-link" style="margin-right:4px;"></i>${person.chatbotUrl}
        </div>
      </div>
    `;
  } else {
    renderMessages(person.messages);
  }

  // 수신 챗봇이므로 봇 ON 상태 + 수신용 레이블 표시
  isBotOn = true;
  botToggle.checked = true;
  updateBotUI();

  chatScreen.classList.add('active');
  document.body.style.overflow = 'hidden';

  isBotOn = true;
  botToggle.checked = true;
  const botOnTimeout = setTimeout(() => {
    chatMessages.scrollTop = chatMessages.scrollHeight;
    clearTimeout(botOnTimeout);
  }, 100);
}

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
  // PC 전체화면 새 탭으로 열기 (모바일 앱 안에 가두지 않음)
  window.open('/aimessage/onechat/dashboard.html', '_blank');
}

function closeDashboard() {
  if (dashboardPanel) dashboardPanel.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');
  document.body.style.overflow = '';
}

// dashboard.html iframe → 부모창 메시지 수신 (닫기 버튼 처리)
window.addEventListener('message', function(e) {
  if (e.data && e.data.type === 'dashboard_close') {
    closeDashboard();
  }
});

if (dashboardBtn) dashboardBtn.addEventListener('click', openDashboard);
if (dashCloseBtn) dashCloseBtn.addEventListener('click', closeDashboard);

const dashInner = document.querySelector('.dashboard-inner');
let dashTouchStartY = 0;

if (dashInner) dashInner.addEventListener('touchstart', (e) => {
  dashTouchStartY = e.touches[0].clientY;
}, { passive: true });

if (dashInner) dashInner.addEventListener('touchmove', (e) => {
  const deltaY = e.touches[0].clientY - dashTouchStartY;
  const body   = document.querySelector('.dashboard-body');
  if (deltaY > 0 && body.scrollTop === 0) {
    dashInner.style.transform = `translateY(${Math.min(deltaY * 0.4, 100)}px)`;
  }
}, { passive: true });

if (dashInner) dashInner.addEventListener('touchend', (e) => {
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
    <div class="msg-avatar" style="overflow:hidden;border-radius:50%;">
      <img src="${getDiceBearUrl(currentPerson && (currentPerson.mem_id || currentPerson.name) || 'peer', currentPerson && currentPerson.name)}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.remove()">
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

  // 아바타 ON이고 일시정지 아닐 때 → DeepSeek API 실제 호출
  if (isBotOn && !avatarPausedByPeer) {
    // 타이핑 인디케이터 표시
    const typingDiv = document.createElement('div');
    typingDiv.className = 'msg-row bot';
    typingDiv.id = 'typingIndicator';
    typingDiv.innerHTML = `
      <div class="msg-body">
        <span class="msg-sender-label label-my-bot">\u{1F916} MY AI</span>
        <div class="msg-bubble my-bot-bubble" style="color:var(--text-muted);font-style:italic;">답변 생성 중...</div>
      </div>
    `;
    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // 학습 데이터: localStorage 대신 서버에서 직접 처리 (ai_reply_onechat.php가 서버 learn_data.json 참조)
    // 프론트에서는 learnContext를 빈 문자열로 전달 (서버가 자동으로 관련 데이터 선별)
    const learnContext = '';

    // 프로필 데이터 — 서버(window.ONE_MEMBER) 기반
    const profileName = (window.ONE_MEMBER && (window.ONE_MEMBER.display || window.ONE_MEMBER.name || window.ONE_MEMBER.mem_id)) || '';

    // 프롬프트 ★ global_prompt는 서버(DB)에서 직접 로드 → localStorage 사용 안 함
    const globalPromptText = '';
    const personPrompt = (currentPerson && currentPerson.promptConfig) ? (currentPerson.promptConfig.customText || '') : '';

    // 최근 대화 히스토리 (최대 10개)
    let historyContext = '';
    if (currentPerson && currentPerson.messages) {
      const recent = currentPerson.messages.slice(-10);
      historyContext = recent.map(m => {
        if (m.type === 'user')            return `상대: ${m.text}`;
        if (m.type === 'owner')           return `나: ${m.text}`;
        if (m.type === 'bot' || m.type === 'my-bot') return `AI아바타: ${m.text}`;
        return '';
      }).filter(Boolean).join('\n');
    }

    // visitor_id, sms_idx: currentChatInfo에서 직접 추출 (세션 없는 visitor 경우도 sms_idx 정확히 전달)
    const _visitorId  = (typeof currentChatInfo !== 'undefined' && currentChatInfo && currentChatInfo.visitor_id)
                        ? currentChatInfo.visitor_id : '';
    const _visitorSmsIdx = (typeof currentChatInfo !== 'undefined' && currentChatInfo && currentChatInfo.sms_idx)
                        ? currentChatInfo.sms_idx : 0;

    fetch('./api/ai_reply_onechat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        user_message:  text,
        profile_name:  profileName,
        global_prompt: globalPromptText,
        person_prompt: personPrompt,
        learn_context: learnContext,
        history:       historyContext,
        person_id:     currentPerson ? currentPerson.id : '',
        visitor_id:    _visitorId,
        sms_idx:       _visitorSmsIdx
      })
    })
    .then(r => r.json())
    .then(data => {
      const indicator = document.getElementById('typingIndicator');
      if (indicator) indicator.remove();

      const botResponse = data.text || data.error || 'AI 응답을 받지 못했습니다.';
      const now2 = new Date();
      const t2 = `${now2.getHours()}:${String(now2.getMinutes()).padStart(2,'0')}`;
      const d2 = now2.toISOString().slice(0,10);

      if (currentPerson) {
        currentPerson.messages.push({ type: 'bot', text: botResponse, time: `${d2} ${t2}`, read: true });
        currentPerson.unreadCount = 0;
        currentPerson.messages.forEach(m => { if (m.type === 'user') m.read = true; });
        updateChatMeta(currentPerson);
        updateUnreadBadgeInList(currentPerson);
      }
      // ★ visitor 채팅: visitor_chat_log에 AI 응답 저장
      if (typeof currentChatInfo !== 'undefined' && currentChatInfo &&
          currentChatInfo.source === 'visitor' && currentChatInfo.visitor_id) {
        fetch('./api/visitor_list.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            mode: 'save_msg',
            visitor_id: currentChatInfo.visitor_id,
            sms_idx: currentChatInfo.sms_idx,
            message: botResponse,
            role: 'assistant'
          })
        }).catch(e => console.warn('[AI Save Error]', e));
      }
      const botDiv = document.createElement('div');
      botDiv.className = 'msg-row bot';
      botDiv.style.animation = 'msgIn 0.25s ease both';
      botDiv.innerHTML = `
        <div class="msg-body">
          <span class="msg-sender-label label-my-bot">\u{1F916} MY AI</span>
          <div class="msg-bubble my-bot-bubble">${escapeHtml(botResponse)}</div>
          <span class="msg-time">${t2}</span>
        </div>
      `;
      chatMessages.appendChild(botDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
      // TTS: AI 응답 음성 재생
      if (window.voiceChat && typeof window.voiceChat.speakText === 'function') {
        window.voiceChat.speakText(botResponse);
      }
    })
    .catch(err => {
      const indicator = document.getElementById('typingIndicator');
      if (indicator) indicator.remove();
      console.error('[AI Reply Error]', err);
    });
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

// ── 화상/음성통화 버튼 (HI모드 방문자 채팅) ──
const csVideoCallBtn = document.getElementById('csVideoCallBtn');
const csVoiceCallBtn = document.getElementById('csVoiceCallBtn');
if (csVideoCallBtn) csVideoCallBtn.addEventListener('click', () => startOperatorVideoCall('video'));
if (csVoiceCallBtn) csVoiceCallBtn.addEventListener('click', () => startOperatorVideoCall('voice'));

function startOperatorVideoCall(mode) {
  const vid     = chatScreen.dataset.visitorId || '';
  const smsIdx  = parseInt(chatScreen.dataset.visitorSmsIdx, 10) || 0;
  const chatRoomId = vid || '';

  fetch('/admin/ajax/onechat_video_api.php?action=create_room&chat_room_id=' + encodeURIComponent(chatRoomId),
        { credentials: 'include' })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) { alert('통화 방 생성 실패: ' + (d.msg || '')); return; }
      const uuid = d.room_uuid;
      // 운영자 팝업
      let url = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(uuid);
      if (chatRoomId) url += '&chat_room_id=' + encodeURIComponent(chatRoomId);
      if (mode === 'voice') url += '&mode=voice';
      window.open(url, 'vc_room', 'width=900,height=700,menubar=no,toolbar=no,resizable=yes');
      // 방문자에게 초대 메시지 전송
      if (vid && smsIdx) {
        const inviteMsg = '__VCALL__:' + uuid + ':' + vid + ':' + mode;
        apiPost('visitor_list.php', { mode: 'send_hi', visitor_id: vid, sms_idx: smsIdx, message: inviteMsg })
          .catch(e => console.warn('[통화초대 전송 오류]', e));
      }
    })
    .catch(() => alert('통화 연결 오류'));
}

// 배경 / 닫기 버튼
promptPanel.addEventListener('click', e => { if (e.target === promptPanel) closePromptPanel(); });
promptCloseBtn.addEventListener('click', closePromptPanel);

// globalPrompt는 chatbot_setting.php API에서 로드 (api.js 참조)

// ===========================
// 테마 (라이트 / 다크)
// ===========================
const themeLight = document.getElementById('themeLight');
const themeDark  = document.getElementById('themeDark');

function applyTheme(theme) {
  if (theme === 'light') {
    document.body.classList.add('theme-light');
    if (themeLight) themeLight.classList.add('active');
    if (themeDark)  themeDark.classList.remove('active');
  } else {
    document.body.classList.remove('theme-light');
    if (themeDark)  themeDark.classList.add('active');
    if (themeLight) themeLight.classList.remove('active');
  }
  localStorage.setItem('appTheme', theme);
}

if (themeLight) themeLight.addEventListener('click', () => { applyTheme('light'); });
if (themeDark)  themeDark.addEventListener('click',  () => { applyTheme('dark');  });

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

// 프로필 데이터: localStorage 제거 → 모두 서버(me.php/DB) 기반
// window.ONE_MEMBER = me.php 응답의 user 객체 (api.js DOMContentLoaded에서 설정)
let profileData = { shortCode: '', globalPrompt: '' }; // DB 없는 항목만 메모리 보관
function loadProfileData() {}   // no-op (서버에서 로드)
function saveProfileData() {}   // no-op (서버에 저장)

// 프로필 사진 또는 이니셜 HTML 반환
function getMyAvatarHtml() {
  const url = window.ONE_MEMBER && window.ONE_MEMBER.profile_url;
  if (url) return `<img src="${url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
  const m = window.ONE_MEMBER || {};
  const name = m.display || m.name || m.mem_id || '';
  return name ? name.charAt(0) : '?';
}

function applyProfileToHeader() {
  // 서버(me.php) 데이터 우선, 없으면 mem_id(아이디) 표시
  const m = window.ONE_MEMBER || {};
  const name = m.display || m.name || m.mem_id || '';
  const initial = name ? name.charAt(0) : '?';
  const profileUrl = m.profile_url || '';

  const adpAvatar    = document.querySelector('.adp-avatar');
  const adpName      = document.querySelector('.adp-name');
  const adpRole      = document.querySelector('.adp-role');
  const headerAvatar = document.getElementById('avatarBtn');

  function setAvatar(el) {
    if (!el) return;
    if (profileUrl) {
      el.innerHTML = `<img src="${profileUrl}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
    } else {
      el.textContent = initial;
    }
  }
  setAvatar(adpAvatar);
  setAvatar(headerAvatar);
  if (adpName) adpName.textContent = name;
  if (adpRole) adpRole.textContent = '';

  // 사이드바도 함께 갱신
  const sbAvatar = document.getElementById('sidebarProfileAvatar');
  const sbName   = document.getElementById('sidebarProfileName');
  setAvatar(sbAvatar);
  if (sbName) sbName.textContent = name || '(이름 없음)';
}

function openProfilePanel() {
  // 해시 라우팅: 프로필 패널 열릴 때 URL 업데이트
  history.pushState({ menu: 'profile' }, '', '#profile');
  // 닫기 아바타 드롭다운
  document.getElementById('avatarDropdown').classList.remove('open');

  // 폼 채우기 — 서버(me.php) 데이터 기반
  const _m = window.ONE_MEMBER || {};
  const _sender = _m.sender || {};
  const _vendor = _m.vendor || {};
  document.getElementById('pfName').value    = _m.name    || _m.mem_id || '';
  document.getElementById('pfTitle').value   = _m.job        || '';
  document.getElementById('pfStatus').value  = _m.status_msg || '';
  document.getElementById('pfPhone').value   = _m.phone      || '';
  document.getElementById('pfEmail').value   = _m.email      || '';
  document.getElementById('pfCompany').value = _m.company    || '';
  document.getElementById('pfShortCode').value = profileData.shortCode || _m.short_code || '';
  document.getElementById('pfGlobalPrompt').value = globalPrompt || '';

  // Phase 3: 발신자/사업체 필드 prefill (Gn_Member 글로벌 프로필)
  const _setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
  _setVal('pfSenderName',           _sender.name);
  _setVal('pfSenderPosition',       _sender.position);
  _setVal('pfSenderAffiliation',    _sender.affiliation);
  _setVal('pfSenderCompanyAddress', _sender.companyaddress);
  _setVal('pfSenderHomeAddress',    _sender.homeaddress);
  _setVal('pfSenderIntroduction',   _sender.introduction);
  _setVal('pfVendorName',           _vendor.name);
  _setVal('pfVendorIndustry',       _vendor.industry);

  // 사진 미리보기
  const _profileUrl = (window.ONE_MEMBER && window.ONE_MEMBER.profile_url) || profileData.photoPreview || '';
  if (_profileUrl) {
    pfAvatarImg.src = _profileUrl;
    pfAvatarImg.style.display = 'block';
    pfAvatarInitial.style.display = 'none';
    pfPhotoRemove.style.display = 'inline-flex';
  } else {
    pfAvatarImg.style.display = 'none';
    pfAvatarInitial.textContent = (window.ONE_MEMBER && (window.ONE_MEMBER.display || window.ONE_MEMBER.name || window.ONE_MEMBER.mem_id) || '?').charAt(0);
    pfAvatarInitial.style.display = '';
    pfPhotoRemove.style.display = 'none';
  }
  profileData.photoRemoved = false;

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
  const link = code ? `https://${CHATBOT_DOMAIN}/s/${code}` : '';
  document.getElementById('pfChatbotLink').value = link;
}

// QR 코드 생성 (qrcode.js CDN 사용)
function generateQR() {
  const link = document.getElementById('pfChatbotLink').value || '';
  pfQrBox.innerHTML = '';

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
profileCloseBtn.addEventListener('click', closeProfilePanel);
pfCancelBtn.addEventListener('click', closeProfilePanel);
profilePanelOverlay.addEventListener('click', closeProfilePanel);

// 사진 업로드
pfPhotoInput.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  profileData.photoFile = file;   // 업로드용 File 객체 보관
  profileData.photoRemoved = false;
  const reader = new FileReader();
  reader.onload = ev => {
    profileData.photoPreview = ev.target.result; // 미리보기용
    pfAvatarImg.src = ev.target.result;
    pfAvatarImg.style.display = 'block';
    pfAvatarInitial.style.display = 'none';
    pfPhotoRemove.style.display = 'inline-flex';
  };
  reader.readAsDataURL(file);
});

// 사진 삭제
pfPhotoRemove.addEventListener('click', () => {
  profileData.photoFile    = null;
  profileData.photoPreview = null;
  profileData.photoRemoved = true; // 저장 시 DELETE 호출
  pfAvatarImg.src = '';
  pfAvatarImg.style.display = 'none';
  pfAvatarInitial.textContent = (window.ONE_MEMBER && (window.ONE_MEMBER.display || window.ONE_MEMBER.name || window.ONE_MEMBER.mem_id) || '?').charAt(0);
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
  profileData.shortCode  = document.getElementById('pfShortCode').value.trim();
  profileData.name       = document.getElementById('pfName').value.trim();
  const newPrompt        = document.getElementById('pfGlobalPrompt').value.trim();
  if (newPrompt) {
    profileData.globalPrompt = newPrompt;
    globalPrompt = newPrompt;
  }

  // 사진 업로드 or 삭제 처리
  function finishSave(showSuccess) {
    applyProfileToHeader();
    closeProfilePanel();
    if (showSuccess !== false) showToast('프로필이 저장되었습니다 ✅');
  }

  if (profileData.photoFile) {
    // 새 사진 업로드
    const fd = new FormData();
    fd.append('photo', profileData.photoFile);
    fetch('./api/profile_photo.php', { method: 'POST', credentials: 'include', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          if (!window.ONE_MEMBER) window.ONE_MEMBER = {};
          window.ONE_MEMBER.profile_url = d.url;
          profileData.photoFile = null;
          profileData.photoPreview = null;
        } else {
          const _em = typeof d.error === 'string' ? d.error : (d.error && d.error.message) || '오류';
          showToast('사진 저장 실패: ' + _em);
          finishSave(false);
          return;
        }
        saveSenderProfile();
        finishSave();
      })
      .catch(() => { showToast('사진 저장 중 오류 발생'); finishSave(); });
    return; // 비동기 완료 후 finishSave 호출
  } else if (profileData.photoRemoved) {
    // 사진 삭제
    const _dfd = new FormData(); _dfd.append('action', 'delete');
    fetch('./api/profile_photo.php', { method: 'POST', credentials: 'include', body: _dfd })
      .then(r => r.json())
      .then(() => {
        if (!window.ONE_MEMBER) window.ONE_MEMBER = {};
        window.ONE_MEMBER.profile_url = null;
        profileData.photoRemoved = false;
        saveSenderProfile();
        finishSave();
      })
      .catch(() => finishSave());
    return;
  }

  finishSave();

  // Phase 3: 발신자/사업체 정보 저장 (profile.php POST → Gn_Member UPDATE)
  function saveSenderProfile() {
    const _g = (id) => { const el = document.getElementById(id); return el ? el.value.trim() : ''; };
    const payload = {
      basic: {
        name:       _g('pfName'),
        phone:      _g('pfPhone'),
        email:      _g('pfEmail'),
        job:        _g('pfTitle'),
        status_msg: _g('pfStatus'),
        company:    _g('pfCompany'),
      },
      sender: {
        name:           _g('pfSenderName'),
        position:       _g('pfSenderPosition'),
        affiliation:    _g('pfSenderAffiliation'),
        companyaddress: _g('pfSenderCompanyAddress'),
        homeaddress:    _g('pfSenderHomeAddress'),
        introduction:   _g('pfSenderIntroduction'),
      },
      vendor: {
        name:     _g('pfVendorName'),
        industry: _g('pfVendorIndustry'),
      },
    };
    fetch('./api/profile.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        // 메모리 ONE_MEMBER도 즉시 갱신
        if (!window.ONE_MEMBER) window.ONE_MEMBER = {};
        if (d.profile && d.profile.basic) {
          const _b = d.profile.basic;
          if (_b.name  !== undefined) window.ONE_MEMBER.name       = _b.name;
          if (_b.phone !== undefined) window.ONE_MEMBER.phone      = _b.phone;
          if (_b.email !== undefined) window.ONE_MEMBER.email      = _b.email;
          if (_b.job   !== undefined) window.ONE_MEMBER.job        = _b.job;
          if (_b.status_msg !== undefined) window.ONE_MEMBER.status_msg = _b.status_msg;
          if (_b.company    !== undefined) window.ONE_MEMBER.company    = _b.company;
        }
        window.ONE_MEMBER.sender = d.profile && d.profile.sender ? d.profile.sender : window.ONE_MEMBER.sender;
        window.ONE_MEMBER.vendor = d.profile && d.profile.vendor ? d.profile.vendor : window.ONE_MEMBER.vendor;
        console.log('[프로필 저장] 기본정보/발신자/사업체 저장 완료');
      } else {
        console.warn('[프로필 저장] 발신자 저장 실패:', d.error);
      }
    })
    .catch(e => console.warn('[프로필 저장] 발신자 저장 오류:', e));
  }

  // saveSenderProfile 호출 (프로필 텍스트 저장)
  saveSenderProfile();

  // shortCode → DB 등록 (공유링크 자동 등록)
  if (profileData.shortCode) {
    var payload = { short_code: profileData.shortCode };
    if (typeof contactSmsIdx !== 'undefined' && contactSmsIdx > 0) {
      payload.sms_idx = contactSmsIdx;
    }
    apiPost('shared_link.php', payload).catch(function() {});
  }

  // ★ 챗봇 이름을 DB와 동기화 (수신자 링크 헤더에 즉시 반영)
  // 프로필 "이름" 필드 변경 → chatbot_setting.php → DB 2개 테이블 자동 업데이트
  (function syncChatbotName() {
    var name = profileData.name;
    if (!name) return;
    var syncIdx = (typeof mySmsIdx !== 'undefined' && mySmsIdx > 0)
      ? mySmsIdx
      : ((typeof cbCurrentSmsIdx !== 'undefined' && cbCurrentSmsIdx > 0) ? cbCurrentSmsIdx : 0);

    function doSync(n, idx) {
      fetch('./api/chatbot_setting.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sms_idx: idx, chatbot_name: n }),
      })
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (data.success) {
          console.log('[프로필 저장] 챗봇 이름 DB 동기화 완료:', n, '/ sms_idx:', idx);
          var cbName = document.getElementById('cbSettingBotName');
          if (cbName) cbName.textContent = n;
          var cbInput = document.getElementById('cbInputName');
          if (cbInput) cbInput.value = n;
        } else {
          console.warn('[프로필 저장] 챗봇 이름 동기화 실패:', data.error);
        }
      })
      .catch(function(e){ console.warn('[프로필 저장] 챗봇 이름 동기화 오류:', e); });
    }

    if (syncIdx > 0) {
      doSync(name, syncIdx);
    } else {
      // sms_idx 없으면 GET으로 내 챗봇 조회 후 저장
      fetch('./api/chatbot_setting.php', { credentials: 'include' })
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (d.success && d.list && d.list.length > 0) {
            var idx = d.list[0].sms_idx;
            if (idx) {
              mySmsIdx = idx;
              doSync(name, idx);
            }
          }
        }).catch(function(){});
    }
  })();
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

// 초기 헤더 적용 — window.ONE_MEMBER는 api.js DOMContentLoaded에서 me.php로 설정됨
window.ONE_MEMBER = null;
applyProfileToHeader(); // 초기 빈 상태 적용, api.js 로드 후 재호출됨

// ===========================
// 사용자 상태 시스템
// userState: 'member-with-bot' | 'member-no-bot' | 'guest'
// ===========================
let userState = 'member-with-bot'; // me.php has_bot으로 api.js에서 설정

const makeBotBtn   = document.getElementById('makeBotBtn');
const makeBotPanel = document.getElementById('makeBotPanel');
const makeBotOverlay = document.getElementById('makeBotOverlay');
const makeBotCloseBtn = document.getElementById('makeBotCloseBtn');
const homeNavBtn   = document.getElementById('homeNavBtn');

// 상태 적용 함수
function applyUserState(state) {
  userState = state;

  const isMemberWithBot = state === 'member-with-bot';
  const isMemberNoBot   = state === 'member-no-bot';
  const isGuest         = state === 'guest';

  // 1. MY 챗봇 만들기 버튼: 챗봇이 없는 경우에만 표시
  makeBotBtn.style.display = (isMemberNoBot || isGuest) ? '' : 'none';

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
    // 회원: API 실데이터 렌더
    if (typeof renderContactsListFromAPI === 'function') {
      renderContactsListFromAPI('', true);
    } else {
      renderContactsList();
    }
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
    document.getElementById('mbLinkValue').textContent = `https://${CHATBOT_DOMAIN}/s/${uniqueCode}`;
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
  const link = `https://${CHATBOT_DOMAIN}/s/${uniqueCode}`;
  document.getElementById(targetElId).textContent = link;
  return link;
}

// ── 회원 플로우 이벤트 ──
document.getElementById('mbMemberIssueBtn').addEventListener('click', () => {
  const link = issueBotLink('mbIssuedLink');
  document.getElementById('mbLinkCard').style.display = 'none';
  document.getElementById('mbMemberIssueBtn').style.display = 'none';
  document.getElementById('mbIssuedWrap').style.display = 'flex';
  document.getElementById('mbIssuedLink').textContent = link;
  showToast('🎉 챗봇 링크가 발급되었습니다!');
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
makeBotBtn.addEventListener('click', openMakeBotPanel);
makeBotCloseBtn.addEventListener('click', closeMakeBotPanel);
makeBotOverlay.addEventListener('click', closeMakeBotPanel);

// ── 채팅/명함 탭 + 버튼도 동일하게 챗봇 생성 패널 열기 ──
const _addUserBtnChat = document.getElementById('addUserBtnChat');
const _addUserBtnContacts = document.getElementById('addUserBtnContacts');
if (_addUserBtnChat) _addUserBtnChat.addEventListener('click', openMakeBotPanel);
if (_addUserBtnContacts) _addUserBtnContacts.addEventListener('click', openMakeBotPanel);

// ── 데모 상태 전환 버튼 ──
document.querySelectorAll('.usb-btn').forEach(btn => {
  btn.addEventListener('click', () => applyUserState(btn.dataset.state));
});

// ── 홈 버튼: 사용자 분양사 도메인의 /m 으로 이동 (2026-05-22 형) ──
// go_home.php 가 세션에서 site_iam 조회 후 적절한 도메인으로 302 redirect:
//   - kiam/빈값 → www.kiam.kr/m
//   - onechat    → onechat.kiam.kr/m
//   - onlysong 등 분양사 → {site_iam}.kiam.kr/m
homeNavBtn.addEventListener('click', () => {
  location.href = '/aimessage/onechat/go_home.php';
});

// 초기 상태 적용 — api.js 로드 완료 후 실행
window.addEventListener('load', function() {
  applyUserState(userState);
});


// ===========================
// 공유리스트: visitor_id 기반 렌더링
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
  // custom_name(운영자 직접) → nickname(DB 한글닉네임) → 해시 생성 순
  return v.custom_name || v.nickname || generateNickname(v.visitor_id || v.visitorId || '');
}

var _visitorListLoading = false;

async function renderVisitorList(query) {
  query = query || '';
  if (!receivedList) return;
  if (_visitorListLoading) return;
  _visitorListLoading = true;

  receivedList.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> 로딩 중...</div>';

  try {
    var params = { page: 1, q: query };
    if (typeof contactSmsIdx !== 'undefined' && contactSmsIdx > 0) params.sms_idx = contactSmsIdx;

    var data = await apiGet('visitor_list.php', params);
    if (!data || !data.success) throw new Error('API 오류');

    var visitors = data.visitors || [];
    receivedList.innerHTML = '';

    var total = data.total || visitors.length;
    if (receivedBadge) receivedBadge.textContent = total + '명';

    if (visitors.length === 0) {
      showEmpty(receivedList, 'fas fa-users',
        query ? '검색 결과가 없습니다' : '공유 링크 접속자가 없어요',
        query ? '' : '운영자 링크를 공유하면 접속자가 여기에 표시됩니다'
      );
      return;
    }

    var withChat    = visitors.filter(function(v) { return v.chat_count > 0; });
    var withoutChat = visitors.filter(function(v) { return v.chat_count === 0; });

    if (withChat.length > 0) {
      appendSectionLabel(receivedList, 'fas fa-comments', '대화중 · ' + withChat.length + '명');
      withChat.forEach(function(v, i) { receivedList.appendChild(buildVisitorCard(v, i)); });
    }
    if (withoutChat.length > 0) {
      appendSectionLabel(receivedList, 'fas fa-user-clock', '미대화 · ' + withoutChat.length + '명');
      withoutChat.forEach(function(v, i) { receivedList.appendChild(buildVisitorCard(v, withChat.length + i)); });
    }
  } catch(e) {
    receivedList.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;"><i class="fas fa-exclamation-circle"></i> 데이터를 불러올 수 없습니다.</div>';
  } finally {
    _visitorListLoading = false;
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
    var botUrl = 'https://' + CHATBOT_DOMAIN + '/s/' + visitor.short_code + '?v=' + vid;
    chipHtml = '<span class="chatlink-chip visitor-chatlink-chip">챗봇 링크 <i class="fas fa-copy"></i></span>';
  }

  card.innerHTML =
    '<div class="cc-avatar ' + cc + '" style="position:relative;overflow:hidden;">' +
      '<span>' + initial + '</span>' +
      (hasChat ? '<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>' : '') +
    '</div>' +
    '<div class="cc-info" style="flex:1;min-width:0;">' +
      '<div class="cc-name-row" style="gap:4px;align-items:center;">' +
        '<span class="' + nameClass + '">' + displayName + '</span>' +
        chipHtml +
        badgeHtml +
      '</div>' +
      '<div class="cc-position" style="font-size:11px;color:var(--text-muted);font-weight:500;">' +
        '<i class="fas fa-fingerprint" style="margin-right:3px;font-size:10px;"></i>ID: ' + vid +
        (visitor.chatbot_name ? ' · ' + visitor.chatbot_name : '') +
      '</div>' +
      (visitor.phone ? '<div class="cc-position" style="font-size:11px;color:#3b82f6;font-weight:600;"><i class="fas fa-phone-alt" style="margin-right:3px;font-size:10px;"></i>' + visitor.phone + '</div>' : '') +
      (hasChat ? '<div class="cc-preview-text">' + previewText + '</div>' : '') +
    '</div>' +
    '<div class="cc-right" style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">' +
      '<span class="chat-time" style="font-size:10px;color:var(--text-muted);">' + timeLabel + '</span>' +
      '<button class="visitor-edit-btn" title="이름 수정">' +
        '<i class="fas fa-pen"></i>' +
      '</button>' +
    '</div>';

  // visitor: 공백(자동닉네임)은 이모지, 직접입력은 DiceBear
  var _vAvDiv = card.querySelector('.cc-avatar');
  if (_vAvDiv) {
    if (displayName && displayName.indexOf(' ') >= 0) {
      var _vEmoji = document.createElement('span');
      _vEmoji.textContent = _nicknameToEmoji(displayName);
      _vEmoji.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.5em;line-height:1;';
      _vAvDiv.insertBefore(_vEmoji, _vAvDiv.firstChild);
    } else {
      var _vImg = document.createElement('img');
      _vImg.src = getDiceBearUrl(vid, displayName);
      _vImg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;';
      _vImg.onerror = function() { this.remove(); };
      _vAvDiv.insertBefore(_vImg, _vAvDiv.firstChild);
    }
  }
  if (visitor.short_code) {
    var chipEl = card.querySelector('.visitor-chatlink-chip');
    if (chipEl) {
      chipEl.addEventListener('click', function(e) {
        e.stopPropagation();
        var botUrl = 'https://' + CHATBOT_DOMAIN + '/s/' + visitor.short_code + '?v=' + vid;
        copyBotLink(e, botUrl);
      });
    }
  }
  card.querySelector('.visitor-edit-btn').addEventListener('click', function(e) {
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

  setDiceBearAvatar(cspAvatar, vid, displayName);
  cspAvatar.className = 'csp-avatar ' + colorFromId(vid);
  cspName.textContent = displayName;
  var phoneInfo = visitor.phone ? ' · \u260e ' + visitor.phone : '';
  cspPosition.textContent = 'visitor ID: ' + vid + ' · ' + (visitor.chatbot_name || '챗봇') + phoneInfo;

  chatMessages.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i></div>';

  chatScreen.classList.add('active');
  chatScreen.dataset.visitorId     = vid;
  chatScreen.dataset.visitorSmsIdx = sms_idx;
  // 화상/음성통화 버튼: 방문자 채팅에서만 표시
  const _cvb = document.getElementById('csVideoCallBtn');
  const _cvvb = document.getElementById('csVoiceCallBtn');
  if (_cvb) _cvb.style.display = vid ? '' : 'none';
  if (_cvvb) _cvvb.style.display = vid ? '' : 'none';
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
          '<div class="msg-avatar" style="overflow:hidden;border-radius:50%;">' +
          (isBot ? '<i class="fas fa-robot" style="font-size:12px;color:#16a34a;"></i>' : '<img src="' + getDiceBearUrl(vid, displayName) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.remove()">') +
          '</div>' +
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
var _initialHash = location.hash; // 초기 해시 저장 (switchTab이 덮어쓰기 전)
initPwaFilters();
switchTab('chat');

// ═══════════════════════════════════════════════════════════════
// HI 공지발송 패널 (Broadcast)
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  'use strict';

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

  var openBcMenuBtn = document.getElementById('openBcMenuBtn');
  if (openBcMenuBtn) {
    openBcMenuBtn.addEventListener('click', function() {
      var dd = document.getElementById('avatarDropdown');
      if (dd) dd.classList.remove('open');
      openPanel(bcMenuPanel);
    });
  }

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

  if (bcMsgInput) {
    bcMsgInput.addEventListener('input', function() {
      if (bcPreviewBody) {
        bcPreviewBody.textContent = this.value || '메시지를 입력하면 여기에 미리보기가 표시됩니다.';
      }
    });
  }

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

  if (bcMenuClose)    bcMenuClose.addEventListener('click', function() { closePanel(bcMenuPanel); });
  if (bcComposeClose) bcComposeClose.addEventListener('click', function() { closePanel(bcComposePanel); });
  if (bcHistClose)    bcHistClose.addEventListener('click', function() { closePanel(bcHistPanel); });
  if (bcStatClose)    bcStatClose.addEventListener('click', closeAllPanels);
  if (bcStatBack)     bcStatBack.addEventListener('click', function() {
    closePanel(bcStatPanel);
    openPanel(bcHistPanel);
  });
  if (bcOverlay) bcOverlay.addEventListener('click', closeAllPanels);

  window.openBroadcastCompose = openBroadcastCompose;
  window.openBroadcastHist    = openBroadcastHist;

  // ═══════════════════════════════════════════════════════════════
  // PWA 등록자 패널
  // ═══════════════════════════════════════════════════════════════
  var sbPwaVisitorBtn = document.getElementById('sbPwaVisitorBtn');
  var pvaPage = 1, pvaLoading = false, pvaHasMore = false, pvaQuery = '';
  var pvaSearchTimer = null;

  if (sbPwaVisitorBtn) {
    sbPwaVisitorBtn.addEventListener('click', function() {
      closeSidebar();
      openPvaPanel();
    });
  }

  function openPvaPanel() {
    var panel = document.getElementById('pwaVisitorPanel');
    if (!panel) return;
    panel.style.display = 'flex';
    pvaPage = 1; pvaQuery = '';
    var si = document.getElementById('pvaSearchInput');
    var li = document.getElementById('pvaList');
    if (si) si.value = '';
    if (li) li.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오는 중...</div>';
    loadPvaList(true);
  }

  window.closePvaVisitorPanel = function() {
    var panel = document.getElementById('pwaVisitorPanel');
    if (panel) panel.style.display = 'none';
  };

  var pwaVisitorPanelEl = document.getElementById('pwaVisitorPanel');
  if (pwaVisitorPanelEl) {
    pwaVisitorPanelEl.addEventListener('click', function(e) {
      if (e.target === this) window.closePvaVisitorPanel();
    });
  }

  window.onPvaSearch = function(val) {
    clearTimeout(pvaSearchTimer);
    pvaSearchTimer = setTimeout(function() {
      pvaQuery = val.trim();
      pvaPage = 1;
      loadPvaList(true);
    }, 350);
  };

  async function loadPvaList(reset) {
    if (pvaLoading) return;
    pvaLoading = true;
    var list = document.getElementById('pvaList');
    var moreBtn = document.getElementById('pvaMore');
    if (reset && list) list.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오는 중...</div>';
    var params = { page: pvaPage, phone_only: 1 };
    if (pvaQuery) params.q = pvaQuery;
    var data = await apiGet('visitor_list.php', params);
    pvaLoading = false;
    if (!data || !data.success) {
      if (list) list.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7280;">불러오기 실패</div>';
      return;
    }
    if (reset && list) list.innerHTML = '';
    if (data.visitors.length === 0 && reset) {
      if (list) list.innerHTML = '<div style="text-align:center;padding:40px;color:#6b7280;"><div style="font-size:32px;margin-bottom:10px;">📭</div>아직 PWA 등록자가 없습니다</div>';
      if (moreBtn) moreBtn.style.display = 'none';
      return;
    }
    data.visitors.forEach(function(v) {
      var name = v.custom_name || v.visitor_id;
      var dt = v.updated_at || v.last_chat || '';
      var dateStr = dt ? dt.slice(0,16).replace('T',' ') : '-';
      var card = document.createElement('div');
      card.style.cssText = 'background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:14px;';
      card.innerHTML =
        '<div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📲</div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;">' + name + '</div>' +
          '<div style="font-size:13px;color:#a78bfa;font-weight:600;">' + (v.phone || '') + '</div>' +
          '<div style="font-size:11px;color:#6b7280;margin-top:2px;">' + (v.chatbot_name || '') + ' · ' + dateStr + '</div>' +
        '</div>' +
        '<div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">' +
          '<button onclick="pvaOpenChat(\'' + v.visitor_id + '\',\'' + v.sms_idx + '\')" style="padding:7px 12px;background:#2563eb;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer;white-space:nowrap;">대화보기</button>' +
        '</div>';
      if (list) list.appendChild(card);
    });
    pvaHasMore = !!data.has_more;
    if (moreBtn) moreBtn.style.display = pvaHasMore ? 'block' : 'none';
  }

  window.loadPvaMore = function() { pvaPage++; loadPvaList(false); };
  window.pvaOpenChat = function(visitorId, smsIdx, callerTab, displayName) {
    window.closePvaVisitorPanel();
    // callerTab === 'received': 수신탭에서 호출 → 탭 전환 없이 대화창만 열기
    if (callerTab === 'received') {
      if (typeof openChatScreenFromAPI === 'function') {
        openChatScreenFromAPI({
          source: 'visitor',
          visitor_id: visitorId,
          sms_idx: parseInt(smsIdx, 10),
          id: 0,
          name: displayName || visitorId,
          chatbot_name: '',
          chatCount: 0,
          unreadCount: 0,
          lastChat: null,
          callerTab: 'received',   // closeChat 시 수신탭 복귀용
        });
      }
    } else {
      // PWA 패널 등 다른 곳에서 호출 → 발신탭 공유뷰로 이동
      switchTab('contacts');
      if (typeof statsBarSwitchToReceived === 'function') statsBarSwitchToReceived();
      setTimeout(async function() {
        if (typeof openChatScreenFromAPI === 'function') {
          openChatScreenFromAPI({
            source: 'visitor',
            visitor_id: visitorId,
            sms_idx: parseInt(smsIdx, 10),
            id: 0,
            name: displayName || visitorId,
            chatbot_name: '',
            chatCount: 0,
            unreadCount: 0,
            lastChat: null,
          });
        }
      }, 400);
    }
  };

}); // end DOMContentLoaded (BC + PWA)

// =====================================================
// 동기화 상태 / 챗봇 설정 패널
// =====================================================
document.addEventListener('DOMContentLoaded', function () {

  // ── 동기화 아이콘 (프로필 우측) → 클릭 시 학습 화면 열기 ──────
  var adpSyncWrap = document.getElementById('adpSyncWrap');
  if (adpSyncWrap) {
    adpSyncWrap.addEventListener('click', function (e) {
      e.stopPropagation();
      var dd = document.getElementById('avatarDropdown');
      if (dd) dd.classList.remove('open');
      if (typeof openLearnScreen === 'function') openLearnScreen();
    });
  }
  // 동기화 상태 아이콘 업데이트 헬퍼
  function updateSyncIcon(state, label) {
    var wrap = document.getElementById('adpSyncWrap');
    var icon = document.getElementById('adpSyncIcon');
    var lbl  = document.getElementById('adpSyncLabel');
    if (!wrap) return;
    wrap.classList.remove('syncing', 'error');
    if (state === 'syncing') { wrap.classList.add('syncing'); }
    else if (state === 'error') { wrap.classList.add('error'); }
    if (lbl) lbl.textContent = label || (state === 'error' ? '오류' : '동기화됨');
  }
  window.updateSyncIcon = updateSyncIcon;

  // ── 챗봇 설정 패널 ────────────────────────────────────
  var openChatbotSettingBtn = document.getElementById('openChatbotSettingBtn');
  var cbSettingPanel        = document.getElementById('cbSettingPanel');
  var cbSettingOverlay      = document.getElementById('cbSettingOverlay');
  var cbSettingCloseBtn     = document.getElementById('cbSettingCloseBtn');
  var cbSettingSaveBtn      = document.getElementById('cbSettingSaveBtn');
  var cbSettingBody         = document.getElementById('cbSettingBody');
  var cbSettingBotName      = document.getElementById('cbSettingBotName');

  if (!cbSettingPanel) return;

  var cbCurrentSmsIdx = 0;

  function openCbSettingPanel() {
    var dd = document.getElementById('avatarDropdown');
    if (dd) dd.classList.remove('open');
    cbSettingPanel.classList.add('open');
    if (cbSettingOverlay) cbSettingOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    cbSettingBody.innerHTML = '<div class="cbsetting-loading"><i class="fas fa-spinner fa-spin"></i> 설정을 불러오는 중...</div>';

    fetch('./api/chatbot_setting.php', { credentials: 'include' })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.success || !data.bots || data.bots.length === 0) {
          cbSettingBody.innerHTML = '<div class="cbsetting-empty"><i class="fas fa-robot"></i><p>등록된 챗봇이 없습니다.</p></div>';
          return;
        }
        var bots = data.bots;
        loadCbSetting(bots[0].sms_idx);
      })
      .catch(function() {
        cbSettingBody.innerHTML = '<div class="cbsetting-empty"><i class="fas fa-exclamation-triangle"></i><p>설정을 불러오지 못했습니다.</p></div>';
      });
  }

  function loadCbSetting(smsIdx) {
    cbCurrentSmsIdx = smsIdx;
    cbSettingBody.innerHTML = '<div class="cbsetting-loading"><i class="fas fa-spinner fa-spin"></i> 불러오는 중...</div>';

    fetch('./api/chatbot_setting.php?sms_idx=' + smsIdx, { credentials: 'include' })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.success) {
          cbSettingBody.innerHTML = '<div class="cbsetting-empty"><p>설정을 불러오지 못했습니다.</p></div>';
          return;
        }
        var s = data.setting;
        if (cbSettingBotName) cbSettingBotName.textContent = s.chatbot_name || '챗봇 설정';
        renderCbSettingForm(s);
      })
      .catch(function() {
        cbSettingBody.innerHTML = '<div class="cbsetting-empty"><p>오류가 발생했습니다.</p></div>';
      });
  }

  function escCbHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function renderCbSettingForm(s) {
    cbSettingBody.innerHTML =
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-robot"></i> 챗봇 이름</div>' +
        '<input type="text" class="cbsetting-input" id="cbInputName" value="' + escCbHtml(s.chatbot_name) + '" placeholder="챗봇 이름 입력" />' +
      '</div>' +
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-cogs"></i> 시스템 프롬프트 <span class="cbsetting-section-badge">핵심 지시</span></div>' +
        '<div class="cbsetting-section-desc">챗봇의 역할, 말투, 금지 표현, 전문 분야 등을 설정합니다.</div>' +
        '<textarea class="cbsetting-textarea" id="cbInputSysPrompt" rows="6" placeholder="예) 너는 친근하고 따뜻한 말투로 대화하는 AI야...">' + escCbHtml(s.gpt_sysprompt) + '</textarea>' +
        '<div class="cbsetting-char-count" id="cbSysPromptCount">' + (s.gpt_sysprompt||'').length + '자</div>' +
      '</div>' +
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-paint-brush"></i> 응답 스타일 <span class="cbsetting-section-badge">톤/스타일</span></div>' +
        '<div class="cbsetting-section-desc">메시지의 분위기와 표현 방식을 정의합니다.</div>' +
        '<textarea class="cbsetting-textarea" id="cbInputMsgStyle" rows="4" placeholder="예) 따뜻하고 친근하게, 이모지 적절히 사용...">' + escCbHtml(s.message_style) + '</textarea>' +
        '<div class="cbsetting-char-count" id="cbMsgStyleCount">' + (s.message_style||'').length + '자</div>' +
      '</div>' +
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-user-edit"></i> 개인 추가 지시 <span class="cbsetting-section-badge optional">선택</span></div>' +
        '<div class="cbsetting-section-desc">시스템 프롬프트에 덧붙일 개인화된 추가 지시사항입니다.</div>' +
        '<textarea class="cbsetting-textarea" id="cbInputUserSysPrompt" rows="4" placeholder="예) 내 이름은 김철수이고 경기도 성남시 시장 후보입니다...">' + escCbHtml(s.user_gpt_sysprompt) + '</textarea>' +
        '<div class="cbsetting-char-count" id="cbUserSysPromptCount">' + (s.user_gpt_sysprompt||'').length + '자</div>' +
      '</div>' +
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-ruler-horizontal"></i> 답변 길이 제한 <span class="cbsetting-section-badge">토큰</span></div>' +
        '<div class="cbsetting-section-desc">AI가 한 번에 생성하는 답변의 최대 길이입니다. 짧을수록 간결하고 빠릅니다.</div>' +
        '<div class="cbsetting-token-btns" id="cbTokenBtns">' +
          '<button type="button" class="cbsetting-token-btn' + ((!s.max_tokens || s.max_tokens <= 150) ? ' active' : '') + '" data-val="100">짧게 <span>~2문장</span></button>' +
          '<button type="button" class="cbsetting-token-btn' + ((s.max_tokens > 150 && s.max_tokens <= 450) ? ' active' : '') + '" data-val="300">보통 <span>~4문장</span></button>' +
          '<button type="button" class="cbsetting-token-btn' + ((s.max_tokens > 450) ? ' active' : '') + '" data-val="600">길게 <span>상세 설명</span></button>' +
        '</div>' +
        '<div style="margin-top:10px;display:flex;align-items:center;gap:10px;">' +
          '<input type="range" id="cbTokenSlider" min="100" max="800" step="50" value="' + (s.max_tokens || 400) + '" style="flex:1;">' +
          '<span class="cbsetting-token-val" id="cbTokenVal">' + (s.max_tokens || 400) + '</span>' +
        '</div>' +
        '<div class="cbsetting-section-desc" style="margin-top:6px;">💡 현재 scosh 계정 기본값: 400 · 범위: 100 ~ 800</div>' +
      '</div>' +
      '<div class="cbsetting-section">' +
        '<div class="cbsetting-section-title"><i class="fas fa-brain"></i> LLM 기본지식 활용 <span class="cbsetting-section-badge">AI</span></div>' +
        '<div class="cbsetting-section-desc">학습 데이터에 없는 질문을 받았을 때 AI 기본 지식 활용 방식을 설정합니다. 각 모드별로 별도 지침을 입력할 수 있습니다.</div>' +
        '<div class="cbsetting-token-btns" id="cbLlmModeBtns">' +
          '<button type="button" class="cbsetting-token-btn' + ((!s.llm_knowledge_mode || s.llm_knowledge_mode === 'off') ? ' active' : '') + '" data-val="off">사용 안함</button>' +
          '<button type="button" class="cbsetting-token-btn' + ((s.llm_knowledge_mode === 'supplement') ? ' active' : '') + '" data-val="supplement">보완 <span>학습↑ LLM↓</span></button>' +
          '<button type="button" class="cbsetting-token-btn' + ((s.llm_knowledge_mode === 'balance') ? ' active' : '') + '" data-val="balance">균형 <span>학습=LLM</span></button>' +
          '<button type="button" class="cbsetting-token-btn' + ((s.llm_knowledge_mode === 'free') ? ' active' : '') + '" data-val="free">자유 <span>LLM↑↑</span></button>' +
        '</div>' +
        '<div id="cbLlmSupplementWrap" style="' + ((s.llm_knowledge_mode === 'supplement') ? '' : 'display:none;') + 'margin-top:10px;">' +
          '<label style="font-size:12px;color:#555;font-weight:600;">보완 모드 지침 <span style="color:#999;font-weight:400">(학습데이터 우선, 없을 때 LLM 보충)</span></label>' +
          '<textarea class="cbsetting-textarea" id="cbInputLlmSupplement" rows="5" placeholder="예) 한의학 병리 설명 → 현대의학 근거 → 청담한의원 치료 연결 → 내원 유도 순서로 답변하라.">' + escCbHtml(s.llm_prompt_supplement || '') + '</textarea>' +
          '<div class="cbsetting-char-count" id="cbLlmSupplementCount">' + (s.llm_prompt_supplement||'').length + '자</div>' +
        '</div>' +
        '<div id="cbLlmBalanceWrap" style="' + ((s.llm_knowledge_mode === 'balance') ? '' : 'display:none;') + 'margin-top:10px;">' +
          '<label style="font-size:12px;color:#555;font-weight:600;">균형 모드 지침 <span style="color:#999;font-weight:400">(학습데이터+LLM 동등 활용)</span></label>' +
          '<textarea class="cbsetting-textarea" id="cbInputLlmBalance" rows="5" placeholder="예) 모든 답변에 의학 심층 정보 + 청담한의원 연결 + 행동 유도의 3층 구조로 답변하라.">' + escCbHtml(s.llm_prompt_balance || '') + '</textarea>' +
          '<div class="cbsetting-char-count" id="cbLlmBalanceCount">' + (s.llm_prompt_balance||'').length + '자</div>' +
        '</div>' +
        '<div id="cbLlmFreeWrap" style="' + ((s.llm_knowledge_mode === 'free') ? '' : 'display:none;') + 'margin-top:10px;">' +
          '<label style="font-size:12px;color:#555;font-weight:600;">자유 모드 지침 <span style="color:#999;font-weight:400">(LLM 지식 자유 활용)</span></label>' +
          '<textarea class="cbsetting-textarea" id="cbInputLlmFree" rows="5" placeholder="예) LLM의 방대한 지식을 자유롭게 활용하여 풍부하고 상세한 답변을 제공하라.">' + escCbHtml(s.llm_prompt_free || '') + '</textarea>' +
          '<div class="cbsetting-char-count" id="cbLlmFreeCount">' + (s.llm_prompt_free||'').length + '자</div>' +
        '</div>' +
      '</div>' +
      '<div style="height:80px;"></div>';

    // 답변 길이 슬라이더 + 빠른 선택 버튼 이벤트
    (function() {
      var slider = document.getElementById('cbTokenSlider');
      var valEl  = document.getElementById('cbTokenVal');
      var btns   = document.querySelectorAll('.cbsetting-token-btn');
      if (!slider || !valEl) return;

      slider.addEventListener('input', function() {
        valEl.textContent = this.value;
        btns.forEach(function(b) { b.classList.remove('active'); });
      });
      btns.forEach(function(btn) {
        btn.addEventListener('click', function() {
          var v = parseInt(this.dataset.val);
          slider.value = v;
          valEl.textContent = v;
          btns.forEach(function(b) { b.classList.remove('active'); });
          this.classList.add('active');
        }.bind(btn));
      });
    })();

    // LLM 모드 버튼 이벤트
    (function() {
      var llmBtns = document.querySelectorAll('#cbLlmModeBtns .cbsetting-token-btn');
      var wrapMap = { supplement: 'cbLlmSupplementWrap', balance: 'cbLlmBalanceWrap', free: 'cbLlmFreeWrap' };
      llmBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
          llmBtns.forEach(function(b) { b.classList.remove('active'); });
          this.classList.add('active');
          Object.values(wrapMap).forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
          });
          var activeWrap = wrapMap[this.dataset.val];
          if (activeWrap) {
            var el = document.getElementById(activeWrap);
            if (el) el.style.display = '';
          }
        }.bind(btn));
      });
    })();

    ['cbInputSysPrompt','cbInputMsgStyle','cbInputUserSysPrompt','cbInputLlmSupplement','cbInputLlmBalance','cbInputLlmFree'].forEach(function(id) {
      var el = document.getElementById(id);
      var cntId = { cbInputSysPrompt:'cbSysPromptCount', cbInputMsgStyle:'cbMsgStyleCount', cbInputUserSysPrompt:'cbUserSysPromptCount', cbInputLlmSupplement:'cbLlmSupplementCount', cbInputLlmBalance:'cbLlmBalanceCount', cbInputLlmFree:'cbLlmFreeCount' }[id];
      if (el && cntId) el.addEventListener('input', function() {
        document.getElementById(cntId).textContent = this.value.length + '자';
      });
    });
  }

  function saveCbSetting() {
    if (!cbCurrentSmsIdx) return;
    var nameEl    = document.getElementById('cbInputName');
    var sysEl     = document.getElementById('cbInputSysPrompt');
    var styleEl   = document.getElementById('cbInputMsgStyle');
    var userSysEl = document.getElementById('cbInputUserSysPrompt');
    if (!nameEl || !sysEl) return;

    var payload = {
      sms_idx:            cbCurrentSmsIdx,
      chatbot_name:       nameEl.value.trim(),
      gptmodel:           'deepseek-chat',
      gpt_sysprompt:      sysEl.value.trim(),
      message_style:      styleEl ? styleEl.value.trim() : '',
      user_gpt_sysprompt: userSysEl ? userSysEl.value.trim() : '',
      max_tokens: (function() { var s = document.getElementById('cbTokenSlider'); return s ? parseInt(s.value) : null; })(),
      llm_knowledge_mode: (function() {
        var ab = document.querySelector('#cbLlmModeBtns .cbsetting-token-btn.active');
        return ab ? ab.dataset.val : 'supplement';
      })(),
      llm_prompt_supplement: (function() { var e = document.getElementById('cbInputLlmSupplement'); return e ? e.value.trim() : ''; })(),
      llm_prompt_balance:    (function() { var e = document.getElementById('cbInputLlmBalance');    return e ? e.value.trim() : ''; })(),
      llm_prompt_free:       (function() { var e = document.getElementById('cbInputLlmFree');       return e ? e.value.trim() : ''; })(),
    };

    if (cbSettingSaveBtn) {
      cbSettingSaveBtn.disabled = true;
      cbSettingSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 저장 중...';
    }

    fetch('./api/chatbot_setting.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (data.success) {
        if (cbSettingBotName) cbSettingBotName.textContent = payload.chatbot_name || '챗봇 설정';
        showCbToast('✅ 챗봇 설정이 저장되었습니다.');
      } else {
        showCbToast('❌ 저장 실패: ' + (data.error || '알 수 없는 오류'));
      }
    })
    .catch(function() { showCbToast('❌ 저장 중 오류가 발생했습니다.'); })
    .finally(function() {
      if (cbSettingSaveBtn) {
        cbSettingSaveBtn.disabled = false;
        cbSettingSaveBtn.innerHTML = '<i class="fas fa-check"></i> 저장';
      }
    });
  }

  function closeCbSettingPanel() {
    cbSettingPanel.classList.remove('open');
    if (cbSettingOverlay) cbSettingOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  function showCbToast(msg) {
    var toast = document.getElementById('cbSettingToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'cbSettingToast';
      toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;white-space:nowrap;max-width:90vw;text-align:center;';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function() { toast.style.opacity = '0'; }, 2500);
  }

  if (openChatbotSettingBtn) openChatbotSettingBtn.addEventListener('click', openCbSettingPanel);
  if (cbSettingCloseBtn)     cbSettingCloseBtn.addEventListener('click', closeCbSettingPanel);
  if (cbSettingOverlay)      cbSettingOverlay.addEventListener('click', closeCbSettingPanel);
  if (cbSettingSaveBtn)      cbSettingSaveBtn.addEventListener('click', saveCbSetting);

  var cbTouchStartX = 0;
  cbSettingPanel.addEventListener('touchstart', function(e) { cbTouchStartX = e.touches[0].clientX; }, { passive: true });
  cbSettingPanel.addEventListener('touchend', function(e) {
    if (e.changedTouches[0].clientX - cbTouchStartX > 80) closeCbSettingPanel();
  });

  // URL ?sms_idx=X → 관리자가 특정 챗봇 설정을 바로 열기
  (function() {
    var params = new URLSearchParams(window.location.search);
    var autoSmsIdx = parseInt(params.get('sms_idx') || '0', 10);
    if (!autoSmsIdx) return;
    setTimeout(function() {
      cbSettingPanel.classList.add('open');
      if (cbSettingOverlay) cbSettingOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
      cbSettingBody.innerHTML = '<div class="cbsetting-loading"><i class="fas fa-spinner fa-spin"></i> 설정을 불러오는 중...</div>';
      fetch('./api/chatbot_setting.php?sms_idx=' + autoSmsIdx, { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.success || !data.setting) {
            cbSettingBody.innerHTML = '<div class="cbsetting-empty"><p>챗봇 설정을 불러오지 못했습니다.</p></div>';
            return;
          }
          var s = data.setting;
          if (cbSettingBotName) {
            cbSettingBotName.textContent = s.chatbot_name || '챗봇 설정';
            if (s.owner_id) {
              var badge = document.createElement('span');
              badge.style.cssText = 'margin-left:6px;font-size:11px;color:#aaa;font-weight:normal;';
              badge.textContent = '(' + s.owner_id + ')';
              cbSettingBotName.appendChild(badge);
            }
          }
          cbCurrentSmsIdx = autoSmsIdx;
          renderCbSettingForm(s);
        })
        .catch(function() {
          cbSettingBody.innerHTML = '<div class="cbsetting-empty"><p>오류가 발생했습니다.</p></div>';
        });
    }, 500);
  })();

}); // end DOMContentLoaded (chatbot setting)


// =====================================================
// 사이드바 드로어 (햄버거 메뉴)
// =====================================================
document.addEventListener('DOMContentLoaded', function () {

  var menuBtn        = document.getElementById('menuBtn');
  var sidebarDrawer  = document.getElementById('sidebarDrawer');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var sidebarCloseBtn= document.getElementById('sidebarCloseBtn');

  if (!sidebarDrawer) return;

  // ── 열기/닫기 ─────────────────────────────────────
  function openSidebar() {
    sidebarDrawer.classList.add('open');
    sidebarOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    syncSidebarProfile();
    syncSidebarThemeState();
    syncSidebarSyncBadge();
  }
  function closeSidebar() {
    sidebarDrawer.classList.remove('open');
    sidebarOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (menuBtn)         menuBtn.addEventListener('click', openSidebar);
  if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
  if (sidebarOverlay)  sidebarOverlay.addEventListener('click', closeSidebar);

  // 스와이프로 닫기 (왼쪽으로)
  var sbTouchStartX = 0;
  sidebarDrawer.addEventListener('touchstart', function(e) {
    sbTouchStartX = e.touches[0].clientX;
  }, { passive: true });
  sidebarDrawer.addEventListener('touchend', function(e) {
    if (sbTouchStartX - e.changedTouches[0].clientX > 80) closeSidebar();
  });

  // ── 프로필 동기화 — applyProfileToHeader에 위임 ──
  function syncSidebarProfile() {
    applyProfileToHeader();
  }

  // ── 동기화 배지 상태 동기화 ───────────────────────
  function syncSidebarSyncBadge() {
    var adpWrap = document.getElementById('adpSyncWrap');
    var sbBadge = document.getElementById('sbSyncBadge');
    if (!sbBadge) return;
    if (adpWrap) {
      var isError   = adpWrap.classList.contains('error');
      var isSyncing = adpWrap.classList.contains('syncing');
      var lbl = document.getElementById('adpSyncLabel');
      var labelText = lbl ? lbl.textContent : '동기화됨';
      sbBadge.classList.remove('error', 'syncing');
      if (isError)   { sbBadge.classList.add('error');   sbBadge.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>' + labelText + '</span>'; }
      else if (isSyncing) { sbBadge.classList.add('syncing'); sbBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>동기화 중</span>'; }
      else { sbBadge.innerHTML = '<i class="fas fa-check-circle"></i><span>' + labelText + '</span>'; }
    }
  }

  // ── 테마 토글 상태 동기화 ─────────────────────────
  function syncSidebarThemeState() {
    var isLight  = document.body.classList.contains('theme-light');
    var swt      = document.getElementById('sbThemeSwitch');
    var lbl      = document.getElementById('sbThemeLabel');
    if (swt) { isLight ? swt.classList.add('light-on') : swt.classList.remove('light-on'); }
    if (lbl) lbl.textContent = isLight ? '라이트' : '다크';
  }

  // 테마 토글 클릭
  var sbThemeBtn = document.getElementById('sbThemeBtn');
  if (sbThemeBtn) {
    sbThemeBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      var isLight = document.body.classList.contains('theme-light');
      if (typeof applyTheme === 'function') {
        applyTheme(isLight ? 'dark' : 'light');
      }
      syncSidebarThemeState();
    });
  }

  // ── 알림음 설정 패널 (2026-05-15) ─────────────────
  (function() {
    var rtPanel   = document.getElementById('ringtonePanel');
    var rtOverlay = document.getElementById('ringtoneOverlay');
    var rtClose   = document.getElementById('ringtoneCloseBtn');
    var rtBody    = document.getElementById('ringtoneBody');
    var rtBtn     = document.getElementById('sbRingtoneBtn');
    if (!rtPanel || !rtBtn) return;

    var _previewingId = null;

    function openRingtonePanel() {
      closeSidebar();
      rtPanel.classList.add('open');
      rtOverlay.classList.add('open');
      loadRingtoneList();
    }
    function closeRingtonePanel() {
      rtPanel.classList.remove('open');
      rtOverlay.classList.remove('open');
      if (typeof window.stopGlobalRingtonePreview === 'function') window.stopGlobalRingtonePreview();
      _previewingId = null;
    }

    rtBtn.addEventListener('click', openRingtonePanel);
    rtClose.addEventListener('click', closeRingtonePanel);
    rtOverlay.addEventListener('click', closeRingtonePanel);

    function getCurrentId() {
      try { return localStorage.getItem('onechat_ringtone_id') || 'chime'; } catch(e) { return 'chime'; }
    }
    function saveId(id) {
      try { localStorage.setItem('onechat_ringtone_id', id); } catch(e) {}
    }

    function renderItem(id, name, desc, isVoice, currentId) {
      var sel  = (id === currentId) ? ' selected' : '';
      var vcls = isVoice ? ' voice-type' : '';
      var icon = isVoice ? 'fa-microphone' : 'fa-music';
      var badge = isVoice ? '<span class="ringtone-voice-badge">3회·5초</span>' : '';
      return '<div class="ringtone-item' + sel + vcls + '" data-id="' + id + '">' +
        '<div class="ringtone-item-icon"><i class="fas ' + icon + '"></i></div>' +
        '<div class="ringtone-item-info">' +
          '<div class="ringtone-item-name">' + escHtml(name) + badge + '</div>' +
          '<div class="ringtone-item-desc">' + escHtml(desc) + '</div>' +
        '</div>' +
        '<button class="ringtone-preview-btn" data-preview="' + id + '" title="미리듣기"><i class="fas fa-play"></i></button>' +
      '</div>';
    }

    function loadRingtoneList() {
      rtBody.innerHTML = '<div class="ringtone-loading"><i class="fas fa-spinner fa-spin"></i> 불러오는 중...</div>';
      var currentId = getCurrentId();

      // call_listener_global.js 로드 타이밍 보장 (최대 1초 대기)
      var builtins = window.ONECHAT_RINGTONES || [];
      if (!builtins.length) {
        setTimeout(function() {
          builtins = window.ONECHAT_RINGTONES || [];
          _renderRingtoneBody(builtins, currentId);
        }, 600);
        return;
      }
      _renderRingtoneBody(builtins, currentId);
    }

    function _renderRingtoneBody(builtins, currentId) {
      var html = '<div class="ringtone-section-title"><i class="fas fa-music"></i> 내장 벨소리</div>';
      builtins.forEach(function(rt) {
        html += renderItem(rt.id, rt.name, rt.desc || '', false, currentId);
      });

      fetch('./api/sounds_list.php', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success && d.sounds && d.sounds.length > 0) {
            html += '<div class="ringtone-section-title" style="margin-top:20px"><i class="fas fa-microphone"></i> 음성 알림음 <span style="font-size:10px;font-weight:400;text-transform:none">(3회 · 5초 간격)</span></div>';
            d.sounds.forEach(function(s) {
              html += renderItem(s.id, s.name, s.url.split('/').pop(), true, currentId);
            });
          }
          rtBody.innerHTML = html;
          attachRingtoneEvents();
        })
        .catch(function() {
          rtBody.innerHTML = html;
          attachRingtoneEvents();
        });
    }

    function attachRingtoneEvents() {
      // 항목 클릭 → 선택
      rtBody.querySelectorAll('.ringtone-item').forEach(function(el) {
        el.addEventListener('click', function(e) {
          if (e.target.closest('.ringtone-preview-btn')) return;
          var id = el.dataset.id;
          saveId(id);
          rtBody.querySelectorAll('.ringtone-item').forEach(function(x) { x.classList.remove('selected'); });
          el.classList.add('selected');
        });
      });
      // 미리듣기 버튼
      rtBody.querySelectorAll('.ringtone-preview-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          var id = btn.dataset.preview;
          // 현재 재생 중이면 중단
          if (_previewingId) {
            if (typeof window.stopGlobalRingtonePreview === 'function') window.stopGlobalRingtonePreview();
            rtBody.querySelectorAll('.ringtone-preview-btn').forEach(function(b) {
              b.classList.remove('playing');
              b.innerHTML = '<i class="fas fa-play"></i>';
            });
            if (_previewingId === id) { _previewingId = null; return; }
          }
          _previewingId = id;
          btn.classList.add('playing');
          btn.innerHTML = '<i class="fas fa-stop"></i>';
          if (typeof window.previewGlobalRingtone === 'function') {
            window.previewGlobalRingtone(id);
          }
          // 10초 후 자동 복원
          setTimeout(function() {
            btn.classList.remove('playing');
            btn.innerHTML = '<i class="fas fa-play"></i>';
            if (_previewingId === id) _previewingId = null;
          }, 10000);
        });
      });
    }
  })();

  // ── 메뉴 1: 대시보드 ──────────────────────────────
  var sbDashboardBtn = document.getElementById('sbDashboardBtn');
  if (sbDashboardBtn) {
    sbDashboardBtn.addEventListener('click', function() {
      closeSidebar();
      // PC 전체화면 새 탭으로 열기
      window.open('/aimessage/onechat/dashboard.html', '_blank');
    });
  }

  // ── 메뉴 4: 공지 방송 발송 ───────────────────────
  var sbBroadcastBtn = document.getElementById('sbBroadcastBtn');
  if (sbBroadcastBtn) {
    sbBroadcastBtn.addEventListener('click', function() {
      closeSidebar();
      history.pushState({ menu: 'broadcast' }, '', '#broadcast');
      var bcBtn = document.getElementById('openBcMenuBtn');
      if (bcBtn) bcBtn.click();
    });
  }

  // ── 메뉴 7: 구독하기 ──────────────────────────────
  var sbSubscribeBtn = document.getElementById('sbSubscribeBtn');
  if (sbSubscribeBtn) {
    sbSubscribeBtn.addEventListener('click', function() {
      closeSidebar();
      window.open('/aimessage/onechat/subscribe.html', '_blank');
    });
  }

  // ── 토스트 헬퍼 ──────────────────────────────────
  function showSbToast(msg) {
    var t = document.getElementById('sbToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'sbToast';
      t.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;white-space:nowrap;max-width:90vw;text-align:center;';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(function() { t.style.opacity = '0'; }, 2500);
  }
  window.openSidebar = openSidebar;
  window.closeSidebar = closeSidebar;

}); // end DOMContentLoaded (sidebar)


// =====================================================
// AI 동행 (Companion) 설정 패널
// =====================================================
document.addEventListener('DOMContentLoaded', function () {

  var sbCompanionBtn   = document.getElementById('sbCompanionBtn');
  var companionPanel   = document.getElementById('companionPanel');
  var companionOverlay = document.getElementById('companionOverlay');
  var companionCloseBtn= document.getElementById('companionCloseBtn');
  var companionSaveBtn = document.getElementById('companionSaveBtn');
  var companionBody    = document.getElementById('companionPanelBody');

  if (!companionPanel) return;

  var cpConfig    = {};   // 현재 로드된 설정
  var cpKeywords  = [];   // 태그 배열
  var cpSmsIdx    = 0;

  // ── 열기 ─────────────────────────────────────────
  function openCompanionPanel() {
    if (typeof closeSidebar === 'function') closeSidebar();
    companionPanel.classList.add('open');
    companionOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    history.pushState({ menu: 'companion' }, '', '#companion');
    loadCompanionConfig();
  }

  // ── 닫기 ─────────────────────────────────────────
  function closeCompanionPanel() {
    companionPanel.classList.remove('open');
    companionOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (sbCompanionBtn)    sbCompanionBtn.addEventListener('click', openCompanionPanel);
  if (companionCloseBtn) companionCloseBtn.addEventListener('click', closeCompanionPanel);
  if (companionOverlay)  companionOverlay.addEventListener('click', closeCompanionPanel);
  if (companionSaveBtn)  companionSaveBtn.addEventListener('click', saveCompanionConfig);

  // 스와이프 닫기 (오른쪽→왼쪽)
  var cpTouchX = 0;
  companionPanel.addEventListener('touchstart', function(e){ cpTouchX = e.touches[0].clientX; }, { passive: true });
  companionPanel.addEventListener('touchend',   function(e){ if (e.changedTouches[0].clientX - cpTouchX > 80) closeCompanionPanel(); });

  // ── 설정 로드 ─────────────────────────────────────
  function loadCompanionConfig() {
    companionBody.innerHTML = '<div class="companion-loading"><i class="fas fa-spinner fa-spin"></i> 설정을 불러오는 중...</div>';
    fetch('./api/companion/config.php', { credentials: 'include' })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.success) throw new Error(data.error || '로드 실패');
        cpConfig  = data.config;
        cpSmsIdx  = data.sms_idx;
        try { cpKeywords = JSON.parse(cpConfig.topic_keywords || '[]'); } catch(e){ cpKeywords = []; }
        renderCompanionForm(data);
        syncSidebarCompanionStatus(cpConfig.is_enabled);
      })
      .catch(function(err) {
        companionBody.innerHTML = '<div class="companion-loading" style="color:#ef4444;"><i class="fas fa-exclamation-triangle"></i> ' + err.message + '</div>';
      });
  }

  // ── 폼 렌더링 ─────────────────────────────────────
  function renderCompanionForm(data) {
    var c    = data.config;
    var st   = data.stats || {};
    var bots = data.bots  || [];

    // 챗봇 선택기 (2개 이상일 때)
    var botSelectorHtml = '';
    if (bots.length > 1) {
      botSelectorHtml = '<div class="cp-section"><div class="cp-section-title"><i class="fas fa-robot"></i> 챗봇 선택</div>'
        + '<select class="cp-time-select" id="cpBotSelect">'
        + bots.map(function(b){ return '<option value="' + b.sms_idx + '"' + (b.sms_idx === cpSmsIdx ? ' selected' : '') + '>' + escCp(b.name) + '</option>'; }).join('')
        + '</select></div>';
    }

    var html = botSelectorHtml +

    // ① ON/OFF
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-power-off"></i> AI 동행 기능</div>' +
      '<div class="cp-toggle-row">' +
        '<div class="cp-toggle-info">' +
          '<span class="cp-toggle-label">AI 동행 활성화</span>' +
          '<span class="cp-toggle-desc">대화 종료 후 AI가 자동으로 재접촉합니다</span>' +
        '</div>' +
        '<div class="cp-switch' + (c.is_enabled ? ' on' : '') + '" id="cpEnableSwitch">' +
          '<div class="cp-switch-knob"></div>' +
        '</div>' +
      '</div>' +
    '</div>' +

    // ② 통계
    '<div class="cp-section">' +
      '<div class="cp-section-title" style="display:flex;align-items:center;justify-content:space-between;">' +
        '<span><i class="fas fa-chart-bar"></i> 동행 현황</span>' +
        '<button class="cp-stats-detail-btn" id="cpStatsDetailBtn" type="button" style="font-size:11px;padding:3px 8px;border-radius:10px;border:1px solid var(--border-color);background:var(--bg-secondary);color:var(--text-muted);cursor:pointer;">상세보기</button>' +
      '</div>' +
      '<div class="cp-stats-grid">' +
        '<div class="cp-stat-card"><div class="cp-stat-value">' + (st.total_sent || 0) + '</div><div class="cp-stat-label">발송 횟수</div></div>' +
        '<div class="cp-stat-card"><div class="cp-stat-value">' + (st.total_replied || 0) + '</div><div class="cp-stat-label">유저 응답</div></div>' +
        '<div class="cp-stat-card"><div class="cp-stat-value">' + (st.reply_rate || 0) + '%</div><div class="cp-stat-label">응답률</div></div>' +
        '<div class="cp-stat-card"><div class="cp-stat-value">' + (st.pending_count || 0) + '</div><div class="cp-stat-label">예약 대기</div></div>' +
      '</div>' +
      '<div id="cpStatsDetail" style="display:none;margin-top:10px;">' +
        '<div id="cpStatsDetailInner" style="font-size:12px;color:var(--text-muted);">로딩 중...</div>' +
      '</div>' +
    '</div>' +

    // ③ 재접촉 간격
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-clock"></i> 재접촉 간격</div>' +
      '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">대화 종료 후 AI가 다시 연락하는 랜덤 간격</div>' +
      '<div class="cp-range-row"><span class="cp-range-label">최소</span>' +
        '<input type="range" class="cp-range-input" id="cpMinGap" min="1" max="14" value="' + (c.min_gap_days||2) + '">' +
        '<span class="cp-range-value" id="cpMinGapVal">' + (c.min_gap_days||2) + '일</span>' +
      '</div>' +
      '<div class="cp-range-row"><span class="cp-range-label">최대</span>' +
        '<input type="range" class="cp-range-input" id="cpMaxGap" min="1" max="21" value="' + (c.max_gap_days||5) + '">' +
        '<span class="cp-range-value" id="cpMaxGapVal">' + (c.max_gap_days||5) + '일</span>' +
      '</div>' +
    '</div>' +

    // ④ 발송 가능 시간
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-moon"></i> 발송 가능 시간</div>' +
      '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">이 시간대에만 AI 메시지가 발송됩니다</div>' +
      '<div class="cp-time-row">' +
        '<select class="cp-time-select" id="cpSendStart">' + _timeOptions(c.send_hour_start||9) + '</select>' +
        '<span class="cp-time-sep">시 ~</span>' +
        '<select class="cp-time-select" id="cpSendEnd">' + _timeOptions(c.send_hour_end||21) + '</select>' +
        '<span class="cp-time-sep">시</span>' +
      '</div>' +
    '</div>' +

    // ⑤ AI 역할 프롬프트
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-robot"></i> AI 동행 역할 설정</div>' +
      '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">AI가 어떤 친구가 되어야 하는지 설명해주세요</div>' +
      '<textarea class="cp-textarea" id="cpRolePrompt" rows="4" placeholder="예) 당신은 건강 전문가이자 따뜻한 친구입니다. 유저의 건강 목표 달성을 응원하는 든든한 동반자입니다.">' + escCp(c.companion_prompt||'') + '</textarea>' +
      '<div class="cp-char-count" id="cpRolePromptCount">' + (c.companion_prompt||'').length + '자</div>' +
    '</div>' +

    // ⑥ 주제 키워드 태그
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-tags"></i> 챗봇 주제 키워드</div>' +
      '<div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">AI가 이 주제를 기반으로 정보를 찾아드립니다</div>' +
      '<div class="cp-tags-wrap" id="cpTagsWrap"></div>' +
    '</div>' +

    // ⑦ 메시지 유형
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-comment-dots"></i> 메시지 유형</div>' +
      '<div class="cp-type-grid">' +
        _typeItem('type_greeting',   c.type_greeting,   'fas fa-hand-wave',     '안부형',     'linear-gradient(135deg,#3b82f6,#1d4ed8)', '😊 지난 대화 연결') +
        _typeItem('type_info',       c.type_info,       'fas fa-lightbulb',     '정보형',     'linear-gradient(135deg,#f59e0b,#d97706)', '💡 유용한 정보') +
        _typeItem('type_news',       c.type_news,       'fas fa-newspaper',     '뉴스형',     'linear-gradient(135deg,#06b6d4,#0284c7)', '📰 최신 소식') +
        _typeItem('type_motivation', c.type_motivation, 'fas fa-fire',          '응원형',     'linear-gradient(135deg,#10b981,#059669)', '💪 응원 메시지') +
      '</div>' +
    '</div>' +

    // ⑧ 테스트 발송
    '<div class="cp-section">' +
      '<div class="cp-section-title"><i class="fas fa-paper-plane"></i> 테스트 발송</div>' +
      '<div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">저장 후 AI 동행 메시지를 최근 유저에게 즉시 테스트 발송합니다</div>' +
      '<button class="companion-test-btn" id="cpTestBtn" type="button">' +
        '<i class="fas fa-vial"></i> 지금 테스트 발송' +
      '</button>' +
    '</div>' +

    '<div class="cp-bottom-spacer"></div>';

    companionBody.innerHTML = html;
    _bindCompanionEvents();
    _renderTags();
  }

  // ── 이벤트 바인딩 ──────────────────────────────────
  function _bindCompanionEvents() {
    // ON/OFF 스위치
    var sw = document.getElementById('cpEnableSwitch');
    if (sw) sw.addEventListener('click', function(){
      this.classList.toggle('on');
    });

    // 슬라이더 값 표시
    var minGap = document.getElementById('cpMinGap');
    var maxGap = document.getElementById('cpMaxGap');
    if (minGap) minGap.addEventListener('input', function(){
      document.getElementById('cpMinGapVal').textContent = this.value + '일';
    });
    if (maxGap) maxGap.addEventListener('input', function(){
      document.getElementById('cpMaxGapVal').textContent = this.value + '일';
    });

    // 프롬프트 글자수
    var rp = document.getElementById('cpRolePrompt');
    if (rp) rp.addEventListener('input', function(){
      document.getElementById('cpRolePromptCount').textContent = this.value.length + '자';
    });

    // 메시지 유형 토글
    document.querySelectorAll('.cp-type-item').forEach(function(item){
      item.addEventListener('click', function(){
        this.classList.toggle('active');
        var chk = this.querySelector('.cp-type-check');
        if (chk) chk.innerHTML = this.classList.contains('active') ? '<i class="fas fa-check"></i>' : '';
      });
    });

    // 통계 상세보기 버튼
    var statsDetailBtn = document.getElementById('cpStatsDetailBtn');
    if (statsDetailBtn) statsDetailBtn.addEventListener('click', function(){
      var detail = document.getElementById('cpStatsDetail');
      var inner  = document.getElementById('cpStatsDetailInner');
      if (!detail) return;
      if (detail.style.display === 'none') {
        detail.style.display = 'block';
        this.textContent = '접기';
        if (inner) inner.innerHTML = '<div style="text-align:center;padding:8px;"><i class="fas fa-spinner fa-spin"></i></div>';
        fetch('./api/companion/stats.php?sms_idx=' + cpSmsIdx + '&period=30', { credentials: 'include' })
          .then(function(r){ return r.json(); })
          .then(function(data) {
            if (!data.success) { inner.textContent = '통계 로드 실패'; return; }
            var logs = data.recent_logs || [];
            if (!logs.length) { inner.innerHTML = '<div style="color:var(--text-muted);text-align:center;padding:8px;">아직 발송 내역이 없습니다</div>'; return; }
            var typeLabel = { greeting:'안부형', info:'정보형', news:'뉴스형', motivation:'응원형' };
            var html = '<div style="font-size:11px;color:var(--text-muted);margin-bottom:6px;">최근 30일 발송 내역 (최대 10건)</div>';
            html += '<div style="display:flex;flex-direction:column;gap:6px;">';
            logs.forEach(function(log) {
              var replied = log.user_replied ? '✅ 응답' : '⏳ 미응답';
              var msgTypeLbl = typeLabel[log.msg_type] || log.msg_type;
              html += '<div style="background:var(--bg-secondary);border-radius:8px;padding:8px 10px;border-left:3px solid ' + (log.user_replied ? '#10b981' : '#94a3b8') + ';">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">' +
                  '<span style="font-weight:600;color:var(--text-primary);">' + escCp(log.visitor_name || '알 수 없음') + '</span>' +
                  '<span style="color:' + (log.user_replied ? '#10b981' : '#94a3b8') + ';font-size:10px;">' + replied + '</span>' +
                '</div>' +
                '<div style="color:var(--text-muted);font-size:11px;margin-bottom:2px;">[' + msgTypeLbl + '] ' + escCp(log.message || '') + (log.message && log.message.length >= 80 ? '…' : '') + '</div>' +
                '<div style="color:#94a3b8;font-size:10px;">' + (log.sent_at || '') + '</div>' +
              '</div>';
            });
            html += '</div>';
            inner.innerHTML = html;
          })
          .catch(function(){ inner.textContent = '로드 실패'; });
      } else {
        detail.style.display = 'none';
        this.textContent = '상세보기';
      }
    });

    // 테스트 발송 버튼
    var testBtn = document.getElementById('cpTestBtn');
    if (testBtn) testBtn.addEventListener('click', function(){
      testSendCompanion();
    });

    // 챗봇 선택기
    var botSel = document.getElementById('cpBotSelect');
    if (botSel) botSel.addEventListener('change', function(){
      cpSmsIdx = parseInt(this.value);
      loadCompanionConfig();
    });

    // 태그 입력
    var tagInput = document.querySelector('.cp-tag-input');
    if (tagInput) {
      tagInput.addEventListener('keydown', function(e){
        if ((e.key === 'Enter' || e.key === ',') && this.value.trim()) {
          e.preventDefault();
          _addTag(this.value.trim().replace(/,/g,''));
          this.value = '';
        }
      });
    }
  }

  // ── 태그 렌더링 ────────────────────────────────────
  function _renderTags() {
    var wrap = document.getElementById('cpTagsWrap');
    if (!wrap) return;
    wrap.innerHTML = cpKeywords.map(function(kw, i){
      return '<span class="cp-tag">' + escCp(kw) +
        '<button class="cp-tag-remove" data-idx="' + i + '" type="button">×</button></span>';
    }).join('') + '<input class="cp-tag-input" placeholder="키워드 입력 후 Enter" />';
    wrap.querySelectorAll('.cp-tag-remove').forEach(function(btn){
      btn.addEventListener('click', function(){
        cpKeywords.splice(parseInt(this.dataset.idx), 1);
        _renderTags();
      });
    });
    // 태그 입력 이벤트 재바인딩
    var inp = wrap.querySelector('.cp-tag-input');
    if (inp) inp.addEventListener('keydown', function(e){
      if ((e.key === 'Enter' || e.key === ',') && this.value.trim()) {
        e.preventDefault();
        _addTag(this.value.trim().replace(/,/g,''));
        this.value = '';
      }
    });
    if (inp) inp.addEventListener('blur', function(){
      if (this.value.trim()) { _addTag(this.value.trim()); this.value = ''; }
    });
  }
  function _addTag(kw) {
    if (kw && cpKeywords.indexOf(kw) === -1) { cpKeywords.push(kw); _renderTags(); }
  }

  // ── 테스트 발송 ───────────────────────────────────
  function testSendCompanion() {
    var btn = document.getElementById('cpTestBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 발송 중...'; }
    fetch('./api/companion/test.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sms_idx: cpSmsIdx }),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-vial"></i> 지금 테스트 발송'; }
      if (data.success) {
        showCpToast('✅ 테스트 발송 완료! [' + (data.details.msg_type || '') + '] ' + (data.details.message || '').substring(0, 30) + '…');
      } else {
        showCpToast('❌ 테스트 실패: ' + (data.error || '알 수 없음'));
      }
    })
    .catch(function(){
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-vial"></i> 지금 테스트 발송'; }
      showCpToast('❌ 네트워크 오류');
    });
  }

  // ── 설정 저장 ─────────────────────────────────────
  function saveCompanionConfig() {
    var swEl   = document.getElementById('cpEnableSwitch');
    var minEl  = document.getElementById('cpMinGap');
    var maxEl  = document.getElementById('cpMaxGap');
    var stEl   = document.getElementById('cpSendStart');
    var enEl   = document.getElementById('cpSendEnd');
    var rpEl   = document.getElementById('cpRolePrompt');

    var payload = {
      sms_idx:          cpSmsIdx,
      is_enabled:       swEl  ? swEl.classList.contains('on')  : false,
      min_gap_days:     minEl ? parseInt(minEl.value)          : 2,
      max_gap_days:     maxEl ? parseInt(maxEl.value)          : 5,
      send_hour_start:  stEl  ? parseInt(stEl.value)           : 9,
      send_hour_end:    enEl  ? parseInt(enEl.value)           : 21,
      companion_prompt: rpEl  ? rpEl.value.trim()              : '',
      topic_keywords:   JSON.stringify(cpKeywords),
      type_greeting:    !!document.querySelector('[data-type="type_greeting"].active'),
      type_info:        !!document.querySelector('[data-type="type_info"].active'),
      type_news:        !!document.querySelector('[data-type="type_news"].active'),
      type_motivation:  !!document.querySelector('[data-type="type_motivation"].active'),
      message_tone:     'friendly',
    };

    if (companionSaveBtn) {
      companionSaveBtn.disabled = true;
      companionSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 저장 중...';
    }

    fetch('./api/companion/config.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (companionSaveBtn) {
        companionSaveBtn.disabled = false;
        companionSaveBtn.innerHTML = '<i class="fas fa-check"></i> 저장';
      }
      if (data.success) {
        showCpToast('✅ AI 동행 설정이 저장되었습니다!');
        syncSidebarCompanionStatus(payload.is_enabled);
        setTimeout(closeCompanionPanel, 800);
      } else {
        showCpToast('❌ ' + (data.error || '저장 실패'));
      }
    })
    .catch(function(){
      if (companionSaveBtn) {
        companionSaveBtn.disabled = false;
        companionSaveBtn.innerHTML = '<i class="fas fa-check"></i> 저장';
      }
      showCpToast('❌ 네트워크 오류');
    });
  }

  // ── 사이드바 상태 동기화 ───────────────────────────
  function syncSidebarCompanionStatus(isEnabled) {
    var dot = document.getElementById('sbCompanionDot');
    var lbl = document.getElementById('sbCompanionLabel');
    if (dot) { dot.className = 'scs-dot ' + (isEnabled ? 'on' : 'off'); }
    if (lbl) { lbl.textContent = isEnabled ? 'ON' : 'OFF'; lbl.className = 'scs-label ' + (isEnabled ? 'on' : ''); }
  }
  window.syncSidebarCompanionStatus = syncSidebarCompanionStatus;

  // ── 토스트 ─────────────────────────────────────────
  function showCpToast(msg) {
    var t = document.getElementById('cpToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'cpToast';
      t.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;white-space:nowrap;max-width:90vw;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.4);';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(function(){ t.style.opacity = '0'; }, 2500);
  }

  // ── 유틸 ───────────────────────────────────────────
  function escCp(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function _timeOptions(selected) {
    var html = '';
    for (var h = 0; h <= 23; h++) {
      html += '<option value="' + h + '"' + (h === selected ? ' selected' : '') + '>' + (h < 10 ? '0' : '') + h + ':00</option>';
    }
    return html;
  }
  function _typeItem(key, active, icon, name, bg, desc) {
    var isActive = active === true || active === 1 || active === '1';
    return '<div class="cp-type-item' + (isActive ? ' active' : '') + '" data-type="' + key + '">' +
      '<div class="cp-type-icon" style="background:' + bg + '"><i class="' + icon + '"></i></div>' +
      '<div><div class="cp-type-name">' + name + '</div><div style="font-size:10px;color:var(--text-muted);">' + desc + '</div></div>' +
      '<div class="cp-type-check">' + (isActive ? '<i class="fas fa-check"></i>' : '') + '</div>' +
    '</div>';
  }

  window.openCompanionPanel = openCompanionPanel;

}); // end DOMContentLoaded (companion)


// =====================================================
// 예약 관리 설정 패널
// =====================================================
document.addEventListener('DOMContentLoaded', function () {

  var sbReserveBtn    = document.getElementById('sbReserveBtn');
  var reservePanel    = document.getElementById('reservePanel');
  var reserveOverlay  = document.getElementById('reserveOverlay');
  var reserveCloseBtn = document.getElementById('reserveCloseBtn');
  var reserveSaveBtn  = document.getElementById('reserveSaveBtn');
  var reserveBody     = document.getElementById('reservePanelBody');
  var reserveTabs     = document.getElementById('reserveTabs');

  if (!reservePanel) return;

  var rvConfig   = {};
  var rvSmsIdx   = 0;
  var rvBotName  = '';
  var rvActiveTab = 'basic';

  // sms_idx 자동 감지
  function getActiveSmsIdx() {
    if (typeof window.chatSmsIdx !== 'undefined' && window.chatSmsIdx > 0) return window.chatSmsIdx;
    try {
      var stored = localStorage.getItem('onechat_sms_idx');
      if (stored && parseInt(stored) > 0) return parseInt(stored);
    } catch(e){}
    try {
      var params = new URLSearchParams(location.search);
      var p = params.get('sms_idx');
      if (p && parseInt(p) > 0) return parseInt(p);
    } catch(e){}
    return 0;
  }

  function openReservePanel() {
    if (typeof closeSidebar === 'function') closeSidebar();
    reservePanel.classList.add('open');
    reserveOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    history.pushState({ menu: 'reserve' }, '', '#reserve');
    rvSmsIdx = getActiveSmsIdx();
    rvActiveTab = 'basic';
    if (reserveTabs) {
      reserveTabs.querySelectorAll('.reserve-tab').forEach(function(t) {
        t.classList.remove('active');
        if (t.dataset.tab === 'basic') t.classList.add('active');
      });
    }
    loadReserveConfig();
  }

  function closeReservePanel() {
    reservePanel.classList.remove('open');
    reserveOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (sbReserveBtn)    sbReserveBtn.addEventListener('click', openReservePanel);
  if (reserveCloseBtn) reserveCloseBtn.addEventListener('click', closeReservePanel);
  if (reserveOverlay)  reserveOverlay.addEventListener('click', closeReservePanel);
  if (reserveSaveBtn)  reserveSaveBtn.addEventListener('click', saveReserveConfig);

  // 탭 클릭
  if (reserveTabs) {
    reserveTabs.addEventListener('click', function(e) {
      var tab = e.target.closest('.reserve-tab');
      if (!tab) return;
      collectCurrentTab();
      rvActiveTab = tab.dataset.tab;
      reserveTabs.querySelectorAll('.reserve-tab').forEach(function(t) { t.classList.remove('active'); });
      tab.classList.add('active');
      renderReserveTab();
    });
  }

  function loadReserveConfig() {
    reserveBody.innerHTML = '<div class="reserve-loading"><i class="fas fa-spinner fa-spin"></i> 설정을 불러오는 중...</div>';
    var url = './api/reserve_setting.php';
    if (rvSmsIdx > 0) url += '?sms_idx=' + rvSmsIdx;

    fetch(url, { credentials: 'include' })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (!data.success) throw new Error(data.error || '로드 실패');
        rvConfig  = data.config || {};
        rvSmsIdx  = data.sms_idx || rvSmsIdx;
        rvBotName = data.bot_name || '';
        try { localStorage.setItem('onechat_sms_idx', rvSmsIdx); } catch(e){}
        renderReserveTab();
      })
      .catch(function(err) {
        reserveBody.innerHTML = '<div class="reserve-loading" style="color:#ef4444;"><i class="fas fa-exclamation-triangle"></i> ' + escRv(err.message) + '</div>';
      });
  }

  function renderReserveTab() {
    switch (rvActiveTab) {
      case 'basic': renderBasicTab(); break;
      case 'time':  renderTimeTab();  break;
      case 'noti':  renderNotiTab();  break;
      default:      renderBasicTab();
    }
  }

  function renderBasicTab() {
    var c = rvConfig;
    var html =
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-robot"></i> 현재 채팅방</div>' +
      '<div style="font-size:14px;font-weight:600;color:var(--text-primary);padding:4px 0;">' +
        '<i class="fas fa-comment-dots" style="color:#14b8a6;margin-right:6px;"></i>' +
        escRv(rvBotName || '채팅방 #' + rvSmsIdx) +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-power-off"></i> 예약 기능</div>' +
      '<div class="rv-toggle-row">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">예약 접수 활성화</span>' +
          '<span class="rv-toggle-desc">활성화하면 방문자가 채팅으로 예약할 수 있습니다</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.is_enabled ? ' on' : '') + '" id="rvEnableSwitch">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-check-double"></i> 예약 확정 방식</div>' +
      '<div class="rv-toggle-row">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">자동 확정</span>' +
          '<span class="rv-toggle-desc">OFF이면 관리자가 수동으로 확정합니다</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.auto_confirm ? ' on' : '') + '" id="rvAutoConfirm">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-th-large"></i> 슬롯 설정</div>' +
      '<div style="margin-bottom:12px;">' +
        '<label class="rv-label">슬롯당 시간 (분)</label>' +
        '<div class="rv-hint">한 예약 건당 소요 시간</div>' +
        '<select class="rv-select" id="rvSlotDuration">' +
          _rvDurationOptions(c.slot_duration || 30) +
        '</select>' +
      '</div>' +
      '<div>' +
        '<label class="rv-label">슬롯당 최대 예약 수</label>' +
        '<div class="rv-hint">같은 시간에 받을 수 있는 최대 예약 건수</div>' +
        '<select class="rv-select" id="rvMaxPerSlot">' +
          _rvMaxOptions(c.max_per_slot || 1) +
        '</select>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-tag"></i> 예약 명칭</div>' +
      '<div class="rv-hint">방문자에게 보이는 예약 이름 (예: 상담 예약, 진료 예약)</div>' +
      '<input type="text" class="rv-input" id="rvReserveName" value="' + escRv(c.reserve_name || '예약') + '" placeholder="예: 상담 예약" />' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-info-circle"></i> 예약 안내 메시지</div>' +
      '<div class="rv-hint">예약 시 방문자에게 보여지는 안내 메시지</div>' +
      '<textarea class="rv-textarea" id="rvGuide" rows="4" placeholder="예) 예약 후 변경은 24시간 전까지 가능합니다.">' + escRv(c.reserve_guide || '') + '</textarea>' +
    '</div>' +
    '<div class="rv-bottom-spacer"></div>';

    reserveBody.innerHTML = html;
    _bindRvSwitches();
  }

  function renderTimeTab() {
    var c = rvConfig;
    var days = c.work_days || [1,2,3,4,5];

    var html =
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-business-time"></i> 영업 시간</div>' +
      '<div class="rv-hint">예약 접수가 가능한 시간대</div>' +
      '<div class="rv-time-row">' +
        '<select class="rv-select" id="rvOpenHour">' + _rvTimeOptions(c.open_hour != null ? c.open_hour : 9) + '</select>' +
        '<span class="rv-time-sep">시 ~</span>' +
        '<select class="rv-select" id="rvCloseHour">' + _rvTimeOptions(c.close_hour != null ? c.close_hour : 18) + '</select>' +
        '<span class="rv-time-sep">시</span>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-utensils"></i> 점심시간</div>' +
      '<div class="rv-toggle-row" style="margin-bottom:12px;">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">점심시간 제외</span>' +
          '<span class="rv-toggle-desc">점심시간에는 예약을 받지 않습니다</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.lunch_enabled ? ' on' : '') + '" id="rvLunchEnabled">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
      '<div class="rv-time-row">' +
        '<select class="rv-select" id="rvLunchStart">' + _rvTimeOptions(c.lunch_start != null ? c.lunch_start : 12) + '</select>' +
        '<span class="rv-time-sep">시 ~</span>' +
        '<select class="rv-select" id="rvLunchEnd">' + _rvTimeOptions(c.lunch_end != null ? c.lunch_end : 13) + '</select>' +
        '<span class="rv-time-sep">시</span>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-calendar-week"></i> 영업 요일</div>' +
      '<div class="rv-hint">예약을 받을 요일을 선택하세요</div>' +
      '<div class="rv-day-grid">' +
        _rvDayChips(days) +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-ban"></i> 취소 기한</div>' +
      '<div class="rv-hint">예약 취소가 가능한 최소 시간 (예약 시간 기준)</div>' +
      '<select class="rv-select" id="rvCancelNotice">' +
        _rvCancelOptions(c.cancel_notice_hours != null ? c.cancel_notice_hours : 24) +
      '</select>' +
    '</div>' +
    '<div class="rv-bottom-spacer"></div>';

    reserveBody.innerHTML = html;
    _bindRvSwitches();
    _bindRvDayChips();
  }

  function renderNotiTab() {
    var c = rvConfig;
    var html =
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-bell"></i> 알림 설정</div>' +
      '<div class="rv-toggle-row" style="margin-bottom:14px;">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">새 예약 알림</span>' +
          '<span class="rv-toggle-desc">새로운 예약이 들어오면 알림</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.noti_new_reserve ? ' on' : '') + '" id="rvNotiNew">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
      '<div class="rv-toggle-row" style="margin-bottom:14px;">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">취소 알림</span>' +
          '<span class="rv-toggle-desc">예약 취소 시 알림</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.noti_cancel ? ' on' : '') + '" id="rvNotiCancel">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
      '<div class="rv-toggle-row">' +
        '<div class="rv-toggle-info">' +
          '<span class="rv-toggle-label">리마인드 알림</span>' +
          '<span class="rv-toggle-desc">예약 시간 전 방문자에게 리마인드 발송</span>' +
        '</div>' +
        '<div class="rv-switch' + (c.noti_remind ? ' on' : '') + '" id="rvNotiRemind">' +
          '<div class="rv-switch-knob"></div>' +
        '</div>' +
      '</div>' +
    '</div>' +
    '<div class="rv-section">' +
      '<div class="rv-section-title"><i class="fas fa-clock"></i> 리마인드 시간</div>' +
      '<div class="rv-hint">예약 시간 몇 시간 전에 알림을 보낼지 설정</div>' +
      '<select class="rv-select" id="rvRemindHours">' +
        '<option value="0.5"' + (c.remind_hours == 0.5 ? ' selected' : '') + '>30분 전</option>' +
        '<option value="1"' + (c.remind_hours == 1 ? ' selected' : '') + '>1시간 전</option>' +
        '<option value="2"' + (c.remind_hours == 2 ? ' selected' : '') + '>2시간 전</option>' +
        '<option value="3"' + (c.remind_hours == 3 ? ' selected' : '') + '>3시간 전</option>' +
        '<option value="6"' + (c.remind_hours == 6 ? ' selected' : '') + '>6시간 전</option>' +
        '<option value="12"' + (c.remind_hours == 12 ? ' selected' : '') + '>12시간 전</option>' +
        '<option value="24"' + (c.remind_hours == 24 ? ' selected' : '') + '>24시간 전 (1일 전)</option>' +
      '</select>' +
    '</div>' +
    '<div class="rv-bottom-spacer"></div>';

    reserveBody.innerHTML = html;
    _bindRvSwitches();
  }

  function _bindRvSwitches() {
    reserveBody.querySelectorAll('.rv-switch').forEach(function(sw) {
      sw.addEventListener('click', function() { this.classList.toggle('on'); });
    });
  }

  function _bindRvDayChips() {
    reserveBody.querySelectorAll('.rv-day-chip').forEach(function(chip) {
      chip.addEventListener('click', function() { this.classList.toggle('active'); });
    });
  }

  function collectCurrentTab() {
    switch (rvActiveTab) {
      case 'basic':
        var sw = document.getElementById('rvEnableSwitch');
        if (sw) rvConfig.is_enabled = sw.classList.contains('on');
        var ac = document.getElementById('rvAutoConfirm');
        if (ac) rvConfig.auto_confirm = ac.classList.contains('on');
        var sd = document.getElementById('rvSlotDuration');
        if (sd) rvConfig.slot_duration = parseInt(sd.value);
        var mp = document.getElementById('rvMaxPerSlot');
        if (mp) rvConfig.max_per_slot = parseInt(mp.value);
        var rn = document.getElementById('rvReserveName');
        if (rn) rvConfig.reserve_name = rn.value.trim();
        var rg = document.getElementById('rvGuide');
        if (rg) rvConfig.reserve_guide = rg.value.trim();
        break;
      case 'time':
        var oh = document.getElementById('rvOpenHour');
        if (oh) rvConfig.open_hour = parseInt(oh.value);
        var chx = document.getElementById('rvCloseHour');
        if (chx) rvConfig.close_hour = parseInt(chx.value);
        var le = document.getElementById('rvLunchEnabled');
        if (le) rvConfig.lunch_enabled = le.classList.contains('on');
        var ls = document.getElementById('rvLunchStart');
        if (ls) rvConfig.lunch_start = parseInt(ls.value);
        var lend = document.getElementById('rvLunchEnd');
        if (lend) rvConfig.lunch_end = parseInt(lend.value);
        var cn = document.getElementById('rvCancelNotice');
        if (cn) rvConfig.cancel_notice_hours = parseInt(cn.value);
        var activeDays = [];
        reserveBody.querySelectorAll('.rv-day-chip.active').forEach(function(chip) {
          activeDays.push(parseInt(chip.dataset.day));
        });
        rvConfig.work_days = activeDays;
        break;
      case 'noti':
        var nn = document.getElementById('rvNotiNew');
        if (nn) rvConfig.noti_new_reserve = nn.classList.contains('on');
        var nc = document.getElementById('rvNotiCancel');
        if (nc) rvConfig.noti_cancel = nc.classList.contains('on');
        var nr = document.getElementById('rvNotiRemind');
        if (nr) rvConfig.noti_remind = nr.classList.contains('on');
        var rh = document.getElementById('rvRemindHours');
        if (rh) rvConfig.remind_hours = parseFloat(rh.value);
        break;
    }
  }

  function saveReserveConfig() {
    collectCurrentTab();
    var payload = Object.assign({}, rvConfig);
    payload.sms_idx = rvSmsIdx;

    if (reserveSaveBtn) {
      reserveSaveBtn.disabled = true;
      reserveSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 저장 중...';
    }

    fetch('./api/reserve_setting.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (reserveSaveBtn) {
        reserveSaveBtn.disabled = false;
        reserveSaveBtn.innerHTML = '<i class="fas fa-check"></i> 저장';
      }
      if (data.success) {
        showRvToast('저장되었습니다');
      } else {
        showRvToast('저장 실패: ' + (data.error || '알 수 없음'));
      }
    })
    .catch(function(){
      if (reserveSaveBtn) {
        reserveSaveBtn.disabled = false;
        reserveSaveBtn.innerHTML = '<i class="fas fa-check"></i> 저장';
      }
      showRvToast('네트워크 오류');
    });
  }

  function showRvToast(msg) {
    var t = document.getElementById('rvToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'rvToast';
      t.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;white-space:nowrap;max-width:90vw;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.4);';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(function(){ t.style.opacity = '0'; }, 2500);
  }

  function escRv(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function _rvTimeOptions(selected) {
    var html = '';
    for (var h = 0; h <= 23; h++) {
      html += '<option value="' + h + '"' + (h === selected ? ' selected' : '') + '>' + (h < 10 ? '0' : '') + h + ':00</option>';
    }
    return html;
  }

  function _rvDurationOptions(selected) {
    var opts = [10, 15, 20, 30, 45, 60, 90, 120];
    return opts.map(function(m) {
      var label = m >= 60 ? (m/60) + '시간' : m + '분';
      return '<option value="' + m + '"' + (m === selected ? ' selected' : '') + '>' + label + '</option>';
    }).join('');
  }

  function _rvMaxOptions(selected) {
    var html = '';
    for (var i = 1; i <= 20; i++) {
      html += '<option value="' + i + '"' + (i === selected ? ' selected' : '') + '>' + i + '건</option>';
    }
    return html;
  }

  function _rvDayChips(activeDays) {
    var dayNames = ['일','월','화','수','목','금','토'];
    var html = '';
    for (var d = 0; d <= 6; d++) {
      var isActive = activeDays.indexOf(d) !== -1;
      html += '<div class="rv-day-chip' + (isActive ? ' active' : '') + '" data-day="' + d + '">' + dayNames[d] + '</div>';
    }
    return html;
  }

  function _rvCancelOptions(selected) {
    var opts = [
      { v: 1, l: '1시간 전' }, { v: 2, l: '2시간 전' }, { v: 3, l: '3시간 전' },
      { v: 6, l: '6시간 전' }, { v: 12, l: '12시간 전' },
      { v: 24, l: '24시간 전 (1일 전)' }, { v: 48, l: '48시간 전 (2일 전)' },
    ];
    return opts.map(function(o) {
      return '<option value="' + o.v + '"' + (o.v === selected ? ' selected' : '') + '>' + o.l + '</option>';
    }).join('');
  }

  window.openReservePanel = openReservePanel;

}); // end DOMContentLoaded (reserve)



// =====================================================
// 해시 라우팅 - 초기 로드 및 뒤로가기 지원
// =====================================================
(function() {
  // 해시값 → 액션 처리 함수
  function handleHash(hash) {
    if (!hash || hash === '#') return;
    switch (hash) {
      case '#chat':
        if (typeof switchTab === 'function') switchTab('chat');
        break;
      case '#send':
        if (typeof switchTab === 'function') switchTab('contacts');
        break;
      case '#receive':
        if (typeof switchTab === 'function') switchTab('received');
        break;
      case '#broadcast':
        var bcBtn = document.getElementById('openBcMenuBtn');
        if (bcBtn) bcBtn.click();
        break;
      case '#reserve':
        if (typeof openReservePanel === 'function') openReservePanel();
        break;
      case '#companion':
        if (typeof openCompanionPanel === 'function') openCompanionPanel();
        break;
      case '#mylink':
      case '#profile':
        if (typeof openProfilePanel === 'function') openProfilePanel();
        break;
      case '#learning':
        if (typeof openLearnScreen === 'function') openLearnScreen();
        break;
      default:
        // #chat/userId 패턴 처리
        if (hash.startsWith('#chat/')) {
          var userId = hash.replace('#chat/', '');
          if (typeof switchTab === 'function') switchTab('chat');
          // 해당 유저 채팅 열기 (openChatById가 있으면 호출)
          if (typeof openChatById === 'function') openChatById(userId);
        }
        break;
    }
  }

  // 페이지 로드 시 초기 해시로 자동 이동
  // (switchTab이 history.pushState로 hash를 덮어쓰기 전에 저장한 _initialHash 사용)
  document.addEventListener('DOMContentLoaded', function() {
    var hash = (typeof _initialHash !== 'undefined' && _initialHash) ? _initialHash : location.hash;
    if (hash && hash !== '#chat') {
      // 약간의 딜레이 후 처리 (다른 DOMContentLoaded 핸들러 완료 후)
      setTimeout(function() { handleHash(hash); }, 300);
    }
  });

  // 뒤로가기 / 앞으로가기 지원
  window.addEventListener('popstate', function(e) {
    var hash = location.hash;
    if (e.state && e.state.tab) {
      if (typeof switchTab === 'function') switchTab(e.state.tab);
    } else if (e.state && e.state.menu) {
      handleHash('#' + e.state.menu);
    } else if (hash) {
      handleHash(hash);
    }
  });

  // 📱 모바일 앱: URL 해시 직접 변경 시 감지 (링크 클릭 등)
  window.addEventListener('hashchange', function() {
    var hash = location.hash;
    if (hash && typeof handleHash === 'function') {
      handleHash(hash);
    }
  });

  // 전역 노출 (외부에서도 해시 처리 가능)
  window.handleHashRoute = handleHash;
})();
