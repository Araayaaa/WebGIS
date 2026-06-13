-- ============================================================
-- Combined DB init
-- Run once to create all databases: sig_spbu, sig_mapping, sig_bansos
-- ============================================================

-- ============================================================
-- sig-01: WebGIS SPBU
-- ============================================================
CREATE DATABASE IF NOT EXISTS sig_spbu
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sig_spbu;

CREATE TABLE IF NOT EXISTS spbu (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(50)     NOT NULL UNIQUE,
    nama        VARCHAR(255)    NOT NULL,
    is_24jam    TINYINT(1)      NOT NULL DEFAULT 0,
    latitude    DECIMAL(10, 8)  NOT NULL,
    longitude   DECIMAL(11, 8)  NOT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO spbu (kode, nama, is_24jam, latitude, longitude) VALUES
('61.701.01', 'SPBU Pertamina Ahmad Yani',               1, -0.02830, 109.34150),
('61.701.02', 'SPBU Pertamina Gajah Mada',               0, -0.01970, 109.33490),
('61.701.03', 'SPBU Pertamina Tanjungpura',              1, -0.03480, 109.33170),
('61.701.04', 'SPBU Pertamina Imam Bonjol',              0, -0.05680, 109.34670),
('61.701.05', 'SPBU Pertamina Soekarno-Hatta',           1, -0.06510, 109.35540),
('61.701.06', 'SPBU Pertamina Sultan Syarif Abdurrahman',0, -0.01330, 109.32890),
('61.701.07', 'SPBU Pertamina Sei Raya Dalam',           1, -0.09420, 109.36800);

-- ============================================================
-- sig-02: SIG Mapping Tanah & Jalan
-- ============================================================
CREATE DATABASE IF NOT EXISTS sig_mapping
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sig_mapping;

CREATE TABLE IF NOT EXISTS tanah (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    pemilik     VARCHAR(150) NULL,
    kategori    VARCHAR(80)  NULL,
    deskripsi   TEXT         NULL,
    luas        DOUBLE       NOT NULL DEFAULT 0,
    keliling    DOUBLE       NOT NULL DEFAULT 0,
    warna       VARCHAR(20)  NOT NULL DEFAULT '#22c55e',
    geojson     JSON         NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jalan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    jenis       VARCHAR(50)  NULL,
    kategori    VARCHAR(80)  NULL,
    deskripsi   TEXT         NULL,
    panjang     DOUBLE       NOT NULL DEFAULT 0,
    warna       VARCHAR(20)  NOT NULL DEFAULT '#ef4444',
    geojson     JSON         NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- sig-03: WebGIS BanSos
-- ============================================================
CREATE DATABASE IF NOT EXISTS sig_bansos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sig_bansos;

CREATE TABLE IF NOT EXISTS religious_centers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)   NOT NULL,
    address     TEXT,
    kas         DECIMAL(15,2)  DEFAULT 0,
    latitude    DOUBLE         NOT NULL,
    longitude   DOUBLE         NOT NULL,
    radius      INT            DEFAULT 300,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS houses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    latitude        DOUBLE         NOT NULL,
    longitude       DOUBLE         NOT NULL,
    address         TEXT,
    rt              VARCHAR(10)    DEFAULT '',
    rw              VARCHAR(10)    DEFAULT '',
    kelurahan       VARCHAR(100)   DEFAULT '',
    status_miskin   ENUM('sangat_miskin','miskin','tidak_miskin','') DEFAULT '',
    jumlah_anggota  INT            DEFAULT 0,
    anggota         JSON,
    aid_status      ENUM('helped','not_helped','outside') DEFAULT 'outside',
    has_data        TINYINT(1)     DEFAULT 0,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS laporan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pelapor     VARCHAR(150)   DEFAULT 'Anonim',
    deskripsi   TEXT           NOT NULL,
    lokasi      VARCHAR(255)   DEFAULT '',
    foto_base64 MEDIUMTEXT,
    status      ENUM('baru','ditangani','selesai') DEFAULT 'baru',
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aid_logs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    house_id            INT            NOT NULL,
    religious_center_id INT            DEFAULT 0,
    status              ENUM('helped','reverted') NOT NULL,
    timestamp           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
