<?php
// app/Controllers/OrcamentoController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/OrcamentoModel.php';

$model = new OrcamentoModel($pdo);
$acao = $_REQUEST['acao'] ?? 'listar';

if ($acao === 'buscar') {
    buscarOrcamento($model);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($acao) {
        case 'excluir':
            excluirOrcamento($model);
            break;

        case 'criar':
            criarOrcamento($model);
            break;

        case 'atualizar':
            atualizarOrcamento($model);
            break;

        default:
            listarOrcamentos();
            break;
    }
}

listarOrcamentos();

function listarOrcamentos(): void
{
    header('Location: /orcamentos');
    exit;
}

function criarOrcamento(OrcamentoModel $model): void
{
    $clienteId        = (int) ($_POST['cliente_id'] ?? 0);
    $servicoPrincipal = $_POST['servico_principal'] ?? '';
    $descricaoServico = trim($_POST['descricao_servico'] ?? '');
    $formaPagamento   = $_POST['forma_pagamento'] ?? 'Pix';
    $status           = $_POST['status'] ?? 'Enviado';
    $valorTotal       = (float) ($_POST['valor_total'] ?? 0);
    $itens            = $_POST['itens'] ?? [];

    if ($clienteId <= 0 || $servicoPrincipal === '' || $descricaoServico === '' || $valorTotal <= 0) {
        header('Location: /orcamentos?erro=1');
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
        die('Erro ao salvar orcamento: ' . $e->getMessage());
    }

    if ($id > 0) {
        fd_audit_log('orcamento.create', 'orcamento', (int) $id, [
            'cliente_id' => $clienteId,
            'valor_total' => $valorTotal,
            'status' => $status,
        ]);
    }

    header('Location: /orcamentos?criado=1&id=' . $id);
    exit;
}

function atualizarOrcamento(OrcamentoModel $model): void
{
    $id               = (int) ($_POST['id'] ?? 0);
    $clienteId        = (int) ($_POST['cliente_id'] ?? 0);
    $servicoPrincipal = $_POST['servico_principal'] ?? '';
    $descricaoServico = trim($_POST['descricao_servico'] ?? '');
    $formaPagamento   = $_POST['forma_pagamento'] ?? 'Pix';
    $status           = $_POST['status'] ?? 'Enviado';
    $valorTotal       = (float) ($_POST['valor_total'] ?? 0);
    $itens            = $_POST['itens'] ?? [];

    if ($id <= 0 || $clienteId <= 0 || $servicoPrincipal === '' || $descricaoServico === '' || $valorTotal <= 0) {
        header('Location: /orcamentos?erro=1');
        exit;
    }

    try {
        $model->atualizar(
            $id,
            $clienteId,
            $servicoPrincipal,
            $descricaoServico,
            $formaPagamento,
            $status,
            $valorTotal,
            $itens
        );
    } catch (Throwable $e) {
        die('Erro ao atualizar orcamento: ' . $e->getMessage());
    }

    fd_audit_log('orcamento.update', 'orcamento', $id, [
        'cliente_id' => $clienteId,
        'valor_total' => $valorTotal,
        'status' => $status,
    ]);

    header('Location: /orcamentos?atualizado=1&id=' . $id);
    exit;
}

function excluirOrcamento(OrcamentoModel $model): void
{
    $id = (int) ($_POST['orcamento_id'] ?? 0);

    if ($id > 0 && $model->excluir($id)) {
        fd_audit_log('orcamento.delete', 'orcamento', $id);
        header('Location: /orcamentos?excluido=1');
        exit;
    }

    header('Location: /orcamentos?erro=1');
    exit;
}

function buscarOrcamento(OrcamentoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');
    ini_set('display_errors', '0');

    try {
        $id = (int) ($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(
                ['success' => false, 'message' => 'ID invalido'],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            exit;
        }

        $orcamento = $model->buscarPorId($id);
        if (!$orcamento) {
            echo json_encode(
                ['success' => false, 'message' => 'Orcamento nao encontrado'],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            exit;
        }

        echo json_encode(
            [
                'success' => true,
                'orcamento' => $orcamento,
                'itens' => $model->buscarItens($id),
            ],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(
            ['success' => false, 'message' => 'Nao foi possivel carregar este orcamento agora.'],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }
}
