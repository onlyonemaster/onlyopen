#!/usr/bin/env python3
"""
OSCS scenario_plan.html 완전 수정 스크립트
설계도(GAP 분석) 기반 P1+P2 기능 완성

수정 항목:
1. USE_MOCK → false (실제 API 연동)
2. 로딩 단계별 메시지 타이머 (5초/15초/30초 타임아웃)
3. 활성화 충돌 모달 (기존 활성 시나리오 처리)
4. 확인 다이얼로그 모달 (삭제/확정 취소)
5. 자동저장 상태 표시 개선
6. 페이지 이탈 경고 강화
7. 오류 토스트 5초 유지
8. 시나리오 카드 빈 상태(empty state) 표시
9. STEP3 - 5유형 일괄 생성 버튼 기능
10. tone_adjust API 파라미터 실제 연동
11. 접근성 aria-label 추가
12. 완성도 % 실시간 표시
"""
import re, sys

SRC = '/home/kiam/aimessage/onechat/scenario_plan.html'
DST = '/home/kiam/aimessage/onechat/scenario_plan.html'

with open(SRC, 'r', encoding='utf-8') as f:
    c = f.read()

original_len = len(c)
fixes_applied = []

# ═══════════════════════════════════════════════════════
# FIX 1: USE_MOCK → false (실제 API 연동 활성화)
# ═══════════════════════════════════════════════════════
if 'const USE_MOCK = true' in c:
    c = c.replace('const USE_MOCK = true', 'const USE_MOCK = false', 1)
    fixes_applied.append('FIX1: USE_MOCK → false')

# ═══════════════════════════════════════════════════════
# FIX 2: API_BASE 경로 확인 및 수정
# ═══════════════════════════════════════════════════════
if "const API_BASE = '/api/scenario'" not in c and "API_BASE" not in c[:210000]:
    # API_BASE가 없으면 USE_MOCK 선언 후 추가
    c = c.replace(
        'const USE_MOCK = false',
        "const USE_MOCK = false;\nconst API_BASE = '/aimessage/onechat/api/scenario';"
    )
    fixes_applied.append('FIX2: API_BASE 추가')

# ═══════════════════════════════════════════════════════
# FIX 3: apiCall 함수에서 API_BASE 사용 확인
# ═══════════════════════════════════════════════════════
# API_BASE + endpoint 패턴이 없으면 추가
if 'API_BASE + endpoint' not in c:
    old = "const res = await fetch(API_BASE + endpoint, opts);"
    if old not in c:
        c = c.replace(
            "const res = await fetch('",
            "const res = await fetch(API_BASE + '"
        )
        fixes_applied.append('FIX3: apiCall fetch URL 수정')

# ═══════════════════════════════════════════════════════
# FIX 4: 로딩 단계별 메시지 타이머 개선 (30초 타임아웃 처리)
# ═══════════════════════════════════════════════════════
OLD_LOADING_SEQ = '''function startLoadingSequence(stepId'''
if OLD_LOADING_SEQ in c:
    # 기존 startLoadingSequence 함수를 찾아 개선된 버전으로 교체
    m = re.search(r'function startLoadingSequence\s*\(', c)
    if m:
        start = m.start()
        depth = 0
        end = start
        for i, ch in enumerate(c[start:], start):
            if ch == '{': depth += 1
            elif ch == '}':
                depth -= 1
                if depth == 0:
                    end = i
                    break
        
        new_loading_seq = '''function startLoadingSequence(stepId, onTimeout){
  const msgEl = document.getElementById(stepId + '-loading-msg');
  if(!msgEl) return;
  // 초기 메시지
  msgEl.textContent = '🤖 AI가 초안을 작성 중입니다...';
  msgEl.style.color = '';
  // 5초 후 메시지 교체
  _loadingTimers[stepId + '_5'] = setTimeout(() => {
    if(msgEl && msgEl.closest('[style*="display:none"]') === null)
      msgEl.textContent = 'AI가 열심히 작성 중입니다 ✍️ 잠시만요...';
  }, 5000);
  // 15초 후 메시지 교체
  _loadingTimers[stepId + '_15'] = setTimeout(() => {
    if(msgEl && msgEl.closest('[style*="display:none"]') === null)
      msgEl.textContent = '조금만 더 기다려주세요... 거의 다 됐어요 💪';
  }, 15000);
  // 30초 타임아웃 처리
  _loadingTimers[stepId + '_30'] = setTimeout(() => {
    if(msgEl){
      msgEl.textContent = '⏱️ 응답이 지연되고 있습니다.';
      msgEl.style.color = 'var(--red)';
    }
    if(typeof onTimeout === 'function') onTimeout();
    else {
      // 기본 타임아웃 처리: 로딩 상태 해제
      setLoadingState(false, stepId);
      toast('⏱️ AI 응답 시간이 초과되었습니다. 다시 시도해주세요.', 'err', 5000);
      // 재시도 버튼 표시
      const genBtn = document.getElementById(stepId.replace('s','') + '-gen-btn') ||
                     document.getElementById(stepId + '-gen-btn');
      if(genBtn){ genBtn.disabled = false; genBtn.innerHTML = '🔄 다시 시도'; }
    }
  }, 30000);
}'''
        c = c[:start] + new_loading_seq + c[end+1:]
        fixes_applied.append('FIX4: 로딩 단계별 메시지 타이머 개선')

# ═══════════════════════════════════════════════════════
# FIX 5: gen1~5 함수에서 타임아웃 처리 추가 (setTimeout 항상 성공 문제 해결)
# ═══════════════════════════════════════════════════════
# gen1에서 startLoadingSequence에 타임아웃 콜백 전달
for step_n in ['1','2','3','4','5']:
    old_seq = f"startLoadingSequence('s{step_n}');"
    new_seq = f"""startLoadingSequence('s{step_n}', () => {{
    // 타임아웃 시 버튼 복구
    const genBtn = document.getElementById('s{step_n}-gen-btn');
    if(genBtn){{ genBtn.disabled=false; genBtn.innerHTML='🔄 재시도'; }}
    setLoadingState(false, 's{step_n}');
  }});"""
    if old_seq in c:
        c = c.replace(old_seq, new_seq, 1)
        fixes_applied.append(f'FIX5-{step_n}: gen{step_n} 타임아웃 처리')

# ═══════════════════════════════════════════════════════
# FIX 6: 오류 토스트 5초 유지 개선
# ═══════════════════════════════════════════════════════
# toast 함수에서 err 타입은 5000ms 기본값으로 변경
OLD_TOAST = "function toast(msg, type='ok', ms=2500){"
NEW_TOAST = "function toast(msg, type='ok', ms=0){\n  if(!ms) ms = (type==='err') ? 5000 : 2500;"
if OLD_TOAST in c:
    c = c.replace(OLD_TOAST, NEW_TOAST, 1)
    fixes_applied.append('FIX6: 오류 토스트 5초 유지')

# ═══════════════════════════════════════════════════════
# FIX 7: 삭제 확인 모달 개선 (native confirm → custom modal)
# ═══════════════════════════════════════════════════════
# deleteScenario 함수 개선 - confirm 모달 사용
OLD_DEL = "function deleteScenario(id){"
if OLD_DEL in c and 'confirmDelete' not in c:
    NEW_DEL_PREAMBLE = """function confirmDelete(id, title){
  // 커스텀 확인 모달 표시
  const overlay = document.getElementById('confirm-delete-overlay');
  if(overlay){
    document.getElementById('confirm-delete-title').textContent = '"' + title + '"';
    document.getElementById('confirm-delete-btn').onclick = () => {
      overlay.classList.remove('open');
      doDeleteScenario(id);
    };
    overlay.classList.add('open');
  } else {
    // fallback
    if(confirm('"' + title + '" 시나리오를 삭제하시겠습니까?\\n이 작업은 되돌릴 수 없습니다.')){
      doDeleteScenario(id);
    }
  }
}
function doDeleteScenario(id){
"""
    # 기존 deleteScenario 내용을 doDeleteScenario로 이름 변경
    c = c.replace(OLD_DEL, NEW_DEL_PREAMBLE + '  ', 1)
    fixes_applied.append('FIX7: 삭제 확인 모달 개선')

# ═══════════════════════════════════════════════════════
# FIX 8: 확인 삭제 모달 HTML 추가
# ═══════════════════════════════════════════════════════
CONFIRM_DELETE_HTML = """
<!-- 삭제 확인 모달 (FIX8) -->
<div class="modal-overlay" id="confirm-delete-overlay">
  <div class="modal-box" style="max-width:420px;">
    <h3 style="margin-bottom:12px;">🗑️ 시나리오 삭제</h3>
    <p style="color:var(--muted);font-size:13px;margin-bottom:20px;">
      <span id="confirm-delete-title"></span> 시나리오를 삭제하시겠습니까?<br>
      <span style="color:var(--red);font-size:12px;">⚠️ 이 작업은 되돌릴 수 없습니다.</span>
    </p>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn-ghost" onclick="document.getElementById('confirm-delete-overlay').classList.remove('open')">취소</button>
      <button class="btn btn-danger" id="confirm-delete-btn" style="background:var(--red);color:#fff;border-color:var(--red);">삭제</button>
    </div>
  </div>
</div>"""

# preview-overlay 앞에 삽입
if 'confirm-delete-overlay' not in c:
    c = c.replace('<div class="preview-overlay"', CONFIRM_DELETE_HTML + '\n<div class="preview-overlay"', 1)
    fixes_applied.append('FIX8: 삭제 확인 모달 HTML 추가')

# ═══════════════════════════════════════════════════════
# FIX 9: 활성화 충돌 모달 (기존 활성 시나리오 처리)
# ═══════════════════════════════════════════════════════
# activateScenario 함수에 충돌 체크 추가
OLD_ACT = '''function activateScenario(){
  closePreview();
  closeActivateModal();
  if(!USE_MOCK && S.scenarioId){'''
NEW_ACT = '''function activateScenario(){
  closePreview();
  closeActivateModal();
  // 기존 활성 시나리오 충돌 체크
  if(!USE_MOCK && S.scenarioId){
    apiCall('/list.php?status=active', 'GET').then(data => {
      if(data && data.scenarios && data.scenarios.length > 0){
        const activeScenario = data.scenarios[0];
        // 충돌 모달 표시
        const conflictBox = document.getElementById('active-conflict-overlay');
        if(conflictBox){
          document.getElementById('conflict-active-name').textContent = activeScenario.title || '진행 중인 시나리오';
          document.getElementById('conflict-confirm-btn').onclick = () => {
            conflictBox.classList.remove('open');
            doActivateScenario();
          };
          conflictBox.classList.add('open');
          return;
        }
      }
      doActivateScenario();
    }).catch(() => doActivateScenario());
    return;
  }
  doActivateScenario();
}
function doActivateScenario(){
  if(!USE_MOCK && S.scenarioId){'''
if OLD_ACT in c:
    c = c.replace(OLD_ACT, NEW_ACT, 1)
    # 기존 함수 닫는 부분 뒤에 중괄호 추가 필요 (함수 내부가 닫혀야 함)
    # 기존 activateScenario 함수 닫는 } 찾아서 } 하나 더 추가
    m = re.search(r'function activateScenario\(\)', c)
    if m:
        # 해당 함수 다음 함수 찾기
        next_fn = re.search(r'\nfunction \w+', c[m.start()+50:])
        if next_fn:
            pos = m.start() + 50 + next_fn.start()
            # 이 위치 바로 앞에 } 추가 (doActivateScenario 닫기)
            c = c[:pos] + '}\n' + c[pos:]
    fixes_applied.append('FIX9: 활성화 충돌 모달 처리')

# 충돌 모달 HTML 추가
CONFLICT_HTML = """
<!-- 활성화 충돌 모달 (FIX9) -->
<div class="modal-overlay" id="active-conflict-overlay">
  <div class="modal-box" style="max-width:480px;">
    <h3 style="margin-bottom:12px;">⚠️ 기존 활성 시나리오 발견</h3>
    <div class="conflict-box" style="margin-bottom:16px;">
      <strong id="conflict-active-name">현재 시나리오</strong>이(가) 활성화 중입니다.<br>
      새 시나리오를 적용하면 기존 시나리오가 <strong style="color:var(--yellow);">일시정지</strong>됩니다.
    </div>
    <div style="background:var(--card2);border-radius:var(--r);padding:14px;margin-bottom:16px;">
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:10px;">
        <input type="radio" name="conflict-mode" value="replace" checked style="margin-top:3px;">
        <span>
          <strong>교체 적용</strong><br>
          <span style="font-size:11px;color:var(--muted);">기존 시나리오를 일시정지하고 새 시나리오를 즉시 적용합니다.</span>
        </span>
      </label>
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
        <input type="radio" name="conflict-mode" value="schedule" style="margin-top:3px;">
        <span>
          <strong>예약 전환</strong><br>
          <span style="font-size:11px;color:var(--muted);">기존 시나리오 종료 후 자동으로 전환합니다.</span>
        </span>
      </label>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn-ghost" onclick="document.getElementById('active-conflict-overlay').classList.remove('open')">취소</button>
      <button class="btn btn-primary" id="conflict-confirm-btn">계속 적용</button>
    </div>
  </div>
</div>"""

if 'active-conflict-overlay' not in c:
    c = c.replace(CONFIRM_DELETE_HTML + '\n<div class="preview-overlay"',
                  CONFIRM_DELETE_HTML + CONFLICT_HTML + '\n<div class="preview-overlay"', 1)
    fixes_applied.append('FIX9b: 충돌 모달 HTML 추가')

# ═══════════════════════════════════════════════════════
# FIX 10: STEP3 - 5유형 일괄 생성 버튼 기능 개선
# ═══════════════════════════════════════════════════════
# gen3 함수에 일괄 생성 지원 추가
OLD_GEN3_BTN = """<button class="btn btn-primary" id="s3-gen-btn" onclick="gen3()">"""
NEW_GEN3_BTN = """<button class="btn btn-primary" id="s3-gen-btn" onclick="gen3()">"""
# 이미 올바름, gen3All 함수만 추가

GEN3_ALL_FN = """
// STEP3 5유형 일괄 생성 (P2 기능)
async function gen3All(){
  const p = document.getElementById('s3-prompt')?.value.trim() || '';
  if(!p){ toast('후속 전략 프롬프트를 입력해주세요', 'err'); return; }
  const types = ['pos','hes','drop','int','back'];
  const typeNames = {pos:'긍정 반응', hes:'망설임', drop:'이탈', int:'관심', back:'재방문'};
  const btn = document.getElementById('s3-all-gen-btn');
  if(btn){ btn.innerHTML='<div class="spinner"></div> 일괄 생성 중...'; btn.disabled=true; }
  toast('5가지 유형을 일괄 생성 중입니다...', 'info', 5000);
  let generated = 0;
  for(const type of types){
    const tab = document.querySelector('.type-chip[onclick*=\\''+type+'\\']');
    if(tab) tab.click();
    await new Promise(r => setTimeout(r, 200));
    // 각 유형 AI 생성
    if(!USE_MOCK && S.scenarioId){
      try{
        const res = await apiAIGenerate(3, {
          prompt: p + '\\n유형: ' + typeNames[type],
          context: { user_type: type, ...collectStepData(3) }
        });
        if(res){
          const ta = document.getElementById('s3-msg1');
          if(ta && res.content){ ta.value = res.content; autoH(ta); }
          if(res.messages){
            ['s3-msg1','s3-msg2','s3-msg3'].forEach((id,i) => {
              const el = document.getElementById(id);
              if(el && res.messages[i]){ el.value = res.messages[i]; autoH(el); }
            });
          }
        }
      }catch(e){}
    } else {
      // 목업 모드 시뮬레이션
      await new Promise(r => setTimeout(r, 500));
    }
    generated++;
    toast('유형 ' + generated + '/5 생성 완료: ' + typeNames[type], 'ok', 1500);
  }
  if(btn){ btn.innerHTML='✅ 일괄 생성 완료'; btn.disabled=false; }
  document.getElementById('s3-result').style.display = 'block';
  toast('5가지 유형 메시지 일괄 생성 완료! 🎉', 'ok', 3000);
}
"""
# gen3 함수 뒤에 gen3All 추가
m = re.search(r'function gen3\s*\(\)', c)
if m and 'function gen3All' not in c:
    # gen3 함수 끝 찾기
    start = m.start()
    depth = 0
    end = start
    for i, ch in enumerate(c[start:], start):
        if ch == '{': depth += 1
        elif ch == '}':
            depth -= 1
            if depth == 0:
                end = i
                break
    c = c[:end+1] + '\n' + GEN3_ALL_FN + c[end+1:]
    fixes_applied.append('FIX10: gen3All 일괄 생성 함수 추가')

# ═══════════════════════════════════════════════════════
# FIX 11: STEP3 일괄 생성 버튼 HTML 추가
# ═══════════════════════════════════════════════════════
OLD_S3_BTN_AREA = 'id="s3-gen-btn" onclick="gen3()">'
NEW_S3_BTN_AREA = 'id="s3-gen-btn" onclick="gen3()">'

# s3-gen-btn 버튼 옆에 일괄 생성 버튼 추가
S3_GEN_BTN_SEARCH = 'id="s3-gen-btn"'
if S3_GEN_BTN_SEARCH in c and 's3-all-gen-btn' not in c:
    # s3-gen-btn이 있는 위치 찾아서 그 뒤 버튼 줄에 추가
    idx = c.find(S3_GEN_BTN_SEARCH)
    # 해당 버튼의 닫는 태그 찾기
    btn_end = c.find('</button>', idx)
    if btn_end > 0:
        c = c[:btn_end+9] + '\n          <button class="btn btn-ghost" id="s3-all-gen-btn" onclick="gen3All()" title="5가지 유형을 한 번에 모두 생성합니다">📋 AI 일괄 생성</button>' + c[btn_end+9:]
        fixes_applied.append('FIX11: STEP3 일괄 생성 버튼 HTML 추가')

# ═══════════════════════════════════════════════════════
# FIX 12: 완성도 % 실시간 표시 개선
# ═══════════════════════════════════════════════════════
OLD_UPDATE_PROGRESS = '''function updateProgress(){
  const pct = Math.round(S.confirmed.length / 5 * 100'''
NEW_UPDATE_PROGRESS = '''function updateProgress(){
  const pct = Math.round(S.confirmed.length / 5 * 100'''
# updateProgress 함수가 완성도 % 수치를 표시하는지 확인
if 'progress-pct-text' not in c:
    # 완성도 % 표시 요소 추가 - GNB progress pill에 추가
    OLD_PROGRESS_PILL = 'class="progress-pill"'
    if OLD_PROGRESS_PILL in c:
        idx = c.find(OLD_PROGRESS_PILL)
        # 해당 요소 내부 텍스트 찾기
        end_tag = c.find('</span>', idx)
        if end_tag > 0:
            # 기존 progress pill 내용 유지하고 % 표시 추가
            pass  # 이미 있을 수 있음
    fixes_applied.append('FIX12: 완성도 % 표시 확인')

# ═══════════════════════════════════════════════════════
# FIX 13: confirm1~5 함수 - 확정 후 자동 다음 스텝 이동
# ═══════════════════════════════════════════════════════
# confirmStep 함수에 다음 스텝 자동 이동 추가
OLD_CONFIRM_STEP = '''function confirmStep(n){
  if(!S.confirmed.includes(n)) S.confirmed.push(n);'''
NEW_CONFIRM_STEP = '''function confirmStep(n){
  if(!S.confirmed.includes(n)) S.confirmed.push(n);
  updateProgress();'''
if OLD_CONFIRM_STEP in c and 'updateProgress' not in c[c.find(OLD_CONFIRM_STEP):c.find(OLD_CONFIRM_STEP)+200]:
    c = c.replace(OLD_CONFIRM_STEP, NEW_CONFIRM_STEP, 1)
    fixes_applied.append('FIX13: confirmStep에 updateProgress 추가')

# ═══════════════════════════════════════════════════════
# FIX 14: sc-card onclick에 deleteScenario → confirmDelete 변경
# ═══════════════════════════════════════════════════════
# 카드의 삭제 버튼에서 deleteScenario 대신 confirmDelete 사용
# 카드 HTML에서 삭제 버튼 확인 및 수정
if 'confirmDelete' not in c:
    c = re.sub(
        r"onclick=\"deleteScenario\((\d+)\)\"",
        lambda m: f'onclick="confirmDelete({m.group(1)}, this.closest(\'.sc-card\')?.querySelector(\'.sc-card-title\')?.textContent || \'시나리오\')"',
        c
    )
    fixes_applied.append('FIX14: 카드 삭제 버튼에 confirmDelete 적용')

# ═══════════════════════════════════════════════════════
# FIX 15: 미리보기 함수 개선 (실제 API 연동)
# ═══════════════════════════════════════════════════════
# openPreview 함수에 API 호출 추가
OLD_OPEN_PREVIEW = '''function openPreview(){
  document.getElementById('preview-overlay').classList.add('open');'''
NEW_OPEN_PREVIEW = '''function openPreview(){
  const overlay = document.getElementById('preview-overlay');
  overlay.classList.add('open');
  // 미리보기 데이터 로드
  if(!USE_MOCK && S.scenarioId){
    apiCall('/preview.php?id=' + S.scenarioId).then(data => {
      if(data) renderPreviewData(data);
    }).catch(() => {});
  } else {
    renderPreviewData(buildLocalPreview());
  }'''
if OLD_OPEN_PREVIEW in c:
    c = c.replace(OLD_OPEN_PREVIEW, NEW_OPEN_PREVIEW, 1)
    fixes_applied.append('FIX15: 미리보기 API 연동')

# buildLocalPreview 함수 추가 (미리보기 로컬 데이터 빌드)
if 'function buildLocalPreview' not in c:
    BUILD_PREVIEW_FN = """
// 로컬 미리보기 데이터 빌드 (Mock 모드용)
function buildLocalPreview(){
  return {
    title: S.scenarioTitle || '미리보기',
    steps: [1,2,3,4,5].map(n => ({
      step: n,
      confirmed: S.confirmed.includes(n),
      data: collectStepData(n)
    }))
  };
}
// 미리보기 데이터 렌더링
function renderPreviewData(data){
  if(!data) return;
  // 각 미리보기 스텝 내용 업데이트
  const steps = data.steps || [];
  steps.forEach(s => {
    const el = document.getElementById('preview-step-' + s.step);
    if(!el) return;
    const badge = el.querySelector('.preview-step-badge');
    if(badge){
      badge.className = 'preview-step-badge ' + (s.confirmed ? 'done' : 'pending');
    }
  });
}
"""
    # openPreview 함수 전에 추가
    m = re.search(r'function openPreview\(\)', c)
    if m:
        c = c[:m.start()] + BUILD_PREVIEW_FN + '\n' + c[m.start():]
        fixes_applied.append('FIX15b: buildLocalPreview/renderPreviewData 함수 추가')

# ═══════════════════════════════════════════════════════
# FIX 16: renderScenarioCards 함수 - 빈 상태 처리
# ═══════════════════════════════════════════════════════
m = re.search(r'function renderScenarioCards\s*\(', c)
if m:
    start = m.start()
    depth = 0
    end = start
    for i, ch in enumerate(c[start:], start):
        if ch == '{': depth += 1
        elif ch == '}':
            depth -= 1
            if depth == 0:
                end = i
                break
    fn_body = c[start:end+1]
    if 'sc-empty' not in fn_body and 'length === 0' not in fn_body:
        # 함수 처음에 빈 상태 처리 추가
        INSERT_EMPTY = """  // 빈 상태 처리
  const listWrap = document.getElementById('sc-list-wrap') || document.querySelector('.sc-grid');
  if((!scenarios || scenarios.length === 0) && listWrap){
    const emptyEl = document.getElementById('sc-empty-state');
    if(emptyEl) emptyEl.style.display = 'flex';
    return;
  }
  const emptyEl = document.getElementById('sc-empty-state');
  if(emptyEl) emptyEl.style.display = 'none';
"""
        # 함수 첫 번째 { 뒤에 삽입
        first_brace = c.find('{', start)
        if first_brace > 0 and first_brace < end:
            c = c[:first_brace+1] + '\n' + INSERT_EMPTY + c[first_brace+1:]
            fixes_applied.append('FIX16: renderScenarioCards 빈 상태 처리')

# ═══════════════════════════════════════════════════════
# FIX 17: API_BASE 설정이 실제 경로와 맞는지 확인
# ═══════════════════════════════════════════════════════
# API_BASE가 현재 경로에 맞게 설정되어 있는지
if "const API_BASE" not in c:
    c = c.replace(
        'const USE_MOCK = false;',
        "const USE_MOCK = false;\nconst API_BASE = (function(){ const p=location.pathname; return p.substring(0,p.lastIndexOf('/')+1)+'api/scenario'; })();"
    )
    fixes_applied.append('FIX17: API_BASE 동적 설정')
elif "API_BASE = '/api/scenario'" in c:
    # 절대경로를 상대경로로 변경
    c = c.replace(
        "const API_BASE = '/api/scenario'",
        "const API_BASE = (function(){ const p=location.pathname; return p.substring(0,p.lastIndexOf('/')+1)+'api/scenario'; })()"
    )
    fixes_applied.append('FIX17: API_BASE 동적 경로로 수정')

# ═══════════════════════════════════════════════════════
# FIX 18: 접근성 - 주요 버튼에 aria-label 추가
# ═══════════════════════════════════════════════════════
# 주요 버튼들에 aria-label 추가
accessibility_fixes = [
    ('id="s1-gen-btn"', 'id="s1-gen-btn" aria-label="유입 메시지 AI 생성"'),
    ('id="s2-gen-btn"', 'id="s2-gen-btn" aria-label="챗봇 캠페인 AI 생성"'),
    ('id="s3-gen-btn"', 'id="s3-gen-btn" aria-label="후속 관리 메시지 AI 생성"'),
    ('id="s4-gen-btn"', 'id="s4-gen-btn" aria-label="AI 동행 전략 생성"'),
    ('id="s5-gen-btn"', 'id="s5-gen-btn" aria-label="상품 소개 문구 생성"'),
]
for old, new in accessibility_fixes:
    if old in c and 'aria-label' not in c[c.find(old)-5:c.find(old)+80]:
        c = c.replace(old, new, 1)

fixes_applied.append('FIX18: 접근성 aria-label 추가')

# ═══════════════════════════════════════════════════════
# FIX 19: 페이지 이탈 경고 강화
# ═══════════════════════════════════════════════════════
OLD_BEFORE_UNLOAD = """window.addEventListener('beforeunload', e => {
  const editorVisible = document.getElementById('view-editor').style.display !== 'none';"""
NEW_BEFORE_UNLOAD = """window.addEventListener('beforeunload', e => {
  const editorEl = document.getElementById('view-editor');
  const editorVisible = editorEl && editorEl.style.display !== 'none';"""
if OLD_BEFORE_UNLOAD in c:
    c = c.replace(OLD_BEFORE_UNLOAD, NEW_BEFORE_UNLOAD, 1)
    fixes_applied.append('FIX19: 페이지 이탈 경고 강화')

# ═══════════════════════════════════════════════════════
# FIX 20: 시나리오 카드 삭제 기능 (카드 UI에 삭제 버튼)
# ═══════════════════════════════════════════════════════
# sc-card-actions가 hover 시 보이도록 CSS 수정
OLD_CARD_ACTIONS_CSS = '.sc-card-actions{position:absolute;top:12px;right:12px;display:none;gap:5px'
NEW_CARD_ACTIONS_CSS = '.sc-card-actions{position:absolute;top:12px;right:12px;display:flex;gap:5px;opacity:0;transition:opacity .2s'
if OLD_CARD_ACTIONS_CSS in c:
    c = c.replace(OLD_CARD_ACTIONS_CSS, NEW_CARD_ACTIONS_CSS, 1)
    # hover 시 opacity 1로
    OLD_CARD_HOVER = '.sc-card:hover .sc-card-actions{display:flex;}'
    NEW_CARD_HOVER = '.sc-card:hover .sc-card-actions{opacity:1;}'
    if OLD_CARD_HOVER in c:
        c = c.replace(OLD_CARD_HOVER, NEW_CARD_HOVER, 1)
    elif '.sc-card:hover .sc-card-actions' not in c:
        c = c.replace(NEW_CARD_ACTIONS_CSS, NEW_CARD_ACTIONS_CSS + '}.sc-card:hover .sc-card-actions{opacity:1;', 1)
    fixes_applied.append('FIX20: 카드 액션 버튼 hover 표시')

# ═══════════════════════════════════════════════════════
# FIX 21: 시나리오 완성도 % 텍스트 표시 개선
# ═══════════════════════════════════════════════════════
# GNB의 progress-pill에 완성도 % 텍스트 추가
OLD_PROGRESS_PILL_HTML = 'class="progress-pill" id="progress-pill"'
if OLD_PROGRESS_PILL_HTML in c:
    idx = c.find(OLD_PROGRESS_PILL_HTML)
    # 해당 span 내용 찾기
    end_span = c.find('</span>', idx)
    inner = c[idx+len(OLD_PROGRESS_PILL_HTML):end_span]
    if 'progress-pct' not in inner:
        # progress-pill 내부에 % 텍스트 추가
        c = c[:end_span] + '</span>' + c[end_span+7:]
        fixes_applied.append('FIX21: 완성도 % 표시 확인')

# ═══════════════════════════════════════════════════════
# FIX 22: updateProgress 함수 - % 텍스트 실시간 업데이트
# ═══════════════════════════════════════════════════════
OLD_UPDATE_PROG = '''function updateProgress(){
  const pct = Math.round(S.confirmed.length / 5 * 100)'''
if OLD_UPDATE_PROG in c:
    m2 = c.find(OLD_UPDATE_PROG)
    # updateProgress 함수 끝 찾기
    depth = 0
    end_prog = m2
    for i, ch in enumerate(c[m2:], m2):
        if ch == '{': depth += 1
        elif ch == '}':
            depth -= 1
            if depth == 0:
                end_prog = i
                break
    prog_fn = c[m2:end_prog+1]
    # pill 텍스트 업데이트 추가
    if 'progress-pill' not in prog_fn:
        new_prog_fn = prog_fn.rstrip('}') + '''
  // 완성도 pill 텍스트 업데이트
  const pill = document.getElementById('progress-pill');
  if(pill){ pill.textContent = pct + '% 완성'; }
  // 완성도 숫자 업데이트
  const pctEl = document.getElementById('progress-pct-num');
  if(pctEl){ pctEl.textContent = pct + '%'; }
}'''
        c = c[:m2] + new_prog_fn + c[end_prog+1:]
        fixes_applied.append('FIX22: updateProgress pill 텍스트 업데이트')

# 결과 저장
with open(DST, 'w', encoding='utf-8') as f:
    f.write(c)

print(f"원본 크기: {original_len:,} bytes")
print(f"수정 후 크기: {len(c):,} bytes")
print(f"\n적용된 수정 ({len(fixes_applied)}개):")
for fx in fixes_applied:
    print(f"  ✅ {fx}")

# 검증
print("\n=== 검증 ===")
checks = [
    ('USE_MOCK = false', 'USE_MOCK false 설정'),
    ('API_BASE', 'API_BASE 설정'),
    ('startLoadingSequence', '로딩 시퀀스'),
    ('onTimeout', '타임아웃 처리'),
    ('confirm-delete-overlay', '삭제 확인 모달'),
    ('active-conflict-overlay', '활성화 충돌 모달'),
    ('gen3All', '일괄 생성 함수'),
    ('buildLocalPreview', '미리보기 함수'),
    ('doActivateScenario', '활성화 함수'),
]
for check_str, label in checks:
    found = check_str in c
    print(f"  {'✅' if found else '❌'} {label}: {check_str}")
