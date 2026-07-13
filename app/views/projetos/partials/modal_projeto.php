<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db.php';

$workspaceId = fd_current_workspace_id() ?? 0;
$stmtCli = $pdo->prepare('SELECT id, nome FROM clientes WHERE workspace_id = ? ORDER BY nome ASC');
$stmtCli->execute([$workspaceId]);
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$stmtTeam = $pdo->prepare('
    SELECT u.id, u.nome, u.email, u.foto_perfil
    FROM workspace_members wm
    INNER JOIN usuarios u ON u.id = wm.user_id
    WHERE wm.workspace_id = ?
    ORDER BY u.nome ASC
');
$stmtTeam->execute([$workspaceId]);
$workspaceMembers = $stmtTeam->fetchAll(PDO::FETCH_ASSOC);

$oportunidadeId = $oportunidadeId ?? 0;
$clienteIdPadrao = $clienteIdPadrao ?? 0;
$nomeProjetoPadrao = $nomeProjetoPadrao ?? '';
$valorPrevistoPadrao = $valorPrevistoPadrao ?? 0.0;
$canManageProjetos = fd_has_any_role(['owner', 'admin', 'operacional']);
$defaultProjectStart = date('Y-m-d');
$defaultProjectDelivery = (new DateTimeImmutable($defaultProjectStart))->modify('+3 days')->format('Y-m-d');

if (!function_exists('fd_task_member_initials')) {
    function fd_task_member_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if (!$parts) {
            return 'FD';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
        return strtoupper($first . $last);
    }
}
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
                <input type="date" name="data_inicio" id="editDataInicioProjeto" class="fd-date-picker-native" x-model="selectedValue" data-project-start-input data-project-delivery-target="editDataEntregaProjeto">
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
                <input type="date" name="data_entrega" id="editDataEntregaProjeto" class="fd-date-picker-native" x-model="selectedValue" data-project-delivery-input>
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

            <div class="col-12">
              <label class="form-label small">Status</label>
              <input type="hidden" name="status" id="editStatusProjeto" value="planejado">
              <div class="fd-project-choice-grid" data-project-status-group="edit">
                <button type="button" class="fd-project-choice is-active" data-project-status-value="planejado" data-project-status-label="Planejamento">
                  <i class="ri-file-list-3-line"></i>
                  <span>Planejamento</span>
                </button>
                <button type="button" class="fd-project-choice" data-project-status-value="em_andamento" data-project-status-label="Em Andamento">
                  <i class="ri-file-list-3-line"></i>
                  <span>Em Andamento</span>
                </button>
                <button type="button" class="fd-project-choice" data-project-status-value="pausado" data-project-status-label="Pausado">
                  <i class="ri-file-list-3-line"></i>
                  <span>Pausado</span>
                </button>
              </div>
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
              <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= $defaultProjectStart ?>', { defaultToToday: true, placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" id="dataInicioProjeto" name="data_inicio" class="fd-date-picker-native" x-model="selectedValue" required data-project-start-input data-project-delivery-target="dataEntregaProjeto">
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
              <div class="fd-date-picker" x-data="flowdeskDatePicker('<?= $defaultProjectDelivery ?>', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
                <input type="date" id="dataEntregaProjeto" name="data_entrega" class="fd-date-picker-native" x-model="selectedValue" required data-project-delivery-input>
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

            <div class="col-12">
              <label class="form-label small">Status</label>
              <input type="hidden" name="status" id="statusProjeto" value="planejado">
              <div class="fd-project-choice-grid" data-project-status-group="create">
                <button type="button" class="fd-project-choice is-active" data-project-status-value="planejado" data-project-status-label="Planejamento">
                  <i class="ri-file-list-3-line"></i>
                  <span>Planejamento</span>
                </button>
                <button type="button" class="fd-project-choice" data-project-status-value="em_andamento" data-project-status-label="Em Andamento">
                  <i class="ri-file-list-3-line"></i>
                  <span>Em Andamento</span>
                </button>
                <button type="button" class="fd-project-choice" data-project-status-value="pausado" data-project-status-label="Pausado">
                  <i class="ri-file-list-3-line"></i>
                  <span>Pausado</span>
                </button>
              </div>
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
  <div class="modal-dialog modal-dialog-centered modal-xl fd-task-modal-dialog">
    <div class="modal-content fd-task-modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/salvar" id="form-tarefa-projeto">
        <input type="hidden" name="projeto_id" value="<?= $projeto_id ?>">
        <input type="hidden" name="tarefa_id" id="tarefaId" value="">
        <input type="hidden" name="coluna" id="tarefaColuna" value="backlog">
        <input type="hidden" name="descricao" id="tarefaDescricao">
        <input type="hidden" name="checklist_json" id="tarefaChecklistJson" value="[]">
        <input type="hidden" name="members_json" id="tarefaMembersJson" value="[]">
        <input type="hidden" name="attachments_json" id="tarefaAttachmentsJson" value="[]">

        <div class="modal-header">
          <h5 class="modal-title" id="modalNovaTarefaLabel">Nova tarefa</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body fd-task-modal-body">
          <div class="row g-4 fd-task-modal-grid">
            <div class="col-lg-8">
              <div class="fd-task-main-scroll">
              <div class="mb-3">
                <label class="form-label small">Titulo da tarefa</label>
                <input type="text" name="titulo" id="tarefaTitulo" class="form-control" placeholder="Ex: Configurar DNS, criar layout da secao hero" required>
              </div>

              <div class="fd-task-actions-bar mb-4">
                <div class="fd-task-action-block">
                  <span class="fd-card-eyebrow">Prioridade</span>
                  <input type="hidden" name="prioridade" id="tarefaPrioridade" value="media">
                  <div class="fd-task-picker" data-picker-key="create" data-picker-type="priority">
                    <button type="button" class="fd-task-picker-trigger" data-picker-trigger="create-priority">
                      <i class="ri-flag-2-line"></i>
                      <span id="tarefaPrioridadeLabel">Media</span>
                      <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="fd-task-picker-menu" data-picker-menu="create-priority">
                      <div class="fd-task-priority-group" data-priority-group="create">
                        <button type="button" class="fd-task-priority-chip" data-value="baixa">Baixa</button>
                        <button type="button" class="fd-task-priority-chip is-active" data-value="media">Media</button>
                        <button type="button" class="fd-task-priority-chip" data-value="alta">Alta</button>
                        <button type="button" class="fd-task-priority-chip" data-value="urgente">Urgente</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fd-task-action-block">
                  <span class="fd-card-eyebrow">Coluna</span>
                  <div class="fd-task-picker" data-picker-key="create" data-picker-type="column">
                    <button type="button" class="fd-task-picker-trigger" data-picker-trigger="create-column">
                      <i class="ri-layout-column-line"></i>
                      <span id="tarefaColunaLabel">Backlog</span>
                      <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="fd-task-picker-menu" data-picker-menu="create-column">
                      <div class="fd-task-column-group" data-column-group="create">
                        <button type="button" class="fd-task-column-chip is-active" data-value="backlog">Backlog</button>
                        <button type="button" class="fd-task-column-chip" data-value="andamento">Em andamento</button>
                        <button type="button" class="fd-task-column-chip" data-value="revisao">Revisao</button>
                        <button type="button" class="fd-task-column-chip" data-value="concluido">Concluido</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fd-task-action-block fd-task-action-block-date">
                  <span class="fd-card-eyebrow">Data de entrega</span>
                  <div class="fd-date-picker fd-task-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
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

              <div class="mb-0">
                <label class="form-label small">Descricao rica</label>
                <div class="fd-task-editor-shell">
                  <div class="fd-task-editor-toolbar" id="tarefaDescricaoToolbar">
                    <span class="ql-formats">
                      <select class="ql-header">
                        <option selected></option>
                        <option value="1"></option>
                        <option value="2"></option>
                      </select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-bold"></button>
                      <button class="ql-italic"></button>
                      <button class="ql-underline"></button>
                    </span>
                    <span class="ql-formats">
                      <select class="ql-color"></select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-list" value="bullet"></button>
                      <button class="ql-list" value="ordered"></button>
                      <button class="ql-list" value="check"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-link"></button>
                      <button class="ql-clean"></button>
                    </span>
                  </div>
                  <div class="fd-task-editor" id="tarefaDescricaoEditor"></div>
                </div>
                <div class="form-text small">Use titulos, cor de texto, listas e checklist como em um card mais rico de operacao.</div>
              </div>

              <div class="fd-task-checklist-shell mt-4">
                <div class="fd-task-checklist-head">
                  <div>
                    <label class="form-label small mb-1">Checklist estruturado</label>
                    <p class="fd-card-subtitle mb-0">Itens reais da tarefa, com progresso salvo no card.</p>
                  </div>
                  <span class="fd-badge fd-badge-neutral" id="tarefaChecklistStats">0/0</span>
                </div>
                <div class="fd-task-checklist-composer">
                  <input type="text" class="form-control" id="tarefaChecklistInput" placeholder="Adicionar item da checklist">
                  <button type="button" class="fd-btn-secondary fd-task-checklist-add" data-checklist-action="add" data-checklist-key="create">
                    <i class="ri-add-line"></i>
                    <span>Adicionar</span>
                  </button>
                </div>
                <div class="fd-task-checklist-list" id="tarefaChecklistList"></div>
              </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="fd-task-side-panel">
                <div class="fd-task-comments-shell fd-task-comments-shell-side">
                  <div class="fd-task-comments-head">
                    <div>
                      <label class="form-label small mb-1">Comentarios e atividade</label>
                      <p class="fd-card-subtitle mb-0">Comentarios ficam disponiveis depois que a tarefa for criada.</p>
                    </div>
                  </div>
                  <div class="fd-task-comments-empty">
                    Salve a tarefa para liberar comentarios e historico colaborativo.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer fd-task-modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar tarefa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditarTarefa" tabindex="-1" aria-labelledby="modalEditarTarefaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl fd-task-modal-dialog">
    <div class="modal-content fd-task-modal-content">
      <form method="post" action="<?= ($base ?? '') ?>/projetos/tarefas/salvar" id="form-editar-tarefa">
        <input type="hidden" name="projeto_id" value="<?= $projeto_id ?>">
        <input type="hidden" name="tarefa_id" id="editTarefaId" value="">
        <input type="hidden" name="coluna" id="editTarefaColuna" value="backlog">
        <input type="hidden" name="descricao" id="editTarefaDescricao">
        <input type="hidden" name="checklist_json" id="editTarefaChecklistJson" value="[]">
        <input type="hidden" name="members_json" id="editTarefaMembersJson" value="[]">
        <input type="hidden" name="attachments_json" id="editTarefaAttachmentsJson" value="[]">

        <div class="modal-header fd-task-modal-header">
          <div class="fd-task-modal-headcopy">
            <h5 class="modal-title" id="modalEditarTarefaLabel">Editar tarefa</h5>
          </div>
          <span class="fd-task-autosave-status" id="editTarefaAutosaveStatus">Salvo</span>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body fd-task-modal-body">
          <div class="row g-4 fd-task-modal-grid">
            <div class="col-lg-8">
              <div class="fd-task-main-scroll">
              <div class="mb-3">
                <label class="form-label small">Titulo da tarefa</label>
                <input type="text" name="titulo" id="editTarefaTitulo" class="form-control" required>
              </div>

              <div class="fd-task-actions-bar mb-4">
                <div class="fd-task-action-block">
                  <span class="fd-card-eyebrow">Prioridade</span>
                  <input type="hidden" name="prioridade" id="editTarefaPrioridade" value="media">
                  <div class="fd-task-picker" data-picker-key="edit" data-picker-type="priority">
                    <button type="button" class="fd-task-picker-trigger" data-picker-trigger="edit-priority">
                      <i class="ri-flag-2-line"></i>
                      <span id="editTarefaPrioridadeLabel">Media</span>
                      <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="fd-task-picker-menu" data-picker-menu="edit-priority">
                      <div class="fd-task-priority-group" data-priority-group="edit">
                        <button type="button" class="fd-task-priority-chip" data-value="baixa">Baixa</button>
                        <button type="button" class="fd-task-priority-chip is-active" data-value="media">Media</button>
                        <button type="button" class="fd-task-priority-chip" data-value="alta">Alta</button>
                        <button type="button" class="fd-task-priority-chip" data-value="urgente">Urgente</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fd-task-action-block">
                  <span class="fd-card-eyebrow">Coluna</span>
                  <div class="fd-task-picker" data-picker-key="edit" data-picker-type="column">
                    <button type="button" class="fd-task-picker-trigger" data-picker-trigger="edit-column">
                      <i class="ri-layout-column-line"></i>
                      <span id="editTarefaColunaLabel">Backlog</span>
                      <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="fd-task-picker-menu" data-picker-menu="edit-column">
                      <div class="fd-task-column-group" data-column-group="edit">
                        <button type="button" class="fd-task-column-chip is-active" data-value="backlog">Backlog</button>
                        <button type="button" class="fd-task-column-chip" data-value="andamento">Em andamento</button>
                        <button type="button" class="fd-task-column-chip" data-value="revisao">Revisao</button>
                        <button type="button" class="fd-task-column-chip" data-value="concluido">Concluido</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fd-task-action-block fd-task-action-block-date">
                  <span class="fd-card-eyebrow">Data de entrega</span>
                  <div class="fd-date-picker fd-task-date-picker" x-data="flowdeskDatePicker('', { placeholder: 'Selecionar data' })" @keydown.escape.window="close()">
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

              <div class="mb-0">
                <label class="form-label small">Descricao rica</label>
                <div class="fd-task-editor-shell">
                  <div class="fd-task-editor-toolbar" id="editTarefaDescricaoToolbar">
                    <span class="ql-formats">
                      <select class="ql-header">
                        <option selected></option>
                        <option value="1"></option>
                        <option value="2"></option>
                      </select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-bold"></button>
                      <button class="ql-italic"></button>
                      <button class="ql-underline"></button>
                    </span>
                    <span class="ql-formats">
                      <select class="ql-color"></select>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-list" value="bullet"></button>
                      <button class="ql-list" value="ordered"></button>
                      <button class="ql-list" value="check"></button>
                    </span>
                    <span class="ql-formats">
                      <button class="ql-link"></button>
                      <button class="ql-clean"></button>
                    </span>
                  </div>
                  <div class="fd-task-editor" id="editTarefaDescricaoEditor"></div>
                </div>
              </div>

              <div class="fd-task-checklist-shell mt-4">
                <div class="fd-task-checklist-head">
                  <div>
                    <label class="form-label small mb-1">Checklist estruturado</label>
                    <p class="fd-card-subtitle mb-0">Transforme a tarefa em entregas menores e acompanhe o progresso.</p>
                  </div>
                  <span class="fd-badge fd-badge-neutral" id="editTarefaChecklistStats">0/0</span>
                </div>
                <div class="fd-task-checklist-composer">
                  <input type="text" class="form-control" id="editTarefaChecklistInput" placeholder="Adicionar item da checklist">
                  <button type="button" class="fd-btn-secondary fd-task-checklist-add" data-checklist-action="add" data-checklist-key="edit">
                    <i class="ri-add-line"></i>
                    <span>Adicionar</span>
                  </button>
                </div>
                <div class="fd-task-checklist-list" id="editTarefaChecklistList"></div>
              </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="fd-task-side-panel">
                <div class="fd-task-comments-shell fd-task-comments-shell-side">
                  <div class="fd-task-comments-head">
                    <div>
                      <label class="form-label small mb-1">Comentarios e atividade</label>
                      <p class="fd-card-subtitle mb-0">Converse sobre a tarefa sem sair do projeto.</p>
                    </div>
                  </div>
                  <div class="fd-task-comments-list" id="editTarefaCommentsList"></div>
                  <div class="fd-task-comment-composer">
                    <textarea class="form-control" id="editTarefaComentarioInput" rows="3" placeholder="Escrever um comentario..."></textarea>
                    <div class="fd-task-comment-actions">
                      <button type="button" class="fd-btn-primary" id="editTarefaComentarioAdicionar">
                        <i class="ri-chat-1-line"></i>
                        <span>Comentar</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

