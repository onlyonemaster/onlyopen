<?php
/**
 * 매뉴얼 관리 시스템 DB 마이그레이션 + 초기 시드
 * 실행: php setup_manual_tables.php 또는 브라우저에서 접속
 */
if (empty($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = '/home/kiam';
// CLI 환경 보정
if (php_sapi_name() === 'cli') {
    $_SERVER['DOCUMENT_ROOT'] = '/home/kiam';
    chdir('/home/kiam');
}
include_once '/home/kiam/lib/db_config.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h2>매뉴얼 관리 DB 마이그레이션</h2><pre>';

// ============================================
// 1. 테이블 생성
// ============================================
$tables = [
    "Gn_Manual_Menu" => "
        CREATE TABLE IF NOT EXISTS Gn_Manual_Menu (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL 슬러그',
            name VARCHAR(100) NOT NULL COMMENT '표시명',
            icon VARCHAR(10) DEFAULT '' COMMENT '아이콘 이모지',
            tag VARCHAR(20) DEFAULT '사용 가이드' COMMENT '태그',
            order_num INT DEFAULT 0 COMMENT '정렬 순서',
            is_visible TINYINT(1) DEFAULT 1 COMMENT '카테고리 공개/비공개',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    "Gn_Manual_Page" => "
        CREATE TABLE IF NOT EXISTS Gn_Manual_Page (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_id INT NOT NULL COMMENT 'FK: Gn_Manual_Menu.id',
            title VARCHAR(200) NOT NULL COMMENT '페이지 제목',
            slug VARCHAR(100) NOT NULL COMMENT '파일명',
            file_path VARCHAR(255) NOT NULL COMMENT '전체 경로',
            order_num INT DEFAULT 0 COMMENT '정렬 순서',
            is_visible TINYINT(1) DEFAULT 1 COMMENT '공개/비공개',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_menu_slug (menu_id, slug),
            FOREIGN KEY (menu_id) REFERENCES Gn_Manual_Menu(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    "Gn_Manual_Edit_History" => "
        CREATE TABLE IF NOT EXISTS Gn_Manual_Edit_History (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL COMMENT 'FK: Gn_Manual_Page.id',
            editor VARCHAR(50) DEFAULT '아리' COMMENT '편집자',
            content_hash_before VARCHAR(64) DEFAULT '' COMMENT '변경 전 SHA256',
            content_hash_after VARCHAR(64) DEFAULT '' COMMENT '변경 후 SHA256',
            diff_summary VARCHAR(500) DEFAULT '' COMMENT '변경 요약',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES Gn_Manual_Page(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

foreach ($tables as $name => $sql) {
    if (mysqli_query($self_con, $sql)) {
        echo "[OK] 테이블 생성: {$name}\n";
    } else {
        echo "[FAIL] {$name}: " . mysqli_error($self_con) . "\n";
    }
}

// ============================================
// 2. 초기 데이터 시드 (디스크 스캔)
// ============================================
echo "\n--- 디스크 스캔 및 시드 ---\n";

$manualRoot = $_SERVER['DOCUMENT_ROOT'] . '/manual/';
$categoryMeta = [
    'account'             => ['name'=>'계정 & 설정',       'icon'=>'⚙️', 'tag'=>'기본 설정',   'ord'=>10],
    'aimessage'           => ['name'=>'AI 메시지',          'icon'=>'✉️', 'tag'=>'사용 가이드', 'ord'=>2],
    'avatar'              => ['name'=>'AI 아바타 학습',     'icon'=>'🤖', 'tag'=>'사용 가이드', 'ord'=>3],
    'dashboard'           => ['name'=>'대시보드 & 분석',    'icon'=>'📊', 'tag'=>'사용 가이드', 'ord'=>6],
    'diver'               => ['name'=>'Diver',              'icon'=>'🔱', 'tag'=>'사용 가이드', 'ord'=>13],
    'funnel'              => ['name'=>'퍼널 관리',          'icon'=>'🔻', 'tag'=>'사용 가이드', 'ord'=>14],
    'namecard'            => ['name'=>'명함 관리',          'icon'=>'📇', 'tag'=>'사용 가이드', 'ord'=>4],
    'onechat'             => ['name'=>'원챗 (OneChat)',     'icon'=>'💬', 'tag'=>'사용 가이드', 'ord'=>1],
    'papercard'           => ['name'=>'명함 프로필',        'icon'=>'🃏', 'tag'=>'소스 분석',   'ord'=>5],
    'sms'                 => ['name'=>'문자 발송',          'icon'=>'📱', 'tag'=>'사용 가이드', 'ord'=>12],
    'source-improvements' => ['name'=>'소스 개선 관리',     'icon'=>'📋', 'tag'=>'관리자 도구', 'ord'=>7],
    'support'             => ['name'=>'고객 지원',          'icon'=>'🎧', 'tag'=>'사용 가이드', 'ord'=>15],
    'vag'                 => ['name'=>'VAG 영상팩토리',     'icon'=>'🎬', 'tag'=>'사용 가이드', 'ord'=>9],
    'vermanager'          => ['name'=>'버전 관리 시스템',   'icon'=>'🔄', 'tag'=>'관리자 도구', 'ord'=>8],
    'voicegen'            => ['name'=>'VoiceGen AI',        'icon'=>'🔊', 'tag'=>'사용 가이드', 'ord'=>11],
];

$totalCreated = 0;
$totalSkipped = 0;

foreach ($categoryMeta as $slug => $meta) {
    $dir = $manualRoot . $slug;
    if (!is_dir($dir)) continue;

    // 메뉴 등록 or 조회
    $escSlug = mysqli_real_escape_string($self_con, $slug);
    $escName = mysqli_real_escape_string($self_con, $meta['name']);
    $escIcon = mysqli_real_escape_string($self_con, $meta['icon']);
    $escTag  = mysqli_real_escape_string($self_con, $meta['tag']);
    $ord     = (int)$meta['ord'];

    $check = mysqli_query($self_con, "SELECT id FROM Gn_Manual_Menu WHERE slug='{$escSlug}'");
    if ($row = mysqli_fetch_assoc($check)) {
        $menuId = $row['id'];
        // 업데이트
        mysqli_query($self_con, "UPDATE Gn_Manual_Menu SET name='{$escName}', icon='{$escIcon}', tag='{$escTag}', order_num={$ord} WHERE id={$menuId}");
        echo "[SKIP] 메뉴 기존: {$meta['name']} (id={$menuId})\n";
    } else {
        mysqli_query($self_con, "INSERT INTO Gn_Manual_Menu (slug,name,icon,tag,order_num,is_visible) VALUES ('{$escSlug}','{$escName}','{$escIcon}','{$escTag}',{$ord},1)");
        $menuId = mysqli_insert_id($self_con);
        echo "[NEW]  메뉴 생성: {$meta['name']} (id={$menuId})\n";
    }

    // 페이지 스캔
    $files = glob($dir . '/*.html');
    $pageOrder = 0;
    foreach ($files as $filePath) {
        $pageOrder++;
        $fileName = basename($filePath);
        $pageSlug = $fileName;

        // 백업 파일/중복 건너뛰기
        if (strpos($fileName, '.bk') !== false || strpos($fileName, '.bak') !== false || strpos($fileName, '.bun') !== false) continue;

        // 타이틀 추출: 파일에서 <title> 읽기 시도
        $title = '';
        $content = @file_get_contents($filePath);
        if ($content && preg_match('/<title>(.*?)<\/title>/s', $content, $m)) {
            $title = trim($m[1]);
        }
        if (!$title && $content && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $content, $m)) {
            $title = trim(strip_tags($m[1]));
        }
        if (!$title) {
            // 파일명으로 추정
            $title = str_replace('.html', '', $fileName);
            $title = str_replace('-', ' ', $title);
            $title = ucwords($title);
        }
        // kiam.kr 접두사 제거
        $title = str_replace('kiam.kr ', '', $title);
        $title = str_replace('kiam.kr', '', $title);

        $relPath = '/manual/' . $slug . '/' . $fileName;
        $escTitle    = mysqli_real_escape_string($self_con, $title);
        $escPageSlug = mysqli_real_escape_string($self_con, $pageSlug);
        $escFilePath = mysqli_real_escape_string($self_con, $relPath);

        $checkP = mysqli_query($self_con, "SELECT id FROM Gn_Manual_Page WHERE menu_id={$menuId} AND slug='{$escPageSlug}'");
        if ($prow = mysqli_fetch_assoc($checkP)) {
            $totalSkipped++;
            // 타이틀만 업데이트
            mysqli_query($self_con, "UPDATE Gn_Manual_Page SET title='{$escTitle}', order_num={$pageOrder} WHERE id={$prow['id']}");
        } else {
            $totalCreated++;
            mysqli_query($self_con, "INSERT INTO Gn_Manual_Page (menu_id,title,slug,file_path,order_num,is_visible) VALUES ({$menuId},'{$escTitle}','{$escPageSlug}','{$escFilePath}',{$pageOrder},1)");
            echo "  [NEW]  페이지: {$title} ({$pageSlug})\n";
        }
    }
}

echo "\n=== 완료 ===\n";
echo "신규 등록: {$totalCreated} 페이지\n";
echo "기존 유지: {$totalSkipped} 페이지\n";

// 통계
$r = mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Menu");
$menuCnt = mysqli_fetch_assoc($r)['cnt'];
$r = mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page");
$pageCnt = mysqli_fetch_assoc($r)['cnt'];
echo "\n총 메뉴: {$menuCnt}개, 총 페이지: {$pageCnt}개\n";
echo '</pre>';