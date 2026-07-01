#!/bin/bash
# =============================================================================
# mirror-restore-kiam — /var/git-mirrors/kiam.git 오프트리 미러에서 복구 [iamserver]
#
#   안전 원칙: 절대 라이브(/home/kiam)를 직접 덮어쓰지 않는다.
#   미러 내용을 "별도 폴더"로 꺼내 관리자가 눈으로 확인 후 직접 반영하게 한다.
#
#   사용:
#     mirror-restore-kiam.sh --list           # 미러 상태/최근 커밋 확인
#     mirror-restore-kiam.sh <복구위치>        # 미러를 해당 폴더로 clone(작업트리 복원)
#   예)  mirror-restore-kiam.sh /home/kiam_restore_$(date +%Y%m%d)
#   작성: 아리 2026-07-02
# =============================================================================
if   [ -x "/usr/local/lib/git-guard/realgit/git" ]; then REAL_GIT="/usr/local/lib/git-guard/realgit/git"
elif [ -x "/usr/libexec/git-core/git" ];            then REAL_GIT="/usr/libexec/git-core/git"
else                                                     REAL_GIT="/usr/bin/git"
fi
MIRROR=/var/git-mirrors/kiam.git

if [ ! -d "$MIRROR" ]; then echo "❌ 미러 없음: $MIRROR"; exit 1; fi

if [ "$1" = "--list" ] || [ -z "$1" ]; then
  echo "=== kiam 미러 정보 ($MIRROR) ==="
  echo "크기: $(du -sh "$MIRROR" 2>/dev/null | cut -f1)"
  echo "브랜치:"; "$REAL_GIT" --git-dir="$MIRROR" branch -a 2>/dev/null | sed 's/^/  /'
  echo "최근 커밋:"; "$REAL_GIT" --git-dir="$MIRROR" log --oneline -5 genspark_ai_developer 2>/dev/null | sed 's/^/  /'
  echo
  echo "복구하려면: $0 <복구위치>   (예: $0 /home/kiam_restore_$(date +%Y%m%d))"
  echo "⚠ 라이브(/home/kiam)를 직접 덮어쓰지 않습니다. 별도 폴더로 꺼낸 뒤 관리자가 확인/반영하세요."
  exit 0
fi

DEST="$1"
if [ -e "$DEST" ]; then echo "❌ 대상이 이미 존재: $DEST (덮어쓰기 방지)"; exit 1; fi
echo "미러 → $DEST 로 복원(clone) 중..."
if "$REAL_GIT" clone "$MIRROR" "$DEST" >/dev/null 2>&1; then
  echo "✅ 복원 완료: $DEST"
  echo "   확인 후 필요한 파일만 /home/kiam 으로 관리자가 직접 반영하세요."
else
  echo "❌ 복원 실패"; exit 1
fi
