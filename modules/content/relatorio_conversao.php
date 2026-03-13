<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/OportunidadeModel.php';

$model = new OportunidadeModel($pdo);



// filtros de período
$hoje        = date('Y-m-d');
$primeiroDia = date('Y-m-01');

$data_inicio = $_GET['data_inicio'] ?? $primeiroDia;
$data_fim    = $_GET['data_fim']    ?? $hoje;

$totalCriadas  = 0;
$totalGanhas   = 0;
$somaGanhas    = 0.0;
$totalPerdidas = 0;
$totalFechadas = 0;
$winRate       = 0.0;
$ticketMedio   = 0.0;

// métricas
$totalCriadas  = $model->contarCriadasPeriodo($data_inicio, $data_fim);
$totalGanhas   = $model->contarGanhasPeriodo($data_inicio, $data_fim);
$somaGanhas    = $model->somarGanhasPeriodo($data_inicio, $data_fim);
$totalPerdidas = $model->contarPerdidasPeriodo($data_inicio, $data_fim);
$totalFechadas = $totalGanhas + $totalPerdidas;

$winRate       = $totalFechadas > 0 ? ($totalGanhas / $totalFechadas) * 100 : 0;
$ticketMedio   = $totalGanhas > 0 ? ($somaGanhas / $totalGanhas) : 0;

$resumoMensal = $model->resumoConversaoMensal(6);

?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4 gap-3">
    <div>
        <h5 class="mb-1">Relatório de conversão</h5>
        <div class="small text-muted">
            Desempenho do funil no período selecionado.
        </div>
    </div>

    <form method="get" class="card mb-4">
  <input type="hidden" name="mod" value="relatorio_conversao">

  <div class="card-body d-flex flex-wrap align-items-end gap-3">
    <div>
      <span class="text-muted text-uppercase small d-block mb-1">
        Período do relatório
      </span>
      <div class="d-flex gap-2">
        <div>
          <label class="form-label form-label-sm text-muted mb-1">De</label>
          <input type="date" name="data_inicio"
                 class="form-control form-control-sm  text-light"
                 value="<?= htmlspecialchars($data_inicio) ?>">
        </div>

        <div>
          <label class="form-label form-label-sm text-muted mb-1">Até</label>
          <input type="date" name="data_fim"
                 class="form-control form-control-sm text-light"
                 value="<?= htmlspecialchars($data_fim) ?>">
        </div>
      </div>
    </div>

    <div class="ms-auto d-flex flex-wrap gap-2">
      <button type="submit" class="btn btn-sm btn-primary px-3">
        Aplicar
      </button>

      <!-- atalhos de período -->
      <button type="button" class="btn btn-sm btn-outline-secondary"
              onclick="setPeriodo('hoje')">
        Hoje
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary"
              onclick="setPeriodo('7d')">
        Últimos 7 dias
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary"
              onclick="setPeriodo('mes')">
        Este mês
      </button>
    </div>
  </div>
</form>

</div>

<div class="row g-3">
    <!-- Criadas -->
    <div class="col-md-3">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="card-kpi-label">
                        <span class="card-kpi-icon">
                            <i class="ri-add-circle-fill"></i>
                        </span>
                        Criadas no período
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value">
                        <?= $totalCriadas ?>
                    </h3>
                    <span class="card-kpi-trend text-muted small">
                        Leads abertas
                    </span>
                </div>
                <div class="card-kpi-footer">
                    <span class="card-kpi-sub text-muted">
                        Todas as oportunidades registradas entre as datas.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ganhas -->
    <div class="col-md-3">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="card-kpi-label">
                        <span class="card-kpi-icon">
                            <i class="ri-checkbox-circle-fill"></i>
                        </span>
                        Oportunidades ganhas
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value">
                        <?= $totalGanhas ?>
                    </h3>
                    <span class="card-kpi-trend text-muted small">
                        R$<?= number_format($somaGanhas, 2, ',', '.') ?>
                    </span>
                </div>
                <div class="card-kpi-footer">
                    <span class="card-kpi-sub text-muted">
                        Somente negócios fechados como ganho.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Perdidas -->
    <div class="col-md-3">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="card-kpi-label">
                        <span class="card-kpi-icon">
                            <i class="ri-close-circle-fill"></i>
                        </span>
                        Oportunidades perdidas
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value">
                        <?= $totalPerdidas ?>
                    </h3>
                    <span class="card-kpi-trend text-muted small">
                        Fechadas sem ganho
                    </span>
                </div>
                <div class="card-kpi-footer">
                    <span class="card-kpi-sub text-muted">
                        Negócios marcados como perdidos.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Taxa de conversão + ticket médio -->
    <div class="col-md-3">
        <div class="card card-kpi h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="card-kpi-label">
                        <span class="card-kpi-icon">
                            <i class="ri-bar-chart-2-fill"></i>
                        </span>
                        Taxa de conversão
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="card-kpi-value">
                        <?= number_format($winRate, 2, ',', '.') ?>%
                    </h3>
                    <span class="card-kpi-trend text-muted small">
                        Win rate
                    </span>
                </div>
                <div class="card-kpi-footer">
                    <span class="card-kpi-sub text-muted">
                        Ticket médio: R$<?= number_format($ticketMedio, 2, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($resumoMensal)): ?>
    <div class="card mt-4">
        <div class="card-body">
            <h6 class="mb-3">Histórico de conversão (últimos 6 meses)</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
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
                            $mesLabel   = date('m/Y', strtotime($linha['mes']));
                            $ganhas     = $linha['ganhas'];
                            $perdidas   = $linha['perdidas'];
                            $fechadas   = $ganhas + $perdidas;
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
        </div>
    </div>
<?php endif; ?>

<div class=" d-flex card col-md-3 mt-4">
  <div class="card-body d-flex align-items-center">
    <div style="width: 220px; height: 220px;">
      <canvas id="graficoConversaoDonut"></canvas>
    </div>
    <div class="ms-4">
      <h6 class="text-muted mb-3">Distribuição das oportunidades</h6>
      <ul class="list-unstyled mb-0 small">
        <li class="mb-1">
          <span class="badge me-2" style="background-color:#22c55e;">&nbsp;</span>
          Ganhas: <strong><?php echo $totalGanhas; ?></strong>
        </li>
        <li class="mb-1">
          <span class="badge me-2" style="background-color:#ef4444;">&nbsp;</span>
          Perdidas: <strong><?php echo $totalPerdidas; ?></strong>
        </li>
        <li>
          <span class="badge me-2" style="background-color:#06b6d4;">&nbsp;</span>
          Em aberto: <strong><?php echo max(0, $totalCriadas - $totalFechadas); ?></strong>
        </li>
      </ul>
    </div>
  </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const totalCriadas  = <?php echo (int)$totalCriadas; ?>;
const totalGanhas   = <?php echo (int)$totalGanhas; ?>;
const totalPerdidas = <?php echo (int)$totalPerdidas; ?>;
const totalFechadas = totalGanhas + totalPerdidas;
const emAberto      = Math.max(0, totalCriadas - totalFechadas);

const ctxDonut = document.getElementById('graficoConversaoDonut').getContext('2d');

const donutChart = new Chart(ctxDonut, {
  type: 'doughnut',
  data: {
    labels: ['Ganhas', 'Perdidas', 'Em aberto'],
    datasets: [{
      data: [totalGanhas, totalPerdidas, emAberto],
      backgroundColor: ['#22c55e', '#ef4444', '#06b6d4'],
      borderWidth: 0,
      hoverOffset: 4
    }]
  },
  options: {
    cutout: '70%',        // “furo” grande para parecer o print
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: function(ctx) {
            const total = totalCriadas || 1;
            const valor = ctx.parsed;
            const pct   = (valor / total * 100).toFixed(1);
            return `${ctx.label}: ${valor} (${pct}%)`;
          }
        }
      }
    }
  },
  plugins: [{
    // texto no centro
    id: 'centerText',
    afterDraw(chart) {
      const {ctx, chartArea: {left, right, top, bottom}} = chart;
      const total = totalCriadas;
      ctx.save();
      ctx.fillStyle = '#e5e7eb';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      const x = (left + right) / 2;
      const y = (top + bottom) / 2;

      ctx.font = 'bold 26px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
      ctx.fillText(total, x, y - 6);

      ctx.font = '12px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
      ctx.fillStyle = '#9ca3af';
      ctx.fillText('Total', x, y + 14);

      ctx.restore();
    }
  }]
});

function formatISO(d) {
  return d.toISOString().slice(0, 10);
}

function setPeriodo(tipo) {
  const hoje = new Date();
  let ini, fim = hoje;

  if (tipo === 'hoje') {
    ini = hoje;
  } else if (tipo === '7d') {
    ini = new Date();
    ini.setDate(hoje.getDate() - 6);
  } else if (tipo === 'mes') {
    ini = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
  }

  document.querySelector('input[name="data_inicio"]').value = formatISO(ini);
  document.querySelector('input[name="data_fim"]').value    = formatISO(fim);
}
</script>
