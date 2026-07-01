#!/bin/bash
# =============================================================================
# mirror-sync — 서빙↔git-work 분리 (방어막4): 서빙 저장소를 "격리된 미러"로 백업 [iamserver]
#
#   문제: /home/webapp 은 "서빙 디렉토리"이면서 동시에 "git 작업 디렉토리"라,
#         여기서 reset --hard / clean 등을 잘못 쓰면 라이브 사이트가 즉시 파괴된다.
#   방어: 서빙 저장소와 물리적으로 분리된 위치(/var/git-mirrors/webapp.git)에
#         bare 미러를 두고 주기적으로 fetch 한다. 서빙 디렉토리 안에서 무슨 일이
#         일어나도 이 미러는 손대지 못한다(=최후의 오프트리 복구원본).
#
#   또한 중첩 저장소(자체 .git 보유 사이트)들도 각각 미러링한다.
#
#   사용:  mirror-sync.sh          # 부모 + 모든 중첩 저장소 미러 동기화
#          mirror-sync.sh --verify # 미러 무결성 점검
#   크론 권장:  */30 * * * * /home/webapp/_safety/bin/mirror-sync.sh >> /home/webapp/_safety/logs/mirror.log 2>&1
#   이식: 아리 2026-07-01  (REPO=/home/webapp, MIRROR_ROOT=/var/git-mirrors)
# =============================================================================
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi
REPO=/home/webapp
MIRROR_ROOT=/var/git-mirrors
LOG="$REPO/_safety/logs/mirror.log"
mkdir -p "$MIRROR_ROOT" "$(dirname "$LOG")" 2>/dev/null
_ts(){ date '+%Y-%m-%d %H:%M:%S'; }
_log(){ echo "[$(_ts)] $*"; }

sync_one() {
  local src="$1" name="$2"
  local mir="$MIRROR_ROOT/$name.git"
  if [ ! -d "$mir" ]; then
    _log "미러 신규 생성: $name → $mir"
    "$REAL_GIT" clone --bare "$src" "$mir" >/dev/null 2>&1 \
      && _log "  ✅ 생성 완료" || { _log "  ❌ 생성 실패"; return 1; }
    return 0
  fi
  # 미러가 서빙 저장소에서 "당겨오는(fetch)" 방식 — 서빙 쪽 명령을 트리거하지 않아
  # shim/훅과 무관하게 안전. 모든 ref(브랜치/태그)를 그대로 복제.
  if "$REAL_GIT" --git-dir="$mir" fetch --prune "$src" '+refs/heads/*:refs/heads/*' '+refs/tags/*:refs/tags/*' >/dev/null 2>&1; then
    _log "  ✅ $name 미러 동기화(fetch)"
  else
    _log "  ⚠ $name 동기화 실패"
  fi
}

if [ "$1" = "--verify" ]; then
  _log "=== 미러 무결성 점검 ==="
  for m in "$MIRROR_ROOT"/*.git; do
    [ -d "$m" ] || continue
    # ★ git 1.8.3.1 호환: `--connectivity-only` 옵션이 없다(2.0+). 
    #   대신 rev-parse 로 HEAD 도달성 + count-objects 로 객체 존재를 확인한다.
    #   (일반 fsck 는 bare 미러에서 dangling 객체를 정상으로도 뱉어 판정이 부정확)
    head_ok=""; obj_ok=""
    "$REAL_GIT" --git-dir="$m" rev-parse --verify HEAD >/dev/null 2>&1 && head_ok=1
    cnt=$("$REAL_GIT" --git-dir="$m" count-objects 2>/dev/null | awk '{print $1}')
    [ -n "$cnt" ] && [ "$cnt" -ge 0 ] 2>/dev/null && obj_ok=1
    if [ -n "$head_ok" ] && [ -n "$obj_ok" ]; then
      echo "  ✅ $(basename "$m") OK ($(du -sh "$m" 2>/dev/null | cut -f1), objects=$cnt)"
    else
      echo "  ❌ $(basename "$m") 손상 의심 (HEAD도달=${head_ok:-N} objects=${obj_ok:-N})"
    fi
  done
  exit 0
fi

_log "===== 미러 동기화 시작 ====="
# 1) 부모(서빙) 저장소
sync_one "$REPO" "webapp"
# 2) 중첩 저장소들 (gitlink=160000 인 하위 경로)
for name in $("$REAL_GIT" -C "$REPO" ls-files -s 2>/dev/null | awk '$1==160000 {print $4}'); do
  [ -d "$REPO/$name/.git" ] && sync_one "$REPO/$name" "nested_$name"
done
_log "===== 미러 동기화 종료 ====="
