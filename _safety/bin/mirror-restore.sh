#!/bin/bash
# =============================================================================
# mirror-restore — 격리 미러(/var/git-mirrors)에서 복구 (방어막4 복구도구) [iamserver]
#
#   서빙 저장소나 중첩 저장소가 파괴/유실됐을 때, 오프트리 미러에서 되살린다.
#   안전을 위해 "복구 대상 폴더를 덮어쓰지 않고" 별도 위치로 꺼내 보여준 뒤,
#   관리자가 직접 확인하고 옮기도록 한다. (자동 덮어쓰기는 하지 않음)
#
#   사용:
#     mirror-restore.sh --list                     # 미러 목록/최신커밋
#     mirror-restore.sh <mirror> [dest]            # 미러를 dest 로 복구(clone)
#         예) mirror-restore.sh webapp /home/webapp_RESTORED
#   이식: 아리 2026-07-01
# =============================================================================
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi
MIRROR_ROOT=/var/git-mirrors

if [ "$1" = "--list" ] || [ -z "$1" ]; then
  echo "=== 사용 가능한 미러 (오프트리 복구원본) ==="
  for m in "$MIRROR_ROOT"/*.git; do
    [ -d "$m" ] || continue
    name=$(basename "$m" .git)
    head=$("$REAL_GIT" --git-dir="$m" log --oneline -1 --all 2>/dev/null)
    sz=$(du -sh "$m" 2>/dev/null | cut -f1)
    printf "  %-24s %6s  최신: %s\n" "$name" "$sz" "$head"
  done
  echo ""
  echo "복구:  mirror-restore.sh <name> <dest폴더>"
  exit 0
fi

name="$1"
dest="${2:-/home/${name}_RESTORED_$(date +%Y%m%d_%H%M%S)}"
mir="$MIRROR_ROOT/${name}.git"
[ -d "$mir" ] || mir="$MIRROR_ROOT/${name}"      # 확장자 유무 모두 허용
[ -d "$mir" ] || { echo "❌ 미러를 찾을 수 없음: $name (mirror-restore.sh --list 로 확인)"; exit 1; }

if [ -e "$dest" ]; then
  echo "❌ 대상 경로가 이미 존재합니다: $dest"
  echo "   (기존 폴더를 덮어쓰지 않습니다. 다른 dest 를 지정하세요.)"
  exit 1
fi

echo "🛟  복구 중: $mir  →  $dest"
if "$REAL_GIT" clone "$mir" "$dest" 2>&1 | tail -3; then
  echo "✅ 복구 완료: $dest"
  echo "   내용 확인 후, 문제 없으면 서빙 위치로 이동/동기화하세요."
  echo "   (자동 덮어쓰기는 안전을 위해 수행하지 않습니다.)"
else
  echo "❌ 복구 실패"
  exit 1
fi
