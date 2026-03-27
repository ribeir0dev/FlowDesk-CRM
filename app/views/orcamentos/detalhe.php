<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/ClienteModel.php';
require_once __DIR__ . '/../../../app/Models/OrcamentoModel.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Orcamento nao encontrado.');
}

$orcModel = new OrcamentoModel($pdo);
$clienteModel = new ClienteModel($pdo);

$orcamento = $orcModel->buscarPorId($id);
if (!$orcamento) {
    http_response_code(404);
    exit('Orcamento nao encontrado.');
}

$cliente = $clienteModel->buscarPorId((int) $orcamento['cliente_id']);
if (!$cliente) {
    http_response_code(404);
    exit('Cliente nao encontrado.');
}

$itens = $orcModel->buscarItens($id);
$servico = $orcamento['servico_principal'] ?? '';

$servicoLabels = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuracao',
    'stream_overlay' => 'Stream Overlay',
    'criativos' => 'Criativos',
    'identidade_visual' => 'Identidade Visual',
];

$servicoIcons = [
    'landing_page' => 'ri-pages-fill',
    'configuracao' => 'ri-tools-line',
    'stream_overlay' => 'ri-tv-2-line',
    'criativos' => 'ri-brush-line',
    'identidade_visual' => 'ri-palette-line',
];

$servicoColors = [
    'landing_page' => '#CEE7FF',
    'configuracao' => '#D3D3D3',
    'stream_overlay' => '#FFFBCE',
    'criativos' => '#FECEFF',
    'identidade_visual' => '#D5CEFF',
];

$label = $servicoLabels[$servico] ?? $servico;
$icon = $servicoIcons[$servico] ?? 'ri-file-text-line';
$bg = $servicoColors[$servico] ?? '#F3F4F6';
$dataOrcamento = !empty($orcamento['criado_em']) ? date('d/m/Y', strtotime($orcamento['criado_em'])) : date('d/m/Y');
$codigoStr = str_pad((string) $orcamento['codigo'], 4, '0', STR_PAD_LEFT);
$nomeClienteParaArquivo = preg_replace('/[^\w\- ]+/u', '', $cliente['nome']);
$nomeArquivoPdf = "Orcamento - #{$codigoStr} - {$nomeClienteParaArquivo}.pdf";
$primeiraLetra = strtoupper(substr(trim((string) ($cliente['nome'] ?? '')), 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orcamento #<?= htmlspecialchars($orcamento['codigo']) ?> - <?= htmlspecialchars($cliente['nome']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --card: #ffffff;
            --line: #dbe3f0;
            --text: #0f172a;
            --muted: #64748b;
            --brand: #2563eb;
            --brand-soft: #dbeafe;
            --success: #16a34a;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #eef4ff 0%, var(--bg) 100%);
            color: var(--text);
        }
        .page {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .topbar, .meta-row, .client-row, .actions, .total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .brand-badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--brand-soft);
            color: var(--brand);
            font-size: 20px;
        }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: var(--text); color: #fff; }
        .btn-secondary { background: #fff; color: var(--text); border: 1px solid var(--line); }
        .sheet {
            margin-top: 24px;
            background: var(--card);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08);
            color: var(--text);
        }
        .eyebrow {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        h1, h2, h3, p { margin: 0; }
        .sheet h1,
        .sheet h2,
        .sheet h3,
        .sheet strong,
        .sheet td,
        .sheet th,
        .sheet span,
        .sheet div {
            color: inherit;
        }
        .title { font-size: 32px; margin-bottom: 6px; }
        .title,
        .client h2,
        .meta-row strong,
        tbody td {
            color: var(--text);
        }
        .subtitle { color: var(--muted); }
        .client {
            margin-top: 28px;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: #f8fbff;
        }
        .client-row { align-items: center; }
        .avatar, .avatar-fallback {
            width: 72px;
            height: 72px;
            border-radius: 20px;
        }
        .avatar { object-fit: cover; }
        .avatar-fallback {
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1d4ed8, #0f172a);
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }
        .client-main {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .meta-row {
            margin-top: 22px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px solid var(--line);
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            color: #0b1924;
        }
        .description {
            margin-top: 18px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid var(--line);
            line-height: 1.7;
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid var(--line);
        }
        thead th {
            background: #f8fafc;
            color: var(--muted);
            text-align: left;
            padding: 14px 16px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        tbody td {
            padding: 16px;
            border-top: 1px solid var(--line);
            vertical-align: top;
        }
        .total-row {
            margin-top: 18px;
            justify-content: flex-end;
        }
        .total-card {
            min-width: 260px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #eff6ff;
            color: var(--brand);
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            gap: 18px;
        }
        .sheet .chip,
        .sheet .total-card,
        .sheet .total-card span {
            color: #0b1924;
        }
        .sheet .total-card span:last-child {
            color: var(--brand);
        }
        #share-alert {
            display: none;
            position: sticky;
            top: 16px;
            z-index: 10;
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 14px;
            color: #fff;
            background: var(--success);
        }
        #share-alert.error { background: var(--danger); }
        @media (max-width: 720px) {
            .page { padding: 20px 14px 36px; }
            .sheet { padding: 20px; border-radius: 22px; }
            .title { font-size: 26px; }
            .client-main { align-items: flex-start; }
            .total-card { width: 100%; }
        }
        @media print {
            body { background: #fff; }
            .page { padding: 0; max-width: none; }
            .topbar, #share-alert, .actions { display: none !important; }
            .sheet { box-shadow: none; border: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div id="share-alert"></div>

        <div class="topbar">
            <div>
                <div class="brand">
                    <span class="brand-badge"><i class="ri-file-list-3-line"></i></span>
                    <span>FlowDesk Proposal</span>
                </div>
            </div>

            <div class="btn-group actions">
                <button type="button" class="btn btn-secondary" onclick="baixarPdf()">
                    <i class="ri-file-download-line"></i>
                    <span>Salvar PDF</span>
                </button>
                <button type="button" class="btn btn-primary" onclick="compartilharLink()">
                    <i class="ri-share-forward-line"></i>
                    <span>Compartilhar</span>
                </button>
            </div>
        </div>

        <div class="sheet">
            <p class="eyebrow">Proposta comercial</p>
            <h1 class="title">Orcamento #<?= htmlspecialchars($orcamento['codigo']) ?></h1>
            <p class="subtitle">Gerado em <?= htmlspecialchars($dataOrcamento) ?> para <?= htmlspecialchars($cliente['nome']) ?>.</p>

            <section class="client">
                <div class="client-row">
                    <div class="client-main">
                        <?php if (!empty($cliente['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?>" alt="Foto do cliente" class="avatar">
                        <?php else: ?>
                            <div class="avatar-fallback"><?= $primeiraLetra ?: '?' ?></div>
                        <?php endif; ?>

                        <div>
                            <p class="eyebrow">Cliente</p>
                            <h2><?= htmlspecialchars($cliente['nome']) ?></h2>
                            <p class="subtitle"><?= !empty($cliente['whatsapp']) ? htmlspecialchars($cliente['whatsapp']) : 'Telefone nao informado' ?></p>
                        </div>
                    </div>

                    <span class="chip" style="background-color: <?= htmlspecialchars($bg) ?>;">
                        <i class="<?= htmlspecialchars($icon) ?>"></i>
                        <?= htmlspecialchars((string) $label) ?>
                    </span>
                </div>
            </section>

            <section class="meta-row">
                <div>
                    <p class="eyebrow">Codigo</p>
                    <strong>#<?= htmlspecialchars($codigoStr) ?></strong>
                </div>
                <div>
                    <p class="eyebrow">Pagamento</p>
                    <strong><?= htmlspecialchars($orcamento['forma_pagamento'] ?? 'Nao informado') ?></strong>
                </div>
                <div>
                    <p class="eyebrow">Status</p>
                    <strong><?= htmlspecialchars($orcamento['status'] ?? 'Enviado') ?></strong>
                </div>
            </section>

            <section class="description">
                <?= nl2br(htmlspecialchars($orcamento['descricao_servico'])) ?>
            </section>

            <table>
                <thead>
                    <tr>
                        <th>Descricao</th>
                        <th style="width: 180px;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($itens)): ?>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['descricao']) ?></td>
                                <td>
                                    <?php if ((float) $item['valor'] == 0.0): ?>
                                        <strong style="color: var(--success);">INCLUSO</strong>
                                    <?php else: ?>
                                        R$<?= number_format((float) $item['valor'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nenhum item lancado para este orcamento.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="total-row">
                <div class="total-card">
                    <span>Valor total</span>
                    <span>R$<?= number_format((float) $orcamento['valor_total'], 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function mostrarShareAlert(msg, tipo = 'success') {
            const el = document.getElementById('share-alert');
            if (!el) return;
            el.textContent = msg;
            el.className = tipo === 'error' ? 'error' : '';
            el.style.display = 'block';
            setTimeout(() => { el.style.display = 'none'; }, 2500);
        }

        async function compartilharLink() {
            const url = window.location.href;
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = url;
                    temp.style.position = 'fixed';
                    temp.style.left = '-9999px';
                    document.body.appendChild(temp);
                    temp.focus();
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }
                mostrarShareAlert('Link copiado para a area de transferencia.');
            } catch (e) {
                console.error(e);
                mostrarShareAlert('Nao foi possivel copiar o link. Copie manualmente.', 'error');
            }
        }

        async function baixarPdf() {
            if (!window.jspdf) return;
            const { jsPDF } = window.jspdf;
            const area = document.querySelector('.sheet');
            const canvas = await html2canvas(area, { backgroundColor: '#ffffff', scale: 2 });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgWidth = pageWidth;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let position = 0;
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            while (imgHeight - position > pageHeight) {
                position -= pageHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            }
            pdf.save('<?= addslashes($nomeArquivoPdf) ?>');
        }
    </script>
</body>
</html>
