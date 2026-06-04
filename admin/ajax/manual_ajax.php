<?php
@header("Content-type: text/html; charset=utf-8");
include_once $_SERVER['DOCUMENT_ROOT']."/lib/rlatjd_fun.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Parsedown.php";

extract($_POST);
$date = date("Y-m-d H:i:s");

// 응답 함수
function send_response($success, $message, $data = null) {
    $response = array(
        "success" => $success,
        "message" => $message
    );
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
    exit;
}

// 입력 검증 함수
function validate_input($field, $value, $required = true) {
    if ($required && empty($value)) {
        send_response(false, $field . " 필수 입력 항목입니다.");
    }
    return mysqli_real_escape_string($GLOBALS['self_con'], $value);
}

// Slug 생성 함수
function generate_slug($title) {
    global $self_con;
    // 한글을 영문으로 변환하는 간단한 로직 (실제로는 더 복잡한 로직 필요)
    $slug = preg_replace('/\s+/', '-', trim($title));
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9\-가-힣]/', '', $slug);
    
    // 중복 체크
    $check_sql = "SELECT COUNT(*) as cnt FROM Gn_Manual_Board WHERE slug = '{$slug}'";
    $result = mysqli_query($self_con, $check_sql);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['cnt'] > 0) {
        $slug .= '-' . time();
    }
    
    return $slug;
}

// 마크다운을 HTML로 변환 (Parsedown 사용)
function markdown_to_html($markdown) {
    try {
        $Parsedown = new Parsedown();
        $Parsedown->setSafeMode(true); // XSS 방지
        $html = $Parsedown->text($markdown);
        return $html;
    } catch (Exception $e) {
        // 오류 발생시 기본 변환
        $html = htmlspecialchars($markdown);
        $html = nl2br($html);
        return $html;
    }
}

// ============================================
// 매뉴얼 목록 조회
// ============================================
if ($mode == "list") {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_POST['search']) ? validate_input("search", $_POST['search'], false) : "";
    $category = isset($_POST['category']) ? validate_input("category", $_POST['category'], false) : "";
    $status = isset($_POST['status']) ? validate_input("status", $_POST['status'], false) : "";
    $sort = isset($_POST['sort']) ? validate_input("sort", $_POST['sort'], false) : "created_at";
    $order = isset($_POST['order']) ? validate_input("order", $_POST['order'], false) : "DESC";
    
    $where = array();
    if ($search) {
        $where[] = "(title LIKE '%{$search}%' OR content LIKE '%{$search}%' OR tags LIKE '%{$search}%')";
    }
    if ($category && $category != 'all') {
        $where[] = "category = '{$category}'";
    }
    if ($status && $status != 'all') {
        $where[] = "status = '{$status}'";
    }
    
    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
    
    // 전체 개수 조회
    $count_sql = "SELECT COUNT(*) as total FROM Gn_Manual_Board {$where_sql}";
    $count_result = mysqli_query($self_con, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'];
    
    // 목록 조회
    $sql = "SELECT 
                m.idx, m.title, m.slug, m.category, m.status, m.view_count, 
                m.chatbot_usage_count, m.created_at, m.updated_at, m.author,
                c.name as category_name, c.color as category_color
            FROM Gn_Manual_Board m
            LEFT JOIN Gn_Manual_Category c ON m.category = c.slug
            {$where_sql}
            ORDER BY {$sort} {$order}
            LIMIT {$offset}, {$limit}";
    
    $result = mysqli_query($self_con, $sql);
    $list = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row;
    }
    
    send_response(true, "조회 성공", array(
        "list" => $list,
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "total_pages" => ceil($total / $limit)
    ));
}

// ============================================
// 매뉴얼 상세 조회
// ============================================
else if ($mode == "detail") {
    $idx = validate_input("idx", $_POST['idx']);
    
    $sql = "SELECT m.*, c.name as category_name 
            FROM Gn_Manual_Board m
            LEFT JOIN Gn_Manual_Category c ON m.category = c.slug
            WHERE m.idx = '{$idx}'";
    
    $result = mysqli_query($self_con, $sql);
    $manual = mysqli_fetch_assoc($result);
    
    if ($manual) {
        send_response(true, "조회 성공", $manual);
    } else {
        send_response(false, "매뉴얼을 찾을 수 없습니다.");
    }
}

// ============================================
// 매뉴얼 생성
// ============================================
else if ($mode == "create") {
    $title = validate_input("제목", $_POST['title']);
    $content = validate_input("내용", $_POST['content']);
    $category = validate_input("카테고리", $_POST['category']);
    
    $slug = isset($_POST['slug']) && $_POST['slug'] ? validate_input("slug", $_POST['slug'], false) : generate_slug($title);
    $summary = isset($_POST['summary']) ? validate_input("summary", $_POST['summary'], false) : "";
    $sub_category = isset($_POST['sub_category']) ? validate_input("sub_category", $_POST['sub_category'], false) : "";
    $tags = isset($_POST['tags']) ? validate_input("tags", $_POST['tags'], false) : "";
    $related_pages = isset($_POST['related_pages']) ? validate_input("related_pages", $_POST['related_pages'], false) : "";
    $faq_data = isset($_POST['faq_data']) ? validate_input("faq_data", $_POST['faq_data'], false) : "";
    $intent_data = isset($_POST['intent_data']) ? validate_input("intent_data", $_POST['intent_data'], false) : "";
    $search_keywords = isset($_POST['search_keywords']) ? validate_input("search_keywords", $_POST['search_keywords'], false) : "";
    $status = isset($_POST['status']) ? validate_input("status", $_POST['status'], false) : "draft";
    $visibility = isset($_POST['visibility']) ? validate_input("visibility", $_POST['visibility'], false) : "public";
    $author = isset($_SESSION['one_member_id']) ? $_SESSION['one_member_id'] : "admin";
    
    // HTML 변환
    $content_html = markdown_to_html($content);
    
    // Slug 중복 확인
    $check_sql = "SELECT COUNT(*) as cnt FROM Gn_Manual_Board WHERE slug = '{$slug}'";
    $check_result = mysqli_query($self_con, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    if ($check_row['cnt'] > 0) {
        send_response(false, "이미 존재하는 Slug입니다. 다른 Slug를 사용해주세요.");
    }
    
    $published_at = ($status == 'published') ? "'{$date}'" : "NULL";
    
    $sql = "INSERT INTO Gn_Manual_Board SET
            title = '{$title}',
            slug = '{$slug}',
            content = '{$content}',
            content_html = '{$content_html}',
            summary = '{$summary}',
            category = '{$category}',
            sub_category = '{$sub_category}',
            tags = '{$tags}',
            related_pages = '{$related_pages}',
            faq_data = '{$faq_data}',
            intent_data = '{$intent_data}',
            search_keywords = '{$search_keywords}',
            status = '{$status}',
            visibility = '{$visibility}',
            author = '{$author}',
            created_at = '{$date}',
            updated_at = '{$date}',
            published_at = {$published_at}";
    
    if (mysqli_query($self_con, $sql)) {
        $new_idx = mysqli_insert_id($self_con);
        send_response(true, "매뉴얼이 생성되었습니다.", array("idx" => $new_idx));
    } else {
        send_response(false, "매뉴얼 생성 실패: " . mysqli_error($self_con));
    }
}

// ============================================
// 매뉴얼 수정
// ============================================
else if ($mode == "update") {
    $idx = validate_input("idx", $_POST['idx']);
    $title = validate_input("제목", $_POST['title']);
    $content = validate_input("내용", $_POST['content']);
    $category = validate_input("카테고리", $_POST['category']);
    
    $slug = isset($_POST['slug']) ? validate_input("slug", $_POST['slug'], false) : "";
    $summary = isset($_POST['summary']) ? validate_input("summary", $_POST['summary'], false) : "";
    $sub_category = isset($_POST['sub_category']) ? validate_input("sub_category", $_POST['sub_category'], false) : "";
    $tags = isset($_POST['tags']) ? validate_input("tags", $_POST['tags'], false) : "";
    $related_pages = isset($_POST['related_pages']) ? validate_input("related_pages", $_POST['related_pages'], false) : "";
    $faq_data = isset($_POST['faq_data']) ? validate_input("faq_data", $_POST['faq_data'], false) : "";
    $intent_data = isset($_POST['intent_data']) ? validate_input("intent_data", $_POST['intent_data'], false) : "";
    $search_keywords = isset($_POST['search_keywords']) ? validate_input("search_keywords", $_POST['search_keywords'], false) : "";
    $status = isset($_POST['status']) ? validate_input("status", $_POST['status'], false) : "draft";
    $visibility = isset($_POST['visibility']) ? validate_input("visibility", $_POST['visibility'], false) : "public";
    $editor = isset($_SESSION['one_member_id']) ? $_SESSION['one_member_id'] : "admin";
    
    // HTML 변환
    $content_html = markdown_to_html($content);
    
    // Slug 중복 확인 (자신 제외)
    if ($slug) {
        $check_sql = "SELECT COUNT(*) as cnt FROM Gn_Manual_Board WHERE slug = '{$slug}' AND idx != '{$idx}'";
        $check_result = mysqli_query($self_con, $check_sql);
        $check_row = mysqli_fetch_assoc($check_result);
        if ($check_row['cnt'] > 0) {
            send_response(false, "이미 존재하는 Slug입니다. 다른 Slug를 사용해주세요.");
        }
    }
    
    // 발행 시간 업데이트
    $published_sql = "";
    if ($status == 'published') {
        // 기존 발행 시간 확인
        $check_published = mysqli_query($self_con, "SELECT published_at FROM Gn_Manual_Board WHERE idx = '{$idx}'");
        $row_published = mysqli_fetch_assoc($check_published);
        if (!$row_published['published_at']) {
            $published_sql = ", published_at = '{$date}'";
        }
    }
    
    $slug_sql = $slug ? ", slug = '{$slug}'" : "";
    
    $sql = "UPDATE Gn_Manual_Board SET
            title = '{$title}',
            content = '{$content}',
            content_html = '{$content_html}',
            summary = '{$summary}',
            category = '{$category}',
            sub_category = '{$sub_category}',
            tags = '{$tags}',
            related_pages = '{$related_pages}',
            faq_data = '{$faq_data}',
            intent_data = '{$intent_data}',
            search_keywords = '{$search_keywords}',
            status = '{$status}',
            visibility = '{$visibility}',
            editor = '{$editor}',
            updated_at = '{$date}'
            {$slug_sql}
            {$published_sql}
            WHERE idx = '{$idx}'";
    
    if (mysqli_query($self_con, $sql)) {
        send_response(true, "매뉴얼이 수정되었습니다.", array("idx" => $idx));
    } else {
        send_response(false, "매뉴얼 수정 실패: " . mysqli_error($self_con));
    }
}

// ============================================
// 매뉴얼 삭제
// ============================================
else if ($mode == "delete") {
    $idx = validate_input("idx", $_POST['idx']);
    
    // 관련 데이터도 함께 삭제
    mysqli_query($self_con, "DELETE FROM Gn_Manual_Search_Keywords WHERE manual_idx = '{$idx}'");
    mysqli_query($self_con, "DELETE FROM Gn_Manual_Bookmark WHERE manual_idx = '{$idx}'");
    
    $sql = "DELETE FROM Gn_Manual_Board WHERE idx = '{$idx}'";
    
    if (mysqli_query($self_con, $sql)) {
        send_response(true, "매뉴얼이 삭제되었습니다.");
    } else {
        send_response(false, "매뉴얼 삭제 실패: " . mysqli_error($self_con));
    }
}

// ============================================
// 상태 변경
// ============================================
else if ($mode == "change_status") {
    $idx = validate_input("idx", $_POST['idx']);
    $status = validate_input("status", $_POST['status']);
    
    if (!in_array($status, array('draft', 'published', 'archived'))) {
        send_response(false, "올바르지 않은 상태값입니다.");
    }
    
    $published_sql = "";
    if ($status == 'published') {
        $check_published = mysqli_query($self_con, "SELECT published_at FROM Gn_Manual_Board WHERE idx = '{$idx}'");
        $row_published = mysqli_fetch_assoc($check_published);
        if (!$row_published['published_at']) {
            $published_sql = ", published_at = '{$date}'";
        }
    }
    
    $sql = "UPDATE Gn_Manual_Board SET status = '{$status}', updated_at = '{$date}' {$published_sql} WHERE idx = '{$idx}'";
    
    if (mysqli_query($self_con, $sql)) {
        send_response(true, "상태가 변경되었습니다.");
    } else {
        send_response(false, "상태 변경 실패: " . mysqli_error($self_con));
    }
}

// ============================================
// 조회수 증가
// ============================================
else if ($mode == "increase_view") {
    $idx = validate_input("idx", $_POST['idx']);
    
    $sql = "UPDATE Gn_Manual_Board SET view_count = view_count + 1 WHERE idx = '{$idx}'";
    
    if (mysqli_query($self_con, $sql)) {
        send_response(true, "조회수가 증가되었습니다.");
    } else {
        send_response(false, "조회수 증가 실패");
    }
}

// ============================================
// 카테고리 목록 조회
// ============================================
else if ($mode == "categories") {
    $sql = "SELECT * FROM Gn_Manual_Category WHERE is_active = 1 ORDER BY order_num ASC";
    $result = mysqli_query($self_con, $sql);
    $categories = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    send_response(true, "조회 성공", $categories);
}

// ============================================
// 통계 조회
// ============================================
else if ($mode == "stats") {
    $total_sql = "SELECT COUNT(*) as total FROM Gn_Manual_Board";
    $published_sql = "SELECT COUNT(*) as published FROM Gn_Manual_Board WHERE status = 'published'";
    $views_sql = "SELECT SUM(view_count) as total_views FROM Gn_Manual_Board";
    $chatbot_sql = "SELECT SUM(chatbot_usage_count) as total_chatbot FROM Gn_Manual_Board";
    
    $total_result = mysqli_fetch_assoc(mysqli_query($self_con, $total_sql));
    $published_result = mysqli_fetch_assoc(mysqli_query($self_con, $published_sql));
    $views_result = mysqli_fetch_assoc(mysqli_query($self_con, $views_sql));
    $chatbot_result = mysqli_fetch_assoc(mysqli_query($self_con, $chatbot_sql));
    
    $stats = array(
        "total_manuals" => $total_result['total'],
        "published_manuals" => $published_result['published'],
        "total_views" => $views_result['total_views'] ?: 0,
        "total_chatbot_usage" => $chatbot_result['total_chatbot'] ?: 0
    );
    
    send_response(true, "조회 성공", $stats);
}

// ============================================
// ★ [신규] 매뉴얼 관리: 메뉴+페이지 트리 조회
// ============================================
else if ($mode == "menu_list") {
    $sql = "SELECT * FROM Gn_Manual_Menu ORDER BY order_num ASC";
    $result = mysqli_query($self_con, $sql);
    $menus = array();
    while ($menu = mysqli_fetch_assoc($result)) {
        $pageSql = "SELECT * FROM Gn_Manual_Page WHERE menu_id={$menu['id']} ORDER BY order_num ASC";
        $pageResult = mysqli_query($self_con, $pageSql);
        $pages = array();
        while ($page = mysqli_fetch_assoc($pageResult)) {
            // 편집 이력 건수
            $histSql = "SELECT COUNT(*) as cnt, MAX(created_at) as last_edit FROM Gn_Manual_Edit_History WHERE page_id={$page['id']}";
            $histResult = mysqli_query($self_con, $histSql);
            $histRow = mysqli_fetch_assoc($histResult);
            $page['history_count'] = (int)$histRow['cnt'];
            $page['last_edit_at'] = $histRow['last_edit'];
            $page['url'] = 'https://kiam.kr/manual/' . $menu['slug'] . '/' . $page['slug'];
            $pages[] = $page;
        }
        $menu['pages'] = $pages;
        $menus[] = $menu;
    }
    send_response(true, "조회 성공", $menus);
}

// ============================================
// ★ [신규] 매뉴얼 관리: 카테고리(메뉴) 토글
// ============================================
else if ($mode == "toggle_menu") {
    $menu_id = validate_input("menu_id", $_POST['menu_id']);
    $is_visible = isset($_POST['is_visible']) ? intval($_POST['is_visible']) : 1;
    $editor = isset($_SESSION['one_member_admin_id']) ? $_SESSION['one_member_admin_id'] : '아리';

    // 메뉴 업데이트
    $q = "UPDATE Gn_Manual_Menu SET is_visible={$is_visible}, updated_at='{$date}' WHERE id={$menu_id}";
    mysqli_query($self_con, $q);

    // 하위 페이지도 동일하게 적용 (ON→전체ON, OFF→전체OFF)
    $pageVis = $is_visible ? 1 : 0;
    mysqli_query($self_con, "UPDATE Gn_Manual_Page SET is_visible={$pageVis}, updated_at='{$date}' WHERE menu_id={$menu_id}");

    // 메뉴명 조회
    $mr = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT name FROM Gn_Manual_Menu WHERE id={$menu_id}"));
    send_response(true, "[" . $mr['name'] . "] " . ($is_visible ? '공개' : '비공개') . "로 변경되었습니다.");
}

// ============================================
// ★ [신규] 매뉴얼 관리: 개별 페이지 토글
// ============================================
else if ($mode == "toggle_page") {
    $page_id = validate_input("page_id", $_POST['page_id']);
    $is_visible = isset($_POST['is_visible']) ? intval($_POST['is_visible']) : 1;
    $editor = isset($_SESSION['one_member_admin_id']) ? $_SESSION['one_member_admin_id'] : '아리';

    $q = "UPDATE Gn_Manual_Page SET is_visible={$is_visible}, updated_at='{$date}' WHERE id={$page_id}";
    mysqli_query($self_con, $q);

    // 모든 페이지가 비공개면 메뉴도 비공개
    $pr = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT menu_id, title FROM Gn_Manual_Page WHERE id={$page_id}"));
    if ($pr) {
        $visCount = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page WHERE menu_id={$pr['menu_id']} AND is_visible=1"));
        if ($visCount['cnt'] == 0) {
            mysqli_query($self_con, "UPDATE Gn_Manual_Menu SET is_visible=0, updated_at='{$date}' WHERE id={$pr['menu_id']}");
        } else {
            mysqli_query($self_con, "UPDATE Gn_Manual_Menu SET is_visible=1, updated_at='{$date}' WHERE id={$pr['menu_id']}");
        }
    }
    send_response(true, "[" . $pr['title'] . "] " . ($is_visible ? '공개' : '비공개') . "로 변경되었습니다.");
}

// ============================================
// ★ [신규] 매뉴얼 관리: 페이지 파일 읽기 (편집용)
// ============================================
else if ($mode == "page_read") {
    $page_id = validate_input("page_id", $_POST['page_id']);
    $r = mysqli_query($self_con, "SELECT p.*, m.slug as menu_slug FROM Gn_Manual_Page p JOIN Gn_Manual_Menu m ON p.menu_id=m.id WHERE p.id={$page_id}");
    $page = mysqli_fetch_assoc($r);
    if (!$page) send_response(false, "페이지를 찾을 수 없습니다.");

    $fullPath = '/home/kiam' . $page['file_path'];
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $page['file_content'] = $content;
        $page['file_size'] = filesize($fullPath);
        send_response(true, "조회 성공", $page);
    } else {
        send_response(false, "파일이 존재하지 않습니다: " . $fullPath);
    }
}

// ============================================
// ★ [신규] 매뉴얼 관리: 페이지 저장 (파일 덮어쓰기 + 이력 기록)
// ============================================
else if ($mode == "page_save") {
    $page_id = validate_input("page_id", $_POST['page_id']);
    $new_content = isset($_POST['content']) ? $_POST['content'] : '';
    $editor = isset($_SESSION['one_member_admin_id']) ? $_SESSION['one_member_admin_id'] : '아리';

    $r = mysqli_query($self_con, "SELECT p.*, m.slug as menu_slug FROM Gn_Manual_Page p JOIN Gn_Manual_Menu m ON p.menu_id=m.id WHERE p.id={$page_id}");
    $page = mysqli_fetch_assoc($r);
    if (!$page) send_response(false, "페이지를 찾을 수 없습니다.");

    $fullPath = '/home/kiam' . $page['file_path'];
    if (!file_exists($fullPath)) send_response(false, "파일이 존재하지 않습니다: " . $fullPath);

    // 이전 내용 해시
    $oldContent = file_get_contents($fullPath);
    $hashBefore = hash('sha256', $oldContent);
    $hashAfter  = hash('sha256', $new_content);

    // 파일 쓰기
    if (file_put_contents($fullPath, $new_content) === false) {
        send_response(false, "파일 저장에 실패했습니다.");
    }

    // diff 요약
    $oldLines = count(explode("\n", $oldContent));
    $newLines = count(explode("\n", $new_content));
    $diff = ($newLines >= $oldLines) ? "+" . ($newLines - $oldLines) : "-" . ($oldLines - $newLines);
    $summary = "{$diff}줄 변경";

    // 이력 기록
    $escEditor = mysqli_real_escape_string($self_con, $editor);
    $escBefore = mysqli_real_escape_string($self_con, $hashBefore);
    $escAfter  = mysqli_real_escape_string($self_con, $hashAfter);
    $escSummary= mysqli_real_escape_string($self_con, $summary);
    mysqli_query($self_con, "INSERT INTO Gn_Manual_Edit_History (page_id, editor, content_hash_before, content_hash_after, diff_summary) VALUES ({$page_id}, '{$escEditor}', '{$escBefore}', '{$escAfter}', '{$escSummary}')");

    // 페이지 업데이트
    $escNewTitle = mysqli_real_escape_string($self_con, $page['title']);
    mysqli_query($self_con, "UPDATE Gn_Manual_Page SET updated_at='{$date}' WHERE id={$page_id}");

    send_response(true, "저장 완료! ({$summary})", array(
        "file_path" => $page['file_path'],
        "diff" => $summary,
        "hash" => substr($hashAfter, 0, 16)
    ));
}

// ============================================
// ★ [신규] 매뉴얼 관리: 편집 이력 조회
// ============================================
else if ($mode == "page_history") {
    $page_id = validate_input("page_id", $_POST['page_id']);
    $r = mysqli_query($self_con, "SELECT h.*, p.title, p.slug, m.name as menu_name 
        FROM Gn_Manual_Edit_History h 
        JOIN Gn_Manual_Page p ON h.page_id=p.id 
        JOIN Gn_Manual_Menu m ON p.menu_id=m.id 
        WHERE h.page_id={$page_id} 
        ORDER BY h.created_at DESC LIMIT 50");
    $history = array();
    while ($row = mysqli_fetch_assoc($r)) $history[] = $row;
    send_response(true, "조회 성공", array("list" => $history, "total" => count($history)));
}

// ============================================
// ★ [신규] 매뉴얼 관리: 전체 편집 이력
// ============================================
else if ($mode == "all_history") {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 100;
    $r = mysqli_query($self_con, "SELECT h.*, p.title, p.slug, p.file_path, m.name as menu_name, m.slug as menu_slug
        FROM Gn_Manual_Edit_History h 
        JOIN Gn_Manual_Page p ON h.page_id=p.id 
        JOIN Gn_Manual_Menu m ON p.menu_id=m.id 
        ORDER BY h.created_at DESC LIMIT {$limit}");
    $history = array();
    while ($row = mysqli_fetch_assoc($r)) $history[] = $row;
    send_response(true, "조회 성공", array("list" => $history, "total" => count($history)));
}

// ============================================
// ★ [신규] 매뉴얼 관리: KPI 대시보드 통계
// ============================================
else if ($mode == "dashboard") {
    $total = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page"));
    $pub   = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page WHERE is_visible=1"));
    $hid   = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Page WHERE is_visible=0"));
    $upd   = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT MAX(updated_at) as last_upd FROM Gn_Manual_Page"));
    $hist  = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Edit_History"));
    $menu  = mysqli_fetch_assoc(mysqli_query($self_con, "SELECT COUNT(*) as cnt FROM Gn_Manual_Menu"));

    $stats = array(
        "total_pages"     => (int)$total['cnt'],
        "public_pages"    => (int)$pub['cnt'],
        "hidden_pages"    => (int)$hid['cnt'],
        "last_updated"    => $upd['last_upd'],
        "total_history"   => (int)$hist['cnt'],
        "total_menus"     => (int)$menu['cnt']
    );
    send_response(true, "조회 성공", $stats);
}

// ============================================
// ★ [신규] 매뉴얼 관리: 디스크 동기화 (신규 파일 자동 등록)
// ============================================
else if ($mode == "scan_sync") {
    $manualRoot = '/home/kiam/manual/';
    $newPages = 0;
    $syncedPages = 0;

    // 기존 메뉴 맵핑
    $menuMap = array();
    $mr = mysqli_query($self_con, "SELECT id, slug FROM Gn_Manual_Menu");
    while ($row = mysqli_fetch_assoc($mr)) $menuMap[$row['slug']] = $row['id'];

    // manual/ 아래 모든 디렉토리 스캔
    $dirs = glob($manualRoot . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $menuSlug = basename($dir);
        if ($menuSlug === 'assets') continue;

        // 메뉴가 없으면 건너뛰기 (수동 등록 필요)
        if (!isset($menuMap[$menuSlug])) continue;
        $menuId = $menuMap[$menuSlug];

        $files = glob($dir . '/*.html');
        $order = 0;
        foreach ($files as $fp) {
            $order++;
            $fn = basename($fp);
            if (strpos($fn, '.bk') !== false || strpos($fn, '.bak') !== false || strpos($fn, '.bun') !== false) continue;

            $eFn = mysqli_real_escape_string($self_con, $fn);
            $check = mysqli_query($self_con, "SELECT id FROM Gn_Manual_Page WHERE menu_id={$menuId} AND slug='{$eFn}'");

            if (mysqli_fetch_assoc($check)) {
                $syncedPages++;
                continue; // 이미 등록됨
            }

            // 신규 등록
            $title = '';
            $c = @file_get_contents($fp);
            if ($c && preg_match('/<title>(.*?)<\/title>/s', $c, $mm)) $title = trim($mm[1]);
            if (!$title && $c && preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $c, $mm)) $title = trim(strip_tags($mm[1]));
            if (!$title) { $title = str_replace('.html', '', $fn); $title = str_replace('-', ' ', $title); }
            $title = str_replace('kiam.kr ', '', $title);
            if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 197) . '...';

            $eTitle = mysqli_real_escape_string($self_con, $title);
            $ePath = mysqli_real_escape_string($self_con, '/manual/' . $menuSlug . '/' . $fn);
            mysqli_query($self_con, "INSERT INTO Gn_Manual_Page (menu_id,title,slug,file_path,order_num,is_visible) VALUES ({$menuId},'{$eTitle}','{$eFn}','{$ePath}',{$order},0)");
            $newPages++;
        }
    }

    send_response(true, "동기화 완료! 신규 {$newPages}건 등록, {$syncedPages}건 유지", array(
        "new_pages" => $newPages,
        "synced_pages" => $syncedPages
    ));
}

// ============================================
// 잘못된 요청
// ============================================
else {
    send_response(false, "올바르지 않은 요청입니다.");
}
?>
