/* ===========================================================
   MyChat Second-Me · 공통 JS (mc 네임스페이스)
   - storage adapter (server / device / hybrid)
   - toast / modal helpers
   - lightweight fetch wrapper
   - 페이지 간 일관된 동작 보장
   =========================================================== */

(function(){
  'use strict';
  var W = window;
  if (W.mc) return; // idempotent

  var LS_KEY_SETTINGS = 'mychat_settings_v1';
  var LS_KEY_INBOX    = 'mychat_inbox_v1';
  var LS_KEY_DECISIONS= 'mychat_decisions_v1';
  var LS_KEY_ANOMALY  = 'mychat_anomaly_v1';
  var LS_KEY_USAGE    = 'mychat_usage_v1';
  var LS_KEY_LEGACY   = 'mychat_legacy_v1';
  var LS_KEY_SYNCPOL  = 'mychat_sync_policy_v1';
  var LS_KEY_POOL     = 'mychatOndevicePool_v1'; // shared with learn-v2.html

  /* -------- safe JSON LS helpers -------- */
  function lsGet(k, defVal){
    try{
      var raw = localStorage.getItem(k);
      if (raw === null || raw === undefined) return defVal;
      return JSON.parse(raw);
    }catch(e){ return defVal; }
  }
  function lsSet(k, v){
    try{ localStorage.setItem(k, JSON.stringify(v)); return true; }
    catch(e){ console.warn('[mc] lsSet failed', k, e); return false; }
  }

  /* -------- toast -------- */
  function ensureToastHost(){
    var h = document.querySelector('.mc-toast-host');
    if (!h){
      h = document.createElement('div');
      h.className = 'mc-toast-host';
      document.body.appendChild(h);
    }
    return h;
  }
  function toast(msg, kind, ms){
    var host = ensureToastHost();
    var t = document.createElement('div');
    t.className = 'mc-toast' + (kind ? ' '+kind : '');
    t.textContent = msg;
    host.appendChild(t);
    setTimeout(function(){
      t.style.opacity = '0';
      t.style.transition = 'opacity .25s';
      setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 260);
    }, ms || 2400);
  }

  /* -------- modal -------- */
  function ensureModalHost(){
    var h = document.querySelector('.mc-modal-host');
    if (!h){
      h = document.createElement('div');
      h.className = 'mc-modal-host';
      h.innerHTML = '<div class="mc-modal"></div>';
      document.body.appendChild(h);
      h.addEventListener('click', function(e){
        if (e.target === h) closeModal();
      });
    }
    return h;
  }
  function openModal(html, opts){
    var h = ensureModalHost();
    var box = h.querySelector('.mc-modal');
    box.innerHTML = html;
    h.classList.add('on');
    if (opts && typeof opts.onOpen === 'function'){
      try{ opts.onOpen(box); }catch(e){ console.warn(e); }
    }
    return box;
  }
  function closeModal(){
    var h = document.querySelector('.mc-modal-host');
    if (h) h.classList.remove('on');
  }

  /* -------- fetch wrapper (graceful fail) -------- */
  function api(path, body, opts){
    opts = opts || {};
    var init = {
      method: opts.method || (body ? 'POST' : 'GET'),
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    };
    if (body){
      if (body instanceof FormData){
        init.body = body;
      } else {
        init.headers['Content-Type'] = 'application/json';
        init.body = JSON.stringify(body);
      }
    }
    return fetch(path, init).then(function(r){
      return r.text().then(function(text){
        var data = null;
        try{ data = text ? JSON.parse(text) : null; }catch(e){}
        if (!r.ok){
          return { ok:false, status:r.status, error: (data && data.error) || ('http_'+r.status), raw:text };
        }
        return data || { ok:true };
      });
    }).catch(function(err){
      return { ok:false, error:'network', detail: String(err) };
    });
  }

  /* -------- settings (BYO API key, storage mode) -------- */
  function getSettings(){
    var s = lsGet(LS_KEY_SETTINGS, {});
    if (!s.storage_mode) s.storage_mode = 'server';
    if (!s.providers) s.providers = {}; // {openai:{api_key,chat_model,embed_model}, ...}
    if (!s.flags) s.flags = { e2ee:false, daily_backup:false, marketing_off:true, proactive:true };
    if (!s.usage_cap) s.usage_cap = { monthly_usd: 20, alert_at: 0.8, fallback: true };
    return s;
  }
  function saveSettings(patch){
    var s = getSettings();
    Object.keys(patch||{}).forEach(function(k){ s[k] = patch[k]; });
    lsSet(LS_KEY_SETTINGS, s);
    // optimistic server save (no-op if endpoint missing)
    api('/aimessage/mychat/api/settings.php', { action:'save_settings', settings: s });
    return s;
  }

  /* -------- storage mode helpers -------- */
  function getStorageMode(){ return getSettings().storage_mode || 'server'; }
  function getChannelScopes(){ return lsGet('mychat_channel_scopes_v1', {}); }
  function setChannelScopes(map){ lsSet('mychat_channel_scopes_v1', map); }

  /* -------- on-device pool (shared schema with learn-v2.html) -------- */
  function poolAdd(entry){
    var pool = lsGet(LS_KEY_POOL, []);
    pool.unshift(Object.assign({ ts: Date.now() }, entry));
    if (pool.length > 5000) pool.length = 5000;
    lsSet(LS_KEY_POOL, pool);
    return pool.length;
  }
  function poolList(filter){
    var pool = lsGet(LS_KEY_POOL, []);
    if (!filter) return pool;
    return pool.filter(function(p){
      if (filter.channel && p.channel !== filter.channel) return false;
      if (filter.kind && p.kind !== filter.kind) return false;
      return true;
    });
  }

  /* -------- inbox (Life Inbox) -------- */
  function inboxAdd(item){
    var inbox = lsGet(LS_KEY_INBOX, []);
    var rec = Object.assign({
      id: 'i_'+Date.now()+'_'+Math.random().toString(36).slice(2,8),
      ts: Date.now(),
      read: false,
      pinned: false,
      source: 'manual',
      kind: 'note',
      title: '',
      summary: '',
      meta: {}
    }, item || {});
    inbox.unshift(rec);
    if (inbox.length > 1000) inbox.length = 1000;
    lsSet(LS_KEY_INBOX, inbox);
    return rec;
  }
  function inboxList(filter){
    var list = lsGet(LS_KEY_INBOX, []);
    if (!filter) return list;
    return list.filter(function(it){
      if (filter.source && it.source !== filter.source) return false;
      if (filter.kind && it.kind !== filter.kind) return false;
      if (filter.unreadOnly && it.read) return false;
      if (filter.pinnedOnly && !it.pinned) return false;
      return true;
    });
  }
  function inboxUpdate(id, patch){
    var list = lsGet(LS_KEY_INBOX, []);
    for (var i=0;i<list.length;i++){
      if (list[i].id === id){
        Object.assign(list[i], patch||{});
        lsSet(LS_KEY_INBOX, list);
        return list[i];
      }
    }
    return null;
  }
  function inboxRemove(id){
    var list = lsGet(LS_KEY_INBOX, []);
    var out = list.filter(function(it){ return it.id !== id; });
    lsSet(LS_KEY_INBOX, out);
  }
  function inboxClear(){ lsSet(LS_KEY_INBOX, []); }

  /* seed demo inbox if empty (so the UI is not empty on first visit) */
  function seedInboxIfEmpty(){
    var existing = lsGet(LS_KEY_INBOX, null);
    if (existing && existing.length) return;
    var now = Date.now();
    var demos = [
      {id:'d1', ts: now-1000*60*8,  source:'phone',  kind:'call',     title:'엄마와 통화 12분',   summary:'주말 식사 약속, 토요일 12시 본가에서.', meta:{from:'엄마', dur_sec: 720}},
      {id:'d2', ts: now-1000*60*32, source:'kakao',  kind:'chat',     title:'팀 채널 87건',        summary:'배포 일정 확정, 수요일 18시 릴리스.', meta:{room:'프로젝트X'}},
      {id:'d3', ts: now-1000*60*60*2,source:'email', kind:'email',    title:'서비스 청구서',       summary:'OpenAI $12.30 결제 예정', meta:{from:'OpenAI'}},
      {id:'d4', ts: now-1000*60*60*5,source:'health',kind:'metric',   title:'어제 수면 5h 32m',    summary:'평소보다 1시간 부족. 컨디션 주의.', meta:{score:62}},
      {id:'d5', ts: now-1000*60*60*22,source:'meet', kind:'recording',title:'회의 녹음 — 마케팅', summary:'4분기 캠페인 4가지 안 논의. 의사결정 보류.', meta:{dur_min:48}}
    ];
    demos.forEach(function(d){ d.read=false; d.pinned=false; });
    lsSet(LS_KEY_INBOX, demos);
  }

  /* -------- decisions -------- */
  function decisionsList(){ return lsGet(LS_KEY_DECISIONS, []); }
  function decisionsAdd(rec){
    var list = lsGet(LS_KEY_DECISIONS, []);
    var r = Object.assign({
      id:'d_'+Date.now()+'_'+Math.random().toString(36).slice(2,6),
      ts: Date.now(),
      title:'', question:'', context:'', predict:[], decide:null, reflect:null
    }, rec||{});
    list.unshift(r);
    if (list.length>500) list.length=500;
    lsSet(LS_KEY_DECISIONS, list);
    return r;
  }
  function decisionsUpdate(id, patch){
    var list = lsGet(LS_KEY_DECISIONS, []);
    for (var i=0;i<list.length;i++){
      if (list[i].id===id){
        Object.assign(list[i], patch||{});
        lsSet(LS_KEY_DECISIONS, list);
        return list[i];
      }
    }
    return null;
  }

  /* -------- anomaly -------- */
  function anomalyList(){ return lsGet(LS_KEY_ANOMALY, []); }
  function anomalyAdd(rec){
    var list = lsGet(LS_KEY_ANOMALY, []);
    list.unshift(Object.assign({
      id:'a_'+Date.now()+'_'+Math.random().toString(36).slice(2,6),
      ts: Date.now(), kind:'unknown', severity:'info', title:'', detail:'', ack:false
    }, rec||{}));
    if (list.length>500) list.length=500;
    lsSet(LS_KEY_ANOMALY, list);
    return list[0];
  }
  function anomalyAck(id){
    var list = lsGet(LS_KEY_ANOMALY, []);
    for (var i=0;i<list.length;i++){ if (list[i].id===id){ list[i].ack=true; break; } }
    lsSet(LS_KEY_ANOMALY, list);
  }

  /* -------- usage tracking (BYO API key) -------- */
  function usageGet(){
    var u = lsGet(LS_KEY_USAGE, null);
    var now = new Date();
    var ym = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0');
    if (!u || u.ym !== ym){
      u = { ym: ym, total_usd: 0, by_provider: {}, by_day: {}, calls: 0, last_reset: Date.now() };
      lsSet(LS_KEY_USAGE, u);
    }
    return u;
  }
  function usageAdd(provider, usd){
    var u = usageGet();
    u.total_usd = +(u.total_usd + (usd||0)).toFixed(4);
    u.calls += 1;
    u.by_provider[provider] = +((u.by_provider[provider]||0) + (usd||0)).toFixed(4);
    var day = new Date().toISOString().slice(0,10);
    u.by_day[day] = +((u.by_day[day]||0) + (usd||0)).toFixed(4);
    lsSet(LS_KEY_USAGE, u);
    // check cap
    var s = getSettings();
    var cap = (s.usage_cap && s.usage_cap.monthly_usd) || 0;
    if (cap > 0 && u.total_usd >= cap){
      toast('⚠️ 이번 달 비용 캡 도달 — 더 저렴한 모델로 폴백', 'warn', 4000);
    } else if (cap > 0 && u.total_usd >= cap * (s.usage_cap.alert_at||0.8)){
      toast('⚠️ 비용 캡의 '+Math.round(((s.usage_cap.alert_at||0.8))*100)+'% 사용', 'warn', 3000);
    }
    return u;
  }

  /* -------- legacy (Digital Legacy) -------- */
  function legacyGet(){
    var l = lsGet(LS_KEY_LEGACY, null);
    if (!l) l = { enabled:false, contact:{name:'', email:'', phone:'', relation:''}, scope:{summary:true, photos:false, messages:false, decisions:false}, message:'', verify_days: 90, last_alive: Date.now() };
    return l;
  }
  function legacySave(patch){
    var l = legacyGet();
    Object.assign(l, patch||{});
    lsSet(LS_KEY_LEGACY, l);
    return l;
  }

  /* -------- sync policy -------- */
  function syncPolicyGet(){
    var p = lsGet(LS_KEY_SYNCPOL, null);
    if (!p){
      p = {
        enabled: false,
        sources: {
          call:   { on:true,  freq:'realtime', retain_days: 365, default_scope:'private' },
          sms:    { on:true,  freq:'realtime', retain_days: 365, default_scope:'private' },
          kakao:  { on:true,  freq:'15min',    retain_days: 365, default_scope:'private' },
          email:  { on:true,  freq:'hourly',   retain_days: 730, default_scope:'private' },
          meet:   { on:false, freq:'manual',   retain_days: 180, default_scope:'private' },
          photo:  { on:false, freq:'daily',    retain_days: 0,   default_scope:'private' },
          video:  { on:false, freq:'manual',   retain_days: 0,   default_scope:'private' },
          health: { on:true,  freq:'hourly',   retain_days: 365, default_scope:'private' },
          cal:    { on:true,  freq:'15min',    retain_days: 730, default_scope:'private' }
        },
        wifi_only: true,
        sleep_quiet_hours: { on:true, start:'23:00', end:'07:00' },
        battery_min: 20,
        encrypt_in_transit: true
      };
    }
    return p;
  }
  function syncPolicySave(patch){
    var p = syncPolicyGet();
    Object.assign(p, patch||{});
    lsSet(LS_KEY_SYNCPOL, p);
    // try server
    api('/aimessage/mychat/api/sync_policy.php', { action:'save', policy: p });
    return p;
  }

  /* -------- top bar helper -------- */
  function renderTopbar(opts){
    opts = opts||{};
    var html = ''
      + '<div class="mc-topbar">'
      +   '<button class="mc-back" aria-label="뒤로가기" onclick="mc.goBack()">←</button>'
      +   '<div style="flex:1; min-width:0">'
      +     '<div class="mc-title">'+(opts.title||'MyChat')+'</div>'
      +     (opts.sub ? '<div class="mc-sub">'+opts.sub+'</div>' : '')
      +   '</div>'
      +   '<div class="mc-actions">'
      +     (opts.actions || '')
      +   '</div>'
      + '</div>';
    return html;
  }
  function goBack(){
    if (history.length > 1) history.back();
    else location.href = '/aimessage/mychat/';
  }

  /* -------- format helpers -------- */
  function fmtTime(ts){
    var d = new Date(ts);
    var now = Date.now();
    var diff = (now - ts)/1000;
    if (diff < 60) return '방금';
    if (diff < 3600) return Math.floor(diff/60)+'분 전';
    if (diff < 86400) return Math.floor(diff/3600)+'시간 전';
    if (diff < 86400*7) return Math.floor(diff/86400)+'일 전';
    return d.toLocaleDateString('ko-KR', {month:'short', day:'numeric'});
  }
  function fmtUsd(v){ return '$'+(v||0).toFixed(2); }
  function fmtKrwLike(usd){ return '₩'+Math.round((usd||0)*1380).toLocaleString(); }
  function esc(s){
    return String(s||'').replace(/[&<>"]/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
    });
  }

  /* -------- public namespace -------- */
  W.mc = {
    // helpers
    lsGet: lsGet, lsSet: lsSet,
    toast: toast, openModal: openModal, closeModal: closeModal,
    api: api, esc: esc, fmtTime: fmtTime, fmtUsd: fmtUsd, fmtKrwLike: fmtKrwLike,
    renderTopbar: renderTopbar, goBack: goBack,
    // settings / storage
    getSettings: getSettings, saveSettings: saveSettings,
    getStorageMode: getStorageMode,
    getChannelScopes: getChannelScopes, setChannelScopes: setChannelScopes,
    // pool
    poolAdd: poolAdd, poolList: poolList,
    // inbox
    inboxAdd: inboxAdd, inboxList: inboxList, inboxUpdate: inboxUpdate, inboxRemove: inboxRemove, inboxClear: inboxClear, seedInboxIfEmpty: seedInboxIfEmpty,
    // decisions
    decisionsList: decisionsList, decisionsAdd: decisionsAdd, decisionsUpdate: decisionsUpdate,
    // anomaly
    anomalyList: anomalyList, anomalyAdd: anomalyAdd, anomalyAck: anomalyAck,
    // usage
    usageGet: usageGet, usageAdd: usageAdd,
    // legacy
    legacyGet: legacyGet, legacySave: legacySave,
    // sync policy
    syncPolicyGet: syncPolicyGet, syncPolicySave: syncPolicySave
  };
})();
