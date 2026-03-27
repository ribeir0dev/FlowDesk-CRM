<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_REQUEST['acao'] = 'salvar_bloco';
require __DIR__ . '/../app/Controllers/ClienteController.php';
