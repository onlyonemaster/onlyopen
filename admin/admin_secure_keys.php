<?php
/**
 * 🔐 시크릿 API 키 관리 (Admin)
 * --------------------------------------------------
 * /home/secure/{provider}_key.enc 를 웹에서 안전하게 갱신.
 *
 *   ▸ openai    : TTS·이미지 (mychat·onechat)
 *   ▸ deepseek  : 일반 LLM 백엔드
 *   ▸ claude    : 고품질 LLM
 *   ▸ pexels    : 이미지 라이브러리
 *
 * 이 파일은 UI만 담당. 실제 저장/검증/테스트는
 * /admin/ajax/secure_keys_api.php 가 처리한다.
 *
 * 작성일: 2026-06-04 by 아리
 */
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_header.inc.php";

// 추가 권한 체크 (로그인 + 관리자)
if (!isset($_SESSION['one_member_admin_id']) || empty($_SESSION['one_member_admin_id'])) {
    echo "<script>alert('관리자 로그인이 필요합니다.');location='/admin/';</script>";
    exit;
}
?>
<style>
.sk-wrap{max-width:980px;margin:24px auto;padding:0 16px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#1f2937;}
.sk-wrap h1{font-size:22px;font-weight:800;margin:0 0 6px;display:flex;align-items:center;gap:10px;}
.sk-wrap .lead{color:#6b7280;font-size:13px;margin-bottom:24px;line-height:1.6;}
.sk-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px 20px;margin-bottom:14px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
.sk-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;}
.sk-head .left{display:flex;align-items:center;gap:10px;}
.sk-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;}
.sk-badge.ok{background:#dcfce7;color:#166534;}
.sk-badge.warn{background:#fef3c7;color:#92400e;}
.sk-badge.err{background:#fee2e2;color:#991b1b;}
.sk-name{font-size:16px;font-weight:700;color:#111827;}
.sk-desc{font-size:12px;color:#6b7280;margin-top:2px;}
.sk-meta{font-size:12px;color:#6b7280;display:flex;gap:18px;flex-wrap:wrap;margin-bottom:10px;}
.sk-meta b{color:#374151;font-weight:600;margin-right:4px;}
.sk-meta code{background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:11px;color:#374151;}
.sk-row{display:flex;gap:8px;align-items:stretch;}
.sk-row input[type=password],.sk-row input[type=text]{flex:1;padding:9px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;font-family:"SF Mono",Menlo,Consolas,monospace;outline:none;}
.sk-row input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.sk-row button{padding:9px 14px;border:0;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;white-space:nowrap;}
.sk-btn-save{background:#2563eb;color:#fff;}
.sk-btn-save:hover{background:#1d4ed8;}
.sk-btn-test{background:#f3f4f6;color:#374151;border:1px solid #d1d5db !important;}
.sk-btn-test:hover{background:#e5e7eb;}
.sk-btn-show{background:#fff;color:#6b7280;border:1px solid #d1d5db !important;padding:9px 11px !important;}
.sk-btn-show:hover{background:#f9fafb;}
.sk-status{margin-top:10px;font-size:12px;min-height:16px;}
.sk-status.ok{color:#166534;}
.sk-status.err{color:#991b1b;}
.sk-status.info{color:#1e40af;}
.sk-toast{position:fixed;bottom:30px;right:30px;background:#111827;color:#fff;padding:12px 20px;border-radius:8px;z-index:9999;font-size:13px;opacity:0;transition:opacity .25s;box-shadow:0 8px 24px rgba(0,0,0,.25);max-width:380px;}
.sk-toast.show{opacity:1;}
.sk-toast.ok{background:#16a34a;}
.sk-toast.err{background:#dc2626;}
.sk-hint{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;font-size:12px;color:#1e40af;line-height:1.7;margin-bottom:18px;}
.sk-hint code{background:#dbeafe;padding:1px 6px;border-radius:3px;font-size:11px;}
.sk-spin{display:inline-block;width:12px;height:12px;border:2px solid #d1d5db;border-top-color:#2563eb;border-radius:50%;animation:sk-spin 0.7s linear infinite;vertical-align:middle;margin-right:6px;}
@keyframes sk-spin{to{transform:rotate(360deg);}}
</style>

<div class="sk-wrap">
    <h1>🔐 시크릿 API 키 관리</h1>
    <p class="lead">
        외부 서비스 API 키를 <code>/home/secure/*.enc</code> 파일로 안전하게 저장합니다.<br>
        키는 base64 인코딩되어 디스크에 기록되며, <b>웹 디렉터리 바깥에 있어 외부에서 직접 접근할 수 없습니다.</b><br>
        저장 후 mychat·onechat 의 음성/이미지 기능과 LLM 백엔드가 자동으로 새 키를 사용합니다 (재배포 불필요).
    </p>

    <div class="sk-hint">
        💡 <b>키 발급처</b><br>
        • OpenAI &nbsp;&nbsp;&nbsp;→ <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a> &nbsp;(<code>sk-</code> / <code>sk-proj-</code> / <code>sk-svcacct-</code> 접두어)<br>
        • DeepSeek &nbsp;→ <a href="https://platform.deepseek.com/api_keys" target="_blank">platform.deepseek.com/api_keys</a> &nbsp;(<code>sk-</code>)<br>
        • Claude &nbsp;&nbsp;&nbsp;→ <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com/settings/keys</a> &nbsp;(<code>sk-ant-</code>)<br>
        • Pexels &nbsp;&nbsp;&nbsp;→ <a href="https://www.pexels.com/api/" target="_blank">pexels.com/api</a>
    </div>

    <div id="sk-list">
        <div style="text-align:center;padding:40px;color:#9ca3af;">
            <span class="sk-spin"></span> 키 상태 불러오는 중...
        </div>
    </div>
</div>

<div class="sk-toast" id="sk-toast"></div>

<script>
const SK_API = '/admin/ajax/secure_keys_api.php';

function skToast(msg, type){
    const t = document.getElementById('sk-toast');
    t.className = 'sk-toast show ' + (type || '');
    t.textContent = msg;
    clearTimeout(t._tm);
    t._tm = setTimeout(()=>{ t.className = 'sk-toast'; }, 3500);
}

function skRender(providers){
    const root = document.getElementById('sk-list');
    root.innerHTML = '';
    providers.forEach(p => {
        const badge = p.exists
            ? (p.decodable
                ? '<span class="sk-badge ok">저장됨</span>'
                : '<span class="sk-badge err">디코딩 실패</span>')
            : '<span class="sk-badge warn">미설정</span>';

        const card = document.createElement('div');
        card.className = 'sk-card';
        card.dataset.provider = p.id;
        card.innerHTML = `
            <div class="sk-head">
                <div class="left">
                    <span class="sk-name">${p.label}</span>
                    ${badge}
                </div>
                <div style="font-size:11px;color:#9ca3af;">${p.id}_key.enc</div>
            </div>
            <div class="sk-desc">${p.desc}</div>
            <div class="sk-meta" style="margin-top:10px;">
                <span><b>현재값</b><code class="sk-preview">${p.exists ? (p.preview || '(빈 파일)') : '(없음)'}</code></span>
                <span><b>수정시각</b>${p.mtime_str || '-'}</span>
                <span><b>크기</b>${p.size || 0} B</span>
            </div>
            <div class="sk-row">
                <input type="password" class="sk-input" placeholder="새 ${p.label} 키 붙여넣기 (저장 시 base64 인코딩)" autocomplete="off">
                <button class="sk-btn-show" title="표시/감추기">👁</button>
                <button class="sk-btn-save">💾 저장</button>
                <button class="sk-btn-test" ${p.exists?'':'disabled style="opacity:.4;cursor:not-allowed;"'}>🔌 연결 테스트</button>
            </div>
            <div class="sk-status"></div>
        `;
        root.appendChild(card);
    });

    // 이벤트 바인딩
    root.querySelectorAll('.sk-card').forEach(card => {
        const pid = card.dataset.provider;
        const input = card.querySelector('.sk-input');
        const status = card.querySelector('.sk-status');

        card.querySelector('.sk-btn-show').addEventListener('click', () => {
            input.type = (input.type === 'password') ? 'text' : 'password';
        });

        card.querySelector('.sk-btn-save').addEventListener('click', () => {
            const v = input.value.trim();
            if (!v) { status.className='sk-status err'; status.textContent='키를 입력하세요.'; return; }
            status.className = 'sk-status info';
            status.innerHTML = '<span class="sk-spin"></span> 저장 중...';

            const fd = new FormData();
            fd.append('mode', 'save_key');
            fd.append('provider', pid);
            fd.append('api_key', v);

            fetch(SK_API, {method:'POST', body:fd, credentials:'same-origin'})
                .then(r => r.json())
                .then(j => {
                    if (j.success) {
                        status.className = 'sk-status ok';
                        status.innerHTML = `✅ 저장 완료 · ${j.preview} · ${j.verified ? '검증 OK':'검증 실패'} · ${j.mtime_str}`;
                        skToast(j.message, 'ok');
                        input.value = '';
                        // 미리보기·시각 즉시 반영
                        card.querySelector('.sk-preview').textContent = j.preview;
                        const metaSpans = card.querySelectorAll('.sk-meta span');
                        if (metaSpans[1]) metaSpans[1].innerHTML = '<b>수정시각</b>' + j.mtime_str;
                        // 테스트 버튼 활성화
                        const tb = card.querySelector('.sk-btn-test');
                        tb.disabled = false; tb.style.opacity = ''; tb.style.cursor = '';
                        // 배지 OK 로
                        card.querySelector('.sk-badge').className = 'sk-badge ok';
                        card.querySelector('.sk-badge').textContent = '저장됨';
                    } else {
                        status.className = 'sk-status err';
                        status.textContent = '❌ ' + (j.message || '저장 실패');
                        skToast(j.message || '저장 실패', 'err');
                    }
                })
                .catch(e => {
                    status.className = 'sk-status err';
                    status.textContent = '❌ 통신 오류: ' + e.message;
                    skToast('통신 오류', 'err');
                });
        });

        card.querySelector('.sk-btn-test').addEventListener('click', () => {
            status.className = 'sk-status info';
            status.innerHTML = '<span class="sk-spin"></span> API 호출 중...';
            const fd = new FormData();
            fd.append('mode', 'test_key');
            fd.append('provider', pid);
            fetch(SK_API, {method:'POST', body:fd, credentials:'same-origin'})
                .then(r => r.json())
                .then(j => {
                    if (j.success) {
                        status.className = 'sk-status ok';
                        status.textContent = '✅ ' + j.message;
                    } else {
                        status.className = 'sk-status err';
                        status.textContent = '❌ ' + (j.message || '테스트 실패');
                    }
                })
                .catch(e => {
                    status.className = 'sk-status err';
                    status.textContent = '❌ 통신 오류: ' + e.message;
                });
        });
    });
}

function skLoad(){
    const fd = new FormData();
    fd.append('mode', 'get_status');
    fetch(SK_API, {method:'POST', body:fd, credentials:'same-origin'})
        .then(r => r.json())
        .then(j => {
            if (j.success) skRender(j.providers);
            else document.getElementById('sk-list').innerHTML =
                '<div style="padding:30px;text-align:center;color:#991b1b;">불러오기 실패: '+(j.message||'')+'</div>';
        })
        .catch(e => {
            document.getElementById('sk-list').innerHTML =
                '<div style="padding:30px;text-align:center;color:#991b1b;">통신 오류: '+e.message+'</div>';
        });
}

document.addEventListener('DOMContentLoaded', skLoad);
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/admin/include/admin_footer.inc.php";
?>
