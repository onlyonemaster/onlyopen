#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  Layer 3 방어막 — /usr/bin/git 직접 호출 차단 가드
#  PATH 우회(/usr/bin/git clean ...)로 Layer 1 래퍼를 건너뛰는
#  경로까지 막는다. 진짜 git 은 독립 복사본에서 실행.
# ════════════════════════════════════════════════════════════════
REAL_GIT="/usr/local/lib/git-guard/realgit/git"
[ -x "$REAL_GIT" ] || REAL_GIT="/usr/libexec/git-core/git"

subcmd=""
skip_next=0
for arg in "$@"; do
    if [ "$skip_next" = "1" ]; then skip_next=0; continue; fi
    case "$arg" in
        -c) skip_next=1; continue ;;
        -C) skip_next=1; continue ;;
        --exec-path=*|--git-dir=*|--work-tree=*|--namespace=*) continue ;;
        --*) continue ;;
        -*) continue ;;
        *) subcmd="$arg"; break ;;
    esac
done

if [ "$subcmd" = "clean" ]; then
    echo "⛔ [Layer 3] git clean 명령이 차단되었습니다. (/usr/bin/git 가드)" >&2
    echo "   서버 소스 보호 정책 — 관리자에게 문의하세요." >&2
    logger -t git-guard "BLOCKED /usr/bin/git clean by uid=$(id -u) cwd=$(pwd) args=[$*]" 2>/dev/null
    exit 1
fi

exec "$REAL_GIT" "$@"
