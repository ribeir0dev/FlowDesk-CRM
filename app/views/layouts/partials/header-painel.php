<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../app/Models/AuthModel.php';
require_once __DIR__ . '/../../../../app/Models/BillingModel.php';
require_once __DIR__ . '/../../../../app/Models/NotificationModel.php';
require_once __DIR__ . '/../../../../config/csrf.php';

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
$currentPlanLabel = 'Free';
$currentPlanRemainingLabel = 'sem vencimento';
$headerNotifications = [];
$headerUnreadNotifications = 0;

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

if (fd_current_workspace_id() !== null) {
    try {
        $billingHeaderModel = new BillingModel($pdo);
        $currentSubscription = $billingHeaderModel->getCurrentSubscription((int) fd_current_workspace_id());
        $currentPlanLabel = (string) ($currentSubscription['plan_nome'] ?? $currentSubscription['nome'] ?? $currentPlanLabel);
        $expiresAt = $currentSubscription['expires_at'] ?? $currentSubscription['trial_ends_at'] ?? null;

        if (!empty($expiresAt)) {
            $today = new DateTimeImmutable('today');
            $expiresDate = new DateTimeImmutable((string) $expiresAt);
            $diffDays = (int) $today->diff($expiresDate)->format('%r%a');
            $currentPlanRemainingLabel = $diffDays < 0
                ? 'expirado'
                : ($diffDays === 0 ? 'vence hoje' : $diffDays . ' dias restantes');
        }
    } catch (Throwable) {
        $currentPlanLabel = 'Free';
        $currentPlanRemainingLabel = 'sem vencimento';
    }

    try {
        $notificationHeaderModel = new NotificationModel($pdo);
        $notificationUserId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $headerNotifications = $notificationHeaderModel->listarRecentes(
            (int) fd_current_workspace_id(),
            $notificationUserId,
            8
        );
        $headerUnreadNotifications = $notificationHeaderModel->contarNaoLidas(
            (int) fd_current_workspace_id(),
            $notificationUserId
        );
    } catch (Throwable) {
        $headerNotifications = [];
        $headerUnreadNotifications = 0;
    }
}

$formatNotificationTime = static function (?string $date): string {
    if (!$date) {
        return '';
    }

    try {
        $createdAt = new DateTimeImmutable($date);
        $now = new DateTimeImmutable('now');
        $seconds = max(0, $now->getTimestamp() - $createdAt->getTimestamp());

        if ($seconds < 60) {
            return 'agora';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . ' min';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600) . ' h';
        }
        if ($seconds < 604800) {
            return floor($seconds / 86400) . ' d';
        }

        return $createdAt->format('d/m/Y');
    } catch (Throwable) {
        return '';
    }
};

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

$hiddenSidebarPreferenceKeys = [];
if ($sidebarModulePreferences !== null) {
    foreach (array_keys($navItems) as $key) {
        $preferenceKey = $navPreferenceMap[$key] ?? $key;

        if ($preferenceKey !== 'configuracoes' && !in_array($preferenceKey, $sidebarModulePreferences, true)) {
            $hiddenSidebarPreferenceKeys[] = $preferenceKey;
        }
    }
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
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/clientes-reference.css?v=20260701-2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">

    <script>
        (() => {
            const savedTheme = localStorage.getItem('flowdesk-theme') || <?= json_encode($userThemePreference, JSON_UNESCAPED_SLASHES) ?> || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
        window.FLOWDESK_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
        window.FLOWDESK_CSRF_TOKEN = <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script defer src="<?= $base ?>/assets/js/modules/date-pickers.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script defer src="<?= $base ?>/assets/js/script.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/confirm-modal.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/task-rich-editor.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/edit-modals.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/dashboard-tasks.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/global-search.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/kanban.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/pipeline-actions.js"></script>
    <script defer src="<?= $base ?>/assets/js/modules/ui-helpers.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body x-data="flowdeskLayout()" x-init="init()">
    <div class="fd-shell" :class="{ 'is-sidebar-compact': sidebarCompact }">
        <aside class="fd-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="fd-sidebar-brand">
                <img src="<?= $base ?>/assets/img/icon.png" alt="FlowDesk" class="fd-sidebar-logo">
                <div class="fd-sidebar-brand-copy">
                    <p class="fd-sidebar-title">FlowDesk</p>
                    <p class="fd-sidebar-subtitle">CRM / SaaS Workspace</p>
                </div>
            </div>

            <nav class="fd-nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <?php $isActiveItem = $mod === $key || ($mod === 'codigo' && $key === 'codigos'); ?>
                    <?php $preferenceKey = $navPreferenceMap[$key] ?? $key; ?>
                    <?php $isHiddenByPreference = in_array($preferenceKey, $hiddenSidebarPreferenceKeys, true); ?>
                    <a href="<?= $item['url'] ?>" class="fd-nav-link <?= $isActiveItem ? 'is-active' : '' ?> <?= $isHiddenByPreference ? 'is-module-hidden' : '' ?>" title="<?= htmlspecialchars($item['label']) ?>" data-sidebar-module="<?= htmlspecialchars($preferenceKey) ?>">
                        <i class="<?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="fd-sidebar-account fd-desktop-sidebar-account">
                <div class="fd-user-menu fd-sidebar-user-menu" x-data="{ open: false }">
                    <button type="button" class="fd-sidebar-account-trigger" @click="open = !open" @click.outside="open = false">
                        <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="fd-sidebar-account-avatar">

                        <span class="fd-sidebar-account-copy">
                            <strong><?= htmlspecialchars($userName) ?></strong>
                            <small>
                                <span class="fd-sidebar-plan-mini"><?= htmlspecialchars($currentPlanLabel) ?></span>
                                <?= htmlspecialchars($currentPlanRemainingLabel) ?>
                            </small>
                        </span>

                        <i class="ri-arrow-up-s-line fd-sidebar-account-arrow"></i>
                    </button>

                    <div class="fd-user-dropdown fd-sidebar-user-dropdown" x-show="open" x-cloak x-transition>
                        <div class="fd-user-dropdown-head">
                            <p class="fd-user-dropdown-name"><?= htmlspecialchars($userName) ?></p>
                            <?php if ($userEmail): ?>
                                <p class="fd-user-dropdown-email"><?= htmlspecialchars($userEmail) ?></p>
                            <?php endif; ?>
                            <span class="fd-sidebar-plan-pill"><?= htmlspecialchars($currentPlanLabel) ?></span>
                        </div>

                        <div class="fd-user-dropdown-body">
                            <div class="fd-sidebar-workspace-current">
                                <span class="fd-workspace-switcher-label">Workspace atual</span>
                                <strong><?= htmlspecialchars($currentWorkspaceName) ?></strong>
                                <span class="fd-badge <?= htmlspecialchars([
                                    'owner' => 'fd-badge-success',
                                    'admin' => 'fd-badge-info',
                                    'financeiro' => 'fd-badge-warning',
                                    'operacional' => 'fd-badge-neutral',
                                    'viewer' => 'fd-badge-neutral',
                                ][$currentWorkspaceRole] ?? 'fd-badge-neutral') ?>">
                                    <?= htmlspecialchars(ucfirst($currentWorkspaceRole)) ?>
                                </span>
                            </div>

                            <?php if (!empty($workspaceOptions)): ?>
                                <div class="fd-sidebar-workspace-list">
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
                            <?php endif; ?>

                            <a href="<?= $base ?>/configuracoes" class="fd-user-dropdown-link">
                                <i class="ri-settings-3-line"></i>
                                <span>Configuracoes</span>
                            </a>

                            <form action="<?= $base ?>/logout" method="post" class="fd-inline-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="fd-user-dropdown-link is-danger">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="fd-overlay" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <div class="fd-main">
            <header class="fd-topbar">
                <div class="fd-topbar-left">
                    <button type="button" class="fd-icon-btn fd-mobile-only" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                        <i class="ri-menu-line"></i>
                    </button>

                    <button type="button" class="fd-icon-btn fd-desktop-only" @click="toggleSidebarCompact()" :aria-label="sidebarCompact ? 'Expandir menu lateral' : 'Compactar menu lateral'">
                        <i :class="sidebarCompact ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'"></i>
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

                    <div class="fd-notification-menu" x-data="{ open: false }">
                        <button
                            type="button"
                            class="fd-icon-btn fd-notification-trigger"
                            @click="open = !open; if (open) window.flowdeskOpenNotifications?.()"
                            @click.outside="open = false"
                            aria-label="Notificacoes"
                            title="Notificacoes"
                        >
                            <i class="ri-notification-3-line"></i>
                            <?php if ($headerUnreadNotifications > 0): ?>
                                <span class="fd-notification-badge" data-notification-badge>
                                    <?= $headerUnreadNotifications > 99 ? '99+' : $headerUnreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <div class="fd-notification-dropdown" x-show="open" x-cloak x-transition>
                            <header class="fd-notification-head">
                                <div>
                                    <strong>Notificações</strong>
                                    <span>Atualizações do seu workspace</span>
                                </div>
                                <?php if ($headerUnreadNotifications > 0): ?>
                                    <small data-notification-unread><?= $headerUnreadNotifications ?> nova<?= $headerUnreadNotifications === 1 ? '' : 's' ?></small>
                                <?php endif; ?>
                            </header>

                            <div class="fd-notification-list">
                                <?php if (!$headerNotifications): ?>
                                    <div class="fd-notification-empty">
                                        <i class="ri-notification-off-line"></i>
                                        <strong>Nenhuma notificação</strong>
                                        <span>As novidades importantes aparecerão aqui.</span>
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($headerNotifications as $notification): ?>
                                    <?php
                                    $notificationUrl = trim((string) ($notification['url'] ?? ''));
                                    if ($notificationUrl === '') {
                                        $notificationUrl = $base . '/dashboard';
                                    } elseif (str_starts_with($notificationUrl, '/') && $base !== '' && !str_starts_with($notificationUrl, $base . '/')) {
                                        $notificationUrl = $base . $notificationUrl;
                                    }
                                    $notificationType = preg_replace('/[^a-z0-9_-]/i', '', (string) ($notification['tipo'] ?? 'info'));
                                    ?>
                                    <a
                                        href="<?= htmlspecialchars($notificationUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        class="fd-notification-item<?= empty($notification['lida_em']) ? ' is-unread' : '' ?>"
                                    >
                                        <span class="fd-notification-icon is-<?= htmlspecialchars($notificationType, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="<?= $notificationType === 'orcamento_confirmado' ? 'ri-checkbox-circle-line' : 'ri-chat-quote-line' ?>"></i>
                                        </span>
                                        <span class="fd-notification-copy">
                                            <strong><?= htmlspecialchars((string) $notification['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($notification['mensagem'])): ?>
                                                <span><?= htmlspecialchars((string) $notification['mensagem'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <small><?= htmlspecialchars($formatNotificationTime((string) ($notification['criada_em'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="fd-language-menu" x-data="{ open: false }">
                        <button type="button" class="fd-icon-btn fd-language-trigger" @click="open = !open" @click.outside="open = false" aria-label="Idioma" title="Idioma">
                            <i class="ri-global-line"></i>
                            <span>BR</span>
                        </button>

                        <div class="fd-user-dropdown fd-language-dropdown" x-show="open" x-cloak x-transition>
                            <div class="fd-user-dropdown-body">
                                <button type="button" class="fd-language-option is-active">
                                    <span class="fd-language-prefix">BR</span>
                                    <span>Português</span>
                                </button>
                                <button type="button" class="fd-language-option">
                                    <span class="fd-language-prefix">US</span>
                                    <span>English</span>
                                </button>
                                <button type="button" class="fd-language-option">
                                    <span class="fd-language-prefix">ES</span>
                                    <span>Español</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="fd-icon-btn" @click="toggleTheme()" aria-label="Alternar tema">
                        <i x-show="theme === 'dark'" x-cloak class="ri-sun-line"></i>
                        <i x-show="theme === 'light'" x-cloak class="ri-moon-clear-line"></i>
                    </button>

                    <div class="fd-user-menu fd-user-menu-topbar" x-data="{ open: false }">
                        <button type="button" class="fd-user-menu-trigger" @click="open = !open" @click.outside="open = false">
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="fd-user-menu-avatar">

                            <div class="fd-user-menu-text">
                                <p class="fd-user-menu-name"><?= htmlspecialchars($userName) ?></p>
                                <p class="fd-user-menu-status"><?= htmlspecialchars($currentPlanLabel) ?></p>
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

                                <form action="<?= $base ?>/logout" method="post" class="fd-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="fd-user-dropdown-link is-danger">
                                        <i class="ri-logout-box-r-line"></i>
                                        <span>Sair</span>
                                    </button>
                                </form>
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
                    const flowdeskUnreadNotifications = <?= json_encode(array_values(array_filter(
                        $headerNotifications,
                        static fn(array $notification): bool => empty($notification['lida_em'])
                    )), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                    window.flowdeskOpenNotifications = async function () {
                        if ('Notification' in window && Notification.permission === 'default') {
                            try {
                                await Notification.requestPermission();
                            } catch (error) {
                                console.debug('Permissao de notificacao nao concedida.', error);
                            }
                        }

                        document.querySelector('[data-notification-badge]')?.remove();
                        document.querySelector('[data-notification-unread]')?.remove();
                        document.querySelectorAll('.fd-notification-item.is-unread').forEach((item) => {
                            item.classList.remove('is-unread');
                        });

                        try {
                            await fetch(<?= json_encode($base . '/notificacoes/marcar-lidas', JSON_UNESCAPED_SLASHES) ?>, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: new URLSearchParams({
                                    csrf_token: window.FLOWDESK_CSRF_TOKEN || ''
                                })
                            });
                        } catch (error) {
                            console.debug('Nao foi possivel marcar as notificacoes como lidas.', error);
                        }
                    };

                    document.addEventListener('DOMContentLoaded', () => {
                        if (!('Notification' in window) || Notification.permission !== 'granted') {
                            return;
                        }

                        const latest = flowdeskUnreadNotifications[0];
                        if (!latest?.id) {
                            return;
                        }

                        const storageKey = `flowdesk-notification-${latest.id}`;
                        if (localStorage.getItem(storageKey)) {
                            return;
                        }

                        const browserNotification = new Notification(latest.titulo, {
                            body: latest.mensagem || 'Existe uma nova atualizacao no seu workspace.'
                        });
                        browserNotification.onclick = () => {
                            window.focus();
                            if (latest.url) {
                                window.location.href = latest.url;
                            }
                        };
                        localStorage.setItem(storageKey, '1');
                    });

                    function flowdeskLayout() {
                        const savedTheme = localStorage.getItem('flowdesk-theme') || <?= json_encode($userThemePreference, JSON_UNESCAPED_SLASHES) ?> || 'dark';
                        const savedSidebarCompact = localStorage.getItem('flowdesk-sidebar-compact') === '1';

                        return {
                            sidebarOpen: false,
                            sidebarCompact: savedSidebarCompact,
                            theme: savedTheme,

                            init() {
                                document.documentElement.setAttribute('data-theme', this.theme);
                            },

                            toggleTheme() {
                                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                                document.documentElement.setAttribute('data-theme', this.theme);
                                localStorage.setItem('flowdesk-theme', this.theme);
                            },

                            toggleSidebarCompact() {
                                this.sidebarCompact = !this.sidebarCompact;
                                localStorage.setItem('flowdesk-sidebar-compact', this.sidebarCompact ? '1' : '0');
                            }
                        };
                    }
                </script>
            </header>

            <main class="fd-content">
