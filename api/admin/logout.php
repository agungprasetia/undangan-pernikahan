<?php
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

admin_logout();
json_response(['ok' => true]);
