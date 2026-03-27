<div class="modal fade " id="modalFiltroClientes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="get">
        <div class="modal-header">
          <h5 class="modal-title">Filtrar clientes</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">

          <!-- Mantém o módulo e a busca atual -->
          <input type="hidden" name="mod" value="clientes">
          <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">

          <p class="small text-muted mb-2">Selecione o(s) status que deseja visualizar:</p>

          <div class="form-check mb-1">
            <input class="form-check-input"
                   type="checkbox"
                   name="status_cliente[]"
                   value="ativo"
                   id="flt_ativo"
                   <?= in_array('ativo', (array)$status_cliente, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="flt_ativo">Ativo</label>
          </div>

          <div class="form-check mb-1">
            <input class="form-check-input"
                   type="checkbox"
                   name="status_cliente[]"
                   value="inativo"
                   id="flt_inativo"
                   <?= in_array('inativo', (array)$status_cliente, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="flt_inativo">Inativo</label>
          </div>

          <div class="form-check mb-1">
            <input class="form-check-input"
                   type="checkbox"
                   name="status_cliente[]"
                   value="potencial"
                   id="flt_potencial"
                   <?= in_array('potencial', (array)$status_cliente, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="flt_potencial">Em potencial</label>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Aplicar filtros</button>
        </div>
      </form>
    </div>
  </div>
</div>