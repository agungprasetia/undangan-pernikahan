<?php
/**
 * Bridge sementara — letakkan di public_html JIKA index.php masih di dalam folder php/
 * Setelah kamu pindahkan isi php/ ke public_html, HAPUS file bridge ini
 * (pakai index.php yang asli dari folder php).
 */
$target = __DIR__ . '/php/index.php';
if (is_file($target)) {
    require $target;
    exit;
}

http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo "Struktur upload salah.\n\n";
echo "Pindahkan SEMUA isi folder php/ ke public_html (satu level di atas).\n";
echo "index.php harus berada langsung di public_html, bukan di public_html/php/.\n";
