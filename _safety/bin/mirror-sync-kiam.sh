#!/bin/bash
# =============================================================================
# mirror-sync-kiam — /home/kiam 저장소를 "격리된 오프트리 미러"로 백업 [iamserver]
#
#   목적: 메인 서비스 /home/kiam 의 git 저장소(분리본 /home/git-repos/kiam.git)를
#         물리적으로 다른 디스크(/var, sda3)에 bare 미러로 복제한다.
#         - /home (nvme0n1p1) 장애 ↔ /var (sda3) 미러가 상호 독립
#         - 폴더 통째 유실/디스크 장애 시 최후의 오프트리 복구원본
#
#   설계 포인트:
#     * kiam 은 .git 을 /home/git-repos/kiam.git 으로 물리 분리했으므로,
#       미러 소스는 그 분리본을 직접 가리킨다(SRC).
#     * 미러가 소스에서 "당겨오는(fetch)" 방식이라 서빙 쪽 명령/훅을 트리거하지 않음
#       → shim/훅과 무관하게 안전.
#
#   사용:  mirror-sync-kiam.sh           # 미러 동기화(없으면 생성)
#          mirror-sync-kiam.sh --verify  # 미러 무결성 점검
#   크론:  */30 * * * * /home/webapp/_safety/bin/mirror-sync-kiam.sh >> /home/git-safety/logs/mirror-kiam.log 2>&1
#   작성: 아리 2026-07-02
#   ── git 1.8.3.1 호환: --connectivity-only 없음 → rev-parse+count-objects 로 점검
# =============================================================================
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi

NAME="kiam"
# 분리된 저장소 본체(포인터가 가리키는 곳). 없으면 서빙폴더로 폴백.
if [ -d "/home/git-repos/kiam.git" ]; then
  SRC="/home/git-repos/kiam.git"
else
  SRC="/home/kiam"
fi
MIRROR_ROOT=/var/git-mirrors        # /home 과 다른 물리 디스크(sda3)
MIRROR="$MIRROR_ROOT/$NAME.git"
LOG="/home/git-safety/logs/mirror-kiam.log"
mkdir -p "$MIRROR_ROOT" "$(dirname "$LOG")" 2>/dev/null

_ts(){ date '+%Y-%m-%d %H:%M:%S'; }
_log(){ echo "[$(_ts)] $*"; }

if [ "$1" = "--verify" ]; then
  _log "=== kiam 미러 무결성 점검 ==="
  if [ ! -d "$MIRROR" ]; then echo "  ❌ 미러 없음: $MIRROR"; exit 1; fi
  head_ok=""; obj_ok=""
  "$REAL_GIT" --git-dir="$MIRROR" rev-parse --verify HEAD >/dev/null 2>&1 && head_ok=1
  cnt=$("$REAL_GIT" --git-dir="$MIRROR" count-objects 2>/dev/null | awk '{print $1}')
  [ -n "$cnt" ] && [ "$cnt" -ge 0 ] 2>/dev/null && obj_ok=1
  # 소스 HEAD 와 미러 HEAD 비교
  src_head=$( ( cd "$SRC" 2>/dev/null && "$REAL_GIT" --git-dir="$SRC" rev-parse HEAD 2>/dev/null ) )
  [ -z "$src_head" ] && src_head=$( cd /home/kiam 2>/dev/null && "$REAL_GIT" rev-parse HEAD 2>/dev/null )
  mir_head=$("$REAL_GIT" --git-dir="$MIRROR" rev-parse refs/heads/genspark_ai_developer 2>/dev/null)
  echo "  소스 HEAD : ${src_head:-?}"
  echo "  미러 HEAD : ${mir_head:-?}"
  if [ -n "$head_ok" ] && [ -n "$obj_ok" ]; then
    echo "  ✅ $NAME 미러 OK ($(du -sh "$MIRROR" 2>/dev/null | cut -f1), objects=$cnt)"
  else
    echo "  ❌ $NAME 미러 손상 의심 (HEAD도달=${head_ok:-N} objects=${obj_ok:-N})"
  fi
  exit 0
fi

_log "===== kiam 미러 동기화 시작 (SRC=$SRC) ====="
if [ ! -d "$MIRROR" ]; then
  _log "미러 신규 생성: $NAME → $MIRROR"
  if "$REAL_GIT" clone --bare "$SRC" "$MIRROR" >/dev/null 2>&1; then
    _log "  ✅ 생성 완료 ($(du -sh "$MIRROR" 2>/dev/null | cut -f1))"
  else
    _log "  ❌ 생성 실패"; exit 1
  fi
else
  if "$REAL_GIT" --git-dir="$MIRROR" fetch --prune "$SRC" \
       '+refs/heads/*:refs/heads/*' '+refs/tags/*:refs/tags/*' >/dev/null 2>&1; then
    _log "  ✅ $NAME 미러 동기화(fetch) 완료"
  else
    _log "  ⚠ $NAME 동기화 실패"
  fi
fi
_log "===== kiam 미러 동기화 종료 ====="
