<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <meta name="description" content="AI코딩으로 일하고, 연결되고, 수익을 만드는 대한민국 AI코딩 허브 생태계의 중심">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
</head>
<body>
    <header>
        <div class="header-top">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>📧 <?php echo SITE_EMAIL; ?> | 📞 1588-0000</span>
                    <div>
                        <?php if (isLoggedIn()): ?>
                            <?php $user = getCurrentUser(); ?>
                            <span>안녕하세요, <?php echo htmlspecialchars($user['name']); ?>님</span>
                            <a href="?page=mypage" style="color: #fff; margin-left: 15px;">마이페이지</a>
                            <a href="?page=logout" style="color: #fff; margin-left: 15px;">로그아웃</a>
                        <?php else: ?>
                            <a href="?page=login" style="color: #fff;">로그인</a>
                            <a href="?page=register" style="color: #fff; margin-left: 15px;">회원가입</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <a href="<?php echo SITE_URL; ?>" class="logo">
                        <div class="logo-icon">AI</div>
                        <span><?php echo SITE_NAME; ?></span>
                    </a>
                    
                    <nav>
                        <ul>
                            <li><a href="?page=home">홈</a></li>
                            <li><a href="?page=about">협회 소개</a></li>
                            <li><a href="?page=business">사업 안내</a></li>
                            <li><a href="?page=platform">허브플랫폼</a></li>
                            <li><a href="?page=festival">페스티벌</a></li>
                            <li><a href="?page=board">소식</a></li>
                            <li><a href="?page=contact">문의</a></li>
                        </ul>
                    </nav>
                    
                    <div class="user-menu">
                        <?php if (!isLoggedIn()): ?>
                            <a href="?page=register" class="btn btn-primary">회원가입</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main>
