<?php
session_start();
session_unset();
session_destroy();

require_once __DIR__ . '/../config/config.php';

// redirect ke halaman login ortu dengan BASE_URL
header('Location: ' . BASE_URL . '/login_ortu.php');
exit;
