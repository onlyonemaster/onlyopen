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

function colorFromId(id) { return 'color-' + ((id % 8) + 1); }

function apiItemToPerson(item) {
    return {
        id: item.id, name: item.name || '', position: item.position || '',
        phone: item.phone || '', regDate: item.regDate || '',
        chatCount: item.chatCount || 0, unreadCount: item.unreadCount || 0,
        lastChat: item.lastChat || null, shortUrl: item.shortUrl || '',
        colorClass: colorFromId(item.id), messages: [],
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

        const timeLabel = formatRelativeDate(item.lastChat);
        const prefix = item.lastRole === 'sender' ? '나: ' : item.lastRole === 'bot' ? '🤖 ' : '';
        const preview = item.lastMsg ? prefix + item.lastMsg.split('\n')[0].slice(0, 28) : '';
        const unread  = item.unreadCount || 0;
        const cc      = colorFromId(item.id);

        li.innerHTML = `
          <div class="profile-wrap">
            <div class="profile-img ${cc}">${(item.name||'?').charAt(0)}</div>
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
async function renderContactsListFromAPI(query = '', reset = true) {
    if (contactLoading && !reset) return;  // 검색(reset=true) 시 이전 요청 취소 허용
    contactLoading = true;
    if (reset) { contactPage = 1; contactQuery = query; contactHasMore = true; contactsList.innerHTML = ''; }

    const params = { page: contactPage, q: contactQuery, filter: contactFilter };
    if (contactSmsIdx > 0) params.sms_idx = contactSmsIdx;
    const data = await apiGet('contacts.php', params);
    contactLoading = false;
    if (!data || !data.success) return;

    contactHasMore = data.has_more;

    if (contactPage === 1 && data.stat) {
        // 아래줄(hdr) 제거 → 상단 contacts-stats-bar 스팬에 실제 데이터 연결
        const _st = document.getElementById('statTotal');
        const _sa = document.getElementById('statActive');
        const _si = document.getElementById('statInactive');
        if (_st) _st.textContent = data.total + '명';
        if (_sa) _sa.textContent = data.stat.chatted + '명';
        if (_si) _si.textContent = data.stat.unused + '명';
        const _appBadge = document.getElementById('appBadge');
        const _appTitle = document.getElementById('appTitle');
        if (_appBadge && _appTitle && _appTitle.textContent === '퍼널') {
            _appBadge.textContent = data.total + '명';
        }
    }

    data.list.forEach(item => {
        const person = apiItemToPerson(item);
        if (typeof buildContactCard === 'function') {
            const card = buildContactCard(person);
            if (card) {
                const nc = card.cloneNode(true);
                nc.addEventListener('click', () => openChatScreenFromAPI(item));
                contactsList.appendChild(nc);
            }
        }
    });
    contactPage++;
}

// ─────────────────────────────────────────
// ③ 채팅룸 열기
// ─────────────────────────────────────────
async function openChatScreenFromAPI(item) {
    const person = apiItemToPerson(item);
    currentChatInfo = { sms_idx: item.sms_idx, request_idx: item.id, person };

    // ★ 버그3 수정: 채팅방 전환 시 직접대화 일시정지 상태 초기화
    if (typeof avatarPausedByPeer !== 'undefined') avatarPausedByPeer = false;
    const banner = document.getElementById('directWaitBanner');
    if (banner) banner.style.display = 'none';

    // ★ peerSimBar 복원 (테스트용 시뮬레이션 바 표시)
    const simBar = document.getElementById('peerSimBar');
    if (simBar) simBar.style.display = '';

    // openChatScreen으로 UI 프레임 초기화
    person.messages = [];
    openChatScreen(person);

    // 항상 AI 모드로 시작 (AI 디폴트 고정 정책)
    const botOn = true;
    isBotOn = botOn;
    if (typeof botToggle !== 'undefined') botToggle.checked = botOn;
    if (typeof updateBotUI === 'function') updateBotUI();

    // 메시지 로드
    const data = await apiGet('messages.php', { request_idx: item.id, sms_idx: item.sms_idx });
    console.log('[API] openChat: data=', data);
    if (!data || !data.success) {
        console.warn('[API] messages.php 응답 실패:', data);
        return;
    }
    console.log('[API] 메시지 수:', data.messages ? data.messages.length : 0);
    if (data.messages && data.messages.length > 0) {
        person.messages = data.messages;
        const cm = document.getElementById('chatMessages');
        console.log('[API] chatMessages 엘리먼트:', cm);
        if (typeof renderMessages === 'function') {
            console.log('[API] renderMessages 호출 시작');
            renderMessages(data.messages);
            console.log('[API] renderMessages 호출 완료');
        } else {
            console.error('[API] renderMessages 함수 없음!');
        }
        if (cm) cm.scrollTop = cm.scrollHeight;
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
        // 원본 호출 전에 텍스트 캡처 (원본 실행 후 textarea 초기화됨)
        const textarea = document.getElementById('ciTextarea');
        const text = textarea ? textarea.value.trim() : '';

        // 원본 UI 처리 실행
        _orig.call(this);

        // ★ DB 저장
        if (currentChatInfo && text) {
            const { sms_idx, request_idx } = currentChatInfo;
            await apiPost('messages.php', { sms_idx, request_idx, message: text });
            // ★ 채팅 목록 갱신 (처음 채팅 시 채팅탭에 반영)
            renderChatListFromAPI(chatQuery, true);
        }
    };

    window.sendWithAttachments = newFn;

    // ★ 핵심: 클릭 버튼의 이벤트 리스너도 교체 (레퍼런스로 등록됐으므로 직접 교체 필요)
    const ciSendBtn = document.getElementById('ciSendBtn');
    if (ciSendBtn) {
        ciSendBtn.removeEventListener('click', _orig);
        ciSendBtn.addEventListener('click', newFn);
        console.log('[API] ciSendBtn 클릭 리스너 교체 완료');
    }

    console.log('[API] sendWithAttachments 패치 완료 (클릭+Enter 모두 적용)');
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
        const { sms_idx, request_idx } = currentChatInfo;
        const cm = document.getElementById('chatMessages');
        if (!cm) return;

        // ① main.js가 DOM에 메시지 추가할 때까지 50ms 대기 후 마지막 user 버블 읽기
        // (window.peerMessages는 const 선언이라 window 프로퍼티가 아님 → DOM에서 직접 읽음)
        setTimeout(() => {
            const userBubbles = cm.querySelectorAll('.msg-row.user .msg-bubble');
            const peerText = userBubbles.length > 0
                ? userBubbles[userBubbles.length - 1].textContent.trim()
                : '';
            console.log('[API] peerMsgBtn: peerText=', peerText);
            if (!peerText) return;

            // DB에 상대방 메시지 저장 (role=receiver)
            apiPost('messages.php', { sms_idx, request_idx, message: peerText, role: 'receiver' })
                .then(() => renderChatListFromAPI(chatQuery, true));

            // ② 봇 켜져 있고 직접대화 일시정지 아닐 때: 1400ms 후 마지막 bot 버블 읽기
            // (main.js가 1200ms setTimeout으로 봇 응답 추가하므로 충분히 기다림)
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
                    apiPost('messages.php', { sms_idx, request_idx, message: botText, role: 'bot' })
                        .then(() => renderChatListFromAPI(chatQuery, true));
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
    const cs = document.getElementById('screenChat');
    if (cs) cs.addEventListener('scroll', () => {
        if (cs.scrollHeight - cs.scrollTop - cs.clientHeight < 120)
            if (chatHasMore && !chatLoading) renderChatListFromAPI(chatQuery, false);
    });
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
    const cs = document.getElementById('chatSearchInput');
    if (cs) {
        let t;
        cs.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => renderChatListFromAPI(cs.value.trim(), true), 300);
        });
    }
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
let receivedFilter = 'all'; // 수신 탭 필터: all | chatted | unused | visitor

// 수신 탭 필터 전환
function filterReceived(btn, filter) {
    receivedFilter = filter;
    document.querySelectorAll('.rfTab').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderReceivedListFromAPI(receivedQuery, true);
}

async function renderReceivedListFromAPI(query = '', reset = true) {
    const receivedList = document.getElementById('receivedList');
    if (!receivedList) return;
    if (receivedLoading) return;
    receivedLoading = true;
    if (reset) { receivedPage = 1; receivedQuery = query; receivedHasMore = true; receivedList.innerHTML = ''; }

    const data = await apiGet('contacts.php', { page: receivedPage, q: receivedQuery, filter: receivedFilter });
    receivedLoading = false;
    if (!data || !data.success) return;

    receivedHasMore = data.has_more;

    // ── 배지: 총 수신자 수 업데이트
    const receivedBadge = document.getElementById('receivedBadge');
    if (receivedBadge) receivedBadge.textContent = `${data.total}명`;

    // ── 통계 바 업데이트
    if (data.stat) {
        const _rt = document.getElementById('rStatTotal');
        const _ra = document.getElementById('rStatActive');
        const _ri = document.getElementById('rStatInactive');
        const _rv = document.getElementById('rStatVisitor');
        if (_rt) _rt.textContent = data.total + '명';
        if (_ra) _ra.textContent = (data.stat.chatted || 0) + '명';
        if (_ri) _ri.textContent = (data.stat.unused || 0) + '명';
        if (_rv) _rv.textContent = (data.stat.visitor || 0) + '명';
    }

    if (data.list.length === 0 && receivedPage === 1) {
        if (typeof showEmpty === 'function') {
            showEmpty(receivedList, 'fas fa-inbox',
                receivedQuery ? '검색 결과가 없습니다' : '수신자가 없습니다', '');
        }
        return;
    }

    data.list.forEach((item, idx) => {
        const isVisitor = (item.source === 'visitor');
        const li = document.createElement('div');
        li.className = 'contact-card received-card'
            + (item.chatCount > 0 ? ' has-chat' : '')
            + (isVisitor ? ' visitor-card' : '');
        li.style.animationDelay = `${idx * 0.03}s`;

        const timeLabel = typeof formatRelativeDate === 'function'
            ? formatRelativeDate(item.lastChat || item.regDate) : (item.lastChat || item.regDate || '');
        const cc = colorFromId(item.id);
        const initial = (item.name || '?').charAt(0);
        const unread  = item.unreadCount || 0;

        // ── visitor 전용 배지/서브텍스트
        const sourceBadge = isVisitor
            ? `<span class="visitor-source-badge" title="챗봇 링크 직접 방문자">🔗 링크방문</span>`
            : '';
        const memberChip = (isVisitor && item.mem_id)
            ? `<span class="visitor-member-chip" title="아이엠 회원"><i class="fas fa-user-check"></i> ${item.mem_id}</span>`
            : '';
        const subLine = isVisitor
            ? `<div class="cc-position" style="font-size:11px;color:var(--text-muted);margin-bottom:1px;">
                 <i class="fas fa-fingerprint" style="margin-right:3px;font-size:9px;opacity:.6;"></i>${item.visitor_id || ''}
                 ${item.phone ? `<span style="margin-left:8px;"><i class="fas fa-phone" style="font-size:9px;"></i> ${item.phone}</span>` : ''}
               </div>`
            : `<div class="cc-position">${item.position || ''}</div>`;

        li.innerHTML = `
          <div class="cc-avatar ${cc}" style="position:relative;">
            ${initial}
            ${isVisitor
                ? `<div class="received-bot-badge visitor-badge" title="링크 방문자" style="background:linear-gradient(135deg,#f59e0b,#f97316);"><i class="fas fa-link"></i></div>`
                : `<div class="received-bot-badge" title="AI 챗봇"><i class="fas fa-robot"></i></div>`
            }
            ${item.chatCount > 0 ? '<div class="cc-chat-indicator"><i class="fas fa-comment-dots"></i></div>' : ''}
          </div>
          <div class="cc-info" style="flex:1;min-width:0;">
            <div class="cc-name-row">
              <span class="cc-name${unread > 0 ? ' has-unread' : ''}">${item.name}</span>
              ${sourceBadge}
              ${memberChip}
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
            ${subLine}
            ${!isVisitor && item.chatCount > 0 ? `<div class="cc-preview-text">${item.phone || ''}</div>` : ''}
          </div>
          <div class="cc-right">
            <span class="chat-time" style="font-size:10px;color:var(--text-muted);">${timeLabel}</span>
            <i class="fas fa-chevron-right cc-arrow" style="margin-top:6px;"></i>
          </div>`;

        // visitor 카드: 챗봇 링크로 이동 (funnel request 없으므로 채팅화면 대신)
        if (isVisitor) {
            li.addEventListener('click', (e) => {
                if (e.target.closest('.chatlink-chip')) return;
                if (item.shortUrl) {
                    window.open('https://chatbot.kiam.kr' + item.shortUrl + '?vid=' + encodeURIComponent(item.visitor_id || ''), '_blank');
                }
            });
        } else {
            li.addEventListener('click', () => openChatScreenFromAPI(item));
        }
        receivedList.appendChild(li);
    });
    receivedPage++;
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
        // 로그인 유저 정보 → profileData 머지 + DOM 반영
        if (me.user) {
            const u = me.user;
            window._meUser = u;

            // profileData 서버값으로 동기화
            if (typeof profileData !== 'undefined') {
                // ── 서버 값이 있으면 항상 덮어쓰기 (localStorage 오염 방지) ──
                // 이름: 서버 display(닉 또는 이름) 우선, 없으면 기존값 유지
                if (u.display || u.name) profileData.name = u.display || u.name || '';
                // 연락처: 서버 값 우선 (비어있으면 기존 localStorage 값 유지)
                if (u.email) profileData.email = u.email;
                if (u.phone) profileData.phone = u.phone;
                // 챗봇 단축코드: DB 값이 있으면 항상 덮어쓰기
                if (u.short_code) profileData.shortCode = u.short_code;
                // localStorage도 최신 서버 상태로 갱신
                if (typeof saveProfileData === 'function') saveProfileData();
                // 헤더/드롭다운 재적용
                if (typeof applyProfileToHeader === 'function') applyProfileToHeader();
            }

            // 드롭다운 adp-name (applyProfileToHeader에서 처리하지만 혹시 null 방어)
            const _adpName = document.querySelector('.adp-name');
            if (_adpName && !_adpName.textContent) _adpName.textContent = u.display || u.name;
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

    // 초기 목록 로드 (비회원이면 건너뜀)
    if (!isGuest && chatList) {
        renderChatListFromAPI('', true);
    }

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
