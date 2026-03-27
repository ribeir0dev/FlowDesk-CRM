export function initFinanceiroCharts() {
  const pieEl = document.querySelector('#chartSaidasTipo');
  const barEl = document.querySelector('#chartAno');

  if (pieEl && window.dadosSaidasTipo?.labels?.length) {
    pieEl.innerHTML = '';

    new ApexCharts(pieEl, {
      chart: {
        type: 'donut',
        height: 320,
        toolbar: { show: false },
        background: 'transparent'
      },
      labels: window.dadosSaidasTipo.labels,
      series: window.dadosSaidasTipo.valores,
      legend: {
        position: 'bottom'
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        width: 0
      },
      theme: {
        mode: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'
      }
    }).render();
  }

  if (barEl && window.anoLabels && window.anoEntradas && window.anoSaidas) {
    barEl.innerHTML = '';

    new ApexCharts(barEl, {
      chart: {
        type: 'bar',
        height: 340,
        stacked: false,
        toolbar: { show: false },
        background: 'transparent'
      },
      series: [
        { name: 'Entradas', data: window.anoEntradas },
        { name: 'Saídas', data: window.anoSaidas }
      ],
      xaxis: {
        categories: window.anoLabels
      },
      dataLabels: {
        enabled: false
      },
      plotOptions: {
        bar: {
          borderRadius: 8,
          columnWidth: '46%'
        }
      },
      theme: {
        mode: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'
      },
      yaxis: {
        labels: {
          formatter: (value) => `R$ ${Number(value).toLocaleString('pt-BR')}`
        }
      }
    }).render();
  }
}