<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../Helpers/auth.php';
    header('Location: ' . fd_base_path() . '/');
    exit;
}

require_once __DIR__ . '/../views/layouts/app.php';
