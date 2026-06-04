<?php
/**
 * 마이챗 데이터 내보내기 (ZIP 다운로드)
 * GET /aimessage/mychat/api/export.php
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';

mychat_cors();
$user   = mychat_auth(true);
$mem_id = $user['mem_id'];
$db     = getDatabaseConnection();
$esc    = $db->real_escape_string($mem_id);

// 데이터 수집
$data = [];

// 학습 데이터
$r = $db->query("SELECT category, scope, source, title, content_text, tags, created_at FROM mychat_data_pool WHERE mem_id='{$esc}' AND is_deleted=0 ORDER BY category, created_at");
$pool = [];
while ($row = $r?->fetch_assoc()) $pool[] = $row;
$data['data_pool'] = $pool;

// 의사결정
$r2 = $db->query("SELECT situation, ai_options, ai_prediction, chosen_option, outcome_text, match_score, match_label, status, decided_at, reflect_remind, created_at FROM mychat_decisions WHERE mem_id='{$esc}' ORDER BY created_at");
$decs = [];
while ($row = $r2?->fetch_assoc()) $decs[] = $row;
$data['decisions'] = $decs;

// 대화 이력
$r3 = $db->query("SELECT role, content, created_at FROM mychat_chat_history WHERE mem_id='{$esc}' ORDER BY id DESC LIMIT 1000");
$chats = [];
while ($row = $r3?->fetch_assoc()) $chats[] = $row;
$data['chat_history'] = array_reverse($chats);

// 아바타 정보 (민감 정보 제외)
$av = $db->query("SELECT avatar_name, persona_prompt, avatar_style, data_storage_mode, data_count, decision_count, match_rate, created_at FROM mychat_avatar WHERE mem_id='{$esc}'")?->fetch_assoc();
$data['avatar'] = $av;

$data['exported_at'] = date('Y-m-d H:i:s');
$data['mem_id']      = $mem_id;

// JSON 생성
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// ZIP 생성
$tmpDir  = sys_get_temp_dir() . '/mychat_export_' . $mem_id . '_' . time();
mkdir($tmpDir, 0700, true);
file_put_contents($tmpDir . '/mychat_data.json', $json);

// CSV 변환 (데이터 풀)
$csvPath = $tmpDir . '/data_pool.csv';
$fp = fopen($csvPath, 'w');
fputs($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
fputcsv($fp, ['카테고리','공개범위','출처','제목','내용','태그','생성일시']);
foreach ($pool as $row) {
    fputcsv($fp, [$row['category'],$row['scope'],$row['source'],$row['title']??'',$row['content_text']??'',$row['tags']??'',$row['created_at']]);
}
fclose($fp);

$zipFile = sys_get_temp_dir() . '/mychat_export_' . $mem_id . '.zip';
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile($tmpDir . '/mychat_data.json', 'mychat_data.json');
$zip->addFile($csvPath, 'data_pool.csv');
$zip->close();

// 임시 파일 정리
array_map('unlink', glob($tmpDir . '/*'));
rmdir($tmpDir);

// 다운로드 헤더
$filename = 'mychat_' . $mem_id . '_' . date('Ymd') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipFile));
header('Pragma: no-cache');
header('Expires: 0');
readfile($zipFile);
unlink($zipFile);
exit;
