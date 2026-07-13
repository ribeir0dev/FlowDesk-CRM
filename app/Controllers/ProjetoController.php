<?php
// app/Controllers/ProjetoController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/BillingModel.php';
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

    case 'excluir':
        excluirProjeto($projetoModel);
        break;

    case 'getTarefa':
        carregarTarefa($projetoModel);
        break;

    case 'salvarTarefa':
        salvarTarefa($projetoModel);
        break;

    case 'autosaveTarefa':
        autosaveTarefa($projetoModel);
        break;

    case 'moverTarefa':
        moverTarefa($projetoModel);
        break;

    case 'excluirTarefa':
        excluirTarefa($projetoModel);
        break;

    case 'comentarTarefa':
        comentarTarefa($projetoModel);
        break;

    case 'atualizarComentarioTarefa':
        atualizarComentarioTarefa($projetoModel);
        break;

    case 'excluirComentarioTarefa':
        excluirComentarioTarefa($projetoModel);
        break;

    case 'atualizarProjeto':
        atualizarProjeto($projetoModel);
        break;

    case 'getProjeto':
        getProjeto($projetoModel);
        break;

    default:
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
}

/* PROJETOS */

function normalizarStatusProjeto(string $status): string
{
    $permitidos = ['planejado', 'em_andamento', 'pausado'];
    return in_array($status, $permitidos, true) ? $status : 'planejado';
}

function entregaPadraoProjeto(?string $dataInicio, ?string $dataEntrega): ?string
{
    if ($dataEntrega !== null && $dataEntrega !== '') {
        return $dataEntrega;
    }

    $base = $dataInicio ?: date('Y-m-d');
    $data = DateTimeImmutable::createFromFormat('Y-m-d', $base);

    if (!$data) {
        $data = new DateTimeImmutable('today');
    }

    return $data->modify('+3 days')->format('Y-m-d');
}

function criarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
    }

    $nome_projeto = trim($_POST['nome_projeto'] ?? '');
    $tipo_projeto = $_POST['tipo_projeto'] ?? 'outro';
    $cliente_id   = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== ''
        ? (int)$_POST['cliente_id']
        : null;
    $data_inicio  = $_POST['data_inicio'] ?: date('Y-m-d');
    $data_entrega = entregaPadraoProjeto($data_inicio, $_POST['data_entrega'] ?: null);
    $status       = normalizarStatusProjeto((string) ($_POST['status'] ?? 'planejado'));
    $descricao    = trim($_POST['descricao'] ?? '');

    $oportunidade_id = (int)($_POST['oportunidade_id'] ?? 0);

    if ($nome_projeto === '') {
        header('Location: ' . fd_base_path() . '/projetos?erro=1');
        exit;
    }

    $billingModel = new BillingModel($GLOBALS['pdo']);
    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
        header('Location: ' . fd_base_path() . '/projetos?erro=1');
        exit;
    }

    try {
        $gate = $billingModel->getResourceGate($workspaceId, 'projects');
        if (!$gate['allowed']) {
            $billingModel->releaseWorkspaceBillingLock($workspaceId);
            header('Location: ' . fd_base_path() . '/projetos?limit=projects');
            exit;
        }

        $novoId = $model->criarProjeto([
            'cliente_id'   => $cliente_id,
            'nome_projeto' => mb_substr($nome_projeto, 0, 180),
            'tipo_projeto' => mb_substr((string) $tipo_projeto, 0, 80),
            'descricao'    => mb_substr($descricao, 0, 4000),
            'data_inicio'  => $data_inicio,
            'data_entrega' => $data_entrega,
            'status'       => $status,
        ]);
    } finally {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
    }

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

    header('Location: ' . fd_base_path() . '/projetos' . ($novoId > 0 ? '?criado=1' : '?erro=1'));
    exit;
}

function concluirProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
    }

    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    if ($projeto_id <= 0) {
        header('Location: ' . fd_base_path() . '/projetos?erro=1');
        exit;
    }

    $ok = $model->excluirProjeto($projeto_id);
    if ($ok) {
        fd_audit_log('projeto.complete', 'projeto', $projeto_id);
    }

    header('Location: ' . fd_base_path() . '/projetos' . ($ok ? '?concluido=1' : '?erro=1'));
    exit;
}

function excluirProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
    }

    $projeto_id = (int)($_POST['projeto_id'] ?? 0);

    if ($projeto_id <= 0) {
        header('Location: ' . fd_base_path() . '/projetos?erro=1');
        exit;
    }

    $ok = $model->excluirProjeto($projeto_id);

    if ($ok) {
        fd_audit_log('projeto.delete', 'projeto', $projeto_id);
    }

    header('Location: ' . fd_base_path() . '/projetos' . ($ok ? '?excluido=1' : '?erro=1'));
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
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
    }

    [$payload, $redirectProjetoId, $invalid] = buildTaskPayloadFromRequest();

    if ($invalid || $redirectProjetoId <= 0) {
        header('Location: ' . fd_base_path() . '/projeto?id=' . $redirectProjetoId . '&erro_tarefa=1');
        exit;
    }

    $ok = $model->salvarTarefa($payload);

    if ($ok) {
        fd_audit_log(($payload['tarefa_id'] ?? 0) > 0 ? 'projeto.task.update' : 'projeto.task.create', 'projeto_tarefa', ($payload['tarefa_id'] ?? 0) > 0 ? (int) $payload['tarefa_id'] : null, [
            'projeto_id' => $payload['projeto_id'],
            'titulo' => $payload['titulo'],
            'coluna' => $payload['coluna'],
            'prioridade' => $payload['prioridade'],
        ]);
    }

    header('Location: ' . fd_base_path() . '/projeto?id=' . $redirectProjetoId . ($ok ? '&ok_tarefa=1' : '&erro_tarefa=1'));
    exit;
}

function autosaveTarefa(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    [$payload, , $invalid] = buildTaskPayloadFromRequest();

    if ($invalid || (int) ($payload['tarefa_id'] ?? 0) <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Dados da tarefa invalidos.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $ok = $model->salvarTarefa($payload);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Nao foi possivel salvar as alteracoes.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    fd_audit_log('projeto.task.autosave', 'projeto_tarefa', (int) $payload['tarefa_id'], [
        'projeto_id' => $payload['projeto_id'],
    ]);

    echo json_encode([
        'ok' => true,
        'saved_at' => fd_format_datetime(date('Y-m-d H:i:s')),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function buildTaskPayloadFromRequest(): array
{
    $projeto_id   = (int)($_POST['projeto_id'] ?? 0);
    $tarefa_id    = (int)($_POST['tarefa_id'] ?? 0);
    $titulo       = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 180);
    $descricao    = fd_sanitize_task_rich_text(mb_substr((string) ($_POST['descricao'] ?? ''), 0, 120000));
    $coluna       = $_POST['coluna'] ?? 'backlog';
    $data_entrega = $_POST['data_entrega'] ?: null;
    $prioridade   = $_POST['prioridade'] ?? 'media';
    $tags         = trim($_POST['tags'] ?? '');
    $checklistRaw = $_POST['checklist_json'] ?? '[]';

    $prioridadesValidas = ['baixa', 'media', 'alta', 'urgente'];
    if (!in_array($prioridade, $prioridadesValidas, true)) {
        $prioridade = 'media';
    }

    $tagsLista = array_values(array_filter(array_map(
        static fn($tag) => trim($tag),
        preg_split('/[,;\n]+/', $tags) ?: []
    )));
    $tagsLista = array_slice(array_unique($tagsLista), 0, 8);
    $tagsNormalizadas = implode(', ', $tagsLista);
    if (strlen((string) $checklistRaw) > 20000) {
        $checklistRaw = '[]';
    }
    $checklist = json_decode((string) $checklistRaw, true);
    if (!is_array($checklist)) {
        $checklist = [];
    }
    $checklist = array_values(array_filter(array_map(
        static function ($item): ?array {
            if (!is_array($item)) {
                return null;
            }

            $texto = trim((string) ($item['texto'] ?? ''));
            if ($texto === '') {
                return null;
            }

            return [
                'texto' => $texto,
                'concluido' => !empty($item['concluido']),
            ];
        },
        $checklist
    )));
    $membersRaw = strlen((string) ($_POST['members_json'] ?? '[]')) <= 4000 ? ($_POST['members_json'] ?? '[]') : '[]';
    $attachmentsRaw = strlen((string) ($_POST['attachments_json'] ?? '[]')) <= 12000 ? ($_POST['attachments_json'] ?? '[]') : '[]';
    $members = json_decode((string) $membersRaw, true);
    if (!is_array($members)) {
        $members = [];
    }
    $attachments = json_decode((string) $attachmentsRaw, true);
    if (!is_array($attachments)) {
        $attachments = [];
    }

    return [[
        'projeto_id'   => $projeto_id,
        'tarefa_id'    => $tarefa_id,
        'titulo'       => $titulo,
        'descricao'    => $descricao,
        'coluna'       => $coluna,
        'data_entrega' => $data_entrega,
        'prioridade'   => $prioridade,
        'tags'         => $tagsNormalizadas,
        'checklist'    => $checklist,
        'members'      => $members,
        'attachments'  => $attachments,
    ], $projeto_id, ($projeto_id <= 0 || $titulo === '')];
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
        header('Location: ' . fd_base_path() . '/projetos');
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

    header('Location: ' . fd_base_path() . '/projeto?id=' . $projeto_id . ($ok ? '&tarefa_excluida=1' : '&erro_tarefa=1'));
    exit;
}

function comentarTarefa(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($tarefaId <= 0 || $comentario === '' || $userId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Comentario invalido.']);
        exit;
    }

    $comentario = mb_substr($comentario, 0, 4000);
    $saved = $model->adicionarComentarioTarefa($tarefaId, $userId, $comentario);

    if (!$saved) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Nao foi possivel salvar o comentario.']);
        exit;
    }

    fd_audit_log('projeto.task.comment', 'projeto_tarefa', $tarefaId, [
        'user_id' => $userId,
    ]);

    echo json_encode(['ok' => true, 'comment' => $saved], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function atualizarComentarioTarefa(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($commentId <= 0 || $comentario === '' || $userId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Comentario invalido.']);
        exit;
    }

    $comment = $model->getComentarioTarefaById($commentId);
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Comentario nao encontrado.']);
        exit;
    }

    $role = (string) (fd_current_workspace_role() ?? '');
    $canManage = in_array($role, ['owner', 'admin'], true) || (int) ($comment['user_id'] ?? 0) === $userId;
    if (!$canManage) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Voce nao pode editar este comentario.']);
        exit;
    }

    $comentario = mb_substr($comentario, 0, 4000);
    $updated = $model->atualizarComentarioTarefa($commentId, $comentario);
    if (!$updated) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Nao foi possivel atualizar o comentario.']);
        exit;
    }

    echo json_encode(['ok' => true, 'comment' => $updated], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function excluirComentarioTarefa(ProjetoModel $model): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($commentId <= 0 || $userId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Comentario invalido.']);
        exit;
    }

    $comment = $model->getComentarioTarefaById($commentId);
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Comentario nao encontrado.']);
        exit;
    }

    $role = (string) (fd_current_workspace_role() ?? '');
    $canManage = in_array($role, ['owner', 'admin'], true) || (int) ($comment['user_id'] ?? 0) === $userId;
    if (!$canManage) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Voce nao pode excluir este comentario.']);
        exit;
    }

    if (!$model->excluirComentarioTarefa($commentId)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Nao foi possivel excluir o comentario.']);
        exit;
    }

    echo json_encode(['ok' => true, 'comment_id' => $commentId], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function atualizarProjeto(ProjetoModel $model): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/projetos');
        exit;
    }

    $id          = (int)($_POST['projeto_id'] ?? 0);
    $nomeProjeto = trim($_POST['nome_projeto'] ?? '');
    $tipoProjeto = $_POST['tipo_projeto'] ?? 'outro';
    $clienteId   = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== ''
        ? (int)$_POST['cliente_id']
        : null;
    $dataInicio  = $_POST['data_inicio'] ?: date('Y-m-d');
    $dataEntrega = entregaPadraoProjeto($dataInicio, $_POST['data_entrega'] ?: null);
    $status      = normalizarStatusProjeto((string) ($_POST['status'] ?? 'planejado'));
    $descricao   = trim($_POST['descricao'] ?? '');

    if ($id <= 0 || $nomeProjeto === '') {
        header('Location: ' . fd_base_path() . '/projetos?erro=1');
        exit;
    }

    $ok = $model->atualizarProjeto($id, [
        'nome_projeto' => $nomeProjeto,
        'tipo_projeto' => mb_substr((string) $tipoProjeto, 0, 80),
        'descricao' => mb_substr($descricao, 0, 4000),
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

    header('Location: ' . fd_base_path() . '/projetos' . ($ok ? '?atualizado=1' : '?erro=1'));
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
