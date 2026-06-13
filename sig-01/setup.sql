-- ============================================================
-- WebGIS SPBU - Database Setup
-- Jalankan script ini di MySQL/phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS sig_spbu
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sig_spbu;

CREATE TABLE IF NOT EXISTS spbu (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(50)     NOT NULL UNIQUE COMMENT 'Kode unik SPBU',
    nama        VARCHAR(255)    NOT NULL COMMENT 'Nama SPBU',
    is_24jam    TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = buka 24 jam',
    latitude    DECIMAL(10, 8)  NOT NULL,
    longitude   DECIMAL(11, 8)  NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data contoh (Pontianak, Kalimantan Barat)
INSERT INTO spbu (kode, nama, is_24jam, latitude, longitude) VALUES
('61.701.01', 'SPBU Pertamina Ahmad Yani',          1, -0.02830, 109.34150),
('61.701.02', 'SPBU Pertamina Gajah Mada',           0, -0.01970, 109.33490),
('61.701.03', 'SPBU Pertamina Tanjungpura',          1, -0.03480, 109.33170),
('61.701.04', 'SPBU Pertamina Imam Bonjol',          0, -0.05680, 109.34670),
('61.701.05', 'SPBU Pertamina Soekarno-Hatta',       1, -0.06510, 109.35540),
('61.701.06', 'SPBU Pertamina Sultan Syarif Abdurrahman', 0, -0.01330, 109.32890),
('61.701.07', 'SPBU Pertamina Sei Raya Dalam',       1, -0.09420, 109.36800);
