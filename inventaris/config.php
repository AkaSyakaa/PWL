<?php
// ──────────────────────────────────────────────────────────────────────────────
// Konfigurasi Database
// ──────────────────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventaris_db');

// Koneksi dengan mysqli
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Jangan tampilkan detail error ke user di production
    error_log("Koneksi DB gagal: " . $conn->connect_error);
    die("Terjadi kesalahan koneksi database. Silakan hubungi administrator.");
}

$conn->set_charset("utf8mb4"); // utf8mb4 lebih aman daripada utf8

// Aktifkan strict mode MySQL untuk validasi data lebih ketat
$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
