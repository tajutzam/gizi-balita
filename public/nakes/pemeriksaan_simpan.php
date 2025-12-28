<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/ml_predict.php';

// ... ambil $_POST
$balita_id      = (int) ($_POST['balita_id'] ?? 0);
$umur_bulan     = (int) ($_POST['umur_bulan'] ?? 0);
$jenis_kelamin  = $_POST['jenis_kelamin'] ?? 'L';
$berat_badan    = (float) ($_POST['berat_badan'] ?? 0);
$tinggi_badan   = (float) ($_POST['tinggi_badan'] ?? 0);
$lingkar_lengan = isset($_POST['lingkar_lengan']) && $_POST['lingkar_lengan'] !== ''
                    ? (float) $_POST['lingkar_lengan']
                    : null;

// Panggil ML
$ml_result = ml_predict_status_gizi(
    $umur_bulan,
    $jenis_kelamin,
    $berat_badan,
    $tinggi_badan,
    $lingkar_lengan
);

if (isset($ml_result['error'])) {
    // kalau error, boleh fallback ke "Gizi Baik" atau simpan null
    $status_gizi = "Gizi Baik";
} else {
    $status_gizi = $ml_result['status_gizi'] ?? "Gizi Baik";
}

// lanjut INSERT ke tabel pemeriksaans pakai $status_gizi
