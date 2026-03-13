<?php
// app/Controllers/ProjetoController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ProjetoModel.php';

$projetoModel = new ProjetoModel($pdo);

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'criar':
        criarProjeto($projetoModel);
        break;

    case 'concluir':
        concluirProjeto($projetoModel);
        break;

    case 'getTarefa':
        carregarTarefa($projetoModel);
        break;

    case 'salvarTarefa':
        salvarTarefa($projetoModel);
        break;

    case 'moverTarefa':
        moverTarefa($projetoModel);
        break;

    case 'excluirTarefa':
        excluirTarefa($projetoModel);
        break;
    
    case 'atualizarProjeto':
    atualizarProjeto($projetoModel);
    break;

    case 'getProjeto':
    getProjeto($projetoModel);
    break;

    default:
        header('Location: /modules/painel.php?mod=projetos');
        exit;
}

/* PROJETOS */

function criarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=projetos');
        exit;
    }

    $nome_projeto = trim($_POST['nome_projeto'] ?? '');
    $tipo_projeto = $_POST['tipo_projeto'] ?? 'outro';
    $cliente_id   = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== ''
        ? (int)$_POST['cliente_id']
        : null;
    $data_inicio  = $_POST['data_inicio']  ?: null;
    $data_entrega = $_POST['data_entrega'] ?: null;
    $status       = $_POST['status']       ?? 'planejado';
    $descricao    = trim($_POST['descricao'] ?? '');

    // novo: vindo do funil
    $oportunidade_id = (int)($_POST['oportunidade_id'] ?? 0);

    if ($nome_projeto === '') {
        header('Location: /modules/painel.php?mod=projetos&erro=1');
        exit;
    }

    // cria projeto normalmente
    $novoId = $model->criarProjeto([
        'cliente_id'   => $cliente_id,
        'nome_projeto' => $nome_projeto,
        'tipo_projeto' => $tipo_projeto,
        'descricao'    => $descricao,
        'data_inicio'  => $data_inicio,
        'data_entrega' => $data_entrega,
        'status'       => $status,
    ]);

    // se veio de uma oportunidade, vincula
    if ($oportunidade_id > 0 && $novoId) {
        require_once __DIR__ . '/../Models/OportunidadeModel.php';
        $opModel = new OportunidadeModel($GLOBALS['pdo']); // reaproveita o mesmo PDO
        $opModel->vincularProjeto($oportunidade_id, (int)$novoId);
    }

    header('Location: /modules/painel.php?mod=projetos&ok=1');
    exit;
}


function concluirProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=projetos');
        exit;
    }

    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    if ($projeto_id <= 0) {
        header('Location: /modules/painel.php?mod=projetos&erro=1');
        exit;
    }

    $model->excluirProjeto($projeto_id);

    header('Location: /modules/painel.php?mod=projetos&concluido=1');
    exit;
}

/* TAREFAS */

function carregarTarefa(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(null);
        exit;
    }

    $tarefa = $model->getTarefaById($id);
    echo json_encode($tarefa ?: null);
    exit;
}

function salvarTarefa(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=projetos');
        exit;
    }

    $projeto_id  = (int)($_POST['projeto_id'] ?? 0);
    $tarefa_id   = (int)($_POST['tarefa_id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $descricao   = trim($_POST['descricao'] ?? '');
    $coluna      = $_POST['coluna'] ?? 'backlog';
    $data_entrega = $_POST['data_entrega'] ?: null;

    if ($projeto_id <= 0 || $titulo === '') {
        header('Location: /modules/painel.php?mod=projeto_detalhe&id=' . $projeto_id . '&erro_tarefa=1');
        exit;
    }

    $model->salvarTarefa([
        'projeto_id'   => $projeto_id,
        'tarefa_id'    => $tarefa_id,
        'titulo'       => $titulo,
        'descricao'    => $descricao,
        'coluna'       => $coluna,
        'data_entrega' => $data_entrega,
    ]);

    header('Location: /modules/painel.php?mod=projeto_detalhe&id=' . $projeto_id);
    exit;
}

function moverTarefa(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $tarefa_id = (int)($_POST['tarefa_id'] ?? 0);
    $coluna    = $_POST['coluna'] ?? '';

    $validas = ['backlog', 'andamento', 'revisao', 'concluido'];
    if ($tarefa_id <= 0 || !in_array($coluna, $validas, true)) {
        http_response_code(400);
        exit;
    }

    $model->moverTarefa($tarefa_id, $coluna);
    http_response_code(204);
    exit;
}

function excluirTarefa(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=projetos');
        exit;
    }

    $tarefa_id  = (int)($_POST['tarefa_id'] ?? 0);
    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    if ($tarefa_id > 0) {
        $model->excluirTarefa($tarefa_id);
    }

    header('Location: /modules/painel.php?mod=projeto_detalhe&id=' . $projeto_id);
    exit;
}

function atualizarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /modules/painel.php?mod=projetos');
        exit;
    }

    $id           = (int)($_POST['projeto_id'] ?? 0);
    $nomeProjeto  = trim($_POST['nome_projeto'] ?? '');
    // ler demais campos (tipo_projeto, cliente_id, datas, status, descricao)

    if ($id <= 0 || $nomeProjeto === '') {
        header('Location: /modules/painel.php?mod=projetos&erro=1');
        exit;
    }

    $model->atualizarProjeto($id, [
        'nome_projeto' => $nomeProjeto,
        // demais campos...
    ]);

    header('Location: /modules/painel.php?mod=projetos&ok=1');
    exit;
}

function getProjeto(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(null);
        exit;
    }
    $proj = $model->getProjetoById($id);
    echo json_encode($proj ?: null);
    exit;
}
