function initPipelineQuickActions() {
  const buildUrl = window.fdUrl;
  if (!buildUrl) return;

  const ID_ESTAGIO_GANHO = 3;
  const ID_ESTAGIO_PERDIDO = 4;

  document.addEventListener('click', async function (e) {
    const btnGanha = e.target.closest('.btn-op-ganha');
    if (btnGanha) {
      const id = btnGanha.getAttribute('data-id');
      if (!id) return;

      const formData = new FormData();
      formData.append('id', id);
      formData.append('estagio_ganho_id', ID_ESTAGIO_GANHO);

      await fetch(buildUrl('/pipeline/marcar-ganha'), {
        method: 'POST',
        body: formData,
      });

      location.reload();
      return;
    }

    const btnPerder = e.target.closest('.btn-op-perder');
    if (btnPerder) {
      const id = btnPerder.getAttribute('data-id');
      if (!id) return;

      const motivo = prompt('Motivo da perda (opcional):', '');
      const formData = new FormData();
      formData.append('id', id);
      formData.append('estagio_perdido_id', ID_ESTAGIO_PERDIDO);
      formData.append('motivo_perda', motivo || '');

      await fetch(buildUrl('/pipeline/marcar-perdida'), {
        method: 'POST',
        body: formData,
      });

      location.reload();
    }
  });
}

window.initPipelineQuickActions = initPipelineQuickActions;
