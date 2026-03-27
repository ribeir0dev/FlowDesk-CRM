function initConfirmModal() {
  const modalEl = document.getElementById('modalConfirmarAcao');
  const msgEl = document.getElementById('modalConfirmarMensagem');
  const btnOk = document.getElementById('modalConfirmarBtnOk');

  if (!modalEl || !window.bootstrap) {
    console.warn('initConfirmModal: modal ou bootstrap nao encontrado');
    return;
  }

  let currentForm = null;

  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.classList || !form.classList.contains('js-confirm-delete')) return;

    e.preventDefault();
    currentForm = form;

    const msg = form.getAttribute('data-confirm-msg') || 'Confirmar exclusao?';
    if (msgEl) msgEl.textContent = msg;

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();
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

window.initConfirmModal = initConfirmModal;
