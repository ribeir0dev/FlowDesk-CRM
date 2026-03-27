<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';

$erro = trim((string) ($_GET['erro'] ?? ''));
$ok = isset($_GET['ok']);
$resetLink = trim((string) ($_GET['link'] ?? ''));
$pageTitle = 'Recuperar senha | FlowDesk';
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
                <p class="fd-page-eyebrow">Recuperacao de acesso</p>
                <h1>Redefina sua senha com um link seguro e temporario.</h1>
                <p>Esta primeira versao ja deixa o fluxo funcional no produto. O envio por e-mail pode ser conectado depois, sem refazer a base.</p>
            </div>
        </section>

        <section class="fd-auth-page-card-wrap">
            <div class="fd-auth-card fd-auth-card-standalone">
                <div class="fd-auth-card-head fd-auth-card-head-standalone">
                    <div>
                        <p class="fd-page-eyebrow">Senha</p>
                        <h2>Esqueci minha senha</h2>
                        <p class="fd-card-subtitle">Informe o e-mail da conta para gerar um link de redefinicao.</p>
                    </div>
                    <a href="<?= ($base ?? '') ?>/login" class="fd-auth-inline-link">Voltar ao login</a>
                </div>

                <?php if ($ok): ?>
                    <div class="fd-auth-flash fd-auth-flash-success">
                        Se existir uma conta com esse e-mail, o link de redefinicao foi gerado com sucesso.
                    </div>

                    <?php if ($resetLink !== ''): ?>
                        <div class="fd-auth-flash fd-auth-flash-neutral">
                            <strong>Link de redefinicao:</strong><br>
                            <a href="<?= htmlspecialchars($resetLink) ?>" class="fd-auth-link"><?= htmlspecialchars($resetLink) ?></a>
                        </div>
                    <?php endif; ?>
                <?php elseif ($erro === 'email'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Informe um e-mail valido para continuar.</div>
                <?php elseif ($erro === 'reset' || $erro === 'csrf'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Nao foi possivel gerar o link de redefinicao agora.</div>
                <?php endif; ?>

                <form method="post" action="<?= ($base ?? '') ?>/esqueci-senha" class="fd-auth-form fd-auth-form-standalone">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <label class="fd-auth-field">
                        <span>E-mail da conta</span>
                        <div class="fd-auth-input-wrap">
                            <i class="ri-mail-line"></i>
                            <input type="email" class="login-input" name="email" placeholder="voce@empresa.com" required>
                        </div>
                    </label>

                    <button type="submit" class="fd-btn-primary fd-auth-submit">
                        <i class="ri-mail-send-line"></i>
                        <span>Gerar link seguro</span>
                    </button>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
