<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/OrcamentoModel.php';

$model = new OrcamentoModel($pdo);
$id = (int) ($_GET['id'] ?? 0);
$editing = $id > 0;
$proposal = $editing ? $model->buscarPorId($id) : null;
if ($editing && !$proposal) {
    header('Location: ' . ($base ?? '') . '/orcamentos?erro=1');
    exit;
}

$clients = $model->listarClientes();
$items = $editing ? $model->buscarItens($id) : [];
if (!$items) {
    $items = [[
        'descricao' => '',
        'quantidade' => 1,
        'valor_unitario' => 0,
        'desconto_percentual' => 0,
        'subtotal' => 0,
    ]];
}

$issueDate = (string) ($proposal['data_emissao'] ?? date('Y-m-d'));
$validUntil = (string) ($proposal['vencimento'] ?? date('Y-m-d', strtotime('+7 days')));
$proposalCode = (string) ($proposal['codigo'] ?? $model->gerarNumeroProposta());
$service = (string) ($proposal['servico_principal'] ?? 'landing_page');
$payment = (string) ($proposal['forma_pagamento'] ?? 'A Vista');
$status = (string) ($proposal['status'] ?? 'Aguardando Aprovação');
$notes = (string) ($proposal['descricao_servico'] ?? 'Prazo de entrega pode variar conforme validacoes e feedback');
$installments = (int) ($proposal['parcelas'] ?? 3);
$deadline = (int) ($proposal['prazo_estimado_dias'] ?? 7);

$services = [
    'landing_page' => 'Landing Page',
    'configuracao' => 'Configuracao',
    'stream_overlay' => 'Stream Overlay',
    'criativos' => 'Criativos',
    'identidade_visual' => 'Identidade Visual',
    'ecommerce' => 'E-Commerce',
    'manutencao' => 'Manutencao',
];

$renderDatePicker = static function (string $name, string $label, string $value, string $dataAttribute): void {
    ?>
    <div class="fd-proposal-field">
        <span><?= e($label) ?></span>
        <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= e($value) ?>', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
            <input type="date" name="<?= e($name) ?>" value="<?= e($value) ?>" class="fd-date-picker-native"
                x-model="selectedValue" required <?= e($dataAttribute) ?>>
            <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label"><?= e($label) ?></span>
                    <strong x-text="triggerLabel"></strong>
                </span>
                <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
            </button>
            <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                <div class="fd-date-picker-head">
                    <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                    <div class="fd-date-picker-head-copy"><span class="fd-date-picker-head-label">Selecione a data</span><strong x-text="headerLabel"></strong></div>
                    <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                </div>
                <div class="fd-date-picker-weekdays">
                    <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                </div>
                <div class="fd-date-picker-days">
                    <template x-for="item in days()" :key="item.key">
                        <span>
                            <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                            <template x-if="!item.empty"><button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button></template>
                        </span>
                    </template>
                </div>
                <div class="fd-date-picker-footer">
                    <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                    <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                </div>
            </div>
        </div>
    </div>
    <?php
};
?>

<div class="fd-proposal-builder" data-proposal-builder>
    <section class="fd-page-header">
        <div>
            <p class="fd-page-subtitle">Monte uma proposta comercial completa, configure condições e organize os itens para envio ao cliente.</p>
        </div>
        <div class="fd-proposal-page-actions">
            <a href="<?= ($base ?? '') ?>/orcamentos" class="fd-btn-secondary"><i class="ri-close-line"></i><span>Cancelar</span></a>
            <button type="submit" form="proposalMainForm" class="fd-btn-secondary"><i class="ri-save-line"></i><span>Salvar proposta</span></button>
            <button type="submit" form="proposalMainForm" class="fd-btn-primary"><i class="ri-send-plane-line"></i><span><?= $editing ? 'Atualizar proposta' : 'Gerar proposta' ?></span></button>
        </div>
    </section>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger">Confira os dados informados e tente novamente.</div>
    <?php endif; ?>

    <form id="proposalMainForm" method="post" action="<?= ($base ?? '') ?>/orcamentos/<?= $editing ? 'atualizar' : 'criar' ?>" data-proposal-form>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <div class="fd-proposal-builder-grid">
            <section class="fd-proposal-builder-card">
                <div class="fd-proposal-form-section">
                    <h3><b class="fd-form-step">1</b>Dados do cliente</h3>
                    <div class="fd-proposal-fields-grid">
                    <label class="fd-proposal-field">
                        <span>Cliente</span>
                        <select name="cliente_id" required data-proposal-client>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= (int) $client['id'] ?>"
                                    data-email="<?= e($client['email'] ?? '') ?>"
                                    data-phone="<?= e($client['whatsapp'] ?? '') ?>"
                                    data-photo="<?= e(!empty($client['foto_perfil']) && !filter_var($client['foto_perfil'], FILTER_VALIDATE_URL) ? (($base ?? '') . '/' . ltrim((string) $client['foto_perfil'], '/')) : ($client['foto_perfil'] ?? '')) ?>"
                                    <?= (int) ($proposal['cliente_id'] ?? 0) === (int) $client['id'] ? 'selected' : '' ?>>
                                    <?= e($client['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="fd-proposal-field"><span>Contato</span><input type="text" readonly data-client-contact-name value="<?= e($proposal['cliente_nome'] ?? '') ?>"></label>
                    <label class="fd-proposal-field"><span>E-mail</span><input type="text" readonly data-client-email-field value="<?= e($proposal['cliente_email'] ?? '') ?>"></label>
                    <label class="fd-proposal-field"><span>Telefone</span><input type="text" readonly data-client-phone-field value="<?= e($proposal['cliente_whatsapp'] ?? '') ?>"></label>
                    </div>
                    <div class="fd-proposal-client-context" data-proposal-client-context hidden>
                        <span><i class="ri-mail-line"></i> <b data-client-email></b></span>
                        <span><i class="ri-whatsapp-line"></i> <b data-client-phone></b></span>
                    </div>
                </div>

                <div class="fd-proposal-form-section">
                    <h3><b class="fd-form-step">2</b>Informações gerais</h3>
                    <div class="fd-proposal-fields-grid">
                        <label class="fd-proposal-field">
                            <span>Nº da proposta</span>
                            <input type="text" name="codigo" value="<?= e($proposalCode) ?>" readonly data-proposal-code>
                        </label>
                        <?php $renderDatePicker('data_emissao', 'Data de emissao', $issueDate, 'data-summary-issue'); ?>
                        <?php $renderDatePicker('vencimento', 'Validade da proposta', $validUntil, 'data-summary-validity'); ?>
                        <label class="fd-proposal-field">
                            <span>Status</span>
                            <?php if ($editing): ?>
                                <select name="status" data-summary-status>
                                    <?php foreach (['Rascunho', 'Aguardando Aprovação', 'Aprovada', 'Recusada', 'Vencida'] as $option): ?>
                                        <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= $option ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" value="Aguardando Aprovação" readonly data-summary-status>
                                <input type="hidden" name="status" value="Aguardando Aprovação">
                            <?php endif; ?>
                        </label>
                        <label class="fd-proposal-field">
                            <span>Prazo estimado</span>
                            <div class="fd-proposal-input-suffix"><input type="number" name="prazo_estimado_dias" min="1" max="365" value="<?= $deadline ?>" data-summary-deadline><b>dias</b></div>
                        </label>
                    </div>
                </div>

                <div class="fd-proposal-form-section">
                    <h3><b class="fd-form-step">3</b>Serviço / Categoria</h3>
                    <input type="hidden" name="servico_principal" value="<?= e($service) ?>" data-service-input>
                    <div class="fd-proposal-choice-grid" data-service-options>
                        <?php foreach ($services as $value => $label): ?>
                            <button type="button" data-value="<?= e($value) ?>" class="<?= $service === $value ? 'is-active' : '' ?>"><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="fd-proposal-form-section">
                    <h3><b class="fd-form-step">4</b>Modo de pagamento</h3>
                    <input type="hidden" name="forma_pagamento" value="<?= e($payment) ?>" data-payment-input>
                    <div class="fd-proposal-choice-grid is-payment" data-payment-options>
                        <?php foreach (['A Vista', 'Parcelado', 'Recorrente', '50/50'] as $value): ?>
                            <button type="button" data-value="<?= e($value) ?>" class="<?= $payment === $value ? 'is-active' : '' ?>"><?= e($value === 'A Vista' ? 'À Vista' : $value) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="fd-proposal-installments" data-installments-panel <?= $payment !== 'Parcelado' ? 'hidden' : '' ?>>
                        <label class="fd-proposal-field">
                            <span>Parcelamento</span>
                            <select name="parcelas" data-summary-installments>
                                <?php for ($parcel = 3; $parcel <= 10; $parcel++): ?>
                                    <option value="<?= $parcel ?>" <?= $installments === $parcel ? 'selected' : '' ?>>
                                        <?= $parcel ?>x <?= $parcel === 3 ? 'sem juros' : 'com juros baixos' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="fd-proposal-form-section">
                    <div class="fd-proposal-section-head">
                        <h3><b class="fd-form-step">5</b>Itens da proposta</h3>
                        <button type="button" class="fd-btn-secondary fd-btn-sm" data-add-proposal-item><i class="ri-add-line"></i><span>Adicionar item</span></button>
                    </div>
                    <div class="fd-proposal-items-table">
                        <div class="fd-proposal-items-header">
                            <span>Descricao</span><span>Qtd.</span><span>Valor unitario</span><span>Desconto %</span><span>Subtotal</span><span></span>
                        </div>
                        <div data-proposal-items>
                            <?php foreach ($items as $index => $item): ?>
                                <div class="fd-proposal-item" data-proposal-item>
                                    <input type="text" name="itens[<?= $index ?>][descricao]" value="<?= e($item['descricao']) ?>" placeholder="Descricao do item" required data-item-description>
                                    <input type="number" name="itens[<?= $index ?>][quantidade]" value="<?= e((string) ($item['quantidade'] ?: 1)) ?>" min="0.01" step="0.01" required data-item-quantity>
                                    <input type="number" name="itens[<?= $index ?>][valor_unitario]" value="<?= e((string) ($item['valor_unitario'] ?: $item['valor'])) ?>" min="0" step="0.01" required data-item-price>
                                    <input type="number" name="itens[<?= $index ?>][desconto_percentual]" value="<?= e((string) ($item['desconto_percentual'] ?? 0)) ?>" min="0" max="100" step="0.01" data-item-discount>
                                    <strong data-item-subtotal>R$<?= number_format((float) ($item['subtotal'] ?: $item['valor']), 2, ',', '.') ?></strong>
                                    <button type="button" data-remove-proposal-item aria-label="Remover item"><i class="ri-delete-bin-line"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="fd-proposal-items-total"><span>Subtotal geral dos itens</span><strong data-items-total>R$0,00</strong></div>
                    </div>
                </div>

                <div class="fd-proposal-form-section">
                    <label class="fd-proposal-field is-full">
                        <span class="fd-section-label"><b class="fd-form-step">6</b>Observações / Escopo / Termos</span>
                        <textarea name="descricao_servico" rows="5" data-summary-notes><?= e($notes) ?></textarea>
                    </label>
                </div>

            </section>

            <aside class="fd-proposal-live-preview">
                <div class="fd-proposal-preview-head">
                    <span>Resumo da proposta</span>
                    <strong class="fd-proposal-status is-waiting" data-preview-status><?= e($status) ?></strong>
                </div>
                <div class="fd-proposal-preview-client">
                    <span class="fd-preview-avatar" data-preview-avatar><i class="ri-user-line"></i></span>
                    <div>
                        <strong data-preview-client><?= e($proposal['cliente_nome'] ?? 'Selecione um cliente') ?></strong>
                        <small data-preview-client-email><?= e($proposal['cliente_email'] ?? 'Selecione um cliente') ?></small>
                    </div>
                </div>
                <div class="fd-proposal-preview-overview">
                    <div><i class="ri-computer-line"></i><span><small>Serviço selecionado</small><strong data-preview-category><?= e($services[$service]) ?></strong></span></div>
                    <div><i class="ri-calendar-line"></i><span><small>Validade da proposta</small><strong data-preview-validity><?= date('d/m/Y', strtotime($validUntil)) ?></strong></span></div>
                    <div><i class="ri-money-dollar-circle-line"></i><span><small>Forma de pagamento</small><strong data-preview-payment><?= e($payment) ?></strong></span></div>
                </div>
                <div class="fd-proposal-preview-items-block">
                    <div class="fd-proposal-preview-block-title"><span>Itens da proposta</span><small>Atualização em tempo real</small></div>
                    <div class="fd-proposal-preview-items" data-preview-items></div>
                    <div class="fd-proposal-preview-totals">
                        <div><span>Subtotal</span><strong data-preview-subtotal>R$0,00</strong></div>
                        <div><span>Desconto</span><strong data-preview-discount>- R$0,00</strong></div>
                        <div class="is-total"><span>Total da proposta</span><strong data-preview-total>R$0,00</strong></div>
                    </div>
                </div>
                <h3 class="fd-preview-additional-title">Resumo adicional</h3>
                <div class="fd-proposal-preview-meta">
                    <div><i class="ri-line-chart-line"></i><small>Valor estimado</small><strong data-preview-extra-total>R$0,00</strong></div>
                    <div><i class="ri-time-line"></i><small>Prazo de entrega</small><strong data-preview-deadline><?= $deadline ?> dias úteis</strong></div>
                    <div><i class="ri-bank-card-line"></i><small>Parcelas</small><strong data-preview-installment-label>1x</strong></div>
                </div>
                <p class="fd-preview-note"><i class="ri-information-fill"></i> Esta é uma proposta comercial. Valores e prazos podem ser ajustados antes da aprovação final pelo cliente.</p>
            </aside>
        </div>
    </form>
</div>

<template id="proposalItemTemplate">
    <div class="fd-proposal-item" data-proposal-item>
        <input type="text" placeholder="Descricao do item" required data-item-description>
        <input type="number" value="1" min="0.01" step="0.01" required data-item-quantity>
        <input type="number" value="0" min="0" step="0.01" required data-item-price>
        <input type="number" value="0" min="0" max="100" step="0.01" data-item-discount>
        <strong data-item-subtotal>R$0,00</strong>
        <button type="button" data-remove-proposal-item aria-label="Remover item"><i class="ri-delete-bin-line"></i></button>
    </div>
</template>

<script type="module" src="<?= ($base ?? '') ?>/assets/js/modules/orcamentos-v2.js"></script>
