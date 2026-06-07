#!/bin/bash
# ════════════════════════════════════════════════════════════════
#  Layer 2 방어막 핵심 로직 — .gitignore 자동 등록기
#  (Layer 4 watcher 와 수동 실행 모두 이 스크립트를 공유한다)
#
#  동작:
#   1. REPO_ROOT 의 모든 top-level 디렉터리를 스캔
#   2. git에 tracked 되지 않은(=git clean -fd 로 삭제 위험) 디렉터리를
#      .gitignore 에 'dir/' 형태로 자동 등록 (중복 방지)
#   3. 보호 섹션 헤더 아래에 정렬·누적
#
#  사용:  protect_gitignore.sh [REPO_ROOT]   (기본 /home/webapp)
# ════════════════════════════════════════════════════════════════
set -u

REPO_ROOT="${1:-/home/webapp}"
GITIGNORE="$REPO_ROOT/.gitignore"
REAL_GIT="/usr/bin/git"
MARKER="# === [AUTO-PROTECT] git clean 방어용 디렉터리 자동 등록 (수정 금지) ==="
LOGTAG="git-guard-protect"

cd "$REPO_ROOT" 2>/dev/null || { echo "repo root 없음: $REPO_ROOT" >&2; exit 1; }

# .gitignore 없으면 생성
[ -f "$GITIGNORE" ] || touch "$GITIGNORE"

# 보호 섹션 마커가 없으면 추가
if ! grep -qF "$MARKER" "$GITIGNORE"; then
    {
        echo ""
        echo "$MARKER"
    } >> "$GITIGNORE"
fi

added=0
# top-level 디렉터리만 순회 (.git, node_modules 제외)
for d in */ ; do
    dir="${d%/}"
    [ -z "$dir" ] && continue
    case "$dir" in
        .git|node_modules) continue ;;
    esac

    # 이미 .gitignore 에 'dir/' 가 등록돼 있으면 skip
    if grep -qxF "${dir}/" "$GITIGNORE"; then
        continue
    fi

    # git에 tracked 파일이 있는 디렉터리는 git clean 대상이 아니므로
    # .gitignore 에 넣으면 오히려 추적에 혼란 → 등록하지 않음.
    # (단, 안전을 위해 tracked 0개인 경우만 ignore 등록)
    tracked_count=$("$REAL_GIT" ls-files "$dir" 2>/dev/null | head -1 | wc -l)
    if [ "$tracked_count" -eq 0 ]; then
        echo "${dir}/" >> "$GITIGNORE"
        added=$((added+1))
        logger -t "$LOGTAG" "auto-added ${dir}/ to .gitignore" 2>/dev/null
    fi
done

# ── 2차 패스: 중첩(nested) 미추적 경로까지 완전 차단 ──────────────
# git clean -fdn 이 "지우겠다"고 보고하는 모든 경로(중첩 디렉터리·파일
# 포함)를 그대로 .gitignore 에 등록한다. → bigserver 의 top-level 한정
# 사각지대를 제거하는 핵심 보강 로직.
while IFS= read -r path; do
    [ -z "$path" ] && continue
    # 따옴표/공백 정리
    path="${path#\"}"; path="${path%\"}"
    # 이미 등록돼 있으면 skip
    if grep -qxF "/$path" "$GITIGNORE" || grep -qxF "$path" "$GITIGNORE"; then
        continue
    fi
    # 루트 기준 절대 경로 패턴(/...)으로 등록해 의도치 않은 광역매칭 방지
    echo "/$path" >> "$GITIGNORE"
    added=$((added+1))
    logger -t "$LOGTAG" "auto-added nested /$path to .gitignore" 2>/dev/null
done < <("$REAL_GIT" clean -fdn 2>/dev/null | sed -n 's/^Would remove //p')

# 보호 섹션 내부를 정렬·중복 제거 (마커 다음 줄부터)
# (간단·안전하게: 마커 이후 라인만 sort -u 로 정리)
tmp="$(mktemp)"
awk -v marker="$MARKER" '
    $0 == marker { print; inblk=1; next }
    inblk==1 { blk[NR]=$0; next }
    { print }
    END {
        # 정렬된 보호 항목 출력
        n=0; for (k in blk) { items[n++]=blk[k] }
        # sort
        for (i=0;i<n;i++) for (j=i+1;j<n;j++) if (items[j]<items[i]){t=items[i];items[i]=items[j];items[j]=t}
        prev="";
        for (i=0;i<n;i++){ if(items[i]!="" && items[i]!=prev){ print items[i]; prev=items[i] } }
    }
' "$GITIGNORE" > "$tmp" && cat "$tmp" > "$GITIGNORE" && rm -f "$tmp"

echo "[protect_gitignore] repo=$REPO_ROOT 신규등록=${added}개"
