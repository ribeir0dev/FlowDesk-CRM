<?php
// app/Controllers/AuthController.php

ini_set('display_errors', 1); // REMOVER EM PRODUCAO
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/AuthModel.php';

$authModel = new AuthModel($pdo);
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {
    case 'login':
        handleLogin($authModel);
        break;

    case 'logout':
        handleLogout();
        break;

    case 'register':
        handleRegister($authModel);
        break;

    case 'switchWorkspace':
        handleSwitchWorkspace($authModel);
        break;

    case 'forgotPassword':
        handleForgotPassword($authModel);
        break;

    case 'resetPassword':
        handleResetPassword($authModel);
        break;

    case 'verifyEmail':
        handleVerifyEmail($authModel);
        break;

    case 'updateProfile':
        handleUpdateProfile($authModel);
        break;

    case 'updatePreferences':
        handleUpdatePreferences($authModel);
        break;

    case 'updateModulePreferences':
        handleUpdateModulePreferences($authModel);
        break;

    default:
        header('Location: ' . fd_base_path() . '/');
        exit;
}

function handleLogin(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/login');
        exit;
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        exit('Erro de seguranca: CSRF');
    }

    $user_or_email = trim($_POST['user_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $lembrar = isset($_POST['remember']);
    $redirect = trim((string) ($_POST['redirect'] ?? ''));

    if ($user_or_email === '' || $password === '') {
        header('Location: ' . fd_base_path() . '/login?erro=campos');
        exit;
    }

    $user = $authModel->findUserByLogin($user_or_email);

    if ($user && password_verify($password, $user['senha'])) {
        if (empty($user['email_verificado_em'])) {
            $verification = $authModel->createEmailVerification((int) $user['id'], (string) $user['email']);
            $_SESSION['auth_flash_error'] = 'email_nao_confirmado';
            $_SESSION['auth_flash_link'] = $verification['verification_url'] ?? null;
            header('Location: ' . fd_base_path() . '/login?erro=email_nao_confirmado');
            exit;
        }

        if (empty($user['workspace_id'])) {
            header('Location: ' . fd_base_path() . '/login?erro=workspace');
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['foto_perfil'] ?? null;
        $_SESSION['user_theme'] = $user['preferred_theme'] ?? 'dark';
        $_SESSION['user_locale'] = $user['preferred_locale'] ?? 'pt-BR';
        $_SESSION['user_timezone'] = $user['preferred_timezone'] ?? 'America/Sao_Paulo';
        $_SESSION['user_sidebar_modules'] = isset($user['sidebar_modules_json']) && $user['sidebar_modules_json'] !== null
            ? (json_decode((string) $user['sidebar_modules_json'], true) ?: [])
            : null;
        fd_mark_login_session($lembrar);
        setCurrentWorkspaceSession($user);
        fd_audit_log('auth.login', 'usuario', (int) $user['id'], [
            'workspace_id' => (int) ($user['workspace_id'] ?? 0),
            'remember' => $lembrar,
        ]);

        if ($lembrar) {
            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/');
        }

        $basePath = fd_base_path();
        $safeRedirect = fd_default_workspace_path((string) ($user['workspace_role'] ?? 'owner'));

        if ($redirect !== '' && str_starts_with($redirect, $basePath . '/')) {
            $safeRedirect = $redirect;
        }

        header('Location: ' . $safeRedirect);
        exit;
    }

    header('Location: ' . fd_base_path() . '/login?erro=invalid');
    exit;
}

function handleSwitchWorkspace(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/dashboard');
        exit;
    }

    fd_require_workspace();

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $workspaceId = (int) ($_POST['workspace_id'] ?? 0);
    $redirect = trim((string) ($_POST['redirect'] ?? ''));

    if ($userId <= 0 || $workspaceId <= 0) {
        header('Location: ' . fd_base_path() . '/dashboard');
        exit;
    }

    $workspace = $authModel->buscarWorkspaceDoUsuario($userId, $workspaceId);
    if (!$workspace) {
        header('Location: ' . fd_base_path() . '/dashboard?workspace=erro');
        exit;
    }

    setCurrentWorkspaceSession($workspace);
    fd_audit_log('auth.workspace.switch', 'workspace', $workspaceId, [
        'role' => $workspace['role'] ?? null,
    ]);

        unset($_SESSION['current_cliente_id']);

        $basePath = fd_base_path();
        $safeRedirect = fd_default_workspace_path((string) ($workspace['role'] ?? 'viewer'));

    if ($redirect !== '' && str_starts_with($redirect, $basePath . '/')) {
        $safeRedirect = $redirect;
    }

    header('Location: ' . $safeRedirect);
    exit;
}

function handleLogout(): void
{
    fd_ensure_session();
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $workspaceId = isset($_SESSION['current_workspace_id']) ? (int) $_SESSION['current_workspace_id'] : null;
    if ($userId && $workspaceId) {
        fd_audit_log('auth.logout', 'usuario', $userId, [
            'workspace_id' => $workspaceId,
        ]);
    }
    fd_destroy_authenticated_session();

    header('Location: ' . fd_base_path() . '/');
    exit;
}

function handleRegister(AuthModel $authModel): void
{
    header('Content-Type: application/json');

    $erros = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'errors' => ['Requisicao invalida.']
        ]);
        exit;
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        echo json_encode([
            'success' => false,
            'errors' => ['Erro de seguranca. Atualize a pagina e tente novamente.']
        ]);
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $workspaceNome = trim($_POST['workspace_nome'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $conf_senha = $_POST['conf_senha'] ?? '';

    if ($nome === '') {
        $erros[] = 'Informe o nome.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Email invalido.';
    }

    if (strlen($senha) < 8 || !preg_match('/\d/', $senha) || !preg_match('/[^a-zA-Z0-9]/', $senha)) {
        $erros[] = 'A senha precisa ter pelo menos 8 caracteres, 1 numero e 1 caractere especial.';
    }

    if ($senha !== $conf_senha) {
        $erros[] = 'As senhas nao conferem.';
    }

    if ($authModel->emailExists($email)) {
        $erros[] = 'E-mail ja cadastrado.';
    }

    if (!$erros) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $createdUser = $authModel->createUser($nome, $email, $hash, $workspaceNome);

        if ($createdUser) {
            echo json_encode([
                'success' => true,
                'message' => 'Conta criada com sucesso! Confirme seu e-mail para ativar o acesso. <a href="' . htmlspecialchars((string) ($createdUser['verification_url'] ?? '#'), ENT_QUOTES) . '">Confirmar e-mail</a>'
            ]);
            exit;
        }

        $erros[] = 'Erro ao salvar usuario.';
    }

    echo json_encode([
        'success' => false,
        'errors' => $erros
    ]);
    exit;
}

function setCurrentWorkspaceSession(array $workspace): void
{
    $_SESSION['current_workspace_id'] = (int) ($workspace['workspace_id'] ?? 0);
    $_SESSION['current_workspace_nome'] = $workspace['workspace_nome'] ?? null;
    $_SESSION['current_workspace_role'] = $workspace['workspace_role']
        ?? $workspace['role']
        ?? 'owner';
}

function handleForgotPassword(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/esqueci-senha');
        exit;
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header('Location: ' . fd_base_path() . '/esqueci-senha?erro=csrf');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . fd_base_path() . '/esqueci-senha?erro=email');
        exit;
    }

    $user = $authModel->findUserByEmail($email);
    if (!$user) {
        header('Location: ' . fd_base_path() . '/esqueci-senha?ok=1');
        exit;
    }

    $reset = $authModel->createPasswordReset((int) $user['id'], $user['email']);
    if (!$reset) {
        header('Location: ' . fd_base_path() . '/esqueci-senha?erro=reset');
        exit;
    }

    header('Location: ' . fd_base_path() . '/esqueci-senha?ok=1&link=' . urlencode((string) ($reset['reset_url'] ?? '')));
    exit;
}

function handleResetPassword(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/esqueci-senha');
        exit;
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header('Location: ' . fd_base_path() . '/esqueci-senha?erro=csrf');
        exit;
    }

    $token = trim((string) ($_POST['token'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $confSenha = (string) ($_POST['conf_senha'] ?? '');

    if ($token === '') {
        header('Location: ' . fd_base_path() . '/esqueci-senha?erro=token');
        exit;
    }

    if (strlen($senha) < 8 || $senha !== $confSenha) {
        header('Location: ' . fd_base_path() . '/redefinir-senha?token=' . urlencode($token) . '&erro=senha');
        exit;
    }

    $ok = $authModel->consumePasswordReset($token, password_hash($senha, PASSWORD_DEFAULT));
    if (!$ok) {
        header('Location: ' . fd_base_path() . '/redefinir-senha?token=' . urlencode($token) . '&erro=token');
        exit;
    }

    $resetData = $authModel->findPasswordResetByToken($token);
    if ($resetData) {
        fd_audit_log('auth.password.reset', 'usuario', (int) ($resetData['user_id'] ?? 0), [
            'email' => $resetData['email'] ?? null,
        ]);
    }

    header('Location: ' . fd_base_path() . '/login?reset=ok');
    exit;
}

function handleUpdateProfile(AuthModel $authModel): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $userIdForm = (int) ($_POST['user_id'] ?? 0);
    $userIdSess = (int) $_SESSION['user_id'];

    if ($userIdForm !== $userIdSess) {
        header('Location: ' . fd_base_path() . '/configuracoes?erro=1');
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '') {
        header('Location: ' . fd_base_path() . '/configuracoes?erro=1');
        exit;
    }

    $fotoPath = null;
    if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $dir = __DIR__ . '/../../public/uploads/avatars/';

            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $nomeArq = 'user_' . $userIdSess . '.' . $ext;
            $destino = $dir . $nomeArq;

            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)) {
                $fotoPath = '/uploads/avatars/' . $nomeArq;
            }
        }
    }

    $senhaHash = null;
    if ($senha !== '') {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    }

    $authModel->updateUser($userIdSess, $nome, $email, $senhaHash, $fotoPath);

    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_email'] = $email;
    if ($fotoPath) {
        $_SESSION['user_avatar'] = $fotoPath;
    }

    header('Location: ' . fd_base_path() . '/configuracoes?ok=1');
    exit;
}


function handleUpdatePreferences(AuthModel $authModel): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $theme = trim((string) ($_POST['preferred_theme'] ?? 'dark'));
    $locale = trim((string) ($_POST['preferred_locale'] ?? 'pt-BR'));
    $timezone = trim((string) ($_POST['preferred_timezone'] ?? 'America/Sao_Paulo'));

    $allowedThemes = ['dark', 'light'];
    $allowedLocales = ['pt-BR', 'en-US', 'es-ES'];
    $allowedTimezones = ['America/Sao_Paulo', 'America/New_York', 'Europe/Lisbon'];

    if (!in_array($theme, $allowedThemes, true)
        || !in_array($locale, $allowedLocales, true)
        || !in_array($timezone, $allowedTimezones, true)) {
        header('Location: ' . fd_base_path() . '/configuracoes?pref=erro');
        exit;
    }

    $ok = $authModel->updateUserPreferences($userId, $theme, $locale, $timezone);

    if ($ok) {
        $_SESSION['user_theme'] = $theme;
        $_SESSION['user_locale'] = $locale;
        $_SESSION['user_timezone'] = $timezone;
    }

    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'pref=ok' : 'pref=erro'));
    exit;
}

function handleUpdateModulePreferences(AuthModel $authModel): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
        header('Location: ' . fd_base_path() . '/configuracoes#modulos');
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $allowedModules = ['dashboard', 'clientes', 'pipeline', 'projetos', 'orcamentos', 'financeiro', 'hospedagens', 'codigos'];
    $selectedModules = $_POST['modules'] ?? [];

    if (!is_array($selectedModules)) {
        $selectedModules = [];
    }

    $selectedModules = array_values(array_filter($selectedModules, static fn ($module) => in_array((string) $module, $allowedModules, true)));

    $ok = $authModel->updateUserModulePreferences($userId, $selectedModules);

    if ($ok) {
        $_SESSION['user_sidebar_modules'] = $selectedModules;
    }

    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'modules=ok' : 'modules=erro') . '#modulos');
    exit;
}

function handleVerifyEmail(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Location: ' . fd_base_path() . '/login');
        exit;
    }

    $token = trim((string) ($_GET['token'] ?? ''));
    if ($token === '') {
        header('Location: ' . fd_base_path() . '/login?erro=confirmacao_token');
        exit;
    }

    $status = $authModel->emailVerificationStatus($token);
    if ($status !== 'ok') {
        header('Location: ' . fd_base_path() . '/login?erro=' . match ($status) {
            'used' => 'confirmacao_ja_usada',
            'expired' => 'confirmacao_expirada',
            default => 'confirmacao_token',
        });
        exit;
    }

    $verification = $authModel->findEmailVerificationByToken($token);
    $ok = $authModel->consumeEmailVerification($token);
    if (!$ok) {
        header('Location: ' . fd_base_path() . '/login?erro=confirmacao_token');
        exit;
    }

    if ($verification) {
        fd_audit_log('auth.email.verify', 'usuario', (int) ($verification['user_id'] ?? 0), [
            'email' => $verification['email'] ?? null,
        ]);
    }

    header('Location: ' . fd_base_path() . '/login?confirmacao=ok');
    exit;
}
