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

function limit_str(string $s, int $len): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $len);
    }
    return substr($s, 0, $len);
}

try {
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
        // fallback kalau body kosong (proxy / Content-Type aneh)
        if (!$body && !empty($_POST)) {
            $body = $_POST;
        }

        $nama = limit_str(trim((string) ($body['nama'] ?? '')), 60);
        $pesan = limit_str(trim((string) ($body['pesan'] ?? '')), 500);
        $kehadiran = limit_str(trim((string) ($body['kehadiran'] ?? 'Hadir')), 30);

        if ($nama === '' || $pesan === '') {
            json_response(['ok' => false, 'error' => 'Nama dan pesan wajib diisi'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO komentar (nama, pesan, kehadiran) VALUES (?, ?, ?)');
        $stmt->execute([$nama, $pesan, $kehadiran]);
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
