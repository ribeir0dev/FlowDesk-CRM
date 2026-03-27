const FLOWDESK_BASE = (window.FLOWDESK_BASE || '').replace(/\/$/, '');

function fdUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${FLOWDESK_BASE}${normalizedPath}`;
}

window.fdUrl = fdUrl;

function fdShowInlineAlert(message, type = 'danger', options = {}) {
  const {
    containerId = null,
    contextSelector = null,
    replace = true,
    scroll = true
  } = options;

  let container = containerId ? document.getElementById(containerId) : null;

  if (!container && contextSelector) {
    const context = document.querySelector(contextSelector);
    if (context) {
      container = context.querySelector('.fd-page-alerts');

      if (!container) {
        container = document.createElement('div');
        container.className = 'fd-page-alerts';
        context.prepend(container);
      }
    }
  }

  if (!container) {
    const pageHeader = document.querySelector('.fd-page-header');
    if (pageHeader?.parentElement) {
      container = pageHeader.parentElement.querySelector('.fd-page-alerts');

      if (!container) {
        container = document.createElement('div');
        container.className = 'fd-page-alerts';
        pageHeader.insertAdjacentElement('afterend', container);
      }
    }
  }

  if (!container) {
    return;
  }

  if (replace) {
    container.innerHTML = '';
  }

  const alert = document.createElement('div');
  alert.className = `alert alert-${type} mb-3`;
  alert.setAttribute('role', 'alert');
  alert.textContent = message;
  container.appendChild(alert);

  if (scroll) {
    window.requestAnimationFrame(() => {
      alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }
}

window.fdShowInlineAlert = fdShowInlineAlert;

// ========================================
// LOGIN / CRIAR CONTA (TABS)
// ========================================
document.addEventListener('DOMContentLoaded', () => {
  const btnLogin = document.getElementById('btn-login');
  const btnCriar = document.getElementById('btn-criar');
  const formLogin = document.getElementById('form-login');
  const formCriar = document.getElementById('form-criar');

  // aplica tema salvo antes de qualquer coisa visual
  if (typeof window.applyThemeFromStorage === 'function') {
    window.applyThemeFromStorage();
  }

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
        const res = await fetch(fdUrl('/register'), {
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
  const sidebar = document.querySelector('.fd-sidebar');

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

  window.formatMoney = formatMoney;

  valorInputs.forEach((inp) => {
    ['input', 'blur'].forEach((evt) => {
      inp.addEventListener(evt, () => {
        formatMoney(inp);
      });
    });
  });

  // ======================================
  // MASCARA TELEFONE (inputs .js-telefone)
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
    if (!dateStr) return '--/--/----';
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

  if (typeof window.initEditModals === 'function') {
    window.initEditModals();
  }

  if (typeof window.initFinanceiroEntradaModal === 'function') {
    window.initFinanceiroEntradaModal();
  }

  if (typeof window.initGlobalSearch === 'function') {
    window.initGlobalSearch();
  }

  if (typeof window.initPipelineQuickActions === 'function') {
    window.initPipelineQuickActions();
  }

  if (typeof window.initDashboardTasks === 'function') {
    window.initDashboardTasks();
  }

  if (typeof window.initClientNameAutosize === 'function') {
    window.initClientNameAutosize();
  }

  if (typeof window.initSensitiveToggle === 'function') {
    window.initSensitiveToggle();
  }

  if (typeof window.initThemeCssPicker === 'function') {
    window.initThemeCssPicker();
  }

  if (document.getElementById('formOrcamento') && typeof window.initModalOrcamento === 'function') {
    window.initModalOrcamento();
  }

  // inicializa o kanban (pipeline CRM) na primeira carga
  if (typeof window.initKanbanDragDrop === 'function') {
    window.initKanbanDragDrop();
  }
  if (typeof window.initConfirmModal === 'function') {
    window.initConfirmModal();
  }

  // inicializa os Charts Financeiros na primeira carga
  if (typeof window.initFinanceiroCharts === 'function') {
    window.initFinanceiroCharts();
  }
});





// ========================================
// FLOWDESK MOTION SYSTEM
// ========================================
document.addEventListener('DOMContentLoaded', () => {
  const motionTargets = document.querySelectorAll([
    '.fd-page-header',
    '.fd-card',
    '.fd-settings-panel',
    '.fd-settings-sidebar',
    '.fd-codigos-toolbar-card',
    '.fd-codigo-card',
    '.fd-codigo-meta-card',
    '.fd-codigo-copy-section',
    '.fd-hospedagens-table-wrap',
    '.fd-table-wrap'
  ].join(','));

  motionTargets.forEach((element, index) => {
    element.style.setProperty('--fd-stagger-index', String(index));
  });

  window.requestAnimationFrame(() => {
    document.documentElement.classList.add('fd-motion-ready');
  });
});
