<?php
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

header('Access-Control-Allow-Origin: *');

$guest = lookup_guest($_GET);
if (!$guest) {
    json_response(['ok' => false, 'nama' => 'Tamu Undangan']);
}
json_response(['ok' => true, 'id' => (int) $guest['id'], 'nama' => $guest['nama']]);
