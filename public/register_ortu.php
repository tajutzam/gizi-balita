<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        // cek email sudah dipakai atau belum
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt->close();
            $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'ortu')");
            $stmt->bind_param('sss', $name, $email, $hash);

            if ($stmt->execute()) {
                $success = 'Registrasi berhasil. Silakan login.';
            } else {
                $error = 'Gagal menyimpan data. Coba lagi.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi Orang Tua - Gizi Balita</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    :root {
      --brand-main: #0f9d58;
      --brand-soft: #e0f7ec;
      --brand-dark: #0b7542;
    }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: radial-gradient(circle at top left, #f1fff7 0, #f8fffb 40%, #ffffff 100%);
    }

    .page-wrapper {
      flex: 1 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
    }

    .auth-card {
      max-width: 450px;
      width: 100%;
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 18px 40px rgba(0,0,0,0.08);
    }

    .auth-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .auth-header-icon {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--brand-soft);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: var(--brand-main);
    }

    footer {
      flex-shrink: 0;
      background: linear-gradient(120deg, #0b4125, #0b7542);
      color: #e2f6ea;
      font-size: .85rem;
    }
    footer a {
      color: #b8f3d1;
      text-decoration: none;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold text-success d-flex align-items-center" href="#">
      <i class="bi bi-heart-fill me-2"></i>
      <span>GiziBalita</span>
    </a>
    <div class="ms-auto">
      <a href="login_ortu.php" class="btn btn-outline-success btn-sm">
        <i class="bi bi-box-arrow-in-right me-1"></i> Login
      </a>
    </div>
  </div>
</nav>

<!-- REGISTER FORM -->
<div class="page-wrapper">
  <div class="card auth-card">
    <div class="card-body p-4 p-md-5">

      <div class="auth-header">
        <div class="auth-header-icon">
          <i class="bi bi-person-plus-fill fs-4"></i>
        </div>
        <h5 class="mb-1">Registrasi Orang Tua</h5>
        <p class="text-muted small mb-0">
          Buat akun untuk mengakses data perkembangan dan status gizi balita Anda.
        </p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small">
          <i class="bi bi-exclamation-circle me-1"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success py-2 small">
          <i class="bi bi-check-circle me-1"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">

        <div class="mb-3">
          <label class="form-label small text-muted">Nama Lengkap</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="name" class="form-control" placeholder="Nama lengkap Anda" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Email</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Password</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Konfirmasi Password</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-repeat"></i></span>
            <input type="password" name="confirm" class="form-control" placeholder="Ulangi password" required>
          </div>
        </div>

        <div class="d-grid mt-3">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-person-plus me-1"></i> Daftar
          </button>
        </div>
      </form>

      <hr class="my-4">

      <div class="text-center small">
        <p class="mb-0">
          Sudah punya akun?
          <a href="login_ortu.php" class="text-success fw-semibold">
            Login di sini
          </a>
        </p>
      </div>

    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between">
      <div>&copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Semua hak dilindungi.</div>
      <div>Pastikan email yang Anda masukkan benar ✔️</div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
