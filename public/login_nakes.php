<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'nakes' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // SET SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = 'nakes';

            header('Location: ' . BASE_URL . '/nakes/dashboard.php');
            exit;

        } else {
            $error = 'Email atau password salah. Atau Anda bukan tenaga kesehatan.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Tenaga Kesehatan - Gizi Balita</title>
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
      background: radial-gradient(circle at top left, #ebf7ff 0, #f7fbff 40%, #ffffff 100%);
    }

    .page-wrapper {
      flex: 1 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
    }

    .auth-card {
      max-width: 430px;
      width: 100%;
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 18px 40px rgba(0,0,0,0.1);
    }

    .auth-header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .auth-header-icon {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #e3f2ff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.9rem;
      color: #1d74b7;
    }

    .auth-header-sub {
      font-size: .8rem;
    }

    footer {
      flex-shrink: 0;
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #e2f6fa;
      font-size: .85rem;
    }
    footer a {
      color: #b8e9ff;
      text-decoration: none;
    }
    footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
      <i class="bi bi-hospital-fill me-2"></i>
      <span>GiziBalita</span>
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= BASE_URL ?>/login_ortu.php" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-people me-1"></i> Login Orang Tua
      </a>
    </div>
  </div>
</nav>

<!-- KONTEN LOGIN -->
<div class="page-wrapper">
  <div class="card auth-card">
    <div class="card-body p-4 p-md-5">

      <div class="auth-header">
        <div class="auth-header-icon">
          <i class="bi bi-shield-plus fs-4"></i>
        </div>
        <h5 class="mb-1">Login Tenaga Kesehatan</h5>
        <p class="text-muted auth-header-sub mb-0">
          Masuk untuk mengelola data balita, pemeriksaan gizi, dan artikel edukasi.
        </p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label small text-muted">Email</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input
              type="email"
              name="email"
              class="form-control"
              placeholder="nakes@puskesmas.go.id"
              required
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Password</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
              type="password"
              name="password"
              class="form-control"
              placeholder="Masukkan password"
              required
            >
          </div>
        </div>

        <div class="d-grid mt-3">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            Login
          </button>
        </div>
      </form>

      <hr class="my-4">

      <div class="text-center small">
        <p class="mb-1 text-muted">
          Akses hanya untuk tenaga kesehatan yang terdaftar.
        </p>
        <p class="mb-0">
          Bukan tenaga kesehatan? 
          <a href="<?= BASE_URL ?>/login_ortu.php" class="text-primary fw-semibold">
            Login sebagai Orang Tua
          </a>
        </p>
      </div>

    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.
      </div>
      <div class="text-md-end">
        <span class="me-2">Dukungan Anda membantu mencegah stunting dan masalah gizi lainnya.</span>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
