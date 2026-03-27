<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/AuthModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceMemberModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceInviteModel.php';
require_once __DIR__ . '/../../../app/Models/WorkspaceModel.php';

if (empty($_SESSION['user_id'])) {
  header('Location: ' . ($base ?? '') . '/');
  exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT id, nome, email, foto_perfil, preferred_theme, preferred_locale, preferred_timezone, sidebar_modules_json
  FROM usuarios
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: ' . ($base ?? '') . '/logout');
  exit;
}

$workspaceModel = new WorkspaceModel($pdo);
$workspace = $workspaceModel->buscarAtual();
if (!$workspace) {
  header('Location: ' . ($base ?? '') . '/dashboard');
  exit;
}

$authModel = new AuthModel($pdo);
$workspaceMemberships = $authModel->listarWorkspacesDoUsuario($user_id);
$workspaceIds = array_values(array_unique(array_map(static fn ($item) => (int) ($item['workspace_id'] ?? 0), $workspaceMemberships)));
$workspaceMemberCounts = [];

if (!empty($workspaceIds)) {
  $placeholders = implode(',', array_fill(0, count($workspaceIds), '?'));
  $memberCountStmt = $pdo->prepare("SELECT workspace_id, COUNT(*) AS total FROM workspace_members WHERE workspace_id IN ($placeholders) GROUP BY workspace_id");
  $memberCountStmt->execute($workspaceIds);
  foreach ($memberCountStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $workspaceMemberCounts[(int) $row['workspace_id']] = (int) $row['total'];
  }
}

$subscriptionStmt = $pdo->prepare("
  SELECT s.status, s.started_at, s.expires_at, s.trial_ends_at, p.nome AS plan_nome, p.code AS plan_code, p.preco AS plan_preco
  FROM subscriptions s
  INNER JOIN plans p ON p.id = s.plan_id
  WHERE s.workspace_id = ?
  ORDER BY s.id DESC
  LIMIT 1
");
$subscriptionStmt->execute([(int) ($workspace['id'] ?? 0)]);
$currentSubscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC) ?: null;

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
$integrationProviders = [
  [
    'key' => 'stripe',
    'label' => 'Stripe',
    'badge' => 'Global',
    'description' => 'Checkout recorrente, cartoes internacionais e cobranca por assinatura.',
    'accent' => 'stripe',
    'status' => 'active',
    'health' => 'Saudavel',
    'svg' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="18" fill="#635BFF"/><path fill="#fff" d="M35.6 24.2c-3.2 0-5.2 1.5-5.2 3.9 0 5.3 7.7 4.5 7.7 8.1 0 1.2-1 2-2.9 2-2 0-4.4-.6-6.1-1.5v5.8c1.8.8 4.1 1.2 6.1 1.2 3.6 0 6.2-1.2 7.8-3.1 1-1.2 1.5-2.8 1.5-4.7 0-5.6-7.8-4.8-7.8-8.2 0-1 .8-1.7 2.4-1.7 1.8 0 3.8.4 5.7 1.2v-5.6c-1.7-.7-3.7-1-5.2-1zM22.1 21.1l6.7-1.4v23.5l-6.7 1.4V21.1z"/></svg>',
  ],
  [
    'key' => 'mercadopago',
    'label' => 'Mercado Pago',
    'badge' => 'Brasil',
    'description' => 'Pix, boleto e cartao para operacao local com conciliacao simplificada.',
    'accent' => 'mercadopago',
    'status' => 'pending',
    'health' => 'Configuracao pendente',
    'svg' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="18" fill="#009EE3"/><ellipse cx="32" cy="32" rx="20" ry="14" fill="#FFE16A"/><path fill="#0B3A82" d="M23 34c3.2-3.5 5.8-5.2 9-5.2s5.8 1.7 9 5.2l-3.4 3.2c-2.2-2.4-3.6-3.2-5.6-3.2s-3.4.8-5.6 3.2L23 34z"/><circle cx="25.5" cy="29.5" r="2.5" fill="#0B3A82"/><circle cx="38.5" cy="29.5" r="2.5" fill="#0B3A82"/></svg>',
  ],
  [
    'key' => 'wise',
    'label' => 'Wise',
    'badge' => 'Internacional',
    'description' => 'Recebimentos globais com conversao cambial e conta multimoeda.',
    'accent' => 'wise',
    'status' => 'disconnected',
    'health' => 'Nao conectado',
    'svg' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="18" fill="#163300"/><path fill="#9FE870" d="M18 20h9.5l4.5 13.2L36.6 20H46L35.5 44h-7.2L18 20zm24.8 0H50l-2.4 5.7h-7.1L42.8 20zm-4.6 11.2h7.2L40.9 44h-7.1l4.4-12.8z"/></svg>',
  ],
  [
    'key' => 'asaas',
    'label' => 'Asaas',
    'badge' => 'Cobrancas',
    'description' => 'Boletos, Pix e automacoes de cobranca para operacao no Brasil.',
    'accent' => 'asaas',
    'status' => 'disconnected',
    'health' => 'Nao conectado',
    'svg' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="18" fill="#0F67FF"/><path fill="#fff" d="M32 16 46 48h-7l-2.4-5.8H27.5L25 48h-7l14-32zm1.8 20.8-3-7.6-3 7.6h6z"/></svg>',
  ],
];
$integrationStatusLabels = [
  'active' => 'Ativo',
  'pending' => 'Pendente',
  'disconnected' => 'Desconectado',
];
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

if (isset($_GET['ok'])) $mensagens[] = ['type' => 'success', 'text' => 'Perfil atualizado com sucesso.'];
if (isset($_GET['erro'])) $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar o perfil.'];
if (($_GET['pref'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Preferencias da conta atualizadas com sucesso.'];
if (($_GET['pref'] ?? '') === 'erro') $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar as preferencias da conta.'];
if (($_GET['modules'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Modulos visiveis do menu atualizados com sucesso.'];
if (($_GET['modules'] ?? '') === 'erro') $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar a organizacao de modulos.'];
if (($_GET['workspace'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Configuracoes do workspace atualizadas com sucesso.'];
if (($_GET['workspace'] ?? '') === 'erro') $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar os dados do workspace.'];
if (($_GET['invite'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Convite criado com sucesso. Copie o link e envie para o membro da equipe.'];
if (($_GET['invite'] ?? '') === 'duplicado') $mensagens[] = ['type' => 'danger', 'text' => 'Ja existe um membro ou convite pendente para este e-mail neste workspace.'];
if (($_GET['invite'] ?? '') === 'revogado') $mensagens[] = ['type' => 'success', 'text' => 'Convite revogado com sucesso.'];
if (isset($_GET['invite_accepted'])) $mensagens[] = ['type' => 'success', 'text' => 'Convite aceito com sucesso. O workspace adicional ja esta vinculado a sua conta.'];
if (($_GET['member_role'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Papel do membro atualizado com sucesso.'];
if (($_GET['member_role'] ?? '') === 'erro') $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar o papel deste membro.'];
if (($_GET['member_remove'] ?? '') === 'ok') $mensagens[] = ['type' => 'success', 'text' => 'Membro removido da conta com sucesso.'];
if (($_GET['member_remove'] ?? '') === 'erro') $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel remover este membro da conta.'];
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
        <a href="#integracoes" class="fd-settings-nav-link" data-settings-target="integracoes">Integracoes</a>
        <a href="#pagamentos" class="fd-settings-nav-link" data-settings-target="pagamentos">Pagamentos</a>
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
          <span class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$workspaceRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$workspaceRole] ?? ucfirst($workspaceRole)) ?> no workspace atual</span>
        </div>
      </div>
    </aside>

    <div class="fd-settings-main">
      <section class="fd-settings-section is-active" id="minha-conta" data-settings-panel="minha-conta">
        <div class="fd-settings-section-stack">
          <article class="fd-card fd-settings-overview-card">
            <div class="fd-settings-overview-grid">
              <div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Conta</span><strong><?= htmlspecialchars($user['nome']) ?></strong><small><?= htmlspecialchars($user['email']) ?></small></div>
              <div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Plano atual</span><strong><?= htmlspecialchars($currentSubscription['plan_nome'] ?? 'Nao definido') ?></strong><small><?= htmlspecialchars($statusLabels[$currentSubscription['status'] ?? 'trial'] ?? 'Sem assinatura ativa') ?></small></div>
              <div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Workspace atual</span><strong><?= htmlspecialchars($workspaceNome) ?></strong><small><?= htmlspecialchars($roleLabels[$workspaceRole] ?? ucfirst($workspaceRole)) ?></small></div>
            </div>
          </article>

          <article class="fd-card fd-settings-form-card">
            <div><p class="fd-card-eyebrow">Perfil pessoal</p><h3 class="fd-settings-section-title">Informacoes de acesso</h3><p class="fd-text-muted">Nome, email, senha e avatar da conta.</p></div>
            <form action="<?= ($base ?? '') ?>/perfil/atualizar" method="post" enctype="multipart/form-data" class="fd-settings-form">
              <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
              <div class="fd-settings-fields">
                <div class="fd-settings-field"><label class="form-label small">Nome</label><input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required></div>
                <div class="fd-settings-field"><label class="form-label small">E-mail</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                <div class="fd-settings-field"><label class="form-label small">Nova senha</label><input type="password" name="senha" class="form-control" placeholder="Deixe em branco para manter a atual"></div>
                <div class="fd-settings-field"><label class="form-label small">Foto de perfil</label><input type="file" name="foto_perfil" class="form-control form-control-sm" accept="image/*"><p class="fd-text-muted fd-settings-help">Opcional. JPG ou PNG, ate 2MB.</p></div>
              </div>
              <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i class="ri-save-line"></i><span>Salvar perfil</span></button></div>
            </form>
          </article>

          <article class="fd-card fd-settings-form-card">
            <div><p class="fd-card-eyebrow">Preferencias pessoais</p><h3 class="fd-settings-section-title">Tema, idioma e fuso</h3><p class="fd-text-muted">Essas opcoes afetam como voce enxerga o produto.</p></div>
            <form action="<?= ($base ?? '') ?>/perfil/preferencias" method="post" class="fd-settings-form" data-preferences-form>
              <div class="fd-settings-fields">
                <div class="fd-settings-field"><label class="form-label small">Tema preferido</label><select name="preferred_theme" class="form-select" data-theme-preference><?php foreach ($themeOptions as $value => $label): ?><option value="<?= $value ?>" <?= (($user['preferred_theme'] ?? 'dark') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="fd-settings-field"><label class="form-label small">Idioma</label><select name="preferred_locale" class="form-select"><?php foreach ($localeOptions as $value => $label): ?><option value="<?= $value ?>" <?= (($user['preferred_locale'] ?? 'pt-BR') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Fuso horario</label><select name="preferred_timezone" class="form-select"><?php foreach ($timezoneOptions as $value => $label): ?><option value="<?= $value ?>" <?= (($user['preferred_timezone'] ?? 'America/Sao_Paulo') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
              </div>
              <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i class="ri-equalizer-line"></i><span>Salvar preferencias</span></button></div>
            </form>
          </article>
        </div>
      </section>
      <section class="fd-settings-section" id="meu-workspace" data-settings-panel="meu-workspace">
        <div class="fd-settings-section-stack">
          <article class="fd-card fd-settings-form-card">
            <div><p class="fd-card-eyebrow">Workspaces da conta</p><h3 class="fd-settings-section-title">Onde voce tem acesso</h3><p class="fd-text-muted">Use esta lista para ver em quais workspaces sua conta participa e quantas pessoas existem em cada ambiente.</p></div>
            <div class="fd-settings-workspaces-list">
              <?php foreach ($workspaceMemberships as $membership): ?>
                <?php $membershipWorkspaceId = (int) ($membership['workspace_id'] ?? 0); $isCurrentWorkspace = $membershipWorkspaceId === (int) ($_SESSION['current_workspace_id'] ?? 0); $membershipRole = (string) ($membership['role'] ?? 'viewer'); $memberTotal = $workspaceMemberCounts[$membershipWorkspaceId] ?? 0; ?>
                <article class="fd-settings-workspace-item<?= $isCurrentWorkspace ? ' is-active' : '' ?>">
                  <div class="fd-settings-workspace-item-copy"><strong><?= htmlspecialchars($membership['workspace_nome'] ?? 'Workspace') ?></strong><span class="fd-text-muted"><?= $memberTotal ?> membro<?= $memberTotal === 1 ? '' : 's' ?> Â· <?= htmlspecialchars($roleLabels[$membershipRole] ?? ucfirst($membershipRole)) ?></span></div>
                  <div class="fd-settings-workspace-item-actions">
                    <?php if ($isCurrentWorkspace): ?>
                      <span class="fd-badge fd-badge-success">Atual</span>
                    <?php else: ?>
                      <form method="post" action="<?= ($base ?? '') ?>/workspace/trocar" class="fd-inline-form"><input type="hidden" name="workspace_id" value="<?= $membershipWorkspaceId ?>"><input type="hidden" name="redirect" value="<?= htmlspecialchars(($base ?? '') . '/configuracoes#meu-workspace') ?>"><button type="submit" class="fd-btn-secondary fd-btn-sm"><i class="ri-repeat-line"></i><span>Acessar</span></button></form>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </article>

          <?php if ($canManageWorkspace): ?>
            <article class="fd-card fd-settings-form-card">
              <div><p class="fd-card-eyebrow">Contexto do workspace</p><h3 class="fd-settings-section-title">Setup operacional</h3><p class="fd-text-muted">Esses dados ajudam o produto a entender melhor o tipo de operacao do workspace.</p></div>
              <form action="<?= ($base ?? '') ?>/workspace/atualizar-configuracoes" method="post" class="fd-settings-form">
                <div class="fd-settings-fields">
                  <div class="fd-settings-field"><label class="form-label small">Nome do workspace</label><input type="text" name="workspace_nome" class="form-control" value="<?= htmlspecialchars((string) ($workspace['nome'] ?? '')) ?>" required></div>
                  <div class="fd-settings-field"><label class="form-label small">Segmento principal</label><select name="segmento" class="form-select" required><option value="">Selecione</option><?php foreach ($segmentos as $value => $label): ?><option value="<?= $value ?>" <?= (($workspace['segmento'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                  <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Objetivo principal</label><select name="objetivo_principal" class="form-select" required><option value="">Selecione</option><?php foreach ($objetivos as $value => $label): ?><option value="<?= $value ?>" <?= (($workspace['objetivo_principal'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                  <div class="fd-settings-field"><label class="form-label small">Tamanho da equipe</label><select name="tamanho_equipe" class="form-select"><option value="">Opcional</option><?php foreach ($tamanhosEquipe as $value => $label): ?><option value="<?= $value ?>" <?= (($workspace['onboarding_tamanho_equipe'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                  <div class="fd-settings-field"><label class="form-label small">Volume de clientes</label><select name="volume_clientes" class="form-select"><option value="">Opcional</option><?php foreach ($volumesClientes as $value => $label): ?><option value="<?= $value ?>" <?= (($workspace['onboarding_volume_clientes'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                  <div class="fd-settings-field"><label class="form-label small">Modulo inicial mais importante</label><select name="modulo_inicial" class="form-select"><option value="">Opcional</option><?php foreach ($modulosIniciais as $value => $label): ?><option value="<?= $value ?>" <?= (($workspace['onboarding_modulo_inicial'] ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                  <div class="fd-settings-field fd-settings-field-span-2"><label class="form-label small">Migracao de dados</label><div class="fd-onboarding-choice-grid"><label class="fd-onboarding-choice <?= !empty($workspace['onboarding_migrar_dados']) ? 'is-selected' : '' ?>"><input type="radio" name="migrar_dados" value="1" <?= !empty($workspace['onboarding_migrar_dados']) ? 'checked' : '' ?>><span><strong>Sim, vou migrar dados</strong><small>Quero trazer base existente para o FlowDesk.</small></span></label><label class="fd-onboarding-choice <?= empty($workspace['onboarding_migrar_dados']) ? 'is-selected' : '' ?>"><input type="radio" name="migrar_dados" value="0" <?= empty($workspace['onboarding_migrar_dados']) ? 'checked' : '' ?>><span><strong>Vou comecar do zero</strong><small>Prefiro estruturar tudo do zero dentro do workspace atual.</small></span></label></div></div>
                </div>
                <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i class="ri-building-line"></i><span>Salvar workspace</span></button></div>
              </form>
            </article>

            <article class="fd-card fd-settings-team-card">
              <div class="fd-settings-team-head"><div><p class="fd-card-eyebrow">Equipe do workspace</p><h3 class="fd-settings-section-title"><?= htmlspecialchars($workspaceNome) ?></h3><p class="fd-text-muted"><?= $workspaceMembersCount ?> membro<?= $workspaceMembersCount === 1 ? '' : 's' ?> vinculado<?= $workspaceMembersCount === 1 ? '' : 's' ?> a esta conta.</p></div></div>
              <?php if (empty($workspaceMembers)): ?>
                <p class="fd-empty-copy">Nenhum membro vinculado a este workspace ainda.</p>
              <?php else: ?>
                <div class="fd-settings-team-list">
                  <?php foreach ($workspaceMembers as $member): ?>
                    <?php $memberAvatar = $member['foto_perfil'] ?: null; if ($memberAvatar && !filter_var($memberAvatar, FILTER_VALIDATE_URL)) { $memberAvatar = str_starts_with($memberAvatar, '/') ? ($base ?? '') . $memberAvatar : ($base ?? '') . '/' . ltrim($memberAvatar, '/'); } $memberInitials = strtoupper(substr(trim((string) $member['nome']), 0, 2)); $memberRole = $member['role'] ?? 'viewer'; ?>
                    <article class="fd-settings-team-member">
                      <div class="fd-settings-team-member-main"><?php if ($memberAvatar): ?><img src="<?= htmlspecialchars($memberAvatar) ?>" alt="Avatar de <?= htmlspecialchars($member['nome']) ?>" class="fd-settings-team-avatar"><?php else: ?><div class="fd-settings-team-avatar fd-settings-team-avatar-fallback"><?= htmlspecialchars($memberInitials ?: 'FD') ?></div><?php endif; ?><div class="fd-settings-team-copy"><strong class="fd-settings-team-name"><?= htmlspecialchars($member['nome']) ?></strong><span class="fd-text-muted"><?= htmlspecialchars($member['email']) ?></span></div></div>
                      <div class="fd-settings-team-meta">
                        <?php if ((int) ($member['is_primary'] ?? 0) === 1): ?><span class="fd-badge fd-badge-info">Principal</span><?php endif; ?>
                        <?php if ($canManageWorkspace && $memberRole !== 'owner' && (int) ($member['user_id'] ?? 0) !== $user_id): ?>
                          <div class="fd-settings-team-meta-stack">
                            <form action="<?= ($base ?? '') ?>/workspace/membros/atualizar-papel" method="post" class="fd-settings-role-form"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><select name="role" class="form-select form-select-sm fd-settings-role-select" onchange="this.form.submit()"><option value="admin" <?= $memberRole === 'admin' ? 'selected' : '' ?>>Admin</option><option value="financeiro" <?= $memberRole === 'financeiro' ? 'selected' : '' ?>>Financeiro</option><option value="operacional" <?= $memberRole === 'operacional' ? 'selected' : '' ?>>Operacional</option><option value="viewer" <?= $memberRole === 'viewer' ? 'selected' : '' ?>>Viewer</option></select></form>
                            <form action="<?= ($base ?? '') ?>/workspace/membros/remover" method="post" class="fd-inline-form" onsubmit="return confirm('Remover este membro do workspace?');"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><button type="submit" class="fd-btn-secondary fd-btn-sm"><i class="ri-user-unfollow-line"></i><span>Remover</span></button></form>
                          </div>
                        <?php else: ?>
                          <span class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$memberRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$memberRole] ?? ucfirst($memberRole)) ?></span>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>
            <article class="fd-card fd-settings-team-card">
              <div class="fd-settings-team-head"><div><p class="fd-card-eyebrow">Convites</p><h3 class="fd-settings-section-title">Equipe e links de convite</h3><p class="fd-text-muted">Crie convites para este workspace e compartilhe o link com seguranca.</p></div></div>
              <form action="<?= ($base ?? '') ?>/workspace/invites/criar" method="post" class="fd-settings-form">
                <div class="fd-settings-fields">
                  <div class="fd-settings-field"><label class="form-label small">E-mail do convidado</label><input type="email" name="email" class="form-control" placeholder="pessoa@empresa.com" required></div>
                  <div class="fd-settings-field"><label class="form-label small">Papel inicial</label><select name="role" class="form-select" required><option value="operacional">Operacional</option><option value="financeiro">Financeiro</option><option value="admin">Admin</option><option value="viewer">Viewer</option></select></div>
                </div>
                <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i class="ri-mail-send-line"></i><span>Gerar convite</span></button></div>
              </form>
              <?php if (!empty($pendingInvites)): ?>
                <div class="fd-settings-team-list">
                  <?php foreach ($pendingInvites as $invite): ?>
                    <?php $inviteRole = $invite['role'] ?? 'viewer'; $inviteLink = ($base ?? '') . '/convite?token=' . urlencode((string) $invite['token']); ?>
                    <article class="fd-settings-team-member fd-settings-invite-member">
                      <div class="fd-settings-team-copy"><strong class="fd-settings-team-name"><?= htmlspecialchars($invite['email']) ?></strong><span class="fd-text-muted">Convite criado por <?= htmlspecialchars($invite['invited_by_nome'] ?? 'Equipe') ?></span><code class="fd-settings-invite-link"><?= htmlspecialchars($inviteLink) ?></code></div>
                      <div class="fd-settings-team-meta fd-settings-team-meta-stack"><span class="fd-badge <?= htmlspecialchars($roleBadgeClasses[$inviteRole] ?? 'fd-badge-neutral') ?>"><?= htmlspecialchars($roleLabels[$inviteRole] ?? ucfirst($inviteRole)) ?></span><span class="fd-text-muted">Expira em <?= htmlspecialchars(fd_format_datetime((string) $invite['expires_at'])) ?></span><div class="fd-action-group"><button type="button" class="fd-btn-secondary fd-btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($inviteLink, ENT_QUOTES) ?>')"><i class="ri-file-copy-line"></i><span>Copiar link</span></button><form action="<?= ($base ?? '') ?>/workspace/invites/revogar" method="post" class="fd-inline-form"><input type="hidden" name="invite_id" value="<?= (int) $invite['id'] ?>"><button type="submit" class="fd-btn-secondary fd-btn-sm"><i class="ri-close-circle-line"></i><span>Revogar</span></button></form></div></div>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>

            <article class="fd-card fd-settings-placeholder-card"><div><p class="fd-card-eyebrow">Acessos especificos</p><h3 class="fd-settings-section-title">Excecoes por modulo</h3><p class="fd-text-muted">Aqui vamos concentrar, na proxima etapa, permissoes extras por pessoa para liberar modulos fora da role padrao em casos pontuais.</p></div><span class="fd-badge fd-badge-info">Preparado para a proxima iteracao</span></article>
          <?php else: ?>
            <article class="fd-card fd-settings-placeholder-card"><div><p class="fd-card-eyebrow">Workspace</p><h3 class="fd-settings-section-title">Acesso limitado pela role atual</h3><p class="fd-text-muted">Seu perfil neste workspace e <strong><?= htmlspecialchars($roleLabels[$workspaceRole] ?? ucfirst($workspaceRole)) ?></strong>. Gestao completa de equipe, convites e contexto operacional fica disponivel apenas para owner e admin.</p></div></article>
          <?php endif; ?>
        </div>
      </section>

      <section class="fd-settings-section" id="integracoes" data-settings-panel="integracoes">
        <div class="fd-settings-integrations-grid">
          <?php foreach ($integrationProviders as $provider): ?>
            <article class="fd-card fd-settings-integration-card fd-settings-integration-card-<?= htmlspecialchars($provider['accent']) ?>">
              <div class="fd-settings-integration-visual">
                <div class="fd-settings-integration-logo fd-settings-integration-logo-svg" aria-hidden="true"><?= $provider['svg'] ?></div>
                <div class="fd-settings-integration-head">
                  <div>
                    <p class="fd-card-eyebrow"><?= htmlspecialchars($provider['badge']) ?></p>
                    <h3 class="fd-settings-section-title"><?= htmlspecialchars($provider['label']) ?></h3>
                  </div>
                  <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($integrationStatusLabels[$provider['status']] ?? 'Em breve') ?></span>
                </div>
              </div>
              <p class="fd-text-muted"><?= htmlspecialchars($provider['description']) ?></p>
              <div class="fd-settings-integration-foot">
                <div class="fd-settings-integration-health">
                  <div class="fd-settings-integration-status fd-settings-integration-status-<?= htmlspecialchars($provider['status']) ?>"><span class="fd-settings-status-dot"></span><span><?= htmlspecialchars($provider['health']) ?></span></div>
                </div>
                <div class="fd-settings-integration-actions"><button type="button" class="fd-btn-secondary" disabled><i class="ri-link"></i><span><?= ($provider['status'] === 'active') ? 'Gerenciar' : 'Conectar' ?></span></button></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="fd-settings-section" id="pagamentos" data-settings-panel="pagamentos">
        <div class="fd-settings-payments-grid">
          <article class="fd-card fd-settings-payment-card"><p class="fd-card-eyebrow">Assinatura atual</p><h3 class="fd-settings-section-title"><?= htmlspecialchars($currentSubscription['plan_nome'] ?? 'Plano nao identificado') ?></h3><div class="fd-settings-overview-grid"><div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Status</span><strong><?= htmlspecialchars($statusLabels[$currentSubscription['status'] ?? 'trial'] ?? 'Sem assinatura') ?></strong><small><?= htmlspecialchars($currentSubscription['plan_code'] ?? 'Sem plano') ?></small></div><div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Preco base</span><strong><?= htmlspecialchars($currentSubscription ? money((float) $currentSubscription['plan_preco']) : 'R$ 0,00') ?></strong><small>valor do plano atual</small></div><div class="fd-settings-overview-item"><span class="fd-settings-meta-label">Trial / vencimento</span><strong><?= htmlspecialchars(!empty($currentSubscription['trial_ends_at']) ? fd_format_date((string) $currentSubscription['trial_ends_at']) : (!empty($currentSubscription['expires_at']) ? fd_format_date((string) $currentSubscription['expires_at']) : 'Sem data')) ?></strong><small>data relevante da assinatura</small></div></div></article>
          <article class="fd-card fd-settings-placeholder-card"><div><p class="fd-card-eyebrow">Meios de pagamento</p><h3 class="fd-settings-section-title">Cartoes e cobranca automatica</h3><p class="fd-text-muted">O cadastro e a edicao de meios de pagamento da assinatura sera centralizado aqui quando a fase de billing for aberta.</p></div><span class="fd-badge fd-badge-neutral">Em breve</span></article>
        </div>
      </section>

      <section class="fd-settings-section" id="modulos" data-settings-panel="modulos">
        <article class="fd-card fd-settings-form-card">
          <div class="fd-settings-modules-hero">
            <div>
              <p class="fd-card-eyebrow">Preferencias visuais</p>
              <h3 class="fd-settings-section-title">Escolha o que aparece na navegacao</h3>
              <p class="fd-text-muted">Essas alteracoes organizam seu painel pessoal sem mudar as permissoes reais do workspace.</p>
            </div>
            <div class="fd-settings-modules-summary">
              <strong><?= count($selectedModuleKeys) ?></strong>
              <span>modulos ativos</span>
            </div>
          </div>
          <form action="<?= ($base ?? '') ?>/perfil/modulos" method="post" class="fd-settings-form">
            <div class="fd-settings-modules-grid">
              <?php foreach ($allModuleOptions as $moduleKey => $moduleConfig): ?>
                <?php $isAllowedModule = in_array($moduleKey, $allowedModulesByRole, true); $isCheckedModule = in_array($moduleKey, $selectedModuleKeys, true); ?>
                <label class="fd-settings-module-card<?= !$isAllowedModule ? ' is-disabled' : '' ?><?= $isCheckedModule ? ' is-checked' : '' ?>">
                  <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($moduleKey) ?>" <?= $isCheckedModule ? 'checked' : '' ?> <?= !$isAllowedModule ? 'disabled' : '' ?>>
                  <span class="fd-settings-module-head">
                    <span class="fd-settings-module-icon"><i class="<?= htmlspecialchars($moduleConfig['icon']) ?>"></i></span>
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
            <div class="fd-settings-actions"><button type="submit" class="fd-btn-primary"><i class="ri-layout-grid-line"></i><span>Salvar organizacao de modulos</span></button></div>
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

  document.querySelectorAll('.fd-settings-module-card').forEach(function (card) {
    const input = card.querySelector('input[type="checkbox"]');
    if (!input) return;

    const syncModuleCard = function () {
      card.classList.toggle('is-checked', input.checked);
    };

    input.addEventListener('change', syncModuleCard);
    syncModuleCard();
  });

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
});
</script>