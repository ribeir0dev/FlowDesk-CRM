<?php
// app/Controllers/ClienteController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';

$clienteModel = new ClienteModel($pdo);

$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {
    case 'criar':
        criarCliente($clienteModel);
        break;

    case 'atualizar':
        atualizarCliente($clienteModel);
        break;

    case 'uploadFoto':
        uploadFotoCliente($clienteModel);
        break;

    case 'bloco':
        buscarBlocoCliente($clienteModel);
        break;

    case 'salvar_bloco':
        salvarBlocoCliente($pdo);
        break;

    default:
        header('Location: /clientes');
        exit;
}

/**
 * Criar cliente (antes: cadastrar_cliente.php)
 */
function criarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /clientes');
        exit;
    }

    $nome     = trim($_POST['nome'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $status   = $_POST['status'] ?? 'ativo';
    $genero   = $_POST['genero'] ?? 'empresa';
    $obs      = trim($_POST['observacoes'] ?? '');

    if ($nome === '' || $whatsapp === '' || $email === '') {
        header('Location: /clientes?erro=1');
        exit;
    }

    $token_publico = gerarTokenPublico(64);

    $clienteId = $clienteModel->criar([
        'nome'          => $nome,
        'whatsapp'      => $whatsapp,
        'email'         => $email,
        'status'        => $status,
        'observacoes'   => $obs,
        'genero'        => $genero,
        'token_publico' => $token_publico,
    ]);

    if ($clienteId > 0) {
        fd_audit_log('cliente.create', 'cliente', $clienteId, [
            'nome' => $nome,
            'email' => $email,
            'status' => $status,
        ]);
    }

    header('Location: /clientes?ok=1');
    exit;
}

/**
 * Atualizar cliente (antes: editar_cliente.php)
 */
function atualizarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /clientes');
        exit;
    }

    $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome     = trim($_POST['nome'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $status   = $_POST['status'] ?? 'ativo';
    $genero   = $_POST['genero'] ?? 'empresa';
    $obs      = trim($_POST['observacoes'] ?? '');

    if ($id <= 0 || $nome === '' || $whatsapp === '' || $email === '') {
        header('Location: /cliente?id=' . $id . '&erro=1');
        exit;
    }

    $ok = $clienteModel->atualizar($id, [
        'nome'        => $nome,
        'whatsapp'    => $whatsapp,
        'email'       => $email,
        'status'      => $status,
        'genero'      => $genero,
        'observacoes' => $obs,
    ]);

    if ($ok) {
        fd_audit_log('cliente.update', 'cliente', $id, [
            'nome' => $nome,
            'email' => $email,
            'status' => $status,
        ]);
    }

    header('Location: /cliente?id=' . $id . ($ok ? '&ok=1' : '&erro=1'));
    exit;
}

/**
 * Upload de foto do cliente (antes: upload_foto_cliente.php)
 */
function uploadFotoCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /clientes');
        exit;
    }

    $cliente_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

    if ($cliente_id <= 0 || empty($_FILES['foto']['name'])) {
        header('Location: /cliente?id=' . $cliente_id);
        exit;
    }

    $ext_permitidas = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $ext_permitidas) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        header('Location: /cliente?id=' . $cliente_id . '&foto=erro');
        exit;
    }

    $baseDir = __DIR__ . '/../../public/uploads/clientes/';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $nome_arquivo = 'cliente_' . $cliente_id . '_' . time() . '.' . $ext;
    $destino      = $baseDir . $nome_arquivo;

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        $caminho_db = '/uploads/clientes/' . $nome_arquivo;
        if ($clienteModel->salvarFotoPerfil($cliente_id, $caminho_db)) {
            fd_audit_log('cliente.photo_upload', 'cliente', $cliente_id, [
                'foto' => $caminho_db,
            ]);
            header('Location: /cliente?id=' . $cliente_id . '&foto=ok');
            exit;
        }
    }

    header('Location: /cliente?id=' . $cliente_id . '&foto=erro');
    exit;
}

/**
 * Buscar bloco de conteúdo do cliente (antes: carregar_bloco_cliente.php)
 */
function buscarBlocoCliente(ClienteModel $clienteModel): void
{
    header('Content-Type: application/json; charset=utf-8');

    $cliente_id = (int)($_GET['cliente_id'] ?? 0);
    $slug       = $_GET['slug'] ?? '';

    if ($cliente_id <= 0 || $slug === '') {
        echo json_encode(null);
        exit;
    }

    $bloco = $clienteModel->buscarBloco($cliente_id, $slug);

    echo json_encode($bloco ?: null);
    exit;
}

function salvarBlocoCliente(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /clientes');
        exit;
    }

    $workspaceId = fd_current_workspace_id() ?? 0;
    if ($workspaceId <= 0) {
        header('Location: /clientes?erro=1');
        exit;
    }

    $cliente_id = (int) ($_POST['cliente_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $compartilhado = isset($_POST['compartilhado']) ? 1 : 0;

    $payload = [];

    if ($slug === 'website') {
        $payload['url'] = trim($_POST['url'] ?? '');
    } elseif (in_array($slug, ['hospedagem', 'acesso_site', 'registro_br'], true)) {
        $payload['url'] = trim($_POST['url'] ?? '');
        $payload['usuario'] = trim($_POST['usuario'] ?? '');
        $payload['senha'] = trim($_POST['senha'] ?? '');
    } else {
        $payload['livre'] = trim($_POST['conteudo_livre'] ?? '');
    }

    $conteudoJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO cliente_blocos (workspace_id, cliente_id, slug, titulo, conteudo, compartilhado, atualizado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            conteudo = VALUES(conteudo),
            compartilhado = VALUES(compartilhado),
            atualizado_em = NOW()
    ");
    $ok = $stmt->execute([$workspaceId, $cliente_id, $slug, $titulo, $conteudoJson, $compartilhado]);

    if ($ok) {
        fd_audit_log('cliente.bloco.save', 'cliente_bloco', $cliente_id, [
            'slug' => $slug,
            'titulo' => $titulo,
            'compartilhado' => $compartilhado,
        ]);
    }

    header('Location: /cliente?id=' . $cliente_id . ($ok ? '&ok_bloco=1' : '&erro=1'));
    exit;
}
