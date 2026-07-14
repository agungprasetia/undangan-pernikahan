<?php
/**
 * Temporary diagnostic — HAPUS file ini setelah website jalan.
 * Buka: https://elialutfiinvitations.com/debug.php
 */

header('Content-Type: text/plain; charset=utf-8');
echo "=== DEBUG UNDANGAN ===\n\n";

echo "1. PHP version: " . PHP_VERSION . "\n";
echo "2. PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'MISSING') . "\n";
echo "3. curl: " . (extension_loaded('curl') ? 'OK' : 'MISSING') . "\n\n";

$configPath = __DIR__ . '/config.php';
echo "4. config.php: " . (is_file($configPath) ? 'ADA' : 'TIDAK ADA — salin config.example.php → config.php') . "\n";

if (!is_file($configPath)) {
    echo "\nSTOP: buat config.php dulu.\n";
    exit;
}

try {
    $c = require $configPath;
    if (!is_array($c)) {
        echo "5. config.php: SALAH FORMAT (harus return array)\n";
        exit;
    }
    echo "5. config keys: " . implode(', ', array_keys($c)) . "\n";
    echo "6. db_host: " . ($c['db_host'] ?? '(kosong)') . "\n";
    echo "7. db_name: " . ($c['db_name'] ?? '(kosong)') . "\n";
    echo "8. db_user: " . ($c['db_user'] ?? '(kosong)') . "\n";
    echo "9. public_url: " . ($c['public_url'] ?? '(kosong)') . "\n\n";
} catch (Throwable $e) {
    echo "5. config.php ERROR: " . $e->getMessage() . "\n";
    exit;
}

echo "10. Coba koneksi MySQL...\n";
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $c['db_host'],
        $c['db_name']
    );
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "    KONEKSI: OK\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "11. Tabel: " . (count($tables) ? implode(', ', $tables) : '(belum ada — jalankan sql/schema.sql)') . "\n";

    if (in_array('undangan', $tables, true)) {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM undangan')->fetchColumn();
        echo "12. Jumlah undangan: $n\n";
    }
    if (in_array('komentar', $tables, true)) {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM komentar')->fetchColumn();
        echo "13. Jumlah komentar: $n\n";
    }
} catch (Throwable $e) {
    echo "    KONEKSI GAGAL: " . $e->getMessage() . "\n";
    echo "\nPerbaiki db_host / db_name / db_user / db_pass di config.php\n";
    echo "Ambil dari hPanel → Databases → MySQL\n";
    exit;
}

echo "\n14. index.html: " . (is_file(__DIR__ . '/index.html') ? 'ADA' : 'TIDAK ADA') . "\n";
echo "15. .htaccess: " . (is_file(__DIR__ . '/.htaccess') ? 'ADA' : 'TIDAK ADA') . "\n";
echo "16. assets/: " . (is_dir(__DIR__ . '/assets') ? 'ADA' : 'TIDAK ADA') . "\n";

echo "\n=== SEMUA CEK SELESAI ===\n";
echo "Kalau semua OK, buka halaman utama. HAPUS debug.php setelah selesai.\n";
