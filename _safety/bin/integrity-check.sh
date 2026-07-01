#!/bin/bash
# =============================================================================
# integrity-check — 사이트 진입점(index) 존재 여부 조기경보   [iamserver / kiam]
#   git checkout/merge/pull/reset 이후 호출되어, 각 사이트의 진입 파일이
#   갑자기 사라졌는지 검사한다. 사라졌으면 경고 로그 + 알림 파일 생성.
#   (삭제를 되돌리진 않는다 — 이미 방어막1 shim 이 대피시키므로, 여긴 "감지/경보" 역할)
#   기준 목록: _safety/site_index_manifest.txt  (없으면 생성)
#   이식: 아리 2026-07-01  (REPO=/home/webapp)
# =============================================================================
REPO=/home/webapp
MANIFEST="$REPO/_safety/site_index_manifest.txt"
LOG="$REPO/_safety/logs/integrity.log"
ALERT="$REPO/_safety/ALERT_missing_index.txt"
mkdir -p "$(dirname "$LOG")" 2>/dev/null
_ts(){ date '+%Y-%m-%d %H:%M:%S'; }

# 매니페스트가 없으면 현재 상태로 최초 생성 (각 사이트 루트의 대표 진입점)
if [ ! -f "$MANIFEST" ]; then
  : > "$MANIFEST"
  for d in "$REPO"/*/; do
    site="$(basename "$d")"
    case "$site" in _safety|docs|.git|node_modules|_bigserver_safety_ref) continue ;; esac
    for idx in index.php index.html public/index.php public/index.html app.py main.py server.js; do
      if [ -f "$d$idx" ]; then echo "$site/$idx" >> "$MANIFEST"; break; fi
    done
  done
  echo "[$(_ts)] manifest 최초 생성: $(wc -l < "$MANIFEST")개 진입점" >> "$LOG"
  exit 0
fi

# 검사: 매니페스트의 진입점이 지금도 존재하는가
missing=0
: > "$ALERT.tmp"
while IFS= read -r rel; do
  [ -z "$rel" ] && continue
  if [ ! -e "$REPO/$rel" ]; then
    echo "MISSING: $rel" >> "$ALERT.tmp"
    missing=$((missing+1))
  fi
done < "$MANIFEST"

if [ "$missing" -gt 0 ]; then
  {
    echo "==================== 🚨 무결성 경보 ($(_ts)) ===================="
    echo "다음 사이트 진입점이 사라졌습니다 ($missing건). 방어막1 stash 를 확인하세요:"
    echo "   git stash list   →   git stash pop"
    cat "$ALERT.tmp"
  } | tee -a "$LOG" > "$ALERT"
  echo "🚨 [integrity] 사이트 진입점 $missing건 소실 감지! → $ALERT 확인, git stash pop 로 복원 가능" >&2
else
  rm -f "$ALERT" 2>/dev/null
  echo "[$(_ts)] integrity OK (모든 진입점 존재)" >> "$LOG"
fi
rm -f "$ALERT.tmp" 2>/dev/null
exit 0
