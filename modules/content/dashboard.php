<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/DashboardModel.php';

require_once __DIR__ . '/../../app/Models/FinanceiroModel.php';

$model = new DashboardModel($pdo);

// mês filtrado
$mes_atual_param = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes_atual_param)) {
    $mes_atual_param = date('Y-m');
}
$ano = (int) substr($mes_atual_param, 0, 4);
$mes = (int) substr($mes_atual_param, 5, 2);
$mes_label = date('m/Y', strtotime($mes_atual_param . '-01'));



// totais financeiro
[$totalEntradasMes, $totalSaidasMes] = $model->totaisFinanceiroMes($ano, $mes);
$percent_entradas = $model->variacaoEntradasMes($ano, $mes);
$percent_saidas = $model->variacaoSaidasMes($ano, $mes);
$diferencaEntradas = $model->diferencaEntradasMes($ano, $mes);
$diferencaSaidas = $model->diferencaSaidasMes($ano, $mes);

// novos clientes
$novosClientesMes = $model->novosClientesMes($ano, $mes);

// tarefas
$status_filtro = $_GET['status'] ?? 'todos';
$tasksHoje = $model->tarefasPorStatus($status_filtro);

// hospedagens
$hoje = new DateTime();
$hospAtivas = $model->hospedagensAtivas($hoje);

// mapa de ícones
$hojeStr = $hoje->format('Y-m-d');
$mapIconesHosp = [
    'wordpress' => ['icon' => 'ri-wordpress-fill', 'color' => '#81BEF0', 'label' => 'WordPress'],
    'vps' => ['icon' => 'ri-cloud-line', 'color' => '#F0AC81', 'label' => 'VPS'],
    'dominio' => ['icon' => 'ri-global-line', 'color' => '#C481F0', 'label' => 'Domínio'],
];
?>


<div class="dashboard-module">

    <!-- 1ª linha: frase + KPIs + filtro -->
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
        <div>
            <h5 class="mb-1">Vamos começar o dia</h5>
            
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted">
                <strong><?= htmlspecialchars($mes_label) ?></strong>
            </span>

            <form method="get" class="position-relative">
                <input type="hidden" name="mod" value="dashboard">
                <input type="text" id="filtroMes" name="mes" value="<?= htmlspecialchars($mes_atual_param) ?>"
                    class="form-control form-control-sm hidden" style="max-width:120px;">

                <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center"
                    onclick="pickerMes.open();">
                    <i class="ri-calendar-event-line"></i>
                </button>

            </form>
        </div>

        <script>
            document.getElementById('filtroMes').addEventListener('change', function () {
                this.form.submit();
            });
        </script>


    </div>

    <!-- 2ª linha: 3 cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-kpi  h-100">
                <div class="card-body d-flex flex-column justify-content-between">

                    <!-- Topo: título + ícone pequeno -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="card-kpi-label"> <span class="card-kpi-icon">
                                    <i class="ri-arrow-left-down-long-fill"></i>
                                </span>Entradas</span>
                        </div>
                    </div>

                    <!-- Valor principal -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="card-kpi-value sensitive-value">
                            R$<?= number_format($totalEntradasMes, 2, ',', '.') ?>
                        </h3>
                        <span class="card-kpi-trend sensitive-value text-success">
                            <?php if ($percent_entradas !== null): ?>
                                <?php
                                $cls = $percent_entradas >= 0 ? 'text-success' : 'text-danger';
                                $sinal = $percent_entradas >= 0 ? '+' : '';
                                ?>
                                <span class="small <?= $cls ?>">
                                    <?= $sinal . number_format($percent_entradas, 2, ',', '.') ?>%
                                </span>
                            <?php else: ?>
                                <span class="card-kpi-trend sensitive-value text-muted">
                                    0%
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Rodapé (texto secundário / ação) -->
                    <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                        <?php if ($diferencaEntradas !== null): ?>
                            <?php
                            $clsValor = $diferencaEntradas >= 0 ? 'text-success' : 'text-danger';
                            $sinalVal = $diferencaEntradas >= 0 ? '+' : '';
                            ?>
                            <span class="card-kpi-sub sensitive-value <?= $clsValor ?>">
                                <?= $sinalVal ?>R$<?= number_format(abs($diferencaEntradas), 2, ',', '.') ?>
                                em relação ao mês passado
                            </span>
                        <?php else: ?>
                            <span class="card-kpi-sub sensitive-value text-muted">
                                Sem comparação com o mês passado
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-kpi  h-100">
                <div class="card-body d-flex flex-column justify-content-between">

                    <!-- Topo: título + ícone pequeno -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="card-kpi-label"> <span class="card-kpi-icon">
                                    <i class="ri-arrow-right-up-long-fill"></i>
                                </span>Saídas</span>
                        </div>
                    </div>

                    <!-- Valor principal -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="card-kpi-value sensitive-value">
                            R$<?= number_format($totalSaidasMes, 2, ',', '.') ?>
                        </h3>
                        <span class="card-kpi-trend sensitive-value text-success">
                            <?php if ($percent_saidas !== null): ?>
                                <?php
                                $cls = $percent_saidas >= 0 ? 'text-danger' : 'text-success';
                                $sinal = $percent_saidas >= 0 ? '+' : '';
                                ?>
                                <span class="small <?= $cls ?>">
                                    <?= $sinal . number_format($percent_saidas, 2, ',', '.') ?>%
                                </span>
                            <?php else: ?>
                                <span class="card-kpi-trend sensitive-value text-muted">
                                    0%
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Rodapé (texto secundário / ação) -->
                    <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                        <?php if ($diferencaSaidas !== null): ?>
                            <?php
                            $clsValor = $diferencaSaidas >= 0 ? 'text-danger' : 'text-success';
                            $sinalVal = $diferencaSaidas >= 0 ? '+' : '';
                            ?>
                            <span class="card-kpi-sub sensitive-value <?= $clsValor ?>">
                                <?= $sinalVal ?>R$<?= number_format(abs($diferencaSaidas), 2, ',', '.') ?>
                                em relação ao mês passado
                            </span>
                        <?php else: ?>
                            <span class="card-kpi-sub sensitive-value text-muted">
                                Sem comparação com o mês passado
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-kpi  h-100">
                <div class="card-body d-flex flex-column justify-content-between">

                    <!-- Topo: título + ícone pequeno -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="card-kpi-label"> <span class="card-kpi-icon">
                                    <i class="ri-group-fill"></i>
                                </span>Novos Clientes</span>
                        </div>
                    </div>

                    <!-- Valor principal -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="card-kpi-value sensitive-value">
                            <?= $novosClientesMes ?> clientes
                        </h3>
                        <span class="card-kpi-trend sensitive-value text-success">
                            <i class="ri-arrow-up-s-line me-1"></i>+33,15%
                        </span>
                    </div>

                    <!-- Rodapé (texto secundário / ação) -->
                    <div class="d-flex justify-content-between align-items-center card-kpi-footer">
                        <span class="card-kpi-sub sensitive-value">+00% em relação ao mês passado</span>
                    </div>

                </div>
            </div>
        </div>


        <!-- 3ª linha: tasks do dia + overview hospedagens -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card  h-100">
                    <div class="card-body">
                        <?php
                        $status_filtro = $_GET['status'] ?? 'todos';
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="small text-muted fw-bold fs-5"> <i
                                        class="ri-calendar-check-fill card-kpi-icon me-2 p-2 bg-light fs-5"></i>Tasks
                                    de hoje</span>
                                <span class="small text-muted ms-2"><?= date('d/m/Y') ?></span>
                            </div>
                            <?php $status_filtro = $_GET['status'] ?? 'todos'; ?>
                        </div>
                        <?php $status_filtro = $_GET['status'] ?? 'todos'; ?>

                        <div class="filtros-tarefas mb-3">
                            <a href="?mod=dashboard&status=todos"
                                class="btn btn-status <?= $status_filtro === 'todos' ? 'btn-status-active' : '' ?>">Todos</a>

                            <a href="?mod=dashboard&status=pendente"
                                class="btn btn-status <?= $status_filtro === 'pendente' ? 'btn-status-active' : '' ?>">Pendente</a>

                            <a href="?mod=dashboard&status=andamento"
                                class="btn btn-status <?= $status_filtro === 'andamento' ? 'btn-status-active' : '' ?>">Em
                                andamento</a>

                            <a href="?mod=dashboard&status=concluida"
                                class="btn btn-status <?= $status_filtro === 'concluida' ? 'btn-status-active' : '' ?>">Concluída</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tarefa</th>
                                        <th>Projeto</th>
                                        <th>Status</th>
                                        <th>Prazo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tasksHoje)): ?>
                                        <tr>
                                            <td colspan="4" class="text-muted small text-center">
                                                Nenhuma task ativa para os projetos.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $mapCol = [
                                            'backlog' => ['label' => 'Pendente', 'class' => 'bg-warning text-dark'],
                                            'andamento' => ['label' => 'Em andamento', 'class' => 'bg-info text-dark'],
                                            'revisao' => ['label' => 'Em revisão', 'class' => 'bg-secondary'],
                                            'concluido' => ['label' => 'Concluída', 'class' => 'bg-success'],
                                        ];
                                        ?>
                                        <?php foreach ($tasksHoje as $t): ?>
                                            <?php $info = $mapCol[$t['coluna']] ?? $mapCol['backlog']; ?>
                                            <tr>
                                                <td><?= htmlspecialchars($t['titulo']) ?></td>
                                                <td><?= htmlspecialchars($t['nome_projeto']) ?></td>
                                                <td>
                                                    <span class="badge <?= $info['class'] ?>"><?= $info['label'] ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($t['data_entrega'])): ?>
                                                        <?= date('d/m/Y', strtotime($t['data_entrega'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sem prazo</span>
                                                    <?php endif; ?>
                                                </td>


                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card  h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted fs-5 fw-bold">
                                <i class="ri-cloud-fill card-kpi-icon me-2 p-2 bg-light fs-5"></i>Hospedagens Ativas
                            </span>
                        </div>

                        <?php if (empty($hospAtivas)): ?>
                            <p class="small text-muted mb-0">Nenhuma hospedagem ativa no momento.</p>
                        <?php else: ?>
                            <ul class="hosp-list">
                                <?php foreach ($hospAtivas as $h): ?>
                                    <?php
                                    $info = $mapIconesHosp[$h['tipo']] ?? $mapIconesHosp['dominio'];

                                    $dtHoje = new DateTime($hojeStr);
                                    $dtFim = new DateTime($h['data_fim']);
                                    $diff = $dtHoje->diff($dtFim, true);
                                    $dias = $diff->days; // inteiro de dias de hoje até data_fim
                            
                                    $textoDias = $dias === 0
                                        ? 'expira hoje'
                                        : ($dias === 1 ? '1 dia restante' : $dias . ' dias restantes');
                                    ?>
                                    <li class="hosp-list-item">
                                        <div class="d-flex align-items-center">
                                            <div class="hosp-list-icon" style="color: <?= htmlspecialchars($info['color']) ?>;">
                                                <i class="<?= htmlspecialchars($info['icon']) ?>"></i>
                                            </div>
                                            <span><?= htmlspecialchars($h['nome']) ?></span>
                                        </div>
                                        <span class="hosp-list-days"><?= $textoDias ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    </div>