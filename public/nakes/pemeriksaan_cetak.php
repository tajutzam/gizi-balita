<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

$pemeriksaan_id = (int)($_GET['id'] ?? 0);
$err = '';
$data = null;

if ($pemeriksaan_id <= 0) {
    $err = "ID pemeriksaan tidak valid.";
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
                p.status_gizi,
                b.nama_balita,
                b.tanggal_lahir,
                b.jenis_kelamin,
                o.name  AS nama_ortu,
                o.email AS email_ortu,
                n.name  AS nama_nakes
            FROM pemeriksaans p
            JOIN balitas b ON b.id = p.balita_id
            LEFT JOIN users o ON o.id = b.user_ortu_id
            LEFT JOIN users n ON n.id = p.nakes_id
            WHERE p.id = ?
            LIMIT 1
        ";

        $stmt = $mysqli->prepare($sql);
        if ($stmt === false) {
            $err = "Gagal menyiapkan query: " . $mysqli->error;
        } else {
            $stmt->bind_param("i", $pemeriksaan_id);
            $stmt->execute();
            $res  = $stmt->get_result();
            $data = $res->fetch_assoc();
            $stmt->close();

            if (!$data) {
                $err = "Data pemeriksaan tidak ditemukan.";
            }
        }
    } catch (Throwable $e) {
        $err = "Terjadi kesalahan: " . $e->getMessage();
    }
}

function format_tanggal_id(?string $tanggal): string {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    try {
        $dt = new DateTime($tanggal);
        return $dt->format('d-m-Y');
    } catch (Throwable $e) {
        return $tanggal;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak Pemeriksaan - GiziBalita</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    * {
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
    }
    body {
      margin: 20px;
      color: #000;
    }
    .kop {
      text-align: center;
      margin-bottom: 16px;
      border-bottom: 2px solid #000;
      padding-bottom: 8px;
    }
    .kop h2 {
      margin: 0;
      font-size: 20px;
    }
    .kop p {
      margin: 2px 0;
      font-size: 12px;
    }
    .section-title {
      font-weight: bold;
      margin-top: 16px;
      margin-bottom: 6px;
      text-transform: uppercase;
      font-size: 13px;
    }
    table.meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 13px;
    }
    table.meta td {
      padding: 4px 6px;
      vertical-align: top;
    }
    table.meta td.label {
      width: 30%;
      font-weight: bold;
    }
    table.data {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
      font-size: 13px;
    }
    table.data th,
    table.data td {
      border: 1px solid #000;
      padding: 6px 8px;
      text-align: left;
    }
    .ttd {
      margin-top: 40px;
      width: 100%;
      font-size: 13px;
    }
    .ttd td {
      vertical-align: top;
      padding: 4px 6px;
    }
    .small-note {
      font-size: 11px;
      margin-top: 10px;
    }

    @media print {
      body { margin: 10mm; }
      .btn-print { display: none; }
    }
  </style>
</head>
<body onload="window.print()">

<button class="btn-print" onclick="window.print()" style="margin-bottom:10px;">
  Cetak
</button>

<div class="kop">
  <h2>SISTEM MONITORING GIZI BALITA</h2>
  <p>Puskesmas / Posyandu</p>
  <p>Riwayat Pemeriksaan Status Gizi Balita</p>
</div>

<?php if ($err): ?>
  <p><strong>Error:</strong> <?= htmlspecialchars($err); ?></p>
<?php elseif ($data): ?>
  <div class="section-title">Data Balita</div>
  <table class="meta">
    <tr>
      <td class="label">Nama Balita</td>
      <td>: <?= htmlspecialchars($data['nama_balita']); ?></td>
    </tr>
    <tr>
      <td class="label">Tanggal Lahir</td>
      <td>: <?= htmlspecialchars(format_tanggal_id($data['tanggal_lahir'])); ?></td>
    </tr>
    <tr>
      <td class="label">Jenis Kelamin</td>
      <td>:
        <?= ($data['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
      </td>
    </tr>
  </table>

  <div class="section-title">Data Orang Tua</div>
  <table class="meta">
    <tr>
      <td class="label">Nama Orang Tua</td>
      <td>: <?= htmlspecialchars($data['nama_ortu'] ?? '-'); ?></td>
    </tr>
    <tr>
      <td class="label">Email</td>
      <td>: <?= htmlspecialchars($data['email_ortu'] ?? '-'); ?></td>
    </tr>
  </table>

  <div class="section-title">Data Pemeriksaan</div>
  <table class="data">
    <tr>
      <th>Tanggal Pemeriksaan</th>
      <th>Umur (bulan)</th>
      <th>Berat Badan (kg)</th>
      <th>Tinggi Badan (cm)</th>
      <th>Lingkar Lengan (cm)</th>
      <th>Status Gizi</th>
    </tr>
    <tr>
      <td><?= htmlspecialchars(format_tanggal_id($data['tanggal'])); ?></td>
      <td><?= (int)$data['umur_bulan']; ?></td>
      <td><?= number_format((float)$data['berat_badan'], 1); ?></td>
      <td><?= number_format((float)$data['tinggi_badan'], 1); ?></td>
      <td>
        <?= $data['lingkar_lengan'] !== null
              ? number_format((float)$data['lingkar_lengan'], 1)
              : '-'; ?>
      </td>
      <td><?= htmlspecialchars($data['status_gizi']); ?></td>
    </tr>
  </table>

  <table class="ttd">
    <tr>
      <td style="width:60%;"></td>
      <td style="text-align:center;">
        <div>Petugas Kesehatan</div>
        <div style="margin-top:50px; font-weight:bold; text-decoration:underline;">
          <?= htmlspecialchars($data['nama_nakes'] ?? ($_SESSION['name'] ?? '........................')); ?>
        </div>
      </td>
    </tr>
  </table>

  <p class="small-note">
    Catatan: Hasil cetak ini merupakan ringkasan satu kali pemeriksaan status gizi
    balita pada tanggal yang tertera di atas.
  </p>
<?php else: ?>
  <p>Data tidak ditemukan.</p>
<?php endif; ?>

</body>
</html>
