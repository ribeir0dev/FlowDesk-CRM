<?php
// actions/AuthController.php

ini_set('display_errors', 1); // REMOVER EM PRODUÇÃO
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../Models/AuthModel.php';

$authModel = new AuthModel($pdo);

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

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

    case 'updateProfile':
        handleUpdateProfile($authModel);
        break;

    default:
        // Se não reconhecer a ação, volta para o login
        header('Location: /index.php');
        exit;
}

/**
 * LOGIN
 */
function handleLogin(AuthModel $authModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /index.php');
        exit;
    }

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        exit('Erro de segurança: CSRF');
    }

    $user_or_email = trim($_POST['user_or_email'] ?? '');
    $password      = $_POST['password'] ?? '';
    $lembrar       = isset($_POST['remember']);

    if ($user_or_email === '' || $password === '') {
        exit('Preencha todos os campos.');
    }

    $user = $authModel->findUserByLogin($user_or_email);

    if ($user && password_verify($password, $user['senha'])) {
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_nome']  = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_plano'] = $user['plano'] ?? null;
        $_SESSION['user_avatar'] = $user['foto_perfil'] ?? null;

        if ($lembrar) {
            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/');
        }

        header('Location: /modules/painel.php');
        exit;
    }

    echo 'Login ou senha inválido.';
    exit;
}

/**
 * LOGOUT
 */
function handleLogout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    header('Location: /index.php');
    exit;
}

/**
 * REGISTRO
 * (equivalente ao criar_conta.php – retorna JSON)
 */
function handleRegister(AuthModel $authModel): void
{
    header('Content-Type: application/json');

    $erros = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'errors'  => ['Requisição inválida.']
        ]);
        exit;
    }

    $nome       = trim($_POST['nome'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $senha      = $_POST['senha'] ?? '';
    $conf_senha = $_POST['conf_senha'] ?? '';

    if (!$nome) {
        $erros[] = 'Informe o nome.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Email inválido.';
    }

    if (strlen($senha) < 8) {
        $erros[] = 'Senha deve ter pelo menos 8 caracteres.';
    }

    if ($senha !== $conf_senha) {
        $erros[] = 'As senhas não conferem.';
    }

    if ($authModel->emailExists($email)) {
        $erros[] = 'E-mail já cadastrado.';
    }

    if (!$erros) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        if ($authModel->createUser($nome, $email, $hash)) {
            echo json_encode([
                'success' => true,
                'message' => "Conta criada com sucesso! <a href='index.php'>Entrar</a>"
            ]);
            exit;
        }

        $erros[] = 'Erro ao salvar usuário.';
    }

    echo json_encode([
        'success' => false,
        'errors'  => $erros
    ]);
    exit;
}

/**
 * ATUALIZAR PERFIL
 */
function handleUpdateProfile(AuthModel $authModel): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
        header('Location: /modules/painel.php?mod=configuracoes');
        exit;
    }

    $userIdForm = (int)($_POST['user_id'] ?? 0);
    $userIdSess = (int)$_SESSION['user_id'];

    if ($userIdForm !== $userIdSess) {
        header('Location: /modules/painel.php?mod=configuracoes&erro=1');
        exit;
    }

    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '') {
        header('Location: /modules/painel.php?mod=configuracoes&erro=1');
        exit;
    }

    $fotoPath = null;
    if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $dir = __DIR__ . '/../../uploads/avatars/';

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

    $_SESSION['user_nome']   = $nome;
    $_SESSION['user_email']  = $email;
    if ($fotoPath) {
        $_SESSION['user_avatar'] = $fotoPath;
    }

    header('Location: /modules/painel.php?mod=configuracoes&ok=1');
    exit;
}
