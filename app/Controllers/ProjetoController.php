<?php
// app/Controllers/ProjetoController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/ProjetoModel.php';

$projetoModel = new ProjetoModel($pdo);

$acao = $_REQUEST['acao'] ?? '';

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
        header('Location: /projetos');
        exit;
}

/* PROJETOS */

function criarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /projetos');
        exit;
    }

    $nome_projeto = trim($_POST['nome_projeto'] ?? '');
    $tipo_projeto = $_POST['tipo_projeto'] ?? 'outro';
    $cliente_id   = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== ''
        ? (int)$_POST['cliente_id']
        : null;
    $data_inicio  = $_POST['data_inicio'] ?: null;
    $data_entrega = $_POST['data_entrega'] ?: null;
    $status       = $_POST['status'] ?? 'planejado';
    $descricao    = trim($_POST['descricao'] ?? '');

    $oportunidade_id = (int)($_POST['oportunidade_id'] ?? 0);

    if ($nome_projeto === '') {
        header('Location: /projetos?erro=1');
        exit;
    }

    $novoId = $model->criarProjeto([
        'cliente_id'   => $cliente_id,
        'nome_projeto' => $nome_projeto,
        'tipo_projeto' => $tipo_projeto,
        'descricao'    => $descricao,
        'data_inicio'  => $data_inicio,
        'data_entrega' => $data_entrega,
        'status'       => $status,
    ]);

    if ($oportunidade_id > 0 && $novoId > 0) {
        require_once __DIR__ . '/../Models/OportunidadeModel.php';
        $opModel = new OportunidadeModel($GLOBALS['pdo']);
        $opModel->vincularProjeto($oportunidade_id, (int)$novoId);
    }

    if ($novoId > 0) {
        fd_audit_log('projeto.create', 'projeto', (int) $novoId, [
            'nome_projeto' => $nome_projeto,
            'cliente_id' => $cliente_id,
        ]);
    }

    header('Location: /projetos' . ($novoId > 0 ? '?criado=1' : '?erro=1'));
    exit;
}

function concluirProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /projetos');
        exit;
    }

    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    if ($projeto_id <= 0) {
        header('Location: /projetos?erro=1');
        exit;
    }

    $ok = $model->excluirProjeto($projeto_id);
    if ($ok) {
        fd_audit_log('projeto.complete', 'projeto', $projeto_id);
    }

    header('Location: /projetos' . ($ok ? '?concluido=1' : '?erro=1'));
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
        header('Location: /projetos');
        exit;
    }

    $projeto_id   = (int)($_POST['projeto_id'] ?? 0);
    $tarefa_id    = (int)($_POST['tarefa_id'] ?? 0);
    $titulo       = trim($_POST['titulo'] ?? '');
    $descricao    = trim($_POST['descricao'] ?? '');
    $coluna       = $_POST['coluna'] ?? 'backlog';
    $data_entrega = $_POST['data_entrega'] ?: null;

    if ($projeto_id <= 0 || $titulo === '') {
        header('Location: /projeto?id=' . $projeto_id . '&erro_tarefa=1');
        exit;
    }

    $ok = $model->salvarTarefa([
        'projeto_id'   => $projeto_id,
        'tarefa_id'    => $tarefa_id,
        'titulo'       => $titulo,
        'descricao'    => $descricao,
        'coluna'       => $coluna,
        'data_entrega' => $data_entrega,
    ]);

    if ($ok) {
        fd_audit_log($tarefa_id > 0 ? 'projeto.task.update' : 'projeto.task.create', 'projeto_tarefa', $tarefa_id > 0 ? $tarefa_id : null, [
            'projeto_id' => $projeto_id,
            'titulo' => $titulo,
            'coluna' => $coluna,
        ]);
    }

    header('Location: /projeto?id=' . $projeto_id . ($ok ? '&ok_tarefa=1' : '&erro_tarefa=1'));
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

    if ($model->moverTarefa($tarefa_id, $coluna)) {
        fd_audit_log('projeto.task.move', 'projeto_tarefa', $tarefa_id, [
            'coluna' => $coluna,
        ]);
        http_response_code(204);
        exit;
    }

    http_response_code(404);
    exit;
}

function excluirTarefa(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /projetos');
        exit;
    }

    $tarefa_id  = (int)($_POST['tarefa_id'] ?? 0);
    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    $ok = false;
    if ($tarefa_id > 0) {
        $ok = $model->excluirTarefa($tarefa_id);
        if ($ok) {
            fd_audit_log('projeto.task.delete', 'projeto_tarefa', $tarefa_id, [
                'projeto_id' => $projeto_id,
            ]);
        }
    }

    header('Location: /projeto?id=' . $projeto_id . ($ok ? '&tarefa_excluida=1' : '&erro_tarefa=1'));
    exit;
}

function atualizarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /projetos');
        exit;
    }

    $id          = (int)($_POST['projeto_id'] ?? 0);
    $nomeProjeto = trim($_POST['nome_projeto'] ?? '');
    $tipoProjeto = $_POST['tipo_projeto'] ?? 'outro';
    $clienteId   = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== ''
        ? (int)$_POST['cliente_id']
        : null;
    $dataInicio  = $_POST['data_inicio'] ?: null;
    $dataEntrega = $_POST['data_entrega'] ?: null;
    $status      = $_POST['status'] ?? 'planejado';
    $descricao   = trim($_POST['descricao'] ?? '');

    if ($id <= 0 || $nomeProjeto === '') {
        header('Location: /projetos?erro=1');
        exit;
    }

    $ok = $model->atualizarProjeto($id, [
        'nome_projeto' => $nomeProjeto,
        'tipo_projeto' => $tipoProjeto,
        'descricao' => $descricao,
        'cliente_id' => $clienteId,
        'data_inicio' => $dataInicio,
        'data_entrega' => $dataEntrega,
        'status' => $status,
    ]);

    if ($ok) {
        fd_audit_log('projeto.update', 'projeto', $id, [
            'nome_projeto' => $nomeProjeto,
            'status' => $status,
        ]);
    }

    header('Location: /projetos' . ($ok ? '?atualizado=1' : '?erro=1'));
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
