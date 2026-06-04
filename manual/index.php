<?php
/**
 * kiam.kr 매뉴얼 메인 페이지 (동적 - 2026-05-03 아리아)
 * Gn_Manual_Menu.is_visible=1 인 메뉴만 카드로 표시
 */
include_once $_SERVER['DOCUMENT_ROOT']."/lib/rlatjd_fun.php";

// visible 메뉴 + description + 페이지 수 조회
$menus = array();
$mr = mysqli_query($self_con, "SELECT * FROM Gn_Manual_Menu WHERE is_visible=1 ORDER BY order_num ASC");
while ($m = mysqli_fetch_assoc($mr)) {
    $cnt = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page WHERE menu_id={$m['id']} AND is_visible=1"));
    $m['page_count'] = (int)$cnt['cnt'];
    $m['description_esc'] = htmlspecialchars($m['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $m['name_esc'] = htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8');
    $m['tag_esc'] = htmlspecialchars($m['tag'], ENT_QUOTES, 'UTF-8');
    $menus[] = $m;
}

// 자주 찾는 문서 (하드코딩 — 인기 페이지)
$popular_links = array(
    array('url' => '/manual/onechat/start.html', 'title' => '원챗 처음 시작하기', 'tag' => '원챗'),
    array('url' => '/manual/onechat/profile.html', 'title' => '내 프로필 & 챗봇 링크 설정', 'tag' => '원챗'),
    array('url' => '/manual/onechat/chat.html', 'title' => 'AI 아바타 ON/OFF 전환하기', 'tag' => '원챗'),
    array('url' => '/manual/onechat/share.html', 'title' => '챗봇 링크 공유하기', 'tag' => '원챗'),
    array('url' => '/manual/onechat/avatar.html', 'title' => 'AI 아바타 학습시키기', 'tag' => '원챗'),
    array('url' => '/manual/namecard/add.html', 'title' => '명함 등록하는 방법', 'tag' => '명함'),
    array('url' => '/manual/account/signup.html', 'title' => '회원가입 & 로그인', 'tag' => '계정'),
    array('url' => '/manual/voicegen/start.html', 'title' => 'VoiceGen AI 시작하기', 'tag' => '음성AI'),
    array('url' => '/manual/voicegen/api.html', 'title' => 'API 키 발급 & 연동', 'tag' => '음성AI'),
    array('url' => '/manual/voicegen/clone.html', 'title' => '음성 클로닝 사용법', 'tag' => '음성AI'),
    array('url' => '/manual/diver/', 'title' => 'DB 수집(온리원디버) 사용 가이드', 'tag' => '디버'),
    array('url' => '/manual/callback/', 'title' => 'IAM 콜백 사용 가이드', 'tag' => '콜백'),
    array('url' => '/manual/sharecallback/', 'title' => '공유콜백 설정하기', 'tag' => '공유콜백'),
);
?>
<!DOCTYPE html>
<html lang="ko" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>kiam.kr 매뉴얼</title>
<link rel="stylesheet" href="/manual/assets/style.css">
<style>
/* 홈 전용 */
.hero {
  background: linear-gradient(135deg,#1e3a5f 0%,#2563eb 60%,#7c3aed 100%);
  padding: 64px 40px; text-align:center; color:white;
}
.hero h1 { font-size:38px; font-weight:800; margin-bottom:12px; letter-spacing:-1px; }
.hero p  { font-size:17px; opacity:.85; max-width:480px; margin:0 auto 28px; }
.hero-search {
  max-width:440px; margin:0 auto; position:relative;
}
.hero-search input {
  width:100%; padding:13px 18px 13px 44px;
  border-radius:10px; border:1px solid rgba(255,255,255,.3);
  background:rgba(255,255,255,.15); color:white;
  font-size:15px; outline:none; backdrop-filter:blur(8px);
}
.hero-search input::placeholder { color:rgba(255,255,255,.6); }
.hero-search .si { position:absolute; left:15px; top:50%; transform:translateY(-50%); font-size:17px; opacity:.7; }

main { max-width:1060px; margin:0 auto; padding:48px 40px 80px; }
.sec-label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text3); margin-bottom:14px; }

.card-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:48px; }
.doc-card {
  background:var(--bg2); border:1px solid var(--border);
  border-radius:14px; padding:22px; text-decoration:none; display:block; transition:.2s;
}
.doc-card:hover { border-color:var(--brand); transform:translateY(-2px); box-shadow:0 6px 20px rgba(37,99,235,.1); }
.doc-card .ci { font-size:28px; margin-bottom:10px; display:block; }
.doc-card .ct { font-size:15px; font-weight:700; color:var(--text); margin-bottom:5px; }
.doc-card .cd { font-size:13px; color:var(--text2); line-height:1.6; }
.doc-card .cb { display:inline-block; margin-top:10px; background:var(--brand-light); color:var(--brand); border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; }

.two-col { display:grid; grid-template-columns:2fr 1fr; gap:32px; }
.pop-list { list-style:none; }
.pop-list li { border-bottom:1px solid var(--border); }
.pop-list li:last-child { border-bottom:none; }
.pop-list a { display:flex; align-items:center; justify-content:space-between; padding:13px 4px; text-decoration:none; color:var(--text); font-size:14px; transition:.15s; }
.pop-list a:hover { color:var(--brand); padding-left:8px; }
.pop-list .tag { font-size:11px; background:var(--bg3); color:var(--text2); border-radius:4px; padding:2px 8px; flex-shrink:0; }

.quickstart { background:var(--bg2); border:1px solid var(--border); border-radius:14px; padding:20px; }
.qs-step {
  display:flex; align-items:center; gap:10px;
  padding:11px 13px; background:var(--bg); border:1px solid var(--border);
  border-radius:10px; text-decoration:none; color:var(--text);
  font-size:13px; margin-bottom:8px; transition:.2s;
}
.qs-step:hover { border-color:var(--brand); }
.qs-step:last-child { margin-bottom:0; }
.qs-num { width:24px; height:24px; background:var(--brand); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }

@media(max-width:900px) { .card-grid{grid-template-columns:1fr 1fr;} .two-col{grid-template-columns:1fr;} }
@media(max-width:600px) { .hero{padding:40px 20px;} .hero h1{font-size:26px;} main{padding:32px 20px 60px;} .card-grid{grid-template-columns:1fr;} }
</style>
</head>
<body>

<!-- 헤더 -->
<header class="m-header">
  <button class="btn-hamburger" id="hamburgerBtn">☰</button>
  <a href="/manual/" class="logo">
    <div class="logo-box">K</div>
    <span class="logo-text">kiam.kr</span>
    <span class="logo-sub">매뉴얼</span>
  </a>
  <div class="search-wrap">
    <span class="si">🔍</span>
    <input type="text" placeholder="문서 검색..." class="m-search-input">
  </div>
  <div class="hright">
    <button class="btn-theme" id="themeBtn">🌙</button>
    <a href="https://kiam.kr" class="btn-site" target="_blank">kiam.kr →</a>
  </div>
</header>

<!-- 히어로 -->
<div class="hero" style="margin-top:60px;">
  <h1>📚 kiam.kr 매뉴얼</h1>
  <p>모든 기능을 쉽게 배울 수 있도록<br>단계별로 안내합니다</p>
  <div class="hero-search">
    <span class="si">🔍</span>
    <input type="text" placeholder="궁금한 기능을 검색해 보세요..." class="m-search-input">
  </div>
</div>

<!-- 메인 -->
<main>
  <div class="sec-label">📂 전체 매뉴얼</div>
  <div class="card-grid">
    <?php foreach ($menus as $m): ?>
    <a href="/manual/<?php echo $m['slug']; ?>/" class="doc-card">
      <span class="ci"><?php echo $m['icon']; ?></span>
      <div class="ct"><?php echo $m['name_esc']; ?></div>
      <div class="cd"><?php echo $m['description_esc'] ?: $m['name_esc'] . ' 매뉴얼입니다. (' . $m['page_count'] . '페이지)'; ?></div>
      <span class="cb"><?php echo $m['tag_esc']; ?></span>
    </a>
    <?php endforeach; ?>
    <!-- 정적 카드 (DB 등록 전까지 표시) -->
    <a href="/manual/diver/" class="doc-card">
      <span class="ci">🗂️</span>
      <div class="ct">DB 수집 (온리원디버)</div>
      <div class="cd">온라인 공개 데이터 수집 솔루션. 휴대폰·이메일·지역번호·지도 수집, 검색 가이드, 업데이터 상세 (7페이지)</div>
      <span class="cb">사용 가이드</span>
    </a>
    <a href="/manual/callback/" class="doc-card">
      <span class="ci">📞</span>
      <div class="ct">IAM 콜백</div>
      <div class="cd">부재중 전화 자동 문자 발송 시스템. 콜백 메시지 등록, 리스트 관리, 셀프폰/푸시 발송, AI 연동 (5페이지)</div>
      <span class="cb">사용 가이드</span>
    </a>
    <a href="/manual/sharecallback/" class="doc-card">
      <span class="ci">🔗</span>
      <div class="ct">공유콜백</div>
      <div class="cd">콜백 메시지를 다른 회원과 공유. 내정보 드롭다운 → 콜백등록관리 → SNS/문자/카톡 공유 (5페이지)</div>
      <span class="cb">사용 가이드</span>
    </a>
  </div>

  <div class="two-col">
    <div>
      <div class="sec-label">🔥 자주 찾는 문서</div>
      <ul class="pop-list">
        <?php foreach ($popular_links as $link): ?>
        <li><a href="<?php echo $link['url']; ?>"><?php echo htmlspecialchars($link['title']); ?> <span class="tag"><?php echo htmlspecialchars($link['tag']); ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <div class="sec-label">🚀 처음이신가요?</div>
      <div class="quickstart">
        <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:12px;">3단계로 시작하세요</div>
        <a href="/manual/account/signup.html" class="qs-step">
          <span class="qs-num">1</span> 회원가입 & 로그인
        </a>
        <a href="/manual/onechat/profile.html" class="qs-step">
          <span class="qs-num">2</span> 내 프로필 & 챗봇 링크 설정
        </a>
        <a href="/manual/onechat/start.html" class="qs-step">
          <span class="qs-num">3</span> 원챗으로 첫 대화 시작
        </a>
      </div>
    </div>
  </div>
</main>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:140;" onclick="closeSidebar()"></div>
<script src="/manual/assets/script.js"></script>
</body>
</html>