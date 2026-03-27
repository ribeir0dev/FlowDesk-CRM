<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/WorkspaceInviteModel.php';

$inviteModel = new WorkspaceInviteModel($pdo);
$acao = $_REQUEST['acao'] ?? 'listar';

switch ($acao) {
    case 'criar':
        criarInvite($inviteModel);
        break;

    case 'revogar':
        revogarInvite($inviteModel);
        break;

    case 'aceitar':
        aceitarInvite($inviteModel);
        break;

    default:
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
}

function criarInvite(WorkspaceInviteModel $inviteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'operacional');
    $allowedRoles = ['admin', 'operacional', 'financeiro', 'viewer'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowedRoles, true)) {
        header('Location: ' . fd_base_path() . '/configuracoes?invite=erro');
        exit;
    }

    $invite = $inviteModel->criarInvite($email, $role, (int) ($_SESSION['user_id'] ?? 0));
    if (!$invite) {
        header('Location: ' . fd_base_path() . '/configuracoes?invite=duplicado');
        exit;
    }

    fd_audit_log('workspace.invite.create', 'workspace_invite', (int) ($invite['id'] ?? 0), [
        'email' => $email,
        'role' => $role,
    ]);

    header('Location: ' . fd_base_path() . '/configuracoes?invite=ok');
    exit;
}

function revogarInvite(WorkspaceInviteModel $inviteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $inviteId = (int) ($_POST['invite_id'] ?? 0);
    if ($inviteId <= 0) {
        header('Location: ' . fd_base_path() . '/configuracoes?invite=erro');
        exit;
    }

    $ok = $inviteModel->revogarInvite($inviteId);
    if ($ok) {
        fd_audit_log('workspace.invite.revoke', 'workspace_invite', $inviteId);
    }
    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'invite=revogado' : 'invite=erro'));
    exit;
}

function aceitarInvite(WorkspaceInviteModel $inviteModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/login');
        exit;
    }

    fd_require_workspace();

    $token = trim($_POST['token'] ?? '');
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $email = trim($_SESSION['user_email'] ?? '');

    if ($token === '' || $userId <= 0 || $email === '') {
        header('Location: ' . fd_base_path() . '/login?erro=invalid');
        exit;
    }

    $acceptedWorkspace = $inviteModel->aceitarInvite($token, $userId, $email);
    if ($acceptedWorkspace) {
        $_SESSION['current_workspace_id'] = (int) ($acceptedWorkspace['workspace_id'] ?? 0);
        $_SESSION['current_workspace_nome'] = $acceptedWorkspace['workspace_nome'] ?? null;
        $_SESSION['current_workspace_role'] = $acceptedWorkspace['role'] ?? 'viewer';

        fd_audit_log('workspace.invite.accept', 'workspace_member', $userId, [
            'email' => $email,
            'workspace_id' => (int) ($acceptedWorkspace['workspace_id'] ?? 0),
            'role' => $acceptedWorkspace['role'] ?? 'viewer',
        ]);

        header('Location: ' . fd_base_path() . '/configuracoes?invite_accepted=1');
        exit;
    }

    header('Location: ' . fd_base_path() . '/convite?token=' . urlencode($token) . '&erro=accept');
    exit;
}
