-- Migrasi untuk database undangan yang sudah ada
-- Jalankan sekali di phpMyAdmin

ALTER TABLE undangan
  MODIFY COLUMN no VARCHAR(20) NULL;

-- Abaikan error "Duplicate column" jika kolom sudah ada
ALTER TABLE undangan
  ADD COLUMN instagram VARCHAR(60) NULL AFTER no;

ALTER TABLE undangan
  ADD COLUMN sent_ig TINYINT(1) NOT NULL DEFAULT 0 AFTER sent;

ALTER TABLE undangan
  ADD COLUMN sent_ig_at DATETIME NULL AFTER sent_at;

ALTER TABLE undangan
  ADD INDEX idx_undangan_instagram (instagram);

ALTER TABLE undangan
  ADD INDEX idx_undangan_sent_ig (sent_ig);
