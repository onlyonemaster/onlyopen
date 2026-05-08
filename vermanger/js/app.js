/**
 * VerManager Frontend — Onlyone OneChat
 * 버전 관리 시스템: AJAX 통신, 모달, 필터, 릴리즈, 동기화
 */

const API = '/vermanger/api/';

// ── Toast ──
function toast(msg, type='success', duration=3500) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'vmtoast vmtoast-'+type; t.style.display='block';
  clearTimeout(t._tid);
  t._tid = setTimeout(()=>t.style.display='none', duration);
}

// ── Modal ──
function openRelease() {
  fetch(API+'improvements?unversioned=true')
    .then(r=>r.json())
    .then(imps=>{
      const lst = document.getElementById('relImpLst');
      if (Array.isArray(imps) && imps.length>0) {
        lst.innerHTML = imps.map(i=>
          `<label class="vmio"><input type="checkbox" name="rImps[]" value="${i.id}"><span>[${i.category}] ${i.title}</span></label>`
        ).join('');
      } else {
        lst.innerHTML = '<p class="vm-empty">미배정 개선사항이 없습니다.</p>';
      }
      // Auto-suggest release type based on count
      fetch(API+'stats').then(r=>r.json()).then(s=>{
        const uv = s.unversioned||0;
        let sug='patch'; if(uv>=5&&uv<10)sug='minor'; if(uv>=10)sug='major';
        document.querySelectorAll('input[name="rType"]').forEach(r=>{ r.checked=(r.value===sug); });
      });
      document.getElementById('relMod').style.display='flex';
    })
    .catch(()=>toast('데이터 로드 실패','error'));
}
function openAddImp() {
  document.getElementById('eImpId').value = '';
  document.getElementById('iTitle').value = '';
  document.getElementById('iDesc').value = '';
  document.getElementById('iCat').value = 'UI';
  document.getElementById('iStat').value = '완료';
  document.getElementById('impModT').textContent = '➕ 개선사항 추가';
  document.getElementById('saveImpBtn').textContent = '저장';
  document.getElementById('impMod').style.display='flex';
}
function editImp(id) {
  fetch(API+'improvements')
    .then(r=>r.json())
    .then(imps=>{
      const imp = imps.find(i=>i.id===id);
      if (!imp) return toast('찾을 수 없음', 'error');
      document.getElementById('eImpId').value = imp.id;
      document.getElementById('iTitle').value = imp.title;
      document.getElementById('iDesc').value = imp.description;
      document.getElementById('iCat').value = imp.category;
      document.getElementById('iStat').value = imp.status;
      document.getElementById('impModT').textContent = '✏️ 개선사항 수정';
      document.getElementById('saveImpBtn').textContent = '수정';
      document.getElementById('impMod').style.display='flex';
    })
    .catch(()=>toast('데이터 로드 실패','error'));
}
function delImp(id) {
  if (!confirm('정말 삭제하시겠습니까?')) return;
  fetch(API+'improvements/'+id, {method:'DELETE'})
    .then(r=>r.json())
    .then(d=>{ toast('삭제 완료'); setTimeout(()=>location.reload(),500); })
    .catch(()=>toast('삭제 실패','error'));
}
function cls(id) { document.getElementById(id).style.display='none'; }

// ── Save Improvement ──
function saveImp() {
  const eid = document.getElementById('eImpId').value;
  const data = {
    title: document.getElementById('iTitle').value.trim(),
    description: document.getElementById('iDesc').value.trim(),
    category: document.getElementById('iCat').value,
    status: document.getElementById('iStat').value
  };
  if (!data.title) return toast('제목을 입력하세요', 'error');

  const isEdit = eid !== '';
  const url = isEdit ? API+'improvements/'+eid : API+'improvements';
  const method = isEdit ? 'PUT' : 'POST';

  fetch(url, {method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
    .then(r=>r.json())
    .then(d=>{
      if (d.error) return toast(d.error, 'error');
      toast(isEdit?'수정 완료':'추가 완료');
      cls('impMod');
      setTimeout(()=>location.reload(),500);
    })
    .catch(()=>toast('저장 실패','error'));
}

// ── Do Release ──
function doRelease() {
  const btn = document.querySelector('.vmmd-f .vmb1');
  const type = document.querySelector('input[name="rType"]:checked')?.value || 'patch';
  const note = document.getElementById('rNote').value.trim();
  const imps = [...document.querySelectorAll('input[name="rImps[]"]:checked')].map(c=>parseInt(c.value));

  // Loading state
  btn.disabled = true;
  btn.textContent = '⏳ 릴리즈 중...';

  fetch(API+'release', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({type, note, improvement_ids:imps})
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.error) { toast(d.error, 'error'); btn.disabled=false; btn.textContent='🚀 릴리즈'; return; }
    toast('🎉 릴리즈 완료: '+d.display_string);
    cls('relMod');
    setTimeout(()=>location.reload(),800);
  })
  .catch(()=>{ toast('릴리즈 실패','error'); btn.disabled=false; btn.textContent='🚀 릴리즈'; });
}

// ── Release Selected ──
function releaseSel() {
  const sel = [...document.querySelectorAll('.icb:checked')].map(c=>parseInt(c.value));
  if (!sel.length) return toast('항목을 선택하세요', 'error');
  
  fetch(API+'improvements?unversioned=true')
    .then(r=>r.json())
    .then(imps=>{
      const lst = document.getElementById('relImpLst');
      if (Array.isArray(imps)) {
        lst.innerHTML = imps.map(i=>
          `<label class="vmio"><input type="checkbox" name="rImps[]" value="${i.id}" ${sel.includes(i.id)?'checked':''}><span>[${i.category}] ${i.title}</span></label>`
        ).join('');
      }
      document.querySelectorAll('input[name="rType"]').forEach(r=>{ if(r.value==='patch') r.checked=true; });
      document.getElementById('rNote').value = '';
      document.getElementById('relMod').style.display='flex';
    });
}

// ── Filter Improvements ──
function filterImps() {
  const cat = document.getElementById('fCat')?.value || '';
  const ver = document.getElementById('fVer')?.value || '';
  const rows = document.querySelectorAll('#impTbl tbody tr');
  let cnt = 0;
  rows.forEach(tr=>{
    const rc = tr.dataset.cat || '';
    const rv = tr.dataset.ver || '';
    let show = true;
    if (cat && rc !== cat) show = false;
    if (ver === 'unassigned' && rv !== 'unassigned') show = false;
    else if (ver && ver !== 'unassigned' && rv !== ver) show = false;
    tr.style.display = show ? '' : 'none';
    if (show) cnt++;
  });
  document.getElementById('impCnt').textContent = '총 '+cnt+'건';
}

// ── Select All ──
function toggleAll(el) {
  document.querySelectorAll('.icb').forEach(c=>c.checked=el.checked);
  updateSelCnt();
}
document.addEventListener('change', e=>{ if(e.target.classList.contains('icb')) updateSelCnt(); });
function updateSelCnt() {
  const n = document.querySelectorAll('.icb:checked').length;
  const el = document.getElementById('selCnt');
  if (el) el.textContent = n;
}

// ── Sync Improvements (from admin_source_improvements.php) ──
function syncImps() {
  const btn = document.querySelector('.vm-ch .vmb2');
  const origText = btn.textContent;

  // Loading state
  btn.disabled = true;
  btn.textContent = '⏳ 동기화 중...';
  toast('🔄 소스 개선 관리 페이지에서 데이터 수집 중...', 'success', 6000);

  fetch(API+'sync-improvements', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({source_url:'https://kiam.kr/admin/admin_source_improvements.php'})
  })
  .then(r=>r.json())
  .then(d=>{
    btn.disabled = false;
    btn.textContent = origText;
    if (d.error) return toast('❌ '+d.error, 'error');
    const synced = d.synced_count || 0;
    if (synced > 0) {
      toast(`✅ 동기화 완료: ${d.message} (${synced}건) — 페이지 새로고침...`);
      setTimeout(()=>location.reload(), 1200);
    } else {
      toast(`ℹ️ ${d.message}`, 'success', 3000);
    }
  })
  .catch(()=>{
    btn.disabled = false;
    btn.textContent = origText;
    toast('❌ 동기화 실패 — 네트워크 오류', 'error');
  });
}

// ── Quick Release from dashboard ──
function quickRelease(typeHint) {
  openRelease();
}

// ── Close modals on overlay click ──
document.addEventListener('click', e=>{
  if (e.target.classList.contains('vmo')) e.target.style.display='none';
});

// ── ESC to close ──
document.addEventListener('keydown', e=>{
  if (e.key==='Escape') document.querySelectorAll('.vmo').forEach(m=>m.style.display='none');
});

// ── Dashboard live stats refresh ──
if (window.location.search.indexOf('tab=') === -1 || window.location.search.indexOf('tab=dashboard') !== -1) {
  setInterval(()=>{
    fetch(API+'stats').then(r=>r.json()).then(s=>{
      const cards = document.querySelectorAll('.vm-sc-i b');
      if (cards.length >= 4) {
        cards[0].textContent = s.total_releases || 0;
        cards[1].textContent = s.total_improvements || 0;
        cards[2].textContent = s.this_week || 0;
        cards[3].textContent = s.unversioned || 0;
      }
    }).catch(()=>{});
  }, 30000); // refresh every 30s
}