<?php
session_start();
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors  = [];
$success = '';
$mode    = $_GET['mode'] ?? 'list'; // list | tambah | edit
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_data = ['username' => ''];

// ── HAPUS ──────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    // Tidak boleh hapus diri sendiri
    if ($hid === (int)$_SESSION['user_id']) {
        $errors[] = 'Tidak bisa menghapus akun sendiri.';
    } else {
        $conn->query("DELETE FROM users WHERE id = $hid");
        header("Location: users.php?msg=hapus");
        exit();
    }
}

// ── LOAD DATA EDIT ─────────────────────────────────────
if ($mode === 'edit' && $edit_id) {
    $res = $conn->query("SELECT id, username FROM users WHERE id = $edit_id");
    if ($res->num_rows === 0) { header("Location: users.php"); exit(); }
    $edit_data = $res->fetch_assoc();
}

// ── PROSES FORM TAMBAH ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (!$username)              $errors[] = 'Username wajib diisi.';
    if (!$password)              $errors[] = 'Password wajib diisi.';
    if (strlen($password) < 6)  $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm)  $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username sudah digunakan.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->bind_param('ss', $username, $hashed);
            $ins->execute();
            header("Location: users.php?msg=tambah");
            exit();
        }
        $stmt->close();
    }
    $mode = 'tambah';
}

// ── PROSES FORM EDIT ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $eid      = (int)$_POST['edit_id'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    $edit_data['username'] = $username;
    $edit_id = $eid;

    if (!$username) $errors[] = 'Username wajib diisi.';
    if ($password && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Cek duplikat username (kecuali diri sendiri)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param('si', $username, $eid);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username sudah digunakan user lain.';
        } else {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET username=?, password=? WHERE id=?");
                $upd->bind_param('ssi', $username, $hashed, $eid);
            } else {
                $upd = $conn->prepare("UPDATE users SET username=? WHERE id=?");
                $upd->bind_param('si', $username, $eid);
            }
            $upd->execute();
            // Update session jika edit diri sendiri
            if ($eid === (int)$_SESSION['user_id']) {
                $_SESSION['username'] = $username;
            }
            header("Location: users.php?msg=edit");
            exit();
        }
        $stmt->close();
    }
    $mode = 'edit';
}

// ── LIST USER ──────────────────────────────────────────
$users = $conn->query("SELECT id, username FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User – Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .table thead { background: #2563eb; color: #fff; }
        .table tbody tr:hover { background: #eff6ff; }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: #dbeafe; color: #2563eb;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem;
        }
        .badge-you { font-size: .7rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand"><i class="bi bi-people-fill me-2"></i>Manajemen User</span>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-seam me-1"></i>Inventaris
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container" style="max-width: 960px;">

    <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = [
            'tambah' => ['success', 'User berhasil ditambahkan!'],
            'edit'   => ['success', 'User berhasil diperbarui!'],
            'hapus'  => ['warning', 'User berhasil dihapus!'],
        ]; $m = $msgs[$_GET['msg']] ?? null; if ($m): ?>
        <div class="alert alert-<?= $m[0] ?> alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= $m[1] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><li><?= implode('</li><li>', array_map('htmlspecialchars', $errors)) ?></li></ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Tambah / Edit -->
        <div class="col-md-4">
            <div class="card p-4">
                <?php if ($mode === 'edit'): ?>
                <h5 class="fw-bold mb-3"><i class="bi bi-pencil-fill text-warning me-2"></i>Edit User</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control"
                               value="<?= htmlspecialchars($edit_data['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                        <small class="text-muted">Min. 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru">
                    </div>
                    <div class="d-flex gap-2">
                        <a href="users.php" class="btn btn-secondary flex-fill">Batal</a>
                        <button type="submit" class="btn btn-warning flex-fill">
                            <i class="bi bi-save me-1"></i>Update
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <h5 class="fw-bold mb-3"><i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah User</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i>Tambah User
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel List User -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Daftar User</h5>
                    <span class="badge bg-primary"><?= $users->num_rows ?> user</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada user.</td></tr>
                                <?php endif; ?>
                                <?php $no = 1; while ($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><?= $no++ ?></td>
                                    <td>
                                        <span class="avatar"><?= strtoupper(substr($u['username'], 0, 2)) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['username']) ?>
                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary badge-you ms-1">Anda</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="users.php?mode=edit&id=<?= $u['id'] ?>"
                                           class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?hapus=<?= $u['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Yakin hapus user \'<?= htmlspecialchars($u['username']) ?>\'?')"
                                           title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Tidak bisa hapus diri sendiri">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
