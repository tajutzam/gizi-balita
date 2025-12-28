<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

/**
 * Ambil data nakes dari database berdasarkan user_id di session
 */
$userId = $_SESSION['user_id'] ?? null;

// nilai default kalau ada yang kosong
$nama      = $_SESSION['name'] ?? 'Tenaga Kesehatan';
$email     = 'email@example.com';
$role      = 'Tenaga Kesehatan';
$tglDaftar = '-';

// Fungsi kecil untuk format tanggal (jika ingin tampilan Indonesia)
function formatTanggalIndo($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00' || $tanggal === '0000-00-00 00:00:00') {
        return '-';
    }

    $ts = strtotime($tanggal);
    if (!$ts) return $tanggal;

    $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);

    return $d . ' ' . ($bulan[$m] ?? $m) . ' ' . $y;
}

if ($userId) {
    $stmt = $mysqli->prepare("SELECT name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $nama      = $row['name']   ?? $nama;
            $email     = $row['email']  ?? $email;
            $role      = $row['role']   ?? $role;
            $tglDaftar = isset($row['created_at']) ? formatTanggalIndo($row['created_at']) : '-';

            // sinkronkan nama ke session (optional)
            $_SESSION['name']  = $nama;
            $_SESSION['email'] = $email;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Tenaga Kesehatan - Gizi Balita (Nakes)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: radial-gradient(circle at top left, #eef7ff 0, #f8fffb 35%, #ffffff 100%);
    }

    .navbar-nakes {
      background: linear-gradient(120deg, #0b5ed7, #0f9d58);
    }
    .navbar-nakes .nav-link,
    .navbar-nakes .navbar-brand {
      color: #fff !important;
    }

    /* Dropdown mengikuti warna navbar */
    .navbar-nakes .dropdown-menu {
      background: linear-gradient(120deg, #0b5ed7cc, #0f9d58cc) !important;
      border-radius: .75rem;
      border: none;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      backdrop-filter: blur(6px);
    }
    .navbar-nakes .dropdown-item {
      color: #fff !important;
    }
    .navbar-nakes .dropdown-item:hover {
      background: rgba(255,255,255,0.15) !important;
    }

    .page-wrapper {
      flex: 1;
      padding: 32px 0 40px;
    }

    .card-soft {
      border: none;
      border-radius: 1.3rem;
      box-shadow: 0 15px 40px rgba(0,0,0,0.1);
      background: #fff;
    }

    .profile-icon {
      width: 85px;
      height: 85px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bs-primary);
      color: #fff;
      font-size: 38px;
      box-shadow: 0 8px 20px rgba(13,110,253,.35);
    }

    footer {
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #e7f7ff;
      font-size: .85rem;
    }
  </style>
</head>
<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-nakes shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= BASE_URL ?>/nakes/dashboard.php">
      <i class="bi bi-hospital me-2"></i> GiziBalita | Nakes
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNakes">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNakes">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/nakes/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/nakes/balita_list.php">Data Balita</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php">Input Pemeriksaan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php">Riwayat Pemeriksaan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/nakes/artikel_manage.php">Artikel Edukasi</a></li>

        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-white bg-opacity-50 me-2" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center;">
              <i class="bi bi-person-fill"></i>
            </div>
            <?= htmlspecialchars($nama); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="<?= BASE_URL ?>/nakes/profile.php">
                <i class="bi bi-person-circle me-2"></i> Profil
              </a>
            </li>
            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.6);"></li>
            <li>
              <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>
<!-- ============== END NAVBAR ============== -->


<!-- =============== MAIN CONTENT =============== -->
<div class="page-wrapper">
  <div class="container px-4 px-md-5">

    <div class="row justify-content-center">
      <div class="col-lg-7 col-xl-6">

        <div class="card card-soft p-4">
          <div class="text-center mb-4">
            <div class="profile-icon mx-auto mb-3">
              <i class="bi bi-person-badge-fill"></i>
            </div>
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($nama); ?></h4>
            <p class="text-muted small mb-0"><?= htmlspecialchars($role); ?></p>
          </div>

          <hr>

          <div class="mb-3">
            <label class="text-muted small">Nama Lengkap</label>
            <div class="fw-semibold"><?= htmlspecialchars($nama); ?></div>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Email</label>
            <div class="fw-semibold"><?= htmlspecialchars($email); ?></div>
          </div>

          <div class="mb-3">
            <label class="text-muted small">Role</label>
            <div class="fw-semibold"><?= htmlspecialchars($role); ?></div>
          </div>

          <div class="mb-4">
            <label class="text-muted small">Tanggal Bergabung</label>
            <div class="fw-semibold"><?= htmlspecialchars($tglDaftar); ?></div>
          </div>

          <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a href="<?= BASE_URL ?>/nakes/edit_profile.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Profil
            </a>
            <a href="<?= BASE_URL ?>/nakes/ubah_password.php" class="btn btn-primary btn-sm">
                <i class="bi bi-shield-lock me-1"></i> Ubah Password
            </a>
        </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- =============== END MAIN CONTENT =============== -->


<!-- ================= FOOTER ================= -->
<footer class="py-3 mt-4">
  <div class="container px-4 px-md-5 text-center text-md-start">
    <div class="d-md-flex justify-content-between">
      <div>&copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.</div>
      <div>Akses profil ini hanya untuk tenaga kesehatan terdaftar.</div>
    </div>
  </div>
</footer>
<!-- =============== END FOOTER =============== -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
