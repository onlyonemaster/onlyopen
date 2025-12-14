<?php
require_once __DIR__ . '/../../config/database.php';

// Connect to database
try {
    $pdo = getDBConnection();
    echo "Database connected successfully!\n\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Sample data arrays
$memberTypes = [
    'individual' => [
        ['name' => '김개발', 'email' => 'developer1@test.com'],
        ['name' => '이코딩', 'email' => 'developer2@test.com'],
        ['name' => '박프로', 'email' => 'developer3@test.com'],
        ['name' => '최풀스택', 'email' => 'developer4@test.com'],
        ['name' => '정백엔드', 'email' => 'developer5@test.com'],
    ],
    'company' => [
        ['name' => '테크혁신㈜', 'email' => 'company1@test.com'],
        ['name' => 'AI솔루션즈', 'email' => 'company2@test.com'],
        ['name' => '스타트업코리아', 'email' => 'company3@test.com'],
        ['name' => '디지털플랫폼', 'email' => 'company4@test.com'],
        ['name' => '클라우드서비스', 'email' => 'company5@test.com'],
    ],
    'education' => [
        ['name' => '서울대학교', 'email' => 'edu1@test.com'],
        ['name' => '한국기술교육대학교', 'email' => 'edu2@test.com'],
        ['name' => 'AI코딩아카데미', 'email' => 'edu3@test.com'],
        ['name' => '한국폴리텍대학', 'email' => 'edu4@test.com'],
        ['name' => '디지털교육원', 'email' => 'edu5@test.com'],
    ],
    'team' => [
        ['name' => 'AI개발팀', 'email' => 'team1@test.com'],
        ['name' => '풀스택개발자팀', 'email' => 'team2@test.com'],
        ['name' => '스타트업팀A', 'email' => 'team3@test.com'],
        ['name' => '프로젝트팀B', 'email' => 'team4@test.com'],
        ['name' => '혁신개발팀', 'email' => 'team5@test.com'],
    ]
];

$password = password_hash('Test1234!', PASSWORD_BCRYPT);
$successCount = 0;
$errorCount = 0;

echo "=== 샘플 회원 등록 시작 ===\n\n";

foreach ($memberTypes as $type => $members) {
    echo "[$type 회원 등록]\n";
    echo str_repeat('-', 50) . "\n";
    
    foreach ($members as $member) {
        try {
            $pdo->beginTransaction();
            
            // Insert into members table
            $stmt = $pdo->prepare("
                INSERT INTO members (member_type, email, password, name, phone, status)
                VALUES (:member_type, :email, :password, :name, :phone, 'active')
            ");
            
            $stmt->execute([
                'member_type' => $type,
                'email' => $member['email'],
                'password' => $password,
                'name' => $member['name'],
                'phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999)
            ]);
            
            $memberId = $pdo->lastInsertId();
            
            // Insert into profile tables based on member type
            switch ($type) {
                case 'individual':
                    $stmt = $pdo->prepare("
                        INSERT INTO developer_profiles (member_id, skills, experience_years, level, bio)
                        VALUES (:member_id, :skills, :experience_years, :level, :bio)
                    ");
                    $stmt->execute([
                        'member_id' => $memberId,
                        'skills' => json_encode(['PHP', 'JavaScript', 'MySQL', 'React']),
                        'experience_years' => rand(1, 10),
                        'level' => ['beginner', 'junior', 'intermediate', 'advanced', 'expert'][rand(0, 4)],
                        'bio' => $member['name'] . '의 프로필입니다.'
                    ]);
                    break;
                    
                case 'company':
                    $stmt = $pdo->prepare("
                        INSERT INTO company_profiles (member_id, company_name, industry, company_size, description)
                        VALUES (:member_id, :company_name, :industry, :company_size, :description)
                    ");
                    $stmt->execute([
                        'member_id' => $memberId,
                        'company_name' => $member['name'],
                        'industry' => ['IT', '소프트웨어', 'AI', '클라우드'][rand(0, 3)],
                        'company_size' => ['startup', 'small', 'medium', 'large'][rand(0, 3)],
                        'description' => $member['name'] . ' 회사 소개입니다.'
                    ]);
                    break;
                    
                case 'education':
                    $stmt = $pdo->prepare("
                        INSERT INTO education_profiles (member_id, institution_name, institution_type, description)
                        VALUES (:member_id, :institution_name, :institution_type, :description)
                    ");
                    $stmt->execute([
                        'member_id' => $memberId,
                        'institution_name' => $member['name'],
                        'institution_type' => ['university', 'academy', 'vocational', 'research'][rand(0, 3)],
                        'description' => $member['name'] . ' 교육기관 소개입니다.'
                    ]);
                    break;
                    
                case 'team':
                    $stmt = $pdo->prepare("
                        INSERT INTO teams (team_name, leader_member_id, description, team_size)
                        VALUES (:team_name, :leader_member_id, :description, :team_size)
                    ");
                    $stmt->execute([
                        'team_name' => $member['name'],
                        'leader_member_id' => $memberId,
                        'description' => $member['name'] . ' 팀 소개입니다.',
                        'team_size' => rand(3, 15)
                    ]);
                    break;
            }
            
            $pdo->commit();
            $successCount++;
            
            echo "✓ {$member['name']} ({$member['email']}) - 등록 완료\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorCount++;
            echo "✗ {$member['name']} ({$member['email']}) - 오류: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "등록 완료 통계\n";
echo str_repeat('=', 50) . "\n";
echo "성공: {$successCount}명\n";
echo "실패: {$errorCount}명\n";
echo "총계: " . ($successCount + $errorCount) . "명\n\n";

// Show current member counts
echo "=== 회원 유형별 현황 ===\n";
$stmt = $pdo->query("SELECT member_type, COUNT(*) as count FROM members GROUP BY member_type");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $typeNames = [
        'individual' => '개인 개발자',
        'company' => '기업',
        'education' => '교육기관',
        'team' => '팀'
    ];
    echo "{$typeNames[$row['member_type']]}: {$row['count']}명\n";
}

echo "\n테스트 계정 비밀번호: Test1234!\n";
echo "완료!\n";
