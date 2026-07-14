<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';

require_admin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT id, no, nama, sent, sent_at, created_at FROM undangan ORDER BY id ASC'
    )->fetchAll();
    $total = count($rows);
    $terkirim = count(array_filter($rows, fn($r) => (int) $r['sent'] === 1));
    json_response(['rows' => $rows, 'total' => $total, 'terkirim' => $terkirim]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $no = trim((string) ($body['no'] ?? ''));
    $nama = trim((string) ($body['nama'] ?? ''));
    if ($no === '' || $nama === '') {
        json_response(['ok' => false, 'error' => 'Nomor & nama wajib diisi'], 400);
    }
    $stmt = $pdo->prepare('INSERT INTO undangan (no, nama) VALUES (?, ?)');
    $stmt->execute([$no, $nama]);
    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM undangan WHERE id = ?');
        $stmt->execute([$id]);
    } else {
        $pdo->exec('DELETE FROM undangan');
    }
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
