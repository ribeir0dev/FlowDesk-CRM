<?php
// app/Controllers/PipelineController.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/OportunidadeModel.php';

$model = new OportunidadeModel($pdo);
$acao = $_REQUEST['acao'] ?? 'index';

switch ($acao) {
    case 'board_json':
        boardJson($model);
        break;

    case 'mover':
        moverOportunidade($model);
        break;

    case 'criar':
        criarOportunidade($model);
        break;

    case 'buscar':
        buscarOportunidade($model);
        break;

    case 'atualizar':
        atualizarOportunidade($model);
        break;

    case 'marcar_ganha':
        marcarGanha($model);
        break;

    case 'marcar_perdida':
        marcarPerdida($model);
        break;

    case 'excluir':
        excluirOportunidade($model);
        break;


    default:
        // aqui você pode incluir a view pipeline.php
        require __DIR__ . '/../../modules/pipeline.php';
        exit;
}

/* ------- ações ------- */

function boardJson(OportunidadeModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    $estagios = $model->listarEstagiosAtivos();
    $board = [];

    foreach ($estagios as $estagio) {
        $cards = $model->listarPorEstagio((int) $estagio['id']);
        $board[] = [
            'id' => (int) $estagio['id'],
            'nome' => $estagio['nome'],
            'slug' => $estagio['slug'],
            'ordem' => (int) $estagio['ordem'],
            'cor_hex' => $estagio['cor_hex'],
            'cards' => array_map(fn($o) => [
                'id' => (int) $o['id'],
                'cliente_nome' => $o['cliente_nome'],
                'titulo' => $o['titulo'],
                'valor_previsto' => (float) $o['valor_previsto'],
                'probabilidade' => (int) $o['probabilidade'],
                'estagio_id' => (int) $o['funil_estagio_id'],
            ], $cards),
        ];
    }

    echo json_encode($board);
    exit;
}

function moverOportunidade(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $novoEstagio = (int) ($_POST['funil_estagio_id'] ?? 0);

    if ($id <= 0 || $novoEstagio <= 0) {
        http_response_code(400);
        exit;
    }

    $ok = $model->moverEstagio($id, $novoEstagio);
    echo json_encode(['ok' => $ok]);
    exit;
}

function criarOportunidade(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = $model->criar($_POST);
    header('Location: /modules/painel.php?mod=pipeline&ok=1');
    exit;
}

function atualizarOportunidade(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Location: /modules/painel.php?mod=pipeline&erro=1');
        exit;
    }

    $model->atualizar($id, $_POST);
    header('Location: /modules/painel.php?mod=pipeline&ok=1');
    exit;
}

function marcarGanha(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $ganhoId = (int) ($_POST['estagio_ganho_id'] ?? 0);

    if ($id && $ganhoId) {
        $model->marcarGanha($id, $ganhoId);
    }
    header('Location: /modules/painel.php?mod=pipeline');
    exit;
}

function marcarPerdida(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $perdidoId = (int) ($_POST['estagio_perdido_id'] ?? 0);
    $motivo = trim($_POST['motivo_perda'] ?? '');

    if ($id && $perdidoId) {
        $model->marcarPerdida($id, $perdidoId, $motivo);
    }
    header('Location: /modules/painel.php?mod=pipeline');
    exit;
}
function buscarOportunidade(OportunidadeModel $model): void
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([]);
        exit;
    }

    $op = $model->buscarPorId($id);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($op ?: []);
    exit;
}

function excluirOportunidade(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        exit;
    }

    $model->excluir($id); // crie este método no model
    header('Location: /modules/painel.php?mod=pipeline');
    exit;
}
