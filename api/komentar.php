<?php
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT nama, pesan, kehadiran, created_at FROM komentar ORDER BY id DESC LIMIT 200'
    )->fetchAll();
    json_response($rows);
}

if ($method === 'POST') {
    $body = read_json_body();
    $nama = mb_substr(trim((string) ($body['nama'] ?? '')), 0, 60);
    $pesan = mb_substr(trim((string) ($body['pesan'] ?? '')), 0, 500);
    $kehadiran = mb_substr(trim((string) ($body['kehadiran'] ?? 'Hadir')), 0, 30);

    if ($nama === '' || $pesan === '') {
        json_response(['ok' => false, 'error' => 'Nama dan pesan wajib diisi'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO komentar (nama, pesan, kehadiran) VALUES (?, ?, ?)');
    $stmt->execute([$nama, $pesan, $kehadiran]);
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
