#!/bin/bash
# =============================================================================
# git 보호 shim  (설치 위치: /usr/local/bin/git — PATH 우선)   [iamserver / kiam]
#   모든 `git` 호출을 가로채, /home/webapp (및 그 하위 중첩 git 저장소)
#   안에서 파괴적 명령을 방어한다.
#
#   [BLOCK]  clean / reflog expire·delete / gc --prune·--aggressive /
#            filter-branch / filter-repo            → 완전 차단
#   [SNAPSHOT] reset --hard/--merge/--keep, checkout -f, switch -f/--discard-changes,
#              restore -W, submodule -f/--checkout, rm, worktree remove/prune,
#              read-tree -u/--reset                 → 실행 직전 stash -u 자동 대피 후 진행
#   [WARN]   branch -D/-M, push -f/--force/--delete, update-ref -d → 경고만
#
#   ★ iamserver 이식판 (2026-07-01, 아리):
#     - REPO = /home/webapp (onlyopen 저장소)
#     - REAL_GIT = 기존 git-guard 독립 복사본(/usr/local/lib/git-guard/realgit/git)
#       → 없으면 /usr/libexec/git-core/git → /usr/bin/git 순으로 폴백
#     - ★git 1.8.3.1 호환: `git stash push -u -m` 미지원 → `git stash save -u --` 로 폴백
#     - 기존 Layer1/Layer3 clean 차단 정책을 흡수·확장 (clean 은 계속 완전차단)
#     - 중첩(nested) git 저장소 보호: 하위 사이트가 자체 .git 을 가지면 그 저장소를 대상으로 대피
#   그 외 명령은 그대로 통과. GitHub 통신과 무관하게 로컬에서만 방어.
#   원본(clean 전용) shim: _safety/snapshots/pre_defense_*/usr_local_bin_git.orig
# =============================================================================

# --- 진짜 git 바이너리 (절대 shim 을 재귀호출하지 않도록 실체 경로 사용) ------
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi

REPO=/home/webapp
SNAP_ROOT="$REPO/_safety/snapshots"
LOG="$REPO/_safety/logs/git-guard.log"

_ts()  { date '+%Y-%m-%d %H:%M:%S'; }
_log() { mkdir -p "$(dirname "$LOG")" 2>/dev/null; echo "[$(_ts)] pid=$$ user=${SUDO_USER:-$USER} pwd=$PWD :: $*" >> "$LOG" 2>/dev/null; }

sub="$1"

# 이 git 호출이 보호 대상 트리(/home/webapp) 안에 대한 것인지 판별
_in_repo() {
  case "$PWD/" in "$REPO"/*|"$REPO"/) return 0 ;; esac
  local prev=""
  for a in "$@"; do
    if [ "$prev" = "-C" ]; then
      case "$a/" in "$REPO"/*|"$REPO"/) return 0 ;; esac
    fi
    prev="$a"
  done
  return 1
}

# 대피(stash)를 실행할 "실제 대상 저장소"의 최상위 경로를 알아낸다.
#   - -C <path> 가 있으면 그 경로 기준, 없으면 현재 디렉토리 기준
#   - 중첩 저장소(예: /home/webapp/<site>) 안이면 그 저장소가 대상이 됨
#   ★ git 1.8.3.1 호환: 이 git 에는 `-C` 옵션이 없다(1.8.5+). 따라서
#     대상 디렉토리로 subshell 안에서 `cd` 한 뒤 rev-parse 를 실행한다.
_target_toplevel() {
  local dir="$PWD" prev=""
  for a in "$@"; do
    if [ "$prev" = "-C" ]; then dir="$a"; fi
    prev="$a"
  done
  ( cd "$dir" 2>/dev/null && "$REAL_GIT" rev-parse --show-toplevel 2>/dev/null ) || echo "$REPO"
}

# ---- 0) 보호 트리 밖이면 방어 로직 생략(진짜 git 그대로) --------------------
if ! _in_repo "$@"; then
  exec "$REAL_GIT" "$@"
fi

# ---- 1) 완전 차단 그룹 -----------------------------------------------------
_block() {
  echo "⛔ git $1 은(는) 차단됐습니다. (사유: $2)" >&2
  echo "   /home/webapp 유실 방어 정책. 꼭 필요하면 관리자가 $REAL_GIT 로 직접 실행하세요." >&2
  _log "BLOCKED: git $*"
  exit 1
}
case "$sub" in
  clean)
    _block "clean" "미추적 파일 삭제(대량 삭제 사고 이력)" "$@" ;;
  reflog)
    for a in "$@"; do case "$a" in expire|delete) _block "reflog $a" "복구 안전망(reflog) 제거" "$@";; esac; done ;;
  gc)
    for a in "$@"; do case "$a" in --prune=now|--prune=all|--aggressive) _block "gc $a" "도달불가 객체(=stash/reset 복구원본) 즉시 삭제" "$@";; esac; done ;;
  filter-branch)
    _block "filter-branch" "히스토리 파괴적 재작성" "$@" ;;
  filter-repo)
    _block "filter-repo" "히스토리 파괴적 재작성" "$@" ;;
esac
# clean 서브커맨드가 다른 위치에 와도(옵션 뒤) 차단 (보수적)
for arg in "$@"; do [ "$arg" = "clean" ] && _block "clean" "미추적 파일 삭제" "$@"; done

# ---- 2) 파괴적(파일 삭제/덮어쓰기 가능) 명령 감지 → 스냅샷 후 진행 -----------
danger=""
case "$sub" in
  reset)
    for a in "$@"; do case "$a" in --hard|--merge|--keep) danger="reset $a";; esac; done ;;
  checkout)
    for a in "$@"; do case "$a" in -f|--force) danger="checkout --force";; esac; done ;;
  switch)
    for a in "$@"; do case "$a" in -f|--force|--discard-changes) danger="switch --force";; esac; done ;;
  restore)
    for a in "$@"; do case "$a" in -W|--worktree) danger="restore --worktree";; esac; done ;;
  submodule)
    for a in "$@"; do case "$a" in -f|--force|--checkout) danger="submodule $a";; esac; done ;;
  rm)
    danger="rm" ;;
  worktree)
    for a in "$@"; do case "$a" in remove|prune) danger="worktree $a";; esac; done ;;
  read-tree)
    for a in "$@"; do case "$a" in -u|--reset) danger="read-tree $a";; esac; done ;;
esac

# ---- 2b) 경고만 하고 진행하는 명령 (참조 삭제/원격 덮어쓰기 — 파일 유실은 아님) --
case "$sub" in
  branch)
    for a in "$@"; do case "$a" in -D|-M) echo "⚠  [git-shim] '$sub $a' 실행 — 브랜치 참조 변경. 커밋은 reflog 에 남습니다." >&2; _log "WARN: git $*";; esac; done ;;
  push)
    for a in "$@"; do case "$a" in -f|--force|--delete|--force-with-lease) echo "⚠  [git-shim] 'push $a' 실행 — 원격을 덮어씁니다. 원격 이력 손실 주의." >&2; _log "WARN: git $*";; esac; done ;;
  update-ref)
    for a in "$@"; do case "$a" in -d) echo "⚠  [git-shim] 'update-ref -d' — 참조 삭제." >&2; _log "WARN: git $*";; esac; done ;;
esac

# ---- 3) 위험 명령 → 실행 전 자동 대피 (★ 대상 저장소 = 중첩 저장소 인식) ------
#   ★ git 1.8.3.1 호환: stash push 미지원 → stash save 로 폴백
_stash_untracked() {   # $1=대상저장소 경로, $2=메시지
  local repo="$1" msg="$2"
  # ★ git 1.8.3.1 호환: `-C` 옵션이 없으므로 subshell 에서 cd 후 실행.
  # 최신 git(2.13+): stash push -u -m
  if ( cd "$repo" 2>/dev/null && "$REAL_GIT" stash push -u -m "$msg" ) >/dev/null 2>&1; then
    return 0
  fi
  # 구형 git(1.8~2.12): stash save -u <msg>  (push 미지원)
  if ( cd "$repo" 2>/dev/null && "$REAL_GIT" stash save -u "$msg" ) >/dev/null 2>&1; then
    return 0
  fi
  return 1
}

if [ -n "$danger" ]; then
  TS="$(date +%Y%m%d_%H%M%S)"
  TARGET="$(_target_toplevel "$@")"
  echo "🛡  [git-shim] '$danger' 실행 전 안전 대피 중… (대상 저장소: $TARGET)" >&2
  _log "GUARD: 위험명령 [$danger] 감지 → 자동 대피 시작 (target=$TARGET)"
  # 대상 저장소(부모든 중첩이든)에 대해 stash -u 로 변경분+미추적 통째 대피
  if _stash_untracked "$TARGET" "git-shim auto [$danger] $TS"; then
    echo "   ✅ 변경/미추적 파일을 git stash 로 대피 완료. (repo: $TARGET)" >&2
    echo "   ↩ 복원: (해당 폴더에서) git stash list  →  git stash pop" >&2
    _log "GUARD: stash -u 대피 성공 (target=$TARGET)"
  else
    echo "   ℹ  대피할 변경/미추적 파일이 없거나 stash 불필요. 그대로 진행." >&2
    _log "GUARD: stash 불필요/실패 → 그대로 진행 (target=$TARGET)"
  fi
  echo "   ⚠ 파일이 사라졌다면: 해당 폴더에서 git stash list 확인 후 git stash pop 하세요." >&2
fi

# ---- 4) 실제 git 실행 ------------------------------------------------------
_log "EXEC: git $*"
exec "$REAL_GIT" "$@"
