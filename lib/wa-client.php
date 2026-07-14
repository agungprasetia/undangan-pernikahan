<?php

/**
 * Client untuk memanggil micro-service WhatsApp di Railway.
 */
function wa_service_request(string $method, string $path, ?array $body = null): array
{
    $cfg = app_config();
    $base = rtrim($cfg['wa_service_url'] ?? '', '/');
    $key = $cfg['wa_api_key'] ?? '';

    if (!$base || !$key) {
        return ['ok' => false, 'error' => 'wa_service_url atau wa_api_key belum diisi di config.php'];
    }

    $url = $base . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => 'Gagal hubungi WA service: ' . $err];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Respons WA service tidak valid (HTTP ' . $httpCode . ')'];
    }

    if ($httpCode >= 400) {
        $data['ok'] = false;
        if (!isset($data['error'])) {
            $data['error'] = 'HTTP ' . $httpCode;
        }
    }

    return $data;
}

function wa_get_status(): array
{
    $cfg = app_config();
    if (empty($cfg['wa_enabled'])) {
        return ['enabled' => false, 'status' => 'off', 'hasQr' => false, 'waEnabled' => false];
    }
    $res = wa_service_request('GET', '/status');
    $res['waEnabled'] = !empty($cfg['wa_enabled']);
    return $res;
}

function wa_get_qr(): array
{
    return wa_service_request('GET', '/qr');
}

function wa_init(): array
{
    return wa_service_request('POST', '/init');
}

function wa_logout(): array
{
    return wa_service_request('POST', '/logout');
}

function wa_send_batch(array $messages): array
{
    return wa_service_request('POST', '/send-batch', ['messages' => $messages]);
}
