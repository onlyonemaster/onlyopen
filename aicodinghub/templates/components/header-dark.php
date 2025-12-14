<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? '한국AI코딩허브협회'; ?> - AI코딩으로 일하고, 연결되고, 수익을 만드는</title>
    <meta name="description" content="한국AI코딩허브협회 - AI코딩 생태계의 중심, 교육-실전-수익을 하나로 연결합니다">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Dark Theme CSS -->
    <link rel="stylesheet" href="/css/dark-theme.css">
    
    <style>
        /* Additional Custom Styles */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Mobile Menu Toggle */
        #mobile-menu {
            display: none;
        }
        
        #mobile-menu.show {
            display: block;
        }
        
        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Navigation -->
    <nav class="fixed w-full bg-black/90 backdrop-blur-lg z-50 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/?page=home" class="flex items-center">
                        <i class="fas fa-rocket text-3xl text-purple-400 mr-3 cyber-glow"></i>
                        <span class="text-xl font-bold text-white hidden sm:inline">한국AI코딩허브협회</span>
                        <span class="text-lg font-bold text-white sm:hidden">AI코딩허브</span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/?page=home" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        홈
                    </a>
                    <a href="/?page=about" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        협회소개
                    </a>
                    <a href="/?page=business" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        사업안내
                    </a>
                    <a href="/?page=platform" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        허브플랫폼
                    </a>
                    <a href="/?page=festival" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        페스티벌
                    </a>
                    <a href="/?page=board" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        게시판
                    </a>
                    <a href="/?page=contact" class="text-gray-300 hover:text-purple-400 px-3 py-2 rounded-md text-sm font-medium transition-all hover:bg-gray-800">
                        문의
                    </a>
                    <a href="/?page=login" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>로그인
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-300 hover:text-white">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden bg-gray-900 border-t border-gray-800">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/?page=home" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    홈
                </a>
                <a href="/?page=about" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    협회소개
                </a>
                <a href="/?page=business" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    사업안내
                </a>
                <a href="/?page=platform" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    허브플랫폼
                </a>
                <a href="/?page=festival" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    페스티벌
                </a>
                <a href="/?page=board" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    게시판
                </a>
                <a href="/?page=contact" class="text-gray-300 hover:text-purple-400 hover:bg-gray-800 block px-3 py-2 rounded-md text-base font-medium">
                    문의
                </a>
                <a href="/?page=login" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white block px-3 py-2 rounded-md text-base font-medium text-center">
                    로그인
                </a>
            </div>
        </div>
    </nav>
    
    <main class="pt-16">
