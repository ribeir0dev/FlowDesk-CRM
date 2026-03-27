function initSensitiveToggle() {
  const toggleBtn = document.getElementById('toggleSensitive');
  const sensitiveEls = document.querySelectorAll('.sensitive-value');

  if (!toggleBtn || !sensitiveEls.length) return;

  const icon = toggleBtn.querySelector('i');
  let hidden = false;

  sensitiveEls.forEach((el) => {
    if (!el.dataset.real) {
      el.dataset.real = el.textContent.trim();
    }
  });

  toggleBtn.addEventListener('click', () => {
    hidden = !hidden;

    sensitiveEls.forEach((el) => {
      if (hidden) {
        if (!el.dataset.real) {
          el.dataset.real = el.textContent.trim();
        }
        el.textContent = '***';
        el.classList.add('sensitive-hidden');
      } else {
        const real = el.dataset.real || '';
        el.textContent = real;
        el.classList.remove('sensitive-hidden');
      }
    });

    if (icon) {
      icon.className = hidden ? 'ri-eye-line' : 'ri-eye-off-line';
    }
  });
}

function applyThemeFromStorage() {
  const linkDark = document.getElementById('theme-dark');
  const linkLight = document.getElementById('theme-claro');
  const linkModern = document.getElementById('theme-modern');
  if (!linkDark || !linkLight || !linkModern) return;

  const theme = localStorage.getItem('sv-theme-css') || 'dark';

  linkDark.disabled = true;
  linkLight.disabled = true;
  linkModern.disabled = true;

  if (theme === 'light') {
    linkLight.disabled = false;
  } else if (theme === 'modern') {
    linkModern.disabled = false;
  } else {
    linkDark.disabled = false;
  }
}

function initThemeCssPicker() {
  const options = document.querySelectorAll('.theme-option');
  if (!options.length) return;

  const linkDark = document.getElementById('theme-dark');
  const linkLight = document.getElementById('theme-claro');
  const linkModern = document.getElementById('theme-modern');
  if (!linkDark || !linkLight || !linkModern) return;

  let currentTheme = localStorage.getItem('sv-theme-css') || 'dark';

  options.forEach((btn) => {
    const t = btn.getAttribute('data-theme');
    btn.classList.toggle('is-active', t === currentTheme);
  });

  options.forEach((btn) => {
    btn.addEventListener('click', () => {
      const selected = btn.getAttribute('data-theme');
      if (!selected || selected === currentTheme) return;

      currentTheme = selected;
      localStorage.setItem('sv-theme-css', selected);

      linkDark.disabled = true;
      linkLight.disabled = true;
      linkModern.disabled = true;

      if (selected === 'light') {
        linkLight.disabled = false;
      } else if (selected === 'modern') {
        linkModern.disabled = false;
      } else {
        linkDark.disabled = false;
      }

      options.forEach((b) => {
        const t = b.getAttribute('data-theme');
        b.classList.toggle('is-active', t === selected);
      });
    });
  });
}

function initClientNameAutosize() {
  const clientNames = document.querySelectorAll('.card-cliente-nome');
  clientNames.forEach((el) => {
    const maxWidth = el.offsetWidth || el.parentElement.offsetWidth;
    let size = parseFloat(getComputedStyle(el).fontSize);

    while (el.scrollWidth > maxWidth && size > 10) {
      size -= 1;
      el.style.fontSize = size + 'px';
    }
  });
}

window.initSensitiveToggle = initSensitiveToggle;
window.applyThemeFromStorage = applyThemeFromStorage;
window.initThemeCssPicker = initThemeCssPicker;
window.initClientNameAutosize = initClientNameAutosize;
