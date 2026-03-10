<?php
require_once 'config.php';

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = $conn->query("SELECT * FROM barang WHERE id_barang = $id");
if ($res->num_rows === 0) { header("Location: index.php"); exit; }
$row = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang – <?= htmlspecialchars($row['nama_barang']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .foto-barang { width: 100%; max-height: 300px; object-fit: contain; border-radius: 10px; background: #f9fafb; }
        .label-field { font-size: .8rem; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
        .value-field { font-size: 1.05rem; font-weight: 500; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-info mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar</a>
        <a href="edit.php?id=<?= $row['id_barang'] ?>" class="btn btn-light btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Barang
        </a>
    </div>
</nav>

<div class="container" style="max-width:800px">
    <div class="card p-4">
        <h4 class="mb-4 fw-bold"><i class="bi bi-box-seam-fill text-info me-2"></i>Detail Barang</h4>
        <div class="row g-4">
            <!-- Foto -->
            <div class="col-md-4 text-center">
                <?php if ($row['foto'] && file_exists('uploads/'.$row['foto'])): ?>
                    <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" class="foto-barang" alt="Foto Barang">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light rounded"
                         style="height:200px; color:#9ca3af; flex-direction:column; gap:8px;">
                        <i class="bi bi-image" style="font-size:3rem;"></i>
                        <span class="small">Tidak ada foto</span>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Info -->
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="label-field">Kode Barang</div>
                        <div class="value-field"><code><?= htmlspecialchars($row['kode_barang']) ?></code></div>
                    </div>
                    <div class="col-6">
                        <div class="label-field">Satuan</div>
                        <div class="value-field"><?= htmlspecialchars($row['satuan']) ?></div>
                    </div>
                    <div class="col-12">
                        <div class="label-field">Nama Barang</div>
                        <div class="value-field"><?= htmlspecialchars($row['nama_barang']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label-field">Harga Beli</div>
                        <div class="value-field text-danger">Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label-field">Harga Jual</div>
                        <div class="value-field text-success">Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="label-field">Stok</div>
                        <?php $stok = (int)$row['jumlah'];
                              $cls = $stok <= 0 ? 'danger' : ($stok <= 5 ? 'warning' : 'success'); ?>
                        <div class="value-field">
                            <span class="badge bg-<?= $cls ?> fs-6"><?= $stok ?> <?= htmlspecialchars($row['satuan']) ?></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="label-field">Tanggal Masuk</div>
                        <div class="value-field">
                            <?= $row['tanggal_masuk'] ? date('d F Y', strtotime($row['tanggal_masuk'])) : '-' ?>
                        </div>
                    </div>
                    <?php
                    $margin    = $row['harga_jual'] - $row['harga_beli'];
                    $pct       = $row['harga_beli'] > 0 ? round($margin / $row['harga_beli'] * 100, 1) : 0;
                    ?>
                    <div class="col-6">
                        <div class="label-field">Margin Keuntungan</div>
                        <div class="value-field text-primary">
                            Rp <?= number_format($margin, 0, ',', '.') ?> (<?= $pct ?>%)
                        </div>
                    </div>
                    <?php if ($row['keterangan']): ?>
                    <div class="col-12">
                        <div class="label-field">Keterangan</div>
                        <div class="value-field"><?= nl2br(htmlspecialchars($row['keterangan'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="mt-4">
        <div class="d-flex gap-2 justify-content-end">
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-list me-1"></i>Semua Barang</a>
            <a href="edit.php?id=<?= $row['id_barang'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="index.php?hapus=<?= $row['id_barang'] ?>" class="btn btn-danger"
               onclick="return confirm('Yakin ingin menghapus barang ini?')">
                <i class="bi bi-trash me-1"></i>Hapus
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
