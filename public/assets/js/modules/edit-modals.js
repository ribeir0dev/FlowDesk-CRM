function initEditModals() {
  const buildUrl = window.fdUrl;

  initEditarTarefaModal();
  initExcluirTarefaModal();
  initEditarProjetoModal(buildUrl);
  initEditarOportunidadeModal(buildUrl);
}

function setFieldValue(element, value) {
  if (!element) return;
  element.value = value;
  element.dispatchEvent(new Event('input', { bubbles: true }));
  element.dispatchEvent(new Event('change', { bubbles: true }));
}

function initEditarTarefaModal() {
  const buildUrl = window.fdUrl;
  const colEditH = document.getElementById('editTarefaColuna');
  const modalEdit = document.getElementById('modalEditarTarefa');
  const modalNova = document.getElementById('modalNovaTarefa');
  const colCreateH = document.getElementById('tarefaColuna');
  const formEdit = document.getElementById('form-editar-tarefa');
  const autosaveStatus = document.getElementById('editTarefaAutosaveStatus');
  let autosaveTimer = null;
  let autosaveHydrating = false;

  const getTaskCard = () => {
    const taskId = Number(document.getElementById('editTarefaId')?.value || 0);
    if (!taskId) return null;
    return document.querySelector(`.fd-project-task-card[data-id="${taskId}"]`);
  };

  const priorityBadgeMap = {
    baixa: { label: 'Baixa', className: 'fd-badge-neutral' },
    media: { label: 'Media', className: 'fd-badge-info' },
    alta: { label: 'Alta', className: 'fd-badge-warning' },
    urgente: { label: 'Urgente', className: 'fd-badge-danger' }
  };

  const syncTaskCardPreview = () => {
    const card = getTaskCard();
    if (!card) return;

    const title = document.getElementById('editTarefaTitulo')?.value?.trim() || 'Sem titulo';
    const titleEl = card.querySelector('[data-task-title]');
    if (titleEl) titleEl.textContent = title;

    const priority = String(document.getElementById('editTarefaPrioridade')?.value || 'media');
    const priorityMeta = priorityBadgeMap[priority] || priorityBadgeMap.media;
    const priorityBadge = card.querySelector('[data-task-priority-badge]');
    if (priorityBadge) {
      priorityBadge.className = `fd-badge ${priorityMeta.className}`;
      priorityBadge.textContent = priorityMeta.label;
    }

    const checklistRaw = document.getElementById('editTarefaChecklistJson')?.value || '[]';
    let checklist = [];
    try {
      checklist = JSON.parse(checklistRaw);
    } catch {
      checklist = [];
    }
    checklist = Array.isArray(checklist) ? checklist : [];
    const total = checklist.length;
    const done = checklist.filter((item) => item && item.concluido).length;
    let checklistBadge = card.querySelector('[data-task-checklist-badge]');

    if (total > 0) {
      if (!checklistBadge) {
        const meta = card.querySelector('.fd-project-task-meta');
        if (meta) {
          checklistBadge = document.createElement('span');
          checklistBadge.className = 'fd-task-tag fd-task-tag-checklist';
          checklistBadge.setAttribute('data-task-checklist-badge', '');
          checklistBadge.innerHTML = '<i class="ri-list-check-3"></i> <span data-task-checklist-text></span>';
          meta.appendChild(checklistBadge);
        }
      }
      const textEl = checklistBadge?.querySelector('[data-task-checklist-text]');
      if (textEl) textEl.textContent = `${done}/${total}`;
    } else if (checklistBadge) {
      checklistBadge.remove();
    }

    const copyEl = card.querySelector('.fd-project-task-copy');
    if (copyEl) copyEl.remove();
  };

  const setAutosaveStatus = (status) => {
    if (!autosaveStatus) return;
    autosaveStatus.dataset.state = status;
    autosaveStatus.textContent = status === 'saving'
      ? 'Salvando...'
      : status === 'error'
        ? 'Falha ao salvar'
        : 'Salvo';
  };

  const queueAutosave = () => {
    if (!formEdit || autosaveHydrating) return;
    const taskId = Number(document.getElementById('editTarefaId')?.value || 0);
    if (!taskId || !buildUrl) return;

    window.clearTimeout(autosaveTimer);
    setAutosaveStatus('saving');
    autosaveTimer = window.setTimeout(async () => {
      try {
        const formData = new FormData(formEdit);
        const response = await fetch(buildUrl('/projetos/tarefas/autosave'), {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        if (!response.ok || !data?.ok) {
          throw new Error(data?.message || 'Nao foi possivel salvar as alteracoes.');
        }
        syncTaskCardPreview();
        setAutosaveStatus('saved');
      } catch (error) {
        setAutosaveStatus('error');
        if (typeof window.fdShowInlineAlert === 'function') {
          window.fdShowInlineAlert(error.message || 'Nao foi possivel salvar as alteracoes.', 'danger', {
            contextSelector: '#modalEditarTarefa .modal-body',
            replace: false,
            scroll: false
          });
        }
      }
    }, 700);
  };

  if (modalNova) {
    modalNova.addEventListener('show.bs.modal', () => {
      setFieldValue(document.getElementById('tarefaId'), '');
      setFieldValue(document.getElementById('tarefaTitulo'), '');
      if (typeof window.fdSetTaskPriority === 'function') {
        window.fdSetTaskPriority('create', 'media');
      } else {
        setFieldValue(document.getElementById('tarefaPrioridade'), 'media');
      }
      if (colCreateH) colCreateH.value = 'backlog';
      if (typeof window.fdSetTaskColumn === 'function') {
        window.fdSetTaskColumn('create', 'backlog');
      }
      setFieldValue(document.getElementById('tarefaDataEntrega'), '');
      if (typeof window.fdSetTaskEditorContent === 'function') {
        window.fdSetTaskEditorContent('create', '');
      }
      if (typeof window.fdSetTaskChecklist === 'function') {
        window.fdSetTaskChecklist('create', []);
      }
      if (typeof window.fdSetTaskMembers === 'function') {
        window.fdSetTaskMembers('create', []);
      }
      if (typeof window.fdSetTaskAttachments === 'function') {
        window.fdSetTaskAttachments('create', []);
      }
      if (typeof window.fdSetTaskContext === 'function') {
        window.fdSetTaskContext('create', { taskId: null });
      }
    });
  }

  if (modalEdit) {
    modalEdit.addEventListener('show.bs.modal', async (event) => {
      autosaveHydrating = true;
      setAutosaveStatus('saved');
      const button = event.relatedTarget;
      if (!button) return;

      const tarefaId = button.getAttribute('data-id');
      const coluna = button.getAttribute('data-coluna') || 'backlog';
      const titulo = button.getAttribute('data-titulo') || '';

      document.getElementById('editTarefaId').value = tarefaId;
      document.getElementById('editTarefaTitulo').value = titulo;
      if (colEditH) colEditH.value = coluna;
      if (typeof window.fdSetTaskColumn === 'function') {
        window.fdSetTaskColumn('edit', coluna);
      }

      if (typeof window.fdSetTaskPriority === 'function') {
        window.fdSetTaskPriority('edit', 'media');
      } else {
        setFieldValue(document.getElementById('editTarefaPrioridade'), 'media');
      }
      setFieldValue(document.getElementById('editTarefaDataEntrega'), '');

      if (typeof window.fdSetTaskEditorContent === 'function') {
        window.fdSetTaskEditorContent('edit', '');
      }
      if (typeof window.fdSetTaskChecklist === 'function') {
        window.fdSetTaskChecklist('edit', []);
      }
      if (typeof window.fdSetTaskMembers === 'function') {
        window.fdSetTaskMembers('edit', []);
      }
      if (typeof window.fdSetTaskAttachments === 'function') {
        window.fdSetTaskAttachments('edit', []);
      }
      if (typeof window.fdSetTaskComments === 'function') {
        window.fdSetTaskComments([]);
      }
      if (typeof window.fdSetTaskContext === 'function') {
        window.fdSetTaskContext('edit', { taskId: tarefaId || null });
      }

      if (!buildUrl || !tarefaId) return;

      try {
        const response = await fetch(buildUrl('/projetos/tarefas/buscar?id=' + encodeURIComponent(tarefaId)));
        if (!response.ok) {
          throw new Error('Falha ao buscar tarefa');
        }

        const data = await response.json();
        if (!data) return;

        setFieldValue(document.getElementById('editTarefaTitulo'), data.titulo || '');
        if (typeof window.fdSetTaskPriority === 'function') {
          window.fdSetTaskPriority('edit', data.prioridade || 'media');
        } else {
          setFieldValue(document.getElementById('editTarefaPrioridade'), data.prioridade || 'media');
        }
        if (colEditH) colEditH.value = data.coluna || coluna;
        if (typeof window.fdSetTaskColumn === 'function') {
          window.fdSetTaskColumn('edit', data.coluna || coluna);
        }
        setFieldValue(document.getElementById('editTarefaDataEntrega'), data.data_entrega || '');

        if (typeof window.fdSetTaskEditorContent === 'function') {
          window.fdSetTaskEditorContent('edit', data.descricao || '');
        }
        if (typeof window.fdSetTaskChecklist === 'function') {
          window.fdSetTaskChecklist('edit', Array.isArray(data.checklist) ? data.checklist : []);
        }
        if (typeof window.fdSetTaskMembers === 'function') {
          window.fdSetTaskMembers('edit', Array.isArray(data.members) ? data.members : []);
        }
        if (typeof window.fdSetTaskAttachments === 'function') {
          window.fdSetTaskAttachments('edit', Array.isArray(data.attachments) ? data.attachments : []);
        }
        if (typeof window.fdSetTaskComments === 'function') {
          window.fdSetTaskComments(Array.isArray(data.comments) ? data.comments : []);
        }
        syncTaskCardPreview();
      } catch (err) {
        console.error('Erro ao carregar tarefa', err);
      } finally {
        window.setTimeout(() => {
          autosaveHydrating = false;
          setAutosaveStatus('saved');
        }, 80);
      }
    });

    modalEdit.addEventListener('hidden.bs.modal', () => {
      window.clearTimeout(autosaveTimer);
      autosaveHydrating = false;
      setAutosaveStatus('saved');
    });
  }

  formEdit?.addEventListener('submit', (event) => {
    event.preventDefault();
  });

  formEdit?.addEventListener('input', queueAutosave);
  formEdit?.addEventListener('change', queueAutosave);
  document.addEventListener('fd:task-edit-changed', queueAutosave);
}

function initExcluirTarefaModal() {
  const modal = document.getElementById('modalExcluirTarefa');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!button) return;

    const tarefaId = button.getAttribute('data-tarefa-id') || '';
    const projetoId = button.getAttribute('data-projeto-id') || '';
    const titulo = button.getAttribute('data-tarefa-titulo') || 'esta tarefa';

    const tarefaInput = document.getElementById('deleteTarefaId');
    const projetoInput = document.getElementById('deleteTarefaProjetoId');
    const nomeEl = document.getElementById('deleteTarefaNome');

    if (tarefaInput) tarefaInput.value = tarefaId;
    if (projetoInput) projetoInput.value = projetoId;
    if (nomeEl) nomeEl.textContent = titulo;
  });
}

function initEditarProjetoModal(buildUrl) {
  const modalEditarProjeto = document.getElementById('modalEditarProjeto');
  if (!modalEditarProjeto || !buildUrl) return;

  modalEditarProjeto.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!button) return;

    const id = button.getAttribute('data-id');
    const fillProjetoForm = (data = {}) => {
      setFieldValue(document.getElementById('editProjetoId'), id || '');
      setFieldValue(document.getElementById('editNomeProjeto'), data.nome_projeto || '');
      setFieldValue(document.getElementById('editTipoProjeto'), data.tipo_projeto || 'outro');
      setFieldValue(document.getElementById('editClienteProjeto'), data.cliente_id || '');
      setFieldValue(document.getElementById('editDataInicioProjeto'), data.data_inicio || '');
      setFieldValue(document.getElementById('editDataEntregaProjeto'), data.data_entrega || '');
      setFieldValue(document.getElementById('editStatusProjeto'), data.status || 'planejado');
      setFieldValue(document.getElementById('editDescricaoProjeto'), data.descricao || '');
      if (typeof window.syncProjectStatusButtons === 'function') {
        window.syncProjectStatusButtons('edit', data.status || 'planejado');
      }
    };

    fillProjetoForm({
      nome_projeto: button.getAttribute('data-nome') || '',
      tipo_projeto: button.getAttribute('data-tipo') || 'outro',
      cliente_id: button.getAttribute('data-cliente-id') || '',
      status: button.getAttribute('data-status') || 'planejado',
      data_inicio: button.getAttribute('data-data-inicio') || '',
      data_entrega: button.getAttribute('data-data-entrega') || '',
      descricao: button.getAttribute('data-descricao') || '',
    });

    if (!id) return;

    fetch(buildUrl('/projetos/buscar?id=' + encodeURIComponent(id)))
      .then(async (r) => {
        if (!r.ok) {
          throw new Error('Falha ao buscar projeto');
        }
        return r.json();
      })
      .then((data) => {
        if (!data) return;
        fillProjetoForm(data);
      })
      .catch((err) => {
        console.error('Erro ao carregar projeto', err);
      });
  });
}

function initEditarOportunidadeModal(buildUrl) {
  const modalEditarOportunidade = document.getElementById('modalEditarOportunidade');
  if (!modalEditarOportunidade || !buildUrl) return;

  modalEditarOportunidade.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const id = button ? button.getAttribute('data-id') : null;
    if (!id) return;

    const form = modalEditarOportunidade.querySelector('form');
    if (!form) return;

    form.querySelector('[name="id"]').value = id;

    const fillOportunidadeForm = (data = {}) => {
      setFieldValue(form.querySelector('[name="titulo"]'), data.titulo || '');
      setFieldValue(form.querySelector('[name="cliente_id"]'), data.cliente_id || '');
      setFieldValue(form.querySelector('[name="funil_estagio_id"]'), data.funil_estagio_id || '');
      setFieldValue(form.querySelector('[name="valor_previsto"]'), data.valor_previsto || '');
      setFieldValue(form.querySelector('[name="probabilidade"]'), data.probabilidade || 0);
      setFieldValue(form.querySelector('[name="origem_lead"]'), data.origem_lead || '');
      setFieldValue(form.querySelector('[name="responsavel"]'), data.responsavel || '');
      setFieldValue(form.querySelector('[name="data_prevista_fechamento"]'), data.data_prevista_fechamento || '');
      setFieldValue(form.querySelector('[name="observacoes"]'), data.observacoes || '');
    };

    fillOportunidadeForm({
      titulo: button.getAttribute('data-titulo') || '',
      cliente_id: button.getAttribute('data-cliente-id') || '',
      funil_estagio_id: button.getAttribute('data-funil-estagio-id') || '',
      valor_previsto: button.getAttribute('data-valor-previsto') || '',
      probabilidade: button.getAttribute('data-probabilidade') || 0,
      origem_lead: button.getAttribute('data-origem-lead') || '',
      responsavel: button.getAttribute('data-responsavel') || '',
      data_prevista_fechamento: button.getAttribute('data-data-prevista-fechamento') || '',
      observacoes: button.getAttribute('data-observacoes') || '',
    });

    try {
      const resp = await fetch(buildUrl(`/pipeline/buscar?id=${encodeURIComponent(id)}`));
      if (!resp.ok) {
        throw new Error('Falha ao buscar oportunidade');
      }
      const data = await resp.json();
      if (!data || !data.id) return;
      fillOportunidadeForm(data);
    } catch (err) {
      console.error('Erro ao carregar oportunidade', err);
    }
  });
}

window.initEditModals = initEditModals;
