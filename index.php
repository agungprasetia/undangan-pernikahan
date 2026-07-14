<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$guestName = 'Tamu Undangan';
$guestId = null;

try {
    $guest = lookup_guest($_GET);
    if ($guest) {
        $guestName = $guest['nama'];
        $guestId = (int) $guest['id'];
    }
} catch (Throwable $e) {
    // Database belum siap — tampilkan undangan tetap, tanpa nama personal
    error_log('undangan lookup_guest: ' . $e->getMessage());
}

$templatePath = __DIR__ . '/index.html';
if (!is_file($templatePath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "index.html tidak ditemukan di public_html.\n";
    echo "Pastikan kamu upload seluruh isi folder php/ ke public_html.";
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
