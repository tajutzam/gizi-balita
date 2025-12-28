<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

/**
 * Helper untuk class badge status gizi
 */
function badgeClassNakes($status) {
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
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}

/* ================== AMBIL FILTER DARI GET ================== */
$q         = trim($_GET['q'] ?? '');
$tgl_awal  = trim($_GET['tgl_awal'] ?? '');
$tgl_akhir = trim($_GET['tgl_akhir'] ?? '');
$statusQ   = trim($_GET['status'] ?? '');

/* ================== AMBIL DATA PEMERIKSAAN DARI DB ================== */
$pemeriksaanList = [];
$listError       = '';

try {
    // Base query
    $sql = "
        SELECT 
            p.id,
            DATE(p.tanggal_pemeriksaan) AS tanggal,
            p.umur_bulan,
            p.berat_badan   AS bb,
            p.tinggi_badan  AS tb,
            p.status_gizi,
            b.id            AS balita_id,
            b.nama_balita,
            n.name          AS nakes,
            o.name          AS nama_ortu
        FROM pemeriksaans p
        JOIN balitas b ON b.id = p.balita_id
        LEFT JOIN users n ON n.id = p.nakes_id
        LEFT JOIN users o ON o.id = b.user_ortu_id
        WHERE 1=1
    ";

    $params = [];
    $types  = '';

    // Filter nama balita / ortu
    if ($q !== '') {
        $sql     .= " AND (b.nama_balita LIKE ? OR o.name LIKE ?)";
        $likeQ    = '%' . $q . '%';
        $params[] = $likeQ;
        $params[] = $likeQ;
        $types   .= 'ss';
    }

    // Filter periode tanggal
    if ($tgl_awal !== '') {
        $sql     .= " AND DATE(p.tanggal_pemeriksaan) >= ?";
        $params[] = $tgl_awal;
        $types   .= 's';
    }
    if ($tgl_akhir !== '') {
        $sql     .= " AND DATE(p.tanggal_pemeriksaan) <= ?";
        $params[] = $tgl_akhir;
        $types   .= 's';
    }

    // Filter status gizi
    if ($statusQ !== '') {
        $sql     .= " AND p.status_gizi = ?";
        $params[] = $statusQ;
        $types   .= 's';
    }

    // Urutkan dari yang terbaru
    $sql .= " ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC";

    // Siapkan statement
    if ($stmt = $mysqli->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $pemeriksaanList[] = [
                'id'          => (int)$row['id'],          // ID pemeriksaan
                'balita_id'   => (int)$row['balita_id'],   // ID BALITA
                'tanggal'     => $row['tanggal'],
                'nama_balita' => $row['nama_balita'],
                'umur_bulan'  => (int)$row['umur_bulan'],
                'bb'          => (float)$row['bb'],
                'tb'          => (float)$row['tb'],
                'status_gizi' => $row['status_gizi'],
                'nakes'       => $row['nakes'] ?? 'Nakes',
                'nama_ortu'   => $row['nama_ortu'] ?? null,
            ];
        }

        $stmt->close();
    } else {
        $listError = "Gagal menyiapkan query: " . $mysqli->error;
    }
} catch (Throwable $e) {
    $listError = "Gagal memuat data pemeriksaan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Pemeriksaan - Gizi Balita (Nakes)</title>
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

    /* Dropdown ikut tema navbar */
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

    .page-wrapper {
      flex: 1 0 auto;
      padding: 28px 0 40px;
    }
    .card-soft {
      border: none;
      border-radius: 1.1rem;
      box-shadow: 0 14px 32px rgba(0,0,0,0.08);
      background: #ffffff;
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
      font-size: .86rem;
    }
    .badge-status {
      font-size: .75rem;
      padding: .25rem .6rem;
      border-radius: 999px;
    }

    footer {
      flex-shrink: 0;
      background: linear-gradient(120deg, #0b3a60, #0b7542);
      color: #e2f6fa;
      font-size: .85rem;
    }
  </style>
</head>
<body>

<!-- =============== NAVBAR NAKES =============== -->
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
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/balita_list.php">Data Balita</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php">Input Pemeriksaan</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php">Riwayat Pemeriksaan</a>
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
<!-- ========= END NAVBAR ========= -->


<!-- =============== MAIN CONTENT =============== -->
<div class="page-wrapper">
  <div class="container-fluid px-4 px-md-5">

    <!-- Header -->
    <div class="row mb-3">
      <div class="col-12 d-md-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">Riwayat Pemeriksaan Gizi</h4>
          <p class="text-muted mb-0">
            Daftar seluruh pemeriksaan status gizi balita yang pernah dicatat di sistem.
          </p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php" class="btn btn-primary btn-sm">
            <i class="bi bi-clipboard2-plus me-1"></i> Input Pemeriksaan Baru
          </a>
        </div>
      </div>
    </div>

    <!-- Pesan sukses / error dari delete -->
    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success py-2 small">
        <i class="bi bi-check-circle me-1"></i>
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($_GET['err'])): ?>
      <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars($_GET['err']) ?>
      </div>
    <?php endif; ?>

    <?php if ($listError): ?>
      <div class="alert alert-danger py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars($listError) ?>
      </div>
    <?php endif; ?>

    <!-- Filter / Pencarian -->
    <div class="card card-soft mb-4">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Nama Balita / Ortu</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Cari nama balita / orang tua..."
                value="<?= htmlspecialchars($q) ?>"
              >
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Periode Tanggal</label>
            <div class="d-flex gap-1">
              <input
                type="date"
                name="tgl_awal"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($tgl_awal) ?>"
              >
              <span class="align-self-center small text-muted">s.d</span>
              <input
                type="date"
                name="tgl_akhir"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($tgl_akhir) ?>"
              >
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Status Gizi</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">Semua</option>
              <?php
              $statusOpt = [
                  'Gizi Buruk',
                  'Gizi Kurang',
                  'Gizi Baik',
                  'Risiko Gizi Lebih',
                  'Gizi Lebih',
                  'Obesitas',
              ];
              foreach ($statusOpt as $s):
              ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= $statusQ === $s ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 text-md-end">
            <button type="submit" class="btn btn-success btn-sm me-1">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php" class="btn btn-outline-secondary btn-sm">
              Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Riwayat Pemeriksaan -->
    <div class="card card-soft">
      <div class="card-body">
        <?php if (empty($pemeriksaanList) && !$listError): ?>
          <div class="text-center py-5">
            <i class="bi bi-clipboard2-x fs-1 text-muted mb-3"></i>
            <h6 class="mb-1">Belum ada data pemeriksaan</h6>
            <p class="text-muted small mb-3">
              Lakukan pemeriksaan pertama lalu input data melalui menu Input Pemeriksaan.
            </p>
            <a href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php" class="btn btn-primary btn-sm">
              <i class="bi bi-clipboard2-plus me-1"></i> Input Pemeriksaan Baru
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:5%;">#</th>
                  <th>Tanggal</th>
                  <th>Balita</th>
                  <th>Umur (bln)</th>
                  <th>BB (kg)</th>
                  <th>TB (cm)</th>
                  <th>Status Gizi</th>
                  <th>Petugas</th>
                  <th style="width:22%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; ?>
                <?php foreach ($pemeriksaanList as $p): ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($p['tanggal']); ?></td>
                    <td>
                      <?= htmlspecialchars($p['nama_balita']); ?>
                      <?php if (!empty($p['nama_ortu'])): ?>
                        <div class="small text-muted">
                          Ortu: <?= htmlspecialchars($p['nama_ortu']); ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td><?= (int)$p['umur_bulan']; ?></td>
                    <td><?= htmlspecialchars(number_format($p['bb'], 1)); ?></td>
                    <td><?= htmlspecialchars(number_format($p['tb'], 1)); ?></td>
                    <td>
                      <span class="badge badge-status <?= badgeClassNakes($p['status_gizi']); ?>">
                        <?= htmlspecialchars($p['status_gizi']); ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($p['nakes'] ?: 'Tenaga Kesehatan'); ?></td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <!-- DETAIL BALITA: pakai balita_id -->
                        <a
                          href="<?= BASE_URL ?>/nakes/balita_detail.php?id=<?= (int)$p['balita_id']; ?>"
                          class="btn btn-outline-primary"
                          title="Detail Balita & Grafik"
                        >
                          <i class="bi bi-person-lines-fill me-1"></i> Detail
                        </a>

                        <!-- CETAK: pakai id pemeriksaan -->
                        <a
                          href="<?= BASE_URL ?>/nakes/pemeriksaan_cetak.php?id=<?= (int)$p['id']; ?>"
                          class="btn btn-outline-secondary"
                          title="Cetak Pemeriksaan"
                          target="_blank"
                        >
                          <i class="bi bi-printer"></i>
                        </a>

                        <button
                          type="button"
                          class="btn btn-outline-danger"
                          title="Hapus"
                          onclick="hapusPemeriksaan(<?= (int)$p['id'] ?>)"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
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
<!-- ========= END MAIN CONTENT ========= -->


<!-- =============== FOOTER =============== -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.
      </div>
      <div class="text-md-end">
        <span class="me-2">Gunakan riwayat ini untuk memantau tren gizi dan melakukan tindak lanjut.</span>
      </div>
    </div>
  </div>
</footer>
<!-- ========= END FOOTER ========= -->

<script>
function hapusPemeriksaan(id) {
  if (!confirm("Yakin ingin menghapus pemeriksaan ini?\nData tidak dapat dikembalikan.")) {
    return;
  }
  window.location.href = "<?= BASE_URL ?>/nakes/pemeriksaan_delete.php?id=" + id;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
