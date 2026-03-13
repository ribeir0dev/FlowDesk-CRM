<!--
///////////////////////
/// Bloco Editar Projeto
////////////////////////
--->

<div class="modal fade " id="modalEditarProjeto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/ProjetoController.php?acao=atualizarProjeto"
        id="form-editar-projeto">
        <input type="hidden" name="projeto_id" id="editProjetoId" value="">

        <div class="modal-header">
          <h5 class="modal-title">Editar projeto</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <!-- mesmos campos do modalNovoProjeto, com ids edit... para preencher via JS -->
          <!-- ex.: -->
          <div class="mb-3">
            <label class="form-label small">Nome do projeto</label>
            <input type="text" name="nome_projeto" id="editNomeProjeto" class="form-control" required>
          </div>
          <!-- restante campos -->
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
/// Bloco Novo Projeto
////////////////////////
--->

<?php
// modules/modals/modal_novo_projeto.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../../config/db.php';

// Se já buscou clientes em projetos.php, pode reaproveitar.
// Mantendo aqui por compatibilidade:
$stmtCli = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

// valores padrão vindos de modules/content/projetos.php
$oportunidadeId = $oportunidadeId ?? 0;
$clienteIdPadrao = $clienteIdPadrao ?? 0;
$nomeProjetoPadrao = $nomeProjetoPadrao ?? '';
$valorPrevistoPadrao = $valorPrevistoPadrao ?? 0.0;
?>

<div class="modal fade " id="modalNovoProjeto" tabindex="-1" aria-labelledby="modalNovoProjetoLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/ProjetoController.php?acao=criar" id="form-novo-projeto">

        <input type="hidden" name="oportunidade_id" value="<?= (int) $oportunidadeId ?>">

        <div class="modal-header">
          <h5 class="modal-title" id="modalNovoProjetoLabel">Novo projeto</h5>
          <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Nome do projeto -->
            <div class="col-12">
              <label class="form-label small">Nome do projeto</label>
              <input type="text" name="nome_projeto" class="form-control"
                placeholder="Ex: Landing page Black Friday Cliente X"
                value="<?= htmlspecialchars($nomeProjetoPadrao) ?>" required>
            </div>

            <!-- Tipo do projeto -->
            <div class="col-md-6">
              <label class="form-label small">Tipo do projeto</label>
              <select name="tipo_projeto" class="form-select form-select-sm" required>
                <option value="landing_page">Landing Page</option>
                <option value="configuracao">Configuração</option>
                <option value="alteracao">Alteração</option>
                <option value="otimizacao">Otimização</option>
                <option value="integracao">Integração</option>
                <option value="design">Design</option>
                <option value="outro">Outro</option>
              </select>
            </div>

            <!-- Cliente -->
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

            <!-- Início -->
            <div class="col-md-6">
              <label class="form-label small">Início do projeto</label>
              <div class="d-flex align-items-center gap-2">
                <input type="date" id="dataInicioProjeto" name="data_inicio" class="form-control form-control-sm"
                  value="<?= date('Y-m-d') ?>"
                  style="position:absolute; opacity:0; pointer-events:none; width:0; height:0;" required>

                <button type="button" class="btn btn-outline-secondary btn-sm"
                  onclick="document.getElementById('dataInicioProjeto').showPicker();">
                  Escolher data
                </button>

                <span class="small text-muted">
                  <span id="labelDataInicioProjeto"><?= date('d/m/Y') ?></span>
                </span>
              </div>
            </div>

            <!-- Entrega -->
            <div class="col-md-6">
              <label class="form-label small">Data de entrega</label>
              <div class="d-flex align-items-center gap-2">
                <input type="date" id="dataEntregaProjeto" name="data_entrega" class="form-control form-control-sm"
                  style="position:absolute; opacity:0; pointer-events:none; width:0; height:0;" required>

                <button type="button" class="btn btn-outline-secondary btn-sm"
                  onclick="document.getElementById('dataEntregaProjeto').showPicker();">
                  Escolher data
                </button>

                <span class="small text-muted">
                  <span id="labelDataEntregaProjeto">—/—/----</span>
                </span>
              </div>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label small">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option value="planejado" selected>Planejado</option>
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
                <option value="pausado">Pausado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>

            <!-- Descrição -->
            <div class="col-12">
              <label class="form-label small">Descrição (opcional)</label>
              <textarea name="descricao" class="form-control" rows="3"
                placeholder="Resumo do escopo do projeto, objetivos, observações importantes."></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            Salvar projeto
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!--
///////////////////////
/// Bloco Tarefa Projeto
////////////////////////
--->

<?php
// modules/modals/modal_tarefa_projeto.php
if (session_status() !== PHP_SESSION_ACTIVE)
  session_start();
require_once __DIR__ . '/../../config/db.php';

$projeto_id = (int) ($_GET['id'] ?? 0); // mesmo id usado em projeto_detalhe.php
?>
<div class="modal fade " id="modalNovaTarefa" tabindex="-1" aria-labelledby="modalNovaTarefaLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/app/Controllers/ProjetoController.php?acao=salvarTarefa" id="form-tarefa-projeto">
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
            <label class="form-label small">Título da tarefa</label>
            <input type="text" name="titulo" id="tarefaTitulo" class="form-control"
              placeholder="Ex: Configurar DNS, Criar layout da seção hero" required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descrição (opcional)</label>
            <textarea name="descricao" id="tarefaDescricao" class="form-control" rows="3"
              placeholder="Detalhes, links, checklist simples, etc."></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label small">Coluna</label>
            <select name="coluna_select" id="tarefaColunaSelect" class="form-select form-select-sm">
              <option value="backlog">Backlog</option>
              <option value="andamento">Em andamento</option>
              <option value="revisao">Revisão</option>
              <option value="concluido">Concluído</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small">Data de entrega</label>
            <input type="date" name="data_entrega" id="tarefaDataEntrega" class="form-control form-control-sm">
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

<!-- Modal reaproveitado para edição -->
<div class="modal fade " id="modalEditarTarefa" tabindex="-1" aria-labelledby="modalEditarTarefaLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <!-- usa o mesmo form, apenas preenchido via JS -->
      <form method="post" action="/app/Controllers/ProjetoController.php?acao=atualizarTarefa" id="form-editar-tarefa">
        <input type="hidden" name="projeto_id" value="<?= $projeto_id ?>">
        <input type="hidden" name="tarefa_id" id="editTarefaId" value="">
        <input type="hidden" name="coluna" id="editTarefaColuna" value="backlog">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarTarefaLabel">Editar tarefa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small">Título da tarefa</label>
            <input type="text" name="titulo" id="editTarefaTitulo" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label small">Descrição (opcional)</label>
            <textarea name="descricao" id="editTarefaDescricao" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label small">Coluna</label>
            <select name="coluna_select" id="editTarefaColunaSelect" class="form-select form-select-sm">
              <option value="backlog">Backlog</option>
              <option value="andamento">Em andamento</option>
              <option value="revisao">Revisão</option>
              <option value="concluido">Concluído</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small">Data de entrega</label>
            <input type="date" name="data_entrega" id="editTarefaDataEntrega" class="form-control form-control-sm">
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