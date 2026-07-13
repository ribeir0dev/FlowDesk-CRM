<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../../../../app/Models/ClienteModel.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . ($base ?? '') . '/');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo '<p class="fd-empty-copy">Cliente nao informado.</p>';
    return;
}

$model = new ClienteModel($pdo);
$cliente = $model->buscarResumoOperacional($id);

if (!$cliente) {
    echo '<p class="fd-empty-copy">Cliente nao encontrado.</p>';
    return;
}

$rows = $model->buscarBlocos($cliente['id']);
$blocos = [];

foreach ($rows as $row) {
    $data = json_decode($row['conteudo'] ?? '{}', true);
    if (!is_array($data)) {
        $data = [];
    }

    $blocos[$row['slug']] = [
        'titulo' => $row['titulo'],
        'dados' => $data,
        'compartilhado' => (int) $row['compartilhado'],
    ];
}

$partesNome = preg_split('/\s+/', trim($cliente['nome']));
$primeiroNome = $partesNome[0] ?? '';
$ultimoNome = count($partesNome) > 1 ? end($partesNome) : '';
$foto = $cliente['foto_perfil'] ?: '/assets/img/avatar.png';
if (!filter_var($foto, FILTER_VALIDATE_URL)) {
    if (str_starts_with($foto, '/')) {
        $foto = ($base ?? '') . $foto;
    } else {
        $foto = ($base ?? '') . '/' . ltrim($foto, '/');
    }
}
$website = $blocos['website']['dados'] ?? [];
$hospedagem = $blocos['hospedagem']['dados'] ?? [];
$acessoSite = $blocos['acesso_site']['dados'] ?? [];
$registroBr = $blocos['registro_br']['dados'] ?? [];
$dataCadastro = $cliente['criado_em'] ?? null;
$atividadesRecentes = $model->listarAtividadesRecentes($id, 6);

$status = strtolower($cliente['status'] ?? 'inativo');
$statusMap = [
    'ativo' => ['label' => 'Ativo', 'class' => 'fd-badge-success'],
    'potencial' => ['label' => 'Potencial', 'class' => 'fd-badge-warning'],
    'inativo' => ['label' => 'Inativo', 'class' => 'fd-badge-neutral'],
];
$statusInfo = $statusMap[$status] ?? $statusMap['inativo'];
$mensagens = [];
$canManageClientes = fd_has_any_role(['owner', 'admin', 'operacional']);

if (isset($_GET['ok'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Cliente atualizado com sucesso.'];
}
if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar o cliente.'];
}
if (isset($_GET['ok_bloco'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Bloco do cliente salvo com sucesso.'];
}
if (isset($_GET['foto']) && $_GET['foto'] === 'ok') {
    $mensagens[] = ['type' => 'success', 'text' => 'Foto do cliente atualizada com sucesso.'];
}
if (isset($_GET['foto']) && $_GET['foto'] === 'erro') {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel enviar a foto do cliente.'];
}
if (isset($_GET['limit']) && $_GET['limit'] === 'storage') {
    $mensagens[] = [
        'type' => 'danger',
        'text' => (string) ($_SESSION['billing_gate_message'] ?? 'Seu workspace atingiu o limite de armazenamento do plano atual.'),
    ];
}
unset($_SESSION['billing_gate_message']);

$accessCards = [
    [
        'icon' => 'ri-global-line',
        'title' => 'Website',
        'description' => 'Informacoes gerais do site do cliente.',
        'slug' => 'website',
        'modal_title' => 'Website',
        'lines' => [
            !empty($website['url']) ? htmlspecialchars($website['url']) : 'Nao informado',
        ],
    ],
    [
        'icon' => 'ri-database-2-line',
        'title' => 'Acesso a hospedagem',
        'description' => 'Dados de login do painel da hospedagem.',
        'slug' => 'hospedagem',
        'modal_title' => 'Acesso a hospedagem',
        'lines' => [
            'URL: ' . htmlspecialchars($hospedagem['url'] ?? '--'),
            'Usuario: ' . htmlspecialchars($hospedagem['usuario'] ?? '--'),
            'Senha: ' . htmlspecialchars($hospedagem['senha'] ?? '--'),
        ],
    ],
    [
        'icon' => 'ri-lock-2-line',
        'title' => 'Acesso ao website',
        'description' => 'Credenciais do CMS ou painel do site.',
        'slug' => 'acesso_site',
        'modal_title' => 'Acesso ao website',
        'lines' => [
            'URL: ' . htmlspecialchars($acessoSite['url'] ?? '--'),
            'Usuario: ' . htmlspecialchars($acessoSite['usuario'] ?? '--'),
            'Senha: ' . htmlspecialchars($acessoSite['senha'] ?? '--'),
        ],
    ],
    [
        'icon' => 'ri-building-4-line',
        'title' => 'Registro.br',
        'description' => 'Dominios, DNS e conta de registro.',
        'slug' => 'registro_br',
        'modal_title' => 'Acesso Registro.br',
        'lines' => [
            'URL: ' . htmlspecialchars($registroBr['url'] ?? '--'),
            'Usuario: ' . htmlspecialchars($registroBr['usuario'] ?? '--'),
            'Senha: ' . htmlspecialchars($registroBr['senha'] ?? '--'),
        ],
    ],
];
?>

<div class="fd-cliente-detalhe">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Relacionamento</p>
            <div class="fd-client-detail-title-row">
                <h2 class="fd-page-title"><?= htmlspecialchars($cliente['nome']) ?></h2>
                <span class="fd-badge <?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
            </div>
            <p class="fd-page-subtitle">Dados principais, acessos e materiais centralizados em uma unica ficha.</p>
        </div>

        <div class="fd-action-group">
            <?php if ($canManageClientes): ?>
                <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarCliente">
                    <i class="ri-edit-box-line"></i>
                    <span>Editar cliente</span>
                </button>
            <?php endif; ?>

            <a href="<?= ($base ?? '') ?>/clientes" class="fd-btn-secondary">
                <i class="ri-arrow-left-line"></i>
                <span>Voltar</span>
            </a>

            <a href="<?= ($base ?? '') ?>/orcamentos/novo?cliente_id=<?= (int) $cliente['id'] ?>" class="fd-btn-primary">
                <i class="ri-add-line"></i>
                <span>Nova proposta</span>
            </a>

            <a href="<?= ($base ?? '') ?>/projetos?cliente_id=<?= (int) $cliente['id'] ?>" class="fd-btn-primary">
                <i class="ri-add-line"></i>
                <span>Novo projeto</span>
            </a>
        </div>
    </section>

    <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-cliente-hero">
        <article class="fd-card fd-cliente-profile">
            <div class="fd-cliente-profile-media">
                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do cliente" class="fd-cliente-profile-avatar">
                <?php if ($canManageClientes): ?>
                    <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalFotoCliente">
                        <i class="ri-camera-3-line"></i>
                        <span>Enviar foto</span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="fd-cliente-profile-body">
                <div class="fd-cliente-profile-top">
                    <div>
                        <h3 class="fd-client-name"><?= htmlspecialchars($cliente['nome']) ?></h3>
                        <p class="fd-client-meta"><?= htmlspecialchars($cliente['email'] ?? 'Sem e-mail cadastrado') ?></p>
                    </div>

                    <span class="fd-badge <?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                </div>

                <div class="fd-client-info-grid">
                    <div>
                        <span class="fd-client-info-label">Primeiro nome</span>
                        <strong class="fd-client-info-value"><?= htmlspecialchars($primeiroNome) ?></strong>
                    </div>
                    <div>
                        <span class="fd-client-info-label">Ultimo nome</span>
                        <strong class="fd-client-info-value"><?= htmlspecialchars($ultimoNome) ?></strong>
                    </div>
                    <div>
                        <span class="fd-client-info-label">WhatsApp</span>
                        <strong class="fd-client-info-value"><?= htmlspecialchars($cliente['whatsapp']) ?></strong>
                    </div>
                    <div>
                        <span class="fd-client-info-label">Cadastro</span>
                        <strong class="fd-client-info-value"><?= $dataCadastro ? date('d/m/Y', strtotime($dataCadastro)) : 'Sem data' ?></strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="fd-card fd-client-detail-kpis">
            <div class="fd-client-detail-kpi is-green"><span class="fd-reference-icon"><i class="ri-money-dollar-circle-line"></i></span><div><span>Receita recebida</span><strong>R$<?= number_format((float) ($cliente['receita_recebida'] ?? 0), 2, ',', '.') ?></strong><small>Historico financeiro</small></div></div>
            <div class="fd-client-detail-kpi is-violet"><span class="fd-reference-icon"><i class="ri-file-list-3-line"></i></span><div><span>Propostas emitidas</span><strong><?= (int) ($cliente['total_orcamentos'] ?? 0) ?></strong><small>Valor de R$<?= number_format((float) ($cliente['valor_orcamentos'] ?? 0), 2, ',', '.') ?></small></div></div>
            <div class="fd-client-detail-kpi is-blue"><span class="fd-reference-icon"><i class="ri-folder-3-line"></i></span><div><span>Projetos ativos</span><strong><?= (int) ($cliente['projetos_abertos'] ?? 0) ?></strong><small>Em andamento</small></div></div>
            <div class="fd-client-detail-kpi is-orange"><span class="fd-reference-icon"><i class="ri-bar-chart-grouped-line"></i></span><div><span>Status do cliente</span><strong><?= e($statusInfo['label']) ?></strong><small>Relacionamento atual</small></div></div>
        </article>
    </section>

    <section class="fd-card">
        <div class="fd-card-header">
            <div>
                <p class="fd-card-title">
                    <span class="fd-section-icon"><i class="ri-lock-password-line"></i></span>
                    Dados de acesso
                </p>
                <p class="fd-card-subtitle">Blocos de informacao para operacao e suporte.</p>
            </div>
        </div>

        <div class="fd-client-grid fd-client-grid-tight">
            <?php foreach ($accessCards as $card): ?>
                <article class="fd-card fd-client-access-card">
                    <div class="fd-client-access-head">
                        <span class="fd-section-icon"><i class="<?= $card['icon'] ?>"></i></span>
                        <h3><?= $card['title'] ?></h3>
                    </div>
                    <p class="fd-card-subtitle"><?= $card['description'] ?></p>
                    <div class="fd-client-access-lines">
                        <?php foreach ($card['lines'] as $line): ?>
                            <p><?= $line ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($canManageClientes): ?>
                        <button
                            type="button"
                            class="fd-btn-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#modalBlocoCliente"
                            data-slug="<?= $card['slug'] ?>"
                            data-titulo="<?= $card['modal_title'] ?>"
                        >
                            <i class="ri-settings-4-line"></i>
                            <span>Ver/gerenciar</span>
                        </button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="fd-client-detail-bottom-grid">
        <article class="fd-card fd-client-detail-activity-card">
            <div class="fd-card-header"><p class="fd-card-title"><span class="fd-section-icon"><i class="ri-pulse-line"></i></span>Atividades recentes</p></div>
            <?php if (empty($atividadesRecentes)): ?>
                <p class="fd-empty-copy">Nenhuma atividade registrada para este cliente.</p>
            <?php else: ?>
                <div class="fd-client-detail-timeline">
                    <?php foreach ($atividadesRecentes as $atividade): ?>
                        <?php $iconAtividade = ['projeto' => 'ri-folder-check-line', 'orcamento' => 'ri-file-list-3-line', 'financeiro' => 'ri-money-dollar-circle-line'][$atividade['tipo']] ?? 'ri-time-line'; ?>
                        <div><span><i class="<?= $iconAtividade ?>"></i></span><p><strong><?= e($atividade['titulo']) ?></strong><small><?= e($atividade['descricao'] ?? '') ?> - <?= !empty($atividade['data_evento']) ? date('d/m/Y H:i', strtotime($atividade['data_evento'])) : 'Sem data' ?></small></p></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="fd-card fd-client-detail-notes-card">
            <div class="fd-card-header"><p class="fd-card-title"><span class="fd-section-icon"><i class="ri-pushpin-2-line"></i></span>Observacoes internas</p></div>
            <div class="fd-client-detail-note"><p><?= nl2br(e($cliente['observacoes'] ?: 'Nenhuma observacao interna foi registrada para este cliente.')) ?></p><small>Contexto privado do workspace</small></div>
            <?php if ($canManageClientes): ?><button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarCliente"><i class="ri-add-line"></i><span>Editar observacoes</span></button><?php endif; ?>
        </article>

        <article class="fd-card fd-client-detail-files-card">
            <div class="fd-card-header"><p class="fd-card-title"><span class="fd-section-icon"><i class="ri-folder-2-line"></i></span>Arquivos e documentos</p></div>
            <div class="fd-client-file-row"><span><i class="ri-file-shield-2-line"></i></span><div><strong>Contratos</strong><small>Documentos contratuais do cliente</small></div></div>
            <div class="fd-client-file-row"><span><i class="ri-image-line"></i></span><div><strong>Identidade visual</strong><small>Logos e materiais da marca</small></div></div>
            <div class="fd-client-file-row"><span><i class="ri-bill-line"></i></span><div><strong>Comprovantes</strong><small>Recibos e documentos financeiros</small></div></div>
            <a href="<?= ($base ?? '') ?>/relatorio-cliente?token=<?= urlencode($cliente['token_publico']) ?>" target="_blank" class="fd-btn-secondary"><i class="ri-link"></i><span>Abrir relatorio compartilhavel</span></a>
        </article>
    </section>
</div>

<?php include __DIR__ . '/modal_cliente.php'; ?>

