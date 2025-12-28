<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

// Ambil daftar orang tua dari tabel users (role = 'ortu')
$ortuList = [];
try {
    $res = $mysqli->query("
        SELECT id, name, email
        FROM users
        WHERE role = 'ortu'
        ORDER BY name
    ");
    while ($row = $res->fetch_assoc()) {
        $ortuList[] = $row;
    }
} catch (Throwable $e) {
    // Kalau error ambil ortu, biar nanti ditampilkan di halaman
    $loadOrtuError = "Gagal memuat data orang tua: " . $e->getMessage();
}

// Inisialisasi variabel form & pesan (sesuai nama kolom tabel)
$err           = '';
$msg           = '';
$user_ortu_id  = $_POST['user_ortu_id']  ?? '';
$nama_balita   = $_POST['nama_balita']   ?? '';
$tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
$jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
$berat_lahir   = $_POST['berat_lahir']   ?? '';
$tinggi_lahir  = $_POST['tinggi_lahir']  ?? '';

/* ============== PROSES SUBMIT FORM ============== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_ortu_id  = (int)$user_ortu_id;
    $nama_balita   = trim($nama_balita);
    $tanggal_lahir = trim($tanggal_lahir);
    $jenis_kelamin = trim($jenis_kelamin);

    // opsional: jika kosong, biarkan null
    $berat_lahir  = ($_POST['berat_lahir']  === '' ? null : (float)$berat_lahir);
    $tinggi_lahir = ($_POST['tinggi_lahir'] === '' ? null : (float)$tinggi_lahir);

    // Validasi dasar
    if ($nama_balita === '' || $user_ortu_id <= 0 || $tanggal_lahir === '' ||
        ($jenis_kelamin !== 'L' && $jenis_kelamin !== 'P')) {

        $err = "Semua field bertanda * wajib diisi dengan benar.";
    } else {

        // Pastikan user_ortu_id valid dan memang role = 'ortu'
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE id=? AND role='ortu' LIMIT 1");
        $stmt->bind_param("i", $user_ortu_id);
        $stmt->execute();
        $checkRes  = $stmt->get_result();
        $ortuValid = $checkRes->fetch_assoc();
        $stmt->close();

        if (!$ortuValid) {
            $err = "Data orang tua tidak valid.";
        } else {
            // Insert ke tabel balitas (sesuai struktur baru)
            try {
                $stmt = $mysqli->prepare("
                    INSERT INTO balitas
                      (user_ortu_id, nama_balita, tanggal_lahir, jenis_kelamin, berat_lahir, tinggi_lahir)
                    VALUES
                      (?, ?, ?, ?, ?, ?)
                ");

                // Catatan:
                // - Jika ingin benar-benar menyimpan NULL di DB untuk berat/tinggi lahir kosong,
                //   tipe kolom sudah NULL, jadi tidak masalah jika kita kirim NULL.
                $stmt->bind_param(
                    "isssdd",
                    $user_ortu_id,
                    $nama_balita,
                    $tanggal_lahir,
                    $jenis_kelamin,
                    $berat_lahir,
                    $tinggi_lahir
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    // Redirect ke list setelah sukses
                    header("Location: " . BASE_URL . "/nakes/balita_list.php");
                    exit;
                } else {
                    $err = "Gagal menyimpan data balita: " . $stmt->error;
                    $stmt->close();
                }
            } catch (Throwable $e) {
                $err = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Data Balita - Gizi Balita (Nakes)</title>
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
        <li class="nav-item mx-lg-1"><a class="nav-link" href="<?= BASE_URL ?>/nakes/dashboard.php">Dashboard</a></li>
        <li class="nav-item mx-lg-1"><a class="nav-link active" href="<?= BASE_URL ?>/nakes/balita_list.php">Data Balita</a></li>
        <li class="nav-item mx-lg-1"><a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_input.php">Input Pemeriksaan</a></li>
        <li class="nav-item mx-lg-1"><a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php">Riwayat Pemeriksaan</a></li>
        <li class="nav-item mx-lg-1"><a class="nav-link" href="<?= BASE_URL ?>/nakes/artikel_manage.php">Artikel Edukasi</a></li>

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
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/nakes/profile.php"><i class="bi bi-person-circle me-2"></i> Profil</a></li>
            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.4);"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
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

    <div class="row mb-3">
      <div class="col-12 d-md-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">Tambah Data Balita</h4>
          <p class="text-muted mb-0">
            Lengkapi informasi balita yang akan dipantau status gizinya.
          </p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= BASE_URL ?>/nakes/balita_list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Balita
          </a>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-7 col-xl-6">

        <div class="card card-soft">
          <div class="card-body">

            <?php if (!empty($loadOrtuError)): ?>
              <div class="alert alert-danger small py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($loadOrtuError) ?>
              </div>
            <?php endif; ?>

            <?php if ($err): ?>
              <div class="alert alert-danger small py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($err) ?>
              </div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <!-- Orang Tua -->
              <div class="mb-3">
                <label class="form-label small text-muted">Orang Tua</label>
                <select name="user_ortu_id" class="form-select form-select-sm" required>
                  <option value="">-- Pilih Orang Tua --</option>
                  <?php foreach ($ortuList as $o): ?>
                    <option value="<?= (int)$o['id'] ?>"
                      <?= (int)$user_ortu_id === (int)$o['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($o['name']) ?> (<?= htmlspecialchars($o['email']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($ortuList)): ?>
                  <small class="text-danger">
                    Belum ada data orang tua. Tambahkan akun orang tua terlebih dahulu.
                  </small>
                <?php endif; ?>
              </div>

              <!-- Nama Balita -->
              <div class="mb-3">
                <label class="form-label small text-muted">Nama Balita</label>
                <input type="text"
                       name="nama_balita"
                       class="form-control form-control-sm"
                       value="<?= htmlspecialchars($nama_balita) ?>"
                       placeholder="Contoh: Aisyah Putri"
                       required>
              </div>

              <!-- Tanggal Lahir -->
              <div class="mb-3">
                <label class="form-label small text-muted">Tanggal Lahir</label>
                <input type="date"
                       name="tanggal_lahir"
                       class="form-control form-control-sm"
                       value="<?= htmlspecialchars($tanggal_lahir) ?>"
                       required>
              </div>

              <!-- Jenis Kelamin -->
              <div class="mb-3">
                <label class="form-label small text-muted d-block">Jenis Kelamin</label>
                <div class="form-check form-check-inline">
                  <input class="form-check-input"
                         type="radio"
                         name="jenis_kelamin"
                         id="jkL"
                         value="L"
                         <?= $jenis_kelamin === 'L' ? 'checked' : '' ?>
                         required>
                  <label class="form-check-label small" for="jkL">Laki-laki</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input"
                         type="radio"
                         name="jenis_kelamin"
                         id="jkP"
                         value="P"
                         <?= $jenis_kelamin === 'P' ? 'checked' : '' ?>
                         required>
                  <label class="form-check-label small" for="jkP">Perempuan</label>
                </div>
              </div>

              <!-- Berat & Tinggi Lahir (opsional) -->
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small text-muted">
                    Berat Lahir (kg) <span class="text-muted">(opsional)</span>
                  </label>
                  <input type="number"
                         name="berat_lahir"
                         class="form-control form-control-sm"
                         min="0"
                         max="10"
                         step="0.01"
                         value="<?= htmlspecialchars($_POST['berat_lahir'] ?? '') ?>"
                         placeholder="Contoh: 3.20">
                </div>
                <div class="col-md-6">
                  <label class="form-label small text-muted">
                    Tinggi/Panjang Lahir (cm) <span class="text-muted">(opsional)</span>
                  </label>
                  <input type="number"
                         name="tinggi_lahir"
                         class="form-control form-control-sm"
                         min="0"
                         max="70"
                         step="0.1"
                         value="<?= htmlspecialchars($_POST['tinggi_lahir'] ?? '') ?>"
                         placeholder="Contoh: 49.5">
                </div>
              </div>

              <hr class="my-3">

              <div class="d-flex justify-content-between align-items-center">
                <a href="<?= BASE_URL ?>/nakes/balita_list.php"
                   class="btn btn-link btn-sm text-muted text-decoration-none">
                  <i class="bi bi-arrow-left"></i> Batal
                </a>

                <div class="d-flex gap-2">
                  <button type="reset" class="btn btn-outline-secondary btn-sm">Reset</button>
                  <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-person-plus me-1"></i> Simpan Data Balita
                  </button>
                </div>
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
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.
      </div>
      <div class="text-md-end">
        <span class="me-2">Data balita digunakan untuk pemantauan status gizi dan perkembangan.</span>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
