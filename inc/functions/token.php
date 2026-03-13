<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../conf/db.php';

// MOSTRAR ERROS ENQUANTO TESTA
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função para gerar token compatível com PHP 5.x e 7+
if (!function_exists('gerarTokenPublico')) {
    function gerarTokenPublico($length = 64) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        }
        // fallback para versões antigas de PHP
        $bytes = openssl_random_pseudo_bytes($length / 2);
        return bin2hex($bytes);
    }
}

// pega só quem ainda não tem token
$stmt = $pdo->query("SELECT id FROM clientes WHERE token_publico IS NULL OR token_publico = ''");
$ids  = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    $token = gerarTokenPublico(64);
    $up = $pdo->prepare("UPDATE clientes SET token_publico = ? WHERE id = ?");
    $up->execute([$token, $id]);
}

echo 'Tokens gerados para ' . count($ids) . ' clientes.';
