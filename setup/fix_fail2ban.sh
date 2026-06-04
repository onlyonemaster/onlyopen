#!/bin/bash
# =============================================================================
# Fail2ban 차단 해제 + 영구 ignoreip 등록 스크립트
# 위치: /home/webapp/setup/fix_fail2ban.sh
# 용도: 젠스파크 IP가 변경되어 fail2ban에 차단됐을 때 실행
# =============================================================================

SSH_PORT=10022
SSH_USER=root
SSH_PASS='onlyKiam12##'
SSH_HOST=127.0.0.1
JAIL_NAME=sshd
JAIL_LOCAL=/etc/fail2ban/jail.local

# SSH 함수 (일관되게 사용)
do_ssh() {
    sshpass -p "$SSH_PASS" ssh \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=5 \
        -p "$SSH_PORT" \
        -o PreferredAuthentications=password \
        -o PubkeyAuthentication=no \
        "$SSH_USER@$SSH_HOST" "$@" 2>/dev/null
}

cat << 'BANNER'

╔══════════════════════════════════════════════════════╗
║        Fail2ban 차단 해제 & 영구 등록 도구          ║
╚══════════════════════════════════════════════════════╝

BANNER

# ── 1단계: 현재 차단된 IP 목록 확인 ──────────────────────
echo "▶ [1/6] 현재 차단된 IP 목록 조회 중..."
BANNED_COUNT=$(do_ssh "fail2ban-client get $JAIL_NAME banned 2>/dev/null" | python3 -c "import sys,ast; print(len(ast.literal_eval(sys.stdin.read())))" 2>/dev/null || echo "0")
echo "   차단된 IP 개수: $BANNED_COUNT"
echo ""

# ── 2단계: 우리 IP 확인 ──────────────────────────────────
echo "▶ [2/6] 현재 샌드박스 공인 IP 확인 중..."
MY_IP=$(curl -s --connect-timeout 5 ifconfig.me 2>/dev/null || echo "127.0.0.1")
echo "   현재 IP: $MY_IP"
echo ""

# ── 3단계: 차단 해제 ─────────────────────────────────────
echo "▶ [3/6] fail2ban에서 IP 차단 해제 중..."
if do_ssh "fail2ban-client set $JAIL_NAME unbanip $MY_IP" 2>/dev/null; then
    echo "   ✅ $MY_IP 차단 해제 완료"
else
    echo "   ⚠️  $MY_IP 가 차단 목록에 없음 (또는 이미 해제됨)"
fi
do_ssh "fail2ban-client set $JAIL_NAME unbanip 127.0.0.1" 2>/dev/null || true
echo ""

# ── 4단계: ignoreip 영구 등록 ────────────────────────────
echo "▶ [4/6] jail.local ignoreip에 영구 등록 중..."
ALREADY=$(do_ssh "grep -q '$MY_IP' $JAIL_LOCAL 2>/dev/null && echo YES || echo NO")
if [ "$ALREADY" = "YES" ]; then
    echo "   ✅ $MY_IP 는 이미 ignoreip에 등록되어 있음"
else
    BACKUP_NAME="${JAIL_LOCAL}.bak-$(date +%Y%m%d_%H%M%S)"
    do_ssh "cp $JAIL_LOCAL $BACKUP_NAME"
    do_ssh "sed -i 's/^ignoreip = /ignoreip = $MY_IP /' $JAIL_LOCAL"
    echo "   ✅ $MY_IP ignoreip 등록 완료 (백업: $BACKUP_NAME)"
fi

# 127.0.0.1도 항상 포함
HAS_LOCALHOST=$(do_ssh "grep -q 'ignoreip.*127.0.0.1' $JAIL_LOCAL 2>/dev/null && echo YES || echo NO")
if [ "$HAS_LOCALHOST" != "YES" ]; then
    do_ssh "sed -i 's/^ignoreip = /ignoreip = 127.0.0.1 /' $JAIL_LOCAL"
    echo "   ✅ 127.0.0.1 ignoreip 등록 완료"
fi

echo ""
echo "   📄 현재 ignoreip 설정:"
do_ssh "grep 'ignoreip' $JAIL_LOCAL"
echo ""

# ── 5단계: fail2ban 재시작 ───────────────────────────────
echo "▶ [5/6] fail2ban 재시작 중..."
if do_ssh "systemctl restart fail2ban"; then
    echo "   ✅ fail2ban 재시작 완료"
else
    echo "   ❌ 재시작 실패"
fi
echo ""

# ── 6단계: 최종 확인 ─────────────────────────────────────
echo "▶ [6/6] 최종 상태 확인..."
echo ""
do_ssh "
echo '   ┌─ fail2ban sshd 상태 ──────────────────────┐'
fail2ban-client status $JAIL_NAME 2>/dev/null | while IFS= read -r line; do printf '   │ %s\n' \"\$line\"; done
echo '   └──────────────────────────────────────────┘'
echo ''
echo '   🟢 접속 테스트:'
hostname && echo '   ✅ SSH 접속 정상!'
"
echo ""

cat << RESULT

╔══════════════════════════════════════════════════════╗
║  🎉 모든 작업 완료!                                  ║
║  $MY_IP 가 fail2ban ignoreip에 등록되었습니다.       ║
╚══════════════════════════════════════════════════════╝
RESULT