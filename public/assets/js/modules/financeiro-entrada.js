function initFinanceiroEntradaModal() {
  const modalNovaEntrada = document.getElementById('modalNovaEntrada');
  const buildUrl = window.fdUrl;
  if (!modalNovaEntrada || !buildUrl) return;

  modalNovaEntrada.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    const form = modalNovaEntrada.querySelector('form');
    const titleEl = modalNovaEntrada.querySelector('.modal-title');
    if (!form) return;

    const isEdicao = button && button.classList.contains('btn-editar-entrada');
    if (isEdicao) return;

    form.reset();

    const acaoInput = form.querySelector('input[name="acao"]');
    if (acaoInput) acaoInput.value = 'adicionar_entrada';

    const idInput = form.querySelector('input[name="id"]');
    if (idInput) idInput.remove();

    if (titleEl) {
      titleEl.textContent = 'Adicionar entrada';
    }
  });

  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-editar-entrada');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    if (!id) return;

    const modal = document.getElementById('modalNovaEntrada');
    const form = modal ? modal.querySelector('form') : null;
    if (!modal || !form) return;

    const titleEl = modal.querySelector('.modal-title');
    if (titleEl) {
      titleEl.textContent = 'Editar entrada';
    }

    try {
      const resp = await fetch(buildUrl(`/financeiro/entrada?id=${id}`));
      const data = await resp.json();
      if (!data || !data.id) return;

      let idInput = form.querySelector('input[name="id"]');
      if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        form.appendChild(idInput);
      }
      idInput.value = data.id;

      const acaoInput = form.querySelector('input[name="acao"]');
      if (acaoInput) acaoInput.value = 'salvar_entrada';

      setFinanceiroFieldValue(form.querySelector('[name="data_lancamento"]'), data.data_lancamento || '');
      setFinanceiroFieldValue(form.querySelector('[name="descricao"]'), data.descricao || '');
      setFinanceiroFieldValue(form.querySelector('[name="servico"]'), data.servico || 'outro');
      setFinanceiroFieldValue(form.querySelector('[name="tipo_pagamento"]'), data.tipo_pagamento || 'integral');
      setFinanceiroFieldValue(form.querySelector('[name="forma_pagamento"]'), data.forma_pagamento || 'pix');
      setFinanceiroFieldValue(form.querySelector('[name="valor_a_receber"]'), floatToBrMoney(data.valor_a_receber));
      setFinanceiroFieldValue(form.querySelector('[name="valor_recebido"]'), floatToBrMoney(data.valor_recebido));
      setFinanceiroFieldValue(form.querySelector('[name="observacoes"]'), data.observacoes || '');
      setFinanceiroFieldValue(form.querySelector('[name="cliente_id"]'), data.cliente_id || '');
    } catch (err) {
      console.error('Erro ao carregar entrada para edicao', err);
    }
  });
}

function setFinanceiroFieldValue(element, value) {
  if (!element) return;
  element.value = value;
  element.dispatchEvent(new Event('input', { bubbles: true }));
  element.dispatchEvent(new Event('change', { bubbles: true }));
}

function floatToBrMoney(v) {
  if (v === null || v === undefined || v === '') return '';
  const num = Number(v) || 0;
  return num
    .toFixed(2)
    .replace('.', ',')
    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

window.initFinanceiroEntradaModal = initFinanceiroEntradaModal;
