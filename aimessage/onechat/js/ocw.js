/**
 * 챗봇 자동 설정하기 (Wizard) — ocw.js v2.0
 * SECTION 0: 회원찾기 + 챗봇선택
 * SECTION 1~4: 정보수집 + 옵션
 * SECTION 4 하단: [자동설정 시작] → 챗봇 설정 4가지 저장
 */
(function(){
'use strict';

const API = '/admin/ajax/agent_orchestrator.php';
let ocwState = {
  memberId: '',
  memberData: null,
  facts: {},
  rawExtraInfo: '',
  selectedSmsIdx: null,
  selectedChatbotName: '',
  options: { tone:'정중함', depth:'일반', qty:30 }
};

/* ── 유틸 ──────────────────────────────────────── */
function show(id){ const el=document.getElementById(id); if(el){el.style.display='block';} }
function hide(id){ const el=document.getElementById(id); if(el){el.style.display='none';} }
function showSec(id){
  const el=document.getElementById(id);
  if(el){ el.classList.add('show'); setTimeout(()=>el.scrollIntoView({behavior:'smooth',block:'start'}),80); }
}
function api(data, cb){
  const fd=new FormData();
  Object.entries(data).forEach(([k,v])=>fd.append(k, typeof v==='object'?JSON.stringify(v):v));
  fetch(API,{method:'POST',body:fd})
    .then(r=>{
      const ct = r.headers.get('content-type')||'';
      if(!ct.includes('json')) return r.text().then(t=>{ throw new Error('비JSON 응답: '+t.slice(0,80)); });
      return r.text().then(t=>{
        if(!t.trim()) throw new Error('빈 응답 (서버 오류 가능)');
        return JSON.parse(t);
      });
    })
    .then(cb)
    .catch(e=>cb({code:500,message:e.message}));
}
function scoreClass(s){ return s>=60?'snum-hi':s>=30?'snum-mi':'snum-lo'; }
function tag(type){ return '<span class="tag-'+type+'">'+(type==='ai'?'AI자동':'직접입력')+'</span>'; }

/* ── SECTION 0: 회원 조회 ───────────────────────── */
window.ocwSearch = function(){
  const mid = (document.getElementById('ocw-member-id').value||'').trim();
  if(!mid){ alert('회원 ID를 입력해 주세요.'); return; }
  ocwState.memberId = mid;
  hide('ocw-history-wrap');
  hide('ocw-search-error');
  show('ocw-search-loading');
  document.getElementById('ocw-btn-search').disabled = true;

  api({ action:'lookup_member', mem_id:mid }, function(res){
    hide('ocw-search-loading');
    document.getElementById('ocw-btn-search').disabled = false;
    if(res.code !== 200){
      const errEl = document.getElementById('ocw-search-error');
      errEl.textContent = res.message || '조회 실패';
      show('ocw-search-error');
      return;
    }
    renderProfile(res.data);
    autoSetChatbot();
    showSec('ocw-sec1');
  });
};

function renderProfile(d){
  ocwState.memberData = d;
  document.getElementById('ocw-p-name').textContent = d.name || d.mem_id || '';
  document.getElementById('ocw-p-job').textContent  = d.job  || '';
  document.getElementById('ocw-p-org').textContent  = d.org  || '';
  document.getElementById('ocw-p-summary').textContent = d.ai_summary || '';
  const score = d.profile_score || 0;
  const sEl = document.getElementById('ocw-p-score');
  sEl.textContent = score + '%';
  sEl.className   = 'snum ' + scoreClass(score);

  // 상세 정보 태그
  const details = document.getElementById('ocw-p-details');
  const tags = [d.industry, d.career_level, d.age_range].filter(Boolean);
  details.innerHTML = tags.map(t=>`<span style="font-size:11px;background:#e8f4fd;color:#1a6fa8;padding:2px 7px;border-radius:8px;margin-right:4px">${t}</span>`).join('');

  // 프로필 희박 경고
  const sparseAlert = document.getElementById('ocw-sparse-alert');
  if(score < 30){
    sparseAlert.style.display = 'flex';
    const extra1Box = document.getElementById('ocw-extra1-box');
    extra1Box.style.display = 'block';
    document.getElementById('ocw-btn-extra1').innerHTML = '<i class="fa fa-chevron-up"></i> 추가 정보 입력 닫기';
  } else {
    sparseAlert.style.display = 'none';
  }
}

/* ── SECTION 0: 챗봇 자동 세팅 ─────────────────── */
function autoSetChatbot(cb){
  api({ action:'get_chatbots', mem_id:ocwState.memberId }, function(res){
    const infoEl = document.getElementById('ocw-chatbot-info');
    if(!infoEl) return;
    if(res.code !== 200 || !res.data || !res.data.length){
      infoEl.style.background='#fff3cd';
      infoEl.style.borderColor='#ffc107';
      infoEl.style.color='#856404';
      infoEl.innerHTML='<i class="fa fa-exclamation-triangle"></i> 등록된 챗봇이 없습니다. 챗봇을 먼저 생성해 주세요.';
      infoEl.style.display='block';
      if(cb) cb(false);
      return;
    }
    const bot = res.data[0];
    ocwState.selectedSmsIdx      = bot.sms_idx;
    ocwState.selectedChatbotName = bot.chatbot_name || '챗봇 #' + bot.sms_idx;
    document.getElementById('ocw-chatbot-name').textContent = ocwState.selectedChatbotName;
    infoEl.style.display = 'block';
    if(cb) cb(true);
  });
}

/* ── SECTION 1 ──────────────────────────────────── */
window.ocwToggleExtra1 = function(){
  const box = document.getElementById('ocw-extra1-box');
  const btn = document.getElementById('ocw-btn-extra1');
  const isOpen = box.style.display !== 'none';
  box.style.display = isOpen ? 'none' : 'block';
  btn.innerHTML = isOpen
    ? '<i class="fa fa-pencil"></i> 수정 / 추가 정보 입력'
    : '<i class="fa fa-chevron-up"></i> 추가 정보 입력 닫기';
};

window.ocwConfirmInfo = function(){
  ocwState.rawExtraInfo = (document.getElementById('ocw-extra1-text').value||'').trim();
  if(ocwState.rawExtraInfo){
    api({ action:'save_extra_info', mem_id:ocwState.memberId, extra_info:ocwState.rawExtraInfo }, function(){});
  }
  document.getElementById('ocw-sec1-tag').textContent = '완료';
  document.getElementById('ocw-sec1-tag').className = 'ocw-s-tag ocw-s-done';
  showSec('ocw-sec2');
  loadFacts();
};

/* ── SECTION 2: 팩트 ────────────────────────────── */
function loadFacts(){
  show('ocw-facts-loading');
  hide('ocw-facts-body');

  const extraText = (document.getElementById('ocw-extra1-text').value||'').trim();
  api({
    action: 'research_member',
    mem_id: ocwState.memberId,
    extra_info: extraText
  }, function(res){
    hide('ocw-facts-loading');
    if(res.code !== 200){
      document.getElementById('ocw-sec2-tag').textContent = '수동 입력 필요';
      show('ocw-facts-body');
      return;
    }
    ocwState.facts = res.data.facts || {};
    renderFacts(res.data.facts);
    show('ocw-facts-body');
  });
}

function renderFacts(facts){
  renderFactGroup('ocw-facts-basic',    facts.basic    || []);
  renderFactGroup('ocw-facts-edu',      facts.edu      || []);
  renderFactGroup('ocw-facts-career',   facts.career   || []);
  renderFactGroup('ocw-facts-activity', facts.activity || []);
}

function renderFactGroup(containerId, items){
  const el = document.getElementById(containerId);
  if(!el) return;
  const valid = (items||[]).filter(f => f && f.label && String(f.label).trim());
  if(!valid.length){
    el.innerHTML = '<div style="font-size:12px;color:#bbb;padding:3px 0">정보 없음 — 추가 검색으로 보완하세요</div>';
    return;
  }
  el.innerHTML = valid.map(f=>`
    <div class="ocw-fact-row">
      <label>${f.label}${tag(f.type||'ai')}</label>
      <div class="fval"><input type="text" value="${(f.val||'').replace(/"/g,'&quot;')}" data-label="${f.label}"></div>
    </div>`).join('');
}

function collectCurrentFacts(){
  const result = {};
  ['basic','edu','career','activity'].forEach(group=>{
    const rows = document.querySelectorAll('#ocw-facts-'+group+' input');
    result[group] = Array.from(rows).map(inp=>({
      label: inp.dataset.label || '',
      val:   inp.value,
      type:  'manual'
    }));
  });
  return result;
}

window.ocwExtraSearch = function(){
  const btn = document.getElementById('ocw-btn-extra-search');
  btn.disabled = true;
  show('ocw-extra-loading');
  hide('ocw-extra-result');

  const currentFacts = collectCurrentFacts();
  const extraText = [ocwState.rawExtraInfo,(document.getElementById('ocw-extra2-text').value||'').trim()].filter(Boolean).join('\n\n');

  api({
    action: 'additional_research',
    mem_id: ocwState.memberId,
    current_facts: currentFacts,
    extra_info: extraText
  }, function(res){
    hide('ocw-extra-loading');
    btn.disabled = false;
    if(res.code !== 200){
      document.getElementById('ocw-extra-result-msg').textContent = '검색 오류: '+(res.message||'알 수 없는 오류');
      show('ocw-extra-result');
      return;
    }
    const newFacts = res.data.facts || {};
    mergeAndRenderFacts(newFacts);
    document.getElementById('ocw-extra-result-msg').textContent =
      '추가 검색 완료 — ' + (res.data.added_count||0) + '건의 새로운 정보가 추가되었습니다.';
    show('ocw-extra-result');
  });
};

function mergeAndRenderFacts(newFacts){
  ['basic','edu','career','activity'].forEach(group=>{
    if(!newFacts[group] || !newFacts[group].length) return;
    const container = document.getElementById('ocw-facts-'+group);
    if(!container) return;
    newFacts[group].filter(f=>f&&f.label&&String(f.label).trim()).forEach(f=>{
      const row = document.createElement('div');
      row.className = 'ocw-fact-row';
      row.style.animation = 'ocwfade .4s';
      row.innerHTML = `
        <label>${f.label}${tag('ai')}</label>
        <div class="fval"><input type="text" value="${(f.val||'').replace(/"/g,'&quot;')}" data-label="${f.label}"></div>`;
      container.appendChild(row);
    });
  });
}

window.ocwConfirmFacts = function(){
  ocwState.facts = collectCurrentFacts();
  document.getElementById('ocw-sec2-tag').textContent = '완료';
  document.getElementById('ocw-sec2-tag').className = 'ocw-s-tag ocw-s-done';
  showSec('ocw-sec3');
  showSec('ocw-sec4');
  show('ocw-start-wrap');
  if(!ocwState.selectedSmsIdx){
    // 챗봇 자동 선택 시도 (버튼은 항상 표시됨)
    autoSetChatbot(function(ok){});
  }
};

/* ── SECTION 3 ──────────────────────────────────── */
window.ocwToggleMethod = function(id){
  const card = document.getElementById('mc-'+id);
  const chk  = document.getElementById('chk-'+id);
  const form = document.getElementById('mc-'+id+'-form');
  chk.checked = !chk.checked;
  card.classList.toggle('sel', chk.checked);
  if(form) form.style.display = chk.checked ? 'block' : 'none';
};
window.ocwAddQA = function(e){
  e.stopPropagation();
  const list = document.getElementById('ocw-qa-list');
  const div = document.createElement('div');
  div.style.marginBottom = '7px';
  div.innerHTML = `<input type="text" class="form-control" placeholder="질문" style="margin-bottom:3px;font-size:12px">
    <textarea class="form-control" rows="2" placeholder="답변" style="font-size:12px"></textarea>`;
  list.appendChild(div);
};
window.ocwAddUrl = function(e){
  e.stopPropagation();
  const list = document.getElementById('ocw-url-list');
  const div = document.createElement('div');
  div.style.cssText = 'display:flex;gap:5px;margin-bottom:5px';
  div.innerHTML = `<input type="text" class="form-control" placeholder="https://" style="font-size:12px">
    <button class="btn btn-sm btn-default" onclick="this.closest('div').remove()"><i class="fa fa-times"></i></button>`;
  list.appendChild(div);
};

/* ── SECTION 4 옵션 ─────────────────────────────── */
window.ocwOpt = function(el, group){
  el.closest('.btn-opts').querySelectorAll('.bopt').forEach(b=>b.classList.remove('active'));
  el.classList.add('active');
  const valMap = {
    '정중함':'정중함','친근함':'친근함','전문적':'전문적',
    '기초':'기초','일반':'일반','심층':'심층',
    '적게(10)':10,'보통(30)':30,'많이(50)':50
  };
  ocwState.options[group] = valMap[el.textContent] || el.textContent;
};

/* ── 자동설정 시작 ────────────────────────────────── */
window.ocwStartGenerate = function(){
  if(!ocwState.selectedSmsIdx){
    alert('회원 ID를 먼저 조회해 주세요. (SECTION 0)');
    return;
  }

  document.getElementById('ocw-btn-start').disabled = true;
  show('ocw-prog-panel');
  hide('ocw-start-wrap');

  // 직접 입력 Q&A 수집
  const qaItems = [];
  document.querySelectorAll('#ocw-qa-list > div').forEach(div=>{
    const q = div.querySelector('input');
    const a = div.querySelector('textarea');
    if(q && a && q.value.trim()) qaItems.push({q:q.value.trim(), a:a.value.trim()});
  });

  api({
    action:    'generate_chatbot_setting',
    mem_id:    ocwState.memberId,
    sms_idx:   ocwState.selectedSmsIdx,
    facts:     ocwState.facts,
    extra_info:[ocwState.rawExtraInfo,(document.getElementById('ocw-extra2-text').value||'').trim()].filter(Boolean).join('\n\n'),
    method_a:  document.getElementById('chk-A').checked ? 1 : 0,
    method_b:  document.getElementById('chk-B').checked ? 1 : 0,
    qa_items:  qaItems,
    tone:      ocwState.options.tone,
    depth:     ocwState.options.depth,
    qty:       ocwState.options.qty
  }, function(res){
    if(res.code !== 200){
      document.getElementById('ocw-prog-msg').textContent = '오류: '+(res.message||'알 수 없는 오류');
      document.getElementById('ocw-btn-start').disabled = false;
      show('ocw-start-wrap');
      return;
    }
    runProgressAnimation(res.data);
  });
};

function runProgressAnimation(data){
  const steps = [
    {id:'ps0', pct:25,  msg:'팩트 정보를 분석하고 있습니다...'},
    {id:'ps1', pct:60,  msg:'시스템 프롬프트·응답 스타일·유저 프롬프트를 생성하고 있습니다...'},
    {id:'ps2', pct:85,  msg:'학습 데이터를 생성하고 있습니다...'},
    {id:'ps3', pct:100, msg:'챗봇 설정 저장 완료!'}
  ];
  let i = 0;
  function tick(){
    if(i >= steps.length){ showResult(data); return; }
    if(i > 0){
      document.getElementById(steps[i-1].id).classList.remove('active');
      document.getElementById(steps[i-1].id).classList.add('done');
    }
    document.getElementById(steps[i].id).classList.add('active');
    document.getElementById('ocw-prog-bar').style.width = steps[i].pct+'%';
    document.getElementById('ocw-prog-msg').textContent = steps[i].msg;
    i++;
    setTimeout(tick, 900);
  }
  setTimeout(tick, 400);
}

function showResult(data){
  hide('ocw-prog-panel');
  const learnMsg = (data && data.learn_count > 0)
    ? data.learn_count + '개 Q&A 학습 데이터 생성 완료'
    : '학습 데이터 없음 (방식 A 미선택)';
  document.getElementById('ocw-r-sysprompt').textContent  = (data && data.gpt_sysprompt)      || '';
  document.getElementById('ocw-r-style').textContent      = (data && data.message_style)       || '';
  document.getElementById('ocw-r-userprompt').textContent = (data && data.user_gpt_sysprompt)  || '';
  document.getElementById('ocw-r-learn').textContent      = learnMsg;
  // LLM 기본지식 활용 지침 표시
  var llmSupEl = document.getElementById('ocw-r-llm-supplement');
  var llmBalEl = document.getElementById('ocw-r-llm-balance');
  var llmFrEl  = document.getElementById('ocw-r-llm-free');
  if (llmSupEl) llmSupEl.textContent = (data && data.llm_prompt_supplement) ? data.llm_prompt_supplement : '(자동 저장됨 — 챗봇설정에서 확인)';
  if (llmBalEl) llmBalEl.textContent = (data && data.llm_prompt_balance)    ? data.llm_prompt_balance    : '(자동 저장됨 — 챗봇설정에서 확인)';
  if (llmFrEl)  llmFrEl.textContent  = (data && data.llm_prompt_free)       ? data.llm_prompt_free       : '(자동 저장됨 — 챗봇설정에서 확인)';
  // 챗봇 설정 페이지 링크에 sms_idx 추가 (관리자용 바로열기)
  const chatbotLink = document.getElementById('ocw-chatbot-link');
  if (chatbotLink && data && data.sms_idx) {
    chatbotLink.href = '/aimessage/onechat/index.php?sms_idx=' + data.sms_idx;
  }
  const r = document.getElementById('ocw-result');
  r.style.display = 'block';
  r.scrollIntoView({behavior:'smooth',block:'start'});
}

/* Enter 키로 조회 */
document.addEventListener('DOMContentLoaded', function(){
  const inp = document.getElementById('ocw-member-id');
  if(inp) inp.addEventListener('keydown', function(e){ if(e.key==='Enter') ocwSearch(); });
});

})();
