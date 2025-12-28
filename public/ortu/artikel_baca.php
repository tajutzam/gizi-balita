<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db2.php';
require_ortu();

/* ================== AMBIL ID ARTIKEL ================== */
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$err = "";
$artikel = null;

if ($id <= 0) {
    $err = "Artikel tidak ditemukan.";
} else {
    try {
        // ✅ KONEKSI PAKAI PDO() DARI includes/db2.php
        $pdo = pdo();

        $sql = "
            SELECT 
                a.id,
                a.judul,
                a.kategori,
                a.konten,
                a.created_at,
                a.status,
                a.cover_image,      -- ambil path cover
                u.name AS penulis
            FROM artikels a
            LEFT JOIN users u ON u.id = a.penulis_id
            WHERE a.id = ?
              AND a.status = 'Terbit'
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $artikel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$artikel) {
            $err = "Artikel tidak ditemukan atau belum diterbitkan.";
        }
    } catch (Throwable $e) {
        $err = "Terjadi kesalahan saat mengambil data artikel: " . $e->getMessage();
    }
}

/* ========== HELPER TANGGAL ========== */
function format_tanggal_id($dateStr) {
    if (!$dateStr) return '';
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;

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

    $m = (int)date('n', $ts);
    $d = date('j', $ts);
    $y = date('Y', $ts);
    return $d . ' ' . ($bulan[$m] ?? $m) . ' ' . $y;
}

/* ========== HELPER COVER URL ========== */
$coverUrl = null;
if ($artikel && !empty($artikel['cover_image'])) {
    $path = str_replace('\\', '/', trim($artikel['cover_image']));

    if (preg_match('~^https?://~i', $path)) {
        // kalau sudah URL penuh
        $coverUrl = $path;
    } else {
        // path relatif terhadap public/
        // contoh di DB: uploads/artikels/cover_2_1764574720_7282.png
        $coverUrl = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>
    <?php if ($artikel): ?>
      <?= htmlspecialchars($artikel['judul']); ?> - Artikel Edukasi - Gizi Balita (Ortu)
    <?php else: ?>
      Artikel Edukasi - Gizi Balita (Ortu)
    <?php endif; ?>
  </title>
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

    .artikel-title {
      font-size: 1.5rem;
      font-weight: 600;
    }
    .artikel-meta {
      font-size: .85rem;
      color: #6c757d;
    }
    .artikel-kategori {
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    .artikel-konten {
      font-size: .95rem;
      line-height: 1.7;
    }

    .artikel-cover {
      width: 100%;
      max-height: 320px;
      object-fit: cover;
      border-radius: .95rem;
      margin-bottom: 1rem;
      border: 1px solid #e5e7eb;
      background-color: #f8fafc;
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
          <a class="nav-link" href="<?= BASE_URL ?>/ortu/grafik_perkembangan.php">
            Grafik Perkembangan
          </a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/ortu/artikel_list.php">
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

    <div class="row mb-3">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <a href="<?= BASE_URL ?>/ortu/artikel_list.php" class="btn btn-link btn-sm text-muted text-decoration-none ps-0">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar artikel
          </a>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-9 col-xl-8">

        <div class="card card-soft">
          <div class="card-body p-4 p-md-4">

            <?php if ($err): ?>
              <div class="alert alert-danger small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($err); ?>
              </div>
            <?php endif; ?>

            <?php if ($artikel): ?>

              <?php if ($coverUrl): ?>
                <img
                  src="<?= htmlspecialchars($coverUrl); ?>"
                  alt="Cover Artikel"
                  class="artikel-cover"
                >
              <?php endif; ?>

              <span class="badge rounded-pill bg-success-subtle text-success artikel-kategori mb-2">
                <i class="bi bi-bookmark me-1"></i>
                <?= htmlspecialchars($artikel['kategori'] ?? 'Edukasi Gizi'); ?>
              </span>

              <h1 class="artikel-title mb-2">
                <?= htmlspecialchars($artikel['judul']); ?>
              </h1>

              <div class="artikel-meta mb-3">
                <span class="me-3">
                  <i class="bi bi-person-circle me-1"></i>
                  <?= htmlspecialchars($artikel['penulis'] ?? 'Tenaga Kesehatan'); ?>
                </span>
                <span class="me-3">
                  <i class="bi bi-calendar-event me-1"></i>
                  <?= htmlspecialchars(format_tanggal_id($artikel['created_at'])); ?>
                </span>
              </div>

              <hr class="my-3">

              <div class="artikel-konten">
                <?php
                // Jika konten disimpan sebagai HTML:
                echo $artikel['konten'];
                // Kalau mau plain text:
                // echo nl2br(htmlspecialchars($artikel['konten']));
                ?>
              </div>
            <?php else: ?>
              <?php if (!$err): ?>
                <div class="alert alert-warning small">
                  Artikel tidak ditemukan.
                </div>
              <?php endif; ?>
            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.
      </div>
      <div class="text-md-end">
        <span class="me-2">Artikel ini bersifat edukatif dan tidak menggantikan konsultasi dengan tenaga kesehatan.</span>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
