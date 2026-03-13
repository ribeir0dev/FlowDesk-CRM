<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/HospedagemModel.php';

$model = new HospedagemModel($pdo);
$hospedagens = $model->listarTodas();

$mapIcones = [
  'wordpress' => ['icon' => 'ri-wordpress-fill', 'label' => 'WordPress', 'color' => '#81BEF0'],
  'vps' => ['icon' => 'ri-cloud-line', 'label' => 'VPS', 'color' => '#F0AC81'],
  'dominio' => ['icon' => 'ri-global-line', 'label' => 'Domínio', 'color' => '#C481F0'],
];
?>


<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
  <div>
    <h5 class="mb-1">Hospedagens</h5>
    <div class="small text-muted">Lista de hospedagens, tipos e prazos.</div>
  </div>

  <div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaHospedagem">
      <i class="ri-add-line me-1"></i> Nova hospedagem
    </button>
  </div>
</div>

<div class="card ">
  <div class="card-body">

    <!-- Cabeçalho tipo tabela -->
    <div class="row fw-semibold text-muted small pb-2 mb-2">
      <div class="col-2 col-md-1">Ícone</div>
      <div class="col-10 col-md-3">Nome</div>
      <div class="col-12 col-md-2 d-none d-md-block">Tipo</div>
      <div class="col-6 col-md-2">Início</div>
      <div class="col-6 col-md-2">Término</div>
      <div class="col-12 col-md-2 text-end">Ações</div>
    </div>

    <?php if (empty($hospedagens)): ?>
      <p class="text-muted small mb-0">Nenhuma hospedagem cadastrada.</p>
    <?php else: ?>
      <div class="row g-2">
        <?php foreach ($hospedagens as $h): ?>
          <?php
          $info = $mapIcones[$h['tipo']] ?? $mapIcones['dominio'];
          $inicio = date('d/m/Y', strtotime($h['data_inicio']));
          $fim = date('d/m/Y', strtotime($h['data_fim']));
          ?>
          <div class="col-12">
            <div class="card rounded-0 px-2">
              <div class="card-body py-2">
                <div class="row align-items-center small">

                  <!-- Ícone -->
                  <div class="col-2 col-md-1">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:32px;height:32px;
                                background: <?= htmlspecialchars($info['color']) ?>20;">
                      <i class="<?= htmlspecialchars($info['icon']) ?>"
                        style="color: <?= htmlspecialchars($info['color']) ?>;"></i>
                    </div>
                  </div>

                  <!-- Nome -->
                  <div class="col-10 col-md-3">
                    <div class="fw-semibold"><?= htmlspecialchars($h['nome']) ?></div>
                  </div>

                  <!-- Tipo -->
                  <div class="col-12 col-md-2 d-none d-md-block">
                    <?= htmlspecialchars($info['label']) ?>
                  </div>

                  <!-- Início -->
                  <div class="col-6 col-md-2">
                    <span class="text-muted d-md-none">Início: </span><?= $inicio ?>
                  </div>

                  <!-- Término -->
                  <div class="col-6 col-md-2">
                    <span class="text-muted d-md-none">Término: </span><?= $fim ?>
                  </div>

                  <!-- Ações -->
                  <div class="col-12 col-md-2 text-end mt-2 mt-md-0">
                    <form method="post" action="/app/Controllers/HospedagemController.php?acao=excluir"
                      class="js-confirm-delete" data-confirm-msg="Deseja mesmos excluir esta hospedagem?">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="hospedagem_id" value="<?= (int) $h['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ri-delete-bin-fill"></i>
                      </button>
                    </form>
                  </div>

                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../modals/modal_crm.php'; ?>