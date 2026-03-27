<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/../Views/layouts/app.php';
