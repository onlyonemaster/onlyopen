/**
 * 원챗(OneChat) Admin Orchestrator Wizard JS
 * window.OCW 공개 API 제공
 */
(function(){'use strict';
var API='/admin/ajax/agent_orchestrator.php';
var currentJobId=null;
var pollTimer=null;

function $(id){return document.getElementById(id);}
function qsa(s){return document.querySelectorAll(s);}

function showToast(msg,type){
  var c=$('ocw-toast-container');if(!c)return;
  var t=document.createElement('div');
  t.className='ocw-toast ocw-toast-'+(type||'info');t.textContent=msg;
  c.appendChild(t);
  setTimeout(function(){t.classList.add('ocw-toast-out');setTimeout(function(){t.remove();},300);},3000);
}

function setStepActive(step){
  qsa('.ocw-step').forEach(function(s){s.classList.remove('active','completed');});
  var s=document.querySelector('.ocw-step[data-step="'+step+'"]');
  if(s)s.classList.add('active');
  for(var i=1;i<step;i++){
    var prev=document.querySelector('.ocw-step[data-step="'+i+'"]');
    if(prev){prev.classList.remove('active');prev.classList.add('completed');}
    var conn=$('ocw-conn-'+(i));
    if(conn)conn.classList.add('completed');
  }
}

function clearForm(){
  qsa('.ocw-input,.ocw-textarea').forEach(function(el){el.value='';});
  qsa('.ocw-select').forEach(function(el){el.selectedIndex=0;});
  showToast('폼이 초기화되었습니다.','info');
}

function addLog(msg,type){
  var c=$('ocw-log-container'),empty=$('ocw-log-empty');
  if(empty)empty.style.display='none';
  var d=document.createElement('div');
  d.className='ocw-log-entry ocw-log-'+(type||'info');
  d.innerHTML='<span class="ocw-log-time">'+new Date().toLocaleTimeString()+'</span> '+msg;
  c.appendChild(d);
  c.scrollTop=c.scrollHeight;
}

function getFormData(){
  return {
    name:$('ocw-name')?$('ocw-name').value.trim():'',
    party:$('ocw-party')?$('ocw-party').value.trim():'',
    election_type:$('ocw-elec-type')?$('ocw-elec-type').value:'',
    district:$('ocw-district')?$('ocw-district').value.trim():'',
    slogan:$('ocw-slogan')?$('ocw-slogan').value.trim():'',
    website:$('ocw-website')?$('ocw-website').value.trim():'',
    youtube:$('ocw-youtube')?$('ocw-youtube').value.trim():'',
    education:$('ocw-edu')?$('ocw-edu').value.trim():'',
    career:$('ocw-career')?$('ocw-career').value.trim():'',
    sns_instagram:$('ocw-sns-ig')?$('ocw-sns-ig').value.trim():'',
    sns_facebook:$('ocw-sns-fb')?$('ocw-sns-fb').value.trim():'',
    sns_twitter:$('ocw-sns-x')?$('ocw-sns-x').value.trim():'',
    sns_blog:$('ocw-sns-blog')?$('ocw-sns-blog').value.trim():'',
    options:{
      prompt:$('ocw-opt-prompt')?$('ocw-opt-prompt').checked:true,
      research:$('ocw-opt-research')?$('ocw-opt-research').checked:true,
      rag:$('ocw-opt-rag')?$('ocw-opt-rag').checked:true,
      bridge:$('ocw-opt-bridge')?$('ocw-opt-bridge').checked:false
    }
  };
}

function launchOrchestrator(){
  var data=getFormData();
  if(!data.name){showToast('후보자 이름을 입력하세요.','error');return;}
  if(!data.election_type){showToast('선거 유형을 선택하세요.','error');return;}

  var btn=$('ocw-launch-btn');btn.disabled=true;btn.textContent='실행 중...';
  var logCard=$('ocw-log-card'),resultCard=$('ocw-result-card');
  if(logCard)logCard.classList.remove('ocw-hidden');
  if(resultCard)resultCard.classList.add('ocw-hidden');
  $('ocw-log-container').innerHTML='';
  addLog('오케스트레이터 시작...','info');
  setStepActive(1);

  fetch(API+'?action=launch',{
    method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)
  }).then(function(r){return r.json();}).then(function(r){
    currentJobId=r.job_id;
    btn.textContent='실행 중... ('+currentJobId+')';
    if(r.status==='completed'){
      handleComplete(r);
    }else if(r.status==='failed'){
      addLog('실패: '+(r.message||'알 수 없는 오류'),'error');
      btn.disabled=false;btn.textContent='재시도';
    }else{
      pollStatus();
    }
  }).catch(function(e){
    addLog('네트워크 오류: '+e.message,'error');
    btn.disabled=false;btn.textContent='재시도';
  });
}

function pollStatus(){
  if(!currentJobId)return;
  fetch(API+'?action=status&job_id='+encodeURIComponent(currentJobId))
  .then(function(r){return r.json();}).then(function(r){
    var job=r.job;
    if(!job)return;
    var cs=job.current_step||0;
    setStepActive(Math.min(cs,5));
    if(cs>=2&&!job.steps.research)addLog('리서치 완료','success');
    if(cs>=3&&job.steps.prompt&&job.steps.prompt.status==='completed')addLog('프롬프트 생성 완료 ('+(job.prompt_layers?Object.keys(job.prompt_layers).length:0)+' 레이어)','success');
    if(cs>=4&&job.steps.inject&&job.steps.inject.status==='completed')addLog('데이터 주입 완료 ('+(job.steps.inject.result_summary?job.steps.inject.result_summary.chunks_created:0)+' 청크)','success');

    if(job.status==='completed'){handleComplete(job);}
    else if(job.status==='failed'){addLog('실패','error');clearPoll();}
    else{pollTimer=setTimeout(pollStatus,2000);}
  }).catch(function(){pollTimer=setTimeout(pollStatus,3000);});
}

function clearPoll(){if(pollTimer){clearTimeout(pollTimer);pollTimer=null;}}

function handleComplete(r){
  clearPoll();
  setStepActive(5);
  addLog('모든 작업 완료!','success');
  var btn=$('ocw-launch-btn');btn.disabled=false;btn.textContent='완료';

  var resultCard=$('ocw-result-card');
  if(resultCard)resultCard.classList.remove('ocw-hidden');

  // Show prompt output
  var layers=r.prompt_layers||(r.steps&&r.steps.prompt&&r.steps.prompt.result_summary?r.steps.prompt.result_summary.layers:null)||{};
  var out=$('ocw-prompt-output');
  if(out){
    var txt='';
    ['L1_PERSONA','L2_POLICY','L3_INTERACTION','L4_SAFETY'].forEach(function(k){
      if(layers[k])txt+='=== '+k+' ===\n'+layers[k]+'\n\n';
    });
    out.textContent=txt||JSON.stringify(layers,null,2);
  }

  // Show stats
  var statsOut=$('ocw-stats-output');
  if(statsOut){
    var inj=r.steps&&r.steps.inject?r.steps.inject.result_summary:{};
    statsOut.innerHTML='<div class="ocw-stat-card"><div class="ocw-stat-value">'+(inj.chunks_created||0)+'</div><div class="ocw-stat-label">생성된 청크</div></div>'+
      '<div class="ocw-stat-card"><div class="ocw-stat-value">'+(inj.websites_crawled||0)+'</div><div class="ocw-stat-label">크롤링 사이트</div></div>'+
      '<div class="ocw-stat-card"><div class="ocw-stat-value">'+(inj.youtube_processed||0)+'</div><div class="ocw-stat-label">유튜브 영상</div></div>'+
      '<div class="ocw-stat-card"><div class="ocw-stat-value">1</div><div class="ocw-stat-label">AI 에이전트</div></div>';
  }
  showToast('후보자 '+(r.input?r.input.name:'')+' 등록 완료!','success');
}

function copyPrompt(){
  var out=$('ocw-prompt-output');if(!out||!out.textContent)return;
  navigator.clipboard.writeText(out.textContent).then(function(){showToast('복사 완료!','success');});
}

// Result tabs
qsa('.ocw-result-tab').forEach(function(tab){
  tab.addEventListener('click',function(){
    qsa('.ocw-result-tab').forEach(function(t){t.classList.remove('active');});
    this.classList.add('active');
    var target=this.dataset.result;
    qsa('.ocw-result-panel').forEach(function(p){p.classList.remove('active');});
    var panel=$('ocw-result-'+target);
    if(panel)panel.classList.add('active');
  });
});

window.OCW={
  launchOrchestrator:launchOrchestrator,
  clearForm:clearForm,
  copyPrompt:copyPrompt,
  getFormData:getFormData
};
})();
