<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$guest = lookup_guest($_GET);
$guestName = $guest ? $guest['nama'] : 'Tamu Undangan';
$guestId = $guest ? (int) $guest['id'] : null;

$templatePath = __DIR__ . '/index.html';
if (!is_file($templatePath)) {
    http_response_code(500);
    echo 'index.html tidak ditemukan';
    exit;
}

$html = file_get_contents($templatePath);
$html = preg_replace('/\{\{\s*guestName\s*\}\}/', escape_html($guestName), $html);
$html = str_replace(
    '</head>',
    '<script>window.GUEST_NAME=' . json_encode($guestName, JSON_UNESCAPED_UNICODE)
        . ';window.GUEST_ID=' . json_encode($guestId) . ';</script></head>',
    $html
);

header('Content-Type: text/html; charset=utf-8');
echo $html;
