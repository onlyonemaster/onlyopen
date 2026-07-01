#!/bin/bash
# =============================================================================
# protect-nested — 중첩(nested) git 저장소 안의 "미추적 소스"를 안전 커밋 (방어막3-B) [iamserver]
#
#   /home/webapp 아래에 자체 .git 을 가진 사이트(=중첩 저장소)가 있으면,
#   그 안의 미추적 파일은 부모 저장소가 보호하지 못하고, 해당 저장소에서
#   git reset --hard 시 소실될 수 있다. (shim 이 stash 로 1차 대피하지만,
#   커밋으로 "이력"에 남겨두는 것이 근본 보호다.)
#
#   시크릿(.env/.pem/.key)·대용량(>5MB)·백업/런타임 폴더는 자동 제외.
#
#   사용:
#     protect-nested.sh            # 진단만(무엇을 커밋/제외할지 미리보기)
#     protect-nested.sh <site>     # 특정 사이트만 진단
#     protect-nested.sh --commit [site]   # 실제 커밋 수행
#   ★ git 1.8.3.1 호환: --pathspec-from-file 미지원 → 개별 add 폴백
#   이식: 아리 2026-07-01  (REPO=/home/webapp)
# =============================================================================
REPO=/home/webapp
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then RG_BIN="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then RG_BIN="/usr/libexec/git-core/git"
else                                                     RG_BIN="/usr/bin/git"
fi
GIT="$RG_BIN -c core.quotePath=false"
cd "$REPO" || exit 1

NESTED=$($RG_BIN ls-files -s 2>/dev/null | awk '$1==160000 {print $4}')

DO_COMMIT=""; ONLY=""
for a in "$@"; do
  case "$a" in
    --commit) DO_COMMIT=1 ;;
    *) ONLY="$a" ;;
  esac
done

# 제외 판정: 시크릿 / 런타임 / 백업 / 대용량
_excluded() {
  local f="$1" full="$2"
  case "$f" in
    .env|.env.*|*/.env|*/.env.*|*secret*|*credential*|*.pem|*.crt|*.key|*password*|*id_rsa*) echo "🔑secret"; return 0 ;;
    backup/*|backups/*|*/backup/*|*/backups/*|*.bak|*.bak_*|*_backup*|*.backup*|\
    __pycache__/*|*/__pycache__/*|*.pyc|node_modules/*|*/node_modules/*|\
    venv/*|*/venv/*|.venv/*|*/.venv/*|dist/*|*/dist/*|build/*|*/build/*|\
    *.log|logs/*|*/logs/*|cache/*|*/cache/*|tmp/*|*/tmp/*|*.lock) echo "⚙runtime/backup"; return 0 ;;
  esac
  local sz; sz=$(stat -c%s "$full" 2>/dev/null || echo 0)
  if [ "$sz" -gt 5242880 ]; then echo "💾large($((sz/1048576))MB)"; return 0; fi
  return 1
}

process_site() {
  local s="$1"
  [ -d "$s/.git" ] || { echo "[$s] .git 없음(스킵)"; return; }
  cd "$REPO/$s" || return
  local untracked; untracked=$($GIT ls-files --others --exclude-standard 2>/dev/null)
  if [ -z "$untracked" ]; then echo "[$s] 🟢 미추적 소스 없음"; cd "$REPO"; return; fi
  local add=() skip=0 srcn=0
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    if reason=$(_excluded "$f" "$REPO/$s/$f"); then skip=$((skip+1)); continue; fi
    add+=("$f"); srcn=$((srcn+1))
  done <<< "$untracked"
  echo "[$s] 소스 ${srcn}개 커밋대상 / 제외 ${skip}개(시크릿·백업·런타임·대용량)"
  if [ -n "$DO_COMMIT" ] && [ "$srcn" -gt 0 ]; then
    # git 1.8.3.1: --pathspec-from-file 미지원 → 개별 add
    for f in "${add[@]}"; do $GIT add "$f" 2>/dev/null; done
    $GIT commit -m "chore(safety): 방어막3-B 미추적 소스 보호 커밋 ($(date +%Y-%m-%d))" >/dev/null 2>&1 \
      && echo "   ✅ ${srcn}개 커밋 완료 (repo: $s)" \
      || echo "   ℹ 커밋할 변경 없음/실패 (repo: $s)"
  fi
  cd "$REPO"
}

if [ -n "$ONLY" ]; then
  process_site "$ONLY"
else
  for s in $NESTED; do process_site "$s"; done
fi
