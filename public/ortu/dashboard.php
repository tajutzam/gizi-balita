<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_ortu();

/**
 * Pastikan di proses login ORTU kamu set minimal:
 *   $_SESSION['user_id'] = <id user>;
 *   $_SESSION['name']    = <nama ortu>;
 *
 * Di sini kita ambil dari user_id, kalau tidak ada fallback ke id.
 */
$ortu_id = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

/* =====================
   1. Ambil Data Balita
   ===================== */
$balita = null;

if ($ortu_id > 0) {
    try {
        $sql = "SELECT id, nama_balita, tanggal_lahir, jenis_kelamin 
                FROM balitas 
                WHERE user_ortu_id = ?
                LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $ortu_id);
            $stmt->execute();
            $balita = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } catch (Throwable $e) {
        // optional logging
        // error_log('Error ambil balita: '.$e->getMessage());
    }
}

/* =======================================================
   1.b Ambil UMUR dari RIWAYAT GIZI (pemeriksaans.umur_bulan)
       - diambil pemeriksaan TERBARU
   ======================================================= */
$umur_bulan = "-"; // default

if ($balita) {
    try {
        $sql = "
            SELECT umur_bulan
            FROM pemeriksaans
            WHERE balita_id = ?
            ORDER BY tanggal_pemeriksaan DESC, id DESC
            LIMIT 1
        ";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $balita_id = (int)$balita['id'];
            $stmt->bind_param("i", $balita_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // umur dari RIWAYAT GIZI
                $umur_bulan = (int)$row['umur_bulan'];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // kalau error, biarkan nanti jatuh ke perhitungan tanggal lahir
        // error_log('Error ambil umur dari riwayat: '.$e->getMessage());
    }
}

/* =====================================================
   1.c Kalau BELUM ada riwayat, baru hitung dari lahir
        (konsisten dengan balita_detail.php)
   ===================================================== */
if ($umur_bulan === "-" && $balita && !empty($balita['tanggal_lahir']) && $balita['tanggal_lahir'] !== '0000-00-00') {
    try {
        $lahir = new DateTime(trim($balita['tanggal_lahir']));
        $now   = new DateTime();

        if ($lahir <= $now) {
            $diff       = $now->diff($lahir);
            $umur_bulan = ($diff->y * 12) + $diff->m;
            if ($umur_bulan < 0) {
                $umur_bulan = 0;
            }
        } else {
            $umur_bulan = 0;
        }
    } catch (Throwable $e) {
        $umur_bulan = "-";
    }
}

/* =========================================
   2. Ambil Status Gizi Terbaru Pemeriksaan
   ========================================= */
$latest = null;

if ($balita) {
    try {
        $sql = "SELECT 
                    tanggal_pemeriksaan,
                    umur_bulan,
                    berat_badan,
                    tinggi_badan,
                    lingkar_lengan,
                    status_gizi
                FROM pemeriksaans
                WHERE balita_id = ?
                ORDER BY tanggal_pemeriksaan DESC, id DESC
                LIMIT 1";

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $balita_id = (int)$balita['id'];
            $stmt->bind_param("i", $balita_id);
            $stmt->execute();
            $latest = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } catch (Throwable $e) {
        // optional logging
        // error_log('Error ambil pemeriksaan: '.$e->getMessage());
    }
}

/* Badge warna */
function badgeClass($status) {
    return match ($status) {
        'Gizi Baik'         => 'bg-success',
        'Gizi Kurang',
        'Gizi Buruk'        => 'bg-danger',
        'Risiko Gizi Lebih' => 'bg-warning text-dark',
        'Gizi Lebih',
        'Obesitas'          => 'bg-orange',
        default             => 'bg-secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Orang Tua</title>
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

    /* NAVBAR */
    .navbar-custom {
      background: linear-gradient(120deg, var(--brand-main), #34c785);
    }
    .navbar-custom .navbar-brand,
    .navbar-custom .nav-link,
    .navbar-custom .dropdown-item {
      color: #fdfdfd !important;
    }
    .navbar-custom .nav-link {
      opacity: .85;
    }
    .navbar-custom .nav-link:hover {
      opacity: 1;
    }
    .navbar-custom .nav-link.active {
      font-weight: 600;
      border-bottom: 2px solid #ffffffcc;
    }
    .navbar-custom .dropdown-menu {
      background: #ffffff;
      border-radius: .75rem;
      border: none;
      box-shadow: 0 12px 30px rgba(0,0,0,0.1);
      padding-top: .5rem;
      padding-bottom: .5rem;
    }
    .navbar-custom .dropdown-item {
      color: #444 !important;
    }
    .navbar-custom .dropdown-item.text-danger {
      color: #dc3545 !important;
    }

    /* MAIN CONTENT */
    .page-wrapper {
      flex: 1 0 auto;
      padding: 32px 0 40px;
    }
    .card-soft {
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 16px 35px rgba(0,0,0,0.06);
      background: #ffffff;
    }
    .badge-status {
      font-size: .95rem;
      padding: .4rem .85rem;
      border-radius: 999px;
    }
    .chip {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: .35rem .75rem;
      background-color: #f3faf6;
      font-size: .8rem;
      color: #3d6653;
      border: 1px solid #e0f2ea;
    }
    .bg-orange {
      background-color: #ffb347;
      color: #212529;
    }

    /* FOOTER */
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
    footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= BASE_URL ?>/ortu/dashboard.php">
      <i class="bi bi-heart-pulse-fill me-2"></i>
      <span>GiziBalita</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOrtu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarOrtu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/ortu/dashboard.php">Beranda</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/pemeriksaan_riwayat.php">Riwayat Gizi</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php">Grafik Perkembangan</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/artikel_list.php">Artikel</a>
        </li>

        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;">
              <i class="bi bi-person-fill"></i>
            </div>
            <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['name'] ?? 'Orang Tua'); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/ortu/profile.php"><i class="bi bi-person-circle me-2"></i> Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">
    <div class="row g-4">

      <!-- Sapaan -->
      <div class="col-12">
        <div class="card card-soft">
          <div class="card-body d-md-flex align-items-center justify-content-between">
            <div>
              <h5 class="mb-1">Halo, <?= htmlspecialchars($_SESSION['name'] ?? '') ?></h5>
              <p class="text-muted mb-2">
                Pantau status gizi dan perkembangan balita Anda langsung dari dashboard ini.
              </p>
              <span class="chip">
                <i class="bi bi-info-circle me-1"></i>
                Tips: lakukan pemeriksaan rutin minimal sebulan sekali.
              </span>
            </div>
            <div class="mt-3 mt-md-0 text-md-end">
              <a href="<?= BASE_URL ?>/ortu/artikel_list.php" class="btn btn-light btn-sm border">
                <i class="bi bi-journal-text me-1"></i> Baca Artikel Gizi
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Profil Balita -->
      <div class="col-md-6 col-xl-5">
        <div class="card card-soft h-100">
          <div class="card-body">
            <h6 class="text-uppercase text-muted mb-3">Profil Balita</h6>

            <?php if ($balita): ?>
              <h4 class="mb-1"><?= htmlspecialchars($balita['nama_balita']) ?></h4>
              <p class="text-muted mb-1">
                Umur: <?= is_numeric($umur_bulan) ? $umur_bulan . ' Tahun' : $umur_bulan; ?>
              </p>
              <p class="text-muted small mb-3">
                Jenis Kelamin: <?= $balita['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
              </p>
            <?php else: ?>
              <h4 class="mb-1">Belum Ada Data</h4>
              <p class="text-muted small">
                Data balita akan muncul setelah diinput oleh tenaga kesehatan.
              </p>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/ortu/balita_detail.php" class="btn btn-outline-success btn-sm">
              <i class="bi bi-person-badge me-1"></i> Lihat Detail Balita
            </a>
          </div>
        </div>
      </div>

      <!-- Status Gizi Terbaru -->
      <div class="col-md-6 col-xl-7">
        <div class="card card-soft h-100">
          <div class="card-body">
            <h6 class="text-uppercase text-muted mb-3">Status Gizi Terbaru</h6>

            <?php if ($latest): ?>
              <span class="badge badge-status <?= badgeClass($latest['status_gizi']) ?> mb-2">
                <?= htmlspecialchars($latest['status_gizi']) ?>
              </span>

              <p class="text-muted mb-1">
                Pemeriksaan terakhir:
                <strong><?= htmlspecialchars($latest['tanggal_pemeriksaan']) ?></strong>
              </p>
              <p class="small text-muted mb-3">
                Berat: <?= $latest['berat_badan'] ?> kg • Tinggi: <?= $latest['tinggi_badan'] ?> cm
              </p>

            <?php else: ?>
              <span class="badge badge-status bg-secondary mb-2">Belum Ada Data</span>
              <p class="text-muted small mb-2">
                Status gizi akan muncul setelah pemeriksaan pertama dilakukan.
              </p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
              <a href="<?= BASE_URL ?>/ortu/pemeriksaan_riwayat.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-clock-history me-1"></i> Riwayat Pemeriksaan
              </a>
              <a href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-graph-up-arrow me-1"></i> Grafik Perkembangan
              </a>
            </div>
          </div>
        </div>
      </div>

    </div> <!-- row -->
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div>&copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Semua hak dilindungi.</div>
      <div class="text-md-end">Dibuat untuk membantu orang tua memantau gizi balita.</div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
