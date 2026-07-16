<?php
/**
 * Instagram helper endpoints:
 * GET  → daftar tamu yang punya IG (+ pesan & link)
 * POST → tandai sent_ig = 1 (body: { id } atau { ids: [] } atau { all: true })
 */
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';
require_once dirname(__DIR__, 2) . '/lib/instagram.php';
require_once dirname(__DIR__, 2) . '/lib/message.php';

require_admin();
ensure_undangan_instagram_columns();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $onlyUnsent = ($_GET['onlyUnsent'] ?? '1') !== '0';
    $sql = $onlyUnsent
        ? "SELECT id, nama, instagram, sent_ig FROM undangan WHERE instagram IS NOT NULL AND instagram != '' AND sent_ig = 0 ORDER BY id ASC"
        : "SELECT id, nama, instagram, sent_ig FROM undangan WHERE instagram IS NOT NULL AND instagram != '' ORDER BY id ASC";
    $rows = $pdo->query($sql)->fetchAll();

    $results = [];
    foreach ($rows as $r) {
        $ig = $r['instagram'];
        $results[] = [
            'id' => (int) $r['id'],
            'nama' => $r['nama'],
            'instagram' => $ig,
            'sent_ig' => (int) $r['sent_ig'],
            'profile' => instagram_profile_url($ig),
            'dm' => instagram_dm_url($ig),
            'pesan' => build_invitation_message($r['nama'], (int) $r['id'], 'ig'),
        ];
    }

    json_response(['ok' => true, 'total' => count($results), 'results' => $results]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $mark = $pdo->prepare('UPDATE undangan SET sent_ig = 1, sent_ig_at = NOW() WHERE id = ?');

    if (!empty($body['all'])) {
        $pdo->exec("UPDATE undangan SET sent_ig = 1, sent_ig_at = NOW() WHERE instagram IS NOT NULL AND instagram != ''");
        json_response(['ok' => true]);
    }

    $ids = [];
    if (!empty($body['ids']) && is_array($body['ids'])) {
        $ids = array_map('intval', $body['ids']);
    } elseif (!empty($body['id'])) {
        $ids = [(int) $body['id']];
    }

    if (!$ids) {
        json_response(['ok' => false, 'error' => 'id / ids wajib'], 400);
    }

    foreach ($ids as $id) {
        $mark->execute([$id]);
    }
    json_response(['ok' => true, 'marked' => count($ids)]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
