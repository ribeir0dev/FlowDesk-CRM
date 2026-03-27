function initFinanceiroCharts() {
  const elPie = document.getElementById('chartSaidasTipo');
  if (elPie && window.dadosSaidasTipo && window.dadosSaidasTipo.labels.length && window.Chart) {
    if (elPie._chartInstance) {
      elPie._chartInstance.destroy();
    }

    const chart = new Chart(elPie, {
      type: 'pie',
      data: {
        labels: window.dadosSaidasTipo.labels,
        datasets: [{
          data: window.dadosSaidasTipo.valores,
          backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label(ctx) {
                const v = ctx.parsed || 0;
                const total = ctx.chart._metasets[0].total || 1;
                const pct = (v * 100 / total).toFixed(1);
                return `${ctx.label}: R$ ${v.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} (${pct}%)`;
              }
            }
          }
        }
      }
    });

    elPie._chartInstance = chart;

    const legendEl = document.getElementById('legendSaidasTipo');
    if (legendEl) {
      const items = chart.data.labels.map((label, i) => {
        const value = chart.data.datasets[0].data[i] || 0;
        const color = chart.data.datasets[0].backgroundColor[i];
        return `
          <div class="d-flex align-items-center mb-1 small">
            <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:${color};margin-right:6px;"></span>
            <span class="me-1">${label}:</span>
            <strong>R$ ${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>
          </div>`;
      }).join('');
      legendEl.innerHTML = items;
    }
  }

  const elBar = document.getElementById('chartAno');
  if (elBar && window.anoLabels && window.anoEntradas && window.anoSaidas && window.Chart) {
    if (elBar._chartInstance) {
      elBar._chartInstance.destroy();
    }

    const barChart = new Chart(elBar, {
      type: 'bar',
      data: {
        labels: window.anoLabels,
        datasets: [
          { label: 'Entradas', data: window.anoEntradas, backgroundColor: '#4CAF50' },
          { label: 'Saidas', data: window.anoSaidas, backgroundColor: '#FF5252' }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
          y: {
            ticks: {
              callback(value) {
                return 'R$ ' + Number(value).toLocaleString('pt-BR');
              }
            }
          }
        }
      }
    });

    elBar._chartInstance = barChart;
  }
}

window.initFinanceiroCharts = initFinanceiroCharts;
