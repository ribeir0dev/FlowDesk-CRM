<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/env.php';
fd_load_env();

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}

require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/admin_auth.php';
require_once __DIR__ . '/../Models/AdminAccountModel.php';

$action = (string) ($_REQUEST['acao'] ?? '');
$model = new AdminAccountModel($pdo);

switch ($action) {
    case 'login':
        adminHandleLogin();
        break;

    case 'logout':
        adminHandleLogout();
        break;

    case 'updateAccount':
        adminHandleUpdateAccount($model);
        break;

    default:
        header('Location: ' . fd_base_path() . '/admin');
        exit;
}

function adminHandleLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        header('Location: ' . fd_base_path() . '/admin/login?erro=seguranca');
        exit;
    }

    fd_admin_ensure_session();

    $lockUntil = (int) ($_SESSION['flowdesk_admin_lock_until'] ?? 0);
    if ($lockUntil > time()) {
        header('Location: ' . fd_base_path() . '/admin/login?erro=bloqueado');
        exit;
    }

    $expectedLogin = trim((string) (getenv('ADMIN_LOGIN') ?: ''));
    $expectedPassword = (string) (getenv('ADMIN_PASSWORD') ?: '');
    if ($expectedLogin === '' || $expectedPassword === '') {
        error_log('[FlowDesk][AdminAuth] ADMIN_LOGIN ou ADMIN_PASSWORD nao configurado.');
        header('Location: ' . fd_base_path() . '/admin/login?erro=configuracao');
        exit;
    }

    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $loginMatches = hash_equals($expectedLogin, $login);
    $passwordMatches = str_starts_with($expectedPassword, '$2')
        ? password_verify($password, $expectedPassword)
        : hash_equals($expectedPassword, $password);

    if (!$loginMatches || !$passwordMatches) {
        $attempts = (int) ($_SESSION['flowdesk_admin_login_attempts'] ?? 0) + 1;
        $_SESSION['flowdesk_admin_login_attempts'] = $attempts;

        if ($attempts >= 5) {
            $_SESSION['flowdesk_admin_lock_until'] = time() + 900;
            $_SESSION['flowdesk_admin_login_attempts'] = 0;
        }

        usleep(350000);
        header('Location: ' . fd_base_path() . '/admin/login?erro=credenciais');
        exit;
    }

    fd_admin_login($expectedLogin);
    header('Location: ' . fd_base_path() . '/admin');
    exit;
}

function adminHandleLogout(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        header('Location: ' . fd_base_path() . '/admin');
        exit;
    }

    fd_admin_logout();
    header('Location: ' . fd_base_path() . '/admin/login?logout=1');
    exit;
}

function adminHandleUpdateAccount(AdminAccountModel $model): void
{
    fd_admin_require_auth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        fd_admin_flash('danger', 'A sessao expirou. Atualize a pagina e tente novamente.');
        header('Location: ' . fd_base_path() . '/admin');
        exit;
    }

    $workspaceId = (int) ($_POST['account_id'] ?? 0);
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $billingCycle = trim((string) ($_POST['billing_cycle'] ?? 'monthly'));
    $expiresAt = trim((string) ($_POST['expires_at'] ?? ''));

    if ($workspaceId <= 0 || $planId <= 0 || !in_array($billingCycle, ['monthly', 'annual'], true)) {
        fd_admin_flash('danger', 'Os dados da assinatura sao invalidos.');
        header('Location: ' . fd_base_path() . '/admin');
        exit;
    }

    if ($model->updateSubscription($workspaceId, $planId, $billingCycle, $expiresAt)) {
        fd_admin_flash('success', 'Plano e vencimento atualizados com sucesso.');
    } else {
        fd_admin_flash('danger', 'Nao foi possivel atualizar essa conta.');
    }

    header('Location: ' . fd_base_path() . '/admin/contas/' . $workspaceId . '/editar');
    exit;
}
