<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/BillingModel.php';
require_once __DIR__ . '/../../../app/Models/OrcamentoModel.php';

$model = new OrcamentoModel($pdo);
$billingModel = new BillingModel($pdo);
$search = mb_substr(trim((string) ($_GET['busca'] ?? '')), 0, 120);
$statusFilter = trim((string) ($_GET['status'] ?? 'todos'));
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$listing = $model->listarPaginado(['search' => $search, 'status' => $statusFilter], $page, 5);
$propostas = $listing['items'];
$summary = $model->resumoGeral();
$selectedId = (int) ($_GET['selecionado'] ?? ($propostas[0]['id'] ?? 0));
$selected = $selectedId > 0 ? $model->buscarPorId($selectedId) : null;
$selectedItems = $selected ? $model->buscarItens((int) $selected['id']) : [];
$selectedActivities = $selected ? $model->listarAtividades((int) $selected['id'], 3) : [];
$selectedAdjustments = $selected ? $model->listarAjustes((int) $selected['id']) : [];
$canManage = fd_has_any_role(['owner', 'admin', 'financeiro']);
$gate = $billingModel->getResourceGate((int) (fd_current_workspace_id() ?? 0), 'orcamentos');

$statusLabels = [
    'Rascunho' => 'Rascunho',
    'Aguardando' => 'Aguardando aprovação',
    'Aguardando Aprovação' => 'Aguardando aprovação',
    'Aprovada' => 'Aprovada',
    'Recusada' => 'Recusada',
    'Vencida' => 'Vencida',
];
$statusClasses = [
    'Rascunho' => 'is-draft',
    'Aguardando' => 'is-waiting',
    'Aguardando Aprovação' => 'is-waiting',
    'Aprovada' => 'is-approved',
    'Recusada' => 'is-refused',
    'Vencida' => 'is-expired',
];
$serviceLabels = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuracao',
    'stream_overlay' => 'Stream Overlay',
    'criativos' => 'Criativos',
    'identidade_visual' => 'Identidade Visual',
    'ecommerce' => 'E-Commerce',
    'manutencao' => 'Manutencao',
];

$formatCompact = static function (float $value): string {
    if ($value < 1000) {
        return 'R$' . number_format($value, 2, ',', '.');
    }
    $compact = number_format($value / 1000, 1, ',', '');
    return 'R$' . rtrim(rtrim($compact, '0'), ',') . 'K';
};

$queryUrl = static function (array $changes) use ($search, $statusFilter): string {
    $params = array_merge(['busca' => $search, 'status' => $statusFilter], $changes);
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null && $value !== 'todos');
    return '?' . http_build_query($params);
};

$messages = [];
if (isset($_GET['criado'])) $messages[] = ['success', 'Proposta criada com sucesso.'];
if (isset($_GET['atualizado'])) $messages[] = ['success', 'Proposta atualizada com sucesso.'];
if (isset($_GET['excluido'])) $messages[] = ['success', 'Proposta excluida com sucesso.'];
if (isset($_GET['confirmado'])) $messages[] = ['success', 'Proposta confirmada e enviada ao financeiro.'];
if (isset($_GET['erro'])) $messages[] = ['danger', 'Nao foi possivel concluir a acao.'];
?>

<div class="fd-proposals">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-subtitle">Visualize, filtre e acompanhe todas as propostas comerciais criadas no workspace.</p>
        </div>
        <div class="fd-proposal-page-actions">
            <button type="button" class="fd-btn-secondary" data-focus-proposal-filters><i class="ri-filter-3-line"></i><span>Filtrar propostas</span></button>
            <?php if ($canManage): ?>
                <?php if ($gate['allowed']): ?>
                    <a href="<?= ($base ?? '') ?>/orcamentos/novo" class="fd-btn-primary"><i class="ri-add-line"></i><span>Nova proposta</span></a>
                <?php else: ?>
                    <a href="<?= ($base ?? '') ?>/configuracoes#pagamentos" class="fd-btn-primary"><i class="ri-vip-crown-2-line"></i><span>Gerenciar assinatura</span></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($messages as [$type, $message]): ?>
        <div class="alert alert-<?= e($type) ?>" role="alert"><?= e($message) ?></div>
    <?php endforeach; ?>

    <section class="fd-proposal-kpis">
        <article class="fd-proposal-kpi">
            <span><i class="ri-file-list-3-line"></i></span>
            <div><small>Total de propostas</small><strong><?= (int) $summary['total'] ?></strong></div>
        </article>
        <article class="fd-proposal-kpi">
            <span><i class="ri-time-line"></i></span>
            <div><small>Aguardando aprovação</small><strong><?= (int) $summary['aguardando'] ?></strong></div>
        </article>
        <article class="fd-proposal-kpi">
            <span><i class="ri-checkbox-circle-line"></i></span>
            <div><small>Aprovadas</small><strong><?= (int) $summary['aprovadas'] ?></strong></div>
        </article>
        <article class="fd-proposal-kpi">
            <span><i class="ri-money-dollar-circle-line"></i></span>
            <div><small>Valor total em propostas</small><strong><?= e($formatCompact((float) $summary['valor_total'])) ?></strong></div>
        </article>
    </section>

    <section class="fd-proposal-workspace">
        <div class="fd-proposal-list-column">
            <form method="get" class="fd-proposal-toolbar">
                <label class="fd-proposal-search">
                    <i class="ri-search-line"></i>
                    <input type="search" name="busca" value="<?= e($search) ?>" placeholder="Buscar por cliente, nº da proposta ou serviço...">
                </label>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            </form>

            <nav class="fd-proposal-filters" aria-label="Filtrar propostas por status" data-proposal-filters>
                <?php
                $filterOptions = ['todos' => 'Todos', 'Rascunho' => 'Rascunho', 'Aguardando Aprovação' => 'Aguardando', 'Aprovada' => 'Aprovadas', 'Recusada' => 'Recusadas', 'Vencida' => 'Vencidas'];
                foreach ($filterOptions as $value => $label):
                ?>
                    <a href="<?= e($queryUrl(['status' => $value, 'pagina' => 1])) ?>"
                       class="<?= $statusFilter === $value || ($value === 'todos' && !isset($filterOptions[$statusFilter])) ? 'is-active' : '' ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="fd-proposal-list">
                <?php if (!$propostas): ?>
                    <div class="fd-proposal-empty">
                        <i class="ri-file-search-line"></i>
                        <strong>Nenhuma proposta encontrada</strong>
                        <span>Ajuste os filtros ou crie uma nova proposta.</span>
                    </div>
                <?php endif; ?>

                <?php foreach ($propostas as $proposal): ?>
                    <?php
                    $proposalStatus = (string) ($proposal['status'] ?? 'Aguardando Aprovação');
                    $isSelected = (int) $proposal['id'] === $selectedId;
                    ?>
                    <a href="<?= e($queryUrl(['pagina' => $listing['page'], 'selecionado' => (int) $proposal['id']])) ?>"
                       class="fd-proposal-row<?= $isSelected ? ' is-selected' : '' ?>">
                        <span class="fd-proposal-selector" aria-hidden="true"></span>
                        <div class="fd-proposal-row-main">
                            <strong><?= e($proposal['codigo']) ?></strong>
                            <span><?= e($proposal['cliente_nome']) ?></span>
                            <small><i class="ri-computer-line"></i><?= e($serviceLabels[$proposal['servico_principal']] ?? $proposal['servico_principal']) ?></small>
                        </div>
                        <div class="fd-proposal-row-dates">
                            <small>Emissao <b><?= date('d/m/Y', strtotime((string) ($proposal['data_emissao'] ?: $proposal['criado_em']))) ?></b></small>
                            <small>Válida até <b><?= date('d/m/Y', strtotime((string) $proposal['vencimento'])) ?></b></small>
                        </div>
                        <div class="fd-proposal-row-value">
                            <strong>R$<?= number_format((float) $proposal['valor_total'], 2, ',', '.') ?></strong>
                            <span class="fd-proposal-status <?= e($statusClasses[$proposalStatus] ?? 'is-waiting') ?>">
                                <?= e($statusLabels[$proposalStatus] ?? $proposalStatus) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <footer class="fd-proposal-list-footer">
                <span>Exibindo <?= count($propostas) ?> de <?= (int) $listing['total'] ?> propostas</span>
                <?php if ($listing['pages'] > 1): ?>
                <nav class="fd-proposal-pagination" aria-label="Paginação das propostas">
                    <a href="<?= e($queryUrl(['pagina' => max(1, $listing['page'] - 1)])) ?>"
                       class="<?= $listing['page'] <= 1 ? 'is-disabled' : '' ?>"><i class="ri-arrow-left-s-line"></i></a>
                    <?php for ($p = 1; $p <= $listing['pages']; $p++): ?>
                        <a href="<?= e($queryUrl(['pagina' => $p])) ?>" class="<?= $p === $listing['page'] ? 'is-active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a href="<?= e($queryUrl(['pagina' => min($listing['pages'], $listing['page'] + 1)])) ?>"
                       class="<?= $listing['page'] >= $listing['pages'] ? 'is-disabled' : '' ?>"><i class="ri-arrow-right-s-line"></i></a>
                </nav>
                <?php endif; ?>
            </footer>
        </div>

        <aside class="fd-proposal-summary">
            <?php if (!$selected): ?>
                <div class="fd-proposal-empty">
                    <i class="ri-cursor-line"></i>
                    <strong>Selecione uma proposta</strong>
                    <span>Os detalhes serao exibidos aqui.</span>
                </div>
            <?php else: ?>
                <?php
                $photo = trim((string) ($selected['cliente_foto'] ?? ''));
                if ($photo !== '' && !filter_var($photo, FILTER_VALIDATE_URL)) {
                    $photo = ($base ?? '') . '/' . ltrim($photo, '/');
                }
                $paymentLabel = $selected['forma_pagamento'] === '50/50'
                    ? '50% de Entrada + 50% na Entrega'
                    : (string) $selected['forma_pagamento'];
                ?>
                <div class="fd-proposal-summary-client">
                    <?php if ($photo): ?>
                        <img src="<?= e($photo) ?>" alt="Foto de <?= e($selected['cliente_nome']) ?>">
                    <?php else: ?>
                        <span><?= e(mb_strtoupper(mb_substr($selected['cliente_nome'], 0, 2))) ?></span>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($selected['cliente_nome']) ?></strong>
                        <small><?= e($selected['codigo']) ?></small>
                        <span class="fd-proposal-client-contact"><i class="ri-mail-line"></i><?= e($selected['cliente_email'] ?: 'E-mail não cadastrado') ?> <i class="ri-phone-line"></i><?= e($selected['cliente_whatsapp'] ?: 'Telefone não cadastrado') ?></span>
                    </div>
                    <span class="fd-proposal-status <?= e($statusClasses[$selected['status']] ?? 'is-waiting') ?>"><?= e($selected['status']) ?></span>
                </div>

                <div class="fd-proposal-summary-metrics">
                    <div><small>Valor da proposta</small><strong>R$<?= number_format((float) $selected['valor_total'], 2, ',', '.') ?></strong></div>
                    <div><small>Forma de pagamento</small><strong><?= e($paymentLabel) ?></strong></div>
                    <div><small>Prazo estimado</small><strong><?= (int) ($selected['prazo_estimado_dias'] ?? 7) ?> dias úteis</strong></div>
                </div>

                <div class="fd-proposal-summary-items">
                    <h3>Itens da proposta</h3>
                    <?php $gross = 0.0; ?>
                    <?php foreach ($selectedItems as $itemIndex => $item): ?>
                        <?php
                        $itemSubtotal = (float) ($item['subtotal'] ?: $item['valor']);
                        $gross += (float) $item['quantidade'] * (float) $item['valor_unitario'];
                        ?>
                        <div><span><b class="fd-item-number"><?= $itemIndex + 1 ?></b><?= e($item['descricao']) ?></span><strong>R$<?= number_format($itemSubtotal, 2, ',', '.') ?></strong></div>
                    <?php endforeach; ?>
                    <div class="is-total"><span>Subtotal</span><strong>R$<?= number_format($gross, 2, ',', '.') ?></strong></div>
                    <div><span>Desconto</span><strong>- R$<?= number_format((float) $selected['desconto_total'], 2, ',', '.') ?></strong></div>
                    <div class="is-grand-total"><span>Total</span><strong>R$<?= number_format((float) $selected['valor_total'], 2, ',', '.') ?></strong></div>
                </div>

                <div class="fd-proposal-quick-actions">
                    <h3>Acoes rapidas</h3>
                    <div>
                        <a href="<?= ($base ?? '') ?>/proposta/<?= e($selected['public_code']) ?>" target="_blank" class="fd-btn-primary">
                            <i class="ri-eye-line"></i><span>Visualizar proposta</span>
                        </a>
                        <?php if ($canManage): ?>
                            <a href="<?= ($base ?? '') ?>/orcamentos/editar/<?= (int) $selected['id'] ?>" class="fd-btn-secondary"><i class="ri-edit-line"></i><span>Editar</span></a>
                        <?php endif; ?>
                        <a href="<?= ($base ?? '') ?>/orcamento?id=<?= (int) $selected['id'] ?>" target="_blank" class="fd-btn-secondary"><i class="ri-file-pdf-2-line"></i><span>Gerar PDF</span></a>
                        <?php if ($canManage): ?>
                            <form method="post" action="<?= ($base ?? '') ?>/orcamentos/duplicar">
                                <input type="hidden" name="orcamento_id" value="<?= (int) $selected['id'] ?>">
                                <button type="submit" class="fd-btn-secondary"><i class="ri-file-copy-line"></i><span>Duplicar</span></button>
                            </form>
                        <?php endif; ?>
                        <?php if ($selectedAdjustments): ?>
                            <button type="button" class="fd-btn-secondary fd-proposal-adjustments-trigger" data-open-proposal-adjustments>
                                <i class="ri-chat-check-line"></i>
                                <span>Alterações Solicitadas</span>
                                <b><?= count($selectedAdjustments) ?></b>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="fd-proposal-activity">
                    <h3>Atividades recentes</h3>
                    <?php if (!$selectedActivities): ?><p>Nenhuma atividade registrada.</p><?php endif; ?>
                    <?php foreach ($selectedActivities as $activity): ?>
                        <div>
                            <span><i class="ri-history-line"></i></span>
                            <div>
                                <strong><?= e(match ($activity['acao']) {
                                    'orcamento.create' => 'Proposta criada',
                                    'orcamento.update' => 'Proposta atualizada',
                                    'orcamento.duplicate' => 'Proposta duplicada',
                                    'orcamento.public_confirm' => 'Proposta confirmada pelo cliente',
                                    'orcamento.adjustment_requested' => 'Alterações solicitadas',
                                    default => 'Proposta modificada',
                                }) ?></strong>
                                <small><?= date('d/m/Y', strtotime($activity['criado_em'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
    </section>
</div>
<?php if ($selected && $selectedAdjustments): ?>
    <div class="fd-proposal-adjustments-backdrop" data-proposal-adjustments-modal aria-hidden="true">
        <section class="fd-proposal-adjustments-modal" role="dialog" aria-modal="true" aria-labelledby="proposal-adjustments-title">
            <header>
                <div>
                    <span class="fd-modal-eyebrow">Retorno do cliente</span>
                    <h2 id="proposal-adjustments-title">Alterações Solicitadas</h2>
                    <p><?= e($selected['codigo']) ?> · <?= e($selected['cliente_nome']) ?></p>
                </div>
                <button type="button" data-close-proposal-adjustments aria-label="Fechar"><i class="ri-close-line"></i></button>
            </header>
            <div class="fd-proposal-adjustments-list">
                <?php foreach ($selectedAdjustments as $adjustment): ?>
                    <article>
                        <div>
                            <i class="ri-chat-quote-line"></i>
                            <time datetime="<?= e((string) $adjustment['criado_em']) ?>"><?= date('d/m/Y \à\s H:i', strtotime((string) $adjustment['criado_em'])) ?></time>
                        </div>
                        <p><?= nl2br(e((string) $adjustment['mensagem'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <footer>
                <button type="button" class="fd-btn-primary" data-close-proposal-adjustments>Entendido</button>
            </footer>
        </section>
    </div>
<?php endif; ?>
<script>
document.querySelector('[data-focus-proposal-filters]')?.addEventListener('click', () => {
    document.querySelector('[data-proposal-filters]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
});

const proposalAdjustmentsModal = document.querySelector('[data-proposal-adjustments-modal]');
const toggleProposalAdjustments = (isOpen) => {
    if (!proposalAdjustmentsModal) return;
    proposalAdjustmentsModal.classList.toggle('is-open', isOpen);
    proposalAdjustmentsModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    document.body.style.overflow = isOpen ? 'hidden' : '';
};

document.querySelector('[data-open-proposal-adjustments]')?.addEventListener('click', () => toggleProposalAdjustments(true));
document.querySelectorAll('[data-close-proposal-adjustments]').forEach((button) => {
    button.addEventListener('click', () => toggleProposalAdjustments(false));
});
proposalAdjustmentsModal?.addEventListener('click', (event) => {
    if (event.target === proposalAdjustmentsModal) toggleProposalAdjustments(false);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') toggleProposalAdjustments(false);
});
</script>
