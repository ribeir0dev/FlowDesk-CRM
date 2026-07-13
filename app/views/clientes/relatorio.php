<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/ClienteModel.php';

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' || !preg_match('/^[a-f0-9]{32,128}$/i', $token)) {
    http_response_code(404);
    exit('Relatorio nao encontrado.');
}

$model = new ClienteModel($pdo);
$cliente = $model->buscarPorToken($token);
if (!$cliente) {
    http_response_code(404);
    exit('Relatorio nao encontrado.');
}

$partesNome = preg_split('/\s+/', trim((string) $cliente['nome']));
$primeiroNome = $partesNome[0] ?? '';
$blocos = $model->buscarBlocosCompartilhados((int) $cliente['id'], isset($cliente['workspace_id']) ? (int) $cliente['workspace_id'] : null);
$dataCriacaoCliente = $cliente['criado_em'] ?? date('Y-m-d');
$inicio = date('d/m/Y', strtotime($dataCriacaoCliente));
$fim = date('d/m/Y');
$textoPeriodo = $inicio . ' - ' . $fim;
$totalProjetosCliente = $cliente['total_projetos'] ?? 0;
$nomeArquivoPdf = preg_replace('/[^A-Za-z0-9\- ]/', '', $cliente['nome']);
$nomeArquivoPdf = trim((string) $nomeArquivoPdf) . ' - ' . date('Y-m-d') . '.pdf';
$inicial = strtoupper(substr($primeiroNome, 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de <?= htmlspecialchars($primeiroNome) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --line: #dbe3f0;
            --text: #0f172a;
            --muted: #64748b;
            --brand: #2563eb;
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
            max-width: 1120px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .topbar, .hero, .hero-main, .meta, .toolbar {
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
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .brand-badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #dbeafe;
            color: var(--brand);
            display: grid;
            place-items: center;
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
        .title { font-size: 32px; margin-bottom: 6px; }
        .subtitle { color: var(--muted); }
        .hero {
            margin-top: 24px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: #f8fbff;
        }
        .hero-main { align-items: center; }
        .avatar, .avatar-fallback {
            width: 74px;
            height: 74px;
            border-radius: 22px;
        }
        .avatar { object-fit: cover; }
        .avatar-fallback {
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1d4ed8, #0f172a);
            color: #fff;
            font-size: 26px;
            font-weight: 700;
        }
        .hero-name { font-size: 28px; }
        .meta {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
        }
        .meta-block strong { display: block; margin-top: 4px; }
        .table-wrap {
            margin-top: 24px;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 22px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
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
            line-height: 1.6;
        }
        .empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed var(--line);
            border-radius: 20px;
            text-align: center;
            color: var(--muted);
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
        a { color: var(--brand); text-decoration: none; }
        @media (max-width: 720px) {
            .page { padding: 20px 14px 36px; }
            .sheet { padding: 20px; border-radius: 22px; }
            .title, .hero-name { font-size: 26px; }
        }
        @media print {
            body { background: #fff; }
            .page { padding: 0; max-width: none; }
            .topbar, #share-alert { display: none !important; }
            .sheet { box-shadow: none; border: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div id="share-alert"></div>

        <div class="topbar">
            <div class="brand">
                <span class="brand-badge"><i class="ri-file-chart-line"></i></span>
                <span>FlowDesk Client Report</span>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="ri-printer-line"></i>
                    <span>Imprimir</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="baixarPdf()">
                    <i class="ri-file-download-line"></i>
                    <span>Baixar PDF</span>
                </button>
                <button type="button" class="btn btn-primary" onclick="compartilharLink()">
                    <i class="ri-share-forward-line"></i>
                    <span>Compartilhar</span>
                </button>
            </div>
        </div>

        <div class="sheet">
            <p class="eyebrow">Relatorio compartilhado</p>
            <h1 class="title">Relatorio do cliente</h1>
            <p class="subtitle"><?= htmlspecialchars($textoPeriodo) ?></p>

            <?php if (empty($blocos)): ?>
                <div class="empty">Nenhuma informacao compartilhada neste relatorio.</div>
            <?php else: ?>
                <section class="hero">
                    <div class="hero-main">
                        <div style="display:flex; align-items:center; gap:16px;">
                            <?php if (!empty($cliente['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?>" alt="Foto do cliente" class="avatar">
                            <?php else: ?>
                                <div class="avatar-fallback"><?= $inicial ?: '?' ?></div>
                            <?php endif; ?>

                            <div>
                                <p class="eyebrow">Cliente</p>
                                <h2 class="hero-name"><?= htmlspecialchars($cliente['nome']) ?></h2>
                                <p class="subtitle">
                                    <?php if (!empty($cliente['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>"><?= htmlspecialchars($cliente['email']) ?></a>
                                    <?php else: ?>
                                        Email nao informado
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="toolbar">
                            <div class="meta-block">
                                <p class="eyebrow">Projetos</p>
                                <strong><?= (int) $totalProjetosCliente ?></strong>
                            </div>
                            <div class="meta-block">
                                <p class="eyebrow">Whatsapp</p>
                                <strong><?= !empty($cliente['whatsapp']) ? htmlspecialchars($cliente['whatsapp']) : 'Nao informado' ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="meta">
                        <div class="meta-block">
                            <p class="eyebrow">Periodo do relatorio</p>
                            <strong><?= htmlspecialchars($textoPeriodo) ?></strong>
                        </div>
                    </div>
                </section>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:25%;">Titulo</th>
                                <th style="width:35%;">URL</th>
                                <th style="width:40%;">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocos as $b): ?>
                                <?php $c = json_decode($b['conteudo'] ?? '{}', true) ?: []; ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['titulo']) ?></td>
                                    <td>
                                        <?php $url = $c['url'] ?? ($c['link'] ?? null); ?>
                                        <?php if (!empty($url)): ?>
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($url) ?></a>
                                        <?php else: ?>
                                            <span style="color:#64748b;">Nao informado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (in_array($b['slug'], ['hospedagem', 'acesso_site', 'registro_br'], true)): ?>
                                            <div><strong>Usuario:</strong> <?= htmlspecialchars($c['usuario'] ?? '--') ?></div>
                                            <div><strong>Senha:</strong> <?= htmlspecialchars($c['senha'] ?? '--') ?></div>
                                        <?php elseif (!empty(trim((string) ($c['livre'] ?? '')))): ?>
                                            <?= nl2br(htmlspecialchars($c['livre'])) ?>
                                        <?php else: ?>
                                            <span style="color:#64748b;">Sem detalhes adicionais.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
