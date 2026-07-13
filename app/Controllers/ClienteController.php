<?php
// app/Controllers/ClienteController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/BillingModel.php';
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
        header('Location: ' . fd_base_path() . '/clientes');
        exit;
}

/**
 * Criar cliente (antes: cadastrar_cliente.php)
 */
function criarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/clientes');
        exit;
    }

    $nome     = mb_substr(trim((string) ($_POST['nome'] ?? '')), 0, 160);
    $whatsapp = mb_substr(trim((string) ($_POST['whatsapp'] ?? '')), 0, 40);
    $email    = mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 180);
    $status   = in_array(($_POST['status'] ?? 'ativo'), ['ativo', 'potencial', 'inativo'], true) ? $_POST['status'] : 'ativo';
    $genero   = in_array(($_POST['genero'] ?? 'empresa'), ['masculino', 'feminino', 'empresa'], true) ? $_POST['genero'] : 'empresa';
    $obs      = mb_substr(trim((string) ($_POST['observacoes'] ?? '')), 0, 4000);

    if ($nome === '' || $whatsapp === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . fd_base_path() . '/clientes?erro=1');
        exit;
    }

    $billingModel = new BillingModel($GLOBALS['pdo']);
    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
        header('Location: ' . fd_base_path() . '/clientes?erro=1');
        exit;
    }

    try {
        $gate = $billingModel->getResourceGate($workspaceId, 'clients');
        if (!$gate['allowed']) {
            $billingModel->releaseWorkspaceBillingLock($workspaceId);
            header('Location: ' . fd_base_path() . '/clientes?limit=clients');
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
    } finally {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
    }

    if ($clienteId > 0) {
        fd_audit_log('cliente.create', 'cliente', $clienteId, [
            'nome' => $nome,
            'email' => $email,
            'status' => $status,
        ]);
    }

    header('Location: ' . fd_base_path() . '/clientes?ok=1');
    exit;
}

/**
 * Atualizar cliente (antes: editar_cliente.php)
 */
function atualizarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/clientes');
        exit;
    }

    $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome     = mb_substr(trim((string) ($_POST['nome'] ?? '')), 0, 160);
    $whatsapp = mb_substr(trim((string) ($_POST['whatsapp'] ?? '')), 0, 40);
    $email    = mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 180);
    $status   = in_array(($_POST['status'] ?? 'ativo'), ['ativo', 'potencial', 'inativo'], true) ? $_POST['status'] : 'ativo';
    $genero   = in_array(($_POST['genero'] ?? 'empresa'), ['masculino', 'feminino', 'empresa'], true) ? $_POST['genero'] : 'empresa';
    $obs      = mb_substr(trim((string) ($_POST['observacoes'] ?? '')), 0, 4000);

    if ($id <= 0 || $nome === '' || $whatsapp === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . fd_base_path() . '/cliente?id=' . $id . '&erro=1');
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

    header('Location: ' . fd_base_path() . '/cliente?id=' . $id . ($ok ? '&ok=1' : '&erro=1'));
    exit;
}

/**
 * Upload de foto do cliente (antes: upload_foto_cliente.php)
 */
function uploadFotoCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/clientes');
        exit;
    }

    $cliente_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

    if ($cliente_id <= 0 || empty($_FILES['foto']['name'])) {
        header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id);
        exit;
    }

    $ext_permitidas = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    $maxBytes = 8 * 1024 * 1024;
    if (!in_array($ext, $ext_permitidas, true) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK || (int) ($_FILES['foto']['size'] ?? 0) > $maxBytes) {
        header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&foto=erro');
        exit;
    }

    $clienteAtual = $clienteModel->buscarPorId($cliente_id);
    if (!$clienteAtual) {
        header('Location: ' . fd_base_path() . '/clientes?erro=1');
        exit;
    }

    $tmpName = (string) ($_FILES['foto']['tmp_name'] ?? '');
    $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
    $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&foto=erro');
        exit;
    }

    $billingModel = new BillingModel($GLOBALS['pdo']);
    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
        header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&foto=erro');
        exit;
    }

    $replacingBytes = $billingModel->getLocalUploadSize((string) ($clienteAtual['foto_perfil'] ?? ''));
    $gate = $billingModel->getStorageGate(
        $workspaceId,
        (int) ($_FILES['foto']['size'] ?? 0),
        $replacingBytes
    );

    if (!$gate['allowed']) {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
        $_SESSION['billing_gate_message'] = $gate['message'];
        header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&limit=storage');
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
            header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&foto=ok');
            $billingModel->releaseWorkspaceBillingLock($workspaceId);
            exit;
        }
    }

    $billingModel->releaseWorkspaceBillingLock($workspaceId);
    header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . '&foto=erro');
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
        header('Location: ' . fd_base_path() . '/clientes');
        exit;
    }

    $workspaceId = fd_current_workspace_id() ?? 0;
    if ($workspaceId <= 0) {
        header('Location: ' . fd_base_path() . '/clientes?erro=1');
        exit;
    }

    $cliente_id = (int) ($_POST['cliente_id'] ?? 0);
    $slug = mb_substr(trim((string) ($_POST['slug'] ?? '')), 0, 80);
    $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 160);
    $compartilhado = isset($_POST['compartilhado']) ? 1 : 0;
    $allowedSlugs = ['website', 'hospedagem', 'acesso_site', 'registro_br', 'observacoes', 'briefing', 'contratos'];

    $clienteModel = new ClienteModel($pdo);
    if ($cliente_id <= 0 || $slug === '' || !in_array($slug, $allowedSlugs, true) || !$clienteModel->buscarPorId($cliente_id)) {
        header('Location: ' . fd_base_path() . '/clientes?erro=1');
        exit;
    }

    $payload = [];

    if ($slug === 'website') {
        $payload['url'] = mb_substr(trim((string) ($_POST['url'] ?? '')), 0, 500);
    } elseif (in_array($slug, ['hospedagem', 'acesso_site', 'registro_br'], true)) {
        $payload['url'] = mb_substr(trim((string) ($_POST['url'] ?? '')), 0, 500);
        $payload['usuario'] = mb_substr(trim((string) ($_POST['usuario'] ?? '')), 0, 180);
        $payload['senha'] = mb_substr(trim((string) ($_POST['senha'] ?? '')), 0, 500);
    } else {
        $payload['livre'] = mb_substr(trim((string) ($_POST['conteudo_livre'] ?? '')), 0, 8000);
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

    header('Location: ' . fd_base_path() . '/cliente?id=' . $cliente_id . ($ok ? '&ok_bloco=1' : '&erro=1'));
    exit;
}
