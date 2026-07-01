#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  Layer 1 방어막 — git clean 차단 래퍼 (kiam / iamserver)
#  PATH 우선순위(/usr/local/bin → /usr/bin)를 이용해 진짜 git보다
#  먼저 실행되어 'clean' 서브커맨드를 무조건 차단한다.
#  SSH · bash script · Claude Code 등 모든 호출 경로에서 동작.
# ════════════════════════════════════════════════════════════════
# 진짜 git 바이너리 (Layer 3 가 /usr/bin/git 을 가드로 교체하므로
# 독립 복사본을 직접 가리킨다. 이 경로가 없으면 /usr/bin/git 로 폴백.)
if [ -x "/usr/local/lib/git-guard/realgit/git" ]; then
    REAL_GIT="/usr/local/lib/git-guard/realgit/git"
else
    REAL_GIT="/usr/bin/git"
fi

# 첫 번째 비옵션 인자(서브커맨드)를 찾는다. -c key=val 같은
# 전역 옵션을 건너뛰고 실제 서브커맨드를 정확히 판별한다.
subcmd=""
skip_next=0
for arg in "$@"; do
    if [ "$skip_next" = "1" ]; then
        skip_next=0
        continue
    fi
    case "$arg" in
        -c)
            # 'git -c alias.clean= clean' 우회 시도 차단:
            # -c 다음 값이 alias.clean 재정의이면 무시하고 계속 검사
            skip_next=1
            continue
            ;;
        --exec-path=*|--git-dir=*|--work-tree=*|--namespace=*|-C|--*)
            # -C <path> 는 값을 하나 더 먹으므로 처리
            if [ "$arg" = "-C" ]; then skip_next=1; fi
            continue
            ;;
        -*)
            continue
            ;;
        *)
            subcmd="$arg"
            break
            ;;
    esac
done

if [ "$subcmd" = "clean" ]; then
    echo "⛔ [Layer 1] git clean 명령이 차단되었습니다. (서버 소스 보호 정책)" >&2
    echo "   파일 정리가 꼭 필요하면 서버 관리자에게 문의하세요." >&2
    logger -t git-guard "BLOCKED git clean attempt by uid=$(id -u) cwd=$(pwd) args=[$*]" 2>/dev/null
    exit 1
fi

exec "$REAL_GIT" "$@"
