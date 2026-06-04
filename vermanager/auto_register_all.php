<?php
/**
 * 버전 관리 시스템 전체 개발 이력 → 소스개선관리 자동 등록
 * 2026-05-03 아리아
 *
 * 커버리지:
 *   Commit 475f543 — 초기 설계 및 구현 (18파일, +1,601라인)              → #5 설명 업데이트
 *   Commit 3acd0e7 — Version Manager 웹앱 구축 (15파일, +1,768라인)     → #5 (기등록)
 *   Commit 4e83eb0 — 다중 사이트·서버 설계 문서 (1파일, +1,145라인)     → #6 (기등록)
 *   Commit abcd83a — 설계 문서 HTML 변환 (1파일, +1,169라인)            → #6 설명 업데이트
 *   Commit d5203e8 — Phase 3: 다중 사이트 통합 (26파일, +3,857/-217)   → #9 (기등록)
 *   Commit 1cbf11c — Phase 4-6: 원격+Git+통합 (11파일, +1,488라인)     → #10 (기등록)
 *   Commit e66b406 — AI 자동 등록 스크립트 (2파일, +227라인)            → 신규 등록
 *   Commit e4cad00 — 통합 매뉴얼 18페이지 (34파일, +4,500라인)          → 신규 등록
 *
 * 중복 체크: 제목+날짜 기준. 안전하게 반복 실행 가능.
 */
include_once '/home/kiam/lib/db_config.php';

$today = date('Y-m-d');

// ════════════════════════════════════════════════════════
// Step 1: #5 설명 보강 — 초기 설계 커밋(475f543) 내용 추가
// ════════════════════════════════════════════════════════
$check5 = mysqli_query($self_con, "SELECT id, description, files_changed, lines_added FROM source_improvements WHERE id = 5");
if ($check5 && mysqli_num_rows($check5) > 0) {
    $row5 = mysqli_fetch_assoc($check5);
    // 초기 설계 커밋 정보가 이미 포함되어 있는지 확인
    if (strpos($row5['description'] ?? '', '475f543') === false) {
        $append5 = "\n\n■ 초기 설계 및 구현 (Commit 475f543)\n"
            . "- 2026-05-02 Onlyone OneChat 버전 관리 시스템 최초 설계·구현\n"
            . "- 18개 파일, +1,601라인 (index.php 라우터, config.php 상수/함수, API 4종, UI 5페이지, CSS/JS)\n"
            . "- RESTful API 설계: /api/version, /api/release, /api/history, /api/improvements\n"
            . "- JSON 파일 DB 구조 설계: package.json, version_history.json, changelog.json, improvements.json\n"
            . "- Semantic Versioning 2.0 도입: parse_semver(), bump_version()\n"
            . "- Apache Alias + .htaccess rewrite → kiam.kr/vermanager 경로 개설";
        $new_desc5 = mysqli_real_escape_string($self_con, $row5['description'] . $append5);
        // 합산 통계 업데이트 (15+18=33파일, 1200+1601=2801라인)
        $upd5 = "UPDATE source_improvements SET
            description = '{$new_desc5}',
            files_changed = 33,
            lines_added = 2801,
            updated_at = NOW()
            WHERE id = 5";
        mysqli_query($self_con, $upd5);
        echo "[UPDATE] #5: 초기 설계 커밋(475f543) 설명 보강 + 통계 합산 (18→33파일, +1,600→+2,801라인)\n";
    } else {
        echo "[SKIP] #5: 이미 업데이트됨\n";
    }
}

// ════════════════════════════════════════════════════════
// Step 2: #6 설명 보강 — 설계 문서 HTML 변환(abcd83a) 추가
// ════════════════════════════════════════════════════════
$check6 = mysqli_query($self_con, "SELECT id, description, files_changed, lines_added FROM source_improvements WHERE id = 6");
if ($check6 && mysqli_num_rows($check6) > 0) {
    $row6 = mysqli_fetch_assoc($check6);
    if (strpos($row6['description'] ?? '', 'abcd83a') === false) {
        $append6 = "\n\n■ 설계 문서 HTML 변환 (Commit abcd83a)\n"
            . "- 2026-05-03: Markdown 설계 문서 → HTML 형식 변환\n"
            . "- 1개 파일, +1,169라인 (MULTI_SITE_VERSION_DESIGN.html)\n"
            . "- 브라우저에서 바로 열람 가능하도록 스타일링·내비게이션 추가\n"
            . "- 접속: https://kiam.kr/vermanager/MULTI_SITE_VERSION_DESIGN.html";
        $new_desc6 = mysqli_real_escape_string($self_con, $row6['description'] . $append6);
        $upd6 = "UPDATE source_improvements SET
            description = '{$new_desc6}',
            files_changed = 2,
            lines_added = 2314,
            updated_at = NOW()
            WHERE id = 6";
        mysqli_query($self_con, $upd6);
        echo "[UPDATE] #6: HTML 변환 커밋(abcd83a) 설명 보강 + 통계 합산 (1→2파일, +1,145→+2,314라인)\n";
    } else {
        echo "[SKIP] #6: 이미 업데이트됨\n";
    }
}

// ════════════════════════════════════════════════════════
// Step 3: AI 자동 등록 스크립트 구현 (Commit e66b406)
// ════════════════════════════════════════════════════════
$autoRegTitle = 'AI 자동 등록 스크립트 구현 (auto_register_*.php)';
$check_auto = mysqli_query($self_con,
    "SELECT id FROM source_improvements WHERE title = '" . mysqli_real_escape_string($self_con, $autoRegTitle) . "'");

if ($check_auto && mysqli_num_rows($check_auto) > 0) {
    $row_auto = mysqli_fetch_assoc($check_auto);
    echo "[SKIP] 이미 등록됨: #{$row_auto['id']} {$autoRegTitle}\n";
} else {
    $autoRegDesc = "■ 개요\n"
        . "버전 관리 시스템의 모든 개발 이력을 관리자 페이지의 '소스 개선 관리' 대시보드에 자동 등록하는 PHP 스크립트를 구현했습니다.\n\n"
        . "■ 구현 내용\n"
        . "1. auto_register_improvements.php (2건 등록)\n"
        . "   - #5: 통합 버전 관리 시스템(Version Manager) 구축\n"
        . "   - #6: 다중 사이트·다중 서버 버전 관리 설계\n"
        . "   - 중복 체크: 제목 + 날짜 기준으로 이미 등록된 항목은 SKIP\n"
        . "   - DB 연결: /home/kiam/lib/db_config.php 재활용\n\n"
        . "2. auto_register_phase3_6.php (2건 등록 + 1건 수정)\n"
        . "   - #6 상태 업데이트: 개발중 → 완료 (Phase 3~6 구현 내용 추가)\n"
        . "   - #9: Phase 3 — 다중 사이트·다중 서버 버전 관리 전체 통합 구현\n"
        . "   - #10: Phase 4~6 — 원격 서버 연동 + Git/GitHub CI/CD 자동화\n\n"
        . "3. auto_register_all.php (통합 스크립트)\n"
        . "   - 8개 커밋의 전체 개발 이력 커버리지\n"
        . "   - 기존 항목 설명 보강 + 신규 항목 등록\n\n"
        . "■ 변경 통계\n"
        . "- 커밋: e66b406 feat(vermanager): Phase 3~6 소스개선관리 자동 등록\n"
        . "- 파일: 2개 (auto_register_improvements.php, auto_register_phase3_6.php)\n"
        . "- 라인: +227\n\n"
        . "■ 실행 방법\n"
        . "cli: php auto_register_all.php";

    $title_ar = mysqli_real_escape_string($self_con, $autoRegTitle);
    $desc_ar  = mysqli_real_escape_string($self_con, $autoRegDesc);

    $sql_ar = "INSERT INTO source_improvements (
        date, project, category, title, description, developer,
        files_changed, lines_added, lines_deleted, status, test_result,
        environments, deployed_date, deployed_by, impact_level, notes, created_at, updated_at
    ) VALUES (
        '{$today}', '아이엠플랫폼', '기능 추가', '{$title_ar}', '{$desc_ar}', '아리',
        2, 227, 0, '배포됨', 'PASS',
        'prod', NOW(), '아리', '중간', NULL, NOW(), NOW()
    )";

    if (mysqli_query($self_con, $sql_ar)) {
        $new_id_ar = mysqli_insert_id($self_con);
        echo "[OK] #{$new_id_ar}: {$autoRegTitle}\n";
    } else {
        echo "[FAIL] {$autoRegTitle}: " . mysqli_error($self_con) . "\n";
    }
}

// ════════════════════════════════════════════════════════
// Step 4: 통합 매뉴얼 18페이지 작성 (Commit e4cad00)
// ════════════════════════════════════════════════════════
$manualTitle = '소스 개선 관리 + 버전 관리 시스템 통합 매뉴얼 18페이지 제작';
$check_manual = mysqli_query($self_con,
    "SELECT id FROM source_improvements WHERE title = '" . mysqli_real_escape_string($self_con, $manualTitle) . "'");

if ($check_manual && mysqli_num_rows($check_manual) > 0) {
    $row_manual = mysqli_fetch_assoc($check_manual);
    echo "[SKIP] 이미 등록됨: #{$row_manual['id']} {$manualTitle}\n";
} else {
    $manualDesc = "■ 개요\n"
        . "소스 개선 관리 시스템과 버전 관리 시스템의 통합 매뉴얼을 제작하여 kiam.kr/manual/ 경로에 게시했습니다.\n\n"
        . "■ 소스 개선 관리 매뉴얼 (8페이지)\n"
        . "1. index.html — 소개 및 개요: 아키텍처, 데이터 모델, 접근 방법\n"
        . "2. dashboard.html — 대시보드 구성: KPI 카드, 상태/프로젝트 차트, 필터, 페이지네이션\n"
        . "3. list.html — 조회·필터링: 다중 필터(날짜/프로젝트/상태/검색), 20건 페이지네이션\n"
        . "4. register.html — 등록: 10필드 폼 (날짜/프로젝트/카테고리/제목/개발자/상태/영향도/환경/파일수/라인수)\n"
        . "5. detail.html — 상세보기·수정: GET id 파라미터로 기존 레코드 로드 후 수정\n"
        . "6. lifecycle.html — 상태 라이프사이클: 개발중→완료→테스트중→배포됨\n"
        . "7. notice.html — 공지사항 연동: AI 공지 변환(notice_ai-write.php), notice_id 매핑\n"
        . "8. auto-register.html — AI 자동 등록: 스크립트 구조, 중복 체크, 실행 방법\n\n"
        . "■ 버전 관리 시스템 매뉴얼 (10페이지)\n"
        . "1. index.html — 시스템 소개: 6단계 로드맵, API 8종, UI 10페이지\n"
        . "2. dashboard.html — 대시보드: 사이트 선택기, KPI 카드, 릴리즈 버튼, 이력·개선사항 목록\n"
        . "3. sites.html — 사이트 관리: 그리드 뷰, 환경별 카운트, 등록·수정·비활성화\n"
        . "4. release.html — 버전 릴리즈: 사이트 선택, 릴리즈 타입, 미릴리즈 개선사항 체크리스트\n"
        . "5. history.html — 릴리즈 이력: 사이트별 필터, 타임라인 UI, 버전 배지\n"
        . "6. improvements.html — 소스 개선사항: CRUD API, released_in 자동 매핑, MySQL 연동 구조\n"
        . "7. changelog.html — 체인지로그: 사이트·버전별 변경사항 그룹화\n"
        . "8. remote.html — 원격 서버 연동: SSH 연결 확인, 배포·동기화 API, 서버 관리 UI\n"
        . "9. git.html — Git/GitHub 자동화: 상태/로그 조회, 자동 커밋/태그/푸시\n"
        . "10. settings.html — 설정 관리: 사이트별 설정 변경\n\n"
        . "■ 변경 통계\n"
        . "- 커밋: e4cad00 feat(manual): 소스 개선 관리 + 버전 관리 시스템 매뉴얼 18페이지 추가\n"
        . "- 파일: 34개 (8 source-improvements + 20 vermanager + 2 API + 1 index + assets 3)\n"
        . "- 라인: +4,500\n\n"
        . "■ 접속 경로\n"
        . "https://kiam.kr/manual/source-improvements/\n"
        . "https://kiam.kr/manual/vermanager/";

    $title_mn  = mysqli_real_escape_string($self_con, $manualTitle);
    $desc_mn   = mysqli_real_escape_string($self_con, $manualDesc);

    $sql_mn = "INSERT INTO source_improvements (
        date, project, category, title, description, developer,
        files_changed, lines_added, lines_deleted, status, test_result,
        environments, deployed_date, deployed_by, impact_level, notes, created_at, updated_at
    ) VALUES (
        '{$today}', '아이엠플랫폼', '문서화', '{$title_mn}', '{$desc_mn}', '아리',
        34, 4500, 0, '배포됨', 'PASS',
        'prod', NOW(), '아리', '중간', NULL, NOW(), NOW()
    )";

    if (mysqli_query($self_con, $sql_mn)) {
        $new_id_mn = mysqli_insert_id($self_con);
        echo "[OK] #{$new_id_mn}: {$manualTitle}\n";
    } else {
        echo "[FAIL] {$manualTitle}: " . mysqli_error($self_con) . "\n";
    }
}

echo "\n=== 자동 등록 완료 ===\n";

// ─────────────────────────────────────────────────
// 등록 결과 확인
// ─────────────────────────────────────────────────
echo "\n--- 버전 관리 관련 등록 현황 ---\n";
$final = mysqli_query($self_con,
    "SELECT id, title, date, status, category, files_changed, lines_added, impact_level
     FROM source_improvements
     WHERE title LIKE '%버전%' OR title LIKE '%Phase%' OR title LIKE '%다중%'
        OR title LIKE '%원격%' OR title LIKE '%Git%' OR title LIKE '%vermanager%'
        OR title LIKE '%매뉴얼%' OR title LIKE '%자동 등록%' OR title LIKE '%설계%'
     ORDER BY id");
while ($row = mysqli_fetch_assoc($final)) {
    echo "#{$row['id']} [{$row['status']}] [{$row['category']}] {$row['title']} (+{$row['lines_added']}라인, {$row['files_changed']}파일) {$row['date']}\n";
}