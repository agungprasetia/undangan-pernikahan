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
        'SELECT id, nama, pesan, kehadiran, created_at FROM komentar ORDER BY id DESC'
    )->fetchAll();
    json_response($rows);
}

if ($method === 'DELETE' && $id) {
    $stmt = $pdo->prepare('DELETE FROM komentar WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
