<div class="modal fade" id="modalNovoCodigo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content fd-modal-content">
      <div class="modal-header fd-modal-header">
        <div>
          <p class="fd-card-eyebrow">Modulo Codigos</p>
          <h2 class="fd-modal-title">Novo codigo</h2>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form action="<?= ($base ?? '') ?>/codigos/criar" method="post" class="fd-modal-form" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="criar">
        <div class="modal-body fd-modal-body">
          <div class="fd-settings-fields">
            <div class="fd-settings-field fd-settings-field-span-2">
              <label class="form-label small">Titulo do codigo</label>
              <input type="text" name="titulo" class="form-control" placeholder="Ex: Notificacao de vendas em JS" required>
            </div>
            <div class="fd-settings-field fd-settings-field-span-2">
              <label class="form-label small">Descricao</label>
              <textarea name="descricao" rows="3" class="form-control" placeholder="Contexto rapido para localizar esse codigo depois"></textarea>
            </div>
            <div class="fd-settings-field">
              <label class="form-label small">Categoria</label>
              <input type="text" name="categoria" class="form-control" placeholder="Ex: Elementor / Efeitos / CSS" list="codigo-categorias" required>
              <datalist id="codigo-categorias">
                <?php foreach ($categorias as $categoria): ?>
                  <option value="<?= htmlspecialchars($categoria) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="fd-settings-field">
              <label class="form-label small">Tipo</label>
              <input type="text" name="tipo" class="form-control" placeholder="Snippet, Efeito, Widget..." value="Snippet">
            </div>
            <div class="fd-settings-field">
              <label class="form-label small">Dificuldade</label>
              <select name="dificuldade" class="form-select">
                <option value="basico">Basico</option>
                <option value="intermediario">Intermediario</option>
                <option value="avancado">Avancado</option>
              </select>
            </div>
            <div class="fd-settings-field fd-settings-field-span-2">
              <label class="form-label small">Instrucoes de uso</label>
              <textarea name="instrucoes" rows="4" class="form-control" placeholder="Passo a passo ou observacoes de uso"></textarea>
            </div>
            <div class="fd-settings-field fd-settings-field-span-2">
              <label class="form-label small">Imagem de preview</label>
              <input type="file" name="preview_image" class="form-control" accept="image/png,image/jpeg,image/gif">
              <p class="fd-text-muted fd-settings-help">Opcional. PNG, JPEG ou GIF ate 8MB. Quando enviada, ela substitui o snippet no card.</p>
            </div>
            <div class="fd-settings-field fd-settings-field-span-2">
              <label class="form-label small">Conteudo do codigo</label>
              <textarea name="conteudo" rows="10" class="form-control fd-code-textarea" placeholder="Cole aqui HTML, CSS, JS, shortcode ou qualquer codigo tecnico" required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer fd-modal-footer">
          <button type="button" class="fd-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="fd-btn-primary">
            <i class="ri-save-line"></i>
            <span>Salvar codigo</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
