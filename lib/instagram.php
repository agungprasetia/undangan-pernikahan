<?php

/**
 * Pastikan kolom Instagram ada (aman dipanggil berkali-kali).
 */
function ensure_undangan_instagram_columns(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = db();
    $cols = $pdo->query('SHOW COLUMNS FROM undangan')->fetchAll(PDO::FETCH_COLUMN);
    $set = array_flip($cols);

    if (!isset($set['instagram'])) {
        $pdo->exec('ALTER TABLE undangan ADD COLUMN instagram VARCHAR(60) NULL AFTER no');
    }
    if (!isset($set['sent_ig'])) {
        $pdo->exec('ALTER TABLE undangan ADD COLUMN sent_ig TINYINT(1) NOT NULL DEFAULT 0 AFTER sent');
    }
    if (!isset($set['sent_ig_at'])) {
        $pdo->exec('ALTER TABLE undangan ADD COLUMN sent_ig_at DATETIME NULL AFTER sent_at');
    }

    // Izinkan no kosong (tamu IG saja)
    try {
        $pdo->exec('ALTER TABLE undangan MODIFY COLUMN no VARCHAR(20) NULL');
    } catch (Throwable $e) {
        // abaikan jika tidak perlu
    }
}

function normalize_instagram(string $raw): ?string
{
    $u = trim($raw);
    $u = ltrim($u, '@');
    $u = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $u);
    $u = explode('?', $u)[0];
    $u = trim($u, "/ \t\n\r\0\x0B");
    $u = strtolower($u);

    if ($u === '' || !preg_match('/^[a-z0-9._]{1,30}$/', $u)) {
        return null;
    }
    return $u;
}

function instagram_profile_url(string $username): string
{
    return 'https://www.instagram.com/' . rawurlencode($username) . '/';
}

function instagram_dm_url(string $username): string
{
    // Deep link buka chat (teks tetap disalin manual)
    return 'https://ig.me/m/' . rawurlencode($username);
}
