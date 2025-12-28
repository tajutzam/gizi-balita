<?php
function pdo() {
    static $pdo;

    if ($pdo) {
        return $pdo;
    }

    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $name = 'gizi_balita';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}