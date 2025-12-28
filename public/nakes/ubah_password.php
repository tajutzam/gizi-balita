<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

$userId = $_SESSION['user_id'];

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($oldPass === '' || $newPass === '' || $confirm === '') {
        $err = "Semua field wajib diisi.";
    } elseif ($newPass !== $confirm) {
        $err = "Konfirmasi password tidak sama.";
    } else {
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row || !password_verify($oldPass, $row['password'])) {
            $err = "Password lama salah.";
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);

            $stmt->close();
            $stmt = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $userId);

            if ($stmt->execute()) {
                $msg = "Password berhasil diubah.";
            } else {
                $err = "Gagal mengubah password.";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ubah Password - Nakes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      min-height: 100vh;
      background: radial-gradient(circle at top left, #eef7ff 0, #f8fffb 40%, #ffffff 100%);
      display: flex;
      flex-direction: column;
    }

    .navbar-nakes {
      background: linear-gradient(120deg, #0b5ed7, #0f9d58);
    }
    .navbar-nakes .nav-link, .navbar-nakes .navbar-brand {
      color: #fff !important;
    }

    .card-soft {
      border: none;
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 18px 40px rgba(0,0,0,0.12);
      background: #ffffffcc;
      backdrop-filter: blur(8px);
    }

    .form-control {
      border-radius: .55rem;
      padding: .58rem .75rem;
    }

    .page-wrapper {
      flex: 1;
      padding-top: 40px;
    }

    footer {
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #ddf7ff;
      text-align: center;
      font-size: .85rem;
      padding: 12px 0;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-nakes shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/nakes/dashboard.php">
      <i class="bi bi-hospital me-2"></i> GiziBalita | Nakes
    </a>
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container px-4 px-md-5">

    <div class="row justify-content-center">
      <div class="col-lg-5">

        <div class="card card-soft">

          <h4 class="fw-bold mb-3 text-center">
            <i class="bi bi-shield-lock-fill me-2"></i> Ubah Password
          </h4>

          <?php if ($msg): ?>
            <div class="alert alert-success d-flex align-items-center">
              <i class="bi bi-check-circle-fill me-2"></i> <?= $msg ?>
            </div>
          <?php endif; ?>

          <?php if ($err): ?>
            <div class="alert alert-danger d-flex align-items-center">
              <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $err ?>
            </div>
          <?php endif; ?>

          <form method="post">

            <div class="mb-3">
              <label class="form-label fw-semibold">Password Lama</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" name="old_password" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Password Baru</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                <input type="password" class="form-control" name="new_password" required>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input type="password" class="form-control" name="confirm" required>
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="profile.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
              <button class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i> Simpan Password
              </button>
            </div>

          </form>

        </div>

      </div>
    </div>

  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> GiziBalita — Sistem Monitoring Gizi Balita
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
