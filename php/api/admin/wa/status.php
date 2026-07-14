<?php
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';
require_once dirname(__DIR__, 3) . '/lib/wa-client.php';

require_admin();
json_response(wa_get_status());
