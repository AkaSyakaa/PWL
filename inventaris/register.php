<?php
session_start();
require_once 'config.php';

// Jika sudah login, langsung ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors  = [];
$success = '';
$data    = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username']);
    $password         = trim($_POST['password']);
    $confirm          = trim($_POST['confirm_password']);

    if (!$data['username'])   $errors[] = 'Username wajib diisi.';
    if (!$password)           $errors[] = 'Password wajib diisi.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Cek duplikat username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $data['username']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Username sudah digunakan, pilih yang lain.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->bind_param('ss', $data['username'], $hashed);
            $ins->execute();
            $success = 'Registrasi berhasil! Silakan login.';
            $data['username'] = '';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi – Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }
        .login-logo {
            background: #2563eb;
            color: #fff;
            width: 60px; height: 60px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1.2rem;
        }
        .btn-register {
            background: #2563eb; color: #fff;
            border: none; padding: .7rem;
            font-weight: 600; border-radius: 8px;
            transition: background .2s;
        }
        .btn-register:hover { background: #1d4ed8; color: #fff; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.2); }
    </style>
</head>
<body>
<div class="register-card">
    <div class="login-logo"><i class="bi bi-person-plus"></i></div>
    <h4 class="text-center fw-bold mb-1">Buat Akun Baru</h4>
    <p class="text-center text-muted small mb-4">Daftarkan diri Anda</p>

    <?php if ($errors): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i><?= $success ?>
        <a href="login.php" class="fw-semibold">Login sekarang</a>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Pilih username"
                       value="<?= htmlspecialchars($data['username']) ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Konfirmasi Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-register w-100">
            <i class="bi bi-person-check me-2"></i>Daftar
        </button>
    </form>

    <hr class="my-3">
    <p class="text-center small text-muted mb-0">
        Sudah punya akun? <a href="login.php" class="text-primary fw-semibold">Login di sini</a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
