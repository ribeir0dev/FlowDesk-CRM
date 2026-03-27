<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

$pageTitle = $pageTitle ?? 'Onboarding';
$userThemePreference = $_SESSION['user_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(fd_preferred_locale()) ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FlowDesk</title>
    <meta name="theme-color" content="#020617">
    <meta name="description" content="FlowDesk CRM - onboarding inicial do workspace.">

    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">

    <script>
        (() => {
            const savedTheme = localStorage.getItem('flowdesk-theme') || <?= json_encode($userThemePreference, JSON_UNESCAPED_SLASHES) ?> || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
        window.FLOWDESK_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="fd-onboarding-body">
    <main class="fd-onboarding-shell">
