#!/bin/bash
# =============================================================================
# mirror-sync-nested-kiam — /home/git-repos/nested_*.git 를 오프트리 미러로 백업
#                            [iamserver / bigserver 방식 이식]
#
#   목적: /home/kiam 밖으로 물리분리된 "중첩 저장소" 본체들
#         (/home/git-repos/nested_*.git)을 물리적으로 다른 디스크
#         (/var, sda3)에 bare 미러로 복제한다.
#         - 메인 kiam.git 은 mirror-sync-kiam.sh 가 별도 담당
#         - 이 스크립트는 nested_*.git 전부를 순회하여 동기화
#
#   설계 포인트:
#     * 소스는 이미 분리된 bare/일반 저장소이므로 fetch 방식으로 당겨온다.
#       (서빙쪽 명령/훅 트리거 없음 → shim/훅과 무관하게 안전)
#     * 대상 미러명은 소스 basename 을 그대로 사용 (nested_*.git)
#
#   사용:  mirror-sync-nested-kiam.sh           # 전체 동기화(없으면 생성)
#          mirror-sync-nested-kiam.sh --verify  # 미러 무결성 점검
#   크론:  10,40 * * * * /home/webapp/_safety/bin/mirror-sync-nested-kiam.sh >> /home/git-safety/logs/mirror-kiam.log 2>&1
#   작성: 아리 2026-07-02
#   ── git 1.8.3.1 호환
# =============================================================================
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi

REPOS_ROOT=/home/git-repos          # 분리된 저장소 본체들이 있는 곳
MIRROR_ROOT=/var/git-mirrors        # /home 과 다른 물리 디스크(sda3)
LOG="/home/git-safety/logs/mirror-kiam.log"
mkdir -p "$MIRROR_ROOT" "$(dirname "$LOG")" 2>/dev/null

_ts(){ date '+%Y-%m-%d %H:%M:%S'; }
_log(){ echo "[$(_ts)] $*"; }

# 대상 목록: nested_*.git 만 (메인 kiam.git / webapp.git 등은 제외)
shopt -s nullglob
NESTED=( "$REPOS_ROOT"/nested_*.git )
shopt -u nullglob

if [ "${1:-}" = "--verify" ]; then
  _log "=== nested 미러 무결성 점검 (${#NESTED[@]}개) ==="
  ok=0; bad=0
  for src in "${NESTED[@]}"; do
    name=$(basename "$src")
    mir="$MIRROR_ROOT/$name"
    if [ ! -d "$mir" ]; then echo "  ❌ 미러 없음: $name"; bad=$((bad+1)); continue; fi
    src_head=$( ( cd "$src" && "$REAL_GIT" rev-parse HEAD 2>/dev/null ) )
    # 미러의 동일 브랜치 HEAD 비교 (기본: 모든 브랜치 중 하나라도 소스와 일치하는지)
    mir_ok=""
    "$REAL_GIT" --git-dir="$mir" rev-parse --verify HEAD >/dev/null 2>&1 && mir_ok=1
    # 소스 HEAD 커밋이 미러에 존재하는가
    hit=""
    [ -n "$src_head" ] && "$REAL_GIT" --git-dir="$mir" cat-file -e "$src_head" 2>/dev/null && hit=1
    if [ -n "$hit" ]; then
      echo "  ✅ $name (HEAD ${src_head:0:7} 미러 보유, $(du -sh "$mir" 2>/dev/null|cut -f1))"
      ok=$((ok+1))
    else
      echo "  ⚠ $name (소스 HEAD ${src_head:0:7} 미러 미보유 / mir_ok=${mir_ok:-N})"
      bad=$((bad+1))
    fi
  done
  _log "=== 점검 종료: OK $ok / 이상 $bad ==="
  exit 0
fi

_log "===== nested 미러 동기화 시작 (${#NESTED[@]}개) ====="
OK=0; NEW=0; FAIL=0
for src in "${NESTED[@]}"; do
  name=$(basename "$src")
  mir="$MIRROR_ROOT/$name"
  if [ ! -d "$mir" ]; then
    if "$REAL_GIT" clone --bare "$src" "$mir" >/dev/null 2>&1; then
      _log "  ✅ 신규 미러 생성: $name ($(du -sh "$mir" 2>/dev/null|cut -f1))"
      NEW=$((NEW+1))
    else
      _log "  ❌ 생성 실패: $name"; FAIL=$((FAIL+1))
    fi
  else
    if "$REAL_GIT" --git-dir="$mir" fetch --prune "$src" \
         '+refs/heads/*:refs/heads/*' '+refs/tags/*:refs/tags/*' >/dev/null 2>&1; then
      OK=$((OK+1))
    else
      _log "  ⚠ 동기화 실패: $name"; FAIL=$((FAIL+1))
    fi
  fi
done
_log "===== nested 미러 종료: 갱신 $OK / 신규 $NEW / 실패 $FAIL ====="
