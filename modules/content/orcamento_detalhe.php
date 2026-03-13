<?php
// orcamento_detalhe.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';
require_once __DIR__ . '/../../app/Models/OrcamentoModel.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Orçamento não encontrado.');
}

$orcModel = new OrcamentoModel($pdo);
$clienteModel = new ClienteModel($pdo);

// orçamento
$orcamento = $orcModel->buscarPorId($id);
if (!$orcamento) {
    http_response_code(404);
    exit('Orçamento não encontrado.');
}

// cliente
$cliente = $clienteModel->buscarPorId((int) $orcamento['cliente_id']);
if (!$cliente) {
    http_response_code(404);
    exit('Cliente não encontrado.');
}

// itens
$itens = $orcModel->buscarItens($id);

// mapeia tipo de serviço para label / ícone / cor
$servico = $orcamento['servico_principal'] ?? '';

$servicoLabels = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuração',
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
    'landing_page' => ['bg' => '#CEE7FF'],
    'configuracao' => ['bg' => '#D3D3D3'],
    'stream_overlay' => ['bg' => '#FFFBCE'],
    'criativos' => ['bg' => '#FECEFF'],
    'identidade_visual' => ['bg' => '#D5CEFF'],
];

$label = $servicoLabels[$servico] ?? $servico;
$icon = $servicoIcons[$servico] ?? 'ri-file-text-line';
$bg = $servicoColors[$servico]['bg'] ?? '#F3F4F6';


// data do orçamento
$dataOrcamento = !empty($orcamento['criado_em'])
    ? date('d/m/Y', strtotime($orcamento['criado_em']))
    : date('d/m/Y');

// nome arquivo PDF
$codigoStr = str_pad((string) $orcamento['codigo'], 4, '0', STR_PAD_LEFT);
$nomeClienteParaArquivo = preg_replace('/[^\w\- ]+/u', '', $cliente['nome']); // remove caracteres problemáticos [web:229]
$nomeArquivoPdf = "Orçamento - #{$codigoStr} - {$nomeClienteParaArquivo}.pdf";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Orçamento #<?= htmlspecialchars($orcamento['codigo']) ?> - <?= htmlspecialchars($cliente['nome']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link href="/assets/css/orcamento.css" rel="stylesheet">

    <style>

    </style>
</head>

<body class="orcamento-page">
    <div class="orc-shell">

        <div id="share-alert" class="alert alert-success py-2 px-3 small text-center">
            Link copiado para a área de transferência.
        </div>

        <div class="orc-card">
            <div class="orc-titulo">ORÇAMENTO</div>

            <!-- bloco do cliente -->
            <div class="orc-header">
                <div class="orc-header-left">
                    <?php if (!empty($cliente['foto_perfil'])): ?>
                        <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?>" alt="Foto do cliente" class="orc-foto">
                    <?php else: ?>
                        <?php
                        $primeiraLetra = strtoupper(substr(trim($cliente['nome'] ?? ''), 0, 1));
                        ?>
                        <div class="orc-avatar-placeholder">
                            <?= $primeiraLetra ?: '?' ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div class="orc-nome"><?= htmlspecialchars($cliente['nome']) ?></div>
                    </div>
                </div>

                <div>
                    <div class="orc-whats">
                        <i class="ri-whatsapp-line"></i>
                        <?= !empty($cliente['whatsapp']) ? htmlspecialchars($cliente['whatsapp']) : 'Telefone não informado' ?>
                    </div>
                </div>
            </div>

            <!-- meta + descrição -->
            <div class="orc-meta d-flex justify-content-between">
                <div class="orc-codigo">
                    <span class="fs-5 me-1 icone-acao ">
                        <i class="ri-hashtag"></i>
                    </span>
                    <span><?= htmlspecialchars($orcamento['codigo']) ?></span>
                </div>
                <div class="data"><?= htmlspecialchars($dataOrcamento) ?></div>
                <div>
                    <span class="orc-servico-badge" style="background-color: <?= htmlspecialchars($bg) ?>;">
                        <i class="<?= $icon ?> fs-6 icon-service me-1" style="color: #0B1924;"></i>
                        <?= htmlspecialchars(string: $label) ?>
                    </span>
                </div>
            </div>

            <div class="orc-descricao-servico">
                <?= nl2br(htmlspecialchars($orcamento['descricao_servico'])) ?>
            </div>

            <!-- tabela de itens -->
            <div class="orc-tabela table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th style="width: 160px;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($itens)): ?>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['descricao']) ?></td>
                                    <td>
                                        <?php if ((float) $item['valor'] == 0.0): ?>
                                            <span style="color:#55BF4B; font-weight:500;">INCLUSO</span>
                                        <?php else: ?>
                                            R$<?= number_format((float) $item['valor'], 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">
                                    Nenhum item lançado para este orçamento.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- total -->
            <div class="orc-total-wrapper">
                <div class="orc-total-card">
                    <span>Valor Total:</span>
                    <span class="orc-total-valor">
                        R$<?= number_format($orcamento['valor_total'], 2, ',', '.') ?>
                    </span>
                </div>
            </div>

            <!-- ações -->
            <div class="orc-footer-actions">
                <button type="button" onclick="baixarPdf()">
                    <i class="ri-file-download-line"></i> Salvar em PDF
                </button>
                <button type="button" onclick="compartilharLink()">
                    <i class="ri-share-forward-line"></i> Compartilhar
                </button>
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
            el.classList.remove('alert-success', 'alert-danger');
            el.classList.add(tipo === 'error' ? 'alert-danger' : 'alert-success');
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
                mostrarShareAlert('Link copiado para a área de transferência.');
            } catch (e) {
                console.error(e);
                mostrarShareAlert('Não foi possível copiar o link. Copie manualmente.', 'error');
            }
        }

        async function baixarPdf() {
            if (!window.jspdf) return;
            const { jsPDF } = window.jspdf;

            document.documentElement.classList.add('pdf-mode');

            const area = document.querySelector('.orc-shell');
            const canvas = await html2canvas(area, {
                backgroundColor: '#ffffff',
                scale: 2
            });

            document.documentElement.classList.remove('pdf-mode');

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