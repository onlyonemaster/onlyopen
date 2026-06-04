<?php
/**
 * 매뉴얼 편집기 페이지 (2026-05-03 by 아리아)
 * - HTML 파일을 직접 편집하고 저장
 * - 저장 시 편집 이력 자동 기록
 * - GET 파라미터: page_id (Gn_Manual_Page.id)
 */
include_once $_SERVER['DOCUMENT_ROOT']."/lib/rlatjd_fun.php";
include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_header.inc.php";

$page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;

// 페이지 정보 조회
$page = null;
$fileContent = '';
if ($page_id > 0) {
    $sql = "SELECT p.*, m.slug as menu_slug, m.name as menu_name, m.icon as menu_icon
            FROM Gn_Manual_Page p 
            JOIN Gn_Manual_Menu m ON p.menu_id = m.id 
            WHERE p.id = {$page_id}";
    $res = mysqli_query($self_con, $sql);
    $page = mysqli_fetch_assoc($res);
    
    if ($page) {
        $fullPath = '/home/kiam' . $page['file_path'];
        if (file_exists($fullPath)) {
            $fileContent = file_get_contents($fullPath);
        }
    }
}

$date_today = date("Y-m-d");
?>
<style>
.loading_div{display:none;position:fixed;left:50%;top:50%;z-index:1000;}
.wrapper{height:100%;overflow:auto !important;}
.content-wrapper{min-height:80% !important;}

.edit-container{background:#fff;border:1px solid #d2d6de;border-radius:4px;padding:20px;margin-bottom:20px;}
.edit-meta{display:flex;align-items:center;gap:16px;padding-bottom:16px;border-bottom:1px solid #e8e8e8;margin-bottom:16px;flex-wrap:wrap;}
.edit-meta .meta-item{display:flex;align-items:center;gap:6px;font-size:13px;color:#666;}
.edit-meta .meta-item strong{color:#333;}
.edit-meta .meta-badge{padding:2px 10px;border-radius:3px;font-size:11px;font-weight:600;}
.edit-meta .badge-path{background:#e8f0fe;color:#2563eb;}

#codeEditor{width:100%;min-height:500px;font-family:'Courier New',Courier,monospace;font-size:13px;line-height:1.6;border:1px solid #d2d6de;border-radius:4px;padding:14px;resize:vertical;tab-size:2;}

.toast{position:fixed;bottom:30px;right:30px;background:#333;color:#fff;padding:12px 24px;border-radius:6px;z-index:9999;font-size:14px;opacity:0;transition:opacity .3s;box-shadow:0 4px 12px rgba(0,0,0,.3);}
.toast.show{opacity:1;}
.toast.success{background:#16a34a;}
.toast.error{background:#dc2626;}

.status-bar{display:flex;align-items:center;gap:12px;padding:8px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;margin-top:12px;font-size:12px;color:#666;}
.status-bar .dot{width:8px;height:8px;border-radius:50%;background:#16a34a;}
.status-bar .dot.unsaved{background:#ea580c;animation:pulse 1s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}

.history-table{max-height:300px;overflow:auto;}
.history-table table{width:100%;font-size:12px;}
</style>

<div class="loading_div"><img src="/images/ajax-loader.gif"></div>
<div class="wrapper">
    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_header_menu.inc.php";?>
    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_left_menu.inc.php";?>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>매뉴얼 편집 <small>HTML 파일을 직접 편집하고 저장합니다</small></h1>
            <ol class="breadcrumb">
                <li><a href="/admin/member_list.php"><i class="fa fa-dashboard"></i> Home</a></li>
                <li><a href="/admin/admin_manual.php">매뉴얼 관리</a></li>
                <li class="active">편집</li>
            </ol>
        </section>

        <section class="content">
<?php if (!$page): ?>
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i> 페이지를 찾을 수 없습니다. 
                <a href="/admin/admin_manual.php">매뉴얼 관리로 돌아가기</a>
            </div>
<?php else: ?>
            <!-- 메타 정보 -->
            <div class="edit-container">
                <div class="edit-meta">
                    <div class="meta-item">
                        <?=$page['menu_icon']?> <strong><?=$page['menu_name']?></strong>
                    </div>
                    <div class="meta-item">▸ <strong><?=$page['title']?></strong></div>
                    <span class="meta-badge badge-path"><?=$page['file_path']?></span>
                    <span style="margin-left:auto;font-size:12px;color:#888;">
                        최종 수정: <?=$page['updated_at']?>
                    </span>
                </div>

                <!-- 편집 영역 -->
                <textarea id="codeEditor" name="content"><?=htmlspecialchars($fileContent)?></textarea>

                <!-- 상태 바 -->
                <div class="status-bar">
                    <span class="dot" id="saveDot"></span>
                    <span id="saveStatus">편집 준비</span>
                    <span style="margin-left:auto;">
                        <span id="lineCount">0</span> 줄 | <span id="charCount">0</span> 자
                    </span>
                </div>

                <!-- 버튼 -->
                <div style="margin-top:16px;display:flex;gap:8px;justify-content:space-between;">
                    <div>
                        <button class="btn btn-sm btn-default" onclick="previewHTML()">
                            <i class="fa fa-eye"></i> 미리보기
                        </button>
                        <button class="btn btn-sm btn-default" onclick="showHistory()">
                            <i class="fa fa-history"></i> 편집 이력
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-default" onclick="location='/admin/admin_manual.php'">
                            <i class="fa fa-arrow-left"></i> 목록으로
                        </button>
                        <button class="btn btn-sm btn-primary" id="saveBtn" onclick="savePage()">
                            <i class="fa fa-save"></i> 저장하기
                        </button>
                    </div>
                </div>
            </div>

            <!-- 편집 이력 영역 -->
            <div class="edit-container" id="historyBox" style="display:none;">
                <h4 style="margin-top:0;">📜 편집 이력</h4>
                <div class="history-table" id="historyContent"></div>
            </div>
<?php endif; ?>
        </section>
    </div>

    <?php include_once $_SERVER['DOCUMENT_ROOT']."/admin/include/admin_footer.inc.php";?>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
var pageId = <?=$page_id?>;
var isModified = false;
var originalContent = '';

$(function() {
    var contHeaderH = $(".main-header").height();
    var navH = $(".navbar").height();
    if(navH != contHeaderH) contHeaderH += navH - 50;
    $(".content-wrapper").css("margin-top", contHeaderH);

    // 초기 내용 저장
    originalContent = $('#codeEditor').val();
    updateCounts();

    // 변경 감지
    $('#codeEditor').on('input', function() {
        isModified = $(this).val() !== originalContent;
        $('#saveDot').toggleClass('unsaved', isModified);
        $('#saveStatus').text(isModified ? '수정됨 (저장 필요)' : '저장됨');
        updateCounts();
    });

    // Ctrl+S 단축키
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            savePage();
        }
    });

    // 탭 키 지원
    $('#codeEditor').on('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            var start = this.selectionStart;
            var end = this.selectionEnd;
            var val = $(this).val();
            $(this).val(val.substring(0, start) + '  ' + val.substring(end));
            this.selectionStart = this.selectionEnd = start + 2;
            $(this).trigger('input');
        }
    });
});

function updateCounts() {
    var val = $('#codeEditor').val();
    var lines = val.split('\n').length;
    var chars = val.length;
    $('#lineCount').text(lines);
    $('#charCount').text(chars);
}

function savePage() {
    var content = $('#codeEditor').val();
    if (!content.trim()) {
        if (!confirm('내용이 비어있습니다. 빈 파일로 저장하시겠습니까?')) return;
    }

    $('#saveBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 저장 중...');

    $.ajax({
        type: 'POST',
        url: '/admin/ajax/manual_ajax.php',
        data: { mode: 'page_save', page_id: pageId, content: content },
        dataType: 'json',
        success: function(res) {
            $('#saveBtn').prop('disabled', false).html('<i class="fa fa-save"></i> 저장하기');
            if (res.success) {
                originalContent = content;
                isModified = false;
                $('#saveDot').removeClass('unsaved');
                $('#saveStatus').text('저장 완료! (' + res.data.diff + ')');
                toast('✅ 저장 완료! ' + res.data.diff, 'success');
                updateCounts();
            } else {
                toast('❌ 저장 실패: ' + res.message, 'error');
            }
        },
        error: function() {
            $('#saveBtn').prop('disabled', false).html('<i class="fa fa-save"></i> 저장하기');
            toast('❌ 서버 통신 실패', 'error');
        }
    });
}

function previewHTML() {
    var content = $('#codeEditor').val();
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write(content);
    w.document.close();
}

function showHistory() {
    $('#historyBox').toggle();
    if ($('#historyBox').is(':visible')) {
        $.ajax({
            type: 'POST',
            url: '/admin/ajax/manual_ajax.php',
            data: { mode: 'page_history', page_id: pageId },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;
                var html = '<table class="table table-condensed table-striped"><thead><tr><th>시간</th><th>편집자</th><th>변경내용</th></tr></thead><tbody>';
                if (res.data.list.length === 0) {
                    html += '<tr><td colspan="3" style="text-align:center;color:#999;">이력이 없습니다</td></tr>';
                } else {
                    res.data.list.forEach(function(h) {
                        html += '<tr><td>' + h.created_at + '</td><td>' + h.editor + '</td><td>' + h.diff_summary + '</td></tr>';
                    });
                }
                html += '</tbody></table>';
                $('#historyContent').html(html);
            }
        });
    }
}

// 페이지 이탈 시 경고
$(window).on('beforeunload', function() {
    if (isModified) return '수정된 내용이 저장되지 않았습니다. 정말 나가시겠습니까?';
});

function toast(msg, type) {
    var $t = $('#toast');
    $t.text(msg).attr('class', 'toast ' + (type || '')).addClass('show');
    clearTimeout($t.data('timer'));
    $t.data('timer', setTimeout(function() { $t.removeClass('show'); }, 3000));
}
</script>