<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/OrcamentoModel.php';
require_once __DIR__ . '/../../../app/Models/ClienteModel.php';

$orcModel = new OrcamentoModel($pdo);
$clienteModel = new ClienteModel($pdo);

$status_orcamento = $_GET['status_orcamento'] ?? ['todos'];
if (!is_array($status_orcamento)) {
    $status_orcamento = [$status_orcamento];
}

$orcamentos = $orcModel->listarComClientes($status_orcamento);
$clientesTodos = $clienteModel->listarFiltrados(['todos'], '');

$servicoLabels = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuracao',
    'stream_overlay' => 'Stream Overlay',
    'criativos' => 'Criativos',
    'identidade_visual' => 'Identidade Visual',
];

$servicoIcons = [
    'landing_page' => 'ri-pages-fill',
    'configuracao' => 'ri-tools-line',
    'stream_overlay' => 'ri-tv-2-line',
    'criativos' => 'ri-brush-line',
    'identidade_visual' => 'ri-palette-line',
];

$servicoColors = [
    'landing_page' => '#CEE7FF',
    'configuracao' => '#D3D3D3',
    'stream_overlay' => '#FFFBCE',
    'criativos' => '#FECEFF',
    'identidade_visual' => '#D5CEFF',
];

$statusClasses = [
    'Enviado' => 'fd-badge-warning',
    'Aprovado' => 'fd-badge-success',
    'Aceito' => 'fd-badge-success',
    'Sem Resposta' => 'fd-badge-neutral',
    'Recusado' => 'fd-badge-danger',
];

$totalOrcamentos = count($orcamentos);
$valorTotal = 0.0;
$enviados = 0;
$aprovados = 0;
$mensagens = [];
$orcamentoMensagemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$canManageOrcamentos = fd_has_any_role(['owner', 'admin', 'financeiro']);
$canDeleteOrcamentos = fd_has_any_role(['owner', 'admin']);

if (isset($_GET['criado'])) {
    $texto = 'Orcamento criado com sucesso.';
    if ($orcamentoMensagemId > 0) {
        $texto .= ' ID #' . $orcamentoMensagemId . '.';
    }
    $mensagens[] = ['type' => 'success', 'text' => $texto];
}
if (isset($_GET['atualizado'])) {
    $texto = 'Orcamento atualizado com sucesso.';
    if ($orcamentoMensagemId > 0) {
        $texto .= ' ID #' . $orcamentoMensagemId . '.';
    }
    $mensagens[] = ['type' => 'success', 'text' => $texto];
}
if (isset($_GET['excluido'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Orcamento excluido com sucesso.'];
}
if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao em orcamentos.'];
}

foreach ($orcamentos as $orcamento) {
    $valorTotal += (float) $orcamento['valor_total'];

    if (($orcamento['status'] ?? '') === 'Enviado') {
        $enviados++;
    }

    if (in_array($orcamento['status'] ?? '', ['Aprovado', 'Aceito'], true)) {
        $aprovados++;
    }
}

$filtroAtivo = !in_array('todos', $status_orcamento, true) && !empty($status_orcamento);
?>

<div class="fd-budgets">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Propostas comerciais</p>
            <p class="fd-page-subtitle">Gerencie propostas, acompanhe status de aprovacao e mantenha a operacao comercial organizada.</p>
        </div>

        <div class="fd-action-group">
            <button
                type="button"
                class="fd-btn-secondary"
                data-bs-toggle="modal"
                data-bs-target="#modalFiltroOrcamento"
            >
                <i class="ri-filter-3-line"></i>
                <span>Filtrar orcamentos</span>
            </button>

            <?php if ($canManageOrcamentos): ?>
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoOrcamento">
                    <i class="ri-add-line"></i>
                    <span>Criar orcamento</span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <div class="fd-page-alerts" id="orcamentosFeedback"></div>

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
                        <i class="ri-file-list-3-fill"></i>
                    </span>
                    Total de orcamentos
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalOrcamentos ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Propostas retornadas pelo filtro atual</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green">
                        <i class="ri-money-dollar-circle-fill"></i>
                    </span>
                    Valor total
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value">R$<?= number_format($valorTotal, 2, ',', '.') ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral sensitive-value">Soma de todos os valores listados</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red">
                        <i class="ri-check-double-line"></i>
                    </span>
                    Aprovados
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $aprovados ?></h3>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral"><?= $enviados ?> enviados aguardando retorno</span>
            </div>
        </article>
    </section>

    <?php if ($filtroAtivo): ?>
        <section class="fd-card">
            <p class="fd-inline-note">Filtro ativo: <?= htmlspecialchars(implode(', ', $status_orcamento)) ?></p>
        </section>
    <?php endif; ?>

    <?php if (empty($orcamentos)): ?>
        <section class="fd-card">
            <p class="fd-empty-copy">Nenhum orcamento encontrado.</p>
        </section>
    <?php else: ?>
        <section class="fd-budget-grid">
            <?php foreach ($orcamentos as $orc): ?>
                <?php
                $servico = $orc['servico_principal'] ?? '';
                $label = $servicoLabels[$servico] ?? ($servico ?: 'Servico nao informado');
                $icon = $servicoIcons[$servico] ?? 'ri-file-text-line';
                $bg = $servicoColors[$servico] ?? '#F3F4F6';
                $status = $orc['status'] ?? 'Sem status';
                $statusClass = $statusClasses[$status] ?? 'fd-badge-neutral';
                ?>
                <article class="fd-card fd-budget-card">
                    <div class="fd-budget-top">
                        <div>
                            <p class="fd-card-eyebrow">Orcamento #<?= htmlspecialchars($orc['codigo']) ?></p>
                            <h3 class="fd-budget-client"><?= htmlspecialchars($orc['cliente_nome']) ?></h3>
                        </div>

                        <span class="fd-badge <?= htmlspecialchars($statusClass) ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>

                    <div class="fd-budget-service">
                        <span class="fd-budget-service-chip" style="background-color: <?= htmlspecialchars($bg) ?>;">
                            <i class="<?= htmlspecialchars($icon) ?>"></i>
                            <span><?= htmlspecialchars($label) ?></span>
                        </span>
                    </div>

                    <div class="fd-budget-meta">
                        <div class="fd-budget-meta-item">
                            <span class="fd-card-eyebrow">Pagamento</span>
                            <strong><?= htmlspecialchars($orc['forma_pagamento']) ?></strong>
                        </div>

                        <div class="fd-budget-meta-item">
                            <span class="fd-card-eyebrow">Valor</span>
                            <strong class="sensitive-value">R$<?= number_format((float) $orc['valor_total'], 2, ',', '.') ?></strong>
                        </div>
                    </div>

                    <div class="fd-budget-actions">
                        <?php if ($canManageOrcamentos): ?>
                            <button
                                type="button"
                                class="fd-btn-table"
                                data-bs-toggle="modal"
                                data-bs-target="#modalNovoOrcamento"
                                data-id="<?= (int) $orc['id'] ?>"
                                title="Editar orcamento"
                            >
                                <i class="ri-file-edit-line"></i>
                            </button>
                        <?php endif; ?>

                        <?php if ($canDeleteOrcamentos): ?>
                            <form
                                method="post"
                                action="/orcamentos/excluir"
                                class="js-confirm-delete"
                                data-confirm-msg="Tem certeza que deseja excluir este orcamento?">
                                <input type="hidden" name="orcamento_id" value="<?= (int) $orc['id'] ?>">
                                <button type="submit" class="fd-btn-table fd-btn-table-danger" title="Excluir orcamento">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <a class="fd-btn-primary fd-btn-grow" href="/orcamento?id=<?= (int) $orc['id'] ?>" target="_blank">
                            <i class="ri-file-pdf-2-line"></i>
                            <span>Abrir PDF</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/modal_orcamento.php'; ?>

