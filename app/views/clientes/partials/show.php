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
$cliente = $model->buscarPorId($id);

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
            <h2 class="fd-page-title"><?= htmlspecialchars($cliente['nome']) ?></h2>
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

    <section class="fd-card">
        <div class="fd-card-header">
            <div>
                <p class="fd-card-title">
                    <span class="fd-section-icon"><i class="ri-folder-2-line"></i></span>
                    Arquivos do cliente
                </p>
                <p class="fd-card-subtitle">Espaco reservado para contratos, logos e comprovantes.</p>
            </div>
        </div>

        <div class="fd-client-grid fd-client-grid-tight">
            <article class="fd-card fd-client-access-card">
                <div class="fd-client-access-head">
                    <span class="fd-section-icon"><i class="ri-file-shield-line"></i></span>
                    <h3>Contratos</h3>
                </div>
                <p class="fd-card-subtitle">Documentos contratuais assinados.</p>
                <a href="#" class="fd-btn-secondary">
                    <i class="ri-folder-open-line"></i>
                    <span>Ver/gerenciar</span>
                </a>
            </article>

            <article class="fd-card fd-client-access-card">
                <div class="fd-client-access-head">
                    <span class="fd-section-icon"><i class="ri-image-line"></i></span>
                    <h3>Logos</h3>
                </div>
                <p class="fd-card-subtitle">Arquivos de identidade visual do cliente.</p>
                <a href="#" class="fd-btn-secondary">
                    <i class="ri-folder-open-line"></i>
                    <span>Ver/gerenciar</span>
                </a>
            </article>

            <article class="fd-card fd-client-access-card">
                <div class="fd-client-access-head">
                    <span class="fd-section-icon"><i class="ri-bill-line"></i></span>
                    <h3>Recibos</h3>
                </div>
                <p class="fd-card-subtitle">Comprovantes e faturas pagas.</p>
                <a href="#" class="fd-btn-secondary">
                    <i class="ri-folder-open-line"></i>
                    <span>Ver/gerenciar</span>
                </a>
            </article>
        </div>
    </section>

    <section class="fd-card fd-client-footer-card">
        <div>
            <p class="fd-card-title">Relatorio compartilhavel</p>
            <p class="fd-card-subtitle">Cliente cadastrado em <?= $dataCadastro ? date('d/m/Y', strtotime($dataCadastro)) : 'data indisponivel' ?>.</p>
        </div>

        <a href="<?= ($base ?? '') ?>/relatorio-cliente?token=<?= urlencode($cliente['token_publico']) ?>" target="_blank" class="fd-btn-secondary">
            <i class="ri-link-unlink-m"></i>
            <span>Gerar link de relatorio</span>
        </a>
    </section>
</div>

<?php include __DIR__ . '/modal_cliente.php'; ?>

