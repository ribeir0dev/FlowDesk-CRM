<?php
// app/Controllers/HospedagemController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/HospedagemModel.php';

$model = new HospedagemModel($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modules/painel.php?mod=hospedagens');
    exit;
}

$acao = $_POST['acao'] ?? 'criar';

switch ($acao) {
    case 'excluir':
        excluirHospedagem($model);
        break;

    case 'criar':
    default:
        criarHospedagem($model);
        break;
}

/**
 * Criar hospedagem
 */
function criarHospedagem(HospedagemModel $model): void
{
    $nome       = trim($_POST['nome'] ?? '');
    $tipo       = $_POST['tipo'] ?? 'dominio';
    $dataInicio = $_POST['data_inicio'] ?? null;
    $dataFim    = $_POST['data_fim'] ?? null;

    if ($nome === '' || !$dataInicio || !$dataFim) {
        header('Location: /modules/painel.php?mod=hospedagens&erro=1');
        exit;
    }

    $model->criar($nome, $tipo, $dataInicio, $dataFim);

    header('Location: /modules/painel.php?mod=hospedagens&ok=1');
    exit;
}

/**
 * Excluir hospedagem
 */
function excluirHospedagem(HospedagemModel $model): void
{
    $id = (int)($_POST['hospedagem_id'] ?? 0);

    if ($id > 0 && $model->excluir($id)) {
        header('Location: /modules/painel.php?mod=hospedagens&excluida=1');
        exit;
    }

    header('Location: /modules/painel.php?mod=hospedagens&erro=1');
    exit;
}
