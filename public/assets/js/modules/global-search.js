function initGlobalSearch() {
  const inputBusca = document.getElementById('global-search');
  const boxResultados = document.getElementById('search-results');
  const buildUrl = window.fdUrl;

  if (!inputBusca || !boxResultados || !buildUrl) return;

  let timer = null;

  inputBusca.addEventListener('input', () => {
    const q = inputBusca.value.trim();
    clearTimeout(timer);

    if (q.length < 2) {
      boxResultados.style.display = 'none';
      boxResultados.innerHTML = '';
      return;
    }

    timer = setTimeout(() => {
      fetch(buildUrl('/busca?q=' + encodeURIComponent(q)))
        .then((r) => (r.ok ? r.json() : []))
        .then((data) => {
          if (!data.length) {
            boxResultados.innerHTML =
              '<div class="list-group-item small text-muted">Nada encontrado.</div>';
            boxResultados.style.display = 'block';
            return;
          }

          boxResultados.innerHTML = data
            .map((item) => {
              let url = '#';
              let icon = '';

              if (item.tipo === 'cliente') {
                url = buildUrl('/cliente?id=' + item.id);
                icon = 'ri-user-fill';
              } else if (item.tipo === 'projeto') {
                url = buildUrl('/projeto?id=' + item.id);
                icon = 'bi-kanban';
              } else if (item.tipo === 'tarefa') {
                url = buildUrl('/projeto?id=' + item.projeto_id);
                icon = 'bi-check2-square';
              }

              return `
                <a href="${url}"
                   class="list-group-item list-group-item-action d-flex align-items-start gap-2">
                  <div class="mt-2"><i class="${icon}"></i></div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">${item.titulo || ''}</div>
                    <div class="small text-muted">${item.subtitulo || ''}</div>
                  </div>
                </a>`;
            })
            .join('');

          boxResultados.style.display = 'block';
        })
        .catch(() => {
          boxResultados.style.display = 'none';
        });
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!boxResultados.contains(e.target) && e.target !== inputBusca) {
      boxResultados.style.display = 'none';
    }
  });
}

window.initGlobalSearch = initGlobalSearch;
