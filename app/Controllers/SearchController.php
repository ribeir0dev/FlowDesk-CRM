<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../config/db.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    $resultados = [];
} else {
    $like = '%' . $q . '%';

    // clientes
    $stmt = $pdo->prepare("
        SELECT 'cliente' AS tipo, id, nome AS titulo, email AS subtitulo
        FROM clientes
        WHERE nome LIKE ? OR email LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$like, $like]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // projetos
    $stmt = $pdo->prepare("
        SELECT 'projeto' AS tipo, id, nome_projeto AS titulo, descricao AS subtitulo
        FROM projetos
        WHERE nome_projeto LIKE ? OR descricao LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$like, $like]);
    $resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // você pode repetir o padrão para tarefas, hospedagens etc.
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultados);
exit;
