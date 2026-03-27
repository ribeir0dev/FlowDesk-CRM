let kanbanDragInitialized = false;

function syncEmptyState(columnBody, type) {
  if (!columnBody) return;

  const emptySelector = '.fd-pipeline-empty';
  const cards = Array.from(columnBody.children).filter((child) =>
    child.matches('.pipeline-card[data-id]')
  );
  const emptyText =
    type === 'pipeline'
      ? 'Nenhuma oportunidade neste estagio.'
      : 'Sem tarefas nesta etapa.';

  if (cards.length > 0) {
    columnBody.querySelectorAll(emptySelector).forEach((emptyState) => emptyState.remove());
    return;
  }

  if (!columnBody.querySelector(emptySelector)) {
    const wrapper = document.createElement('div');
    wrapper.className = 'fd-pipeline-empty';
    wrapper.innerHTML = `<p>${emptyText}</p>`;
    columnBody.appendChild(wrapper);
  }
}

function initKanbanDragDrop() {
  const pipelineColumns = document.querySelectorAll('.pipeline-column-body');
  const projetoColumns = document.querySelectorAll('.kanban-column-body');
  const buildUrl = window.fdUrl;

  if (!pipelineColumns.length && !projetoColumns.length) return;
  if (!buildUrl) {
    console.warn('initKanbanDragDrop: fdUrl nao encontrado');
    return;
  }

  let draggedCard = null;

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

  pipelineColumns.forEach((col) => {
    syncEmptyState(col, 'pipeline');

    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });

    col.addEventListener('drop', async function (e) {
      e.preventDefault();
      if (!draggedCard) return;

      const origem = draggedCard.parentElement;
      this.appendChild(draggedCard);
      syncEmptyState(origem, 'pipeline');
      syncEmptyState(this, 'pipeline');

      const cardId = draggedCard.getAttribute('data-id');
      const columnWrap = this.closest('.pipeline-column');
      const estagioId = columnWrap.getAttribute('data-estagio-id');

      try {
        const formData = new FormData();
        formData.append('id', cardId);
        formData.append('funil_estagio_id', estagioId);

        const response = await fetch(buildUrl('/pipeline/mover'), {
          method: 'POST',
          body: formData,
        });

        if (!response.ok) {
          throw new Error(`pipeline move failed: ${response.status}`);
        }
      } catch (err) {
        if (origem) {
          origem.appendChild(draggedCard);
          syncEmptyState(origem, 'pipeline');
          syncEmptyState(this, 'pipeline');
        }
        console.error('Erro ao mover oportunidade', err);
      }
    });
  });

  projetoColumns.forEach((col) => {
    syncEmptyState(col, 'projeto');

    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });

    col.addEventListener('drop', async function (e) {
      e.preventDefault();
      if (!draggedCard) return;

      const origem = draggedCard.parentElement;
      this.appendChild(draggedCard);
      syncEmptyState(origem, 'projeto');
      syncEmptyState(this, 'projeto');

      const cardId = draggedCard.getAttribute('data-id');
      const colunaSlug = this.getAttribute('data-coluna');

      try {
        const formData = new FormData();
        formData.append('tarefa_id', cardId);
        formData.append('coluna', colunaSlug);

        const response = await fetch(buildUrl('/projetos/tarefas/mover'), {
          method: 'POST',
          body: formData,
        });

        if (!response.ok) {
          throw new Error(`task move failed: ${response.status}`);
        }
      } catch (err) {
        if (origem) {
          origem.appendChild(draggedCard);
          syncEmptyState(origem, 'projeto');
          syncEmptyState(this, 'projeto');
        }
        console.error('Erro ao mover tarefa', err);
      }
    });
  });
}

window.initKanbanDragDrop = initKanbanDragDrop;
