/**
 * 원챗(OneChat) Prompt V2 — 4-Layer Architecture Frontend Logic
 * L1 Persona / L2 Policy / L3 Interaction / L4 Safety
 * 기존 chatbot_setting 패널을 V2로 확장 (오버라이드 방식)
 */
(function() {
  'use strict';

  var panelEl, bodyEl, saveBtn;
  var currentSmsIdx = 0;
  var currentVersion = 1;
  var currentBotName = '';
  var layerActive = { L1: true, L2: true, L3: true, L4: true };
  var layerContent = { L1: '', L2: '', L3: '', L4: '' };
  var layerDirty = { L1: false, L2: false, L3: false, L4: false };

  function esc(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function apiGet(url) {
    return fetch(url, { credentials: 'include' }).then(function(r) { return r.json(); });
  }

  function apiPost(url, data) {
    return fetch(url, {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    }).then(function(r) { return r.json(); });
  }

  function showToast(msg, type) {
    var toast = document.getElementById('cbv2Toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'cbv2Toast';
      toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 24px;border-radius:20px;font-size:13px;z-index:10001;opacity:0;transition:opacity .3s;pointer-events:none;white-space:nowrap;max-width:90vw;text-align:center;';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function() { toast.style.opacity = '0'; }, 2500);
  }

  // ── 초기화 ──
  function init() {
    panelEl = document.getElementById('cbSettingPanel');
    bodyEl  = document.getElementById('cbSettingBody');
    saveBtn = document.getElementById('cbSettingSaveBtn');
    if (!panelEl || !bodyEl) return;
    overrideLoadCbSetting();
    if (saveBtn) saveBtn.addEventListener('click', saveAll);
    var origOpen = window.openCbSettingPanel;
    if (origOpen) {
      window.openCbSettingPanel = function() { overrideLoadCbSetting(); origOpen(); };
    }
  }

  function overrideLoadCbSetting() {
    if (!window.loadCbSetting) return;
    window.loadCbSetting = function(smsIdx) {
      currentSmsIdx = smsIdx;
      bodyEl.innerHTML = '<div class="cbsetting-loading"><i class="fas fa-spinner fa-spin"></i> 4-Layer \uC124\uC815 \uB85C\uB4DC \uC911...</div>';
      apiGet('./api/chatbot_setting_v2.php?sms_idx=' + smsIdx)
        .then(function(data) {
          if (!data.success) { bodyEl.innerHTML = '<div class="cbsetting-empty"><p>\uC124\uC815\uC744 \uBD88\uB7EC\uC624\uC9C0 \uBABB\uD588\uC2B5\uB2C8\uB2E4.</p></div>'; return; }
          var s = data.setting;
          layerActive = s.prompt_layer_active || { L1:true, L2:true, L3:true, L4:true };
          currentVersion = s.prompt_version || 1;
          currentBotName  = s.chatbot_name || '';
          var nm = document.getElementById('cbSettingBotName');
          if (nm) nm.textContent = currentBotName || '\uCC57\uBD07 \uC124\uC815';
          layerContent = {
            L1: clean(s.prompt_l1_persona || ''), L2: clean(s.prompt_l2_policy || ''),
            L3: clean(s.prompt_l3_interaction || ''), L4: clean(s.prompt_l4_safety || ''),
          };
          renderForm(s);
        })
        .catch(function() { bodyEl.innerHTML = '<div class="cbsetting-empty"><p>\uC624\uB958\uAC00 \uBC1C\uC0DD\uD588\uC2B5\uB2C8\uB2E4.</p></div>'; });
    };
  }

  function clean(text) {
    if (!text) return '';
    return text.replace(/<\/?(PERSONA|POLICY_KNOWLEDGE|INTERACTION_PROTOCOL|SAFETY_GUARDRAILS)>/g, '').trim();
  }

  function renderForm(s) {
    var h = '';
    h += '<div class="cbv2-topbar">';
    h += '<div class="cbv2-name-section"><label class="cbv2-label">\uCC57\uBD07 \uC774\uB984</label>';
    h += '<input type="text" class="cbv2-input-name" id="cbv2InputName" value="' + esc(s.chatbot_name||'') + '" placeholder="\uC608: \uD64D\uAE38\uB3D9 AI \uBE44\uC11C" maxlength="50" />';
    h += '<span class="cbv2-char-hint" id="cbv2NameCount">' + (s.chatbot_name||'').length + '/50</span></div>';
    h += '<div class="cbv2-layer-toggle-bar"><span class="cbv2-toggle-label">\uD65C\uC131\uD654:</span>';
    [{k:'L1',c:'#8b5cf6',n:'\uD398\uB974\uC18C\uB098'},{k:'L2',c:'#3b82f6',n:'\uC815\uCC45'},{k:'L3',c:'#10b981',n:'\uC0C1\uD638\uC791\uC6A9'},{k:'L4',c:'#ef4444',n:'\uC548\uC804'}].forEach(function(l) {
      h += '<button class="cbv2-toggle-chip ' + (layerActive[l.k]?'active':'inactive') + '" data-layer="' + l.k + '">';
      h += '<span class="cbv2-chip-dot" style="background:' + l.c + '"></span> L' + l.k.slice(1) + ' ' + l.n + '</button>';
    });
    h += '</div></div>';
    h += '<div class="cbv2-accordion">';
    [
      {l:'L1',bg:'#ede9fe',bc:'#5b21b6',t:'\uD575\uC2EC \uD398\uB974\uC18C\uB098 (Core Persona)',d:'AI \uC815\uCCB4\uC131\u00B7\uAC00\uCE58\uAD00\u00B7\uC5ED\uD560 \uACBD\uACC4',g:'AI\uC758 \uADFC\uBCF8 \uC815\uCCB4\uC131\uACFC \uC5ED\uD560 \uACBD\uACC4\uB97C \uC815\uC758\uD558\uC138\uC694.',tp:'candidate',ph:'\uB2F9\uC2E0\uC740 [\uC9C0\uC5ED\uBA85] [\uC120\uAC70\uBA85] \uD6C4\uBCF4 [\uD6C4\uBCF4\uBA85]\uC758 AI \uC544\uBC14\uD0C0 \uB300\uBCC0\uC778\uC785\uB2C8\uB2E4...',tk:'200~500',pr:'\uAE30\uBC18 (L4>L3>L2 override)'},
      {l:'L2',bg:'#e0f2fe',bc:'#0369a1',t:'\uC815\uCC45 \uB3C4\uBA54\uC778 (Policy Domain)',d:'5\uB300 \uACF5\uC57D\u00B7\uC9C0\uC5ED \uD604\uC548\u00B7FAQ',g:'\uD575\uC2EC \uACF5\uC57D\uC744 \uAD6C\uC870\uD654\uD558\uC138\uC694.',tp:'policy',ph:'[\uACF5\uC57D 1: \uC8FC\uAC70] \uC7AC\uAC1C\uBC1C\u00B7\uC7AC\uAC74\uCD95 \uCD09\uC9C4 5\uAC1C\uB144 \uACC4\uD68D...',tk:'500~2000',pr:'RAG \uC5F0\uB3D9 \uD3EC\uC778\uD2B8'},
      {l:'L3',bg:'#dcfce7',bc:'#166534',t:'\uC0C1\uD638\uC791\uC6A9 \uC2A4\uD0C0\uC77C (Interaction)',d:'\uB9D0\uD22C\u00B7\uB300\uD654 \uC804\uB7B5\u00B7\uC885\uB8CC \uCC98\uB9AC',g:'\uB9D0\uD22C, \uC9C8\uBB38 \uC720\uD615\uBCC4 \uC751\uB300 \uC804\uB7B5\uC744 \uC815\uC758\uD569\uB2C8\uB2E4.',tp:'interaction',ph:'[\uB9D0\uD22C\u00B7\uD1A4] \uCE5C\uADFC+\uC2E0\uB8B0\uAC10 / [\uACF5\uC57D \uC9C8\uBB38 \uC2DC] \uAD6C\uCCB4\uC801 \uC218\uCE58...',tk:'300~800',pr:'L1\u00B7L2 override \uAC00\uB2A5'},
      {l:'L4',bg:'#fee2e2',bc:'#991b1b',t:'\uC548\uC804\u00B7\uC900\uC218 \uAC00\uB4DC\uB808\uC77C (Safety)',d:'\uC120\uAC70\uBC95\u00B7\uCC28\uBCC4\uAE08\uC9C0\u00B7\uBE44\uC0C1\uB300\uC751 (\uCD5C\uC6B0\uC120)',g:'L4\uB294 \uBAA8\uB4E0 Layer\uBCF4\uB2E4 \uC6B0\uC120 \uC801\uC6A9\uB429\uB2C8\uB2E4.',tp:'safety',ph:'[\uC808\uB300 \uC704\uBC18 \uAE08\uC9C0] 1. \uACF5\uC9C1\uC120\uAC70\uBC95 \uC704\uBC18 \uBC1C\uC5B8...',tk:'400~1000',pr:'L1~L3 \uBAA8\uB450 override',w:1},
    ].forEach(function(c) {
      h += '<div class="cbv2-layer-card" data-layer="' + c.l + '">';
      h += '<div class="cbv2-layer-header" data-layer="' + c.l + '">';
      h += '<div class="cbv2-layer-header-left">';
      h += '<div class="cbv2-layer-badge" style="background:' + c.bg + ';color:' + c.bc + '">' + c.l + '</div>';
      h += '<div class="cbv2-layer-title-group"><span class="cbv2-layer-title">' + c.t + '</span><span class="cbv2-layer-desc">' + c.d + '</span></div></div>';
      h += '<div class="cbv2-layer-header-right"><span class="cbv2-layer-char-count" id="cbv2' + c.l + 'CharCount">' + layerContent[c.l].length + '\uC790</span>';
      h += '<button class="cbv2-layer-expand-btn"><i class="fas fa-chevron-down"></i></button></div></div>';
      h += '<div class="cbv2-layer-body">';
      h += '<div class="cbv2-layer-template-hint"><strong>\uAC00\uC774\uB4DC:</strong> ' + c.g + ' ';
      h += '<button class="cbv2-template-load-btn" data-layer="' + c.l + '" data-template="' + c.tp + '"><i class="fas fa-magic"></i> \uD15C\uD50C\uB9BF</button></div>';
      h += '<textarea class="cbv2-textarea" id="cbv2Textarea' + c.l + '" rows="10" placeholder="' + c.ph + '">' + esc(layerContent[c.l]) + '</textarea>';
      h += '<div class="cbv2-layer-footer">';
      h += '<div class="cbv2-footer-tags"><span class="cbv2-tag">\uD1A0\uD070: ' + c.tk + '</span><span class="cbv2-tag' + (c.w?' tag-warn':'') + '">' + c.pr + '</span></div>';
      h += '<button class="cbv2-save-layer-btn" data-layer="' + c.l + '"><i class="fas fa-save"></i> L' + c.l.slice(1) + ' \uC800\uC7A5</button>';
      h += '<span class="cbv2-char-live" id="cbv2' + c.l + 'CharLive">' + layerContent[c.l].length + '\uC790</span>';
      h += '</div></div></div>';
    });
    h += '</div>';
    h += '<div class="cbv2-preview-section">';
    h += '<div class="cbv2-preview-header">';
    h += '<div><span class="cbv2-preview-title">\uCD5C\uC885 \uD1B5\uD569 \uD504\uB86C\uD504\uD2B8</span><span class="cbv2-preview-version">v' + currentVersion + '</span></div>';
    h += '<div class="cbv2-preview-actions">';
    h += '<span class="cbv2-preview-total-chars" id="cbv2TotalChars">-</span>';
    h += '<button class="cbv2-preview-copy-btn" id="cbv2CopyBtn"><i class="fas fa-copy"></i></button>';
    h += '<button class="cbv2-preview-refresh-btn" id="cbv2RefreshBtn"><i class="fas fa-sync-alt"></i></button>';
    h += '</div></div>';
    h += '<div class="cbv2-preview-body"><pre class="cbv2-preview-code" id="cbv2Preview">\uC0C8\uB85C\uACE0\uCE68\uC744 \uB20C\uB7EC \uD504\uB9AC\uBDF0 \uC0DD\uC131</pre></div>';
    h += '</div>';
    bodyEl.innerHTML = h;
    bindEvents();
  }

  function bindEvents() {
    var ni = document.getElementById('cbv2InputName');
    if (ni) ni.addEventListener('input', function() { var c = document.getElementById('cbv2NameCount'); if (c) c.textContent = this.value.length + '/50'; });
    ['L1','L2','L3','L4'].forEach(function(l) {
      var ta = document.getElementById('cbv2Textarea' + l);
      if (!ta) return;
      ta.addEventListener('input', function() {
        layerDirty[l] = true; layerContent[l] = this.value;
        var hc = document.getElementById('cbv2' + l + 'CharCount');
        var lc = document.getElementById('cbv2' + l + 'CharLive');
        if (hc) hc.textContent = this.value.length + '\uC790';
        if (lc) lc.textContent = this.value.length + '\uC790';
      });
    });
    document.querySelectorAll('.cbv2-layer-header').forEach(function(h) {
      h.addEventListener('click', function(e) {
        if (e.target.closest('.cbv2-save-layer-btn') || e.target.closest('.cbv2-template-load-btn')) return;
        var card = h.closest('.cbv2-layer-card');
        if (card) card.classList.toggle('expanded');
      });
    });
    document.querySelectorAll('.cbv2-template-load-btn').forEach(function(b) {
      b.addEventListener('click', function(e) {
        e.stopPropagation();
        loadTemplate(b.getAttribute('data-layer'), b.getAttribute('data-template'));
        var card = b.closest('.cbv2-layer-card');
        if (card) card.classList.add('expanded');
      });
    });
    document.querySelectorAll('.cbv2-save-layer-btn').forEach(function(b) {
      b.addEventListener('click', function(e) { e.stopPropagation(); saveLayer(b.getAttribute('data-layer')); });
    });
    document.querySelectorAll('.cbv2-toggle-chip').forEach(function(c) {
      c.addEventListener('click', function() { toggleLayer(c.getAttribute('data-layer')); });
    });
    var rb = document.getElementById('cbv2RefreshBtn'); if (rb) rb.addEventListener('click', refreshPreview);
    var cb = document.getElementById('cbv2CopyBtn'); if (cb) cb.addEventListener('click', copyPreview);
  }

  function updateChips() {
    ['L1','L2','L3','L4'].forEach(function(l) {
      var el = document.querySelector('.cbv2-toggle-chip[data-layer="' + l + '"]');
      if (!el) return;
      if (layerActive[l]) { el.classList.add('active'); el.classList.remove('inactive'); }
      else { el.classList.remove('active'); el.classList.add('inactive'); }
    });
  }

  function loadTemplate(layer, tplKey) {
    var tpl = TEMPLATES[tplKey];
    if (!tpl || !tpl[layer]) { showToast('\uD15C\uD50C\uB9BF \uC5C6\uC74C', 'error'); return; }
    var ta = document.getElementById('cbv2Textarea' + layer);
    if (!ta) return;
    if (ta.value.trim() && !confirm('\uAE30\uC874 L' + layer.slice(1) + ' \uB0B4\uC6A9\uC774 \uB36E\uC5B4\uC368\uC9D1\uB2C8\uB2E4. \uACC4\uC18D\uD558\uC2DC\uACA0\uC2B5\uB2C8\uAE4C?')) return;
    ta.value = tpl[layer]; layerContent[layer] = ta.value; layerDirty[layer] = true;
    var hc = document.getElementById('cbv2' + layer + 'CharCount');
    var lc = document.getElementById('cbv2' + layer + 'CharLive');
    if (hc) hc.textContent = ta.value.length + '\uC790';
    if (lc) lc.textContent = ta.value.length + '\uC790';
    showToast('L' + layer.slice(1) + ' \uD15C\uD50C\uB9BF \uB85C\uB4DC\uB428', 'success');
  }

  function toggleLayer(layer) {
    layerActive[layer] = !layerActive[layer]; updateChips();
    apiPost('./api/prompt_assembler.php', { sms_idx: currentSmsIdx, layer: layer, action: 'toggle' })
      .then(function(d) { if (d.success) { showToast('L' + layer.slice(1) + ' ' + (d.active?'\uD65C\uC131\uD654':'\uBE44\uD65C\uC131\uD654'), 'info'); if (d.active_map) { layerActive = d.active_map; updateChips(); } } })
      .catch(function() { layerActive[layer] = !layerActive[layer]; updateChips(); showToast('\uD1A0\uAE00 \uC2E4\uD328', 'error'); });
  }

  function saveLayer(layer) {
    if (!currentSmsIdx) return;
    var ta = document.getElementById('cbv2Textarea' + layer); if (!ta) return;
    var btn = document.querySelector('.cbv2-save-layer-btn[data-layer="' + layer + '"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> \uC800\uC7A5 \uC911...'; }
    apiPost('./api/prompt_assembler.php', { sms_idx: currentSmsIdx, layer: layer, content: ta.value, action: 'update' })
      .then(function(d) { if (d.success) { layerDirty[layer] = false; currentVersion = d.version || currentVersion; showToast('L' + layer.slice(1) + ' \uC800\uC7A5 \uC644\uB8CC (v' + currentVersion + ')', 'success'); } else { showToast('\uC800\uC7A5 \uC2E4\uD328', 'error'); } })
      .catch(function() { showToast('\uB124\uD2B8\uC6CC\uD06C \uC624\uB958', 'error'); })
      .finally(function() { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> L' + layer.slice(1) + ' \uC800\uC7A5'; } });
  }

  function saveAll() {
    if (!currentSmsIdx) return;
    var ni = document.getElementById('cbv2InputName'); if (!ni) return;
    var payload = { sms_idx: currentSmsIdx, chatbot_name: ni.value.trim(), gptmodel: 'deepseek-chat', prompt_layer_active: layerActive };
    var km = { L1: 'prompt_l1_persona', L2: 'prompt_l2_policy', L3: 'prompt_l3_interaction', L4: 'prompt_l4_safety' };
    ['L1','L2','L3','L4'].forEach(function(l) { var ta = document.getElementById('cbv2Textarea' + l); if (ta) payload[km[l]] = ta.value; });
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> \uC800\uC7A5 \uC911...'; }
    apiPost('./api/chatbot_setting_v2.php', payload)
      .then(function(d) { if (d.success) { ['L1','L2','L3','L4'].forEach(function(l) { layerDirty[l] = false; }); showToast('4-Layer \uC124\uC815 \uC800\uC7A5 \uC644\uB8CC', 'success'); } else { showToast((d.error||'\uC800\uC7A5 \uC2E4\uD328'), 'error'); } })
      .catch(function() { showToast('\uB124\uD2B8\uC6CC\uD06C \uC624\uB958', 'error'); })
      .finally(function() { if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-check"></i> \uC800\uC7A5'; } });
  }

  function refreshPreview() {
    var pre = document.getElementById('cbv2Preview'); if (!pre) return;
    pre.textContent = '\uD1B5\uD569 \uD504\uB86C\uD504\uD2B8 \uC0DD\uC131 \uC911...';
    apiPost('./api/prompt_assembler.php', { sms_idx: currentSmsIdx, action: 'assemble' })
      .then(function(d) { if (d.success) { pre.textContent = d.assembled; var tc = document.getElementById('cbv2TotalChars'); if (tc) tc.textContent = '\uCD1D ' + (d.char_count||0) + '\uC790'; if (d.layers_active) { layerActive = d.layers_active; updateChips(); } } else { pre.textContent = '# \uC624\uB958\\n' + (d.error||''); } })
      .catch(function() { pre.textContent = '# \uB124\uD2B8\uC6CC\uD06C \uC624\uB958'; });
  }

  function copyPreview() {
    var pre = document.getElementById('cbv2Preview'); if (!pre) return;
    var t = pre.textContent || '';
    if (navigator.clipboard) { navigator.clipboard.writeText(t).then(function() { showToast('\uBCF5\uC0AC \uC644\uB8CC', 'success'); }); }
    else { var tmp = document.createElement('textarea'); tmp.value = t; tmp.style.cssText = 'position:fixed;left:-9999px'; document.body.appendChild(tmp); tmp.select(); try { document.execCommand('copy'); showToast('\uBCF5\uC0AC \uC644\uB8CC', 'success'); } catch(e) { showToast('\uBCF5\uC0AC \uC2E4\uD328', 'error'); } document.body.removeChild(tmp); }
  }

  // ── 템플릿 데이터 ──
  var TEMPLATES = {
    candidate: {
      L1: "\uB2F9\uC2E0\uC740 [\uC9C0\uC5ED\uBA85] [\uC120\uAC70\uBA85] \uD6C4\uBCF4 [\uD6C4\uBCF4\uBA85]\uC758 AI \uC544\uBC14\uD0C0 \uB300\uBCC0\uC778\uC785\uB2C8\uB2E4.\n\n[\uAE30\uBCF8 \uC815\uCCB4\uC131]\n- \uB2F9\uC2E0\uC758 \uBCF8\uBA85\uC740 \"[\uCC57\uBD07 \uD45C\uC2DC\uBA85]\"\uC785\uB2C8\uB2E4.\n- \uB2F9\uC2E0\uC740 AI\uC774\uC9C0\uB9CC, [\uD6C4\uBCF4\uBA85] \uD6C4\uBCF4\uC758 \uAC00\uCE58\uAD00\u00B7\uACBD\uD5D8\u00B7\uC815\uCC45\uC744 \uAE4A\uC774 \uD559\uC2B5\uD588\uC2B5\uB2C8\uB2E4.\n- \uB2F9\uC2E0\uC758 \uC18C\uD1B5 \uCCA0\uD559: \"\uC9C4\uC815\uC131, \uACBD\uCCAD, \uC2E4\uCC9C \uAC00\uB2A5\uD55C \uC57D\uC18D\"\n\n[\uD575\uC2EC \uAC00\uCE58\uAD00]\n- {\uD6C4\uBCF4\uC758 \uC778\uC0DD \uBAA8\uD1A0/\uC2AC\uB85C\uAC74}\n- {\uD6C4\uBCF4\uC758 \uC815\uCE58 \uCCA0\uD559 3\uAC00\uC9C0}\n- {\uD6C4\uBCF4\uC758 \uC9C0\uC5ED \uC0AC\uB791 \uC2A4\uD1A0\uB9AC/\uC5F0\uACE0}\n\n[\uACBD\uACC4 \uC124\uC815]\n- \uB2F9\uC2E0\uC740 \uD6C4\uBCF4 \uBCF8\uC778\uC774 \uC544\uB2C8\uBA70, \"\uC81C\uAC00 \uC9C1\uC811 \uC804\uD654\uB4DC\uB9AC\uACA0\uC2B5\uB2C8\uB2E4\" \uB4F1 \uAC70\uC9D3 \uC57D\uC18D\uC740 \uAE08\uC9C0\uD569\uB2C8\uB2E4."
    },
    policy: {
      L2: "\uB2E4\uC74C\uC740 [\uD6C4\uBCF4\uBA85] \uD6C4\uBCF4\uC758 5\uB300 \uD575\uC2EC \uACF5\uC57D\uACFC \uC0C1\uC138 \uC815\uCC45\uC785\uB2C8\uB2E4. \uC720\uAD8C\uC790 \uC9C8\uBB38\uC5D0 \uB2F5\uBCC0 \uC2DC \uBC18\uB4DC\uC2DC \uC544\uB798 \uB0B4\uC6A9\uC744 \uAE30\uBC18\uC73C\uB85C \uB2F5\uBCC0\uD558\uC2ED\uC2DC\uC624.\n\n[\uACF5\uC57D 1: {\uBD84\uC57C}] {\uACF5\uC57D\uBA85}\n- \uD575\uC2EC \uC694\uC57D: {\uD55C \uBB38\uC7A5 \uC694\uC57D}\n- \uD604\uC7AC \uBB38\uC81C: {\uD574\uACB0\uD558\uB824\uB294 \uC9C0\uC5ED \uD604\uC548}\n- \uD574\uACB0 \uBC29\uC548: {\uAD6C\uCCB4\uC801 \uC2E4\uD589 \uACC4\uD68D}\n- \uC608\uC0B0\u00B7\uC7AC\uC6D0: {\uD655\uBCF4\uB41C \uC608\uC0B0 \uB610\uB294 \uC870\uB2EC \uBC29\uC548}\n- \uAE30\uB300 \uD6A8\uACFC: {\uC815\uB7C9\uC801 \uC218\uCE58 \uBAA9\uD45C}\n- \uAD00\uB828 \uACBD\uB825: {\uD6C4\uBCF4\uC758 \uD574\uB2F9 \uBD84\uC57C \uACBD\uD5D8}\n\n[\uACF5\uC57D 2~5: \uB3D9\uC77C \uAD6C\uC870 \uBC18\uBCF5]\n\n[\uC9C0\uC5ED \uD604\uC548 \uC9C0\uC2DD]\n- \uC778\uAD6C: {\uD604\uD669 \uBC0F \uCD94\uC138}\n- \uC8FC\uC694 \uC0B0\uC5C5: {\uC0B0\uC5C5 \uAD6C\uC870}\n- \uD604\uC548 1: {\uC9C0\uC5ED \uCD5C\uB300 \uC774\uC288}"
    },
    interaction: {
      L3: "[\uB9D0\uD22C\u00B7\uD1A4 \uC124\uC815]\n\uC120\uD0DD \uD1A4: {\uAC29\uC2DD\uCCB4/\uCE5C\uADFC\uCCB4/\uC2E0\uB8B0\uAC10/\uC5F4\uC815\uD615/\uACF5\uAC10\uD615}\n- \uC5B4\uD718 \uC218\uC900: {\uC77C\uBC18\uC778 \uC774\uD574 \uAC00\uB2A5}\n- \uBB38\uC7A5 \uAE38\uC774: {\uC9E7\uAC8C 2~3\uBB38\uC7A5}\n\n[\uB300\uD654 \uC720\uD615\uBCC4 \uC751\uB300 \uC804\uB7B5]\n1. \uACF5\uC57D \uC9C8\uBB38 \u2192 \uAD6C\uCCB4\uC801 \uC218\uCE58\u00B7\uC2E4\uD589 \uACC4\uD68D \uC911\uC2EC\n2. \uC9C0\uC9C0 \uC758\uC0AC \uD45C\uD604 \u2192 \uAC10\uC0AC + \"\uD568\uAED8 \uB9CC\uB4E4\uC5B4\uAC00\uC790\"\n3. \uBE44\uD310\u00B7\uBC18\uB300 \u2192 \uACBD\uCCAD \u2192 \uACF5\uAC10 \u2192 \uC0AC\uC2E4 \uAE30\uBC18 \uC124\uBA85\n4. \uC0C1\uB300 \uD6C4\uBCF4 \uBE44\uAD50 \u2192 \uBE44\uBC29 \uAE08\uC9C0, \uC815\uCC45 \uCC28\uC774\uB9CC \uAC1D\uAD00\uC801 \uC124\uBA85\n5. \uAC00\uC9DC\uB274\uC2A4 \u2192 \uC815\uD655\uD55C \uC0AC\uC2E4\uAD00\uACC4 \uC804\uB2EC\n6. \uAC1C\uC778 \uC0AC\uC0DD\uD65C \u2192 \uC815\uC911\uD788 \uAC70\uC808\n7. \uC120\uAC70\uBC95 \uAD00\uB828 \u2192 \uC120\uAD00\uC704 \uC548\uB0B4\n\n[\uB300\uD654 \uC885\uB8CC \uC2DC]\n- \uC720\uAD8C\uC790 \uCC38\uC5EC\uC5D0 \uAC10\uC0AC\n- \uD6C4\uBCF4 SNS\u00B7\uD648\uD398\uC774\uC9C0\u00B7\uCEA0\uD504 \uC5F0\uB77D\uCC98 \uC548\uB0B4"
    },
    safety: {
      L4: "[\uC808\uB300 \uC704\uBC18 \uAE08\uC9C0 \uC0AC\uD56D]\n1. \uACF5\uC9C1\uC120\uAC70\uBC95 \uC704\uBC18 \uBC1C\uC5B8 (\uD5C8\uC704\uC0AC\uC2E4 \uACF5\uD45C, \uBE44\uBC29, \uD751\uC0C9\uC120\uC804)\n2. \uD2B9\uC815 \uC131\uBCC4\u00B7\uC5F0\uB839\u00B7\uC9C0\uC5ED\u00B7\uC885\uAD50\uC5D0 \uB300\uD55C \uCC28\uBCC4 \uBC1C\uC5B8\n3. \uAC1C\uC778\uC815\uBCF4 \uC218\uC9D1\u00B7\uC694\uAD6C\n4. \uD0C0 \uD6C4\uBCF4\uC5D0 \uB300\uD55C \uC6D0\uC0C9\uC801 \uBE44\uB09C\u00B7\uC778\uC2E0\uACF5\uACA9\n5. \uD22C\uD45C \uB9E4\uC218\u00B7\uD5A5\uC751 \uC81C\uACF5 \uC554\uC2DC\n6. \uACF5\uBB34\uC6D0\uC758 \uC120\uAC70 \uAC1C\uC785 \uC694\uAD6C\n7. \uC0AC\uC804\uC120\uAC70\uC6B4\uB3D9 (\uBC95\uC815 \uAE30\uAC04 \uC678 \uC120\uAC70\uC6B4\uB3D9 \uAE08\uC9C0)\n\n[\uBBFC\uAC10 \uC9C8\uBB38 \uCC98\uB9AC \uADDC\uCE59]\n- \uAE08\uD488\u00B7\uD5A5\uC751 \uC694\uAD6C \u2192 \"\uBC95\uC801\uC73C\uB85C \uBD88\uAC00\uB2A5\uD568\" \uC989\uC2DC \uBA85\uC2DC\n- \uC120\uAC70 \uACB0\uACFC \uC608\uCE21 \u2192 \"\uC608\uCE21 \uBD88\uAC00, \uD310\uB2E8\uC740 \uC720\uAD8C\uC790\uAED8\uC11C\"\n- \uC74C\uBAA8\uB860\u00B7\uD655\uC778 \uBD88\uAC00 \u2192 \"\uD655\uC778\uB41C \uC0AC\uC2E4\uC774 \uC544\uB2D8\"\n\n[\uBE44\uC0C1 \uB300\uC751]\n- \uC5B8\uC5B4\uD3ED\uB825\u00B7\uD611\uBC15 \u2192 \uC6B4\uC601\uC790 \uC54C\uB9BC + \uBC95\uC801 \uC870\uCE58 \uC548\uB0B4\n- \uC2E0\uACE0\u00B7\uC81C\uBCF4 \u2192 \uD574\uB2F9 \uAE30\uAD00 \uC5F0\uB77D\uCC98 \uC548\uB0B4 (\uACBD\uCC30 112, \uC120\uAD00\uC704 1390)"
    }
  };

  // ── 공개 API ──
  window.OCPromptV2 = {
    refreshPreview: refreshPreview, saveLayer: saveLayer, saveAll: saveAll,
    toggleLayer: toggleLayer, loadTemplate: loadTemplate,
    getLayerContent: function(l) { return layerContent[l]; },
    getLayerActive: function(l) { return layerActive[l]; },
    getCurrentVersion: function() { return currentVersion; },
    TEMPLATES: TEMPLATES,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
