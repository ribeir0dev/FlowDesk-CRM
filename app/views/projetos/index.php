<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/BillingModel.php';
require_once __DIR__ . '/../../../app/Models/ProjetoModel.php';

$billingModel = new BillingModel($pdo);
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
    'landing_page' => ['label' => 'Landing Page', 'icon' => 'ri-layout-line', 'color' => '#81BEF0'],
    'configuracao' => ['label' => 'Configuracao', 'icon' => 'ri-settings-3-line', 'color' => '#F0AC81'],
    'alteracao' => ['label' => 'Alteracao', 'icon' => 'ri-edit-line', 'color' => '#81F09F'],
    'otimizacao' => ['label' => 'Otimizacao', 'icon' => 'ri-rocket-line', 'color' => '#F0ED81'],
    'integracao' => ['label' => 'Integracao', 'icon' => 'ri-links-line', 'color' => '#C481F0'],
    'design' => ['label' => 'Design', 'icon' => 'ri-palette-line', 'color' => '#DA81F0'],
    'outro' => ['label' => 'Outro', 'icon' => 'ri-apps-2-line', 'color' => '#5C5C5C'],
];

$statusMap = [
    'planejado' => ['label' => 'Planejado', 'class' => 'fd-badge-neutral'],
    'em_andamento' => ['label' => 'Em andamento', 'class' => 'fd-badge-info'],
    'pausado' => ['label' => 'Pausado', 'class' => 'fd-badge-warning'],
    'concluido' => ['label' => 'Concluido', 'class' => 'fd-badge-success'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'fd-badge-danger'],
    'atrasado' => ['label' => 'Atrasado', 'class' => 'fd-badge-danger'],
];

$totalProjetos = count($projetos);
$projetosAtivos = 0;
$projetosEntregaProxima = 0;
$projetosAtrasados = 0;
$projetosConcluidos = 0;
$hoje = new DateTimeImmutable('today');
$mensagens = [];
$canManageProjetos = fd_has_any_role(['owner', 'admin', 'operacional']);
$projectsGate = $billingModel->getResourceGate((int) (fd_current_workspace_id() ?? 0), 'projects');
$shouldShowManageSubscriptionProjetos = $canManageProjetos && !$projectsGate['allowed'];

if (isset($_GET['criado'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto criado com sucesso.'];
}
if (isset($_GET['atualizado'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto atualizado com sucesso.'];
}
if (isset($_GET['concluido'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto concluido com sucesso.'];
}
if (isset($_GET['excluido'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Projeto excluido com sucesso.'];
}
if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao em projetos.'];
}
if (($_GET['limit'] ?? '') === 'projects') {
    $mensagens[] = ['type' => 'warning', 'text' => 'Seu plano atingiu o limite de projetos. Faca upgrade para continuar criando novos projetos.'];
}

foreach ($projetos as $projeto) {
    $status = $projeto['status'] ?? '';

    if (!in_array($status, ['concluido', 'cancelado'], true)) {
        $projetosAtivos++;
    } elseif ($status === 'concluido') {
        $projetosConcluidos++;
    }

    if (!empty($projeto['data_entrega'])) {
        $entrega = DateTimeImmutable::createFromFormat('Y-m-d', $projeto['data_entrega']) ?: new DateTimeImmutable($projeto['data_entrega']);
        $diffDias = (int) $hoje->diff($entrega)->format('%r%a');

        if ($diffDias >= 0 && $diffDias <= 7) {
            $projetosEntregaProxima++;
        }
        if ($diffDias < 0 && !in_array($status, ['concluido', 'cancelado'], true)) {
            $projetosAtrasados++;
        }
    }
}
$cronogramaProjetos = array_values(array_filter($projetos, static fn (array $item): bool => !empty($item['data_entrega']) && !in_array($item['status'] ?? '', ['concluido', 'cancelado'], true)));
usort($cronogramaProjetos, static fn (array $a, array $b): int => strcmp((string) $a['data_entrega'], (string) $b['data_entrega']));
$cronogramaProjetos = array_slice($cronogramaProjetos, 0, 5);
?>

<div class="fd-projects fd-projects-v2">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Operacao e entregas</p>
            <p class="fd-page-subtitle">Acompanhe o andamento dos projetos, clientes vinculados e prazos de entrega em um so lugar.</p>
        </div>

        <?php if ($canManageProjetos): ?>
            <div class="fd-action-group">
                <?php if ($shouldShowManageSubscriptionProjetos): ?>
                    <a href="<?= ($base ?? '') ?>/configuracoes#pagamentos" class="fd-btn-primary">
                        <i class="ri-vip-crown-2-line"></i>
                        <span>Gerenciar Assinatura</span>
                    </a>
                <?php else: ?>
                    <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoProjeto">
                        <i class="ri-file-add-line"></i>
                        <span>Novo projeto</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-projects-reference">
        <div class="fd-project-reference-kpis">
            <?php
            $projectKpis = [
                ['label' => 'Total de projetos', 'value' => $totalProjetos, 'note' => '100% do total', 'icon' => 'ri-stack-line', 'tone' => 'blue'],
                ['label' => 'Projetos ativos', 'value' => $projetosAtivos, 'note' => $totalProjetos ? round(($projetosAtivos / $totalProjetos) * 100) . '% do total' : '0% do total', 'icon' => 'ri-pulse-line', 'tone' => 'cyan'],
                ['label' => 'Em atraso', 'value' => $projetosAtrasados, 'note' => $totalProjetos ? round(($projetosAtrasados / $totalProjetos) * 100) . '% do total' : '0% do total', 'icon' => 'ri-time-line', 'tone' => 'red'],
                ['label' => 'Concluídos', 'value' => $projetosConcluidos, 'note' => $totalProjetos ? round(($projetosConcluidos / $totalProjetos) * 100) . '% do total' : '0% do total', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
                ['label' => 'Entregas próximas', 'value' => $projetosEntregaProxima, 'note' => 'Próximos 7 dias', 'icon' => 'ri-calendar-event-line', 'tone' => 'purple'],
            ];
            foreach ($projectKpis as $kpi):
            ?>
                <article class="fd-project-reference-kpi is-<?= $kpi['tone'] ?>">
                    <div><small><?= e($kpi['label']) ?></small><strong><?= (int) $kpi['value'] ?></strong><span><?= e($kpi['note']) ?></span></div>
                    <i class="<?= e($kpi['icon']) ?>"></i>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="fd-project-reference-layout">
            <article class="fd-card fd-project-reference-table-card">
                <header class="fd-reference-card-head">
                    <div><h2>Todos os projetos <span><?= $totalProjetos ?></span></h2></div>
                    <label class="fd-reference-search"><i class="ri-search-line"></i><input type="search" placeholder="Buscar projeto..." data-project-search></label>
                </header>
                <div class="fd-table-wrap">
                    <table class="fd-table fd-project-reference-table">
                        <thead><tr><th>Projeto</th><th>Cliente</th><th>Categoria</th><th>Progresso</th><th>Prazo</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                        <?php foreach ($projetos as $p):
                            $tipoInfo = $mapTipos[$p['tipo_projeto']] ?? ['label' => ucfirst((string) $p['tipo_projeto']), 'icon' => 'ri-apps-2-line', 'color' => '#5C5C5C'];
                            $statusKey = $p['status'] ?? 'planejado';
                            $isLate = !empty($p['data_entrega']) && !in_array($statusKey, ['concluido', 'cancelado'], true) && strtotime($p['data_entrega']) < strtotime('today');
                            if ($isLate) $statusKey = 'atrasado';
                            $statusInfo = $statusMap[$statusKey] ?? ['label' => ucfirst((string) $statusKey), 'class' => 'fd-badge-neutral'];
                            $progress = $statusKey === 'concluido' ? 100 : ($statusKey === 'em_andamento' ? 50 : ($statusKey === 'pausado' ? 30 : 10));
                        ?>
                            <tr data-project-row data-search-text="<?= e(mb_strtolower(($p['nome_projeto'] ?? '') . ' ' . ($p['cliente_nome'] ?? '') . ' ' . $tipoInfo['label'])) ?>">
                                <td><div class="fd-project-table-name"><span style="--type-color:<?= e($tipoInfo['color']) ?>"><i class="<?= e($tipoInfo['icon']) ?>"></i></span><div><strong><?= e($p['nome_projeto']) ?></strong><small><?= e(mb_strimwidth((string) ($p['descricao'] ?? ''), 0, 45, '...')) ?></small></div></div></td>
                                <td><?= e($p['cliente_nome'] ?: 'Sem cliente') ?></td>
                                <td><span class="fd-project-category"><?= e($tipoInfo['label']) ?></span></td>
                                <td><div class="fd-project-progress"><span><?= $progress ?>%</span><b><i style="width:<?= $progress ?>%"></i></b></div></td>
                                <td><strong><?= !empty($p['data_entrega']) ? date('d/m/Y', strtotime($p['data_entrega'])) : 'Sem data' ?></strong><small class="<?= $isLate ? 'is-late' : '' ?>"><?= $isLate ? 'Atrasado' : 'Em dia' ?></small></td>
                                <td><span class="fd-badge <?= e($statusInfo['class']) ?>"><?= e($statusInfo['label']) ?></span></td>
                                <td><a class="fd-btn-table" href="<?= ($base ?? '') ?>/projeto?id=<?= (int) $p['id'] ?>" title="Abrir projeto"><i class="ri-arrow-right-up-line"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <footer class="fd-reference-card-footer">Exibindo <?= min(8, $totalProjetos) ?> de <?= $totalProjetos ?> projetos</footer>
            </article>

            <aside class="fd-card fd-project-schedule-card">
                <header class="fd-reference-card-head"><h2><i class="ri-calendar-event-line"></i>Cronograma de entregas</h2><span>Próximos projetos</span></header>
                <div class="fd-project-schedule-list">
                    <?php if (!$cronogramaProjetos): ?><p class="fd-empty-copy">Nenhuma entrega agendada.</p><?php endif; ?>
                    <?php foreach ($cronogramaProjetos as $project):
                        $delivery = new DateTimeImmutable($project['data_entrega']);
                        $days = (int) $hoje->diff($delivery)->format('%r%a');
                    ?>
                        <a href="<?= ($base ?? '') ?>/projeto?id=<?= (int) $project['id'] ?>" class="fd-project-schedule-item">
                            <time><strong><?= $delivery->format('d') ?></strong><small><?= mb_strtoupper(fd_format_month_year($delivery->format('Y-m-d')), 'UTF-8') ?></small></time>
                            <span class="fd-project-schedule-dot <?= $days < 0 ? 'is-late' : ($days <= 3 ? 'is-warning' : '') ?>"></span>
                            <div><strong><?= e($project['nome_projeto']) ?></strong><small><?= e($project['cliente_nome'] ?: 'Sem cliente') ?></small></div>
                            <b class="<?= $days < 0 ? 'is-late' : '' ?>"><?= $days < 0 ? 'Atrasado' : 'Em ' . $days . ' dias' ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>

        <div class="fd-project-reference-widgets">
            <article class="fd-card"><h3>Saúde dos projetos</h3><div class="fd-health-donut" style="--healthy:<?= $totalProjetos ? round(($projetosAtivos / $totalProjetos) * 100) : 0 ?>"><strong><?= $totalProjetos ?></strong><small>Projetos</small></div><p><span class="is-green"></span>Ativos <b><?= $projetosAtivos ?></b></p><p><span class="is-red"></span>Em atraso <b><?= $projetosAtrasados ?></b></p></article>
            <article class="fd-card"><h3>Marcos importantes</h3><?php foreach (array_slice($cronogramaProjetos, 0, 3) as $item): ?><a href="<?= ($base ?? '') ?>/projeto?id=<?= (int) $item['id'] ?>"><i class="ri-flag-line"></i><span><strong><?= e($item['nome_projeto']) ?></strong><small>Data prevista: <?= date('d/m/Y', strtotime($item['data_entrega'])) ?></small></span></a><?php endforeach; ?></article>
            <article class="fd-card"><h3>Resumo operacional</h3><p>Projetos ativos <b><?= $projetosAtivos ?></b></p><p>Entregas próximas <b><?= $projetosEntregaProxima ?></b></p><p>Concluídos <b><?= $projetosConcluidos ?></b></p></article>
        </div>
    </section>

    <section class="fd-orcamento-stats fd-project-stats">
        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-stack-line"></i>
            </span>
            <strong>Total de Projetos</strong>
            <span><?= $totalProjetos ?></span>
        </article>

        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-menu-4-line"></i>
            </span>
            <strong>Projetos Ativos</strong>
            <span><?= $projetosAtivos ?></span>
        </article>

        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-calendar-schedule-line"></i>
            </span>
            <strong>Entregas Proximas</strong>
            <span><?= $projetosEntregaProxima ?></span>
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
                $statusKey = $p['status'] ?? '';
                if (
                    !empty($p['data_entrega'])
                    && !in_array($statusKey, ['concluido', 'cancelado'], true)
                    && (DateTimeImmutable::createFromFormat('Y-m-d', $p['data_entrega']) ?: new DateTimeImmutable($p['data_entrega'])) < $hoje
                ) {
                    $statusKey = 'atrasado';
                }
                $statusInfo = $statusMap[$statusKey] ?? ['label' => ucfirst((string) $statusKey), 'class' => 'fd-badge-neutral'];
                $inicio = $p['data_inicio'] ? date('d/m/Y', strtotime($p['data_inicio'])) : 'Sem data';
                $entrega = $p['data_entrega'] ? date('d/m/Y', strtotime($p['data_entrega'])) : 'Sem data';
                ?>
                <article class="fd-card fd-project-card">
                    <div class="fd-project-card-top">
                        <div class="fd-project-type-wrap">
                            <span class="fd-project-type-icon" style="background-color: <?= htmlspecialchars($tipoInfo['color']) ?>20; color: <?= htmlspecialchars($tipoInfo['color']) ?>;">
                                <i class="<?= htmlspecialchars($tipoInfo['icon']) ?>"></i>
                            </span>
                            <h3 class="fd-project-name"><?= htmlspecialchars($p['nome_projeto']) ?></h3>
                        </div>

                        <span class="fd-badge <?= htmlspecialchars($statusInfo['class']) ?>">
                            <?= htmlspecialchars($statusInfo['label']) ?>
                        </span>
                    </div>

                    <div class="fd-project-content">
                        <div class="fd-project-info-grid">
                            <p class="fd-project-client">Cliente: <strong><?= !empty($p['cliente_nome']) ? htmlspecialchars($p['cliente_nome']) : 'Sem cliente vinculado' ?></strong></p>
                            <p class="fd-project-client">Tipo do projeto: <strong><?= htmlspecialchars($tipoInfo['label']) ?></strong></p>
                        </div>

                        <div class="fd-project-meta">
                            <div class="fd-project-meta-item">
                                <span class="fd-card-eyebrow">Inicio</span>
                                <strong><?= $inicio ?></strong>
                            </div>

                            <div class="fd-project-meta-item">
                                <span class="fd-card-eyebrow">Entrega Prevista</span>
                                <strong><?= $entrega ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="fd-project-actions">
                        <a href="<?= ($base ?? '') ?>/projeto?id=<?= (int) $p['id'] ?>" class="fd-btn-primary">
                            <i class="ri-eye-line"></i>
                            <span>Acessar Projeto</span>
                        </a>

                        <?php if ($canManageProjetos): ?>
                            <form
                                method="post"
                                action="<?= ($base ?? '') ?>/projetos/concluir"
                                class="fd-project-confirm-form"
                                data-confirm-title="Confirmar Acao"
                                data-confirm-message="Ao confirmar essa acao o projeto sera excluido da database, voce esta certo de que finalizou o projeto?"
                                data-confirm-button="Confirmar"
                            >
                                <input type="hidden" name="projeto_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="fd-btn-outline-success">
                                    <i class="ri-list-check-3"></i>
                                    <span>Concluir Projeto</span>
                                </button>
                            </form>

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

                            <form
                                method="post"
                                action="<?= ($base ?? '') ?>/projetos/excluir"
                                class="fd-project-confirm-form"
                                data-confirm-title="Confirmar Acao"
                                data-confirm-message="Ao confirmar essa acao o projeto sera excluido da database, voce deseja continuar?"
                                data-confirm-button="Confirmar"
                            >
                                <input type="hidden" name="projeto_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="fd-btn-table fd-btn-table-danger" title="Excluir projeto">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<div class="modal fade fd-action-confirm-modal" id="modalConfirmarProjetoAcao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarProjetoTitulo">Confirmar Acao</h5>
            </div>
            <div class="modal-body">
                <p id="modalConfirmarProjetoMensagem">
                    Ao confirmar essa acao o projeto sera excluido da database, voce deseja continuar?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="fd-confirm-cancel" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="fd-btn-primary" id="modalConfirmarProjetoBotao">Confirmar</button>
            </div>
        </div>
    </div>
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
