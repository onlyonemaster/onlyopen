#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  git clean 4중 방어 시스템 — 상태 점검 도구
#  사용:  bash /usr/local/lib/git-guard/status.sh [REPO_ROOT]
# ════════════════════════════════════════════════════════════════
REPO_ROOT="${1:-/home/webapp}"
REAL_GIT="/usr/local/lib/git-guard/realgit/git"
echo "════════════════════════════════════════════════════════"
echo "   git clean 4중 방어 시스템 상태 (repo: $REPO_ROOT)"
echo "════════════════════════════════════════════════════════"

echo ""
echo "【Layer 1】 /usr/local/bin/git 래퍼"
if [ -f /usr/local/bin/git ]; then
    echo "  설치됨  | 불변속성: $(lsattr /usr/local/bin/git 2>/dev/null | awk '{print $1}')"
else echo "  ❌ 없음"; fi

echo ""
echo "【Layer 2】 .gitignore 보호 등록"
cnt=$("$REAL_GIT" -C "$REPO_ROOT" clean -fdn 2>/dev/null | grep -c "Would remove")
echo "  git clean 삭제대상: ${cnt}개 (0이어야 정상)"

echo ""
echo "【Layer 3】 /usr/bin/git 가드"
if head -1 /usr/bin/git 2>/dev/null | grep -q bash; then
    echo "  설치됨  | 불변속성: $(lsattr /usr/bin/git 2>/dev/null | awk '{print $1}')"
else echo "  ⚠️ 원본 git (가드 미적용)"; fi

echo ""
echo "【Layer 4】 inotify watcher (systemd)"
echo "  active : $(systemctl is-active webapp-gitignore-watcher.service 2>/dev/null)"
echo "  enabled: $(systemctl is-enabled webapp-gitignore-watcher.service 2>/dev/null)"
echo "════════════════════════════════════════════════════════"
