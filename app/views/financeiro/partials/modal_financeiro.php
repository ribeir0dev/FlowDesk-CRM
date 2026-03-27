<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db.php';

$workspaceId = fd_current_workspace_id() ?? 0;
$stmtCli = $pdo->prepare('SELECT id, nome FROM clientes WHERE workspace_id = ? ORDER BY nome ASC');
$stmtCli->execute([$workspaceId]);
$listaClientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="modal fade" id="modalNovaEntrada" tabindex="-1" aria-labelledby="modalNovaEntradaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" id="form-nova-entrada">
                <input type="hidden" name="acao" value="adicionar_entrada">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaEntradaLabel">Adicionar entrada</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Data</label>
                            <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= date('Y-m-d') ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                                <input type="date" name="data_lancamento" class="fd-date-picker-native" x-model="selectedValue" required>
                                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                                    <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                                    <span class="fd-date-picker-trigger-copy">
                                        <span class="fd-date-picker-trigger-label">Lancamento</span>
                                        <strong x-text="triggerLabel"></strong>
                                    </span>
                                    <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
                                </button>
                                <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                                    <div class="fd-date-picker-head">
                                        <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                                        <div class="fd-date-picker-head-copy">
                                            <span class="fd-date-picker-head-label">Selecione a data</span>
                                            <strong x-text="headerLabel"></strong>
                                        </div>
                                        <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                                    </div>
                                    <div class="fd-date-picker-weekdays">
                                        <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                                    </div>
                                    <div class="fd-date-picker-days">
                                        <template x-for="item in days()" :key="item.key">
                                            <div>
                                                <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                                                <template x-if="!item.empty">
                                                    <button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="fd-date-picker-footer">
                                        <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                                        <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small">Descricao</label>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Site institucional para cliente X" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Cliente (opcional)</label>
                            <select name="cliente_id" class="form-select form-select-sm">
                                <option value="">Sem cliente vinculado</option>
                                <?php foreach ($listaClientes as $cliente): ?>
                                    <option value="<?= (int) $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Servico</label>
                            <select name="servico" class="form-select" required>
                                <option value="landing_page">Landing page</option>
                                <option value="website">Website</option>
                                <option value="configuracao">Configuracao</option>
                                <option value="alteracao">Alteracao</option>
                                <option value="design">Design</option>
                                <option value="salario">Salario</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Tipo de pagamento</label>
                            <select name="tipo_pagamento" class="form-select" required>
                                <option value="50_50">50/50</option>
                                <option value="integral" selected>Integral</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Forma de pagamento</label>
                            <select name="forma_pagamento" class="form-select" required>
                                <option value="pix" selected>PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="cartao">Cartao</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Valor a receber</label>
                            <input type="text" min="0" name="valor_a_receber" class="form-control js-money" placeholder="0,00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Valor ja recebido</label>
                            <input type="text" min="0" name="valor_recebido" class="form-control js-money" placeholder="0,00" value="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Observacoes (opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Detalhes sobre esta entrada, condicoes de pagamento e observacoes uteis."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar entrada</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovaSaida" tabindex="-1" aria-labelledby="modalNovaSaidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" id="form-nova-saida">
                <input type="hidden" name="acao" value="adicionar_saida">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaSaidaLabel">Adicionar saida</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Data</label>
                            <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= date('Y-m-d') ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                                <input type="date" name="data_lancamento" class="fd-date-picker-native" x-model="selectedValue" required>
                                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                                    <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                                    <span class="fd-date-picker-trigger-copy">
                                        <span class="fd-date-picker-trigger-label">Lancamento</span>
                                        <strong x-text="triggerLabel"></strong>
                                    </span>
                                    <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
                                </button>
                                <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                                    <div class="fd-date-picker-head">
                                        <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                                        <div class="fd-date-picker-head-copy">
                                            <span class="fd-date-picker-head-label">Selecione a data</span>
                                            <strong x-text="headerLabel"></strong>
                                        </div>
                                        <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                                    </div>
                                    <div class="fd-date-picker-weekdays">
                                        <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                                    </div>
                                    <div class="fd-date-picker-days">
                                        <template x-for="item in days()" :key="item.key">
                                            <div>
                                                <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                                                <template x-if="!item.empty">
                                                    <button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="fd-date-picker-footer">
                                        <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                                        <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small">Descricao</label>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Almoco com cliente, ferramenta, deslocamento, etc." required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Tipo de gasto</label>
                            <select name="tipo" class="form-select" required>
                                <option value="mercado">Mercado</option>
                                <option value="lanche">Lanche</option>
                                <option value="almoco">Almoco</option>
                                <option value="pagamentos">Pagamentos</option>
                                <option value="retiradas">Retiradas</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Valor</label>
                            <input type="text" min="0" name="valor" class="form-control js-money" placeholder="0,00" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Observacoes (opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Categoria detalhada, contexto da saida e forma de pagamento."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar saida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGastoFixo" tabindex="-1" aria-labelledby="modalGastoFixoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao" id="form-gasto-fixo">
                <input type="hidden" name="acao" value="adicionar_fixo">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalGastoFixoLabel">Adicionar gasto fixo</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small">Tipo de gasto</label>
                            <input type="text" name="tipo_gasto" class="form-control" placeholder="Ex: Hospedagem, internet, aluguel" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Valor</label>
                            <input type="text" min="0" name="valor" class="form-control js-money" placeholder="0,00" required>
                            <div class="form-text small">
                                Se for parcelado, este e o valor de <strong>cada parcela</strong>.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Data de inicio</label>
                            <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= date('Y-m-d') ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                                <input type="date" name="data_inicio" class="fd-date-picker-native" x-model="selectedValue" required>
                                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                                    <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                                    <span class="fd-date-picker-trigger-copy">
                                        <span class="fd-date-picker-trigger-label">Inicio</span>
                                        <strong x-text="triggerLabel"></strong>
                                    </span>
                                    <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
                                </button>
                                <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                                    <div class="fd-date-picker-head">
                                        <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                                        <div class="fd-date-picker-head-copy">
                                            <span class="fd-date-picker-head-label">Selecione a data</span>
                                            <strong x-text="headerLabel"></strong>
                                        </div>
                                        <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                                    </div>
                                    <div class="fd-date-picker-weekdays">
                                        <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                                    </div>
                                    <div class="fd-date-picker-days">
                                        <template x-for="item in days()" :key="item.key">
                                            <div>
                                                <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                                                <template x-if="!item.empty">
                                                    <button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="fd-date-picker-footer">
                                        <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                                        <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="ehParcelado" name="eh_parcelado">
                                <label class="form-check-label small" for="ehParcelado">
                                    Este gasto e parcelado
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 parcelas-field d-none">
                            <label class="form-label small">Quantidade total de parcelas</label>
                            <select name="parcelas_totais" class="form-select form-select-sm">
                                <?php for ($i = 1; $i <= 24; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6 parcelas-field d-none">
                            <label class="form-label small">Parcelas restantes</label>
                            <select name="parcelas_restantes" class="form-select form-select-sm">
                                <?php for ($i = 1; $i <= 24; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Observacoes (opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Detalhes adicionais sobre este gasto fixo."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar gasto fixo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chk = document.getElementById('ehParcelado');
    const fields = document.querySelectorAll('.parcelas-field');
    if (!chk) return;

    function toggleParcelas() {
        fields.forEach((field) => {
            field.classList.toggle('d-none', !chk.checked);
        });
    }

    chk.addEventListener('change', toggleParcelas);
    toggleParcelas();
});
</script>
