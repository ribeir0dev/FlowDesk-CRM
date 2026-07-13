<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/CodigoModel.php';

$model = new CodigoModel($pdo);

$search = trim((string) ($_GET['q'] ?? ''));
$categoriaAtual = trim((string) ($_GET['categoria'] ?? ''));
$dificuldadeAtual = trim((string) ($_GET['dificuldade'] ?? ''));
$sortAtual = trim((string) ($_GET['sort'] ?? 'recentes'));

$codigos = $model->listarTodos([
    'q' => $search,
    'categoria' => $categoriaAtual,
    'dificuldade' => $dificuldadeAtual,
    'sort' => $sortAtual,
]);
$categorias = $model->listarFiltros();

$dificuldadeLabels = [
    'basico' => 'Basico',
    'intermediario' => 'Intermediario',
    'avancado' => 'Avancado',
];

$codigoMensagens = [];
if (isset($_GET['ok'])) $codigoMensagens[] = ['type' => 'success', 'text' => 'Codigo salvo com sucesso.'];
if (isset($_GET['deleted'])) $codigoMensagens[] = ['type' => 'success', 'text' => 'Codigo removido com sucesso.'];
if (isset($_GET['favorite'])) $codigoMensagens[] = ['type' => 'success', 'text' => 'Favorito atualizado.'];
if (isset($_GET['erro'])) $codigoMensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao em Codigos.'];
unset($_SESSION['codigo_error_detail']);
$canManageCodigos = in_array(fd_current_workspace_role(), ['owner', 'admin', 'operacional'], true);
$totalCodigos = count($codigos);
$totalFavoritos = count(array_filter($codigos, static fn ($codigo) => !empty($codigo['favorito'])));
$totalCopias = array_sum(array_map(static fn ($codigo) => (int) ($codigo['copias'] ?? 0), $codigos));
$categoriasContagem = [];
foreach ($codigos as $codigoResumo) {
    $categoriaResumo = trim((string) ($codigoResumo['categoria'] ?? 'Sem categoria')) ?: 'Sem categoria';
    $categoriasContagem[$categoriaResumo] = ($categoriasContagem[$categoriaResumo] ?? 0) + 1;
}
arsort($categoriasContagem);
?>

<div class="fd-codigos">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Base tecnica do workspace</p>
            <p class="fd-page-subtitle">Guarde snippets, codigos de Elementor, CSS, JS e instrucoes de uso dentro do proprio painel.</p>
        </div>

        <div class="fd-page-actions">
            <?php if ($canManageCodigos): ?>
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCodigo">
                    <i class="ri-add-line"></i>
                    <span>Novo codigo</span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($codigoMensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert"><?= e($mensagem['text']) ?></div>
    <?php endforeach; ?>

    <section class="fd-code-reference-kpis">
        <article class="fd-code-reference-kpi is-blue"><span class="fd-reference-icon"><i class="ri-code-s-slash-line"></i></span><div><span>Total de snippets</span><strong><?= $totalCodigos ?></strong><small>Base tecnica atual</small></div></article>
        <article class="fd-code-reference-kpi is-red"><span class="fd-reference-icon"><i class="ri-heart-3-line"></i></span><div><span>Favoritos</span><strong><?= $totalFavoritos ?></strong><small>Marcados para acesso rapido</small></div></article>
        <article class="fd-code-reference-kpi is-green"><span class="fd-reference-icon"><i class="ri-line-chart-line"></i></span><div><span>Copias realizadas</span><strong><?= $totalCopias ?></strong><small>Uso acumulado</small></div></article>
        <article class="fd-code-reference-kpi is-violet"><span class="fd-reference-icon"><i class="ri-time-line"></i></span><div><span>Ultimos adicionados</span><strong><?= min($totalCodigos, 8) ?></strong><small>Disponiveis no acervo</small></div></article>
    </section>

    <div class="fd-code-reference-layout">
        <div class="fd-code-reference-main">

    <section class="fd-card fd-codigos-toolbar-card">
        <form method="get" action="<?= ($base ?? '') ?>/codigos" class="fd-codigos-toolbar">
            <div class="fd-codigos-search">
                <i class="ri-search-line"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nome ou descricao...">
            </div>

            <select name="categoria" class="form-select fd-codigos-select">
                <option value="">Mostrar todos</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria) ?>" <?= $categoriaAtual === $categoria ? 'selected' : '' ?>><?= htmlspecialchars($categoria) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="form-select fd-codigos-select">
                <option value="recentes" <?= $sortAtual === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                <option value="mais_copiados" <?= $sortAtual === 'mais_copiados' ? 'selected' : '' ?>>Mais copiados</option>
                <option value="favoritos" <?= $sortAtual === 'favoritos' ? 'selected' : '' ?>>Favoritos primeiro</option>
            </select>

            <select name="dificuldade" class="form-select fd-codigos-select">
                <option value="">Todas dificuldades</option>
                <?php foreach ($dificuldadeLabels as $difficultyValue => $difficultyLabel): ?>
                    <option value="<?= $difficultyValue ?>" <?= $dificuldadeAtual === $difficultyValue ? 'selected' : '' ?>><?= $difficultyLabel ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="fd-btn-secondary">
                <i class="ri-filter-3-line"></i>
                <span>Filtrar</span>
            </button>
        </form>
    </section>

    <section class="fd-codigos-grid">
        <?php if (empty($codigos)): ?>
            <article class="fd-card fd-codigos-empty">
                <div>
                    <p class="fd-card-eyebrow">Nada encontrado</p>
                    <h3 class="fd-settings-section-title">Seu acervo de codigos comeca aqui</h3>
                    <p class="fd-text-muted">Adicione snippets, elementos do Elementor, trechos CSS, JS ou instrucoes de uso para guardar sua base tecnica dentro do workspace.</p>
                </div>
                <?php if ($canManageCodigos): ?>
                    <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCodigo">
                        <i class="ri-add-line"></i>
                        <span>Criar primeiro codigo</span>
                    </button>
                <?php endif; ?>
            </article>
        <?php else: ?>
            <?php foreach ($codigos as $codigo): ?>
                <?php
                $categoria = (string) ($codigo['categoria'] ?? 'Sem categoria');
                $dificuldade = (string) ($codigo['dificuldade'] ?? 'basico');
                $preview = trim((string) ($codigo['conteudo'] ?? ''));
                $preview = preg_replace('/\s+/', ' ', $preview ?? '');
                $preview = mb_substr($preview, 0, 180);
                $previewImage = trim((string) ($codigo['preview_image'] ?? ''));
                if ($previewImage !== '' && !filter_var($previewImage, FILTER_VALIDATE_URL)) {
                    $previewImage = ($base ?? '') . '/' . ltrim($previewImage, '/');
                }
                $autorNome = trim((string) ($codigo['autor_nome'] ?? 'Workspace'));
                $autorPrimeiroNome = preg_split('/\s+/', $autorNome)[0] ?? $autorNome;
                $autorFoto = trim((string) ($codigo['autor_foto'] ?? ''));
                if ($autorFoto !== '' && !filter_var($autorFoto, FILTER_VALIDATE_URL)) {
                    $autorFoto = ($base ?? '') . '/' . ltrim($autorFoto, '/');
                }
                $autorInicial = strtoupper(mb_substr($autorPrimeiroNome !== '' ? $autorPrimeiroNome : 'W', 0, 1));
                ?>
                <article class="fd-card fd-codigo-card">
                    <a href="<?= ($base ?? '') ?>/codigo?id=<?= (int) $codigo['id'] ?>" class="fd-codigo-card-cover">
                        <?php if ($previewImage !== ''): ?>
                            <img src="<?= htmlspecialchars($previewImage) ?>" alt="Preview de <?= htmlspecialchars((string) ($codigo['titulo'] ?? 'codigo')) ?>" class="fd-codigo-card-image">
                        <?php else: ?>
                            <div class="fd-codigo-card-glow"></div>
                            <pre><?= htmlspecialchars($preview) ?></pre>
                        <?php endif; ?>
                    </a>
                    <div class="fd-codigo-card-body">
                        <div class="fd-codigo-card-meta">
                            <span class="fd-badge fd-badge-info"><?= htmlspecialchars($categoria) ?></span>
                            <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($dificuldadeLabels[$dificuldade] ?? ucfirst($dificuldade)) ?></span>
                        </div>
                        <h3><a href="<?= ($base ?? '') ?>/codigo?id=<?= (int) $codigo['id'] ?>"><?= htmlspecialchars($codigo['titulo']) ?></a></h3>
                        <p><?= htmlspecialchars($codigo['descricao'] ?: 'Sem descricao adicional.') ?></p>
                    </div>
                    <div class="fd-codigo-card-footer">
                        <div class="fd-codigo-card-author">
                            <?php if ($autorFoto !== ''): ?>
                                <img src="<?= htmlspecialchars($autorFoto) ?>" alt="Foto de <?= htmlspecialchars($autorPrimeiroNome) ?>" class="fd-codigo-card-author-avatar">
                            <?php else: ?>
                                <span class="fd-codigo-card-author-avatar fd-codigo-card-author-fallback"><?= htmlspecialchars($autorInicial) ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($autorPrimeiroNome) ?></span>
                        </div>
                        <div class="fd-codigo-card-actions">
                            <?php if ($canManageCodigos): ?>
                                <form method="post" action="<?= ($base ?? '') ?>/codigos/favoritar" class="fd-inline-form">
                                    <input type="hidden" name="codigo_id" value="<?= (int) $codigo['id'] ?>">
                                    <button type="submit" class="fd-btn-table <?= !empty($codigo['favorito']) ? 'fd-btn-table-active' : '' ?>" aria-label="Favoritar codigo">
                                        <i class="<?= !empty($codigo['favorito']) ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="fd-btn-secondary fd-btn-sm js-copy-codigo" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-content="<?= htmlspecialchars((string) ($codigo['conteudo'] ?? ''), ENT_QUOTES) ?>">
                                <i class="ri-file-copy-line"></i>
                                <span>Copiar</span>
                            </button>
                            <?php if ($canManageCodigos): ?>
                                <button type="button" class="fd-btn-table fd-btn-table-danger js-delete-codigo" data-bs-toggle="modal" data-bs-target="#modalExcluirCodigo" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-titulo="<?= htmlspecialchars((string) ($codigo['titulo'] ?? 'este codigo'), ENT_QUOTES) ?>" aria-label="Excluir codigo">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

        </div>
        <aside class="fd-reference-sidebar fd-code-reference-sidebar">
            <section class="fd-reference-side-card">
                <div class="fd-card-header"><strong>Categorias populares</strong><span>Ver todas</span></div>
                <?php foreach (array_slice($categoriasContagem, 0, 6, true) as $categoriaNome => $categoriaTotal): ?>
                    <div class="fd-code-category-row"><span class="fd-list-icon"><i class="ri-folder-code-line"></i></span><strong><?= e($categoriaNome) ?></strong><b><?= (int) $categoriaTotal ?></b></div>
                <?php endforeach; ?>
            </section>
            <section class="fd-reference-side-card">
                <div class="fd-card-header"><strong>Uso recente</strong><span>Ver todos</span></div>
                <?php foreach (array_slice($codigos, 0, 5) as $codigoRecente): ?>
                    <a class="fd-code-recent-row" href="<?= ($base ?? '') ?>/codigo?id=<?= (int) $codigoRecente['id'] ?>"><span class="fd-list-icon"><i class="ri-code-box-line"></i></span><div><strong><?= e($codigoRecente['titulo']) ?></strong><small><?= e($codigoRecente['categoria'] ?? 'Snippet') ?></small></div></a>
                <?php endforeach; ?>
            </section>
            <section class="fd-reference-side-card fd-code-collection-card"><span class="fd-reference-icon"><i class="ri-code-s-slash-line"></i></span><div><strong>Base do workspace</strong><p>Snippets essenciais reunidos em um unico acervo.</p><span class="fd-badge fd-badge-info"><?= $totalCodigos ?> snippets</span></div></section>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-copy-codigo').forEach(function (button) {
        button.addEventListener('click', async function () {
            const content = button.dataset.codigoContent || '';
            const codigoId = button.dataset.codigoId || '';
            if (!content) return;

            try {
                await navigator.clipboard.writeText(content);
            } catch (error) {
                if (typeof window.fdShowFloatingAlert === 'function') {
                    window.fdShowFloatingAlert('Nao foi possivel copiar o codigo.', 'danger');
                }
                return;
            }
            button.classList.add('is-copied');
            const label = button.querySelector('span');
            const original = label ? label.textContent : '';
            if (label) label.textContent = 'Copiado';
            if (typeof window.fdShowFloatingAlert === 'function') {
                window.fdShowFloatingAlert('Codigo copiado com sucesso.', 'success');
            }

            if (codigoId) {
                fetch('<?= ($base ?? '') ?>/codigos/copiar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({ codigo_id: codigoId, acao: 'copiar' }).toString(),
                }).catch(function () {});
            }

            window.setTimeout(function () {
                button.classList.remove('is-copied');
                if (label) label.textContent = original;
            }, 1600);
        });
    });

    const deleteModal = document.getElementById('modalExcluirCodigo');
    if (deleteModal) {
        const titleTarget = deleteModal.querySelector('[data-delete-codigo-title]');
        const inputTarget = deleteModal.querySelector('input[name="codigo_id"]');

        document.querySelectorAll('.js-delete-codigo').forEach(function (button) {
            button.addEventListener('click', function () {
                if (titleTarget) titleTarget.textContent = button.dataset.codigoTitulo || 'este codigo';
                if (inputTarget) inputTarget.value = button.dataset.codigoId || '';
            });
        });
    }
});
</script>

<?php if ($canManageCodigos): ?>
    <?php include __DIR__ . '/partials/modal_codigo.php'; ?>
    <?php include __DIR__ . '/partials/modal_excluir_codigo.php'; ?>
<?php endif; ?>
