<?php
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';
require_once dirname(__DIR__, 3) . '/lib/wa-client.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$cfg = app_config();
if (empty($cfg['wa_enabled'])) {
    json_response(['ok' => false, 'error' => 'wa_enabled=false di config.php'], 400);
}

wa_init();
json_response(['ok' => true]);
