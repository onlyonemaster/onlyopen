# `_safety` — 유실 방어 시스템 (Data-Loss Guard) [iamserver / kiam]

> 설치: 아리 2026-07-01 · 목적: `git reset --hard`/`clean` 등이 사이트 파일을 삭제하는 사고 원천 차단
> bigserver(175.126.232.229)에 구축·검증된 방어체계를 iamserver(222.239.248.226)에 이식.

## 왜 필요한가 (한 줄)

GitHub가 아니라 **서버 로컬에서 실행되는 `git reset --hard`** 가, 저장소에 안 올린(미추적) 사이트 파일을 삭제해 온 것이 모든 유실의 원인. 이 시스템은 그 명령을 가로채 **파일이 지워지기 전에 자동 대피**시킨다.

## iamserver 고유 환경 (bigserver 와의 차이)

| 항목 | bigserver | iamserver (이 서버) |
|------|-----------|---------------------|
| OS | Ubuntu 22.04 | **CentOS 7** |
| git 버전 | 2.34.1 | **1.8.3.1** (구버전) |
| 보호 저장소 | `/var/www/webapp` | **`/home/webapp`** (onlyopen) |
| 진짜 git 경로 | `/usr/bin/git` | **`/usr/local/lib/git-guard/realgit/git`** (기존 git-guard 독립 복사본) |
| 기존 방어 | clean 4중차단(사고後 reset 추가) | **clean 4중차단만 존재** → reset 무방비였음 (이번에 확장) |
| 백업 | `/root/scripts/webapp_backup.sh` | **`/etc/cron.d/kiam_backup`** (기존 kiam 백업 존중) |

### ★ git 1.8.3.1 호환 처리 (중요)
iamserver 의 git 은 매우 구형이라 다음을 폴백 처리했다 (검증 완료):
- **`git stash push -u -m` 미지원** → `git stash save -u <msg>` 로 폴백
- **`git -C <path>` 옵션 자체가 없음**(1.8.5+) → subshell 에서 `cd` 후 실행
- **`git fsck --connectivity-only` 미지원** → `rev-parse --verify HEAD` + `count-objects` 로 무결성 판정
- **`git add --pathspec-from-file` 미지원** → 파일별 개별 `add` 폴백

## 구성 (설치된 방어막)

### 🛡️ 방어막 1 — `git` 보호 shim  → `/usr/local/bin/git`
- 원본: `_safety/bin/git-shim.sh`
- 모든 `git` 호출을 PATH 우선순위로 가로챔. **보호 저장소(/home/webapp) 안에서만** 방어 발동.
- 기존 Layer1(`/usr/local/bin/git` clean차단) 을 **전체 위험명령 방어로 교체·확장**. Layer3(`/usr/bin/git` 가드)·Layer4(inotify watcher)는 그대로 유지.
- shim 자체는 `chattr +i`(불변속성)로 잠금.
- **3등급 정책:**

  | 등급 | 명령 | 동작 | 사유 |
  |------|------|------|------|
  | ⛔ **완전차단** | `git clean` | 차단 | 미추적 파일 삭제 |
  | ⛔ 완전차단 | `git reflog expire` / `delete` | 차단 | 복구 안전망(reflog) 제거 |
  | ⛔ 완전차단 | `git gc --prune=now/--aggressive` | 차단 | stash/reset 복구원본(도달불가 객체) 즉시 삭제 |
  | ⛔ 완전차단 | `git filter-branch` / `filter-repo` | 차단 | 히스토리 파괴적 재작성 |
  | 🛡 **스냅샷 후 진행** | `git reset --hard/--merge/--keep` | stash -u 대피 | 미추적/변경 파일 삭제 |
  | 🛡 스냅샷 후 진행 | `git checkout -f`, `switch -f/--discard-changes` | stash -u 대피 | 강제 덮어쓰기 |
  | 🛡 스냅샷 후 진행 | `git restore -W/--worktree` | stash -u 대피 | 로컬 변경분 덮어쓰기 |
  | 🛡 스냅샷 후 진행 | `git submodule -f/--force/--checkout` | stash -u 대피 | 하위리포 삭제 위험 |
  | 🛡 스냅샷 후 진행 | `git rm`, `git worktree remove/prune`, `git read-tree -u/--reset` | stash -u 대피 | 파일/워크트리 삭제·워킹 덮어쓰기 |
  | ⚠️ **경고 후 진행** | `git branch -D/-M`, `push -f/--delete/--force-with-lease`, `update-ref -d` | 로그+경고 | 참조 삭제/원격 덮어쓰기(파일유실 아님) |

- 대피 후 복원: `git stash list` → `git stash pop`
- 차단 명령이 꼭 필요하면 **관리자가 `/usr/local/lib/git-guard/realgit/git` 로 직접** 실행 (shim 우회).
- 로그: `_safety/logs/git-guard.log`

### 🛡️ 방어막 2 — Git 훅 (조기경보) → `.git/hooks/{post-checkout,post-merge,post-rewrite}`
- 실행: `_safety/bin/integrity-check.sh`
- checkout/merge/pull/rebase 이후 **사이트 진입점(index) 존재 여부** 검사.
- 사라진 게 있으면 `_safety/ALERT_missing_index.txt` 생성 + stderr 경고.
- 기준 목록: `_safety/site_index_manifest.txt` (신규 사이트 추가 시 이 파일 삭제 후 재생성)

### 🛡️ 방어막 3 — 추적 통일 + 중첩 저장소 보호 (진단 도구 제공)
부분추적(=사이트 안에 추적/미추적 파일이 섞여 reset 시 미추적분만 소실)을 없애 **reset 을 무해화**한다.
- **3-A 진단**: `_safety/bin/scan-unprotected.sh [site]` — 각 사이트에서 (미추적)∧(gitignore로도 무시 안 됨)∧(런타임 산출물 아님)인 "진짜 위험 파일"을 찾음.
- **3-B 중첩 저장소**: `_safety/bin/protect-nested.sh [--commit] [site]` — 자체 `.git`을 가진 사이트의 미추적 소스만 안전 커밋(시크릿·백업·런타임·5MB초과 자동 제외).
- shim v3 가 중첩 저장소를 대상으로 stash 대피하므로, 중첩 저장소 안 reset 도 보호됨(검증 완료).

### 🛡️ 방어막 4 — 서빙↔git-work 물리 분리 (오프트리 미러)
서빙 디렉토리(`/home/webapp`) 안에서 무슨 일이 나도 손댈 수 없는 **격리 백업**을 둔다.
- 미러 위치: `/var/git-mirrors/*.git` (부모 webapp + 중첩 저장소, 압축된 bare)
- 동기화: `_safety/bin/mirror-sync.sh` (fetch 방식이라 서빙 쪽 명령/훅을 건드리지 않음)
  - **크론 자동화**: `*/30 * * * *` (30분마다)
  - 무결성 점검: `mirror-sync.sh --verify`
- 복구: `_safety/bin/mirror-restore.sh --list` → `mirror-restore.sh <name> <dest>`
  - **덮어쓰지 않고** 별도 폴더로 꺼내 확인 후 관리자가 이동(안전장치).

### 🧰 백업 (기존 kiam 백업 존중)
iamserver 는 이미 `/etc/cron.d/kiam_backup` 으로 소스/DB/설정을 매일 백업 중(변경시만, 7개 보관, `/disk/backup`).
- 이 방어체계는 기존 백업을 **건드리지 않고**, webapp 은 방어막4 미러 + GitHub 로 이중 보호.

## 사고 발생 시 복구 순서
1. `git stash list` — 방어막1이 대피시킨 변경/미추적 파일 확인 (중첩 저장소면 **그 폴더 안에서** 실행)
2. `git stash pop` (또는 `git stash apply stash@{N}`) — 복원
3. `_safety/ALERT_*.txt` 확인 — 무엇이 사라졌는지
4. 백업에서 복원: `/disk/backup/source/<날짜>/` (kiam 백업)
5. **최후 수단(폴더 통째 유실)**: `_safety/bin/mirror-restore.sh --list` → `mirror-restore.sh webapp <복구위치>`

## 유지보수
- 신규 사이트 추가 후: `rm _safety/site_index_manifest.txt && _safety/bin/integrity-check.sh` (매니페스트 재생성)
- shim 재설치: `chattr -i /usr/local/bin/git; cp _safety/bin/git-shim.sh /usr/local/bin/git; chmod +x /usr/local/bin/git; chattr +i /usr/local/bin/git`
- shim 원본(clean 전용 구버전)·기존 git wrapper 백업: `_safety/snapshots/pre_defense_*/`

## 주의 (한계)
- `_safety/snapshots`, `logs`, `ALERT_*`, `manifest` 는 런타임 산출물이라 git 추적 제외. 방어 스크립트(`bin/`)와 이 문서만 추적.
- git 1.8.3.1 은 구형이라 `stash` 가 느릴 수 있음. 기능은 검증 완료.
- 기존 Layer4 inotify watcher(systemd `webapp-gitignore-watcher.service`)는 clean 방어용으로 계속 가동 중 — 건드리지 않음.
