<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';

$token = $_GET['token'] ?? '';
if ($token === '') {
  http_response_code(404);
  exit('Relatório não encontrado.');
}

$model = new ClienteModel($pdo);
$cliente = $model->buscarPorToken($token);

if (!$cliente) {
  http_response_code(404);
  exit('Relatório não encontrado.');
}

// nome
$partesNome = preg_split('/\s+/', trim($cliente['nome']));
$primeiroNome = $partesNome[0] ?? '';
$ultimoNome = $partesNome[count($partesNome) - 1] ?? '';

// blocos compartilhados
$blocos = $model->buscarBlocosCompartilhados((int) $cliente['id']);

// período (criado_em -> hoje)
$dataCriacaoCliente = $cliente['criado_em'] ?? date('Y-m-d');
$inicio = date('d/m/Y', strtotime($dataCriacaoCliente));
$fim = date('d/m/Y');
$textoPeriodo = $inicio . ' - ' . $fim;

// total de projetos
$totalProjetosCliente = $cliente['total_projetos'] ?? 0;

// nome do PDF
$nomeArquivoPdf = preg_replace('/[^A-Za-z0-9\- ]/', '', $cliente['nome']);
$nomeArquivoPdf = trim($nomeArquivoPdf);
$dataHoje = date('Y-m-d');
$nomeArquivoPdf .= ' - ' . $dataHoje . '.pdf';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Relatório de <?= htmlspecialchars($primeiroNome) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
  <link href="/assets/css/relatorio.css" rel="stylesheet"> <!-- painel.scss compilado -->
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">
</head>

<body class="relatorio-cliente-page">
  <div class="report-shell">
    <div id="share-alert" class="alert alerta alert-success py-2 px-3 small text-center">
      Link copiado para a área de transferência.
    </div>

    <!-- barra topo: logo + botões -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex icone align-items-center gap-2">
        <img src="/assets/img/icon.png" alt="Sua logo" class="relatorio-logo">
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-relatorio d-inline-flex align-items-center" onclick="window.print()">
          <i class="ri-printer-line me-1"></i> Imprimir
        </button>

        <button type="button" class="btn btn-relatorio d-inline-flex align-items-center" onclick="baixarPdf()">
          <i class="ri-file-download-line me-1"></i> Baixar PDF
        </button>

        <button type="button" class="btn btn-relatorio d-inline-flex align-items-center" onclick="compartilharLink()">
          <i class="ri-share-forward-line me-1"></i> Compartilhar
        </button>
      </div>
    </div>

    <!-- título do relatório -->
    <div class="text-center mb-4">
      <h2 class="titulo-pagina mb-1">Relatório do Cliente</h2>
      <div class="titulo-pagina small"><?= htmlspecialchars($textoPeriodo) ?></div>
    </div>

    <?php if (empty($blocos)): ?>
      <div class="block-card p-4 text-center text-muted small">
        Nenhuma informação compartilhada neste relatório.
      </div>
    <?php else: ?>

      <div class="block-card p-0 mb-4 relatorio-card">
        <!-- cabeçalho tipo documento: dados do cliente -->
        <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 cliente relatorio-cliente-header">
          <div class="d-flex align-items-center gap-3">
            <?php if (!empty($cliente['foto_perfil'])): ?>
              <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?>" alt="Foto do cliente"
                class="relatorio-foto-cliente">
            <?php else: ?>
              <div class="relatorio-avatar-placeholder">
                <?= strtoupper(substr($primeiroNome, 0, 1)) ?>
              </div>
            <?php endif; ?>

            <div>
              <div class="relatorio-nome-cliente">
                <?= htmlspecialchars($cliente['nome']) ?>
              </div>

              <div class="relatorio-contatos">
                <div>
                  <span class="me-1">✉</span>
                  <?php if (!empty($cliente['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>" class="relatorio-email-link">
                      <?= htmlspecialchars($cliente['email']) ?>
                    </a>
                  <?php else: ?>
                    <span>Não informado</span>
                  <?php endif; ?>
                </div>
                <div>
                  <span class="me-1">☎</span>
                  <?= !empty($cliente['whatsapp']) ? htmlspecialchars($cliente['whatsapp']) : 'Não informado' ?>
                </div>
              </div>
            </div>
          </div>

          <div class="text-end">
            <div class="relatorio-projetos-label">Projetos</div>
            <div class="relatorio-projetos-valor">
              <?= (int) $totalProjetosCliente ?>
            </div>
          </div>
        </div>

        <!-- tabela de detalhes -->
        <div class="table-responsive">
          <table class="table mb-0 align-middle relatorio-tabela">
            <thead>
              <tr>
                <th class="small text-muted border-0 ps-4" style="width:25%;">Título</th>
                <th class="small text-muted border-0" style="width:35%;">URL</th>
                <th class="small text-muted border-0" style="width:40%;">Detalhes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blocos as $b): ?>
                <?php $c = json_decode($b['conteudo'] ?? '{}', true) ?: []; ?>
                <tr>
                  <td class="border-top align-top ps-4 py-3">
                    <?= htmlspecialchars($b['titulo']) ?>
                  </td>

                  <td class="border-top align-top py-3">
                    <?php $url = $c['url'] ?? ($c['link'] ?? null); ?>
                    <?php if (!empty($url)): ?>
                      <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                        <?= htmlspecialchars($url) ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">Não informado</span>
                    <?php endif; ?>
                  </td>

                  <td class="border-top align-top py-3">
                    <?php if (in_array($b['slug'], ['hospedagem', 'acesso_site', 'registro_br'], true)): ?>
                      <div><strong>Usuário:</strong> <?= htmlspecialchars($c['usuario'] ?? '—') ?></div>
                      <div><strong>Senha:</strong> <?= htmlspecialchars($c['senha'] ?? '—') ?></div>

                    <?php elseif ($b['slug'] === 'website'): ?>
                      <?php if (!empty(trim($c['livre'] ?? ''))): ?>
                        <?= nl2br(htmlspecialchars($c['livre'])) ?>
                      <?php else: ?>
                        <span class="text-muted">Sem observações adicionais.</span>
                      <?php endif; ?>

                    <?php else: ?>
                      <?php if (!empty(trim($c['livre'] ?? ''))): ?>
                        <?= nl2br(htmlspecialchars($c['livre'])) ?>
                      <?php else: ?>
                        <span class="text-muted">Sem detalhes adicionais.</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>
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

      setTimeout(() => {
        el.style.display = 'none';
      }, 2500);
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

      const area = document.querySelector('.report-shell');
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