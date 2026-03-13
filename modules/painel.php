<?php
$pageTitle = "Dashboard | FlowDesk";
session_start();
include __DIR__ . '/../inc/headers/painel.php';
require_once __DIR__ . '/../inc/functions/view.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
// usa e() para deixar pronto para HTML
$user_name_raw = $_SESSION['user_nome'] ?? 'Usuário';
$user_avatar_raw = $_SESSION['user_avatar'] ?? '/assets/img/avatar.png';
$first_name_raw = trim(explode(' ', $user_name_raw)[0] ?? 'Usuário');

$first_name = e($first_name_raw);
$user_name = e($user_name_raw);
$user_avatar = e($user_avatar_raw);

$mod = $_GET['mod'] ?? 'dashboard';
?>


<div class="container-fluid painel-pai">
  <div class="row flex-nowrap">
    <div class="sv-sidebar-wrap">
      <aside class="sv-sidebar sidebar">
        <div class="sv-sidebar-top">
          <div class="sv-brand">
            <div class="sv-brand-logo"><img src="/assets/img/icon.png" alt="Logo" width="40" /></div>
            <div class="sv-brand-text">
              <span class="sv-brand-name">FlowDesk</span>
              <span class="sv-brand-sub">Sistema de Gerenciamento CRM</span>
            </div>
          </div>
        </div>

        <hr class="sv-divider">

        <nav class="sv-menu">
          <div class="sv-menu-group">
            <p class="sv-menu-title">Menu</p>
            <a href="painel.php?mod=dashboard"
              class="sv-menu-item <?= ($mod === 'dashboard' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-layout-grid-fill"></i></i></span>
              <span>Dashboard</span>
            </a>
          </div>

          <div class="sv-menu-group">
            <p class="sv-menu-title">Gerenciamento</p>
            <a href="painel.php?mod=clientes"
              class="sv-menu-item <?= ($mod === 'clientes' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-group-3-line"></i></span>
              <span>Clientes</span>
            </a>
            <a href="painel.php?mod=projetos"
              class="sv-menu-item <?= ($mod === 'projetos' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-layout-5-fill"></i></span>
              <span>Projetos</span>
            </a>
            <a href="painel.php?mod=pipeline"
              class="sv-menu-item <?= ($mod === 'pipeline' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-filter-2-fill"></i></span>
              <span>Funil</span>
            </a>
            <a href="painel.php?mod=financeiro"
              class="sv-menu-item <?= ($mod === 'financeiro' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-wallet-3-fill"></i></span>
              <span>Financeiro</span>
            </a>
            <a href="painel.php?mod=orcamentos"
              class="sv-menu-item <?= ($mod === 'orcamentos' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-currency-fill"></i></span>
              <span>Orçamento</span>
            </a>
          </div>

          <div class="sv-menu-group">
            <p class="sv-menu-title">Outros
            </p>
            <a href="painel.php?mod=hospedagens"
              class="sv-menu-item <?= ($mod === 'hospedagens' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-server-fill"></i></span>
              <span>Hospedagens</span>
            </a>
            <a href="painel.php?mod=configuracoes"
              class="sv-menu-item <?= ($mod === 'configuracoes' ? 'sv-menu-item--active' : '') ?>">
              <span class="sv-icon"><i class="ri-settings-5-fill"></i></span>
              <span>Configuração</span>
            </a>
          </div>
        </nav>

        <div class="sv-sidebar-footer">
          <div class="sv-cta">
            <p class="sv-cta-title">&copy; 2025 FlowDesk</p>
            <p class="sv-cta-text">Termos de uso • Politica de Privacidade</p>
          </div>
        </div>
      </aside>

      <?php
      // Definições de módulo e avatar
      $mod = $_GET['mod'] ?? 'dashboard';
      $module_titles = [
        'dashboard' => 'Dashboard',
        'clientes' => 'Clientes',
        'projetos' => 'Projetos',
        'pipeline' => 'Funil',
        'orcamentos' => 'Orçamento',
        'financeiro' => 'Financeiro',
        'hospedagens' => 'Hospedagens',
        'configuracoes' => 'Configurações',
      ];
      $mod_name = $module_titles[$mod] ?? ucfirst($mod);
      ?>
    </div>
    <!-- Conteúdo principal -->
    <main class="col py-3 px-4 bg-light" id="painel_content">
      <!-- Menu Mobile Toggle -->
      <div class="d-lg-none" id="mobileTopbar">
        <div class="d-flex align-items-center justify-content-between px-3 py-2">
          <div class="d-flex align-items-center" id="mobileBrand">
            <img src="/assets/img/icon.png" alt="Logo" width="32" class="me-2" />
            <span class="fw-bold text-light">Flow Desk</span>
          </div>
          <button class="btn btn-light" id="menuToggle" aria-label="Abrir menu" style="box-shadow: none;">
            <i class="ri-menu-line" style="font-size: 1rem; color: #fff;"></i>
          </button>
        </div>
      </div>
      <!-- Topbar do conteúdo -->
      <div class="d-flex align-items-center justify-content-between gap-3 pb-3 topbar-conteudo">

        <!-- ESQUERDA: busca ocupa o espaço flexível -->
        <div class="flex-grow-1 me-3">
          <form class="position-relative w-100" onsubmit="return false;">
            <i class="ri-search-line position-absolute"
              style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem;"></i>

            <input type="search" class="form-control" id="global-search" placeholder="Pesquisar no painel"
              style="padding-left: 2.2rem;">

            <div id="search-results" class="list-group position-absolute  search-results-dropdown"
              style="display:none;"></div>
          </form>
        </div>

        <!-- DIREITA: olho + separador + perfil -->
        <div class="d-flex align-items-center gap-3">

          <!-- ícone olho -->
          <button id="toggleSensitive" class="btn btn-outline-primary btn-sm">
            <i class="ri-eye-off-line"></i>
          </button>

          <!-- barrinha vertical de separação -->
          <div class="topbar-divider"></div>

          <!-- perfil / dropdown -->
          <div class="dropdown d-flex align-items-center">
            <div class="d-flex align-items-center me-1 user-pill">
              <span class="usuario me-2">Olá, <?= $first_name ?></span>

              <img src="<?= $user_avatar ?>" alt="Avatar" class="rounded-circle me-1" width="32" height="32"
                style="object-fit:cover;">
            </div>

            <a class="btn btn-sm p-0 chevron-btn" type="button" id="userDropdown" data-bs-toggle="dropdown"
              aria-expanded="false">
              <span class="chevron-circle rounded-circle">
                <i class="ri-arrow-down-s-line"></i>
              </span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li>
                <a class="dropdown-item" href="/modules/painel.php?mod=configuracoes">
                  <i class="ri-settings-3-line me-2"></i>Configurações
                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li>
                <a href="/app/Controllers/AuthController.php?acao=logout" class="dropdown-item">
                  <i class="ri-logout-box-r-line me-2"></i>Sair
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>



      <!-- Área de conteúdo dinâmico -->
      <!-- Área de conteúdo dinâmico com transição -->
      <div id="mods-container" class="p-3">
        <div id="mod-content" class="mod-view mod-ativo">
          <?php
          $mod_file = __DIR__ . '/content/' . $mod . '.php';
          if (file_exists($mod_file)) {
            include $mod_file;
          } else {
            echo '<p class="text-muted">Selecione um módulo para iniciar.</p>';
          }
          ?>
        </div>
      </div>
    </main>
  </div> <!-- row -->
</div> <!-- container -->



<div class="modal fade" id="modalConfirmarAcao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0">
        <h5 class="modal-title">Confirmar ação</h5>
        <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
          <i class="ri-close-line"></i>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-0" id="modalConfirmarMensagem">Tem certeza que deseja continuar?</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="modalConfirmarBtnOk">Sim, excluir</button>
      </div>
    </div>
  </div>
</div>


<?php
include __DIR__ . '/../inc/footers/footer.php';
?>