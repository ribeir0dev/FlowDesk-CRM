<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard | FlowDesk' ?></title>

    <link rel="icon" href="/favicon.ico">
    <link id="theme-dark" rel="stylesheet" href="/assets/css/painel.css">
    <link id="theme-claro" rel="stylesheet" href="/assets/css/painel_claro.css" disabled>
    <link id="theme-modern" rel="stylesheet" href="/assets/css/painel_moderno.css" disabled>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Opcional: tema mais parecido com o teu design -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- JS principal do projeto (define initKanbanDragDrop, initModalOrcamento, etc.) -->
    <script src="/assets/js/scripts.js"></script>
</head>

<body class="bg-dark-subtle">