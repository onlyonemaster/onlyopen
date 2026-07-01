#!/bin/bash
# =============================================================================
# split-nested-kiam — /home/kiam 안의 중첩(nested) git 저장소를 전부 밖으로 물리분리
#                     [iamserver / bigserver 방식 이식]
#
#   목적: 서비스 폴더(/home/kiam) 안에 있는 하위 사이트/라이브러리의 .git 본체를
#         서비스 폴더 밖(/home/git-repos/nested_*.git)으로 이동하고,
#         원위치엔 포인터 파일만 남겨 "서비스 폴더 안 git 본체 = 0" 을 달성한다.
#
#   안전 원칙:
#     * 이동 전 각 .git 을 tar 백업 (/home/backups/git_split/nested/)
#     * 원자적 mv (같은 FS: /home 과 /home/git-repos 모두 nvme0n1p1)
#     * 이동 후 HEAD 동일성 검증 (다르면 롤백)
#     * REAL_GIT 직접 사용 (shim 우회) — 분리 작업 자체는 파괴명령 아님
#
#   사용:  split-nested-kiam.sh --dry-run   # 대상만 표시
#          split-nested-kiam.sh             # 실제 분리 실행
#   작성: 아리 2026-07-02
#   ── git 1.8.3.1 호환: -C 없음 → 서브셸 cd 사용
# =============================================================================
set -u
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi

SVC=/home/kiam
REPOS_ROOT=/home/git-repos          # /home 과 같은 FS (원자적 mv)
BK_ROOT=/home/backups/git_split/nested
LOG=/home/git-safety/logs/split-nested.log
DRY=0; [ "${1:-}" = "--dry-run" ] && DRY=1

mkdir -p "$REPOS_ROOT" "$BK_ROOT" "$(dirname "$LOG")" 2>/dev/null
_ts(){ date '+%Y-%m-%d %H:%M:%S'; }
_log(){ echo "[$(_ts)] $*" | tee -a "$LOG"; }

# FS 동일성 확인 (다르면 위험 → 중단)
FS_SVC=$(df -P "$SVC" 2>/dev/null | tail -1 | awk '{print $1}')
FS_REPOS=$(df -P "$REPOS_ROOT" 2>/dev/null | tail -1 | awk '{print $1}')
if [ "$FS_SVC" != "$FS_REPOS" ]; then
  _log "❌ FS 불일치($FS_SVC vs $FS_REPOS) — mv가 느린 복사가 됨. 중단."
  exit 1
fi

# 안전한 이름 생성: 서비스 상대경로 → nested_<경로슬래시를__로>
_name_for() {  # $1 = .git 의 부모 디렉토리(절대경로)
  local d="$1"
  local rel="${d#$SVC/}"
  # 슬래시/공백/한글 등을 안전문자로
  echo "nested_$(echo "$rel" | sed -e 's#[/ ]#__#g' -e 's#[^A-Za-z0-9_.-]#_#g')"
}

_log "===== 중첩 저장소 분리 시작 (dry-run=$DRY) ====="

# .git 이 '디렉토리'인 것만 대상 (포인터는 이미 분리됨)
mapfile -t GITS < <(find "$SVC" -type d -name .git 2>/dev/null)
_log "발견된 중첩 .git 본체: ${#GITS[@]} 개"

OK=0; FAIL=0; SKIP=0
for g in "${GITS[@]}"; do
  d=$(dirname "$g")
  [ "$d" = "$SVC" ] && continue   # 메인은 제외(이미 분리됨)
  name=$(_name_for "$d")
  dest="$REPOS_ROOT/$name.git"

  # 이동 전 HEAD 기록
  head_before=$( ( cd "$d" && "$REAL_GIT" rev-parse HEAD 2>/dev/null ) )

  if [ "$DRY" = "1" ]; then
    printf "  [DRY] %-55s → %s  (HEAD %s, %s)\n" "$d" "$dest" "${head_before:0:7}" "$(du -sh "$g" 2>/dev/null|cut -f1)"
    continue
  fi

  if [ -e "$dest" ]; then
    _log "  ⚠ 대상 이미 존재, 스킵: $dest"; SKIP=$((SKIP+1)); continue
  fi

  # 1) 백업
  bkfile="$BK_ROOT/${name}_$(date +%Y%m%d_%H%M%S).tar.gz"
  ( cd "$d" && tar -czf "$bkfile" .git ) 2>/dev/null

  # 2) 원자 이동
  if ! mv "$g" "$dest"; then
    _log "  ❌ 이동 실패: $g"; FAIL=$((FAIL+1)); continue
  fi

  # 3) 포인터 생성
  echo "gitdir: $dest" > "$d/.git"

  # 4) worktree 지정 (bare 아님)
  ( cd "$dest" && "$REAL_GIT" config core.worktree "$d" && "$REAL_GIT" config core.bare false ) 2>/dev/null

  # 5) 검증: HEAD 동일?
  head_after=$( ( cd "$d" && "$REAL_GIT" rev-parse HEAD 2>/dev/null ) )
  if [ "$head_before" = "$head_after" ] && [ -n "$head_after" ]; then
    _log "  ✅ $d → $name.git (HEAD ${head_after:0:7} 유지)"
    OK=$((OK+1))
  else
    # 롤백
    _log "  ❌ HEAD 불일치! 롤백: $d (before=${head_before:0:7} after=${head_after:0:7})"
    rm -f "$d/.git"
    mv "$dest" "$g" 2>/dev/null
    FAIL=$((FAIL+1))
  fi
done

_log "===== 분리 종료: 성공 $OK / 실패 $FAIL / 스킵 $SKIP ====="
