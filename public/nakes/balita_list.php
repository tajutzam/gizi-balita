<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

/**
 * Helper label jenis kelamin
 */
function jkLabel($jk) {
    return $jk === 'L' ? 'Laki-laki' : 'Perempuan';
}

// Ambil filter dari query string
$q    = trim($_GET['q']   ?? '');
$jk   = trim($_GET['jk']  ?? '');
$ortu = trim($_GET['desa'] ?? ''); // pakai field ini sebagai filter nama orang tua

// Build query dari tabel balitas + join users (ortu)
$sql = "
  SELECT 
    b.id,
    b.nama_balita,
    b.tanggal_lahir,
    b.jenis_kelamin,
    b.berat_lahir,
    b.tinggi_lahir,
    u.name  AS nama_ortu,
    u.email AS email_ortu
  FROM balitas b
  LEFT JOIN users u ON u.id = b.user_ortu_id
  WHERE 1=1
";

$params = [];
$types  = "";

// Filter: q (nama balita / nama ortu / email ortu)
if ($q !== '') {
    $sql .= " AND (
                b.nama_balita LIKE ?
                OR u.name LIKE ?
                OR u.email LIKE ?
             )";
    $like = '%'.$q.'%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

// Filter: jenis kelamin
if ($jk === 'L' || $jk === 'P') {
    $sql     .= " AND b.jenis_kelamin = ?";
    $params[] = $jk;
    $types   .= "s";
}

// Filter tambahan berdasarkan nama orang tua (pakai input 'desa' di form)
if ($ortu !== '') {
    $sql     .= " AND u.name LIKE ?";
    $params[] = '%'.$ortu.'%';
    $types   .= "s";
}

// Urutkan berdasarkan nama balita
$sql .= " ORDER BY b.nama_balita ASC";

// Eksekusi query
$balitaList = [];
try {
    $stmt = $mysqli->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $balitaList[] = $row;
    }
    $stmt->close();
} catch (Throwable $e) {
    $loadError = "Gagal memuat data balita: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Balita - Gizi Balita (Nakes)</title>
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
              <a class="dropdown-item" href="<?=  BASE_URL ?>/nakes/profile.php">
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
          <h4 class="mb-1">Data Balita</h4>
          <p class="text-muted mb-0">
            Kelola daftar balita yang tercatat dan siap dilakukan pemeriksaan status gizi.
          </p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= BASE_URL ?>/nakes/balita_add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Tambah Balita
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($loadError)): ?>
      <div class="alert alert-danger small py-2">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars($loadError) ?>
      </div>
    <?php endif; ?>

    <!-- Filter / Pencarian -->
    <div class="card card-soft mb-4">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Cari Balita / Ortu</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Nama balita / nama orang tua / email..."
                value="<?= htmlspecialchars($q) ?>"
              >
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Jenis Kelamin</label>
            <select name="jk" class="form-select form-select-sm">
              <option value="">Semua</option>
              <option value="L" <?= ($jk === 'L') ? 'selected' : '' ?>>Laki-laki</option>
              <option value="P" <?= ($jk === 'P') ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Nama Orang Tua (opsional)</label>
            <input
              type="text"
              name="desa" <!-- pakai nama ini untuk filter nama ortu -->
          </div>
          <div class="col-md-2 text-md-end">
            <button type="submit" class="btn btn-success btn-sm me-1">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="<?= BASE_URL ?>/nakes/balita_list.php" class="btn btn-outline-secondary btn-sm">
              Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Data Balita -->
    <div class="card card-soft">
      <div class="card-body">
        <?php if (empty($balitaList)): ?>
          <div class="text-center py-5">
            <i class="bi bi-people fs-1 text-muted mb-3"></i>
            <h6 class="mb-1">Belum ada data balita</h6>
            <p class="text-muted small mb-3">
              Tambahkan data balita terlebih dahulu sebelum melakukan pemeriksaan gizi.
            </p>
            <a href="<?= BASE_URL ?>/nakes/balita_add.php" class="btn btn-primary btn-sm">
              <i class="bi bi-person-plus me-1"></i> Tambah Balita
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:5%;">#</th>
                  <th>Nama Balita</th>
                  <th>Tgl Lahir</th>
                  <th>Jenis Kelamin</th>
                  <th>Orang Tua</th>
                  <th>Berat Lahir (kg)</th>
                  <th>Tinggi Lahir (cm)</th>
                  <th style="width:14%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; ?>
                <?php foreach ($balitaList as $b): ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($b['nama_balita']); ?></td>
                    <td><?= htmlspecialchars($b['tanggal_lahir']); ?></td>
                    <td><?= htmlspecialchars(jkLabel($b['jenis_kelamin'])); ?></td>
                    <td>
                      <?= htmlspecialchars($b['nama_ortu'] ?? '-'); ?>
                      <?php if (!empty($b['email_ortu'])): ?>
                        <small class="text-muted d-block"><?= htmlspecialchars($b['email_ortu']); ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= $b['berat_lahir']  !== null ? htmlspecialchars($b['berat_lahir'])  : '-'; ?></td>
                    <td><?= $b['tinggi_lahir'] !== null ? htmlspecialchars($b['tinggi_lahir']) : '-'; ?></td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <a
                          href="<?= BASE_URL ?>/nakes/balita_detail.php?id=<?= (int)$b['id'] ?>"
                          class="btn btn-outline-secondary"
                          title="Detail"
                        >
                          <i class="bi bi-eye"></i>
                        </a>
                        <a
                          href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php?balita_id=<?= (int)$b['id'] ?>"
                          class="btn btn-outline-primary"
                          title="Input Pemeriksaan"
                        >
                          <i class="bi bi-clipboard2-plus"></i>
                        </a>
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
        <span class="me-2">Pastikan setiap balita terdaftar sebelum dilakukan pemeriksaan.</span>
      </div>
    </div>
  </div>
</footer>
<!-- ========= END FOOTER ========= -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
