<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$errors = [];
$data   = ['kode_barang'=>'','nama_barang'=>'','satuan'=>'','harga_beli'=>'',
           'harga_jual'=>'','jumlah'=>'','tanggal_masuk'=>'','keterangan'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['kode_barang']   = trim($_POST['kode_barang']);
    $data['nama_barang']   = trim($_POST['nama_barang']);
    $data['satuan']        = trim($_POST['satuan']);
    $data['harga_beli']    = trim($_POST['harga_beli']);
    $data['harga_jual']    = trim($_POST['harga_jual']);
    $data['jumlah']        = trim($_POST['jumlah']);
    $data['tanggal_masuk'] = trim($_POST['tanggal_masuk']);
    $data['keterangan']    = trim($_POST['keterangan']);

    if (!$data['kode_barang'])      $errors[] = 'Kode barang wajib diisi.';
    if (!$data['nama_barang'])      $errors[] = 'Nama barang wajib diisi.';
    if (!$data['satuan'])           $errors[] = 'Satuan wajib diisi.';
    if ($data['harga_beli'] === '') $errors[] = 'Harga beli wajib diisi.';
    if ($data['harga_jual'] === '') $errors[] = 'Harga jual wajib diisi.';
    if ($data['jumlah'] === '')     $errors[] = 'Jumlah stok wajib diisi.';
    if (!$data['tanggal_masuk'])    $errors[] = 'Tanggal masuk wajib diisi.';

    $kode = $conn->real_escape_string($data['kode_barang']);
    $cek  = $conn->query("SELECT id_barang FROM barang WHERE kode_barang='$kode'");
    if ($cek->num_rows > 0) $errors[] = 'Kode barang sudah digunakan.';

    $nama_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ftype   = mime_content_type($_FILES['foto']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $errors[] = 'Format foto harus JPG, PNG, GIF, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nama_foto = uniqid('foto_') . '.' . strtolower($ext);
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto);
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO barang
            (kode_barang,nama_barang,satuan,harga_beli,harga_jual,jumlah,tanggal_masuk,keterangan,foto)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssddisss',
            $data['kode_barang'], $data['nama_barang'], $data['satuan'],
            $data['harga_beli'],  $data['harga_jual'],  $data['jumlah'],
            $data['tanggal_masuk'], $data['keterangan'], $nama_foto);
        $stmt->execute();
        header("Location: index.php?msg=tambah");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang – Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        #preview { max-width: 200px; border-radius: 10px; display: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar</a>
        <span class="text-white small opacity-75"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?></span>
    </div>
</nav>

<div class="container" style="max-width:700px">
    <div class="card p-4">
        <h4 class="mb-4 fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Barang Baru</h4>

        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kode Barang <span class="text-danger">*</span></label>
                    <input type="text" name="kode_barang" class="form-control"
                           value="<?= htmlspecialchars($data['kode_barang']) ?>" placeholder="Contoh: BRG001">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" name="nama_barang" class="form-control"
                           value="<?= htmlspecialchars($data['nama_barang']) ?>" placeholder="Nama lengkap barang">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                    <select name="satuan" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Pcs','Unit','Lusin','Kardus','Kg','Liter','Meter','Set','Box'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['satuan']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Harga Beli (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_beli" class="form-control" min="0"
                           value="<?= htmlspecialchars($data['harga_beli']) ?>" placeholder="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" class="form-control" min="0"
                           value="<?= htmlspecialchars($data['harga_jual']) ?>" placeholder="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jumlah Stok <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah" class="form-control" min="0"
                           value="<?= htmlspecialchars($data['jumlah']) ?>" placeholder="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_masuk" class="form-control"
                           value="<?= htmlspecialchars($data['tanggal_masuk']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Foto Barang <span class="text-danger">*</span></label>
                    <input type="file" name="foto" class="form-control" accept="image/*" id="fotoInput">
                    <small class="text-muted">Maks 2 MB · JPG/PNG/GIF/WEBP</small>
                </div>
                <div class="col-12 text-center">
                    <img id="preview" src="" alt="Preview Foto">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"
                              placeholder="Deskripsi tambahan (opsional)"><?= htmlspecialchars($data['keterangan']) ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan Barang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('fotoInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
