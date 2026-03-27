function initEditModals() {
  const buildUrl = window.fdUrl;

  initEditarTarefaModal();
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
