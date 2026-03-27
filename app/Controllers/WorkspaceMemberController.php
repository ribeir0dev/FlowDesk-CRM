<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/WorkspaceMemberModel.php';

$memberModel = new WorkspaceMemberModel($pdo);
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {
    case 'atualizar_papel':
        atualizarPapel($memberModel);
        break;

    case 'remover':
        removerMembro($memberModel);
        break;

    default:
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
}

function atualizarPapel(WorkspaceMemberModel $memberModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $memberId = (int) ($_POST['member_id'] ?? 0);
    $role = trim((string) ($_POST['role'] ?? ''));
    $actorUserId = (int) ($_SESSION['user_id'] ?? 0);
    $actorRole = trim((string) ($_SESSION['current_workspace_role'] ?? 'viewer'));

    if ($memberId <= 0 || $actorUserId <= 0 || $role === '') {
        header('Location: ' . fd_base_path() . '/configuracoes?member_role=erro');
        exit;
    }

    $ok = $memberModel->atualizarPapel($memberId, $role, $actorUserId, $actorRole);
    if ($ok) {
        fd_audit_log('workspace.member.role_update', 'workspace_member', $memberId, [
            'role' => $role,
            'actor_role' => $actorRole,
        ]);
    }
    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'member_role=ok' : 'member_role=erro'));
    exit;
}

function removerMembro(WorkspaceMemberModel $memberModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $memberId = (int) ($_POST['member_id'] ?? 0);
    $actorUserId = (int) ($_SESSION['user_id'] ?? 0);
    $actorRole = trim((string) ($_SESSION['current_workspace_role'] ?? 'viewer'));

    if ($memberId <= 0 || $actorUserId <= 0) {
        header('Location: ' . fd_base_path() . '/configuracoes?member_remove=erro');
        exit;
    }

    $ok = $memberModel->removerMembro($memberId, $actorUserId, $actorRole);
    if ($ok) {
        fd_audit_log('workspace.member.remove', 'workspace_member', $memberId, [
            'actor_role' => $actorRole,
        ]);
    }
    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'member_remove=ok' : 'member_remove=erro'));
    exit;
}
