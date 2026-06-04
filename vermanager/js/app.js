/**
 * VerManager Frontend — Onlyone OneChat
 * AJAX 통신, 모달, 필터, 릴리즈 처리
 */

const API = '/vermanger/api/';

// ── Toast ──
function toast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'vmtoast vmtoast-'+type; t.style.display='block';
  setTimeout(()=>t.style.display='none',3000);
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
      document.getElementById('relMod').style.display='flex';
    });
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
    });
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
  const type = document.querySelector('input[name="rType"]:checked')?.value || 'patch';
  const note = document.getElementById('rNote').value.trim();
  const imps = [...document.querySelectorAll('input[name="rImps[]"]:checked')].map(c=>parseInt(c.value));

  fetch(API+'release', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({type, note, improvement_ids:imps})
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.error) return toast(d.error, 'error');
    toast('릴리즈 완료: '+d.display_string);
    cls('relMod');
    setTimeout(()=>location.reload(),800);
  })
  .catch(()=>toast('릴리즈 실패','error'));
}

// ── Release Selected ──
function releaseSel() {
  const sel = [...document.querySelectorAll('.icb:checked')].map(c=>parseInt(c.value));
  if (!sel.length) return toast('항목을 선택하세요', 'error');
  document.querySelectorAll('input[name="rType"]').forEach(r=>{ if(r.value==='patch') r.checked=true; });
  document.getElementById('rNote').value = '';
  document.querySelectorAll('input[name="rImps[]"]').forEach(c=>{ c.checked = sel.includes(parseInt(c.value)); });
  document.getElementById('relMod').style.display='flex';
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

// ── Sync Improvements ──
function syncImps() {
  toast('🔄 소스 개선 관리 페이지에서 데이터 수집 중...');
  fetch(API+'sync-improvements', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({source_url:'https://kiam.kr/admin/admin_source_improvements.php'})
  })
  .then(r=>r.json())
  .then(d=>{ toast('동기화 완료: '+d.message); })
  .catch(()=>toast('동기화 실패','error'));
}

// ── Close modals on overlay click ──
document.addEventListener('click', e=>{
  if (e.target.classList.contains('vmo')) e.target.style.display='none';
});

// ── ESC to close ──
document.addEventListener('keydown', e=>{
  if (e.key==='Escape') document.querySelectorAll('.vmo').forEach(m=>m.style.display='none');
});