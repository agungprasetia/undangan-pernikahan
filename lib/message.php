<?php

function build_invitation_message(string $nama, ?int $id = null, string $channel = 'wa'): string
{
    $cfg = app_config();
    $base = rtrim($cfg['public_url'], '/');
    $link = $id ? "{$base}/?id={$id}" : "{$base}/?to=" . rawurlencode($nama);
    $bahagia = $cfg['nama_bahagia'];
    // WhatsApp mendukung *bold*, Instagram lebih aman plain text
    $nameLine = $channel === 'ig' ? $bahagia : "*{$bahagia}*";

    return <<<MSG
Kepada Yth.
Bapak/Ibu/Saudara/i
{$nama} & calon

Tanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i {$nama} & calon untuk menghadiri acara kami.

Berikut link undangan kami, untuk info lengkap dari acara bisa kunjungi :

{$link}

Merupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.
Terima kasih banyak atas perhatiannya.

Kami yang berbahagia
{$nameLine}
MSG;
}
