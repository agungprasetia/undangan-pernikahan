<?php
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';
require_once dirname(__DIR__, 3) . '/lib/wa-client.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

wa_logout();
json_response(['ok' => true]);
