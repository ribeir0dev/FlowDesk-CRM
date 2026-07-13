<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/AuthModel.php';
require_once __DIR__ . '/../../../app/Models/BillingModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceMemberModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceInviteModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceModel.php';

if (empty($_SESSION['user_id'])) {
  header('Location: ' . ($base ?? '') . '/');
  exit;
}

$user_id = (int) $_SESSION['user_id'];

$profileSelect = "
  SELECT id, nome, email, foto_perfil, instagram_url, behance_url, website_url,
         preferred_theme, preferred_locale, preferred_timezone, sidebar_modules_json
  FROM usuarios
  WHERE id = ?
  LIMIT 1
";

try {
  $stmt = $pdo->prepare($profileSelect);
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
  if ($exception->getCode() !== '42S22') {
    throw $exception;
  }

  $stmt = $pdo->prepare("
    SELECT id, nome, email, foto_perfil,
           preferred_theme, preferred_locale, preferred_timezone, sidebar_modules_json
    FROM usuarios
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (is_array($user)) {
    $user['instagram_url'] = null;
    $user['behance_url'] = null;
    $user['website_url'] = null;
  }
}

if (!$user) {
  header('Location: ' . ($base ?? '') . '/login');
  exit;
}

$workspaceModel = new WorkspaceModel($pdo);
$workspace = $workspaceModel->buscarAtual();
if (!$workspace) {
  header('Location: ' . ($base ?? '') . '/dashboard');
  exit;
}
$pixManualConfig = $workspaceModel->buscarPixManual();

$authModel = new AuthModel($pdo);
$workspaceMemberships = $authModel->listarWorkspacesDoUsuario($user_id);
$workspaceIds = array_values(array_unique(array_map(static fn($item) => (int) ($item['workspace_id'] ?? 0), $workspaceMemberships)));
$workspaceMemberCounts = [];

if (!empty($workspaceIds)) {
  $placeholders = implode(',', array_fill(0, count($workspaceIds), '?'));
  $memberCountStmt = $pdo->prepare("SELECT workspace_id, COUNT(*) AS total FROM workspace_members WHERE workspace_id IN ($placeholders) GROUP BY workspace_id");
  $memberCountStmt->execute($workspaceIds);
  foreach ($memberCountStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $workspaceMemberCounts[(int) $row['workspace_id']] = (int) $row['total'];
  }
}

$billingModel = new BillingModel($pdo);
$billingSnapshot = $billingModel->getWorkspaceSnapshot((int) ($workspace['id'] ?? 0));
$currentSubscription = $billingSnapshot['subscription'] ?? null;
$billingPlans = $billingSnapshot['plans'] ?? [];
$billingResources = $billingSnapshot['resources'] ?? [];

$avatar = $user['foto_perfil'] ?: '/assets/img/avatar.png';
if (!filter_var($avatar, FILTER_VALIDATE_URL)) {
  if (str_starts_with($avatar, '/')) {
    $avatar = ($base ?? '') . $avatar;
  } else {
    $avatar = ($base ?? '') . '/' . ltrim($avatar, '/');
  }
}

$iniciais = strtoupper(substr(trim((string) $user['nome']), 0, 2));
$workspaceRole = fd_current_workspace_role() ?? 'owner';
$workspaceNome = $_SESSION['current_workspace_nome'] ?? ($workspace['nome'] ?? 'Workspace atual');
$memberModel = new WorkspaceMemberModel($pdo);
$workspaceMembers = $memberModel->listarMembros();
$workspaceMembersCount = $memberModel->contarMembros();
$inviteModel = new WorkspaceInviteModel($pdo);
$pendingInvites = [];
$mensagens = [];

$roleLabels = [
  'owner' => 'Owner',
  'admin' => 'Admin',
  'financeiro' => 'Financeiro',
  'operacional' => 'Operacional',
  'viewer' => 'Viewer',
];

$roleBadgeClasses = [
  'owner' => 'fd-badge-success',
  'admin' => 'fd-badge-info',
  'financeiro' => 'fd-badge-warning',
  'operacional' => 'fd-badge-neutral',
  'viewer' => 'fd-badge-neutral',
];

$segmentos = [
  'freelancer' => 'Freelancer',
  'studio' => 'Studio criativo',
  'agencia' => 'Agencia',
  'consultoria' => 'Consultoria',
];
$objetivos = [
  'vender_mais' => 'Organizar vendas e pipeline',
  'entregar_melhor' => 'Organizar projetos e entregas',
  'controlar_financas' => 'Controlar financeiro e cobrancas',
];
$tamanhosEquipe = [
  'solo' => 'So eu por enquanto',
  '2_5' => 'De 2 a 5 pessoas',
  '6_10' => 'De 6 a 10 pessoas',
  '11_plus' => 'Mais de 10 pessoas',
];
$volumesClientes = [
  'ate_10' => 'Ate 10 clientes ativos',
  '11_25' => 'Entre 11 e 25 clientes',
  '26_50' => 'Entre 26 e 50 clientes',
  '50_plus' => 'Mais de 50 clientes',
];
$modulosIniciais = [
  'crm' => 'Clientes e CRM',
  'pipeline' => 'Pipeline comercial',
  'projetos' => 'Projetos e entregas',
  'financeiro' => 'Financeiro',
];
$themeOptions = [
  'dark' => 'Escuro',
  'light' => 'Claro',
];
$localeOptions = [
  'pt-BR' => 'Portugues (Brasil)',
  'en-US' => 'English (US)',
  'es-ES' => 'Espanol',
];
$timezoneOptions = [
  'America/Sao_Paulo' => 'America/Sao_Paulo',
  'America/New_York' => 'America/New_York',
  'Europe/Lisbon' => 'Europe/Lisbon',
];
$integrationStatusLabels = [
  'active' => 'Ativo',
  'pending' => 'Pendente',
  'disconnected' => 'Desconectado',
  'soon' => 'Em breve',
];
$integrationProviders = [
  [
    'key' => 'pix_manual',
    'label' => 'Pix Manual',
    'badge' => 'Fallback',
    'description' => 'Gere Pix Copia e Cola e QR Code a partir de uma chave configurada no workspace.',
    'accent' => 'pix',
    'status' => trim((string) ($pixManualConfig['pix_chave'] ?? '')) !== '' ? 'active' : 'pending',
    'health' => trim((string) ($pixManualConfig['pix_chave'] ?? '')) !== '' ? 'Configurado' : 'Configuracao pendente',
    'icon' => 'ri-qr-code-line',
  ],
  [
    'key' => 'stripe',
    'label' => 'Stripe',
    'badge' => 'Em breve',
    'description' => 'Checkout recorrente, cartoes internacionais e cobranca por assinatura.',
    'accent' => 'stripe',
    'status' => 'soon',
    'health' => 'Em breve',
    'image' => 'https://www.vectorlogo.zone/logos/stripe/stripe-icon.svg',
  ],
  [
    'key' => 'mercadopago',
    'label' => 'Mercado Pago',
    'badge' => 'Em breve',
    'description' => 'Pix, boleto e cartao para operacao local com conciliacao simplificada.',
    'accent' => 'mercadopago',
    'status' => 'soon',
    'health' => 'Em breve',
    'image' => 'https://flowdesk.site/wp-content/uploads/2026/05/MP_RGB_HANDSHAKE_pluma_vertical.svg',
  ],
  [
    'key' => 'asaas',
    'label' => 'Asaas',
    'badge' => 'Em breve',
    'description' => 'Boletos, Pix e automacoes de cobranca para operacao no Brasil.',
    'accent' => 'asaas',
    'status' => 'soon',
    'health' => 'Em breve',
    'image' => 'https://flowdesk.site/wp-content/uploads/2026/05/asaas-pagamentos-logo.svg',
  ],
  [
    'key' => 'webhooks',
    'label' => 'Webhooks',
    'badge' => 'Automacao',
    'description' => 'Envie eventos do FlowDesk para outras ferramentas.',
    'accent' => 'webhooks',
    'status' => 'disconnected',
    'health' => 'Nao conectado',
    'icon' => 'ri-webhook-line',
  ],
  [
    'key' => 'whatsapp',
    'label' => 'WhatsApp',
    'badge' => 'Em breve',
    'description' => 'Organize contatos e conversas de atendimento.',
    'accent' => 'whatsapp',
    'status' => 'soon',
    'health' => 'Em breve',
    'icon' => 'ri-whatsapp-line',
  ],
  [
    'key' => 'google_drive',
    'label' => 'Google Drive',
    'badge' => 'Arquivos',
    'description' => 'Centralize links de documentos por cliente e projeto.',
    'accent' => 'drive',
    'status' => 'disconnected',
    'health' => 'Nao conectado',
    'icon' => 'ri-google-fill',
  ],
  [
    'key' => 'figma',
    'label' => 'Figma',
    'badge' => 'Em breve',
    'description' => 'Referencie arquivos de design dentro do workspace.',
    'accent' => 'figma',
    'status' => 'soon',
    'health' => 'Em breve',
    'icon' => 'ri-palette-line',
  ],
  [
    'key' => 'behance',
    'label' => 'Behance',
    'badge' => 'Em breve',
    'description' => 'Conecte portfolio e referencias ao perfil da conta.',
    'accent' => 'behance',
    'status' => 'soon',
    'health' => 'Em breve',
    'icon' => 'ri-behance-line',
  ],
];
$selectedIntegrationKey = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['integration'] ?? ''));
$selectedIntegration = null;
foreach ($integrationProviders as $provider) {
  if (($provider['key'] ?? '') === $selectedIntegrationKey) {
    $selectedIntegration = $provider;
    break;
  }
}

$planOrder = ['free' => 1, 'starter' => 2, 'pro' => 3, 'enterprise' => 4];
$currentPlanCode = (string) ($currentSubscription['plan_code'] ?? 'free');
$currentPlanRank = $planOrder[$currentPlanCode] ?? 0;
$upgradePlans = array_values(array_filter($billingPlans, static function (array $plan) use ($planOrder, $currentPlanRank): bool {
  $code = (string) ($plan['plan_code'] ?? '');
  return ($planOrder[$code] ?? 0) > $currentPlanRank;
}));
$nextUpgradePlan = $upgradePlans[0] ?? null;
$planCardsToShow = [];
if ($currentSubscription) {
  $planCardsToShow[] = array_merge($currentSubscription, ['is_current_plan' => true]);
}
if ($nextUpgradePlan) {
  $planCardsToShow[] = array_merge($nextUpgradePlan, ['is_current_plan' => false]);
}
$allModuleOptions = [
  'dashboard' => ['label' => 'Dashboard', 'description' => 'Resumo da operacao e atalhos iniciais.', 'icon' => 'ri-dashboard-line', 'group' => 'Visao geral'],
  'clientes' => ['label' => 'Clientes', 'description' => 'Cadastro, contexto e historico comercial.', 'icon' => 'ri-group-line', 'group' => 'Relacionamento'],
  'pipeline' => ['label' => 'Pipeline', 'description' => 'Etapas comerciais e acompanhamento de leads.', 'icon' => 'ri-git-branch-line', 'group' => 'Comercial'],
  'projetos' => ['label' => 'Projetos', 'description' => 'Execucao, tarefas e entregas.', 'icon' => 'ri-folder-line', 'group' => 'Operacao'],
  'orcamentos' => ['label' => 'Orcamentos', 'description' => 'Propostas, itens e PDFs comerciais.', 'icon' => 'ri-file-list-3-line', 'group' => 'Comercial'],
  'financeiro' => ['label' => 'Financeiro', 'description' => 'Entradas, saidas e consolidado operacional.', 'icon' => 'ri-money-dollar-circle-line', 'group' => 'Financeiro'],
  'hospedagens' => ['label' => 'Hospedagens', 'description' => 'Dominios, renovacoes e vencimentos.', 'icon' => 'ri-server-line', 'group' => 'Infra'],
  'codigos' => ['label' => 'Codigos', 'description' => 'Biblioteca tecnica com snippets, efeitos e trechos reutilizaveis.', 'icon' => 'ri-code-box-line', 'group' => 'Tecnico'],
];
$allowedModulesByRole = match ($workspaceRole) {
  'operacional' => ['clientes', 'pipeline', 'projetos', 'codigos'],
  'financeiro' => ['clientes', 'orcamentos', 'financeiro', 'hospedagens', 'codigos'],
  'viewer' => ['clientes', 'projetos', 'orcamentos', 'codigos'],
  default => ['dashboard', 'clientes', 'pipeline', 'projetos', 'orcamentos', 'financeiro', 'hospedagens', 'codigos'],
};
$rawModulePreferences = json_decode((string) ($user['sidebar_modules_json'] ?? ''), true);
if (!is_array($rawModulePreferences)) {
  $rawModulePreferences = null;
}
$selectedModuleKeys = $rawModulePreferences === null ? $allowedModulesByRole : array_values(array_intersect(array_keys($allModuleOptions), $rawModulePreferences));

$canManageWorkspace = fd_has_any_role(['owner', 'admin']);
if ($canManageWorkspace) {
  $pendingInvites = $inviteModel->listarPendentes();
}

$statusLabels = [
  'trial' => 'Trial ativo',
  'active' => 'Assinatura ativa',
  'past_due' => 'Pagamento pendente',
  'cancelled' => 'Assinatura cancelada',
  'expired' => 'Assinatura expirada',
];

if (isset($_GET['ok']))
  $mensagens[] = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso.'];
if (isset($_GET['erro']))
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar o perfil.'];
if (($_GET['senha'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Senha atualizada com sucesso.'];
if (($_GET['senha'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Confira a senha atual e a confirmação da nova senha.'];
if (($_GET['pref'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Preferencias da conta atualizadas com sucesso.'];
if (($_GET['pref'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar as preferencias da conta.'];
if (($_GET['modules'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Modulos visiveis do menu atualizados com sucesso.'];
if (($_GET['modules'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar a organizacao de modulos.'];
if (($_GET['workspace'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Configuracoes do workspace atualizadas com sucesso.'];
if (($_GET['workspace'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar os dados do workspace.'];
if (($_GET['pix'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Pix manual atualizado com sucesso.'];
if (($_GET['pix'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar a configuracao do Pix manual. Rode a migration se os campos ainda nao existem.'];
if (($_GET['invite'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Convite criado com sucesso. Copie o link e envie para o membro da equipe.'];
if (($_GET['invite'] ?? '') === 'limit')
  $mensagens[] = ['type' => 'warning', 'text' => 'Seu plano atual atingiu o limite de usuarios. Ajuste o plano antes de convidar novos membros para este workspace.'];
if (($_GET['invite'] ?? '') === 'duplicado')
  $mensagens[] = ['type' => 'danger', 'text' => 'Ja existe um membro ou convite pendente para este e-mail neste workspace.'];
if (($_GET['invite'] ?? '') === 'revogado')
  $mensagens[] = ['type' => 'success', 'text' => 'Convite revogado com sucesso.'];
if (isset($_GET['invite_accepted']))
  $mensagens[] = ['type' => 'success', 'text' => 'Convite aceito com sucesso. O workspace adicional ja esta vinculado a sua conta.'];
if (($_GET['member_role'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Papel do membro atualizado com sucesso.'];
if (($_GET['member_role'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar o papel deste membro.'];
if (($_GET['member_remove'] ?? '') === 'ok')
  $mensagens[] = ['type' => 'success', 'text' => 'Membro removido da conta com sucesso.'];
if (($_GET['member_remove'] ?? '') === 'erro')
  $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel remover este membro da conta.'];
?>

<div class="fd-settings">
  <section class="fd-page-header">
    <div>
      <p class="fd-page-eyebrow">Conta e organizacao do workspace</p>
    </div>
  </section>

  <?php foreach ($mensagens as $mensagem): ?>
    <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert"><?= e($mensagem['text']) ?></div>
  <?php endforeach; ?>

  <div class="fd-settings-shell">
    <aside class="fd-card fd-settings-sidebar-card">
      <div class="fd-settings-sidebar-copy">
        <p class="fd-card-eyebrow">Central da conta</p>
      </div>
      <nav class="fd-settings-nav">
        <a href="#minha-conta" class="fd-settings-nav-link is-active" data-settings-target="minha-conta">Minha Conta</a>
        <a href="#meu-workspace" class="fd-settings-nav-link" data-settings-target="meu-workspace">Meu Workspace</a>
        <a href="#integracoes" class="fd-settings-nav-link" data-settings-target="integracoes">Integrações</a>
        <a href="#pagamentos" class="fd-settings-nav-link" data-settings-target="pagamentos">Assinaturas</a>
        <a href="#modulos" class="fd-settings-nav-link" data-settings-target="modulos">Modulos</a>
      </nav>
      <div class="fd-settings-sidebar-profile">
        <div class="fd-settings-avatar-wrap">
          <?php if (!empty($user['foto_perfil'])): ?>
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="fd-settings-avatar">
          <?php else: ?>
            <div class="fd-settings-avatar-fallback"><?= htmlspecialchars($iniciais ?: 'FD') ?></div>
          <?php endif; ?>
        </div>
        <div class="fd-settings-profile-copy">
          <strong class="fd-settings-name"><?= htmlspecialchars($user['nome']) ?></strong>
          <span class="fd-text-muted"><?= htmlspecialchars($user['email']) ?></span>
          <span
            class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$workspaceRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$workspaceRole] ?? ucfirst($workspaceRole)) ?>
            no workspace atual</span>
        </div>
      </div>
    </aside>

    <div class="fd-settings-main">
      <section class="fd-settings-section is-active" id="minha-conta" data-settings-panel="minha-conta">
        <div class="fd-settings-section-stack fd-account-blocks">
          <article class="fd-card fd-settings-form-card fd-account-block fd-profile-card" data-profile-interface>
            <div class="fd-account-block-head">
              <span class="fd-account-block-icon"><i class="ri-user-3-line"></i></span>
              <div>
                <h3 class="fd-settings-section-title">Informacoes do perfil</h3>
              </div>
            </div>
            <form action="<?= ($base ?? '') ?>/perfil/atualizar" method="post" class="fd-settings-form">
              <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
              <div class="fd-settings-fields">
                <div class="fd-settings-field"><label class="form-label small">Nome</label><input type="text"
                    name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required></div>
                <div class="fd-settings-field"><label class="form-label small">E-mail</label><input type="email"
                    name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
              </div>
              <div class="fd-settings-actions">
                <button type="submit" class="fd-btn-primary"><i class="ri-save-line"></i><span>Salvar perfil</span></button>
              </div>
            </form>

            <section class="fd-profile-photo-section" data-avatar-uploader>
              <label class="form-label small">Foto de Perfil</label>
              <input type="file" accept="image/jpeg,image/png,image/webp" data-avatar-input hidden>
              <div class="fd-avatar-upload-box" data-avatar-dropzone tabindex="0" role="button"
                aria-label="Arraste uma imagem ou carregue pelo computador">
                <div class="fd-avatar-upload-idle" data-avatar-idle>
                  <span class="fd-avatar-upload-icon"><i class="ri-image-add-line"></i></span>
                  <p>Arraste e solte sua imagem aqui ou
                    <button type="button" class="fd-avatar-upload-link" data-avatar-select>Carregar imagem</button>
                  </p>
                  <small>JPEG, PNG ou WebP, com no maximo 8 MB.</small>
                </div>

                <div class="fd-avatar-upload-progress" data-avatar-progress hidden>
                  <strong data-avatar-progress-value>0%</strong>
                  <div class="fd-avatar-progress-track"><span data-avatar-progress-bar></span></div>
                  <small>Enviando imagem com seguranca...</small>
                </div>

                <div class="fd-avatar-editor" data-avatar-editor hidden>
                  <div class="fd-avatar-editor-left">
                    <div class="fd-avatar-cropper-stage" data-avatar-stage>
                      <img src="" alt="Previa do recorte" data-avatar-image>
                      <span class="fd-avatar-cropper-mask" aria-hidden="true"></span>
                    </div>
                    <div class="fd-avatar-zoom-control">
                      <i class="ri-subtract-line"></i>
                      <input type="range" min="1" max="3" step="0.01" value="1" aria-label="Zoom da foto" data-avatar-zoom>
                      <i class="ri-add-line"></i>
                    </div>
                  </div>
                  <div class="fd-avatar-file-details">
                    <div class="fd-avatar-file-copy">
                      <span>Nome do arquivo: <strong data-avatar-file-name>-</strong></span>
                      <span>Tipo do arquivo: <strong data-avatar-file-type>-</strong></span>
                      <span>Tamanho do arquivo: <strong data-avatar-file-size>-</strong></span>
                    </div>
                    <div class="fd-avatar-editor-actions">
                      <button type="button" class="fd-btn-primary" data-avatar-save>
                        <i class="ri-save-line"></i><span>Salvar imagem</span>
                      </button>
                      <button type="button" class="fd-btn-secondary" data-avatar-discard>
                        <i class="ri-delete-bin-line"></i><span>Descartar imagem</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <p class="fd-profile-inline-feedback" data-avatar-feedback aria-live="polite"></p>
            </section>

            <section class="fd-profile-social-section">
              <label class="form-label small">Links do Perfil</label>
              <div class="fd-social-links" data-social-links>
                <?php
                $socialNetworks = [
                  'instagram' => ['label' => 'Instagram', 'icon' => 'ri-instagram-line', 'url' => (string) ($user['instagram_url'] ?? '')],
                  'behance' => ['label' => 'Behance', 'icon' => 'ri-behance-line', 'url' => (string) ($user['behance_url'] ?? '')],
                  'website' => ['label' => 'Website', 'icon' => 'ri-global-line', 'url' => (string) ($user['website_url'] ?? '')],
                ];
                foreach ($socialNetworks as $network => $social):
                  $hasLink = trim($social['url']) !== '';
                ?>
                  <button type="button" class="fd-social-link-button<?= $hasLink ? ' is-linked' : '' ?>"
                    data-social-trigger="<?= htmlspecialchars($network) ?>"
                    data-social-label="<?= htmlspecialchars($social['label']) ?>"
                    data-social-url="<?= htmlspecialchars($social['url']) ?>"
                    aria-label="Editar link do <?= htmlspecialchars($social['label']) ?>">
                    <i class="<?= htmlspecialchars($social['icon']) ?>"></i>
                    <span class="fd-social-linked-indicator" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none">
                        <path d="M10.6 13.4a4 4 0 0 0 5.66.06l2.2-2.2a4 4 0 1 0-5.66-5.66l-1.26 1.26M13.4 10.6a4 4 0 0 0-5.66-.06l-2.2 2.2a4 4 0 1 0 5.66 5.66l1.26-1.26" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </span>
                  </button>
                <?php endforeach; ?>
                <div class="fd-social-popover" data-social-popover hidden>
                  <div class="fd-social-popover-head">
                    <strong data-social-title>Link do perfil</strong>
                    <button type="button" data-social-close aria-label="Fechar"><i class="ri-close-line"></i></button>
                  </div>
                  <input type="url" class="form-control" placeholder="https://..." data-social-input>
                  <p class="fd-profile-inline-feedback" data-social-feedback aria-live="polite"></p>
                  <div class="fd-social-popover-actions">
                    <button type="button" class="fd-btn-danger-soft fd-btn-sm" data-social-remove>Remover</button>
                    <button type="button" class="fd-btn-primary fd-btn-sm" data-social-save>Salvar link</button>
                  </div>
                </div>
              </div>
            </section>
          </article>

          <article class="fd-card fd-settings-form-card fd-account-block fd-security-card">
            <div class="fd-account-block-head">
              <span class="fd-account-block-icon"><i class="ri-shield-check-line"></i></span>
              <div>
                <h3 class="fd-settings-section-title">Seguranca</h3>
              </div>
            </div>
            <form action="<?= ($base ?? '') ?>/perfil/senha" method="post" class="fd-settings-form">
              <div class="fd-settings-fields fd-security-fields">
                <div class="fd-settings-field">
                  <label class="form-label small">Senha Atual</label>
                  <input type="password" name="senha_atual" class="form-control" placeholder="Senha atual" required>
                </div>
                <div class="fd-settings-field">
                  <label class="form-label small">Nova Senha</label>
                  <input type="password" name="senha" class="form-control" placeholder="Minimo de 8 caracteres"
                    minlength="8" required data-profile-password>
                  <div class="fd-password-strength" data-profile-password-strength>
                    <span class="fd-password-strength-bar"><i></i></span>
                    <small data-profile-password-label>Digite uma nova senha</small>
                    <ul>
                      <li data-password-rule="length"><i class="ri-checkbox-circle-line"></i> Pelo menos 8 caracteres</li>
                      <li data-password-rule="number"><i class="ri-checkbox-circle-line"></i> Pelo menos 1 numero</li>
                      <li data-password-rule="special"><i class="ri-checkbox-circle-line"></i> Pelo menos 1 caractere especial</li>
                    </ul>
                  </div>
                </div>
                <div class="fd-settings-field">
                  <label class="form-label small">Repetir nova senha</label>
                  <input type="password" name="conf_senha" class="form-control" placeholder="Repita a nova senha"
                    minlength="8" required>
                </div>
              </div>
              <div class="fd-settings-actions">
                <button type="submit" class="fd-btn-primary"><i class="ri-lock-password-line"></i><span>Alterar senha</span></button>
              </div>
            </form>
          </article>

          <article class="fd-card fd-settings-form-card fd-account-block">
            <div class="fd-account-block-head">
              <span class="fd-account-block-icon"><i class="ri-equalizer-line"></i></span>
              <div>
                <p class="fd-card-eyebrow">Preferencias pessoais</p>
                <h3 class="fd-settings-section-title">Tema, idioma e fuso</h3>
                <p class="fd-text-muted">Essas opcoes afetam como voce enxerga o produto.</p>
              </div>
            </div>
            <form action="<?= ($base ?? '') ?>/perfil/preferencias" method="post" class="fd-settings-form" data-preferences-form>
              <div class="fd-settings-fields">
                <div class="fd-settings-field"><label class="form-label small">Tema preferido</label><select
                    name="preferred_theme" class="form-select"
                    data-theme-preference><?php foreach ($themeOptions as $value => $label): ?>
                      <option value="<?= $value ?>" <?= (($user['preferred_theme'] ?? 'dark') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                  </select></div>
                <div class="fd-settings-field"><label class="form-label small">Idioma</label><select
                    name="preferred_locale" class="form-select"><?php foreach ($localeOptions as $value => $label): ?>
                      <option value="<?= $value ?>" <?= (($user['preferred_locale'] ?? 'pt-BR') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                  </select></div>
                <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Fuso
                    horario</label><select name="preferred_timezone"
                    class="form-select"><?php foreach ($timezoneOptions as $value => $label): ?>
                      <option value="<?= $value ?>" <?= (($user['preferred_timezone'] ?? 'America/Sao_Paulo') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                  </select></div>
              </div>
              <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i
                    class="ri-equalizer-line"></i><span>Salvar preferencias</span></button></div>
            </form>
          </article>
        </div>
      </section>
      <section class="fd-settings-section" id="meu-workspace" data-settings-panel="meu-workspace">
        <div class="fd-settings-section-stack">
          <article class="fd-card fd-settings-form-card">
            <div>
              <p class="fd-card-eyebrow">Workspaces da conta</p>
              <h3 class="fd-settings-section-title">Onde voce tem acesso</h3>
              <p class="fd-text-muted">Use esta lista para ver em quais workspaces sua conta participa e quantas pessoas
                existem em cada ambiente.</p>
            </div>
            <div class="fd-settings-workspaces-list">
              <?php foreach ($workspaceMemberships as $membership): ?>
                <?php $membershipWorkspaceId = (int) ($membership['workspace_id'] ?? 0);
                $isCurrentWorkspace = $membershipWorkspaceId === (int) ($_SESSION['current_workspace_id'] ?? 0);
                $membershipRole = (string) ($membership['role'] ?? 'viewer');
                $memberTotal = $workspaceMemberCounts[$membershipWorkspaceId] ?? 0; ?>
                <article class="fd-settings-workspace-item<?= $isCurrentWorkspace ? ' is-active' : '' ?>">
                  <div class="fd-settings-workspace-item-copy">
                    <strong><?= htmlspecialchars($membership['workspace_nome'] ?? 'Workspace') ?></strong><span
                      class="fd-text-muted"><?= $memberTotal ?> membro<?= $memberTotal === 1 ? '' : 's' ?> Â·
                      <?= htmlspecialchars($roleLabels[$membershipRole] ?? ucfirst($membershipRole)) ?></span></div>
                  <div class="fd-settings-workspace-item-actions">
                    <?php if ($isCurrentWorkspace): ?>
                      <span class="fd-badge fd-badge-success">Atual</span>
                    <?php else: ?>
                      <form method="post" action="<?= ($base ?? '') ?>/workspace/trocar" class="fd-inline-form"><input
                          type="hidden" name="workspace_id" value="<?= $membershipWorkspaceId ?>"><input type="hidden"
                          name="redirect"
                          value="<?= htmlspecialchars(($base ?? '') . '/configuracoes#meu-workspace') ?>"><button
                          type="submit" class="fd-btn-secondary fd-btn-sm"><i
                            class="ri-repeat-line"></i><span>Acessar</span></button></form>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </article>

          <?php if ($canManageWorkspace): ?>
            <article class="fd-card fd-settings-form-card">
              <div>
                <p class="fd-card-eyebrow">Contexto do workspace</p>
                <h3 class="fd-settings-section-title">Setup operacional</h3>
                <p class="fd-text-muted">Esses dados ajudam o produto a entender melhor o tipo de operacao do workspace.
                </p>
              </div>
              <form action="<?= ($base ?? '') ?>/workspace/atualizar-configuracoes" method="post"
                class="fd-settings-form">
                <div class="fd-settings-fields">
                  <div class="fd-settings-field"><label class="form-label small">Nome do workspace</label><input
                      type="text" name="workspace_nome" class="form-control"
                      value="<?= htmlspecialchars((string) ($workspace['nome'] ?? '')) ?>" required></div>
                  <div class="fd-settings-field"><label class="form-label small">Segmento principal</label><select
                      name="segmento" class="form-select" required>
                      <option value="">Selecione</option><?php foreach ($segmentos as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($workspace['segmento'] ?? '') === $value) ? 'selected' : '' ?>>
                          <?= $label ?></option><?php endforeach; ?>
                    </select></div>
                  <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Objetivo
                      principal</label><select name="objetivo_principal" class="form-select" required>
                      <option value="">Selecione</option><?php foreach ($objetivos as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($workspace['objetivo_principal'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></div>
                  <div class="fd-settings-field"><label class="form-label small">Tamanho da equipe</label><select
                      name="tamanho_equipe" class="form-select">
                      <option value="">Opcional</option><?php foreach ($tamanhosEquipe as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($workspace['onboarding_tamanho_equipe'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></div>
                  <div class="fd-settings-field"><label class="form-label small">Volume de clientes</label><select
                      name="volume_clientes" class="form-select">
                      <option value="">Opcional</option><?php foreach ($volumesClientes as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($workspace['onboarding_volume_clientes'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></div>
                  <div class="fd-settings-field"><label class="form-label small">Modulo inicial mais
                      importante</label><select name="modulo_inicial" class="form-select">
                      <option value="">Opcional</option><?php foreach ($modulosIniciais as $value => $label): ?>
                        <option value="<?= $value ?>" <?= (($workspace['onboarding_modulo_inicial'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></div>
                  <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Migracao de
                      dados</label>
                    <div class="fd-onboarding-choice-grid"><label
                        class="fd-onboarding-choice <?= !empty($workspace['onboarding_migrar_dados']) ? 'is-selected' : '' ?>"><input
                          type="radio" name="migrar_dados" value="1" <?= !empty($workspace['onboarding_migrar_dados']) ? 'checked' : '' ?>><span><strong>Sim, vou migrar dados</strong><small>Quero trazer base existente
                            para o FlowDesk.</small></span></label><label
                        class="fd-onboarding-choice <?= empty($workspace['onboarding_migrar_dados']) ? 'is-selected' : '' ?>"><input
                          type="radio" name="migrar_dados" value="0" <?= empty($workspace['onboarding_migrar_dados']) ? 'checked' : '' ?>><span><strong>Vou comecar do zero</strong><small>Prefiro estruturar tudo do
                            zero dentro do workspace atual.</small></span></label></div>
                  </div>
                </div>
                <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i
                      class="ri-building-line"></i><span>Salvar workspace</span></button></div>
              </form>
            </article>

            <article class="fd-card fd-settings-team-card">
              <div class="fd-settings-team-head">
                <div>
                  <p class="fd-card-eyebrow">Equipe do workspace</p>
                  <h3 class="fd-settings-section-title"><?= htmlspecialchars($workspaceNome) ?></h3>
                  <p class="fd-text-muted"><?= $workspaceMembersCount ?>
                    membro<?= $workspaceMembersCount === 1 ? '' : 's' ?>
                    vinculado<?= $workspaceMembersCount === 1 ? '' : 's' ?> a esta conta.</p>
                </div>
              </div>
              <?php if (empty($workspaceMembers)): ?>
                <p class="fd-empty-copy">Nenhum membro vinculado a este workspace ainda.</p>
              <?php else: ?>
                <div class="fd-settings-team-list">
                  <?php foreach ($workspaceMembers as $member): ?>
                    <?php $memberAvatar = $member['foto_perfil'] ?: null;
                    if ($memberAvatar && !filter_var($memberAvatar, FILTER_VALIDATE_URL)) {
                      $memberAvatar = str_starts_with($memberAvatar, '/') ? ($base ?? '') . $memberAvatar : ($base ?? '') . '/' . ltrim($memberAvatar, '/');
                    }
                    $memberInitials = strtoupper(substr(trim((string) $member['nome']), 0, 2));
                    $memberRole = $member['role'] ?? 'viewer'; ?>
                    <article class="fd-settings-team-member">
                      <div class="fd-settings-team-member-main"><?php if ($memberAvatar): ?><img
                            src="<?= htmlspecialchars($memberAvatar) ?>"
                            alt="Avatar de <?= htmlspecialchars($member['nome']) ?>"
                            class="fd-settings-team-avatar"><?php else: ?>
                          <div class="fd-settings-team-avatar fd-settings-team-avatar-fallback">
                            <?= htmlspecialchars($memberInitials ?: 'FD') ?></div><?php endif; ?>
                        <div class="fd-settings-team-copy"><strong
                            class="fd-settings-team-name"><?= htmlspecialchars($member['nome']) ?></strong><span
                            class="fd-text-muted"><?= htmlspecialchars($member['email']) ?></span></div>
                      </div>
                      <div class="fd-settings-team-meta">
                        <?php if ((int) ($member['is_primary'] ?? 0) === 1): ?><span
                            class="fd-badge fd-badge-info">Principal</span><?php endif; ?>
                        <?php if ($canManageWorkspace && $memberRole !== 'owner' && (int) ($member['user_id'] ?? 0) !== $user_id): ?>
                          <div class="fd-settings-team-meta-stack">
                            <form action="<?= ($base ?? '') ?>/workspace/membros/atualizar-papel" method="post"
                              class="fd-settings-role-form"><input type="hidden" name="member_id"
                                value="<?= (int) $member['id'] ?>"><select name="role"
                                class="form-select form-select-sm fd-settings-role-select" onchange="this.form.submit()">
                                <option value="admin" <?= $memberRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="financeiro" <?= $memberRole === 'financeiro' ? 'selected' : '' ?>>Financeiro
                                </option>
                                <option value="operacional" <?= $memberRole === 'operacional' ? 'selected' : '' ?>>Operacional
                                </option>
                                <option value="viewer" <?= $memberRole === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                              </select></form>
                            <form action="<?= ($base ?? '') ?>/workspace/membros/remover" method="post" class="fd-inline-form"
                              onsubmit="return confirm('Remover este membro do workspace?');"><input type="hidden"
                                name="member_id" value="<?= (int) $member['id'] ?>"><button type="submit"
                                class="fd-btn-secondary fd-btn-sm"><i
                                  class="ri-user-unfollow-line"></i><span>Remover</span></button></form>
                          </div>
                        <?php else: ?>
                          <span
                            class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$memberRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$memberRole] ?? ucfirst($memberRole)) ?></span>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>
            <article class="fd-card fd-settings-team-card">
              <div class="fd-settings-team-head">
                <div>
                  <p class="fd-card-eyebrow">Convites</p>
                  <h3 class="fd-settings-section-title">Equipe e links de convite</h3>
                  <p class="fd-text-muted">Crie convites para este workspace e compartilhe o link com seguranca.</p>
                </div>
              </div>
              <form action="<?= ($base ?? '') ?>/workspace/invites/criar" method="post" class="fd-settings-form">
                <div class="fd-settings-fields">
                  <div class="fd-settings-field"><label class="form-label small">E-mail do convidado</label><input
                      type="email" name="email" class="form-control" placeholder="pessoa@empresa.com" required></div>
                  <div class="fd-settings-field"><label class="form-label small">Papel inicial</label><select name="role"
                      class="form-select" required>
                      <option value="operacional">Operacional</option>
                      <option value="financeiro">Financeiro</option>
                      <option value="admin">Admin</option>
                      <option value="viewer">Viewer</option>
                    </select></div>
                </div>
                <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i
                      class="ri-mail-send-line"></i><span>Gerar convite</span></button></div>
              </form>
              <?php if (!empty($pendingInvites)): ?>
                <div class="fd-settings-team-list">
                  <?php foreach ($pendingInvites as $invite): ?>
                    <?php $inviteRole = $invite['role'] ?? 'viewer';
                    $inviteLink = ($base ?? '') . '/convite?token=' . urlencode((string) $invite['token']); ?>
                    <article class="fd-settings-team-member fd-settings-invite-member">
                      <div class="fd-settings-team-copy"><strong
                          class="fd-settings-team-name"><?= htmlspecialchars($invite['email']) ?></strong><span
                          class="fd-text-muted">Convite criado por
                          <?= htmlspecialchars($invite['invited_by_nome'] ?? 'Equipe') ?></span><code
                          class="fd-settings-invite-link"><?= htmlspecialchars($inviteLink) ?></code></div>
                      <div class="fd-settings-team-meta fd-settings-team-meta-stack"><span
                          class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$inviteRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$inviteRole] ?? ucfirst($inviteRole)) ?></span><span
                          class="fd-text-muted">Expira em
                          <?= htmlspecialchars(fd_format_datetime((string) $invite['expires_at'])) ?></span>
                        <div class="fd-action-group"><button type="button" class="fd-btn-secondary fd-btn-sm"
                            onclick="navigator.clipboard.writeText('<?= htmlspecialchars($inviteLink, ENT_QUOTES) ?>')"><i
                              class="ri-file-copy-line"></i><span>Copiar link</span></button>
                          <form action="<?= ($base ?? '') ?>/workspace/invites/revogar" method="post" class="fd-inline-form">
                            <input type="hidden" name="invite_id" value="<?= (int) $invite['id'] ?>"><button type="submit"
                              class="fd-btn-secondary fd-btn-sm"><i
                                class="ri-close-circle-line"></i><span>Revogar</span></button></form>
                        </div>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>

            <article class="fd-card fd-settings-placeholder-card">
              <div>
                <p class="fd-card-eyebrow">Acessos especificos</p>
                <h3 class="fd-settings-section-title">Excecoes por modulo</h3>
                <p class="fd-text-muted">Aqui vamos concentrar, na proxima etapa, permissoes extras por pessoa para
                  liberar modulos fora da role padrao em casos pontuais.</p>
              </div><span class="fd-badge fd-badge-info">Preparado para a proxima iteracao</span>
            </article>
          <?php else: ?>
            <article class="fd-card fd-settings-placeholder-card">
              <div>
                <p class="fd-card-eyebrow">Workspace</p>
                <h3 class="fd-settings-section-title">Acesso limitado pela role atual</h3>
                <p class="fd-text-muted">Seu perfil neste workspace e
                  <strong><?= htmlspecialchars($roleLabels[$workspaceRole] ?? ucfirst($workspaceRole)) ?></strong>. Gestao
                  completa de equipe, convites e contexto operacional fica disponivel apenas para owner e admin.</p>
              </div>
            </article>
          <?php endif; ?>
        </div>
      </section>

      <section class="fd-settings-section" id="integracoes" data-settings-panel="integracoes">
        <?php if ($selectedIntegration): ?>
          <div class="fd-integration-config">
            <a href="<?= ($base ?? '') ?>/configuracoes#integracoes" class="fd-btn-secondary fd-btn-sm fd-integration-back">
              <i class="ri-arrow-left-line"></i>
              <span>Voltar para integracoes</span>
            </a>
            <div class="fd-integration-config-grid">
              <article class="fd-card fd-integration-config-card">
                <div class="fd-integration-config-head">
                  <span class="fd-settings-integration-logo">
                    <?php if (!empty($selectedIntegration['image'])): ?>
                      <img src="<?= htmlspecialchars((string) $selectedIntegration['image']) ?>" alt="<?= htmlspecialchars($selectedIntegration['label']) ?>">
                    <?php elseif (!empty($selectedIntegration['svg'])): ?>
                      <?= $selectedIntegration['svg'] ?>
                    <?php else: ?>
                      <i class="<?= htmlspecialchars((string) ($selectedIntegration['icon'] ?? 'ri-plug-line')) ?>"></i>
                    <?php endif; ?>
                  </span>
                  <div>
                    <h3 class="fd-settings-section-title"><?= htmlspecialchars($selectedIntegration['label']) ?></h3>
                    <p class="fd-text-muted"><?= htmlspecialchars($selectedIntegration['description']) ?></p>
                  </div>
                </div>

                <?php if (($selectedIntegration['key'] ?? '') === 'pix_manual'): ?>
                  <div class="fd-integration-info-box">
                    <i class="ri-information-line"></i>
                    <p>O Pix manual sera usado automaticamente nos documentos de cobranca e fechamento quando nenhuma integração de pagamento estiver ativa.</p>
                  </div>

                  <form action="<?= ($base ?? '') ?>/workspace/pix-manual" method="post" class="fd-settings-form">
                    <div class="fd-settings-fields">
                      <div class="fd-settings-field fd-settings-field-span-2">
                        <label class="form-label small">Chave Pix</label>
                        <input type="text" name="pix_chave" class="form-control" value="<?= htmlspecialchars((string) ($pixManualConfig['pix_chave'] ?? '')) ?>" placeholder="E-mail, telefone, CPF/CNPJ ou chave aleatoria">
                      </div>
                      <div class="fd-settings-field">
                        <label class="form-label small">Nome do recebedor</label>
                        <input type="text" name="pix_nome" class="form-control" value="<?= htmlspecialchars((string) ($pixManualConfig['pix_nome'] ?? '')) ?>" placeholder="Nome que aparecera no Pix">
                      </div>
                      <div class="fd-settings-field">
                        <label class="form-label small">Cidade</label>
                        <input type="text" name="pix_cidade" class="form-control" value="<?= htmlspecialchars((string) ($pixManualConfig['pix_cidade'] ?? '')) ?>" placeholder="Ex: Sao Paulo">
                      </div>
                    </div>

                    <div class="fd-integration-toggle-row">
                      <div>
                        <strong>Status do fallback</strong>
                        <span><?= trim((string) ($pixManualConfig['pix_chave'] ?? '')) !== '' ? 'Pronto para gerar QR Code manual' : 'Configure uma chave Pix para ativar' ?></span>
                      </div>
                      <span class="fd-badge <?= trim((string) ($pixManualConfig['pix_chave'] ?? '')) !== '' ? 'fd-badge-success' : 'fd-badge-warning' ?>">
                        <?= trim((string) ($pixManualConfig['pix_chave'] ?? '')) !== '' ? 'Ativo' : 'Pendente' ?>
                      </span>
                    </div>

                    <button type="submit" class="fd-btn-primary">Salvar Pix manual</button>
                  </form>

                  <div class="fd-integration-methods">
                    <strong>Metodo suportado</strong>
                    <span>PIX Copia e Cola</span>
                    <span>QR Code</span>
                  </div>
                <?php else: ?>
                  <div class="fd-integration-info-box">
                    <i class="ri-information-line"></i>
                    <p>Use esta tela para concentrar token, ambiente e status da integracao. A conexao real sera ativada quando a API desta integracao for implementada.</p>
                  </div>

                  <div class="fd-settings-fields">
                    <div class="fd-settings-field fd-settings-field-span-2">
                      <label class="form-label small">Access Token</label>
                      <input type="password" class="form-control" placeholder="APP_USR-..." disabled>
                    </div>
                  </div>

                  <div class="fd-integration-toggle-row">
                    <div>
                      <strong>Ambiente</strong>
                      <span>Sandbox ou producao</span>
                    </div>
                    <span class="fd-integration-mode-pill">Producao</span>
                  </div>

                  <div class="fd-integration-toggle-row">
                    <div>
                      <strong>Integracao ativa</strong>
                      <span>Habilita/desabilita a geracao de cobrancas</span>
                    </div>
                    <span class="fd-settings-module-toggle" aria-hidden="true"></span>
                  </div>

                  <button type="button" class="fd-btn-primary" disabled>Salvar configuracao</button>

                  <div class="fd-integration-methods">
                    <strong>Metodos suportados</strong>
                    <span>PIX</span>
                    <span>Cartao de Credito</span>
                    <span>Boleto Bancario</span>
                  </div>
                <?php endif; ?>
              </article>

              <article class="fd-card fd-integration-config-card">
                <div class="fd-integration-config-head">
                  <span class="fd-settings-integration-logo"><i class="ri-book-open-line"></i></span>
                  <div>
                    <h3 class="fd-settings-section-title">Tutorial de configuracao</h3>
                    <p class="fd-text-muted">Passo a passo para integrar.</p>
                  </div>
                </div>
                <div class="fd-integration-steps">
                  <?php foreach (['Criar conta', 'Criar uma aplicacao', 'Obter o Access Token', 'Configurar o Webhook', 'Metodos de pagamento'] as $index => $step): ?>
                    <div class="fd-integration-step">
                      <span><?= $index + 1 ?></span>
                      <strong><?= htmlspecialchars($step) ?></strong>
                      <i class="ri-arrow-down-s-line"></i>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="fd-integration-tip">
                  <strong>Dica</strong>
                  <p>Recomendamos testar primeiro no modo Sandbox com credenciais de teste antes de ativar o modo Producao.</p>
                </div>
              </article>
            </div>
          </div>
        <?php else: ?>
          <div class="fd-settings-integrations-grid fd-settings-integrations-grid-compact">
            <?php foreach ($integrationProviders as $provider): ?>
              <a
                href="<?= ($base ?? '') ?>/configuracoes?integration=<?= urlencode((string) $provider['key']) ?>#integracoes"
                class="fd-settings-integration-tile fd-settings-integration-status-<?= htmlspecialchars($provider['status']) ?>">
                <?php if (in_array($provider['status'], ['active', 'pending'], true)): ?>
                  <span class="fd-integration-check"><i class="ri-check-line"></i></span>
                <?php endif; ?>
                <?php if (($provider['status'] ?? '') === 'soon'): ?>
                  <span class="fd-integration-soon">Em breve</span>
                <?php endif; ?>
                <span class="fd-settings-integration-logo">
                  <?php if (!empty($provider['image'])): ?>
                    <img src="<?= htmlspecialchars((string) $provider['image']) ?>" alt="<?= htmlspecialchars($provider['label']) ?>">
                  <?php elseif (!empty($provider['svg'])): ?>
                    <?= $provider['svg'] ?>
                  <?php else: ?>
                    <i class="<?= htmlspecialchars((string) ($provider['icon'] ?? 'ri-plug-line')) ?>"></i>
                  <?php endif; ?>
                </span>
                <strong><?= htmlspecialchars($provider['label']) ?></strong>
              </a>
            <?php endforeach; ?>
          </div>
          <article class="fd-card fd-integration-suggestion">
            <h3 class="fd-settings-section-title">Nao encontrou a integracao que precisa?</h3>
            <p class="fd-text-muted">Envie sua sugestao e nossa equipe avaliara para futuras versoes.</p>
            <button type="button" class="fd-btn-secondary">Sugerir integracao</button>
          </article>
        <?php endif; ?>
      </section>

      <section class="fd-settings-section" id="pagamentos" data-settings-panel="pagamentos">
        <div class="fd-settings-payments-grid">
          <article class="fd-card fd-settings-payment-card">
            <p class="fd-card-eyebrow">Assinatura atual</p>
            <div class="fd-settings-overview-grid">
              <div class="fd-settings-overview-item">
                <span class="fd-settings-meta-label">Status</span>
                <strong><?= htmlspecialchars($statusLabels[$currentSubscription['status'] ?? 'trial'] ?? 'Sem assinatura') ?></strong>
                <span class="fd-settings-plan-mini"><?= htmlspecialchars($currentSubscription['plan_nome'] ?? 'Plano nao identificado') ?></span>
              </div>
              <div class="fd-settings-overview-item">
                <span class="fd-settings-meta-label">Preco base</span>
                <strong><?= htmlspecialchars($currentSubscription ? money((float) $currentSubscription['plan_preco']) : 'R$ 0,00') ?></strong>
                <small>valor do plano atual</small>
              </div>
              <div class="fd-settings-overview-item">
                <span class="fd-settings-meta-label">Vencimento</span>
                <strong><?= htmlspecialchars(!empty($currentSubscription['expires_at']) ? fd_format_date((string) $currentSubscription['expires_at']) : (!empty($currentSubscription['trial_ends_at']) ? fd_format_date((string) $currentSubscription['trial_ends_at']) : 'Sem data')) ?></strong>
                <small>encerramento da assinatura</small>
              </div>
            </div>
            <div class="fd-settings-billing-usage-grid">
              <?php foreach ($billingResources as $resourceKey => $resource): ?>
                <?php if ($resourceKey === 'storage_mb') continue; ?>
                <?php
                $ratio = $resource['ratio'];
                $meterClass = $resource['is_over_limit'] ? 'is-over' : ($resource['is_near_limit'] ? 'is-warning' : 'is-ok');
                $usedLabel = $resource['used'] === null ? '-' : (string) $resource['used'];
                $limitLabel = $resource['is_unlimited'] ? 'Ilimitado' : (string) ($resource['limit'] ?? '-');
                ?>
                <article class="fd-settings-billing-usage-card">
                  <div class="fd-settings-billing-usage-copy">
                    <span class="fd-settings-meta-label"><?= htmlspecialchars($resource['label']) ?></span>
                    <strong><?= htmlspecialchars($usedLabel) ?> / <?= htmlspecialchars($limitLabel) ?></strong>
                    <small>
                      <?php if ($resource['is_unlimited']): ?>
                        ilimitado
                      <?php elseif ($resource['is_over_limit']): ?>
                        limite atingido no workspace atual
                      <?php elseif ($resource['is_near_limit']): ?>
                        atencao: consumo perto do limite
                      <?php else: ?>
                        consumo atual do workspace
                      <?php endif; ?>
                    </small>
                  </div>
                  <div class="fd-settings-billing-meter <?= $meterClass ?>">
                    <span style="width: <?= (int) ($ratio ?? 0) ?>%"></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
            <div class="fd-settings-subtle-actions">
              <button type="button" class="fd-btn-secondary fd-btn-sm" disabled>Cancelar assinatura</button>
            </div>
          </article>
          <article class="fd-card fd-settings-payment-card">
            <p class="fd-card-eyebrow">Upgrade de plano</p>
            <div class="fd-settings-billing-plan-grid">
              <?php if (empty($planCardsToShow)): ?>
                <article class="fd-settings-billing-plan-card">
                  <div class="fd-settings-billing-plan-head">
                    <div>
                      <strong>Nenhum plano encontrado</strong>
                      <small>Verifique a configuracao de assinaturas.</small>
                    </div>
                  </div>
                </article>
              <?php endif; ?>
              <?php foreach ($planCardsToShow as $plan): ?>
                <article class="fd-settings-billing-plan-card<?= !empty($plan['is_current_plan']) ? ' is-current' : '' ?>">
                  <div class="fd-settings-billing-plan-head">
                    <div>
                      <strong><?= htmlspecialchars($plan['plan_nome'] ?? 'Plano') ?></strong>
                      <small><?= htmlspecialchars($plan['plan_code'] ?? 'sem-code') ?></small>
                    </div>
                    <span class="fd-badge <?= !empty($plan['is_current_plan']) ? 'fd-badge-success' : 'fd-badge-info' ?>">
                      <?= !empty($plan['is_current_plan']) ? 'Atual' : 'Upgrade' ?>
                    </span>
                  </div>
                  <div class="fd-settings-billing-plan-price">
                    <strong><?= htmlspecialchars(money((float) ($plan['plan_preco'] ?? 0))) ?></strong>
                    <span>/mes</span>
                  </div>
                  <?php
                  $planCode = (string) ($plan['plan_code'] ?? '');
                  $usuariosLabel = ($planCode === 'enterprise' && ($plan['users_limit'] ?? null) === null)
                    ? 'Configuravel'
                    : (($plan['users_limit'] ?? null) ? (int) $plan['users_limit'] : 'Ilimitado');
                  $clientesLabel = ($planCode === 'enterprise' && ($plan['clients_limit'] ?? null) === null)
                    ? 'Configuravel'
                    : (($plan['clients_limit'] ?? null) ? (int) $plan['clients_limit'] : 'Ilimitado');
                  $projetosLabel = ($plan['projects_limit'] ?? null) ? (int) $plan['projects_limit'] : 'Ilimitado';
                  $orcamentosLabel = (array_key_exists('orcamentos_limit', $plan) && ($plan['orcamentos_limit'] ?? null))
                    ? (int) $plan['orcamentos_limit']
                    : 'Ilimitado';
                  ?>
                  <ul class="fd-settings-billing-plan-features">
                    <li>Usuarios: <?= htmlspecialchars((string) $usuariosLabel) ?></li>
                    <li>Clientes: <?= htmlspecialchars((string) $clientesLabel) ?></li>
                    <li>Projetos: <?= htmlspecialchars((string) $projetosLabel) ?></li>
                    <li>Orcamentos: <?= htmlspecialchars((string) $orcamentosLabel) ?></li>
                  </ul>
                  <button type="button" class="fd-btn-primary" disabled>Fazer upgrade</button>
                </article>
              <?php endforeach; ?>
            </div>
          </article>
        </div>
      </section>

      <section class="fd-settings-section" id="modulos" data-settings-panel="modulos">
        <article class="fd-card fd-settings-form-card">
          <div class="fd-settings-modules-hero">
            <div>
              <p class="fd-card-eyebrow">Preferencias visuais</p>
              <h3 class="fd-settings-section-title">Escolha o que aparece na navegacao</h3>
              <p class="fd-text-muted">Essas alteracoes organizam seu painel pessoal sem mudar as permissoes reais do
                workspace.</p>
            </div>
            <div class="fd-settings-modules-summary">
              <strong><?= count($selectedModuleKeys) ?></strong>
              <span>modulos ativos</span>
            </div>
          </div>
          <form action="<?= ($base ?? '') ?>/perfil/modulos" method="post" class="fd-settings-form" data-modules-autosave-form>
            <div class="fd-settings-modules-grid">
              <?php foreach ($allModuleOptions as $moduleKey => $moduleConfig): ?>
                <?php $isAllowedModule = in_array($moduleKey, $allowedModulesByRole, true);
                $isCheckedModule = in_array($moduleKey, $selectedModuleKeys, true); ?>
                <label
                  class="fd-settings-module-card<?= !$isAllowedModule ? ' is-disabled' : '' ?><?= $isCheckedModule ? ' is-checked' : '' ?>">
                  <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($moduleKey) ?>" <?= $isCheckedModule ? 'checked' : '' ?>   <?= !$isAllowedModule ? 'disabled' : '' ?>>
                  <span class="fd-settings-module-head">
                    <span class="fd-settings-module-icon"><i
                        class="<?= htmlspecialchars($moduleConfig['icon']) ?>"></i></span>
                    <span class="fd-settings-module-toggle" aria-hidden="true"></span>
                  </span>
                  <span class="fd-settings-module-copy">
                    <small class="fd-settings-module-group"><?= htmlspecialchars($moduleConfig['group']) ?></small>
                    <strong><?= htmlspecialchars($moduleConfig['label']) ?></strong>
                    <small><?= htmlspecialchars($moduleConfig['description']) ?></small>
                  </span>
                  <em><?= $isAllowedModule ? ($isCheckedModule ? 'Visivel no menu' : 'Oculto no menu') : 'Indisponivel pela role atual' ?></em>
                </label>
              <?php endforeach; ?>
            </div>
            <p class="fd-settings-autosave-status" data-modules-autosave-status aria-live="polite">As alteracoes sao salvas automaticamente.</p>
          </form>
        </article>
      </section>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const preferenceForm = document.querySelector('[data-preferences-form]');
    if (preferenceForm) {
      preferenceForm.addEventListener('submit', function () {
        const themeField = preferenceForm.querySelector('[data-theme-preference]');
        if (themeField && themeField.value) {
          localStorage.setItem('flowdesk-theme', themeField.value);
        }
      });
    }

    document.querySelectorAll('.fd-onboarding-choice').forEach(function (card) {
      card.addEventListener('click', function () {
        const input = card.querySelector('input');
        if (!input) return;
        input.checked = true;
        document.querySelectorAll('.fd-onboarding-choice').forEach(function (item) {
          item.classList.toggle('is-selected', !!item.querySelector('input:checked'));
        });
      });
    });

    const modulesForm = document.querySelector('[data-modules-autosave-form]');
    const modulesAutosaveStatus = document.querySelector('[data-modules-autosave-status]');
    let modulesAutosaveTimer = null;
    let modulesAutosaveController = null;

    const getSelectedModules = function () {
      if (!modulesForm) return [];

      return Array.from(modulesForm.querySelectorAll('input[name="modules[]"]:checked'))
        .filter(function (input) {
          return !input.disabled;
        })
        .map(function (input) {
          return input.value;
        });
    };

    const syncSidebarModules = function () {
      const selectedModules = getSelectedModules();

      document.querySelectorAll('[data-sidebar-module]').forEach(function (item) {
        const moduleKey = item.dataset.sidebarModule;

        if (!moduleKey || moduleKey === 'configuracoes') return;

        item.classList.toggle('is-module-hidden', !selectedModules.includes(moduleKey));
      });

      const counter = document.querySelector('.fd-settings-modules-summary strong');
      if (counter) {
        counter.textContent = String(selectedModules.length);
      }
    };

    const setModulesAutosaveStatus = function (message, state) {
      if (!modulesAutosaveStatus) return;

      modulesAutosaveStatus.textContent = message;
      modulesAutosaveStatus.dataset.state = state || 'idle';
    };

    const saveModulesPreferences = function () {
      if (!modulesForm) return;

      if (modulesAutosaveController) {
        modulesAutosaveController.abort();
      }

      modulesAutosaveController = new AbortController();
      const formData = new FormData();

      getSelectedModules().forEach(function (moduleKey) {
        formData.append('modules[]', moduleKey);
      });

      if (window.FLOWDESK_CSRF_TOKEN) {
        formData.append('csrf_token', window.FLOWDESK_CSRF_TOKEN);
      }

      setModulesAutosaveStatus('Salvando alteracoes...', 'saving');

      fetch(modulesForm.action, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(window.FLOWDESK_CSRF_TOKEN ? { 'X-CSRF-TOKEN': window.FLOWDESK_CSRF_TOKEN } : {}),
        },
        signal: modulesAutosaveController.signal,
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return { response, payload };
          });
        })
        .then(function (result) {
          if (!result.response.ok || !result.payload.ok) {
            throw new Error(result.payload.message || 'Nao foi possivel salvar os modulos.');
          }

          setModulesAutosaveStatus('Alteracoes salvas automaticamente.', 'saved');
        })
        .catch(function (error) {
          if (error.name === 'AbortError') return;

          setModulesAutosaveStatus('Falha ao salvar. Tente novamente.', 'error');
          if (typeof window.fdShowFloatingAlert === 'function') {
            window.fdShowFloatingAlert(error.message || 'Nao foi possivel salvar os modulos.', 'danger');
          }
        });
    };

    document.querySelectorAll('.fd-settings-module-card').forEach(function (card) {
      const input = card.querySelector('input[type="checkbox"]');
      if (!input) return;

      const syncModuleCard = function () {
        card.classList.toggle('is-checked', input.checked);
      };

      input.addEventListener('change', function () {
        syncModuleCard();
        syncSidebarModules();

        clearTimeout(modulesAutosaveTimer);
        modulesAutosaveTimer = setTimeout(saveModulesPreferences, 260);
      });
      syncModuleCard();
    });

    if (modulesForm) {
      modulesForm.addEventListener('submit', function (event) {
        event.preventDefault();
        saveModulesPreferences();
      });

      syncSidebarModules();
    }

    const navLinks = Array.from(document.querySelectorAll('[data-settings-target]'));
    const panels = Array.from(document.querySelectorAll('[data-settings-panel]'));
    let activePanelId = null;
    let isSwitchingPanel = false;

    const activatePanel = function (targetId, updateHash = true) {
      const nextPanel = panels.find(function (panel) {
        return panel.id === targetId;
      }) || panels.find(function (panel) {
        return panel.id === 'minha-conta';
      });

      if (!nextPanel || isSwitchingPanel || nextPanel.id === activePanelId) {
        return;
      }

      isSwitchingPanel = true;
      const nextId = nextPanel.id;
      const currentPanel = panels.find(function (panel) {
        return panel.dataset.settingsPanel === activePanelId;
      });

      navLinks.forEach(function (link) {
        link.classList.toggle('is-active', link.dataset.settingsTarget === nextId);
      });

      if (currentPanel) {
        currentPanel.classList.remove('is-active');
        currentPanel.classList.add('is-leaving');
      }

      nextPanel.hidden = false;
      nextPanel.classList.remove('is-leaving');

      window.requestAnimationFrame(function () {
        nextPanel.classList.add('is-entering');
        nextPanel.classList.add('is-active');

        window.setTimeout(function () {
          panels.forEach(function (panel) {
            const isActive = panel.dataset.settingsPanel === nextId;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
            panel.classList.remove('is-entering');
            panel.classList.remove('is-leaving');
          });

          activePanelId = nextId;
          isSwitchingPanel = false;
        }, 280);
      });

      if (updateHash && window.location.hash !== '#' + nextId) {
        history.replaceState(null, '', '#' + nextId);
      }
    };

    navLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        activatePanel(link.dataset.settingsTarget);
      });
    });

    const initialPanel = (window.location.hash || '').replace('#', '') || 'minha-conta';
    const initialExists = panels.some(function (panel) { return panel.id === initialPanel; });
    const startId = initialExists ? initialPanel : 'minha-conta';

    panels.forEach(function (panel) {
      const isActive = panel.dataset.settingsPanel === startId;
      panel.hidden = !isActive;
      panel.classList.toggle('is-active', isActive);
      panel.classList.remove('is-entering');
      panel.classList.remove('is-leaving');
    });

    navLinks.forEach(function (link) {
      link.classList.toggle('is-active', link.dataset.settingsTarget === startId);
    });

    activePanelId = startId;

    const settingsRoot = document.querySelector('.fd-settings');
    const avatarUploader = document.querySelector('[data-avatar-uploader]');
    const avatarInput = document.querySelector('[data-avatar-input]');
    const avatarDropzone = document.querySelector('[data-avatar-dropzone]');
    const avatarSelect = document.querySelector('[data-avatar-select]');
    const avatarIdle = document.querySelector('[data-avatar-idle]');
    const avatarProgress = document.querySelector('[data-avatar-progress]');
    const avatarProgressValue = document.querySelector('[data-avatar-progress-value]');
    const avatarProgressBar = document.querySelector('[data-avatar-progress-bar]');
    const avatarEditor = document.querySelector('[data-avatar-editor]');
    const avatarStage = document.querySelector('[data-avatar-stage]');
    const avatarImage = document.querySelector('[data-avatar-image]');
    const avatarZoom = document.querySelector('[data-avatar-zoom]');
    const avatarSave = document.querySelector('[data-avatar-save]');
    const avatarDiscard = document.querySelector('[data-avatar-discard]');
    const avatarFeedback = document.querySelector('[data-avatar-feedback]');
    const avatarFileName = document.querySelector('[data-avatar-file-name]');
    const avatarFileType = document.querySelector('[data-avatar-file-type]');
    const avatarFileSize = document.querySelector('[data-avatar-file-size]');

    if (avatarUploader && avatarInput && avatarDropzone && avatarImage && avatarStage && avatarZoom) {
      const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
      let uploadToken = '';
      let naturalWidth = 0;
      let naturalHeight = 0;
      let position = { x: 0, y: 0 };
      let dragStart = null;
      let imageUrl = '';
      let activeFile = null;

      const setAvatarFeedback = function (message, state) {
        avatarFeedback.textContent = message || '';
        avatarFeedback.dataset.state = state || '';
      };

      const setAvatarView = function (view) {
        avatarIdle.hidden = view !== 'idle';
        avatarProgress.hidden = view !== 'progress';
        avatarEditor.hidden = view !== 'editor';
        avatarDropzone.classList.toggle('is-editing', view === 'editor');
      };

      const formatBytes = function (bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 KB';
        const units = ['B', 'KB', 'MB'];
        const unit = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${(bytes / Math.pow(1024, unit)).toFixed(unit === 0 ? 0 : 2)} ${units[unit]}`;
      };

      const getBaseScale = function () {
        const rect = avatarStage.getBoundingClientRect();
        if (!naturalWidth || !naturalHeight || !rect.width || !rect.height) return 1;
        return Math.max(rect.width / naturalWidth, rect.height / naturalHeight);
      };

      const renderAvatarCrop = function () {
        const rect = avatarStage.getBoundingClientRect();
        const scale = getBaseScale() * Number(avatarZoom.value || 1);
        const width = naturalWidth * scale;
        const height = naturalHeight * scale;
        const maxX = Math.max(0, (width - rect.width) / 2);
        const maxY = Math.max(0, (height - rect.height) / 2);
        position.x = Math.min(maxX, Math.max(-maxX, position.x));
        position.y = Math.min(maxY, Math.max(-maxY, position.y));
        avatarImage.style.width = `${width}px`;
        avatarImage.style.height = `${height}px`;
        avatarImage.style.transform = `translate(calc(-50% + ${position.x}px), calc(-50% + ${position.y}px))`;
      };

      const resetAvatarState = function () {
        position = { x: 0, y: 0 };
        naturalWidth = 0;
        naturalHeight = 0;
        uploadToken = '';
        activeFile = null;
        avatarZoom.value = '1';
        avatarInput.value = '';
        if (imageUrl) URL.revokeObjectURL(imageUrl);
        imageUrl = '';
        avatarImage.removeAttribute('src');
        setAvatarView('idle');
      };

      const discardAvatarUpload = async function () {
        if (uploadToken) {
          const data = new FormData();
          data.append('upload_token', uploadToken);
          try {
            await fetch(`${window.FLOWDESK_BASE || ''}/perfil/avatar/descartar`, { method: 'POST', body: data });
          } catch (error) {
            // The temporary file is also removed by the server cleanup routine.
          }
        }
        resetAvatarState();
        setAvatarFeedback('', '');
      };

      const openAvatarEditor = function (file, response) {
        uploadToken = response.token;
        activeFile = file;
        avatarFileName.textContent = response.file?.name || file.name;
        avatarFileType.textContent = (response.file?.type || file.type).replace('image/', '.').toUpperCase();
        avatarFileSize.textContent = formatBytes(response.file?.size || file.size);
        if (imageUrl) URL.revokeObjectURL(imageUrl);
        imageUrl = URL.createObjectURL(file);
        avatarImage.onload = function () {
          naturalWidth = avatarImage.naturalWidth;
          naturalHeight = avatarImage.naturalHeight;
          position = { x: 0, y: 0 };
          avatarZoom.value = '1';
          setAvatarView('editor');
          requestAnimationFrame(renderAvatarCrop);
        };
        avatarImage.src = imageUrl;
      };

      const uploadAvatar = function (file) {
        setAvatarFeedback('', '');
        if (!file || !allowedTypes.includes(file.type)) {
          setAvatarFeedback('Use uma imagem JPEG, PNG ou WebP.', 'error');
          return;
        }
        if (file.size > 8 * 1024 * 1024) {
          setAvatarFeedback('A imagem deve ter no máximo 8 MB.', 'error');
          return;
        }

        setAvatarView('progress');
        avatarProgressValue.textContent = '0%';
        avatarProgressBar.style.width = '0%';
        const payload = new FormData();
        payload.append('foto_perfil', file);
        payload.append('csrf_token', window.FLOWDESK_CSRF_TOKEN || '');
        const request = new XMLHttpRequest();
        request.open('POST', `${window.FLOWDESK_BASE || ''}/perfil/avatar/preparar`);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-CSRF-Token', window.FLOWDESK_CSRF_TOKEN || '');
        request.upload.addEventListener('progress', function (event) {
          if (!event.lengthComputable) return;
          const progress = Math.min(100, Math.round((event.loaded / event.total) * 100));
          avatarProgressValue.textContent = `${progress}%`;
          avatarProgressBar.style.width = `${progress}%`;
        });
        request.addEventListener('load', function () {
          let response = {};
          try { response = JSON.parse(request.responseText || '{}'); } catch (error) {}
          if (request.status < 200 || request.status >= 300 || !response.ok) {
            setAvatarView('idle');
            setAvatarFeedback(response.message || 'Não foi possível enviar a imagem.', 'error');
            return;
          }
          avatarProgressValue.textContent = '100%';
          avatarProgressBar.style.width = '100%';
          window.setTimeout(function () { openAvatarEditor(file, response); }, 180);
        });
        request.addEventListener('error', function () {
          setAvatarView('idle');
          setAvatarFeedback('Falha de conexão durante o upload.', 'error');
        });
        request.send(payload);
      };

      const handleSelectedFile = function (file) {
        settingsRoot?.classList.remove('is-avatar-dragging');
        avatarDropzone.classList.remove('is-dragover');
        uploadAvatar(file);
      };

      avatarSelect.addEventListener('click', function () { avatarInput.click(); });
      avatarDropzone.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && avatarEditor.hidden) {
          event.preventDefault();
          avatarInput.click();
        }
      });
      avatarInput.addEventListener('change', function () {
        handleSelectedFile(avatarInput.files?.[0] || null);
      });
      ['dragenter', 'dragover'].forEach(function (eventName) {
        avatarDropzone.addEventListener(eventName, function (event) {
          event.preventDefault();
          if (!avatarEditor.hidden) return;
          avatarDropzone.classList.add('is-dragover');
          settingsRoot?.classList.add('is-avatar-dragging');
        });
      });
      ['dragleave', 'drop'].forEach(function (eventName) {
        avatarDropzone.addEventListener(eventName, function (event) {
          event.preventDefault();
          if (eventName === 'dragleave' && avatarDropzone.contains(event.relatedTarget)) return;
          avatarDropzone.classList.remove('is-dragover');
          settingsRoot?.classList.remove('is-avatar-dragging');
          if (eventName === 'drop') handleSelectedFile(event.dataTransfer?.files?.[0] || null);
        });
      });

      avatarZoom.addEventListener('input', renderAvatarCrop);
      avatarStage.addEventListener('pointerdown', function (event) {
        dragStart = { x: event.clientX, y: event.clientY, positionX: position.x, positionY: position.y };
        avatarStage.setPointerCapture(event.pointerId);
      });
      avatarStage.addEventListener('pointermove', function (event) {
        if (!dragStart) return;
        position.x = dragStart.positionX + event.clientX - dragStart.x;
        position.y = dragStart.positionY + event.clientY - dragStart.y;
        renderAvatarCrop();
      });
      ['pointerup', 'pointercancel'].forEach(function (eventName) {
        avatarStage.addEventListener(eventName, function () { dragStart = null; });
      });

      avatarDiscard.addEventListener('click', discardAvatarUpload);
      avatarSave.addEventListener('click', async function () {
        if (!uploadToken || !activeFile || !naturalWidth || !naturalHeight) return;
        avatarSave.disabled = true;
        setAvatarFeedback('Salvando a nova foto...', 'saving');
        const rect = avatarStage.getBoundingClientRect();
        const scale = getBaseScale() * Number(avatarZoom.value || 1);
        const displayedWidth = naturalWidth * scale;
        const displayedHeight = naturalHeight * scale;
        const left = (rect.width - displayedWidth) / 2 + position.x;
        const top = (rect.height - displayedHeight) / 2 + position.y;
        const sourceX = Math.max(0, -left / scale);
        const sourceY = Math.max(0, -top / scale);
        const sourceSize = Math.min(rect.width / scale, rect.height / scale, naturalWidth - sourceX, naturalHeight - sourceY);
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        if (!context || sourceSize <= 0) {
          avatarSave.disabled = false;
          setAvatarFeedback('Não foi possível gerar o recorte.', 'error');
          return;
        }

        canvas.width = 512;
        canvas.height = 512;
        context.drawImage(avatarImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 512, 512);
        const payload = new FormData();
        payload.append('upload_token', uploadToken);
        payload.append('avatar_crop_data', canvas.toDataURL('image/jpeg', 0.92));

        try {
          const response = await fetch(`${window.FLOWDESK_BASE || ''}/perfil/avatar/confirmar`, { method: 'POST', body: payload });
          const result = await response.json();
          if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível salvar a foto.');
          document.querySelectorAll('.fd-settings-avatar, .fd-sidebar-account-avatar, .fd-user-menu-avatar').forEach(function (image) {
            if (image instanceof HTMLImageElement) image.src = result.avatar_url;
          });
          const fallback = document.querySelector('.fd-settings-avatar-fallback');
          if (fallback) {
            const image = document.createElement('img');
            image.src = result.avatar_url;
            image.alt = 'Avatar';
            image.className = 'fd-settings-avatar';
            fallback.replaceWith(image);
          }
          resetAvatarState();
          setAvatarFeedback(result.message, 'success');
        } catch (error) {
          setAvatarFeedback(error.message || 'Não foi possível salvar a foto.', 'error');
        } finally {
          avatarSave.disabled = false;
        }
      });
    }

    const socialLinks = document.querySelector('[data-social-links]');
    if (socialLinks) {
      const popover = socialLinks.querySelector('[data-social-popover]');
      const socialInput = socialLinks.querySelector('[data-social-input]');
      const socialTitle = socialLinks.querySelector('[data-social-title]');
      const socialFeedback = socialLinks.querySelector('[data-social-feedback]');
      let activeTrigger = null;

      const closeSocialPopover = function () {
        popover.hidden = true;
        activeTrigger = null;
        socialFeedback.textContent = '';
        socialFeedback.dataset.state = '';
      };

      socialLinks.querySelectorAll('[data-social-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
          activeTrigger = trigger;
          socialTitle.textContent = `Link do ${trigger.dataset.socialLabel}`;
          socialInput.value = trigger.dataset.socialUrl || '';
          popover.hidden = false;
          socialLinks.querySelector('[data-social-remove]').hidden = !socialInput.value;
          socialInput.focus();
        });
      });

      socialLinks.querySelector('[data-social-close]').addEventListener('click', closeSocialPopover);

      const persistSocialLink = async function (url) {
        if (!activeTrigger) return;
        socialFeedback.textContent = 'Salvando...';
        socialFeedback.dataset.state = 'saving';
        const payload = new FormData();
        payload.append('network', activeTrigger.dataset.socialTrigger);
        payload.append('url', url);
        try {
          const response = await fetch(`${window.FLOWDESK_BASE || ''}/perfil/link`, { method: 'POST', body: payload });
          const result = await response.json();
          if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível salvar o link.');
          activeTrigger.dataset.socialUrl = result.url || '';
          activeTrigger.classList.toggle('is-linked', Boolean(result.url));
          socialFeedback.textContent = result.url ? 'Link salvo.' : 'Link removido.';
          socialFeedback.dataset.state = 'success';
          window.setTimeout(closeSocialPopover, 600);
        } catch (error) {
          socialFeedback.textContent = error.message || 'Não foi possível salvar o link.';
          socialFeedback.dataset.state = 'error';
        }
      };

      socialLinks.querySelector('[data-social-save]').addEventListener('click', function () {
        const url = socialInput.value.trim();
        if (!/^https?:\/\/\S+$/i.test(url)) {
          socialFeedback.textContent = 'Informe um link completo começando com http:// ou https://.';
          socialFeedback.dataset.state = 'error';
          return;
        }
        persistSocialLink(url);
      });
      socialLinks.querySelector('[data-social-remove]').addEventListener('click', function () {
        persistSocialLink('');
      });
    }

    const profilePassword = document.querySelector('[data-profile-password]');
    const passwordStrength = document.querySelector('[data-profile-password-strength]');
    if (profilePassword && passwordStrength) {
      const strengthBar = passwordStrength.querySelector('.fd-password-strength-bar i');
      const strengthLabel = passwordStrength.querySelector('[data-profile-password-label]');
      profilePassword.addEventListener('input', function () {
        const value = profilePassword.value;
        const rules = {
          length: value.length >= 8,
          number: /\d/.test(value),
          special: /[^A-Za-z0-9]/.test(value),
        };
        const score = Object.values(rules).filter(Boolean).length;
        strengthBar.style.width = `${(score / 3) * 100}%`;
        strengthBar.dataset.score = String(score);
        strengthLabel.textContent = score === 3 ? 'Senha forte' : score === 2 ? 'Senha media' : value ? 'Senha fraca' : 'Digite uma nova senha';
        Object.entries(rules).forEach(function ([rule, valid]) {
          passwordStrength.querySelector(`[data-password-rule="${rule}"]`)?.classList.toggle('is-valid', valid);
        });
      });
    }
  });
</script>
