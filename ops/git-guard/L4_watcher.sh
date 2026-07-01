#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  Layer 4 방어막 — 신규 디렉터리 실시간 자동 .gitignore 등록 watcher
#  inotifywait 으로 REPO_ROOT 를 감시하다가 새 폴더/이동이 생기면
#  즉시 protect_gitignore.sh 를 호출해 .gitignore 에 등록한다.
#  → 사람이 깜빡해도 새 사이트 솔루션이 git clean 으로 삭제되지 않음.
# ════════════════════════════════════════════════════════════════
set -u
REPO_ROOT="${1:-/home/webapp}"
PROTECT="/usr/local/lib/git-guard/protect_gitignore.sh"
LOG="/var/log/webapp-gitignore-watcher.log"
LOGTAG="git-guard-watcher"

log() { echo "$(date '+%F %T') $*" >> "$LOG" 2>/dev/null; logger -t "$LOGTAG" "$*" 2>/dev/null; }

log "watcher 시작 — repo=$REPO_ROOT"

# 시작 시 1회 초기 스캔으로 기존 미등록 폴더 보정
"$PROTECT" "$REPO_ROOT" >> "$LOG" 2>&1
log "초기 스캔 완료"

# 디바운스: 짧은 시간 내 다수 이벤트를 한 번으로 묶기
LAST_RUN=0
debounce_run() {
    now=$(date +%s)
    if [ $((now - LAST_RUN)) -ge 2 ]; then
        "$PROTECT" "$REPO_ROOT" >> "$LOG" 2>&1
        LAST_RUN=$now
        log "이벤트 처리 — .gitignore 갱신"
    fi
}

# REPO_ROOT 최상위에서 디렉터리 생성/이동만 감시 (재귀 X → 부하 최소)
# create, moved_to = 새 폴더 등장 / isdir 필터
inotifywait -m -e create -e moved_to --format '%e %f' "$REPO_ROOT" 2>>"$LOG" | \
while read -r event fname; do
    case "$event" in
        *ISDIR*)
            log "신규 디렉터리 감지: $fname ($event)"
            debounce_run
            ;;
    esac
done
