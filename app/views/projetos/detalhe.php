<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/ProjetoModel.php';

$projeto_id = (int) ($_GET['id'] ?? 0);
if ($projeto_id <= 0) {
    header('Location: /projetos');
    exit;
}

$model = new ProjetoModel($pdo);
$projeto = $model->buscarComCliente($projeto_id);

if (!$projeto) {
    header('Location: /projetos');
    exit;
}

$tarefas = $model->listarTarefasPorProjeto($projeto_id);

$cols = [
    'backlog' => [],
    'andamento' => [],
    'revisao' => [],
    'concluido' => [],
];

foreach ($tarefas as $tarefa) {
    $cols[$tarefa['coluna']][] = $tarefa;
}

function fmtDataProjeto(?string $data): string
{
    return $data ? date('d/m/Y', strtotime($data)) : 'Sem data';
}

$tipoMap = [
    'landing_page' => ['label' => 'Landing Page', 'icon' => 'ri-layout-grid-line', 'color' => '#81BEF0'],
    'configuracao' => ['label' => 'Configuração', 'icon' => 'ri-settings-3-line', 'color' => '#F0AC81'],
    'alteracao' => ['label' => 'Alteração', 'icon' => 'ri-pencil-line', 'color' => '#81F09F'],
    'otimizacao' => ['label' => 'Otimização', 'icon' => 'ri-flashlight-line', 'color' => '#F0ED81'],
    'integracao' => ['label' => 'Integração', 'icon' => 'ri-global-line', 'color' => '#C481F0'],
    'design' => ['label' => 'Design', 'icon' => 'ri-palette-line', 'color' => '#DA81F0'],
    'outro' => ['label' => 'Outro', 'icon' => 'ri-more-fill', 'color' => '#5C5C5C'],
];

$statusMap = [
    'planejado' => ['label' => 'Planejado', 'class' => 'fd-badge-neutral'],
    'em_andamento' => ['label' => 'Em andamento', 'class' => 'fd-badge-info'],
    'concluido' => ['label' => 'Concluído', 'class' => 'fd-badge-success'],
    'pausado' => ['label' => 'Pausado', 'class' => 'fd-badge-warning'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'fd-badge-danger'],
];

$colunas = [
    'backlog' => 'Backlog',
    'andamento' => 'Em andamento',
    'revisao' => 'Revisão',
    'concluido' => 'Concluído',
];

$tipoInfo = $tipoMap[$projeto['tipo_projeto'] ?? ''] ?? ['label' => 'Outro', 'icon' => 'ri-more-fill', 'color' => '#5C5C5C'];
$statusInfo = $statusMap[$projeto['status'] ?? ''] ?? ['label' => ucfirst((string) ($projeto['status'] ?? 'Sem status')), 'class' => 'fd-badge-neutral'];
$totalTarefas = count($tarefas);
$tarefasConcluidas = count($cols['concluido']);
$tarefasEmAndamento = count($cols['andamento']);
$tarefasPendentes = $totalTarefas - $tarefasConcluidas;
$mensagens = [];
$canManageProjetos = fd_has_any_role(['owner', 'admin', 'operacional']);

if (isset($_GET['ok_tarefa'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Tarefa salva com sucesso.'];
}
if (isset($_GET['tarefa_excluida'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Tarefa excluida com sucesso.'];
}
if (isset($_GET['erro_tarefa'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar a tarefa.'];
}
?>

<div class="fd-project-detail">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Execução do projeto</p>
            <h2 class="fd-page-title"><?= htmlspecialchars($projeto['nome_projeto']) ?></h2>
            <p class="fd-page-subtitle">
                Cliente: <?= $projeto['cliente_nome'] ? htmlspecialchars($projeto['cliente_nome']) : 'Sem cliente vinculado' ?>
            </p>
        </div>

        <div class="fd-action-group">
            <a href="/projetos" class="fd-btn-secondary">
                <i class="ri-arrow-left-line"></i>
                <span>Voltar</span>
            </a>

            <?php if ($canManageProjetos): ?>
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaTarefa">
                    <i class="ri-add-line"></i>
                    <span>Nova tarefa</span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-card fd-project-detail-hero">
        <div class="fd-project-detail-top">
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

        <div class="fd-project-detail-meta">
            <div class="fd-project-meta-item">
                <span class="fd-card-eyebrow">Início</span>
                <strong><?= fmtDataProjeto($projeto['data_inicio'] ?? null) ?></strong>
            </div>

            <div class="fd-project-meta-item">
                <span class="fd-card-eyebrow">Entrega</span>
                <strong><?= fmtDataProjeto($projeto['data_entrega'] ?? null) ?></strong>
            </div>

            <div class="fd-project-meta-item">
                <span class="fd-card-eyebrow">Cliente</span>
                <strong><?= $projeto['cliente_nome'] ? htmlspecialchars($projeto['cliente_nome']) : 'Sem cliente vinculado' ?></strong>
            </div>
        </div>

        <?php if (!empty($projeto['descricao'])): ?>
            <div class="fd-project-description">
                <p class="fd-card-eyebrow">Resumo do escopo</p>
                <p><?= nl2br(htmlspecialchars($projeto['descricao'])) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <section class="fd-kpi-grid fd-kpi-grid-3">
        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-violet">
                        <i class="ri-list-check-3"></i>
                    </span>
                    Total de tarefas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalTarefas ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Cards registrados neste projeto</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green">
                        <i class="ri-loader-4-fill"></i>
                    </span>
                    Em andamento
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $tarefasEmAndamento ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Tarefas na coluna de execução</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red">
                        <i class="ri-checkbox-circle-fill"></i>
                    </span>
                    Concluídas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $tarefasConcluidas ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral"><?= $tarefasPendentes ?> ainda pendentes no fluxo</span>
            </div>
        </article>
    </section>

    <section class="fd-project-kanban">
        <?php foreach ($colunas as $slug => $titulo): ?>
            <article class="fd-card fd-project-column">
                <div class="fd-project-column-head">
                    <div>
                        <h3 class="fd-project-column-title"><?= htmlspecialchars($titulo) ?></h3>
                        <p class="fd-card-subtitle"><?= count($cols[$slug]) ?> tarefa(s)</p>
                    </div>

                    <span class="fd-badge fd-badge-neutral"><?= count($cols[$slug]) ?></span>
                </div>

                <div class="kanban-column-body fd-project-column-body" data-coluna="<?= htmlspecialchars($slug) ?>">
                    <?php if (empty($cols[$slug])): ?>
                        <div class="fd-pipeline-empty">
                            <p>Sem tarefas nesta etapa.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cols[$slug] as $t): ?>
                            <div class="fd-card fd-project-task-card pipeline-card" draggable="<?= $canManageProjetos ? 'true' : 'false' ?>" data-id="<?= (int) $t['id'] ?>">
                                <div class="fd-project-task-head">
                                    <strong class="fd-project-task-title"><?= htmlspecialchars($t['titulo']) ?></strong>

                                    <div class="fd-project-task-actions">
                                        <?php if ($canManageProjetos): ?>
                                        <button
                                            type="button"
                                            class="fd-btn-table"
                                            title="Editar tarefa"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditarTarefa"
                                            data-id="<?= (int) $t['id'] ?>"
                                            data-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                                            data-descricao="<?= htmlspecialchars($t['descricao'] ?? '') ?>"
                                            data-coluna="<?= htmlspecialchars($t['coluna']) ?>"
                                        >
                                            <i class="ri-file-edit-line"></i>
                                        </button>

                                        <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/excluir" onsubmit="return confirm('Excluir esta tarefa?');">
                                            <input type="hidden" name="tarefa_id" value="<?= (int) $t['id'] ?>">
                                            <input type="hidden" name="projeto_id" value="<?= (int) $projeto_id ?>">
                                            <button type="submit" class="fd-btn-table fd-btn-table-danger" title="Excluir tarefa">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($t['descricao'])): ?>
                                    <p class="fd-project-task-copy"><?= nl2br(htmlspecialchars($t['descricao'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include __DIR__ . '/../projetos/partials/modal_projeto.php'; ?>
