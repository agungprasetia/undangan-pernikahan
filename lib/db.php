<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            throw new RuntimeException(
                'config.php belum ada. Salin config.local.example.php (lokal) atau config.example.php (Hostinger) menjadi config.php.'
            );
        }
        $loaded = require $path;
        if (!is_array($loaded)) {
            throw new RuntimeException('config.php harus return array. Lihat config.example.php');
        }
        $config = $loaded;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = app_config();

        // db_host, db_name, db_user wajib diisi (bukan placeholder)
        foreach (['db_host', 'db_name', 'db_user'] as $key) {
            $val = trim((string) ($c[$key] ?? ''));
            if ($val === '' || strpos($val, 'XXXXXXXX') !== false || strpos($val, 'u123456789') !== false) {
                throw new RuntimeException(
                    "Isi {$key} di config.php dengan data MySQL yang benar."
                );
            }
        }

        // db_pass boleh kosong (XAMPP/Laragon lokal). Tolak hanya placeholder contoh.
        $pass = (string) ($c['db_pass'] ?? '');
        if (strpos($pass, 'PASSWORD_') !== false || $pass === 'password-mysql' || $pass === 'PASSWORD_MYSQL_KAMU') {
            throw new RuntimeException(
                'Isi db_pass di config.php. Untuk lokal XAMPP/Laragon biasanya kosong: \'db_pass\' => \'\''
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $c['db_host'],
            $c['db_name']
        );
        $pdo = new PDO($dsn, $c['db_user'], $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
