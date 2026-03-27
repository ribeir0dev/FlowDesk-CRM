<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';

$erro = $_GET['erro'] ?? '';
$cadastro = $_GET['cadastro'] ?? '';
$reset = $_GET['reset'] ?? '';
$confirmacao = $_GET['confirmacao'] ?? '';
$redirect = trim((string) ($_GET['redirect'] ?? '')); 
$flashError = trim((string) ($_SESSION['auth_flash_error'] ?? ''));
$flashLink = trim((string) ($_SESSION['auth_flash_link'] ?? ''));
unset($_SESSION['auth_flash_error']);
unset($_SESSION['auth_flash_link']);
$pageTitle = 'Entrar | FlowDesk';
include __DIR__ . '/../../../app/views/layouts/partials/header-login.php';
?>

<div class="fd-auth-page fd-auth-page-login">
    <div class="fd-auth-page-shell">
        <section class="fd-auth-page-brand">
            <a href="<?= ($base ?? '') ?>/" class="fd-public-brand">
                <img src="<?= ($base ?? '') ?>/assets/img/icon.png" alt="FlowDesk" class="fd-public-brand-logo">
                <span class="fd-public-brand-copy">
                    <strong>FlowDesk</strong>
                    <small>CRM | SaaS Workspace</small>
                </span>
            </a>

            <div class="fd-auth-page-copy">
                <p class="fd-page-eyebrow">Welcome back</p>
                <h1>Entre no seu workspace com a mesma linguagem visual do produto.</h1>
                <p>Login dedicado, contexto claro e uma entrada mais coerente com a proposta do FlowDesk como SaaS.</p>
            </div>

            <div class="fd-auth-page-points">
                <span><i class="ri-check-line"></i> Acesso direto ao dashboard operacional</span>
                <span><i class="ri-check-line"></i> Visual alinhado ao dark theme principal</span>
                <span><i class="ri-check-line"></i> Base pronta para onboarding e membros na Fase 2</span>
            </div>
        </section>

        <section class="fd-auth-page-card-wrap">
            <div class="fd-auth-card fd-auth-card-standalone">
                <div class="fd-auth-card-head fd-auth-card-head-standalone">
                    <div>
                        <p class="fd-page-eyebrow">Acesso</p>
                        <h2>Entrar no FlowDesk</h2>
                        <p class="fd-card-subtitle">Use seu email ou usuario para voltar ao seu workspace.</p>
                    </div>
                    <a href="<?= ($base ?? '') ?>/cadastro" class="fd-auth-inline-link">Criar conta</a>
                </div>

                <?php if ($cadastro === 'ok'): ?>
                    <div class="fd-auth-flash fd-auth-flash-success">Conta criada com sucesso. Agora e so entrar no seu workspace.</div>
                <?php elseif ($confirmacao === 'ok'): ?>
                    <div class="fd-auth-flash fd-auth-flash-success">E-mail confirmado com sucesso. Seu acesso ja esta liberado.</div>
                <?php elseif ($reset === 'ok'): ?>
                    <div class="fd-auth-flash fd-auth-flash-success">Senha redefinida com sucesso. Agora voce ja pode entrar.</div>
                <?php elseif ($erro === 'sessao' || $flashError === 'sessao'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Sua sessao expirou por inatividade. Entre novamente para continuar.</div>
                <?php elseif ($erro === 'email_nao_confirmado' || $flashError === 'email_nao_confirmado'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">
                        Seu e-mail ainda nao foi confirmado.
                        <?php if ($flashLink !== ''): ?>
                            <a href="<?= htmlspecialchars($flashLink) ?>">Confirmar agora</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($erro === 'confirmacao_expirada'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Este link de confirmacao expirou. Gere um novo cadastro ou solicite um novo link de suporte.</div>
                <?php elseif ($erro === 'confirmacao_ja_usada'): ?>
                    <div class="fd-auth-flash fd-auth-flash-success">Este e-mail ja foi confirmado anteriormente. Voce ja pode entrar.</div>
                <?php elseif ($erro === 'confirmacao_token'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">O link de confirmacao informado e invalido.</div>
                <?php elseif ($erro === 'workspace'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Seu usuario ainda nao esta vinculado a um workspace ativo.</div>
                <?php elseif ($erro === 'invalid'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Login ou senha invalido.</div>
                <?php elseif ($erro === 'campos'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Preencha usuario/e-mail e senha para continuar.</div>
                <?php endif; ?>

                <form id="form-login" method="POST" action="<?= ($base ?? '') ?>/login" class="fd-auth-form fd-auth-form-standalone">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <?php if ($redirect !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <?php endif; ?>

                    <label class="fd-auth-field">
                        <span>Usuario ou e-mail</span>
                        <div class="fd-auth-input-wrap">
                            <i class="ri-user-line"></i>
                            <input id="user_or_email" type="text" class="login-input" placeholder="voce@empresa.com" name="user_or_email" required>
                        </div>
                    </label>

                    <label class="fd-auth-field">
                        <span>Senha</span>
                        <div class="fd-auth-input-wrap">
                            <i class="ri-lock-password-line"></i>
                            <input id="password" type="password" class="login-input" placeholder="Sua senha" name="password" required>
                        </div>
                    </label>

                    <div class="fd-auth-meta">
                        <label for="rememberMe" class="fd-auth-check">
                            <input type="checkbox" id="rememberMe" name="remember" checked>
                            <span>Lembrar este dispositivo</span>
                        </label>

                        <a href="<?= ($base ?? '') ?>/esqueci-senha" class="fd-auth-link">Esqueci minha senha</a>
                    </div>

                    <button type="submit" class="fd-btn-primary fd-auth-submit">
                        <i class="ri-login-box-line"></i>
                        <span>Entrar no FlowDesk</span>
                    </button>
                </form>

                <div id="msg-login" class="fd-auth-feedback"></div>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>

