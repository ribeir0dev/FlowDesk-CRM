<?php if (fd_has_any_role(['owner', 'admin', 'financeiro'])): ?>
<div class="modal fade" id="modalNovaHospedagem" tabindex="-1" aria-labelledby="modalNovaHospedagemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/hospedagens/criar">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNovaHospedagemLabel">Nova hospedagem</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="fd-settings-form">
            <div class="fd-settings-field">
              <label class="form-label small">Nome da hospedagem</label>
              <input type="text" name="nome" class="form-control" placeholder="Ex: Hospedagem WordPress Cliente X" required>
            </div>

            <div class="fd-settings-field">
              <label class="form-label small">Tipo da hospedagem</label>
              <select name="tipo" class="form-select" required>
                <option value="wordpress">WordPress</option>
                <option value="vps">VPS</option>
                <option value="dominio">Dominio</option>
              </select>
            </div>

            <div class="fd-settings-fields">
              <div class="fd-settings-field">
                <label class="form-label small">Data de inicio</label>
                <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= date('Y-m-d') ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                  <input type="date" name="data_inicio" class="fd-date-picker-native" x-model="selectedValue" required>
                  <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                    <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                    <span class="fd-date-picker-trigger-copy">
                      <span class="fd-date-picker-trigger-label">Inicio</span>
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

              <div class="fd-settings-field">
                <label class="form-label small">Data de termino</label>
                <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                  <input type="date" name="data_fim" class="fd-date-picker-native" x-model="selectedValue" required>
                  <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                    <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                    <span class="fd-date-picker-trigger-copy">
                      <span class="fd-date-picker-trigger-label">Termino</span>
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
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="fd-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="fd-btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
