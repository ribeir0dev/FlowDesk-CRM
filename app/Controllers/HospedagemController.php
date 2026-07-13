<?php
// app/Controllers/HospedagemController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/HospedagemModel.php';

$model = new HospedagemModel($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . fd_base_path() . '/hospedagens');
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
        header('Location: ' . fd_base_path() . '/hospedagens?erro=1');
        exit;
    }

    if ($model->criar($nome, $tipo, $dataInicio, $dataFim)) {
        fd_audit_log('hospedagem.create', 'hospedagem', null, [
            'nome' => $nome,
            'tipo' => $tipo,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ]);
        header('Location: ' . fd_base_path() . '/hospedagens?ok=1');
        exit;
    }

    header('Location: ' . fd_base_path() . '/hospedagens?erro=1');
    exit;
}

/**
 * Excluir hospedagem
 */
function excluirHospedagem(HospedagemModel $model): void
{
    $id = (int)($_POST['hospedagem_id'] ?? 0);

    if ($id > 0 && $model->excluir($id)) {
        fd_audit_log('hospedagem.delete', 'hospedagem', $id);
        header('Location: ' . fd_base_path() . '/hospedagens?excluida=1');
        exit;
    }

    header('Location: ' . fd_base_path() . '/hospedagens?erro=1');
    exit;
}
