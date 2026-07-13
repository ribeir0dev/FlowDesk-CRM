<?php
$base = fd_base_path();
$totalAccounts = count($accounts);
$activeAccounts = count(array_filter($accounts, static fn(array $account): bool => ($account['status'] ?? '') === 'active'));
$expiredAccounts = count(array_filter($accounts, static fn(array $account): bool => ($account['status'] ?? '') === 'expired'));
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contas | FlowDesk Admin</title>
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
            <nav>
                <a href="<?= e($base) ?>/admin" class="is-active"><i class="ri-team-line"></i> Contas</a>
            </nav>
            <form method="post" action="<?= e($base) ?>/admin/logout" class="fd-admin-logout">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit"><i class="ri-logout-box-r-line"></i> Sair do admin</button>
            </form>
        </aside>

        <main class="fd-admin-content">
            <header class="fd-admin-topbar">
                <div>
                    <p class="fd-admin-eyebrow">Gestao manual</p>
                    <h1>Contas e assinaturas</h1>
                    <p>Controle planos, periodicidade e vencimentos sem checkout ou renovacao automatica.</p>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="fd-admin-floating-alert is-<?= e($flash['type']) ?>" data-admin-alert>
                    <span><?= e($flash['message']) ?></span>
                    <button type="button" aria-label="Fechar"><i class="ri-close-line"></i></button>
                </div>
            <?php endif; ?>

            <section class="fd-admin-stats">
                <article><span>Total de contas</span><strong><?= $totalAccounts ?></strong><i class="ri-group-line"></i></article>
                <article><span>Contas ativas</span><strong><?= $activeAccounts ?></strong><i class="ri-checkbox-circle-line"></i></article>
                <article><span>Contas expiradas</span><strong><?= $expiredAccounts ?></strong><i class="ri-timer-flash-line"></i></article>
            </section>

            <section class="fd-admin-panel">
                <div class="fd-admin-panel-head">
                    <div>
                        <h2>Contas cadastradas</h2>
                        <p>Cada conta representa um workspace e seu proprietario principal.</p>
                    </div>
                    <label class="fd-admin-search">
                        <i class="ri-search-line"></i>
                        <input type="search" placeholder="Buscar conta..." data-admin-search>
                    </label>
                </div>

                <div class="fd-admin-table-wrap">
                    <table class="fd-admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Conta</th>
                                <th>E-mail</th>
                                <th>Plano atual</th>
                                <th>Periodicidade</th>
                                <th>Expiracao</th>
                                <th>Status</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <?php
                            $status = (string) ($account['status'] ?? 'sem_assinatura');
                            $searchText = strtolower(implode(' ', [
                                $account['account_id'] ?? '',
                                $account['nome'] ?? '',
                                $account['email'] ?? '',
                                $account['workspace_nome'] ?? '',
                                $account['plano_nome'] ?? '',
                            ]));
                            ?>
                            <tr data-admin-row data-search="<?= e($searchText) ?>">
                                <td><span class="fd-admin-id">#<?= (int) $account['account_id'] ?></span></td>
                                <td>
                                    <div class="fd-admin-account-cell">
                                        <span class="fd-admin-avatar"><?= e(mb_strtoupper(mb_substr((string) ($account['nome'] ?: $account['workspace_nome']), 0, 1))) ?></span>
                                        <div>
                                            <strong><?= e($account['nome'] ?: 'Proprietario nao identificado') ?></strong>
                                            <small><?= e($account['workspace_nome']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($account['email'] ?: '-') ?></td>
                                <td><span class="fd-admin-plan"><?= e($account['plano_nome'] ?: 'Sem plano') ?></span></td>
                                <td><?= ($account['billing_cycle'] ?? 'monthly') === 'annual' ? 'Anual' : 'Mensal' ?></td>
                                <td><?= !empty($account['expires_at']) ? e(fd_format_date((string) $account['expires_at'])) : 'Sem data' ?></td>
                                <td><span class="fd-admin-status is-<?= e($status) ?>"><?= $status === 'active' ? 'Ativa' : ($status === 'expired' ? 'Expirada' : 'Sem assinatura') ?></span></td>
                                <td><a class="fd-admin-edit" href="<?= e($base) ?>/admin/contas/<?= (int) $account['account_id'] ?>/editar"><i class="ri-pencil-line"></i> Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$accounts): ?>
                            <tr><td colspan="8" class="fd-admin-empty">Nenhuma conta cadastrada.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <script>
        (() => {
            const search = document.querySelector('[data-admin-search]');
            const rows = [...document.querySelectorAll('[data-admin-row]')];
            search?.addEventListener('input', () => {
                const value = search.value.trim().toLocaleLowerCase('pt-BR');
                rows.forEach(row => row.hidden = value !== '' && !row.dataset.search.includes(value));
            });

            const alert = document.querySelector('[data-admin-alert]');
            alert?.querySelector('button')?.addEventListener('click', () => alert.remove());
            if (alert) window.setTimeout(() => alert.remove(), 5000);
        })();
    </script>
</body>
</html>

