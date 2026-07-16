<?php
/**
 * Salin file ini menjadi config.php lalu isi dengan data Hostinger kamu.
 * Jangan upload config.example.php saja — harus bernama config.php
 */

return [
    // --- MySQL (dari hPanel Hostinger → Databases) ---
    'db_host' => 'localhost',
    'db_name' => 'u619145000_undangan',   // ganti dengan nama database Hostinger
    'db_user' => 'u619145000_admin',      // ganti dengan username MySQL Hostinger
    'db_pass' => 'u619145000_adminA',  // ganti dengan password MySQL

    // --- Website ---
    'public_url' => 'https://elialutfiinvitations.com',
    'mempelai_pria' => 'Lutfi',
    'mempelai_wanita' => 'Elia',
    'nama_bahagia' => 'Elia & Lutfi',

    // --- Admin ---
    'admin_password' => 'admin123',

    // --- WhatsApp via Railway ---
    'wa_service_url' => 'https://wa-service-production-056e.up.railway.app/',
    'wa_api_key' => 'buat-string-rahasia-panjang-di-sini',
    'wa_enabled' => true,
];
