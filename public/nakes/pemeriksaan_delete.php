<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_nakes();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: " . BASE_URL . "/nakes/pemeriksaan_list.php?err=ID pemeriksaan tidak valid");
    exit();
}

try {
    // Cek dulu ada datanya atau tidak
    $stmt = $mysqli->prepare("SELECT id FROM pemeriksaans WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header("Location: " . BASE_URL . "/nakes/pemeriksaan_list.php?err=Data pemeriksaan tidak ditemukan");
        exit();
    }

    // Eksekusi delete
    $del = $mysqli->prepare("DELETE FROM pemeriksaans WHERE id = ? LIMIT 1");
    $del->bind_param("i", $id);

    if ($del->execute()) {
        header("Location: " . BASE_URL . "/nakes/pemeriksaan_list.php?msg=Data pemeriksaan berhasil dihapus");
    } else {
        header("Location: " . BASE_URL . "/nakes/pemeriksaan_list.php?err=Gagal menghapus data pemeriksaan");
    }

    $del->close();

} catch (Throwable $e) {
    header("Location: " . BASE_URL . "/nakes/pemeriksaan_list.php?err=" . urlencode($e->getMessage()));
    exit();
}
