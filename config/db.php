<?php
// db.php - conexao segura
require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/env.php';
fd_load_env();

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'db_flowdesk';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS');
$db_pass = $db_pass === false ? 'root' : $db_pass;
$db_charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('[FlowDesk][DB] ' . $e->getMessage());
    http_response_code(500);
    exit('Nao foi possivel conectar ao banco de dados.');
}
