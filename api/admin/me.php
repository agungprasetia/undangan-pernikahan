<?php
require_once dirname(__DIR__, 3) . '/lib/auth.php';
require_once dirname(__DIR__, 3) . '/lib/helpers.php';

json_response(['ok' => is_admin()]);
