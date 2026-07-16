<?php

/**
 * Baca spreadsheet CSV / XLSX tanpa dependency berat.
 * CSV: native PHP. XLSX: butuh PhpSpreadsheet (composer install) atau export CSV dari Excel.
 */
function parse_spreadsheet(string $filePath, string $originalName): array
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext === 'csv') {
        return parse_csv($filePath);
    }

    if (in_array($ext, ['xlsx', 'xls'], true)) {
        $vendor = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($vendor)) {
            require_once $vendor;
            return parse_xlsx_phpspreadsheet($filePath);
        }
        throw new RuntimeException(
            'File .xlsx butuh PhpSpreadsheet. Jalankan "composer install", atau simpan spreadsheet sebagai .csv'
        );
    }

    throw new RuntimeException('Format tidak didukung. Gunakan .csv atau .xlsx');
}

function parse_csv(string $filePath): array
{
    $rows = [];
    $handle = fopen($filePath, 'r');
    if (!$handle) throw new RuntimeException('Gagal membaca file CSV');

    $headers = null;
    while (($line = fgetcsv($handle)) !== false) {
        if ($headers === null) {
            $headers = array_map('trim', $line);
            continue;
        }
        $row = [];
        foreach ($headers as $i => $h) {
            $row[$h] = $line[$i] ?? '';
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function parse_xlsx_phpspreadsheet(string $filePath): array
{
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);
    if (count($data) < 2) return [];

    $headers = array_map('trim', array_values(array_shift($data)));
    $rows = [];
    foreach ($data as $line) {
        $values = array_values($line);
        $row = [];
        foreach ($headers as $i => $h) {
            $row[$h] = $values[$i] ?? '';
        }
        if (implode('', $row) === '') continue;
        $rows[] = $row;
    }
    return $rows;
}

function insert_undangan_rows(array $rows): array
{
    require_once __DIR__ . '/instagram.php';
    ensure_undangan_instagram_columns();

    $pdo = db();
    $insert = $pdo->prepare('INSERT INTO undangan (no, instagram, nama) VALUES (?, ?, ?)');
    $inserted = 0;
    $skipped = 0;

    $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            [$noKey, $namaKey, $igKey] = find_spreadsheet_keys($row);
            $nama = $namaKey ? trim((string) $row[$namaKey]) : '';
            $noRaw = $noKey ? trim((string) $row[$noKey]) : '';
            $igRaw = $igKey ? trim((string) $row[$igKey]) : '';

            if ($nama === '') {
                $skipped++;
                continue;
            }

            $no = $noRaw !== '' ? normalize_wa_number($noRaw) : null;
            $ig = $igRaw !== '' ? normalize_instagram($igRaw) : null;

            // Minimal: harus ada WA atau Instagram
            if (!$no && !$ig) {
                $skipped++;
                continue;
            }

            $insert->execute([$no, $ig, $nama]);
            $inserted++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return ['inserted' => $inserted, 'skipped' => $skipped, 'totalBaris' => count($rows)];
}
