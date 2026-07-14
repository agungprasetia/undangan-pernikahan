<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            throw new RuntimeException(
                'config.php belum ada. Salin config.example.php menjadi config.php lalu isi data MySQL Hostinger.'
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
        foreach (['db_host', 'db_name', 'db_user', 'db_pass'] as $key) {
            $val = (string) ($c[$key] ?? '');
            if ($val === '' || strpos($val, 'XXXXXXXX') !== false || strpos($val, 'PASSWORD_') !== false) {
                throw new RuntimeException(
                    "Isi {$key} di config.php dengan data MySQL dari hPanel Hostinger (jangan biarkan contoh)."
                );
            }
        }
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $c['db_host'],
            $c['db_name']
        );
        $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
