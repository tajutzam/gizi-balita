<?php
require_once __DIR__ . '/../config/config.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function current_user_role()
{
    return $_SESSION['role'] ?? null;
}

function require_nakes()
{
    if (!is_logged_in() || current_user_role() !== 'nakes') {
        header('Location: /login_nakes.php');
        exit;
    }
}

function require_ortu()
{
    if (!is_logged_in() || current_user_role() !== 'ortu') {
        header('Location: /login_ortu.php');
        exit;
    }
}
