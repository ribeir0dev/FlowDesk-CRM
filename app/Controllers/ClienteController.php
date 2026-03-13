<?php
// app/Controllers/ClienteController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../inc/functions/auth.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';

$clienteModel = new ClienteModel($pdo);

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

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

    default:
        header('Location: /modules/painel.php?mod=clientes');
        exit;
}

/**
 * Criar cliente (antes: cadastrar_cliente.php)
 */
function criarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=clientes');
        exit;
    }

    $nome     = trim($_POST['nome'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $status   = $_POST['status'] ?? 'ativo';
    $genero   = $_POST['genero'] ?? 'empresa';
    $obs      = trim($_POST['observacoes'] ?? '');

    if ($nome === '' || $whatsapp === '' || $email === '') {
        header('Location: /modules/painel.php?mod=clientes&erro=1');
        exit;
    }

    if (!function_exists('gerarTokenPublico')) {
        function gerarTokenPublico($length = 64) {
            return bin2hex(random_bytes($length / 2));
        }
    }

    $token_publico = gerarTokenPublico(64);

    $clienteModel->criar([
        'nome'          => $nome,
        'whatsapp'      => $whatsapp,
        'email'         => $email,
        'status'        => $status,
        'observacoes'   => $obs,
        'genero'        => $genero,
        'token_publico' => $token_publico,
    ]);

    header('Location: /modules/painel.php?mod=clientes&ok=1');
    exit;
}

/**
 * Atualizar cliente (antes: editar_cliente.php)
 */
function atualizarCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=clientes');
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
        header('Location: /modules/painel.php?mod=cliente&id=' . $id . '&erro=1');
        exit;
    }

    $clienteModel->atualizar($id, [
        'nome'        => $nome,
        'whatsapp'    => $whatsapp,
        'email'       => $email,
        'status'      => $status,
        'genero'      => $genero,
        'observacoes' => $obs,
    ]);

    header('Location: /modules/painel.php?mod=cliente&id=' . $id . '&ok=1');
    exit;
}

/**
 * Upload de foto do cliente (antes: upload_foto_cliente.php)
 */
function uploadFotoCliente(ClienteModel $clienteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=clientes');
        exit;
    }

    $cliente_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

    if ($cliente_id <= 0 || empty($_FILES['foto']['name'])) {
        header('Location: /modules/painel.php?mod=cliente&id=' . $cliente_id);
        exit;
    }

    $ext_permitidas = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $ext_permitidas) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        header('Location: /modules/painel.php?mod=cliente&id=' . $cliente_id . '&foto=erro');
        exit;
    }

    $baseDir = __DIR__ . '/../../uploads/clientes/';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $nome_arquivo = 'cliente_' . $cliente_id . '_' . time() . '.' . $ext;
    $destino      = $baseDir . $nome_arquivo;

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        $caminho_db = '/uploads/clientes/' . $nome_arquivo;
        $clienteModel->salvarFotoPerfil($cliente_id, $caminho_db);
    }

    header('Location: /modules/painel.php?mod=cliente&id=' . $cliente_id);
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
