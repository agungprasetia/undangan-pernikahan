<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$body = read_json_body();
if (admin_login((string) ($body['password'] ?? ''))) {
    json_response(['ok' => true]);
}
json_response(['ok' => false, 'error' => 'Password salah'], 401);
