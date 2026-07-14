<?php

const ADMIN_COOKIE = 'undangan_admin';

function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 7 * 24 * 60 * 60,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function is_admin(): bool
{
    start_session_if_needed();
    $cfg = app_config();
    return isset($_SESSION[ADMIN_COOKIE]) && hash_equals($cfg['admin_password'], $_SESSION[ADMIN_COOKIE]);
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
    $cfg = app_config();
    if (hash_equals($cfg['admin_password'], $password)) {
        $_SESSION[ADMIN_COOKIE] = $cfg['admin_password'];
        return true;
    }
    return false;
}

function admin_logout(): void
{
    start_session_if_needed();
    unset($_SESSION[ADMIN_COOKIE]);
}
