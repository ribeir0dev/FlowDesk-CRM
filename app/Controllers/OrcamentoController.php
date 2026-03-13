<?php
// app/Controllers/OrcamentoController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/OrcamentoModel.php';

$model = new OrcamentoModel($pdo);

$acao = $_REQUEST['acao'] ?? 'listar';

// decide se é listagem (GET) ou ações (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($acao) {
        case 'excluir':
            excluirOrcamento($model);
            break;
        case 'criar':
            criarOrcamento($model);
            break;
        case 'buscar':
            buscarOrcamento($model);
            break;
        default:
            listarOrcamentos($model);
            break;
    }
} else {
    listarOrcamentos($model);
}

/**
 * Lista orçamentos e mostra módulo
 */
function listarOrcamentos(OrcamentoModel $model): void
{
    $orcamentos    = $model->listarComClientes();
    $clientesTodos = $model->listarClientes();

    $titulo = 'Novo Orçamento';

    // painel.php provavelmente inclui esse arquivo com base em ?mod=orcamentos
    require __DIR__ . '/../../modules/content/orcamentos.php';
}

/**
 * Criar orçamento
 */
function criarOrcamento(OrcamentoModel $model): void
{
    $clienteId        = (int)($_POST['cliente_id'] ?? 0);
    $servicoPrincipal = $_POST['servico_principal'] ?? '';
    $descricaoServico = trim($_POST['descricao_servico'] ?? '');
    $formaPagamento   = $_POST['forma_pagamento'] ?? 'Pix';
    $status           = $_POST['status'] ?? 'Enviado';
    $valorTotal       = (float)($_POST['valor_total'] ?? 0);
    $itens            = $_POST['itens'] ?? [];

    if ($clienteId <= 0 || $servicoPrincipal === '' || $descricaoServico === '' || $valorTotal <= 0) {
        header('Location: /modules/painel.php?mod=orcamentos&erro=1');
        exit;
    }

    try {
        $id = $model->criar(
            $clienteId,
            $servicoPrincipal,
            $descricaoServico,
            $formaPagamento,
            $status,
            $valorTotal,
            $itens
        );
    } catch (Throwable $e) {
        die('Erro ao salvar orçamento: ' . $e->getMessage());
    }


    header('Location: /modules/painel.php?mod=orcamentos&ok=1&id=' . $id);
    exit;
}


/**
 * Excluir orçamento
 */
function excluirOrcamento(OrcamentoModel $model): void
{
    $id = (int)($_POST['orcamento_id'] ?? 0);

    if ($id > 0 && $model->excluir($id)) {
        header('Location: /modules/painel.php?mod=orcamentos&excluido=1');
        exit;
    }

    header('Location: /modules/painel.php?mod=orcamentos&erro=1');
    exit;
}


function buscarOrcamento(OrcamentoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $orcamento = $model->buscarPorId($id);
    if (!$orcamento) {
        echo json_encode(['success' => false, 'message' => 'Orçamento não encontrado']);
        exit;
    }

    $itens = $model->buscarItens($id);

    echo json_encode([
        'success'   => true,
        'orcamento' => $orcamento,
        'itens'     => $itens,
    ]);
    exit;
}
