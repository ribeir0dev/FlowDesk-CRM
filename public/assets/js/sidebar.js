export function initSidebar() {
  const btn = document.getElementById('menuToggle');
  const sidebar = document.querySelector('.fd-sidebar');

  if (!btn || !sidebar) return;

  btn.addEventListener('click', () => {
    sidebar.classList.toggle('is-open');
  });
}
