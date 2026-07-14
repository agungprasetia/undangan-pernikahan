<?php
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';
require_once dirname(__DIR__, 2) . '/lib/message.php';
require_once dirname(__DIR__, 2) . '/lib/wa-client.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$cfg = app_config();
$body = read_json_body();
$onlyUnsent = ($body['onlyUnsent'] ?? true) !== false;
$pdo = db();

$sql = $onlyUnsent
    ? 'SELECT * FROM undangan WHERE sent = 0 ORDER BY id ASC'
    : 'SELECT * FROM undangan ORDER BY id ASC';
$rows = $pdo->query($sql)->fetchAll();

if (empty($rows)) {
    json_response(['ok' => true, 'mode' => 'auto', 'dikirim' => 0, 'total' => 0, 'results' => []]);
}

$waStatus = wa_get_status();
$canAutoSend = !empty($cfg['wa_enabled']) && ($waStatus['status'] ?? '') === 'ready';

$messages = [];
foreach ($rows as $row) {
    $messages[] = [
        'id' => (int) $row['id'],
        'number' => $row['no'],
        'text' => build_invitation_message($row['nama'], (int) $row['id']),
        'nama' => $row['nama'],
    ];
}

$results = [];

if ($canAutoSend) {
    $batch = wa_send_batch($messages);

    if (empty($batch['ok'])) {
        json_response([
            'ok' => false,
            'error' => $batch['error'] ?? 'Gagal kirim batch ke WA service',
        ], 502);
    }

    $markSent = $pdo->prepare('UPDATE undangan SET sent = 1, sent_at = NOW() WHERE id = ?');

    foreach ($batch['results'] ?? [] as $r) {
        if (($r['status'] ?? '') === 'terkirim') {
            $markSent->execute([(int) $r['id']]);
        }
        $noWa = normalize_wa_number((string) ($r['number'] ?? ''));
        $link = $noWa ? 'https://wa.me/' . $noWa . '?text=' . rawurlencode($r['text'] ?? '') : null;
        $results[] = [
            'id' => $r['id'] ?? null,
            'nama' => $r['nama'] ?? '',
            'no' => $r['number'] ?? '',
            'status' => $r['status'] ?? 'gagal',
            'error' => $r['error'] ?? null,
            'link' => $link,
        ];
    }

    json_response([
        'ok' => true,
        'mode' => 'auto',
        'dikirim' => count(array_filter($results, fn($x) => $x['status'] === 'terkirim')),
        'total' => count($results),
        'results' => $results,
    ]);
}

// Fallback: link wa.me manual
foreach ($messages as $m) {
    $noWa = normalize_wa_number($m['number']);
    $link = $noWa ? 'https://wa.me/' . $noWa . '?text=' . rawurlencode($m['text']) : null;
    $results[] = [
        'id' => $m['id'],
        'nama' => $m['nama'],
        'no' => $m['number'],
        'status' => 'link',
        'link' => $link,
    ];
}

json_response([
    'ok' => true,
    'mode' => 'link',
    'dikirim' => 0,
    'total' => count($results),
    'results' => $results,
]);
