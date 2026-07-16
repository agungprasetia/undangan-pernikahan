<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $body = read_json_body();
    $password = trim((string) ($body['password'] ?? $_POST['password'] ?? ''));

    if ($password === '') {
        json_response(['ok' => false, 'error' => 'Password kosong'], 400);
    }

    $cfg = app_config();
    $expected = trim((string) ($cfg['admin_password'] ?? ''));

    if ($expected === '') {
        json_response(['ok' => false, 'error' => 'admin_password belum diisi di config.php'], 500);
    }

    if (admin_login($password)) {
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Password salah'], 401);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
