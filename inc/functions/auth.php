<?php
// inc/functions/auth.php

if (!function_exists('gerarTokenPublico')) {
    function gerarTokenPublico($length = 64) {
        // Gera um token hex seguro, ex: 64 caracteres
        return bin2hex(random_bytes($length / 2));
    }
}


function require_login(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}