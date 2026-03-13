<!--
///////////////////////
/// Bloco Nova Hospedagem
////////////////////////
--->

<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
require_once __DIR__ . '/../../config/db.php';
?>
<div class="modal fade " id="modalNovaHospedagem" tabindex="-1" aria-labelledby="modalNovaHospedagemLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/HospedagemController.php">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNovaHospedagemLabel">Nova hospedagem</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Nome da hospedagem</label>
            <input type="text" name="nome" class="form-control" required
              placeholder="Ex: Hospedagem WordPress Cliente X">
          </div>

          <div class="mb-3">
            <label class="form-label small">Tipo da hospedagem</label>
            <select name="tipo" class="form-select form-select-sm" required>
              <option value="wordpress">WordPress</option>
              <option value="vps">VPS</option>
              <option value="dominio">Domínio</option>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small">Data de início</label>
              <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"
                required>
            </div>
            <div class="col-md-6">
              <label class="form-label small">Data de término</label>
              <input type="date" name="data_fim" class="form-control form-control-sm" required>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--
///////////////////////
/// Bloco Nova Oportunidade
////////////////////////
--->

<div class="modal fade " id="modalNovaOportunidade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/PipelineController.php?acao=criar">
        <div class="modal-header">
          <h5 class="modal-title">Nova oportunidade</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small">Título</label>
              <input type="text" name="titulo" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($listaClientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>">
                    <?= htmlspecialchars($c['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Estágio inicial</label>
              <select name="funil_estagio_id" class="form-select" required>
                <?php foreach ($estagios as $e): ?>
                  <option value="<?= (int) $e['id'] ?>">
                    <?= htmlspecialchars($e['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Valor previsto</label>
              <input type="number" step="0.01" min="0" name="valor_previsto" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Probabilidade (%)</label>
              <input type="number" min="0" max="100" name="probabilidade" class="form-control" value="0">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Origem do lead</label>
              <input type="text" name="origem_lead" class="form-control" placeholder="Instagram, Indicação...">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Responsável</label>
              <input type="text" name="responsavel" class="form-control" value="<?= $first_name ?? '' ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Data prevista fechamento</label>
              <input type="date" name="data_prevista_fechamento" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label small">Observações</label>
              <textarea name="observacoes" rows="3" class="form-control"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--
///////////////////////
/// Bloco Editar Oportunidade
////////////////////////
--->

<?php
// modules/modals/modal_editar_oportunidade.php
// usa $listaClientes e $estagios já carregados no pipeline.php
?>
<div class="modal fade " id="modalEditarOportunidade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/PipelineController.php?acao=atualizar" id="form-editar-oportunidade">
        <input type="hidden" name="id">

        <div class="modal-header">
          <h5 class="modal-title">Editar oportunidade</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small">Título</label>
              <input type="text" name="titulo" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($listaClientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>">
                    <?= htmlspecialchars($c['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Estágio</label>
              <select name="funil_estagio_id" class="form-select" required>
                <?php foreach ($estagios as $e): ?>
                  <option value="<?= (int) $e['id'] ?>">
                    <?= htmlspecialchars($e['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Valor previsto</label>
              <input type="number" step="0.01" min="0" name="valor_previsto" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Probabilidade (%)</label>
              <input type="number" min="0" max="100" name="probabilidade" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Origem do lead</label>
              <input type="text" name="origem_lead" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Responsável</label>
              <input type="text" name="responsavel" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Data prevista fechamento</label>
              <input type="date" name="data_prevista_fechamento" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label small">Observações</label>
              <textarea name="observacoes" rows="3" class="form-control"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--
///////////////////////
/// Bloco Nova Hospedagem
////////////////////////
--->

<?php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
require_once __DIR__ . '/../../config/db.php';
?>
<div class="modal fade modal-right" id="modalNovaHospedagem" tabindex="-1" aria-labelledby="modalNovaHospedagemLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/HospedagemController.php">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNovaHospedagemLabel">Nova hospedagem</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Nome da hospedagem</label>
            <input type="text" name="nome" class="form-control" required
              placeholder="Ex: Hospedagem WordPress Cliente X">
          </div>

          <div class="mb-3">
            <label class="form-label small">Tipo da hospedagem</label>
            <select name="tipo" class="form-select form-select-sm" required>
              <option value="wordpress">WordPress</option>
              <option value="vps">VPS</option>
              <option value="dominio">Domínio</option>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small">Data de início</label>
              <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"
                required>
            </div>
            <div class="col-md-6">
              <label class="form-label small">Data de término</label>
              <input type="date" name="data_fim" class="form-control form-control-sm" required>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>