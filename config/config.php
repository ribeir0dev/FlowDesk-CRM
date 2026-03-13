<?php

if (session_status() === PHP_SESSION_NONE) {


// Segurança extra para sessão
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
}
?>
