<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$listaClientes = $listaClientes ?? [];
$categoriasFinanceiras = $categoriasFinanceiras ?? [];
?>

<div class="modal fade fd-fin-modal" id="modalContaReceber" tabindex="-1" aria-labelledby="modalContaReceberLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao">
                <input type="hidden" name="acao" value="adicionar_entrada">
                <input type="hidden" name="aba" value="receber">
                <input type="hidden" name="valor_recebido" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalContaReceberLabel">Nova conta a receber</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="fd-fin-modal-grid">
                        <label class="fd-fin-modal-field is-full">
                            <span>Cliente</span>
                            <select name="cliente_id" class="form-select">
                                <option value="">Sem cliente vinculado</option>
                                <?php foreach ($listaClientes as $cliente): ?>
                                    <option value="<?= (int) $cliente['id'] ?>"><?= e($cliente['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="fd-fin-modal-field is-full">
                            <span>Descricao</span>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Landing Page, mensalidade, consultoia" required>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Valor</span>
                            <input type="text" name="valor_a_receber" class="form-control js-money" placeholder="00,00" required>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Moeda</span>
                            <select class="form-select" name="moeda">
                                <option value="BRL">BRL</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Vencimento</span>
                            <input type="date" name="data_lancamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </label>

                        <div class="fd-fin-modal-field is-full">
                            <span>Modo de Pagamento</span>
                            <div class="fd-fin-choice-box">
                                <input type="radio" class="btn-check" name="tipo_pagamento" id="receberModoVista" value="integral" checked>
                                <label for="receberModoVista"><i class="ri-file-close-line"></i> A Vista</label>

                                <input type="radio" class="btn-check" name="tipo_pagamento" id="receberModoParcelado" value="parcelado">
                                <label for="receberModoParcelado"><i class="ri-file-close-line"></i> Parcelado</label>

                                <input type="radio" class="btn-check" name="tipo_pagamento" id="receberModoRecorrente" value="recorrente">
                                <label for="receberModoRecorrente"><i class="ri-file-close-line"></i> Recorrente</label>
                            </div>
                        </div>

                        <div class="fd-fin-modal-field is-full">
                            <span>Status</span>
                            <div class="fd-fin-choice-box">
                                <input type="radio" class="btn-check" name="status_pagamento" id="receberStatusPendente" value="pendente" checked>
                                <label for="receberStatusPendente"><i class="ri-file-close-line"></i> Pendente</label>

                                <input type="radio" class="btn-check" name="status_pagamento" id="receberStatusParcial" value="parcial">
                                <label for="receberStatusParcial"><i class="ri-file-close-line"></i> Parcial</label>

                                <input type="radio" class="btn-check" name="status_pagamento" id="receberStatusPago" value="pago">
                                <label for="receberStatusPago"><i class="ri-file-close-line"></i> Pago</label>
                            </div>
                        </div>

                        <label class="fd-fin-modal-field is-full">
                            <span>Categoria</span>
                            <select name="categoria_financeira" class="form-select">
                                <option value="outro">Sem Categoria</option>
                                <?php foreach ($categoriasFinanceiras as $categoria): ?>
                                    <option value="<?= e($categoria['nome']) ?>"><?= e($categoria['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="servico" value="outro">
                        </label>

                        <label class="fd-fin-modal-field is-full">
                            <span>Observacoes</span>
                            <input type="text" name="observacoes" class="form-control" placeholder="Adiciones observacoes">
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn fd-fin-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fd-fin-modal-submit">Lancar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade fd-fin-modal" id="modalRegistrarPagamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao">
                <input type="hidden" name="acao" value="registrar_pagamento_entrada" data-payment-action>
                <input type="hidden" name="aba" value="receber" data-payment-tab>
                <input type="hidden" name="id" value="" data-payment-id>
                <div class="modal-header">
                    <h5 class="modal-title">Registrar pagamento</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button>
                </div>
                <div class="modal-body">
                    <div class="fd-fin-modal-grid">
                        <p class="fd-card-subtitle is-full" data-payment-title></p>
                        <label class="fd-fin-modal-field is-full">
                            <span>Valor pago</span>
                            <input type="text" name="valor_pago" class="form-control js-money" placeholder="00,00" required data-payment-value>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn fd-fin-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fd-fin-modal-submit">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade fd-fin-modal" id="modalFinanceiroCategorias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao">
                <input type="hidden" name="acao" value="criar_categoria">
                <div class="modal-header">
                    <h5 class="modal-title">Categorias financeiras</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button>
                </div>
                <div class="modal-body">
                    <div class="fd-fin-modal-grid">
                        <label class="fd-fin-modal-field is-full">
                            <span>Nova categoria</span>
                            <input type="text" name="nome" class="form-control" placeholder="Digite o nome e pressione Enter" required>
                        </label>
                        <div class="fd-fin-modal-field is-full">
                            <span>Cor</span>
                            <div class="fd-fin-color-picker">
                                <?php foreach (['#5690D9', '#56D96E', '#D95656', '#FACC15', '#A855F7', '#F97316'] as $index => $color): ?>
                                    <label style="--cat-color: <?= e($color) ?>">
                                        <input type="radio" name="cor" value="<?= e($color) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                        <span></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="fd-fin-category-list is-full">
                            <?php foreach ($categoriasFinanceiras as $categoria): ?>
                                <span><i style="background: <?= e($categoria['cor'] ?? '#5690D9') ?>"></i><?= e($categoria['nome']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn fd-fin-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fd-fin-modal-submit">Salvar categoria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  ['modalContaReceber', 'modalContaPagar'].forEach((id) => {
    const modal = document.getElementById(id);
    modal?.addEventListener('show.bs.modal', (event) => {
      const isEdit = event.relatedTarget?.classList?.contains('js-fin-edit-entrada') || event.relatedTarget?.classList?.contains('js-fin-edit-saida');
      if (isEdit) return;
      const form = modal.querySelector('form');
      form?.reset();
      form?.querySelector('input[name="id"]')?.remove();
      const action = form?.querySelector('input[name="acao"]');
      if (action) action.value = id === 'modalContaPagar' ? 'adicionar_saida' : 'adicionar_entrada';
    });
  });

  const paymentModal = document.getElementById('modalRegistrarPagamento');
  paymentModal?.addEventListener('show.bs.modal', (event) => {
    const btn = event.relatedTarget;
    const kind = btn?.dataset.kind || 'entrada';
    paymentModal.querySelector('[data-payment-action]').value = kind === 'saida' ? 'registrar_pagamento_saida' : 'registrar_pagamento_entrada';
    paymentModal.querySelector('[data-payment-tab]').value = kind === 'saida' ? 'pagar' : 'receber';
    paymentModal.querySelector('[data-payment-id]').value = btn?.dataset.id || '';
    paymentModal.querySelector('[data-payment-title]').textContent = btn?.dataset.title || '';
    const saldo = Number(btn?.dataset.saldo || 0);
    paymentModal.querySelector('[data-payment-value]').value = saldo > 0 ? saldo.toFixed(2).replace('.', ',') : '';
  });

  const fillForm = (form, data, type) => {
    if (!form || !data) return;
    let id = form.querySelector('input[name="id"]');
    if (!id) {
      id = document.createElement('input');
      id.type = 'hidden';
      id.name = 'id';
      form.appendChild(id);
    }
    id.value = data.id || '';
    form.querySelector('input[name="acao"]').value = type === 'saida' ? 'salvar_saida' : 'salvar_entrada';
    Object.entries({
      cliente_id: data.cliente_id || '',
      favorecido: data.favorecido || data.favorecido_label || '',
      descricao: data.descricao || '',
      valor_a_receber: data.valor_a_receber || '',
      valor: data.valor || '',
      moeda: data.moeda || 'BRL',
      data_lancamento: data.data_lancamento || '',
      servico: data.categoria_financeira || data.servico || 'outro',
      categoria_financeira: data.categoria_financeira || data.tipo || '',
      observacoes: data.observacoes || ''
    }).forEach(([name, value]) => {
      const field = form.querySelector(`[name="${name}"]`);
      if (field) field.value = String(value).replace('.', ',');
    });
  };

  document.querySelectorAll('.js-fin-edit-entrada').forEach((button) => {
    button.addEventListener('click', () => fillForm(document.querySelector('#modalContaReceber form'), JSON.parse(button.dataset.record || '{}'), 'entrada'));
  });
  document.querySelectorAll('.js-fin-edit-saida').forEach((button) => {
    button.addEventListener('click', () => fillForm(document.querySelector('#modalContaPagar form'), JSON.parse(button.dataset.record || '{}'), 'saida'));
  });
});
</script>

<div class="modal fade fd-fin-modal" id="modalContaPagar" tabindex="-1" aria-labelledby="modalContaPagarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/financeiro/acao">
                <input type="hidden" name="acao" value="adicionar_saida">
                <input type="hidden" name="aba" value="pagar">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalContaPagarLabel">Nova conta a pagar</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="fd-fin-modal-grid">
                        <label class="fd-fin-modal-field is-full">
                            <span>Cliente</span>
                            <input type="text" name="favorecido" class="form-control" placeholder="Sem cliente vinculado">
                        </label>

                        <label class="fd-fin-modal-field is-full">
                            <span>Descricao</span>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Landing Page, mensalidade, consultoia" required>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Valor</span>
                            <input type="text" name="valor" class="form-control js-money" placeholder="00,00" required>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Moeda</span>
                            <select class="form-select" name="moeda">
                                <option value="BRL">BRL</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </label>

                        <label class="fd-fin-modal-field">
                            <span>Vencimento</span>
                            <input type="date" name="data_lancamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </label>

                        <div class="fd-fin-modal-field is-full">
                            <span>Tipo de Despesa</span>
                            <div class="fd-fin-choice-box fd-fin-choice-box-2">
                                <input type="radio" class="btn-check" name="tipo" id="pagarTipoAvulsa" value="operacional" checked>
                                <label for="pagarTipoAvulsa"><i class="ri-file-close-line"></i> Avulsa</label>

                                <input type="radio" class="btn-check" name="tipo" id="pagarTipoRecorrente" value="recorrente">
                                <label for="pagarTipoRecorrente"><i class="ri-file-close-line"></i> Recorrente</label>
                            </div>
                        </div>

                        <div class="fd-fin-modal-field is-full">
                            <span>Status</span>
                            <div class="fd-fin-choice-box">
                                <input type="radio" class="btn-check" name="status_pagamento" id="pagarStatusPendente" value="pendente" checked>
                                <label for="pagarStatusPendente"><i class="ri-file-close-line"></i> Pendente</label>

                                <input type="radio" class="btn-check" name="status_pagamento" id="pagarStatusParcial" value="parcial">
                                <label for="pagarStatusParcial"><i class="ri-file-close-line"></i> Parcial</label>

                                <input type="radio" class="btn-check" name="status_pagamento" id="pagarStatusPago" value="pago">
                                <label for="pagarStatusPago"><i class="ri-file-close-line"></i> Pago</label>
                            </div>
                        </div>

                        <label class="fd-fin-modal-field is-full">
                            <span>Categoria</span>
                            <select name="categoria_financeira" class="form-select">
                                <option value="">Sem Categoria</option>
                                <?php foreach ($categoriasFinanceiras as $categoria): ?>
                                    <option value="<?= e($categoria['nome']) ?>"><?= e($categoria['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="fd-fin-modal-field is-full">
                            <span>Observacoes</span>
                            <input type="text" name="observacoes" class="form-control" placeholder="Adiciones observacoes">
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn fd-fin-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn fd-fin-modal-submit">Lancar</button>
                </div>
            </form>
        </div>
    </div>
</div>
