<?php 
$page_title = "소식·참여 - 한국AI코딩허브협회"; 
include dirname(__DIR__) . '/components/header.php'; 

// DB 연결 및 게시글 가져오기
try {
    $pdo = getDBConnection();
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build query based on category
    if ($category === 'all') {
        $stmt = $pdo->prepare("SELECT * FROM boards WHERE status = 'active' ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $countStmt = $pdo->query("SELECT COUNT(*) FROM boards WHERE status = 'active'");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM boards WHERE status = 'active' AND board_type = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$category, $limit, $offset]);
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE status = 'active' AND board_type = ?");
        $countStmt->execute([$category]);
    }
    $boards = $stmt->fetchAll();
    
    // Get total count
    $totalCount = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalCount / $limit));
} catch (Exception $e) {
    error_log("Board error: " . $e->getMessage());
    $boards = [];
    $totalPages = 1;
    $page = 1;
    $category = 'all';
}
?>

<!-- Hero Section -->
<section class="relative min-h-[50vh] flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-green-900/20 to-gray-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/3 left-1/4 w-96 h-96 bg-green-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/3 right-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse delay-1000"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-green-400 via-blue-400 to-purple-400 text-transparent bg-clip-text">
                소식 · 참여
            </h1>
            <p class="text-xl text-gray-300 mb-4">
                협회의 최신 소식과 공지사항을 확인하세요
            </p>
        </div>
    </div>
</section>

<!-- Board Categories -->
<section class="py-8 bg-gray-950 border-b border-gray-800">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-4">
            <a href="?page=board&category=all" class="px-6 py-2 <?php echo $category === 'all' ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-800'; ?> text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all <?php echo $category !== 'all' ? 'border border-gray-700' : ''; ?>">
                <i class="fas fa-th mr-2"></i>전체
            </a>
            <a href="?page=board&category=notice" class="px-6 py-2 <?php echo $category === 'notice' ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-800'; ?> text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all <?php echo $category !== 'notice' ? 'border border-gray-700' : ''; ?>">
                <i class="fas fa-bullhorn mr-2"></i>공지사항
            </a>
            <a href="?page=board&category=news" class="px-6 py-2 <?php echo $category === 'news' ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-800'; ?> text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all <?php echo $category !== 'news' ? 'border border-gray-700' : ''; ?>">
                <i class="fas fa-newspaper mr-2"></i>뉴스
            </a>
            <a href="?page=board&category=qna" class="px-6 py-2 <?php echo $category === 'qna' ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-800'; ?> text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all <?php echo $category !== 'qna' ? 'border border-gray-700' : ''; ?>">
                <i class="fas fa-question-circle mr-2"></i>Q&A
            </a>
        </div>
    </div>
</section>

<!-- Board List -->
<section class="py-20 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <?php if (empty($boards)): ?>
                <!-- Empty State -->
                <div class="text-center py-20">
                    <div class="text-6xl mb-6">📋</div>
                    <h3 class="text-2xl font-bold text-white mb-4">등록된 게시글이 없습니다</h3>
                    <p class="text-gray-400 mb-8">첫 번째 게시글을 작성해보세요!</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="?page=board&action=write" class="inline-block px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all">
                        <i class="fas fa-pen mr-2"></i>글쓰기
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Board Items -->
                <div class="space-y-4">
                    <?php foreach ($boards as $board): 
                        $types = [
                            'notice' => ['label' => '공지', 'color' => 'from-red-500 to-pink-500', 'icon' => 'fa-bullhorn'],
                            'news' => ['label' => '뉴스', 'color' => 'from-blue-500 to-purple-500', 'icon' => 'fa-newspaper'],
                            'qna' => ['label' => 'Q&A', 'color' => 'from-green-500 to-blue-500', 'icon' => 'fa-question-circle']
                        ];
                        $type = $types[$board['board_type']] ?? ['label' => $board['board_type'], 'color' => 'from-gray-500 to-gray-600', 'icon' => 'fa-file'];
                    ?>
                    <div class="group bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl p-6 border border-gray-700 hover:border-purple-500 transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/20" data-aos="fade-up">
                        <div class="flex flex-col md:flex-row md:items-center gap-4">
                            <!-- Type Badge -->
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r <?php echo $type['color']; ?> text-white rounded-full font-semibold text-sm shadow-lg">
                                    <i class="fas <?php echo $type['icon']; ?> mr-2"></i>
                                    <?php echo $type['label']; ?>
                                </span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-purple-400 transition-colors cursor-pointer">
                                    <?php echo htmlspecialchars($board['title']); ?>
                                </h3>
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400">
                                    <span class="flex items-center">
                                        <i class="far fa-calendar mr-2"></i>
                                        <?php echo formatDate($board['created_at']); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="far fa-eye mr-2"></i>
                                        조회 <?php echo number_format($board['views']); ?>
                                    </span>
                                    <?php if (isset($board['author_name'])): ?>
                                    <span class="flex items-center">
                                        <i class="far fa-user mr-2"></i>
                                        <?php echo htmlspecialchars($board['author_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="flex-shrink-0">
                                <a href="?page=board&action=view&id=<?php echo $board['id']; ?>" class="inline-flex items-center px-6 py-2 bg-gray-700 text-white rounded-full hover:bg-purple-600 transition-all">
                                    자세히 보기
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-12 flex justify-center">
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=board&category=<?php echo $category; ?>&p=<?php echo $page - 1; ?>" class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition-all">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-800 text-gray-600 rounded-lg cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                        <?php endif; ?>
                        
                        <?php
                        // Calculate page range
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        // Show first page if not in range
                        if ($start > 1): ?>
                            <a href="?page=board&category=<?php echo $category; ?>&p=1" class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition-all">1</a>
                            <?php if ($start > 2): ?>
                                <span class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=board&category=<?php echo $category; ?>&p=<?php echo $i; ?>" class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition-all">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php 
                        // Show last page if not in range
                        if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg">...</span>
                            <?php endif; ?>
                            <a href="?page=board&category=<?php echo $category; ?>&p=<?php echo $totalPages; ?>" class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition-all"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=board&category=<?php echo $category; ?>&p=<?php echo $page + 1; ?>" class="px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition-all">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-800 text-gray-600 rounded-lg cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Write Button (for logged in users) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="mt-8 text-center">
                    <a href="?page=board&action=write" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transition-all">
                        <i class="fas fa-pen mr-2"></i>
                        글쓰기
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Board Features -->
<section class="py-20 bg-gray-950">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-400 text-transparent bg-clip-text">
                게시판 기능
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 max-w-5xl mx-auto">
            <a href="#comments" class="bg-gray-800 rounded-xl p-6 text-center border border-gray-700 hover:border-purple-500 hover:shadow-lg hover:shadow-purple-500/20 transition-all duration-300 cursor-pointer" data-aos="fade-up" data-aos-delay="0">
                <div class="text-4xl mb-3">💬</div>
                <h3 class="text-lg font-bold text-white mb-2">댓글 시스템</h3>
                <p class="text-gray-400 text-sm">실시간 소통</p>
            </a>

            <a href="#search" class="bg-gray-800 rounded-xl p-6 text-center border border-gray-700 hover:border-blue-500 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 cursor-pointer" data-aos="fade-up" data-aos-delay="100">
                <div class="text-4xl mb-3">🔍</div>
                <h3 class="text-lg font-bold text-white mb-2">검색 기능</h3>
                <p class="text-gray-400 text-sm">빠른 정보 찾기</p>
            </a>

            <a href="#files" class="bg-gray-800 rounded-xl p-6 text-center border border-gray-700 hover:border-green-500 hover:shadow-lg hover:shadow-green-500/20 transition-all duration-300 cursor-pointer" data-aos="fade-up" data-aos-delay="200">
                <div class="text-4xl mb-3">📎</div>
                <h3 class="text-lg font-bold text-white mb-2">파일 첨부</h3>
                <p class="text-gray-400 text-sm">자료 공유</p>
            </a>

            <a href="#notifications" class="bg-gray-800 rounded-xl p-6 text-center border border-gray-700 hover:border-pink-500 hover:shadow-lg hover:shadow-pink-500/20 transition-all duration-300 cursor-pointer" data-aos="fade-up" data-aos-delay="300">
                <div class="text-4xl mb-3">🔔</div>
                <h3 class="text-lg font-bold text-white mb-2">알림 기능</h3>
                <p class="text-gray-400 text-sm">새 글 알림</p>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-purple-900/30 via-gray-900 to-blue-900/30">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-4xl font-bold mb-6 text-white">
                협회 활동에 참여하세요!
            </h2>
            <p class="text-xl text-gray-300 mb-8">
                다양한 소식과 이벤트를 놓치지 마세요
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="?page=register" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-purple-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-user-plus mr-2"></i>
                    회원가입
                </a>
                <a href="?page=festival" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/50 transform hover:-translate-y-1 transition-all">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    페스티벌 참여
                </a>
            </div>
        </div>
    </div>
</section>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
