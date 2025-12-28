<?php
require_once _DIR_ . '/../../config/config.php';
require_once _DIR_ . '/../../includes/auth.php';
require_once _DIR_ . '/../../includes/db.php';
require_nakes();

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Metode request tidak valid."
    ]);
    exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

if ($id <= 0 || $userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak valid."
    ]);
    exit;
}

// Opsional: ambil cover_image dulu untuk dihapus dari filesystem
$coverPath = null;
if ($stmt = $mysqli->prepare("SELECT cover_image FROM artikels WHERE id = ? AND penulis_id = ? LIMIT 1")) {
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $coverPath = $row['cover_image'] ?? null;
    }
    $stmt->close();
}

// Hapus artikel
$stmt = $mysqli->prepare("DELETE FROM artikels WHERE id = ? AND penulis_id = ? LIMIT 1");
$stmt->bind_param("ii", $id, $userId);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Kalau mau sekalian hapus file gambar fisik:
    if ($coverPath) {
        $fullPath = _DIR_ . '/../../' . ltrim($coverPath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    echo json_encode([
        "success" => true
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menghapus artikel di database."
    ]);
}