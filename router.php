<?php
/**
 * Router untuk PHP built-in server (php -S).
 * Pemakaian:
 *   php -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// /admin → /admin/ (penting agar CSS/JS relative path benar)
if ($uri === '/admin') {
    header('Location: /admin/', true, 301);
    return true;
}

// File statis yang benar-benar ada → biarkan server bawaan yang handle
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Mapping API (sama seperti .htaccess)
$routes = [
    '/api/guest' => 'api/guest.php',
    '/api/komentar' => 'api/komentar.php',
    '/api/admin/me' => 'api/admin/me.php',
    '/api/admin/login' => 'api/admin/login.php',
    '/api/admin/logout' => 'api/admin/logout.php',
    '/api/admin/upload' => 'api/admin/upload.php',
    '/api/admin/kirim' => 'api/admin/kirim.php',
    '/api/admin/undangan' => 'api/admin/undangan.php',
    '/api/admin/komentar' => 'api/admin/komentar.php',
    '/api/admin/wa/status' => 'api/admin/wa/status.php',
    '/api/admin/wa/qr' => 'api/admin/wa/qr.php',
    '/api/admin/wa/init' => 'api/admin/wa/init.php',
    '/api/admin/wa/logout' => 'api/admin/wa/logout.php',
];

$path = rtrim($uri, '/') ?: '/';

// /api/admin/undangan/123 → set $_GET['id']
if (preg_match('#^/api/admin/undangan/(\d+)$#', $path, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/api/admin/undangan.php';
    return true;
}
if (preg_match('#^/api/admin/komentar/(\d+)$#', $path, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/api/admin/komentar.php';
    return true;
}

if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
    return true;
}

// Admin panel
if ($path === '/admin') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/admin/index.html');
    return true;
}

// Landing page
require __DIR__ . '/index.php';
return true;
