<div class="modal fade" id="modalExcluirCodigo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content fd-modal-content fd-delete-modal">
      <div class="modal-header fd-modal-header fd-delete-modal-header">
        <div class="fd-delete-modal-headline">
          <span class="fd-delete-modal-icon"><i class="ri-delete-bin-line"></i></span>
          <div>
            <p class="fd-card-eyebrow">Confirmacao de exclusao</p>
            <h2 class="fd-modal-title">Apagar codigo</h2>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form action="<?= ($base ?? '') ?>/codigos/excluir" method="post" class="fd-modal-form">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="codigo_id" value="">
        <div class="modal-body fd-modal-body">
          <div class="fd-delete-modal-copy">
            <p class="fd-delete-modal-title">Voce deseja realmente excluir <strong data-delete-codigo-title>este codigo</strong>?</p>
            <p class="fd-text-muted">Essa acao remove o codigo do workspace e nao pode ser desfeita depois.</p>
          </div>
        </div>
        <div class="modal-footer fd-modal-footer fd-delete-modal-footer">
          <button type="button" class="fd-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="fd-btn-danger-soft">
            <i class="ri-delete-bin-line"></i>
            <span>Excluir codigo</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
