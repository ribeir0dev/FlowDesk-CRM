<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/DashboardModel.php';
require_once __DIR__ . '/../../../app/Models/FinanceiroModel.php';

$model = new DashboardModel($pdo);

$mes_atual_param = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes_atual_param)) {
    $mes_atual_param = date('Y-m');
}

$ano = (int) substr($mes_atual_param, 0, 4);
$mes = (int) substr($mes_atual_param, 5, 2);
$mes_label = fd_format_month_year($mes_atual_param . '-01');

[$totalEntradasMes, $totalSaidasMes] = $model->totaisFinanceiroMes($ano, $mes);
$percent_entradas = $model->variacaoEntradasMes($ano, $mes);
$percent_saidas = $model->variacaoSaidasMes($ano, $mes);
$diferencaEntradas = $model->diferencaEntradasMes($ano, $mes);
$diferencaSaidas = $model->diferencaSaidasMes($ano, $mes);

$novosClientesMes = $model->novosClientesMes($ano, $mes);

$status_filtro = $_GET['status'] ?? 'todos';
$tasksHoje = $model->tarefasPorStatus($status_filtro);

$hoje = new DateTime();
$hospAtivas = $model->hospedagensAtivas($hoje);
$workspaceContext = $model->contextoWorkspace();
$workspaceCounts = $model->contagensOperacionais();

$totalSetupRegistrado = array_sum($workspaceCounts);
$dashboardInicial = $totalSetupRegistrado === 0;
$onboardingRecemConcluido = isset($_GET['onboarding']) && $_GET['onboarding'] === 'ok';

$segmentoLabels = [
    'freelancer' => 'Freelancer',
    'studio' => 'Studio criativo',
    'agencia' => 'Agencia',
    'consultoria' => 'Consultoria',
];
$objetivoLabels = [
    'vender_mais' => 'Organizar vendas e pipeline',
    'entregar_melhor' => 'Organizar projetos e entregas',
    'controlar_financas' => 'Controlar financeiro e cobrancas',
];
$equipeLabels = [
    'solo' => 'Solo',
    '2_5' => 'Equipe de 2 a 5 pessoas',
    '6_10' => 'Equipe de 6 a 10 pessoas',
    '11_plus' => 'Equipe com mais de 10 pessoas',
];
$volumeLabels = [
    'ate_10' => 'Ate 10 clientes ativos',
    '11_25' => 'Entre 11 e 25 clientes',
    '26_50' => 'Entre 26 e 50 clientes',
    '50_plus' => 'Mais de 50 clientes ativos',
];
$moduloLabels = [
    'crm' => 'Clientes e CRM',
    'pipeline' => 'Pipeline comercial',
    'projetos' => 'Projetos e entregas',
    'financeiro' => 'Financeiro',
];

$acoesIniciais = [
    'crm' => ['label' => 'Cadastrar primeiro cliente', 'url' => ($base ?? '') . '/clientes', 'icon' => 'ri-user-add-line'],
    'pipeline' => ['label' => 'Abrir pipeline comercial', 'url' => ($base ?? '') . '/pipeline', 'icon' => 'ri-git-branch-line'],
    'projetos' => ['label' => 'Criar primeiro projeto', 'url' => ($base ?? '') . '/projetos', 'icon' => 'ri-kanban-view'],
    'financeiro' => ['label' => 'Entrar no financeiro', 'url' => ($base ?? '') . '/financeiro', 'icon' => 'ri-wallet-3-line'],
];

$objetivoParaModulo = [
    'vender_mais' => 'pipeline',
    'entregar_melhor' => 'projetos',
    'controlar_financas' => 'financeiro',
];

$moduloPrioritario = $workspaceContext['onboarding_modulo_inicial'] ?? '';
if ($moduloPrioritario === '' && !empty($workspaceContext['objetivo_principal'])) {
    $moduloPrioritario = $objetivoParaModulo[$workspaceContext['objetivo_principal']] ?? 'crm';
}
if ($moduloPrioritario === '') {
    $moduloPrioritario = 'crm';
}

$proximaAcao = $acoesIniciais[$moduloPrioritario] ?? $acoesIniciais['crm'];
$workspaceNome = $workspaceContext['nome'] ?? ($_SESSION['current_workspace_nome'] ?? 'seu workspace');

$setupChecklist = [
    ['label' => 'Clientes', 'value' => (int) ($workspaceCounts['clientes'] ?? 0)],
    ['label' => 'Oportunidades', 'value' => (int) ($workspaceCounts['oportunidades'] ?? 0)],
    ['label' => 'Projetos', 'value' => (int) ($workspaceCounts['projetos'] ?? 0)],
    ['label' => 'Tarefas', 'value' => (int) ($workspaceCounts['tarefas'] ?? 0)],
    ['label' => 'Hospedagens', 'value' => (int) ($workspaceCounts['hospedagens'] ?? 0)],
];

$hojeStr = $hoje->format('Y-m-d');
$mapIconesHosp = [
    'wordpress' => ['icon' => 'ri-wordpress-fill', 'color' => '#81BEF0', 'label' => 'WordPress'],
    'vps'       => ['icon' => 'ri-cloud-line', 'color' => '#F0AC81', 'label' => 'VPS'],
    'dominio'   => ['icon' => 'ri-global-line', 'color' => '#C481F0', 'label' => 'Dominio'],
];
$mesesPicker = [
    '01' => 'Jan',
    '02' => 'Fev',
    '03' => 'Mar',
    '04' => 'Abr',
    '05' => 'Mai',
    '06' => 'Jun',
    '07' => 'Jul',
    '08' => 'Ago',
    '09' => 'Set',
    '10' => 'Out',
    '11' => 'Nov',
    '12' => 'Dez',
];

function fdMoney(float $value): string
{
    return 'R$' . number_format($value, 2, ',', '.');
}
?>

<div class="fd-dashboard">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Resumo operacional</p>
            <p class="fd-page-subtitle">Acompanhe os principais numeros e tarefas do mes atual.</p>
        </div>

        <div class="fd-page-actions">
            <form method="get" class="fd-inline-form">
                <?php if ($status_filtro !== 'todos'): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filtro) ?>">
                <?php endif; ?>

                <div
                    class="fd-month-picker"
                    x-data="flowdeskMonthPicker('<?= htmlspecialchars($mes_atual_param, ENT_QUOTES) ?>')"
                    @keydown.escape.window="close()"
                >
                    <input type="hidden" name="mes" x-model="selectedValue">

                    <button
                        type="button"
                        class="fd-month-picker-trigger"
                        @click="toggle()"
                        :aria-expanded="open.toString()"
                    >
                        <span class="fd-month-picker-trigger-icon">
                            <i class="ri-calendar-event-line"></i>
                        </span>
                        <span class="fd-month-picker-trigger-copy">
                            <span class="fd-month-picker-trigger-label">Periodo</span>
                            <strong x-text="triggerLabel"></strong>
                        </span>
                        <i class="ri-arrow-down-s-line fd-month-picker-trigger-arrow"></i>
                    </button>

                    <div
                        class="fd-month-picker-panel"
                        x-show="open"
                        x-cloak
                        x-transition.opacity.scale.origin.top.right
                        @click.outside="close()"
                    >
                        <div class="fd-month-picker-head">
                            <button type="button" class="fd-month-picker-nav" @click="prevYear()">
                                <i class="ri-arrow-left-s-line"></i>
                            </button>
                            <div class="fd-month-picker-head-copy">
                                <span class="fd-month-picker-head-label">Selecione o mes</span>
                                <strong x-text="displayYear"></strong>
                            </div>
                            <button type="button" class="fd-month-picker-nav" @click="nextYear()">
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </div>

                        <div class="fd-month-picker-grid">
                            <?php foreach ($mesesPicker as $numeroMes => $labelMes): ?>
                                <button
                                    type="button"
                                    class="fd-month-picker-month"
                                    :class="monthButtonClass('<?= $numeroMes ?>')"
                                    @click="selectMonth('<?= $numeroMes ?>')"
                                >
                                    <?= $labelMes ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="fd-month-picker-footer">
                            <button type="button" class="fd-month-picker-link" @click="resetToCurrent()">
                                Este mes
                            </button>
                            <button type="button" class="fd-month-picker-apply" @click="submit()">
                                Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <div class="fd-page-alerts" id="dashboardFeedback"></div>

    <?php if ($dashboardInicial): ?>
        <section class="fd-card fd-dashboard-setup-card">
            <div class="fd-dashboard-setup-top">
                <div class="fd-dashboard-setup-copy">
                    <div>
                        <p class="fd-card-eyebrow">Conta pronta para comecar</p>
                        <h3 class="fd-dashboard-setup-title">O workspace <?= htmlspecialchars($workspaceNome) ?> ja tem contexto. Falta so o primeiro movimento real.</h3>
                        <p class="fd-dashboard-setup-subtitle">Com base no onboarding, o melhor ponto de partida agora e <strong><?= htmlspecialchars($moduloLabels[$moduloPrioritario] ?? 'Clientes e CRM') ?></strong>.</p>
                    </div>

                    <div class="fd-dashboard-setup-tags">
                        <?php if (!empty($workspaceContext['segmento'])): ?>
                            <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($segmentoLabels[$workspaceContext['segmento']] ?? $workspaceContext['segmento']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($workspaceContext['objetivo_principal'])): ?>
                            <span class="fd-badge fd-badge-info"><?= htmlspecialchars($objetivoLabels[$workspaceContext['objetivo_principal']] ?? $workspaceContext['objetivo_principal']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($workspaceContext['onboarding_tamanho_equipe'])): ?>
                            <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($equipeLabels[$workspaceContext['onboarding_tamanho_equipe']] ?? $workspaceContext['onboarding_tamanho_equipe']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($workspaceContext['onboarding_volume_clientes'])): ?>
                            <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($volumeLabels[$workspaceContext['onboarding_volume_clientes']] ?? $workspaceContext['onboarding_volume_clientes']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="fd-dashboard-setup-side">
                    <div class="fd-dashboard-setup-callout">
                        <strong>Proximo passo</strong>
                        <?php if (!empty($workspaceContext['onboarding_migrar_dados'])): ?>
                            <p>Como voce vai migrar dados, vale abrir o modulo principal e concentrar a base inicial nele primeiro.</p>
                        <?php else: ?>
                            <p>Como a conta vai comecar do zero, cadastre hoje o primeiro item real da operacao e deixe o painel ganhar vida.</p>
                        <?php endif; ?>
                    </div>

                    <div class="fd-dashboard-setup-actions">
                        <a href="<?= htmlspecialchars($proximaAcao['url']) ?>" class="fd-btn-primary">
                            <i class="<?= htmlspecialchars($proximaAcao['icon']) ?>"></i>
                            <span><?= htmlspecialchars($proximaAcao['label']) ?></span>
                        </a>
                        <a href="<?= ($base ?? '') ?>/configuracoes" class="fd-btn-secondary">
                            <i class="ri-settings-3-line"></i>
                            <span>Revisar conta</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="fd-dashboard-setup-stats">
                <?php foreach ($setupChecklist as $item): ?>
                    <div class="fd-dashboard-setup-stat">
                        <span><?= htmlspecialchars($item['label']) ?></span>
                        <strong><?= (int) $item['value'] ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($onboardingRecemConcluido): ?>
        <section class="fd-card fd-dashboard-success-card">
            <div>
                <p class="fd-card-eyebrow">Onboarding concluido</p>
                <h3 class="fd-dashboard-success-title">Configuracao inicial salva com sucesso</h3>
                <p class="fd-dashboard-success-subtitle">O dashboard agora segue a rotina real do workspace. Conforme clientes, oportunidades e projetos entrarem, esses blocos vao ficando mais completos.</p>
            </div>
        </section>
    <?php endif; ?>

    <section class="fd-kpi-grid">
        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green">
                        <i class="ri-arrow-left-down-long-fill"></i>
                    </span>
                    Entradas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value"><?= fdMoney((float) $totalEntradasMes) ?></h3>

                <?php if ($percent_entradas !== null): ?>
                    <?php
                    $cls = $percent_entradas >= 0 ? 'fd-trend-positive' : 'fd-trend-negative';
                    $sinal = $percent_entradas >= 0 ? '+' : '';
                    ?>
                    <span class="fd-kpi-trend <?= $cls ?>">
                        <?= $sinal . number_format($percent_entradas, 2, ',', '.') ?>%
                    </span>
                <?php else: ?>
                    <span class="fd-kpi-trend fd-trend-neutral">0%</span>
                <?php endif; ?>
            </div>

            <div class="fd-kpi-footer">
                <?php if ($diferencaEntradas !== null): ?>
                    <?php
                    $clsValor = $diferencaEntradas >= 0 ? 'fd-trend-positive' : 'fd-trend-negative';
                    $sinalVal = $diferencaEntradas >= 0 ? '+' : '';
                    ?>
                    <span class="fd-kpi-note <?= $clsValor ?>">
                        <?= $sinalVal ?><?= fdMoney((float) abs($diferencaEntradas)) ?> em relacao ao mes passado
                    </span>
                <?php else: ?>
                    <span class="fd-kpi-note fd-trend-neutral">Sem comparacao com o mes passado</span>
                <?php endif; ?>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red">
                        <i class="ri-arrow-right-up-long-fill"></i>
                    </span>
                    Saidas
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value"><?= fdMoney((float) $totalSaidasMes) ?></h3>

                <?php if ($percent_saidas !== null): ?>
                    <?php
                    $cls = $percent_saidas >= 0 ? 'fd-trend-negative' : 'fd-trend-positive';
                    $sinal = $percent_saidas >= 0 ? '+' : '';
                    ?>
                    <span class="fd-kpi-trend <?= $cls ?>">
                        <?= $sinal . number_format($percent_saidas, 2, ',', '.') ?>%
                    </span>
                <?php else: ?>
                    <span class="fd-kpi-trend fd-trend-neutral">0%</span>
                <?php endif; ?>
            </div>

            <div class="fd-kpi-footer">
                <?php if ($diferencaSaidas !== null): ?>
                    <?php
                    $clsValor = $diferencaSaidas >= 0 ? 'fd-trend-negative' : 'fd-trend-positive';
                    $sinalVal = $diferencaSaidas >= 0 ? '+' : '';
                    ?>
                    <span class="fd-kpi-note <?= $clsValor ?>">
                        <?= $sinalVal ?><?= fdMoney((float) abs($diferencaSaidas)) ?> em relacao ao mes passado
                    </span>
                <?php else: ?>
                    <span class="fd-kpi-note fd-trend-neutral">Sem comparacao com o mes passado</span>
                <?php endif; ?>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-violet">
                        <i class="ri-group-fill"></i>
                    </span>
                    Novos clientes
                </span>
            </div>

            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value sensitive-value"><?= (int) $novosClientesMes ?> clientes</h3>
                <span class="fd-kpi-trend fd-trend-neutral">No periodo selecionado</span>
            </div>

            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Baseado no mes <?= htmlspecialchars($mes_label) ?></span>
            </div>
        </article>
    </section>

    <section class="fd-dashboard-grid">
        <article class="fd-card">
            <div class="fd-card-header">
                <div>
                    <p class="fd-card-title">
                        <span class="fd-section-icon">
                            <i class="ri-calendar-check-fill"></i>
                        </span>
                        Tasks de hoje
                    </p>
                    <p class="fd-card-subtitle"><?= fd_format_date('now') ?></p>
                </div>
            </div>

            <div class="fd-filter-row">
                <a href="<?= ($base ?? '') ?>/dashboard?status=todos&mes=<?= urlencode($mes_atual_param) ?>" class="fd-chip <?= $status_filtro === 'todos' ? 'is-active' : '' ?>">Todos</a>
                <a href="<?= ($base ?? '') ?>/dashboard?status=pendente&mes=<?= urlencode($mes_atual_param) ?>" class="fd-chip <?= $status_filtro === 'pendente' ? 'is-active' : '' ?>">Pendente</a>
                <a href="<?= ($base ?? '') ?>/dashboard?status=andamento&mes=<?= urlencode($mes_atual_param) ?>" class="fd-chip <?= $status_filtro === 'andamento' ? 'is-active' : '' ?>">Em andamento</a>
                <a href="<?= ($base ?? '') ?>/dashboard?status=concluida&mes=<?= urlencode($mes_atual_param) ?>" class="fd-chip <?= $status_filtro === 'concluida' ? 'is-active' : '' ?>">Concluida</a>
            </div>

            <div class="fd-table-wrap">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th class="fd-task-check-col">Avancar</th>
                            <th>Tarefa</th>
                            <th>Projeto</th>
                            <th>Status</th>
                            <th>Prazo</th>
                        </tr>
                    </thead>
                    <tbody data-status-filter="<?= htmlspecialchars($status_filtro) ?>">
                        <?php if (empty($tasksHoje)): ?>
                            <tr>
                                <td colspan="5" class="fd-empty-state">
                                    <?php if ($dashboardInicial): ?>
                                        Seu workspace ainda nao tem projetos ou tarefas. Comece por <strong><?= htmlspecialchars($proximaAcao['label']) ?></strong> e o painel passa a refletir sua rotina.
                                    <?php else: ?>
                                        Nenhuma task ativa para os projetos.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $mapCol = [
                                'backlog'   => ['label' => 'Pendente', 'class' => 'fd-badge-warning'],
                                'andamento' => ['label' => 'Em andamento', 'class' => 'fd-badge-info'],
                                'revisao'   => ['label' => 'Em revisao', 'class' => 'fd-badge-neutral'],
                                'concluido' => ['label' => 'Concluida', 'class' => 'fd-badge-success'],
                            ];
                            ?>
                            <?php foreach ($tasksHoje as $t): ?>
                                <?php $info = $mapCol[$t['coluna']] ?? $mapCol['backlog']; ?>
                                <?php
                                $nextColuna = match ($t['coluna']) {
                                    'backlog' => 'andamento',
                                    'andamento' => 'revisao',
                                    'revisao' => 'concluido',
                                    default => '',
                                };
                                $isConcluida = $t['coluna'] === 'concluido';
                                ?>
                                <tr class="fd-dashboard-task-row" data-task-id="<?= (int) $t['id'] ?>" data-coluna="<?= htmlspecialchars($t['coluna']) ?>">
                                    <td class="fd-task-check-col">
                                        <label class="fd-task-advance" title="<?= $isConcluida ? 'Task ja concluida' : 'Avancar tarefa para a proxima etapa' ?>">
                                            <input
                                                type="checkbox"
                                                class="fd-task-advance-input js-dashboard-task-advance"
                                                data-id="<?= (int) $t['id'] ?>"
                                                data-current-coluna="<?= htmlspecialchars($t['coluna']) ?>"
                                                data-next-coluna="<?= htmlspecialchars($nextColuna) ?>"
                                                <?= $isConcluida || $nextColuna === '' ? 'checked disabled' : '' ?>
                                            >
                                            <span class="fd-task-advance-box">
                                                <i class="ri-check-line"></i>
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <a class="fd-task-title-link" href="<?= ($base ?? '') ?>/projeto?id=<?= (int) ($t['projeto_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($t['titulo']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a class="fd-task-project-link" href="<?= ($base ?? '') ?>/projeto?id=<?= (int) ($t['projeto_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($t['nome_projeto']) ?>
                                        </a>
                                    </td>
                                    <td class="fd-task-status-cell">
                                        <span class="fd-badge <?= $info['class'] ?>">
                                            <?= $info['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['data_entrega'])): ?>
                                            <?= fd_format_date($t['data_entrega']) ?>
                                        <?php else: ?>
                                            <span class="fd-text-muted">Sem prazo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="fd-card">
            <div class="fd-card-header">
                <div>
                    <p class="fd-card-title">
                        <span class="fd-section-icon">
                            <i class="ri-cloud-fill"></i>
                        </span>
                        Hospedagens ativas
                    </p>
                    <p class="fd-card-subtitle">Servicos proximos da renovacao</p>
                </div>
            </div>

            <?php if (empty($hospAtivas)): ?>
                <p class="fd-empty-copy">
                    <?php if ($dashboardInicial): ?>
                        Nenhuma hospedagem cadastrada ainda. Se essa frente faz parte da sua operacao, vale registrar depois que os primeiros clientes e projetos estiverem no sistema.
                    <?php else: ?>
                        Nenhuma hospedagem ativa no momento.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($hospAtivas as $h): ?>
                        <?php
                        $info = $mapIconesHosp[$h['tipo']] ?? $mapIconesHosp['dominio'];

                        $dtHoje = new DateTime($hojeStr);
                        $dtFim = new DateTime($h['data_fim']);
                        $diff = $dtHoje->diff($dtFim, true);
                        $dias = $diff->days;

                        $textoDias = $dias === 0
                            ? 'expira hoje'
                            : ($dias === 1 ? '1 dia restante' : $dias . ' dias restantes');
                        ?>
                        <li class="fd-list-item">
                            <div class="fd-list-main">
                                <span class="fd-list-icon" style="color: <?= htmlspecialchars($info['color']) ?>;">
                                    <i class="<?= htmlspecialchars($info['icon']) ?>"></i>
                                </span>
                                <span class="fd-list-name"><?= htmlspecialchars($h['nome']) ?></span>
                            </div>

                            <span class="fd-list-meta"><?= $textoDias ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</div>
