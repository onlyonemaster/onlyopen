# webapp git 물리적 분리 구조 (iamserver, 2026-07-02 적용)

## 목적
git 파괴 명령이 실제 서비스 파일을 지우는 사고를 원천 차단.
(bigserver v4 방어책을 iamserver = CentOS 7 / git 1.8.3.1 에 이식)

## 구조
- 서비스 파일:     /home/webapp              ← .git 은 35byte 포인터 파일만 존재
- git 저장소 본체:  /home/git-repos/webapp.git ← 서비스 폴더 밖으로 분리 (13M)
- 백업 미러:       /var/git-mirrors/webapp.git ← 30분마다 fetch 동기화

> ⚠️ bigserver 는 `/var/git-repos` 를 썼지만, iamserver 는 `/home` 과 `/var` 가
>    **파일시스템이 다르다**. `.git` 본체는 원자적 `mv` 를 위해 **같은 파일시스템**인
>    `/home/git-repos` 로 옮겼다. 미러는 단순 복사이므로 물리 분리 효과를 위해
>    다른 파일시스템(`/var/git-mirrors`)에 둔다.

## 핵심
- /home/webapp/.git 은 "gitdir: /home/git-repos/webapp.git" 포인터 파일.
- 저장소 본체가 서비스 폴더 밖에 있으므로, 서비스 폴더 삭제/이동과 저장소는 물리 분리됨.
- core.worktree = /home/webapp (분리 저장소가 이 워크트리를 가리킴).

## git shim (v4, /usr/local/bin/git, chattr +i 보호)
- reset --hard / checkout -f / rm / clean 등 파일 삭제 명령 = **실행 자체 BLOCK**.
- BLOCK 직전 stash 스냅샷을 남겨 복구원본 확보(작업트리는 그대로 유지).
- 예외 실행: `GITSHIM_ALLOW=1 git ...` 또는 `/usr/local/lib/git-guard/realgit/git ...`
- REAL_GIT: /usr/local/lib/git-guard/realgit/git (폴백: /usr/libexec/git-core/git → /usr/bin/git)

## git 1.8.3.1 호환 처리
- `stash push` 없음 → `stash save -u` 폴백
- `-C <path>` 없음(2.x) → 서브셸 `( cd DIR && git ... )`
- `fsck --connectivity-only` 없음 → `rev-parse --verify HEAD` + `count-objects`

## 복구
- 저장소 손상 시: /var/git-mirrors/webapp.git 에서 복구 (mirror-restore.sh).
- 서비스 파일 손상 시: /home/backups/git_split/ 및 kiam 백업(/disk/backup)에서 복구.
- .git 백업(분리 전): /home/backups/git_split/webapp_dotgit_pre_split_*.tar.gz
