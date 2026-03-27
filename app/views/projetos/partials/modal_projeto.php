<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db.php';

$workspaceId = fd_current_workspace_id() ?? 0;
$stmtCli = $pdo->prepare('SELECT id, nome FROM clientes WHERE workspace_id = ? ORDER BY nome ASC');
$stmtCli->execute([$workspaceId]);
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$oportunidadeId = $oportunidadeId ?? 0;
$clienteIdPadrao = $clienteIdPadrao ?? 0;
$nomeProjetoPadrao = $nomeProjetoPadrao ?? '';
$valorPrevistoPadrao = $valorPrevistoPadrao ?? 0.0;
$canManageProjetos = fd_has_any_role(['owner', 'admin', 'operacional']);
?>

<?php if ($canManageProjetos): ?>
<div class="modal fade" id="modalEditarProjeto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/atualizar" id="form-editar-projeto">
        <input type="hidden" name="projeto_id" id="editProjetoId" value="">

        <div class="modal-header">
          <h5 class="modal-title">Editar projeto</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small">Nome do projeto</label>
              <input type="text" name="nome_projeto" id="editNomeProjeto" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small">Tipo do projeto</label>
              <select name="tipo_projeto" id="editTipoProjeto" class="form-select form-select-sm" required>
                <option value="landing_page">Landing Page</option>
                <option value="configuracao">Configuracao</option>
                <option value="alteracao">Alteracao</option>
                <option value="otimizacao">Otimizacao</option>
                <option value="integracao">Integracao</option>
                <option value="design">Design</option>
                <option value="outro">Outro</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" id="editClienteProjeto" class="form-select form-select-sm">
                <option value="">Sem cliente vinculado</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small">Inicio do projeto</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" name="data_inicio" id="editDataInicioProjeto" class="fd-date-picker-native" x-model="selectedValue">
                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                  <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                  <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label">Data</span>
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

            <div class="col-md-6">
              <label class="form-label small">Data de entrega</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" name="data_entrega" id="editDataEntregaProjeto" class="fd-date-picker-native" x-model="selectedValue">
                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                  <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                  <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label">Entrega</span>
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

            <div class="col-md-6">
              <label class="form-label small">Status</label>
              <select name="status" id="editStatusProjeto" class="form-select form-select-sm">
                <option value="planejado">Planejado</option>
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluido</option>
                <option value="pausado">Pausado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small">Descricao</label>
              <textarea name="descricao" id="editDescricaoProjeto" class="form-control" rows="3" placeholder="Resumo do escopo, entregas e observacoes importantes."></textarea>
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

<div class="modal fade" id="modalNovoProjeto" tabindex="-1" aria-labelledby="modalNovoProjetoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/criar" id="form-novo-projeto">
        <input type="hidden" name="oportunidade_id" value="<?= (int) $oportunidadeId ?>">

        <div class="modal-header">
          <h5 class="modal-title" id="modalNovoProjetoLabel">Novo projeto</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small">Nome do projeto</label>
              <input
                type="text"
                name="nome_projeto"
                class="form-control"
                placeholder="Ex: Landing page Black Friday Cliente X"
                value="<?= htmlspecialchars($nomeProjetoPadrao) ?>"
                required
              >
            </div>

            <div class="col-md-6">
              <label class="form-label small">Tipo do projeto</label>
              <select name="tipo_projeto" class="form-select form-select-sm" required>
                <option value="landing_page">Landing Page</option>
                <option value="configuracao">Configuracao</option>
                <option value="alteracao">Alteracao</option>
                <option value="otimizacao">Otimizacao</option>
                <option value="integracao">Integracao</option>
                <option value="design">Design</option>
                <option value="outro">Outro</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small">Cliente</label>
              <select name="cliente_id" class="form-select form-select-sm">
                <option value="">Sem cliente vinculado</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $clienteIdPadrao ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text small">Opcional, mas recomendado.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label small">Inicio do projeto</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= date('Y-m-d') ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" id="dataInicioProjeto" name="data_inicio" class="fd-date-picker-native" x-model="selectedValue" required>
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

            <div class="col-md-6">
              <label class="form-label small">Data de entrega</label>
              <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" id="dataEntregaProjeto" name="data_entrega" class="fd-date-picker-native" x-model="selectedValue" required>
                <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                  <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                  <span class="fd-date-picker-trigger-copy">
                    <span class="fd-date-picker-trigger-label">Entrega</span>
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

            <div class="col-md-6">
              <label class="form-label small">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option value="planejado" selected>Planejado</option>
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluido</option>
                <option value="pausado">Pausado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label small">Descricao (opcional)</label>
              <textarea name="descricao" class="form-control" rows="3" placeholder="Resumo do escopo do projeto, objetivos e observacoes importantes."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar projeto</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$projeto_id = (int) ($_GET['id'] ?? 0);
?>
<div class="modal fade" id="modalNovaTarefa" tabindex="-1" aria-labelledby="modalNovaTarefaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/salvar" id="form-tarefa-projeto">
        <input type="hidden" name="projeto_id" value="<?= $projeto_id ?>">
        <input type="hidden" name="tarefa_id" id="tarefaId" value="">
        <input type="hidden" name="coluna" id="tarefaColuna" value="backlog">

        <div class="modal-header">
          <h5 class="modal-title" id="modalNovaTarefaLabel">Nova tarefa</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Titulo da tarefa</label>
            <input type="text" name="titulo" id="tarefaTitulo" class="form-control" placeholder="Ex: Configurar DNS, criar layout da secao hero" required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descricao (opcional)</label>
            <textarea name="descricao" id="tarefaDescricao" class="form-control" rows="3" placeholder="Detalhes, links ou checklist da tarefa."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small">Coluna</label>
            <select name="coluna_select" id="tarefaColunaSelect" class="form-select form-select-sm">
              <option value="backlog">Backlog</option>
              <option value="andamento">Em andamento</option>
              <option value="revisao">Revisao</option>
              <option value="concluido">Concluido</option>
            </select>
          </div>

          <div class="mb-0">
            <label class="form-label small">Data de entrega</label>
            <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
              <input type="date" name="data_entrega" id="tarefaDataEntrega" class="fd-date-picker-native" x-model="selectedValue">
              <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                <span class="fd-date-picker-trigger-copy">
                  <span class="fd-date-picker-trigger-label">Entrega</span>
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

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar tarefa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditarTarefa" tabindex="-1" aria-labelledby="modalEditarTarefaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/salvar" id="form-editar-tarefa">
        <input type="hidden" name="projeto_id" value="<?= $projeto_id ?>">
        <input type="hidden" name="tarefa_id" id="editTarefaId" value="">
        <input type="hidden" name="coluna" id="editTarefaColuna" value="backlog">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarTarefaLabel">Editar tarefa</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Titulo da tarefa</label>
            <input type="text" name="titulo" id="editTarefaTitulo" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descricao (opcional)</label>
            <textarea name="descricao" id="editTarefaDescricao" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small">Coluna</label>
            <select name="coluna_select" id="editTarefaColunaSelect" class="form-select form-select-sm">
              <option value="backlog">Backlog</option>
              <option value="andamento">Em andamento</option>
              <option value="revisao">Revisao</option>
              <option value="concluido">Concluido</option>
            </select>
          </div>

          <div class="mb-0">
            <label class="form-label small">Data de entrega</label>
            <div class="fd-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
              <input type="date" name="data_entrega" id="editTarefaDataEntrega" class="fd-date-picker-native" x-model="selectedValue">
              <button type="button" class="fd-date-picker-trigger" @click="toggle()" :aria-expanded="open.toString()">
                <span class="fd-date-picker-trigger-icon"><i class="ri-calendar-line"></i></span>
                <span class="fd-date-picker-trigger-copy">
                  <span class="fd-date-picker-trigger-label">Entrega</span>
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

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
        </div>
      </form>
    </div>
  </div>
</div>

