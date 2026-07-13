const FLOWDESK_BASE = (window.FLOWDESK_BASE || '').replace(/\/$/, '');

function fdUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${FLOWDESK_BASE}${normalizedPath}`;
}

window.fdUrl = fdUrl;

function fdEnsureFloatingAlertStack() {
  let stack = document.getElementById('fdFloatingAlertStack');

  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'fdFloatingAlertStack';
    stack.className = 'fd-floating-alert-stack';
    stack.setAttribute('aria-live', 'polite');
    stack.setAttribute('aria-atomic', 'true');
    document.body.appendChild(stack);
  }

  return stack;
}

function fdGetAlertTypeFromClass(element, fallback = 'info') {
  const classes = Array.from(element.classList || []);
  const match = classes.find((className) => className.startsWith('alert-'));

  return match ? match.replace('alert-', '') : fallback;
}

function fdShowFloatingAlert(message, type = 'danger', options = {}) {
  const stack = fdEnsureFloatingAlertStack();
  const toast = document.createElement('div');
  const timeout = Number(options.timeout ?? (type === 'danger' ? 9500 : 6500));

  toast.className = `alert alert-${type} fd-floating-alert is-entering`;
  toast.setAttribute('role', 'alert');

  const typeConfig = {
    success: { icon: 'ri-checkbox-circle-fill', title: 'Tudo certo' },
    danger: { icon: 'ri-error-warning-fill', title: 'Ocorreu um erro' },
    error: { icon: 'ri-error-warning-fill', title: 'Ocorreu um erro' },
    warning: { icon: 'ri-alert-fill', title: 'Atenção' },
    info: { icon: 'ri-information-fill', title: 'Informação' }
  };
  const config = typeConfig[type] || typeConfig.info;

  const icon = document.createElement('span');
  icon.className = 'fd-floating-alert-icon';
  icon.innerHTML = `<i class="${config.icon}"></i>`;

  const content = document.createElement('div');
  content.className = 'fd-floating-alert-content';
  const title = document.createElement('strong');
  title.textContent = String(options.title || config.title);
  const copy = document.createElement('span');
  copy.textContent = String(message || 'Nao foi possivel concluir esta acao.');
  content.append(title, copy);

  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'fd-floating-alert-close';
  close.setAttribute('aria-label', 'Fechar mensagem');
  close.innerHTML = '<i class="ri-close-line"></i>';

  const dismiss = () => {
    toast.classList.add('is-leaving');
    window.setTimeout(() => toast.remove(), 220);
  };

  close.addEventListener('click', dismiss);
  toast.append(icon, content, close);
  stack.appendChild(toast);

  window.requestAnimationFrame(() => {
    toast.classList.remove('is-entering');
  });

  if (timeout > 0) {
    window.setTimeout(dismiss, timeout);
  }

  return toast;
}

function fdPromotePageAlertsToFloating() {
  const alerts = Array.from(document.querySelectorAll('main .alert[role="alert"]'));

  alerts.forEach((alert) => {
    if (alert.closest('.modal') || alert.closest('.fd-floating-alert-stack') || alert.dataset.fdFloatingProcessed === '1') {
      return;
    }

    const type = fdGetAlertTypeFromClass(alert, 'info');
    const text = alert.textContent.trim();

    if (!text) {
      return;
    }

    alert.dataset.fdFloatingProcessed = '1';
    fdShowFloatingAlert(text, type);
    alert.remove();
  });
}

function fdObservePageAlerts() {
  const main = document.querySelector('main');

  if (!main || window.__flowdeskFloatingAlertsObserver) {
    return;
  }

  window.__flowdeskFloatingAlertsObserver = new MutationObserver(() => {
    fdPromotePageAlertsToFloating();
  });
  window.__flowdeskFloatingAlertsObserver.observe(main, {
    childList: true,
    subtree: true
  });
}

window.fdShowFloatingAlert = fdShowFloatingAlert;
window.fdPromotePageAlertsToFloating = fdPromotePageAlertsToFloating;

function fdShowInlineAlert(message, type = 'danger', options = {}) {
  if (!options.inline) {
    fdShowFloatingAlert(message, type, options);
    return;
  }

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

document.addEventListener('DOMContentLoaded', () => {
  fdPromotePageAlertsToFloating();
  fdObservePageAlerts();

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

  const addDaysToDate = (value, days) => {
    if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return '';
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    if (Number.isNaN(date.getTime())) return '';
    date.setDate(date.getDate() + days);
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0'),
    ].join('-');
  };

  const setDatePickerValue = (input, value) => {
    if (!input || !value) return;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  };

  document.querySelectorAll('[data-project-start-input]').forEach((startInput) => {
    const deliveryInput = document.getElementById(startInput.dataset.projectDeliveryTarget || '');
    if (!deliveryInput) return;

    const syncDelivery = () => {
      const defaultDelivery = addDaysToDate(startInput.value, 3);
      setDatePickerValue(deliveryInput, defaultDelivery);
    };

    if (startInput.value && !deliveryInput.value) {
      syncDelivery();
    }

    startInput.addEventListener('change', syncDelivery);
    startInput.addEventListener('input', syncDelivery);
  });

  const syncProjectStatusButtons = (scope, value) => {
    const inputId = scope === 'edit' ? 'editStatusProjeto' : 'statusProjeto';
    const hidden = document.getElementById(inputId);
    const group = document.querySelector(`[data-project-status-group="${scope}"]`);
    if (!hidden || !group) return;

    hidden.value = value || 'planejado';
    group.querySelectorAll('[data-project-status-value]').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.projectStatusValue === hidden.value);
    });
  };

  window.syncProjectStatusButtons = syncProjectStatusButtons;

  document.querySelectorAll('[data-project-status-group]').forEach((group) => {
    const scope = group.dataset.projectStatusGroup || 'create';
    const activeButton = group.querySelector('[data-project-status-value].is-active');
    const inputId = scope === 'edit' ? 'editStatusProjeto' : 'statusProjeto';
    const hidden = document.getElementById(inputId);
    syncProjectStatusButtons(scope, hidden?.value || activeButton?.dataset.projectStatusValue || 'planejado');
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-project-status-value]');
    if (!button) return;

    const group = button.closest('[data-project-status-group]');
    if (!group) return;

    event.preventDefault();
    syncProjectStatusButtons(
      group.dataset.projectStatusGroup || 'create',
      button.dataset.projectStatusValue || 'planejado'
    );
  });

  const projectConfirmModalEl = document.getElementById('modalConfirmarProjetoAcao');
  const projectConfirmTitle = document.getElementById('modalConfirmarProjetoTitulo');
  const projectConfirmMessage = document.getElementById('modalConfirmarProjetoMensagem');
  const projectConfirmButton = document.getElementById('modalConfirmarProjetoBotao');
  let pendingProjectConfirmForm = null;

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('.fd-project-confirm-form');
    if (!form) return;

    event.preventDefault();
    pendingProjectConfirmForm = form;

    if (projectConfirmTitle) {
      projectConfirmTitle.textContent = form.dataset.confirmTitle || 'Confirmar Acao';
    }

    if (projectConfirmMessage) {
      projectConfirmMessage.textContent = form.dataset.confirmMessage || 'Deseja continuar com esta acao?';
    }

    if (projectConfirmButton) {
      projectConfirmButton.textContent = form.dataset.confirmButton || 'Confirmar';
    }

    if (!projectConfirmModalEl || !window.bootstrap) {
      pendingProjectConfirmForm = null;
      form.submit();
      return;
    }

    bootstrap.Modal.getOrCreateInstance(projectConfirmModalEl).show();
  });

  if (projectConfirmButton) {
    projectConfirmButton.addEventListener('click', () => {
      if (!pendingProjectConfirmForm) return;

      const form = pendingProjectConfirmForm;
      pendingProjectConfirmForm = null;
      bootstrap.Modal.getInstance(projectConfirmModalEl)?.hide();
      form.submit();
    });
  }

  if (projectConfirmModalEl) {
    projectConfirmModalEl.addEventListener('hidden.bs.modal', () => {
      pendingProjectConfirmForm = null;
    });
  }

  if (typeof window.initTaskRichEditor === 'function') {
    window.initTaskRichEditor();
  }

  if (typeof window.initEditModals === 'function') {
    window.initEditModals();
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

});





// ========================================
// FLOWDESK MOTION SYSTEM
// ========================================
document.addEventListener('DOMContentLoaded', () => {
  const projectSearch = document.querySelector('[data-project-search]');
  if (projectSearch) projectSearch.addEventListener('input', () => {
    const query = projectSearch.value.trim().toLocaleLowerCase('pt-BR');
    document.querySelectorAll('[data-project-row]').forEach((row) => {
      row.hidden = query !== '' && !(row.dataset.searchText || '').includes(query);
    });
  });

  const hostingSearch = document.querySelector('[data-hosting-search]');
  const hostingType = document.querySelector('[data-hosting-type]');
  const hostingStatus = document.querySelector('[data-hosting-status]');
  const filterHostingRows = () => {
    const query = hostingSearch ? hostingSearch.value.trim().toLocaleLowerCase('pt-BR') : '';
    document.querySelectorAll('[data-hosting-row]').forEach((row) => {
      const matchesQuery = query === '' || (row.dataset.searchText || '').includes(query);
      const matchesType = !hostingType || hostingType.value === '' || row.dataset.type === hostingType.value;
      const matchesStatus = !hostingStatus || hostingStatus.value === '' || row.dataset.status === hostingStatus.value;
      row.hidden = !(matchesQuery && matchesType && matchesStatus);
    });
  };
  [hostingSearch, hostingType, hostingStatus].filter(Boolean).forEach((control) => control.addEventListener(control.tagName === 'SELECT' ? 'change' : 'input', filterHostingRows));

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
