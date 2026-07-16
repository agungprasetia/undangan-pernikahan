<?php

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function escape_html(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function lookup_guest(array $query): ?array
{
    $pdo = db();

    if (!empty($query['id'])) {
        $stmt = $pdo->prepare('SELECT id, nama FROM undangan WHERE id = ?');
        $stmt->execute([(int) $query['id']]);
        $row = $stmt->fetch();
        if ($row) return $row;
    }

    $to = trim((string) ($query['to'] ?? $query['kepada'] ?? ''));
    if ($to !== '') {
        $decoded = urldecode($to);
        $stmt = $pdo->prepare('SELECT id, nama FROM undangan WHERE LOWER(nama) = LOWER(?)');
        $stmt->execute([$decoded]);
        $row = $stmt->fetch();
        if ($row) return $row;
    }

    return null;
}

function normalize_wa_number(string $raw): ?string
{
    // Excel kadang simpan nomor sebagai scientific notation: 8.95E+11
    $raw = trim($raw);
    if ($raw !== '' && preg_match('/^\d+(\.\d+)?[eE][+\-]?\d+$/', $raw)) {
        $raw = sprintf('%.0f', (float) $raw);
    }

    $n = preg_replace('/[^0-9]/', '', $raw);
    if (!$n) return null;

    // 0895... → 62895...
    if (strpos($n, '0') === 0) {
        $n = '62' . substr($n, 1);
    }
    // 620895... (salah) → 62895...
    if (strpos($n, '620') === 0) {
        $n = '62' . substr($n, 3);
    }
    // 895... (Excel hilangkan 0) → 62895...
    if (strpos($n, '62') !== 0 && strpos($n, '8') === 0) {
        $n = '62' . $n;
    }

    return $n;
}

function find_spreadsheet_keys(array $row): array
{
    $noKey = null;
    $namaKey = null;
    $igKey = null;
    foreach (array_keys($row) as $k) {
        $key = trim((string) $k);
        if (preg_match('/^(no|nomor|hp|telp|telepon|phone|whatsapp|wa)$/i', $key)) $noKey = $k;
        if (preg_match('/^(nama|name)$/i', $key)) $namaKey = $k;
        if (preg_match('/^(ig|instagram|username|user_ig|ig_username)$/i', $key)) $igKey = $k;
    }
    return [$noKey, $namaKey, $igKey];
}
