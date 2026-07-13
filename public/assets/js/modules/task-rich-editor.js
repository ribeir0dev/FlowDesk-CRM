function initTaskRichEditor() {
  if (typeof window.Quill === 'undefined') {
    console.warn('initTaskRichEditor: Quill nao encontrado');
    return;
  }

  const buildUrl = window.fdUrl;
  const editors = {};
  const state = {
    checklist: { create: [], edit: [] },
    members: { create: [], edit: [] },
    attachments: { create: [], edit: [] },
    comments: { edit: [] },
    taskIds: { create: null, edit: null },
    draggingChecklist: { create: null, edit: null },
    column: { create: 'backlog', edit: 'backlog' },
    commentEditingId: null,
    commentDeletingId: null
  };

  function notifyTaskEditChanged() {
    document.dispatchEvent(new CustomEvent('fd:task-edit-changed'));
  }

  const priorityMeta = {
    baixa: { label: 'Baixa', badgeClass: 'fd-badge-neutral' },
    media: { label: 'Media', badgeClass: 'fd-badge-info' },
    alta: { label: 'Alta', badgeClass: 'fd-badge-warning' },
    urgente: { label: 'Urgente', badgeClass: 'fd-badge-danger' }
  };

  function normalizeHtml(html) {
    const clean = (html || '').trim();
    if (clean === '' || clean === '<p><br></p>') {
      return '';
    }
    return clean;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function prepareHtml(html) {
    const clean = normalizeHtml(html);
    if (clean === '') {
      return '<p><br></p>';
    }

    const looksLikeHtml = /<\/?[a-z][\s\S]*>/i.test(clean);
    if (looksLikeHtml) {
      return clean;
    }

    return `<p>${escapeHtml(clean)}</p>`;
  }

  function getPrefix(key) {
    return key === 'create' ? '' : 'edit';
  }

  function getInitials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'FD';
    const first = parts[0].slice(0, 1);
    const last = parts.length > 1 ? parts[parts.length - 1].slice(0, 1) : '';
    return (first + last).toUpperCase();
  }

  function normalizeMediaUrl(url) {
    const value = String(url || '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value)) return value;
    const base = (window.FLOWDESK_BASE || '').replace(/\/$/, '');
    return value.startsWith('/') ? `${base}${value}` : `${base}/${value}`;
  }

  function createEditor(key, editorSelector, toolbarSelector, hiddenInputSelector, placeholder) {
    const editorElement = document.querySelector(editorSelector);
    const toolbarElement = document.querySelector(toolbarSelector);
    const hiddenInput = document.querySelector(hiddenInputSelector);

    if (!editorElement || !toolbarElement || !hiddenInput) {
      return;
    }

    const quill = new Quill(editorElement, {
      theme: 'snow',
      placeholder,
      modules: { toolbar: toolbarElement }
    });

    const sync = () => {
      hiddenInput.value = normalizeHtml(quill.root.innerHTML);
      if (key === 'edit') {
        notifyTaskEditChanged();
      }
    };

    quill.on('text-change', sync);
    sync();
    editors[key] = { quill, hiddenInput };
  }

  function getChecklistConfig(key) {
    const prefix = getPrefix(key);
    return {
      list: document.getElementById(prefix ? 'editTarefaChecklistList' : 'tarefaChecklistList'),
      input: document.getElementById(prefix ? 'editTarefaChecklistInput' : 'tarefaChecklistInput'),
      hidden: document.getElementById(prefix ? 'editTarefaChecklistJson' : 'tarefaChecklistJson'),
      stats: document.getElementById(prefix ? 'editTarefaChecklistStats' : 'tarefaChecklistStats')
    };
  }

  function syncChecklist(key) {
    const config = getChecklistConfig(key);
    if (config.hidden) {
      config.hidden.value = JSON.stringify(state.checklist[key] || []);
    }
  }

  function renderChecklist(key) {
    const config = getChecklistConfig(key);
    if (!config.list) return;

    const items = state.checklist[key] || [];
    const done = items.filter((item) => item.concluido).length;

    if (config.stats) {
      config.stats.textContent = `${done}/${items.length}`;
    }

    if (!items.length) {
      config.list.innerHTML = '<div class="fd-task-checklist-empty">Nenhum item ainda. Adicione os passos desta tarefa.</div>';
      syncChecklist(key);
      return;
    }

    config.list.innerHTML = items.map((item, index) => `
      <div class="fd-task-checklist-item ${item.concluido ? 'is-done' : ''}" draggable="true" data-checklist-item="${key}" data-index="${index}">
        <button type="button" class="fd-task-checklist-drag" data-checklist-drag="${key}" data-index="${index}" aria-label="Reordenar item">
          <i class="ri-draggable"></i>
        </button>
        <label class="fd-task-checklist-toggle">
          <input type="checkbox" data-checklist-toggle="${key}" data-index="${index}" ${item.concluido ? 'checked' : ''}>
          <span></span>
        </label>
        <div class="fd-task-checklist-copy">
          <strong>${escapeHtml(item.texto)}</strong>
        </div>
        <button type="button" class="fd-task-checklist-remove" data-checklist-remove="${key}" data-index="${index}" aria-label="Remover item">
          <i class="ri-close-line"></i>
        </button>
      </div>
    `).join('');

    syncChecklist(key);
    if (key === 'edit') {
      notifyTaskEditChanged();
    }
  }

  function setChecklist(key, items) {
    state.checklist[key] = Array.isArray(items)
      ? items.map((item) => ({
          texto: String(item?.texto || '').trim(),
          concluido: Boolean(item?.concluido)
        })).filter((item) => item.texto !== '').slice(0, 24)
      : [];

    renderChecklist(key);
  }

  function addChecklistItem(key) {
    const config = getChecklistConfig(key);
    const value = String(config.input?.value || '').trim();
    if (!value) return;
    state.checklist[key].push({ texto: value, concluido: false });
    if (config.input) config.input.value = '';
    renderChecklist(key);
    config.input?.focus();
  }

  function moveChecklistItem(key, fromIndex, toIndex) {
    if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0) return;
    const items = state.checklist[key];
    if (!items[fromIndex] || !items[toIndex]) return;
    const [moved] = items.splice(fromIndex, 1);
    items.splice(toIndex, 0, moved);
    renderChecklist(key);
  }

  function getMembersConfig(key) {
    const prefix = getPrefix(key);
    return {
      hidden: document.getElementById(prefix ? 'editTarefaMembersJson' : 'tarefaMembersJson'),
      count: document.getElementById(prefix ? 'editTarefaMembersCount' : 'tarefaMembersCount'),
      pills: document.querySelectorAll(`.fd-task-member-pill[data-member-key="${key}"]`)
    };
  }

  function setMembers(key, members) {
    const ids = Array.isArray(members)
      ? members.map((item) => Number(item?.user_id ?? item)).filter((value) => value > 0)
      : [];
    state.members[key] = Array.from(new Set(ids)).slice(0, 8);
    renderMembers(key);
  }

  function renderMembers(key) {
    const config = getMembersConfig(key);
    config.pills.forEach((pill) => {
      const userId = Number(pill.dataset.userId || 0);
      pill.classList.toggle('is-active', state.members[key].includes(userId));
    });

    if (config.hidden) {
      config.hidden.value = JSON.stringify(state.members[key]);
    }
    if (config.count) {
      config.count.textContent = String(state.members[key].length);
    }
  }

  function getAttachmentsConfig(key) {
    const prefix = getPrefix(key);
    return {
      hidden: document.getElementById(prefix ? 'editTarefaAttachmentsJson' : 'tarefaAttachmentsJson'),
      count: document.getElementById(prefix ? 'editTarefaAttachmentsCount' : 'tarefaAttachmentsCount'),
      list: document.getElementById(prefix ? 'editTarefaAttachmentsList' : 'tarefaAttachmentsList'),
      label: document.getElementById(prefix ? 'editTarefaAttachmentLabel' : 'tarefaAttachmentLabel'),
      url: document.getElementById(prefix ? 'editTarefaAttachmentUrl' : 'tarefaAttachmentUrl')
    };
  }

  function setAttachments(key, attachments) {
    state.attachments[key] = Array.isArray(attachments)
      ? attachments.map((item) => ({
          label: String(item?.label || '').trim(),
          url: String(item?.url || '').trim()
        })).filter((item) => item.url).slice(0, 12)
      : [];
    renderAttachments(key);
  }

  function renderAttachments(key) {
    const config = getAttachmentsConfig(key);
    if (!config.list) return;

    if (config.hidden) {
      config.hidden.value = JSON.stringify(state.attachments[key]);
    }

    if (config.count) {
      config.count.textContent = String(state.attachments[key].length);
    }

    if (!state.attachments[key].length) {
      config.list.innerHTML = '<div class="fd-task-attachments-empty">Nenhum anexo ainda.</div>';
      return;
    }

    config.list.innerHTML = state.attachments[key].map((attachment, index) => `
      <div class="fd-task-attachment-item">
        <a href="${escapeHtml(attachment.url)}" target="_blank" rel="noopener noreferrer" class="fd-task-attachment-copy">
          <i class="ri-attachment-2"></i>
          <span>
            <strong>${escapeHtml(attachment.label || attachment.url)}</strong>
            <small>${escapeHtml(attachment.url)}</small>
          </span>
        </a>
        <button type="button" class="fd-task-attachment-remove" data-attachment-remove="${key}" data-index="${index}" aria-label="Remover anexo">
          <i class="ri-close-line"></i>
        </button>
      </div>
    `).join('');
  }

  function addAttachment(key) {
    const config = getAttachmentsConfig(key);
    const label = String(config.label?.value || '').trim();
    const url = String(config.url?.value || '').trim();
    if (!url) return;
    try {
      new URL(url);
    } catch {
      return;
    }
    state.attachments[key].push({ label, url });
    if (config.label) config.label.value = '';
    if (config.url) config.url.value = '';
    renderAttachments(key);
    config.label?.focus();
  }

  function getCommentsConfig() {
    return {
      list: document.getElementById('editTarefaCommentsList'),
      input: document.getElementById('editTarefaComentarioInput'),
      button: document.getElementById('editTarefaComentarioAdicionar')
    };
  }

  function closeTaskPickers() {
    document.querySelectorAll('.fd-task-picker').forEach((picker) => {
      picker.classList.remove('is-open');
    });
  }

  function renderColumnState(key) {
    const hidden = document.getElementById(key === 'create' ? 'tarefaColuna' : 'editTarefaColuna');
    const buttons = document.querySelectorAll(`.fd-task-column-group[data-column-group="${key}"] .fd-task-column-chip`);
    const label = document.getElementById(key === 'create' ? 'tarefaColunaLabel' : 'editTarefaColunaLabel');
    buttons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.value === state.column[key]);
    });
    if (hidden) {
      hidden.value = state.column[key];
    }
    if (label) {
      const activeButton = Array.from(buttons).find((button) => button.dataset.value === state.column[key]);
      label.textContent = activeButton?.textContent?.trim() || 'Backlog';
    }
  }

  function setColumnState(key, value) {
    const allowed = ['backlog', 'andamento', 'revisao', 'concluido'];
    state.column[key] = allowed.includes(value) ? value : 'backlog';
    renderColumnState(key);
    if (key === 'edit') {
      notifyTaskEditChanged();
    }
  }

  function renderComments() {
    const config = getCommentsConfig();
    if (!config.list) return;

    const comments = state.comments.edit || [];
    if (!state.taskIds.edit) {
      config.list.innerHTML = '<div class="fd-task-comments-empty">Salve a tarefa para liberar comentarios.</div>';
      if (config.button) config.button.disabled = true;
      return;
    }

    if (config.button) config.button.disabled = false;

    if (!comments.length) {
      config.list.innerHTML = '<div class="fd-task-comments-empty">Nenhum comentario ainda. Use este espaco para alinhamentos da equipe.</div>';
      return;
    }

    config.list.innerHTML = comments.map((comment) => {
      const isEditing = Number(comment.id) === Number(state.commentEditingId);
      const isDeleting = Number(comment.id) === Number(state.commentDeletingId);
      const avatar = comment.foto_perfil
        ? `<img src="${escapeHtml(normalizeMediaUrl(comment.foto_perfil))}" alt="Avatar de ${escapeHtml(comment.nome || 'Usuario')}" class="fd-task-comment-avatar">`
        : `<span class="fd-task-comment-avatar fd-task-comment-avatar-fallback">${escapeHtml(getInitials(comment.nome || 'Usuario'))}</span>`;

      return `
        <article class="fd-task-comment-item" data-comment-id="${Number(comment.id || 0)}">
          ${avatar}
          <div class="fd-task-comment-copy">
            <div class="fd-task-comment-meta">
              <strong>${escapeHtml(comment.nome || 'Usuario')}</strong>
              <a href="#" class="fd-task-comment-time" tabindex="-1" aria-disabled="true">${escapeHtml(comment.criado_em_formatado || '')}</a>
            </div>
            <div class="fd-task-comment-bubble">
              ${isEditing
                ? `<textarea class="form-control fd-task-comment-edit-input" rows="3">${escapeHtml(comment.comentario || '')}</textarea>`
                : `<p>${escapeHtml(comment.comentario || '')}</p>`}
            </div>
            <div class="fd-task-comment-inline-actions">
              ${isEditing
                ? `<button type="button" class="fd-task-comment-action" data-comment-save="${Number(comment.id || 0)}">Salvar</button>
                   <span class="fd-task-comment-action-sep">&bull;</span>
                   <button type="button" class="fd-task-comment-action" data-comment-cancel="${Number(comment.id || 0)}">Cancelar</button>`
                : isDeleting
                  ? `<button type="button" class="fd-task-comment-action is-danger" data-comment-confirm-delete="${Number(comment.id || 0)}">Confirmar exclusao</button>
                     <span class="fd-task-comment-action-sep">&bull;</span>
                     <button type="button" class="fd-task-comment-action" data-comment-cancel-delete="${Number(comment.id || 0)}">Cancelar</button>`
                  : `<button type="button" class="fd-task-comment-action" data-comment-edit="${Number(comment.id || 0)}">Editar</button>
                     <span class="fd-task-comment-action-sep">&bull;</span>
                     <button type="button" class="fd-task-comment-action" data-comment-delete="${Number(comment.id || 0)}">Excluir</button>`}
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  async function updateComment(commentId, comentario) {
    if (!buildUrl) return;
    const formData = new FormData();
    formData.append('comment_id', String(commentId));
    formData.append('comentario', comentario);

    const response = await fetch(buildUrl('/projetos/tarefas/comentario/atualizar'), {
      method: 'POST',
      body: formData
    });
    const data = await response.json();

    if (!response.ok || !data?.ok || !data?.comment) {
      throw new Error(data?.message || 'Nao foi possivel atualizar o comentario.');
    }

    state.comments.edit = state.comments.edit.map((comment) => Number(comment.id) === Number(commentId) ? data.comment : comment);
    state.commentEditingId = null;
    renderComments();
  }

  async function deleteComment(commentId) {
    if (!buildUrl) return;
    const formData = new FormData();
    formData.append('comment_id', String(commentId));

    const response = await fetch(buildUrl('/projetos/tarefas/comentario/excluir'), {
      method: 'POST',
      body: formData
    });
    const data = await response.json();

    if (!response.ok || !data?.ok) {
      throw new Error(data?.message || 'Nao foi possivel excluir o comentario.');
    }

    state.comments.edit = state.comments.edit.filter((comment) => Number(comment.id) !== Number(commentId));
    state.commentDeletingId = null;
    renderComments();
  }

  async function submitComment() {
    const config = getCommentsConfig();
    const taskId = Number(state.taskIds.edit || 0);
    const comment = String(config.input?.value || '').trim();
    if (!taskId || !comment || !buildUrl) return;

    config.button?.setAttribute('disabled', 'disabled');
    try {
      const formData = new FormData();
      formData.append('tarefa_id', String(taskId));
      formData.append('comentario', comment);

      const response = await fetch(buildUrl('/projetos/tarefas/comentar'), {
        method: 'POST',
        body: formData
      });
      const data = await response.json();

      if (!response.ok || !data?.ok || !data.comment) {
        throw new Error(data?.message || 'Nao foi possivel salvar o comentario.');
      }

      state.comments.edit.unshift(data.comment);
      if (config.input) config.input.value = '';
      renderComments();
    } catch (error) {
      if (typeof window.fdShowInlineAlert === 'function') {
        window.fdShowInlineAlert(error.message || 'Nao foi possivel salvar o comentario.', 'danger', {
          contextSelector: '#modalEditarTarefa .modal-body',
          replace: false,
          scroll: false
        });
      }
    } finally {
      config.button?.removeAttribute('disabled');
    }
  }

  function setPriorityState(groupKey, value) {
    const hiddenInput = document.getElementById(groupKey === 'create' ? 'tarefaPrioridade' : 'editTarefaPrioridade');
    const buttons = document.querySelectorAll(`.fd-task-priority-group[data-priority-group="${groupKey}"] .fd-task-priority-chip`);
    const safeValue = priorityMeta[value] ? value : 'media';
    const label = document.getElementById(groupKey === 'create' ? 'tarefaPrioridadeLabel' : 'editTarefaPrioridadeLabel');

    if (hiddenInput) {
      hiddenInput.value = safeValue;
    }

    buttons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.value === safeValue);
    });

    if (label) {
      label.textContent = priorityMeta[safeValue].label;
    }
    if (groupKey === 'edit') {
      notifyTaskEditChanged();
    }
  }

  createEditor('create', '#tarefaDescricaoEditor', '#tarefaDescricaoToolbar', '#tarefaDescricao', 'Adicione contexto, checklist e detalhes operacionais da tarefa...');
  createEditor('edit', '#editTarefaDescricaoEditor', '#editTarefaDescricaoToolbar', '#editTarefaDescricao', 'Refine o contexto operacional desta tarefa...');

  window.fdSetTaskEditorContent = function (key, html) {
    const instance = editors[key];
    if (!instance) return;
    instance.quill.root.innerHTML = prepareHtml(html);
    instance.hiddenInput.value = normalizeHtml(instance.quill.root.innerHTML);
  };

  window.fdSetTaskPriority = function (key, value) {
    setPriorityState(key, value);
  };

  window.fdSetTaskChecklist = function (key, items) {
    setChecklist(key, items);
  };

  window.fdSetTaskMembers = function (key, members) {
    setMembers(key, members);
  };

  window.fdSetTaskAttachments = function (key, attachments) {
    setAttachments(key, attachments);
  };

  window.fdSetTaskComments = function (comments) {
    state.comments.edit = Array.isArray(comments) ? comments : [];
    renderComments();
  };

  window.fdSetTaskContext = function (key, payload = {}) {
    state.taskIds[key] = payload.taskId || null;
    if (key === 'edit') {
      renderComments();
    }
  };

  window.fdSetTaskColumn = function (key, value) {
    setColumnState(key, value);
  };

  ['create', 'edit'].forEach((groupKey) => {
    const prefix = getPrefix(groupKey);
    const titleInput = document.getElementById(prefix ? 'editTarefaTitulo' : 'tarefaTitulo');
    const buttons = document.querySelectorAll(`.fd-task-priority-group[data-priority-group="${groupKey}"] .fd-task-priority-chip`);
    const columnButtons = document.querySelectorAll(`.fd-task-column-group[data-column-group="${groupKey}"] .fd-task-column-chip`);
    const checklistConfig = getChecklistConfig(groupKey);
    const membersConfig = getMembersConfig(groupKey);
    const attachmentsConfig = getAttachmentsConfig(groupKey);

    titleInput?.addEventListener('input', () => {});

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        setPriorityState(groupKey, button.dataset.value || 'media');
        closeTaskPickers();
      });
    });

    columnButtons.forEach((button) => {
      button.addEventListener('click', () => {
        setColumnState(groupKey, button.dataset.value || 'backlog');
        closeTaskPickers();
      });
    });

    checklistConfig.input?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addChecklistItem(groupKey);
      }
    });

    document.querySelector(`.fd-task-checklist-add[data-checklist-key="${groupKey}"]`)?.addEventListener('click', () => {
      addChecklistItem(groupKey);
    });

    checklistConfig.list?.addEventListener('change', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLInputElement)) return;
      if (!target.matches(`[data-checklist-toggle="${groupKey}"]`)) return;
      const index = Number(target.dataset.index || -1);
      if (!state.checklist[groupKey][index]) return;
      state.checklist[groupKey][index].concluido = target.checked;
      renderChecklist(groupKey);
    });

    checklistConfig.list?.addEventListener('click', (event) => {
      const removeButton = event.target.closest(`[data-checklist-remove="${groupKey}"]`);
      if (removeButton) {
        const index = Number(removeButton.dataset.index || -1);
        if (index >= 0) {
          state.checklist[groupKey].splice(index, 1);
          renderChecklist(groupKey);
        }
      }
    });

    checklistConfig.list?.addEventListener('dragstart', (event) => {
      const item = event.target.closest(`[data-checklist-item="${groupKey}"]`);
      if (!item) return;
      state.draggingChecklist[groupKey] = Number(item.dataset.index || -1);
      item.classList.add('is-dragging');
    });

    checklistConfig.list?.addEventListener('dragend', (event) => {
      const item = event.target.closest(`[data-checklist-item="${groupKey}"]`);
      item?.classList.remove('is-dragging');
      state.draggingChecklist[groupKey] = null;
    });

    checklistConfig.list?.addEventListener('dragover', (event) => {
      event.preventDefault();
    });

    checklistConfig.list?.addEventListener('drop', (event) => {
      event.preventDefault();
      const targetItem = event.target.closest(`[data-checklist-item="${groupKey}"]`);
      const fromIndex = state.draggingChecklist[groupKey];
      const toIndex = Number(targetItem?.dataset.index || -1);
      moveChecklistItem(groupKey, fromIndex, toIndex);
    });

    membersConfig.pills.forEach((pill) => {
      pill.addEventListener('click', () => {
        const userId = Number(pill.dataset.userId || 0);
        if (!userId) return;
        if (state.members[groupKey].includes(userId)) {
          state.members[groupKey] = state.members[groupKey].filter((id) => id !== userId);
        } else {
          state.members[groupKey].push(userId);
        }
        state.members[groupKey] = Array.from(new Set(state.members[groupKey])).slice(0, 8);
        renderMembers(groupKey);
      });
    });

    document.querySelector(`.fd-task-attachment-add[data-attachment-key="${groupKey}"]`)?.addEventListener('click', () => {
      addAttachment(groupKey);
    });

    attachmentsConfig.url?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addAttachment(groupKey);
      }
    });

    attachmentsConfig.list?.addEventListener('click', (event) => {
      const removeButton = event.target.closest(`[data-attachment-remove="${groupKey}"]`);
      if (!removeButton) return;
      const index = Number(removeButton.dataset.index || -1);
      if (index < 0) return;
      state.attachments[groupKey].splice(index, 1);
      renderAttachments(groupKey);
    });

    setPriorityState(groupKey, 'media');
    setColumnState(groupKey, 'backlog');
    setChecklist(groupKey, []);
    setMembers(groupKey, []);
    setAttachments(groupKey, []);
  });

  document.querySelectorAll('.fd-task-picker-trigger').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      const key = trigger.dataset.pickerTrigger;
      const picker = trigger.closest('.fd-task-picker');
      const willOpen = !picker?.classList.contains('is-open');
      closeTaskPickers();
      if (willOpen) {
        picker?.classList.add('is-open');
      }
      event.stopPropagation();
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.fd-task-picker')) {
      closeTaskPickers();
    }
  });

  const commentsConfig = getCommentsConfig();
  commentsConfig.button?.addEventListener('click', submitComment);
  commentsConfig.list?.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-comment-edit]');
    if (editButton) {
      state.commentDeletingId = null;
      state.commentEditingId = Number(editButton.dataset.commentEdit || 0);
      renderComments();
      return;
    }

    const cancelButton = event.target.closest('[data-comment-cancel]');
    if (cancelButton) {
      state.commentEditingId = null;
      renderComments();
      return;
    }

    const saveButton = event.target.closest('[data-comment-save]');
    if (saveButton) {
      const commentId = Number(saveButton.dataset.commentSave || 0);
      const item = saveButton.closest('.fd-task-comment-item');
      const input = item?.querySelector('.fd-task-comment-edit-input');
      const comentario = String(input?.value || '').trim();
      if (!commentId || !comentario) return;
      updateComment(commentId, comentario).catch((error) => {
        if (typeof window.fdShowInlineAlert === 'function') {
          window.fdShowInlineAlert(error.message || 'Nao foi possivel atualizar o comentario.', 'danger', {
            contextSelector: '#modalEditarTarefa .modal-body',
            replace: false,
            scroll: false
          });
        }
      });
      return;
    }

    const deleteButton = event.target.closest('[data-comment-delete]');
    if (deleteButton) {
      state.commentEditingId = null;
      state.commentDeletingId = Number(deleteButton.dataset.commentDelete || 0);
      renderComments();
      return;
    }

    const cancelDeleteButton = event.target.closest('[data-comment-cancel-delete]');
    if (cancelDeleteButton) {
      state.commentDeletingId = null;
      renderComments();
      return;
    }

    const confirmDeleteButton = event.target.closest('[data-comment-confirm-delete]');
    if (confirmDeleteButton) {
      const commentId = Number(confirmDeleteButton.dataset.commentConfirmDelete || 0);
      if (!commentId) return;
      deleteComment(commentId).catch((error) => {
        if (typeof window.fdShowInlineAlert === 'function') {
          window.fdShowInlineAlert(error.message || 'Nao foi possivel excluir o comentario.', 'danger', {
            contextSelector: '#modalEditarTarefa .modal-body',
            replace: false,
            scroll: false
          });
        }
      });
    }
  });
}

window.initTaskRichEditor = initTaskRichEditor;
