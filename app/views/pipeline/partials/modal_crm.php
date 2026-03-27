<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../../../../config/db.php';

$canManagePipeline = fd_has_any_role(['owner', 'admin', 'operacional']);
?>

<?php if ($canManagePipeline): ?>
<div class="modal fade" id="modalNovaOportunidade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/pipeline/criar">
        <div class="modal-header">
          <h5 class="modal-title">Nova oportunidade</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label small">Titulo</label>
              <input type="text" name="titulo" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($listaClientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Estagio inicial</label>
              <select name="funil_estagio_id" class="form-select" required>
                <?php foreach ($estagios as $e): ?>
                  <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
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
              <input type="text" name="origem_lead" class="form-control" placeholder="Instagram, indicacao...">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Responsavel</label>
              <input type="text" name="responsavel" class="form-control" value="<?= $first_name ?? '' ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Data prevista de fechamento</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" name="data_prevista_fechamento" class="fd-date-picker-native" x-model="selectedValue">
                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                  <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                  <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label">Fechamento</span>
                    <strong x-text="triggerLabel"></strong>
                  </span>
                  <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
                </button>
                <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                  <div class="fd-date-picker-head">
                    <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                    <div class="fd-date-picker-head-copy">
                      <span class="fd-date-picker-head-label">Selecione a data</span>
                      <strong x-text="headerLabel"></strong>
                    </div>
                    <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                  </div>
                  <div class="fd-date-picker-weekdays">
                    <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                  </div>
                  <div class="fd-date-picker-days">
                    <template x-for="item in days()" :key="item.key">
                      <div>
                        <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                        <template x-if="!item.empty">
                          <button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button>
                        </template>
                      </div>
                    </template>
                  </div>
                  <div class="fd-date-picker-footer">
                    <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                    <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small">Observacoes</label>
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

<div class="modal fade" id="modalEditarOportunidade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/pipeline/atualizar" id="form-editar-oportunidade">
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
              <label class="form-label small">Titulo</label>
              <input type="text" name="titulo" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($listaClientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small">Estagio</label>
              <select name="funil_estagio_id" class="form-select" required>
                <?php foreach ($estagios as $e): ?>
                  <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
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
              <label class="form-label small">Responsavel</label>
              <input type="text" name="responsavel" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label small">Data prevista de fechamento</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" name="data_prevista_fechamento" class="fd-date-picker-native" x-model="selectedValue">
                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                  <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                  <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label">Fechamento</span>
                    <strong x-text="triggerLabel"></strong>
                  </span>
                  <i class="ri-arrow-down-s-line fd-date-picker-trigger-arrow"></i>
                </button>
                <div class="fd-date-picker-panel" x-show="open" x-cloak x-transition.opacity.scale.origin.top.left @click.outside="close()">
                  <div class="fd-date-picker-head">
                    <button type="button" class="fd-date-picker-nav" @click="prevMonth()"><i class="ri-arrow-left-s-line"></i></button>
                    <div class="fd-date-picker-head-copy">
                      <span class="fd-date-picker-head-label">Selecione a data</span>
                      <strong x-text="headerLabel"></strong>
                    </div>
                    <button type="button" class="fd-date-picker-nav" @click="nextMonth()"><i class="ri-arrow-right-s-line"></i></button>
                  </div>
                  <div class="fd-date-picker-weekdays">
                    <template x-for="weekday in weekdays" :key="weekday"><span class="fd-date-picker-weekday" x-text="weekday"></span></template>
                  </div>
                  <div class="fd-date-picker-days">
                    <template x-for="item in days()" :key="item.key">
                      <div>
                        <template x-if="item.empty"><span class="fd-date-picker-day is-empty"></span></template>
                        <template x-if="!item.empty">
                          <button type="button" class="fd-date-picker-day" :class="{ 'is-today': isToday(item.day), 'is-selected': isSelected(item.day) }" @click="selectDay(item.day)" x-text="item.day"></button>
                        </template>
                      </div>
                    </template>
                  </div>
                  <div class="fd-date-picker-footer">
                    <button type="button" class="fd-date-picker-link" @click="clear()">Limpar</button>
                    <button type="button" class="fd-date-picker-link" @click="selectToday()">Hoje</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small">Observacoes</label>
              <textarea name="observacoes" rows="3" class="form-control"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

