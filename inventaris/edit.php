<?php
require_once 'config.php';

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data barang — PREPARED STATEMENT
$stmtGet = $conn->prepare("SELECT * FROM barang WHERE id_barang = ?");
$stmtGet->bind_param('i', $id);
$stmtGet->execute();
$res = $stmtGet->get_result();
if ($res->num_rows === 0) { header("Location: index.php"); exit; }
$data = $res->fetch_assoc();
$stmtGet->close();

$errors = [];

// ──────────────────────────────────────────────────────────────────────────────
// FUNGSI VALIDASI (sama dengan tambah.php)
// ──────────────────────────────────────────────────────────────────────────────

function validasiNamaBarang(string $nilai): string|false
{
    $nilai = trim($nilai);
    if ($nilai === '') return false;
    if (preg_match('/\d/', $nilai)) return false;
    if (!preg_match('/^[\p{L}\s.,\-\'\/()]+$/u', $nilai)) return false;
    if (mb_strlen($nilai) < 2 || mb_strlen($nilai) > 150) return false;
    return $nilai;
}

function validasiKodeBarang(string $nilai): string|false
{
    $nilai = trim(strtoupper($nilai));
    if (!preg_match('/^[A-Z0-9\-_]{2,50}$/', $nilai)) return false;
    return $nilai;
}

function validasiAngkaPositif(string $nilai, bool $bolehNol = false): float|false
{
    $nilai = trim($nilai);
    if (!is_numeric($nilai)) return false;
    $angka = (float)$nilai;
    if ($bolehNol ? $angka < 0 : $angka <= 0) return false;
    return $angka;
}

function validasiTanggal(string $nilai): string|false
{
    $nilai = trim($nilai);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) return false;
    [$y, $m, $d] = explode('-', $nilai);
    if (!checkdate((int)$m, (int)$d, (int)$y)) return false;
    return $nilai;
}

function validasiKeterangan(string $nilai): string
{
    return mb_substr(strip_tags(trim($nilai)), 0, 500);
}

// ──────────────────────────────────────────────────────────────────────────────
// PROSES FORM
// ──────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Kode Barang
    $kodeValid = validasiKodeBarang($_POST['kode_barang'] ?? '');
    if ($kodeValid === false) {
        $errors['kode_barang'] = 'Kode barang wajib diisi (huruf kapital, angka, - atau _), 2–50 karakter.';
    } else {
        $data['kode_barang'] = $kodeValid;
    }

    // 2. Nama Barang
    $namaValid = validasiNamaBarang($_POST['nama_barang'] ?? '');
    if ($namaValid === false) {
        $errors['nama_barang'] = 'Nama barang wajib diisi, hanya boleh huruf dan spasi (tidak boleh mengandung angka), 2–150 karakter.';
    } else {
        $data['nama_barang'] = $namaValid;
    }

    // 3. Satuan
    $satuanDiizinkan = ['Pcs','Unit','Lusin','Kardus','Kg','Liter','Meter','Set','Box'];
    $data['satuan']  = trim($_POST['satuan'] ?? '');
    if (!in_array($data['satuan'], $satuanDiizinkan, true)) {
        $errors['satuan'] = 'Pilih satuan yang valid.';
    }

    // 4. Harga Beli
    $hargaBeliValid = validasiAngkaPositif($_POST['harga_beli'] ?? '');
    if ($hargaBeliValid === false) {
        $errors['harga_beli'] = 'Harga beli wajib diisi dan harus lebih dari 0.';
    } else {
        $data['harga_beli'] = $hargaBeliValid;
    }

    // 5. Harga Jual
    $hargaJualValid = validasiAngkaPositif($_POST['harga_jual'] ?? '');
    if ($hargaJualValid === false) {
        $errors['harga_jual'] = 'Harga jual wajib diisi dan harus lebih dari 0.';
    } else {
        $data['harga_jual'] = $hargaJualValid;
        if (isset($data['harga_beli']) && $hargaJualValid < $data['harga_beli']) {
            $errors['harga_jual'] = 'Harga jual tidak boleh lebih kecil dari harga beli.';
        }
    }

    // 6. Jumlah Stok
    $jumlahValid = validasiAngkaPositif($_POST['jumlah'] ?? '', true);
    if ($jumlahValid === false) {
        $errors['jumlah'] = 'Jumlah stok wajib diisi dan tidak boleh negatif.';
    } else {
        $data['jumlah'] = (int)$jumlahValid;
    }

    // 7. Tanggal Masuk
    $tglValid = validasiTanggal($_POST['tanggal_masuk'] ?? '');
    if ($tglValid === false) {
        $errors['tanggal_masuk'] = 'Tanggal masuk tidak valid.';
    } else {
        $data['tanggal_masuk'] = $tglValid;
    }

    // 8. Keterangan
    $data['keterangan'] = validasiKeterangan($_POST['keterangan'] ?? '');

    // 9. Cek duplikat kode (kecuali diri sendiri) — PREPARED STATEMENT
    if (!isset($errors['kode_barang'])) {
        $stmtCek = $conn->prepare("SELECT id_barang FROM barang WHERE kode_barang = ? AND id_barang != ?");
        $stmtCek->bind_param('si', $data['kode_barang'], $id);
        $stmtCek->execute();
        $stmtCek->store_result();
        if ($stmtCek->num_rows > 0) {
            $errors['kode_barang'] = 'Kode barang sudah digunakan barang lain.';
        }
        $stmtCek->close();
    }

    // 10. Upload Foto (opsional saat edit)
    $nama_foto = $data['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ftype   = mime_content_type($_FILES['foto']['tmp_name']);
        if (!in_array($ftype, $allowed)) {
            $errors['foto'] = 'Format foto harus JPG, PNG, GIF, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errors['foto'] = 'Ukuran foto maksimal 2 MB.';
        } else {
            if ($nama_foto && file_exists('uploads/' . $nama_foto)) unlink('uploads/' . $nama_foto);
            $ext       = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nama_foto = uniqid('foto_') . '.' . strtolower($ext);
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $nama_foto);
        }
    }

    // 11. Update — PREPARED STATEMENT
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE barang SET
            kode_barang=?, nama_barang=?, satuan=?, harga_beli=?, harga_jual=?,
            jumlah=?, tanggal_masuk=?, keterangan=?, foto=?
            WHERE id_barang=?");
        $stmt->bind_param(
            'sssddisssi',
            $data['kode_barang'], $data['nama_barang'], $data['satuan'],
            $data['harga_beli'],  $data['harga_jual'],  $data['jumlah'],
            $data['tanggal_masuk'], $data['keterangan'], $nama_foto, $id
        );
        $stmt->execute();
        $stmt->close();
        header("Location: index.php?msg=edit");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang – Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.21.0/dist/jquery.validate.min.js"></script>
    <style>
        body { background: #f0f4f8; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        #preview { max-width: 200px; border-radius: 10px; }
        label.error { color: #dc3545; font-size: .82rem; margin-top: 3px; display: block; }
        .is-invalid-jq { border-color: #dc3545 !important; box-shadow: 0 0 0 .2rem rgba(220,53,69,.15) !important; }
        .field-hint { font-size: .78rem; color: #6b7280; margin-top: 3px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-warning mb-4">
    <div class="container">
        <a class="navbar-brand text-dark fw-bold" href="index.php">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar
        </a>
    </div>
</nav>

<div class="container" style="max-width:700px">
    <div class="card p-4">
        <h4 class="mb-4 fw-bold"><i class="bi bi-pencil-fill text-warning me-2"></i>Edit Barang</h4>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form id="formEdit" method="POST" enctype="multipart/form-data" novalidate>
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kode Barang <span class="text-danger">*</span></label>
                    <input type="text" name="kode_barang" id="kode_barang"
                           class="form-control <?= isset($errors['kode_barang']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($data['kode_barang']) ?>" maxlength="50">
                    <?php if (isset($errors['kode_barang'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['kode_barang']) ?></div>
                    <?php endif; ?>
                    <div class="field-hint">Huruf kapital, angka, tanda hubung (-) atau garis bawah (_)</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" name="nama_barang" id="nama_barang"
                           class="form-control <?= isset($errors['nama_barang']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($data['nama_barang']) ?>" maxlength="150">
                    <?php if (isset($errors['nama_barang'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['nama_barang']) ?></div>
                    <?php endif; ?>
                    <div class="field-hint">Hanya huruf dan spasi — <strong>tidak boleh mengandung angka</strong></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                    <select name="satuan" id="satuan"
                            class="form-select <?= isset($errors['satuan']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Pcs','Unit','Lusin','Kardus','Kg','Liter','Meter','Set','Box'] as $s): ?>
                        <option value="<?= $s ?>" <?= $data['satuan']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['satuan'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['satuan']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Harga Beli (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_beli" id="harga_beli"
                           class="form-control <?= isset($errors['harga_beli']) ? 'is-invalid' : '' ?>"
                           min="1" value="<?= htmlspecialchars($data['harga_beli']) ?>">
                    <?php if (isset($errors['harga_beli'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['harga_beli']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" id="harga_jual"
                           class="form-control <?= isset($errors['harga_jual']) ? 'is-invalid' : '' ?>"
                           min="1" value="<?= htmlspecialchars($data['harga_jual']) ?>">
                    <?php if (isset($errors['harga_jual'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['harga_jual']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jumlah Stok <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah" id="jumlah"
                           class="form-control <?= isset($errors['jumlah']) ? 'is-invalid' : '' ?>"
                           min="0" value="<?= htmlspecialchars($data['jumlah']) ?>">
                    <?php if (isset($errors['jumlah'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['jumlah']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_masuk" id="tanggal_masuk"
                           class="form-control <?= isset($errors['tanggal_masuk']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($data['tanggal_masuk']) ?>">
                    <?php if (isset($errors['tanggal_masuk'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['tanggal_masuk']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ganti Foto <small class="text-muted">(opsional)</small></label>
                    <input type="file" name="foto" id="fotoInput"
                           class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if (isset($errors['foto'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['foto']) ?></div>
                    <?php endif; ?>
                    <div class="field-hint">Maks 2 MB · JPG / PNG / GIF / WEBP</div>
                </div>

                <div class="col-12 text-center">
                    <?php if ($data['foto'] && file_exists('uploads/'.$data['foto'])): ?>
                        <img id="preview" src="uploads/<?= htmlspecialchars($data['foto']) ?>" alt="Foto Barang" style="max-width:200px; border-radius:10px;">
                    <?php else: ?>
                        <img id="preview" src="" alt="" style="max-width:200px; border-radius:10px; display:none;">
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan <small class="text-muted">(opsional, maks 500 karakter)</small></label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars($data['keterangan']) ?></textarea>
                    <div class="field-hint"><span id="charCount">0</span>/500 karakter</div>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Update Barang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$.validator.addMethod('noAngka', function(value) {
    return !/\d/.test(value);
}, 'Nama barang tidak boleh mengandung angka.');

$.validator.addMethod('hanyaHuruf', function(value) {
    return /^[\u00C0-\u017Ea-zA-Z\s.,\-'\/()]+$/.test(value);
}, 'Nama barang hanya boleh mengandung huruf dan spasi.');

$.validator.addMethod('kodeFormat', function(value) {
    return /^[A-Za-z0-9\-_]{2,50}$/.test(value);
}, 'Kode hanya boleh huruf, angka, tanda hubung (-) atau garis bawah (_).');

$.validator.addMethod('hargaJualMinBeli', function(value) {
    const beli = parseFloat($('#harga_beli').val()) || 0;
    return parseFloat(value) >= beli;
}, 'Harga jual tidak boleh lebih kecil dari harga beli.');

$('#formEdit').validate({
    rules: {
        kode_barang:   { required: true, minlength: 2, maxlength: 50, kodeFormat: true },
        nama_barang:   { required: true, minlength: 2, maxlength: 150, noAngka: true, hanyaHuruf: true },
        satuan:        { required: true },
        harga_beli:    { required: true, min: 1, number: true },
        harga_jual:    { required: true, min: 1, number: true, hargaJualMinBeli: true },
        jumlah:        { required: true, min: 0, digits: true },
        tanggal_masuk: { required: true },
        foto:          { accept: 'image/jpeg|image/png|image/gif|image/webp' }
    },
    messages: {
        kode_barang:   { required: 'Kode barang wajib diisi.' },
        nama_barang:   { required: 'Nama barang wajib diisi.' },
        satuan:        { required: 'Pilih satuan terlebih dahulu.' },
        harga_beli:    { required: 'Harga beli wajib diisi.', min: 'Harga beli harus lebih dari 0.' },
        harga_jual:    { required: 'Harga jual wajib diisi.', min: 'Harga jual harus lebih dari 0.' },
        jumlah:        { required: 'Jumlah stok wajib diisi.', min: 'Stok tidak boleh negatif.', digits: 'Harus angka bulat.' },
        tanggal_masuk: { required: 'Tanggal masuk wajib diisi.' },
        foto:          { accept: 'Format harus JPG, PNG, GIF, atau WEBP.' }
    },
    highlight:   function(el) { $(el).addClass('is-invalid is-invalid-jq'); },
    unhighlight: function(el) { $(el).removeClass('is-invalid is-invalid-jq'); },
    errorPlacement: function(error, element) {
        error.addClass('text-danger small mt-1');
        error.insertAfter(element);
    }
});

document.getElementById('fotoInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('preview');
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

const ktArea = document.getElementById('keterangan');
const counter = document.getElementById('charCount');
counter.textContent = ktArea.value.length;
ktArea.addEventListener('input', () => counter.textContent = ktArea.value.length);
</script>
</body>
</html>
