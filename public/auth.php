<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

header('Location: ' . $base . '/');
exit;
