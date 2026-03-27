<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base === '/' || $base === '\\') {
    $base = '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'FlowDesk' ?></title>
    <meta name="description" content="FlowDesk CRM - plataforma de gestao para clientes, projetos, pipeline e financeiro.">
    <meta name="theme-color" content="#020617">

    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">

    <script>
        (() => {
            const savedTheme = localStorage.getItem('flowdesk-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
        window.FLOWDESK_BASE = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
