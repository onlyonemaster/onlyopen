<?php
/**
 * 버전 관리 시스템 개발 → 소스개선관리 자동 등록
 * 2026-05-03 아리아
 */
include_once '/home/kiam/lib/db_config.php';

// ── 등록할 개선 항목들 ──
$improvements = [
    [
        'title'       => '통합 버전 관리 시스템(Version Manager) 구축',
        'description' => "■ 개요\nOnlyone OneChat의 버전 관리 시스템을 kiam.kr/vermanager 경로에 구축했습니다.\n\n■ 구현 내용\n- Apache Alias 설정: /vermanager → /home/vermanager (httpd.conf 서버 레벨)\n- RESTful API: 버전 조회(/api/version), 릴리즈(/api/release), 이력(/api/history), 개선사항(/api/improvements)\n- 대시보드 UI: 현재 버전, 릴리즈 버튼, 체인지로그, 개선사항 관리\n- JSON 파일 기반 데이터 저장: package.json, version_history.json, changelog.json, improvements.json\n- Semantic Versioning (SemVer) 지원: MAJOR.MINOR.PATCH\n\n■ 접속 경로\nhttps://kiam.kr/vermanager\n\n■ 기술 스택\nPHP 8.2 + Apache 2.4 + JSON 파일 DB",
        'project'    => '아이엠플랫폼',
        'category'   => '기능 추가',
        'status'     => '배포됨',
        'impact'     => '중간',
        'test'       => 'PASS',
        'envs'       => 'prod',
        'deployer'   => '아리',
        'files'      => 15,
        'added'      => 1200,
        'deleted'    => 50,
    ],
    [
        'title'       => '다중 사이트·다중 서버 버전 관리 설계',
        'description' => "■ 개요\n현재 서버에 운영 중인 7개 사이트(kiam.kr, dev.kiam.kr, chatbot.kiam.kr, superchatbot.kiam.kr, spscenter.net 등)와 향후 최대 5대 서버의 모든 소프트웨어 버전을 통합 관리할 수 있는 확장 설계를 완료했습니다.\n\n■ 설계 핵심\n1. 사이트 등록부 패턴: site-registry.json으로 모든 사이트를 중앙 등록\n2. 사이트별 독립 데이터: data/sites/{사이트ID}/ 아래 각 사이트별 버전/이력/체인지로그 저장\n3. 운영·개발 분리: environment 필드로 production/development 구분\n4. 부모-자식 연결: parent_site_id로 운영↔개발 관계 추적\n5. 다중 서버 지원: 원격 버전 알리미/API 연동 설계\n\n■ 설계 문서\nhttps://kiam.kr/vermanager/MULTI_SITE_VERSION_DESIGN.html\n\n■ 6단계 로드맵\n데이터구조 → 설정개편 → API수정 → UI개선 → 원격연동 → Git자동화",
        'project'    => '아이엠플랫폼',
        'category'   => '리팩토링',
        'status'     => '개발중',
        'impact'     => '높음',
        'test'       => 'PASS',
        'envs'       => 'dev,test',
        'deployer'   => '아리',
        'files'      => 1,
        'added'      => 1145,
        'deleted'    => 0,
    ],
];

$today = date('Y-m-d');

foreach ($improvements as $imp) {
    $title   = mysqli_real_escape_string($self_con, $imp['title']);
    $desc    = mysqli_real_escape_string($self_con, $imp['description']);
    $project = mysqli_real_escape_string($self_con, $imp['project']);
    $cat     = mysqli_real_escape_string($self_con, $imp['category']);
    $status  = mysqli_real_escape_string($self_con, $imp['status']);
    $impact  = mysqli_real_escape_string($self_con, $imp['impact']);
    $test    = mysqli_real_escape_string($self_con, $imp['test']);
    $envs    = mysqli_real_escape_string($self_con, $imp['envs']);
    $dep     = mysqli_real_escape_string($self_con, $imp['deployer']);
    $files   = (int)$imp['files'];
    $added   = (int)$imp['added'];
    $deleted = (int)$imp['deleted'];

    // 중복 체크 (같은 제목이 오늘 이미 등록됐는지)
    $check_sql = "SELECT id FROM source_improvements WHERE title = '{$title}' AND date = '{$today}'";
    $check_res = mysqli_query($self_con, $check_sql);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        echo "[SKIP] 이미 등록됨: {$title}\n";
        continue;
    }

    $sql = "INSERT INTO source_improvements (
        date, project, category, title, description, developer,
        files_changed, lines_added, lines_deleted, status, test_result,
        environments, deployed_date, deployed_by, impact_level, notes, created_at, updated_at
    ) VALUES (
        '{$today}', '{$project}', '{$cat}', '{$title}', '{$desc}', '아리',
        {$files}, {$added}, {$deleted}, '{$status}', '{$test}',
        '{$envs}', NOW(), '{$dep}', '{$impact}', NULL, NOW(), NOW()
    )";

    if (mysqli_query($self_con, $sql)) {
        $new_id = mysqli_insert_id($self_con);
        echo "[OK] #{$new_id}: {$title}\n";
    } else {
        echo "[FAIL] {$title}: " . mysqli_error($self_con) . "\n";
    }
}

echo "\n=== 완료 ===\n";
