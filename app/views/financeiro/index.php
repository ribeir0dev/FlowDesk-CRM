<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../app/Models/FinanceiroModel.php';

$mes_atual = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes_atual)) {
    $mes_atual = date('Y-m');
}

$aba_atual = $_GET['aba'] ?? 'analise';
if (!in_array($aba_atual, ['analise', 'entradas', 'saidas', 'fixos'], true)) {
    $aba_atual = 'analise';
}

$mes_label = date('m/Y', strtotime($mes_atual . '-01'));
$inicio_mes = $mes_atual . '-01';
$fim_mes = date('Y-m-t', strtotime($inicio_mes));

$model = new FinanceiroModel($pdo);

$totais = $model->totaisMes($inicio_mes, $fim_mes);
$total_entradas_mes = $totais['entradas_mes'];
$total_saidas_mes = $totais['saidas_mes'];
$caixa_total = $totais['caixa_total'];
$caixa_mes = $totais['caixa_mes'];

$saidas_por_tipo = $model->totaisSaidasPorTipo($inicio_mes, $fim_mes);
$ano_atual = substr($mes_atual, 0, 4);
$anoResumo = $model->totaisAnoPorMes($ano_atual);

$ano = (int) substr($mes_atual, 0, 4);
$mesN = (int) substr($mes_atual, 5, 2);
$dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mesN, $ano);

$media_entrada_dia = $dias_no_mes ? $total_entradas_mes / $dias_no_mes : 0;
$media_saida_dia = $dias_no_mes ? $total_saidas_mes / $dias_no_mes : 0;
$saldo_medio_dia = $media_entrada_dia - $media_saida_dia;

$inicio_mes_anterior = date('Y-m-01', strtotime($inicio_mes . ' -1 month'));
$fim_mes_anterior = date('Y-m-t', strtotime($inicio_mes_anterior));

$totais_ant = $model->totaisMes($inicio_mes_anterior, $fim_mes_anterior);
$entradas_ant = $totais_ant['entradas_mes'];
$saidas_ant = $totais_ant['saidas_mes'];
$mesesPicker = [
    '01' => 'Jan',
    '02' => 'Fev',
    '03' => 'Mar',
    '04' => 'Abr',
    '05' => 'Mai',
    '06' => 'Jun',
    '07' => 'Jul',
    '08' => 'Ago',
    '09' => 'Set',
    '10' => 'Out',
    '11' => 'Nov',
    '12' => 'Dez',
];

function variacao_pct($atual, $anterior)
{
    if ($anterior == 0) {
        return $atual > 0 ? 100 : 0;
    }
    return (($atual - $anterior) / $anterior) * 100;
}

$var_entradas = variacao_pct($total_entradas_mes, $entradas_ant);
$var_saidas = variacao_pct($total_saidas_mes, $saidas_ant);

$entradas = $model->listarEntradasMes($inicio_mes, $fim_mes);
$saidas = $model->listarSaidasMes($inicio_mes, $fim_mes);
$fixosPagosMes = $model->idsFixosPagosNoMes($inicio_mes, $fim_mes);
$fixos = $model->listarFixosAtivosAte($fim_mes);
$total_fixos_mes = $model->totalFixosMesNaoPagos($fim_mes, $inicio_mes, $fim_mes);

function fd_money_value($value): string
{
    return 'R$ ' . money($value);
}
?>

<div class="fd-financeiro" x-data="{ tab: '<?= e($aba_atual) ?>' }" data-financeiro-tab="<?= e($aba_atual) ?>">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Resumo financeiro</p>
            <p class="fd-page-subtitle">Visao geral de entradas, saidas, caixa e gastos fixos do mes.</p>
        </div>

        <div class="fd-page-actions">
            <form method="get" class="fd-inline-form">
                <input type="hidden" name="aba" :value="tab">

                <div
                    class="fd-month-picker"
                    x-data="flowdeskMonthPicker('<?= htmlspecialchars($mes_atual, ENT_QUOTES) ?>')"
                    @keydown.escape.window="close()"
                >
                    <input type="hidden" name="mes" x-model="selectedValue">

                    <button
                        type="button"
                        class="fd-month-picker-trigger"
                        @click="toggle()"
                        :aria-expanded="open.toString()"
                    >
                        <span class="fd-month-picker-trigger-icon">
                            <i class="ri-calendar-event-line"></i>
                        </span>
                        <span class="fd-month-picker-trigger-copy">
                            <span class="fd-month-picker-trigger-label">Periodo</span>
                            <strong x-text="triggerLabel"></strong>
                        </span>
                        <i class="ri-arrow-down-s-line fd-month-picker-trigger-arrow"></i>
                    </button>

                    <div
                        class="fd-month-picker-panel"
                        x-show="open"
                        x-cloak
                        x-transition.opacity.scale.origin.top.right
                        @click.outside="close()"
                    >
                        <div class="fd-month-picker-head">
                            <button type="button" class="fd-month-picker-nav" @click="prevYear()">
                                <i class="ri-arrow-left-s-line"></i>
                            </button>
                            <div class="fd-month-picker-head-copy">
                                <span class="fd-month-picker-head-label">Selecione o mes</span>
                                <strong x-text="displayYear"></strong>
                            </div>
                            <button type="button" class="fd-month-picker-nav" @click="nextYear()">
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </div>

                        <div class="fd-month-picker-grid">
                            <?php foreach ($mesesPicker as $numeroMes => $labelMes): ?>
                                <button
                                    type="button"
                                    class="fd-month-picker-month"
                                    :class="monthButtonClass('<?= $numeroMes ?>')"
                                    @click="selectMonth('<?= $numeroMes ?>')"
                                >
                                    <?= $labelMes ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="fd-month-picker-footer">
                            <button type="button" class="fd-month-picker-link" @click="resetToCurrent()">
                                Este mes
                            </button>
                            <button type="button" class="fd-month-picker-apply" @click="submit()">
                                Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="fd-tabs">
        <button type="button" class="fd-tab" data-tab="analise" :class="{ 'is-active': tab === 'analise' }" @click="tab = 'analise'">Analise</button>
        <button type="button" class="fd-tab" data-tab="entradas" :class="{ 'is-active': tab === 'entradas' }" @click="tab = 'entradas'">Entradas</button>
        <button type="button" class="fd-tab" data-tab="saidas" :class="{ 'is-active': tab === 'saidas' }" @click="tab = 'saidas'">Saidas</button>
        <button type="button" class="fd-tab" data-tab="fixos" :class="{ 'is-active': tab === 'fixos' }" @click="tab = 'fixos'">Gasto Fixo</button>
    </section>

    <section x-show="tab === 'analise'" x-cloak class="fd-fin-panel fd-fin-panel-analise">
        <div class="fd-kpi-grid fd-kpi-grid-4">
            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">
                        <span class="fd-kpi-icon fd-kpi-icon-green">
                            <i class="ri-arrow-down-box-fill"></i>
                        </span>
                        Entradas (<?= e($mes_label) ?>)
                    </span>
                </div>

                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value fd-value-green"><?= fd_money_value($total_entradas_mes) ?></h3>
                    <span class="fd-kpi-trend <?= $var_entradas >= 0 ? 'fd-trend-positive' : 'fd-trend-negative' ?>">
                        <?= $var_entradas >= 0 ? '+' : '' ?><?= number_format($var_entradas, 2, ',', '.') ?>%
                    </span>
                </div>

                <div class="fd-kpi-footer">
                    <span class="fd-kpi-note <?= $var_entradas >= 0 ? 'fd-trend-positive' : 'fd-trend-negative' ?>">
                        <?= $var_entradas >= 0 ? '+' : '' ?><?= number_format($var_entradas, 2, ',', '.') ?>% em relacao ao mes passado
                    </span>
                </div>
            </article>

            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">
                        <span class="fd-kpi-icon fd-kpi-icon-red">
                            <i class="ri-arrow-up-box-fill"></i>
                        </span>
                        Saidas (<?= e($mes_label) ?>)
                    </span>
                </div>

                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value fd-value-red"><?= fd_money_value($total_saidas_mes) ?></h3>
                    <span class="fd-kpi-trend <?= $var_saidas >= 0 ? 'fd-trend-negative' : 'fd-trend-positive' ?>">
                        <?= $var_saidas >= 0 ? '+' : '' ?><?= number_format($var_saidas, 2, ',', '.') ?>%
                    </span>
                </div>

                <div class="fd-kpi-footer">
                    <span class="fd-kpi-note <?= $var_saidas >= 0 ? 'fd-trend-negative' : 'fd-trend-positive' ?>">
                        <?= $var_saidas >= 0 ? '+' : '' ?><?= number_format($var_saidas, 2, ',', '.') ?>% em relacao ao mes passado
                    </span>
                </div>
            </article>

            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">
                        <span class="fd-kpi-icon fd-kpi-icon-violet">
                            <i class="ri-bank-fill"></i>
                        </span>
                        Caixa disponivel
                    </span>
                </div>

                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value <?= $caixa_total >= 0 ? 'fd-value-green' : 'fd-value-red' ?>">
                        <?= fd_money_value($caixa_total) ?>
                    </h3>
                </div>

                <div class="fd-kpi-footer">
                    <span class="fd-kpi-note fd-trend-neutral">Dinheiro disponivel na conta Santander</span>
                </div>
            </article>

            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">
                        <span class="fd-kpi-icon <?= $caixa_mes >= $total_fixos_mes ? 'fd-kpi-icon-green' : 'fd-kpi-icon-red' ?>">
                            <i class="ri-shield-check-line"></i>
                        </span>
                        Gastos previstos
                    </span>
                </div>

                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value"><?= fd_money_value($total_fixos_mes) ?></h3>
                </div>

                <div class="fd-kpi-footer">
                    <span class="fd-kpi-note fd-trend-neutral">Estimativa de gastos previstos para esse mes</span>
                </div>
            </article>
        </div>

        <div class="fd-kpi-grid fd-kpi-grid-3">
            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">Media diaria de entrada</span>
                </div>
                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value fd-value-green"><?= fd_money_value($media_entrada_dia) ?></h3>
                </div>
            </article>

            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">Media diaria de saida</span>
                </div>
                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value fd-value-red"><?= fd_money_value($media_saida_dia) ?></h3>
                </div>
            </article>

            <article class="fd-card fd-kpi-card">
                <div class="fd-kpi-top">
                    <span class="fd-kpi-label">Saldo medio diario</span>
                </div>
                <div class="fd-kpi-main">
                    <h3 class="fd-kpi-value <?= $saldo_medio_dia >= 0 ? 'fd-value-green' : 'fd-value-red' ?>">
                        <?= fd_money_value($saldo_medio_dia) ?>
                    </h3>
                </div>
            </article>
        </div>

        <div class="fd-fin-grid">
            <article class="fd-card">
                <div class="fd-card-header">
                    <div>
                        <p class="fd-card-title">
                            <span class="fd-section-icon"><i class="ri-pie-chart-2-fill"></i></span>
                            Saidas por tipo
                        </p>
                        <p class="fd-card-subtitle">Distribuicao mensal</p>
                    </div>
                </div>

                <div class="fd-chart-card">
                    <div class="fd-chart-box fd-chart-box-sm">
                        <div id="chartSaidasTipo"></div>
                    </div>
                    <div id="legendSaidasTipo" class="fd-chart-legend"></div>
                </div>
            </article>

            <article class="fd-card">
                <div class="fd-card-header">
                    <div>
                        <p class="fd-card-title">
                            <span class="fd-section-icon"><i class="ri-bar-chart-box-line"></i></span>
                            Rendimento anual
                        </p>
                        <p class="fd-card-subtitle"><?= e($ano_atual) ?></p>
                    </div>
                </div>

                <div class="fd-chart-box">
                    <div id="chartAno"></div>
                </div>
            </article>
        </div>
    </section>

    <section x-show="tab === 'entradas'" x-cloak class="fd-fin-panel">
        <div class="fd-card">
            <div class="fd-card-header fd-card-header-stack-mobile">
                <div>
                    <p class="fd-card-title">
                        <span class="fd-section-icon"><i class="ri-arrow-down-box-fill"></i></span>
                        Entradas
                    </p>
                    <p class="fd-card-subtitle">Receitas registradas no periodo selecionado</p>
                </div>

                <div class="fd-action-group">
                    <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaEntrada">
                        <i class="ri-add-line"></i>
                        <span>Nova entrada</span>
                    </button>

                    <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalGastoFixo">
                        <i class="ri-coins-line"></i>
                        <span>Gasto fixo</span>
                    </button>
                </div>
            </div>

            <div class="fd-table-wrap">
                <table class="fd-table fd-table-entradas">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Cliente</th>
                            <th>Descricao</th>
                            <th>Forma</th>
                            <th class="fd-text-right">A receber</th>
                            <th class="fd-text-right">Recebido</th>
                            <th class="fd-text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entradas)): ?>
                            <tr>
                                <td colspan="7" class="fd-empty-state">Nenhuma entrada neste mes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entradas as $e): ?>
                                <tr>
                                    <td><?= date('d', strtotime($e['data_lancamento'])) ?></td>
                                    <td>
                                        <?php if (!empty($e['cliente_nome'])): ?>
                                            <?= e($e['cliente_nome']) ?>
                                        <?php else: ?>
                                            <span class="fd-text-muted">Sem cliente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($e['descricao']) ?></td>
                                    <td><?= strtoupper($e['forma_pagamento']) ?></td>
                                    <td class="fd-text-right"><?= fd_money_value($e['valor_a_receber']) ?></td>
                                    <td class="fd-text-right"><?= fd_money_value($e['valor_recebido']) ?></td>
                                    <td class="fd-text-right">
                                        <div class="fd-table-actions">
                                            <button
                                                type="button"
                                                class="fd-btn-table fd-btn-table-icon btn-editar-entrada"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalNovaEntrada"
                                                data-id="<?= (int) $e['id'] ?>"
                                                title="Editar entrada"
                                                aria-label="Editar entrada"
                                            >
                                                <i class="ri-pencil-line"></i>
                                            </button>

                                            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="js-confirm-delete" data-confirm-msg="Deseja mesmo excluir esta entrada?">
                                                <input type="hidden" name="acao" value="excluir_entrada">
                                                <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
                                                <button type="submit" class="fd-btn-table fd-btn-table-icon fd-btn-table-danger" title="Excluir entrada" aria-label="Excluir entrada">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section x-show="tab === 'saidas'" x-cloak class="fd-fin-panel">
        <div class="fd-card">
            <div class="fd-card-header fd-card-header-stack-mobile">
                <div>
                    <p class="fd-card-title">
                        <span class="fd-section-icon"><i class="ri-arrow-up-box-fill"></i></span>
                        Saidas
                    </p>
                    <p class="fd-card-subtitle">Despesas registradas no periodo selecionado</p>
                </div>

                <div class="fd-action-group">
                    <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaSaida">
                        <i class="ri-add-line"></i>
                        <span>Nova saida</span>
                    </button>
                </div>
            </div>

            <div class="fd-table-wrap">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descricao</th>
                            <th class="fd-text-right">Valor</th>
                            <th class="fd-text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($saidas)): ?>
                            <tr>
                                <td colspan="5" class="fd-empty-state">Nenhuma saida neste mes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($saidas as $s): ?>
                                <tr>
                                    <td><?= date_br($s['data_lancamento']) ?></td>
                                    <td><?= ucfirst($s['tipo']) ?></td>
                                    <td><?= e($s['descricao']) ?></td>
                                    <td class="fd-text-right"><?= fd_money_value($s['valor']) ?></td>
                                    <td class="fd-text-right">
                                        <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="js-confirm-delete fd-inline-form" data-confirm-msg="Deseja mesmo excluir esta saida?">
                                            <input type="hidden" name="acao" value="excluir_saida">
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="fd-btn-table fd-btn-table-icon fd-btn-table-danger" title="Excluir saida" aria-label="Excluir saida">
                                                <i class="ri-delete-bin-line"></i>
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
    </section>

    <section x-show="tab === 'fixos'" x-cloak class="fd-fin-panel">
        <div class="fd-card">
            <div class="fd-card-header fd-card-header-stack-mobile">
                <div>
                    <p class="fd-card-title">
                        <span class="fd-section-icon"><i class="ri-coin-line"></i></span>
                        Gastos fixos
                    </p>
                    <p class="fd-card-subtitle">Controle de despesas recorrentes e parceladas</p>
                </div>

                <div class="fd-action-group">
                    <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalGastoFixo">
                        <i class="ri-add-line"></i>
                        <span>Novo gasto fixo</span>
                    </button>
                </div>
            </div>

            <div class="fd-table-wrap">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th>Tipo de gasto</th>
                            <th class="fd-text-right">Valor</th>
                            <th>Parcelas</th>
                            <th>Status</th>
                            <th class="fd-text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fixos as $f): ?>
                            <?php
                            $totaisParcelas = (int) $f['parcelas_totais'];
                            $ehParcelado = (int) $f['eh_parcelado'] === 1;

                            if ($ehParcelado && $totaisParcelas > 0) {
                                $inicio = new DateTime($f['data_inicio']);
                                $ref = new DateTime($inicio_mes);
                                $diffMeses = ($ref->format('Y') - $inicio->format('Y')) * 12
                                    + ($ref->format('m') - $inicio->format('m'))
                                    + 1;
                                $parcelaAtual = max(1, min($totaisParcelas, $diffMeses));
                                $textoParcelas = "{$parcelaAtual}/{$totaisParcelas}";
                            } else {
                                $textoParcelas = '-';
                            }

                            $foiPagoNesteMes = in_array($f['id'], $fixosPagosMes, true);
                            ?>
                            <tr>
                                <td><?= e($f['tipo_gasto']) ?></td>
                                <td class="fd-text-right"><?= fd_money_value($f['valor']) ?></td>
                                <td>
                                    <span class="fd-badge fd-badge-neutral"><?= $textoParcelas ?></span>
                                </td>
                                <td>
                                    <?php if ($foiPagoNesteMes): ?>
                                        <span class="fd-badge fd-badge-success">Pago</span>
                                    <?php else: ?>
                                        <span class="fd-badge fd-badge-warning">A ser pago</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fd-text-right">
                                    <div class="fd-table-actions">
                                        <?php if (!$foiPagoNesteMes): ?>
                                            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="fd-inline-form" onsubmit="return confirm('Confirmar pagamento deste gasto fixo neste mes?');">
                                                <input type="hidden" name="acao" value="pagar_fixo">
                                                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                                <button type="submit" class="fd-btn-table fd-btn-table-icon fd-btn-table-success" title="Marcar como pago" aria-label="Marcar como pago">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="fd-inline-form" onsubmit="return confirm('Remover este gasto fixo permanentemente?');">
                                            <input type="hidden" name="acao" value="remover_fixo">
                                            <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                            <button type="submit" class="fd-btn-table fd-btn-table-icon fd-btn-table-danger" title="Excluir gasto fixo" aria-label="Excluir gasto fixo">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/partials/modal_financeiro.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const financeRoot = document.querySelector('.fd-financeiro');

    if (financeRoot) {
        const syncFinanceiroForms = function () {
            const activeTab = financeRoot.dataset.financeiroTab || 'analise';
            financeRoot.dataset.financeiroTab = activeTab;

            document.querySelectorAll('form[action$="/financeiro/acao"]').forEach(function (form) {
                let abaInput = form.querySelector('input[name="aba"]');
                if (!abaInput) {
                    abaInput = document.createElement('input');
                    abaInput.type = 'hidden';
                    abaInput.name = 'aba';
                    form.appendChild(abaInput);
                }
                abaInput.value = activeTab;
            });
        };

        syncFinanceiroForms();

        document.querySelectorAll('.fd-tabs .fd-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                financeRoot.dataset.financeiroTab = btn.dataset.tab || 'analise';
                syncFinanceiroForms();
            });
        });
    }

});

window.dadosSaidasTipo = {
    labels: <?= json_encode(array_column($saidas_por_tipo, 'tipo')) ?>,
    valores: <?= json_encode(array_map('floatval', array_column($saidas_por_tipo, 'total'))) ?>
};

window.anoLabels   = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
window.anoEntradas = <?= json_encode(array_values($anoResumo['entradas'])) ?>;
window.anoSaidas   = <?= json_encode(array_values($anoResumo['saidas'])) ?>;
</script>

