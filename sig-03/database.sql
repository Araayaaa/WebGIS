-- ============================================================
-- WebGIS BantSOSial — Database Schema
-- Database: sig_bansos
-- ============================================================

CREATE DATABASE IF NOT EXISTS sig_bansos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sig_bansos;

-- ============================================================
-- Table: religious_centers
-- ============================================================
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

-- ============================================================
-- Table: houses
-- ============================================================
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

-- ============================================================
-- Table: laporan
-- ============================================================
CREATE TABLE IF NOT EXISTS laporan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pelapor     VARCHAR(150)   DEFAULT 'Anonim',
    deskripsi   TEXT           NOT NULL,
    lokasi      VARCHAR(255)   DEFAULT '',
    foto_base64 MEDIUMTEXT,
    status      ENUM('baru','ditangani','selesai') DEFAULT 'baru',
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: aid_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS aid_logs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    house_id            INT            NOT NULL,
    religious_center_id INT            DEFAULT 0,
    status              ENUM('helped','reverted') NOT NULL,
    timestamp           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
