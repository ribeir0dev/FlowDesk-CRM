<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';
require_once __DIR__ . '/../../../app/Models/AuthModel.php';

$token = trim((string) ($_GET['token'] ?? ''));
$erro = trim((string) ($_GET['erro'] ?? ''));
$authModel = new AuthModel($pdo);
$token = html_entity_decode(rawurldecode($token), ENT_QUOTES, 'UTF-8');
$reset = $token !== '' ? $authModel->findPasswordResetByToken($token) : null;

$resetStatus = $token === '' ? 'missing' : $authModel->passwordResetStatus($token);
$isValidReset = $resetStatus === 'ok';

$pageTitle = 'Redefinir senha | FlowDesk';
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
                <p class="fd-page-eyebrow">Nova senha</p>
                <h1>Escolha uma nova senha para voltar ao seu workspace.</h1>
                <p>O link e temporario e de uso unico. Depois de redefinir, voce volta ao login normalmente.</p>
            </div>
        </section>

        <section class="fd-auth-page-card-wrap">
            <div class="fd-auth-card fd-auth-card-standalone">
                <div class="fd-auth-card-head fd-auth-card-head-standalone">
                    <div>
                        <p class="fd-page-eyebrow">Redefinicao</p>
                        <h2>Criar nova senha</h2>
                        <p class="fd-card-subtitle">Use pelo menos 8 caracteres para proteger melhor sua conta.</p>
                    </div>
                    <a href="<?= ($base ?? '') ?>/login" class="fd-auth-inline-link">Voltar ao login</a>
                </div>

                <?php if (!$isValidReset): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">
                        <?php if ($resetStatus === 'missing' || $resetStatus === 'not_found'): ?>
                            Este link de redefinicao nao foi encontrado. Gere um novo link e tente novamente.
                        <?php elseif ($resetStatus === 'used'): ?>
                            Este link de redefinicao ja foi usado. Gere um novo link para continuar.
                        <?php elseif ($resetStatus === 'expired'): ?>
                            Este link de redefinicao expirou. Gere um novo link para continuar.
                        <?php else: ?>
                            Este link de redefinicao e invalido, expirou ou ja foi usado.
                        <?php endif; ?>
                    </div>
                <?php elseif ($erro === 'senha'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">A senha precisa ter pelo menos 8 caracteres e as duas precisam ser iguais.</div>
                <?php elseif ($erro === 'token'): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Nao foi possivel concluir a redefinicao com este link.</div>
                <?php endif; ?>

                <?php if ($isValidReset): ?>
                    <form method="post" action="<?= ($base ?? '') ?>/redefinir-senha" class="fd-auth-form fd-auth-form-standalone">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <label class="fd-auth-field">
                            <span>Nova senha</span>
                            <div class="fd-auth-input-wrap">
                                <i class="ri-lock-password-line"></i>
                                <input type="password" class="login-input" name="senha" placeholder="Minimo de 8 caracteres" required>
                            </div>
                        </label>

                        <label class="fd-auth-field">
                            <span>Confirmar nova senha</span>
                            <div class="fd-auth-input-wrap">
                                <i class="ri-shield-check-line"></i>
                                <input type="password" class="login-input" name="conf_senha" placeholder="Repita a nova senha" required>
                            </div>
                        </label>

                        <button type="submit" class="fd-btn-primary fd-auth-submit">
                            <i class="ri-key-2-line"></i>
                            <span>Salvar nova senha</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
