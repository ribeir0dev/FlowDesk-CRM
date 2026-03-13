<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ClienteModel.php';

// Aceita um ou vários status
$status_cliente = $_GET['status_cliente'] ?? ['todos'];

if (!is_array($status_cliente)) {
  $status_cliente = [$status_cliente];
}

$busca = trim($_GET['busca'] ?? '');

$model = new ClienteModel($pdo);
$clientes_filtrados = $model->listarFiltrados($status_cliente, $busca);
?>



<!-- Linha 1: título + botão CTA -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Clientes</h5>
  <div class="d-flex gap-3">
    <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
      data-bs-target="#modalNovoCliente">
      <i class="ri-user-add-fill me-2"></i>Adicionar cliente
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center" data-bs-toggle="modal"
      data-bs-target="#modalFiltroClientes">
      <i class="ri-filter-3-line me-2"></i> Filtrar
    </button>
  </div>
</div>

<!-- Linha 3: cards de clientes -->
<div class="row g-3">
  <?php foreach ($clientes_filtrados as $cli): ?>
    <?php
    $temFoto = !empty($cli['foto_perfil']);
    $inicial = strtoupper(mb_substr($cli['nome'], 0, 1));
    $genero = $cli['genero'] ?? 'empresa';

    $classeGenero = match ($genero) {
      'masculino' => 'cliente-masculino',
      'feminino' => 'cliente-feminino',
      default => 'cliente-empresa',
    };

    $statusClasse = $cli['status'] === 'ativo'
      ? 'bg-success-subtle text-success'
      : ($cli['status'] === 'potencial'
        ? 'bg-warning-subtle text-warning'
        : 'bg-secondary-subtle text-secondary');

    $criadoEm = $cli['criado_em']
      ? date('d/m/Y H:i', strtotime($cli['criado_em']))
      : null;
    ?>
    <div class="col-md-4 col-lg-2">
      <a href="painel.php?mod=cliente&id=<?= (int) $cli['id'] ?>" class="text-decoration-none">
        <div class="card h-100 cliente-card text-center d-flex align-items-center justify-content-center">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">

            <!-- Avatar -->
            <div class="cliente-avatar mb-2">
              <?php if ($temFoto): ?>
                <img src="<?= htmlspecialchars($cli['foto_perfil']) ?>" alt="Foto de <?= htmlspecialchars($cli['nome']) ?>"
                  class="rounded-circle cliente-foto" width="64" height="64" style="object-fit:cover;">
              <?php else: ?>
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                  style="width:64px;height:64px;background:#e5e5e5;">
                  <span class="fw-semibold fs-5"><?= $inicial ?></span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Nome -->
            <h6 class="mb-1 mt-1 card-cliente-nome"><?= htmlspecialchars($cli['nome']) ?></h6>

            <!-- Status -->
            <span class="small <?= $statusClasse ?> badge-cliente">
              <?= ucfirst($cli['status']) ?>
            </span>

          </div>
        </div>
      </a>
    </div>

  <?php endforeach; ?>
</div>



<?php if (empty($clientes_filtrados)): ?>
  <p class="text-muted small mt-3 mb-0">Nenhum cliente encontrado com os filtros atuais.</p>
<?php endif; ?>

<?php include __DIR__ . '/../modals/modal_cliente.php'; ?>
<?php include __DIR__ . '/../modals/modal_filtros.php'; ?>