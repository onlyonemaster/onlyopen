<?php
// PHP built-in server router — mimics .htaccess behavior
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve API requests to api/index.php
if (strpos($uri, '/vermanger/api/') === 0) {
    $_SERVER['REQUEST_URI'] = str_replace('/vermanger', '', $_SERVER['REQUEST_URI']);
    require __DIR__ . '/api/index.php';
    return true;
}
if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Serve static files directly
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // let PHP built-in server handle it
}

// Rewrite everything else to index.php
$_GET['path'] = trim($uri, '/');
require __DIR__ . '/index.php';
return true;
