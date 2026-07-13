<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/OrcamentoModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';

$code = strtolower(trim((string) ($_GET['codigo'] ?? '')));
$action = (string) ($_REQUEST['acao'] ?? '');

function public_proposal_redirect(string $code, array $params = []): void
{
    $base = function_exists('fd_base_path') ? fd_base_path() : '';
    $query = $params ? '?' . http_build_query($params) : '';
    header('Location: ' . $base . '/proposta/' . rawurlencode($code) . $query);
    exit;
}

function public_proposal_notify_creator(PDO $pdo, OrcamentoModel $model, array $proposal, string $type, string $title, string $message): void
{
    $workspaceId = (int) ($proposal['workspace_id'] ?? 0);
    $proposalId = (int) ($proposal['id'] ?? 0);
    if ($workspaceId <= 0 || $proposalId <= 0) {
        return;
    }

    $creatorId = $model->buscarCriadorId($workspaceId, $proposalId);
    $base = function_exists('fd_base_path') ? fd_base_path() : '';

    (new NotificationModel($pdo))->criar([
        'workspace_id' => $workspaceId,
        'user_id' => $creatorId,
        'tipo' => $type,
        'titulo' => $title,
        'mensagem' => $message,
        'entidade' => 'orcamento',
        'entidade_id' => $proposalId,
        'url' => $base . '/orcamentos?selecionado=' . $proposalId,
        'payload' => [
            'codigo' => (string) ($proposal['codigo'] ?? ''),
            'cliente' => (string) ($proposal['cliente_nome'] ?? ''),
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !preg_match('/^[a-f0-9]{24}$/', $code)) {
    public_proposal_redirect($code, ['erro' => 'acao']);
}

if (!csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
    public_proposal_redirect($code, ['erro' => 'csrf']);
}

$model = new OrcamentoModel($pdo);

if ($action === 'confirmar') {
    $currentProposal = $model->buscarPorCodigoPublico($code);
    if ($currentProposal && (string) ($currentProposal['status'] ?? '') === 'Aprovada') {
        public_proposal_redirect($code, ['confirmado' => '1']);
    }

    $proposal = $model->aprovarPorCodigoPublico($code);
    if (!$proposal) {
        public_proposal_redirect($code, ['erro' => 'confirmar']);
    }

    $cliente = (string) ($proposal['cliente_nome'] ?? 'Cliente');
    $codigo = (string) ($proposal['codigo'] ?? 'proposta');
    public_proposal_notify_creator(
        $pdo,
        $model,
        $proposal,
        'orcamento_confirmado',
        'Proposta confirmada',
        "{$cliente} confirmou a proposta {$codigo}."
    );

    public_proposal_redirect($code, ['confirmado' => '1']);
}

if ($action === 'ajustes') {
    $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
    if (mb_strlen($mensagem) < 5) {
        public_proposal_redirect($code, ['erro' => 'ajustes_vazio']);
    }

    $proposal = $model->solicitarAjustesPorCodigoPublico($code, $mensagem);
    if (!$proposal) {
        public_proposal_redirect($code, ['erro' => 'ajustes']);
    }

    $cliente = (string) ($proposal['cliente_nome'] ?? 'Cliente');
    $codigo = (string) ($proposal['codigo'] ?? 'proposta');
    public_proposal_notify_creator(
        $pdo,
        $model,
        $proposal,
        'orcamento_ajustes',
        'Alterações solicitadas',
        "{$cliente} solicitou ajustes na proposta {$codigo}."
    );

    public_proposal_redirect($code, ['ajustes' => '1']);
}

public_proposal_redirect($code, ['erro' => 'acao']);
