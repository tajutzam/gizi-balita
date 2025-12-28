<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

function statusBadgeClass($status) {
    switch ($status) {
        case 'Terbit': return 'bg-success';
        case 'Draft':  return 'bg-secondary';
        default:       return 'bg-light text-dark';
    }
}

// ambil filter
$q        = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$status   = trim($_GET['status'] ?? '');

// build query
$sql = "
  SELECT 
    a.id,
    a.judul,
    a.kategori,
    a.status,
    a.created_at,
    a.cover_image,
    u.name AS penulis
  FROM artikels a
  LEFT JOIN users u ON u.id = a.penulis_id
  WHERE 1=1
";
$params = [];
$types  = '';

if ($q !== '') {
    $sql .= " AND a.judul LIKE ?";
    $params[] = '%'.$q.'%';
    $types   .= 's';
}

if ($kategori !== '') {
    $sql .= " AND a.kategori = ?";
    $params[] = $kategori;
    $types   .= 's';
}

if ($status !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $status;
    $types   .= 's';
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$artikelList = [];
while ($row = $res->fetch_assoc()) {
    $artikelList[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen Artikel Edukasi - Gizi Balita (Nakes)</title>
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

    .thumb-mini {
      width: 72px;
      height: 48px;
      object-fit: cover;
      border-radius: .5rem;
      border: 1px solid #e5e7eb;
      background-color: #f8fafc;
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
            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.4);"></li>
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
          <h4 class="mb-1">Manajemen Artikel Edukasi</h4>
          <p class="text-muted mb-0">
            Buat, kelola, dan publikasikan artikel mengenai gizi balita untuk dibaca oleh orang tua.
          </p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= BASE_URL ?>/nakes/artikel_form.php" class="btn btn-success btn-sm">
            <i class="bi bi-journal-plus me-1"></i> Tambah Artikel Baru
          </a>
        </div>
      </div>
    </div>

    <!-- Filter / Pencarian -->
    <div class="card card-soft mb-4">
      <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Judul / Kata Kunci</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Cari judul artikel..."
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
              >
            </div>
          </div>

          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Kategori</label>
            <select name="kategori" class="form-select form-select-sm">
              <option value="">Semua</option>
              <?php
              $kategoriOpt = [
                  'Gizi Buruk',
                  'Gizi Kurang',
                  'Gizi Baik',
                  'Risiko Gizi Lebih',
                  'Gizi Lebih',
                  'Obesitas',
                  'Stunting',
              ];
              $kategoriQ = $_GET['kategori'] ?? '';
              foreach ($kategoriOpt as $k):
              ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $kategoriQ === $k ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <?php
              $statusOpt = ['', 'Terbit', 'Draft'];
              $statusQ   = $_GET['status'] ?? '';
              ?>
              <option value="">Semua</option>
              <?php foreach ($statusOpt as $s): if ($s === '') continue; ?>
                <option value="<?= $s ?>" <?= $statusQ === $s ? 'selected' : '' ?>>
                  <?= $s ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 text-md-end">
            <button type="submit" class="btn btn-success btn-sm me-1">
              <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="<?= BASE_URL ?>/nakes/artikel_manage.php" class="btn btn-outline-secondary btn-sm">
              Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabel Artikel -->
    <div class="card card-soft">
      <div class="card-body">
        <?php if (empty($artikelList)): ?>
          <div class="text-center py-5">
            <i class="bi bi-journal-x fs-1 text-muted mb-3"></i>
            <h6 class="mb-1">Belum ada artikel</h6>
            <p class="text-muted small mb-3">
              Mulai buat artikel edukasi pertama Anda untuk membimbing orang tua mengenai gizi balita.
            </p>
            <a href="<?= BASE_URL ?>/nakes/artikel_form.php" class="btn btn-success btn-sm">
              <i class="bi bi-journal-plus me-1"></i> Tambah Artikel Baru
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:5%;">#</th>
                  <th style="width:10%;">Cover</th>
                  <th>Judul</th>
                  <th>Kategori</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th>Penulis</th>
                  <th style="width:18%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; ?>
                <?php foreach ($artikelList as $a): ?>
                  <tr id="row-<?= (int)$a['id'] ?>">
                    <td><?= $no++; ?></td>
                    <td>
                      <?php if (!empty($a['cover_image'])): ?>
                        <img
                          src="<?= BASE_URL . '/' . htmlspecialchars($a['cover_image']); ?>"
                          alt="Cover"
                          class="thumb-mini"
                        >
                      <?php else: ?>
                        <span class="text-muted small">
                          <i class="bi bi-image me-1"></i> -
                        </span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['judul']); ?></td>
                    <td><?= htmlspecialchars($a['kategori']); ?></td>
                    <td><?= htmlspecialchars($a['created_at']); ?></td>
                    <td>
                      <span class="badge badge-status <?= statusBadgeClass($a['status']); ?>">
                        <?= htmlspecialchars($a['status']); ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($a['penulis'] ?? '-'); ?></td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <a
                          href="<?= BASE_URL ?>/nakes/artikel_view.php?id=<?= (int)$a['id'] ?>"
                          class="btn btn-outline-secondary"
                          title="Lihat"
                        >
                          <i class="bi bi-eye"></i>
                        </a>
                        <a
                          href="<?= BASE_URL ?>/nakes/artikel_form.php?id=<?= (int)$a['id'] ?>"
                          class="btn btn-outline-primary"
                          title="Edit"
                        >
                          <i class="bi bi-pencil"></i>
                        </a>
                        <!-- Tombol delete pakai modal + AJAX -->
                        <button
                          type="button"
                          class="btn btn-outline-danger btn-delete"
                          title="Hapus"
                          data-id="<?= (int)$a['id'] ?>"
                          data-judul="<?= htmlspecialchars($a['judul']) ?>"
                          data-row="row-<?= (int)$a['id'] ?>"
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
        <span class="me-2">Artikel yang baik membantu orang tua mengambil keputusan gizi yang tepat.</span>
      </div>
    </div>
  </div>
</footer>
<!-- ========= END FOOTER ========= -->

<!-- ============== MODAL DELETE ============== -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;">
      <div class="modal-header bg-danger text-white" style="border-top-left-radius:1rem;border-top-right-radius:1rem;">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Apakah Anda yakin ingin menghapus artikel berikut?</p>
        <div class="p-3 bg-light rounded border mb-2">
          <strong id="delJudul" class="text-danger"></strong>
        </div>
        <small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          Batal
        </button>
        <button id="btnConfirmDelete" class="btn btn-danger btn-sm">
          <i class="bi bi-trash me-1"></i> Hapus
        </button>
      </div>
    </div>
  </div>
</div>
<!-- ========= END MODAL ========= -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const deleteButtons = document.querySelectorAll(".btn-delete");
    const modalDeleteEl = document.getElementById("modalDelete");
    const modalDelete   = new bootstrap.Modal(modalDeleteEl);
    const delJudul      = document.getElementById("delJudul");
    const btnConfirm    = document.getElementById("btnConfirmDelete");

    let deleteId   = null;
    let deleteRowId = null;

    deleteButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            deleteId    = this.dataset.id;
            deleteRowId = this.dataset.row;
            delJudul.textContent = this.dataset.judul;

            modalDelete.show();
        });
    });

    btnConfirm.addEventListener("click", function () {
        if (!deleteId) return;

        fetch("<?= BASE_URL ?>/nakes/artikel_delete_ajax.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "id=" + encodeURIComponent(deleteId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(deleteRowId);
                if (row) {
                    row.style.transition = "0.4s";
                    row.style.opacity = "0";
                    setTimeout(() => row.remove(), 400);
                }
                modalDelete.hide();
            } else {
                alert("Gagal menghapus: " + (data.message || "Terjadi kesalahan"));
            }
        })
        .catch(err => {
            alert("Error: " + err);
        });
    });
});
</script>
</body>
</html>
