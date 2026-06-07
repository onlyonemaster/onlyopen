/**
 * IAM 회원 DM 채팅 시스템
 * dm.js — 채팅 탭 전체 로직
 */

// ── 상태 ────────────────────────────────────────────────────
let dmCurrentRoom        = null;  // 현재 열린 채팅방 {room_id, room_type, room_name, ...}
let dmCurrentSubtab      = 'direct';
let dmPollingTimer       = null;
let dmLastMsgId          = 0;
let dmReplyTarget        = null;  // 답장 대상 메시지 {msg_id, sender_name, content}
let dmPollingInProgress  = false; // 폴링 중복 방지 락
let dmPendingTempId      = null;  // 낙관적 렌더링 임시 요소 ID
let dmGroupMode       = false; // 모달이 그룹 생성 모드인지
let dmSelectedMembers = [];    // 그룹 생성 시 선택된 멤버
let dmRooms           = [];    // 현재 로드된 채팅방 목록
let dmContextMenuEl   = null;  // 컨텍스트 메뉴 DOM

// [DEBUG] dm.js 실행 확인용 (임시)
const DM_API_BASE = 'api/dm/';
const QUICK_EMOJIS = ['👍','❤️','😂','😮','😢','🙏'];

// ── DM 전용 fetch 래퍼 (api.js 로드 여부와 무관하게 동작) ───
async function _dmGet(endpoint, params = {}) {
  try {
    const url = new URL(DM_API_BASE + endpoint, location.href);
    Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    const res = await fetch(url, { credentials: 'include' });
    if (res.status === 401) { window.top.location.href = '/iam/login.php'; return null; }
    return res.json();
  } catch(e) { console.error('[DM GET]', e); return null; }
}
async function _dmPost(endpoint, body = {}) {
  try {
    const res = await fetch(DM_API_BASE + endpoint, {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    if (res.status === 401) { window.top.location.href = '/iam/login.php'; return null; }
    return res.json();
  } catch(e) { console.error('[DM POST]', e); return null; }
}

// ── 초기화 ───────────────────────────────────────────────────
function initDM() {
  // 서브 탭 전환
  document.querySelectorAll('.dm-subtab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.dm-subtab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      dmCurrentSubtab = btn.dataset.dmtab;
      loadDMRooms();
    });
  });

  // 새 채팅 버튼
  const newChatBtn = document.getElementById('dmNewChatBtn');
  if (newChatBtn) newChatBtn.addEventListener('click', () => openDMSearchModal(false));

  // 그룹 만들기 버튼
  const newGroupBtn = document.getElementById('dmNewGroupBtn');
  if (newGroupBtn) newGroupBtn.addEventListener('click', () => openDMSearchModal(true));

  // 뒤로가기
  document.getElementById('dmBackBtn')?.addEventListener('click', closeDMChat);

  // 전송 버튼
  document.getElementById('dmSendBtn')?.addEventListener('click', sendDMMessage);

  // 입력창 Enter 키
  const dmInput = document.getElementById('dmInput');
  if (dmInput) {
    dmInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendDMMessage(); }
    });
    dmInput.addEventListener('input', () => {
      dmInput.style.height = 'auto';
      dmInput.style.height = Math.min(dmInput.scrollHeight, 120) + 'px';
    });
  }

  // 파일 첨부
  document.getElementById('dmAttachBtn')?.addEventListener('click', () => {
    document.getElementById('dmFileInput')?.click();
  });
  document.getElementById('dmFileInput')?.addEventListener('change', handleDMFileSelect);

  // 답장 취소
  document.getElementById('dmReplyCancelBtn')?.addEventListener('click', cancelDMReply);

  // 투표 버튼
  document.getElementById('dmPollBtn')?.addEventListener('click', openPollModal);

  // 모달 닫기
  document.getElementById('dmModalClose')?.addEventListener('click', closeDMSearchModal);
  document.getElementById('dmPollModalClose')?.addEventListener('click', () => {
    document.getElementById('dmPollModal').style.display = 'none';
  });

  // 회원 검색
  let searchTimer;
  document.getElementById('dmMemberSearch')?.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => searchDMMembers(e.target.value), 300);
  });

  // 그룹 생성 버튼
  document.getElementById('dmGroupCreateBtn')?.addEventListener('click', createGroupChat);

  // 투표 옵션 추가
  document.getElementById('dmPollAddOption')?.addEventListener('click', () => {
    const opts = document.getElementById('dmPollOptions');
    const cnt = opts.querySelectorAll('.dm-poll-option').length + 1;
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'dm-poll-option';
    inp.placeholder = '선택지 ' + cnt;
    inp.style.cssText = 'width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg-input);color:var(--text);font-size:13px;margin-bottom:8px;box-sizing:border-box;';
    opts.appendChild(inp);
  });

  // 투표 제출
  document.getElementById('dmPollSubmit')?.addEventListener('click', submitPoll);

  // 컨텍스트 메뉴 닫기 (외부 클릭)
  document.addEventListener('click', () => removeDMContextMenu());

  // 고정 메시지 클릭 → 해당 메시지로 스크롤
  document.getElementById('dmPinnedBanner')?.addEventListener('click', () => {
    if (!dmCurrentRoom?.pinned_msg_id) return;
    const el = document.querySelector('[data-msg-id="' + dmCurrentRoom.pinned_msg_id + '"]');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  // 오버레이 클릭 시 모달 닫기
  document.getElementById('dmSearchModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDMSearchModal();
  });
  document.getElementById('dmPollModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
}

// ── 채팅방 목록 로드 ─────────────────────────────────────────
async function loadDMRooms() {
  const listEl = document.getElementById('dmRoomList');
  // 최초 로드 시에만 로딩 스피너 표시 (30초 refresh 시 번쩍임 방지)
  if (dmRooms.length === 0) {
    listEl.innerHTML = '<div class="dm-loading"><i class="fas fa-spinner fa-spin"></i> 로딩 중...</div>';
  }

  try {
    const data = await _dmGet('dm_rooms.php', { type: dmCurrentSubtab });
    if (!data || !data.success) throw new Error('API 오류');
    dmRooms = data.rooms || [];

    // 탭 배지 업데이트
    const totalUnread = data.total_unread || 0;
    const badge = document.getElementById('chatBadge');
    if (badge) {
      badge.textContent = dmRooms.length;
    }
    updateDMTabBadge(totalUnread);

    if (dmRooms.length === 0) {
      listEl.innerHTML = '<div class="dm-loading" style="flex-direction:column;gap:8px;">' +
        '<i class="fas fa-comment-dots" style="font-size:40px;opacity:0.3;"></i>' +
        '<span>' + (dmCurrentSubtab === 'direct' ? '아직 대화가 없습니다.<br>새 채팅을 시작해보세요!' : '참여한 그룹이 없습니다.') + '</span>' +
        '</div>';
      return;
    }

    listEl.innerHTML = '';
    dmRooms.forEach(room => listEl.appendChild(buildDMRoomCard(room)));
  } catch (e) {
    listEl.innerHTML = '<div class="dm-loading" style="color:#ef4444;">로드 실패</div>';
  }
}

// ── 채팅방 목록 검색 필터 ──
async function filterDMRooms(query) {
  const listEl = document.getElementById('dmRoomList');
  if (!listEl) return;
  const q = (query || '').trim().toLowerCase();

  // 캐시가 비어있으면 먼저 전체 로드 (퍼널/공유와 동일한 방식)
  if (dmRooms.length === 0) {
    await loadDMRooms();
  }

  if (!q) {
    // 검색어 없으면 전체 목록 복원
    listEl.innerHTML = '';
    dmRooms.forEach(room => listEl.appendChild(buildDMRoomCard(room)));
    return;
  }

  // JS 필터링: room_name(또는 name/partner_name 호환), last_msg(또는 last_message) 포함 검색
  const filtered = dmRooms.filter(room => {
    const name = (room.room_name || room.name || room.partner_name || '').toLowerCase();
    const last = (room.last_msg || room.last_message || '').toLowerCase();
    return name.includes(q) || last.includes(q);
  });

  listEl.innerHTML = '';
  if (filtered.length === 0) {
    listEl.innerHTML = '<div class="dm-loading" style="flex-direction:column;gap:8px;">' +
      '<i class="fas fa-search" style="font-size:32px;opacity:0.3;"></i>' +
      '<span>검색 결과가 없습니다</span></div>';
    return;
  }
  filtered.forEach(room => listEl.appendChild(buildDMRoomCard(room)));
}
function updateDMTabBadge(count) {
  // 네비 탭 배지
  const navBtn = document.querySelector('[data-tab="chat"]');
  if (!navBtn) return;
  let badge = navBtn.querySelector('.nav-unread-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'nav-unread-badge';
    badge.style.cssText = 'position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:99px;padding:0 4px;display:flex;align-items:center;justify-content:center;';
    navBtn.style.position = 'relative';
    navBtn.appendChild(badge);
  }
  badge.textContent = count;
  badge.style.display = count > 0 ? 'flex' : 'none';
}

function buildDMRoomCard(room) {
  const card = document.createElement('div');
  card.className = 'dm-room-card' + (room.is_pinned ? ' pinned' : '');

  const cc = colorFromId(room.room_id.toString());
  const name = room.room_name || '채팅방';
  const initial = name.charAt(0);
  const isOnline = room.other?.is_online;
  const timeLabel = room.last_msg_at ? formatRelativeDate(room.last_msg_at) : '';
  const preview = room.last_msg
    ? (room.last_sender && room.last_sender !== (window.myMemId || '')
        ? room.last_sender + ': ' + room.last_msg
        : room.last_msg)
    : '아직 대화 없음';

  const groupIcon = room.room_type === 'group'
    ? '<i class="fas fa-users" style="font-size:18px;"></i>'
    : initial;

  card.innerHTML =
    '<div class="dm-room-avatar ' + cc + '">' +
      groupIcon +
      (isOnline ? '<div class="dm-online-dot"></div>' : '') +
    '</div>' +
    '<div class="dm-room-info">' +
      '<div class="dm-room-name">' +
        (room.is_pinned ? '<i class="fas fa-thumbtack dm-pin-icon" style="margin-right:4px;font-size:10px;"></i>' : '') +
        escHtml(name) +
        (room.room_type === 'group' ? ' <span style="font-size:11px;color:var(--text-muted);">(' + room.member_count + '명)</span>' : '') +
      '</div>' +
      '<div class="dm-room-preview">' + escHtml(preview) + '</div>' +
    '</div>' +
    '<div class="dm-room-meta">' +
      '<span class="dm-room-time">' + timeLabel + '</span>' +
      (room.unread_count > 0 ? '<span class="dm-unread-badge">' + room.unread_count + '</span>' : '') +
    '</div>';

  card.addEventListener('click', () => openDMChat(room));
  return card;
}

// ── 채팅창 열기/닫기 ─────────────────────────────────────────
async function openDMChat(room) {
  dmCurrentRoom = room;
  dmLastMsgId   = 0;
  dmReplyTarget = null;

  const screen = document.getElementById('dmChatScreen');
  screen.classList.add('active');

  // 뒤에 있는 요소들 숨기기 (레이어 안전 처리)
  const screenChat = document.getElementById('screenChat');
  if (screenChat) screenChat.style.visibility = 'hidden';
  const appHeader = document.querySelector('.app-header');
  if (appHeader) appHeader.style.visibility = 'hidden';
  const bottomNav = document.querySelector('.bottom-nav');
  if (bottomNav) bottomNav.style.visibility = 'hidden';

  // 헤더
  document.getElementById('dmHeaderName').textContent = room.room_name || '채팅';
  const subEl = document.getElementById('dmHeaderSub');
  if (room.room_type === 'group') {
    subEl.textContent = room.member_count + '명';
    document.getElementById('dmMembersBtn').style.display = '';
  } else {
    subEl.textContent = room.other?.is_online ? '온라인' : (room.other?.last_seen ? formatRelativeDate(room.other.last_seen) + ' 접속' : '');
    document.getElementById('dmMembersBtn').style.display = 'none';
  }

  // 고정 메시지
  const pinnedBanner = document.getElementById('dmPinnedBanner');
  pinnedBanner.style.display = 'none';

  // 메시지 영역 초기화
  document.getElementById('dmMessages').innerHTML = '<div class="dm-loading"><i class="fas fa-spinner fa-spin"></i></div>';
  document.getElementById('dmEmptyState')?.remove();
  document.getElementById('dmInput').value = '';
  cancelDMReply();

  await loadDMHistory();
  startDMPolling();
}

function closeDMChat() {
  stopDMPolling();
  dmCurrentRoom = null;
  document.getElementById('dmChatScreen').classList.remove('active');

  // 숨긴 요소 복구
  const screenChat = document.getElementById('screenChat');
  if (screenChat) screenChat.style.visibility = '';
  const appHeader = document.querySelector('.app-header');
  if (appHeader) appHeader.style.visibility = '';
  const bottomNav = document.querySelector('.bottom-nav');
  if (bottomNav) bottomNav.style.visibility = '';

  loadDMRooms(); // 목록 새로고침
}

// ── 메시지 히스토리 로드 ─────────────────────────────────────
async function loadDMHistory() {
  if (!dmCurrentRoom) return;
  const data = await _dmGet('dm_messages.php', { room_id: dmCurrentRoom.room_id, limit: 50 });
  if (!data || !data.success) return;

  const msgs = data.messages || [];
  const msgsEl = document.getElementById('dmMessages');
  msgsEl.innerHTML = '';

  if (msgs.length === 0) {
    msgsEl.innerHTML = '<div class="dm-empty-state"><i class="fas fa-comment-dots" style="font-size:40px;color:var(--text-muted);margin-bottom:12px;"></i><p style="color:var(--text-muted);">첫 메시지를 보내보세요!</p></div>';
    return;
  }

  // 고정 메시지 배너
  if (data.pinned) {
    dmCurrentRoom.pinned_msg_id = data.pinned.msg_id;
    const banner = document.getElementById('dmPinnedBanner');
    document.getElementById('dmPinnedText').textContent = data.pinned.content;
    banner.style.display = 'flex';
  }

  renderDMMessages(msgs, msgsEl, false);

  if (msgs.length > 0) {
    dmLastMsgId = msgs[msgs.length - 1].msg_id;
    // 읽음 처리
    _dmPost('dm_read.php', { mode: 'read', room_id: dmCurrentRoom.room_id, last_msg_id: dmLastMsgId });
  }

  msgsEl.scrollTop = msgsEl.scrollHeight;
}

function renderDMMessages(msgs, container, append = true) {
  // 이미 렌더된 마지막 날짜 구분선 파악 (중복 방지)
  const lastDividerEl = [...container.querySelectorAll('.dm-date-divider')].pop();
  let lastDate = lastDividerEl?.dataset?.dmDate || '';

  msgs.forEach(msg => {
    // 이미 DOM에 있는 메시지 스킵 (중복 방지)
    if (msg.msg_id && container.querySelector('[data-msg-id="' + msg.msg_id + '"]')) return;

    // 날짜 구분선 (날짜 바뀔 때만)
    if (msg.date !== lastDate) {
      lastDate = msg.date;
      const divider = document.createElement('div');
      divider.className = 'dm-date-divider';
      divider.dataset.dmDate = msg.date; // 날짜 저장 (중복 체크용)
      divider.textContent = formatDateLabel(msg.date);
      container.appendChild(divider);
    }
    container.appendChild(buildDMMessageRow(msg));
  });
}

function buildDMMessageRow(msg) {
  const row = document.createElement('div');
  const isMine = msg.is_mine;
  row.className = 'dm-msg-row' + (isMine ? ' mine' : '') + (msg.msg_type === 'system' ? ' system' : '');
  row.dataset.msgId = msg.msg_id;

  if (msg.msg_type === 'system') {
    row.innerHTML = '<div class="dm-bubble">' + escHtml(msg.content || '') + '</div>';
    return row;
  }

  const cc = colorFromId(msg.sender_id);
  let bodyHtml = '';

  // 발신자 이름 (그룹, 상대방만)
  if (!isMine && dmCurrentRoom?.room_type === 'group') {
    bodyHtml += '<div class="dm-msg-sender-name">' + escHtml(msg.sender_name) + '</div>';
  }

  // 답장 인용
  if (msg.reply) {
    bodyHtml += '<div class="dm-reply-quote">' +
      '<span class="dm-reply-quote-sender">' + escHtml(msg.reply.sender) + '</span> ' +
      escHtml(msg.reply.content) +
      '</div>';
  }

  // 본문 버블
  if (msg.is_deleted) {
    bodyHtml += '<div class="dm-bubble deleted">삭제된 메시지입니다</div>';
  } else if (msg.msg_type === 'image') {
    bodyHtml += '<div class="dm-image-msg"><img src="' + escHtml(msg.file_url) + '" alt="이미지" loading="lazy"></div>';
  } else if (msg.msg_type === 'file') {
    const sizeStr = msg.file_size ? formatFileSize(msg.file_size) : '';
    bodyHtml += '<div class="dm-file-msg" onclick="window.open(\'' + escHtml(msg.file_url) + '\')">' +
      '<div class="dm-file-icon"><i class="fas fa-file-alt"></i></div>' +
      '<div class="dm-file-info">' +
        '<div class="dm-file-name">' + escHtml(msg.file_name || '파일') + '</div>' +
        '<div class="dm-file-size">' + sizeStr + '</div>' +
      '</div>' +
      '<i class="fas fa-download" style="color:var(--text-muted);"></i>' +
      '</div>';
  } else if (msg.msg_type === 'poll') {
    bodyHtml += buildPollHtml(msg);
  } else {
    const editedTag = msg.edited_at ? '<span class="dm-edited-tag">(수정됨)</span>' : '';
    bodyHtml += '<div class="dm-bubble">' + escHtml(msg.content || '') + editedTag + '</div>';
  }

  // 반응
  if (msg.reactions && msg.reactions.length > 0) {
    bodyHtml += '<div class="dm-reactions">';
    msg.reactions.forEach(r => {
      bodyHtml += '<span class="dm-reaction-chip' + (r.i_reacted ? ' mine' : '') + '" onclick="toggleReaction(' + msg.msg_id + ',\'' + r.emoji + '\')">' +
        r.emoji + ' <span class="dm-reaction-count">' + r.count + '</span></span>';
    });
    bodyHtml += '</div>';
  }

  // 시간 + 읽음
  const readIcon = isMine ? '<span class="dm-read-check"><i class="fas fa-check-double"></i></span>' : '';
  bodyHtml += '<div class="dm-msg-meta"><span class="dm-msg-time">' + msg.time + '</span>' + readIcon + '</div>';

  const avatarHtml = !isMine
    ? '<div class="dm-msg-sender-avatar ' + cc + '">' + (msg.sender_name || '?').charAt(0) + '</div>'
    : '';

  row.innerHTML = avatarHtml + '<div class="dm-msg-body">' + bodyHtml + '</div>';

  // 길게 누르기 / 우클릭 → 컨텍스트 메뉴
  row.addEventListener('contextmenu', e => {
    e.preventDefault();
    showDMContextMenu(e, msg);
  });
  const bubble = row.querySelector('.dm-bubble');
  if (bubble) {
    let pressTimer;
    bubble.addEventListener('touchstart', () => { pressTimer = setTimeout(() => showDMContextMenu({ clientX: 0, clientY: 0 }, msg), 600); }, { passive: true });
    bubble.addEventListener('touchend', () => clearTimeout(pressTimer));
  }

  return row;
}

function buildPollHtml(msg) {
  return '<div class="dm-poll-msg" id="poll-' + msg.msg_id + '">' +
    '<div class="dm-poll-question"><i class="fas fa-poll" style="margin-right:6px;color:var(--accent-blue);"></i>투표 로딩 중...</div>' +
    '</div>';
  // 실제 데이터는 비동기로 로드
  setTimeout(() => loadPollData(msg.msg_id), 100);
}

async function loadPollData(msg_id) {
  const el = document.getElementById('poll-' + msg_id);
  if (!el) return;
  const data = await _dmGet('dm_read.php', { mode: 'poll', msg_id });
  if (!data || !data.success) return;

  let html = '<div class="dm-poll-question"><i class="fas fa-poll" style="margin-right:6px;color:var(--accent-blue);"></i>' + escHtml(data.question) + '</div>';
  data.options.forEach(opt => {
    const voted = opt.i_voted;
    html += '<div class="dm-poll-option' + (voted ? ' voted' : '') + '" onclick="votePoll(' + msg_id + ',' + opt.idx + ')">' +
      (voted ? '<i class="fas fa-check-circle" style="color:var(--accent-blue);font-size:14px;"></i>' : '<i class="far fa-circle" style="color:var(--text-muted);font-size:14px;"></i>') +
      '<span style="flex:1;font-size:13px;">' + escHtml(opt.text) + '</span>' +
      '<div class="dm-poll-bar"><div class="dm-poll-fill" style="width:' + opt.percent + '%"></div></div>' +
      '<span class="dm-poll-percent">' + opt.percent + '%</span>' +
      '</div>';
  });
  const endStr = data.is_ended ? ' · 마감됨' : (data.ends_at ? ' · ~' + data.ends_at.slice(0, 10) : '');
  html += '<div class="dm-poll-footer">총 ' + data.total_votes + '명 투표' + endStr + '</div>';
  el.innerHTML = html;
}

async function votePoll(msg_id, option_idx) {
  await _dmPost('dm_read.php', { mode: 'poll_vote', msg_id, option_idx });
  loadPollData(msg_id);
}

// ── 메시지 전송 ──────────────────────────────────────────────
async function sendDMMessage() {
  if (!dmCurrentRoom) return;
  const input = document.getElementById('dmInput');
  const content = input.value.trim();
  if (!content) return;

  input.value = '';
  input.style.height = 'auto';

  // ── 낙관적 렌더링: 서버 응답 전에 즉시 화면에 표시 ──
  const tempId = 'temp_' + Date.now();
  const now = new Date();
  const hh = now.getHours().toString().padStart(2, '0');
  const mm = now.getMinutes().toString().padStart(2, '0');
  const replySnap = dmReplyTarget ? {
    msg_id:  dmReplyTarget.msg_id,
    content: (dmReplyTarget.content || '').slice(0, 60),
    sender:  dmReplyTarget.sender_name,
  } : null;
  const tempMsg = {
    msg_id:     tempId,
    is_mine:    true,
    sender_id:  '',
    sender_name:'나',
    content,
    msg_type:   'text',
    time:       hh + ':' + mm,
    date:       now.toISOString().slice(0, 10),
    reactions:  [],
    reply:      replySnap,
    is_deleted: false,
    edited_at:  null,
  };

  const msgsEl = document.getElementById('dmMessages');
  const emptyState = msgsEl.querySelector('.dm-empty-state');
  if (emptyState) emptyState.remove();

  const tempRow = buildDMMessageRow(tempMsg);
  tempRow.style.opacity = '0.55'; // 전송 중 반투명
  msgsEl.appendChild(tempRow);
  msgsEl.scrollTop = msgsEl.scrollHeight;

  const payload = {
    room_id: dmCurrentRoom.room_id,
    content,
    msg_type: 'text',
  };
  if (dmReplyTarget) {
    payload.reply_to_msg_id = dmReplyTarget.msg_id;
    cancelDMReply();
  }

  // poll이 temp를 알 수 있도록 등록 (제거 + 실제 순서로 재렌더)
  dmPendingTempId = tempId;

  const data = await _dmPost('dm_messages.php', payload);

  if (data?.success) {
    // dmLastMsgId는 업데이트 안 함 → 동시에 온 상대방 메시지 누락 방지
    // poll이 old after_id로 조회하여 상대방 msg + 내 msg 모두 올바른 순서로 렌더
    pollDMMessages();
  } else {
    // 전송 실패: 임시 요소 제거
    const tempEl = msgsEl.querySelector('[data-msg-id="' + tempId + '"]');
    if (tempEl) tempEl.remove();
    dmPendingTempId = null;
    if (data !== null) alert('메시지 전송에 실패했습니다. 다시 시도해주세요.');
  }
}

// ── 폴링 ─────────────────────────────────────────────────────
function startDMPolling() {
  stopDMPolling();
  dmPollingTimer = setInterval(pollDMMessages, 2000); // 2초 폴링 (실시간 수신)
}

function stopDMPolling() {
  if (dmPollingTimer) { clearInterval(dmPollingTimer); dmPollingTimer = null; }
}

async function pollDMMessages() {
  if (!dmCurrentRoom || dmPollingInProgress) return; // 중복 실행 방지
  dmPollingInProgress = true;
  // 낙관적 임시 요소 캡처 (이번 poll에서 처리)
  const pendingTemp = dmPendingTempId;
  dmPendingTempId = null;
  try {
    const data = await _dmGet('dm_messages.php', {
      room_id: dmCurrentRoom.room_id,
      mode: 'poll',
      after_id: dmLastMsgId,
    });
    if (!data?.success || !data.messages?.length) {
      // 아직 서버에 반영 안 됐을 수 있음 → temp 유지
      if (pendingTemp) dmPendingTempId = pendingTemp;
      return;
    }

    const msgsEl = document.getElementById('dmMessages');

    // 낙관적 임시 요소 제거: 실제 메시지가 올바른 순서로 삽입되도록
    if (pendingTemp) {
      const tempEl = msgsEl.querySelector('[data-msg-id="' + pendingTemp + '"]');
      if (tempEl) tempEl.remove();
    }

    const emptyState = msgsEl.querySelector('.dm-empty-state');
    if (emptyState) emptyState.remove();

    const isBottom = msgsEl.scrollHeight - msgsEl.scrollTop - msgsEl.clientHeight < 60;
    renderDMMessages(data.messages, msgsEl);
    dmLastMsgId = data.messages[data.messages.length - 1].msg_id;

    if (isBottom) msgsEl.scrollTop = msgsEl.scrollHeight;
    _dmPost('dm_read.php', { mode: 'read', room_id: dmCurrentRoom.room_id, last_msg_id: dmLastMsgId });
  } finally {
    dmPollingInProgress = false;
  }
}

// ── 답장 ─────────────────────────────────────────────────────
function setDMReply(msg) {
  dmReplyTarget = msg;
  const preview = document.getElementById('dmReplyPreview');
  document.getElementById('dmReplySender').textContent = msg.sender_name;
  document.getElementById('dmReplyText').textContent = msg.content?.slice(0, 50) || '';
  preview.style.display = 'flex';
  document.getElementById('dmInput').focus();
}

function cancelDMReply() {
  dmReplyTarget = null;
  const preview = document.getElementById('dmReplyPreview');
  if (preview) preview.style.display = 'none';
}

// ── 이모지 반응 ──────────────────────────────────────────────
async function toggleReaction(msg_id, emoji) {
  await _dmPost('dm_messages.php', { mode: 'react', msg_id, emoji });
  pollDMMessages();
}

// ── 컨텍스트 메뉴 ────────────────────────────────────────────
function showDMContextMenu(e, msg) {
  removeDMContextMenu();

  const menu = document.createElement('div');
  menu.className = 'dm-context-menu';
  dmContextMenuEl = menu;

  // 빠른 이모지 반응
  const quickRow = document.createElement('div');
  quickRow.className = 'dm-quick-reactions';
  QUICK_EMOJIS.forEach(emoji => {
    const span = document.createElement('span');
    span.className = 'dm-quick-emoji';
    span.textContent = emoji;
    span.onclick = (ev) => { ev.stopPropagation(); toggleReaction(msg.msg_id, emoji); removeDMContextMenu(); };
    quickRow.appendChild(span);
  });
  menu.appendChild(quickRow);

  const items = [
    { icon: 'fa-reply',   label: '답장',    action: () => setDMReply(msg) },
    { icon: 'fa-copy',    label: '복사',     action: () => copyToClipboard(msg.content || '') },
  ];

  if (msg.is_mine && !msg.is_deleted) {
    items.push({ icon: 'fa-edit',       label: '수정',    action: () => editDMMessage(msg) });
    items.push({ icon: 'fa-thumbtack',  label: '고정',    action: () => pinDMMessage(msg.msg_id) });
    items.push({ icon: 'fa-trash',      label: '삭제',    action: () => deleteDMMessage(msg.msg_id), danger: true });
  }

  items.forEach(item => {
    const el = document.createElement('div');
    el.className = 'dm-context-item' + (item.danger ? ' danger' : '');
    el.innerHTML = '<i class="fas ' + item.icon + '"></i><span>' + item.label + '</span>';
    el.onclick = (ev) => { ev.stopPropagation(); item.action(); removeDMContextMenu(); };
    menu.appendChild(el);
  });

  // 위치 계산
  const x = Math.min(e.clientX || window.innerWidth / 2, window.innerWidth - 180);
  const y = Math.min(e.clientY || window.innerHeight / 2, window.innerHeight - 200);
  menu.style.left = x + 'px';
  menu.style.top  = y + 'px';
  document.body.appendChild(menu);
}

function removeDMContextMenu() {
  if (dmContextMenuEl) { dmContextMenuEl.remove(); dmContextMenuEl = null; }
}

async function editDMMessage(msg) {
  const newContent = prompt('메시지 수정:', msg.content);
  if (newContent === null || newContent.trim() === msg.content) return;
  await _dmPost('dm_messages.php', { mode: 'edit', msg_id: msg.msg_id, content: newContent.trim() });
  pollDMMessages();
}

async function deleteDMMessage(msg_id) {
  if (!confirm('메시지를 삭제할까요?')) return;
  await _dmPost('dm_messages.php', { mode: 'delete', msg_id });
  pollDMMessages();
}

async function pinDMMessage(msg_id) {
  if (!dmCurrentRoom) return;
  await _dmPost('dm_messages.php', { mode: 'pin', msg_id, room_id: dmCurrentRoom.room_id });
  await loadDMHistory();
}

// ── 파일 첨부 ────────────────────────────────────────────────
async function handleDMFileSelect(e) {
  const file = e.target.files[0];
  if (!file || !dmCurrentRoom) return;
  e.target.value = '';

  const formData = new FormData();
  formData.append('file', file);
  formData.append('room_id', dmCurrentRoom.room_id);

  try {
    const resp = await fetch('api/dm/dm_upload.php', { method: 'POST', body: formData });
    const data = await resp.json();
    if (data.success) pollDMMessages();
    else showToast('파일 업로드 실패');
  } catch (err) {
    showToast('파일 업로드 오류');
  }
}

// ── 회원 검색 모달 ───────────────────────────────────────────
function openDMSearchModal(isGroup) {
  dmGroupMode = isGroup;
  dmSelectedMembers = [];

  document.getElementById('dmModalTitle').textContent = isGroup ? '그룹 만들기' : '새 채팅';
  document.getElementById('dmGroupNameRow').style.display = isGroup ? 'block' : 'none';
  document.getElementById('dmSelectedMembers').style.display = isGroup ? 'flex' : 'none';
  document.getElementById('dmGroupCreateRow').style.display = isGroup ? 'block' : 'none';
  document.getElementById('dmMemberSearch').value = '';
  document.getElementById('dmMemberList').innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">이름을 검색하세요</div>';
  document.getElementById('dmSearchModal').style.display = 'flex';
}

function closeDMSearchModal() {
  document.getElementById('dmSearchModal').style.display = 'none';
}

async function searchDMMembers(q) {
  const listEl = document.getElementById('dmMemberList');
  if (q.length < 2) {
    listEl.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">이름을 검색하세요 (2자 이상)</div>';
    return;
  }
  listEl.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i></div>';
  const data = await _dmGet('dm_search.php', { q });
  if (!data?.success) {
    listEl.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">검색 중 오류가 발생했습니다. 다시 시도해주세요.</div>';
    return;
  }

  if (!data.members.length) {
    listEl.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">검색 결과 없음</div>';
    return;
  }

  listEl.innerHTML = '';
  data.members.forEach(m => {
    const isSelected = dmSelectedMembers.some(s => s.mem_id === m.mem_id);
    const item = document.createElement('div');
    item.className = 'dm-member-item' + (isSelected ? ' selected' : '');
    const cc = colorFromId(m.mem_id);
    item.innerHTML =
      '<div class="dm-member-avatar ' + cc + '">' +
        (m.mem_name || '?').charAt(0) +
        (m.is_online ? '<div class="dm-online-dot"></div>' : '') +
      '</div>' +
      '<div>' +
        '<div class="dm-member-name">' + escHtml(m.mem_name) + '</div>' +
        '<div class="dm-member-id">@' + escHtml(m.mem_id) + '</div>' +
      '</div>' +
      (isSelected ? '<i class="fas fa-check-circle" style="color:var(--accent-blue);margin-left:auto;"></i>' : '');

    item.addEventListener('click', () => selectDMMember(m));
    listEl.appendChild(item);
  });
}

async function selectDMMember(member) {
  if (!dmGroupMode) {
    // 1:1 채팅 시작
    closeDMSearchModal();
    const data = await _dmPost('dm_create.php', { type: 'direct', target_mem_id: member.mem_id });
    if (!data?.success) {
      alert('채팅방을 열 수 없습니다. 잠시 후 다시 시도해주세요.');
      return;
    }
    await loadDMRooms();
    const room = dmRooms.find(r => r.room_id === data.room_id);
    if (room) {
      openDMChat(room);
    } else {
      // 목록 새로고침 후 재시도
      setTimeout(async () => {
        await loadDMRooms();
        const r2 = dmRooms.find(r => r.room_id === data.room_id);
        if (r2) openDMChat(r2);
      }, 500);
    }
    return;
  }

  // 그룹: 멤버 토글
  const idx = dmSelectedMembers.findIndex(s => s.mem_id === member.mem_id);
  if (idx >= 0) {
    dmSelectedMembers.splice(idx, 1);
  } else {
    dmSelectedMembers.push(member);
  }
  renderSelectedMembers();
  searchDMMembers(document.getElementById('dmMemberSearch').value);
}

function renderSelectedMembers() {
  const el = document.getElementById('dmSelectedMembers');
  el.innerHTML = '';
  dmSelectedMembers.forEach(m => {
    const chip = document.createElement('span');
    chip.className = 'dm-selected-chip';
    chip.innerHTML = escHtml(m.mem_name) + ' <i class="fas fa-times" style="cursor:pointer;" onclick="removeDMSelectedMember(\'' + m.mem_id + '\')"></i>';
    el.appendChild(chip);
  });
}

function removeDMSelectedMember(mem_id) {
  dmSelectedMembers = dmSelectedMembers.filter(m => m.mem_id !== mem_id);
  renderSelectedMembers();
}

async function createGroupChat() {
  const name = document.getElementById('dmGroupNameInput').value.trim();
  if (!name) { alert('그룹 이름을 입력해주세요.'); return; }
  if (dmSelectedMembers.length === 0) { alert('멤버를 선택해주세요.'); return; }

  const data = await _dmPost('dm_create.php', {
    type: 'group',
    name,
    members: dmSelectedMembers.map(m => m.mem_id),
  });
  if (!data?.success) { alert('그룹 생성 실패'); return; }
  closeDMSearchModal();
  await loadDMRooms();
  const room = dmRooms.find(r => r.room_id === data.room_id);
  if (room) openDMChat(room);
}

// ── 투표 모달 ────────────────────────────────────────────────
function openPollModal() {
  if (!dmCurrentRoom) return;
  document.getElementById('dmPollQuestion').value = '';
  document.getElementById('dmPollOptions').innerHTML =
    '<input type="text" class="dm-poll-option" placeholder="선택지 1" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg-input);color:var(--text);font-size:13px;margin-bottom:8px;box-sizing:border-box;" />' +
    '<input type="text" class="dm-poll-option" placeholder="선택지 2" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg-input);color:var(--text);font-size:13px;margin-bottom:8px;box-sizing:border-box;" />';
  document.getElementById('dmPollMultiple').checked = false;
  document.getElementById('dmPollModal').style.display = 'flex';
}

async function submitPoll() {
  const question = document.getElementById('dmPollQuestion').value.trim();
  const options = Array.from(document.querySelectorAll('.dm-poll-option')).map(i => i.value.trim()).filter(v => v);
  const is_multiple = document.getElementById('dmPollMultiple').checked;

  if (!question) { alert('질문을 입력해주세요.'); return; }
  if (options.length < 2) { alert('선택지를 2개 이상 입력해주세요.'); return; }

  const data = await _dmPost('dm_messages.php', {
    room_id: dmCurrentRoom.room_id,
    content: question,
    msg_type: 'poll',
    poll_options: options,
    poll_multiple: is_multiple,
  });
  if (data?.success) {
    document.getElementById('dmPollModal').style.display = 'none';
    pollDMMessages();
  }
}

// ── 유틸 ─────────────────────────────────────────────────────
function formatDateLabel(dateStr) {
  const d = new Date(dateStr);
  const today = new Date();
  const diff = Math.floor((today - d) / 86400000);
  if (diff === 0) return '오늘';
  if (diff === 1) return '어제';
  return dateStr;
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + 'B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + 'KB';
  return (bytes / 1048576).toFixed(1) + 'MB';
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text);
    showToast('복사되었습니다');
  } catch (e) {
    showToast('복사 실패');
  }
}

// ── 채팅 탭 진입 시 DM 목록 로드 ────────────────────────────
// main.js의 switchTab에서 'chat' 탭 진입 시 호출
function onDMTabActivated() {
  loadDMRooms();
  // 30초마다 목록 갱신
  if (window._dmRoomRefreshTimer) clearInterval(window._dmRoomRefreshTimer);
  window._dmRoomRefreshTimer = setInterval(loadDMRooms, 30000);
}

function onDMTabDeactivated() {
  if (window._dmRoomRefreshTimer) { clearInterval(window._dmRoomRefreshTimer); }
  stopDMPolling();
}

// DOM 준비 후 초기화
document.addEventListener('DOMContentLoaded', initDM);
