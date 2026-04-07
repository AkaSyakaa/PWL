-- Buat database
CREATE DATABASE IF NOT EXISTS inventaris_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE inventaris_db;

-- Tabel Users (untuk login)
CREATE TABLE IF NOT EXISTS users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Contoh user default (password: admin123)
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;

-- Tabel Barang
CREATE TABLE IF NOT EXISTS barang (
    id_barang    INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang  VARCHAR(50) UNIQUE NOT NULL,
    nama_barang  VARCHAR(150) NOT NULL,
    satuan       VARCHAR(50),
    harga_beli   DECIMAL(15,2),
    harga_jual   DECIMAL(15,2),
    jumlah       INT DEFAULT 0,
    tanggal_masuk DATE,
    keterangan   TEXT,
    foto         VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data contoh barang
INSERT INTO barang (kode_barang, nama_barang, satuan, harga_beli, harga_jual, jumlah, tanggal_masuk, keterangan, foto)
VALUES
('BRG001', 'Laptop Asus VivoBook',   'Unit', 7500000, 9000000, 10, '2024-01-10', 'Laptop untuk kebutuhan kantor', NULL),
('BRG002', 'Mouse Wireless Logitech','Pcs',   150000,  220000,  25, '2024-01-15', 'Mouse nirkabel ergonomis',      NULL),
('BRG003', 'Keyboard Mechanical',    'Pcs',   350000,  500000,  15, '2024-02-01', 'Keyboard gaming mechanical',    NULL)
ON DUPLICATE KEY UPDATE kode_barang=kode_barang;
