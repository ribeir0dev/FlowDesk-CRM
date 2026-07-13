<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/csrf.php';
require_once __DIR__ . '/../../Helpers/auth.php';
require_once __DIR__ . '/../../Models/OrcamentoModel.php';

$code = strtolower(trim((string) ($_GET['codigo'] ?? '')));
$model = new OrcamentoModel($pdo);
$proposal = $model->buscarPorCodigoPublico($code);

if (!$proposal) {
    http_response_code(404);
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Proposta nao encontrada</title></head><body><h1>Proposta nao encontrada</h1></body></html>';
    exit;
}

$items = $model->buscarItens((int) $proposal['id'], (int) $proposal['workspace_id']);
$services = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuração',
    'stream_overlay' => 'Stream Overlay',
    'criativos' => 'Criativos',
    'identidade_visual' => 'Identidade Visual',
    'ecommerce' => 'E-Commerce',
    'manutencao' => 'Manutenção',
];
$serviceLabel = $services[$proposal['servico_principal']] ?? (string) $proposal['servico_principal'];
$paymentLabel = $proposal['forma_pagamento'] === '50/50'
    ? '50% de Entrada + 50% na Entrega'
    : (string) $proposal['forma_pagamento'];
if ($proposal['forma_pagamento'] === 'Parcelado' && !empty($proposal['parcelas'])) {
    $paymentLabel = (int) $proposal['parcelas'] . 'x parcelado';
}
$installmentLabel = $proposal['forma_pagamento'] === 'Parcelado' && !empty($proposal['parcelas'])
    ? (int) $proposal['parcelas'] . 'x de R$' . number_format((float) $proposal['valor_total'] / (int) $proposal['parcelas'], 2, ',', '.')
    : '1x de R$' . number_format((float) $proposal['valor_total'], 2, ',', '.');
$photo = trim((string) ($proposal['cliente_foto'] ?? ''));
if ($photo !== '' && !filter_var($photo, FILTER_VALIDATE_URL)) {
    $photo = fd_base_path() . '/' . ltrim($photo, '/');
}
$firstName = trim(explode(' ', trim((string) $proposal['cliente_nome']))[0] ?? '');
$overview = trim((string) $proposal['descricao_servico']);
$overview = $overview !== '' ? $overview : 'Uma proposta preparada sob medida para o seu projeto.';
$scopeLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $overview) ?: [])));
if (!$scopeLines) {
    $scopeLines = [$overview];
}
$issueTimestamp = strtotime((string) ($proposal['data_emissao'] ?: $proposal['criado_em']));
$validTimestamp = strtotime((string) $proposal['vencimento']);
$validDays = max(1, (int) ceil(($validTimestamp - $issueTimestamp) / 86400));
$isConfirmed = (string) ($proposal['status'] ?? '') === 'Aprovada';
$publicBase = fd_base_path() . '/proposta/' . e($proposal['public_code']);
$feedbackMessages = [
    'confirmado' => 'Proposta confirmada com sucesso. Obrigado!',
    'ajustes' => 'Solicitação de ajustes enviada com sucesso.',
];
$errorMessages = [
    'csrf' => 'Sua sessão expirou. Atualize a página e tente novamente.',
    'ajustes_vazio' => 'Descreva os ajustes desejados antes de enviar.',
    'confirmar' => 'Não foi possível confirmar a proposta agora.',
    'ajustes' => 'Não foi possível enviar a solicitação agora.',
    'acao' => 'Ação inválida para esta proposta.',
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($proposal['codigo']) ?> | FlowDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --bg: #07101f;
            --panel: #111b2b;
            --panel-2: #162235;
            --line: #27364b;
            --text: #f8fafc;
            --muted: #9aa9bd;
            --blue: #2764f5;
            --green: #13d494;
            --yellow: #facc15;
        }
        * { box-sizing: border-box; }
        html { background: var(--bg); }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 48% -20%, rgba(36, 99, 235, .13), transparent 36%),
                linear-gradient(180deg, #07101f 0%, #0b1423 100%);
        }
        button, a { font: inherit; }
        .public-shell { width: min(1540px, calc(100% - 56px)); margin: 0 auto; padding: 16px 0 34px; }
        .public-topbar {
            height: 48px;
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
        }
        .public-brand { display: flex; align-items: center; gap: 12px; color: #fff; font-size: 20px; font-weight: 800; }
        .public-brand-mark { color: #2d67ff; font-size: 28px; transform: skewX(-8deg); }
        .public-security {
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            border: 1px solid rgba(148, 163, 184, .14);
            border-radius: 9px;
            color: #cbd5e1;
            background: rgba(13, 23, 38, .62);
        }
        .public-security > span, .public-security-actions, .public-security button {
            display: flex;
            align-items: center;
            gap: 9px;
        }
        .public-security-actions { gap: 28px; }
        .public-security button {
            padding: 0;
            border: 0;
            color: #e2e8f0;
            background: transparent;
            cursor: pointer;
        }
        .public-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.18fr) minmax(340px, .96fr);
            gap: 24px;
            margin-top: 14px;
            align-items: start;
        }
        .public-main { display: grid; gap: 9px; }
        .public-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(23, 35, 53, .96), rgba(14, 24, 39, .96));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.018);
        }
        .proposal-hero { padding: 17px 28px 0; }
        .proposal-hero-top { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 20px; align-items: start; }
        .eyebrow { margin: 0 0 9px; color: #67a2ff; font-size: 11px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }
        h1 { margin: 0 0 4px; font-size: 29px; line-height: 1.08; letter-spacing: -.035em; }
        .proposal-code-line { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid rgba(250, 204, 21, .2);
            border-radius: 999px;
            color: var(--yellow);
            background: rgba(120, 86, 0, .34);
            font-size: 12px;
            font-weight: 700;
        }
        .proposal-greeting { margin: 10px 0 15px; color: #c0cad7; font-size: 13px; }
        .client-inline { display: flex; align-items: center; gap: 13px; min-width: 260px; height: stretch; justify-items: center; }
        .client-inline img, .client-avatar {
            width: 62px; height: 62px; border-radius: 50%; object-fit: cover;
            display: grid; place-items: center; background: #21304a; color: #93c5fd; font-weight: 800;
        }
        .client-inline div { display: grid; gap: 4px; }
        .client-inline strong { font-size: 17px; }
        .client-inline span { color: var(--muted); font-size: 12px; }
        .proposal-dates {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border-top: 1px solid var(--line);
        }
        .proposal-date { display: grid; grid-template-columns: 28px 1fr; gap: 10px; align-items: center; padding: 13px 12px; }
        .proposal-date + .proposal-date { border-left: 1px solid var(--line); }
        .proposal-date i { color: #b7c5d8; font-size: 22px; }
        .proposal-date small { display: block; color: var(--muted); font-size: 11px; }
        .proposal-date strong { display: block; margin-top: 2px; font-size: 13px; }
        .section-card {
            display: grid;
            grid-template-columns: 66px minmax(0, 1fr);
            min-height: 76px;
            padding: 15px 0px;
        }
        .section-icon {
            display: grid;
            place-items: center;
            border-right: 1px solid var(--line);
            color: #8fb7ff;
            font-size: 26px;
            align-content: start;
            padding: 15px 0px;
            margin-left: 10px;
            background: unset;
        }
        .section-body { padding: 10px 17px 11px; min-width: 0; }
        .section-title { display: flex; align-items: center; gap: 8px; margin: 0 0 15px; font-size: 14px; }
        .section-number {
            width: 19px; height: 19px; display: grid; place-items: center;
            border: 1px solid #3478ff; border-radius: 7px;
            color: #fff; background: #1d4fb4; font-size: 11px;
        }
        .section-body p { margin: 0; color: #aebacc; font-size: 12px; line-height: 1.45; }
        .proposal-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .proposal-table th {
            padding: 6px 14px; color: #9eacc0; font-size: 9px; font-weight: 600;
            text-align: left; text-transform: uppercase; border: 1px solid var(--line);
        }
        .proposal-table td { padding: 6px 14px; border: 1px solid var(--line); }
        .proposal-table th:nth-child(n+2), .proposal-table td:nth-child(n+2) { text-align: right; }
        .proposal-table tfoot td { font-weight: 700; }
        .scope-list { display: grid; gap: 4px; }
        .scope-list span { color: #acb8c9; font-size: 11px; }
        .scope-list i { margin-right: 8px; color: #b9c8dc; }
        .process { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .process-step { position: relative; display: grid; grid-template-columns: 26px 1fr; gap: 9px; color: #c8d2df; font-size: 11px; }
        .process-step:not(:last-child)::after {
            content: ""; position: absolute; top: 12px; left: calc(100% - 36px);
            width: 50px; border-top: 1px solid #3a6bbd;
        }
        .process-step > span { width: 25px; height: 25px; display: grid; place-items: center; border: 1px solid #4a70a7; border-radius: 50%; background: #20324c; }
        .process-step strong { display: block; margin-bottom: 4px; font-size: 11px; }
        .process-step small { color: #8f9db0; line-height: 1.35; }
        .faq-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .faq-actions { display: flex; gap: 8px; }
        .faq-actions a {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px;
            border: 1px solid var(--line); border-radius: 7px; color: #e6edf7;
            background: rgba(17, 28, 45, .7); text-decoration: none; font-size: 11px;
        }
        .summary-card { position: sticky; top: 16px; padding: 20px 22px; }
        .summary-card h2 { margin: 0; padding-bottom: 14px; border-bottom: 1px solid var(--line); font-size: 17px; }
        .summary-total { padding: 17px 2px 13px; }
        .summary-total small { color: #b3bfd0; font-size: 14px; }
        .summary-total strong { display: block; margin-top: 4px; color: var(--green); font-size: 40px; letter-spacing: -.04em; }
        .summary-facts { display: grid; gap: 14px; padding: 10px 0 17px; border-bottom: 1px solid var(--line); }
        .summary-fact { display: grid; grid-template-columns: 32px 1fr; gap: 10px; align-items: center; }
        .summary-fact > i { width: 31px; height: 31px; display: grid; place-items: center; border-radius: 50%; background: #202d41; color: #c2d0e2; }
        .summary-fact small { display: block; color: var(--muted); font-size: 11px; }
        .summary-fact strong { display: block; margin-top: 2px; font-size: 14px; }
        .summary-contact { padding: 14px 0; }
        .summary-contact > small { display: block; margin-bottom: 11px; color: #cbd5e1; }
        .summary-contact .client-inline { min-width: 0; }
        .summary-contact .client-inline img, .summary-contact .client-avatar { width: 50px; height: 50px; }
        .summary-actions { display: grid; gap: 9px; }
        .summary-action {
            min-height: 58px; display: flex; align-items: center; justify-content: center; gap: 12px;
            padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px;
            color: #f8fafc; background: #172235; text-decoration: none; cursor: pointer;
        }
        .summary-action.is-primary { border-color: #2e6bff; background: linear-gradient(135deg, #2764f5, #1f58df); }
        .summary-action i { font-size: 24px; }
        .summary-action span { display: grid; text-align: left; }
        .summary-action strong { font-size: 14px; }
        .summary-action small { margin-top: 2px; color: #c8d4e4; font-size: 11px; }
        .terms-box {
            display: flex; gap: 10px; margin-top: 10px; padding: 12px;
            border: 1px solid var(--line); border-radius: 8px; color: #aeb9c9; font-size: 10px; line-height: 1.4;
        }
        .terms-box input { margin-top: 2px; accent-color: var(--blue); }
        .secure-note { margin: 12px 0 0; color: #8391a5; font-size: 10px; line-height: 1.55; }
        .copy-toast {
            position: fixed; right: 24px; bottom: 24px; z-index: 20;
            padding: 12px 16px; border: 1px solid #315697; border-radius: 9px;
            color: #fff; background: #142a4e; opacity: 0; transform: translateY(12px);
            transition: .2s ease; pointer-events: none;
        }
        .copy-toast.is-visible { opacity: 1; transform: none; }
        .status-badge.is-approved {
            border-color: rgba(19, 212, 148, .25);
            color: var(--green);
            background: rgba(19, 212, 148, .12);
        }
        .summary-action-form { margin: 0; }
        .summary-action-form .summary-action { width: 100%; }
        .summary-confirmed-state {
            display: grid;
            gap: 8px;
            margin-bottom: 10px;
            padding: 16px;
            border: 1px solid rgba(19, 212, 148, .22);
            border-radius: 10px;
            color: #dffcf1;
            background: rgba(19, 212, 148, .1);
        }
        .summary-confirmed-state i {
            width: 34px; height: 34px; display: grid; place-items: center;
            border-radius: 50%; color: var(--green); background: rgba(19, 212, 148, .16);
            font-size: 20px;
        }
        .summary-confirmed-state strong { font-size: 16px; }
        .summary-confirmed-state small { color: #9adcc8; line-height: 1.45; }
        .proposal-feedback {
            margin: 14px 0 0;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }
        .proposal-feedback.is-success {
            border: 1px solid rgba(19, 212, 148, .25);
            color: #baf7e4;
            background: rgba(19, 212, 148, .1);
        }
        .proposal-feedback.is-error {
            border: 1px solid rgba(239, 68, 68, .28);
            color: #fecaca;
            background: rgba(239, 68, 68, .1);
        }
        .proposal-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            place-items: center;
            padding: 20px;
            background: rgba(2, 6, 23, .76);
            backdrop-filter: blur(10px);
        }
        .proposal-modal-backdrop.is-open { display: grid; }
        .proposal-modal {
            width: min(520px, 100%);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(23, 35, 53, .98), rgba(14, 24, 39, .98));
            box-shadow: 0 24px 70px rgba(0, 0, 0, .38);
        }
        .proposal-modal header,
        .proposal-modal footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
        }
        .proposal-modal footer {
            justify-content: flex-end;
            border-top: 1px solid var(--line);
            border-bottom: 0;
        }
        .proposal-modal h3 { margin: 0; font-size: 18px; }
        .proposal-modal-body { padding: 20px; }
        .proposal-modal label { display: grid; gap: 8px; color: var(--muted); font-size: 13px; font-weight: 700; }
        .proposal-modal textarea {
            width: 100%;
            min-height: 150px;
            resize: vertical;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            color: var(--text);
            background: rgba(9, 15, 26, .8);
            outline: none;
        }
        .proposal-modal-close,
        .proposal-modal-secondary,
        .proposal-modal-primary {
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--text);
            background: rgba(30, 41, 59, .65);
            cursor: pointer;
        }
        .proposal-modal-close { width: 38px; height: 38px; }
        .proposal-modal-secondary,
        .proposal-modal-primary { min-height: 44px; padding: 0 18px; font-weight: 800; }
        .proposal-modal-primary { border-color: #2e6bff; background: linear-gradient(135deg, #2764f5, #1f58df); }
        @media (max-width: 980px) {
            .public-shell { width: min(100% - 28px, 760px); }
            .public-topbar { height: auto; grid-template-columns: 1fr; padding: 12px 0; }
            .public-security { min-height: 42px; height: auto; }
            .public-grid { grid-template-columns: 1fr; }
            .summary-card { position: static; }
        }
        @media (max-width: 640px) {
            .public-shell { width: 100%; padding: 0 12px 20px; }
            .public-brand { padding: 10px 4px 2px; }
            .public-security { padding: 0 12px; }
            .public-security > span { font-size: 11px; }
            .public-security-actions { gap: 12px; }
            .public-security-actions button span { display: none; }
            .proposal-hero { padding: 20px 18px 0; }
            .proposal-hero-top { grid-template-columns: 1fr; }
            .client-inline { min-width: 0; }
            .proposal-dates { grid-template-columns: 1fr; }
            .proposal-date + .proposal-date { border-left: 0; border-top: 1px solid var(--line); }
            .section-card { grid-template-columns: 46px minmax(0, 1fr); }
            .section-icon { font-size: 21px; }
            .section-body { padding: 12px; }
            .proposal-table { min-width: 560px; }
            .table-scroll { overflow-x: auto; }
            .process { grid-template-columns: 1fr 1fr; }
            .process-step:not(:last-child)::after { display: none; }
            .faq-row { align-items: flex-start; flex-direction: column; }
            .faq-actions { width: 100%; flex-direction: column; }
            .faq-actions a { justify-content: center; }
            .summary-total strong { font-size: 34px; }
        }
        @media print {
            body { background: #07101f; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .public-shell { width: 100%; }
            .public-security-actions, .summary-actions, .terms-box { display: none; }
        }
    </style>
</head>
<body>
<main class="public-shell">
    <header class="public-topbar">
        <div class="public-brand"><i class="ri-flashlight-fill public-brand-mark"></i> FlowDesk</div>
        <div class="public-security">
            <span><i class="ri-shield-check-line"></i> Proposta segura e confiável</span>
            <div class="public-security-actions">
                                <button type="button" data-share-proposal><i class="ri-share-line"></i><span>Compartilhar</span></button>
            </div>
        </div>
    </header>

    <div class="public-grid">
        <section class="public-main">
            <article class="public-card proposal-hero">
                <div class="proposal-hero-top">
                    <div>
                        <p class="eyebrow">Proposta comercial</p>
                        <h1>Proposta Comercial</h1>
                        <div class="proposal-code-line">
                            <span><?= e($proposal['codigo']) ?></span>
                            <span class="status-badge<?= $isConfirmed ? ' is-approved' : '' ?>">
                                <i class="<?= $isConfirmed ? 'ri-checkbox-circle-line' : 'ri-time-line' ?>"></i>
                                <?= $isConfirmed ? 'Proposta Confirmada' : e($proposal['status']) ?>
                            </span>
                        </div>
                        <p class="proposal-greeting">Olá <?= e($firstName) ?>, preparamos uma proposta sob medida para o seu projeto de <?= e($serviceLabel) ?>.</p>
                    </div>
                    <div class="client-inline">
                        <?php if ($photo): ?>
                            <img src="<?= e($photo) ?>" alt="Foto de <?= e($proposal['cliente_nome']) ?>">
                        <?php else: ?>
                            <span class="client-avatar"><?= e(mb_strtoupper(mb_substr($proposal['cliente_nome'], 0, 2))) ?></span>
                        <?php endif; ?>
                        <div><strong><?= e($proposal['cliente_nome']) ?></strong><span><?= e($proposal['cliente_email'] ?: 'Cliente FlowDesk') ?></span></div>
                    </div>
                </div>
                <div class="proposal-dates">
                    <div class="proposal-date"><i class="ri-calendar-line"></i><div><small>Data de emissão</small><strong><?= date('d/m/Y', $issueTimestamp) ?></strong></div></div>
                    <div class="proposal-date"><i class="ri-calendar-check-line"></i><div><small>Validade da proposta</small><strong><?= date('d/m/Y', $validTimestamp) ?> (<?= $validDays ?> dias)</strong></div></div>
                    <div class="proposal-date"><i class="ri-time-line"></i><div><small>Prazo estimado de entrega</small><strong><?= (int) $proposal['prazo_estimado_dias'] ?> dias úteis</strong></div></div>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-pages-line"></i></div>
                <div class="section-body">
                    <h2 class="section-title"><span class="section-number">1</span>Visão geral do projeto</h2>
                    <p><?= nl2br(e($overview)) ?></p>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-list-check-3"></i></div>
                <div class="section-body">
                    <h2 class="section-title"><span class="section-number">2</span>Itens da proposta</h2>
                    <div class="table-scroll">
                        <table class="proposal-table">
                            <thead><tr><th>Descrição do item</th><th>Qtd.</th><th>Valor unitário</th><th>Subtotal</th></tr></thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e($item['descricao']) ?></td>
                                    <td><?= number_format((float) $item['quantidade'], 0, ',', '.') ?></td>
                                    <td>R$<?= number_format((float) $item['valor_unitario'], 2, ',', '.') ?></td>
                                    <td>R$<?= number_format((float) ($item['subtotal'] ?: $item['valor']), 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr><td colspan="3">TOTAL</td><td>R$<?= number_format((float) $proposal['valor_total'], 2, ',', '.') ?></td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-focus-3-line"></i></div>
                <div class="section-body">
                    <h2 class="section-title"><span class="section-number">3</span>Escopo / Entregáveis</h2>
                    <div class="scope-list">
                        <?php foreach (array_slice($scopeLines, 0, 5) as $line): ?><span><i class="ri-checkbox-circle-line"></i><?= e($line) ?></span><?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-calendar-schedule-line"></i></div>
                <div class="section-body">
                    <h2 class="section-title"><span class="section-number">4</span>Prazo e processo</h2>
                    <div class="process">
                        <div class="process-step"><span>1</span><div><strong>Briefing</strong><small>Coleta de informações e alinhamento</small></div></div>
                        <div class="process-step"><span>2</span><div><strong>Desenvolvimento</strong><small>Criação e implementação</small></div></div>
                        <div class="process-step"><span>3</span><div><strong>Revisão</strong><small>Ajustes e validação com o cliente</small></div></div>
                        <div class="process-step"><span>4</span><div><strong>Entrega</strong><small>Publicação e entrega final</small></div></div>
                    </div>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-file-shield-2-line"></i></div>
                <div class="section-body">
                    <h2 class="section-title"><span class="section-number">5</span>Observações e condições</h2>
                    <p>Esta proposta é válida pelo prazo indicado acima e contempla todos os itens descritos neste documento.<br>Alterações de escopo poderão impactar prazo e investimento.</p>
                </div>
            </article>

            <article class="public-card section-card">
                <div class="section-icon"><i class="ri-contacts-book-3-line"></i></div>
                <div class="section-body faq-row">
                    <div><h2 class="section-title"><span class="section-number">6</span>Dúvidas frequentes</h2><p>Ficou com alguma dúvida? Nossa equipe está pronta para ajudar.</p></div>
                    <div class="faq-actions">
                        <a href="https://wa.me/5535997202531"><i class="ri-chat-3-line"></i>Abrir chat</a>
                        <a href="mailto:suporte@flowdesk.site"><i class="ri-mail-line"></i>Enviar mensagem</a>
                    </div>
                </div>
            </article>
        </section>

        <aside class="public-card summary-card">
            <h2>Resumo da proposta</h2>
            <?php foreach ($feedbackMessages as $key => $message): ?>
                <?php if (isset($_GET[$key])): ?>
                    <div class="proposal-feedback is-success"><i class="ri-checkbox-circle-line"></i><?= e($message) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php $publicError = trim((string) ($_GET['erro'] ?? '')); ?>
            <?php if ($publicError !== ''): ?>
                <div class="proposal-feedback is-error"><i class="ri-error-warning-line"></i><?= e($errorMessages[$publicError] ?? 'Não foi possível concluir a ação.') ?></div>
            <?php endif; ?>
            <div class="summary-total"><small>Total da proposta</small><strong>R$<?= number_format((float) $proposal['valor_total'], 2, ',', '.') ?></strong></div>
            <div class="summary-facts">
                <div class="summary-fact"><i class="ri-bank-card-line"></i><div><small>Forma de pagamento</small><strong><?= e($paymentLabel) ?></strong></div></div>
                <div class="summary-fact"><i class="ri-calendar-event-line"></i><div><small>Validade da proposta</small><strong><?= date('d/m/Y', $validTimestamp) ?> (<?= $validDays ?> dias)</strong></div></div>
                <div class="summary-fact"><i class="ri-file-list-3-line"></i><div><small>Parcelas</small><strong><?= e($installmentLabel) ?></strong></div></div>
            </div>
            <div class="summary-contact">
                <small>Contato</small>
                <div class="client-inline">
                    <?php if ($photo): ?>
                        <img src="<?= e($photo) ?>" alt="Foto de <?= e($proposal['cliente_nome']) ?>">
                    <?php else: ?>
                        <span class="client-avatar"><?= e(mb_strtoupper(mb_substr($proposal['cliente_nome'], 0, 2))) ?></span>
                    <?php endif; ?>
                    <div><strong><?= e($proposal['cliente_nome']) ?></strong><span><i class="ri-phone-line"></i> <?= e($proposal['cliente_whatsapp'] ?: 'Não informado') ?></span></div>
                </div>
            </div>
            <div class="summary-actions">
                <?php if ($isConfirmed): ?>
                    <div class="summary-confirmed-state">
                        <i class="ri-checkbox-circle-fill"></i>
                        <span><strong>Proposta Confirmada</strong><small>A confirmação foi registrada e enviada ao responsável.</small></span>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= $publicBase ?>/confirmar" class="summary-action-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="summary-action is-primary"><i class="ri-checkbox-circle-line"></i><span><strong>Confirmar proposta</strong><small>Confirmar e iniciar o projeto</small></span></button>
                    </form>
                    <button type="button" class="summary-action" data-open-adjustments><i class="ri-chat-search-line"></i><span><strong>Solicitar ajustes</strong><small>Quero solicitar alterações</small></span></button>
                <?php endif; ?>
                <button type="button" class="summary-action" onclick="window.print()"><i class="ri-download-line"></i><span><strong>Baixar PDF</strong><small>Salvar proposta completa</small></span></button>
            </div>
            <?php if (!$isConfirmed): ?>
                <p class="terms-box"><i class="ri-information-line"></i><span>Ao confirmar esta proposta, você concorda com os termos, condições e escopo descritos neste documento.</span></p>
            <?php endif; ?>
            <p class="secure-note"><i class="ri-lock-line"></i> Proposta protegida e criptografada. Seus dados estão seguros conosco.</p>
        </aside>
    </div>
</main>
<div class="proposal-modal-backdrop" data-adjustments-modal aria-hidden="true">
    <form method="post" action="<?= $publicBase ?>/ajustes" class="proposal-modal">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <header>
            <div>
                <p class="eyebrow">Solicitação do cliente</p>
                <h3>Solicitar ajustes</h3>
            </div>
            <button type="button" class="proposal-modal-close" data-close-adjustments aria-label="Fechar"><i class="ri-close-line"></i></button>
        </header>
        <div class="proposal-modal-body">
            <label>
                Descreva o que você gostaria de alterar
                <textarea name="mensagem" minlength="5" maxlength="4000" required placeholder="Ex.: Gostaria de ajustar o prazo, substituir um item ou revisar uma condição da proposta."></textarea>
            </label>
        </div>
        <footer>
            <button type="button" class="proposal-modal-secondary" data-close-adjustments>Cancelar</button>
            <button type="submit" class="proposal-modal-primary"><i class="ri-send-plane-line"></i> Enviar solicitação</button>
        </footer>
    </form>
</div>
<div class="copy-toast" data-copy-toast>Link copiado com sucesso.</div>
<script>
document.querySelector('[data-share-proposal]')?.addEventListener('click', async () => {
    const toast = document.querySelector('[data-copy-toast]');
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast.classList.add('is-visible');
        setTimeout(() => toast.classList.remove('is-visible'), 2200);
    } catch (error) {
        window.prompt('Copie o link da proposta:', window.location.href);
    }
});

const adjustmentsModal = document.querySelector('[data-adjustments-modal]');
const setAdjustmentsModal = (isOpen) => {
    if (!adjustmentsModal) return;
    adjustmentsModal.classList.toggle('is-open', isOpen);
    adjustmentsModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    document.body.style.overflow = isOpen ? 'hidden' : '';
    if (isOpen) adjustmentsModal.querySelector('textarea')?.focus();
};

document.querySelector('[data-open-adjustments]')?.addEventListener('click', () => setAdjustmentsModal(true));
document.querySelectorAll('[data-close-adjustments]').forEach((button) => {
    button.addEventListener('click', () => setAdjustmentsModal(false));
});
adjustmentsModal?.addEventListener('click', (event) => {
    if (event.target === adjustmentsModal) setAdjustmentsModal(false);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setAdjustmentsModal(false);
});
</script>
</body>
</html>
