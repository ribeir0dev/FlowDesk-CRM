<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Helpers/auth.php';
require_once __DIR__ . '/../../../app/Models/ProjetoModel.php';

$projeto_id = (int) ($_GET['id'] ?? 0);
if ($projeto_id <= 0) {
    header('Location: ' . fd_base_path() . '/projetos');
    exit;
}

$model = new ProjetoModel($pdo);
$projeto = $model->buscarComCliente($projeto_id);

if (!$projeto) {
    header('Location: ' . fd_base_path() . '/projetos');
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

function fmtChecklistProgress(array $task): ?string
{
    $total = (int) ($task['checklist_total'] ?? 0);
    if ($total <= 0) {
        return null;
    }

    $done = min((int) ($task['checklist_done'] ?? 0), $total);
    return $done . '/' . $total;
}

$tipoMap = [
    'landing_page' => ['label' => 'Landing Page', 'icon' => 'ri-layout-grid-line', 'color' => '#81BEF0'],
    'configuracao' => ['label' => 'Configuracao', 'icon' => 'ri-settings-3-line', 'color' => '#F0AC81'],
    'alteracao' => ['label' => 'Alteracao', 'icon' => 'ri-pencil-line', 'color' => '#81F09F'],
    'otimizacao' => ['label' => 'Otimizacao', 'icon' => 'ri-flashlight-line', 'color' => '#F0ED81'],
    'integracao' => ['label' => 'Integracao', 'icon' => 'ri-global-line', 'color' => '#C481F0'],
    'design' => ['label' => 'Design', 'icon' => 'ri-palette-line', 'color' => '#DA81F0'],
    'outro' => ['label' => 'Outro', 'icon' => 'ri-more-fill', 'color' => '#5C5C5C'],
];

$colunas = [
    'backlog' => 'Backlog',
    'andamento' => 'Em andamento',
    'revisao' => 'Revisao',
    'concluido' => 'Concluido',
];

$prioridadeMap = [
    'baixa' => ['label' => 'Baixa', 'class' => 'fd-badge-neutral'],
    'media' => ['label' => 'Media', 'class' => 'fd-badge-info'],
    'alta' => ['label' => 'Alta', 'class' => 'fd-badge-warning'],
    'urgente' => ['label' => 'Urgente', 'class' => 'fd-badge-danger'],
];

$tipoInfo = $tipoMap[$projeto['tipo_projeto'] ?? ''] ?? ['label' => 'Outro', 'icon' => 'ri-more-fill', 'color' => '#5C5C5C'];
$totalTarefas = count($tarefas);
$tarefasConcluidas = count($cols['concluido']);
$tarefasRevisao = count($cols['revisao']);
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

<div class="fd-project-detail fd-project-detail-v2">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Execucao do projeto</p>
            <h2 class="fd-page-title"><?= htmlspecialchars($projeto['nome_projeto']) ?></h2>
        </div>

        <div class="fd-action-group">
            <a href="<?= ($base ?? '') ?>/projetos" class="fd-btn-secondary">
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

    <section class="fd-card fd-project-summary-strip">
        <div class="fd-project-type-wrap">
            <span class="fd-project-type-icon" style="background-color: <?= htmlspecialchars($tipoInfo['color']) ?>20; color: <?= htmlspecialchars($tipoInfo['color']) ?>;">
                <i class="<?= htmlspecialchars($tipoInfo['icon']) ?>"></i>
            </span>
            <h3 class="fd-project-type-title"><?= htmlspecialchars($tipoInfo['label']) ?></h3>
        </div>

        <div class="fd-project-meta-item">
            <span class="fd-card-eyebrow">Inicio</span>
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
    </section>

    <section class="fd-orcamento-stats fd-project-stats">
        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-list-check-3"></i>
            </span>
            <strong>Total de Tarefas</strong>
            <span><?= $totalTarefas ?></span>
        </article>

        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-check-double-line"></i>
            </span>
            <strong>Revisao</strong>
            <span><?= $tarefasRevisao ?></span>
        </article>

        <article class="fd-orcamento-stat">
            <span class="fd-orcamento-stat-icon">
                <i class="ri-check-line"></i>
            </span>
            <strong>Concluidas</strong>
            <span><?= $tarefasConcluidas ?></span>
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
                                    <strong class="fd-project-task-title" data-task-title><?= htmlspecialchars($t['titulo']) ?></strong>

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
                                                data-coluna="<?= htmlspecialchars($t['coluna']) ?>"
                                            >
                                                <i class="ri-file-edit-line"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="fd-btn-table fd-btn-table-danger"
                                                title="Excluir tarefa"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalExcluirTarefa"
                                                data-tarefa-id="<?= (int) $t['id'] ?>"
                                                data-projeto-id="<?= (int) $projeto_id ?>"
                                                data-tarefa-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                                            >
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php
                                $priority = $prioridadeMap[$t['prioridade'] ?? 'media'] ?? $prioridadeMap['media'];
                                $checklistProgress = fmtChecklistProgress($t);
                                ?>
                                <div class="fd-project-task-meta">
                                    <span class="fd-badge <?= htmlspecialchars($priority['class']) ?>" data-task-priority-badge>
                                        <?= htmlspecialchars($priority['label']) ?>
                                    </span>

                                    <?php if ($checklistProgress !== null): ?>
                                        <span class="fd-task-tag fd-task-tag-checklist" data-task-checklist-badge>
                                            <i class="ri-list-check-3"></i>
                                            <span data-task-checklist-text><?= htmlspecialchars($checklistProgress) ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include __DIR__ . '/../projetos/partials/modal_projeto.php'; ?>

<div class="modal fade" id="modalExcluirTarefa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fd-delete-modal">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/excluir" id="form-excluir-tarefa">
                <input type="hidden" name="tarefa_id" id="deleteTarefaId" value="">
                <input type="hidden" name="projeto_id" id="deleteTarefaProjetoId" value="">

                <div class="modal-header fd-delete-modal-header">
                    <div class="fd-delete-modal-headline">
                        <span class="fd-delete-modal-icon">
                            <i class="ri-delete-bin-6-line"></i>
                        </span>
                        <div class="fd-delete-modal-copy">
                            <h5 class="modal-title">Excluir tarefa</h5>
                            <p class="fd-delete-modal-title mb-0">
                                Esta acao vai remover <strong id="deleteTarefaNome">esta tarefa</strong> do projeto.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="fd-card-subtitle mb-0">
                        O conteudo da tarefa, checklist e comentarios vinculados serao excluidos permanentemente.
                    </p>
                </div>

                <div class="modal-footer fd-delete-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="fd-btn-danger-soft">
                        <i class="ri-delete-bin-line"></i>
                        <span>Excluir tarefa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
