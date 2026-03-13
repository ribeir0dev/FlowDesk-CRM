<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/FinanceiroModel.php';

$mes_atual = $_GET['mes'] ?? date('Y-m'); // YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $mes_atual)) {
    $mes_atual = date('Y-m');
}
$mes_label = date('m/Y', strtotime($mes_atual . '-01'));

$inicio_mes = $mes_atual . '-01';
$fim_mes = date('Y-m-t', strtotime($inicio_mes));

$model = new FinanceiroModel($pdo);

// totais
$totais = $model->totaisMes($inicio_mes, $fim_mes);
$total_entradas_mes = $totais['entradas_mes'];
$total_saidas_mes = $totais['saidas_mes'];
$caixa_total = $totais['caixa_total'];
$caixa_mes = $totais['caixa_mes'];
$saidas_por_tipo = $model->totaisSaidasPorTipo($inicio_mes, $fim_mes);
$ano_atual = substr($mes_atual, 0, 4);
$anoResumo = $model->totaisAnoPorMes($ano_atual);

// médias
$ano = (int) substr($mes_atual, 0, 4);
$mesN = (int) substr($mes_atual, 5, 2);
$dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mesN, $ano); // função nativa [web:264]

$media_entrada_dia = $dias_no_mes ? $total_entradas_mes / $dias_no_mes : 0;
$media_saida_dia = $dias_no_mes ? $total_saidas_mes / $dias_no_mes : 0;
$saldo_medio_dia = $media_entrada_dia - $media_saida_dia;


// resumo
$inicio_mes_anterior = date('Y-m-01', strtotime($inicio_mes . ' -1 month'));
$fim_mes_anterior = date('Y-m-t', strtotime($inicio_mes_anterior));

$totais_ant = $model->totaisMes($inicio_mes_anterior, $fim_mes_anterior);

$entradas_ant = $totais_ant['entradas_mes'];
$saidas_ant = $totais_ant['saidas_mes'];

function variacao_pct($atual, $anterior)
{
    if ($anterior == 0) {
        return $atual > 0 ? 100 : 0;
    }
    return (($atual - $anterior) / $anterior) * 100;
}

$var_entradas = variacao_pct($total_entradas_mes, $entradas_ant);
$var_saidas = variacao_pct($total_saidas_mes, $saidas_ant);

// listas
$entradas = $model->listarEntradasMes($inicio_mes, $fim_mes);
$saidas = $model->listarSaidasMes($inicio_mes, $fim_mes);
$fixosPagosMes = $model->idsFixosPagosNoMes($inicio_mes, $fim_mes);
$fixos = $model->listarFixosAtivosAte($fim_mes);
$total_fixos_mes = $model->totalFixosMesNaoPagos($fim_mes, $inicio_mes, $fim_mes);
?>

<!-- Cabeçalho + filtro de mês (fora das abas, pois afeta tudo) -->
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h5 class="mb-1">Financeiro</h5>
        <div class="small text-muted">Visão geral de entradas, saídas e gastos fixos do mês.</div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">
            <strong><?= e($mes_label) ?></strong>
        </span>
        <form method="get" class="position-relative">
            <input type="hidden" name="mod" value="financeiro">
            <input type="month" id="filtroMesFinanceiro" name="mes" value="<?= e($mes_atual) ?>"
                style="position:absolute; opacity:0; pointer-events:none; width:0; height:0;">
            <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center"
                onclick="document.getElementById('filtroMesFinanceiro').showPicker();">
                <i class="ri-calendar-2-fill"></i>
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('filtroMesFinanceiro').addEventListener('change', function () {
        this.form.submit();
    });
</script>

<!-- NAV TABS -->
<ul class="nav nav-tabs mb-3 gap-3" id="financeiroTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-analise" data-bs-toggle="tab" data-bs-target="#pane-analise"
            type="button" role="tab">
            Análise
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-entradas" data-bs-toggle="tab" data-bs-target="#pane-entradas" type="button"
            role="tab">
            Entradas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-saidas" data-bs-toggle="tab" data-bs-target="#pane-saidas" type="button"
            role="tab">
            Saídas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-fixos" data-bs-toggle="tab" data-bs-target="#pane-fixos" type="button"
            role="tab">
            Gasto Fixo
        </button>
    </li>
</ul>

<div class="tab-content" id="financeiroTabContent">

    <!-- ABA ANÁLISE -->
    <div class="tab-pane fade show active" id="pane-analise" role="tabpanel" aria-labelledby="tab-analise">
        <!-- 1ª LINHA: Resumo financeiro + filtro de mês (apenas cards) -->
        <div class="row g-3 mb-4">
            <!-- Entradas -->
            <div class="col-md-3">
                <div class="card card-kpi h-100">
                    <div class="card-body d-flex flex-column justify-content-between">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="card-kpi-label">
                                    <span class="card-kpi-icon">
                                        <i class="ri-arrow-down-box-fill"></i>
                                    </span>
                                    Entradas (<?= e($mes_label) ?>)
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-kpi-value text-entradas">
                                R$ <?= money($total_entradas_mes) ?>
                            </h3>
                            <span class="card-kpi-trend text-success">
                                <i
                                    class="ri-arrow-up-s-line me-1"></i><?= $var_entradas >= 0 ? '+' : '' ?><?= number_format($var_entradas, 2, ',', '.') ?>%
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                            <span
                                class="card-kpi-sub"><?= $var_entradas >= 0 ? '+' : '' ?><?= number_format($var_entradas, 2, ',', '.') ?>%
                                em relação ao mês passado</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Saídas -->
            <div class="col-md-3">
                <div class="card card-kpi h-100">
                    <div class="card-body d-flex flex-column justify-content-between">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="card-kpi-label">
                                    <span class="card-kpi-icon">
                                        <i class="ri-arrow-up-box-fill"></i>
                                    </span>
                                    Saídas (<?= e($mes_label) ?>)
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-kpi-value text-saidas">
                                R$ <?= money($total_saidas_mes) ?>
                            </h3>
                            <span class="card-kpi-trend text-success">
                                <i
                                    class="ri-arrow-up-s-line me-1"></i><?= $var_saidas >= 0 ? '+' : '' ?><?= number_format($var_saidas, 2, ',', '.') ?>%
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                            <span
                                class="card-kpi-sub"><?= $var_saidas >= 0 ? '+' : '' ?><?= number_format($var_saidas, 2, ',', '.') ?>%
                                em relação ao mês passado</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Caixa total -->
            <div class="col-md-3">
                <div class="card card-kpi h-100">
                    <div class="card-body d-flex flex-column justify-content-between">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="card-kpi-label">
                                    <span class="card-kpi-icon">
                                        <i class="ri-bank-fill"></i>
                                    </span>
                                    Caixa Disponível
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-kpi-value <?= $caixa_total >= 0 ? 'text-entradas' : 'text-saidas' ?>">
                                R$ <?= money($caixa_total) ?>
                            </h3>
                        </div>

                        <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                            <span class="card-kpi-sub">Dinheiro disponível na conta Santander</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Custo de vida / Gastos Previstos -->
            <?php
            $icone_verde = $caixa_mes >= $total_fixos_mes;
            $corIcone = $icone_verde ? '#60E999' : '#E96060';
            ?>
            <div class="col-md-3">
                <div class="card card-kpi h-100">
                    <div class="card-body d-flex flex-column justify-content-between">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="card-kpi-label">
                                    <span class="card-kpi-icon">
                                        <div class="">
                                            <svg width="24" height="24" viewBox="0 0 25 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M11.3156 2.35225C11.9098 2.0805 12.5928 2.0805 13.1871 2.35225L18.9111 4.96991C19.7118 5.33608 20.2253 6.13562 20.2253 7.01607L20.2254 12.4811C20.2254 14.0375 19.7481 15.5838 18.7447 16.8253C17.9444 17.8155 16.8583 19.0727 15.7472 20.089C15.1923 20.5966 14.6131 21.0609 14.0452 21.4022C13.4919 21.7349 12.8712 21.999 12.2514 21.999C11.6317 21.999 11.011 21.7349 10.4576 21.4022C9.88978 21.0608 9.31059 20.5966 8.75563 20.0889C7.64456 19.0726 6.55842 17.8154 5.75814 16.8252C4.75474 15.5837 4.27744 14.0376 4.27742 12.4813L4.27734 7.01612C4.27733 6.13565 4.79089 5.33607 5.5916 4.9699L11.3156 2.35225ZM13.0019 6.24903C13.0019 5.83481 12.6662 5.49903 12.2519 5.49903C11.8377 5.49903 11.5019 5.83481 11.5019 6.24903V6.71902C10.4999 6.94639 9.75194 7.84248 9.75194 8.91327V9.17834C9.75194 10.1162 10.3337 10.9557 11.2119 11.2851L12.7653 11.8677C13.058 11.9774 13.2519 12.2573 13.2519 12.5699V12.835C13.2519 13.2492 12.9162 13.585 12.5019 13.585H11.8145C11.5038 13.585 11.2519 13.3331 11.2519 13.0224C11.2519 12.6082 10.9162 12.2724 10.5019 12.2724C10.0877 12.2724 9.75194 12.6082 9.75194 13.0224C9.75194 14.0552 10.5111 14.9108 11.5019 15.0614V15.499C11.5019 15.9132 11.8377 16.249 12.2519 16.249C12.6662 16.249 13.0019 15.9132 13.0019 15.499V15.0292C14.0039 14.8018 14.7519 13.9058 14.7519 12.835V12.5699C14.7519 11.632 14.1702 10.7925 13.292 10.4632L11.7386 9.88059C11.4459 9.77081 11.2519 9.49097 11.2519 9.17834V8.91327C11.2519 8.49906 11.5877 8.16327 12.0019 8.16327H12.6893C13.0001 8.16327 13.2519 8.41515 13.2519 8.72587C13.2519 9.14008 13.5877 9.47587 14.0019 9.47587C14.4162 9.47587 14.7519 9.14008 14.7519 8.72587C14.7519 7.693 13.9927 6.83744 13.0019 6.6868V6.24903Z"
                                                    fill="<?= $corIcone ?>" />
                                            </svg>
                                        </div>
                                    </span>
                                    Gastos Previstos
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-kpi-value">
                                R$ <?= money($total_fixos_mes) ?>
                            </h3>
                            <span class="card-kpi-trend text-success">
                                
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                            <span class="card-kpi-sub">Estimativa de Gastos Previstos para esse mês</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-kpi h-100">
                    <div class="card-body">
                        <span class="card-kpi-label">Média diária de entrada</span>
                        <h3 class="card-kpi-value text-entradas">
                            R$
                            <?= money($media_entrada_dia) ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-kpi h-100">
                    <div class="card-body">
                        <span class="card-kpi-label">Média diária de saída</span>
                        <h3 class="card-kpi-value text-saidas">
                            R$
                            <?= money($media_saida_dia) ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-kpi h-100">
                    <div class="card-body">
                        <span class="card-kpi-label">Saldo médio diário</span>
                        <h3 class="card-kpi-value <?= $saldo_medio_dia >= 0 ? 'text-entradas' : 'text-saidas' ?>">
                            R$
                            <?= money($saldo_medio_dia) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="mb-3">Saídas por tipo (Mensal)</h6>

                        <div class="d-flex align-items-center">
                            <!-- área do gráfico menor -->
                            <div style="width: 220px; height: 220px;">
                                <canvas id="chartSaidasTipo"></canvas>
                            </div>

                            <!-- legenda ao lado -->
                            <div class="ms-4 flex-grow-1" id="legendSaidasTipo"></div>
                        </div>
                    </div>
            </div>

        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Rendimento anual (<?= e($ano_atual) ?>)</h6>
                    </div>
                    <canvas id="chartAno" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- os outros gráficos irão nas cols ao lado/abaixo -->
    </div>

</div>

<!-- ABA ENTRADAS -->
<div class="tab-pane fade" id="pane-entradas" role="tabpanel" aria-labelledby="tab-entradas">
    <div class="row g-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="ri-bar-chart-2-line me-2"></i>Movimentações financeiras
            </h6>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ri-add-fill me-2"></i> Adicionar dado
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalNovaEntrada">
                            <i class="ri-arrow-down-box-fill me-1"></i>Receita (Entrada)
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalNovaSaida">
                            <i class="ri-arrow-up-box-fill me-1"></i>Despesa (Saída)
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalGastoFixo">
                            <i class="ri-cash-fill me-1"></i>Gasto fixo
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Card Entradas -->
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-2">
                                <i class="ri-arrow-down-box-fill text-muted icon-claro"></i>
                            </div>
                            <h6 class="mb-0">Entradas</h6>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 table-financeiro">
                            <thead>
                                <tr>
                                    <th>Dia</th>
                                    <th>Cliente</th>
                                    <th>Descrição</th>
                                    <th>Forma</th>
                                    <th class="text-end">A receber</th>
                                    <th class="text-end">Recebido</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($entradas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-muted small">Nenhuma entrada neste mês.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entradas as $e): ?>
                                        <tr>
                                            <td><?= date('d', strtotime($e['data_lancamento'])) ?></td>

                                            <td>
                                                <?php if (!empty($e['cliente_nome'])): ?>
                                                    <?= e($e['cliente_nome']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">Sem cliente</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><?= e($e['descricao']) ?></td>
                                            <td><?= strtoupper($e['forma_pagamento']) ?></td>
                                            <td class="text-end">R$ <?= money($e['valor_a_receber']) ?></td>
                                            <td class="text-end">R$ <?= money($e['valor_recebido']) ?></td>

                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Ações
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <form method="post"
                                                                action="/app/Controllers/FinanceiroController.php">
                                                                <input type="hidden" name="acao" value="adicionar_entrada">
                                                                <button type="button" class="dropdown-item btn-editar-entrada"
                                                                    data-bs-toggle="modal" data-bs-target="#modalNovaEntrada"
                                                                    data-id="<?= (int) $e['id'] ?>">
                                                                    Editar
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="post"
                                                                action="/app/Controllers/FinanceiroController.php"
                                                                class="js-confirm-delete"
                                                                data-confirm-msg="Deseja mesmos excluir esta entrada?">
                                                                <input type="hidden" name="acao" value="excluir_entrada">
                                                                <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    Excluir
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ABA SAÍDAS -->
<div class="tab-pane fade" id="pane-saidas" role="tabpanel" aria-labelledby="tab-saidas">
    <div class="row g-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="ri-bar-chart-2-line me-2"></i>Movimentações financeiras
            </h6>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ri-add-fill me-2"></i> Adicionar dado
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalNovaEntrada">
                            <i class="ri-arrow-down-box-fill me-1"></i>Receita (Entrada)
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalNovaSaida">
                            <i class="ri-arrow-up-box-fill me-1"></i>Despesa (Saída)
                        </button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                            data-bs-target="#modalGastoFixo">
                            <i class="ri-cash-fill me-1"></i>Gasto fixo
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Card Saídas -->
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-2">
                                <i class="ri-arrow-up-box-fill text-muted icon-claro"></i>
                            </div>
                            <h6 class="mb-0">Saídas</h6>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 table-financeiro">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($saidas)): ?>
                                    <tr>
                                        <td colspan="5" class="text-muted small">Nenhuma saída neste mês.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($saidas as $s): ?>
                                        <tr>
                                            <td><?= date_br($s['data_lancamento']) ?></td>
                                            <td><?= ucfirst($s['tipo']) ?></td>
                                            <td><?= e($s['descricao']) ?></td>
                                            <td class="text-end">R$ <?= money($s['valor']) ?></td>
                                            <td class="text-end">
                                                <form method="post" action="/app/Controllers/FinanceiroController.php"
                                                    class="js-confirm-delete"
                                                    data-confirm-msg="Deseja mesmos excluir esta saída?">
                                                    <input type="hidden" name="acao" value="excluir_saida">
                                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                    <button class="btn btn-outline-danger btn-sm">
                                                        <i class="ri-delete-bin-fill"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ABA GASTO FIXO -->
<div class="tab-pane fade" id="pane-fixos" role="tabpanel" aria-labelledby="tab-fixos">
    <!-- 3ª LINHA: Gastos Fixos -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="ri-coin-line me-2"></i>Gastos fixos
                </h6>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tipo de gasto</th>
                            <th class="text-end">Valor</th>
                            <th>Parcelas</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fixos as $f): ?>
                            <?php
                            $totais = (int) $f['parcelas_totais'];
                            $ehParcelado = (int) $f['eh_parcelado'] === 1;

                            if ($ehParcelado && $totais > 0) {
                                $inicio = new DateTime($f['data_inicio']);
                                $ref = new DateTime($inicio_mes);
                                $diffMeses = ($ref->format('Y') - $inicio->format('Y')) * 12
                                    + ($ref->format('m') - $inicio->format('m'))
                                    + 1;
                                $parcelaAtual = max(1, min($totais, $diffMeses));
                                $textoParcelas = "{$parcelaAtual}/{$totais}";
                            } else {
                                $parcelaAtual = null;
                                $textoParcelas = '—';
                            }

                            $foiPagoNesteMes = in_array($f['id'], $fixosPagosMes, true);
                            ?>
                            <tr>
                                <td><?= e($f['tipo_gasto']) ?></td>
                                <td class="text-end">R$ <?= money($f['valor']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark px-2"><?= $textoParcelas ?></span>
                                </td>
                                <td>
                                    <?php if ($foiPagoNesteMes): ?>
                                        <span class="badge bg-success-subtle text-success">Pago</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning">A ser pago</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (!$foiPagoNesteMes): ?>
                                        <form method="post" action="/app/Controllers/FinanceiroController.php" class="d-inline"
                                            onsubmit="return confirm('Confirmar pagamento deste gasto fixo neste mês?');">
                                            <input type="hidden" name="acao" value="pagar_fixo">
                                            <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                            <button class="btn btn-success btn-sm">
                                                <i class="ri-checkbox-circle-line"></i> Pago
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="/app/Controllers/FinanceiroController.php" class="d-inline"
                                        onsubmit="return confirm('Remover este gasto fixo permanentemente?');">
                                        <input type="hidden" name="acao" value="remover_fixo">
                                        <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="ri-delete-bin-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div> <!-- fim tab-content -->

<?php
include __DIR__ . '/../modals/modal_financeiro.php';
?>

<script>
  window.dadosSaidasTipo = {
    labels: <?= json_encode(array_column($saidas_por_tipo, 'tipo')) ?>,
    valores: <?= json_encode(array_map('floatval', array_column($saidas_por_tipo, 'total'))) ?>
  };

  window.anoLabels   = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
  window.anoEntradas = <?= json_encode(array_values($anoResumo['entradas'])) ?>;
  window.anoSaidas   = <?= json_encode(array_values($anoResumo['saidas'])) ?>;
</script>

