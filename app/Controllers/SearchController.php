<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../Helpers/auth.php';

$workspaceId = fd_current_workspace_id() ?? 0;
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    $resultados = [];
} else {
    $like = '%' . $q . '%';

    // clientes
    $stmt = $pdo->prepare("
        SELECT 'cliente' AS tipo, id, nome AS titulo, email AS subtitulo
        FROM clientes
        WHERE workspace_id = ?
          AND (nome LIKE ? OR email LIKE ?)
        LIMIT 10
    ");
    $stmt->execute([$workspaceId, $like, $like]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // projetos
    $stmt = $pdo->prepare("
        SELECT 'projeto' AS tipo, id, nome_projeto AS titulo, descricao AS subtitulo
        FROM projetos
        WHERE workspace_id = ?
          AND (nome_projeto LIKE ? OR descricao LIKE ?)
        LIMIT 10
    ");
    $stmt->execute([$workspaceId, $like, $like]);
    $resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Voce pode repetir o padrao para tarefas, hospedagens etc.
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultados);
exit;

