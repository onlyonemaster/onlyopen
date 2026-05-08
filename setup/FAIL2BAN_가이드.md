# 🔧 Fail2ban 차단 해결 가이드

## 문제 현상
```
ssh: connect to host 222.239.248.226 port 10022: Connection refused
```

## 원인
젠스파크 샌드박스의 공인 IP가 변경되면, 해당 IP가 Fail2ban에 의해 무차별 대입 공격 IP로 오인되어 차단된다.

## 🟢 자동 해결 (권장)

```bash
cd /home/webapp/setup && bash fix_fail2ban.sh
```

이 스크립트 한 줄이면 모든 과정이 자동으로 진행된다.

## 🔵 수동 해결 (스크립트 사용 불가 시)

### 1단계: 내 IP 확인
```bash
curl -s ifconfig.me
# 예: 20.83.126.49
```

### 2단계: 비밀번호로 SSH 접속 시도
```bash
sshpass -p 'onlyKiam12##' ssh -o StrictHostKeyChecking=no -p 10022 \
  -o PreferredAuthentications=password -o PubkeyAuthentication=no \
  root@127.0.0.1 "hostname"
```

### 3단계: 차단 해제
```bash
sshpass -p 'onlyKiam12##' ssh ... root@127.0.0.1 \
  "fail2ban-client set sshd unbanip <차단된IP>"
```

### 4단계: ignoreip 영구 등록
```bash
sshpass -p 'onlyKiam12##' ssh ... root@127.0.0.1 \
  "cp /etc/fail2ban/jail.local /etc/fail2ban/jail.local.bak-$(date +%Y%m%d) && \
   sed -i 's/^ignoreip = /ignoreip = <차단된IP> /' /etc/fail2ban/jail.local && \
   systemctl restart fail2ban"
```

## 서버 정보
| 항목 | 값 |
|------|-----|
| 호스트 | 127.0.0.1 (같은 머신) |
| SSH 포트 | 10022 |
| 사용자 | root |
| Jail 이름 | sshd |
| 설정 파일 | /etc/fail2ban/jail.local |
