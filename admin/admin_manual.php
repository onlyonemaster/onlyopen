<?php
/**
 * 매뉴얼 관리 페이지 (2026-05-03 by 아리아)
 * - 카테고리별 아코디언 + 공개/비공개 토글
 * - 개별 페이지 공개/비공개 체크박스
 * - 디스크 동기화 (신규 페이지 자동 등록)
 * - 링크 복사, 편집, 이력 조회
 */
include_once $_SERVER['DOCUMENT_ROOT']."/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_header.inc.php";
extract($_GET);
$date_today = date("Y-m-d");
?>
<script type="text/javascript" src="/jquery.lightbox_me.js"></script>
<style>
.loading_div{display:none;position:fixed;left:50%;top:50%;z-index:1000;}
.wrapper{height:100%;overflow:auto !important;}
.content-wrapper{min-height:80% !important;}

/* 토스트 */
.toast{position:fixed;bottom:30px;right:30px;background:#333;color:#fff;padding:12px 24px;border-radius:6px;z-index:9999;font-size:14px;opacity:0;transition:opacity .3s;box-shadow:0 4px 12px rgba(0,0,0,.3);}
.toast.show{opacity:1;}
.toast.success{background:#16a34a;}
.toast.error{background:#dc2626;}

/* KPI 카드 */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;}
.kpi-card{background:#fff;border-radius:6px;padding:18px 20px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.08);border-top:3px solid #2563eb;}
.kpi-card.kpi-green{border-top-color:#16a34a;}
.kpi-card.kpi-orange{border-top-color:#ea580c;}
.kpi-card.kpi-purple{border-top-color:#7c3aed;}
.kpi-kvs{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:20px;}
.kpi-kv{background:#fff;border-radius:4px;padding:10px 18px;box-shadow:0 1px 2px rgba(0,0,0,.06);font-size:13px;display:flex;align-items:center;gap:8px;}
.kpi-kv .kv-num{font-size:22px;font-weight:800;color:#2563eb;}
.kpi-kv .kv-label{color:#888;}

/* 아코디언 패널 */
.cat-panel{border:1px solid #d2d6de;border-radius:4px;margin-bottom:10px;background:#fff;}
.cat-header{padding:12px 16px;background:#f9fafc;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid transparent;transition:background .2s;}
.cat-header:hover{background:#f0f4f8;}
.cat-header.open{border-bottom-color:#d2d6de;font-weight:bold;}
.cat-left{display:flex;align-items:center;gap:12px;}
.cat-icon{font-size:22px;}
.cat-name{font-size:15px;font-weight:700;color:#333;}
.cat-tag{font-size:11px;padding:2px 8px;border-radius:3px;background:#e8f0fe;color:#2563eb;font-weight:600;}
.cat-tag.admin{background:#fef3c7;color:#b45309;}
.cat-right{display:flex;align-items:center;gap:16px;}
.cat-count{font-size:12px;color:#888;}
.cat-arrow{transition:transform .2s;color:#888;font-size:14px;}
.cat-header.open .cat-arrow{transform:rotate(180deg);}
.cat-body{display:none;padding:8px 0;}
.cat-body.open{display:block;}
.page-row{display:flex;align-items:center;justify-content:space-between;padding:9px 16px 9px 48px;border-bottom:1px solid #f0f0f0;transition:background .15s;}
.page-row:hover{background:#fafbfc;}
.page-row:last-child{border-bottom:none;}
.page-left{display:flex;align-items:center;gap:10px;}
.page-title{font-size:13px;color:#333;}
.page-slug{font-size:11px;color:#999;margin-left:6px;}
.page-actions{display:flex;gap:6px;}

/* 토글 스위치 */
.switch-wrap{display:flex;align-items:center;gap:8px;}
.switch{position:relative;display:inline-block;width:44px;height:24px;}
.switch input{display:none;}
.slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.3s;}
.slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
input:checked+.slider{background:#2563eb;}
input:checked+.slider:before{transform:translateX(20px);}
.switch-label{font-size:12px;color:#666;min-width:36px;}

/* 액션 버튼 */
.act-btn{cursor:pointer;color:#666;font-size:13px;padding:2px 6px;border-radius:3px;transition:.15s;text-decoration:none;}
.act-btn:hover{color:#2563eb;background:#e8f0fe;text-decoration:none;}
.act-btn.copy{color:#16a34a;}
.act-btn.copy:hover{color:#16a34a;background:#dcfce7;}
.act-btn.danger{color:#dc2626;}
.act-btn.danger:hover{color:#dc2626;background:#fef2f2;}

/* 필터 바 */
.filter-bar{background:#fff;border:1px solid #d2d6de;border-radius:4px;padding:14px 16px;margin-bottom:16px;}
.filter-bar .form-control{width:auto;display:inline-block;vertical-align:middle;}
.filter-bar .form-control.search{width:220px;}
.filter-bar>span, .filter-bar>select, .filter-bar>input{margin-right:8px;}

/* 테이블 */
.box-body{overflow:auto;padding:0px !important;}
thead tr th{position:sticky;top:0;background:#ebeaea;z-index:10;}
</style>

<div class="loading_div"><img src="/images/ajax-loader.gif"></div>
<div class="wrapper">
    <!-- Top 메뉴 -->
    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_header_menu.inc.php";?>
    <!-- Left 메뉴 -->
    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_left_menu.inc.php";?>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>매뉴얼 관리 <small>매뉴얼 페이지의 공개/비공개, 편집, 이력을 관리합니다</small></h1>
            <ol class="breadcrumb">
                <li><a href="/admin/member_list.php"><i class="fa fa-dashboard"></i> Home</a></li>
                <li class="active">매뉴얼 관리</li>
            </ol>
        </section>

        <section class="content">
            <!-- ===== KPI ===== -->
            <div class="kpi-grid" id="kpiBox">
                <div class="kpi-card" id="kpiTotal"><div class="kpi-num" style="font-size:28px;font-weight:800;color:#1e293b;">--</div><div class="kpi-label" style="font-size:12px;color:#888;margin-top:4px;">📄 총 매뉴얼 페이지</div></div>
                <div class="kpi-card kpi-green" id="kpiPublic"><div class="kpi-num" style="font-size:28px;font-weight:800;color:#1e293b;">--</div><div class="kpi-label" style="font-size:12px;color:#888;margin-top:4px;">👁️ 공개 페이지</div></div>
                <div class="kpi-card kpi-orange" id="kpiHidden"><div class="kpi-num" style="font-size:28px;font-weight:800;color:#1e293b;">--</div><div class="kpi-label" style="font-size:12px;color:#888;margin-top:4px;">🙈 비공개 페이지</div></div>
                <div class="kpi-card kpi-purple" id="kpiUpdated"><div class="kpi-num" style="font-size:28px;font-weight:800;color:#1e293b;">--</div><div class="kpi-label" style="font-size:12px;color:#888;margin-top:4px;">🕐 최종 수정일</div></div>
            </div>

            <!-- ===== 필터 바 ===== -->
            <div class="filter-bar">
                <input type="text" class="form-control input-sm search" placeholder="🔍 제목 또는 파일명 검색..." id="filterSearch" onkeyup="applyFilter()">
                <select class="form-control input-sm" id="filterCat" onchange="applyFilter()">
                    <option value="all">📂 전체 카테고리</option>
                </select>
                <select class="form-control input-sm" id="filterVis" onchange="applyFilter()">
                    <option value="all">👁️ 전체 상태</option>
                    <option value="1">공개만</option>
                    <option value="0">비공개만</option>
                </select>
                <div style="flex:1;text-align:right;">
                    <button class="btn btn-sm btn-success" onclick="scanSync()"><i class="fa fa-refresh"></i> 디스크 동기화 (신규 페이지 자동 등록)</button>
                    <button class="btn btn-sm btn-default" onclick="showAllHistory()"><i class="fa fa-history"></i> 전체 편집 이력</button>
                    <?php if ($_SESSION['one_member_admin_id'] != "onlyonemaket"){?>
                    <button class="btn btn-sm btn-primary" onclick="location='manual_write.php'"><i class="fa fa-pencil"></i> 새 매뉴얼 작성</button>
                    <?php }?>
                    <button class="btn btn-sm btn-info" onclick="location='/cliente_list.php?status=5'"><i class="fa fa-eye"></i> 매뉴얼 보기</button>
                </div>
            </div>

            <!-- ===== 카테고리 아코디언 ===== -->
            <div id="categoryPanels"></div>

            <!-- ===== 전체 페이지 목록 테이블 ===== -->
            <div class="row" style="margin-top:16px;">
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">📋 전체 페이지 목록</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover table-striped" id="pageTable">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">ID</th>
                                        <th style="width:110px;">카테고리</th>
                                        <th>페이지명</th>
                                        <th style="width:65px;">공개</th>
                                        <th style="width:85px;">등록일</th>
                                        <th style="width:85px;">수정일</th>
                                        <th style="width:95px;">편집이력</th>
                                        <th style="width:160px;">액션</th>
                                    </tr>
                                </thead>
                                <tbody id="pageTableBody"></tbody>
                            </table>
                        </div>
                        <div class="box-footer">
                            <span id="tableCount" style="font-size:13px;color:#888;">총 --건</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_footer.inc.php";?>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ============ 전역 상태 ============
var menus = [];
var allPages = [];

function api(mode, data, callback) {
    data = data || {};
    data.mode = mode;
    $.ajax({
        type: 'POST',
        url: '/admin/ajax/manual_ajax.php',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.success && callback) callback(res.data, res.message);
            else if (!res.success) toast(res.message, 'error');
        },
        error: function() { toast('서버 통신 실패', 'error'); }
    });
}

// ============ 로드 ============
function loadAll() {
    api('dashboard', {}, function(d) {
        $('#kpiTotal .kpi-num').text(d.total_pages);
        $('#kpiPublic .kpi-num').text(d.public_pages);
        $('#kpiHidden .kpi-num').text(d.hidden_pages);
        $('#kpiUpdated .kpi-num').text(d.last_updated ? d.last_updated.substring(0,10) : '-');
    });
    api('menu_list', {}, function(d) {
        menus = d;
        allPages = [];
        menus.forEach(function(m) {
            m.pages.forEach(function(p) {
                p.menuName = m.name;
                p.menuSlug = m.slug;
                p.menuTag = m.tag;
                allPages.push(p);
            });
        });
        renderAll();
    });
}

function renderAll() {
    renderAccordion();
    renderTable();
    populateFilterCat();
}

// ============ 아코디언 ============
function renderAccordion() {
    var html = '';
    menus.forEach(function(m) {
        var pub = m.pages.filter(function(p){return p.is_visible==1;}).length;
        var tagClass = m.tag === '관리자 도구' ? ' admin' : '';
        html += '<div class="cat-panel" data-menu="'+m.slug+'">';
        html += '  <div class="cat-header" onclick="$(this).toggleClass(\'open\').next(\'.cat-body\').toggleClass(\'open\')">';
        html += '    <div class="cat-left">';
        html += '      <span class="cat-icon">'+m.icon+'</span>';
        html += '      <span class="cat-name">'+m.name+'</span>';
        html += '      <span class="cat-tag'+tagClass+'">'+m.tag+'</span>';
        html += '    </div>';
        html += '    <div class="cat-right">';
        html += '      <span class="cat-count">'+pub+'/'+m.pages.length+' 공개</span>';
        html += '      <div class="switch-wrap">';
        html += '        <span class="switch-label">'+(m.is_visible==1?'ON':'OFF')+'</span>';
        html += '        <label class="switch" onclick="event.stopPropagation()">';
        html += '          <input type="checkbox" '+(m.is_visible==1?'checked':'')+' onchange="toggleMenu('+m.id+',this.checked)">';
        html += '          <span class="slider"></span>';
        html += '        </label>';
        html += '      </div>';
        html += '      <i class="fa fa-chevron-down cat-arrow"></i>';
        html += '    </div>';
        html += '  </div>';
        html += '  <div class="cat-body">';
        m.pages.forEach(function(p) {
            html += '    <div class="page-row" data-page-id="'+p.id+'">';
            html += '      <div class="page-left">';
            html += '        <label class="switch">';
            html += '          <input type="checkbox" '+(p.is_visible==1?'checked':'')+' onchange="togglePage('+p.id+',this.checked)">';
            html += '          <span class="slider"></span>';
            html += '        </label>';
            html += '        <span class="page-title">'+p.title+'</span>';
            html += '        <span class="page-slug">'+p.slug+'</span>';
            html += '      </div>';
            html += '      <div class="page-actions">';
            html += '        <a href="javascript:void(0)" class="act-btn copy" onclick="copyLink(\''+p.url+'\')"><i class="fa fa-link"></i> 복사</a>';
            html += '        <a href="javascript:void(0)" class="act-btn" onclick="editPage('+p.id+')"><i class="fa fa-pencil"></i> 편집</a>';
            html += '        <a href="javascript:void(0)" class="act-btn" onclick="viewHistory('+p.id+')"><i class="fa fa-history"></i> 이력('+p.history_count+')</a>';
            html += '      </div>';
            html += '    </div>';
        });
        html += '  </div>';
        html += '</div>';
    });
    $('#categoryPanels').html(html);
    // 첫 번째 카테고리 열기
    setTimeout(function() { $('.cat-header').first().addClass('open').next('.cat-body').addClass('open'); }, 100);
}

// ============ 페이지 테이블 ============
function renderTable(filterObj) {
    var pages = allPages.slice();
    if (filterObj) {
        if (filterObj.search) {
            var s = filterObj.search.toLowerCase();
            pages = pages.filter(function(p){return p.title.toLowerCase().indexOf(s)>=0||p.slug.toLowerCase().indexOf(s)>=0||p.menuName.toLowerCase().indexOf(s)>=0;});
        }
        if (filterObj.cat&&filterObj.cat!=='all') {
            pages = pages.filter(function(p){return p.menuSlug===filterObj.cat;});
        }
        if (filterObj.vis&&filterObj.vis!=='all') {
            var v = parseInt(filterObj.vis);
            pages = pages.filter(function(p){return p.is_visible===(v===1?1:0);});
        }
    }
    var html = '';
    pages.forEach(function(p) {
        var tagClass = p.menuTag==='관리자 도구'?' admin':'';
        html += '<tr>';
        html += '<td>'+p.id+'</td>';
        html += '<td><span class="cat-tag'+tagClass+'">'+p.menuName+'</span></td>';
        html += '<td><strong>'+p.title+'</strong><br><small style="color:#999;">'+p.file_path+'</small></td>';
        html += '<td><label class="switch"><input type="checkbox" '+(p.is_visible==1?'checked':'')+' onchange="togglePage('+p.id+',this.checked)"><span class="slider"></span></label></td>';
        html += '<td>'+(p.created_at?p.created_at.substring(0,10):'-')+'</td>';
        html += '<td>'+(p.updated_at?p.updated_at.substring(0,10):'-')+'</td>';
        html += '<td>'+(p.history_count||0)+'건</td>';
        html += '<td>';
        html += '  <a href="javascript:void(0)" class="act-btn copy" onclick="copyLink(\''+p.url+'\')"><i class="fa fa-link"></i> 복사</a> ';
        html += '  <a href="javascript:void(0)" class="act-btn" onclick="editPage('+p.id+')"><i class="fa fa-pencil"></i> 편집</a> ';
        html += '  <a href="javascript:void(0)" class="act-btn" onclick="viewHistory('+p.id+')"><i class="fa fa-history"></i> 이력</a>';
        html += '</td>';
        html += '</tr>';
    });
    $('#pageTableBody').html(html);
    $('#tableCount').text('총 '+pages.length+'건');
}

function applyFilter() {
    renderTable({
        search: $('#filterSearch').val(),
        cat: $('#filterCat').val(),
        vis: $('#filterVis').val()
    });
}

function populateFilterCat() {
    var html = '<option value="all">📂 전체 카테고리</option>';
    menus.forEach(function(m){html+='<option value="'+m.slug+'">'+m.icon+' '+m.name+'</option>';});
    $('#filterCat').html(html);
}

// ============ 액션: 토글 ============
function toggleMenu(menuId, checked) {
    api('toggle_menu', {menu_id:menuId, is_visible:checked?1:0}, function(d, msg) {
        toast(msg, 'success');
        loadAll();
    });
}

function togglePage(pageId, checked) {
    api('toggle_page', {page_id:pageId, is_visible:checked?1:0}, function(d, msg) {
        toast(msg, 'success');
        loadAll();
    });
}

// ============ 액션: 링크 복사 ============
function copyLink(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function(){toast('📋 링크가 복사되었습니다: '+url, 'success');});
    } else {
        prompt('Ctrl+C로 복사하세요:', url);
    }
}

// ============ 액션: 편집 ============
function editPage(pageId) {
    window.open('manual_edit.php?page_id='+pageId, '_blank');
}

// ============ 액션: 이력 보기 ============
function viewHistory(pageId) {
    api('page_history', {page_id:pageId}, function(d) {
        var html = '<div style="max-height:400px;overflow:auto;">';
        html += '<h4>📜 편집 이력</h4>';
        html += '<table class="table table-condensed"><thead><tr><th>시간</th><th>편집자</th><th>변경내용</th></tr></thead><tbody>';
        if (d.list.length === 0) html += '<tr><td colspan="3">이력 없음</td></tr>';
        d.list.forEach(function(h) {
            html += '<tr><td>'+h.created_at+'</td><td>'+h.editor+'</td><td>'+h.diff_summary+'</td></tr>';
        });
        html += '</tbody></table></div>';
        // AdminLTE modal 또는 window.open
        var w = window.open('','_blank','width=600,height=500');
        w.document.write('<html><head><title>편집 이력</title><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"></head><body style="padding:20px;">'+html+'</body></html>');
    });
}

// ============ 액션: 전체 이력 ============
function showAllHistory() {
    api('all_history', {limit:200}, function(d) {
        var html = '<div style="max-height:500px;overflow:auto;">';
        html += '<h4>📊 전체 편집 이력 ('+d.total+'건)</h4>';
        html += '<table class="table table-condensed"><thead><tr><th>시간</th><th>메뉴</th><th>페이지</th><th>편집자</th><th>변경</th></tr></thead><tbody>';
        d.list.forEach(function(h) {
            html += '<tr><td>'+h.created_at+'</td><td>'+h.menu_name+'</td><td>'+h.title+'</td><td>'+h.editor+'</td><td>'+h.diff_summary+'</td></tr>';
        });
        html += '</tbody></table></div>';
        var w = window.open('','_blank','width=750,height=550');
        w.document.write('<html><head><title>전체 편집 이력</title><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"></head><body style="padding:20px;">'+html+'</body></html>');
    });
}

// ============ 액션: 디스크 동기화 ============
function scanSync() {
    $('#scanBtn').prop('disabled', true);
    toast('🔄 디스크 스캔 중...', 'success');
    api('scan_sync', {}, function(d, msg) {
        toast(msg, 'success');
        loadAll();
    });
}

// ============ 토스트 ============
function toast(msg, type) {
    var $t = $('#toast');
    $t.text(msg).attr('class', 'toast '+(type||'')).addClass('show');
    clearTimeout($t.data('timer'));
    $t.data('timer', setTimeout(function(){$t.removeClass('show');}, 2500));
}

// ============ 초기화 ============
$(function() {
    loadAll();
    // AdminLTE 높이 보정
    var contHeaderH = $(".main-header").height();
    var navH = $(".navbar").height();
    if(navH != contHeaderH) contHeaderH += navH - 50;
    $(".content-wrapper").css("margin-top", contHeaderH);
});
</script>