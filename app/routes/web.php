<?php

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

$ensureSession = function () {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
};

$ensureAuth = function () use ($ensureSession, $base) {
    $ensureSession();

    fd_require_workspace();
    fd_enforce_workspace_onboarding();
};

$ensureRole = function (string|array $roles) use ($ensureAuth) {
    $ensureAuth();
    fd_require_role($roles);
};

$renderModulo = function (string $mod) use ($ensureAuth) {
    $ensureAuth();
    $_GET['mod'] = $mod;
    require __DIR__ . '/../views/layouts/app.php';
};

$router->get('/', function () use ($ensureSession, $base) {
    $ensureSession();

    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    require __DIR__ . '/../views/public/home.php';
});

$router->get('/login', function () use ($ensureSession, $base) {
    $ensureSession();

    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    require __DIR__ . '/../views/auth/login.php';
});

$router->get('/cadastro', function () use ($ensureSession, $base) {
    $ensureSession();

    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    require __DIR__ . '/../views/auth/register.php';
});

$router->get('/esqueci-senha', function () use ($ensureSession, $base) {
    $ensureSession();

    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    require __DIR__ . '/../views/auth/forgot-password.php';
});

$router->get('/redefinir-senha', function () use ($ensureSession, $base) {
    $ensureSession();

    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    require __DIR__ . '/../views/auth/reset-password.php';
});

$router->get('/confirmar-email', function () {
    $_REQUEST['acao'] = 'verifyEmail';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/login', function () {
    $_REQUEST['acao'] = 'login';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->get('/convite', function () use ($ensureSession) {
    $ensureSession();
    require __DIR__ . '/../views/auth/invite.php';
});

$router->get('/onboarding', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin']);
    $renderModulo('onboarding');
});

$router->get('/busca', function () use ($ensureAuth) {
    $ensureAuth();
    require __DIR__ . '/../Controllers/SearchController.php';
});

$router->post('/register', function () {
    $_REQUEST['acao'] = 'register';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/esqueci-senha', function () {
    $_REQUEST['acao'] = 'forgotPassword';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/redefinir-senha', function () {
    $_REQUEST['acao'] = 'resetPassword';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->get('/logout', function () {
    $_REQUEST['acao'] = 'logout';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/atualizar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updateProfile';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/preferencias', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updatePreferences';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/modulos', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updateModulePreferences';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/workspace/trocar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'switchWorkspace';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/onboarding/salvar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'salvar_onboarding';
    require __DIR__ . '/../Controllers/WorkspaceController.php';
});

$router->post('/workspace/atualizar-configuracoes', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'atualizar_configuracoes';
    require __DIR__ . '/../Controllers/WorkspaceController.php';
});

$router->post('/workspace/invites/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/WorkspaceInviteController.php';
});

$router->post('/workspace/invites/revogar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'revogar';
    require __DIR__ . '/../Controllers/WorkspaceInviteController.php';
});

$router->post('/workspace/membros/atualizar-papel', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'atualizar_papel';
    require __DIR__ . '/../Controllers/WorkspaceMemberController.php';
});

$router->post('/workspace/membros/remover', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'remover';
    require __DIR__ . '/../Controllers/WorkspaceMemberController.php';
});

$router->post('/convite/aceitar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'aceitar';
    require __DIR__ . '/../Controllers/WorkspaceInviteController.php';
});

$router->get('/dashboard', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin']);
    $renderModulo('dashboard');
});

$router->get('/clientes', function () use ($renderModulo) {
    $renderModulo('clientes');
});

$router->get('/cliente', function () use ($renderModulo) {
    $renderModulo('cliente');
});

$router->post('/clientes/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/ClienteController.php';
});

$router->post('/clientes/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'atualizar';
    require __DIR__ . '/../Controllers/ClienteController.php';
});

$router->post('/clientes/upload-foto', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'uploadFoto';
    require __DIR__ . '/../Controllers/ClienteController.php';
});

$router->get('/clientes/bloco', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'bloco';
    require __DIR__ . '/../Controllers/ClienteController.php';
});

$router->post('/clientes/blocos/salvar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'salvar_bloco';
    require __DIR__ . '/../Controllers/ClienteController.php';
});

$router->get('/projetos', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional', 'viewer']);
    $renderModulo('projetos');
});

$router->get('/projeto', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional', 'viewer']);
    $renderModulo('projeto');
});

$router->post('/projetos/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/concluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'concluir';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->get('/projetos/buscar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'getProjeto';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'atualizarProjeto';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->get('/projetos/tarefas/buscar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'getTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/tarefas/salvar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'salvarTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/tarefas/mover', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'moverTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/tarefas/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'excluirTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->get('/pipeline', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $renderModulo('pipeline');
});

$router->get('/pipeline/buscar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'buscar';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->get('/pipeline/board-json', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'board_json';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'atualizar';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'excluir';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/mover', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'mover';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/marcar-ganha', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'marcar_ganha';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->post('/pipeline/marcar-perdida', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'marcar_perdida';
    require __DIR__ . '/../Controllers/PipelineController.php';
});

$router->get('/orcamentos', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro', 'viewer']);
    $renderModulo('orcamentos');
});

$router->get('/orcamentos/buscar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro', 'viewer']);
    $_REQUEST['acao'] = 'buscar';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->post('/orcamentos/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->post('/orcamentos/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'atualizar';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->post('/orcamentos/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'excluir';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->get('/orcamento', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro', 'viewer']);
    $renderModulo('orcamento');
});

$router->get('/financeiro', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $renderModulo('financeiro');
});

$router->get('/financeiro/entrada', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'buscar_entrada';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->post('/financeiro/acao', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/hospedagens', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $renderModulo('hospedagens');
});

$router->post('/hospedagens/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_POST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/HospedagemController.php';
});

$router->post('/hospedagens/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_POST['acao'] = 'excluir';
    require __DIR__ . '/../Controllers/HospedagemController.php';
});

$router->get('/codigos', function () use ($renderModulo) {
    $renderModulo('codigos');
});

$router->get('/codigo', function () use ($renderModulo) {
    $renderModulo('codigo');
});

$router->post('/codigos/criar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/favoritar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'favoritar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/copiar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'copiar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/excluir', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'excluir';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->get('/configuracoes', function () use ($renderModulo) {
    $renderModulo('configuracoes');
});

$router->get('/relatorio-cliente', function () {
    require __DIR__ . '/../views/clientes/relatorio.php';
});

$router->get('/relatorio-conversao', function () use ($ensureAuth) {
    $ensureAuth();
    require __DIR__ . '/../views/pipeline/relatorio_conversao.php';
});
