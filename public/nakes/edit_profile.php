<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

$userId = $_SESSION['user_id'];

// Default nilai form
$nama  = $_SESSION['name'] ?? '';
$email = $_SESSION['email'] ?? '';
$msg   = '';
$err   = '';

// Jika submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nama === '' || $email === '') {
        $err = "Nama dan email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Format email tidak valid.";
    } else {
        // cek email dipakai nakes lain atau tidak
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $err = "Email sudah digunakan pengguna lain.";
        } else {
            // update email + nama
            $stmt->close();
            $stmt = $mysqli->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->bind_param("ssi", $nama, $email, $userId);

            if ($stmt->execute()) {
                $msg = "Profil berhasil diperbarui.";

                // update session
                $_SESSION['name']  = $nama;
                $_SESSION['email'] = $email;
            } else {
                $err = "Gagal menyimpan perubahan.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Profil - Nakes</title>

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    body {
      min-height: 100vh;
      background: radial-gradient(circle at top left, #eef7ff 0, #f8fffb 40%, #ffffff 100%);
      display: flex;
      flex-direction: column;
    }

    .page-wrapper {
      flex: 1;
      padding-top: 40px;
    }

    .card-soft {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 18px 40px rgba(0,0,0,0.12);
      background: #ffffffcc;
      backdrop-filter: blur(8px);
      padding: 2rem;
    }

    .form-control {
      border-radius: .55rem;
      padding: .58rem .75rem;
    }

    footer {
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #ddf7ff;
      text-align: center;
      font-size: .85rem;
      padding: 10px 0;
      margin-top: 30px;
    }
  </style>
</head>
<body>

<div class="page-wrapper">
  <div class="container px-4 px-md-5">
    <div class="row justify-content-center">
      <div class="col-lg-5">

        <div class="card card-soft">

          <h4 class="fw-bold mb-1 text-center">
            <i class="bi bi-person-lines-fill me-2"></i> Edit Profil
          </h4>
          <p class="text-muted small text-center mb-4">
            Perbarui nama dan email yang digunakan untuk akun tenaga kesehatan Anda.
          </p>

          <?php if ($msg): ?>
            <div class="alert alert-success d-flex align-items-center py-2">
              <i class="bi bi-check-circle-fill me-2"></i>
              <span><?= htmlspecialchars($msg) ?></span>
            </div>
          <?php endif; ?>

          <?php if ($err): ?>
            <div class="alert alert-danger d-flex align-items-center py-2">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span><?= htmlspecialchars($err) ?></span>
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label fw-semibold">Nama Lengkap</label>
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-person"></i>
                </span>
                <input
                  type="text"
                  class="form-control"
                  name="name"
                  value="<?= htmlspecialchars($nama) ?>"
                  required
                >
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Email</label>
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-envelope"></i>
                </span>
                <input
                  type="email"
                  class="form-control"
                  name="email"
                  value="<?= htmlspecialchars($email) ?>"
                  required
                >
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="profile.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
              <button class="btn btn-success">
                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
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
