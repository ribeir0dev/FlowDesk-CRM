<?php
$base = fd_base_path();
$expiresAt = !empty($account['expires_at'])
    ? substr((string) $account['expires_at'], 0, 10)
    : date('Y-m-d');
$status = (string) ($account['status'] ?? 'sem_assinatura');
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar conta #<?= (int) $account['account_id'] ?> | FlowDesk Admin</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/css/admin.css">
</head>
<body>
    <div class="fd-admin-app">
        <aside class="fd-admin-sidebar">
            <a href="<?= e($base) ?>/admin" class="fd-admin-brand">
                <span><i class="ri-shield-keyhole-fill"></i></span>
                <div><strong>FlowDesk</strong><small>Control Center</small></div>
            </a>
            <nav><a href="<?= e($base) ?>/admin" class="is-active"><i class="ri-team-line"></i> Contas</a></nav>
            <form method="post" action="<?= e($base) ?>/admin/logout" class="fd-admin-logout">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit"><i class="ri-logout-box-r-line"></i> Sair do admin</button>
            </form>
        </aside>

        <main class="fd-admin-content">
            <header class="fd-admin-edit-header">
                <a href="<?= e($base) ?>/admin" class="fd-admin-back"><i class="ri-arrow-left-line"></i> Voltar para contas</a>
                <div>
                    <p class="fd-admin-eyebrow">Conta #<?= (int) $account['account_id'] ?></p>
                    <h1><?= e($account['nome'] ?: $account['workspace_nome']) ?></h1>
                    <p><?= e($account['email'] ?: 'E-mail nao identificado') ?> · <?= e($account['workspace_nome']) ?></p>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="fd-admin-floating-alert is-<?= e($flash['type']) ?>" data-admin-alert>
                    <span><?= e($flash['message']) ?></span>
                    <button type="button" aria-label="Fechar"><i class="ri-close-line"></i></button>
                </div>
            <?php endif; ?>

            <div class="fd-admin-edit-grid">
                <section class="fd-admin-panel fd-admin-edit-panel">
                    <div class="fd-admin-panel-head">
                        <div>
                            <h2>Plano da conta</h2>
                            <p>As alteracoes passam a valer imediatamente no workspace.</p>
                        </div>
                    </div>

                    <form method="post" action="<?= e($base) ?>/admin/contas/atualizar" class="fd-admin-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="account_id" value="<?= (int) $account['account_id'] ?>">

                        <label>
                            <span>Tipo de plano</span>
                            <div class="fd-admin-input">
                                <i class="ri-vip-crown-2-line"></i>
                                <select name="plan_id" required>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?= (int) $plan['id'] ?>" <?= (int) $account['plan_id'] === (int) $plan['id'] ? 'selected' : '' ?>>
                                            <?= e($plan['nome']) ?> · R$ <?= money((float) $plan['preco']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </label>

                        <fieldset class="fd-admin-cycle">
                            <legend>Periodicidade</legend>
                            <label>
                                <input type="radio" name="billing_cycle" value="monthly" <?= ($account['billing_cycle'] ?? 'monthly') !== 'annual' ? 'checked' : '' ?>>
                                <span><i class="ri-calendar-line"></i><strong>Mensal</strong><small>Controle de ciclos mensais</small></span>
                            </label>
                            <label>
                                <input type="radio" name="billing_cycle" value="annual" <?= ($account['billing_cycle'] ?? '') === 'annual' ? 'checked' : '' ?>>
                                <span><i class="ri-calendar-2-line"></i><strong>Anual</strong><small>Controle de ciclos anuais</small></span>
                            </label>
                        </fieldset>

                        <label>
                            <span>Data de expiracao</span>
                            <div class="fd-admin-input">
                                <i class="ri-calendar-event-line"></i>
                                <input type="date" name="expires_at" value="<?= e($expiresAt) ?>" required>
                            </div>
                            <small class="fd-admin-help">Datas anteriores a hoje deixam a conta expirada. Hoje ou uma data futura deixam a conta ativa.</small>
                        </label>

                        <button type="submit" class="fd-admin-primary"><i class="ri-save-3-line"></i> Salvar alteracoes</button>
                    </form>
                </section>

                <aside class="fd-admin-summary">
                    <p class="fd-admin-eyebrow">Resumo atual</p>
                    <h2><?= e($account['plano_nome'] ?: 'Sem plano') ?></h2>
                    <span class="fd-admin-status is-<?= e($status) ?>"><?= $status === 'active' ? 'Conta ativa' : ($status === 'expired' ? 'Conta expirada' : 'Sem assinatura') ?></span>
                    <dl>
                        <div><dt>Periodicidade</dt><dd><?= ($account['billing_cycle'] ?? 'monthly') === 'annual' ? 'Anual' : 'Mensal' ?></dd></div>
                        <div><dt>Vencimento</dt><dd><?= !empty($account['expires_at']) ? e(fd_format_date((string) $account['expires_at'])) : 'Sem data' ?></dd></div>
                        <div><dt>Workspace</dt><dd><?= e($account['workspace_nome']) ?></dd></div>
                    </dl>
                </aside>
            </div>
        </main>
    </div>
    <script>
        (() => {
            const alert = document.querySelector('[data-admin-alert]');
            alert?.querySelector('button')?.addEventListener('click', () => alert.remove());
            if (alert) window.setTimeout(() => alert.remove(), 5000);
        })();
    </script>
</body>
</html>

