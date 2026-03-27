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
if (!empty($_SESSION['codigo_error_detail'])) {
    $codigoMensagens[] = ['type' => 'danger', 'text' => 'Detalhe tecnico: ' . (string) $_SESSION['codigo_error_detail']];
}
unset($_SESSION['codigo_error_detail']);
?>

<div class="fd-codigos">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Base tecnica do workspace</p>
            <p class="fd-page-subtitle">Guarde snippets, codigos de Elementor, CSS, JS e instrucoes de uso dentro do proprio painel.</p>
        </div>

        <div class="fd-page-actions">
            <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCodigo">
                <i class="ri-add-line"></i>
                <span>Novo codigo</span>
            </button>
        </div>
    </section>

    <?php foreach ($codigoMensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert"><?= e($mensagem['text']) ?></div>
    <?php endforeach; ?>

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
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCodigo">
                    <i class="ri-add-line"></i>
                    <span>Criar primeiro codigo</span>
                </button>
            </article>
        <?php else: ?>
            <?php foreach ($codigos as $codigo): ?>
                <?php
                $categoria = (string) ($codigo['categoria'] ?? 'Sem categoria');
                $dificuldade = (string) ($codigo['dificuldade'] ?? 'basico');
                $preview = trim((string) ($codigo['conteudo'] ?? ''));
                $preview = preg_replace('/\s+/', ' ', $preview ?? '');
                $preview = mb_substr($preview, 0, 180);
                ?>
                <article class="fd-card fd-codigo-card">
                    <a href="<?= ($base ?? '') ?>/codigo?id=<?= (int) $codigo['id'] ?>" class="fd-codigo-card-cover">
                        <div class="fd-codigo-card-glow"></div>
                        <pre><?= htmlspecialchars($preview) ?></pre>
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
                            <i class="ri-user-line"></i>
                            <span><?= htmlspecialchars($codigo['autor_nome'] ?? 'Workspace') ?></span>
                        </div>
                        <div class="fd-codigo-card-actions">
                            <form method="post" action="<?= ($base ?? '') ?>/codigos/favoritar" class="fd-inline-form">
                                <input type="hidden" name="codigo_id" value="<?= (int) $codigo['id'] ?>">
                                <button type="submit" class="fd-btn-table <?= !empty($codigo['favorito']) ? 'fd-btn-table-active' : '' ?>" aria-label="Favoritar codigo">
                                    <i class="<?= !empty($codigo['favorito']) ? 'ri-heart-fill' : 'ri-heart-line' ?>"></i>
                                </button>
                            </form>
                            <button type="button" class="fd-btn-secondary fd-btn-sm js-copy-codigo" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-content="<?= htmlspecialchars((string) ($codigo['conteudo'] ?? ''), ENT_QUOTES) ?>">
                                <i class="ri-file-copy-line"></i>
                                <span>Copiar</span>
                            </button>
                            <button type="button" class="fd-btn-table fd-btn-table-danger js-delete-codigo" data-bs-toggle="modal" data-bs-target="#modalExcluirCodigo" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-titulo="<?= htmlspecialchars((string) ($codigo['titulo'] ?? 'este codigo'), ENT_QUOTES) ?>" aria-label="Excluir codigo">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-copy-codigo').forEach(function (button) {
        button.addEventListener('click', async function () {
            const content = button.dataset.codigoContent || '';
            const codigoId = button.dataset.codigoId || '';
            if (!content) return;

            await navigator.clipboard.writeText(content);
            button.classList.add('is-copied');
            const label = button.querySelector('span');
            const original = label ? label.textContent : '';
            if (label) label.textContent = 'Copiado';

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

<?php include __DIR__ . '/partials/modal_codigo.php'; ?>
<?php include __DIR__ . '/partials/modal_excluir_codigo.php'; ?>
