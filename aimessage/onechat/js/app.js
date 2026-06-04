/* ============================================================
   원챗(OneChat) x 청담한의원 — 고객사 브리핑 목업 JS
   ============================================================ */
'use strict';

var currentScreen = 0;
var totalScreens = 9;
var selectedRegion = null;
var selectedSlot = null;
var diagStep = 0;

function uid(id) { return document.getElementById(id); }

/* ---------- 화면 전환 ---------- */
function showScreen(n) {
  var screens = document.querySelectorAll('.screen');
  for (var i = 0; i < screens.length; i++) { screens[i].classList.remove('on'); }
  var target = uid('screen' + n);
  if (target) target.classList.add('on');
  currentScreen = n;
  var ind = uid('stepIndicator');
  if (ind) ind.textContent = (n + 1) + ' / ' + totalScreens;
  updateTopbar();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (n === 3) setTimeout(populateSlots, 100);
  if (n === 5) { setTimeout(buildFunnel, 100); setTimeout(buildTrust, 100); }
  if (n === 7) setTimeout(buildNoShow, 100);
}

function goTo(n) { showScreen(n); }

function nextScreen() {
  if (currentScreen < totalScreens - 1) showScreen(currentScreen + 1);
}

function prevScreen() {
  if (currentScreen > 0) showScreen(currentScreen - 1);
}

function updateTopbar() {
  var steps = document.querySelectorAll('.topbar-step');
  for (var i = 0; i < steps.length; i++) {
    var s = steps[i];
    s.classList.remove('active', 'done');
    if (i < currentScreen) s.classList.add('done');
    if (i === currentScreen) s.classList.add('active');
  }
}

/* ---------- 키보드 ---------- */
document.addEventListener('keydown', function(e) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); nextScreen(); }
  else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); prevScreen(); }
});

document.addEventListener('click', function(e) {
  var step = e.target.closest('.topbar-step');
  if (step) {
    var n = parseInt(step.getAttribute('data-step'), 10);
    if (!isNaN(n)) showScreen(n);
  }
});

/* ---------- 신체 부위 선택 ---------- */
document.addEventListener('click', function(e) {
  var region = e.target.closest('.body-region');
  if (!region) return;
  var all = document.querySelectorAll('.body-region');
  for (var i = 0; i < all.length; i++) {
    all[i].classList.remove('active');
    all[i].setAttribute('fill', '#e8f5e9');
  }
  region.classList.add('active');
  region.setAttribute('fill', '#66bb6a');
  selectedRegion = {
    name: region.getAttribute('data-region'),
    code: region.getAttribute('data-code')
  };
  var area = uid('selectedArea');
  var bubble = uid('regionBubble');
  if (bubble) { bubble.textContent = selectedRegion.name + '이(가) 아파요'; }
  if (area) area.style.display = 'block';
  diagStep = 1;
  runDiagnosis();
  scrollDiag();
});

/* ---------- 진단 문진 ---------- */
function runDiagnosis() {
  var follow = uid('diagFollowUp');
  var btns = uid('diagButtons');
  if (diagStep === 1) {
    if (follow) {
      follow.style.display = 'block';
      follow.innerHTML = '<div class="bubble ai"><strong>' + selectedRegion.name + '</strong> 통증이 있으시군요.<br>어떤 종류의 통증인가요?</div>';
    }
    if (btns) btns.innerHTML =
      '<button class="choice-btn" onclick="answerDiag(\'찌릿한 통증\')">찌릿한 통증</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'뻐근한 통증\')">뻐근한 통증</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'욱신거리는 통증\')">욱신거리는 통증</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'타는 듯한 통증\')">타는 듯한 통증</button>';
  } else if (diagStep === 2) {
    if (btns) btns.innerHTML =
      '<button class="choice-btn" onclick="answerDiag(\'1주 미만\')">1주 미만</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'1주~1개월\')">1주~1개월</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'1~3개월\')">1~3개월</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'3개월 이상\')">3개월 이상</button>';
  } else if (diagStep === 3) {
    if (btns) btns.innerHTML =
      '<button class="choice-btn" onclick="answerDiag(\'앉아 있을 때\')">앉아 있을 때</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'움직일 때\')">움직일 때</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'아침에 일어날 때\')">아침에 일어날 때</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'밤에 잘 때\')">밤에 잘 때</button>';
  } else if (diagStep === 4) {
    if (btns) btns.innerHTML =
      '<button class="choice-btn" onclick="answerDiag(\'하루 종일 앉아서 일함\')">하루 종일 앉아서 일함</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'운동 부족\')">운동 부족</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'스트레스 많음\')">스트레스 많음</button>' +
      '<button class="choice-btn" onclick="answerDiag(\'수면 부족\')">수면 부족</button>';
  } else if (diagStep === 5) {
    if (follow) follow.innerHTML +=
      '<div class="bubble ai"><strong>진단이 완료되었습니다!</strong><br>총 5개 항목의 증상 데이터를 수집했습니다.<br><br>수집된 정보를 바탕으로 청담한의원에서 도움이 될 만한 <strong>맞춤 콘텐츠</strong>를 보내드릴게요</div>';
    if (btns) btns.innerHTML =
      '<button class="choice-btn" style="background:var(--green-50);border-color:var(--green-300);color:var(--green-700);" onclick="goTo(2)">맞춤 콘텐츠 보러가기</button>';
  }
}

function answerDiag(answer) {
  var follow = uid('diagFollowUp');
  if (follow) follow.innerHTML += '<div class="bubble user">' + answer + '</div>';
  var nextQ = ['', '', '통증이 얼마나 오래 지속되셨나요?', '어떤 상황에서 통증이 심해지나요?', '생활 습관 중 해당되는 것을 골라주세요.', ''];
  if (diagStep >= 2 && diagStep <= 4 && follow) {
    follow.innerHTML += '<div class="bubble ai">' + nextQ[diagStep] + '</div>';
  }
  diagStep++;
  runDiagnosis();
  scrollDiag();
}

function scrollDiag() {
  var body = document.querySelector('#screen1 .chat-body');
  if (body) { setTimeout(function() { body.scrollTop = body.scrollHeight; }, 100); }
}

/* ---------- 예약 슬롯 ---------- */
function populateSlots() {
  var container = uid('bookingSlots');
  if (!container) return;
  var slots = ['10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];
  var html = '';
  for (var i = 0; i < slots.length; i++) {
    html += '<div class="booking-slot" data-slot="' + slots[i] + '">' + slots[i] + '</div>';
  }
  container.innerHTML = html;
}

document.addEventListener('click', function(e) {
  var slot = e.target.closest('.booking-slot');
  if (!slot) return;
  var all = document.querySelectorAll('.booking-slot');
  for (var i = 0; i < all.length; i++) { all[i].classList.remove('selected'); }
  slot.classList.add('selected');
  selectedSlot = slot.getAttribute('data-slot');
  var btn = uid('bookingConfirmBtn');
  if (btn) btn.style.display = 'block';
});

document.addEventListener('click', function(e) {
  var btn = e.target.closest('#bookingConfirmBtn');
  if (!btn) return;
  if (!selectedSlot) return;
  var result = uid('bookingResult');
  var booked = uid('bookedSlot');
  if (result) result.style.display = 'block';
  if (booked) booked.textContent = selectedSlot + '시';
  btn.style.display = 'none';
  var slots = document.querySelectorAll('.booking-slot');
  for (var i = 0; i < slots.length; i++) {
    slots[i].style.pointerEvents = 'none';
    slots[i].style.opacity = '0.6';
  }
});

/* ---------- 퍼널 바 ---------- */
function buildFunnel() {
  var c = uid('funnelContainer');
  if (!c) return;
  var d = [
    {l:'인스타 노출',w:100,cls:'c1',v:'8,000'},
    {l:'챗봇 진입',w:42,cls:'c2',v:'336'},
    {l:'진단 완료',w:27,cls:'c3',v:'218'},
    {l:'고관심 분류',w:12,cls:'c4',v:'98'},
    {l:'예약 완료',w:5,cls:'c5',v:'40'}
  ];
  var h='';
  for(var i=0;i<d.length;i++) {
    var x=d[i];
    h+='<div class="funnel-bar"><span class="bar-label">'+x.l+'</span><div class="bar-track"><div class="bar-fill '+x.cls+'" style="width:'+x.w+'%">'+x.v+'</div></div></div>';
  }
  c.innerHTML=h;
}

/* ---------- 신뢰 점수 ---------- */
function buildTrust() {
  var c=uid('trustList');
  if(!c)return;
  var d=[
    {n:'이지은',m:'허리, 진단 완료, 콘텐츠 2회',s:'82%',cls:'high',e:'👩'},
    {n:'김민수',m:'목, 진단 완료, 콘텐츠 1회',s:'58%',cls:'mid',e:'👨'},
    {n:'박서연',m:'어깨, 진단 중도 이탈',s:'22%',cls:'low',e:'👩'}
  ];
  var h='';
  for(var i=0;i<d.length;i++){
    var x=d[i];
    h+='<div class="trust-item"><div class="trust-avatar">'+x.e+'</div><div class="trust-info"><div class="trust-name">'+x.n+'</div><div class="trust-meta">'+x.m+'</div></div><div class="trust-bar"><div class="trust-fill '+x.cls+'" style="width:'+x.s+'"></div></div><div class="trust-score '+x.cls+'">'+x.s+'</div></div>';
  }
  c.innerHTML=h;
}

/* ---------- 노쇼 위험 ---------- */
function buildNoShow() {
  var c=uid('noshowList');
  if(!c)return;
  var d=[
    {n:'최재훈',m:'허리, 예약 D-2, 과거 노쇼 2회',s:'78%',cls:'high',e:'👨'},
    {n:'송미영',m:'목, 예약 D-5, 첫 방문, 거리 15km',s:'45%',cls:'mid',e:'👩'}
  ];
  var h='';
  for(var i=0;i<d.length;i++){
    var x=d[i];
    h+='<div class="trust-item"><div class="trust-avatar">'+x.e+'</div><div class="trust-info"><div class="trust-name">'+x.n+'</div><div class="trust-meta">'+x.m+'</div></div><div class="trust-bar"><div class="trust-fill '+x.cls+'" style="width:'+x.s+'"></div></div><div class="trust-score '+x.cls+'">'+x.s+'</div></div>';
  }
  c.innerHTML=h;
}

/* ---------- 초기화 ---------- */
showScreen(0);