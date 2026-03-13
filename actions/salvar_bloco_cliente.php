<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modules/painel.php?mod=clientes');
    exit;
}

$cliente_id   = (int)($_POST['cliente_id'] ?? 0);
$slug         = trim($_POST['slug'] ?? '');
$titulo       = trim($_POST['titulo'] ?? '');
$compartilhado= isset($_POST['compartilhado']) ? 1 : 0;

// monta estrutura conforme slug
$payload = [];

if ($slug === 'website') {
    $payload['url'] = trim($_POST['url'] ?? '');
} elseif (in_array($slug, ['hospedagem','acesso_site','registro_br'], true)) {
    $payload['url']     = trim($_POST['url'] ?? '');
    $payload['usuario'] = trim($_POST['usuario'] ?? '');
    $payload['senha']   = trim($_POST['senha'] ?? '');
} else {
    $payload['livre']   = trim($_POST['conteudo_livre'] ?? '');
}

$conteudoJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare("
  INSERT INTO cliente_blocos (cliente_id, slug, titulo, conteudo, compartilhado, atualizado_em)
  VALUES (?, ?, ?, ?, ?, NOW())
  ON DUPLICATE KEY UPDATE
    titulo = VALUES(titulo),
    conteudo = VALUES(conteudo),
    compartilhado = VALUES(compartilhado),
    atualizado_em = NOW()
");
$stmt->execute([$cliente_id, $slug, $titulo, $conteudoJson, $compartilhado]);


header('Location: /modules/painel.php?mod=clientes&id=' . $cliente_id . '&ok_bloco=1');
exit;
