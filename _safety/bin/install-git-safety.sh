#!/bin/bash
# =============================================================================
# install-git-safety.sh — Git 유실 방어 시스템 자동 설치 (STEP 2~4) [iamserver판]
#   사용법: sudo bash install-git-safety.sh <SVC_ROOT> <REPO_NAME> [REPOS_ROOT]
#   예시:  sudo bash install-git-safety.sh /home/webapp webapp /home/git-repos
#
#   ⚠ 실행 전 반드시 서비스 파일 백업(tar)을 먼저 수행할 것 (매뉴얼 STEP 1).
#   ⚠ 이 스크립트는 각 단계 검증에 실패하면 즉시 중단한다.
#
#   ── iamserver 조정 (CentOS 7 / git 1.8.3.1) ─────────────────────────────
#   * 진짜 git: /usr/local/lib/git-guard/realgit/git (폴백: libexec, /usr/bin/git)
#   * `.git` 본체 이동 대상 REPOS_ROOT 기본값 = /home/git-repos
#     (bigserver는 /var 였으나, iamserver는 /home 과 /var 가 파일시스템이 달라
#      같은 FS 인 /home/git-repos 로 옮겨야 mv 가 원자적·안전하다.)
#   * git 1.8.3.1: `-C` 없음 → 서브셸 cd, `stash push` 없음 → stash save,
#     `fsck --connectivity-only` 없음 → rev-parse+count-objects.
#   * /usr/local/bin/git 에 chattr +i 가 걸려 있으면 자동 해제 후 재설정.
#   상세 설명: _safety/GIT_SAFETY_MANUAL.md
# =============================================================================
set -euo pipefail

SVC_ROOT="${1:-}"
REPO_NAME="${2:-}"
REPOS_ROOT="${3:-/home/git-repos}"
MIRROR_ROOT="/var/git-mirrors"
SELF_DIR="$(cd "$(dirname "$0")" && pwd)"

# 진짜 git 결정
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi

die(){ echo "❌ $*" >&2; exit 1; }
ok(){  echo "✅ $*"; }
step(){ echo ""; echo "==== $* ===="; }

[ -n "$SVC_ROOT" ] && [ -n "$REPO_NAME" ] || die "사용법: install-git-safety.sh <SVC_ROOT> <REPO_NAME> [REPOS_ROOT]"
[ "$(id -u)" -eq 0 ] || die "root 권한으로 실행하세요 (sudo)."
[ -d "$SVC_ROOT" ]  || die "서비스 폴더가 없습니다: $SVC_ROOT"

# ---------------------------------------------------------------------------
step "STEP 0: 사전 점검"
[ -e "$SVC_ROOT/.git" ] || die "$SVC_ROOT/.git 가 없습니다. git 저장소가 맞습니까?"

BR="$( ( cd "$SVC_ROOT" && "$REAL_GIT" rev-parse --abbrev-ref HEAD ) 2>/dev/null || echo '?')"
HEAD0="$( ( cd "$SVC_ROOT" && "$REAL_GIT" rev-parse HEAD ) 2>/dev/null || echo '?')"
NFILES0="$( ( cd "$SVC_ROOT" && "$REAL_GIT" ls-files ) 2>/dev/null | wc -l)"
echo "  브랜치=$BR  HEAD=$HEAD0  추적파일수=$NFILES0"

FS_SVC="$(df -P "$SVC_ROOT"   | tail -1 | awk '{print $6}')"
FS_REPOS="$(df -P "$(dirname "$REPOS_ROOT")" 2>/dev/null | tail -1 | awk '{print $6}')"
echo "  서비스FS=$FS_SVC  저장소이동대상FS=$FS_REPOS ($REPOS_ROOT)"
[ "$FS_SVC" = "$FS_REPOS" ] || die "파일시스템이 다릅니다($FS_SVC vs $FS_REPOS). mv 가 느린 복사가 되어 위험 → REPOS_ROOT 를 같은 FS 로 지정하세요."
ok "사전 점검 완료 (동일 파일시스템 확인)"

# ---------------------------------------------------------------------------
step "STEP 2: git 보호 shim(v4) 설치"
[ -f "$SELF_DIR/git-shim.v4.sh" ] || die "$SELF_DIR/git-shim.v4.sh 가 없습니다."
mkdir -p "$SVC_ROOT/_safety/shim_backups"
if [ -f /usr/local/bin/git ]; then
  # chattr +i 걸려 있으면 해제
  chattr -i /usr/local/bin/git 2>/dev/null || true
  cp /usr/local/bin/git "$SVC_ROOT/_safety/shim_backups/git.prev.$(date +%Y%m%d_%H%M%S).bak"
  ok "기존 git(shim) 백업 + immutable 해제"
fi
cp "$SELF_DIR/git-shim.v4.sh" /usr/local/bin/git
sed -i "s#^REPO=.*#REPO=$SVC_ROOT#" /usr/local/bin/git
chmod +x /usr/local/bin/git
hash -r 2>/dev/null || true
FIRST_GIT="$(which -a git | head -1)"
[ "$FIRST_GIT" = "/usr/local/bin/git" ] || die "PATH 우선순위 오류: $FIRST_GIT (/usr/local/bin 이 앞이어야 함)"
# 차단 동작 검증: reset --hard 가 반드시 실패(=차단)해야 함
if ( cd "$SVC_ROOT" && /usr/local/bin/git reset --hard HEAD ) >/dev/null 2>&1; then
  die "shim 검증 실패: reset --hard 가 차단되지 않았습니다!"
fi
# shim 자기보호 (immutable)
chattr +i /usr/local/bin/git 2>/dev/null || true
ok "shim(v4) 설치 + 차단 검증 + immutable(chattr +i) 완료"

# ---------------------------------------------------------------------------
step "STEP 3: 저장소 본체를 서비스 폴더 밖으로 분리"
mkdir -p "$REPOS_ROOT"
if [ -d "$SVC_ROOT/.git" ]; then
  if [ -e "$REPOS_ROOT/${REPO_NAME}.git" ]; then
    mv "$REPOS_ROOT/${REPO_NAME}.git" "$REPOS_ROOT/${REPO_NAME}.git.old_$(date +%Y%m%d_%H%M%S)"
  fi
  mv "$SVC_ROOT/.git" "$REPOS_ROOT/${REPO_NAME}.git"
  echo "gitdir: $REPOS_ROOT/${REPO_NAME}.git" > "$SVC_ROOT/.git"
  ok "본체 이동 완료 ($REPOS_ROOT/${REPO_NAME}.git)"
else
  echo "  ℹ .git 이 이미 포인터 파일입니다. 분리 상태로 간주하고 설정만 확인합니다."
fi
"$REAL_GIT" --git-dir="$REPOS_ROOT/${REPO_NAME}.git" config core.worktree "$SVC_ROOT"
"$REAL_GIT" --git-dir="$REPOS_ROOT/${REPO_NAME}.git" config core.bare false
"$REAL_GIT" --git-dir="$REPOS_ROOT/${REPO_NAME}.git" config core.untrackedCache true 2>/dev/null || true

# 검증 (git 1.8.3.1: -C 없음 → 서브셸 cd)
TOP="$( ( cd "$SVC_ROOT" && "$REAL_GIT" rev-parse --show-toplevel ) )"
GDIR="$( ( cd "$SVC_ROOT" && "$REAL_GIT" rev-parse --git-dir ) )"
HEAD1="$( ( cd "$SVC_ROOT" && "$REAL_GIT" rev-parse HEAD ) )"
NFILES1="$( ( cd "$SVC_ROOT" && "$REAL_GIT" ls-files ) | wc -l)"
[ "$TOP" = "$SVC_ROOT" ]                          || die "worktree 불일치: $TOP"
case "$GDIR" in
  "$REPOS_ROOT/${REPO_NAME}.git"|"$SVC_ROOT/.git") : ;;
  *) die "git-dir 불일치: $GDIR" ;;
esac
[ "$HEAD1" = "$HEAD0" ]                            || die "HEAD 변경됨! $HEAD0 -> $HEAD1"
[ "$NFILES1" = "$NFILES0" ]                        || echo "  ⚠ 추적파일수 변화: $NFILES0 -> $NFILES1 (확인 요망)"
ok "분리 검증 완료 (HEAD/파일수 일치)"

# ---------------------------------------------------------------------------
step "STEP 4: 백업 미러 + 자동 동기화"
mkdir -p "$MIRROR_ROOT" "$SVC_ROOT/_safety/logs"
if [ ! -d "$MIRROR_ROOT/${REPO_NAME}.git" ]; then
  "$REAL_GIT" clone --mirror "$REPOS_ROOT/${REPO_NAME}.git" "$MIRROR_ROOT/${REPO_NAME}.git"
else
  "$REAL_GIT" --git-dir="$REPOS_ROOT/${REPO_NAME}.git" push --mirror "$MIRROR_ROOT/${REPO_NAME}.git" || true
fi
ok "미러 생성/갱신 완료"

if [ -f "$SELF_DIR/mirror-sync.sh" ]; then
  CRON_LINE="*/30 * * * * $SELF_DIR/mirror-sync.sh >> $SVC_ROOT/_safety/logs/mirror.log 2>&1  # git 미러 자동백업"
  ( crontab -l 2>/dev/null | grep -v "mirror-sync.sh" ; echo "$CRON_LINE" ) | crontab -
  ok "미러 동기화 크론 등록"
else
  echo "  ⚠ mirror-sync.sh 없음 → 크론 수동 등록 필요"
fi

# ---------------------------------------------------------------------------
step "완료 — 최종 상태"
echo "  서비스폴더 : $SVC_ROOT"
echo "  .git 포인터: $(cat "$SVC_ROOT/.git")"
echo "  저장소본체 : $REPOS_ROOT/${REPO_NAME}.git"
echo "  백업미러   : $MIRROR_ROOT/${REPO_NAME}.git"
echo "  shim       : /usr/local/bin/git (v4, immutable)"
echo ""
ok "Git 유실 방어 시스템 설치 완료. 매뉴얼 STEP 5 통합검증(서비스 curl 등)을 이어서 수행하세요."
