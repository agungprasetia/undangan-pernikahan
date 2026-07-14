-- Database: undangan (buat di hPanel Hostinger → MySQL Databases)
-- Jalankan di phpMyAdmin setelah database dibuat

CREATE TABLE IF NOT EXISTS undangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  sent TINYINT(1) NOT NULL DEFAULT 0,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_undangan_sent (sent),
  INDEX idx_undangan_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS komentar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(60) NOT NULL,
  pesan TEXT NOT NULL,
  kehadiran VARCHAR(30) DEFAULT 'Hadir',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
