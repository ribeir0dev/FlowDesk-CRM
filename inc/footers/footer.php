<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modContent = document.getElementById('mod-content');
    if (!modContent) return;

    function reinitModals() {
      if (!window.bootstrap) return;

      const modalEls = document.querySelectorAll('#mod-content .modal');
      modalEls.forEach(el => {
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.dispose();
        bootstrap.Modal.getOrCreateInstance(el);
      });
    }

    function reinitConteudoDinamico() {
      if (typeof initSensitiveToggle === 'function') initSensitiveToggle();
      if (typeof initThemeCssPicker === 'function') initThemeCssPicker();
      if (typeof initModalOrcamento === 'function' && document.getElementById('formOrcamento')) {
        initModalOrcamento();
      }
      if (typeof initKanbanDragDrop === 'function') {
        initKanbanDragDrop();
      }
      if (typeof initClientNameFit === 'function') initClientNameFit();
      if (typeof initFinanceiroCharts === 'function') initFinanceiroCharts();
    }

    function initClientNameFit() {
      const clientNames = document.querySelectorAll('.card-cliente-nome');
      clientNames.forEach(el => {
        const maxWidth = el.offsetWidth || el.parentElement.offsetWidth;
        let size = parseFloat(getComputedStyle(el).fontSize);
        while (el.scrollWidth > maxWidth && size > 10) {
          size -= 1;
          el.style.fontSize = size + 'px';
        }
      });
    }

    async function trocarMod(href) {
      modContent.classList.add('mod-fade-out');
      await new Promise(r => setTimeout(r, 200));

      let htmlNovo = '';
      try {
        const resp = await fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const texto = await resp.text();

        const tmp = document.createElement('div');
        tmp.innerHTML = texto;
        const inner = tmp.querySelector('#mod-content');
        htmlNovo = inner ? inner.innerHTML : texto;
      } catch (e) {
        console.error(e);
        htmlNovo = '<p class="text-danger">Erro ao carregar módulo.</p>';
      }

      modContent.innerHTML = htmlNovo;

      reinitModals();
      reinitConteudoDinamico();

      requestAnimationFrame(() => {
        modContent.classList.remove('mod-fade-out');
      });
    }

    document.querySelectorAll('.sv-menu-item[href*="painel.php?mod="]').forEach(link => {
      link.addEventListener('click', async (e) => {
        e.preventDefault();

        const href = link.getAttribute('href');
        const url = new URL(href, window.location.origin);
        const novoMod = url.searchParams.get('mod') || 'dashboard';

        const atualMod = new URL(window.location.href).searchParams.get('mod') || 'dashboard';
        if (novoMod === atualMod) return;

        await trocarMod(href);

        window.history.pushState({ mod: novoMod }, '', href);

        document.querySelectorAll('.sv-menu-item').forEach(a => {
          a.classList.toggle('sv-menu-item--active', a === link);
        });
      });
    });

    window.addEventListener('popstate', async () => {
      const url = new URL(window.location.href);
      const mod = url.searchParams.get('mod') || 'dashboard';
      const href = `painel.php?mod=${encodeURIComponent(mod)}`;

      await trocarMod(href);

      document.querySelectorAll('.sv-menu-item').forEach(a => {
        const aMod = new URL(a.href, window.location.origin).searchParams.get('mod');
        a.classList.toggle('sv-menu-item--active', aMod === mod);
      });
    });

    // primeiro load
    reinitModals();
    reinitConteudoDinamico();
  });
</script>


<script>
  // locale global pt (opcional)
  if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.pt) {
    flatpickr.localize(flatpickr.l10ns.pt);
  }

  if (window.flatpickr) {
    flatpickr("#filtroMes", {
      plugins: [
        new monthSelectPlugin({
          shorthand: true,
          dateFormat: "Y-m",
          altFormat: "M Y",
          theme: "light"
        })
      ],
      allowInput: false
    });
  }
</script>