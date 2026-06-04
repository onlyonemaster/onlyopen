<?php
/**
 * 세션 변수 디버그 파일
 * 실서버에 업로드 후 브라우저에서 접속하면 세션 변수 목록이 출력됩니다
 * 확인 후 반드시 삭제하세요!
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/plain; charset=utf-8');
echo "=== SESSION 변수 목록 ===\n\n";
if (empty($_SESSION)) {
    echo "⚠️  세션이 비어있습니다. 로그인 후 다시 접속해주세요.\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "[{$key}] => " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) . "\n";
    }
}
echo "\n=== 확인 완료 후 이 파일을 삭제하세요 ===\n";
