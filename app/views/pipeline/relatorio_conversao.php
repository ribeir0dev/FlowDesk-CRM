<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/OportunidadeModel.php';
require_once __DIR__ . '/../../../app/views/layouts/partials/header-painel.php';

$model = new OportunidadeModel($pdo);

$hoje = date('Y-m-d');
$primeiroDia = date('Y-m-01');

$data_inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['data_inicio'] ?? '')) ? $_GET['data_inicio'] : $primeiroDia;
$data_fim = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['data_fim'] ?? '')) ? $_GET['data_fim'] : $hoje;

$totalCriadas = $model->contarCriadasPeriodo($data_inicio, $data_fim);
$totalGanhas = $model->contarGanhasPeriodo($data_inicio, $data_fim);
$somaGanhas = $model->somarGanhasPeriodo($data_inicio, $data_fim);
$totalPerdidas = $model->contarPerdidasPeriodo($data_inicio, $data_fim);
$totalFechadas = $totalGanhas + $totalPerdidas;
$winRate = $totalFechadas > 0 ? ($totalGanhas / $totalFechadas) * 100 : 0;
$ticketMedio = $totalGanhas > 0 ? ($somaGanhas / $totalGanhas) : 0;
$resumoMensal = $model->resumoConversaoMensal(6);
$emAberto = max(0, $totalCriadas - $totalFechadas);
?>

<div class="fd-conversion-report">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Performance comercial</p>
            <h2 class="fd-page-title">Relatório de conversão</h2>
            <p class="fd-page-subtitle">Acompanhe ganhos, perdas e taxa de conversão do pipeline no período selecionado.</p>
        </div>

        <a href="/pipeline" class="fd-btn-secondary">
            <i class="ri-arrow-left-line"></i>
            <span>Voltar ao pipeline</span>
        </a>
    </section>

    <section class="fd-card">
        <form method="get" class="fd-conversion-filter">
            <div class="fd-conversion-filter-group">
                <div class="fd-settings-field">
                    <label class="form-label small">De</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                </div>

                <div class="fd-settings-field">
                    <label class="form-label small">Até</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                </div>
            </div>

            <div class="fd-action-group">
                <button type="submit" class="fd-btn-primary">
                    <i class="ri-filter-2-line"></i>
                    <span>Aplicar</span>
                </button>

                <button type="button" class="fd-btn-secondary" onclick="setPeriodo('hoje')">Hoje</button>
                <button type="button" class="fd-btn-secondary" onclick="setPeriodo('7d')">Últimos 7 dias</button>
                <button type="button" class="fd-btn-secondary" onclick="setPeriodo('mes')">Este mês</button>
            </div>
        </form>
    </section>

    <section class="fd-kpi-grid fd-kpi-grid-4">
        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-violet"><i class="ri-add-circle-fill"></i></span>
                    Criadas no período
                </span>
            </div>
            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalCriadas ?></h3>
            </div>
            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Todas as oportunidades registradas entre as datas.</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-green"><i class="ri-checkbox-circle-fill"></i></span>
                    Oportunidades ganhas
                </span>
            </div>
            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalGanhas ?></h3>
            </div>
            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral sensitive-value">R$<?= number_format($somaGanhas, 2, ',', '.') ?></span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red"><i class="ri-close-circle-fill"></i></span>
                    Oportunidades perdidas
                </span>
            </div>
            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= $totalPerdidas ?></h3>
            </div>
            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral">Negócios marcados como perdidos.</span>
            </div>
        </article>

        <article class="fd-card fd-kpi-card">
            <div class="fd-kpi-top">
                <span class="fd-kpi-label">
                    <span class="fd-kpi-icon fd-kpi-icon-red"><i class="ri-bar-chart-2-fill"></i></span>
                    Taxa de conversão
                </span>
            </div>
            <div class="fd-kpi-main">
                <h3 class="fd-kpi-value"><?= number_format($winRate, 2, ',', '.') ?>%</h3>
            </div>
            <div class="fd-kpi-footer">
                <span class="fd-kpi-note fd-trend-neutral sensitive-value">Ticket médio: R$<?= number_format($ticketMedio, 2, ',', '.') ?></span>
            </div>
        </article>
    </section>

    <section class="fd-card fd-chart-card">
        <div id="chartConversaoDonut" class="fd-chart-box fd-chart-box-sm"></div>

        <div class="fd-chart-legend">
            <p class="fd-card-eyebrow">Distribuição do período</p>
            <h3 class="fd-settings-section-title">Panorama das oportunidades</h3>
            <div class="fd-conversion-legend">
                <div class="fd-conversion-legend-item">
                    <span class="fd-badge fd-badge-success">Ganhos</span>
                    <strong><?= $totalGanhas ?></strong>
                </div>
                <div class="fd-conversion-legend-item">
                    <span class="fd-badge fd-badge-danger">Perdidas</span>
                    <strong><?= $totalPerdidas ?></strong>
                </div>
                <div class="fd-conversion-legend-item">
                    <span class="fd-badge fd-badge-info">Em aberto</span>
                    <strong><?= $emAberto ?></strong>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($resumoMensal)): ?>
        <section class="fd-card">
            <div class="fd-card-header-stack-mobile">
                <div>
                    <p class="fd-card-eyebrow">Histórico</p>
                    <h3 class="fd-settings-section-title">Últimos 6 meses</h3>
                </div>
            </div>

            <div class="fd-table-wrap">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th class="text-end">Ganhas</th>
                            <th class="text-end">Perdidas</th>
                            <th class="text-end">Fechadas</th>
                            <th class="text-end">Win rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumoMensal as $linha): ?>
                            <?php
                            $mesLabel = date('m/Y', strtotime($linha['mes']));
                            $ganhas = $linha['ganhas'];
                            $perdidas = $linha['perdidas'];
                            $fechadas = $ganhas + $perdidas;
                            $winRateMes = $linha['win_rate'];
                            ?>
                            <tr>
                                <td><?= $mesLabel ?></td>
                                <td class="text-end"><?= $ganhas ?></td>
                                <td class="text-end"><?= $perdidas ?></td>
                                <td class="text-end"><?= $fechadas ?></td>
                                <td class="text-end"><?= number_format($winRateMes, 2, ',', '.') ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    const ganhos = <?= (int) $totalGanhas ?>;
    const perdidos = <?= (int) $totalPerdidas ?>;
    const emAndamento = <?= (int) $emAberto ?>;

    new ApexCharts(document.querySelector('#chartConversaoDonut'), {
      chart: {
        type: 'donut',
        height: 320
      },
      series: [ganhos, perdidos, emAndamento],
      labels: ['Ganhos', 'Perdidos', 'Em andamento'],
      colors: ['#22c55e', '#ef4444', '#06b6d4'],
      dataLabels: { enabled: false },
      legend: { position: 'bottom' },
      theme: {
        mode: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'
      }
    }).render();

    function setPeriodo(tipo) {
      const hoje = new Date();
      const inicio = document.querySelector('input[name="data_inicio"]');
      const fim = document.querySelector('input[name="data_fim"]');
      const format = (date) => date.toISOString().slice(0, 10);

      if (!inicio || !fim) return;

      if (tipo === 'hoje') {
        inicio.value = format(hoje);
        fim.value = format(hoje);
      } else if (tipo === '7d') {
        const start = new Date(hoje);
        start.setDate(start.getDate() - 6);
        inicio.value = format(start);
        fim.value = format(hoje);
      } else if (tipo === 'mes') {
        const start = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        inicio.value = format(start);
        fim.value = format(hoje);
      }
    }
</script>
