<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';
require_once dirname(__DIR__, 2) . '/lib/spreadsheet.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

if (empty($_FILES['file']['tmp_name'])) {
    json_response(['ok' => false, 'error' => 'File tidak ditemukan'], 400);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['ok' => false, 'error' => 'Upload gagal (kode ' . $file['error'] . ')'], 400);
}

try {
    $rows = parse_spreadsheet($file['tmp_name'], $file['name']);
    $result = insert_undangan_rows($rows);
    json_response(['ok' => true] + $result);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}
