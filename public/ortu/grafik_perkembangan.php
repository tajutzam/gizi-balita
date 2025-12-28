<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_ortu();

/* ==========================================================
   1) Ambil balita milik orang tua
   ========================================================== */
$ortuId = $_SESSION["user_id"] ?? 0;

$balita   = null;
$balitaId = 0;

$stmt = $mysqli->prepare("
    SELECT id, nama_balita, tanggal_lahir, jenis_kelamin
    FROM balitas
    WHERE user_ortu_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $ortuId);
$stmt->execute();
$res = $stmt->get_result();

if ($res && ($row = $res->fetch_assoc())) {
    $balita   = $row;
    $balitaId = (int)$row['id'];
}
$stmt->close();

/* ==========================================================
   2) Ambil grafik perkembangan + status gizi terbaru
   ========================================================== */
$umurBulan   = [];
$beratBadan  = [];
$tinggiBadan = [];

$status_gizi  = "-";
$tgl_terakhir = null;

if ($balitaId > 0) {
    // Status gizi terakhir
    $stmt = $mysqli->prepare("
        SELECT status_gizi, tanggal_pemeriksaan
        FROM pemeriksaans
        WHERE balita_id = ?
        ORDER BY tanggal_pemeriksaan DESC, id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $balitaId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && ($row = $res->fetch_assoc())) {
        $status_gizi  = $row['status_gizi'];
        $tgl_terakhir = $row['tanggal_pemeriksaan'];
    }
    $stmt->close();

    // Data grafik (urut dari yang paling awal)
    $stmt = $mysqli->prepare("
        SELECT umur_bulan, berat_badan, tinggi_badan
        FROM pemeriksaans
        WHERE balita_id = ?
        ORDER BY tanggal_pemeriksaan ASC, id ASC
    ");
    $stmt->bind_param("i", $balitaId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $umurBulan[]   = (int)$row['umur_bulan'];
        $beratBadan[]  = (float)$row['berat_badan'];
        $tinggiBadan[] = (float)$row['tinggi_badan'];
    }
    $stmt->close();
}

/* Apakah data asli ada? */
$hasRealData = !empty($umurBulan);

/* ==========================================================
   3) Dummy jika belum ada data
   ========================================================== */
if (empty($umurBulan)) {
    $umurBulan   = [12, 14, 16, 18, 20, 22, 24];
    $beratBadan  = [8.5, 8.9, 9.3, 9.8, 10.2, 10.8, 11.5];
    $tinggiBadan = [72, 74, 76, 78, 80, 83, 85];
}

/* Badge warna status gizi (optional) */
function badgeClass($status) {
    return match ($status) {
        'Gizi Baik'         => 'bg-success',
        'Gizi Kurang',
        'Gizi Buruk'        => 'bg-danger',
        'Risiko Gizi Lebih' => 'bg-warning text-dark',
        'Gizi Lebih',
        'Obesitas'          => 'bg-orange text-dark',
        default             => 'bg-secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Grafik Perkembangan</title>
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
    .card-article {
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 16px 35px rgba(0,0,0,0.06);
      background: #ffffff;
    }
    .badge-kategori {
      background-color: var(--brand-soft);
      color: var(--brand-dark);
      border-radius: 999px;
      padding: .25rem .75rem;
      font-size: .75rem;
      font-weight: 500;
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
      background-color: #ffb347 !important;
      color: #212529 !important;
    }

    .chart-wrapper {
      position: relative;
      width: 100%;
      height: 320px;
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


<!-- =============== NAVBAR =============== -->
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
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/dashboard.php">
            Beranda
          </a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/pemeriksaan_riwayat.php">
            Riwayat Gizi
          </a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php">
            Grafik Perkembangan
          </a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/artikel_list.php">
            Artikel
          </a>
        </li>

        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;">
              <i class="bi bi-person-fill"></i>
            </div>
            <span class="d-none d-sm-inline">
              <?= htmlspecialchars($_SESSION['name'] ?? 'Orang Tua'); ?>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li>
              <a class="dropdown-item" href="<?= BASE_URL ?>/ortu/profile.php">
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
<!-- ============ END NAVBAR ============ -->


<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
      <div>
        <h4 class="mb-1">Grafik Perkembangan Balita</h4>
        <p class="text-muted mb-0">
          Pantau perubahan berat dan tinggi badan seiring pertambahan umur (dalam bulan).
        </p>
      </div>
      <?php if ($balita): ?>
        <div class="text-md-end mt-2 mt-md-0">
          <span class="chip">
            <i class="bi bi-person-badge me-1"></i>
            <?= htmlspecialchars($balita['nama_balita']) ?>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-3 mb-3">
      <span class="badge badge-status <?= badgeClass($status_gizi) ?>">
        Status Gizi Terakhir: <?= htmlspecialchars($status_gizi) ?>
      </span>
      <?php if ($tgl_terakhir): ?>
        <span class="text-muted small ms-2">
          (Pemeriksaan: <?= date('d-m-Y', strtotime($tgl_terakhir)); ?>)
        </span>
      <?php endif; ?>

      <?php if (!$hasRealData): ?>
        <div class="mt-2">
          <span class="chip">
            <i class="bi bi-info-circle me-1"></i>
            Grafik di bawah masih menggunakan data contoh. Data asli akan muncul setelah pemeriksaan pertama dilakukan.
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="row g-4 mt-1">

      <div class="col-md-6">
        <div class="card card-article h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="text-uppercase text-muted mb-0">Berat Badan / Umur</h6>
              <span class="badge-kategori">
                <i class="bi bi-activity me-1"></i> Kg per bulan
              </span>
            </div>
            <div class="chart-wrapper mt-2">
              <canvas id="chartBBU"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card card-article h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="text-uppercase text-muted mb-0">Tinggi Badan / Umur</h6>
              <span class="badge-kategori">
                <i class="bi bi-graph-up-arrow me-1"></i> Cm per bulan
              </span>
            </div>
            <div class="chart-wrapper mt-2">
              <canvas id="chartTBU"></canvas>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ================== FOOTER FULL ================= -->
<footer>
  <div class="container py-3 text-center">
    © <?= date("Y") ?> GiziBalita — Sistem Monitoring Gizi Balita
  </div>
</footer>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const umurLabels = <?= json_encode($umurBulan) ?>;
const dataBB     = <?= json_encode($beratBadan) ?>;
const dataTB     = <?= json_encode($tinggiBadan) ?>;

// Helper: buat chart dengan gradient
function createGradient(ctx, colorTop, colorBottom) {
  const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
  gradient.addColorStop(0, colorTop);
  gradient.addColorStop(1, colorBottom);
  return gradient;
}

window.addEventListener('load', () => {
  const ctxBB = document.getElementById('chartBBU').getContext('2d');
  const ctxTB = document.getElementById('chartTBU').getContext('2d');

  const gradientBB = createGradient(
    ctxBB,
    'rgba(15, 157, 88, 0.35)',
    'rgba(15, 157, 88, 0)'
  );
  const gradientTB = createGradient(
    ctxTB,
    'rgba(11, 94, 215, 0.35)',
    'rgba(11, 94, 215, 0)'
  );

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: {
          usePointStyle: true,
          padding: 16
        }
      },
      tooltip: {
        mode: 'index',
        intersect: false,
        callbacks: {
          label: function(context) {
            const label = context.dataset.label || '';
            const value = context.parsed.y;
            return label + ': ' + value;
          }
        }
      }
    },
    interaction: {
      mode: 'index',
      intersect: false
    },
    scales: {
      x: {
        title: {
          display: true,
          text: 'Umur (bulan)'
        },
        grid: {
          display: false
        }
      }
    }
  };

  new Chart(ctxBB, {
    type: 'line',
    data: {
      labels: umurLabels,
      datasets: [{
        label: 'Berat (kg)',
        data: dataBB,
        borderColor: '#0f9d58',
        backgroundColor: gradientBB,
        borderWidth: 2,
        tension: 0.35,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#0f9d58',
        pointBorderWidth: 1
      }]
    },
    options: {
      ...commonOptions,
      scales: {
        ...commonOptions.scales,
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Berat Badan (kg)'
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        }
      }
    }
  });

  new Chart(ctxTB, {
    type: 'line',
    data: {
      labels: umurLabels,
      datasets: [{
        label: 'Tinggi (cm)',
        data: dataTB,
        borderColor: '#0b5ed7',
        backgroundColor: gradientTB,
        borderWidth: 2,
        tension: 0.35,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#0b5ed7',
        pointBorderWidth: 1
      }]
    },
    options: {
      ...commonOptions,
      scales: {
        ...commonOptions.scales,
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Tinggi Badan (cm)'
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        }
      }
    }
  });
});
</script>

</body>
</html>
