# Sistem Informasi Manajemen Inventaris

Aplikasi CRUD sederhana untuk manajemen inventaris barang menggunakan **PHP** dan **MySQL**.

## Fitur
- ✅ **Create** – Tambah barang baru dengan upload foto
- ✅ **Read** – Daftar barang dengan pencarian & detail lengkap
- ✅ **Update** – Edit data barang termasuk ganti foto
- ✅ **Delete** – Hapus barang (foto ikut terhapus)
- ✅ Validasi form & indikator stok (merah/kuning/hijau)
- ✅ Kalkulasi margin keuntungan otomatis

## Struktur File
```
inventaris/
├── config.php       # Koneksi database
├── database.sql     # Script buat tabel + data contoh
├── index.php        # Halaman utama (list + hapus)
├── tambah.php       # Form tambah barang
├── edit.php         # Form edit barang
├── detail.php       # Halaman detail barang
└── uploads/         # Folder foto (dibuat otomatis)
```

## Cara Install

### 1. Persyaratan
- XAMPP / Laragon / WAMP (PHP 7.4+ & MySQL 5.7+)

### 2. Setup Database
1. Buka **phpMyAdmin** → klik **Import**
2. Pilih file `database.sql` → klik **Go**
3. Database `inventaris_db` dan tabel `barang` otomatis terbuat

### 3. Konfigurasi
Edit `config.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');    // sesuaikan
define('DB_PASS', '');        // sesuaikan
define('DB_NAME', 'inventaris_db');
```

### 4. Jalankan
Letakkan folder `inventaris/` di dalam `htdocs/` (XAMPP), lalu buka:
```
http://localhost/inventaris/
```

## Struktur Tabel `barang`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id_barang | INT AUTO_INCREMENT | Primary Key |
| kode_barang | VARCHAR(50) UNIQUE | Kode unik barang |
| nama_barang | VARCHAR(150) | Nama lengkap barang |
| satuan | VARCHAR(50) | Satuan (Pcs, Unit, Kg, dll) |
| harga_beli | DECIMAL(15,2) | Harga pembelian |
| harga_jual | DECIMAL(15,2) | Harga penjualan |
| jumlah | INT | Stok tersedia |
| tanggal_masuk | DATE | Tanggal masuk barang |
| keterangan | TEXT | Deskripsi tambahan |
| foto | VARCHAR(100) | Nama file foto |
