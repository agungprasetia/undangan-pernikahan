<?php
require_once dirname(__DIR__, 3) . '/lib/db.php';
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';
require_once dirname(__DIR__, 3) . '/lib/wa-client.php';

require_admin();

try {
    json_response(wa_get_status());
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage(), 'status' => 'error'], 500);
}
