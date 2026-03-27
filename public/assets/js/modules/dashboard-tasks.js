function initDashboardTasks() {
  const checkboxes = document.querySelectorAll('.js-dashboard-task-advance');
  const buildUrl = window.fdUrl;
  const showInlineAlert = window.fdShowInlineAlert;

  if (!checkboxes.length || !buildUrl) {
    return;
  }

  const statusMap = {
    backlog: { label: 'Pendente', className: 'fd-badge-warning' },
    andamento: { label: 'Em andamento', className: 'fd-badge-info' },
    revisao: { label: 'Em revisao', className: 'fd-badge-neutral' },
    concluido: { label: 'Concluida', className: 'fd-badge-success' }
  };

  const nextMap = {
    backlog: 'andamento',
    andamento: 'revisao',
    revisao: 'concluido',
    concluido: ''
  };

  function showError(message) {
    if (typeof showInlineAlert === 'function') {
      showInlineAlert(message, 'danger', {
        containerId: 'dashboardFeedback',
        contextSelector: '.fd-dashboard',
        replace: true,
        scroll: true
      });
      return;
    }

    console.error(message);
  }

  function maybeRenderEmptyState(tbody) {
    if (!tbody) return;

    const taskRows = tbody.querySelectorAll('.fd-dashboard-task-row');
    if (taskRows.length > 0) return;

    const row = document.createElement('tr');
    row.innerHTML = '<td colspan="5" class="fd-empty-state">Nenhuma task ativa para os projetos.</td>';
    tbody.appendChild(row);
  }

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', async () => {
      if (!checkbox.checked) {
        return;
      }

      const tarefaId = checkbox.dataset.id;
      const currentColuna = checkbox.dataset.currentColuna || '';
      const nextColuna = checkbox.dataset.nextColuna || '';
      const row = checkbox.closest('.fd-dashboard-task-row');
      const tbody = row?.closest('tbody');
      const statusFilter = tbody?.dataset.statusFilter || 'todos';

      if (!tarefaId || !nextColuna) {
        checkbox.checked = currentColuna === 'concluido';
        return;
      }

      checkbox.disabled = true;
      row?.classList.add('is-progressing');

      try {
        const formData = new FormData();
        formData.append('tarefa_id', tarefaId);
        formData.append('coluna', nextColuna);

        const response = await fetch(buildUrl('/projetos/tarefas/mover'), {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          throw new Error('Nao foi possivel avancar esta task agora.');
        }

        checkbox.dataset.currentColuna = nextColuna;
        checkbox.dataset.nextColuna = nextMap[nextColuna] || '';
        row?.setAttribute('data-coluna', nextColuna);

        const badge = row?.querySelector('.fd-task-status-cell .fd-badge');
        const nextStatus = statusMap[nextColuna];
        if (badge && nextStatus) {
          badge.className = `fd-badge ${nextStatus.className}`;
          badge.textContent = nextStatus.label;
        }

        const shouldRemove =
          statusFilter === 'pendente' && nextColuna !== 'backlog' ||
          statusFilter === 'andamento' && nextColuna !== 'andamento' ||
          statusFilter === 'concluida' && nextColuna !== 'concluido';

        if (shouldRemove && row) {
          row.classList.add('is-leaving');
          window.setTimeout(() => {
            row.remove();
            maybeRenderEmptyState(tbody);
          }, 220);
          return;
        }

        if (nextColuna === 'concluido') {
          checkbox.checked = true;
          checkbox.disabled = true;
          row?.classList.add('is-complete');
        } else {
          checkbox.checked = false;
          checkbox.disabled = false;
        }
      } catch (error) {
        console.error(error);
        checkbox.checked = false;
        checkbox.disabled = false;
        row?.classList.remove('is-progressing');
        showError(error?.message || 'Nao foi possivel avancar esta task agora.');
        return;
      }

      row?.classList.remove('is-progressing');
    });
  });
}

window.initDashboardTasks = initDashboardTasks;
