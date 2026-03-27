<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/ClienteModel.php';

$status_cliente = $_GET['status_cliente'] ?? ['todos'];
if (!is_array($status_cliente)) {
    $status_cliente = [$status_cliente];
}

$busca = trim($_GET['busca'] ?? '');

$model = new ClienteModel($pdo);
$clientes_filtrados = $model->listarFiltrados($status_cliente, $busca);

$contagem = count($clientes_filtrados);
$clienteSelecionadoId = isset($_GET['cliente']) ? (int) $_GET['cliente'] : 0;
$idsFiltrados = array_map(static fn(array $cli): int => (int) $cli['id'], $clientes_filtrados);

if ($clienteSelecionadoId <= 0 || !in_array($clienteSelecionadoId, $idsFiltrados, true)) {
    $clienteSelecionadoId = $idsFiltrados[0] ?? 0;
}

$clienteSelecionado = $clienteSelecionadoId > 0 ? $model->buscarResumoOperacional($clienteSelecionadoId) : null;
$cliente = $clienteSelecionadoId > 0 ? $model->buscarPorId($clienteSelecionadoId) : null;
$atividadesRecentes = $clienteSelecionadoId > 0 ? $model->listarAtividadesRecentes($clienteSelecionadoId) : [];

$mensagens = [];
$canManageClientes = fd_has_any_role(['owner', 'admin', 'operacional']);

if (isset($_GET['ok'])) {
    $mensagens[] = ['type' => 'success', 'text' => 'Cliente salvo com sucesso.'];
}

if (isset($_GET['erro'])) {
    $mensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel salvar o cliente.'];
}

$fmtMoney = static function ($value): string {
    return 'R$' . number_format((float) $value, 2, ',', '.');
};

$fmtMoneyCompact = static function ($value): string {
    $value = (float) $value;

    if (abs($value) >= 1000) {
        $compact = $value / 1000;
        $decimals = fmod($compact, 1.0) === 0.0 ? 0 : 1;
        return 'R$' . number_format($compact, $decimals, ',', '.') . 'k';
    }

    return 'R$' . number_format($value, 0, ',', '.');
};

$fmtDate = static function (?string $value): string {
    if (!$value) {
        return 'Sem data';
    }

    $time = strtotime($value);
    return $time ? date('d/m/Y', $time) : 'Sem data';
};

$buildClienteUrl = static function (int $clienteId) use ($base, $busca, $status_cliente): string {
    $params = ['cliente' => $clienteId];

    if ($busca !== '') {
        $params['busca'] = $busca;
    }

    if (!empty($status_cliente)) {
        $params['status_cliente'] = $status_cliente;
    }

    return ($base ?? '') . '/clientes?' . http_build_query($params);
};
?>

<div class="fd-clientes fd-clientes-master">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Relacionamento</p>
            <p class="fd-page-subtitle">Centralize contatos, contexto e proximos passos em uma visao operacional unica.</p>
        </div>

        <?php if ($canManageClientes): ?>
            <div class="fd-action-group">
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                    <i class="ri-user-add-line"></i>
                    <span>Adicionar cliente</span>
                </button>
            </div>
        <?php endif; ?>
    </section>

    <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-clientes-toolbar">
        <form method="get" action="<?= ($base ?? '') ?>/clientes" class="fd-client-search-form">
            <?php foreach ($status_cliente as $statusItem): ?>
                <input type="hidden" name="status_cliente[]" value="<?= htmlspecialchars($statusItem) ?>">
            <?php endforeach; ?>
            <?php if ($clienteSelecionadoId > 0): ?>
                <input type="hidden" name="cliente" value="<?= $clienteSelecionadoId ?>">
            <?php endif; ?>

            <label class="fd-client-search">
                <i class="ri-search-line"></i>
                <input
                    type="search"
                    name="busca"
                    value="<?= htmlspecialchars($busca) ?>"
                    class="fd-input"
                    placeholder="Pesquisar por cliente, tipo, empresa..."
                >
            </label>
        </form>
        
            <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalFiltroClientes">
                <i class="ri-filter-3-line"></i>
                <span>Filtrar</span>
            </button>
    </section>

    <section class="fd-clientes-shell">
        <aside class="fd-clientes-sidebar">
            <article class="fd-client-list-panel">
                        <div class="fd-clientes-toolbar-copy">
            <p class="fd-clientes-summary-text">Total de cliente<?= $contagem === 1 ? '' : 's' ?> cadastrado<?= $contagem === 1 ? '' : 's' ?>.</p>
            <span class="fd-clientes-summary-count" aria-label="Total de clientes cadastrados"><?= $contagem ?></span>
        </div>
                <?php if (empty($clientes_filtrados)): ?>
                    <div class="fd-empty-state fd-empty-state-compact">
                        <p class="fd-empty-copy">Nenhum cliente encontrado com os filtros atuais.</p>
                    </div>
                <?php else: ?>
                    <div class="fd-client-list">
                        <?php foreach ($clientes_filtrados as $cli): ?>
                            <?php
                            $temFoto = !empty($cli['foto_perfil']);
                            $fotoCliente = $cli['foto_perfil'] ?? '';
                            if ($temFoto && !filter_var($fotoCliente, FILTER_VALIDATE_URL)) {
                                if (str_starts_with($fotoCliente, '/')) {
                                    $fotoCliente = ($base ?? '') . $fotoCliente;
                                } else {
                                    $fotoCliente = ($base ?? '') . '/' . ltrim($fotoCliente, '/');
                                }
                            }

                            $inicial = strtoupper(mb_substr($cli['nome'], 0, 1));
                            $status = strtolower($cli['status'] ?? 'inativo');
                            $statusMap = [
                                'ativo' => ['label' => 'Ativo', 'class' => 'fd-badge-success'],
                                'potencial' => ['label' => 'Potencial', 'class' => 'fd-badge-warning'],
                                'inativo' => ['label' => 'Inativo', 'class' => 'fd-badge-neutral'],
                            ];
                            $statusInfo = $statusMap[$status] ?? $statusMap['inativo'];
                            $isSelected = (int) $cli['id'] === $clienteSelecionadoId;
                            ?>
                            <a href="<?= $buildClienteUrl((int) $cli['id']) ?>" class="fd-client-row-link">
                                <article class="fd-client-row <?= $isSelected ? 'is-active' : '' ?>">
                                    <div class="fd-client-row-main">
                                        <?php if ($temFoto): ?>
                                            <img src="<?= htmlspecialchars($fotoCliente) ?>" alt="Foto de <?= htmlspecialchars($cli['nome']) ?>" class="fd-client-avatar">
                                        <?php else: ?>
                                            <div class="fd-client-avatar fd-client-avatar-fallback">
                                                <span><?= $inicial ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="fd-client-row-copy">
                                            <h3 class="fd-client-name"><?= htmlspecialchars($cli['nome']) ?></h3>
                                            <p class="fd-client-date">Desde <?= $fmtDate($cli['criado_em'] ?? null) ?></p>
                                            <span class="fd-badge <?= $statusInfo['class'] ?> fd-client-row-badge"><?= $statusInfo['label'] ?></span>
                                        </div>
                                    </div>

                                    <div class="fd-client-row-side">
                                        <strong class="fd-client-row-value"><?= $fmtMoneyCompact($cli['receita_recebida'] ?? 0) ?></strong>
                                    </div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </aside>

        <div class="fd-clientes-preview">
            <?php if (!$clienteSelecionado): ?>
                <article class="fd-card fd-client-preview-empty">
                    <p class="fd-card-title">Nenhum cliente selecionado</p>
                    <p class="fd-card-subtitle">Escolha um cliente na lista para abrir o resumo operacional aqui.</p>
                </article>
            <?php else: ?>
                <?php
                $fotoSelecionada = $clienteSelecionado['foto_perfil'] ?: '/assets/img/avatar.png';
                if (!filter_var($fotoSelecionada, FILTER_VALIDATE_URL)) {
                    if (str_starts_with($fotoSelecionada, '/')) {
                        $fotoSelecionada = ($base ?? '') . $fotoSelecionada;
                    } else {
                        $fotoSelecionada = ($base ?? '') . '/' . ltrim($fotoSelecionada, '/');
                    }
                }

                $statusSelecionado = strtolower($clienteSelecionado['status'] ?? 'inativo');
                $statusInfoSelecionado = [
                    'ativo' => ['label' => 'Ativo', 'class' => 'fd-badge-success'],
                    'potencial' => ['label' => 'Potencial', 'class' => 'fd-badge-warning'],
                    'inativo' => ['label' => 'Inativo', 'class' => 'fd-badge-neutral'],
                ][$statusSelecionado] ?? ['label' => 'Inativo', 'class' => 'fd-badge-neutral'];

                $activityMap = [
                    'projeto' => ['class' => 'fd-activity-project', 'icon' => 'ri-folder-2-line'],
                    'orcamento' => ['class' => 'fd-activity-budget', 'icon' => 'ri-file-list-3-line'],
                    'financeiro' => ['class' => 'fd-activity-money', 'icon' => 'ri-wallet-3-line'],
                ];
                ?>

                <article class="fd-card fd-client-preview-card">
                    <div class="fd-client-preview-head">
                        <div class="fd-client-preview-ident">
                            <img src="<?= htmlspecialchars($fotoSelecionada) ?>" alt="Foto de <?= htmlspecialchars($clienteSelecionado['nome']) ?>" class="fd-cliente-profile-avatar">

                            <div class="fd-client-preview-copy">
                                <div class="fd-client-preview-title-row">
                                    <h3 class="fd-page-title"><?= htmlspecialchars($clienteSelecionado['nome']) ?></h3>
                                    <span class="fd-badge <?= $statusInfoSelecionado['class'] ?>"><?= $statusInfoSelecionado['label'] ?></span>
                                </div>

                                <p class="fd-card-subtitle">Desde <?= $fmtDate($clienteSelecionado['criado_em'] ?? null) ?></p>
                                <div class="fd-client-preview-meta">
                                    <span><i class="ri-mail-line"></i><?= htmlspecialchars($clienteSelecionado['email'] ?: 'Sem e-mail cadastrado') ?></span>
                                    <span><i class="ri-phone-line"></i><?= htmlspecialchars($clienteSelecionado['whatsapp'] ?: 'Sem WhatsApp informado') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="fd-action-group">
                            <?php if ($canManageClientes): ?>
                                <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarCliente">
                                    <i class="ri-edit-box-line"></i>
                                    <span>Editar cliente</span>
                                </button>
                            <?php endif; ?>

                            <a href="<?= ($base ?? '') ?>/cliente?id=<?= (int) $clienteSelecionado['id'] ?>" class="fd-btn-primary">
                                <i class="ri-eye-line"></i>
                                <span>Ver detalhes</span>
                            </a>
                        </div>
                    </div>

                    <section class="fd-client-preview-metrics">
                        <article class="fd-client-metric-card">
                            <span class="fd-client-metric-label">Receita recebida</span>
                            <strong class="fd-client-metric-value"><?= $fmtMoney($clienteSelecionado['receita_recebida'] ?? 0) ?></strong>
                        </article>

                        <article class="fd-client-metric-card">
                            <span class="fd-client-metric-label">Projetos abertos</span>
                            <strong class="fd-client-metric-value"><?= (int) ($clienteSelecionado['projetos_abertos'] ?? 0) ?></strong>
                        </article>

                        <article class="fd-client-metric-card">
                            <span class="fd-client-metric-label">Valor em propostas</span>
                            <strong class="fd-client-metric-value"><?= $fmtMoney($clienteSelecionado['valor_orcamentos'] ?? 0) ?></strong>
                        </article>
                    </section>

                    <section class="fd-client-preview-section">
                        <div class="fd-client-context-grid">
                            <div>
                                <span class="fd-client-info-label">Genero / tipo</span>
                                <strong class="fd-client-info-value"><?= htmlspecialchars(ucfirst($clienteSelecionado['genero'] ?? 'empresa')) ?></strong>
                            </div>

                            <div>
                                <span class="fd-client-info-label">Propostas emitidas</span>
                                <strong class="fd-client-info-value"><?= (int) ($clienteSelecionado['total_orcamentos'] ?? 0) ?></strong>
                            </div>

                            <div class="fd-client-context-block">
                                <span class="fd-client-info-label">Observacoes internas</span>
                                <strong class="fd-client-info-value"><?= htmlspecialchars($clienteSelecionado['observacoes'] ?: 'Sem observacoes registradas ate agora.') ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="fd-client-preview-section">
                        <div class="fd-client-preview-section-head">
                            <div>
                                <p class="fd-card-title">Atividades recentes</p>
                            </div>
                        </div>

                        <?php if (empty($atividadesRecentes)): ?>
                            <p class="fd-empty-copy">Ainda nao ha atividades recentes para este cliente.</p>
                        <?php else: ?>
                            <div class="fd-client-activity-list">
                                <?php foreach ($atividadesRecentes as $atividade): ?>
                                    <?php $tipoAtividade = $activityMap[$atividade['tipo']] ?? ['class' => '', 'icon' => 'ri-time-line']; ?>
                                    <article class="fd-client-activity-item <?= $tipoAtividade['class'] ?>">
                                        <div class="fd-client-activity-copy">
                                            <span class="fd-client-activity-title">
                                                <i class="<?= $tipoAtividade['icon'] ?>"></i>
                                                <?= htmlspecialchars($atividade['titulo']) ?>
                                            </span>
                                        </div>

                                        <span class="fd-client-activity-date"><?= $fmtDate($atividade['data_evento'] ?? null) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </article>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/partials/modal_cliente.php'; ?>
<?php include __DIR__ . '/../shared/partials/modal_filtros.php'; ?>
