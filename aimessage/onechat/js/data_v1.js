// 챗봇 대화 리스트 데이터
const chatData = [
  {
    id: 1,
    name: "김판수",
    position: "CEO",
    phone: "01074432565",
    regDate: "2026-03-18",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/AcquGK",
    colorClass: "color-1",
    pwaInstalled: false,
    messages: []
  },
  {
    id: 2,
    name: "유정복시장",
    position: "인천시장",
    phone: "0104111879",
    regDate: "2026-03-15",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/fWvnXg",
    colorClass: "color-2",
    pwaInstalled: true,
    messages: []
  },
  {
    id: 3,
    name: "노강선",
    position: "대표이사",
    phone: "01088148819",
    regDate: "2026-03-13",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/B62BeP",
    colorClass: "color-3",
    pwaInstalled: false,
    messages: []
  },
  {
    id: 4,
    name: "이종원",
    position: "이사장",
    phone: "01073354929",
    regDate: "2026-03-13",
    chatCount: 2,
    unreadCount: 1,
    lastChat: "2026-03-13 20:26",
    shortUrl: "/s/CsTn3L",
    colorClass: "color-4",
    pwaInstalled: true,
    messages: [
      { type: "user", text: "안녕하세요", time: "2026-03-13 20:24", read: true },
      { type: "bot", text: "안녕하세요! 이종원 이사장님, 만나 뵙게 되어 반갑습니다.\n혹시 궁금한 점이나 도움이 필요하시면 언제든 말씀해 주세요. 진심을 담아 도와드리겠습니다.", time: null, read: true },
      { type: "user", text: "챗봇 기능이 어떻게 되나요?", time: "2026-03-13 20:26", read: false },
      { type: "bot", text: "저희 챗봇은 AI 기반의 퍼널 메시징 시스템을 통해 명함 정보만으로도 효과적인 커뮤니케이션을 자동화할 수 있도록 돕고 있습니다.\n\n각 수신자에게 맞춤형 감성 메시지를 주기적으로 생성하고 발송하여 지속적인 관계를 유지할 수 있습니다.", time: null, read: true }
    ]
  },
  {
    id: 5,
    name: "서지열",
    position: "C.E.O",
    phone: "01044498072",
    regDate: "2026-03-12",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/3QKgDj",
    colorClass: "color-5",
    pwaInstalled: false,
    messages: []
  },
  {
    id: 6,
    name: "김도환",
    position: "단장/자문기관",
    phone: "01041637088",
    regDate: "2026-03-01",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/rtRpxp",
    colorClass: "color-6",
    pwaInstalled: false,
    messages: []
  },
  {
    id: 7,
    name: "양기대",
    position: "민주당 남북고속철도추진 특별위원장",
    phone: "01055065389",
    regDate: "2026-02-27",
    chatCount: 3,
    unreadCount: 2,
    lastChat: "2026-03-22 21:32",
    shortUrl: "/s/thGfpY",
    colorClass: "color-7",
    pwaInstalled: true,
    promptConfig: {
      mode: "merged",
      tone: "formal",
      relation: "political",
      purpose: "introduce",
      custom: "위원장님은 남북고속철도 및 선거 솔루션에 관심이 많으십니다. 관련 내용을 자연스럽게 언급해주세요."
    },
    messages: [
      { type: "user", text: "안녕하세요", time: "2026-03-22 21:29", read: true },
      { type: "bot", text: "안녕하세요! 양기대 위원장님, 만나 뵙게 되어 반갑습니다.\n혹시 궁금한 점이나 도움이 필요하시면 언제든 말씀해 주세요.", time: null, read: true },
      { type: "user", text: "여기 선거 솔루션은 어떻게 사용하는 거지?", time: "2026-03-22 21:31", read: false },
      { type: "bot", text: "좋은 질문 감사합니다, 위원장님.\n\n저희 선거 솔루션은 AI 기반의 퍼널 메시징 시스템을 통해, 명함 정보만으로도 효과적인 커뮤니케이션을 자동화할 수 있도록 돕고 있습니다.\n\n이 시스템은 위원장님의 명함 정보를 기반으로, 각 수신자에게 맞춤형 감성 메시지를 주기적으로 생성하고 발송합니다.", time: null, read: true },
      { type: "user", text: "더 자세히 설명해줘", time: "2026-03-22 21:32", read: false },
      { type: "bot", text: "물론입니다! 더 궁금한 점이 있으시거나 직접 체험해보고 싶으시면, 언제든지 말씀해 주세요. 추가적인 안내를 드리겠습니다!", time: null, read: true }
    ]
  },
  {
    id: 8,
    name: "김상준",
    position: "광고학박사",
    phone: "01096999950",
    regDate: "2026-02-27",
    chatCount: 3,
    unreadCount: 0,
    lastChat: "2026-02-27 18:12",
    shortUrl: "/s/TcLH54",
    colorClass: "color-8",
    pwaInstalled: false,
    messages: [
      { type: "user", text: "안녕하세요", time: "2026-02-27 18:09", read: true },
      { type: "bot", text: "안녕하세요! 김상준 박사님, 만나 뵙게 되어 반갑습니다.\n광고 분야에서 다양한 경험을 쌓고 계신 박사님을 도와드릴 수 있어 영광입니다.", time: null, read: true },
      { type: "user", text: "광고 캠페인 효과 분석이 가능한가요?", time: "2026-02-27 18:10", read: true },
      { type: "bot", text: "네, 가능합니다! AI 기반 분석 도구를 활용하여 캠페인 데이터를 분석하고 인사이트를 제공해드릴 수 있습니다.", time: null, read: true },
      { type: "user", text: "자세한 방법을 알고 싶어요", time: "2026-02-27 18:12", read: true },
      { type: "bot", text: "분석을 원하시는 캠페인 데이터를 공유해주시면 바로 분석을 시작해드리겠습니다!\n언제든지 편하게 연락 주세요.", time: null, read: true }
    ]
  },
  {
    id: 9,
    name: "김문준",
    position: "지점장",
    phone: "01048097952",
    regDate: "2026-02-25",
    chatCount: 2,
    unreadCount: 1,
    lastChat: "2026-02-25 18:25",
    shortUrl: "/s/rgUA59",
    colorClass: "color-1",
    pwaInstalled: false,
    messages: [
      { type: "user", text: "안녕하세요", time: "2026-02-25 18:20", read: true },
      { type: "bot", text: "안녕하세요! 김문준 지점장님, 반갑습니다.\n지점 운영에 도움이 필요하신 부분이 있으시면 말씀해 주세요.", time: null, read: true },
      { type: "user", text: "고객 관리 시스템을 소개해줘", time: "2026-02-25 18:25", read: false },
      { type: "bot", text: "저희 고객 관리 시스템은 명함 기반 자동 연락처 관리부터 맞춤형 메시지 발송까지 지원합니다.\n\n편리하게 고객과의 관계를 유지하고 신규 고객 확보에도 효과적입니다!", time: null, read: true }
    ]
  },
  {
    id: 10,
    name: "김혁",
    position: "원장 | CEO",
    phone: "01068000000",
    regDate: "2026-02-25",
    chatCount: 1,
    unreadCount: 0,
    lastChat: "2026-02-25 10:03",
    shortUrl: "/s/tuJsNF",
    colorClass: "color-2",
    pwaInstalled: true,
    messages: [
      { type: "user", text: "안녕하세요", time: "2026-02-25 10:00", read: true },
      { type: "bot", text: "안녕하세요! 김혁 원장님, 만나 뵙게 되어 반갑습니다.\n궁금하신 점이나 도움이 필요하신 사항이 있으시면 언제든지 말씀해 주세요. 진심을 담아 도와드리겠습니다!", time: null, read: true }
    ]
  },
  {
    id: 11,
    name: "남민우",
    position: "원장",
    phone: "01041547433",
    regDate: "2026-02-25",
    chatCount: 0,
    lastChat: null,
    shortUrl: "/s/9ey2UB",
    colorClass: "color-3",
    pwaInstalled: false,
    messages: []
  }
];

// ===========================
// 수신 리스트 데이터
// (내가 상대방 챗봇 링크를 받아 입장한 경우)
// ===========================
const receivedData = [
  {
    id: 'r1',
    name: '박민준',
    botName: '박민준 AI 비서',
    position: '대표이사',
    company: '(주)넥스트이노베이션',
    phone: '01023456789',
    receivedDate: '2026-03-28',
    chatbotUrl: 'https://chatbot.kiam.kr/s/MJ8K2A',
    colorClass: 'color-4',
    chatCount: 3,
    unreadCount: 1,
    lastChat: '2026-03-28 16:45',
    messages: [
      { type: 'bot', text: '안녕하세요! 박민준 대표님의 AI 비서입니다. 무엇을 도와드릴까요? 😊', time: '2026-03-28 16:30', read: true },
      { type: 'user', text: '넥스트이노베이션 서비스가 궁금해요.', time: '2026-03-28 16:40', read: true },
      { type: 'bot', text: '저희 넥스트이노베이션은 AI 기반 비즈니스 자동화 솔루션을 제공합니다.\n마케팅 자동화, 고객관리, 업무 효율화까지 원스톱으로 지원해드립니다!', time: '2026-03-28 16:41', read: true },
      { type: 'user', text: '미팅 일정을 잡고 싶어요.', time: '2026-03-28 16:45', read: false }
    ]
  },
  {
    id: 'r2',
    name: '이수진',
    botName: '이수진 마케팅 봇',
    position: '마케팅 이사',
    company: '(주)브랜드랩',
    phone: '01087654321',
    receivedDate: '2026-03-25',
    chatbotUrl: 'https://chatbot.kiam.kr/s/SJ9P3R',
    colorClass: 'color-6',
    chatCount: 5,
    unreadCount: 0,
    lastChat: '2026-03-26 11:20',
    messages: [
      { type: 'bot', text: '안녕하세요! 이수진 이사님의 마케팅 챗봇입니다. 브랜드랩의 마케팅 솔루션을 소개해드릴게요 🎯', time: '2026-03-25 10:00', read: true },
      { type: 'user', text: '어떤 마케팅 서비스를 제공하시나요?', time: '2026-03-25 10:05', read: true },
      { type: 'bot', text: 'SNS 광고, 콘텐츠 마케팅, 이메일 자동화, 퍼포먼스 마케팅까지 제공합니다!\n특히 AI 기반 타겟팅으로 ROI를 극대화해 드립니다.', time: '2026-03-25 10:06', read: true },
      { type: 'user', text: '견적서를 받을 수 있을까요?', time: '2026-03-25 14:30', read: true },
      { type: 'bot', text: '물론이죠! 이메일 주소를 알려주시면 맞춤 견적서를 보내드리겠습니다 📧', time: '2026-03-25 14:31', read: true },
      { type: 'user', text: 'contact@example.com 으로 보내주세요', time: '2026-03-26 11:20', read: true }
    ]
  },
  {
    id: 'r3',
    name: '최현우',
    botName: '최현우 투자 어드바이저',
    position: '파트너',
    company: 'KV 벤처스',
    phone: '01034567890',
    receivedDate: '2026-03-20',
    chatbotUrl: 'https://chatbot.kiam.kr/s/HW5K7T',
    colorClass: 'color-8',
    chatCount: 0,
    unreadCount: 0,
    lastChat: null,
    messages: []
  },
  {
    id: 'r4',
    name: '정유리',
    botName: '정유리 컨설턴트',
    position: '수석 컨설턴트',
    company: '(주)비즈파트너스',
    phone: '01056781234',
    receivedDate: '2026-03-18',
    chatbotUrl: 'https://chatbot.kiam.kr/s/YR2C8M',
    colorClass: 'color-2',
    chatCount: 2,
    unreadCount: 0,
    lastChat: '2026-03-19 09:50',
    messages: [
      { type: 'bot', text: '안녕하세요! 정유리 컨설턴트입니다. 비즈니스 성장을 위한 전략 컨설팅을 제공합니다 💼', time: '2026-03-18 15:00', read: true },
      { type: 'user', text: '스타트업 컨설팅도 가능한가요?', time: '2026-03-19 09:45', read: true },
      { type: 'bot', text: '네, 스타트업 특화 컨설팅도 가능합니다!\n시드 단계부터 Series A까지 투자 유치, 비즈니스 모델 수립, 팀 빌딩까지 지원해드립니다.', time: '2026-03-19 09:50', read: true }
    ]
  },
  {
    id: 'r5',
    name: '한동훈',
    botName: '한동훈 네트워크',
    position: '대표',
    company: '(주)커넥트원',
    phone: '01078901234',
    receivedDate: '2026-03-10',
    chatbotUrl: 'https://chatbot.kiam.kr/s/DH4N1Q',
    colorClass: 'color-5',
    chatCount: 0,
    unreadCount: 0,
    lastChat: null,
    messages: []
  }
];


// ===========================
// 공유 리스트 visitor 데이터
// (운영자 링크 1개로 접속한 방문자 — visitor_id 기반)
// ===========================
const visitorData = [
  {
    id: 'v1',
    visitorId: 'xK3mP2',
    customName: '김영철',
    pwaInstalled: true,
    phone: '01012345678',
    chatCount: 15,
    unreadCount: 2,
    lastChat: '2026-04-09 10:30',
    messages: [
      { type: 'bot', text: '안녕하세요! 무엇을 도와드릴까요? 😊', time: '2026-04-08 09:00', read: true },
      { type: 'user', text: '보험 상품이 궁금한데요.', time: '2026-04-08 09:05', read: true },
      { type: 'bot', text: '물론이죠! 어떤 종류의 보험이 필요하신가요?', time: '2026-04-08 09:06', read: true },
      { type: 'user', text: '실손보험 위주로 알고 싶어요.', time: '2026-04-09 10:30', read: false },
      { type: 'user', text: '비교도 해주실 수 있나요?', time: '2026-04-09 10:31', read: false }
    ]
  },
  {
    id: 'v2',
    visitorId: 'Zp9mQ7',
    customName: null,
    pwaInstalled: false,
    phone: null,
    chatCount: 3,
    unreadCount: 0,
    lastChat: '2026-04-09 09:15',
    messages: [
      { type: 'bot', text: '안녕하세요! 무엇을 도와드릴까요?', time: '2026-04-09 09:00', read: true },
      { type: 'user', text: '안녕하세요', time: '2026-04-09 09:10', read: true },
      { type: 'user', text: '상품 가격이 얼마예요?', time: '2026-04-09 09:15', read: true }
    ]
  },
  {
    id: 'v3',
    visitorId: 'Rm4tL8',
    customName: '박현숙 대리',
    pwaInstalled: true,
    phone: '01098765432',
    chatCount: 8,
    unreadCount: 0,
    lastChat: '2026-04-08 16:20',
    messages: [
      { type: 'bot', text: '안녕하세요!', time: '2026-04-07 14:00', read: true },
      { type: 'user', text: '담당자 연결 부탁드립니다.', time: '2026-04-08 16:20', read: true }
    ]
  },
  {
    id: 'v4',
    visitorId: 'Kn2wH5',
    customName: null,
    pwaInstalled: false,
    phone: null,
    chatCount: 0,
    unreadCount: 0,
    lastChat: null,
    messages: []
  },
  {
    id: 'v5',
    visitorId: 'Ts6fJ3',
    customName: '이진수',
    pwaInstalled: true,
    phone: null,
    chatCount: 22,
    unreadCount: 1,
    lastChat: '2026-04-09 11:00',
    messages: [
      { type: 'bot', text: '안녕하세요!', time: '2026-04-06 10:00', read: true },
      { type: 'user', text: '가입 방법 알려주세요', time: '2026-04-09 11:00', read: false }
    ]
  }
];
