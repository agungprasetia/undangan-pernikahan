<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';
require_once dirname(__DIR__, 2) . '/lib/instagram.php';
require_once dirname(__DIR__, 2) . '/lib/message.php';

require_admin();
ensure_undangan_instagram_columns();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT id, no, instagram, nama, sent, sent_ig, sent_at, sent_ig_at, created_at
         FROM undangan ORDER BY id ASC'
    )->fetchAll();

    // Lengkapi link IG + pesan siap salin
    foreach ($rows as &$r) {
        $ig = $r['instagram'] ?: null;
        $r['ig_profile'] = $ig ? instagram_profile_url($ig) : null;
        $r['ig_dm'] = $ig ? instagram_dm_url($ig) : null;
        $r['ig_pesan'] = $ig
            ? build_invitation_message($r['nama'], (int) $r['id'], 'ig')
            : null;
    }
    unset($r);

    $total = count($rows);
    $terkirim = count(array_filter($rows, fn($r) => (int) $r['sent'] === 1));
    $igTotal = count(array_filter($rows, fn($r) => !empty($r['instagram'])));
    $igDone = count(array_filter($rows, fn($r) => (int) $r['sent_ig'] === 1));

    json_response([
        'rows' => $rows,
        'total' => $total,
        'terkirim' => $terkirim,
        'ig_total' => $igTotal,
        'ig_done' => $igDone,
    ]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $nama = trim((string) ($body['nama'] ?? ''));
    $noRaw = trim((string) ($body['no'] ?? ''));
    $igRaw = trim((string) ($body['instagram'] ?? $body['ig'] ?? ''));

    if ($nama === '') {
        json_response(['ok' => false, 'error' => 'Nama wajib diisi'], 400);
    }

    $no = $noRaw !== '' ? normalize_wa_number($noRaw) : null;
    $ig = $igRaw !== '' ? normalize_instagram($igRaw) : null;

    if ($noRaw !== '' && !$no) {
        json_response(['ok' => false, 'error' => 'Nomor WhatsApp tidak valid'], 400);
    }
    if ($igRaw !== '' && !$ig) {
        json_response(['ok' => false, 'error' => 'Username Instagram tidak valid'], 400);
    }
    if (!$no && !$ig) {
        json_response(['ok' => false, 'error' => 'Isi minimal No. WA atau Username Instagram'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO undangan (no, instagram, nama) VALUES (?, ?, ?)');
    $stmt->execute([$no, $ig, $nama]);
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
