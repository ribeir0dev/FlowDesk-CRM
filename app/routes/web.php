<?php

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

require_once __DIR__ . '/../../config/csrf.php';

$ensureSession = function () {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
};

$verifyCsrf = function () use ($base) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (csrf_verify((string) $token)) {
        return;
    }

    http_response_code(419);
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $isFetch = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if ($isFetch || str_contains($accept, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Sessao expirada. Atualize a pagina e tente novamente.']);
        exit;
    }

    $_SESSION['app_flash_error'] = 'Sessao expirada. Atualize a pagina e tente novamente.';
    header('Location: ' . $base . '/dashboard');
    exit;
};

$ensureAuth = function () use ($ensureSession, $verifyCsrf) {
    $ensureSession();
    $verifyCsrf();

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

    require __DIR__ . '/../views/public/home-coming-soon.php';
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

$router->get('/logout', function () use ($ensureSession, $base) {
    $ensureSession();

    header('Location: ' . $base . (isset($_SESSION['user_id']) ? '/dashboard' : '/login'));
    exit;
});

$router->post('/logout', function () use ($ensureSession, $verifyCsrf) {
    $ensureSession();
    $verifyCsrf();

    $_REQUEST['acao'] = 'logout';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->get('/admin/login', function () use ($ensureSession, $base) {
    $ensureSession();
    require_once __DIR__ . '/../Helpers/admin_auth.php';

    if (fd_admin_is_authenticated()) {
        header('Location: ' . $base . '/admin');
        exit;
    }

    require __DIR__ . '/../views/admin/login.php';
});

$router->post('/admin/login', function () {
    $_REQUEST['acao'] = 'login';
    require __DIR__ . '/../Controllers/AdminController.php';
});

$router->get('/admin', function () {
    require_once __DIR__ . '/../Helpers/admin_auth.php';
    fd_admin_require_auth();

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        require __DIR__ . '/../../config/db.php';
    }
    require_once __DIR__ . '/../Models/AdminAccountModel.php';

    $adminModel = new AdminAccountModel($pdo);
    $accounts = $adminModel->listAccounts();
    $flash = fd_admin_pull_flash();
    require __DIR__ . '/../views/admin/index.php';
});

$router->get('/admin/contas/{id}/editar', function (string $id) {
    require_once __DIR__ . '/../Helpers/admin_auth.php';
    fd_admin_require_auth();

    $accountId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$accountId) {
        http_response_code(404);
        require __DIR__ . '/../../public/404.php';
        exit;
    }

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        require __DIR__ . '/../../config/db.php';
    }
    require_once __DIR__ . '/../Models/AdminAccountModel.php';

    $adminModel = new AdminAccountModel($pdo);
    $account = $adminModel->findAccount((int) $accountId);
    if (!$account) {
        http_response_code(404);
        require __DIR__ . '/../../public/404.php';
        exit;
    }

    $plans = $adminModel->listPlans();
    $flash = fd_admin_pull_flash();
    require __DIR__ . '/../views/admin/edit.php';
});

$router->post('/admin/contas/atualizar', function () {
    $_REQUEST['acao'] = 'updateAccount';
    require __DIR__ . '/../Controllers/AdminController.php';
});

$router->post('/admin/logout', function () {
    $_REQUEST['acao'] = 'logout';
    require __DIR__ . '/../Controllers/AdminController.php';
});

$router->post('/perfil/atualizar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updateProfile';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/senha', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updatePassword';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/avatar/preparar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'prepareAvatar';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/avatar/confirmar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'confirmAvatar';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/avatar/descartar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'discardAvatar';
    require __DIR__ . '/../Controllers/AuthController.php';
});

$router->post('/perfil/link', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'updateSocialLink';
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

$router->post('/workspace/pix-manual', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin']);
    $_REQUEST['acao'] = 'atualizar_pix_manual';
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

$router->post('/projetos/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'excluir';
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

$router->post('/projetos/tarefas/autosave', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'autosaveTarefa';
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

$router->post('/projetos/tarefas/comentar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'comentarTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/tarefas/comentario/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'atualizarComentarioTarefa';
    require __DIR__ . '/../Controllers/ProjetoController.php';
});

$router->post('/projetos/tarefas/comentario/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'excluirComentarioTarefa';
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

$router->get('/orcamentos/novo', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_GET['mod'] = 'orcamento-form';
    $renderModulo('orcamento-form');
});

$router->get('/orcamentos/editar/{id}', function (string $id) use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_GET['mod'] = 'orcamento-form';
    $_GET['id'] = (int) $id;
    $renderModulo('orcamento-form');
});

$router->get('/orcamentos/novo', function () use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_GET['mod'] = 'orcamento-form';
    $renderModulo('orcamento-form');
});

$router->get('/orcamentos/editar/{id}', function (string $id) use ($renderModulo, $ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_GET['mod'] = 'orcamento-form';
    $_GET['id'] = (int) $id;
    $renderModulo('orcamento-form');
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

$router->post('/orcamentos/duplicar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'duplicar';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->post('/orcamentos/duplicar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'duplicar';
    require __DIR__ . '/../Controllers/OrcamentoController.php';
});

$router->post('/orcamentos/confirmar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'confirmar';
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

$router->get('/proposta/{codigo}', function (string $codigo) {
    $_GET['codigo'] = $codigo;
    require __DIR__ . '/../views/orcamentos/publica.php';
});

$router->post('/proposta/{codigo}/confirmar', function (string $codigo) {
    $_GET['codigo'] = $codigo;
    $_REQUEST['acao'] = 'confirmar';
    require __DIR__ . '/../Controllers/PublicProposalController.php';
});

$router->post('/proposta/{codigo}/ajustes', function (string $codigo) {
    $_GET['codigo'] = $codigo;
    $_REQUEST['acao'] = 'ajustes';
    require __DIR__ . '/../Controllers/PublicProposalController.php';
});

$router->post('/notificacoes/marcar-lidas', function () use ($ensureAuth) {
    $ensureAuth();
    require_once __DIR__ . '/../Models/NotificationModel.php';
    require_once __DIR__ . '/../../config/csrf.php';
    require __DIR__ . '/../../config/db.php';

    if (!csrf_verify((string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'csrf']);
        return;
    }

    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    (new NotificationModel($pdo))->marcarLidas($workspaceId, $userId);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
});

$router->post('/telegram/webhook', function () {
    require __DIR__ . '/../Controllers/TelegramController.php';
});

$router->get('/financeiro/entrada', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'buscar_entrada';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/financeiro/saida', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'buscar_saida';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/financeiro/documento', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'financeiro']);
    $_REQUEST['acao'] = 'documento';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/financeiro/documento-publico', function () {
    $_REQUEST['acao'] = 'documento_publico';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/financeiro/gerar-cobranca', function () {
    $_REQUEST['acao'] = 'documento_publico';
    require __DIR__ . '/../Controllers/FinanceiroController.php';
});

$router->get('/cobranca/{codigo}', function (string $codigo) {
    $_GET['codigo'] = $codigo;
    $_REQUEST['acao'] = 'documento_publico';
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

$router->post('/codigos/criar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'criar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/atualizar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'atualizar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/favoritar', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'favoritar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/copiar', function () use ($ensureAuth) {
    $ensureAuth();
    $_REQUEST['acao'] = 'copiar';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->post('/codigos/excluir', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    $_REQUEST['acao'] = 'excluir';
    require __DIR__ . '/../Controllers/CodigoController.php';
});

$router->get('/configuracoes', function () use ($renderModulo) {
    $renderModulo('configuracoes');
});

$router->get('/relatorio-cliente', function () {
    require __DIR__ . '/../views/clientes/relatorio.php';
});

$router->get('/relatorio-conversao', function () use ($ensureRole) {
    $ensureRole(['owner', 'admin', 'operacional']);
    require __DIR__ . '/../views/pipeline/relatorio_conversao.php';
});
