/**
 * 원챗 API 연동 모듈 v7
 * 버그 수정:
 *  1. 발신/수신 리스트 → 채팅 후 채팅탭 반영 (클릭 리스너 교체 방식으로 수정)
 *  2. 개별 채팅 기록 저장
 *  3. 챗봇 자동응답 오동작 방지 (avatarPausedByPeer 초기화, peerSimBar 복원)
 *  4. 챗봇 이미 보유 시 중복 생성 차단 (버그 4: makeBotBtn 등 3개 리스너 교체)
 */

const ONECHAT_API = './api';

// ─────────────────────────────────────────
// 로그아웃
// ─────────────────────────────────────────
function doLogout() {
    if (!confirm('로그아웃 하시겠습니까?')) return;
    fetch('/ajax/logout.php', { credentials: 'include' })
        .finally(() => { window.top.location.href = '/iam/login.php'; });
}

// ─────────────────────────────────────────
// 상태
// ─────────────────────────────────────────
let chatPage = 1, chatQuery = '', chatHasMore = true, chatLoading = false;
let contactPage = 1, contactQuery = '', contactHasMore = true, contactLoading = false;
let contactFilter = 'all';
let contactSmsIdx = 0;
let currentChatInfo = null; // { sms_idx, request_idx, person }
let currentMemId = ''; // me.php에서 받은 로그인 mem_id (메모리만 유지)
let mySmsIdx = 0;      // 내 챗봇 sms_idx (me.php에서 자동 설정, 메모리만 유지)

// ─────────────────────────────────────────
// API 공통
// ─────────────────────────────────────────
async function apiGet(endpoint, params = {}) {
    const url = new URL(ONECHAT_API + '/' + endpoint, location.href);
    Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    try {
        const res = await fetch(url, { credentials: 'include' });
        if (res.status === 401) {
            // 비회원 상태면 리다이렉트 대신 null 반환
            if (typeof userState !== 'undefined' && userState === 'guest') return null;
            window.top.location.href = '/iam/login.php'; return null;
        }
        return res.json();
    } catch(e) { console.error('[API] GET:', e); return null; }
}

async function apiPost(endpoint, body = {}) {
    try {
        const res = await fetch(ONECHAT_API + '/' + endpoint, {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (res.status === 401) {
            // 비회원 상태면 리다이렉트 대신 null 반환
            if (typeof userState !== 'undefined' && userState === 'guest') return null;
            window.top.location.href = '/iam/login.php'; return null;
        }
        return res.json();
    } catch(e) { console.error('[API] POST:', e); return null; }
}

function colorFromId(id) {
    // 숫자형이면 바로 사용, 문자열이면 char code 합산으로 해시
    const n = typeof id === 'number' ? id : String(id).split('').reduce((a, c) => a + c.charCodeAt(0), 0);
    return 'color-' + ((Math.abs(n) % 8) + 1);
}

function apiItemToPerson(item) {
    return {
        id: item.id, name: item.name || '', position: item.position || '',
        phone: item.phone || '', regDate: item.regDate || '',
        chatCount: item.chatCount || 0, unreadCount: item.unreadCount || 0,
        lastChat: item.lastChat || null, shortUrl: item.shortUrl || '',
        colorClass: colorFromId(item.id), messages: [],
        profile: item.profile || '',
    };
}

// ─────────────────────────────────────────
// ① 채팅 탭 목록
// ─────────────────────────────────────────
async function renderChatListFromAPI(query = '', reset = true) {
    if (!chatList) return; // DM 탭이 기존 채팅 탭을 대체한 경우 chatList가 없음
    if (chatLoading) return;
    chatLoading = true;
    if (reset) { chatPage = 1; chatQuery = query; chatHasMore = true; chatList.innerHTML = ''; }

    const data = await apiGet('list.php', { page: chatPage, q: chatQuery });
    chatLoading = false;
    if (!data || !data.success) return;

    chatHasMore = data.has_more;
    if (typeof chatBadge !== 'undefined') chatBadge.textContent = `${data.total}명`;

    if (data.list.length === 0 && chatPage === 1) {
        showEmpty(chatList, 'fas fa-comment-slash',
            chatQuery ? '검색 결과가 없습니다' : '아직 대화한 사람이 없어요',
            chatQuery ? '' : '명함리스트에서 챗봇 URL을 공유해보세요'
        );
        return;
    }

    data.list.forEach((item, idx) => {
        const li = document.createElement('li');
        li.className = 'chat-item has-chat';
        li.dataset.id = item.id;
        li.dataset.smsIdx = item.sms_idx;
        li.style.animationDelay = `${idx * 0.04}s`;

        // 닉네임 우선순위 적용 (visitor 카드와 동일)
        if (item.source === 'visitor' || item.visitor_id) {
            const rn = (item.custom_name && item.custom_name.trim())
                ? item.custom_name.trim()
                : (item.nickname && item.nickname.trim())
                    ? item.nickname.trim()
                    : (item.visitor_id && typeof generateNickname === 'function')
                        ? generateNickname(item.visitor_id)
                        : item.name;
            if (rn) item.name = rn;
        }

        const timeLabel = formatRelativeDate(item.lastChat);
        const prefix = (item.lastRole === 'sender' || item.lastRole === 'operator') ? '나: '
                     : (item.lastRole === 'bot' || item.lastRole === 'assistant') ? '🤖 ' : '';
        const preview = item.lastMsg ? prefix + item.lastMsg.split('\n')[0].slice(0, 28) : '';
        const unread  = item.unreadCount || 0;
        const cc      = colorFromId(item.id);

        li.innerHTML = `
          <div class="profile-wrap">
            <div class="profile-img ${cc}">
              ${(item.profile||'').trim() ? `<img class="profile-photo" src="https://kiam.kr${(item.profile||'').trim()}" alt="${(item.name||'?').charAt(0)}" onerror="this.style.display='none'">` : ''}${(item.profile||'').trim() ? '' : (item.name||'?').charAt(0)}
            </div>
            ${unread > 0
              ? `<div class="chat-count-dot unread">${unread}</div>`
              : `<div class="chat-count-dot read"><i class="fas fa-check"></i></div>`}
          </div>
          <div class="chat-info">
            <div class="chat-name-row">
              <div class="chat-name ${unread > 0 ? 'has-unread' : ''}">${item.name}</div>
              ${item.shortUrl ? `<span class="chatlink-chip" onclick="copyBotLink(event,'https://chatbot.kiam.kr${item.shortUrl}')">챗봇 링크 <i class="fas fa-copy"></i></span>` : ''}
            </div>
            <div class="chat-position">${item.position}</div>
            <div class="chat-preview ${unread > 0 ? 'unread-preview' : ''}">${preview}</div>
          </div>
          <div class="chat-meta">
            <span class="chat-time">${timeLabel}</span>
            ${unread > 0
              ? `<span class="chat-badge unread-badge">답변필요 ${unread}</span>`
              : `<span class="chat-badge done-badge">완료</span>`}
          </div>
          <i class="fas fa-chevron-right chat-arrow"></i>`;

        li.addEventListener('click', () => openChatScreenFromAPI(item));
        chatList.appendChild(li);
    });
    chatPage++;
}

// ─────────────────────────────────────────
// ② 명함리스트 탭
// ─────────────────────────────────────────

// 외부(main.js)에서 호출 가능한 필터 전환 함수
function setContactFilter(filter) {
    contactFilter = filter;
}

async function renderContactsListFromAPI(query = '', reset = true) {
    const contactsList = document.getElementById('contactsList');
    if (!contactsList) { console.warn('[API] contactsList not found'); return; }
    if (contactLoading && !reset) return;
    if (reset) { contactPage = 1; contactQuery = query; contactHasMore = true; }
    contactLoading = true;

    let data;
    try {
        const params = { page: contactPage, q: contactQuery, filter: contactFilter };
        if (contactSmsIdx > 0) params.sms_idx = contactSmsIdx;
        data = await apiGet('contacts.php', params);
    } finally {
        contactLoading = false;
    }
    if (!data || !data.success) return;

    // reset 시 기존 카드 지우기 (API 응답 후 지워야 깜빡임 방지)
    if (reset) contactsList.innerHTML = '';

    contactHasMore = data.has_more;

    // 통계 바 업데이트
    if (contactPage === 1 && data.stat) {
        const _st = document.getElementById('statTotal');
        const _sa = document.getElementById('statActive');
        const _si = document.getElementById('statInactive');
        if (_st) _st.textContent = data.total + '명';
        if (_sa) _sa.textContent = (data.stat.chatted || 0) + '명';
        if (_si) _si.textContent = (data.stat.unused  || 0) + '명';

        // 🔗 링크방문 통계 (공유탭 전용)
        const _sv  = document.getElementById('statVisitor');
        const _svc = document.getElementById('statVisitorChatted');
        const _svn = document.getElementById('statVisitorNone');
        if (_sv)  _sv.textContent  = (data.stat.visitor || 0) + '명';
        if (_svc) _svc.textContent = (data.stat.visitor_chatted || 0) + '명';
        if (_svn) _svn.textContent = (data.stat.visitor_none    || 0) + '명';

        // appBadge (헤더 배지)
        const _ab = document.getElementById('appBadge');
        if (_ab) _ab.textContent = data.total + '명';
    }

    data.list.forEach((item, idx) => {
        // buildContactCard 없으면 직접 생성
        let card;
        if (typeof buildContactCard === 'function') {
            card = buildContactCard(apiItemToPerson(item), idx);
        }
        if (!card) return;

        // cloneNode 대신 원본 카드 사용 (cloneNode는 인라인 onclick은 복사되나 addEventListener는 복사 안 됨)
        // chatlink-chip 버튼 클릭이 카드 전체 클릭으로 전파되지 않도록 차단
        const chips = card.querySelectorAll('.chatlink-chip');
        chips.forEach(chip => {
            chip.addEventListener('click', e => e.stopPropagation());
        });

        // 카드 클릭 → 채팅창 열기
        card.addEventListener('click', () => {
            console.log('[API] 카드 클릭:', item.source, item.id, item.name);
            openChatScreenFromAPI(item);
        });
        contactsList.appendChild(card);
    });
    contactPage++;
}

// ─────────────────────────────────────────
// ③ 채팅룸 열기
// ─────────────────────────────────────────
async function openChatScreenFromAPI(item) {
    console.log('[CHAT] openChatScreenFromAPI 시작', item.source, item.id, item.name);
    const person = apiItemToPerson(item);

    // ── visitor source: visitor_list.php?mode=history 로 채팅 이력 조회 ──
    if (item.source === 'visitor' && item.visitor_id) {
        currentChatInfo = { sms_idx: item.sms_idx, request_idx: item.id, person,
                            source: 'visitor', visitor_id: item.visitor_id };
        window.currentChatInfo = currentChatInfo;

        if (typeof avatarPausedByPeer !== 'undefined') avatarPausedByPeer = false;
        const banner = document.getElementById('directWaitBanner');
        if (banner) banner.style.display = 'none';
        const simBar = document.getElementById('peerSimBar');
        if (simBar) simBar.style.display = 'none'; // visitor 채팅엔 시뮬 불필요

        person.messages = [];
        // 닉네임 우선: custom_name → nickname(자동생성) → visitor_id 순
        person.name     = item.custom_name || item.nickname || item.name || item.visitor_id;
        person.botName  = item.chatbot_name || '챗봇';
        openChatScreen(person);

        // visitor 채팅 — chatScreen에 visitor 정보 설정 (운영자 답장용)
        const _cs = document.getElementById('chatScreen');
        if (_cs) {
            _cs.dataset.visitorId     = item.visitor_id;
            _cs.dataset.visitorSmsIdx = String(item.sms_idx);
            // 수신탭에서 열었는지 기록 → closeChat 시 수신탭으로 복귀
            _cs.dataset.callerTab = item.callerTab || '';
        }
        // 봇 토글 복원 (visitor에게 운영자가 직접 답장 가능)
        const botToggleWrap = document.getElementById('botToggleWrap');
        if (botToggleWrap) botToggleWrap.style.display = '';

        const data = await apiGet('visitor_list.php', {
            mode: 'history', visitor_id: item.visitor_id, sms_idx: item.sms_idx
        });
        if (!data || !data.success) {
            console.warn('[API] visitor_list.php history 실패:', data);
            const cm = document.getElementById('chatMessages');
            if (cm) cm.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">대화 이력이 없습니다</div>';
            return;
        }
        // ★ history 응답에서 닉네임 정보를 받아 헤더 이름 업데이트
        if (data.custom_name || data.nickname) {
            const resolvedName = data.custom_name || data.nickname;
            person.name = resolvedName;
            const cspNameEl = document.getElementById('cspName');
            if (cspNameEl) cspNameEl.textContent = resolvedName;
            const cspAvatarEl = document.getElementById('cspAvatar');
            if (cspAvatarEl) cspAvatarEl.textContent = resolvedName.charAt(0);
        }
        if (data.messages && data.messages.length > 0) {
            const msgs = data.messages.map(m => ({
                type: m.role === 'user' ? 'user' : (m.role === 'operator' ? 'notice' : 'bot'),
                text: m.message,
                time: m.time,
            }));
            person.messages = msgs;
            if (typeof renderMessages === 'function') renderMessages(msgs);
            const cm = document.getElementById('chatMessages');
            if (cm) cm.scrollTop = cm.scrollHeight;
        } else {
            const cm = document.getElementById('chatMessages');
            if (cm) cm.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">아직 대화 내역이 없습니다</div>';
        }
        return;
    }

    // ── funnel source (기존 로직) ──
    console.log('[CHAT] funnel 분기 진입, request_idx:', item.id, 'sms_idx:', item.sms_idx);
    currentChatInfo = { sms_idx: item.sms_idx, request_idx: item.id, person, source: 'funnel' };
    window.currentChatInfo = currentChatInfo;

    if (typeof avatarPausedByPeer !== 'undefined') avatarPausedByPeer = false;
    const banner = document.getElementById('directWaitBanner');
    if (banner) banner.style.display = 'none';
    const simBar = document.getElementById('peerSimBar');
    if (simBar) simBar.style.display = '';
    // 봇 토글 복원
    const botToggleWrap = document.getElementById('botToggleWrap');
    if (botToggleWrap) botToggleWrap.style.display = '';

    person.messages = [];
    console.log('[CHAT] openChatScreen 호출 전');
    openChatScreen(person);
    console.log('[CHAT] openChatScreen 호출 후');

    const botOn = true;
    isBotOn = botOn;
    if (typeof botToggle !== 'undefined') botToggle.checked = botOn;
    if (typeof updateBotUI === 'function') updateBotUI();

    console.log('[CHAT] messages.php 요청 시작');
    const data = await apiGet('messages.php', { request_idx: item.id, sms_idx: item.sms_idx });
    console.log('[CHAT] messages.php 응답:', data ? data.success : 'null');
    if (!data || !data.success) {
        console.warn('[API] messages.php 응답 실패:', data);
        const cm = document.getElementById('chatMessages');
        if (cm) cm.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">대화 이력 로드 실패</div>';
        return;
    }
    if (data.messages && data.messages.length > 0) {
        person.messages = data.messages;
        const cm = document.getElementById('chatMessages');
        console.log('[CHAT] renderMessages 호출, 메시지수:', data.messages.length);
        if (typeof renderMessages === 'function') renderMessages(data.messages);
        if (cm) cm.scrollTop = cm.scrollHeight;
    } else {
        const cm = document.getElementById('chatMessages');
        if (cm) cm.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">아직 대화 내역이 없습니다</div>';
    }
}

// ─────────────────────────────────────────
// ④ sendWithAttachments 패치
// ★ 버그1,2 핵심 수정:
//   - 클릭 리스너를 교체해야 클릭 버튼도 패치 적용됨
//   - Enter 키는 window.sendWithAttachments()를 동적 호출하므로 자동 적용
// ─────────────────────────────────────────
function patchSendWithAttachments() {
    const _orig = window.sendWithAttachments;
    if (typeof _orig !== 'function') {
        console.warn('[API] sendWithAttachments 미발견, 재시도');
        setTimeout(patchSendWithAttachments, 200);
        return;
    }

    const newFn = async function() {
        // ★ 원본 호출 전에 텍스트 캡처 (orig 실행 후 textarea가 초기화됨)
        const textarea = document.getElementById('ciTextarea');
        const text = textarea ? textarea.value.trim() : '';

        // 원본 UI 처리 실행 (visitor 채팅 저장도 main.js 내부에서 처리)
        _orig.call(this);

        // ★ DB 저장 (funnel 채팅만 여기서 처리; visitor는 main.js가 담당)
        if (currentChatInfo && text) {
            const { sms_idx, request_idx, source, visitor_id } = currentChatInfo;
            if (!(source === 'visitor' && visitor_id)) {
                // funnel 채팅: chatbot_history에 sender 역할로 저장
                await apiPost('messages.php', { sms_idx, request_idx, message: text });
            }
            // ★ 채팅 목록 갱신 (처음 채팅 시 채팅탭에 반영)
            renderChatListFromAPI(chatQuery, true);
        }
    };

    window.sendWithAttachments = newFn;

    // ★ main.js가 () => window.sendWithAttachments() 간접 호출로 등록되어 있으므로
    //   window.sendWithAttachments 교체만으로 충분. 버튼 리스너 별도 조작 불필요.
    console.log('[API] sendWithAttachments 패치 완료');
}

// ─────────────────────────────────────────
// ⑤ 상대방 메시지 + 챗봇 응답 DB 저장
// peerMsgBtn 클릭 시:
//   1) 상대방 메시지 → role=receiver 로 DB 저장
//   2) 1300ms 후 챗봇 응답 → role=bot 으로 DB 저장
//   (main.js 가 1200ms timeout 으로 UI 추가하므로 살짝 뒤에 저장)
// ─────────────────────────────────────────
function hookPeerMsgBtn() {
    const btn = document.getElementById('peerMsgBtn');
    if (!btn || btn.dataset.apiHooked) return;
    btn.dataset.apiHooked = '1';

    btn.addEventListener('click', () => {
        if (!currentChatInfo) return;
        const { sms_idx, request_idx, source, visitor_id } = currentChatInfo;
        const isVisitor = (source === 'visitor' && visitor_id);
        const cm = document.getElementById('chatMessages');
        if (!cm) return;

        // ① main.js가 DOM에 메시지 추가할 때까지 50ms 대기 후 마지막 user 버블 읽기
        setTimeout(() => {
            const userBubbles = cm.querySelectorAll('.msg-row.user .msg-bubble');
            const peerText = userBubbles.length > 0
                ? userBubbles[userBubbles.length - 1].textContent.trim()
                : '';
            console.log('[API] peerMsgBtn: peerText=', peerText);
            if (!peerText) return;

            // DB에 상대방 메시지 저장
            if (isVisitor) {
                // visitor 채팅: visitor_chat_log에 user 역할로 저장
                apiPost('visitor_list.php', { mode: 'save_msg', visitor_id, sms_idx, message: peerText, role: 'user' })
                    .then(() => renderChatListFromAPI(chatQuery, true));
            } else {
                apiPost('messages.php', { sms_idx, request_idx, message: peerText, role: 'receiver' })
                    .then(() => renderChatListFromAPI(chatQuery, true));
            }

            // ② 봇 켜져 있고 직접대화 일시정지 아닐 때: 1400ms 후 마지막 bot 버블 읽기
            const botToggle = document.getElementById('botToggle');
            const botIsOn = botToggle ? botToggle.checked : (typeof isBotOn !== 'undefined' && isBotOn);
            const paused  = typeof avatarPausedByPeer !== 'undefined' && avatarPausedByPeer;
            if (botIsOn && !paused) {
                setTimeout(() => {
                    const botBubbles = cm.querySelectorAll('.msg-row.bot .msg-bubble');
                    const botText = botBubbles.length > 0
                        ? botBubbles[botBubbles.length - 1].textContent.trim()
                        : '';
                    console.log('[API] peerMsgBtn: botText=', botText);
                    if (!botText) return;
                    if (isVisitor) {
                        // visitor 채팅: visitor_chat_log에 assistant 역할로 저장
                        apiPost('visitor_list.php', { mode: 'save_msg', visitor_id, sms_idx, message: botText, role: 'assistant' })
                            .then(() => renderChatListFromAPI(chatQuery, true));
                    } else {
                        apiPost('messages.php', { sms_idx, request_idx, message: botText, role: 'bot' })
                            .then(() => renderChatListFromAPI(chatQuery, true));
                    }
                }, 1400);
            }
        }, 50);
    });
}

// ─────────────────────────────────────────
// ⑤-b 봇 토글 DB 저장
// ─────────────────────────────────────────
function hookBotToggle() {
    const toggle = document.getElementById('botToggle');
    if (!toggle) return;
    if (toggle.dataset.apiHooked) return;
    toggle.dataset.apiHooked = '1';
    toggle.addEventListener('change', () => {
        if (!currentChatInfo) return;
        apiPost('bot_toggle.php', {
            sms_idx: currentChatInfo.sms_idx,
            request_idx: currentChatInfo.request_idx,
            bot_on: toggle.checked ? 1 : 0
        });
    });
}

// ─────────────────────────────────────────
// ⑥ 챗봇 중복 생성 차단
// ★ 버그4 수정:
//   window.openMakeBotPanel 교체만으로는 안 됨.
//   makeBotBtn, addUserBtnChat, addUserBtnContacts 모두
//   원본 레퍼런스로 addEventListener 등록됨 → 직접 교체 필요.
// ─────────────────────────────────────────
function patchMakeBotPanel() {
    const _orig = window.openMakeBotPanel;
    if (typeof _orig !== 'function') {
        console.warn('[API] openMakeBotPanel 미발견, 재시도');
        setTimeout(patchMakeBotPanel, 200);
        return;
    }

    const newFn = function() {
        if (typeof userState !== 'undefined' && userState === 'member-with-bot') {
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);' +
                'background:#1e293b;color:#fff;padding:10px 20px;border-radius:20px;' +
                'font-size:13px;z-index:9999;white-space:nowrap;box-shadow:0 4px 12px rgba(0,0,0,0.3);';
            toast.textContent = '⚠️ 이미 챗봇을 소유하고 있습니다';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
            return;
        }
        _orig();
    };

    window.openMakeBotPanel = newFn;

    // ★ 핵심: addEventListener로 원본 레퍼런스가 등록된 버튼 3개 모두 교체
    ['makeBotBtn', 'addUserBtnChat', 'addUserBtnContacts'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.removeEventListener('click', _orig);
            btn.addEventListener('click', newFn);
            console.log('[API] ' + id + ' 클릭 리스너 교체 완료');
        }
    });
}

// ─────────────────────────────────────────
// ⑦ 대시보드 API
// ─────────────────────────────────────────
async function loadDashboardFromAPI() {
    const data = await apiGet('dashboard.php');
    if (!data || !data.success) return;
    const map = {
        '.dash-total': data.total_receivers, '.dash-chatted': data.chatted,
        '.dash-unused': data.unused, '.dash-rate': data.response_rate + '%',
    };
    Object.entries(map).forEach(([sel, val]) => {
        const el = document.querySelector(sel);
        if (el) el.textContent = val;
    });
    const gauge = document.querySelector('.dash-gauge-fill');
    if (gauge) gauge.style.width = data.response_rate + '%';
}

// ─────────────────────────────────────────
// ⑧ 무한 스크롤
// ─────────────────────────────────────────
function initInfiniteScroll() {
    // screenChat 스크롤: dm.js의 dmRoomList가 처리하므로 여기서는 등록하지 않음
    const ct = document.getElementById('screenContacts');
    if (ct) ct.addEventListener('scroll', () => {
        if (ct.scrollHeight - ct.scrollTop - ct.clientHeight < 120)
            if (contactHasMore && !contactLoading) renderContactsListFromAPI(contactQuery, false);
    });
    const sr = document.getElementById('screenReceived');
    if (sr) sr.addEventListener('scroll', () => {
        if (sr.scrollHeight - sr.scrollTop - sr.clientHeight < 120)
            if (receivedHasMore && !receivedLoading) renderReceivedListFromAPI(receivedQuery, false);
    });
}

// ─────────────────────────────────────────
// ⑨ 검색
// ─────────────────────────────────────────
function initSearch() {
    // chatSearchInput: dm.js가 처리하므로 여기서는 등록하지 않음
    const co = document.getElementById('contactsSearchInput');
    if (co) {
        let t;
        co.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => renderContactsListFromAPI(co.value.trim(), true), 300);
        });
    }
    // receivedSearchInput은 main.js에서 renderVisitorList로 처리
}

// ─────────────────────────────────────────
// ⑩ 수신리스트 탭 - 실제 DB 데이터 연동
// ★ data.js의 mock receivedData 대신 contacts.php 실제 데이터를 receivedList에 렌더
// ─────────────────────────────────────────
let receivedPage = 1, receivedQuery = '', receivedHasMore = true, receivedLoading = false;

async function renderReceivedListFromAPI(query = '', reset = true) {
    const receivedList = document.getElementById('receivedList');
    if (!receivedList) return;
    if (receivedLoading) return;
    receivedLoading = true;
    if (reset) { receivedPage = 1; receivedQuery = query; receivedHasMore = true; receivedList.innerHTML = ''; }

    const data = await apiGet('contacts.php', { page: receivedPage, q: receivedQuery });
    receivedLoading = false;
    if (!data || !data.success) return;

    receivedHasMore = data.has_more;

    const receivedBadge = document.getElementById('receivedBadge');
    if (receivedBadge) receivedBadge.textContent = `${data.total}명`;

    if (data.list.length === 0 && receivedPage === 1) {
        if (typeof showEmpty === 'function') {
            showEmpty(receivedList, 'fas fa-inbox',
                receivedQuery ? '검색 결과가 없습니다' : '수신자가 없습니다', '');
        }
        return;
    }

    data.list.forEach((item, idx) => {
        const li = document.createElement('div');
        li.className = 'contact-card received-card' + (item.chatCount > 0 ? ' has-chat' : '');
        li.style.animationDelay = `${idx * 0.03}s`;

        // 닉네임 우선순위: custom_name → nickname(DB한글) → generateNickname(해시한글) → name
        const resolvedName = (item.custom_name && item.custom_name.trim())
            ? item.custom_name.trim()
            : (item.nickname && item.nickname.trim())
                ? item.nickname.trim()
                : (item.visitor_id && typeof generateNickname === 'function')
                    ? generateNickname(item.visitor_id)
                    : (item.name || '방문자');
        item.name = resolvedName; // 이후 참조를 위해 덮어쓰기

        const timeLabel = typeof formatRelativeDate === 'function'
            ? formatRelativeDate(item.lastChat) : (item.lastChat || '');
        const cc = colorFromId(item.id);
        const initial = resolvedName.charAt(0);
        const unread  = item.unreadCount || 0;

        // 프로필 사진: 있으면 사진, 없으면 이니셜+그라디언트
        const profilePath2 = item.profile ? item.profile.trim() : '';
        const photoHtml2   = profilePath2
            ? `<img class="cc-avatar-photo" src="https://kiam.kr${profilePath2}" alt="${initial}"
                   onerror="this.style.display='none'">`
            : '';

        li.innerHTML = `
          <div class="cc-avatar ${cc}" style="position:relative;">
            ${photoHtml2}${photoHtml2 ? '' : initial}
            <div class="received-bot-badge" title="AI 챗봇"><i class="fas fa-robot"></i></div>
            ${item.chatCount > 0 ? '<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>' : ''}
          </div>
          <div class="cc-info" style="flex:1;min-width:0;">
            <div class="cc-name-row">
              <span class="cc-name${unread > 0 ? ' has-unread' : ''}">${item.name}</span>
              ${item.shortUrl ? `<span class="chatlink-chip" onclick="copyBotLink(event,'https://chatbot.kiam.kr${item.shortUrl}')">챗봇 링크 <i class="fas fa-copy"></i></span>` : ''}
              ${unread > 0
                ? `<span class="cc-chat-badge unread" style="background:rgba(239,68,68,0.12);color:#ef4444;">${unread}개 미읽음</span>`
                : item.chatCount > 0
                  ? `<span class="cc-chat-badge"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:3px;"></i>${item.chatCount}회</span>`
                  : '<span class="cc-unused-tag">미대화</span>'}
            </div>
            <div class="cc-position" style="font-size:11px;color:var(--accent-blue);font-weight:600;margin-bottom:2px;">
              <i class="fas fa-robot" style="margin-right:3px;font-size:10px;"></i>${item.chatbot_name || ''}
            </div>
            <div class="cc-position">${item.position || ''}</div>
            ${item.chatCount > 0 ? `<div class="cc-preview-text">${item.phone || ''}</div>` : ''}
          </div>
          <div class="cc-right">
            <span class="chat-time" style="font-size:10px;color:var(--text-muted);">${timeLabel}</span>
            <i class="fas fa-chevron-right cc-arrow" style="margin-top:6px;"></i>
          </div>`;

        li.addEventListener('click', () => openChatScreenFromAPI(item));
        receivedList.appendChild(li);
    });
    receivedPage++;
}


// ─────────────────────────────────────────
// ④ 수신 탭: received_list.php 연동 (renderInboxFromAPI)
//    - 로그인 회원: mem_id 기반 (모든 기기 통합)
//    - 비로그인: 비로그인 안내 표시
// ─────────────────────────────────────────
let inboxPage = 1, inboxQuery = '', inboxHasMore = true, inboxLoading = false;
let inboxCurrentFilter = 'all'; // all | chatted | none | dm

function inboxFilter(btn, filter) {
    inboxCurrentFilter = filter;
    document.querySelectorAll('.inbox-filter-chip').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderInboxFromAPI('', true);
}

async function renderInboxFromAPI(query = '', reset = true) {
    const receivedList = document.getElementById('receivedList');
    if (!receivedList) return;

    // 비로그인 상태 처리
    const isGuest = (typeof userState !== 'undefined' && userState === 'guest');
    if (isGuest) {
        receivedList.innerHTML = `
          <div class="guest-empty-wrap">
            <div class="guest-empty-icon"><i class="fas fa-inbox"></i></div>
            <div class="guest-empty-title">수신함을 이용하려면<br>로그인이 필요해요</div>
            <div class="guest-empty-desc">로그인하면 다른 운영자의 챗봇에서<br>나에게 온 메시지를 한 곳에서 확인할 수 있어요.</div>
            <button class="guest-empty-btn" onclick="window.top.location.href='/iam/login.php'">
              <i class="fas fa-sign-in-alt"></i> 로그인하기
            </button>
          </div>`;
        return;
    }

    if (inboxLoading && !reset) return;
    inboxLoading = true;
    if (reset) { inboxPage = 1; inboxQuery = query; inboxHasMore = true; receivedList.innerHTML = ''; }

    // global_visitor_id: 쿠키에서만 읽기
    const gvid = document.cookie.split(';').map(c=>c.trim())
        .find(c=>c.startsWith('global_visitor_id='))?.split('=')[1] || '';

    const params = { page: inboxPage, q: inboxQuery, filter: inboxCurrentFilter };
    if (gvid) params.global_visitor_id = gvid;
    // 세션이 끊겨도 mem_id를 직접 파라미터로 전달 (리프레시 후 세션 만료 대비)
    if (currentMemId) params.mem_id = currentMemId;
    console.log('[inbox] 요청 params:', JSON.stringify(params), '| currentMemId:', currentMemId);
    const data = await apiGet('received_list.php', params);
    console.log('[inbox] 응답:', data ? ('total='+data.total+', list='+data.list?.length) : 'null');
    inboxLoading = false;
    if (!data || !data.success) {
        if (inboxPage === 1 && typeof showEmpty === 'function') {
            showEmpty(receivedList, 'fas fa-inbox', '수신된 메시지가 없습니다', '다른 운영자의 챗봇을 방문하면 여기에 표시돼요');
        }
        return;
    }

    inboxHasMore = data.has_more || false;

    // 통계 업데이트
    const _it = document.getElementById('inboxStatTotal');
    const _ic = document.getElementById('inboxStatChatted');
    const _in = document.getElementById('inboxStatNone');
    if (inboxPage === 1) {
        if (_it) _it.textContent = (data.total || 0) + '명';
        if (_ic && data.stat) _ic.textContent = (data.stat.chatted || 0) + '명';
        if (_in && data.stat) _in.textContent = (data.stat.none    || 0) + '명';
    }

    if ((!data.list || data.list.length === 0) && inboxPage === 1) {
        if (typeof showEmpty === 'function') {
            showEmpty(receivedList, 'fas fa-inbox',
                inboxQuery ? '검색 결과가 없습니다' : '수신된 챗봇 메시지가 없어요',
                inboxQuery ? '' : '다른 운영자의 챗봇을 방문하면 여기에 표시돼요');
        }
        return;
    }

    (data.list || []).forEach((item, idx) => {
        const card = buildInboxCard(item, idx);
        receivedList.appendChild(card);
    });
    inboxPage++;
}

function buildInboxCard(item, idx) {
    const el = document.createElement('div');
    el.className = 'contact-card received-card inbox-card' + (item.unread_count > 0 ? ' has-unread' : '');
    el.style.animationDelay = `${idx * 0.03}s`;

    const timeLabel = typeof formatRelativeDate === 'function'
        ? formatRelativeDate(item.last_chat_at || item.subscribed_at)
        : (item.last_chat_at || '');
    const cc      = typeof colorFromId === 'function' ? colorFromId(parseInt(item.id) || 0) : 'color-1';
    const initial = (item.chatbot_name || '?').charAt(0).toUpperCase();
    const unread  = item.unread_count || 0;
    const lastMsg = item.last_message
        ? (item.last_role === 'user' ? '나: ' : '🤖 ') + String(item.last_message).split('\n')[0].slice(0, 28)
        : '아직 대화 없음';
    const isDm    = item.moved_to_dm == 1;

    // 프로필 사진: 있으면 사진, 없으면 이니셜+그라디언트
    const profilePath = item.operator_profile ? item.operator_profile.trim() : '';
    const photoHtml   = profilePath
        ? `<img class="cc-avatar-photo" src="https://kiam.kr${profilePath}" alt="${initial}"
               onerror="this.style.display='none'">`
        : '';

    el.innerHTML = `
      <div class="cc-avatar ${cc}" style="position:relative;">
        ${photoHtml}${photoHtml ? '' : initial}
        <div class="received-bot-badge" title="AI 챗봇"><i class="fas fa-robot"></i></div>
      </div>
      <div class="cc-info" style="flex:1;min-width:0;">
        <div class="cc-name-row">
          <span class="cc-name${unread > 0 ? ' has-unread' : ''}">${escHtml(item.chatbot_name || '챗봇')}</span>
          ${unread > 0
            ? `<span class="cc-chat-badge unread" style="background:rgba(239,68,68,0.12);color:#ef4444;">${unread}개 미읽음</span>`
            : isDm
              ? `<span class="cc-chat-badge" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><i class="fas fa-comment-dots" style="font-size:9px;margin-right:2px;"></i>DM전환</span>`
              : ''}
        </div>
        <div class="cc-position" style="font-size:11px;color:var(--accent-blue);font-weight:600;margin-bottom:2px;">
          <i class="fas fa-user" style="margin-right:3px;font-size:10px;"></i>${escHtml(item.operator_name || item.operator_id || '')}
        </div>
        <div class="cc-preview-text">${escHtml(lastMsg)}</div>
      </div>
      <div class="cc-right" style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
        <span class="chat-time" style="font-size:10px;color:var(--text-muted);">${timeLabel}</span>
        ${!isDm
          ? `<button class="inbox-dm-btn" title="DM으로 전환" onclick="inboxMoveToDm(event,'${escHtml(item.visitor_id)}','${escHtml(item.short_code)}','${escHtml(item.mem_code || '')}')">
               <i class="fas fa-exchange-alt"></i> DM
             </button>`
          : `<span style="font-size:10px;color:#3b82f6;"><i class="fas fa-check-circle"></i> DM</span>`}
      </div>`;

    el.addEventListener('click', (e) => {
        if (e.target.closest('.inbox-dm-btn')) return; // DM 버튼 클릭은 별도 처리
        // 챗봇 채팅 화면 열기 (visitor 기반) — 닉네임 우선 전달
        const displayName = item.custom_name || item.nickname || item.visitor_id;
        if (typeof pvaOpenChat === 'function') {
            pvaOpenChat(item.visitor_id, item.sms_idx, 'received', displayName); // 수신탭에서 호출 → 탭 유지
        }
    });
    return el;
}

async function inboxMoveToDm(event, visitorId, shortCode, targetMemId) {
    event.stopPropagation();
    if (!targetMemId) {
        alert('상대방이 회원이 아닙니다. 챗봇 채팅만 가능합니다.');
        return;
    }
    if (!confirm('DM방으로 전환하면 수신함에서 사라지고 채팅 탭으로 이동합니다.\n계속하시겠어요?')) return;

    const res = await apiPost('received_list.php', {
        mode: 'move_to_dm',
        global_visitor_id: visitorId,
        short_code: shortCode,
        target_mem_id: targetMemId
    });
    if (res && res.success) {
        showCopyToast('DM방으로 전환되었습니다! 채팅 탭에서 확인하세요.');
        renderInboxFromAPI('', true); // 수신 목록 새로고침
        setTimeout(() => {
            if (typeof switchTab === 'function') switchTab('chat');
        }, 1500);
    } else {
        alert('DM 전환 실패: ' + (res?.error || '알 수 없는 오류'));
    }
}

// ─────────────────────────────────────────
// 초기화
// ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    // ★ me.php로 실제 로그인 상태 확인 → 비로그인이면 로그인 페이지로 이동
    try {
        const meRes = await fetch('./api/me.php', { credentials: 'include' });
        const me = await meRes.json();
        if (!me.logged_in) {
            window.top.location.href = '/iam/login.php';
            return;
        }
        const serverState = me.has_bot ? 'member-with-bot' : 'member-no-bot';
        if (typeof applyUserState === 'function' && serverState !== (typeof userState !== 'undefined' ? userState : '')) {
            applyUserState(serverState);
        }
        // ★ window.ONE_MEMBER 설정 — 모든 페이지에서 서버 회원 정보 참조
        window.ONE_MEMBER = me.user || null;

        // shortCode → profileData 메모리 반영
        if (me.user && me.user.short_code && typeof profileData !== 'undefined') {
            profileData.shortCode = me.user.short_code;
        }
        if (typeof applyProfileToHeader === 'function') applyProfileToHeader();

        // mem_id, sms_idx → 메모리 변수만 유지
        if (me.user && me.user.mem_id) {
            currentMemId = me.user.mem_id;
        } else if (me.logged_in && me.user && me.user.short_code) {
            currentMemId = me.user.short_code;
        }
        if (me.user && me.user.sms_idx) {
            mySmsIdx = parseInt(me.user.sms_idx, 10) || 0;
        }
    } catch(e) { console.warn('[me.php]', e); }

    const isGuest = false; // 비로그인은 위에서 이미 리다이렉트됨

    // 기존 함수 교체 (mock 데이터 → 실제 DB 연동)
    window.renderChatList = (q = '') => {
        if (typeof userState !== 'undefined' && userState === 'guest') return;
        renderChatListFromAPI(q, true);
    };
    // chatbot chip filter
    let chipsLoaded = false;
    async function loadChatbotTabs() {
        if (chipsLoaded) return;
        var chips = document.getElementById('chatbotChips');
        if (!chips) return;
        var data = await apiGet('contacts.php', { mode: 'tabs' });
        if (!data || !data.success || !data.tabs || data.tabs.length === 0) return;
        chipsLoaded = true;
        chips.style.display = '';
        chips.innerHTML = '';
        var allChip = document.createElement('button');
        allChip.className = 'cb-chip active';
        allChip.dataset.idx = '0';
        allChip.textContent = '전체';
        allChip.title = '전체';
        chips.appendChild(allChip);
        data.tabs.forEach(function(tab) {
            var btn = document.createElement('button');
            btn.className = 'cb-chip';
            btn.dataset.idx = tab.sms_idx;
            btn.innerHTML = tab.chatbot_short + ' <span class="cb-cnt">(' + tab.count + ')</span>';
            btn.title = tab.chatbot_name;
            chips.appendChild(btn);
        });
        chips.addEventListener('click', function(e) {
            var btn = e.target.closest('.cb-chip');
            if (!btn) return;
            chips.querySelectorAll('.cb-chip').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            contactSmsIdx = parseInt(btn.dataset.idx, 10) || 0;
            renderContactsListFromAPI('', true);
        });
    }

    window.renderContactsList = function(q) {
        if (typeof userState !== 'undefined' && userState === 'guest') {
            if (typeof renderContactsWithState === 'function') renderContactsWithState();
            return;
        }
        loadChatbotTabs();
        return renderContactsListFromAPI(q || '', true);
    };
    // 공유리스트(visitor_id)는 main.js의 renderVisitorList 전역 함수 그대로 사용
    // 채팅탭 초기 로드: dm.js의 onDMTabActivated()가 담당하므로 여기서는 호출하지 않음
    // (chatList ID는 레거시 - 현재 HTML은 dmRoomList 사용)

    // 무한 스크롤 + 검색
    initInfiniteScroll();
    initSearch();

    // 봇 토글 저장
    hookBotToggle();

    // ★ 상대방 메시지 + 챗봇 응답 DB 저장
    hookPeerMsgBtn();

    // ★ sendWithAttachments 패치 (클릭 리스너 교체 포함)
    patchSendWithAttachments();

    // ★ 챗봇 중복 생성 차단
    patchMakeBotPanel();

    // 대시보드
    const db = document.getElementById('dashboardBtn');
    if (db) db.addEventListener('click', loadDashboardFromAPI);

    // ★ 알림 배지 초기 로드
    refreshNotifBadge();
});

// ─────────────────────────────────────────
// 챗봇 링크 복사
// ─────────────────────────────────────────
function copyBotLink(event, url) {
    event.stopPropagation();
    navigator.clipboard.writeText(url).then(() => {
        showCopyToast('링크가 복사되었습니다!');
    }).catch(() => {
        // fallback
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        showCopyToast('링크가 복사되었습니다!');
    });
}

function showCopyToast(msg) {
    let t = document.getElementById('copyToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'copyToast';
        t.className = 'copy-toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 1800);
}

// ─────────────────────────────────────────
// 알림 패널
// ─────────────────────────────────────────
let notifData = [];
let notifFilter = 'all';

function openNotifPanel() {
    document.getElementById('notifPanel').classList.add('open');
    document.getElementById('notifOverlay').classList.add('open');
    loadNotifications();
}
function closeNotifPanel() {
    document.getElementById('notifPanel').classList.remove('open');
    document.getElementById('notifOverlay').classList.remove('open');
}

async function loadNotifications() {
    const area = document.getElementById('notifListArea');
    area.innerHTML = '<div class="notif-loading"><i class="fas fa-spinner fa-spin"></i></div>';
    const data = await apiGet('notifications.php');
    if (!data || !data.success) {
        area.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>알림을 불러올 수 없습니다</p></div>';
        return;
    }
    notifData = data.notifications || [];
    updateNotifBadge(data.badge, data.unread_total);
    renderNotifList();
}

function renderNotifList() {
    const area = document.getElementById('notifListArea');
    const filtered = notifFilter === 'all'
        ? notifData
        : notifData.filter(n => n.type === notifFilter);

    if (filtered.length === 0) {
        area.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>알림이 없습니다</p></div>';
        return;
    }

    const typeLabel = { connect_request: '🙋 연결요청', first_chat: '👋 첫 사용', hi: '📣 HI공지' };
    const typeIcon  = { connect_request: '🙋', first_chat: '👋', hi: '📣' };

    let html = '';
    let lastDate = '';
    filtered.forEach(n => {
        const d = new Date(n.created_at);
        const today = new Date();
        const yest  = new Date(); yest.setDate(yest.getDate() - 1);
        let dateLabel = d.toLocaleDateString('ko-KR', {month:'long', day:'numeric'});
        if (d.toDateString() === today.toDateString()) dateLabel = '오늘';
        else if (d.toDateString() === yest.toDateString()) dateLabel = '어제';

        if (dateLabel !== lastDate) {
            html += `<div class="notif-date-sep">${dateLabel}</div>`;
            lastDate = dateLabel;
        }

        const timeStr = d.toLocaleTimeString('ko-KR', {hour:'2-digit', minute:'2-digit'});
        const unreadCls = n.is_read ? '' : ' unread';
        html += `<div class="notif-item${unreadCls}" data-idx="${n.idx}" data-type="${n.type}"
                      onclick="onNotifClick(${n.idx}, ${n.sms_idx}, ${n.request_idx}, '${n.type}')">
          <div class="ni-icon ${n.type}">${typeIcon[n.type] || '🔔'}</div>
          <div class="ni-content">
            <div class="ni-summary">${escHtml(n.summary || '')}</div>
            <div class="ni-meta">
              <span class="ni-tag ${n.type}">${typeLabel[n.type] || n.type}</span>
              <span class="ni-time">${timeStr}</span>
            </div>
          </div>
          ${n.is_read ? '' : '<div class="ni-dot"></div>'}
        </div>`;
    });
    area.innerHTML = html;
}

function filterNotif(btn, type) {
    document.querySelectorAll('.nftab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    notifFilter = type;
    renderNotifList();
}

async function onNotifClick(idx, smsIdx, requestIdx, type) {
    // 읽음 처리
    await apiPost('notifications.php', { action: 'read', idx });
    const item = document.querySelector(`.notif-item[data-idx="${idx}"]`);
    if (item) { item.classList.remove('unread'); const dot = item.querySelector('.ni-dot'); if (dot) dot.remove(); }
    // 로컬 데이터 업데이트
    const n = notifData.find(x => x.idx === idx);
    if (n) n.is_read = 1;
    // 채팅 화면 이동
    closeNotifPanel();
    if (typeof openChatScreenFromAPI === 'function') {
        openChatScreenFromAPI({ sms_idx: smsIdx, request_idx: requestIdx });
    }
    // 배지 갱신
    refreshNotifBadge();
}

async function readAllNotif() {
    await apiPost('notifications.php', { action: 'read_all' });
    notifData.forEach(n => { n.is_read = 1; });
    document.querySelectorAll('.notif-item.unread').forEach(el => {
        el.classList.remove('unread');
        const dot = el.querySelector('.ni-dot'); if (dot) dot.remove();
    });
    updateNotifBadge(0, 0);
}

function updateNotifBadge(badge, unread) {
    const dot  = document.getElementById('notifBadgeDot');
    const ub   = document.getElementById('notifUnreadBadge');
    if (dot) dot.style.display = badge > 0 ? '' : 'none';
    if (ub)  { ub.textContent = unread; ub.style.display = unread > 0 ? '' : 'none'; }
}

async function refreshNotifBadge() {
    const data = await apiGet('notifications.php', { badge: 1 });
    if (data) updateNotifBadge(data.badge, data.unread);
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
