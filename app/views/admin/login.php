<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/csrf.php';

$base = fd_base_path();
$error = (string) ($_GET['erro'] ?? '');
$logout = isset($_GET['logout']);
$messages = [
    'credenciais' => 'Login ou senha administrativa invalidos.',
    'bloqueado' => 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.',
    'configuracao' => 'As credenciais administrativas ainda nao foram configuradas no servidor.',
    'seguranca' => 'A sessao expirou. Atualize a pagina e tente novamente.',
];
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | FlowDesk</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/css/admin.css">
</head>
<body class="fd-admin-login-page">
    <main class="fd-admin-login-shell">
        <section class="fd-admin-login-brand">
            <span class="fd-admin-logo"><i class="ri-shield-keyhole-line"></i></span>
            <p class="fd-admin-eyebrow">FlowDesk Control</p>
            <h1>Gestao administrativa sem misturar acessos.</h1>
            <p>Area reservada para controle manual dos planos e vencimentos das contas cadastradas.</p>
        </section>

        <section class="fd-admin-login-card">
            <div class="fd-admin-card-heading">
                <span class="fd-admin-icon"><i class="ri-lock-2-line"></i></span>
                <div>
                    <p class="fd-admin-eyebrow">Acesso restrito</p>
                    <h2>Entrar no painel admin</h2>
                </div>
            </div>

            <?php if ($logout): ?>
                <div class="fd-admin-alert is-success">Sessao administrativa encerrada.</div>
            <?php elseif (isset($messages[$error])): ?>
                <div class="fd-admin-alert is-danger"><?= e($messages[$error]) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e($base) ?>/admin/login" class="fd-admin-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <label>
                    <span>Login administrativo</span>
                    <div class="fd-admin-input">
                        <i class="ri-user-settings-line"></i>
                        <input type="text" name="login" autocomplete="username" required autofocus>
                    </div>
                </label>

                <label>
                    <span>Senha</span>
                    <div class="fd-admin-input">
                        <i class="ri-key-2-line"></i>
                        <input type="password" name="password" autocomplete="current-password" required>
                    </div>
                </label>

                <button type="submit" class="fd-admin-primary">
                    <i class="ri-login-box-line"></i>
                    Entrar com seguranca
                </button>
            </form>
        </section>
    </main>
</body>
</html>

