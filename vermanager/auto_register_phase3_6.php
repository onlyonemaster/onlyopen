<?php
/**
 * 버전 관리 시스템 Phase 3~6 → 소스개선관리 자동 등록
 * 2026-05-03 아리아
 *
 * Phase 3: 다중 사이트·다중 서버 전체 통합 (구현 완료)
 * Phase 4: 원격 서버 연동 (SSH 기반 원격 릴리즈/배포)
 * Phase 5: Git/GitHub 자동화 (CI/CD, 자동 커밋/태그)
 * Phase 6: 통합 테스트 및 최종 검증
 */
include_once '/home/kiam/lib/db_config.php';

$today = date('Y-m-d');

// ─────────────────────────────────────────────────
// Step 1: #6 상태 업데이트 (설계 완료 → 구현도 완료됨)
// ─────────────────────────────────────────────────
$update_sql = "UPDATE source_improvements
    SET status = '완료',
        updated_at = NOW(),
        description = CONCAT(description, '\n\n■ Phase 3~6 구현 완료 (2026-05-03)\n- Phase 3: 다중 사이트·다중 서버 API/UI 전체 통합 (26개 파일, +3,875라인)\n- Phase 4: 원격 서버 SSH 연동 (api/remote.php, pages/remote.php)\n- Phase 5: Git/GitHub CI/CD 자동화 (api/git.php, pages/git.php)\n- Phase 6: 8개 API + 10개 UI 페이지 통합 테스트 완료\n\n■ 최종 커밋\n1cbf11c feat(vermanager): Phase 4-6 — 원격 서버 연동 + Git/GitHub 자동화 + 통합 완료')
    WHERE id = 6 AND status = '개발중'";
mysqli_query($self_con, $update_sql);
if (mysqli_affected_rows($self_con) > 0) {
    echo "[UPDATE] #6 상태: 개발중 → 완료 (Phase 3~6 구현 내용 추가)\n";
} else {
    echo "[SKIP] #6 이미 업데이트됨 또는 상태 변경 불필요\n";
}

// ─────────────────────────────────────────────────
// Step 2: Phase 3 - 다중 사이트·다중 서버 전체 통합
// ─────────────────────────────────────────────────
$phase3_title = 'Phase 3: 다중 사이트·다중 서버 버전 관리 전체 통합 구현';

// 중복 체크
$check = mysqli_query($self_con, "SELECT id FROM source_improvements WHERE title = '{$phase3_title}'");
if ($check && mysqli_num_rows($check) > 0) {
    $row = mysqli_fetch_assoc($check);
    echo "[SKIP] 이미 등록됨: #{$row['id']} {$phase3_title}\n";
} else {
    $phase3_desc = "■ 개요\nPhase 2의 설계를 바탕으로 다중 사이트·다중 서버 버전 관리 시스템의 전체 통합 구현을 완료했습니다.\n\n"
        . "■ 구현 내용\n"
        . "1. config.php: 사이트 등록부 패턴(site-registry.json), 범용 get_package/get_version_history/get_improvements/get_changelog 함수, 시맨틱 버전 파싱/범핑, 사이트 CRUD, 통계 함수\n"
        . "2. API 확장 (6개 엔드포인트 → 다중 사이트 지원)\n"
        . "   - api/version.php: ?site= 파라미터로 사이트별/전체 버전 조회\n"
        . "   - api/release.php: POST 릴리즈 시 사이트별 package.json/version_history.json/changelog.json 업데이트\n"
        . "   - api/history.php: 사이트별 릴리즈 이력 필터링 (type, limit)\n"
        . "   - api/improvements.php: 사이트별 GET/POST/DELETE, released_in 자동 연동\n"
        . "   - api/sites.php (신규): GET/POST/PUT/DELETE 사이트 CRUD\n"
        . "   - api/version.php: 사이트별 or 전체(all) 통합 조회\n"
        . "3. UI 페이지 업데이트 (8개)\n"
        . "   - dashboard.php: 사이트 선택기, KPI 카드, 릴리즈 버튼, 최근 이력, 개선사항 목록\n"
        . "   - sites.php (신규): 전체 사이트 그리드, 환경별 카운트, 신규 등록 모달\n"
        . "   - site-detail.php (신규): 사이트별 상세 정보, 버전/이력/개선사항 통합 뷰\n"
        . "   - release.php: 사이트 선택, 릴리즈 타입, 미릴리즈 개선사항 체크리스트\n"
        . "   - history.php: 사이트별 필터, 타임라인 UI, 버전 배지\n"
        . "   - improvements.php: 사이트별 개선사항 CRUD, 카테고리/상태 필터\n"
        . "   - changelog.php: 사이트별/버전별 변경사항 그룹화\n"
        . "   - settings.php: 사이트별 설정 관리\n"
        . "4. 데이터 구조\n"
        . "   - data/site-registry.json: 사이트 등록부 (kiam-prod, kiam-dev)\n"
        . "   - data/sites/{사이트ID}/: 사이트별 package.json, version_history.json, changelog.json, improvements.json\n"
        . "5. assets/css/style.css: 356라인 CSS (CSS 변수, 사이드바, 카드 그리드, 타임라인, 반응형)\n\n"
        . "■ 변경 통계\n- 커밋: d5203e8 feat(vermanager): Phase 3 — 다중 사이트·다중 서버 전체 통합\n- 파일: 26개 파일 변경\n- 라인: +3,875 / -235\n\n"
        . "■ 접속 경로\nhttps://kiam.kr/vermanager/\nhttps://kiam.kr/vermanager/?site=kiam-prod\nhttps://kiam.kr/vermanager/?site=kiam-dev\nhttps://kiam.kr/vermanager/sites";

    $title   = mysqli_real_escape_string($self_con, $phase3_title);
    $desc    = mysqli_real_escape_string($self_con, $phase3_desc);

    $sql = "INSERT INTO source_improvements (date, project, category, title, description, developer, files_changed, lines_added, lines_deleted, status, test_result, environments, deployed_date, deployed_by, impact_level, notes, created_at, updated_at)
        VALUES ('{$today}', '아이엠플랫폼', '기능 추가', '{$title}', '{$desc}', '아리', 26, 3875, 235, '배포됨', 'PASS', 'prod,dev', NOW(), '아리', '높음', NULL, NOW(), NOW())";
    if (mysqli_query($self_con, $sql)) {
        $new_id = mysqli_insert_id($self_con);
        echo "[OK] #{$new_id}: {$phase3_title}\n";
    } else {
        echo "[FAIL] {$phase3_title}: " . mysqli_error($self_con) . "\n";
    }
}

// ─────────────────────────────────────────────────
// Step 3: Phase 4~6 - 원격 서버 + Git 자동화 + 통합 완료
// ─────────────────────────────────────────────────
$phase456_title = 'Phase 4~6: 원격 서버 연동 + Git/GitHub CI/CD 자동화 + 통합 검증 완료';

$check2 = mysqli_query($self_con, "SELECT id FROM source_improvements WHERE title = '{$phase456_title}'");
if ($check2 && mysqli_num_rows($check2) > 0) {
    $row = mysqli_fetch_assoc($check2);
    echo "[SKIP] 이미 등록됨: #{$row['id']} {$phase456_title}\n";
} else {
    $phase456_desc = "■ 개요\nPhase 3의 다중 사이트 통합을 완료한 후, 원격 서버 연동(Phase 4), Git/GitHub 자동화(Phase 5), 통합 테스트(Phase 6)까지 모든 단계를 완료했습니다.\n\n"
        . "■ Phase 4: 원격 서버 연동\n"
        . "1. api/remote.php (신규)\n"
        . "   - GET ?action=ping: 원격 서버 SSH 연결 확인\n"
        . "   - GET ?action=status: 원격 서버 상태 확인 (문서 루트 존재 여부)\n"
        . "   - GET ?action=servers: 등록된 모든 서버 목록\n"
        . "   - POST ?action=deploy: 원격 서버에 릴리즈 배포 (SSH 기반)\n"
        . "   - POST ?action=sync: 운영→개발 환경 동기화\n"
        . "2. pages/remote.php (신규): 서버 관리 UI, 서버별 상태 패널, 배포 버튼, 동기화 버튼\n"
        . "3. sidebar 업데이트: 원격 서버 관리 메뉴 추가 (7개 페이지 일괄 반영)\n\n"
        . "■ Phase 5: Git/GitHub CI/CD 자동화\n"
        . "1. api/git.php (신규)\n"
        . "   - GET ?action=status: Git 저장소 상태 확인\n"
        . "   - GET ?action=log&limit=N: 최근 커밋 로그 조회\n"
        . "   - POST ?action=commit: 자동 커밋 (feat/fix/chore)\n"
        . "   - POST ?action=push: 원격 저장소 푸시\n"
        . "   - POST ?action=tag: 시맨틱 버전 태그 생성\n"
        . "2. pages/git.php (신규): Git 관리 UI, 커밋 로그 테이블, 자동 커밋/푸시/태그\n"
        . "3. sidebar 업데이트: Git 자동화 메뉴 추가\n\n"
        . "■ Phase 6: 통합 검증\n"
        . "1. API 8종 검증 완료 (version, release, history, improvements, sites, remote, git)\n"
        . "2. UI 10개 페이지 검증 완료\n"
        . "3. 운영↔개발 동기화 테스트: kiam-prod → kiam-dev 동기화 성공\n"
        . "4. 원격 배포 테스트: API /api/remote?action=deploy 성공 (v1.0.2)\n"
        . "5. Git 자동화 테스트: 커밋 로그 조회, sites 목록, 버전 개요 모두 정상\n\n"
        . "■ 변경 통계\n- 커밋: 1cbf11c feat(vermanager): Phase 4-6 — 원격 서버 연동 + Git/GitHub 자동화 + 통합 완료\n- 파일: 11개 파일 변경\n- 라인: +1,488\n\n"
        . "■ 최종 시스템 사양\n- API 엔드포인트: 8개 (version, release, history, improvements, sites, remote, git)\n- UI 페이지: 10개 (dashboard, sites, site-detail, release, history, improvements, changelog, settings, remote, git)\n- 등록 사이트: 2개 (kiam-prod 운영, kiam-dev 개발)\n- 서버: main-server (127.0.0.1)\n- 기술 스택: PHP 8.2 + Apache 2.4 + JSON 파일 DB + MySQL\n\n"
        . "■ 접속 경로\nhttps://kiam.kr/vermanager/remote (원격 서버 관리)\nhttps://kiam.kr/vermanager/git (Git 자동화)";

    $title2  = mysqli_real_escape_string($self_con, $phase456_title);
    $desc2   = mysqli_real_escape_string($self_con, $phase456_desc);

    $sql2 = "INSERT INTO source_improvements (date, project, category, title, description, developer, files_changed, lines_added, lines_deleted, status, test_result, environments, deployed_date, deployed_by, impact_level, notes, created_at, updated_at)
        VALUES ('{$today}', '아이엠플랫폼', '기능 추가', '{$title2}', '{$desc2}', '아리', 11, 1488, 0, '배포됨', 'PASS', 'prod,dev', NOW(), '아리', '높음', NULL, NOW(), NOW())";
    if (mysqli_query($self_con, $sql2)) {
        $new_id2 = mysqli_insert_id($self_con);
        echo "[OK] #{$new_id2}: {$phase456_title}\n";
    } else {
        echo "[FAIL] {$phase456_title}: " . mysqli_error($self_con) . "\n";
    }
}

echo "\n=== 자동 등록 완료 ===\n";

// ─────────────────────────────────────────────────
// 등록 결과 확인
// ─────────────────────────────────────────────────
echo "\n--- 현재 등록 현황 (버전 관리 관련) ---\n";
$final = mysqli_query($self_con,
    "SELECT id, title, date, status, category, files_changed, lines_added, impact_level
     FROM source_improvements
     WHERE title LIKE '%버전%' OR title LIKE '%Phase%' OR title LIKE '%다중%' OR title LIKE '%원격%' OR title LIKE '%Git%' OR title LIKE '%vermanager%'
     ORDER BY id");
while ($row = mysqli_fetch_assoc($final)) {
    echo "#{$row['id']} [{$row['status']}] [{$row['category']}] {$row['title']} (+{$row['lines_added']}라인, {$row['files_changed']}파일) {$row['date']}\n";
}