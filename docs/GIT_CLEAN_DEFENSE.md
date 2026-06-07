# git clean -fd 4중 방어 시스템 (kiam / iamserver)

> 빅서버(bigserver)에서 `git clean -fd` 로 다수 소스가 삭제된 사고가
> kiam 서버에서도 재발하여, 빅서버보다 강화된 **4중 방어 시스템**을 구축함.
> 참고 노트: https://ainote.kiam.kr/share.html?id=1557

## 사고 원인
`git clean -fd` 는 **추적되지 않은(untracked) 파일·디렉터리를 영구 삭제**한다.
운영 서버에서 직접 만든 사이트 솔루션이 git에 커밋되지 않은 상태에서
`git clean -fd` 가 실행되면 그 소스가 통째로 사라진다.

## 4중 방어 구조

| 레이어 | 위치 | 역할 | 빅서버 대비 |
|--------|------|------|-------------|
| **L1** | `/usr/local/bin/git` | PATH 우선 래퍼. `git clean` 차단 | 동일 + `-c alias.clean=` 우회 차단 |
| **L2** | `/home/webapp/.gitignore` | 모든 top-level + **중첩** 미추적 경로 등록 | **중첩 디렉터리까지** 보호(빅서버 사각지대 해결) |
| **L3** | `/usr/bin/git` | 직접호출 가드. `/usr/bin/git clean` 차단 | **신규** — 빅서버 유일 우회로 차단 |
| **L4** | `systemd: webapp-gitignore-watcher` | 신규 폴더 실시간 자동 .gitignore 등록 | 동일 |

### 빅서버에서 뚫렸던 유일한 우회로
```
/usr/bin/git -c alias.clean= clean -fdx   # 빅서버에서는 작동했음
```
→ kiam 서버에서는 **L1 + L3 가 모두 차단** (테스트 완료).

## 핵심 파일
```
/usr/local/bin/git                              # L1 래퍼 (immutable)
/usr/bin/git                                    # L3 가드 (immutable)
/usr/local/lib/git-guard/realgit/git            # 진짜 git 독립 복사본 (immutable)
/usr/local/lib/git-guard/protect_gitignore.sh   # L2 등록 엔진 (L4 가 재사용)
/usr/local/lib/git-guard/watcher.sh             # L4 watcher 본체
/usr/local/lib/git-guard/status.sh              # 상태 점검 도구
/etc/systemd/system/webapp-gitignore-watcher.service
/var/log/webapp-gitignore-watcher.log           # L4 로그
```

## 운영 명령
```bash
# 상태 점검
bash /usr/local/lib/git-guard/status.sh

# watcher 재시작 / 로그
systemctl restart webapp-gitignore-watcher.service
tail -f /var/log/webapp-gitignore-watcher.log

# 수동으로 .gitignore 보호 갱신
/usr/local/lib/git-guard/protect_gitignore.sh /home/webapp
```

## 정상적으로 clean 이 꼭 필요할 때 (관리자 전용)
```bash
# 가드를 우회하는 진짜 git 직접 실행 (신중히!)
/usr/local/lib/git-guard/realgit/git clean -fdn   # 먼저 -n 으로 시뮬레이션
```

## 잠금 해제(유지보수 시)
```bash
chattr -i /usr/local/bin/git /usr/bin/git /usr/local/lib/git-guard/realgit/git
# ... 수정 후 다시 잠금 ...
chattr +i /usr/local/bin/git /usr/bin/git /usr/local/lib/git-guard/realgit/git
```
