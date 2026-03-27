<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/OportunidadeModel.php';

$model = new OportunidadeModel($pdo);

$origem = $_GET['origem'] ?? '';
$responsavel = $_GET['responsavel'] ?? '';
$periodo = $_GET['periodo'] ?? '';

$filtros = compact('origem', 'responsavel', 'periodo');

$estagios = $model->listarEstagiosAtivos();
$oportunidadesPorEstagio = [];
$totaisPorEstagio = [];

foreach ($estagios as $est) {
    $idEstagio = (int) $est['id'];
    $ops = $model->listarPorEstagio($idEstagio);

    $oportunidadesPorEstagio[$idEstagio] = $ops;
    $totaisPorEstagio[$idEstagio] = array_sum(array_column($ops, 'valor_previsto'));
}

$workspaceId = fd_current_workspace_id() ?? 0;
$stmtCli = $pdo->prepare("SELECT id, nome FROM clientes WHERE workspace_id = ? ORDER BY nome ASC");
$stmtCli->execute([$workspaceId]);
$listaClientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$totalLeads = 0;
$totalValor = 0.0;
$totalValorPonderado = 0.0;
$mensagens = [];
$canManagePipeline = fd_has_any_role(['owner', 'admin', 'operacional']);
$canDeletePipeline = fd_has_any_role(['owner', 'admin']);

if (isset($_GET['ok'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Oportunidade salva com sucesso.'];
}
if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao no pipeline.'];
}

foreach ($oportunidadesPorEstagio as $ops) {
    foreach ($ops as $op) {
        $totalLeads++;
        $valor = (float) $op['valor_previsto'];
        $prob = isset($op['probabilidade']) ? (int) $op['probabilidade'] : 0;

        $totalValor += $valor;
        $totalValorPonderado += $valor * ($prob / 100);
    }
}
?>

<div class="fd-pipeline">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Funil comercial</p>
            <p class="fd-page-subtitle">Acompanhe o avanco das oportunidades em cada estagio do funil de vendas.</p>
        </div>

        <div class="fd-action-group">
            <form method="get" class="fd-inline-filter">
                <select name="periodo" class="fd-pipeline-select">
                    <option value="">Periodo: Todos</option>
                    <option value="mes_atual" <?= $periodo === 'mes_atual' ? 'selected' : '' ?>>Mes atual</option>
                    <option value="proximos_30" <?= $periodo === 'proximos_30' ? 'selected' : '' ?>>Proximos 30 dias</option>
                </select>

                <button type="submit" class="fd-btn-secondary">
                    <i class="ri-filter-2-line"></i>
                    <span>Filtrar</span>
                </button>
            </form>

            <?php if ($canManagePipeline): ?>
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaOportunidade">
                    <i class="ri-add-line"></i>
                    <span>Nova oportunidade</span>
                </button>
            <?php endif; ?>
        </div>
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
                    Oportunidades abertas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalLeads ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Soma de todas as oportunidades nao concluidas</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green">
                        <i class="ri-money-dollar-circle-fill"></i>
                    </span>
                    Valor total do funil
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value">R$<?= number_format($totalValor, 2, ',', '.') ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral sensitive-value">Soma de todos os valores previstos</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red">
                        <i class="ri-line-chart-fill"></i>
                    </span>
                    Valor ponderado
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value">R$<?= number_format($totalValorPonderado, 2, ',', '.') ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral sensitive-value">Estimativa de receita esperada do funil</span>
            </div>
        </article>
    </section>

    <?php if (empty($estagios)): ?>
        <section class="fd-card">
            <p class="fd-empty-copy">Nenhum estagio de funil cadastrado.</p>
        </section>
    <?php else: ?>
        <section class="fd-pipeline-board">
            <?php foreach ($estagios as $estagio): ?>
                <?php
                $idEstagio = (int) $estagio['id'];
                $oportunidades = $oportunidadesPorEstagio[$idEstagio] ?? [];
                $totalEstagio = $totaisPorEstagio[$idEstagio] ?? 0.0;
                $isPerdidoStage = (($estagio['slug'] ?? '') === 'perdido');
                ?>
                <article class="fd-card fd-pipeline-column" data-estagio-id="<?= $idEstagio ?>">
                    <div class="fd-pipeline-column-head">
                        <div>
                            <div class="fd-pipeline-column-title-row">
                                <span class="fd-pipeline-stage-tag" style="background-color: <?= htmlspecialchars($estagio['cor_hex'] ?? '#4f46e5') ?>;">
                                    <?= htmlspecialchars($estagio['nome']) ?>
                                </span>
                                <span class="fd-badge fd-badge-neutral"><?= count($oportunidades) ?></span>
                            </div>
                            <p class="fd-card-subtitle">Total: R$ <?= number_format($totalEstagio, 2, ',', '.') ?></p>
                        </div>
                    </div>

                    <div class="pipeline-column-body fd-pipeline-column-body" data-estagio-id="<?= $idEstagio ?>">
                        <?php if (empty($oportunidades)): ?>
                            <div class="fd-pipeline-empty">
                                <p>Nenhuma oportunidade neste estagio.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($oportunidades as $op): ?>
                                <?php
                                $prob = (int) $op['probabilidade'];
                                $dataPrevista = $op['data_prevista_fechamento']
                                    ? date('d/m/Y', strtotime($op['data_prevista_fechamento']))
                                    : 'Sem data';
                                ?>
                                <div class="fd-pipeline-card pipeline-card" draggable="<?= $canManagePipeline ? 'true' : 'false' ?>" data-id="<?= (int) $op['id'] ?>">
                                    <div class="fd-pipeline-card-top">
                                        <div>
                                            <h3 class="fd-pipeline-card-title"><?= htmlspecialchars($op['titulo']) ?></h3>
                                            <p class="fd-pipeline-card-client"><?= htmlspecialchars($op['cliente_nome']) ?></p>
                                        </div>

                                        <div class="fd-pipeline-card-actions">
                                            <?php if ($canManagePipeline): ?>
                                                <button
                                                    type="button"
                                                    class="fd-btn-table"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditarOportunidade"
                                                    data-id="<?= (int) $op['id'] ?>"
                                                    data-titulo="<?= htmlspecialchars($op['titulo'] ?? '', ENT_QUOTES) ?>"
                                                    data-cliente-id="<?= (int) ($op['cliente_id'] ?? 0) ?>"
                                                    data-funil-estagio-id="<?= (int) ($op['funil_estagio_id'] ?? 0) ?>"
                                                    data-valor-previsto="<?= htmlspecialchars((string) ($op['valor_previsto'] ?? ''), ENT_QUOTES) ?>"
                                                    data-probabilidade="<?= (int) ($op['probabilidade'] ?? 0) ?>"
                                                    data-origem-lead="<?= htmlspecialchars($op['origem_lead'] ?? '', ENT_QUOTES) ?>"
                                                    data-responsavel="<?= htmlspecialchars($op['responsavel'] ?? '', ENT_QUOTES) ?>"
                                                    data-data-prevista-fechamento="<?= htmlspecialchars($op['data_prevista_fechamento'] ?? '', ENT_QUOTES) ?>"
                                                    data-observacoes="<?= htmlspecialchars($op['observacoes'] ?? '', ENT_QUOTES) ?>"
                                                >
                                                    <i class="ri-pencil-line"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($isPerdidoStage && $canDeletePipeline): ?>
                                                <form method="post" action="<?= ($base ?? '') ?>/pipeline/excluir" onsubmit="return confirm('Excluir definitivamente esta oportunidade?');">
                                                    <input type="hidden" name="id" value="<?= (int) $op['id'] ?>">
                                                    <button type="submit" class="fd-btn-table fd-btn-table-danger">
                                                        <i class="ri-delete-bin-2-fill"></i>
                                                    </button>
                                                </form>
                                            <?php elseif (empty($op['projeto_id']) && $canManagePipeline): ?>
                                                <button
                                                    type="button"
                                                    class="fd-btn-table"
                                                    onclick="window.location.href='/projetos?cliente_id=<?= (int) $op['cliente_id'] ?>&oportunidade_id=<?= (int) $op['id'] ?>'"
                                                >
                                                    <i class="ri-folder-add-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="fd-btn-table"
                                                    onclick="window.location.href='/projeto?id=<?= (int) $op['projeto_id'] ?>'"
                                                >
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="fd-pipeline-card-middle">
                                        <span class="fd-pipeline-money">R$ <?= number_format((float) $op['valor_previsto'], 2, ',', '.') ?></span>
                                        <span class="fd-badge fd-badge-info"><?= $prob ?>%</span>
                                    </div>

                                        <div class="fd-pipeline-card-bottom">
                                            <?php if ($canManagePipeline): ?>
                                                <div class="fd-pipeline-vote">
                                                    <button type="button" class="fd-btn-table btn-op-ganha" data-id="<?= (int) $op['id'] ?>">
                                                        <i class="ri-thumb-up-fill"></i>
                                                    </button>
                                                    <button type="button" class="fd-btn-table btn-op-perder" data-id="<?= (int) $op['id'] ?>">
                                                        <i class="ri-thumb-down-fill"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>

                                            <span class="fd-pipeline-date">Previsto: <?= $dataPrevista ?></span>
                                        </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <div class="fd-pipeline-report">
        <a href="/relatorio-conversao" class="fd-btn-secondary">
            <i class="ri-bar-chart-2-fill"></i>
            <span>Ver relatório de conversão</span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../pipeline/partials/modal_crm.php'; ?>

