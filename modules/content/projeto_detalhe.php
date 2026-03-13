<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/ProjetoModel.php';

$projeto_id = (int) ($_GET['id'] ?? 0);
if ($projeto_id <= 0) {
    header('Location: /modules/painel.php?mod=projetos');
    exit;
}

$model = new ProjetoModel($pdo);
$projeto = $model->buscarComCliente($projeto_id);

if (!$projeto) {
    header('Location: /modules/painel.php?mod=projetos');
    exit;
}

$tarefas = $model->listarTarefasPorProjeto($projeto_id);

// separa por coluna
$cols = [
    'backlog' => [],
    'andamento' => [],
    'revisao' => [],
    'concluido' => [],
];

foreach ($tarefas as $t) {
    $cols[$t['coluna']][] = $t;
}

function fmtData($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '—';
}
?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h5 class="mb-1"><?= htmlspecialchars($projeto['nome_projeto']) ?></h5>
        <div class="small text-muted">
            Cliente:
            <?= $projeto['cliente_nome'] ? htmlspecialchars($projeto['cliente_nome']) : 'Não vinculado' ?>
            • Início: <?= fmtData($projeto['data_inicio']) ?>
            • Entrega: <?= fmtData($projeto['data_entrega']) ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="/modules/painel.php?mod=projetos" class="btn btn-light btn-sm"><i class="ri-arrow-left-long-fill"></i>
            Voltar</a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaTarefa">
            <i class="ri-file-list-fill me-1"></i> Nova tarefa
        </button>
    </div>
</div>

<!-- Quadro Kanban -->
<div class="row g-3">

    <?php
    $colunas = [
        'backlog' => 'Backlog',
        'andamento' => 'Em andamento',
        'revisao' => 'Revisão',
        'concluido' => 'Concluído',
    ];
    ?>

    <?php foreach ($colunas as $slug => $titulo): ?>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0  h-100">
                <div class="card-header bg-light py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold"><?= htmlspecialchars($titulo) ?></span>
                        <span class="badge bg-secondary-subtle text-muted small">
                            <?= count($cols[$slug]) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-2 kanban-column-body" data-coluna="<?= htmlspecialchars($slug) ?>"
                    style="min-height: 120px;">

                    <?php if (empty($cols[$slug])): ?>
                        <p class="small text-muted text-center my-2">Sem tarefas.</p>
                    <?php else: ?>
                        <?php foreach ($cols[$slug] as $t): ?>
                            <div class="card mb-2 shadow-none rounded-3 pipeline-card" draggable="true"
                                data-id="<?= (int) $t['id'] ?>">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="small">
                                            <?= htmlspecialchars($t['titulo']) ?>
                                        </strong>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" title="Editar"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarTarefa"
                                                data-id="<?= (int) $t['id'] ?>" data-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                                                data-descricao="<?= htmlspecialchars($t['descricao'] ?? '') ?>"
                                                data-coluna="<?= htmlspecialchars($t['coluna']) ?>">
                                                <i class="ri-file-edit-fill"></i>
                                            </button>

                                            <form method="post" action="/app/Controllers/ProjetoController.php?acao=excluirTarefa"
                                                onsubmit="return confirm('Excluir esta tarefa?');">
                                                <input type="hidden" name="tarefa_id" value="<?= (int) $t['id'] ?>">
                                                <input type="hidden" name="projeto_id" value="<?= (int) $projeto_id ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="ri-delete-bin-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php if (!empty($t['descricao'])): ?>
                                        <p class="small text-muted mb-0 mt-1">
                                            <?= nl2br(htmlspecialchars($t['descricao'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<?php include __DIR__ . '/../modals/modal_projeto.php'; ?>