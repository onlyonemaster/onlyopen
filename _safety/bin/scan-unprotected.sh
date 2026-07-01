#!/bin/bash
# =============================================================================
# scan-unprotected — "진짜 보호 안 되는 소스 파일" 스캐너 (방어막3 진단) [iamserver]
#   각 사이트에서: (미추적) AND (gitignore로도 무시 안 됨) AND (런타임 산출물 아님)
#   인 파일을 찾아낸다. 이 파일들이 reset --hard 시 소실될 진짜 위험 대상.
#   런타임 산출물(__pycache__, *.pyc, *.log, backups, node_modules 등)은 제외 표시.
#   사용: scan-unprotected.sh [site]   (site 생략 시 전 사이트)
#   이식: 아리 2026-07-01  (REPO=/home/webapp)
# =============================================================================
REPO=/home/webapp
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then RG_BIN="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then RG_BIN="/usr/libexec/git-core/git"
else                                                     RG_BIN="/usr/bin/git"
fi
# core.quotePath=false: 한글 등 UTF-8 경로를 8진 이스케이프 없이 그대로 출력
GIT="$RG_BIN -c core.quotePath=false"
cd "$REPO" || exit 1

# find 단계에서 아예 들어가지 않을(프루닝) 런타임 디렉토리
PRUNE=( -name .git -o -name __pycache__ -o -name node_modules -o -name venv \
        -o -name vendor -o -name logs -o -name backups -o -name cache \
        -o -name .cache -o -name tmp -o -name WebView2 -o -name EBWebView \
        -o -name .venv -o -name dist -o -name build )

# 파일명 기준 런타임 산출물(=보호 불필요) 패턴
is_runtime() {
  case "$1" in
    *.pyc|*.pyo|*.log|*.lock|*.sqlite-journal|*.map|*.min.js|*.min.css) return 0 ;;
  esac
  return 1
}

scan_site() {
  local site="$1"
  [ -d "$site" ] || return
  # gitlink 는 건너뜀 (서브모듈 별도 처리)
  if [ "$($GIT ls-files -s "$site" 2>/dev/null | head -1 | awk '{print $1}')" = "160000" ]; then
    echo "[$site] 🔗gitlink — 서브모듈(별도처리)"; return
  fi
  # 1) 디스크의 실제 파일(런타임 디렉토리 프루닝) 목록
  local disk_list tracked_list untracked
  disk_list=$(find "$site" \( "${PRUNE[@]}" \) -prune -o -type f -print 2>/dev/null | sed 's#^\./##' | sort -u)
  # 2) git 추적 파일 목록
  tracked_list=$($GIT ls-files "$site" 2>/dev/null | sort -u)
  # 3) 미추적 = disk - tracked
  untracked=$(comm -23 <(printf '%s\n' "$disk_list") <(printf '%s\n' "$tracked_list"))
  [ -z "$untracked" ] && return
  # 4) gitignore로 무시되는 것 일괄 제거 (check-ignore --stdin: 무시되는 것만 출력)
  local ignored not_ignored
  ignored=$(printf '%s\n' "$untracked" | $GIT check-ignore --stdin 2>/dev/null | sort -u)
  not_ignored=$(comm -23 <(printf '%s\n' "$untracked" | sort -u) <(printf '%s\n' "$ignored"))
  [ -z "$not_ignored" ] && return
  # 5) 런타임 파일명 필터 + 시크릿 분류
  local src=0 rt=0 secret=0 out=""
  while read -r f; do
    [ -z "$f" ] && continue
    if is_runtime "$f"; then rt=$((rt+1)); continue; fi
    case "$f" in
      *.env|*/.env|*secret*|*credential*|*.pem|*.key|*password*)
        secret=$((secret+1)); out+="   🔑 $f\n"; continue ;;
    esac
    src=$((src+1)); out+="   📄 $f\n"
  done < <(printf '%s\n' "$not_ignored")
  if [ "$src" -gt 0 ] || [ "$secret" -gt 0 ]; then
    echo "[$site] 🔴 보호안됨 소스 ${src}개, 시크릿 ${secret}개 (런타임산출물 ${rt}개 제외)"
    [ -n "${VERBOSE:-}" ] && echo -e "$out"
  else
    [ -n "${VERBOSE:-}" ] && echo "[$site] 🟢 보호안된 소스 없음 (런타임산출물 ${rt}개만 미추적)"
  fi
}

if [ -n "$1" ]; then
  VERBOSE=1 scan_site "$1"
else
  for d in */; do
    site="${d%/}"
    case "$site" in _safety|docs|node_modules|_bigserver_safety_ref) continue ;; esac
    scan_site "$site"
  done
fi
