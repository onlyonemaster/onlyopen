# iamserver 유실 방어체계 설계·구축 보고서

> 작성: 아리 2026-07-01
> 대상 서버: iamserver (kiam, 222.239.248.226, CentOS 7, git 1.8.3.1)
> 보호 저장소: `/home/webapp` (GitHub onlyopen)
> 참조 원본: bigserver (175.126.232.229) 에 구축·검증된 4중 방어체계

---

## 1. 배경 — 왜 이 작업을 했는가

bigserver 에서 발생한 대규모 유실 사고의 **진짜 원인**은 GitHub 가 아니라, 서버 로컬에서 실행된 **`git reset --hard`** 였다. 이 명령은 GitHub 통신과 무관하게(네트워크를 끊어도 발생) 로컬 작업트리를 되돌리면서, **git 에 안 올린 미추적 파일을 삭제**한다.

bigserver 는 이 사고 후 4중 방어막을 구축했으나, **iamserver 에는 방어체계가 `git clean` 차단뿐**이었다. 즉 bigserver 가 당한 것과 **동일한 `reset --hard` 위험에 무방비**로 노출되어 있었다. 이 보고서는 그 위험을 제거한 기록이다.

## 2. 진단 결과 (iamserver 실측)

| 항목 | 실측 값 | 위험도 |
|------|---------|--------|
| 보호 저장소 | `/home/webapp` (사이트 33개, GitHub onlyopen) | — |
| 기존 방어 | `git clean` 만 4중 차단 (Layer1~4) | 🔴 reset 무방비 |
| reflog reset 횟수 | **42회** (commit 191회 대비) | 🔴 reset 실사용 중 |
| 조기경보 훅 | 없음 | 🔴 |
| 격리 미러 백업 | 없음 | 🔴 |
| git 버전 | **1.8.3.1** (매우 구형) | ⚠️ 호환처리 필요 |
| 진짜 git | `/usr/local/lib/git-guard/realgit/git` (독립 복사본, 보존됨) | ✅ 활용 가능 |
| 기존 백업 | `/etc/cron.d/kiam_backup` (kiam 소스/DB/설정, 매일, 7개 보관) | ✅ 정상 가동 |

**결론**: `git clean` 은 막혀 있었으나 `git reset --hard` 는 완전 무방비 → bigserver 와 동일한 시한폭탄.

## 3. 설계 원칙

> **"어떤 git 명령도 관리자 승인 없이는 사이트 파일 한 개도 못 지운다"**

1. GitHub 는 유지 (복구·이력·협업 이점). 위험한 로컬 명령만 봉인.
2. bigserver 에서 **검증된** 방어 소스를 이식하되, **git 1.8.3.1 호환**으로 개조.
3. 기존 iamserver 자산(git-guard realgit, kiam 백업, inotify watcher)을 **존중·재활용**.
4. 파괴적 변경 없이 설치. 원본은 전부 `_safety/snapshots/` 에 백업.

## 4. 구축한 방어막 (4겹)

### 방어막 1 — git 보호 shim (`/usr/local/bin/git`)
- 기존 clean-only 래퍼를 **전체 위험명령 방어**로 교체.
- `reset --hard`/`checkout -f`/`rm` 등 → 실행 직전 `stash -u` 자동 대피 → 파일 생존.
- `clean`/`reflog expire`/`gc --prune`/`filter-branch` → 완전 차단.
- 중첩(nested) git 저장소도 그 저장소를 대상으로 대피.
- **git 1.8.3.1 호환**: `stash save` 폴백, `-C` 미사용(cd 방식).

### 방어막 2 — 조기경보 훅 (`.git/hooks/`)
- checkout/merge/pull 이후 사이트 진입점 소실 감지 → 경보.

### 방어막 3 — 추적 통일 진단 도구
- `scan-unprotected.sh`(미보호 소스 스캔), `protect-nested.sh`(중첩 저장소 미추적 소스 안전 커밋).

### 방어막 4 — 격리 미러 백업 (`/var/git-mirrors`)
- 서빙 저장소와 물리적으로 분리된 bare 미러. 30분마다 fetch 동기화(크론).
- 서빙 폴더가 통째로 사라져도 `mirror-restore.sh` 로 복구.

## 5. 검증 결과 (실측 통과)

| 테스트 | 결과 |
|--------|------|
| webapp 밖에서 정상 git 동작 | ✅ 통과 |
| `git clean` 차단 | ✅ 차단됨 |
| `git reflog expire` 차단 | ✅ 차단됨 |
| `git gc --prune=now / --aggressive` 차단 | ✅ 차단됨 |
| `git filter-branch` 차단 | ✅ 차단됨 |
| **중첩저장소 `reset --hard` → 미추적(ghost) 파일 생존** | ✅ **생존** (stash 대피→pop 복원) |
| 중첩 저장소 정확 인식 (target) | ✅ 정확 |
| 조기경보 진입점 소실 감지 | ✅ ALERT 생성 |
| 미러 생성·무결성·실복구(796파일) | ✅ 통과 |
| 미러 크론 30분 등록 | ✅ 등록 |

## 6. 관리자 유의사항

1. **차단 명령이 정말 필요할 때**: `/usr/local/lib/git-guard/realgit/git clean ...` 처럼 실체 경로로 직접 실행(shim 우회).
2. **stash 누적**: reset 등을 쓸 때마다 자동 대피되어 `git stash list` 에 쌓임. 파일이 사라진 듯하면 `git stash pop`. 가끔 정리 권장.
3. **기존 kiam 백업·inotify watcher 는 그대로** 가동 중 — 건드리지 않았음.
4. **디스크**: `/`(175G 여유)에 미러(현재 13M) 저장. `/disk` 는 93% 사용 중이라 kiam 백업 보관수(7)는 유지.

## 7. 파일 맵

```
/home/webapp/_safety/
├── README.md                     # 방어체계 설명 (iamserver 맞춤)
├── bin/
│   ├── git-shim.sh               # 방어막1 (→ /usr/local/bin/git 에 설치)
│   ├── integrity-check.sh        # 방어막2 조기경보
│   ├── scan-unprotected.sh       # 방어막3-A 진단
│   ├── protect-nested.sh         # 방어막3-B 중첩저장소 보호
│   ├── mirror-sync.sh            # 방어막4 미러 동기화 (크론)
│   └── mirror-restore.sh         # 방어막4 미러 복구
├── hooks/{post-checkout,post-merge,post-rewrite}  # .git/hooks 에 설치됨
├── logs/                         # git-guard/integrity/mirror 로그 (추적제외)
├── snapshots/                    # 기존 git wrapper·crontab 원본 백업 (추적제외)
└── site_index_manifest.txt       # 진입점 기준목록 (추적제외)

/usr/local/bin/git                # 방어막1 shim (chattr +i 잠금)
/var/git-mirrors/webapp.git       # 방어막4 격리 미러
crontab: */30 * * * * mirror-sync.sh   # 미러 자동백업
```
