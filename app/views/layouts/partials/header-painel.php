<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../app/Models/AuthModel.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../../../config/db.php';
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

$pageTitle = $pageTitle ?? 'FlowDesk';
$userName = $_SESSION['user_nome'] ?? $_SESSION['user_name'] ?? $_SESSION['nome'] ?? 'Usuario';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
$userAvatar = $_SESSION['user_avatar'] ?? ($_SESSION['avatar'] ?? '/assets/img/profile.png');
$userThemePreference = $_SESSION['user_theme'] ?? 'dark';

if (!filter_var($userAvatar, FILTER_VALIDATE_URL)) {
    if (str_starts_with($userAvatar, '/')) {
        $userAvatar = $base . $userAvatar;
    } else {
        $userAvatar = $base . '/' . ltrim($userAvatar, '/');
    }
}

$mod = $_GET['mod'] ?? 'dashboard';
$currentWorkspaceRole = fd_current_workspace_role() ?? 'owner';
$currentWorkspaceName = $_SESSION['current_workspace_nome'] ?? 'Workspace atual';
$currentClienteId = fd_current_cliente_id();
$workspaceOptions = [];

$sidebarModulePreferences = $_SESSION['user_sidebar_modules'] ?? null;
if ($sidebarModulePreferences !== null && !is_array($sidebarModulePreferences)) {
    $sidebarModulePreferences = [];
}

$navPreferenceMap = [
    'cliente' => 'clientes',
    'configuracoes' => 'configuracoes',
];

if (!empty($_SESSION['user_id'])) {
    $authHeaderModel = new AuthModel($pdo);
    $workspaceOptions = $authHeaderModel->listarWorkspacesDoUsuario((int) $_SESSION['user_id']);
}

$navItems = match ($currentWorkspaceRole) {
    'operacional' => [
        'clientes' => ['label' => 'Clientes', 'icon' => 'ri-group-line', 'url' => $base . '/clientes'],
        'pipeline' => ['label' => 'Pipeline', 'icon' => 'ri-git-branch-line', 'url' => $base . '/pipeline'],
        'projetos' => ['label' => 'Projetos', 'icon' => 'ri-folder-line', 'url' => $base . '/projetos'],
        'codigos' => ['label' => 'Codigos', 'icon' => 'ri-code-box-line', 'url' => $base . '/codigos'],
        'configuracoes' => ['label' => 'Configuracoes', 'icon' => 'ri-settings-3-line', 'url' => $base . '/configuracoes'],
    ],
    'financeiro' => [
        'clientes' => ['label' => 'Clientes', 'icon' => 'ri-group-line', 'url' => $base . '/clientes'],
        'orcamentos' => ['label' => 'Orcamentos', 'icon' => 'ri-file-list-3-line', 'url' => $base . '/orcamentos'],
        'financeiro' => ['label' => 'Financeiro', 'icon' => 'ri-money-dollar-circle-line', 'url' => $base . '/financeiro'],
        'hospedagens' => ['label' => 'Hospedagens', 'icon' => 'ri-server-line', 'url' => $base . '/hospedagens'],
        'codigos' => ['label' => 'Codigos', 'icon' => 'ri-code-box-line', 'url' => $base . '/codigos'],
        'configuracoes' => ['label' => 'Configuracoes', 'icon' => 'ri-settings-3-line', 'url' => $base . '/configuracoes'],
    ],
    'viewer' => [
        'cliente' => ['label' => 'Meu cliente', 'icon' => 'ri-group-line', 'url' => $currentClienteId !== null ? $base . '/cliente?id=' . $currentClienteId : $base . '/clientes'],
        'projetos' => ['label' => 'Meus projetos', 'icon' => 'ri-folder-line', 'url' => $base . '/projetos'],
        'orcamentos' => ['label' => 'Meus orcamentos', 'icon' => 'ri-file-list-3-line', 'url' => $base . '/orcamentos'],
        'codigos' => ['label' => 'Codigos', 'icon' => 'ri-code-box-line', 'url' => $base . '/codigos'],
        'configuracoes' => ['label' => 'Configuracoes', 'icon' => 'ri-settings-3-line', 'url' => $base . '/configuracoes'],
    ],
    default => [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'ri-dashboard-line', 'url' => $base . '/dashboard'],
        'clientes' => ['label' => 'Clientes', 'icon' => 'ri-group-line', 'url' => $base . '/clientes'],
        'pipeline' => ['label' => 'Pipeline', 'icon' => 'ri-git-branch-line', 'url' => $base . '/pipeline'],
        'projetos' => ['label' => 'Projetos', 'icon' => 'ri-folder-line', 'url' => $base . '/projetos'],
        'orcamentos' => ['label' => 'Orcamentos', 'icon' => 'ri-file-list-3-line', 'url' => $base . '/orcamentos'],
        'financeiro' => ['label' => 'Financeiro', 'icon' => 'ri-money-dollar-circle-line', 'url' => $base . '/financeiro'],
        'hospedagens' => ['label' => 'Hospedagens', 'icon' => 'ri-server-line', 'url' => $base . '/hospedagens'],
        'codigos' => ['label' => 'Codigos', 'icon' => 'ri-code-box-line', 'url' => $base . '/codigos'],
        'configuracoes' => ['label' => 'Configuracoes', 'icon' => 'ri-settings-3-line', 'url' => $base . '/configuracoes'],
    ],
};

if ($sidebarModulePreferences !== null) {
    $navItems = array_filter(
        $navItems,
        static function (array $item, string $key) use ($sidebarModulePreferences, $navPreferenceMap): bool {
            $preferenceKey = $navPreferenceMap[$key] ?? $key;

            if ($preferenceKey === 'configuracoes') {
                return true;
            }

            return in_array($preferenceKey, $sidebarModulePreferences, true);
        },
        ARRAY_FILTER_USE_BOTH
    );
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(fd_preferred_locale()) ?>" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FlowDesk</title>
    <meta name="theme-color" content="#020617">
    <meta name="description" content="FlowDesk CRM - gestao de clientes, projetos, pipeline e financeiro.">

    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <script>
        (() => {
            const savedTheme = localStorage.getItem('flowdesk-theme') || <?= json_encode($userThemePreference, JSON_UNESCAPED_SLASHES) ?> || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
        window.FLOWDESK_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script defer src="<?= $base ?>/assets/js/modules/date-pickers.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="<?= $base ?>/assets/js/script.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/confirm-modal.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/edit-modals.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/dashboard-tasks.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/financeiro-charts.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/financeiro-entrada.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/global-search.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/kanban.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/orcamentos.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/pipeline-actions.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/ui-helpers.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body x-data="flowdeskLayout()" x-init="init()">
    <div class="fd-shell">
        <aside class="fd-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="fd-sidebar-brand">
                <img src="<?= $base ?>/assets/img/icon.png" alt="FlowDesk" class="fd-sidebar-logo">
                <div>
                    <p class="fd-sidebar-title">FlowDesk</p>
                    <p class="fd-sidebar-subtitle">CRM / SaaS Workspace</p>
                </div>
            </div>

            <nav class="fd-nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <?php $isActiveItem = $mod === $key || ($mod === 'codigo' && $key === 'codigos'); ?>
                    <a href="<?= $item['url'] ?>" class="fd-nav-link <?= $isActiveItem ? 'is-active' : '' ?>">
                        <i class="<?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="fd-overlay" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <div class="fd-main">
            <header class="fd-topbar">
                <div class="fd-topbar-left">
                    <button type="button" class="fd-icon-btn fd-mobile-only" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                        <i class="ri-menu-line"></i>
                    </button>

                    <div class="fd-topbar-title-copy">
                        <p class="fd-topbar-eyebrow">Modulo selecionado</p>
                        <h1 class="fd-topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
                    </div>
                </div>

                <div class="fd-topbar-right">
                    <?php if (!empty($workspaceOptions)): ?>
                        <div class="fd-workspace-switcher fd-workspace-switcher-desktop" x-data="{ open: false }">
                            <button type="button" class="fd-workspace-switcher-trigger" @click="open = !open" @click.outside="open = false">
                                <div class="fd-workspace-switcher-copy">
                                    <span class="fd-workspace-switcher-label">Workspace</span>
                                    <strong><?= htmlspecialchars($currentWorkspaceName) ?></strong>
                                </div>
                                <span class="fd-badge <?= htmlspecialchars([
                                    'owner' => 'fd-badge-success',
                                    'admin' => 'fd-badge-info',
                                    'financeiro' => 'fd-badge-warning',
                                    'operacional' => 'fd-badge-neutral',
                                    'viewer' => 'fd-badge-neutral',
                                ][$currentWorkspaceRole] ?? 'fd-badge-neutral') ?>">
                                    <?= htmlspecialchars(ucfirst($currentWorkspaceRole)) ?>
                                </span>
                                <i class="ri-arrow-down-s-line fd-user-menu-arrow"></i>
                            </button>

                            <div class="fd-user-dropdown fd-workspace-dropdown" x-show="open" x-cloak x-transition>
                                <div class="fd-user-dropdown-head">
                                    <p class="fd-user-dropdown-name">Trocar workspace</p>
                                    <p class="fd-user-dropdown-email">Escolha a conta que deseja acessar agora.</p>
                                </div>

                                <div class="fd-user-dropdown-body">
                                    <?php foreach ($workspaceOptions as $workspaceOption): ?>
                                        <?php $isCurrentWorkspace = (int) ($workspaceOption['workspace_id'] ?? 0) === (int) ($_SESSION['current_workspace_id'] ?? 0); ?>
                                        <form method="post" action="<?= $base ?>/workspace/trocar" class="fd-workspace-switcher-form">
                                            <input type="hidden" name="workspace_id" value="<?= (int) ($workspaceOption['workspace_id'] ?? 0) ?>">
                                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ($base . '/dashboard')) ?>">
                                            <button type="submit" class="fd-workspace-option<?= $isCurrentWorkspace ? ' is-active' : '' ?>">
                                                <span class="fd-workspace-option-main">
                                                    <strong><?= htmlspecialchars($workspaceOption['workspace_nome'] ?? 'Workspace') ?></strong>
                                                    <small><?= htmlspecialchars(ucfirst((string) ($workspaceOption['role'] ?? 'viewer'))) ?></small>
                                                </span>
                                                <?php if ($isCurrentWorkspace): ?>
                                                    <i class="ri-check-line"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="fd-icon-btn" @click="toggleTheme()" aria-label="Alternar tema">
                        <i x-show="theme === 'dark'" x-cloak class="ri-sun-line"></i>
                        <i x-show="theme === 'light'" x-cloak class="ri-moon-clear-line"></i>
                    </button>

                    <div class="fd-user-menu" x-data="{ open: false }">
                        <button type="button" class="fd-user-menu-trigger" @click="open = !open" @click.outside="open = false">
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="fd-user-menu-avatar">

                            <div class="fd-user-menu-text">
                                <p class="fd-user-menu-name"><?= htmlspecialchars($userName) ?></p>
                                <p class="fd-user-menu-status">Conta ativa</p>
                            </div>

                            <i class="ri-arrow-down-s-line fd-user-menu-arrow"></i>
                        </button>

                        <div class="fd-user-dropdown" x-show="open" x-cloak x-transition>
                            <div class="fd-user-dropdown-head">
                                <p class="fd-user-dropdown-name"><?= htmlspecialchars($userName) ?></p>
                                <?php if ($userEmail): ?>
                                    <p class="fd-user-dropdown-email"><?= htmlspecialchars($userEmail) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="fd-user-dropdown-body">
                                <a href="<?= $base ?>/configuracoes" class="fd-user-dropdown-link">
                                    <i class="ri-settings-3-line"></i>
                                    <span>Configuracoes</span>
                                </a>

                                <a href="<?= $base ?>/logout" class="fd-user-dropdown-link is-danger">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($workspaceOptions)): ?>
                    <div class="fd-workspace-switcher fd-workspace-switcher-mobile fd-mobile-only-block" x-data="{ open: false }">
                        <button type="button" class="fd-workspace-switcher-trigger fd-workspace-switcher-trigger-mobile" @click="open = !open" @click.outside="open = false">
                            <div class="fd-workspace-switcher-copy">
                                <span class="fd-workspace-switcher-label">Workspace</span>
                                <strong><?= htmlspecialchars($currentWorkspaceName) ?></strong>
                            </div>
                            <span class="fd-badge <?= htmlspecialchars([
                                'owner' => 'fd-badge-success',
                                'admin' => 'fd-badge-info',
                                'financeiro' => 'fd-badge-warning',
                                'operacional' => 'fd-badge-neutral',
                                'viewer' => 'fd-badge-neutral',
                            ][$currentWorkspaceRole] ?? 'fd-badge-neutral') ?>">
                                <?= htmlspecialchars(ucfirst($currentWorkspaceRole)) ?>
                            </span>
                            <i class="ri-arrow-down-s-line fd-user-menu-arrow"></i>
                        </button>

                        <div class="fd-user-dropdown fd-workspace-dropdown" x-show="open" x-cloak x-transition>
                            <div class="fd-user-dropdown-head">
                                <p class="fd-user-dropdown-name">Trocar workspace</p>
                                <p class="fd-user-dropdown-email">Escolha a conta que deseja acessar agora.</p>
                            </div>

                            <div class="fd-user-dropdown-body">
                                <?php foreach ($workspaceOptions as $workspaceOption): ?>
                                    <?php $isCurrentWorkspace = (int) ($workspaceOption['workspace_id'] ?? 0) === (int) ($_SESSION['current_workspace_id'] ?? 0); ?>
                                    <form method="post" action="<?= $base ?>/workspace/trocar" class="fd-workspace-switcher-form">
                                        <input type="hidden" name="workspace_id" value="<?= (int) ($workspaceOption['workspace_id'] ?? 0) ?>">
                                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ($base . '/dashboard')) ?>">
                                        <button type="submit" class="fd-workspace-option<?= $isCurrentWorkspace ? ' is-active' : '' ?>">
                                            <span class="fd-workspace-option-main">
                                                <strong><?= htmlspecialchars($workspaceOption['workspace_nome'] ?? 'Workspace') ?></strong>
                                                <small><?= htmlspecialchars(ucfirst((string) ($workspaceOption['role'] ?? 'viewer'))) ?></small>
                                            </span>
                                            <?php if ($isCurrentWorkspace): ?>
                                                <i class="ri-check-line"></i>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <script>
                    function flowdeskLayout() {
                        const savedTheme = localStorage.getItem('flowdesk-theme') || <?= json_encode($userThemePreference, JSON_UNESCAPED_SLASHES) ?> || 'dark';

                        return {
                            sidebarOpen: false,
                            theme: savedTheme,

                            init() {
                                document.documentElement.setAttribute('data-theme', this.theme);
                            },

                            toggleTheme() {
                                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                                document.documentElement.setAttribute('data-theme', this.theme);
                                localStorage.setItem('flowdesk-theme', this.theme);
                            }
                        };
                    }
                </script>
            </header>

            <main class="fd-content">
