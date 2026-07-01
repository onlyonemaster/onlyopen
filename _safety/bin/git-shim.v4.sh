#!/bin/bash
# =============================================================================
# git 보호 shim  v4  (설치 위치: /usr/local/bin/git — PATH 우선)  [iamserver판]
#   모든 `git` 호출을 가로채, /home/webapp (및 그 하위 중첩 git 저장소)
#   안에서 파괴적 명령을 방어한다.
#
#   [BLOCK]  clean / reflog expire·delete / gc --prune·--aggressive /
#            filter-branch / filter-repo            → 완전 차단
#
#   ★ v4 (2026-07-02): "서버 파일을 지우는 git 명령"을 대피 후 실행이 아니라
#     ★ 실행 자체 거부(BLOCK)★ 로 승격. (사장님 지시: git이 서비스 파일을 못 지우게)
#     대상: reset --hard/--merge/--keep, checkout -f, switch -f/--discard-changes,
#           restore -W/--worktree, submodule -f/--checkout,
#           rm, worktree remove/prune, read-tree -u/--reset
#     → 거부 직전에 만약을 위해 stash -u 스냅샷은 남긴다(복구원본 확보).
#     꼭 필요하면 관리자가 GITSHIM_ALLOW=1 를 붙이거나 진짜 git 으로 직접 실행.
#
#   [WARN]   branch -D/-M, push -f/--force/--delete, update-ref -d → 경고만
#
#   v3 (2026-07-01): 중첩(nested) git 저장소 보호. 그대로 유지.
#   그 외 명령은 그대로 통과. GitHub 통신과 무관하게 로컬에서만 방어.
#
#   ── iamserver 조정 (CentOS 7 / git 1.8.3.1) ─────────────────────────────
#   * REPO=/home/webapp
#   * REAL_GIT 폴백: /usr/local/lib/git-guard/realgit/git → /usr/libexec/git-core/git → /usr/bin/git
#   * git 1.8.3.1 호환: `stash push` 없음 → `stash save` 폴백,
#     `-C <path>` 없음(1.8.5+) → 서브셸 ( cd DIR && git ... ) 사용.
# =============================================================================

# --- 진짜 git 경로 결정 (shim 자기 자신은 절대 호출하지 않도록 폴백) ---------
REAL_GIT=""
for cand in /usr/local/lib/git-guard/realgit/git /usr/libexec/git-core/git /usr/bin/git; do
  if [ -x "$cand" ]; then REAL_GIT="$cand"; break; fi
done
[ -n "$REAL_GIT" ] || REAL_GIT=/usr/bin/git

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
# git 1.8.3.1 에는 `-C` 옵션이 없으므로 서브셸 cd 로 대체.
_target_toplevel() {
  local dir="$PWD" prev=""
  for a in "$@"; do
    if [ "$prev" = "-C" ]; then dir="$a"; fi
    prev="$a"
  done
  ( cd "$dir" 2>/dev/null && "$REAL_GIT" rev-parse --show-toplevel 2>/dev/null ) || echo "$REPO"
}

# 대상 저장소에서 stash 스냅샷 (git 1.8.3.1: stash push 없음 → stash save 폴백)
_stash_snapshot() {  # $1=repo path, $2=message
  local repo="$1" msg="$2"
  if ( cd "$repo" 2>/dev/null && "$REAL_GIT" stash push -u -m "$msg" ) >/dev/null 2>&1; then return 0; fi
  if ( cd "$repo" 2>/dev/null && "$REAL_GIT" stash save -u "$msg" )    >/dev/null 2>&1; then return 0; fi
  return 1
}
_stash_pop() {  # $1=repo path
  ( cd "$1" 2>/dev/null && "$REAL_GIT" stash pop ) >/dev/null 2>&1
}

# ---- 0) 보호 트리 밖이면 방어 로직 생략(진짜 git 그대로) --------------------
if ! _in_repo "$@"; then
  exec "$REAL_GIT" "$@"
fi

# ---- 1) 완전 차단 그룹 -----------------------------------------------------
_block() {
  echo "⛔ git $1 은(는) 차단됐습니다. (사유: $2)" >&2
  echo "   /home/webapp 유실 방어 정책. 꼭 필요하면 관리자가 $REAL_GIT 로 직접 실행하세요." >&2
  _log "BLOCKED: git ${*:3}"
  exit 1
}

case "$sub" in
  clean)
    _block "clean" "미추적 파일 삭제(대량 유실 사고 이력)" "$@" ;;
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

# ---- 2) 파괴적(파일 삭제/덮어쓰기 가능) 명령 감지 --------------------------
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

# ---- 3) ★v4★ 위험 명령 → 스냅샷 남기고 "실행 자체 거부" ---------------------
if [ -n "$danger" ]; then
  TS="$(date +%Y%m%d_%H%M%S)"
  TARGET="$(_target_toplevel "$@")"

  # 관리자 명시적 허용 탈출구 (환경변수 GITSHIM_ALLOW=1)
  if [ "$GITSHIM_ALLOW" = "1" ]; then
    echo "🔓 [git-shim] GITSHIM_ALLOW=1 감지 — '$danger' 예외 실행 허용 (관리자 책임)." >&2
    _log "OVERRIDE: GITSHIM_ALLOW=1 → 위험명령 [$danger] 예외 실행 (target=$TARGET) : git $*"
    # 예외 실행 전에도 안전 대피는 남긴다 (git 1.8.3.1 호환 stash)
    _stash_snapshot "$TARGET" "git-shim override [$danger] $TS"
    _log "EXEC(override): git $*"
    exec "$REAL_GIT" "$@"
  fi

  # 기본 정책: 거부. 단, 거부 전에 복구원본 스냅샷(stash)을 남겨 안전망 확보.
  echo "⛔ [git-shim v4] '$danger' 는(은) 차단됐습니다." >&2
  echo "   사유: 이 명령은 서버(/home/webapp)의 실제 파일을 삭제/덮어쓸 수 있습니다." >&2
  echo "   서비스 파일 유실 방어 정책(사장님 지시)에 따라 실행을 거부합니다." >&2
  _log "GUARD-v4: 위험명령 [$danger] 차단 (target=$TARGET) : git $*"

  # 만일을 대비해 현재 작업트리 상태를 stash 로 스냅샷 후 즉시 pop
  #  → 스냅샷(=복구원본)은 stash reflog 에 남고, 작업트리는 원상복구(파일 안 사라짐).
  if _stash_snapshot "$TARGET" "git-shim v4 blocked-snapshot [$danger] $TS"; then
    _stash_pop "$TARGET"
    echo "   ℹ  현재 상태 스냅샷을 안전 기록했습니다(작업트리는 그대로 유지)." >&2
    _log "GUARD-v4: blocked-snapshot 기록 완료 (target=$TARGET)"
  fi

  echo "" >&2
  echo "   ▶ 정말 실행이 필요하면 (관리자 책임):" >&2
  echo "       GITSHIM_ALLOW=1 git $*" >&2
  echo "     또는  $REAL_GIT $*" >&2
  exit 1
fi

# ---- 4) 실제 git 실행 ------------------------------------------------------
_log "EXEC: git $*"
exec "$REAL_GIT" "$@"
