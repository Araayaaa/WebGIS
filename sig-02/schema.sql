-- ============================================================
--  SIG Mapping Tanah & Jalan - Skema Database
--  MySQL 8.x
-- ============================================================

CREATE DATABASE IF NOT EXISTS sig_mapping
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sig_mapping;

-- ------------------------------------------------------------
-- Tabel TANAH (disimpan sebagai polygon)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tanah (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(150) NOT NULL,
  pemilik     VARCHAR(150) NULL,
  kategori    VARCHAR(80) NULL,            -- status: SHM, HGB, HGU, HP
  deskripsi   TEXT NULL,
  luas        DOUBLE NOT NULL DEFAULT 0,   -- meter persegi (m2)
  keliling    DOUBLE NOT NULL DEFAULT 0,   -- meter (m)
  warna       VARCHAR(20) NOT NULL DEFAULT '#22c55e',
  geojson     JSON NOT NULL,               -- GeoJSON geometry (Polygon)
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel JALAN (disimpan sebagai polyline / linestring)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jalan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(150) NOT NULL,
  jenis       VARCHAR(50) NULL,            -- mis. Aspal, Beton, Tanah
  kategori    VARCHAR(80) NULL,            -- Jalan Nasional, Jalan Provinsi, Jalan Kabupaten
  deskripsi   TEXT NULL,
  panjang     DOUBLE NOT NULL DEFAULT 0,   -- meter (m)
  warna       VARCHAR(20) NOT NULL DEFAULT '#ef4444',
  geojson     JSON NOT NULL,               -- GeoJSON geometry (LineString)
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Migrasi: tambah kolom kategori bila tabel lama belum punya.
-- MySQL 8 tidak mendukung "ADD COLUMN IF NOT EXISTS", jadi dicek dulu
-- via information_schema. Aman dijalankan berulang.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS add_kategori_columns;
DELIMITER //
CREATE PROCEDURE add_kategori_columns()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tanah' AND COLUMN_NAME = 'kategori'
  ) THEN
    ALTER TABLE tanah ADD COLUMN kategori VARCHAR(80) NULL AFTER pemilik;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jalan' AND COLUMN_NAME = 'kategori'
  ) THEN
    ALTER TABLE jalan ADD COLUMN kategori VARCHAR(80) NULL AFTER jenis;
  END IF;
END //
DELIMITER ;
CALL add_kategori_columns();
DROP PROCEDURE add_kategori_columns;
