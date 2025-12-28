<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_ortu();

/**
 * Catatan:
 * Di proses login ORTU, pastikan kamu set:
 *   $_SESSION['user_id'] = <id user dari tabel users>;
 *   $_SESSION['name']    = <nama orang tua>;
 *
 * Di tabel balitas, kolom relasinya adalah:
 *   balitas.user_ortu_id => mengarah ke users.id (ortu)
 */
$ortu_id = $_SESSION['user_id'] ?? 0; // sudah benar

/* ================== AMBIL RIWAYAT PEMERIKSAAN DARI DB ================== */

$riwayat   = [];
$errorList = "";

if ($ortu_id <= 0) {
    $errorList = "Sesi login orang tua tidak valid. Silakan login ulang.";
} else {
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
            JOIN balitas b ON b.id = p.balita_id
            WHERE b.user_ortu_id = ?
            ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC
        ";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $ortu_id);
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
        } else {
            $errorList = "Gagal menyiapkan query: " . $mysqli->error;
        }
    } catch (Throwable $e) {
        $errorList = "Gagal memuat data pemeriksaan: " . $e->getMessage();
    }
}

/* ================== BADGE STATUS GIZI ================== */

function badgeClass($status) {
    switch ($status) {
        case 'Gizi Baik':
            return 'bg-success';
        case 'Gizi Kurang':
        case 'Gizi Buruk':
            return 'bg-danger';
        case 'Risiko Gizi Lebih':
            return 'bg-warning text-dark';
        case 'Gizi Lebih':
        case 'Obesitas':
            return 'bg-orange';
        default:
            return 'bg-secondary';
    }
}
?>  
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Pemeriksaan</title>
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

    /* MAIN */
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
      font-size: .8rem;
      padding: .25rem .6rem;
      border-radius: 999px;
    }
    .table thead th {
      font-size: .8rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #6c757d;
      border-bottom-width: 1px;
    }
    .table td {
      vertical-align: middle;
      font-size: .88rem;
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
          <a class="nav-link active" href="<?= BASE_URL ?>/ortu/pemeriksaan_riwayat.php">
            Riwayat Gizi
          </a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php">
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


<!-- =============== MAIN CONTENT =============== -->
<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">

    <div class="row mb-3">
      <div class="col-12 d-md-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">Riwayat Pemeriksaan Gizi Balita</h4>
          <p class="text-muted mb-0">
            Daftar pemeriksaan yang pernah dilakukan oleh tenaga kesehatan untuk balita Anda.
          </p>
        </div>

        <?php if (!empty($riwayat)): ?>
        <div class="mt-3 mt-md-0">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="printRiwayat()">
            <i class="bi bi-printer me-1"></i> Cetak PDF
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($errorList): ?>
      <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars($errorList) ?>
      </div>
    <?php endif; ?>

    <div class="card card-soft">
      <div class="card-body">

        <?php if (empty($riwayat) && !$errorList): ?>
          <div class="text-center py-5">
            <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
            <h6 class="mb-1">Belum ada data pemeriksaan</h6>
            <p class="text-muted small mb-0">
              Pemeriksaan pertama akan muncul di sini setelah tenaga kesehatan menginput data balita Anda.
            </p>
          </div>
        <?php else: ?>
          <div id="riwayatTableWrap" class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width: 5%;">#</th>
                  <th>Tanggal</th>
                  <th>Umur (Bulan)</th>
                  <th>Berat (kg)</th>
                  <th>Tinggi (cm)</th>
                  <th>Status Gizi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; ?>
                <?php foreach ($riwayat as $row): ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['tanggal']); ?></td>
                    <td><?= (int)$row['umur_bulan']; ?></td>
                    <td><?= number_format($row['berat_badan'], 1); ?></td>
                    <td><?= number_format($row['tinggi_badan'], 1); ?></td>
                    <td>
                      <span class="badge badge-status <?= badgeClass($row['status_gizi']); ?>">
                        <?= htmlspecialchars($row['status_gizi']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>
<!-- ============ END MAIN CONTENT ============ -->


<!-- =============== FOOTER =============== -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Semua hak dilindungi.
      </div>
      <div class="text-md-end">
        <span class="me-2">Pantau riwayat gizi untuk mendukung tumbuh kembang optimal balita Anda.</span>
      </div>
    </div>
  </div>
</footer>
<!-- ============ END FOOTER ============ -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function printRiwayat() {
  const wrap = document.getElementById('riwayatTableWrap');
  if (!wrap) return;

  const tableHTML = wrap.innerHTML;
  const w = window.open('', '_blank');
  const ortuName = <?= json_encode($_SESSION['name'] ?? ''); ?>;

  w.document.write('<!DOCTYPE html>');
  w.document.write('<html lang="id"><head><meta charset="UTF-8">');
  w.document.write('<title>Cetak Riwayat Gizi Balita</title>');
  w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
  w.document.write('<style>');
  w.document.write('@page { size: A4; margin: 20mm; }');
  w.document.write('body { font-family: system-ui,-apple-system,"Segoe UI",Arial,sans-serif; font-size: 11pt; color:#212529; }');
  w.document.write('h1 { font-size: 18pt; margin-bottom: 4px; }');
  w.document.write('.subtitle { font-size: 10pt; color:#6c757d; margin-bottom: 12px; }');
  w.document.write('.meta { font-size: 10pt; margin-bottom: 4px; }');
  w.document.write('table { width:100%; border-collapse: collapse; margin-top: 12px; }');
  w.document.write('th, td { border:1px solid #dee2e6; padding:6px 8px; font-size:10pt; }');
  w.document.write('th { background-color:#f1f3f5; text-transform:uppercase; font-weight:600; }');
  w.document.write('.badge { border-radius:999px; padding:3px 8px; font-size:9pt; }');
  w.document.write('.bg-success { background-color:#198754 !important; color:#fff !important; }');
  w.document.write('.bg-danger { background-color:#dc3545 !important; color:#fff !important; }');
  w.document.write('.bg-warning { background-color:#ffc107 !important; color:#212529 !important; }');
  w.document.write('.bg-orange { background-color:#ffb347 !important; color:#212529 !important; }');
  w.document.write('.bg-secondary { background-color:#6c757d !important; color:#fff !important; }');
  w.document.write('</style>');
  w.document.write('</head><body>');
  w.document.write('<div>');
  w.document.write('<h1>Riwayat Pemeriksaan Gizi Balita</h1>');
  w.document.write('<div class="subtitle">Laporan riwayat pemeriksaan status gizi balita.</div>');
  w.document.write('<div class="meta">Orang Tua: <strong>' + (ortuName || '-') + '</strong></div>');
  w.document.write('<div class="meta">Dicetak pada: ' + new Date().toLocaleString("id-ID") + '</div>');
  w.document.write('</div>');
  w.document.write(tableHTML);
  w.document.write('</body></html>');

  w.document.close();
  w.focus();
  w.print();
  w.close();
}
</script>
</body>
</html>
