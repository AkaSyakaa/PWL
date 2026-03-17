<?php
require_once 'config.php';

// ── Hapus Barang — PREPARED STATEMENT ─────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hapusId = (int)$_GET['hapus'];

    // Ambil nama foto dulu — PREPARED STATEMENT
    $stmtFoto = $conn->prepare("SELECT foto FROM barang WHERE id_barang = ?");
    $stmtFoto->bind_param('i', $hapusId);
    $stmtFoto->execute();
    $resFoto = $stmtFoto->get_result();
    $rowFoto = $resFoto->fetch_assoc();
    $stmtFoto->close();

    if ($rowFoto && $rowFoto['foto'] && file_exists('uploads/' . $rowFoto['foto'])) {
        unlink('uploads/' . $rowFoto['foto']);
    }

    // Hapus record — PREPARED STATEMENT
    $stmtHapus = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
    $stmtHapus->bind_param('i', $hapusId);
    $stmtHapus->execute();
    $stmtHapus->close();

    header("Location: index.php?msg=hapus");
    exit;
}

// ── Pencarian — PREPARED STATEMENT ────────────────────────────────────────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Sanitasi input pencarian (hanya izinkan karakter aman)
$search = preg_replace('/[^\p{L}\p{N}\s\-_.,]/u', '', $search);

if ($search !== '') {
    $like   = '%' . $search . '%';
    $stmt   = $conn->prepare("SELECT * FROM barang WHERE kode_barang LIKE ? OR nama_barang LIKE ? ORDER BY id_barang DESC");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM barang ORDER BY id_barang DESC");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .table thead { background: #2563eb; color: #fff; }
        .table tbody tr:hover { background: #eff6ff; }
        .badge-stok { font-size: .8rem; }
        .foto-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .foto-placeholder { width: 50px; height: 50px; background: #e5e7eb; border-radius: 8px;
                            display:flex; align-items:center; justify-content:center; color:#9ca3af; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand"><i class="bi bi-box-seam me-2"></i>Inventaris Barang</span>
        <a href="tambah.php" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-plus-circle me-1"></i>Tambah Barang
        </a>
    </div>
</nav>

<div class="container">

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = [
            'tambah' => ['success', 'Barang berhasil ditambahkan!'],
            'edit'   => ['success', 'Barang berhasil diperbarui!'],
            'hapus'  => ['warning', 'Barang berhasil dihapus!']
        ]; ?>
        <?php $m = $msgs[$_GET['msg']] ?? null; if ($m): ?>
        <div class="alert alert-<?= $m[0] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $m[1] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card mb-4 p-3">
        <form class="row g-2" method="GET">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari kode atau nama barang..."
                           value="<?= htmlspecialchars($search) ?>" maxlength="100">
                    <button class="btn btn-primary" type="submit">Cari</button>
                    <?php if ($search): ?>
                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 text-md-end d-flex align-items-center justify-content-md-end">
                <span class="text-muted small">Total: <strong><?= $result->num_rows ?></strong> barang</span>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">No</th>
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Tgl Masuk</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data barang.</td></tr>
                        <?php endif; ?>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3"><?= $no++ ?></td>
                            <td>
                                <?php if ($row['foto'] && file_exists('uploads/'.$row['foto'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($row['foto']) ?>"
                                         class="foto-thumb" alt="foto">
                                <?php else: ?>
                                    <div class="foto-placeholder"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($row['kode_barang']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= htmlspecialchars($row['satuan']) ?></td>
                            <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                            <td>
                                <?php $stok = (int)$row['jumlah'];
                                      $cls = $stok <= 0 ? 'danger' : ($stok <= 5 ? 'warning' : 'success'); ?>
                                <span class="badge bg-<?= $cls ?> badge-stok"><?= $stok ?></span>
                            </td>
                            <td><?= $row['tanggal_masuk'] ? date('d/m/Y', strtotime($row['tanggal_masuk'])) : '-' ?></td>
                            <td class="text-center">
                                <a href="detail.php?id=<?= (int)$row['id_barang'] ?>"
                                   class="btn btn-sm btn-outline-info me-1" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= (int)$row['id_barang'] ?>"
                                   class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="index.php?hapus=<?= (int)$row['id_barang'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin ingin menghapus barang ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
