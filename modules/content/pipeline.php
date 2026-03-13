<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/OportunidadeModel.php';

$model = new OportunidadeModel($pdo);

// filtros básicos (a lógica ainda não está aplicada nas queries)
$origem = $_GET['origem'] ?? '';
$responsavel = $_GET['responsavel'] ?? '';
$periodo = $_GET['periodo'] ?? '';

$filtros = compact('origem', 'responsavel', 'periodo');

// estágios e oportunidades por estágio
$estagios = $model->listarEstagiosAtivos();
$oportunidadesPorEstagio = [];
$totaisPorEstagio = [];

foreach ($estagios as $est) {
    $idEstagio = (int) $est['id'];
    $ops = $model->listarPorEstagio($idEstagio);

    $oportunidadesPorEstagio[$idEstagio] = $ops;
    $totaisPorEstagio[$idEstagio] = array_sum(array_column($ops, 'valor_previsto'));
}

// clientes para o modal de nova oportunidade
$stmtCli = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$listaClientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$totalLeads = 0;
$totalValor = 0.0;
$totalValorPonderado = 0.0;

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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1">Pipeline</h1>
        <p class="text-muted small mb-0">
            Acompanhe as oportunidades em cada estágio do funil de vendas.
        </p>
    </div>

    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="mod" value="pipeline">
            <input type="hidden" name="action" value="index">

            <select name="periodo" class="form-select form-select-sm">
                <option value="">Período: Todos</option>
                <option value="mes_atual" <?= $periodo === 'mes_atual' ? 'selected' : '' ?>>Mês atual</option>
                <option value="proximos_30" <?= $periodo === 'proximos_30' ? 'selected' : '' ?>>Próximos 30 dias</option>
            </select>

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ri-filter-2-line me-1"></i>Filtrar
            </button>
        </form>

        <button type="button" class="btn btn-sm btn-outline-light " data-bs-toggle="modal"
            data-bs-target="#modalNovaOportunidade">
            <i class="ri-add-line"></i> Nova oportunidade
        </button>
    </div>
</div>
<div class="row g-3 mb-3">
    <!-- Card: Oportunidades abertas -->
    <div class="col-md-4">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="card-kpi-label">
                            <span class="card-kpi-icon">
                                <i class="ri-stack-fill"></i>
                            </span>
                            Oportunidades abertas
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value">
                        <?= $totalLeads ?>
                    </h3>
                </div>

                <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                    <span class="card-kpi-sub text-muted">
                        Soma de todas as oportunidades não concluídas
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Valor total do funil -->
    <div class="col-md-4">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="card-kpi-label">
                            <span class="card-kpi-icon">
                                <i class="ri-money-dollar-circle-fill"></i>
                            </span>
                            Valor total do funil
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value sensitive-value">
                        R$<?= number_format($totalValor, 2, ',', '.') ?>
                    </h3>
                </div>

                <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                    <span class="card-kpi-sub text-muted sensitive-value">
                        Soma de todos os valores previstos
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Valor ponderado (previsão) -->
    <div class="col-md-4">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="card-kpi-label">
                            <span class="card-kpi-icon">
                                <i class="ri-line-chart-fill"></i>
                            </span>
                            Valor ponderado previsto
                        </span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value sensitive-value">
                        R$<?= number_format($totalValorPonderado, 2, ',', '.') ?>
                    </h3>
                </div>

                <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                    <span class="card-kpi-sub text-muted sensitive-value">
                        Estimativa de receita esperada do funil
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($estagios)): ?>
    <div class="alert alert-warning small mb-0">
        Nenhum estágio de funil cadastrado.
    </div>
<?php else: ?>
    <div class="pipeline-board d-flex gap-3 pb-3">
        <?php foreach ($estagios as $estagio): ?>
            <?php
            $idEstagio = (int) $estagio['id'];
            $oportunidades = $oportunidadesPorEstagio[$idEstagio] ?? [];
            $totalEstagio = $totaisPorEstagio[$idEstagio] ?? 0.0;

            // ajuste aqui: use slug ou id do estágio "Perdido"
            $isPerdidoStage = (($estagio['slug'] ?? '') === 'perdido');
            ?>
            <div class="pipeline-column bg-dark rounded-3 p-3 flex-shrink-0" style="min-width: 24.24%;"
                data-estagio-id="<?= $idEstagio ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill"
                                style="background-color: <?= htmlspecialchars($estagio['cor_hex'] ?? '#4f46e5') ?>;">
                                <?= htmlspecialchars($estagio['nome']) ?>
                            </span>
                            <span class="badge bg-secondary">
                                <?= count($oportunidades) ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            Total: R$ <?= number_format($totalEstagio, 2, ',', '.') ?>
                        </small>
                    </div>
                </div>

                <div class="pipeline-column-body d-flex flex-column gap-2 mt-2">
                    <?php if (empty($oportunidades)): ?>
                        <div class="card bg-transparent text-muted small">
                            <div class="card-body py-3 text-center">
                                Nenhuma oportunidade neste estágio.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($oportunidades as $op): ?>
                            <?php
                            $prob = (int) $op['probabilidade'];
                            $dataPrevista = $op['data_prevista_fechamento']
                                ? date('d/m/Y', strtotime($op['data_prevista_fechamento']))
                                : 'Sem data';
                            ?>
                            <div class="card bg-dark shadow-sm pipeline-card" draggable="true" data-id="<?= (int) $op['id'] ?>">
                                <div class="card-body bg-dark lead p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <div class="fw-semibold text-white small">
                                                <?= htmlspecialchars($op['titulo']) ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($op['cliente_nome']) ?>
                                            </div>
                                        </div>
                                        <div class="smal">
                                            <button type="button" class="btn btn-icon btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarOportunidade"
                                                data-id="<?= (int) $op['id'] ?>">
                                                <i class="ri-pencil-line"></i>
                                            </button>

                                            <?php if ($isPerdidoStage): ?>
                                                <!-- estágio Perdido: opção de excluir lead -->
                                                <form method="post" action="/app/Controllers/PipelineController.php?acao=excluir"
                                                    onsubmit="return confirm('Excluir definitivamente esta oportunidade?');"
                                                    class="d-inline">
                                                    <input type="hidden" name="id" value="<?= (int) $op['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-light btn-sm">
                                                        <i class="ri-delete-bin-2-fill"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- demais estágios: criar / ver projeto -->
                                                <?php if (empty($op['projeto_id'])): ?>
                                                    <button type="button" class="btn btn-outline-light btn-sm"
                                                        onclick="window.location.href='/modules/painel.php?mod=projetos&cliente_id=<?= (int) $op['cliente_id'] ?>&oportunidade_id=<?= (int) $op['id'] ?>'">
                                                        <i class="ri-folder-add-fill me-1"></i> Criar Projeto
                                                    </button>

                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-light btn-sm"
                                                        onclick="window.location.href='/modules/painel.php?mod=projeto_detalhe&id=<?= (int) $op['projeto_id'] ?>'">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold text-success small">
                                            R$ <?= number_format((float) $op['valor_previsto'], 2, ',', '.') ?>
                                        </span>
                                        <span class="badge bg-secondary small">
                                            <?= $prob ?>%
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn me-2 btn-op-ganha" data-id="<?= (int) $op['id'] ?>">
                                                <i class="ri-thumb-up-fill"></i>
                                            </button>
                                            <button type="button" class="btn btn-op-perder" data-id="<?= (int) $op['id'] ?>">
                                                <i class="ri-thumb-down-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        Previsto: <?= $dataPrevista ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<div class="d-flex justify-content-center">
<a href="/modules/painel.php?mod=relatorio_conversao"
           class="btn btn-relatorio btn-sm">
            <i class="ri-bar-chart-2-fill"></i> Ver Relatório de Conversão
        </a>
</div>
<?php include __DIR__ . '/../modals/modal_crm.php'; ?>