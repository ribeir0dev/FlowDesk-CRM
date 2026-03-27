const ORC_SERVICOS_DESCR = {
  landing_page: 'Landing Page completa, responsiva e otimizada para conversao.',
  configuracao: 'Configuracao tecnica (dominio, hospedagem e DNS).',
  stream_overlay: 'Pacote de overlays para stream (cenas, alerts e paineis).',
  criativos: 'Criacao de pecas criativas para campanhas de trafego pago.',
  identidade_visual: 'Identidade visual completa (logo, paleta, tipografia).'
};

function initModalOrcamento() {
  const form = document.getElementById('formOrcamento');
  if (!form) {
    console.warn('initModalOrcamento: formOrcamento nao encontrado');
    return;
  }

  const servicoSelect = document.getElementById('servicoPrincipal');
  const descServico = document.getElementById('descricaoServico');
  const btnAddItem = document.getElementById('btnAdicionarItem');
  const itensContainer = document.getElementById('itensContainer');
  const totalSpan = document.getElementById('totalOrcamento');
  const totalInput = document.getElementById('valorTotalInput');
  const displayId = document.getElementById('orcDisplayId');
  const hiddenId = document.getElementById('orcId');
  const modalEl = document.getElementById('modalNovoOrcamento');
  const clienteSelect = document.getElementById('clienteSelect');
  const formaPagamento = document.getElementById('formaPagamento');
  const statusSelect = document.getElementById('statusOrcamento');
  const modalTitulo = document.getElementById('orcModalTitulo');
  const btnSalvar = document.getElementById('btnSalvarOrcamento');
  const buildUrl = window.fdUrl;
  const showInlineAlert = window.fdShowInlineAlert;

  if (!buildUrl) {
    console.warn('initModalOrcamento: fdUrl nao encontrado');
    return;
  }

  if (!btnAddItem || !itensContainer || !totalSpan || !totalInput || !servicoSelect || !descServico) {
    console.warn('initModalOrcamento: elementos do modal nao encontrados');
    return;
  }

  let itemIndex = 0;

  function mostrarErro(message) {
    if (typeof showInlineAlert === 'function') {
      showInlineAlert(message, 'danger', {
        containerId: 'orcamentosFeedback',
        contextSelector: '.fd-budgets',
        replace: true,
        scroll: true
      });
      return;
    }

    console.error(message);
  }

  function definirModoNovo() {
    form.setAttribute('action', buildUrl('/orcamentos/criar'));
    if (modalTitulo) modalTitulo.textContent = 'Novo orcamento';
    if (btnSalvar) btnSalvar.textContent = 'Salvar orcamento';
  }

  function definirModoEdicao() {
    form.setAttribute('action', buildUrl('/orcamentos/atualizar'));
    if (modalTitulo) modalTitulo.textContent = 'Editar orcamento';
    if (btnSalvar) btnSalvar.textContent = 'Salvar alteracoes';
  }

  if (modalEl) {
    modalEl.addEventListener('show.bs.modal', async (event) => {
      const button = event.relatedTarget;
      const id = button ? button.getAttribute('data-id') : null;

      if (!id) {
        definirModoNovo();
        hiddenId.value = '';
        displayId.textContent = 'Novo';
        form.reset();
        itensContainer.innerHTML = '';
        totalSpan.textContent = '0,00';
        totalInput.value = '0.00';
        itemIndex = 0;
        adicionarItem();
      } else {
        await carregarOrcamento(parseInt(id, 10));
      }
    });
  }

  servicoSelect.addEventListener('change', () => {
    const key = servicoSelect.value;
    descServico.value = ORC_SERVICOS_DESCR[key] || '';
  });

  btnAddItem.addEventListener('click', () => {
    adicionarItem();
  });

  function adicionarItem(descricao = '', valorNum = 0) {
    const idx = itemIndex++;
    const row = document.createElement('div');
    row.className = 'row g-3 align-items-end mb-3';
    row.dataset.index = idx;

    const valorStr = (valorNum || valorNum === 0)
      ? Number(valorNum).toFixed(2).replace('.', ',')
      : '';

    row.innerHTML = `
      <div class="col-md-7">
        <label class="form-label">Descricao do item</label>
        <input type="text"
               class="form-control orc-item-descricao"
               name="itens[${idx}][descricao]"
               value="${descricao.replace(/"/g, '&quot;')}"
               required>
      </div>

      <div class="col-md-3">
        <label class="form-label">Valor (R$)</label>
        <input type="text"
               class="form-control orc-item-valor js-money"
               name="itens[${idx}][valor]"
               value="${valorStr}"
               placeholder="0,00"
               required>
      </div>

      <div class="col-md-2 text-end">
        <button type="button"
                class="btn btn-outline-danger btn-sm btn-remover-item">
          <i class="ri-delete-bin-line me-1"></i>Remover
        </button>
      </div>
    `;

    itensContainer.appendChild(row);

    const valorInput = row.querySelector('.orc-item-valor');
    const btnRemove = row.querySelector('.btn-remover-item');

    if (typeof window.formatMoney === 'function') {
      ['input', 'blur'].forEach((evt) => {
        valorInput.addEventListener(evt, () => {
          window.formatMoney(valorInput);
          calcularTotal();
        });
      });

      if (valorInput.value) {
        window.formatMoney(valorInput);
      }
    } else {
      valorInput.addEventListener('input', calcularTotal);
    }

    btnRemove.addEventListener('click', () => {
      row.remove();
      calcularTotal();
    });
  }

  function calcularTotal() {
    let total = 0;

    document.querySelectorAll('.orc-item-valor').forEach((inp) => {
      let v = (inp.value || '').trim();
      if (!v) return;

      v = v.replace(/[^\d,.-]/g, '');
      v = v.replace(/\./g, '').replace(',', '.');

      const num = parseFloat(v);
      if (!isNaN(num)) total += num;
    });

    const totalBr = total.toFixed(2).replace('.', ',');
    totalSpan.textContent = totalBr;
    totalInput.value = total.toFixed(2);
  }

  form.addEventListener('submit', () => {
    calcularTotal();
  });

  async function carregarOrcamento(id) {
    try {
      const res = await fetch(buildUrl('/orcamentos/buscar?id=' + encodeURIComponent(id)));
      if (!res.ok) {
        throw new Error(`Falha ao buscar orcamento (HTTP ${res.status})`);
      }

      const raw = await res.text();
      let data = null;

      try {
        data = JSON.parse(raw);
      } catch (parseError) {
        console.error('Resposta invalida ao carregar orcamento:', raw);
        throw new Error('Resposta invalida do servidor ao carregar orcamento');
      }

      if (!data.success) {
        mostrarErro(data.message || 'Nao foi possivel carregar este orcamento para edicao.');
        return;
      }

      const o = data.orcamento;
      const itens = data.itens || [];
      definirModoEdicao();

      hiddenId.value = o.id;
      displayId.textContent = o.codigo || ('#' + o.id);

      if (clienteSelect) clienteSelect.value = o.cliente_id;
      servicoSelect.value = o.servico_principal;
      descServico.value = o.descricao_servico;
      if (formaPagamento) formaPagamento.value = o.forma_pagamento;
      if (statusSelect) statusSelect.value = o.status;

      itensContainer.innerHTML = '';
      itemIndex = 0;

      if (itens.length) {
        itens.forEach((item) => {
          adicionarItem(item.descricao || '', parseFloat(item.valor));
        });
      } else {
        adicionarItem();
      }

      const totalNum = Number(o.valor_total) || 0;
      totalSpan.textContent = totalNum.toFixed(2).replace('.', ',');
      totalInput.value = totalNum.toFixed(2);
    } catch (err) {
      console.error(err);
      mostrarErro(err?.message || 'Nao foi possivel carregar os dados do orcamento para edicao.');
    }
  }
}

window.initModalOrcamento = initModalOrcamento;
