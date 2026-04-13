<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventaris_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// ── Fungsi buat thumbnail fisik ──────────────────────────────────────────────
function buat_thumbnail($src_path, $dest_path, $lebar = 150, $tinggi = 150) {
    $info = @getimagesize($src_path);
    if (!$info) return false;

    [$w, $h] = $info;
    $mime = $info['mime'];

    $src = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($src_path),
        'image/png'  => imagecreatefrompng($src_path),
        'image/gif'  => imagecreatefromgif($src_path),
        'image/webp' => imagecreatefromwebp($src_path),
        default      => false,
    };
    if (!$src) return false;

    // Center crop agar proporsional, tidak gepeng
    $rasio_src  = $w / $h;
    $rasio_dest = $lebar / $tinggi;
    if ($rasio_src > $rasio_dest) {
        $crop_h = $h;
        $crop_w = (int)($h * $rasio_dest);
        $crop_x = (int)(($w - $crop_w) / 2);
        $crop_y = 0;
    } else {
        $crop_w = $w;
        $crop_h = (int)($w / $rasio_dest);
        $crop_x = 0;
        $crop_y = (int)(($h - $crop_h) / 2);
    }

    $thumb = imagecreatetruecolor($lebar, $tinggi);

    // Pertahankan transparansi PNG/GIF
    if (in_array($mime, ['image/png', 'image/gif'])) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $trans = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $trans);
    }

    imagecopyresampled($thumb, $src, 0, 0, $crop_x, $crop_y, $lebar, $tinggi, $crop_w, $crop_h);
    imagejpeg($thumb, $dest_path, 85);

    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}
?>
