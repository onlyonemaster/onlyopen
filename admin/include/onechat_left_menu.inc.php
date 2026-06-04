<?php
/**
 * 원챗(OneChat) 시스템 관리 + 결제관리 — 좌측 메뉴 블록
 * admin/include/admin_left_menu.inc.php 에서 include 하여 사용
 * ★ 최적화: 배지 카운트는 AJAX로 지연 로딩
 * 업데이트: 2026-05-09 — 시스템 관리 메뉴 수정 (아이콘 교체, 메뉴명 간소화)
 */
$ocPayFiles = ['onechat_dashboard.php','onechat_payment_list.php','onechat_subscriber_list.php','onechat_plan_stats.php','onechat_ai_insight.php'];
$ocSysFiles = [
    'onechat_system_dashboard.php','onechat_bot_manager.php','onechat_operator_queue.php',
    'onechat_scenario_manager.php','onechat_message_manager.php','onechat_contact_manager.php',
    'onechat_ai_analytics.php','onechat_companion_manager.php','onechat_system_config.php',
];
?>
<li class="<?=in_array($fileName, $ocSysFiles)?'active':''?>">
    <a href="javascript:;;"><span style="font-size:15px;margin-right:4px">🆕</span> <span>원챗시스템 관리</span>
        <span class="pull-right-container">
            <small class="label pull-right bg-red" id="ocSysBadge" style="display:none;"></small>
        </span>
    </a>
    <ul class="treeview-menu" style="display: block;">
        <li <?=$fileName=="onechat_system_dashboard.php"?" class='active'":""?>>
            <a href="/admin/onechat_system_dashboard.php"><i class="fa fa-tachometer"></i> <span>시스템 대시보드</span></a></li>
        <li <?=$fileName=="onechat_bot_manager.php"?" class='active'":""?>>
            <a href="/admin/onechat_bot_manager.php"><i class="fa fa-android"></i> <span>챗봇 설정 관리</span></a></li>
        <li <?=$fileName=="onechat_operator_queue.php"?" class='active'":""?>>
            <a href="/admin/onechat_operator_queue.php"><i class="fa fa-list-alt"></i> <span>운영자 대기열</span>
            <small class="label pull-right bg-red" id="ocQueueBadge" style="display:none;"></small></a></li>
        <li <?=$fileName=="onechat_scenario_manager.php"?" class='active'":""?>>
            <a href="/admin/onechat_scenario_manager.php"><i class="fa fa-sitemap"></i> <span>시나리오 캠페인 관리</span></a></li>
        <li <?=$fileName=="onechat_message_manager.php"?" class='active'":""?>>
            <a href="/admin/onechat_message_manager.php"><i class="fa fa-comments-o"></i> <span>메시지/대화 관리</span></a></li>
        <li <?=$fileName=="onechat_contact_manager.php"?" class='active'":""?>>
            <a href="/admin/onechat_contact_manager.php"><i class="fa fa-address-book"></i> <span>회원·연락처 관리</span></a></li>
        <li <?=$fileName=="onechat_ai_analytics.php"?" class='active'":""?>>
            <a href="/admin/onechat_ai_analytics.php"><i class="fa fa-line-chart"></i> <span>AI 분석·인사이트</span></a></li>
        <li <?=$fileName=="onechat_companion_manager.php"?" class='active'":""?>>
            <a href="/admin/onechat_companion_manager.php"><i class="fa fa-heartbeat"></i> <span>AI 동행·일기</span></a></li>
        <li <?=$fileName=="onechat_system_config.php"?" class='active'":""?>>
            <a href="/admin/onechat_system_config.php"><i class="fa fa-cogs"></i> <span>시스템 설정</span></a></li>
    </ul>
</li>
<li class="<?=in_array($fileName, $ocPayFiles)?'active':''?>">
    <a href="javascript:;;"><i class="fa fa-comments"></i> <span>원챗 결제관리</span>
        <span class="pull-right-container"><small class="label pull-right bg-orange" id="ocMenuBadge" style="display:none;"></small></span>
    </a>
    <ul class="treeview-menu" style="display: block;">
        <li <?=$fileName=="onechat_dashboard.php"?" class='active'":""?>>
            <a href="/admin/onechat_dashboard.php"><i class="fa fa-dashboard"></i> <span>원챗 대시보드</span></a></li>
        <li <?=$fileName=="onechat_payment_list.php"?" class='active'":""?>>
            <a href="/admin/onechat_payment_list.php"><i class="fa fa-credit-card"></i> <span>구독결제 관리</span></a></li>
        <li <?=$fileName=="onechat_subscriber_list.php"?" class='active'":""?>>
            <a href="/admin/onechat_subscriber_list.php"><i class="fa fa-users"></i> <span>구독회원 관리</span>
            <small class="label pull-right bg-green" id="ocTodayBadge" style="display:none;"></small></a></li>
        <li <?=$fileName=="onechat_plan_stats.php"?" class='active'":""?>>
            <a href="/admin/onechat_plan_stats.php"><i class="fa fa-bar-chart"></i> <span>플랜별 통계</span></a></li>
        <li <?=$fileName=="onechat_ai_insight.php"?" class='active'":""?>>
            <a href="/admin/onechat_ai_insight.php"><i class="fa fa-lightbulb-o"></i> <span>AI 결제 인사이트</span></a></li>
    </ul>
</li>
<script>
(function(){
  var badgeLoaded = false;
  function loadBadges() {
    if (badgeLoaded) return; badgeLoaded = true;
    var xhrSys = new XMLHttpRequest();
    xhrSys.open('GET', '/admin/ajax/onechat_system_api.php?action=system_kpi', true);
    xhrSys.withCredentials = true;
    xhrSys.onload = function() {
      if (xhrSys.status !== 200) return;
      try { var d = JSON.parse(xhrSys.responseText); if (!d.ok) return;
        if (d.queue_urgent > 0) { var qb = document.getElementById('ocQueueBadge'); qb.textContent = d.queue_urgent; qb.style.display = ''; }
        if (d.queue_pending > 0) { var sb = document.getElementById('ocSysBadge'); sb.textContent = d.queue_pending; sb.style.display = ''; }
      } catch(e) {}
    }; xhrSys.send();
    var xhrPay = new XMLHttpRequest();
    xhrPay.open('GET', '/admin/ajax/onechat_dashboard_api.php?action=menu_counts', true);
    xhrPay.withCredentials = true;
    xhrPay.onload = function() {
      if (xhrPay.status !== 200) return;
      try { var d = JSON.parse(xhrPay.responseText); if (!d.ok) return;
        if (d.total > 0) { var b = document.getElementById('ocMenuBadge'); b.textContent = d.total.toLocaleString('ko-KR'); b.style.display = ''; }
        if (d.today > 0) { var t = document.getElementById('ocTodayBadge'); t.textContent = d.today.toLocaleString('ko-KR'); t.style.display = ''; }
      } catch(e) {}
    }; xhrPay.send();
  }
  if (document.readyState === 'complete') setTimeout(loadBadges, 300);
  else window.addEventListener('load', function(){ setTimeout(loadBadges, 300); });
})();
</script>
