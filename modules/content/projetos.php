<?php
// modules/content/projetos.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ProjetoModel.php';

$model    = new ProjetoModel($pdo);
$projetos = $model->listarTodosComCliente();

// Dados para criar projeto a partir de oportunidade
$oportunidadeId = (int)($_GET['oportunidade_id'] ?? 0);
$clienteIdPadrao = (int)($_GET['cliente_id'] ?? 0);
$nomeProjetoPadrao = '';
$valorPrevistoPadrao = 0.0;

if ($oportunidadeId > 0) {
    require_once __DIR__ . '/../../app/Models/OportunidadeModel.php';
    $opModel = new OportunidadeModel($pdo);
    $op = $opModel->buscarPorId($oportunidadeId);

    if ($op) {
        $clienteIdPadrao     = (int)$op['cliente_id'];
        $nomeProjetoPadrao   = $op['titulo'] ?? '';
        $valorPrevistoPadrao = (float)$op['valor_previsto'];
    }
}

// Rótulos + ícones + cores por tipo
$mapTipos = [
    'landing_page' => ['label' => 'Landing Page', 'icon' => 'ri-layout-grid-line',           'color' => '#81BEF0'],
    'configuracao' => ['label' => 'Configuração', 'icon' => 'ri-settings-3-line',             'color' => '#F0AC81'],
    'alteracao'    => ['label' => 'Alteração',    'icon' => 'ri-pencil-line',           'color' => '#81F09F'],
    'otimizacao'   => ['label' => 'Otimização',   'icon' => 'ri-flashlight-line',             'color' => '#F0ED81'],
    'integracao'   => ['label' => 'Integração',   'icon' => 'ri-global-line',                'color' => '#C481F0'],
    'design'       => ['label' => 'Design',       'icon' => 'ri-palette-line',   'color' => '#DA81F0'],
    'outro'        => ['label' => 'Outro',        'icon' => 'ri-more-fill',   'color' => '#5C5C5C'],
];
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h5 class="mb-1">Projetos</h5>
        <div class="small text-muted">Lista de projetos, clientes e prazos.</div>
    </div>

    <div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoProjeto">
            <i class="ri-file-add-fill"></i> Novo projeto
        </button>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($projetos)): ?>
        <div class="col-12">
            <div class="alert alert-light border small mb-0">
                Nenhum projeto cadastrado até o momento.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projetos as $p): ?>
            <?php
            $tipoInfo = $mapTipos[$p['tipo_projeto']] ?? [
                'label' => ucfirst($p['tipo_projeto']),
                'icon' => 'lni-more-alt',
                'color' => '#5C5C5C',
            ];
            $inicio = $p['data_inicio'] ? date('d/m/Y', strtotime($p['data_inicio'])) : '—';
            $entrega = $p['data_entrega'] ? date('d/m/Y', strtotime($p['data_entrega'])) : '—';
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="card  h-100">
                    <div class="card-body d-flex flex-column">

                        <!-- Ícone + tipo -->
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-2" style="
                     width: 40px;
                     height: 40px;
                     border-radius: 50%;
                     display: flex;
                     align-items: center;
                     justify-content: center;
                     background-color: <?= htmlspecialchars($tipoInfo['color']) ?>20;
                   ">
                                <i class="lni <?= htmlspecialchars($tipoInfo['icon']) ?>"
                                    style="font-size: 1.4rem; color: <?= htmlspecialchars($tipoInfo['color']) ?>;"></i>
                            </div>
                            <div>
                                <span class="small text-muted d-block">Tipo do projeto</span>
                                <strong class="small"><?= htmlspecialchars($tipoInfo['label']) ?></strong>
                            </div>
                        </div>

                        <!-- Nome do projeto -->
                        <h6 class="mt-2 mb-1">
                            <?= htmlspecialchars($p['nome_projeto']) ?>
                        </h6>

                        <!-- Cliente -->
                        <p class="small text-muted mb-2">
                            Cliente:
                            <?php if (!empty($p['cliente_nome'])): ?>
                                <?= htmlspecialchars($p['cliente_nome']) ?>
                            <?php else: ?>
                                <span class="text-muted">Não vinculado</span>
                            <?php endif; ?>
                        </p>

                        <!-- Datas -->
                        <p class="small mb-1">
                            Início: <strong><?= $inicio ?></strong>
                        </p>
                        <p class="small mb-3">
                            Entrega: <strong><?= $entrega ?></strong>
                        </p>

                        <!-- Ações -->
                        <!-- Ações -->
                        <div class="mt-auto d-flex justify-content-between gap-2">
                            <a href="/modules/painel.php?mod=projeto_detalhe&id=<?= (int) $p['id'] ?>"
                                class="btn btn-primary btn-sm w-100">
                                Detalhes
                            </a>

                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Editar" data-bs-toggle="modal"
                                data-bs-target="#modalEditarProjeto" data-id="<?= (int) $p['id'] ?>">
                                <i class="ri-file-edit-fill"></i>
                            </button>


                            <form method="post" action="/app/Controllers/ProjetoController.php?acao=concluir"
                                onsubmit="return confirm('Concluir este projeto? Ele será removido da lista.');">
                                <input type="hidden" name="projeto_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Concluir projeto">
                                    <i class="ri-check-fill"></i>
                                </button>
                            </form>
                        </div>


                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../modals/modal_projeto.php'; ?>

<?php if ($oportunidadeId > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('modalNovoProjeto');
  if (!modalEl || !window.bootstrap) return;
  var modal = new bootstrap.Modal(modalEl);
  modal.show();
});
</script>
<?php endif; ?>