<?php
/**
 * Salin file ini menjadi config.php lalu isi dengan data Hostinger kamu.
 */

return [
    // --- MySQL (dari hPanel Hostinger → Databases) ---
    'db_host' => 'localhost',
    'db_name' => 'u123456789_undangan',
    'db_user' => 'u123456789_admin',
    'db_pass' => 'PASSWORD_MYSQL_KAMU',

    // --- Website ---
    'public_url' => 'https://domain-kamu.com',
    'mempelai_pria' => 'Lutfi',
    'mempelai_wanita' => 'Elia',
    'nama_bahagia' => 'Elia & Lutfi',

    // --- Admin ---
    'admin_password' => 'ganti-password-kuat',

    // --- WhatsApp via Railway ---
    // URL service Node.js di Railway, contoh: https://wa-service-production-xxxx.up.railway.app
    'wa_service_url' => 'https://YOUR-RAILWAY-APP.up.railway.app',
    // API key harus SAMA dengan WA_API_KEY di Railway
    'wa_api_key' => 'buat-string-rahasia-panjang-di-sini',
    'wa_enabled' => true,
];
