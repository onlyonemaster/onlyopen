# `/home/kiam` 메인 서비스 보호 현황 (Data-Loss Guard for kiam)

> 적용: 아리 2026-07-02 · 대상: **메인 PHP 서비스 `/home/kiam`** (repo: kiammain)
> 목적: 깃 버전관리 정상화 + 메인소스 유실 원천 차단

## 한눈에 보기

| 방어 계층 | 내용 | 위치/값 | 상태 |
|---|---|---|---|
| **① git shim v4 BLOCK** | 파괴 명령 실행 자체 거부 | `/usr/local/bin/git` (chattr +i) | ✅ |
| **② 미추적 소스 편입** | git 밖 소스 792개 커밋 | HEAD `4510528` | ✅ |
| **③ `.git` 물리 분리(메인)** | 메인 저장소 본체를 서비스 폴더 밖으로 | `/home/git-repos/kiam.git` | ✅ |
| **③-b 중첩 저장소 분리** | 하위사이트·라이브러리 11개 `.git` 전부 밖으로 | `/home/git-repos/nested_*.git` | ✅ |
| **④ 오프트리 미러** | 다른 물리디스크에 bare 미러 (메인+중첩 12개) | `/var/git-mirrors/*.git` | ✅ |
| **⑤ 원격 백업** | GitHub 오프사이트 | `github.com/onlyonemaster/kiammain.git` | ✅ |
| **⑥ 기존 일일백업** | 소스/DB/설정 (건드리지 않음) | `/disk/backup` (cron.d/kiam_backup) | ✅ |

> 🎯 **서비스 폴더(`/home/kiam`) 안 git 본체 = 0개** (메인 1 + 중첩 11 전부 밖으로 물리분리). bigserver와 동일 구조.

## ① git shim v4 (파괴명령 BLOCK)
- `PROTECTED_REPOS="/home/kiam"` — kiam 안에서만 방어 발동
- 차단 명령: `reset --hard/--merge/--keep`, `checkout -f`, `switch -f`, `restore -W`,
  `rm`, `clean`, `worktree remove/prune`, `read-tree -u`, `reflog expire/delete`,
  `gc --prune/--aggressive`, `filter-branch/filter-repo`
- BLOCK 직전 stash 스냅샷 기록(복구원본 확보), 작업트리는 그대로 유지
- 예외 실행(관리자): `GITSHIM_ALLOW=1 git ...` 또는 `/usr/local/lib/git-guard/realgit/git ...`
- 로그/스냅샷: **`/home/git-safety/`** (서비스 폴더 밖 — 오염 방지)
- `/home/webapp`(아리 작업폴더)은 **보호 제외** — 정상 git 동작

## ② 미추적 서비스 소스 git 편입 (커밋 4510528)
그동안 git·백업 어디에도 없던 라이브 소스를 이력에 영구 편입.
- **포함 792개**: aimessage 349, manual 148, iam 134, admin 59, autowork 53, event 39 …
  (php 242 / html 358 / js 29 / json 21 / md 21 / py 18 / css 16 / ts·tsx 11)
- **제외(안전)**: 시크릿 10(`.env*`/`.git-credentials`/`admin_secure_keys.php`/`secure_keys_api.php`),
  런타임(`venv311` 심링크/`chromadb_data` 벡터DB/`__pycache__`),
  개발백업 905(`*.bak`/`*.운영반영전`), 미디어 50, vendor 721(composer 재현), 5MB초과 1
- `.gitignore` **[ARI-SAFETY]** 블록 추가 → 위 시크릿/런타임 재커밋 원천 차단
- 편입 후 "진짜 순수 소스" 미추적 잔여 = **0개** (남은 건 전부 의도적 제외 대상)
- 사전 백업: `/home/git-safety/precommit/backup_before_commit_*/`

## ③ `.git` 물리 분리
- 본체: `/home/git-repos/kiam.git` (793M, `/home` 과 같은 FS=nvme0n1p1 → 원자적 이동)
- 포인터: `/home/kiam/.git` = `gitdir: /home/git-repos/kiam.git` (33 byte)
- `core.worktree=/home/kiam`, `core.bare=false`
- 분리 전 백업: `/home/backups/git_split/kiam_dotgit_pre_split_*.tar.gz` (771M)
- **무손실 검증**: HEAD 동일(`4510528`), 추적파일 42437=42437, HEAD 객체 유효, shim BLOCK 유지

## ③-b 중첩(nested) 저장소 물리 분리
서비스 폴더 안에 각자 `.git` 을 갖고 있던 하위사이트/라이브러리 **11개**를 전부 밖으로 이동.
`git ls-files -s | awk '$1==160000'`(gitlink) + `find -type d -name .git` 로 식별.
- **진짜 하위사이트 3개**: `iam/landing/landing_main`(master, HEAD c223a87), `iam/landing/iamprofile`(master, HEAD 13d2342), `aimessage/superchatbot`(feature/chatbot-invitation-system, HEAD 8b92fcf)
- **vendor 라이브러리 8개**: `excel_down/vendor/*`(phpspreadsheet 68M 등, composer 재현 가능하나 bigserver 일관성 위해 동일 분리)
- 이동: 각 `.git` 본체 → `/home/git-repos/nested_<상대경로>.git`, 원위치엔 포인터(`gitdir:`)만
- `core.worktree`=원위치, `core.bare=false` 설정
- 분리 전 각 `.git` **tar 백업**: `/home/backups/git_split/nested/`
- **무손실 검증**: 11개 전부 이동 전후 HEAD 동일, HEAD 불일치 시 자동 롤백(0건 발생)
- 스크립트: `_safety/bin/split-nested-kiam.sh` (`--dry-run` 지원)
- 결과: **서비스 폴더 안 git 본체 0개** (`find /home/kiam -type d -name .git` → 0)

## ④ 오프트리 미러 (다른 물리디스크)
- 메인 미러: `/var/git-mirrors/kiam.git` (787M, `/var`=sda3 — `/home`(nvme)와 독립)
- 중첩 미러: `/var/git-mirrors/nested_*.git` (11개, 동일 sda3)
- 동기화(메인): `_safety/bin/mirror-sync-kiam.sh` (fetch 방식, 서빙 명령/훅 미트리거)
- 동기화(중첩): `_safety/bin/mirror-sync-nested-kiam.sh` (`nested_*.git` 전체 순회)
- **크론**: 메인 `5,35 * * * *` · 중첩 `10,40 * * * *` (webapp 미러 `*/30` 과 오프셋)
- 무결성: `mirror-sync-kiam.sh --verify` / `mirror-sync-nested-kiam.sh --verify`
- 복구: `mirror-restore-kiam.sh --list` → `mirror-restore-kiam.sh <복구위치>`
  (라이브 직접 덮어쓰기 금지 — 별도 폴더로 꺼내 관리자 확인 후 반영)

## 사고 발생 시 복구 순서
1. **파괴명령이 막혔다면** → 이미 방어 성공. stash 확인: `git stash list` → `git stash pop`
2. **파일이 사라졌다면(추적 파일)** → `git checkout -- <경로>` (shim이 -f 없는 복원은 허용)
3. **커밋에 있는 소스 복구** → 원격/미러에서: `mirror-restore-kiam.sh <위치>`
4. **폴더 통째 유실** → ④미러 또는 ⑤GitHub, ⑥`/disk/backup/source/<날짜>/`
5. **저장소 손상** → `/home/backups/git_split/kiam_dotgit_pre_split_*.tar.gz` 복원

## 유지보수 주의
- `/home/kiam/.git` **및 모든 하위 `.git`(11개)** 은 포인터 파일이므로 **삭제/덮어쓰기 금지**.
  본체는 `/home/git-repos/kiam.git` 및 `/home/git-repos/nested_*.git`.
- 새 하위사이트를 추가해 `.git` 이 생기면: `_safety/bin/split-nested-kiam.sh` 를 다시 돌리면
  신규 중첩 저장소도 자동으로 밖으로 분리됨(`--dry-run` 먼저 권장).
- 신규 서비스 소스 추가 시: 정상적으로 `git add` → `git commit`(shim이 add/commit은 허용)
- 시크릿·런타임은 `.gitignore [ARI-SAFETY]` 블록으로 자동 제외됨. 새 시크릿 유형은 이 블록에 추가.
- 이 문서/스크립트는 `/home/webapp`(아리 작업 리포)에서 버전관리됨.
