<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

/**
 * Halaman DETAIL BALITA (NAKES)
 * Route: nakes/balita_detail.php?id=ID_BALITA
 */

$balita_id  = (int)($_GET['id'] ?? 0);
$err        = "";
$balita     = null;
$umur_bulan = "-";
$riwayat    = [];

/* ==============================
   1. Validasi & Ambil data balita
   ============================== */
if ($balita_id <= 0) {
    $err = "ID balita tidak valid atau tidak dikirim.";
} else {
    try {
        $sql = "
            SELECT 
                b.id,
                b.nama_balita,
                b.tanggal_lahir,
                b.jenis_kelamin,
                b.user_ortu_id,
                u.name  AS nama_ortu,
                u.email AS email_ortu
            FROM balitas b
            LEFT JOIN users u ON u.id = b.user_ortu_id
            WHERE b.id = ?
            LIMIT 1
        ";

        $stmt = $mysqli->prepare($sql);
        if ($stmt === false) {
            $err = "Gagal menyiapkan query balita: " . $mysqli->error;
        } else {
            $stmt->bind_param("i", $balita_id);
            $stmt->execute();
            $balita = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$balita) {
                $err = "Data balita tidak ditemukan.";
            }
        }
    } catch (Throwable $e) {
        $err = "Terjadi kesalahan saat mengambil data balita: " . $e->getMessage();
    }
}

/* ==============================
   2. Ambil RIWAYAT PEMERIKSAAN
   ============================== */
if ($balita) {
    try {
        $sql = "
            SELECT 
                p.id,
                DATE(p.tanggal_pemeriksaan) AS tanggal,
                p.umur_bulan,
                p.berat_badan,
                p.tinggi_badan,
                p.lingkar_lengan,
                p.status_gizi
            FROM pemeriksaans p
            WHERE p.balita_id = ?
            ORDER BY p.tanggal_pemeriksaan ASC, p.id ASC
        ";

        $stmt = $mysqli->prepare($sql);
        if ($stmt === false) {
            $err = "Gagal menyiapkan query riwayat: " . $mysqli->error;
        } else {
            $stmt->bind_param("i", $balita_id);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                $riwayat[] = [
                    'id'             => (int)$row['id'],
                    'tanggal'        => $row['tanggal'],
                    'umur_bulan'     => (int)$row['umur_bulan'],
                    'berat_badan'    => (float)$row['berat_badan'],
                    'tinggi_badan'   => (float)$row['tinggi_badan'],
                    'lingkar_lengan' => $row['lingkar_lengan'] !== null ? (float)$row['lingkar_lengan'] : null,
                    'status_gizi'    => $row['status_gizi'],
                ];
            }

            $stmt->close();
        }
    } catch (Throwable $e) {
        $err = "Gagal memuat riwayat pemeriksaan: " . $e->getMessage();
    }
}

/* =========================
   3. Tentukan UMUR (mengikuti umur di pemeriksaan_list)
   ========================= */
/**
 * Logika:
 * - Jika ada riwayat pemeriksaan:
 *     umur_bulan = umur_bulan dari PEMERIKSAAN TERAKHIR
 *   (supaya sama persis dengan yang tampil di pemeriksaan_list.php)
 * - Jika belum ada riwayat:
 *     baru fallback hitung dari tanggal_lahir (kalau tersedia)
 */

$umur_bulan = "-";

if (!empty($riwayat)) {
    // Pemeriksaan di-order ASC, jadi elemen terakhir = pemeriksaan terbaru
    $last = end($riwayat);
    $umur_bulan = (int)$last['umur_bulan'];
} elseif ($balita && !empty($balita['tanggal_lahir']) && $balita['tanggal_lahir'] !== '0000-00-00') {
    // Fallback: hitung dari tanggal lahir kalau belum pernah diperiksa
    try {
        $lahir = new DateTime(trim($balita['tanggal_lahir']));
        $now   = new DateTime();

        if ($lahir <= $now) {
            $diff       = $now->diff($lahir);
            $umur_bulan = ($diff->y * 12) + $diff->m;
        } else {
            $umur_bulan = 0;
        }
    } catch (Throwable $e) {
        $umur_bulan = "-";
    }
}

/* =========================
   4. Helper format tanggal
   ========================= */
function format_tanggal_id(?string $tanggal): string {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    try {
        $dt = new DateTime($tanggal);
        return $dt->format('d-m-Y');
    } catch (Throwable $e) {
        return $tanggal;
    }
}

/* =========================
   5. Badge warna status gizi
   ========================= */
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

/* =========================
   6. Siapkan data untuk Chart
   ========================= */
$chart_labels   = [];
$chart_berat    = [];
$chart_tinggi   = [];
$chart_lila     = [];

foreach ($riwayat as $rw) {
    $chart_labels[] = $rw['tanggal'];              // label di sumbu X
    $chart_berat[]  = $rw['berat_badan'];          // kg
    $chart_tinggi[] = $rw['tinggi_badan'];         // cm
    $chart_lila[]   = $rw['lingkar_lengan'] ?? 0;  // cm, default 0 kalau null
}

// encode ke JSON untuk dipakai di JavaScript
$chart_labels_json = json_encode($chart_labels);
$chart_berat_json  = json_encode($chart_berat);
$chart_tinggi_json = json_encode($chart_tinggi);
$chart_lila_json   = json_encode($chart_lila);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Balita - Nakes</title>
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
      background: radial-gradient(circle at top left, #eef7ff 0, #f8fffb 35%, #ffffff 100%);
    }

    /* NAVBAR NAKES (SAMA NUANSANYA DENGAN pemeriksaan_list.php) */
    .navbar-nakes {
      background: linear-gradient(120deg, #0b5ed7, #0f9d58);
    }
    .navbar-nakes .navbar-brand,
    .navbar-nakes .nav-link {
      color: #fdfdfd !important;
    }
    .navbar-nakes .nav-link {
      opacity: .9;
    }
    .navbar-nakes .nav-link:hover {
      opacity: 1;
    }
    .navbar-nakes .nav-link.active {
      font-weight: 600;
      border-bottom: 2px solid #ffffffcc;
    }

    .navbar-nakes .dropdown-menu {
      background: linear-gradient(120deg, #0b5ed7cc, #0f9d58cc) !important;
      border-radius: 0.75rem;
      border: none;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      backdrop-filter: blur(6px);
      padding-top: .4rem;
      padding-bottom: .4rem;
    }

    .navbar-nakes .dropdown-item {
      color: #ffffff !important;
      font-size: 0.9rem;
    }

    .navbar-nakes .dropdown-item:hover {
      background-color: rgba(255,255,255,0.16) !important;
      color: #ffffff !important;
    }

    .navbar-nakes .dropdown-item.text-danger {
      color: #ffb3b8 !important;
    }
    .navbar-nakes .dropdown-item.text-danger:hover {
      background-color: rgba(255, 99, 132, 0.2) !important;
      color: #ffe6e8 !important;
    }

    /* MAIN CONTENT */
    .page-wrapper {
      flex: 1 0 auto;
      padding: 32px 0 40px;
    }
    .card-soft {
      border: none;
      border-radius: 1.1rem;
      box-shadow: 0 14px 32px rgba(0,0,0,0.08);
      background: #ffffff;
    }
    .badge-status {
      font-size: .85rem;
      padding: .35rem .8rem;
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
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #e2f6fa;
      font-size: .85rem;
    }
    footer a {
      color: #b8f3d1;
      text-decoration: none;
    }
    footer a:hover {
      text-decoration: underline;
    }

    #growthChart {
      max-height: 320px;
    }
  </style>
</head>
<body>

<!-- NAVBAR NAKES -->
<nav class="navbar navbar-expand-lg navbar-nakes shadow-sm">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= BASE_URL ?>/nakes/dashboard.php">
      <i class="bi bi-hospital me-2"></i>
      <span>GiziBalita | Nakes</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNakes">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNakes">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/dashboard.php">Dashboard</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/nakes/balita_list.php">Data Balita</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php">Input Pemeriksaan</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php">Riwayat Pemeriksaan</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/artikel_manage.php">Artikel Edukasi</a>
        </li>

        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;">
              <i class="bi bi-person-badge-fill"></i>
            </div>
            <span class="d-none d-sm-inline">
              <?= htmlspecialchars($_SESSION['name'] ?? 'Tenaga Kesehatan'); ?>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li>
              <a class="dropdown-item" href="<?= BASE_URL ?>/nakes/profile.php">
                <i class="bi bi-person-circle me-2"></i> Profil
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
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

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">
    <div class="row g-4">

      <!-- Header + tombol kembali -->
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Detail Data Balita</h5>
          <a href="<?= BASE_URL ?>/nakes/balita_list.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Balita
          </a>
        </div>
        <p class="text-muted mb-3">
          Informasi detail balita beserta riwayat pemeriksaan dan grafik perkembangan gizi.
        </p>
      </div>

      <!-- PANEL PROFIL BALITA -->
      <div class="col-12 col-lg-5">
        <div class="card card-soft mb-4 mb-lg-0">
          <div class="card-body">

            <?php if ($err): ?>
              <div class="alert alert-danger small mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($err); ?>
              </div>
            <?php endif; ?>

            <?php if ($balita): ?>
              <h4 class="mb-3"><?= htmlspecialchars($balita['nama_balita']); ?></h4>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Nama Balita</div>
                <div class="col-sm-8"><?= htmlspecialchars($balita['nama_balita']); ?></div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Tanggal Lahir</div>
                <div class="col-sm-8">
                  <?= htmlspecialchars(format_tanggal_id($balita['tanggal_lahir'] ?? null)); ?>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Umur</div>
                <div class="col-sm-8">
                  <?= is_numeric($umur_bulan) ? $umur_bulan . ' Tahun' : $umur_bulan; ?>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Jenis Kelamin</div>
                <div class="col-sm-8">
                  <?= ($balita['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                </div>
              </div>

              <hr class="my-3">

              <h6 class="text-muted text-uppercase mb-3">Data Orang Tua</h6>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Nama Orang Tua</div>
                <div class="col-sm-8">
                  <?= htmlspecialchars($balita['nama_ortu'] ?? '-'); ?>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-sm-4 text-muted">Email</div>
                <div class="col-sm-8">
                  <?= htmlspecialchars($balita['email_ortu'] ?? '-'); ?>
                </div>
              </div>

              <p class="small text-muted mt-3 mb-2">
                Umur balita mengikuti <strong>umur pada pemeriksaan terakhir</strong>, agar konsisten
                dengan tampilan di menu <em>Riwayat Pemeriksaan</em>.
              </p>

              <?php if (!empty($riwayat)): ?>
              <?php endif; ?>

            <?php elseif (!$err): ?>
              <div class="text-center py-5">
                <i class="bi bi-exclamation-circle fs-1 text-muted mb-3"></i>
                <h6 class="mb-1">Data balita tidak ditemukan</h6>
                <p class="text-muted small mb-3">
                  Kembali ke daftar balita dan pilih balita yang tersedia.
                </p>
                <a href="<?= BASE_URL ?>/nakes/balita_list.php" class="btn btn-primary btn-sm">
                  <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Balita
                </a>
              </div>
            <?php endif; ?>
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
      <div>&copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.</div>
      <div class="text-md-end">
        Panel detail balita untuk tenaga kesehatan dalam pemantauan gizi.
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($riwayat)): ?>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            ticks: { maxRotation: 45, minRotation: 0 }
          }
        }
      }
    });
  </script>
<?php endif; ?>

</body>
</html>
