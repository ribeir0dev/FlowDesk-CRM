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

$aba_atual = $_GET['aba'] ?? 'visao';
if (!in_array($aba_atual, ['visao', 'receber', 'pagar', 'clientes'], true)) {
    $aba_atual = 'visao';
}

$inicio_mes = $mes_atual . '-01';
$fim_mes = date('Y-m-t', strtotime($inicio_mes));
$mes_label = fd_format_month_year($inicio_mes);
$filters = [
    'status' => trim((string) ($_GET['status'] ?? '')),
    'categoria' => trim((string) ($_GET['categoria'] ?? '')),
    'cliente_id' => trim((string) ($_GET['cliente_id'] ?? '')),
    'busca' => trim((string) ($_GET['busca'] ?? '')),
];
$inicioFiltro = $_GET['inicio'] ?? $inicio_mes;
$fimFiltro = $_GET['fim'] ?? $fim_mes;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicioFiltro)) {
    $inicioFiltro = $inicio_mes;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fimFiltro)) {
    $fimFiltro = $fim_mes;
}

$model = new FinanceiroModel($pdo);
$resumoReceber = $model->resumoContasReceber($inicioFiltro, $fimFiltro, $filters);
$resumoPagar = $model->resumoContasPagar($inicioFiltro, $fimFiltro, $filters);
$contasReceber = $model->listarContasReceber($inicioFiltro, $fimFiltro, $filters);
$contasPagar = $model->listarContasPagar($inicioFiltro, $fimFiltro, $filters);
$logTransacoes = $model->listarLogTransacoes(10, $inicioFiltro, $fimFiltro, $filters);
$fechamentoClientes = $model->listarFechamentoClientes($inicioFiltro, $fimFiltro, $filters);
$listaClientes = $model->listarClientesParaLancamento();
$categoriasFinanceiras = $model->listarCategorias();

$receitasPeriodo = (float) ($resumoReceber['recebido'] ?? 0);
$despesasPeriodo = (float) ($resumoPagar['pago'] ?? 0);
$lucroPeriodo = $receitasPeriodo - $despesasPeriodo;
$totalReceber = (float) ($resumoReceber['pendente'] ?? 0);
$totalPagar = (float) ($resumoPagar['pendente'] ?? 0);

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

function fd_fin_money($value): string
{
    return 'R$' . money((float) $value);
}

function fd_fin_status_label(string $status): string
{
    return match ($status) {
        'pago' => 'Pago',
        'parcial' => 'Parcial',
        'vencido' => 'Vencido',
        default => 'Pendente',
    };
}

function fd_fin_status_class(string $status): string
{
    return match ($status) {
        'pago' => 'is-paid',
        'parcial' => 'is-partial',
        'vencido' => 'is-overdue',
        default => 'is-pending',
    };
}

function fd_fin_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = substr($parts[0] ?? 'C', 0, 1);
    $last = count($parts) > 1 ? substr((string) end($parts), 0, 1) : '';
    return strtoupper($first . $last);
}

function fd_fin_photo_url(?string $photo, string $base): ?string
{
    $photo = trim((string) $photo);
    if ($photo === '') {
        return null;
    }

    if (filter_var($photo, FILTER_VALIDATE_URL)) {
        return $photo;
    }

    return ($base ?? '') . '/' . ltrim($photo, '/');
}
?>

<div class="fd-financeiro fd-financeiro-v2 fd-financeiro-redesign" x-data="{ tab: '<?= e($aba_atual) ?>' }" data-financeiro-tab="<?= e($aba_atual) ?>">
    <section class="fd-page-header fd-fin-hero">
        <div>
            <p class="fd-page-eyebrow">Financeiro operacional</p>
            <p class="fd-page-subtitle">Controle contas a receber, contas a pagar, fechamento por cliente e movimentacoes do periodo.</p>
        </div>

        <div class="fd-page-actions fd-financeiro-top-actions">
            <button type="button" class="fd-btn-primary fd-fin-main-action" data-bs-toggle="modal" data-bs-target="#modalContaReceber">
                <i class="ri-flashlight-line"></i>
                <span>Venda rapida</span>
            </button>
            <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalContaPagar">
                <i class="ri-add-circle-line"></i>
                <span>Lancar despesa</span>
            </button>
            <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalFinanceiroCategorias">
                <i class="ri-price-tag-3-line"></i>
                <span>Categorias</span>
            </button>
        </div>
    </section>

    <section class="fd-fin-nav-card">
        <div class="fd-financeiro-tabs">
            <button type="button" class="fd-fin-tab" :class="{ 'is-active': tab === 'visao' }" @click="tab = 'visao'">
                <i class="ri-eye-line"></i><span>Visao Geral</span>
            </button>
            <button type="button" class="fd-fin-tab" :class="{ 'is-active': tab === 'receber' }" @click="tab = 'receber'">
                <i class="ri-arrow-right-down-long-line"></i></i><span>Receber</span>
            </button>
            <button type="button" class="fd-fin-tab" :class="{ 'is-active': tab === 'pagar' }" @click="tab = 'pagar'">
               <i class="ri-arrow-right-up-long-line"></i><span>A Pagar</span>
            </button>
            <button type="button" class="fd-fin-tab" :class="{ 'is-active': tab === 'clientes' }" @click="tab = 'clientes'">
                <i class="ri-user-shared-line"></i><span>Clientes</span>
            </button>
        </div>

        <form method="get" class="fd-fin-period-form">
            <input type="hidden" name="aba" :value="tab">
            <div class="fd-month-picker" x-data="flowdeskMonthPicker('<?= htmlspecialchars($mes_atual, ENT_QUOTES) ?>')" @keydown.escape.window="close()">
                <input type="hidden" name="mes" x-model="selectedValue">
                <button type="button" class="fd-month-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                    <span class="fd-month-picker-trigger-icon"><i class="ri-calendar-event-line"></i></span>
                    <span class="fd-month-picker-trigger-copy">
                        <span class="fd-month-picker-trigger-label">Periodo</span>
                        <strong x-text="triggerLabel"></strong>
                    </span>
                    <i class="ri-arrow-down-s-line fd-month-picker-trigger-arrow"></i>
                </button>
                <div class="fd-month-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.right @click.outside="close()">
                    <div class="fd-month-picker-head">
                        <button type="button" class="fd-month-picker-nav" @click="prevYear()"><i class="ri-arrow-left-s-line"></i></button>
                        <div class="fd-month-picker-head-copy">
                            <span class="fd-month-picker-head-label">Selecione o mes</span>
                            <strong x-text="displayYear"></strong>
                        </div>
                        <button type="button" class="fd-month-picker-nav" @click="nextYear()"><i class="ri-arrow-right-s-line"></i></button>
                    </div>
                    <div class="fd-month-picker-grid">
                        <?php foreach ($mesesPicker as $numeroMes => $labelMes): ?>
                            <button type="button" class="fd-month-picker-month" :class="monthButtonClass('<?= $numeroMes ?>')" @click="selectMonth('<?= $numeroMes ?>')"><?= $labelMes ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="fd-month-picker-footer">
                        <button type="button" class="fd-month-picker-link" @click="resetToCurrent()">Este mes</button>
                        <button type="button" class="fd-month-picker-apply" @click="submit()">Aplicar</button>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section x-show="tab === 'visao'" x-cloak class="fd-fin-panel">
        <div class="fd-fin-overview-grid">
            <article class="fd-fin-stat-card">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-arrow-right-down-long-line"></i></span>Contas a Receber</div>
                <div class="fd-fin-lines">
                    <div><span>Recebido</span><strong class="fd-value-green"><?= fd_fin_money($resumoReceber['recebido'] ?? 0) ?></strong></div>
                    <div><span>Pendente</span><strong class="fd-value-blue"><?= fd_fin_money($resumoReceber['pendente'] ?? 0) ?></strong></div>
                    <div><span>Vencido</span><strong class="fd-value-red"><?= fd_fin_money($resumoReceber['vencido'] ?? 0) ?></strong></div>
                    <div class="is-total"><span>Total</span><strong><?= fd_fin_money($resumoReceber['total'] ?? 0) ?></strong></div>
                </div>
            </article>

            <article class="fd-fin-stat-card">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-arrow-right-up-long-line"></i></span>Contas a Pagar</div>
                <div class="fd-fin-lines">
                    <div><span>Pago</span><strong class="fd-value-green"><?= fd_fin_money($resumoPagar['pago'] ?? 0) ?></strong></div>
                    <div><span>Pendente</span><strong class="fd-value-blue"><?= fd_fin_money($resumoPagar['pendente'] ?? 0) ?></strong></div>
                    <div><span>Vencido</span><strong class="fd-value-red"><?= fd_fin_money($resumoPagar['vencido'] ?? 0) ?></strong></div>
                    <div class="is-total"><span>Total</span><strong><?= fd_fin_money($resumoPagar['total'] ?? 0) ?></strong></div>
                </div>
            </article>

            <article class="fd-fin-stat-card">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-line-chart-line"></i></span>Lucro do Periodo</div>
                <div class="fd-fin-lines">
                    <div><span>Receitas</span><strong class="fd-value-green"><?= fd_fin_money($receitasPeriodo) ?></strong></div>
                    <div><span>Despesas</span><strong class="fd-value-red"><?= fd_fin_money($despesasPeriodo) ?></strong></div>
                    <div class="is-total"><span>Lucro Liquido</span><strong class="<?= $lucroPeriodo >= 0 ? '' : 'fd-value-red' ?>"><?= fd_fin_money($lucroPeriodo) ?></strong></div>
                </div>
            </article>
        </div>

        <article class="fd-fin-section-card fd-fin-transactions">
            <div class="fd-fin-section-head">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-history-line"></i></span>Transacoes</div>
            </div>

            <form method="get" class="fd-fin-transaction-filters">
                <input type="hidden" name="aba" value="visao">
                <select class="fd-filter-control" name="status" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach (['pendente' => 'Pendente', 'parcial' => 'Parcial', 'pago' => 'Pago', 'vencido' => 'Vencido'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="fd-filter-control" name="cliente_id" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($listaClientes as $cliente): ?>
                        <option value="<?= (int) $cliente['id'] ?>" <?= (string) $filters['cliente_id'] === (string) $cliente['id'] ? 'selected' : '' ?>><?= e($cliente['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="fd-filter-control" name="categoria" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($categoriasFinanceiras as $categoria): ?>
                        <option value="<?= e($categoria['nome']) ?>" <?= $filters['categoria'] === $categoria['nome'] ? 'selected' : '' ?>><?= e($categoria['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="fd-filter-control" name="inicio" type="date" value="<?= e($inicioFiltro) ?>">
                <span>-</span>
                <input class="fd-filter-control" name="fim" type="date" value="<?= e($fimFiltro) ?>" onchange="this.form.submit()">
            </form>

            <div class="fd-fin-transaction-list">
                <?php if (empty($logTransacoes)): ?>
                    <div class="fd-fin-empty-row">Nenhuma transacao registrada ainda.</div>
                <?php else: ?>
                    <?php foreach ($logTransacoes as $index => $log): ?>
                        <details class="fd-fin-transaction-row" <?= $index === 1 ? 'open' : '' ?>>
                            <summary>
                                <span class="fd-fin-status-dot <?= $index % 2 === 0 ? 'is-green' : 'is-red' ?>"></span>
                                <span>
                                    <strong><?= e((string) $log['descricao']) ?></strong>
                                    <small>Vencimento <?= date_br((string) $log['data_lancamento']) ?></small>
                                </span>
                                <i class="ri-arrow-down-s-line"></i>
                            </summary>
                            <div class="fd-fin-transaction-body">
                                <div><span>Fonte:</span> <?= e((string) $log['fonte']) ?></div>
                                <div><span>Categoria:</span> <?= e((string) ($log['categoria'] ?: 'Geral')) ?></div>
                                <div><span>Data do pagamento:</span> <?= date_br((string) $log['data_lancamento']) ?></div>
                                <div><span>Data do vencimento:</span> <?= date_br((string) $log['data_lancamento']) ?></div>
                                <div><span>Status:</span> <b>Pendente</b></div>
                                <span class="fd-fin-outline-pill">Contas a Pagar</span>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section x-show="tab === 'receber'" x-cloak class="fd-fin-panel">
        <form method="get" class="fd-fin-filter-row">
            <input type="hidden" name="aba" value="receber">
            <strong>Filtro Rapido:</strong>
            <input class="fd-filter-control" name="inicio" type="date" value="<?= e($inicioFiltro) ?>">
            <span>-</span>
            <input class="fd-filter-control" name="fim" type="date" value="<?= e($fimFiltro) ?>" onchange="this.form.submit()">
            <select class="fd-filter-control" name="status" onchange="this.form.submit()">
                <option value="">Status</option>
                <?php foreach (['pendente' => 'Pendente', 'parcial' => 'Parcial', 'pago' => 'Pago', 'vencido' => 'Vencido'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="fd-filter-control" name="categoria" onchange="this.form.submit()">
                <option value="">Categoria</option>
                <?php foreach ($categoriasFinanceiras as $categoria): ?>
                    <option value="<?= e($categoria['nome']) ?>" <?= $filters['categoria'] === $categoria['nome'] ? 'selected' : '' ?>><?= e($categoria['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="fd-fin-total-chip is-green">Total: <?= fd_fin_money($resumoReceber['recebido'] ?? 0) ?></span>
            <span class="fd-fin-total-chip is-yellow">Pendente mes: <?= fd_fin_money($totalReceber) ?></span>
            <button type="button" class="fd-btn-primary fd-fin-new-button" data-bs-toggle="modal" data-bs-target="#modalContaReceber">
                <i class="ri-add-line"></i> Nova Conta a Receber
            </button>
        </form>

        <article class="fd-fin-section-card">
            <div class="fd-fin-list-title-row">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-arrow-right-down-long-line"></i></span>Contas a Receber</div>
            </div>
            <div class="fd-fin-search-row">
                <form method="get" class="fd-fin-search" style="margin:0;">
                    <input type="hidden" name="aba" value="receber">
                    <i class="ri-search-line"></i><input name="busca" type="search" value="<?= e($filters['busca']) ?>" placeholder="Buscar cliente, titulo...">
                </form>
            </div>

            <div class="fd-fin-account-list">
                <?php if (empty($contasReceber)): ?>
                    <div class="fd-fin-empty-row">Nenhuma conta a receber neste periodo.</div>
                <?php else: ?>
                    <?php foreach ($contasReceber as $conta): ?>
                        <?php
                        $status = (string) ($conta['status_pagamento_calc'] ?? 'pendente');
                        $valorTotal = (float) ($conta['valor_a_receber'] ?? 0);
                        $valorPago = (float) ($conta['valor_recebido'] ?? 0);
                        $saldo = max(0, $valorTotal - $valorPago);
                        ?>
                        <article class="fd-fin-account-card">
                            <span class="fd-fin-status-badge <?= fd_fin_status_class($status) ?>"><?= fd_fin_status_label($status) ?></span>
                            <h3><?= e($conta['descricao']) ?></h3>
                            <p>Pagador: <?= e($conta['cliente_nome'] ?: 'Cliente') ?></p>
                            <p>Vencimento: <?= date_br($conta['data_lancamento']) ?></p>
                            <div class="fd-fin-account-values">
                                <span><small>Valor Total</small><strong><?= fd_fin_money($valorTotal) ?></strong></span>
                                <span><small>Valor Pago</small><strong class="fd-value-green"><?= fd_fin_money($valorPago) ?></strong></span>
                                <span><small>Saldo Restante</small><strong class="fd-value-yellow"><?= fd_fin_money($saldo) ?></strong></span>
                            </div>
                            <div class="fd-fin-account-actions">
                                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrarPagamento" data-kind="entrada" data-id="<?= (int) $conta['id'] ?>" data-title="<?= e($conta['descricao']) ?>" data-saldo="<?= e((string) $saldo) ?>"><i class="ri-money-dollar-circle-line"></i> <?= $status === 'pago' ? 'Recibo' : 'Registrar Pagamento' ?></button>
                                <?php if ($status !== 'pago'): ?><a class="fd-btn-secondary" target="_blank" href="<?= e(fd_financeiro_documento_url($base ?? '', ['tipo' => 'cobranca', 'id' => (int) $conta['id']])) ?>"><i class="ri-bank-card-line"></i> Cobrar</a><?php endif; ?>
                                <button type="button" class="fd-icon-action js-fin-edit-entrada" data-bs-toggle="modal" data-bs-target="#modalContaReceber" data-record='<?= e(json_encode($conta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'><i class="ri-pencil-line"></i></button>
                                <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="fd-inline-form js-confirm-delete" data-confirm-msg="Deseja excluir esta conta a receber?">
                                    <input type="hidden" name="acao" value="excluir_entrada">
                                    <input type="hidden" name="aba" value="receber">
                                    <input type="hidden" name="id" value="<?= (int) $conta['id'] ?>">
                                    <button type="submit" class="fd-icon-action is-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section x-show="tab === 'pagar'" x-cloak class="fd-fin-panel">
        <form method="get" class="fd-fin-filter-row">
            <input type="hidden" name="aba" value="pagar">
            <strong>Filtro Rapido:</strong>
            <input class="fd-filter-control" name="inicio" type="date" value="<?= e($inicioFiltro) ?>">
            <span>-</span>
            <input class="fd-filter-control" name="fim" type="date" value="<?= e($fimFiltro) ?>" onchange="this.form.submit()">
            <select class="fd-filter-control" name="status" onchange="this.form.submit()">
                <option value="">Status</option>
                <?php foreach (['pendente' => 'Pendente', 'parcial' => 'Parcial', 'pago' => 'Pago', 'vencido' => 'Vencido'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="fd-filter-control" name="categoria" onchange="this.form.submit()">
                <option value="">Categoria</option>
                <?php foreach ($categoriasFinanceiras as $categoria): ?>
                    <option value="<?= e($categoria['nome']) ?>" <?= $filters['categoria'] === $categoria['nome'] ? 'selected' : '' ?>><?= e($categoria['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="fd-fin-total-chip is-green">Total: <?= fd_fin_money($resumoPagar['pago'] ?? 0) ?></span>
            <span class="fd-fin-total-chip is-yellow">Pendente: <?= fd_fin_money($totalPagar) ?></span>
            <button type="button" class="fd-btn-primary fd-fin-new-button" data-bs-toggle="modal" data-bs-target="#modalContaPagar">
                <i class="ri-add-line"></i> Nova Conta a Pagar
            </button>
        </form>

        <article class="fd-fin-section-card">
            <div class="fd-fin-list-title-row">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-arrow-right-up-long-line"></i></span>Contas a Pagar</div>
            </div>
            <div class="fd-fin-search-row">
                <form method="get" class="fd-fin-search" style="margin:0;">
                    <input type="hidden" name="aba" value="pagar">
                    <i class="ri-search-line"></i><input name="busca" type="search" value="<?= e($filters['busca']) ?>" placeholder="Buscar favorecido, titulo...">
                </form>
            </div>

            <div class="fd-fin-account-list">
                <?php if (empty($contasPagar)): ?>
                    <div class="fd-fin-empty-row">Nenhuma conta a pagar neste periodo.</div>
                <?php else: ?>
                    <?php foreach ($contasPagar as $conta): ?>
                        <?php
                        $status = (string) ($conta['status_pagamento_calc'] ?? 'pago');
                        $valorTotal = (float) ($conta['valor'] ?? 0);
                        $valorPago = (float) ($conta['valor_pago_calc'] ?? ($status === 'pago' ? $valorTotal : 0));
                        $saldo = max(0, $valorTotal - $valorPago);
                        ?>
                        <article class="fd-fin-account-card">
                            <span class="fd-fin-status-badge <?= fd_fin_status_class($status) ?>"><?= fd_fin_status_label($status) ?></span>
                            <h3><?= e($conta['descricao']) ?></h3>
                            <p>Vencimento: <?= date_br($conta['data_lancamento']) ?></p>
                            <div class="fd-fin-account-values">
                                <span><small>Valor Total</small><strong><?= fd_fin_money($valorTotal) ?></strong></span>
                                <span><small>Valor Pago</small><strong class="fd-value-green"><?= fd_fin_money($valorPago) ?></strong></span>
                                <span><small>Saldo Restante</small><strong class="fd-value-yellow"><?= fd_fin_money($saldo) ?></strong></span>
                            </div>
                            <div class="fd-fin-account-actions">
                                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistrarPagamento" data-kind="saida" data-id="<?= (int) $conta['id'] ?>" data-title="<?= e($conta['descricao']) ?>" data-saldo="<?= e((string) $saldo) ?>"><i class="ri-money-dollar-circle-line"></i> Registrar Pagamento</button>
                                <button type="button" class="fd-icon-action js-fin-edit-saida" data-bs-toggle="modal" data-bs-target="#modalContaPagar" data-record='<?= e(json_encode($conta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'><i class="ri-pencil-line"></i></button>
                                <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" class="fd-inline-form js-confirm-delete" data-confirm-msg="Deseja excluir esta conta a pagar?">
                                    <input type="hidden" name="acao" value="excluir_saida">
                                    <input type="hidden" name="aba" value="pagar">
                                    <input type="hidden" name="id" value="<?= (int) $conta['id'] ?>">
                                    <button type="submit" class="fd-icon-action is-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="fd-fin-section-card fd-fin-recurring">
            <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-loop-left-line"></i></span>Despesas Recorrentes</div>
            <div class="fd-fin-recurring-stats">
                <div><span class="fd-fin-icon"><i class="ri-loop-left-line"></i></span><strong>Despesas Ativas</strong><b><?= count($contasPagar) ?></b></div>
                <div><span class="fd-fin-icon"><i class="ri-loop-left-line"></i></span><strong>Custo Mensal Fixo</strong><b><?= fd_fin_money($resumoPagar['total'] ?? 0) ?></b></div>
                <div><span class="fd-fin-icon"><i class="ri-loop-left-line"></i></span><strong>Total de Contas</strong><b><?= count($contasPagar) ?></b></div>
            </div>
            <div class="fd-table-wrap">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th>Descricao</th>
                            <th>Valor</th>
                            <th>Ciclo</th>
                            <th>Proximo Venc.</th>
                            <th>Status</th>
                            <th class="fd-text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contasPagar)): ?>
                            <tr><td colspan="6" class="fd-empty-state">Nenhuma despesa recorrente cadastrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($contasPagar as $conta): ?>
                                <tr>
                                    <td><?= e($conta['descricao']) ?></td>
                                    <td><strong class="fd-value-red"><?= fd_fin_money($conta['valor']) ?></strong></td>
                                    <td>Mensal</td>
                                    <td><?= date_br($conta['data_lancamento']) ?></td>
                                    <td><span class="fd-fin-status-badge is-paid">Ativa</span></td>
                                    <td class="fd-text-right">
                                        <button type="button" class="fd-btn-table">Editar Assinatura</button>
                                        <button type="button" class="fd-btn-table fd-btn-table-danger">Cancelar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section x-show="tab === 'clientes'" x-cloak class="fd-fin-panel">
        <form method="get" class="fd-fin-filter-row">
            <input type="hidden" name="aba" value="clientes">
            <strong>Filtro Rapido:</strong>
            <input class="fd-filter-control" name="inicio" type="date" value="<?= e($inicioFiltro) ?>">
            <span>-</span>
            <input class="fd-filter-control" name="fim" type="date" value="<?= e($fimFiltro) ?>" onchange="this.form.submit()">
        </form>

        <article class="fd-fin-section-card">
            <div class="fd-fin-clients-head">
                <div class="fd-fin-card-title"><span class="fd-fin-icon"><i class="ri-file-list-3-line"></i></span>Fechamento por Cliente</div>
                <form method="get" class="fd-fin-search is-compact" style="margin:0;">
                    <input type="hidden" name="aba" value="clientes">
                    <i class="ri-search-line"></i><input name="busca" type="search" value="<?= e($filters['busca']) ?>" placeholder="Buscar cliente...">
                </form>
            </div>

            <div class="fd-fin-client-list">
                <?php if (empty($fechamentoClientes)): ?>
                    <div class="fd-fin-empty-row">Nenhum lancamento por cliente neste periodo.</div>
                <?php else: ?>
                    <?php foreach ($fechamentoClientes as $linha): ?>
                        <?php $clienteFoto = fd_fin_photo_url($linha['cliente_foto'] ?? null, $base ?? ''); ?>
                        <article class="fd-fin-client-row">
                            <div class="fd-fin-client-info">
                                <?php if ($clienteFoto): ?>
                                    <img src="<?= e($clienteFoto) ?>" alt="Foto de <?= e((string) $linha['cliente_nome']) ?>" class="fd-fin-avatar fd-fin-avatar-img">
                                <?php else: ?>
                                    <span class="fd-fin-avatar"><?= e(fd_fin_initials((string) $linha['cliente_nome'])) ?></span>
                                <?php endif; ?>
                                <div>
                                    <h3><?= e($linha['cliente_nome']) ?></h3>
                                    <p><?= (int) ($linha['contas_pendentes'] ?? 0) ?> contas pendentes</p>
                                </div>
                            </div>
                            <div class="fd-fin-client-total">
                                <strong><?= fd_fin_money($linha['total_reais']) ?></strong>
                                <span>Total pendente</span>
                            </div>
                            <a target="_blank" href="<?= e(fd_financeiro_documento_url($base ?? '', ['tipo' => 'fechamento_cliente', 'cliente_id' => (int) $linha['cliente_id'], 'inicio' => $inicioFiltro, 'fim' => $fimFiltro])) ?>" class="fd-btn-primary"><i class="ri-file-close-line"></i> Fechamento</a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </section>
</div>

<?php include __DIR__ . '/partials/modal_financeiro.php'; ?>
