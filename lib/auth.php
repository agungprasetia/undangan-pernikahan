<?php

require_once __DIR__ . '/db.php';

const ADMIN_COOKIE = 'undangan_admin';

function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 7 * 24 * 60 * 60,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(7 * 24 * 60 * 60, '/');
        }
        session_start();
    }
}

function admin_expected_password(): string
{
    $cfg = app_config();
    return trim((string) ($cfg['admin_password'] ?? ''));
}

function is_admin(): bool
{
    start_session_if_needed();
    $expected = admin_expected_password();
    if ($expected === '' || !isset($_SESSION[ADMIN_COOKIE])) {
        return false;
    }
    return hash_equals($expected, (string) $_SESSION[ADMIN_COOKIE]);
}

function require_admin(): void
{
    if (!is_admin()) {
        json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}

function admin_login(string $password): bool
{
    start_session_if_needed();
    $expected = admin_expected_password();
    $given = trim($password);
    if ($expected === '' || $given === '') {
        return false;
    }
    if (hash_equals($expected, $given)) {
        $_SESSION[ADMIN_COOKIE] = $expected;
        return true;
    }
    return false;
}

function admin_logout(): void
{
    start_session_if_needed();
    unset($_SESSION[ADMIN_COOKIE]);
}
