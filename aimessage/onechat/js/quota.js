/**
 * 원챗(OneChat) 구독 한도(Quota) 관리 - 프론트엔드
 * 
 * 역할:
 *   1. API에서 quota 상태를 주기적으로 조회
 *   2. 대시보드에 프로그레스바 렌더링
 *   3. 70% 도달 시 경고 모달 표시
 *   4. 100% 도달 시 기능 차단 안내
 *   5. 사이드바에 현재 플랜 배지 표시
 */
var Quota = {
  data: null,           // 최신 quota 데이터
  fetchTimer: null,     // 폴링 타이머
  warnedTypes: {},      // 이미 경고한 타입 추적 (중복 방지)

  // ── 초기화 ──────────────────────────────────────────────
  init: function() {
    this.fetch();
    // 30초마다 갱신
    this.fetchTimer = setInterval(function() { Quota.fetch(); }, 30000);
  },

  // ── API에서 quota 상태 조회 ──────────────────────────────
  fetch: function() {
    var self = this;
    fetch('/aimessage/onechat/api/quota.php?action=status', { credentials: 'include' })
      .then(function(r) { return r.json(); })
      .then(function(json) {
        if (json.ok) {
          self.data = json;
          self.renderDashboard();
          self.renderSidebarBadge();
          self.checkWarnings();
        }
      })
      .catch(function(err) {
        console.log('[Quota] fetch error:', err);
      });
  },

  // ── 대시보드 렌더링 ──────────────────────────────────────
  renderDashboard: function() {
    var d = this.data;
    if (!d || !d.limits) return;

    var planNames = {
      'free': 'Free', 'basic': 'Basic', 'standard': 'Standard', 'pro': 'Pro',
      'business': 'Business', 'b2b-biz': 'Business', 'b2b-pro': 'Pro (B2B)', 'b2b-team': 'Team',
      'team': 'Team'
    };

    // ── 요금제 배지 ──
    var badgeEl = document.getElementById('quotaPlanBadge');
    if (badgeEl) {
      badgeEl.textContent = planNames[d.plan_id] || d.plan_id || 'Free';
    }

    // 만료 표시
    var expireEl = document.getElementById('quotaExpireInfo');
    if (expireEl) {
      if (d.sub_end_date && d.plan_id !== 'free') {
        var ed = new Date(d.sub_end_date);
        var now = new Date();
        var daysLeft = Math.ceil((ed - now) / (1000 * 60 * 60 * 24));
        expireEl.innerHTML = daysLeft > 0
          ? '다음 갱신일: <strong>' + ed.getFullYear() + '.' + (ed.getMonth()+1) + '.' + ed.getDate() + '</strong> (' + daysLeft + '일 남음)'
          : '<span style="color:var(--red)">구독이 만료되었습니다</span>';
        expireEl.style.display = '';
      } else {
        expireEl.style.display = 'none';
      }
    }

    // ── 리소스별 프로그레스바 ──
    var resources = ['profile', 'ai_msg', 'resp'];
    var labels = {
      'profile': '프로필 확장',
      'ai_msg': 'AI 메시지',
      'resp': '챗봇 응답자'
    };

    for (var i = 0; i < resources.length; i++) {
      var key = resources[i];
      var lim = d.limits[key];
      if (!lim) continue;

      var barFill  = document.getElementById('quotaFill_' + key);
      var barPct   = document.getElementById('quotaPct_' + key);
      var barLabel = document.getElementById('quotaLabel_' + key);
      var barUsed  = document.getElementById('quotaUsed_' + key);

      var pct = lim.limit > 0 ? Math.min(100, (lim.used / lim.limit) * 100) : 0;

      if (barFill) {
        barFill.style.width = pct.toFixed(1) + '%';
        // 색상: 70%+ 주황, 90%+ 빨강, 0% 회색
        barFill.className = 'quota-fill';
        if (pct >= 90) barFill.classList.add('danger');
        else if (pct >= 70) barFill.classList.add('warn');
        else if (pct > 0) barFill.classList.add('safe');
      }
      if (barPct)  barPct.textContent = pct.toFixed(1) + '%';
      if (barLabel) barLabel.textContent = labels[key];
      if (barUsed) barUsed.textContent = lim.used.toLocaleString() + ' / ' + lim.limit.toLocaleString();
    }

    // ── 챗봇 개수 ──
    var botUsed = document.getElementById('quotaUsed_bot');
    if (botUsed) botUsed.textContent = d.limits.bot.used + ' / ' + d.limits.bot.limit;

    // ── 전체 섹션 표시 ──
    var section = document.getElementById('quotaSection');
    if (section) section.style.display = '';
  },

  // ── 사이드바 플랜 배지 ────────────────────────────────────
  renderSidebarBadge: function() {
    var d = this.data;
    if (!d) return;

    var planNames = {
      'free': 'Free', 'basic': 'Basic', 'standard': 'Standard', 'pro': 'Pro',
      'business': 'Business', 'b2b-biz': 'Business', 'b2b-pro': 'Pro (B2B)', 'b2b-team': 'Team',
      'team': 'Team'
    };
    var badge = document.getElementById('sbPlanBadge');
    if (badge) {
      badge.textContent = planNames[d.plan_id] || 'Free';
      badge.className = 'sb-plan-badge ' + (d.plan_id === 'free' ? 'free' : 'paid');
    }
  },

  // ── 70% 경고 체크 ──────────────────────────────────────
  checkWarnings: function() {
    var d = this.data;
    if (!d) return;

    if (!d.warnings) {
      // Reset warnedTypes for resources now below 70% so warnings fire again after reset
      if (d.limits) {
        ['profile', 'ai_msg', 'resp'].forEach(function(key) {
          var lim = d.limits[key];
          if (lim && lim.limit > 0) {
            var pct = (lim.used / lim.limit) * 100;
            if (pct < 70 && self.warnedTypes[key]) {
              delete self.warnedTypes[key];
            }
          }
        });
      }
      return;
    }

    var self = this;
    var labels = { 'profile': '프로필 확장', 'ai_msg': 'AI 메시지', 'resp': '챗봇 응답자' };
    var messages = [];

    for (var key in d.warnings) {
      if (!d.warnings.hasOwnProperty(key)) continue;
      var pct = d.warnings[key];
      if (!self.warnedTypes[key]) {
        self.warnedTypes[key] = true;
        messages.push(labels[key] + ' 사용량이 ' + pct + '%에 도달했습니다.');
      }
    }

    if (messages.length > 0) {
      this.showWarningModal(messages);
    }
  },

  // ── 경고 모달 표시 ─────────────────────────────────────
  showWarningModal: function(messages) {
    var overlay = document.getElementById('quotaWarningOverlay');
    var body = document.getElementById('quotaWarningBody');
    if (!overlay || !body) return;

    var html = '<div class="qw-icon"><i class="fas fa-exclamation-triangle"></i></div>';
    html += '<div class="qw-title">사용량 알림</div>';
    for (var i = 0; i < messages.length; i++) {
      html += '<div class="qw-msg">' + messages[i] + '</div>';
    }
    html += '<div class="qw-hint">한도 소진 전에 다음 플랜으로 업그레이드하시면 중단 없이 계속 이용하실 수 있습니다.</div>';
    html += '<div class="qw-btns">';
    html += '<button class="qw-btn upgrade" onclick="Quota.goUpgrade()">업그레이드 하기</button>';
    html += '<button class="qw-btn close" onclick="Quota.closeWarning()">닫기</button>';
    html += '</div>';
    body.innerHTML = html;
    overlay.classList.add('show');
  },

  closeWarning: function() {
    var overlay = document.getElementById('quotaWarningOverlay');
    if (overlay) overlay.classList.remove('show');
  },

  goUpgrade: function() {
    window.location.href = '/aimessage/onechat/subscribe.html';
  },

  // ── 차단 알림 (API 호출자가 사용) ──────────────────────
  showBlockedModal: function(type, used, limit) {
    var overlay = document.getElementById('quotaWarningOverlay');
    var body = document.getElementById('quotaWarningBody');
    if (!overlay || !body) return;

    var labels = { 'profile': '프로필 확장', 'ai_msg': 'AI 메시지', 'resp': '챗봇 응답자' };
    var html = '<div class="qw-icon" style="color:var(--red)"><i class="fas fa-ban"></i></div>';
    html += '<div class="qw-title">한도 초과</div>';
    html += '<div class="qw-msg">' + (labels[type] || type) + ' 사용 한도를 모두 소진했습니다. (' + used.toLocaleString() + '/' + limit.toLocaleString() + ')</div>';
    html += '<div class="qw-hint">서비스를 계속 이용하시려면 상위 플랜으로 업그레이드해 주세요.</div>';
    html += '<div class="qw-btns">';
    html += '<button class="qw-btn upgrade" onclick="Quota.goUpgrade()">업그레이드 하기</button>';
    html += '<button class="qw-btn close" onclick="Quota.closeWarning()">닫기</button>';
    html += '</div>';
    body.innerHTML = html;
    overlay.classList.add('show');
  }
};

// ── 페이지 로드 후 초기화 ────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  Quota.init();
});