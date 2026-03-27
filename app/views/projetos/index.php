<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/ProjetoModel.php';

$model = new ProjetoModel($pdo);
$projetos = $model->listarTodosComCliente();

$oportunidadeId = (int) ($_GET['oportunidade_id'] ?? 0);
$clienteIdPadrao = (int) ($_GET['cliente_id'] ?? 0);
$nomeProjetoPadrao = '';
$valorPrevistoPadrao = 0.0;

if ($oportunidadeId > 0) {
    require_once __DIR__ . '/../../../app/Models/OportunidadeModel.php';
    $opModel = new OportunidadeModel($pdo);
    $op = $opModel->buscarPorId($oportunidadeId);

    if ($op) {
        $clienteIdPadrao = (int) $op['cliente_id'];
        $nomeProjetoPadrao = $op['titulo'] ?? '';
        $valorPrevistoPadrao = (float) $op['valor_previsto'];
    }
}

$mapTipos = [
    'landing_page' => ['label' => 'Landing Page', 'icon' => 'ri-layout-grid-line', 'color' => '#81BEF0'],
    'configuracao' => ['label' => 'Configuracao', 'icon' => 'ri-settings-3-line', 'color' => '#F0AC81'],
    'alteracao' => ['label' => 'Alteracao', 'icon' => 'ri-pencil-line', 'color' => '#81F09F'],
    'otimizacao' => ['label' => 'Otimizacao', 'icon' => 'ri-flashlight-line', 'color' => '#F0ED81'],
    'integracao' => ['label' => 'Integracao', 'icon' => 'ri-global-line', 'color' => '#C481F0'],
    'design' => ['label' => 'Design', 'icon' => 'ri-palette-line', 'color' => '#DA81F0'],
    'outro' => ['label' => 'Outro', 'icon' => 'ri-more-fill', 'color' => '#5C5C5C'],
];

$statusMap = [
    'planejado' => ['label' => 'Planejado', 'class' => 'fd-badge-neutral'],
    'em_andamento' => ['label' => 'Em andamento', 'class' => 'fd-badge-info'],
    'concluido' => ['label' => 'Concluido', 'class' => 'fd-badge-success'],
    'pausado' => ['label' => 'Pausado', 'class' => 'fd-badge-warning'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'fd-badge-danger'],
];

$totalProjetos = count($projetos);
$projetosAtivos = 0;
$projetosEntregaProxima = 0;
$hoje = new DateTimeImmutable('today');
$mensagens = [];
$canManageProjetos = fd_has_any_role(['owner', 'admin', 'operacional']);

if (isset($_GET['criado'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto criado com sucesso.'];
}
if (isset($_GET['atualizado'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto atualizado com sucesso.'];
}
if (isset($_GET['concluido'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto concluido com sucesso.'];
}
if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao em projetos.'];
}

foreach ($projetos as $projeto) {
    $status = $projeto['status'] ?? '';

    if (in_array($status, ['planejado', 'em_andamento'], true)) {
        $projetosAtivos++;
    }

    if (!empty($projeto['data_entrega'])) {
        $entrega = DateTimeImmutable::createFromFormat('Y-m-d', $projeto['data_entrega']) ?: new DateTimeImmutable($projeto['data_entrega']);
        $diffDias = (int) $hoje->diff($entrega)->format('%r%a');

        if ($diffDias >= 0 && $diffDias <= 7) {
            $projetosEntregaProxima++;
        }
    }
}
?>

<div class="fd-projects">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Operacao e entregas</p>
            <p class="fd-page-subtitle">Acompanhe o andamento dos projetos, clientes vinculados e prazos de entrega em um so lugar.</p>
        </div>

        <?php if ($canManageProjetos): ?>
            <div class="fd-action-group">
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoProjeto">
                    <i class="ri-file-add-line"></i>
                    <span>Novo projeto</span>
                </button>
            </div>
        <?php endif; ?>
    </section>

    <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-kpi-grid fd-kpi-grid-3">
        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-violet">
                        <i class="ri-stack-fill"></i>
                    </span>
                    Total de projetos
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalProjetos ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Projetos cadastrados na operacao atual</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green">
                        <i class="ri-loader-4-fill"></i>
                    </span>
                    Projetos ativos
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $projetosAtivos ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Em planejamento ou execucao</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red">
                        <i class="ri-calendar-close-fill"></i>
                    </span>
                    Entregas proximas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $projetosEntregaProxima ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Projetos com entrega prevista nos proximos 7 dias</span>
            </div>
        </article>
    </section>

    <?php if (empty($projetos)): ?>
        <section class="fd-card">
            <p class="fd-empty-copy">Nenhum projeto cadastrado ate o momento.</p>
        </section>
    <?php else: ?>
        <section class="fd-project-grid">
            <?php foreach ($projetos as $p): ?>
                <?php
                $tipoInfo = $mapTipos[$p['tipo_projeto']] ?? [
                    'label' => ucfirst((string) $p['tipo_projeto']),
                    'icon' => 'ri-more-fill',
                    'color' => '#5C5C5C',
                ];
                $statusInfo = $statusMap[$p['status']] ?? ['label' => ucfirst((string) $p['status']), 'class' => 'fd-badge-neutral'];
                $inicio = $p['data_inicio'] ? date('d/m/Y', strtotime($p['data_inicio'])) : 'Sem data';
                $entrega = $p['data_entrega'] ? date('d/m/Y', strtotime($p['data_entrega'])) : 'Sem data';
                ?>
                <article class="fd-card fd-project-card">
                    <div class="fd-project-card-top">
                        <div class="fd-project-type-wrap">
                            <span class="fd-project-type-icon" style="background-color: <?= htmlspecialchars($tipoInfo['color']) ?>20; color: <?= htmlspecialchars($tipoInfo['color']) ?>;">
                                <i class="<?= htmlspecialchars($tipoInfo['icon']) ?>"></i>
                            </span>

                            <div>
                                <p class="fd-card-eyebrow">Tipo do projeto</p>
                                <h3 class="fd-project-type-title"><?= htmlspecialchars($tipoInfo['label']) ?></h3>
                            </div>
                        </div>

                        <span class="fd-badge <?= htmlspecialchars($statusInfo['class']) ?>">
                            <?= htmlspecialchars($statusInfo['label']) ?>
                        </span>
                    </div>

                    <div class="fd-project-content">
                        <div>
                            <h3 class="fd-project-name"><?= htmlspecialchars($p['nome_projeto']) ?></h3>
                            <p class="fd-project-client">
                                <?= !empty($p['cliente_nome']) ? htmlspecialchars($p['cliente_nome']) : 'Sem cliente vinculado' ?>
                            </p>
                        </div>

                        <div class="fd-project-meta">
                            <div class="fd-project-meta-item">
                                <span class="fd-card-eyebrow">Inicio</span>
                                <strong><?= $inicio ?></strong>
                            </div>

                            <div class="fd-project-meta-item">
                                <span class="fd-card-eyebrow">Entrega</span>
                                <strong><?= $entrega ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="fd-project-actions">
                        <a href="/projeto?id=<?= (int) $p['id'] ?>" class="fd-btn-primary fd-btn-grow">
                            <i class="ri-eye-line"></i>
                            <span>Detalhes</span>
                        </a>

                        <?php if ($canManageProjetos): ?>
                        <button
                            type="button"
                            class="fd-btn-table"
                            title="Editar"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditarProjeto"
                            data-id="<?= (int) $p['id'] ?>"
                            data-nome="<?= htmlspecialchars($p['nome_projeto'] ?? '', ENT_QUOTES) ?>"
                            data-tipo="<?= htmlspecialchars($p['tipo_projeto'] ?? '', ENT_QUOTES) ?>"
                            data-cliente-id="<?= (int) ($p['cliente_id'] ?? 0) ?>"
                            data-status="<?= htmlspecialchars($p['status'] ?? '', ENT_QUOTES) ?>"
                            data-data-inicio="<?= htmlspecialchars($p['data_inicio'] ?? '', ENT_QUOTES) ?>"
                            data-data-entrega="<?= htmlspecialchars($p['data_entrega'] ?? '', ENT_QUOTES) ?>"
                            data-descricao="<?= htmlspecialchars($p['descricao'] ?? '', ENT_QUOTES) ?>"
                        >
                            <i class="ri-file-edit-line"></i>
                        </button>

                        <form method="post" action="<?= ($base ?? '') ?>/projetos/concluir" onsubmit="return confirm('Concluir este projeto? Ele será removido da lista.');">
                            <input type="hidden" name="projeto_id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="fd-btn-table fd-btn-table-success" title="Concluir projeto">
                                <i class="ri-check-line"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/modal_projeto.php'; ?>

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

