# 🛡️ Git 유실 방어 시스템 매뉴얼 — iamserver 적용판 (v4)

> **대상 독자:** iamserver(및 유사 CentOS/구버전 git 서버)에서 작업하는 젠스파크(GenSpark) AI 개발자
> **원본:** bigserver 매뉴얼(2026-07-02) → **iamserver(CentOS 7 / git 1.8.3.1)에 이식·적용 완료**
> **목적:** `git reset --hard` / `git clean` 등 파괴적 git 명령과 "저장소가 서비스 폴더 안에 있는 구조" 때문에 발생한 **대규모 파일 유실 참사**의 재발 방지.

---

## 0. 무슨 일이 있었나 (배경)

- 서버 웹 루트가 **git 저장소이면서 동시에 실제 서비스 폴더**였다.
- git은 **"커밋에 없는 파일 = 지워도 되는 파일"** 로 간주한다. 따라서
  `git reset --hard`, `git checkout -f`, `git clean` 실행 시 **커밋 안 된 실제 서비스 파일이 통째로 삭제**된다.
- **GitHub 가 파일을 지운 게 아니다.** 로컬에서 실행된 파괴 명령이 근본 원인.

**최종 해결책 두 축 (v4):**
1. **git 파괴 명령 자체를 실행 거부(BLOCK)** 하는 shim(v4).
2. **git 저장소 본체(.git)를 서비스 폴더 밖으로 물리적 분리.**

---

## 1. iamserver 최종 적용 구조

```
/home/webapp/                    ← 실제 서비스 파일. git 본체 없음.
/home/webapp/.git                ← 디렉토리가 아니라 35바이트 "포인터 파일"
        │  내용: gitdir: /home/git-repos/webapp.git
        ▼
/home/git-repos/webapp.git       ← git 저장소 본체(히스토리·객체, 13M). 서비스 폴더 밖.
/var/git-mirrors/webapp.git      ← 저장소 백업 미러(30분마다 자동 동기화, 다른 FS).

/usr/local/bin/git               ← git 보호 shim(v4). PATH 우선. chattr +i. 파괴 명령 BLOCK.
/usr/local/lib/git-guard/realgit/git ← 진짜 git 1.8.3.1 (shim이 내부적으로 호출).
```

**핵심 원리 3가지:**
1. **PATH 우선 shim**: `/usr/local/bin`이 앞서므로 누가 `git`을 쳐도 shim이 먼저 가로챈다.
2. **파괴 명령 실행 거부**: `reset --hard`, `checkout -f`, `rm`, `clean` 등은 아예 실행되지 않는다. (BLOCK 전 stash 스냅샷 남김)
3. **물리적 분리**: 저장소 본체가 서비스 폴더 밖에 있어, 서비스 폴더 사고 ↔ 저장소가 상호 안전.

---

## 2. bigserver ↔ iamserver 차이 (이식 시 반드시 반영한 부분)

| 항목 | bigserver | iamserver(적용값) |
|------|-----------|-------------------|
| OS / git | Ubuntu / git 2.34.1 | **CentOS 7 / git 1.8.3.1** |
| 서비스 폴더 | `/var/www/webapp` | **`/home/webapp`** |
| 저장소 본체 | `/var/git-repos/webapp.git` | **`/home/git-repos/webapp.git`** (동일 FS 이동 위해) |
| 백업 미러 | `/var/git-mirrors/webapp.git` | `/var/git-mirrors/webapp.git` (다른 FS) |
| 진짜 git | `/usr/bin/git` | **`/usr/local/lib/git-guard/realgit/git`** (폴백 체인) |
| stash | `stash push -u` | **`stash save -u` 폴백** (push 없음) |
| `-C <path>` | 사용 가능 | **없음 → 서브셸 `cd`** |
| fsck 검증 | `--connectivity-only` | **`rev-parse HEAD`+`count-objects`** |

> `/home` 과 `/var` 가 다른 파일시스템이라, `.git` 본체는 원자적 `mv` 를 위해 **같은 FS 인 `/home/git-repos`** 로 이동했다. (bigserver 매뉴얼 STEP0 의 "파일시스템 동일" 조건 준수)

---

## 3. shim(v4)이 하는 일 요약

| 분류 | 명령 | 동작 |
|------|------|------|
| **BLOCK(실행 거부)** | `clean`, `reflog expire/delete`, `gc --prune/--aggressive`, `filter-branch/repo` | 완전 차단 |
| **BLOCK(v4 신규)** | `reset --hard/--merge/--keep`, `checkout -f`, `switch -f`, `restore -W`, `submodule -f`, `rm`, `worktree remove/prune`, `read-tree -u` | **실행 거부** (거부 전 stash 스냅샷) |
| **WARN(경고만)** | `branch -D/-M`, `push -f/--delete`, `update-ref -d` | 실행하되 경고 |
| **PASS(통과)** | `status`, `log`, `add`, `commit`, `pull`, `push`, `fetch`, `diff` 등 | 그대로 실행 |

**예외 실행(관리자가 정말 필요할 때만):**
```bash
GITSHIM_ALLOW=1 git reset --hard HEAD          # 명시적 허용(책임하에, 사전 stash 대피)
/usr/local/lib/git-guard/realgit/git reset --hard HEAD   # 진짜 git 직접 호출
```
- shim은 보호 트리(`REPO=/home/webapp`) **안**에서만 방어. 밖(`/tmp` 등)은 진짜 git 그대로.
- 중첩(nested) git 저장소도 인식. 모든 이벤트는 `_safety/logs/git-guard.log` 에 기록.

---

## 4. 재설치 / 다른 서버 이식 (자동 스크립트)

```bash
# STEP 1 백업(먼저 수동): .git 및 서비스 파일 tar
# STEP 2~4 자동:
sudo bash /home/webapp/_safety/bin/install-git-safety.sh /home/webapp webapp /home/git-repos
#                                                          └서비스폴더 └저장소명 └본체이동대상(같은FS)
```
스크립트는 각 단계 검증에 실패하면 즉시 중단한다. (chattr +i 자동 해제/재적용 포함)

---

## 5. 사고 발생 시 복구 (Runbook)

**증상 A: 서비스 파일이 사라졌다**
```bash
RG=/usr/local/lib/git-guard/realgit/git
cd /home/webapp && $RG stash list          # shim 이 남긴 대피 스냅샷 확인
$RG stash pop                              # 대피본 복구
# 또는 tar 백업에서:
ls /home/backups/git_split/
```

**증상 B: 저장소(.git 본체)가 손상됐다**
```bash
bash /home/webapp/_safety/bin/mirror-restore.sh   # /var/git-mirrors 에서 복구
# 또는 수동:
mv /home/git-repos/webapp.git /home/git-repos/webapp.git.broken
/usr/local/lib/git-guard/realgit/git clone --mirror /var/git-mirrors/webapp.git /home/git-repos/webapp.git
# core.worktree 재설정(2장 참고)
```

**증상 C: shim이 정상 명령까지 막는다**
- 임시로 `GITSHIM_ALLOW=1` 또는 `/usr/local/lib/git-guard/realgit/git` 사용. shim은 절대 삭제 금지.

---

## 6. 절대 금지 사항

1. ❌ `/usr/local/bin/git`(shim)을 삭제/되돌리지 말 것 (chattr +i 로 보호됨).
2. ❌ `.git`을 다시 서비스 폴더 안 디렉토리로 되돌리지 말 것 (분리 구조 유지).
3. ❌ 백업 없이 `mv .git` 하지 말 것.
4. ❌ 파괴 명령을 `GITSHIM_ALLOW=1`로 습관적으로 우회하지 말 것.
5. ✅ 파일을 만들면 **즉시 커밋**할 것 — 커밋 안 된 파일은 100% 복구를 보장할 수 없다.
6. ✅ 작업 후 서비스가 살아있는지 확인할 것.

---

## 7. iamserver 실제 적용값 (2026-07-02 완료)

| 항목 | 값 |
|------|------|
| 서비스 폴더(워크트리) | `/home/webapp` |
| 저장소 본체 | `/home/git-repos/webapp.git` |
| 백업 미러 | `/var/git-mirrors/webapp.git` |
| shim | `/usr/local/bin/git` (v4, REPO=/home/webapp, chattr +i) |
| 진짜 git | `/usr/local/lib/git-guard/realgit/git` (1.8.3.1) |
| 브랜치 | `genspark_ai_developer` |
| 미러 크론 | `*/30 * * * * .../mirror-sync.sh` |
| .git 사전백업 | `/home/backups/git_split/webapp_dotgit_pre_split_*.tar.gz` |
| 로그 | `/home/webapp/_safety/logs/git-guard.log`, `mirror.log` |

### 검증 결과 (적용 시점)
- 분리 전/후 HEAD 동일, 추적파일수 813 = 813 (**유실 0**)
- `reset --hard`/`checkout -f`/`rm`/`clean` → 전부 BLOCK
- 미추적(ghost) 파일 reset --hard 시도에도 **생존** 확인
- 미러 무결성 OK (13M, 1713 objects), 30분 크론 동작
