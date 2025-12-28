<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_ortu();

$userId = $_SESSION['user_id'];

// Ambil data user dari DB
$stmt = $mysqli->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userName, $userEmail);
$stmt->fetch();
$stmt->close();

// Jika tidak ada data (harusnya tidak mungkin)
$userName = $userName ?? 'Orang Tua';
$userEmail = $userEmail ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Akun</title>
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

    .navbar-custom {
      background: linear-gradient(120deg, var(--brand-main), #34c785);
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link {
      color: #fff !important;
    }

    .page-wrapper {
      flex: 1 0 auto;
      padding: 32px 0 40px;
    }

    .card-soft {
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 16px 35px rgba(0,0,0,0.06);
    }

    footer {
      flex-shrink: 0;
      background: linear-gradient(120deg, #0b4125, #0b7542);
      color: #e2f6ea;
      font-size: .85rem;
    }
  </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/ortu/dashboard.php">
      <i class="bi bi-heart-pulse-fill me-2"></i> GiziBalita
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOrtu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarOrtu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/dashboard.php">Beranda</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/pemeriksaan_riwayat.php">Riwayat Gizi</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php">Grafik Perkembangan</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/artikel_list.php">Artikel</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle active" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-fill"></i> <?= htmlspecialchars($userName) ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#">Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">

    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">

        <div class="card card-soft">
          <div class="card-body">

            <div class="d-flex align-items-center mb-3">
              <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:52px;height:52px;">
                <i class="bi bi-person-fill text-success fs-4"></i>
              </div>
              <div>
                <h5 class="mb-0">Profil Akun</h5>
                <small class="text-muted">Informasi akun Anda</small>
              </div>
            </div>

            <hr>

            <form>

              <div class="mb-3">
                <label class="form-label small text-muted">Nama Lengkap</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($userName) ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label small text-muted">Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($userEmail) ?>" readonly>
              </div>

              <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Ingin mengubah nama atau email? Silakan hubungi petugas kesehatan / admin.
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between">
      <div>&copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Semua hak dilindungi.</div>
      <div>Pantau pertumbuhan si kecil setiap bulan ❤️</div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
