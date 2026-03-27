export function initThemeToggle() {
  const buttons = document.querySelectorAll('[data-theme-toggle]');

  const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('flowdesk-theme', theme);
  };

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
    });
  });
}