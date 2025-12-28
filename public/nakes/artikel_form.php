<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ' . BASE_URL . '/login_nakes.php');
    exit;
}

// deteksi edit / tambah
$isEdit    = false;
$artikelId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && ctype_digit($_POST['id'])) {
        $artikelId = (int)$_POST['id'];
        $isEdit    = true;
    }
} else {
    if (!empty($_GET['id']) && ctype_digit($_GET['id'])) {
        $artikelId = (int)$_GET['id'];
        $isEdit    = true;
    }
}

// DEFAULT nilai form
$formJudul    = '';
$formKategori = '';
$formStatus   = 'Draft';
$formKonten   = '';
$formCover    = null;   // path cover lama (kalau edit)

$error   = '';
$success = '';

// opsi kategori dan status
$kategoriOpt = [
    'Gizi Buruk',
    'Gizi Kurang',
    'Gizi Baik',
    'Risiko Gizi Lebih',
    'Gizi Lebih',
    'Obesitas',
    'Stunting',
];

$statusOpt = ['Draft', 'Terbit'];

/* ========== AMBIL DATA JIKA EDIT (GET) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $isEdit && $artikelId) {
    $stmt = $mysqli->prepare("
        SELECT judul, kategori, status, konten, cover_image
        FROM artikels
        WHERE id = ? AND penulis_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $artikelId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $formJudul    = $row['judul'] ?? '';
        $formKategori = $row['kategori'] ?? '';
        $formStatus   = $row['status'] ?? 'Draft';
        $formKonten   = $row['konten'] ?? '';
        $formCover    = $row['cover_image'] ?? null; // contoh: uploads/artikels/cover_xxx.png
    } else {
        $error  = 'Artikel tidak ditemukan atau Anda tidak berhak mengedit artikel ini.';
        $isEdit = false;
    }
    $stmt->close();
}

/* ========== HELPER UPLOAD COVER ========== */
function upload_cover_image(?array $file, int $userId): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        // tidak ada file baru
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal meng-upload file (error code: ' . $file['error'] . ').');
    }

    // Validasi tipe
    $allowedExt  = ['jpg','jpeg','png','webp'];
    $fileName    = $file['name'] ?? '';
    $tmpPath     = $file['tmp_name'] ?? '';

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Format file cover harus JPG, JPEG, PNG, atau WEBP.');
    }

    /*
     * Folder tujuan (di dalam public/)
     * __DIR__ = .../gizi-balita-php/public/nakes
     * __DIR__ . '/..' = .../gizi-balita-php/public  ✅
     */
    $baseDir   = realpath(__DIR__ . '/..'); // arahkan ke folder public
    $uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'artikels';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Nama unik
    $newName  = 'cover_' . $userId . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        throw new RuntimeException('Gagal memindahkan file upload.');
    }

    // Path relatif untuk disimpan di DB (relatif terhadap folder public)
    // hasil: "uploads/artikels/cover_xxx.png"
    $relativePath = 'uploads/artikels/' . $newName;
    return $relativePath;
}

/* ========== PROSES SIMPAN (POST) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul    = trim($_POST['judul'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $status   = trim($_POST['status'] ?? 'Draft');
    $konten   = trim($_POST['konten'] ?? '');
    $action   = $_POST['action'] ?? 'draft';   // draft / publish

    // isi ulang form kalau error
    $formJudul    = $judul;
    $formKategori = $kategori;
    $formStatus   = $status;
    $formKonten   = $konten;

    if ($judul === '' || $kategori === '' || $konten === '') {
        $error = 'Judul, kategori, dan konten wajib diisi.';
    } elseif (!in_array($kategori, $kategoriOpt, true)) {
        $error = 'Kategori tidak valid.';
    } elseif (!in_array($status, $statusOpt, true)) {
        $error = 'Status tidak valid.';
    } else {
        // kalau tombol "Simpan sebagai Draft", paksa status Draft
        $finalStatus = ($action === 'draft') ? 'Draft' : $status;

        // default: pakai cover lama (kalau edit)
        $coverPath = $formCover;

        // coba upload cover baru (kalau ada)
        try {
            $newCover = upload_cover_image($_FILES['cover_image'] ?? null, (int)$userId);
            if ($newCover !== null) {
                $coverPath = $newCover;
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error === '') {
            if ($isEdit && $artikelId) {
                // UPDATE
                $stmt = $mysqli->prepare("
                    UPDATE artikels
                    SET judul = ?, kategori = ?, status = ?, konten = ?, cover_image = ?
                    WHERE id = ? AND penulis_id = ?
                ");
                $stmt->bind_param(
                    'sssssii',
                    $judul,
                    $kategori,
                    $finalStatus,
                    $konten,
                    $coverPath,
                    $artikelId,
                    $userId
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: ' . BASE_URL . '/nakes/artikel_manage.php?msg=updated');
                    exit;
                } else {
                    $error = 'Gagal memperbarui artikel. Coba lagi.';
                }
                $stmt->close();
            } else {
                // INSERT BARU
                $stmt = $mysqli->prepare("
                    INSERT INTO artikels (judul, kategori, status, konten, cover_image, penulis_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param(
                    'sssssi',
                    $judul,
                    $kategori,
                    $finalStatus,
                    $konten,
                    $coverPath,
                    $userId
                );
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: ' . BASE_URL . '/nakes/artikel_manage.php?msg=created');
                    exit;
                } else {
                    $error = 'Gagal menyimpan artikel. Coba lagi.';
                }
                $stmt->close();
            }
        }
    }
}

/* ========== HELPER COVER PREVIEW URL (untuk form edit) ========== */
/*
   Struktur:
   - file ini: public/nakes/artikel_form.php
   - gambar : public/uploads/artikels/xxx.png
   BASE_URL sudah mengarah ke .../public
*/
$coverPreviewUrl = null;
if (!empty($formCover)) {
    $normalized       = str_replace('\\', '/', $formCover);
    $coverPreviewUrl  = rtrim(BASE_URL, '/') . '/' . ltrim($normalized, '/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= $isEdit ? 'Edit Artikel' : 'Tambah Artikel Baru'; ?> - Gizi Balita (Nakes)</title>
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

    textarea.form-control {
      min-height: 220px;
      font-size: .9rem;
    }

    .thumb-preview {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border-radius: .5rem;
      border: 1px solid #e5e7eb;
      background-color: #f8fafc;
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
          <a class="nav-link" href="<?= BASE_URL ?>/nakes/pemeriksaan_list.php">Riwayat Pemeriksaan</a>
        </li>

        <li class="nav-item mx-lg-1">
          <a class="nav-link active" href="<?= BASE_URL ?>/nakes/artikel_manage.php">Artikel Edukasi</a>
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
          <h4 class="mb-1">
            <?= $isEdit ? 'Edit Artikel Edukasi' : 'Tambah Artikel Edukasi'; ?>
          </h4>
          <p class="text-muted mb-0">
            Tulis artikel yang akan tampil di halaman orang tua sebagai bahan edukasi gizi balita.
          </p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= BASE_URL ?>/nakes/artikel_manage.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar
          </a>
        </div>
      </div>
    </div>

    <!-- Card Form Artikel -->
    <div class="row justify-content-center">
      <div class="col-lg-9 col-xl-8">
        <div class="card card-soft">
          <div class="card-body">

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($error); ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle me-1"></i>
                <?= htmlspecialchars($success); ?>
              </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
              <?php if ($isEdit && $artikelId): ?>
                <input type="hidden" name="id" value="<?= (int)$artikelId; ?>">
              <?php endif; ?>

              <!-- Judul -->
              <div class="mb-3">
                <label class="form-label small text-muted">Judul Artikel</label>
                <input
                  type="text"
                  name="judul"
                  class="form-control form-control-sm"
                  placeholder="Contoh: Panduan Nutrisi Seimbang untuk Balita 1–3 Tahun"
                  value="<?= htmlspecialchars($formJudul); ?>"
                  required
                >
              </div>

              <div class="row g-2">
                <!-- Kategori -->
                <div class="col-md-6">
                  <label class="form-label small text-muted">Kategori (Terkait Status Gizi)</label>
                  <select name="kategori" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategoriOpt as $k): ?>
                      <option
                        value="<?= htmlspecialchars($k); ?>"
                        <?= $formKategori === $k ? 'selected' : ''; ?>
                      >
                        <?= htmlspecialchars($k); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                  <label class="form-label small text-muted">Status Artikel</label>
                  <select name="status" class="form-select form-select-sm" required>
                    <?php foreach ($statusOpt as $s): ?>
                      <option
                        value="<?= htmlspecialchars($s); ?>"
                        <?= $formStatus === $s ? 'selected' : ''; ?>
                      >
                        <?= htmlspecialchars($s); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted small">
                    <i class="bi bi-info-circle"></i>
                    Pilih <strong>Draft</strong> jika artikel belum siap ditampilkan ke orang tua.
                  </small>
                </div>
              </div>

              <!-- Cover -->
              <div class="mb-3 mt-3">
                <label class="form-label small text-muted">Cover Artikel (opsional)</label>
                <input
                  type="file"
                  name="cover_image"
                  class="form-control form-control-sm"
                  accept=".jpg,.jpeg,.png,.webp"
                >
                <small class="text-muted small d-block mt-1">
                  Disarankan ukuran landscape (misal 1200×600px). Format: JPG, PNG, atau WEBP.
                </small>

                <?php if (!empty($coverPreviewUrl)): ?>
                  <div class="mt-2 d-flex align-items-center gap-2">
                    <img
                      src="<?= htmlspecialchars($coverPreviewUrl); ?>"
                      alt="Cover saat ini"
                      class="thumb-preview"
                    >
                    <span class="small text-muted">
                      Cover saat ini. Jika Anda memilih file baru, cover akan diganti.
                    </span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Konten -->
              <div class="mb-3 mt-3">
                <label class="form-label small text-muted">Konten Artikel</label>
                <textarea
                  name="konten"
                  class="form-control"
                  placeholder="Tulis isi artikel di sini. Sertakan tips, penjelasan, dan panduan yang mudah dipahami orang tua..."
                  required
                ><?= htmlspecialchars($formKonten); ?></textarea>
              </div>

              <div class="alert alert-info small">
                <i class="bi bi-info-circle me-1"></i>
                Gunakan bahasa yang sederhana dan ramah, hindari istilah medis yang terlalu teknis tanpa penjelasan.
              </div>

              <hr class="my-3">

              <div class="d-flex justify-content-between align-items-center">
                <button type="reset" class="btn btn-outline-secondary btn-sm">
                  Reset
                </button>
                <div class="d-flex gap-2">
                  <button type="submit" name="action" value="draft" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    Simpan sebagai Draft
                  </button>
                  <button type="submit" name="action" value="publish" class="btn btn-success btn-sm">
                    <i class="bi bi-cloud-upload me-1"></i>
                    Simpan & Terbitkan
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
<!-- ========= END MAIN CONTENT ========= -->


<!-- =============== FOOTER =============== -->
<footer>
  <div class="container-fluid px-4 px-md-5 py-3">
    <div class="d-md-flex justify-content-between align-items-center">
      <div class="mb-2 mb-md-0">
        &copy; <?= date('Y') ?> <strong>GiziBalita</strong>. Sistem Monitoring Gizi Balita.
      </div>
      <div class="text-md-end">
        <span class="me-2">Artikel yang informatif membantu orang tua membuat keputusan gizi yang lebih baik.</span>
      </div>
    </div>
  </div>
</footer>
<!-- ========= END FOOTER ========= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
