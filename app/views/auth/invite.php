<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';
require_once __DIR__ . '/../../../app/Helpers/auth.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceInviteModel.php';

$token = trim($_GET['token'] ?? '');
$erro = trim($_GET['erro'] ?? '');
$pageTitle = 'Convite | FlowDesk';
$inviteModel = new WorkspaceInviteModel($pdo);
$invite = $token !== '' ? $inviteModel->buscarPorToken($token) : null;
$currentEmail = trim($_SESSION['user_email'] ?? '');
$isLogged = !empty($_SESSION['user_id']);
$canAccept = false;

if ($invite && $isLogged && $currentEmail !== '') {
    $canAccept = mb_strtolower($currentEmail) === mb_strtolower(trim((string) $invite['email']))
        && ($invite['status'] ?? '') === 'pending'
        && strtotime((string) $invite['expires_at']) >= time();
}

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
                <p class="fd-page-eyebrow">Convite para equipe</p>
                <h1>Acesse um novo workspace com convite seguro.</h1>
                <p>Use este convite para entrar em uma conta compartilhada do FlowDesk mantendo seus dados e permissões separados.</p>
            </div>
        </section>

        <section class="fd-auth-page-card-wrap">
            <div class="fd-auth-card fd-auth-card-standalone">
                <div class="fd-auth-card-head fd-auth-card-head-standalone">
                    <div>
                        <p class="fd-page-eyebrow">Convite</p>
                        <h2>Entrar na equipe</h2>
                        <p class="fd-card-subtitle">Valide o convite e aceite o acesso ao workspace.</p>
                    </div>
                </div>

                <?php if (!$invite): ?>
                    <div class="fd-auth-flash fd-auth-flash-danger">Convite invalido ou inexistente.</div>
                <?php else: ?>
                    <?php if ($erro === 'accept'): ?>
                        <div class="fd-auth-flash fd-auth-flash-danger">Nao foi possivel aceitar este convite com a conta atual.</div>
                    <?php endif; ?>

                    <?php if (($invite['status'] ?? '') === 'accepted'): ?>
                        <div class="fd-auth-flash fd-auth-flash-success">Este convite ja foi aceito anteriormente.</div>
                    <?php elseif (($invite['status'] ?? '') === 'revoked'): ?>
                        <div class="fd-auth-flash fd-auth-flash-danger">Este convite foi revogado.</div>
                    <?php elseif (strtotime((string) $invite['expires_at']) < time()): ?>
                        <div class="fd-auth-flash fd-auth-flash-danger">Este convite expirou.</div>
                    <?php endif; ?>

                    <div class="fd-settings-team-list">
                        <article class="fd-settings-team-member">
                            <div class="fd-settings-team-copy">
                                <strong class="fd-settings-team-name"><?= htmlspecialchars($invite['workspace_nome']) ?></strong>
                                <span class="fd-text-muted">Convidado por <?= htmlspecialchars($invite['invited_by_nome']) ?></span>
                                <span class="fd-text-muted">E-mail do convite: <?= htmlspecialchars($invite['email']) ?></span>
                            </div>
                            <div class="fd-settings-team-meta">
                                <span class="fd-badge fd-badge-info"><?= htmlspecialchars(ucfirst($invite['role'])) ?></span>
                            </div>
                        </article>
                    </div>

                    <?php if (!$isLogged): ?>
                        <a href="<?= ($base ?? '') ?>/login?redirect=<?= urlencode(($base ?? '') . '/convite?token=' . urlencode($token)) ?>" class="fd-btn-primary fd-auth-submit">
                            <i class="ri-login-box-line"></i>
                            <span>Entrar para aceitar</span>
                        </a>
                    <?php elseif (!$canAccept): ?>
                        <div class="fd-auth-flash fd-auth-flash-danger">
                            Este convite deve ser aceito com a conta de e-mail <strong><?= htmlspecialchars($invite['email']) ?></strong>.
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= ($base ?? '') ?>/convite/aceitar" class="fd-auth-form fd-auth-form-standalone">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <button type="submit" class="fd-btn-primary fd-auth-submit">
                                <i class="ri-user-add-line"></i>
                                <span>Aceitar convite</span>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../../../app/views/layouts/partials/footer.php'; ?>
