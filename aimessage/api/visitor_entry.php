<?php
/**
 * 방문자 챗봇 엔트리 (2차 시스템)
 * chatbot.kiam.kr/s/{code} → 여기로 라우팅됨
 * visitor_id 자동 발급 + 방문자용 챗봇 페이지 서빙
 */

require_once dirname(__DIR__) . '/config/database.php';

// ── 브라우저 캐시 방지 (항상 최신 챗봇 상태 반영) ──
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ── 로그인 세션 확인: 로그인된 경우 mem_id를 JS로 전달 ──
$_logged_mem_id = '';
try {
    if (session_status() === PHP_SESSION_NONE) {
        @ini_set('session.cache_expire', 60);
        @ini_set('session.gc_maxlifetime', 86400);
        // 절대 경로 고정: aivote.kiam.kr(onechat)과 동일한 세션 저장소 사용
        // chatbot.kiam.kr DocumentRoot가 달라도 동일 세션을 읽기 위해 하드코딩
        $sess_path = '/home/kiam/_session';
        if (!is_dir($sess_path)) {
            $sess_path = $_SERVER['DOCUMENT_ROOT'] . '/_session';
        }
        if (is_dir($sess_path)) session_save_path($sess_path);
        session_set_cookie_params([
            'lifetime' => 86400, 'path' => '/', 'domain' => '.kiam.kr',
            'secure' => false, 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
    }
    $_logged_mem_id = trim($_SESSION['one_member_id'] ?? '');
    $_logged_mem_idx = 0;
    if ($_logged_mem_id) {
        try {
            $_db_vi = getDatabaseConnection();
            $stmt_vi = $_db_vi->prepare("SELECT mem_code FROM Gn_Member WHERE mem_id=? LIMIT 1");
            $stmt_vi->bind_param('s', $_logged_mem_id);
            $stmt_vi->execute();
            $row_vi = $stmt_vi->get_result()->fetch_assoc();
            if ($row_vi) $_logged_mem_idx = (int)$row_vi['mem_code'];
            $stmt_vi->close();
        } catch(Exception $e2) {}
    }
} catch (Exception $e) { $_logged_mem_id = ''; $_logged_mem_idx = 0; }

$code = trim($_GET['code'] ?? '');

if (empty($code) || !preg_match('/^[a-zA-Z0-9]{2,20}$/', $code)) {
    http_response_code(404);
    echo '<h2>잘못된 챗봇 링크입니다.</h2>';
    exit;
}

try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT id, mem_code, sms_idx, chatbot_name FROM Gn_onechat_shared_link WHERE short_code = ? LIMIT 1");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo '<html><head><meta charset="UTF-8"><title>챗봇을 찾을 수 없습니다</title></head>';
        echo '<body style="font-family:sans-serif;text-align:center;padding:60px 20px;">';
        echo '<h2>챗봇 링크가 존재하지 않습니다.</h2>';
        echo '<p style="color:#888;">링크를 다시 확인해 주세요.</p>';
        echo '</body></html>';
        exit;
    }

    $sms_idx_raw  = (int)$row['sms_idx'];
    $chatbot_name = htmlspecialchars($row['chatbot_name'] ?: '챗봇', ENT_QUOTES, 'UTF-8');
    $short_code  = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $mem_code    = htmlspecialchars($row['mem_code'], ENT_QUOTES, 'UTF-8');
    $request_idx  = (int)($_GET['r'] ?? 0); // 수신자 개별 식별자
    $visitor_param = preg_match('/^[A-Z0-9]{4,10}$/', $_GET['v'] ?? '') ? strtoupper($_GET['v']) : ''; // 공유 리스트 직접 링크용 visitor_id
    $from_list   = (isset($_GET['from_list']) && $_GET['from_list'] === '1') ? true : false; // 목록에서 진입 여부

    // Direction A: sms_idx=0 이거나 chatbot_name이 비어있으면 → mem_code로 기본 챗봇 자동 연결 시도
    $chatbot_name_raw = trim($row['chatbot_name'] ?? '');
    if ($sms_idx_raw === 0 || empty($chatbot_name_raw)) {
        // mem_code 계정의 챗봇 중 이름이 있는 가장 최신 것을 자동 연결
        $auto_stmt = $db->prepare("
            SELECT sms_idx, chatbot_name 
            FROM Gn_aievent_ms_info 
            WHERE customer_id = ? AND chatbot_name IS NOT NULL AND chatbot_name != ''
            ORDER BY sms_idx DESC LIMIT 1
        ");
        $auto_stmt->bind_param('s', $row['mem_code']);
        $auto_stmt->execute();
        $auto_row = $auto_stmt->get_result()->fetch_assoc();
        $auto_stmt->close();

        if ($auto_row) {
            // 자동 연결 성공 → DB 업데이트 후 계속 진행
            $upd = $db->prepare("UPDATE Gn_onechat_shared_link SET sms_idx=?, chatbot_name=? WHERE short_code=?");
            $upd->bind_param('iss', $auto_row['sms_idx'], $auto_row['chatbot_name'], $code);
            $upd->execute();
            $upd->close();
            $sms_idx_raw  = (int)$auto_row['sms_idx'];
            $chatbot_name = htmlspecialchars($auto_row['chatbot_name'], ENT_QUOTES, 'UTF-8');
        } else {
            // 챗봇 설정 없음 → 기본 챗봇 자동 생성 후 바로 연결 (준비중 메시지 없음)
            $default_name = $row['mem_code'];
            $mem_code_esc = $db->real_escape_string($row['mem_code']);
            $name_esc     = $db->real_escape_string($default_name);
            $db->query("INSERT INTO Gn_aievent_ms_info (customer_id, chatbot_name, sendable, regdate) VALUES ('{$mem_code_esc}', '{$name_esc}', 1, NOW())");
            $new_sms_idx = (int)$db->insert_id;
            $upd2 = $db->prepare("UPDATE Gn_onechat_shared_link SET sms_idx=?, chatbot_name=? WHERE short_code=?");
            $upd2->bind_param('iss', $new_sms_idx, $default_name, $code);
            $upd2->execute();
            $upd2->close();
            $sms_idx_raw  = $new_sms_idx;
            $chatbot_name = htmlspecialchars($default_name, ENT_QUOTES, 'UTF-8');
        }
    }
    $sms_idx = $sms_idx_raw;

    /* ── 구독 테이블 자동 생성 ────────────────────────────── */
    $db->query("
        CREATE TABLE IF NOT EXISTS Gn_visitor_subscriptions (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            visitor_id    VARCHAR(20)  NOT NULL,
            short_code    VARCHAR(20)  NOT NULL,
            sms_idx       INT          NOT NULL DEFAULT 0,
            chatbot_name  VARCHAR(100) NOT NULL DEFAULT '',
            operator_id   VARCHAR(50)  NOT NULL DEFAULT '',
            pwa_installed TINYINT      NOT NULL DEFAULT 0,
            first_chat_at DATETIME     NULL,
            last_chat_at  DATETIME     NULL,
            unread_count  INT          NOT NULL DEFAULT 0,
            subscribed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_visitor_bot (visitor_id, short_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

} catch (Exception $e) {
    http_response_code(500);
    echo '서버 오류가 발생했습니다.';
    exit;
}
/* PHP 단에서 visitor_id를 미리 읽어 구독 수 판별은 JS로 위임
   (visitor_id 는 localStorage 에 있으므로 JS 에서 처리) */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= $chatbot_name ?></title>
<link rel="icon" href="/favicon.ico">
<link rel="manifest" href="/manifest.php?code=<?= urlencode($short_code) ?>&name=<?= urlencode($chatbot_name) ?>">
<meta name="theme-color" content="#4F46E5">
<script src="/pwa-install-chatbot_v1.js" defer></script>
<script src="/aimessage/chatbot/frontend/js/oc_fingerprint.js" defer></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  /* ── 다크 테마 (기본) ── */
  --bg: #0d1421;
  --bg2: #131c2e;
  --card: #1a2540;
  --border: rgba(255,255,255,0.07);
  --text: #e8eef6;
  --text2: #7a90b0;
  --blue: #4a90e2;
  --green: #22c55e;
  --bubble-bot: #1a2f50;
  --bubble-user: #0f3460;
  --bubble-bot-text: #d0dff0;
  --bubble-user-text: #cce0ff;
  /* ── 운영자 안내문 (다크 보라) ── */
  --notice-gradient: linear-gradient(135deg, #3b0764 0%, #5b21b6 45%, #7c3aed 100%);
  --notice-header-bg: rgba(0,0,0,0.28);
  --notice-body-bg: rgba(109,40,217,0.18);
  --notice-body-text: #ddd6fe;
  --notice-time-text: #c4b5fd;
  --notice-border: rgba(139,92,246,0.5);
  --notice-glow: 0 4px 20px rgba(124,58,237,0.4);
  /* ── 공통 ── */
  --radius: 16px;
  --header-h: 60px;
  --input-h: 64px;
}

/* ── 라이트 테마 ── */
[data-theme="light"] {
  --bg: #eef2f8;
  --bg2: #ffffff;
  --card: #ffffff;
  --border: rgba(0,0,0,0.09);
  --text: #1a2540;
  --text2: #5a7090;
  --blue: #1d6dcc;
  --bubble-bot: #e0e8f4;
  --bubble-user: #dbeafe;
  --bubble-bot-text: #1a2540;
  --bubble-user-text: #1e3a5f;
  /* ── 운영자 안내문 (라이트 보라) ── */
  --notice-gradient: linear-gradient(135deg, #5b21b6 0%, #7c3aed 50%, #8b5cf6 100%);
  --notice-header-bg: rgba(0,0,0,0.12);
  --notice-body-bg: #f5f3ff;
  --notice-body-text: #3b0764;
  --notice-time-text: #7c3aed;
  --notice-border: rgba(124,58,237,0.35);
  --notice-glow: 0 4px 16px rgba(124,58,237,0.18);
}

html, body {
  height: 100%; width: 100%;
  background: var(--bg);
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  overflow: hidden;
}

/* ─── 레이아웃 ─── */
.app { display: flex; flex-direction: column; height: 100vh; height: 100dvh; max-width: 480px; margin: 0 auto; touch-action: manipulation; }

/* ─── 헤더 ─── */
.header {
  height: var(--header-h);
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 12px;
  padding: 0 16px;
  flex-shrink: 0;
  position: relative;
}
.header-avatar {
  width: 38px; height: 38px; border-radius: 12px;
  background: linear-gradient(135deg, #0f2a50, #1d4ed8, #4a90e2);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800; color: #fff;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(74,144,226,0.35);
  letter-spacing: -0.5px;
}
.header-info { flex: 1; min-width: 0; }
.header-name {
  font-size: 14px; font-weight: 700; color: var(--text);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.header-sub { font-size: 11px; color: var(--text2); display:flex; align-items:center; gap:4px; }
.nick-edit-btn {
  background: none; border: none; padding: 0 2px; cursor: pointer;
  font-size: 11px; opacity: 0.6; line-height: 1; vertical-align: middle;
}
.nick-edit-btn:hover { opacity: 1; }
.theme-toggle-btn {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(255,255,255,0.08);
  border: 1px solid var(--border);
  color: var(--text2); font-size: 15px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}
.theme-toggle-btn:hover { background: rgba(255,255,255,0.14); color: var(--text); }
[data-theme="light"] .theme-toggle-btn { background: rgba(0,0,0,0.06); }

.pwa-install-btn {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(255,255,255,0.08);
  border: 1px solid var(--border);
  color: var(--text2); font-size: 16px;
  display: none;
  align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
  padding: 0;
}
.pwa-install-btn:hover { background: rgba(59,130,246,0.2); color: var(--blue); }
[data-theme="light"] .pwa-install-btn { background: rgba(0,0,0,0.06); }
#pwaInstallBtn { display: none !important; }
.mychatbot-btn {
  padding: 5px 10px; border-radius: 14px;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: #fff; border: none;
  font-size: 11px; font-weight: 700;
  cursor: pointer; white-space: nowrap; flex-shrink: 0;
  display: flex; align-items: center; gap: 4px;
  transition: opacity 0.2s, transform 0.1s;
  box-shadow: 0 2px 8px rgba(79,70,229,0.35);
}
.mychatbot-btn:hover { opacity: 0.88; }
.mychatbot-btn:active { transform: scale(0.96); }
/* ─── 삼점 메뉴 버튼 & 드롭다운 ─── */
#moreMenuBtn {
  width: 32px; height: 32px;
  background: none; border: none;
  font-size: 22px; font-weight: 700; color: var(--text2);
  cursor: pointer; padding: 0;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; line-height: 1;
}
#moreMenuBtn:hover { background: rgba(255,255,255,0.08); color: var(--text); }
[data-theme="light"] #moreMenuBtn:hover { background: rgba(0,0,0,0.06); }
#moreMenuDropdown {
  display: none;
  position: fixed;
  top: 56px; right: 12px;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.3);
  min-width: 185px;
  z-index: 2000;
  overflow: hidden;
  animation: dropIn .18s cubic-bezier(.32,.72,0,1);
}
#moreMenuDropdown.open { display: block; }
.menu-item {
  padding: 13px 18px;
  font-size: 14px; color: var(--text);
  cursor: pointer; display: flex; align-items: center; gap: 10px;
  border: none; background: none; width: 100%; text-align: left;
  font-family: inherit;
}
.menu-item:hover { background: rgba(255,255,255,0.07); }
[data-theme="light"] .menu-item:hover { background: rgba(0,0,0,0.05); }
.menu-item.hidden { display: none !important; }
.menu-divider { height: 1px; background: var(--border); margin: 4px 0; }
@keyframes dropIn {
  from { opacity:0; transform:translateY(-8px) scale(.96); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
/* ─── 메시지 컨텍스트 메뉴 ─── */
#msgContextMenu {
  display: none;
  position: fixed;
  z-index: 3000;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  min-width: 155px;
  overflow: hidden;
}
#msgContextMenu.open { display: block; }
.ctx-item {
  padding: 12px 18px;
  font-size: 14px; color: var(--text);
  cursor: pointer; display: flex; align-items: center; gap: 10px;
  border: none; background: none; width: 100%;
  text-align: left; font-family: inherit;
}
.ctx-item:hover { background: rgba(255,255,255,0.08); }
[data-theme="light"] .ctx-item:hover { background: rgba(0,0,0,0.05); }
#ctxDelete { color: #f87171; }


/* ─── 뒤로가기(목록) 버튼 ─── */
.back-to-list-btn {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,0.08);
  border: 1px solid var(--border);
  color: var(--text2); font-size: 18px;
  display: none;
  align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
  text-decoration: none;
}
.back-to-list-btn:hover { background: rgba(79,70,229,0.2); color: var(--text); }
.back-to-list-btn.show  { display: flex; }
[data-theme="light"] .back-to-list-btn { background: rgba(0,0,0,0.06); }

/* ─── 메시지 영역 ─── */
.messages {
  flex: 1; overflow-y: auto; padding: 16px 12px;
  display: flex; flex-direction: column; gap: 8px;
  scroll-behavior: smooth;
}
.messages::-webkit-scrollbar { width: 4px; }
.messages::-webkit-scrollbar-track { background: transparent; }
.messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

/* ─── 메시지 버블 ─── */
.msg-wrap { display: flex; gap: 8px; max-width: 100%; }
.msg-wrap.user { flex-direction: row-reverse; }

.msg-avatar {
  width: 30px; height: 30px; border-radius: 10px;
  background: linear-gradient(135deg, #1a3a5c, #2563eb);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0; margin-top: 2px;
}
.msg-avatar.user-av {
  background: linear-gradient(135deg, #1e3a6e, #2563eb);
}
[data-theme="light"] .msg-avatar {
  background: linear-gradient(135deg, #0f2a50, #1d4ed8);
}
[data-theme="light"] .msg-avatar.user-av {
  background: linear-gradient(135deg, #1e40af, #3b82f6);
}

.bubble {
  max-width: 75%;
  min-width: 80px;
  padding: 10px 14px;
  border-radius: 16px;
  font-size: 14px; line-height: 1.6;
  word-break: keep-all;
  overflow-wrap: break-word;
}

.msg-wrap > div:not(.msg-avatar) {
  display: flex;
  flex-direction: column;
  max-width: calc(100% - 38px);
  min-width: 0;
}
.msg-wrap.user > div:not(.msg-avatar) {
  align-items: flex-end;
}
.msg-wrap.bot > div:not(.msg-avatar) {
  align-items: flex-start;
}
.bot .bubble {
  background: var(--bubble-bot);
  border-bottom-left-radius: 4px;
  color: var(--bubble-bot-text, var(--text));
  min-width: min(200px, 75%);
}
.user .bubble {
  background: var(--bubble-user);
  border-bottom-right-radius: 4px;
  color: var(--bubble-user-text, var(--text));
  text-align: left;
}
.msg-time {
  font-size: 10px; color: var(--text2);
  margin-top: 2px; padding: 0 2px;
}
/* ─── 운영자 안내문 카드 (Style C: 보라 풀박스) ─── */
.operator-notice {
  width: 100%;
  margin: 10px 0;
  animation: noticeIn 0.35s cubic-bezier(.22,.68,0,1.2) both;
}
@keyframes noticeIn {
  from { opacity:0; transform:translateY(8px) scale(0.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
.operator-notice-inner {
  background: var(--notice-gradient);
  border: 1.5px solid var(--notice-border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--notice-glow);
}
.operator-notice-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  background: var(--notice-header-bg);
  color: rgba(255,255,255,0.92);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.operator-notice-header .notice-icon { font-size: 14px; }
.operator-notice-header .notice-label {
  flex: 1;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
  opacity: 0.85;
}
.operator-notice-header .notice-badge {
  background: rgba(255,255,255,0.18);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 20px;
  padding: 2px 8px;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #fff;
}
.operator-notice-divider {
  height: 1px;
  background: rgba(255,255,255,0.15);
  margin: 0 14px;
}
.operator-notice-body {
  padding: 13px 16px 11px;
  font-size: 14px;
  line-height: 1.7;
  color: var(--notice-body-text);
  background: var(--notice-body-bg);
  word-break: break-word;
  overflow-wrap: break-word;
}
.operator-notice-time {
  padding: 0 16px 9px;
  font-size: 10px;
  color: var(--notice-time-text);
  text-align: right;
  background: var(--notice-body-bg);
  letter-spacing: 0.3px;
}



/* ─── 타이핑 인디케이터 ─── */
.typing { display: none; }
.typing.visible { display: flex; }
.typing-dots { display: flex; gap: 4px; padding: 12px 14px; }
.typing-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--text2);
  animation: blink 1.2s infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes blink { 0%,80%,100%{opacity:.3} 40%{opacity:1} }

/* ─── PWA 설치 유도 메시지 ─── */
.pwa-nudge {
  background: rgba(59,130,246,0.12);
  border: 1px solid rgba(59,130,246,0.25);
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 13px; line-height: 1.7;
  color: var(--text2);
  margin: 4px 0;
}
.pwa-nudge strong { color: var(--blue); }
.pwa-nudge-btn {
  margin-top: 8px; padding: 7px 16px;
  background: var(--blue); color: #fff;
  border: none; border-radius: 20px;
  font-size: 12px; font-weight: 600;
  cursor: pointer; width: 100%;
}

/* ─── PWA 설치 카드 ─── */
.pwa-card {
  background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 50%, #3b82f6 100%);
  border: 1.5px solid rgba(59,130,246,0.5);
  border-radius: 16px;
  overflow: hidden;
  margin: 10px 0;
  box-shadow: 0 4px 20px rgba(37,99,235,0.3);
  animation: pwaCardIn 0.4s cubic-bezier(.22,.68,0,1.2) both;
}
@keyframes pwaCardIn {
  from { opacity:0; transform:translateY(12px) scale(0.95); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}
.pwa-card-header {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px;
  background: rgba(0,0,0,0.2);
  color: rgba(255,255,255,0.95);
}
.pwa-card-header .pwa-card-icon { font-size: 18px; }
.pwa-card-header .pwa-card-title {
  flex: 1; font-size: 13px; font-weight: 800;
  letter-spacing: 0.5px;
}
.pwa-card-body {
  padding: 14px 16px 10px;
  background: rgba(37,99,235,0.15);
  color: #dbeafe;
  font-size: 13px; line-height: 1.7;
}
.pwa-card-phone {
  margin-top: 10px;
  display: flex; align-items: center; gap: 8px;
}
.pwa-card-phone label {
  font-size: 11px; color: #93c5fd;
  font-weight: 600; white-space: nowrap;
}
.pwa-card-phone input {
  flex: 1; padding: 8px 12px;
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 10px;
  background: rgba(255,255,255,0.1);
  color: #fff; font-size: 13px;
  outline: none; font-family: inherit;
}
.pwa-card-phone input::placeholder { color: rgba(255,255,255,0.4); }
.pwa-card-phone input:focus { border-color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.15); }
.pwa-card-actions {
  padding: 10px 16px 14px;
  background: rgba(37,99,235,0.15);
}
.pwa-card-install-btn {
  width: 100%; padding: 11px 16px;
  background: #fff; color: #1e40af;
  border: none; border-radius: 12px;
  font-size: 14px; font-weight: 700;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s;
}
.pwa-card-install-btn:hover { background: #dbeafe; }
.pwa-card-install-btn:active { transform: scale(0.98); }
.pwa-card-saved {
  text-align: center; padding: 6px;
  font-size: 11px; color: #86efac;
  display: none;
}

/* ─── 전화번호 등록 모달 ─── */
#phoneModal {
  display: none;
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(0,0,0,0.6);
  align-items: flex-end; justify-content: center;
}
#phoneModal.open { display: flex; }
#phoneModalBox {
  background: #ffffff;
  border-radius: 24px 24px 0 0;
  padding: 28px 20px 40px;
  width: 100%; max-width: 680px;
  animation: iosSlideUp .35s cubic-bezier(.32,.72,0,1);
}
#phoneModalBox .pm-title {
  font-size: 19px; font-weight: 800; color: #111827;
  text-align: center; margin-bottom: 4px;
}
#phoneModalBox .pm-sub {
  font-size: 13px; color: #6b7280;
  text-align: center; margin-bottom: 22px;
}
.pm-benefits {
  background: #f0f9ff;
  border-radius: 14px;
  padding: 16px 18px;
  margin-bottom: 20px;
}
.pm-benefit-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 7px 0;
  border-bottom: 1px solid #e0f2fe;
  font-size: 14px; color: #1e3a5f; line-height: 1.5;
}
.pm-benefit-item:last-child { border-bottom: none; padding-bottom: 0; }
.pm-benefit-icon {
  font-size: 18px; flex-shrink: 0; margin-top: 1px;
}
.pm-benefit-title {
  font-weight: 700; color: #0f172a; display: block; margin-bottom: 1px;
}
.pm-benefit-desc { font-size: 12px; color: #64748b; }
.pm-input-wrap {
  display: flex; gap: 8px; margin-bottom: 14px;
}
.pm-input-wrap input {
  flex: 1; padding: 13px 16px;
  border: 2px solid #d1d5db; border-radius: 12px;
  font-size: 16px; color: #111827;
  outline: none; font-family: inherit;
  transition: border-color .2s;
}
.pm-input-wrap input:focus { border-color: #2563eb; }
.pm-input-wrap input::placeholder { color: #9ca3af; }
.pm-register-btn {
  width: 100%; padding: 14px;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: #fff; border: none; border-radius: 14px;
  font-size: 16px; font-weight: 700; cursor: pointer;
  transition: opacity .2s; margin-bottom: 10px;
}
.pm-register-btn:hover { opacity: .88; }
.pm-register-btn:disabled { opacity: .5; cursor: default; }
.pm-skip-btn {
  width: 100%; padding: 10px;
  background: none; border: none;
  font-size: 13px; color: #9ca3af;
  cursor: pointer;
}
.pm-success-msg {
  display: none; text-align: center;
  padding: 14px; color: #059669;
  font-size: 15px; font-weight: 700;
}

/* ─── Android 설치 가이드 ─── */
#androidGuideOverlay {
  display: none;
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(0,0,0,0.55);
  align-items: flex-end; justify-content: center;
}
#androidGuideOverlay.open { display: flex; }
#androidGuideBox {
  background: white;
  border-radius: 24px 24px 0 0;
  padding: 24px 20px 32px;
  width: 100%; max-width: 680px;
  animation: iosSlideUp .3s ease;
}
#androidGuideBox .guide-title {
  font-size: 17px; font-weight: 800; color: #111827;
  text-align: center; margin-bottom: 6px;
}
#androidGuideBox .guide-sub {
  font-size: 13px; color: #6b7280;
  text-align: center; margin-bottom: 20px; line-height: 1.6;
}
#androidGuideClose {
  margin-top: 20px; width: 100%;
  padding: 14px; border: none; border-radius: 14px;
  background: #2563eb; color: white;
  font-size: 15px; font-weight: 700; cursor: pointer;
}

/* ─── 기타 브라우저 설치 가이드 ─── */
#otherBrowserGuide {
  display: none;
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(0,0,0,0.55);
  align-items: flex-end; justify-content: center;
}
#otherBrowserGuide.open { display: flex; }
#otherBrowserGuideBox {
  background: white;
  border-radius: 24px 24px 0 0;
  padding: 24px 20px 32px;
  width: 100%; max-width: 680px;
  animation: iosSlideUp .3s ease;
}
#otherBrowserGuideBox .guide-title {
  font-size: 17px; font-weight: 800; color: #111827;
  text-align: center; margin-bottom: 6px;
}
#otherBrowserGuideBox .guide-sub {
  font-size: 13px; color: #6b7280;
  text-align: center; margin-bottom: 20px;
}
#otherBrowserGuideClose {
  margin-top: 20px; width: 100%;
  padding: 14px; border: none; border-radius: 14px;
  background: #2563eb; color: white;
  font-size: 15px; font-weight: 700; cursor: pointer;
}

/* ─── iOS 가이드 오버레이 ─── */
#iosGuideOverlay {
  display: none;
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(0,0,0,0.55);
  align-items: flex-end;
  justify-content: center;
}
#iosGuideOverlay.open { display: flex; }
#iosGuideBox {
  background: white;
  border-radius: 24px 24px 0 0;
  padding: 24px 20px 32px;
  width: 100%; max-width: 680px;
  animation: iosSlideUp .3s ease;
}
@keyframes iosSlideUp {
  from { transform: translateY(100%); }
  to   { transform: translateY(0); }
}
#iosGuideBox .guide-title {
  font-size: 17px; font-weight: 800; color: #111827;
  text-align: center; margin-bottom: 6px;
}
#iosGuideBox .guide-sub {
  font-size: 13px; color: #6b7280;
  text-align: center; margin-bottom: 20px;
}
.guide-step {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 12px 0; border-bottom: 1px solid #f3f4f6;
}
.guide-step:last-of-type { border-bottom: none; }
.guide-step-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: #2563eb; color: white;
  font-size: 13px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.guide-step-text { font-size: 14px; color: #374151; line-height: 1.6; }
.guide-step-text strong { color: #111827; }
.guide-step-icon { font-size: 22px; }
#iosGuideClose {
  margin-top: 20px; width: 100%;
  padding: 14px; border: none; border-radius: 14px;
  background: #2563eb; color: white;
  font-size: 15px; font-weight: 700; cursor: pointer;
}
[data-theme="light"] .pwa-card {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #60a5fa 100%);
  box-shadow: 0 4px 16px rgba(37,99,235,0.2);
}

/* ─── Android 설치 배너 ─── */
#pwaInstallBanner {
  display: none;
  background: linear-gradient(135deg, #1d4ed8, #4f46e5);
  color: #fff;
  padding: 12px 16px;
  margin: 8px 12px;
  border-radius: 14px;
  gap: 10px;
  align-items: center;
  animation: iosSlideUp .3s ease;
  flex-shrink: 0;
}
#pwaInstallBanner.show { display: flex; }
.pwa-banner-text { flex: 1; }
.pwa-banner-title { font-size: 14px; font-weight: 700; }
.pwa-banner-desc  { font-size: 11px; opacity: .85; margin-top: 2px; }
.pwa-banner-btn {
  background: #fff; color: #1d4ed8;
  border: none; border-radius: 10px;
  padding: 8px 14px; font-size: 13px; font-weight: 700;
  cursor: pointer; white-space: nowrap; flex-shrink: 0;
}
.pwa-banner-close {
  background: none; border: none; color: rgba(255,255,255,.7);
  font-size: 18px; cursor: pointer; padding: 0 4px; flex-shrink: 0;
}
/* 버튼 스피너 */
@keyframes spin { to { transform: rotate(360deg); } }
.pwa-spin { display: inline-block; animation: spin .8s linear infinite; }

/* ─── 입력 바 ─── */
.input-bar {
  min-height: var(--input-h);
  height: auto;
  background: var(--bg2);
  border-top: 1px solid var(--border);
  display: flex; align-items: flex-end; gap: 10px;
  padding: 10px 12px;
  padding-bottom: max(10px, env(safe-area-inset-bottom));
  flex-shrink: 0;
}
.msg-input {
  flex: 1; background: rgba(255,255,255,0.06);
  border: 1px solid var(--border);
  border-radius: 24px; padding: 10px 16px;
  color: var(--text); font-size: 14px;
  outline: none; resize: none;
  min-height: 42px; max-height: 140px; height: 42px;
  overflow-y: hidden; box-sizing: border-box;
  font-family: inherit;
}
/* iOS Safari: 16px 미만 input/textarea 포커스 시 자동 확대 방지 */
@supports (-webkit-touch-callout: none) {
  .msg-input { font-size: 16px; }
}
.msg-input::placeholder { color: var(--text2); }
.msg-input:focus { border-color: rgba(59,130,246,0.4); }
.send-btn {
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--blue); color: #fff;
  border: none; font-size: 16px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0;
  transition: opacity .15s;
}
.send-btn:disabled { opacity: 0.4; cursor: default; }
.suggestion-chips { display:flex; flex-wrap:wrap; gap:7px; padding:6px 6px 10px 44px; }
.suggestion-chip {
  background: rgba(255,255,255,0.10);
  border: 1px solid rgba(255,255,255,0.22);
  color: #cce4ff;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  cursor: pointer;
  transition: background .15s, border-color .15s;
  white-space: nowrap;
}
.suggestion-chip:hover { background: rgba(99,179,237,0.25); border-color: rgba(99,179,237,0.6); color:#fff; }
/* 직접 입력하기 버튼 */
.suggestion-chip.direct-input {
  background: rgba(99,102,241,0.12);
  border-color: rgba(99,102,241,0.45);
  color: #a5b4fc;
}
.suggestion-chip.direct-input:hover { background: rgba(99,102,241,0.28); border-color: rgba(99,102,241,0.8); color:#fff; }
[data-theme="light"] .suggestion-chip.direct-input { background: rgba(99,102,241,0.08); border-color: rgba(99,102,241,0.4); color: #4338ca; }
#micBtn {
  width: 38px; height: 38px; border-radius: 50%;
  background: rgba(255,255,255,0.08);
  border: 1.5px solid rgba(255,255,255,0.28);
  color: #9aafc9;
  cursor: pointer; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
#micBtn:hover { background: rgba(59,130,246,0.1); color: var(--blue); border-color: var(--blue); }
#attachBtn {
  width: 38px; height: 38px; border-radius: 50%;
  background: transparent;
  border: none;
  color: #9aafc9;
  cursor: pointer; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  transition: background 0.15s, color 0.15s;
}
#attachBtn:hover { background: rgba(59,130,246,0.12); color: var(--blue); }
#micBtn.recording {
  background: #fee2e2; color: #ef4444; border-color: #ef4444;
  animation: micPulse 1.2s infinite;
}
@keyframes micPulse {
  0%,100% { box-shadow: 0 0 0 3px rgba(239,68,68,0.25); }
  50%      { box-shadow: 0 0 0 7px rgba(239,68,68,0.08); }
}

/* ─── 빈 화면 ─── */
.empty-state {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 12px; padding: 40px 20px; text-align: center;
  color: var(--text2);
}
.empty-icon {
  width: 64px; height: 64px; border-radius: 20px;
  background: rgba(59,130,246,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
}
.empty-title { font-size: 15px; font-weight: 700; color: var(--text); }
.empty-desc { font-size: 13px; line-height: 1.7; }
</style>
</head>
<body>
<div class="app">
  <!-- 헤더 -->
  <div class="header">
    <a class="back-to-list-btn" id="backToListBtn" href="/chatbot_list.html" title="목록으로">&#8592;</a>
    <div class="header-avatar" id="headerAv">AI</div>
    <div class="header-info">
      <div class="header-name" id="headerName"><?= $chatbot_name ?></div>
      <div class="header-sub" id="headerSub">
        <span id="nickDisplay">닉네임 로딩 중...</span>
        <button class="nick-edit-btn" id="nickEditBtn" title="닉네임 변경" onclick="openNickModal()">✏️</button>
      </div>
    </div>
    <!-- 기능 버튼 (DOM 유지, JS 호환) -->
    <button id="videoCallBtn" style="display:none;">📹</button>
    <button id="voiceCallBtn" style="display:none;">🎙️</button>
    <button id="pwaInstallBtn" style="display:none;">📲</button>
    <button id="themeToggleBtn" style="display:none;">🌙</button>
    <!-- 헤더 표시 버튼 -->
    <button class="mychatbot-btn" id="mychatbotBtn" onclick="goMyChatbot()" title="MY챗봇 만들기">✨ MY챗봇</button>
    <button id="moreMenuBtn" title="메뉴">⋮</button>
  </div>

  <!-- Android PWA 설치 배너 (beforeinstallprompt 발생 시 자동 표시) -->
  <div id="pwaInstallBanner">
    <div class="pwa-banner-text">
      <div class="pwa-banner-title">📲 앱으로 설치하기</div>
      <div class="pwa-banner-desc">홈화면에 추가하면 더 빠르게 이용할 수 있어요</div>
    </div>
    <button class="pwa-banner-btn" onclick="triggerPwaInstall()">설치</button>
    <button class="pwa-banner-close" onclick="this.parentElement.remove()">✕</button>
  </div>

  <!-- 메시지 목록 -->
  <div class="messages" id="messages">
    <div class="empty-state" id="emptyState">
      <div class="empty-icon">🤖</div>
      <div class="empty-title"><?= $chatbot_name ?></div>
      <div class="empty-desc">안녕하세요! 궁금하신 점을 편하게 물어보세요.</div>
    </div>
    <!-- 타이핑 인디케이터 -->
    <div class="msg-wrap bot typing" id="typingIndicator">
      <div class="msg-avatar">🤖</div>
      <div class="bubble" style="background:var(--bubble-bot);border-bottom-left-radius:4px;">
        <div class="typing-dots">
          <div class="typing-dot"></div>
          <div class="typing-dot"></div>
          <div class="typing-dot"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 화상통화 초대 배너 -->
  <div id="vcInviteBanner" style="display:none;background:linear-gradient(90deg,#4c6ef5,#667eea);color:#fff;padding:10px 14px;align-items:center;gap:10px;cursor:pointer" onclick="joinVideoInvite()">
    <span style="font-size:20px">📹</span>
    <div style="flex:1;min-width:0">
      <div id="vcInviteText" style="font-size:13px;font-weight:700">화상통화 초대가 왔습니다!</div>
      <div id="vcInviteHost" style="font-size:11px;opacity:.85"></div>
    </div>
    <span style="background:rgba(255,255,255,.2);border-radius:8px;padding:4px 10px;font-size:12px;font-weight:700;white-space:nowrap">참여하기 ▶</span>
    <span style="font-size:18px;opacity:.7;flex-shrink:0;padding:0 4px" onclick="event.stopPropagation();dismissVcBanner()">✕</span>
  </div>

  <!-- 입력 바 -->
  <div class="input-bar">
    <input type="file" id="hiddenFileInput" style="display:none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip">
    <button id="attachBtn" title="파일 첨부">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
    <textarea class="msg-input" id="msgInput"
              placeholder="메시지를 입력하세요..." autocomplete="off"
              maxlength="1000" rows="1"></textarea>
    <button id="micBtn" title="음성 입력">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
    </button>
    <button class="send-btn" id="sendBtn" disabled>&#10148;</button>
  </div>

  <!-- iOS 홈 화면 추가 가이드 -->
  <div id="iosGuideOverlay" onclick="closeIosGuide(event)">
    <div id="iosGuideBox">
      <div class="guide-title">📱 홈 화면에 추가하기</div>
      <div class="guide-sub">다음 앱처럼 바로 실행할 수 있어요</div>
      <div class="guide-step">
        <div class="guide-step-num">1</div>
        <div class="guide-step-text">
          하단 가운데 <strong>공유 버튼</strong>을 탭하세요<br>
          <span class="guide-step-icon">⬛</span>
          <span style="font-size:13px;color:#9ca3af">(네모에 화살표 올라가는 아이콘)</span>
        </div>
      </div>
      <div class="guide-step">
        <div class="guide-step-num">2</div>
        <div class="guide-step-text">
          스크롤 내려서 <strong>“홈 화면에 추가”</strong>를 탭하세요<br>
          <span class="guide-step-icon">➕</span>
        </div>
      </div>
      <div class="guide-step">
        <div class="guide-step-num">3</div>
        <div class="guide-step-text">
          오른쪽 위 <strong>“추가”</strong>를 탭하면 완료!<br>
          <span style="font-size:13px;color:#059669">홈 화면에 아이콘이 생겨요 ✅</span>
        </div>
      </div>
      <button id="iosGuideClose" onclick="closeIosGuide()">확인했어요</button>
    </div>
  </div>

  <!-- 닉네임 수정 모달 -->
  <div id="nickModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card,#fff);border-radius:16px;padding:24px 20px;width:90%;max-width:320px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div style="font-size:16px;font-weight:700;margin-bottom:8px;color:var(--text-primary,#111);">✏️ 닉네임 변경</div>
      <div style="font-size:13px;color:var(--text-secondary,#6b7280);margin-bottom:16px;">챗봇에서 사용할 닉네임을 입력해 주세요</div>
      <input type="text" id="nickInput" maxlength="20" placeholder="닉네임 입력 (최대 20자)"
        style="width:100%;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;color:var(--text-primary,#111);background:var(--bg-input,#f9fafb);">
      <div style="display:flex;gap:8px;margin-top:14px;">
        <button onclick="closeNickModal()" style="flex:1;padding:10px;border:1.5px solid #d1d5db;background:transparent;border-radius:8px;font-size:14px;cursor:pointer;color:var(--text-secondary,#6b7280);">취소</button>
        <button id="nickSaveBtn" onclick="saveNickname()" style="flex:2;padding:10px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">저장</button>
      </div>
    </div>
  </div>

  <!-- 전화번호 등록 모달 (PWA 설치 완료 후) -->
  <div id="phoneModal">
    <div id="phoneModalBox">
      <div class="pm-title" id="pmTitle">📱 앱 설치 완료!</div>
      <div class="pm-sub" id="pmSub">휴대폰 번호를 등록하면 더 많은 기능을 이용할 수 있어요</div>
      <div class="pm-benefits">
        <div class="pm-benefit-item">
          <span class="pm-benefit-icon">💬</span>
          <div>
            <span class="pm-benefit-title">관리자와 직접 1:1 소통</span>
            <span class="pm-benefit-desc">채팅 외에 직접 연락이 가능하며 중요 알림을 받을 수 있어요</span>
          </div>
        </div>
        <div class="pm-benefit-item">
          <span class="pm-benefit-icon">🤖</span>
          <div>
            <span class="pm-benefit-title">AI 아바타 채팅 시스템 이용</span>
            <span class="pm-benefit-desc">맞춤형 AI가 24시간 응대하며 개인화된 서비스를 제공해요</span>
          </div>
        </div>
        <div class="pm-benefit-item">
          <span class="pm-benefit-icon">🗂️</span>
          <div>
            <span class="pm-benefit-title">대화 내용 영구 저장</span>
            <span class="pm-benefit-desc">나눈 대화를 안전하게 보관하고 언제든 다시 확인할 수 있어요</span>
          </div>
        </div>
        <div class="pm-benefit-item">
          <span class="pm-benefit-icon">📋</span>
          <div>
            <span class="pm-benefit-title">항목별 한눈에 이력 조회</span>
            <span class="pm-benefit-desc">대화 내용을 주제별·날짜별로 정리해 빠르게 찾아볼 수 있어요</span>
          </div>
        </div>
      </div>
      <div class="pm-input-wrap">
        <input type="text" id="pmName" placeholder="이름 (선택사항)" maxlength="20"
          style="margin-bottom:8px;width:100%;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        <input type="tel" id="pmPhone" placeholder="010-0000-0000" maxlength="13" inputmode="numeric" style="margin-bottom:8px;">
        <input type="text" id="pmRegion" placeholder="주소 (선택사항)" maxlength="100"
          style="width:100%;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;display:none;">
      </div>
      <button class="pm-register-btn" id="pmRegisterBtn" onclick="submitPhoneModal()">등록하기</button>
      <div class="pm-success-msg" id="pmSuccess">✅ 등록 완료! 이제 더 편리하게 이용하세요</div>
      <button class="pm-skip-btn" onclick="closePhoneModal()">나중에 할게요</button>
    </div>
  </div>

  <!-- Android Chrome 설치 가이드 -->
  <div id="androidGuideOverlay">
    <div id="androidGuideBox">
      <div class="guide-title">📲 홈화면에 설치하기</div>
      <div class="guide-sub">Chrome이 아직 설치 준비 중이에요<br>아래 방법으로 바로 설치할 수 있어요</div>
      <div class="guide-step">
        <div class="guide-step-num">1</div>
        <div class="guide-step-text">
          Chrome 주소창 오른쪽 <strong>⊕ 설치 아이콘</strong>을 탭하세요<br>
          <span style="font-size:12px;color:#6b7280">(아이콘이 없으면 ⋮ 메뉴 → "홈 화면에 추가")</span>
        </div>
      </div>
      <div class="guide-step">
        <div class="guide-step-num">2</div>
        <div class="guide-step-text">
          <strong>"설치"</strong>를 탭하면 홈화면에 아이콘이 생겨요 ✅
        </div>
      </div>
      <div class="guide-step" style="border-bottom:none">
        <div class="guide-step-num" style="background:#10b981">💡</div>
        <div class="guide-step-text">
          또는 <strong>페이지를 새로고침</strong>한 뒤 📲 버튼을 다시 눌러보세요.<br>
          <span style="font-size:12px;color:#6b7280">Chrome이 준비되면 바로 설치 팝업이 뜹니다</span>
        </div>
      </div>
      <button id="androidGuideClose" onclick="closeAndroidGuide()">확인했어요</button>
    </div>
  </div>

  <!-- 기타 브라우저 설치 가이드 -->
  <div id="otherBrowserGuide">
    <div id="otherBrowserGuideBox">
      <div class="guide-title">📲 홈 화면에 추가하기</div>
      <div class="guide-sub">브라우저 메뉴를 이용해 앱처럼 저장하세요</div>
      <div class="guide-step">
        <div class="guide-step-num">1</div>
        <div class="guide-step-text">
          브라우저 우측 상단 <strong>메뉴(⋮)</strong> 버튼을 탭하세요
        </div>
      </div>
      <div class="guide-step">
        <div class="guide-step-num">2</div>
        <div class="guide-step-text">
          <strong>"홈 화면에 추가"</strong> 또는 <strong>"바로가기 추가"</strong>를 선택하세요
        </div>
      </div>
      <div class="guide-step">
        <div class="guide-step-num">3</div>
        <div class="guide-step-text">
          확인을 탭하면 홈 화면에 아이콘이 생겨요 ✅
        </div>
      </div>
      <button id="otherBrowserGuideClose" onclick="closeOtherGuide()">확인했어요</button>
    </div>
  </div>
</div>



<!-- ─── 삼점 드롭다운 ─── -->
<div id="moreMenuDropdown">
  <button class="menu-item hidden" id="menuVideoCall" onclick="startVideoCall();closeMoreMenu()">📹 화상통화</button>
  <button class="menu-item hidden" id="menuVoiceCall" onclick="startVoiceCall();closeMoreMenu()">🎙️ 음성통화</button>
  <button class="menu-item hidden" id="menuPwaInstall" onclick="triggerPwaInstall();closeMoreMenu()">📲 앱 설치</button>
  <div class="menu-divider"></div>
  <button class="menu-item" id="menuThemeToggle" onclick="toggleThemeFromMenu()">🌙 다크/라이트 전환</button>
</div>
<!-- ─── 메시지 컨텍스트 메뉴 ─── -->
<div id="msgContextMenu">
  <button class="ctx-item" id="ctxCopy">📋 복사</button>
  <button class="ctx-item" id="ctxDelete">🗑️ 삭제</button>
  <button class="ctx-item" id="ctxReask">🔄 다시 질문</button>
</div>

<script>
const SMS_IDX     = <?= $sms_idx ?>;
const SHORT_CODE  = '<?= $short_code ?>';
const REQUEST_IDX  = <?= $request_idx ?>; // 수신자 개별 식별자 (0이면 비식별)
const VISITOR_PARAM = '<?= $visitor_param ?>'; // 공유 리스트 직접 링크용 (있으면 고정 ID)
const CHAT_API    = '/shared_visitor_chat.php';
// 로그인 회원 ID: 세션에서 읽어 JS로 전달 (비로그인='') 
const LOGGED_MEM_ID  = '<?= htmlspecialchars($_logged_mem_id, ENT_QUOTES, 'UTF-8') ?>';
const LOGGED_MEM_IDX = <?= (int)($_logged_mem_idx ?? 0) ?>;
const CHATBOT_OWNER  = '<?= htmlspecialchars($mem_code, ENT_QUOTES, 'UTF-8') ?>'; // 챗봇 소유자 회원 ID
// 목록에서 진입 여부 (from_list=1)
const FROM_LIST   = <?= $from_list ? 'true' : 'false' ?>;

// ─── 챗봇 이름 동적 재로드 (PWA/브라우저 캐시 우회) ─────────────────────────
// 서비스 워커 또는 브라우저 캐시에 이전 HTML이 남아있어도 최신 이름을 표시하기 위해
// 페이지 로드 직후 API로 실제 DB값을 조회하여 헤더·타이틀을 덮어씀
(function() {
  try {
    fetch('/aimessage/api/chatbot_info.php?code=' + encodeURIComponent(SHORT_CODE) + '&_t=' + Date.now(), {
      cache: 'no-store'
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
      if (!data.success || !data.chatbot_name) return;
      var name = data.chatbot_name;
      // 헤더 이름 업데이트
      var h = document.getElementById('headerName');
      if (h) h.textContent = name;
      // 빈 화면 타이틀 업데이트
      var e = document.querySelector('.empty-title');
      if (e) e.textContent = name;
      // 브라우저 탭 타이틀 업데이트
      document.title = name;
    })
    .catch(function(){});
  } catch(e) {}
})();

// ─── global_visitor_id: 기기 공통 ID (모든 챗봇에서 동일하게 사용) ────────
// 최초 생성 후 localStorage 'onechat_global_vid' 에 영구 저장
// 수신 탭 조회 시 이 ID 하나로 전체 구독 목록을 찾을 수 있음
function getOrCreateGlobalVid() {
  var gvid = localStorage.getItem('onechat_global_vid');
  if (!gvid) {
    gvid = Math.random().toString(36).substr(2, 8).toUpperCase();
    localStorage.setItem('onechat_global_vid', gvid);
  }
  return gvid;
}
const GLOBAL_VISITOR_ID = getOrCreateGlobalVid();

// ─── visitor_id 생성/로드 ───────────────────────────────
// A안(통일): global_visitor_id를 챗봇별 visitor_id로도 사용
// REQUEST_IDX 있으면 수신자 전용 키(_r접미사) 사용
// VISITOR_PARAM 있으면 직접 링크 고정
function getVisitorId() {
  if (VISITOR_PARAM) {
    return VISITOR_PARAM;
  }
  if (REQUEST_IDX > 0) {
    // 수신자 전용: 챗봇별 고유키 유지 (기존 호환)
    const key = 'vc_vid_' + SHORT_CODE + '_r' + REQUEST_IDX;
    let vid = localStorage.getItem(key);
    if (!vid) {
      vid = GLOBAL_VISITOR_ID; // global과 동일하게
      localStorage.setItem(key, vid);
    }
    return vid;
  }
  // 일반 방문: global_visitor_id 사용 (챗봇별 키에도 동기화)
  const key = 'vc_vid_' + SHORT_CODE;
  localStorage.setItem(key, GLOBAL_VISITOR_ID); // 항상 동기화
  return GLOBAL_VISITOR_ID;
}
const VISITOR_ID = getVisitorId();

// ─── 뒤로가기(목록) 버튼 초기화 ──────────────────────────
(function initBackToListBtn() {
  var btn = document.getElementById('backToListBtn');
  if (!btn) return;
  // from_list=1 이거나 URL 파라미터에 from_list=1 이 있으면 버튼 표시
  var params = new URLSearchParams(window.location.search);
  var show = FROM_LIST || params.get('from_list') === '1';
  if (show) {
    // 목록 링크에 vid 파라미터 포함
    btn.href = '/chatbot_list.html?vid=' + encodeURIComponent(GLOBAL_VISITOR_ID);
    btn.classList.add('show');
  }
})();

// ─── 닉네임 생성/로드 ──────────────────────────────────────
// 닉네임 형태: "맑은 구름", "빛나는 별" 등 (visitor_id 기반 결정론적 생성)
var NICK_ADJ  = ['맑은','따뜻한','시원한','투명한','포근한','달리는','흐르는','춤추는','빛나는',
                 '노래하는','반짝이는','조용한','용감한','즐거운','씩씩한','차분한','산뜻한',
                 '깨끗한','밝은','부드러운','가벼운','신나는','든든한','상쾌한','유쾌한'];
var NICK_NOUN = ['구름','강','나뭇잎','별','바람','달','햇살','파도','이슬','꽃','눈','안개',
                 '나비','노래','새벽','여름','가을','봄','하늘','들판','숲','강물','산','호수','바다'];
function _simpleHash(s) {
  var h = 0;
  for (var i = 0; i < s.length; i++) { h = (Math.imul(31, h) + s.charCodeAt(i)) | 0; }
  return Math.abs(h);
}
function generateNickname(vid) {
  var h = _simpleHash(vid || '');
  return NICK_ADJ[h % NICK_ADJ.length] + ' ' + NICK_NOUN[Math.floor(h / NICK_ADJ.length) % NICK_NOUN.length];
}
var VISITOR_NICKNAME_KEY = 'vc_nick_' + SHORT_CODE;
function getOrSetNickname() {
  // 1) localStorage에 저장된 닉네임 우선
  var saved = localStorage.getItem(VISITOR_NICKNAME_KEY);
  if (saved) return saved;
  // 2) VISITOR_ID가 영문/숫자 ID 그대로인 경우에도 반드시 한글 닉네임 생성
  //    (VISITOR_PARAM = ?v=XXXXXXXX 로 들어온 경우 포함)
  var nick = generateNickname(VISITOR_ID);
  localStorage.setItem(VISITOR_NICKNAME_KEY, nick);
  return nick;
}
let VISITOR_NICKNAME = getOrSetNickname();

// ─── DB에 저장된 닉네임이 있으면 그것을 우선 사용 ──────────────
// (다른 브라우저에서 이미 저장된 닉네임을 복원)
(function syncNicknameFromDB() {
  fetch(CHAT_API + '?mode=get_nickname&visitor_id=' + encodeURIComponent(VISITOR_ID) + '&sms_idx=' + SMS_IDX)
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.nickname) {
        // DB 닉네임이 현재와 다르면 덮어씀 (브라우저 통일)
        if (d.nickname !== VISITOR_NICKNAME) {
          VISITOR_NICKNAME = d.nickname;
          localStorage.setItem(VISITOR_NICKNAME_KEY, d.nickname);
          var el = document.getElementById('nickDisplay');
          if (el) el.textContent = d.nickname;
        }
        // 저장 완료 플래그 세팅
        localStorage.setItem('vc_nick_saved_' + SHORT_CODE, '1');
      }
    }).catch(function(){});
})();

// ─── 첫 방문 시 닉네임 DB 저장 (syncNicknameFromDB 에서 DB값 없을 때만 실행) ──
(function saveNicknameOnce() {
  // syncNicknameFromDB 가 vc_nick_saved_ 플래그를 세팅하므로,
  // 여기서는 0.8초 후 플래그가 없으면(DB에 닉네임 없음) 저장
  setTimeout(function() {
    var savedFlag = localStorage.getItem('vc_nick_saved_' + SHORT_CODE);
    if (savedFlag) return; // syncNicknameFromDB 에서 이미 처리됨
    fetch(CHAT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mode: 'save_nickname',
        visitor_id: VISITOR_ID,
        sms_idx: SMS_IDX,
        nickname: VISITOR_NICKNAME
      })
    }).then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) localStorage.setItem('vc_nick_saved_' + SHORT_CODE, '1');
      }).catch(function() {});
  }, 800);
})();

// ─── 구독 등록 + 리스트/채팅 분기 ──────────────────────────
// 페이지 로드 시 즉시 구독 등록 → 구독 수에 따라 리스트 or 채팅 결정
(function initSubscription() {
  const MY_CHATLIST_API = '/aimessage/api/my_chatlist.php';

  fetch(MY_CHATLIST_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      visitor_id:        VISITOR_ID,
      global_visitor_id: GLOBAL_VISITOR_ID,
      mem_id:            LOGGED_MEM_ID,  // 로그인 회원이면 mem_id 전달 (비로그인='')
      short_code:        SHORT_CODE,
      sms_idx:           SMS_IDX,
      chatbot_name:      document.getElementById('headerName') ? document.getElementById('headerName').textContent : '챗봇',
      operator_id:       ''  // my_chatlist.php 서버에서 short_code로 자동 보완
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.success) return;

    const params    = new URLSearchParams(window.location.search);
    const isFromList = FROM_LIST || params.get('from_list') === '1';

    // 로그인 회원이 다른 사람의 챗봇 링크를 방문 → 원챗 수신 탭으로 리다이렉트
    if (LOGGED_MEM_ID && CHATBOT_OWNER && LOGGED_MEM_ID !== CHATBOT_OWNER && !isFromList) {
      window.location.replace('/aimessage/onechat/#receive');
      return;
    }

    const count = data.subscription_count || 1;

    // 구독 2개 이상 + 리스트 진입이 아닌 경우 → 챗봇 리스트로 리다이렉트
    if (count >= 2 && !isFromList) {
      window.location.replace(
        '/chatbot_list.html?vid=' + encodeURIComponent(GLOBAL_VISITOR_ID)
      );
      return;
    }
  })
  .catch(function() {
    // 네트워크 오류 시 그냥 채팅 화면으로 진행
  });
})();

// ─── OC_ID 5단계 복구 시스템 ────────────────────────────
(function initOCSystem() {
  var OC_API_BASE = '/aimessage/api';

  // STEP 1: localStorage
  var ocId = localStorage.getItem('onechat_oc_id');
  if (ocId) { applyOCId(ocId); return; }

  // STEP 2: 쿠키
  var cookieOC = getCookieOC();
  if (cookieOC) {
    localStorage.setItem('onechat_oc_id', cookieOC);
    applyOCId(cookieOC);
    return;
  }

  // STEP 3: 핑거프린트 복구 (1.5초 후 — fingerprint.js 로드 대기)
  setTimeout(function() {
    if (typeof getOCFingerprint !== 'function') {
      scheduleOCSavePrompt();
      return;
    }
    var fp = getOCFingerprint();
    var recoverVid = (typeof GLOBAL_VISITOR_ID !== 'undefined' && GLOBAL_VISITOR_ID) ? GLOBAL_VISITOR_ID : '';
    fetch(OC_API_BASE + '/oc_recover.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        fingerprint: fp.fingerprint,
        user_agent: fp.user_agent,
        screen_info: fp.screen_info,
        visitor_id: recoverVid
      })
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (d.success && d.oc_id) {
        localStorage.setItem('onechat_oc_id', d.oc_id);
        setOCCookie(d.oc_id);
        applyOCId(d.oc_id);
      } else {
        scheduleOCSavePrompt();
      }
    })
    .catch(function() { scheduleOCSavePrompt(); });
  }, 1500);

  function getCookieOC() {
    var m = document.cookie.match(/(?:^|;\s*)ocid=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : null;
  }

  function setOCCookie(id) {
    document.cookie = 'ocid=' + encodeURIComponent(id) +
      '; max-age=31536000; path=/; SameSite=Lax';
  }

  function applyOCId(id) {
    window._oc_id = id;
    var sub = document.getElementById('headerSub');
    if (sub) {
      var badge = document.createElement('span');
      badge.style.cssText = 'font-size:9px;background:#22c55e;color:#fff;border-radius:6px;padding:1px 5px;margin-left:4px;';
      badge.textContent = '원챗회원';
      sub.appendChild(badge);
    }
  }

  var _ocPromptShown = false;
  function scheduleOCSavePrompt() {
    if (_ocPromptShown) return;
    var _botCount = 0;
    var _origAppend = window.appendMessage;
    if (typeof _origAppend !== 'function') {
      // appendMessage 아직 미정의 → 정의 후 후킹
      var _waitCount = 0;
      var _waitTimer = setInterval(function() {
        _waitCount++;
        if (typeof window.appendMessage === 'function' || _waitCount > 20) {
          clearInterval(_waitTimer);
          if (typeof window.appendMessage === 'function') hookAppend();
        }
      }, 200);
    } else {
      hookAppend();
    }

    function hookAppend() {
      var _orig = window.appendMessage;
      window.appendMessage = function(role, text, time, isHistory) {
        _orig.apply(this, arguments);
        if (!isHistory && role === 'bot') {
          _botCount++;
          if (_botCount === 3 && !_ocPromptShown) {
            _ocPromptShown = true;
            setTimeout(function(){ openPhoneModal('chat3'); }, 600);
          }
        }
      };
    }
  }

  window._ocSystem = {
    showSaveModal: function(){ openPhoneModal('chat3'); },
    setOCCookie: setOCCookie,
    applyOCId: applyOCId,
    API_BASE: OC_API_BASE
  };
})();

// header sub에 닉네임 표시
document.getElementById('nickDisplay').textContent = VISITOR_NICKNAME;

// ─── DOM refs ───────────────────────────────────────────
const messages    = document.getElementById('messages');
const emptyState  = document.getElementById('emptyState');
const typingEl    = document.getElementById('typingIndicator');
const msgInput    = document.getElementById('msgInput');
const sendBtn     = document.getElementById('sendBtn');
let msgCount      = 0;
let isWaiting     = false;

// ─── PWA (pwa-install-chatbot.js 에서 처리) ──────────────
const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
// beforeinstallprompt 발생 시에만 메뉴 항목 표시 (외부 JS와 동일 조건)
window.addEventListener('beforeinstallprompt', function() {
  if (!isPWA) {
    var _mp = document.getElementById('menuPwaInstall');
    if (_mp) _mp.classList.remove('hidden');
  }
});

// ─── 메시지 렌더 ────────────────────────────────────────
function appendMessage(role, text, time, isHistory) {
  if (emptyState) emptyState.style.display = 'none';

  const now = time || new Date().toLocaleTimeString('ko-KR', { hour:'2-digit', minute:'2-digit' });

  // 운영자 안내문: 카드 형태로 렌더링
  if (role === 'operator') {
    // 화상/음성통화 초대 카드
    if (text.indexOf('__VCALL__:') === 0) {
      var parts = text.split(':');
      var vcRoomUuid = parts[1] || '';
      var vcVid = parts[2] || (typeof GLOBAL_VISITOR_ID !== 'undefined' ? GLOBAL_VISITOR_ID : '');
      var vcMode = parts[3] || 'video';
      var vcIcon = vcMode === 'voice' ? '🎙️' : '📹';
      var vcLabel = vcMode === 'voice' ? '음성통화' : '화상통화';
      var vcUrl = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(vcRoomUuid)
                  + '&vid=' + encodeURIComponent(vcVid);
      playCallRingtone(vcMode);
      var vcCard = document.createElement('div');
      vcCard.className = 'operator-notice';
      vcCard.innerHTML =
        '<div class="operator-notice-inner">' +
          '<div class="operator-notice-header">' +
            '<span class="notice-icon">' + vcIcon + ' <span class="vc-ring-anim">📳</span></span>' +
            '<span class="notice-label">' + vcLabel + ' 초대</span>' +
            '<span class="notice-badge">CALL</span>' +
          '</div>' +
          '<div class="operator-notice-divider"></div>' +
          '<div class="operator-notice-body">운영자가 ' + vcLabel + '에 초대했습니다.</div>' +
          '<a href="' + escHtml(vcUrl) + '" target="_blank" onclick="stopCallRingtone()" style="display:block;margin:12px 0 4px;padding:10px 0;background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;text-align:center;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;">' + vcIcon + ' 참여하기</a>' +
          '<div class="operator-notice-time">' + now + '</div>' +
        '</div>';
      messages.insertBefore(vcCard, typingEl);
      messages.scrollTop = messages.scrollHeight;
      return;
    }
    const card = document.createElement('div');
    card.className = 'operator-notice';
    card.innerHTML =
      '<div class="operator-notice-inner">' +
        '<div class="operator-notice-header">' +
          '<span class="notice-icon">📢</span>' +
          '<span class="notice-label">운영자 안내문</span>' +
          '<span class="notice-badge">NOTICE</span>' +
        '</div>' +
        '<div class="operator-notice-divider"></div>' +
        '<div class="operator-notice-body">' + escHtml(text) + '</div>' +
        '<div class="operator-notice-time">' + now + '</div>' +
      '</div>';
    messages.insertBefore(card, typingEl);
    messages.scrollTop = messages.scrollHeight;
    return;
  }

  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap ' + role;

  const avatarText = role === 'bot' ? '🤖' : '😊';
  const avClass = role === 'bot' ? '' : ' user-av';

  // ── 예약 마커 처리 ──
  // [RS:need_info] 감지 → 예약 정보 입력 팝업 자동 표시
  if (role === 'bot') {
    var niMatch = text.match(/\[RS:need_info:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2}(?::\d{2})?)\]/);
    if (niMatch && !isHistory) {
      var niDate = niMatch[1], niTime = niMatch[2];
      setTimeout(function(){ openPhoneModal('reserve:' + niDate + ':' + niTime); }, 500);
    }
  }
  var displayText = role === 'bot' ? text.replace(/\[RS:[^\]]*\]/g, '').trim() : text;
  var confirmMatch = role === 'bot' ? displayText.match(/\[RESERVE_CONFIRM\](.*?)\[\/RESERVE_CONFIRM\]/s) : null;
  var bubbleHtml;
  if (confirmMatch) {
    displayText = displayText.replace(/\s*\[RESERVE_CONFIRM\].*?\[\/RESERVE_CONFIRM\]\s*/s, '').trim();
    var rcParts = confirmMatch[1].split('|');
    var rcNo = rcParts[0] || '', rcDate = rcParts[1] || '', rcTime = rcParts[2] || '';
    var rcName = rcParts[3] || '', rcPhone = rcParts[4] || '';
    bubbleHtml = (displayText ? escHtml(displayText) + '<br>' : '') +
      '<div style="background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.3);border-radius:10px;padding:10px 12px;margin-top:6px;box-sizing:border-box;width:100%">' +
        '<div style="display:flex;align-items:center;gap:5px;color:#34d399;font-size:12px;font-weight:700;margin-bottom:8px">✅ 예약 완료</div>' +
        (rcName ? '<div style="font-size:12px;color:#e2e8f0;margin-bottom:4px"><span style="color:#94a3b8;font-size:11px">예약자</span><br><span>' + escHtml(rcName) + (rcPhone ? ' · ' + escHtml(rcPhone) : '') + '</span></div>' : '') +
        '<div style="font-size:12px;color:#e2e8f0;margin-bottom:4px"><span style="color:#94a3b8;font-size:11px">날짜</span><br><span style="white-space:nowrap">' + escHtml(rcDate) + '</span></div>' +
        '<div style="font-size:12px;color:#e2e8f0;margin-bottom:4px"><span style="color:#94a3b8;font-size:11px">시간</span><br><span style="white-space:nowrap">' + escHtml(rcTime) + '</span></div>' +
        '<div style="font-size:12px;color:#e2e8f0"><span style="color:#94a3b8;font-size:11px">예약번호</span><br><strong style="color:#34d399;letter-spacing:1px;white-space:nowrap">' + escHtml(rcNo) + '</strong></div>' +
      '</div>' +
      '<div style="font-size:11px;color:#64748b;margin-top:6px">📩 예약이 확정되었습니다!</div>';
  } else {
    bubbleHtml = escHtml(displayText);
  }

  if (confirmMatch) {
    // 예약 완료 → 소프트회원 처리됨, pwa_phone_saved 자동 설정
    if (!localStorage.getItem('pwa_phone_saved')) {
      localStorage.setItem('pwa_phone_saved', '1');
      phoneSaved = true;
      // 저장 유도 배너/모달 닫기
      var regCard = document.getElementById('phoneRegCard');
      if (regCard) regCard.style.display = 'none';
      var pm = document.getElementById('phoneModal');
      if (pm) pm.classList.remove('open');
    }
    // 예약 확인 카드: wrapper 폭을 고정(280px)하고 bubble이 100% 채우게
    wrap.innerHTML =
      '<div class="msg-avatar' + avClass + '">' + avatarText + '</div>' +
      '<div style="width:min(280px,calc(100% - 38px));min-width:0;">' +
        '<div class="bubble" style="max-width:100%;width:100%;min-width:0;">' + bubbleHtml + '</div>' +
        '<div class="msg-time">' + now + '</div>' +
      '</div>';
  } else {
    wrap.innerHTML =
      '<div class="msg-avatar' + avClass + '">' + avatarText + '</div>' +
      '<div>' +
        '<div class="bubble">' + bubbleHtml + '</div>' +
        '<div class="msg-time">' + now + '</div>' +
      '</div>';
  }

  if (typeof attachLongPress === 'function') attachLongPress(wrap, role);
  messages.insertBefore(wrap, typingEl);
  messages.scrollTop = messages.scrollHeight;
  // bot 응답에서만 카운트 (이력 로드 제외)
  // user 메시지에서 카운트하면 PWA 카드가 봇 응답 앞에 끼어서 사라짐
  if (!isHistory && role === 'bot') {
    msgCount++;
    checkPwaNudge();
  }
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
          .replace(/"/g,'&quot;').replace(/\n/g,'<br>');
}

// ─── PWA 설치 카드 ───────────────────────────────────────────
// nudgeShown: localStorage에 저장해 새로고침해도 유지
let nudgeShown = JSON.parse(localStorage.getItem('pwa_nudge_shown') || '{}');
let phoneSaved = !!localStorage.getItem('pwa_phone_saved');

function checkPwaNudge() {
  if (isPWA) return;
  // localStorage 플래그도 체크 — 이미 PWA 설치된 사용자에겐 카드 안 띄움
  if (localStorage.getItem('chatbotPwaInstalled') === 'true') return;
  const triggers = { 3: 'T1', 10: 'T2', 20: 'T3' };
  const key = triggers[msgCount];
  if (!key || nudgeShown[key]) return;
  nudgeShown[key] = true;
  localStorage.setItem('pwa_nudge_shown', JSON.stringify(nudgeShown));

  const descs = {
    T1: '대화 내용을 안전하게 보관하려면<br>홈 화면에 앱 아이콘을 추가해 주세요.',
    T2: '지금까지 나눔 대화, 저장해 두시면<br>언제든 다시 이어갈 수 있어요.',
    T3: '대화가 많이 쌓였네요!<br>소중한 대화를 안전하게 보관하세요.'
  };

  // 전화번호 저장 여부에 따라 입력창 조건부 렌더링
  var phoneBlock = phoneSaved
    ? '<div class="pwa-card-phone"><span style="font-size:12px;color:#22c55e;">&#9989; 알림 등록 완료</span></div>'
    : '<div class="pwa-card-phone">' +
        '<label>&#128222; 연락처 (선택)</label>' +
        '<input type="tel" id="pwaPhone_' + key + '" placeholder="010-0000-0000" maxlength="13">' +
      '</div>';

  const card = document.createElement('div');
  card.className = 'pwa-card';
  card.id = 'pwaCard_' + key;
  card.innerHTML =
    '<div class="pwa-card-header">' +
      '<span class="pwa-card-icon">&#128241;</span>' +
      '<span class="pwa-card-title">앱 아이콘 저장하기</span>' +
    '</div>' +
    '<div class="pwa-card-body">' +
      descs[key] +
      phoneBlock +
    '</div>' +
    '<div class="pwa-card-actions">' +
      '<button class="pwa-card-install-btn" onclick="handlePwaInstall(\'' + key + '\')">' +
        '&#128242; 홈 화면에 저장하기' +
      '</button>' +
    '</div>';
  messages.insertBefore(card, typingEl);
  messages.scrollTop = messages.scrollHeight;

  // 전화번호 자동 포맷 (미저장 상태일 때만)
  if (!phoneSaved) {
    var phoneInput = document.getElementById('pwaPhone_' + key);
    if (phoneInput) {
      phoneInput.addEventListener('input', function() {
        var v = this.value.replace(/[^0-9]/g, '');
        if (v.length > 3 && v.length <= 7) v = v.slice(0,3) + '-' + v.slice(3);
        else if (v.length > 7) v = v.slice(0,3) + '-' + v.slice(3,7) + '-' + v.slice(7,11);
        this.value = v;
      });
    }
  }
}

function handlePwaInstall(key) {
  // 전화번호 미저장 + 입력창에 값 있으면 저장
  if (!phoneSaved) {
    var phoneInput = document.getElementById('pwaPhone_' + key);
    if (phoneInput && phoneInput.value.trim()) {
      var phone = phoneInput.value.trim();
      fetch(CHAT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mode: 'save_phone', visitor_id: VISITOR_ID, sms_idx: SMS_IDX, phone: phone })
      }).then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success) {
            phoneSaved = true;
            localStorage.setItem('pwa_phone_saved', '1');
            // 같은 페이지 내 다른 PWA 카드 입력창도 완료 처리
            document.querySelectorAll('.pwa-card-phone input').forEach(function(inp) {
              inp.disabled = true; inp.value = phone;
            });
          }
        }).catch(function() {});
    }
  }
  // PWA 설치
  triggerPwaInstall();
}

// triggerPwaInstall: pwa-install-chatbot.js 로드 후 덮어쓰기 됨
// 스크립트 로드 전 클릭 대비 더미 유지
function triggerPwaInstall() {
  if (window._chatbotPWA) {
    if (window._chatbotPWA.deferredPrompt) { window._chatbotPWA.install(); return; }
    if (window._chatbotPWA.isIOS()) { window._chatbotPWA.showIOSGuide(); return; }
  }
  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  if (isIOS) { document.getElementById('iosGuideOverlay') && document.getElementById('iosGuideOverlay').classList.add('open'); return; }
  if (/Android/i.test(navigator.userAgent)) {
    document.getElementById('androidGuideOverlay') && document.getElementById('androidGuideOverlay').classList.add('open');
    return;
  }
  document.getElementById('otherBrowserGuide') && document.getElementById('otherBrowserGuide').classList.add('open');
}

function _doPrompt() {
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then(function(result) {
    deferredPrompt = null;
    _resetInstallBtn();
    if (result.outcome === 'accepted') {
      var banner = document.getElementById('pwaInstallBanner');
      if (banner) banner.remove();
      (function(){var _mp=document.getElementById('menuPwaInstall');if(_mp)_mp.classList.add('hidden');}());
      setTimeout(function() { openPhoneModal('pwa'); }, 600);
    }
  });
}

var _waitTimer = null;
function _waitAndPrompt() {
  // 버튼 스피너 표시 (짧게)
  var btn = document.getElementById('pwaInstallBtn');
  btn.innerHTML = '<span class="pwa-spin">⏳</span>';
  btn.disabled = true;

  var elapsed = 0;
  _waitTimer = setInterval(function() {
    elapsed += 200;
    if (deferredPrompt) {
      clearInterval(_waitTimer);
      _waitTimer = null;
      _resetInstallBtn();
      _doPrompt();
    } else if (elapsed >= 1500) {
      // 1.5초 대기 후에도 없으면 → Android 전용 가이드 즉시 표시
      clearInterval(_waitTimer);
      _waitTimer = null;
      _resetInstallBtn();
      document.getElementById('androidGuideOverlay').classList.add('open');
    }
  }, 200);
}

function _resetInstallBtn() {
  var btn = document.getElementById('pwaInstallBtn');
  btn.innerHTML = '📲';
  btn.disabled = false;
}

// ─── 닉네임 모달 ────────────────────────────────────────
function openNickModal() {
  var modal = document.getElementById('nickModal');
  var input = document.getElementById('nickInput');
  if (!modal) return;
  input.value = VISITOR_NICKNAME;
  modal.style.display = 'flex';
  setTimeout(function() { input.focus(); input.select(); }, 100);
}
function closeNickModal() {
  var modal = document.getElementById('nickModal');
  if (modal) modal.style.display = 'none';
}
function saveNickname() {
  var input = document.getElementById('nickInput');
  var nick = input ? input.value.trim() : '';
  if (!nick || nick.length < 1) {
    input.style.borderColor = '#ef4444';
    input.focus();
    setTimeout(function() { input.style.borderColor = ''; }, 1200);
    return;
  }
  var btn = document.getElementById('nickSaveBtn');
  btn.disabled = true;
  btn.textContent = (window._phoneModalContext||'').startsWith('reserve:') ? '예약 확정 중...' : '저장 중...';
  fetch(CHAT_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      mode: 'update_nickname',
      visitor_id: VISITOR_ID,
      sms_idx: SMS_IDX,
      nickname: nick
    })
  }).then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        // 로컬 닉네임 업데이트
        localStorage.setItem(VISITOR_NICKNAME_KEY, nick);
        // VISITOR_NICKNAME은 const이므로 nickDisplay만 업데이트
        document.getElementById('nickDisplay').textContent = nick;
        closeNickModal();
      } else {
        alert('저장 중 오류가 발생했습니다. 다시 시도해 주세요.');
      }
      btn.disabled = false;
      btn.textContent = '저장';
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = '저장';
    });
}
// 모달 배경 클릭 시 닫기
document.getElementById('nickModal').addEventListener('click', function(e) {
  if (e.target === this) closeNickModal();
});
// Enter 키 저장
document.getElementById('nickInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') saveNickname();
  if (e.key === 'Escape') closeNickModal();
});

// ─── 전화번호 모달 (범용) ────────────────────────────────
// context: 'pwa'|'chat3'|'file'|'voice'|'video'
function openPhoneModal(context) {
  // pwa_phone_saved 체크는 아래 isReserve 분기에서 처리
  var cfgs = {
    'pwa':   { title: '📱 앱 설치 완료!',          sub: '휴대폰 번호를 등록하면 더 많은 기능을 이용할 수 있어요' },
    'chat3': { title: '💬 대화 내용을 저장할까요?', sub: '번호를 등록하면 어디서든 대화를 이어갈 수 있어요' },
    'file':  { title: '📎 파일 첨부 기능',           sub: '번호를 등록하면 파일 첨부 기능을 사용할 수 있어요' },
    'voice': { title: '🎙️ 음성통화 기능',            sub: '번호를 등록하면 음성통화 기능을 사용할 수 있어요' },
    'video': { title: '📹 화상통화 기능',             sub: '번호를 등록하면 화상통화 기능을 사용할 수 있어요' }
  };
  var ctx = context || 'pwa';
  var isReserve = ctx.startsWith('reserve:');

  // 이미 저장된 경우라도 예약 context는 팝업 오픈 (정보 수집 목적)
  if (!isReserve && localStorage.getItem('pwa_phone_saved')) {
    _executePhoneModalContext(ctx); return;
  }

  var cfg = cfgs[ctx] || cfgs['pwa'];
  if (isReserve) cfg = { title: '📅 예약 고객 정보 입력', sub: '예약을 확정하려면 고객 정보가 필요합니다' };

  var titleEl = document.getElementById('pmTitle');
  var subEl   = document.getElementById('pmSub');
  var benefits= document.querySelector('#phoneModalBox .pm-benefits');
  var skipBtn = document.querySelector('#phoneModalBox .pm-skip-btn');
  var regionEl= document.getElementById('pmRegion');
  if (titleEl) titleEl.textContent = cfg.title;
  if (subEl)   subEl.textContent   = cfg.sub;
  // 예약 context: 혜택 목록 숨기기, 주소 필드 표시, skip 텍스트 변경
  if (benefits) benefits.style.display = isReserve ? 'none' : '';
  if (regionEl) regionEl.style.display = isReserve ? '' : 'none';
  if (skipBtn)  skipBtn.textContent    = isReserve ? '예약 취소' : '나중에 할게요';
  window._phoneModalContext = ctx;
  var modal = document.getElementById('phoneModal');
  if (modal) modal.classList.add('open');
}

// 전화번호 등록 완료 후 대기 중인 기능 실행
// voice/video/file: fetch 콜백 내에서 window.open/fi.click은 팝업 차단기에 막히므로
// 토스트 안내 후 사용자가 버튼을 다시 누르게 유도 (pwa_phone_saved 설정 완료 상태)
function _executePhoneModalContext(context) {
  var msgs = { file: '📎 파일첨부 버튼을 다시 눌러주세요.', voice: '🎙️ 음성통화 버튼을 다시 눌러주세요.', video: '📹 화상통화 버튼을 다시 눌러주세요.' };
  if (msgs[context]) { _showPhoneToast(msgs[context]); }
}

function _showPhoneToast(msg) {
  var t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.78);color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;pointer-events:none;opacity:1;transition:opacity 0.4s;';
  document.body.appendChild(t);
  setTimeout(function() { t.style.opacity='0'; setTimeout(function(){ t.remove(); }, 400); }, 2600);
}

function closePhoneModal() {
  var modal = document.getElementById('phoneModal');
  if (modal) modal.classList.remove('open');
  // 버튼/성공 메시지 상태 초기화 (재오픈 대비)
  var btn = document.getElementById('pmRegisterBtn');
  var succ = document.getElementById('pmSuccess');
  if (btn) { btn.disabled = false; btn.textContent = '등록하기'; btn.style.display = ''; }
  if (succ) succ.style.display = 'none';
}

function submitPhoneModal() {
  var input = document.getElementById('pmPhone');
  var phone = input ? input.value.trim() : '';
  var nameEl = document.getElementById('pmName');
  var real_name = nameEl ? nameEl.value.trim() : '';
  if (!phone || phone.length < 10) {
    input.style.borderColor = '#ef4444';
    input.focus();
    setTimeout(function() { input.style.borderColor = ''; }, 1200);
    return;
  }
  var btn = document.getElementById('pmRegisterBtn');
  btn.disabled = true;
  btn.textContent = '저장 중...';

  var ctx = window._phoneModalContext || '';
  var isReserve = ctx.startsWith('reserve:');
  var regionEl = document.getElementById('pmRegion');
  var region   = regionEl ? regionEl.value.trim() : '';

  if (isReserve) {
    // ─── 예약 확정 API 호출 ───
    var rParts = ctx.split(':'); // reserve:YYYY-MM-DD:HH:MM:SS
    var rDate = rParts[1] || '';
    var rTime = rParts.slice(2).join(':') || ''; // HH:MM 또는 HH:MM:SS
    fetch(CHAT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mode: 'reserve_confirm',
        visitor_id: VISITOR_ID, sms_idx: SMS_IDX,
        slot_date: rDate, slot_time: rTime,
        customer_name: real_name, customer_phone: phone, customer_region: region
      })
    }).then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) {
          localStorage.setItem('pwa_phone_saved', '1');
          phoneSaved = true;
          closePhoneModal();
          // 채팅창에 예약 확정 카드 추가
          if (typeof addMessage === 'function' && d.confirm_text) {
            addMessage('bot', d.confirm_text, false);
          }
        } else {
          btn.disabled = false; btn.textContent = '예약 확정';
          alert(d.error || '예약 처리 중 오류가 발생했습니다.');
        }
      }).catch(function(){
        btn.disabled = false; btn.textContent = '예약 확정';
        alert('네트워크 오류가 발생했습니다.');
      });
  } else {
    // ─── 일반 전화번호 저장 ───
    fetch(CHAT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mode: 'save_phone',
        visitor_id: VISITOR_ID, sms_idx: SMS_IDX,
        phone: phone, real_name: real_name
      })
    }).then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          localStorage.setItem('pwa_phone_saved', '1');
          phoneSaved = true;
          btn.style.display = 'none';
          var succ = document.getElementById('pmSuccess');
          if (succ) succ.style.display = 'block';
          document.querySelectorAll('.pwa-card-phone input').forEach(function(inp) {
            inp.disabled = true; inp.value = phone;
          });
          setTimeout(function() {
            closePhoneModal();
            _executePhoneModalContext(ctx);
          }, 1500);
        } else {
          btn.disabled = false;
          btn.textContent = '등록하기';
          alert('저장 중 오류가 발생했습니다. 다시 시도해 주세요.');
        }
      }).catch(function() {
        btn.disabled = false;
        btn.textContent = '등록하기';
      });
  }
}

// 기타 브라우저 가이드 닫기
function closeOtherGuide() {
  document.getElementById('otherBrowserGuide').classList.remove('open');
  setTimeout(function() { openPhoneModal('pwa'); }, 300);
}

// Android 가이드 닫기
function closeAndroidGuide() {
  document.getElementById('androidGuideOverlay').classList.remove('open');
  setTimeout(function() { openPhoneModal('pwa'); }, 300);
}

// ─── MY챗봇 만들기 ──────────────────────────────────────
function goMyChatbot() {
  window.open('https://kiam.kr/admin/ai_message_settings.php', '_blank');
}

function closeIosGuide(e) {
  if (e && e.target !== document.getElementById('iosGuideOverlay')) return;
  document.getElementById('iosGuideOverlay').classList.remove('open');
  // 가이드 확인 후 전화번호 모달
  setTimeout(function() { openPhoneModal('pwa'); }, 300);
}

function installPwa() { triggerPwaInstall(); }

// pmPhone 자동 포맷
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var pmPhone = document.getElementById('pmPhone');
    if (pmPhone) {
      pmPhone.addEventListener('input', function() {
        var v = this.value.replace(/[^0-9]/g, '');
        if (v.length > 3 && v.length <= 7) v = v.slice(0,3) + '-' + v.slice(3);
        else if (v.length > 7) v = v.slice(0,3) + '-' + v.slice(3,7) + '-' + v.slice(7,11);
        this.value = v;
      });
    }
  });
})();

// ─── 채팅 이력 로드 (새로고침 시) ──────────────────────
let lastOperatorMsgId = 0;
let lastBotMsgId      = 0;   // AI 자발 메시지(예약 동행) 추적용
let _audioCtxCache    = null;

// 알림음: Web Audio API로 "띵동" 2음 생성 (외부 파일 의존 없음)
function playNotifySound() {
  try {
    if (!_audioCtxCache) {
      const AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return;
      _audioCtxCache = new AC();
    }
    if (_audioCtxCache.state === 'suspended') {
      _audioCtxCache.resume().catch(function(){});
    }
    const ctx = _audioCtxCache;
    const playNote = function(freq, start, dur) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.type = 'sine'; osc.frequency.value = freq;
      gain.gain.setValueAtTime(0, ctx.currentTime + start);
      gain.gain.linearRampToValueAtTime(0.15, ctx.currentTime + start + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur);
      osc.start(ctx.currentTime + start);
      osc.stop(ctx.currentTime + start + dur + 0.05);
    };
    playNote(880, 0, 0.18);
    playNote(1318, 0.12, 0.22);
  } catch(e) {}
}
// 사용자 첫 인터랙션 시 AudioContext 미리 활성화 (브라우저 정책 우회)
['click','touchstart','keydown'].forEach(function(ev){
  document.addEventListener(ev, function once(){
    try {
      if (!_audioCtxCache) {
        const AC = window.AudioContext || window.webkitAudioContext;
        if (AC) _audioCtxCache = new AC();
      }
      if (_audioCtxCache && _audioCtxCache.state === 'suspended') _audioCtxCache.resume();
    } catch(e) {}
    document.removeEventListener(ev, once);
  }, { once: true });
});
async function loadHistory() {
  try {
    const resp = await fetch(CHAT_API + '?mode=history&visitor_id=' + VISITOR_ID + '&sms_idx=' + SMS_IDX);
    const data = await resp.json();
    if (data.success && data.messages && data.messages.length > 0) {
      data.messages.forEach(function(m) {
        if (m.role === 'operator') {
          appendMessage('operator', m.message, m.time, true);
        } else {
          appendMessage(m.role === 'assistant' ? 'bot' : 'user', m.message, m.time, true);
        }
        if (m.id) {
          var _mid = parseInt(m.id, 10);
          lastOperatorMsgId = Math.max(lastOperatorMsgId, _mid);
          if (m.role === 'assistant') lastBotMsgId = Math.max(lastBotMsgId, _mid);
        }
      });
    } else if (data.greeting_message) {
      // 첫 방문: 활성 시나리오의 entry_message를 첫 인사로 표시
      var now = new Date();
      var hh = String(now.getHours()).padStart(2,'0');
      var mm = String(now.getMinutes()).padStart(2,'0');
      appendMessage('bot', data.greeting_message, hh + ':' + mm, true);
      fetchGreetingSuggestions();
    }
    if (data.last_id) lastOperatorMsgId = Math.max(lastOperatorMsgId, parseInt(data.last_id, 10));
  } catch(e) {}
}

async function fetchGreetingSuggestions() {
  var cacheKey = 'gs_' + SMS_IDX;
  var cached = sessionStorage.getItem(cacheKey);
  if (cached) {
    try { renderSuggestions(JSON.parse(cached)); } catch(e) {}
    return;
  }
  try {
    var resp = await fetch(CHAT_API + '?mode=greeting_suggest&sms_idx=' + SMS_IDX);
    var d = await resp.json();
    if (d.success && d.suggestions && d.suggestions.length > 0) {
      sessionStorage.setItem(cacheKey, JSON.stringify(d.suggestions));
      renderSuggestions(d.suggestions);
    }
  } catch(e) {}
}

// ─── 운영자 메시지 폴링 (4초) ────────────────────────────
async function pollOperatorMessages() {
  try {
    const resp = await fetch(CHAT_API + '?mode=poll&visitor_id=' + VISITOR_ID + '&sms_idx=' + SMS_IDX + '&after_id=' + lastOperatorMsgId);
    const data = await resp.json();
    if (data.success && data.messages && data.messages.length > 0) {
      data.messages.forEach(function(m) {
        appendMessage('operator', m.message, m.time);
        if (m.id) lastOperatorMsgId = Math.max(lastOperatorMsgId, parseInt(m.id, 10));
      });
    }
  } catch(e) {}
}

// ─── 메시지 전송 ────────────────────────────────────────
function removeSuggestions() {
  const el = document.getElementById('suggestion-chips');
  if (el) el.remove();
}

function renderSuggestions(qs) {
  if (!qs || qs.length === 0) return;
  removeSuggestions();
  const wrap = document.createElement('div');
  wrap.id = 'suggestion-chips';
  wrap.className = 'suggestion-chips';
  const DIRECT_LABELS = ['직접 입력하기', '직접입력', '직접 입력', '직접 작성', '기타'];
  qs.forEach(function(q, idx) {
    const btn = document.createElement('button');
    const isDirectInput = DIRECT_LABELS.some(function(l){ return q.trim() === l; })
                          || (idx === qs.length - 1 && qs.length === 4);
    btn.className = 'suggestion-chip' + (isDirectInput ? ' direct-input' : '');
    btn.textContent = q;
    if (isDirectInput) {
      // 직접 입력 버튼: 입력창 포커스만 (전송 안 함)
      btn.onclick = function() {
        removeSuggestions();
        msgInput.value = '';
        msgInput.focus();
      };
    } else {
      btn.onclick = function() {
        msgInput.value = q;
        msgInput.dispatchEvent(new Event('input'));
        removeSuggestions();
        sendMessage();
      };
    }
    wrap.appendChild(btn);
  });
  messages.insertBefore(wrap, typingEl);
  messages.scrollTop = messages.scrollHeight;
}

async function sendMessage() {
  const text = msgInput.value.trim();
  if (!text || isWaiting) return;

  removeSuggestions();
  isWaiting = true;
  sendBtn.disabled = true;
  msgInput.value = '';
  msgInput.style.height = '42px';
  msgInput.style.overflowY = 'hidden';
  msgInput.disabled = true;

  appendMessage('user', text);

  typingEl.classList.add('visible');
  messages.scrollTop = messages.scrollHeight;

  try {
    const resp = await fetch(CHAT_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_id: VISITOR_ID,
        sms_idx: SMS_IDX,
        message: text
      })
    });
    const data = await resp.json();
    typingEl.classList.remove('visible');

    if (data.success && data.response) {
      appendMessage('bot', data.response);
      if (data.bot_log_id) lastBotMsgId = Math.max(lastBotMsgId, parseInt(data.bot_log_id, 10));
      if (data.suggestions && data.suggestions.length > 0) {
        renderSuggestions(data.suggestions);
      }
    } else {
      appendMessage('bot', '죄송합니다. 잠시 후 다시 시도해 주세요.');
    }
  } catch(e) {
    typingEl.classList.remove('visible');
    appendMessage('bot', '연결에 문제가 발생했습니다. 잠시 후 다시 시도해 주세요.');
  }

  isWaiting = false;
  sendBtn.disabled = false;
  msgInput.disabled = false;
  msgInput.focus();
}

// ─── 이벤트 ─────────────────────────────────────────────
msgInput.addEventListener('input', function() {
  sendBtn.disabled = !this.value.trim();
  this.style.height = 'auto';
  var h = Math.min(this.scrollHeight, 140);
  this.style.height = h + 'px';
  this.style.overflowY = this.scrollHeight > 140 ? 'auto' : 'hidden';
});
msgInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
sendBtn.addEventListener('click', sendMessage);

// ─── 테마 토글 ──────────────────────────────────────────
var _curTheme = localStorage.getItem('vc_theme') || 'dark';
function _applyTheme(t) {
  if (t === 'light') document.documentElement.setAttribute('data-theme','light');
  else document.documentElement.removeAttribute('data-theme');
  _curTheme = t;
  localStorage.setItem('vc_theme', t);
  var mi = document.getElementById('menuThemeToggle');
  if (mi) mi.textContent = (t === 'light') ? '🌙 다크 모드로 전환' : '☀️ 라이트 모드로 전환';
}
_applyTheme(_curTheme);
function toggleThemeFromMenu() {
  _applyTheme(_curTheme === 'light' ? 'dark' : 'light');
  closeMoreMenu();
}

// ─── 삼점 메뉴 ──────────────────────────────────────────
function closeMoreMenu() {
  var dd = document.getElementById('moreMenuDropdown');
  if (dd) dd.classList.remove('open');
}
(function() {
  var btn = document.getElementById('moreMenuBtn');
  if (!btn) return;
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('moreMenuDropdown').classList.toggle('open');
  });
})();

// ─── 꾹 누르기 컨텍스트 메뉴 ─────────────────────────────
var _ctxTarget = null, _ctxRole = '', _holdTimer = null;
function attachLongPress(wrap, role) {
  wrap.addEventListener('touchstart', function(e) {
    var t = e.touches[0];
    _holdTimer = setTimeout(function() {
      openContextMenu(t.clientX, t.clientY, wrap, role);
    }, 500);
  }, { passive: true });
  wrap.addEventListener('touchmove', cancelHold, { passive: true });
  wrap.addEventListener('touchend', cancelHold);
  wrap.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    openContextMenu(e.clientX, e.clientY, wrap, role);
  });
}
function cancelHold() {
  if (_holdTimer) { clearTimeout(_holdTimer); _holdTimer = null; }
}
function openContextMenu(x, y, wrap, role) {
  cancelHold();
  _ctxTarget = wrap; _ctxRole = role;
  var menu = document.getElementById('msgContextMenu');
  document.getElementById('ctxDelete').style.display = (role === 'user') ? '' : 'none';
  document.getElementById('ctxReask').style.display  = (role === 'bot')  ? '' : 'none';
  menu.style.left = Math.min(x, window.innerWidth  - 165) + 'px';
  menu.style.top  = Math.min(y, window.innerHeight - 115) + 'px';
  menu.classList.add('open');
  if (navigator.vibrate) navigator.vibrate(30);
}
function closeContextMenu() {
  var menu = document.getElementById('msgContextMenu');
  if (menu) menu.classList.remove('open');
  _ctxTarget = null; _ctxRole = '';
}
document.addEventListener('click', function(e) {
  var dd = document.getElementById('moreMenuDropdown');
  var mBtn = document.getElementById('moreMenuBtn');
  if (dd && dd.classList.contains('open') && !dd.contains(e.target) && e.target !== mBtn)
    dd.classList.remove('open');
  var cm = document.getElementById('msgContextMenu');
  if (cm && cm.classList.contains('open') && !cm.contains(e.target))
    closeContextMenu();
});
document.getElementById('ctxCopy').addEventListener('click', function() {
  if (!_ctxTarget) return;
  var bubble = _ctxTarget.querySelector('.bubble');
  var txt = bubble ? bubble.innerText : '';
  if (navigator.clipboard) navigator.clipboard.writeText(txt).catch(function(){});
  else { var ta=document.createElement('textarea'); ta.value=txt;
         document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
  _showToast('복사되었습니다');
  closeContextMenu();
});
document.getElementById('ctxDelete').addEventListener('click', function() {
  if (!_ctxTarget) return;
  if (confirm('이 메시지를 삭제하시겠어요?')) {
    var el = _ctxTarget;
    el.style.transition = 'opacity .2s';
    el.style.opacity = '0';
    setTimeout(function() { el.remove(); }, 230);
  }
  closeContextMenu();
});
document.getElementById('ctxReask').addEventListener('click', function() {
  if (!_ctxTarget) return;
  var bubble = _ctxTarget.querySelector('.bubble');
  if (bubble && msgInput) {
    msgInput.value = bubble.innerText;
    msgInput.dispatchEvent(new Event('input'));
    msgInput.focus();
  }
  closeContextMenu();
});
function _showToast(msg) {
  var t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);' +
    'background:rgba(0,0,0,.75);color:#fff;padding:8px 18px;border-radius:20px;' +
    'font-size:13px;z-index:4000;pointer-events:none;white-space:nowrap;';
  document.body.appendChild(t);
  setTimeout(function(){ t.remove(); }, 2000);
}

// ─── 초기화 ─────────────────────────────────────────────
loadHistory();
setInterval(pollOperatorMessages, 4000);

// ─── AI 자발 메시지(예약 동행) 폴링 + 알림음 ──────────
async function pollBotMessages() {
  try {
    if (lastBotMsgId === 0) return;  // history 로드 전이면 스킵 (전체 다 받지 않도록)
    const resp = await fetch(CHAT_API + '?mode=poll_bot&visitor_id=' + VISITOR_ID + '&sms_idx=' + SMS_IDX + '&after_id=' + lastBotMsgId);
    const data = await resp.json();
    if (data.success && data.messages && data.messages.length > 0) {
      var hadNew = false;
      data.messages.forEach(function(m) {
        // 마커 제거된 깔끔한 텍스트로 표시 (appendMessage가 내부에서 처리)
        appendMessage('bot', m.message, m.time, false);
        if (m.id) lastBotMsgId = Math.max(lastBotMsgId, parseInt(m.id, 10));
        hadNew = true;
      });
      if (hadNew) {
        playNotifySound();
        // 페이지 미포커스면 페이지 제목으로도 알림
        if (document.hidden && !document.title.startsWith('🔔')) {
          document.title = '🔔 ' + document.title;
        }
      }
    }
  } catch(e) {}
}
setInterval(pollBotMessages, 4000);

// 페이지 포커스 복귀 시 제목 알림 제거
document.addEventListener('visibilitychange', function() {
  if (!document.hidden && document.title.startsWith('🔔 ')) {
    document.title = document.title.substring(2);
  }
});

document.addEventListener('DOMContentLoaded', function() {
  var styles = document.createElement('style');
  styles.textContent = '@keyframes iosSlideUp{from{transform:translateY(100%);}to{transform:translateY(0);}}';
  document.head.appendChild(styles);
});

/* ═══════════════════════════════════════════════════════
   마이크 입력 — visitor_entry.php
   SpeechRecognition 우선 / MediaRecorder fallback
   ═══════════════════════════════════════════════════════ */
(function() {
  var SpeechRec    = window.SpeechRecognition || window.webkitSpeechRecognition || null;
  var useSpeechAPI = !!SpeechRec;
  var isRecording   = false;
  var recognition   = null;
  var mediaRec      = null;
  var audioChunks   = [];
  var interimBuf    = '';
  var autoStopTimer = null; // 30초 자동 꺼짐 타이머

  var micBtnEl = document.getElementById('micBtn');
  var inputEl  = document.getElementById('msgInput');
  var sbEl     = document.getElementById('sendBtn');

  function setMicUI(on) {
    if (!micBtnEl) return;
    if (on) micBtnEl.classList.add('recording');
    else    micBtnEl.classList.remove('recording');
  }

  function commitInterim() {
    var buf = interimBuf.trim();
    if (!buf || !inputEl) return;
    inputEl.value = (inputEl.value ? inputEl.value + ' ' : '') + buf;
    inputEl.placeholder = '메시지를 입력하세요...';
    inputEl.dispatchEvent(new Event('input')); // sendBtn disabled 상태 갱신
    interimBuf = '';
  }

  /* ── SpeechRecognition ── */
  function startSpeechRecognition() {
    var isIos = /iPhone|iPad|iPod/.test(navigator.userAgent);
    var rec = new SpeechRec();
    rec.lang = 'ko-KR';
    rec.interimResults = true;
    rec.continuous = !isIos; // iOS만 false(continuous:true 불안정), Android/기타 true(딸깍·매번권한 방지)
    rec.maxAlternatives = 1;

    rec.onstart = function() { isRecording = true; recognition = rec; setMicUI(true); };

    rec.onresult = function(e) {
      // 발언 감지 → 10초 타이머 리셋
      clearTimeout(autoStopTimer);
      autoStopTimer = setTimeout(function() {
        if (isRecording) stopRecording();
      }, 10000);
      var interim = '', finalText = '';
      for (var i = e.resultIndex; i < e.results.length; i++) {
        if (e.results[i].isFinal) finalText += e.results[i][0].transcript;
        else interim += e.results[i][0].transcript;
      }
      if (inputEl) {
        if (interim) {
          interimBuf = interim;
          inputEl.placeholder = interim + '...';
        }
        if (finalText) {
          interimBuf = '';
          inputEl.value = (inputEl.value ? inputEl.value + ' ' : '') + finalText;
          inputEl.placeholder = '메시지를 입력하세요...';
          inputEl.dispatchEvent(new Event('input'));
        }
      }
    };

    rec.onerror = function(e) {
      isRecording = false; recognition = null; setMicUI(false); interimBuf = '';
      if (inputEl) inputEl.placeholder = '메시지를 입력하세요...';
      if (e.error === 'not-allowed') {
        var _ua = navigator.userAgent;
        var _wv = (/Android/.test(_ua) && /wv/.test(_ua))
               || (/iPhone|iPad/.test(_ua) && /AppleWebKit/.test(_ua) && !/Safari/.test(_ua));
        if (_wv) {
          alert('마이크 접근 권한이 필요합니다.\n앱 설정 > 권한에서 마이크를 허용해주세요.');
        } else {
          alert('마이크 접근 권한이 필요합니다.\n브라우저 주소창의 자물쇠 아이콘을 눌러 마이크를 허용해주세요.');
        }
      } else if (e.error !== 'no-speech' && e.error !== 'aborted') {
        console.warn('[mic] SpeechRecognition error:', e.error);
      }
    };

    rec.onend = function() {
      recognition = null;
      if (isIos && isRecording) {
        // iOS: continuous:false → 묵음 자동 종료 시 재시작
        startSpeechRecognition();
      } else if (!isRecording) {
        // 수동 중지(stopRecording) → UI 정리
        // Android continuous:true → onend는 stopRecording 시에만 발생하므로 이 분기가 처리
        setMicUI(false);
        interimBuf = '';
        if (inputEl) { inputEl.placeholder = '메시지를 입력하세요...'; if (inputEl.value.trim()) inputEl.focus(); }
      }
    };

    try { rec.start(); }
    catch(ex) { useSpeechAPI = false; startMediaRecorder(); }
  }

  /* ── MediaRecorder fallback ── */
  function startMediaRecorder() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('이 브라우저는 마이크를 지원하지 않습니다.\nChrome 또는 Edge를 사용해주세요.'); return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true })
    .then(function(stream) {
      audioChunks = [];
      mediaRec = new MediaRecorder(stream);
      mediaRec.ondataavailable = function(e) { if (e.data.size > 0) audioChunks.push(e.data); };
      mediaRec.onstop = function() {
        stream.getTracks().forEach(function(t) { t.stop(); });
        sendSTT();
      };
      mediaRec.start();
      isRecording = true; setMicUI(true);
    })
    .catch(function(err) { alert('마이크 접근 권한이 필요합니다.\n(' + err.message + ')'); });
  }

  function sendSTT() {
    if (!audioChunks.length) return;
    var blob = new Blob(audioChunks, { type: 'audio/webm' });
    var fd   = new FormData();
    fd.append('audio', blob, 'voice.webm');
    if (inputEl) { inputEl.placeholder = '변환 중...'; inputEl.disabled = true; }
    fetch('/aimessage/onechat/api/stt_proxy.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (inputEl) { inputEl.disabled = false; inputEl.placeholder = '메시지를 입력하세요...'; }
      if (data && data.text) {
        if (inputEl) {
          inputEl.value = (inputEl.value ? inputEl.value + ' ' : '') + data.text;
          inputEl.dispatchEvent(new Event('input'));
          inputEl.focus();
        }
      }
    })
    .catch(function() { if (inputEl) { inputEl.disabled = false; inputEl.placeholder = '메시지를 입력하세요...'; } });
  }

  function stopRecording() {
    clearTimeout(autoStopTimer);
    autoStopTimer = null;
    commitInterim();
    isRecording = false; setMicUI(false); // onend 이전에 false 설정 → 자동 재시작 방지
    if (useSpeechAPI && recognition) { try { recognition.stop(); } catch(e) {} }
    else if (mediaRec && mediaRec.state !== 'inactive') { mediaRec.stop(); }
  }

  /* ── 마이크 버튼 클릭 ── */
  var attachBtnEl = document.getElementById('attachBtn');
  if (attachBtnEl) {
    attachBtnEl.addEventListener('click', function() {
      if (LOGGED_MEM_IDX || localStorage.getItem('pwa_phone_saved')) {
        document.getElementById('hiddenFileInput').click();
      } else {
        openPhoneModal('file');
      }
    });
  }

  // ─── 파일 첨부 처리 (로그인 회원 + 소프트 회원) ───────────────────
  var hiddenFileInputEl = document.getElementById('hiddenFileInput');
  if (hiddenFileInputEl) {
    hiddenFileInputEl.addEventListener('change', function() {
      var file = this.files[0];
      if (!file) return;
      if (file.size > 10 * 1024 * 1024) { alert('파일 크기는 10MB 이하만 가능합니다.'); this.value=''; return; }
      var isImage = file.type.startsWith('image/');
      var fd = new FormData();
      fd.append('file', file);
      fd.append('visitor_id', VISITOR_ID || '');
      fd.append('sms_idx', SMS_IDX || '');
      if (isImage) {
        var reader = new FileReader();
        reader.onload = function(e) { appendFileMessage(file.name, e.target.result, 'image', null); };
        reader.readAsDataURL(file);
      }
      fetch('/aimessage/api/upload_chatfile.php', { method:'POST', body:fd, credentials:'include' })
        .then(function(r){ return r.json(); }).then(function(d){
          if (d.ok) { if (!isImage) appendFileMessage(d.name, d.url, d.file_type, d.size_str); }
          else { alert('파일 업로드 실패: ' + (d.msg||'')); }
        }).catch(function(){ if (!isImage) alert('파일 업로드 중 오류가 발생했습니다.'); });
      this.value = '';
    });
  }

  function appendFileMessage(name, url, type, sizeStr) {
    var wrap = document.createElement('div');
    wrap.className = 'msg-wrap user';
    var bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.style.cssText = 'background:var(--bubble-user);color:var(--bubble-user-text);border-bottom-right-radius:4px;';
    if (type === 'image') {
      var img = document.createElement('img');
      img.src = url; img.alt = name;
      img.style.cssText = 'max-width:200px;max-height:200px;border-radius:8px;display:block;cursor:pointer;';
      img.onclick = function(){ window.open(url,'_blank'); };
      bubble.appendChild(img);
    } else {
      var icon = {pdf:'📄',doc:'📝',docx:'📝',xls:'📊',xlsx:'📊',ppt:'📋',pptx:'📋',zip:'🗜️',txt:'📃'};
      var em = icon[type] || '📎';
      bubble.innerHTML = '<div style="display:flex;align-items:center;gap:6px;">' + em +
        '<span style="font-size:13px;word-break:break-all;">' + name + '</span>' +
        (sizeStr ? '<span style="font-size:11px;opacity:.6;">' + sizeStr + '</span>' : '') + '</div>' +
        (url ? '<a href="' + url + '" target="_blank" style="font-size:11px;color:#93c5fd;display:block;margin-top:6px;">다운로드</a>' : '');
    }
    wrap.appendChild(bubble);
    var msgList = document.getElementById('messages');
    var typing  = document.getElementById('typingIndicator');
    if (msgList && typing) msgList.insertBefore(wrap, typing);
    else if (msgList) msgList.appendChild(wrap);
    if (msgList) msgList.scrollTop = msgList.scrollHeight;
  }

  // ─── 통화 알림음 (Web Audio API + 모바일 자동재생 정책 우회) ─────────────
  var _callRingtoneTimer = null;
  var _callRingCount = 0;
  var _sharedAudioCtx = null;   // 페이지 전체에서 공유 — 사용자 터치 후 unlock

  // 사용자 첫 터치/클릭 시 AudioContext 미리 생성 및 unlock
  function _ensureAudioCtx() {
    if (!_sharedAudioCtx) {
      try {
        _sharedAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
      } catch(e) {}
    }
    if (_sharedAudioCtx && _sharedAudioCtx.state === 'suspended') {
      _sharedAudioCtx.resume().catch(function(){});
    }
    return _sharedAudioCtx;
  }

  // 첫 터치/클릭에서 AudioContext 사전 unlock (iOS/Android 자동재생 정책 대응)
  function _unlockAudio() {
    _ensureAudioCtx();
    document.removeEventListener('touchstart', _unlockAudio, true);
    document.removeEventListener('click',      _unlockAudio, true);
  }
  document.addEventListener('touchstart', _unlockAudio, true);
  document.addEventListener('click',      _unlockAudio, true);

  // 브라우저 알림 권한 요청 (초기 접속 시)
  (function() {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  })();

  function _showCallNotification(mode) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    var label = (mode === 'voice') ? '음성통화' : '화상통화';
    var body  = '운영자가 ' + label + '에 초대했습니다. 참여하기를 눌러주세요.';
    try {
      new Notification(label + ' 초대', {
        body: body,
        icon: '/favicon.ico',
        requireInteraction: true,
        vibrate: [600, 300, 600]
      });
    } catch(e) {}
  }

  function _doRing(ctx, freq1, freq2) {
    if (!_sharedAudioCtx || _callRingCount >= 10) { stopCallRingtone(); return; }
    _callRingCount++;
    var now = ctx.currentTime;
    function _tone(freq, vol) {
      var osc  = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.frequency.value = freq; osc.type = 'sine';
      gain.gain.setValueAtTime(0, now);
      gain.gain.linearRampToValueAtTime(vol, now + 0.05);
      gain.gain.setValueAtTime(vol, now + 0.75);
      gain.gain.linearRampToValueAtTime(0, now + 0.9);
      osc.start(now); osc.stop(now + 0.9);
    }
    _tone(freq1, 0.3);
    _tone(freq2, 0.15);
    _callRingtoneTimer = setTimeout(function() { _doRing(ctx, freq1, freq2); }, 1600);
  }

  function playCallRingtone(mode) {
    try {
      stopCallRingtone();
      var freq1 = (mode === 'voice') ? 480 : 440;
      var freq2 = (mode === 'voice') ? 620 : 550;
      _callRingCount = 0;

      var ctx = _ensureAudioCtx();
      if (!ctx) return;

      var _start = function() { _doRing(ctx, freq1, freq2); };

      if (ctx.state === 'suspended') {
        // Android Chrome: resume 후 재생
        ctx.resume().then(_start).catch(function(e) {
          console.log('[call] ctx resume failed', e);
        });
      } else {
        _start();
      }

      // 진동 (모바일)
      if (navigator.vibrate) navigator.vibrate([600, 300, 600, 300, 600, 300, 600]);

      // 브라우저 알림 (백그라운드 탭 대응)
      _showCallNotification(mode);

    } catch(e) { console.log('[call] ringtone error', e); }
  }

  function stopCallRingtone() {
    if (_callRingtoneTimer) { clearTimeout(_callRingtoneTimer); _callRingtoneTimer = null; }
    // AudioContext는 닫지 않고 유지 (다음 통화에서 재사용)
  }
  window.stopCallRingtone = stopCallRingtone;

  // ─── 화상통화 초대 배너 폴링 ──────────────────────────────────────────────
  var _vcInvite = null;
  var _vcBannerDismissed = false;
  var _vcLastRingUuid = null;

  function checkVcInvite() {
    if (!LOGGED_MEM_IDX) return;
    fetch('/admin/ajax/onechat_video_api.php?action=pending_invite&vid=' + encodeURIComponent(VISITOR_ID || ''), {
      credentials: 'include'
    }).then(function(r) { return r.json(); }).then(function(d) {
      if (!d.ok || !d.count) { hideVcBanner(); return; }
      var inv = d.invites[0];
      if (_vcBannerDismissed && _vcInvite && _vcInvite.room_uuid === inv.room_uuid) return;
      var isNew = !_vcInvite || _vcInvite.room_uuid !== inv.room_uuid;
      _vcInvite = inv;
      _vcBannerDismissed = false;
      document.getElementById('vcInviteText').textContent =
        (inv.host_name || '방장') + '님이 화상통화에 초대했습니다';
      var el = document.getElementById('vcInviteBanner');
      el.style.display = 'flex';
      if (isNew && _vcLastRingUuid !== inv.room_uuid) {
        _vcLastRingUuid = inv.room_uuid;
        playCallRingtone('video');
      }
    }).catch(function(){});
  }

  function hideVcBanner() {
    document.getElementById('vcInviteBanner').style.display = 'none';
  }

  function dismissVcBanner() {
    _vcBannerDismissed = true;
    stopCallRingtone();
    hideVcBanner();
  }

  // ─── 화상/음성 통화 메뉴 항목 (항상 표시, 클릭 시 로그인 체크) ─────────
  (function() {
    var _mv = document.getElementById('menuVideoCall');
    var _mvv = document.getElementById('menuVoiceCall');
    if (_mv) _mv.classList.remove('hidden');
    if (_mvv) _mvv.classList.remove('hidden');
  })();

  function startVideoCall() {
    if (!LOGGED_MEM_IDX && !localStorage.getItem('pwa_phone_saved')) {
      openPhoneModal('video');
      return;
    }
    var chatRoomId = '';
    try { chatRoomId = window._oc_id || localStorage.getItem('onechat_oc_id') || ''; } catch(e) {}
    var vid = (typeof GLOBAL_VISITOR_ID !== 'undefined' ? GLOBAL_VISITOR_ID : '') || '';
    var apiUrl = '/admin/ajax/onechat_video_api.php?action=create_room&chat_room_id=' + encodeURIComponent(chatRoomId);
    if (vid && !LOGGED_MEM_IDX) apiUrl += '&vid=' + encodeURIComponent(vid);
    fetch(apiUrl, { credentials: 'include' })
    .then(function(r) { return r.json(); }).then(function(d) {
      if (!d.ok) { alert('화상통화 방 생성 실패: ' + (d.msg || '')); return; }
      var url = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(d.room_uuid);
      if (chatRoomId) url += '&chat_room_id=' + encodeURIComponent(chatRoomId);
      if (vid && !LOGGED_MEM_IDX) url += '&vid=' + encodeURIComponent(vid);
      window.open(url, 'vc_room', 'width=900,height=700,menubar=no,toolbar=no,resizable=yes');
    }).catch(function(e) { alert('화상통화 연결 오류'); });
  }

  function startVoiceCall() {
    if (!LOGGED_MEM_IDX && !localStorage.getItem('pwa_phone_saved')) {
      openPhoneModal('voice');
      return;
    }
    var chatRoomId = '';
    try { chatRoomId = window._oc_id || localStorage.getItem('onechat_oc_id') || ''; } catch(e) {}
    var vid = (typeof GLOBAL_VISITOR_ID !== 'undefined' ? GLOBAL_VISITOR_ID : '') || '';
    var apiUrl = '/admin/ajax/onechat_video_api.php?action=create_room&chat_room_id=' + encodeURIComponent(chatRoomId);
    if (vid && !LOGGED_MEM_IDX) apiUrl += '&vid=' + encodeURIComponent(vid);
    fetch(apiUrl, { credentials: 'include' })
    .then(function(r) { return r.json(); }).then(function(d) {
      if (!d.ok) { alert('음성통화 방 생성 실패: ' + (d.msg || '')); return; }
      var url = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(d.room_uuid) + '&mode=voice';
      if (chatRoomId) url += '&chat_room_id=' + encodeURIComponent(chatRoomId);
      if (vid && !LOGGED_MEM_IDX) url += '&vid=' + encodeURIComponent(vid);
      window.open(url, 'vc_room', 'width=520,height=420,menubar=no,toolbar=no,resizable=yes');
    }).catch(function(e) { alert('음성통화 연결 오류'); });
  }


  window.startVideoCall = startVideoCall;
  window.startVoiceCall = startVoiceCall;
  window.joinVideoInvite = joinVideoInvite;
  function joinVideoInvite() {
    if (!_vcInvite) return;
    stopCallRingtone();
    var url = '/admin/onechat/video_room.php?room_uuid=' + encodeURIComponent(_vcInvite.room_uuid);
    if (_vcInvite.chat_room_id) url += '&chat_room_id=' + encodeURIComponent(_vcInvite.chat_room_id);
    window.open(url, 'vc_room', 'width=900,height=700,menubar=no,toolbar=no');
    hideVcBanner();
  }

  if (LOGGED_MEM_IDX) {
    setTimeout(function() { checkVcInvite(); setInterval(checkVcInvite, 10000); }, 2000);
  }

  // ── Web Push Service Worker 등록 (화상통화 백그라운드 알림) ────────
  (function() {
    if (!LOGGED_MEM_IDX) return;
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    var VAPID_PUBLIC = 'BC9GbBQP_7LbqRfe7rEWk3aROoYjgf16656abdbx3Cx18dw9exk-WoQeXBP5Evif6A-3qijFuBAE_4eaoWfGFRs';

    function _b64ToUint8(base64String) {
      var padding = '='.repeat((4 - base64String.length % 4) % 4);
      var b64 = (base64String + padding).replace(/-/g,'+').replace(/_/g,'/');
      var raw = atob(b64);
      var arr = new Uint8Array(raw.length);
      for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
      return arr;
    }

    function _saveSub(sub) {
      var j = sub.toJSON();
      fetch('/aimessage/api/vcall_push_subscribe.php', {
        method: 'POST', credentials: 'include',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action:'subscribe', endpoint: j.endpoint, keys: j.keys })
      }).catch(function(){});
    }

    navigator.serviceWorker.register('/vcall-sw.js', { scope: '/' })
      .then(function(reg) {
        return reg.pushManager.getSubscription().then(function(existing) {
          if (existing) { _saveSub(existing); return; }
          return Notification.requestPermission().then(function(perm) {
            if (perm !== 'granted') return;
            return reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: _b64ToUint8(VAPID_PUBLIC)
            }).then(_saveSub);
          });
        });
      }).catch(function(e) { console.log('[push] SW 등록 실패', e); });
  })();

  // ─── Visitor용 Web Push 구독 (예약 동행 알림용 — 알림음 절대 보장) ──
  (function() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    if (typeof VISITOR_ID === 'undefined' || typeof SMS_IDX === 'undefined' || !VISITOR_ID || !SMS_IDX) return;

    function _vpB64ToUint8(b64) {
      const pad = '='.repeat((4 - b64.length % 4) % 4);
      const s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
      const raw = atob(s);
      const out = new Uint8Array(raw.length);
      for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
      return out;
    }

    function _vpSaveSub(sub) {
      const j = sub.toJSON();
      fetch('/aimessage/api/visitor_push_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'subscribe',
          visitor_id: VISITOR_ID,
          sms_idx: SMS_IDX,
          subscription: {
            endpoint: j.endpoint,
            keys: { p256dh: j.keys.p256dh, auth: j.keys.auth }
          }
        })
      }).catch(function(){});
    }

    // 페이지 로드 5초 후 구독 시도 (vcall과 충돌 방지 + 사용자 인터랙션 대기)
    setTimeout(function() {
      navigator.serviceWorker.ready.then(function(reg) {
        return reg.pushManager.getSubscription().then(function(existing) {
          if (existing) { _vpSaveSub(existing); return; }
          if (Notification.permission === 'denied') return;
          // VAPID 공개키 가져오기
          return fetch('/aimessage/api/visitor_push_subscribe.php?action=vapid_key')
            .then(r => r.json())
            .then(function(d) {
              if (!d.success || !d.publicKey) return;
              // 권한이 default면 사용자에게 요청
              const askPerm = (Notification.permission === 'granted')
                ? Promise.resolve('granted')
                : Notification.requestPermission();
              return askPerm.then(function(perm) {
                if (perm !== 'granted') return;
                return reg.pushManager.subscribe({
                  userVisibleOnly: true,
                  applicationServerKey: _vpB64ToUint8(d.publicKey)
                }).then(_vpSaveSub);
              });
            });
        });
      }).catch(function(e) { console.log('[vpush] 구독 실패', e); });
    }, 5000);
  })();


  if (micBtnEl) {
    micBtnEl.addEventListener('click', function() {
      if (isRecording) {
        stopRecording();
      } else {
        clearTimeout(autoStopTimer);
        autoStopTimer = setTimeout(function() {
          if (isRecording) stopRecording();
        }, 10000); // 10초 무발언 시 자동 꺼짐
        if (useSpeechAPI) startSpeechRecognition(); else startMediaRecorder();
      }
    });
  }

  /* ── 전송 버튼: pointerdown에서 interim 확정, click에서 마이크 중지 ── */
  if (sbEl) {
    sbEl.addEventListener('pointerdown', function() { commitInterim(); });
    sbEl.addEventListener('click', function() { if (isRecording) stopRecording(); });
  }
})();
/* ── 모바일 키보드 대응 (카카오톡/크롬 WebView) ───────────────
   visualViewport.height < window.innerHeight*0.85 일 때만 키보드로
   판정하고 .app 높이를 축소 → 키보드 없는 정상 상태에서는 CSS의
   100dvh 그대로 유지 (inline style이 박혀 reflow 일으키지 않음)
   ─────────────────────────────────────────────────────────── */
(function() {
  var appEl = document.querySelector('.app');
  if (!appEl || !window.visualViewport) return;
  function syncHeight() {
    var vvh = window.visualViewport.height;
    if (vvh < window.innerHeight * 0.85) {
      // 키보드가 올라온 상태로 판단 → 보이는 영역으로 강제 축소
      appEl.style.height = vvh + 'px';
    } else {
      // 키보드 없음 → inline style 제거하고 CSS 100dvh로 복귀
      appEl.style.height = '';
    }
  }
  window.visualViewport.addEventListener('resize', syncHeight);
  // 초기 호출 제거: 페이지 로드 시점에는 CSS의 100dvh를 그대로 두고,
  // resize 이벤트가 발생할 때만 키보드 여부 판정 후 조정
})();

</script>
</body>
</html>
