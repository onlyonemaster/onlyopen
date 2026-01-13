# 🎯 AI 정책사업 매칭 공모 랜딩페이지

## 📍 접속 정보

- **URL**: https://open.kiam.kr/kaiba-meet/
- **경로**: `/var/www/open/kaiba-meet`
- **도메인**: open.kiam.kr (SSL 인증 완료)

## 🎨 프로젝트 개요

익명 CEO 및 업체 대표들을 대상으로 AI 정책사업 컨소시엄 매칭을 위한 현장 참여를 유도하는 랜딩페이지입니다.

### 핵심 목적
> "AI 정책사업을 혼자 하기 어려운 익명 CEO·대표가 현장에 와서 컨소시엄 매칭을 직접 받고 싶어지게 만드는 것"

## 🏗️ 기술 스택

- **HTML5**: 시맨틱 마크업
- **CSS3**: 반응형 디자인 (Mobile First)
- **JavaScript (ES6+)**: 인터랙티브 기능
- **Apache**: 웹서버

## 📂 파일 구조

```
/var/www/open/kaiba-meet/
├── index.html          # 메인 랜딩페이지
├── style.css           # 스타일시트
├── script.js           # 인터랙션 스크립트
└── README.md           # 프로젝트 문서
```

## 📊 페이지 구조

1. **Hero Section** - 강력한 첫인상
   - 메인 헤드라인: "AI 정책사업, 혼자 하면 거의 안 됩니다"
   - 핵심 수치: 120~150건, 5천~2만5천 업체
   - CTA 버튼

2. **Section 1: 왜 AI 정책사업인가**
   - 배경 설명
   - AI 정책사업의 중요성

3. **Section 2: 혼자 하면 안 되는 이유**
   - 현실 공감 체크리스트
   - 문제 인식

4. **Section 3: 매칭형 컨소시엄**
   - 솔루션 제시
   - 매칭 프로세스 시각화

5. **Section 4: 협회가 해주는 일**
   - 7가지 핵심 서비스
   - 신뢰 구축

6. **Section 5: 현장에서 무엇이 벌어지는가**
   - 타임라인 (브리핑 10분 + AI 코딩 10분 + 매칭)
   - 구체적 진행 내용

7. **Section 6: 대상자**
   - 7가지 대상 체크리스트
   - 참여 유도

8. **CTA Section**
   - 최종 행동 유도
   - 참여 링크

## 🎨 디자인 특징

### 컬러 팔레트
```css
Primary:   #2563EB (블루 - 신뢰감)
Secondary: #DC2626 (레드 - 긴급성)
Accent:    #F59E0B (오렌지 - 기회)
```

### 반응형 디자인
- 모바일 우선 (Mobile First)
- 태블릿 최적화
- 데스크톱 대응 (최대 1200px)

### 애니메이션
- Fade-in 효과
- Scroll 인터랙션
- Hover 이펙트
- Pulse 애니메이션 (CTA 버튼)

## 🚀 배포 정보

### Apache 설정
- **설정 파일**: `/etc/httpd/conf.d/open.kiam.kr.conf`
- **Alias**: `/kaiba-meet` → `/var/www/open/kaiba-meet`
- **SSL**: 인증 완료 (Let's Encrypt)

### 접근 권한
```bash
소유자: apache:apache
권한: 755
```

## 📱 메시지 활용 방식

### 발송 메시지 구조
```
제목: [AI 정책사업 매칭 공모]

본문:
AI 정책사업을 혼자 하기 어려우신가요?

현장에서 바로 매칭해드립니다.
기술·솔루션·마케팅·운영을 연결합니다.

👉 https://open.kiam.kr/kaiba-meet/

※ 자세한 내용은 위 링크에서 확인하세요
```

### 핵심 전략
- ❌ 메시지에 긴 설명 넣지 않기
- ⭕ 랜딩페이지 링크 하나만 전달
- ⭕ 모든 설득은 랜딩페이지에서

## 🔧 유지보수

### 파일 수정 시
```bash
# 1. 파일 수정
cd /var/www/open/kaiba-meet
vi index.html  # 또는 style.css, script.js

# 2. 권한 확인
chown -R apache:apache .
chmod -R 755 .

# 3. Apache 재시작 (필요시)
systemctl restart httpd
```

### 설정 변경 시
```bash
# 1. Apache 설정 수정
vi /etc/httpd/conf.d/open.kiam.kr.conf

# 2. 설정 테스트
apachectl configtest

# 3. Apache 재시작
systemctl restart httpd
```

## 📊 성과 측정 지표

### 목표 KPI
- 페이지 열람률: 70% 이상 (메시지 발송 대비)
- 체류 시간: 평균 2분 이상
- CTA 클릭률: 30% 이상
- 현장 참여율: 50% 이상 (CTA 클릭 대비)

## 🔍 테스트 체크리스트

- [x] 모바일 반응형 테스트
- [x] 태블릿 반응형 테스트
- [x] 데스크톱 디스플레이 테스트
- [x] SSL 인증 확인
- [x] 페이지 로딩 속도 (3초 이내)
- [x] CTA 버튼 작동 확인
- [x] 스크롤 애니메이션 확인
- [x] 접근성 테스트

## 🛠️ 트러블슈팅

### 페이지가 열리지 않는 경우
```bash
# 1. Apache 상태 확인
systemctl status httpd

# 2. 에러 로그 확인
tail -f /var/log/httpd/open.kiam.kr-ssl-error.log

# 3. 파일 권한 확인
ls -la /var/www/open/kaiba-meet/
```

### CSS/JS가 적용되지 않는 경우
```bash
# 1. 파일 존재 확인
ls /var/www/open/kaiba-meet/

# 2. 파일 권한 확인
chmod 644 /var/www/open/kaiba-meet/*.css
chmod 644 /var/www/open/kaiba-meet/*.js

# 3. 브라우저 캐시 삭제 후 재접속
```

## 📞 연락처

- **조직**: 한국인공지능협회
- **프로젝트**: AI 정책사업 매칭 공모
- **제작일**: 2026년 1월 13일

## 📝 변경 이력

### v1.0.0 (2026-01-13)
- 초기 랜딩페이지 제작 완료
- 8개 섹션 구성
- 반응형 디자인 적용
- 애니메이션 효과 구현
- Apache 설정 및 배포 완료

## 🎯 핵심 원칙 (절대 잊지 말 것)

1. 이 페이지의 목적은 단 하나: **"현장에 오게 만들기"**
2. 정보 제공 ❌ 행동 유도 ⭕
3. 긴 설명 ❌ 짧고 강력한 메시지 ⭕
4. 협회 자랑 ❌ 방문자의 문제 해결 ⭕
5. 복잡한 구조 ❌ 명확한 흐름 ⭕

---

**이 랜딩페이지는 '정책사업 정보 페이지'가 아니라 '현장으로 오게 만드는 설득 장치'입니다.**
