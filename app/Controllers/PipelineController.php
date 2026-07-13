<?php
// app/Controllers/PipelineController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
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
        header('Location: ' . fd_base_path() . '/pipeline');
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
    if ($ok) {
        fd_audit_log('pipeline.move', 'oportunidade', $id, [
            'funil_estagio_id' => $novoEstagio,
        ]);
    }
    echo json_encode(['ok' => $ok]);
    exit;
}

function criarOportunidade(OportunidadeModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $data = normalizarPayloadOportunidade($_POST);
    if ($data['cliente_id'] <= 0 || $data['funil_estagio_id'] <= 0 || $data['titulo'] === '') {
        header('Location: ' . fd_base_path() . '/pipeline?erro=1');
        exit;
    }

    $id = $model->criar($data);
    if ($id > 0) {
        fd_audit_log('pipeline.create', 'oportunidade', $id, [
            'titulo' => $data['titulo'],
            'cliente_id' => (int) $data['cliente_id'],
        ]);
    }
    header('Location: ' . fd_base_path() . '/pipeline' . ($id > 0 ? '?ok=1' : '?erro=1'));
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
        header('Location: ' . fd_base_path() . '/pipeline?erro=1');
        exit;
    }

    $data = normalizarPayloadOportunidade($_POST);
    if ($data['cliente_id'] <= 0 || $data['funil_estagio_id'] <= 0 || $data['titulo'] === '') {
        header('Location: ' . fd_base_path() . '/pipeline?erro=1');
        exit;
    }

    $ok = $model->atualizar($id, $data);
    if ($ok) {
        fd_audit_log('pipeline.update', 'oportunidade', $id, [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
        ]);
    }
    header('Location: ' . fd_base_path() . '/pipeline' . ($ok ? '?ok=1' : '?erro=1'));
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
        $ok = $model->marcarGanha($id, $ganhoId);
        if ($ok) {
            fd_audit_log('pipeline.mark_won', 'oportunidade', $id, [
                'funil_estagio_id' => $ganhoId,
            ]);
        }
        header('Location: ' . fd_base_path() . '/pipeline' . ($ok ? '?ok=1' : '?erro=1'));
        exit;
    }
        header('Location: ' . fd_base_path() . '/pipeline?erro=1');
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
        $ok = $model->marcarPerdida($id, $perdidoId, $motivo);
        if ($ok) {
            fd_audit_log('pipeline.mark_lost', 'oportunidade', $id, [
                'funil_estagio_id' => $perdidoId,
                'motivo_perda' => $motivo,
            ]);
        }
        header('Location: ' . fd_base_path() . '/pipeline' . ($ok ? '?ok=1' : '?erro=1'));
        exit;
    }
    header('Location: ' . fd_base_path() . '/pipeline?erro=1');
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

    $ok = $model->excluir($id);
    if ($ok) {
        fd_audit_log('pipeline.delete', 'oportunidade', $id);
    }
    header('Location: ' . fd_base_path() . '/pipeline' . ($ok ? '?ok=1' : '?erro=1'));
    exit;
}

function normalizarPayloadOportunidade(array $data): array
{
    return [
        'cliente_id' => (int) ($data['cliente_id'] ?? 0),
        'projeto_id' => isset($data['projeto_id']) && $data['projeto_id'] !== '' ? (int) $data['projeto_id'] : null,
        'funil_estagio_id' => (int) ($data['funil_estagio_id'] ?? 0),
        'titulo' => mb_substr(trim((string) ($data['titulo'] ?? '')), 0, 160),
        'valor_previsto' => (float) ($data['valor_previsto'] ?? 0),
        'probabilidade' => max(0, min(100, (int) ($data['probabilidade'] ?? 0))),
        'origem_lead' => mb_substr(trim((string) ($data['origem_lead'] ?? '')), 0, 120),
        'responsavel' => mb_substr(trim((string) ($data['responsavel'] ?? '')), 0, 120),
        'data_prevista_fechamento' => trim((string) ($data['data_prevista_fechamento'] ?? '')),
        'motivo_perda' => mb_substr(trim((string) ($data['motivo_perda'] ?? '')), 0, 500),
        'observacoes' => mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 4000),
    ];
}
