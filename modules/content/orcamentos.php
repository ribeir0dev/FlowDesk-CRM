<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/OrcamentoModel.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';

$orcModel     = new OrcamentoModel($pdo);
$clienteModel = new ClienteModel($pdo);

// status_orcamento: Enviado, Aprovado, Recusado, etc.
$status_orcamento = $_GET['status_orcamento'] ?? ['todos'];
if (!is_array($status_orcamento)) {
    $status_orcamento = [$status_orcamento];
}

$orcamentos    = $orcModel->listarComClientes($status_orcamento);
$clientesTodos = $clienteModel->listarFiltrados(['todos'], '');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-1">Orçamentos</h5>
  </div>

  <div class="d-flex gap-2">
    <button type="button"
            class="btn btn-outline-secondary btn-sm d-flex align-items-center"
            data-bs-toggle="modal"
            data-bs-target="#modalFiltroOrcamento">
      <i class="ri-filter-3-line me-2"></i> Filtrar orçamentos
    </button>

    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoOrcamento">
      <i class="ri-add-line me-2"></i>Criar Orçamento
    </button>
  </div>
</div>


<div class="table-responsive">
  <table class="table align-middle mb-0">
    <thead class="table-dark">
      <tr>
        <th style="width: 180px;">ID ORÇAMENTO</th>
        <th style="width: 180px;">CLIENTE</th>
        <th style="width: 180px;">SERVIÇO</th>
        <th style="width: 140px;">PAGAMENTO</th>
        <th style="width: 140px;">STATUS</th>
        <th style="width: 140px;">VALOR</th>
        <th style="width: 140px; text-align: center">AÇÕES</th>
      </tr>
    </thead>
    <tbody id="orcamentosTableBody">
      <?php if (!empty($orcamentos)): ?>
        <?php foreach ($orcamentos as $orc): ?>
          <tr>
            <td>
              <span class="fs-5 me-1 icone-acao ">
                <i class="ri-hashtag"></i>
              </span>
              <?= htmlspecialchars($orc['codigo']) ?>
            </td>

            <td><?= htmlspecialchars($orc['cliente_nome']) ?></td>
            <?php
            $servico = $orc['servico_principal'] ?? '';

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
              'landing_page' => ['bg' => '#CEE7FF'], // azul claro
              'configuracao' => ['bg' => '#D3D3D3'], // ciano
              'stream_overlay' => ['bg' => '#FFFBCE '], // laranja
              'criativos' => ['bg' => '#FECEFF'], // verde
              'identidade_visual' => ['bg' => '#D5CEFF'], // rosa
            ];

            $label = $servicoLabels[$servico] ?? $servico;
            $icon = $servicoIcons[$servico] ?? 'ri-file-text-line';
            $bg   = $servicoColors[$servico]['bg']   ?? '#F3F4F6';
            ?>
            <td>
              <span class="d-inline-flex align-items-center badge-service" style="background-color: <?= $bg ?>;">
                <i class="<?= $icon ?> fs-6 icon-service me-1" style="color: #0B1924;"></i>
                <?= htmlspecialchars($label) ?>
              </span>
            </td>

            <td>
              <span class="bg-pagamento" style="background: #CEFFD5; padding: 5px 30px; border-radius: 5px; color:#0B1924">
                <?= htmlspecialchars($orc['forma_pagamento']) ?>
              </span>
            </td>

            <td>
              <?php
              $status = $orc['status'];
              $statusClass = 'bg-secondary-subtle';
              if ($status === 'Enviado')
                $statusClass = 'bg-warning-subtle p-2 texto-status';
              if ($status === 'Aprovado')
                $statusClass = 'bg-success-subtle p-2 texto-status';
              if ($status === 'Recusado')
                $statusClass = 'bg-danger-subtle p-2 texto-status';
              ?>
              <span class="badge <?= $statusClass ?>">
                <?= htmlspecialchars($status) ?>
              </span>
            </td>

            <td>
              R$<?= number_format($orc['valor_total'], 2, ',', '.') ?>
            </td>

            <td>
              <div class="d-flex acoes">
                <!-- Editar -->
                <button class="icone-acao" data-bs-toggle="modal" data-bs-target="#modalNovoOrcamento"
                  data-id="<?= (int) $orc['id'] ?>">
                  <i class="ri-file-edit-fill"></i>
                </button>

                <!-- Excluir -->
                <form method="post" action="/app/Controllers/OrcamentoController.php?acao=excluir" class="d-inline"
                  onsubmit="return confirm('Tem certeza que deseja excluir este orçamento?');">
                  <input type="hidden" name="orcamento_id" value="<?= (int) $orc['id'] ?>">
                  <button type="submit" class="icone-acao">
                    <i class="ri-delete-bin-fill"></i>
                  </button>
                </form>

                <!-- PDF -->
                <a class="icone-acao"
                  href="/modules/content/orcamento_detalhe.php?id=<?= (int)$orc['id'] ?>" target="_blank">
                  <i class="ri-file-pdf-2-fill"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-4">
            Nenhum orçamento encontrado.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
// Modal de edição do cliente (a criar em modules/modals/modal_editar_cliente.php)
include __DIR__ . '/../modals/modal_orcamento.php';