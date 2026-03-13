// ========================================
// LOGIN / CRIAR CONTA (TABS)
// ========================================
document.addEventListener('DOMContentLoaded', () => {
  const btnLogin = document.getElementById('btn-login');
  const btnCriar = document.getElementById('btn-criar');
  const formLogin = document.getElementById('form-login');
  const formCriar = document.getElementById('form-criar');

  // aplica tema salvo antes de qualquer coisa visual
  applyThemeFromStorage();

  // Alterna visualmente entre Login e Criar Conta
  function ativarLogin() {
    btnLogin?.classList.add('active');
    btnCriar?.classList.remove('active');
    if (formLogin) formLogin.style.display = '';
    if (formCriar) formCriar.style.display = 'none';
  }

  function ativarCriar() {
    btnCriar?.classList.add('active');
    btnLogin?.classList.remove('active');
    if (formLogin) formLogin.style.display = 'none';
    if (formCriar) formCriar.style.display = '';
  }

  if (btnLogin && btnCriar && formLogin && formCriar) {
    btnLogin.addEventListener('click', ativarLogin);
    btnCriar.addEventListener('click', ativarCriar);
  }

  // ======================================
  // AJAX: CRIAR CONTA
  // ======================================
  if (formCriar) {
    formCriar.addEventListener('submit', async (e) => {
      e.preventDefault();

      const form = e.target;
      const formData = new FormData(form);
      const caixa = document.getElementById('msg-criar-conta');
      if (!caixa) return;

      caixa.innerHTML = '<div class="alert alert-info">Processando, aguarde...</div>';

      try {
        const res = await fetch('app/Controllers/AuthController.php?acao=register', {
          method: 'POST',
          body: formData,
        });
        const data = await res.json();

        if (data.success) {
          caixa.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
          form.reset();
          ativarLogin();
        } else {
          const erros = (data.errors || ['Erro desconhecido.'])
            .map((err) => `<li>${err}</li>`)
            .join('');
          caixa.innerHTML = `<div class="alert alert-danger"><ul>${erros}</ul></div>`;
        }
      } catch {
        caixa.innerHTML = '<div class="alert alert-danger">Erro ao conectar ao servidor!</div>';
      }
    });
  }

  // ======================================
  // MENU MOBILE (SIDEBAR)
  // ======================================
  const menuBtn = document.getElementById('menuToggle');
  const sidebar = document.querySelector('.sv-sidebar');

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
      const clicouForaSidebar = !sidebar.contains(e.target);
      const clicouForaBotao = e.target !== menuBtn && !menuBtn.contains(e.target);

      if (
        window.innerWidth <= 991 &&
        sidebar.classList.contains('show') &&
        clicouForaSidebar &&
        clicouForaBotao
      ) {
        sidebar.classList.remove('show');
      }
    });
  }

  // ======================================
  // FORMATADOR DE MOEDA (inputs .js-money)
  // ======================================
  const valorInputs = document.querySelectorAll('input.js-money');

  function formatMoney(input) {
    let v = (input.value || '').replace(/\D/g, '');
    if (!v) {
      input.value = '';
      return;
    }
    v = (parseInt(v, 10) / 100).toFixed(2);
    v = v.replace('.', ',');
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = v;
  }

  valorInputs.forEach((inp) => {
    ['input', 'blur'].forEach((evt) => {
      inp.addEventListener(evt, () => {
        formatMoney(inp);
      });
    });
  });

  // ======================================
  // MÁSCARA TELEFONE (inputs .js-telefone)
  // ======================================
  const telInputs = document.querySelectorAll('.js-telefone');

  function maskPhone(value) {
    value = value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 10) {
      value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    } else if (value.length > 6) {
      value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
    } else if (value.length > 2) {
      value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
    } else if (value.length > 0) {
      value = value.replace(/^(\d{0,2})/, '($1');
    }
    return value;
  }

  telInputs.forEach((input) => {
    ['input', 'blur'].forEach((evt) => {
      input.addEventListener(evt, () => {
        input.value = maskPhone(input.value);
      });
    });
  });

  // ======================================
  // LABELS DE DATA (inicio/entrega projeto)
  // ======================================
  const inicioInput = document.getElementById('dataInicioProjeto');
  const entregaInput = document.getElementById('dataEntregaProjeto');
  const inicioLabel = document.getElementById('labelDataInicioProjeto');
  const entregaLabel = document.getElementById('labelDataEntregaProjeto');

  const formatBR = (dateStr) => {
    if (!dateStr) return '—/—/----';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
  };

  if (inicioInput && inicioLabel) {
    inicioInput.addEventListener('change', () => {
      inicioLabel.textContent = formatBR(inicioInput.value);
    });
  }

  if (entregaInput && entregaLabel) {
    entregaInput.addEventListener('change', () => {
      entregaLabel.textContent = formatBR(entregaInput.value);
    });
  }

  // ======================================
  // MODAL EDITAR TAREFA (KANBAN PROJETO)
  // ======================================
  const colEdit = document.getElementById('editTarefaColunaSelect');
  const colEditH = document.getElementById('editTarefaColuna');
  const modalEdit = document.getElementById('modalEditarTarefa');

  if (colEdit && colEditH) {
    colEdit.addEventListener('change', () => {
      colEditH.value = colEdit.value;
    });
  }

  if (modalEdit) {
    modalEdit.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      if (!button) return;

      const tarefaId = button.getAttribute('data-id');
      const titulo = button.getAttribute('data-titulo') || '';
      const descricao = button.getAttribute('data-descricao') || '';
      const coluna = button.getAttribute('data-coluna') || 'backlog';

      document.getElementById('editTarefaId').value = tarefaId;
      document.getElementById('editTarefaTitulo').value = titulo;
      document.getElementById('editTarefaDescricao').value = descricao;
      colEdit.value = coluna;
      colEditH.value = coluna;
    });
  }

  // ======================================
  // MODAL EDITAR PROJETO (via AJAX)
  // ======================================
  const modalEditarProjeto = document.getElementById('modalEditarProjeto');

  if (modalEditarProjeto) {
    modalEditarProjeto.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      if (!button) return;

      const id = button.getAttribute('data-id');
      document.getElementById('editProjetoId').value = id;

      fetch('/app/Controllers/ProjetoController.php?acao=getProjeto&id=' + encodeURIComponent(id))
        .then((r) => (r.ok ? r.json() : null))
        .then((data) => {
          if (!data) return;
          document.getElementById('editNomeProjeto').value = data.nome_projeto || '';
          // aqui você pode preencher os demais campos se quiser
        });
    });
  }

  // função auxiliar para formatar número em "1.234,56"
  function floatToBrMoney(v) {
    if (v === null || v === undefined || v === '') return '';
    const num = Number(v) || 0;
    return num
      .toFixed(2)
      .replace('.', ',')
      .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // ======================================
  // EDITAR ENTRADA FINANCEIRA (modal)
  // ======================================
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-editar-entrada');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    if (!id) return;

    const modal = document.getElementById('modalNovaEntrada');
    const form = modal.querySelector('form');

    const titleEl = modal.querySelector('.modal-title');
    if (titleEl) {
      titleEl.textContent = 'Editar entrada';
    }

    try {
      const resp = await fetch(`/app/Controllers/FinanceiroController.php?acao=buscar_entrada&id=${id}`);
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

      if (form.querySelector('[name="data_lancamento"]'))
        form.querySelector('[name="data_lancamento"]').value = data.data_lancamento || '';

      if (form.querySelector('[name="descricao"]'))
        form.querySelector('[name="descricao"]').value = data.descricao || '';

      if (form.querySelector('[name="servico"]'))
        form.querySelector('[name="servico"]').value = data.servico || 'outro';

      if (form.querySelector('[name="tipo_pagamento"]'))
        form.querySelector('[name="tipo_pagamento"]').value = data.tipo_pagamento || 'integral';

      if (form.querySelector('[name="forma_pagamento"]'))
        form.querySelector('[name="forma_pagamento"]').value = data.forma_pagamento || 'pix';

      if (form.querySelector('[name="valor_a_receber"]'))
        form.querySelector('[name="valor_a_receber"]').value =
          floatToBrMoney(data.valor_a_receber);

      if (form.querySelector('[name="valor_recebido"]'))
        form.querySelector('[name="valor_recebido"]').value =
          floatToBrMoney(data.valor_recebido);

      if (form.querySelector('[name="observacoes"]'))
        form.querySelector('[name="observacoes"]').value = data.observacoes || '';

      if (form.querySelector('[name="cliente_id"]'))
        form.querySelector('[name="cliente_id"]').value = data.cliente_id || '';

    } catch (err) {
      console.error('Erro ao carregar entrada para edição', err);
    }
  });

  // ======================================
  // BUSCA GLOBAL NO PAINEL (autocomplete)
  // ======================================
  const inputBusca = document.getElementById('global-search');
  const boxResultados = document.getElementById('search-results');

  if (inputBusca && boxResultados) {
    let timer = null;

    inputBusca.addEventListener('input', () => {
      const q = inputBusca.value.trim();
      clearTimeout(timer);

      if (q.length < 2) {
        boxResultados.style.display = 'none';
        boxResultados.innerHTML = '';
        return;
      }

      timer = setTimeout(() => {
        fetch('/app/Controllers/SearchController.php?q=' + encodeURIComponent(q))
          .then((r) => (r.ok ? r.json() : []))
          .then((data) => {
            if (!data.length) {
              boxResultados.innerHTML =
                '<div class="list-group-item small text-muted">Nada encontrado.</div>';
              boxResultados.style.display = 'block';
              return;
            }

            boxResultados.innerHTML = data
              .map((item) => {
                let url = '#';
                let icon = '';

                if (item.tipo === 'cliente') {
                  url = '/modules/painel.php?mod=cliente&id=' + item.id;
                  icon = 'ri-user-fill';
                } else if (item.tipo === 'projeto') {
                  url = '/modules/painel.php?mod=projeto_detalhe&id=' + item.id;
                  icon = 'bi-kanban';
                } else if (item.tipo === 'tarefa') {
                  url = '/modules/painel.php?mod=projeto_detalhe&id=' + item.projeto_id;
                  icon = 'bi-check2-square';
                }

                return `
                  <a href="${url}"
                     class="list-group-item list-group-item-action d-flex align-items-start gap-2">
                    <div class="mt-2"><i class="${icon}"></i></div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold small">${item.titulo || ''}</div>
                      <div class="small text-muted">${item.subtitulo || ''}</div>
                    </div>
                  </a>`;
              })
              .join('');

            boxResultados.style.display = 'block';
          })
          .catch(() => {
            boxResultados.style.display = 'none';
          });
      }, 300);
    });

    document.addEventListener('click', (e) => {
      if (!boxResultados.contains(e.target) && e.target !== inputBusca) {
        boxResultados.style.display = 'none';
      }
    });
  }

  // ======================================
  // IDs fixos dos estágios (pipeline CRM)
  // ======================================
  const ID_ESTAGIO_GANHO = 3; // ajuste para os IDs reais
  const ID_ESTAGIO_PERDIDO = 4;

  document.addEventListener('click', async function (e) {
    // GANHAR
    const btnGanha = e.target.closest('.btn-op-ganha');
    if (btnGanha) {
      const id = btnGanha.getAttribute('data-id');
      if (!id) return;

      const formData = new FormData();
      formData.append('id', id);
      formData.append('estagio_ganho_id', ID_ESTAGIO_GANHO);

      await fetch('/app/Controllers/PipelineController.php?acao=marcar_ganha', {
        method: 'POST',
        body: formData,
      });

      location.reload();
      return;
    }

    // PERDER
    const btnPerder = e.target.closest('.btn-op-perder');
    if (btnPerder) {
      const id = btnPerder.getAttribute('data-id');
      if (!id) return;

      const motivo = prompt('Motivo da perda (opcional):', '');
      const formData = new FormData();
      formData.append('id', id);
      formData.append('estagio_perdido_id', ID_ESTAGIO_PERDIDO);
      formData.append('motivo_perda', motivo || '');

      await fetch('/app/Controllers/PipelineController.php?acao=marcar_perdida', {
        method: 'POST',
        body: formData,
      });

      location.reload();
      return;
    }
  });


  // Ajuste automático do nome do cliente em uma linha
  const clientNames = document.querySelectorAll('.card-cliente-nome');
    clientNames.forEach(el => {
    const maxWidth = el.offsetWidth || el.parentElement.offsetWidth;
    let size = parseFloat(getComputedStyle(el).fontSize);

    while (el.scrollWidth > maxWidth && size > 10) {
      size -= 1;
      el.style.fontSize = size + 'px';
    }
  });


  // Helpers globais ao carregar 
  initSensitiveToggle();
  initThemeCssPicker();

  if (document.getElementById('formOrcamento')) {
    initModalOrcamento();
  }

  // inicializa o kanban (pipeline CRM) na primeira carga
  initKanbanDragDrop();
  initConfirmModal();

  // inicializa os Charts Financeiros na primeira carga
  initFinanceiroCharts();
});


// ======================================
// TOGGLE CAMPOS SENSÍVEIS
// ======================================
function initSensitiveToggle() {
  const toggleBtn = document.getElementById('toggleSensitive');
  const sensitiveEls = document.querySelectorAll('.sensitive-value');

  if (!toggleBtn || !sensitiveEls.length) return;

  const icon = toggleBtn.querySelector('i');
  let hidden = false;

  sensitiveEls.forEach(el => {
    if (!el.dataset.real) {
      el.dataset.real = el.textContent.trim();
    }
  });

  toggleBtn.addEventListener('click', () => {
    hidden = !hidden;

    sensitiveEls.forEach((el) => {
      if (hidden) {
        if (!el.dataset.real) {
          el.dataset.real = el.textContent.trim();
        }
        el.textContent = '•••';
        el.classList.add('sensitive-hidden');
      } else {
        const real = el.dataset.real || '';
        el.textContent = real;
        el.classList.remove('sensitive-hidden');
      }
    });

    if (icon) {
      icon.className = hidden ? 'ri-eye-line' : 'ri-eye-off-line';
    }
  });
}

// ======================================
// THEME CSS SWITCHER / PICKER
// ======================================
function applyThemeFromStorage() {
  const linkDark = document.getElementById('theme-dark');
  const linkLight = document.getElementById('theme-claro');
  const linkModern = document.getElementById('theme-modern');
  if (!linkDark || !linkLight || !linkModern) return;

  const theme = localStorage.getItem('sv-theme-css') || 'dark';

  linkDark.disabled = true;
  linkLight.disabled = true;
  linkModern.disabled = true;

  if (theme === 'light') {
    linkLight.disabled = false;
  } else if (theme === 'modern') {
    linkModern.disabled = false;
  } else {
    linkDark.disabled = false;
  }
}

function initThemeCssPicker() {
  const options = document.querySelectorAll('.theme-option');
  if (!options.length) return;

  const linkDark = document.getElementById('theme-dark');
  const linkLight = document.getElementById('theme-claro');
  const linkModern = document.getElementById('theme-modern');
  if (!linkDark || !linkLight || !linkModern) return;

  let currentTheme = localStorage.getItem('sv-theme-css') || 'dark';

  options.forEach((btn) => {
    const t = btn.getAttribute('data-theme');
    btn.classList.toggle('is-active', t === currentTheme);
  });

  options.forEach((btn) => {
    btn.addEventListener('click', () => {
      const selected = btn.getAttribute('data-theme');
      if (!selected || selected === currentTheme) return;

      currentTheme = selected;
      localStorage.setItem('sv-theme-css', selected);

      linkDark.disabled = true;
      linkLight.disabled = true;
      linkModern.disabled = true;

      if (selected === 'light') {
        linkLight.disabled = false;
      } else if (selected === 'modern') {
        linkModern.disabled = false;
      } else {
        linkDark.disabled = false;
      }

      options.forEach((b) => {
        const t = b.getAttribute('data-theme');
        b.classList.toggle('is-active', t === selected);
      });
    });
  });
}

// ======================================
// KANBAN: DRAG & DROP (PIPELINE CRM)
// ======================================
let kanbanDragInitialized = false;

function initKanbanDragDrop() {
  const pipelineColumns = document.querySelectorAll('.pipeline-column-body');
  const projetoColumns = document.querySelectorAll('.kanban-column-body');

  if (!pipelineColumns.length && !projetoColumns.length) return;

  

  let draggedCard = null;

  // registra eventos globais apenas uma vez
  if (!kanbanDragInitialized) {
    kanbanDragInitialized = true;

    document.addEventListener('dragstart', function (e) {
      const card = e.target.closest('[data-id][draggable="true"]');
      if (!card) return;
      draggedCard = card;
      if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
      
    });

    document.addEventListener('dragend', function () {
      
      draggedCard = null;
    });
  }

  // FUNIL – mover oportunidades
  pipelineColumns.forEach(col => {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });

    col.addEventListener('drop', async function (e) {
      e.preventDefault();
      if (!draggedCard) return;

      this.appendChild(draggedCard);

      const cardId = draggedCard.getAttribute('data-id');
      const columnWrap = this.closest('.pipeline-column');
      const estagioId = columnWrap.getAttribute('data-estagio-id');

      

      try {
        const formData = new FormData();
        formData.append('id', cardId);
        formData.append('funil_estagio_id', estagioId);

        await fetch('/app/Controllers/PipelineController.php?acao=mover', {
          method: 'POST',
          body: formData,
        });
      } catch (err) {
        console.error('Erro ao mover oportunidade', err);
      }
    });
  });

  // PROJETO – mover tarefas
  projetoColumns.forEach(col => {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });

    col.addEventListener('drop', async function (e) {
      e.preventDefault();
      if (!draggedCard) return;

      this.appendChild(draggedCard);

      const cardId = draggedCard.getAttribute('data-id');
      const colunaSlug = this.getAttribute('data-coluna');

      

      try {
        const formData = new FormData();
        formData.append('tarefa_id', cardId);
        formData.append('coluna', colunaSlug);

        await fetch('/app/Controllers/ProjetoController.php?acao=moverTarefa', {
          method: 'POST',
          body: formData,
        });
      } catch (err) {
        console.error('Erro ao mover tarefa', err);
      }
    });
  });
}




// ======================================
// ORÇAMENTO – MODAL E ITENS
// ======================================
const ORC_SERVICOS_DESCR = {
  landing_page: 'Landing Page completa, responsiva, otimizada para conversão.',
  configuracao: 'Configuração técnica (domínio, hospedagem, hospedagem, DNS etc).',
  stream_overlay: 'Pacote de overlays para stream (cenas, alerts, painéis).',
  criativos: 'Criação de peças criativas para campanhas de tráfego pago.',
  identidade_visual: 'Identidade visual completa (logo, paleta, tipografia).'
};

function initModalOrcamento() {
  const form = document.getElementById('formOrcamento');
  if (!form) {
    console.warn('initModalOrcamento: formOrcamento não encontrado');
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

  if (!btnAddItem || !itensContainer || !totalSpan || !totalInput || !servicoSelect || !descServico) {
    console.warn('initModalOrcamento: elementos do modal não encontrados');
    return;
  }

  let itemIndex = 0;

  if (modalEl) {
    modalEl.addEventListener('show.bs.modal', async (event) => {
      const button = event.relatedTarget;
      const id = button ? button.getAttribute('data-id') : null;

      if (!id) {
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
        <label class="form-label">Descrição do item</label>
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

    if (typeof formatMoney === 'function') {
      ['input', 'blur'].forEach(evt => {
        valorInput.addEventListener(evt, () => {
          formatMoney(valorInput);
          calcularTotal();
        });
      });

      if (valorInput.value) {
        formatMoney(valorInput);
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

    document.querySelectorAll('.orc-item-valor').forEach(inp => {
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
      const res = await fetch('/app/Controllers/OrcamentoController.php?acao=buscar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: 'id=' + encodeURIComponent(id)
      });

      const data = await res.json();
      if (!data.success) {
        alert(data.message || 'Erro ao carregar orçamento');
        return;
      }

      const o = data.orcamento;
      const itens = data.itens || [];

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
        itens.forEach(item => {
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
      alert('Erro ao carregar dados do orçamento');
    }
  }
}


function initConfirmModal() {
  const modalEl = document.getElementById('modalConfirmarAcao');
  const msgEl = document.getElementById('modalConfirmarMensagem');
  const btnOk = document.getElementById('modalConfirmarBtnOk');

  if (!modalEl || !window.bootstrap) {
    console.warn('initConfirmModal: modal ou bootstrap não encontrado');
    return;
  }

  

  let currentForm = null;

  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.classList || !form.classList.contains('js-confirm-delete')) return;

    

    e.preventDefault();
    currentForm = form;

    const msg = form.getAttribute('data-confirm-msg') || 'Confirmar exclusão?';
    if (msgEl) msgEl.textContent = msg;

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();
    modalEl.dataset.currentFormId = ''; // só para garantir que não usa evento interno
  });

  if (btnOk) {
    btnOk.addEventListener('click', function () {
      if (!currentForm) return;
      const formToSubmit = currentForm;
      currentForm = null;
      const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
      modalInstance.hide();
      formToSubmit.submit();
    });
  }
}



function initFinanceiroCharts() {
    // PIE
  const elPie = document.getElementById('chartSaidasTipo');
  if (elPie && window.dadosSaidasTipo && dadosSaidasTipo.labels.length) {

    // se já existir um chart nesse canvas, destrói primeiro
    if (elPie._chartInstance) {
      elPie._chartInstance.destroy();
    }

    const chart = new Chart(elPie, {
      type: 'pie',
      data: {
        labels: dadosSaidasTipo.labels,
        datasets: [{
          data: dadosSaidasTipo.valores,
          backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label(ctx) {
                const v = ctx.parsed || 0;
                const total = ctx.chart._metasets[0].total || 1;
                const pct = (v * 100 / total).toFixed(1);
                return `${ctx.label}: R$ ${v.toLocaleString('pt-BR',{ minimumFractionDigits: 2 })} (${pct}%)`;
              }
            }
          }
        }
      }
    });

    // guarda referência para reutilizar
    elPie._chartInstance = chart;

    const legendEl = document.getElementById('legendSaidasTipo');
    if (legendEl) {
      const items = chart.data.labels.map((label, i) => {
        const value = chart.data.datasets[0].data[i] || 0;
        const color = chart.data.datasets[0].backgroundColor[i];
        return `
          <div class="d-flex align-items-center mb-1 small">
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:${color};margin-right:6px;"></span>
            <span class="me-1">${label}:</span>
            <strong>R$ ${Number(value).toLocaleString('pt-BR',{ minimumFractionDigits: 2 })}</strong>
          </div>`;
      }).join('');
      legendEl.innerHTML = items;
    }
  }

  // BAR anual
  const elBar = document.getElementById('chartAno');
  if (elBar && window.anoLabels && window.anoEntradas && window.anoSaidas) {

    if (elBar._chartInstance) {
      elBar._chartInstance.destroy();
    }

    const barChart = new Chart(elBar, {
      type: 'bar',
      data: {
        labels: anoLabels,
        datasets: [
          { label: 'Entradas', data: anoEntradas, backgroundColor: '#4CAF50' },
          { label: 'Saídas',   data: anoSaidas,   backgroundColor: '#FF5252' }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
          y: {
            ticks: {
              callback(value) {
                return 'R$ ' + Number(value).toLocaleString('pt-BR');
              }
            }
          }
        }
      }
    });

    elBar._chartInstance = barChart;
  }
}


