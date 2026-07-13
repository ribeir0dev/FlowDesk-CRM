<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/CodigoModel.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Codigo nao encontrado.');
}

$model = new CodigoModel($pdo);
$codigo = $model->buscarPorId($id);
if (!$codigo) {
    http_response_code(404);
    exit('Codigo nao encontrado.');
}

$dificuldadeLabels = [
    'basico' => 'Basico',
    'intermediario' => 'Intermediario',
    'avancado' => 'Avancado',
];

$dificuldadeLabel = $dificuldadeLabels[$codigo['dificuldade'] ?? 'basico'] ?? ucfirst((string) ($codigo['dificuldade'] ?? 'basico'));
$descricao = trim((string) ($codigo['descricao'] ?? ''));
$previewImage = trim((string) ($codigo['preview_image'] ?? ''));
if ($previewImage !== '' && !filter_var($previewImage, FILTER_VALIDATE_URL)) {
    $previewImage = ($base ?? '') . '/' . ltrim($previewImage, '/');
}
$categorias = $model->listarFiltros();

$codigoMensagens = [];
if (isset($_GET['updated'])) {
    $codigoMensagens[] = ['type' => 'success', 'text' => 'Codigo atualizado com sucesso.'];
}
if (isset($_GET['erro'])) {
    $codigoMensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel atualizar este codigo.'];
}
unset($_SESSION['codigo_error_detail']);
$canManageCodigos = in_array(fd_current_workspace_role(), ['owner', 'admin', 'operacional'], true);
?>

<div class="fd-codigo-detalhe">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Codigos > <?= htmlspecialchars($codigo['categoria']) ?></p>
            <p class="fd-page-subtitle">Acervo tecnico do workspace para uso recorrente em projetos, sites e automacoes.</p>
        </div>

        <div class="fd-page-actions">
            <a href="<?= ($base ?? '') ?>/codigos" class="fd-btn-secondary">
                <i class="ri-arrow-left-line"></i>
                <span>Voltar</span>
            </a>
        </div>
    </section>

    <?php foreach ($codigoMensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert"><?= e($mensagem['text']) ?></div>
    <?php endforeach; ?>

    <section class="fd-card fd-codigo-detalhe-hero fd-codigo-detalhe-shell">
        <div class="fd-codigo-detalhe-top">
            <div class="fd-codigo-detalhe-intro">
                <p class="fd-card-eyebrow">Biblioteca tecnica</p>
                <h1 class="fd-codigo-detalhe-title"><?= htmlspecialchars((string) ($codigo['titulo'] ?? 'Codigo sem titulo')) ?></h1>
                <p class="fd-codigo-detalhe-description"><?= htmlspecialchars($descricao !== '' ? $descricao : 'Snippet salvo no workspace para reutilizacao rapida em projetos e entregas.') ?></p>
                <div class="fd-codigo-detalhe-badges">
                    <span class="fd-badge fd-badge-info"><?= htmlspecialchars((string) ($codigo['categoria'] ?? 'Sem categoria')) ?></span>
                    <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars((string) ($codigo['tipo'] ?? 'Snippet')) ?></span>
                    <span class="fd-badge fd-badge-neutral"><?= htmlspecialchars($dificuldadeLabel) ?></span>
                </div>
            </div>
            <div class="fd-codigo-detalhe-actions">
                <button type="button" class="fd-btn-primary js-copy-codigo-detalhe" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-content="<?= htmlspecialchars((string) ($codigo['conteudo'] ?? ''), ENT_QUOTES) ?>">
                    <i class="ri-file-copy-line"></i>
                    <span>Copiar codigo</span>
                </button>
                <a href="mailto:?subject=<?= rawurlencode((string) ($codigo['titulo'] ?? 'Codigo FlowDesk')) ?>" class="fd-btn-secondary">
                    <i class="ri-share-forward-line"></i>
                    <span>Compartilhar</span>
                </a>
                <?php if ($canManageCodigos): ?>
                    <button type="button" class="fd-btn-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarCodigo">
                        <i class="ri-edit-line"></i>
                        <span>Editar</span>
                    </button>
                    <button type="button" class="fd-btn-secondary fd-btn-danger-soft js-delete-codigo" data-bs-toggle="modal" data-bs-target="#modalExcluirCodigo" data-codigo-id="<?= (int) $codigo['id'] ?>" data-codigo-titulo="<?= htmlspecialchars((string) ($codigo['titulo'] ?? 'este codigo'), ENT_QUOTES) ?>">
                        <i class="ri-delete-bin-line"></i>
                        <span>Excluir</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="fd-codigo-detalhe-preview-wrap">
            <div class="fd-codigo-detalhe-preview-bar">
                <span class="fd-codigo-detalhe-dot"></span>
                <span class="fd-codigo-detalhe-dot"></span>
                <span class="fd-codigo-detalhe-dot"></span>
                <span class="fd-codigo-detalhe-preview-label"><?= $previewImage !== '' ? 'Preview visual' : 'Preview tecnico' ?></span>
            </div>
            <div class="fd-codigo-detalhe-preview">
                <?php if ($previewImage !== ''): ?>
                    <img src="<?= htmlspecialchars($previewImage) ?>" alt="Preview de <?= htmlspecialchars((string) ($codigo['titulo'] ?? 'codigo')) ?>" class="fd-codigo-detalhe-image">
                <?php else: ?>
                    <pre><?= htmlspecialchars((string) ($codigo['conteudo'] ?? '')) ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="fd-codigo-detalhe-grid fd-codigo-detalhe-grid-premium">
        <article class="fd-card fd-codigo-meta-card">
            <span class="fd-codigo-meta-icon"><i class="ri-user-line"></i></span>
            <div>
                <strong>Usuario</strong>
                <span><?= htmlspecialchars($codigo['autor_nome'] ?? 'Workspace') ?></span>
            </div>
        </article>
        <article class="fd-card fd-codigo-meta-card">
            <span class="fd-codigo-meta-icon"><i class="ri-sparkling-line"></i></span>
            <div>
                <strong>Dificuldade</strong>
                <span><?= htmlspecialchars($dificuldadeLabel) ?></span>
            </div>
        </article>
        <article class="fd-card fd-codigo-meta-card">
            <span class="fd-codigo-meta-icon"><i class="ri-time-line"></i></span>
            <div>
                <strong>Ultima atualizacao</strong>
                <span><?= htmlspecialchars(fd_format_datetime((string) ($codigo['atualizado_em'] ?? $codigo['criado_em'] ?? ''))) ?></span>
            </div>
        </article>
        <article class="fd-card fd-codigo-meta-card">
            <span class="fd-codigo-meta-icon"><i class="ri-code-box-line"></i></span>
            <div>
                <strong>Tipo</strong>
                <span><?= htmlspecialchars((string) ($codigo['tipo'] ?? 'Snippet')) ?></span>
            </div>
        </article>
    </section>

    <section class="fd-codigo-content-grid">
        <section class="fd-card fd-codigo-copy-section">
            <h3 class="fd-settings-section-title">Sobre este codigo</h3>
            <p class="fd-text-muted"><?= nl2br(htmlspecialchars((string) ($codigo['descricao'] ?? 'Sem descricao adicional.'))) ?></p>
        </section>

        <section class="fd-card fd-codigo-copy-section">
            <h3 class="fd-settings-section-title">Instrucoes de uso</h3>
            <div class="fd-codigo-instrucoes"><?= nl2br(htmlspecialchars((string) ($codigo['instrucoes'] ?: 'Sem instrucoes detalhadas para este codigo.'))) ?></div>
        </section>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('.js-copy-codigo-detalhe');
    if (button) {
        button.addEventListener('click', async function () {
            const content = button.dataset.codigoContent || '';
            const codigoId = button.dataset.codigoId || '';
            if (!content) return;

            await navigator.clipboard.writeText(content);
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
                if (label) label.textContent = original;
            }, 1600);
        });
    }

    const deleteModal = document.getElementById('modalExcluirCodigo');
    if (deleteModal) {
        const titleTarget = deleteModal.querySelector('[data-delete-codigo-title]');
        const inputTarget = deleteModal.querySelector('input[name="codigo_id"]');

        document.querySelectorAll('.js-delete-codigo').forEach(function (deleteButton) {
            deleteButton.addEventListener('click', function () {
                if (titleTarget) titleTarget.textContent = deleteButton.dataset.codigoTitulo || 'este codigo';
                if (inputTarget) inputTarget.value = deleteButton.dataset.codigoId || '';
            });
        });
    }
});
</script>

<?php if ($canManageCodigos): ?>
<div class="modal fade" id="modalEditarCodigo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content fd-modal-content">
            <div class="modal-header fd-modal-header">
                <div>
                    <p class="fd-card-eyebrow">Modulo Codigos</p>
                    <h2 class="fd-modal-title">Editar codigo</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= ($base ?? '') ?>/codigos/atualizar" method="post" class="fd-modal-form" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="codigo_id" value="<?= (int) $codigo['id'] ?>">
                <div class="modal-body fd-modal-body">
                    <div class="fd-settings-fields">
                        <div class="fd-settings-field fd-settings-field-span-2">
                            <label class="form-label small">Titulo do codigo</label>
                            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars((string) ($codigo['titulo'] ?? '')) ?>" required>
                        </div>
                        <div class="fd-settings-field fd-settings-field-span-2">
                            <label class="form-label small">Descricao</label>
                            <textarea name="descricao" rows="3" class="form-control"><?= htmlspecialchars((string) ($codigo['descricao'] ?? '')) ?></textarea>
                        </div>
                        <div class="fd-settings-field">
                            <label class="form-label small">Categoria</label>
                            <input type="text" name="categoria" class="form-control" value="<?= htmlspecialchars((string) ($codigo['categoria'] ?? '')) ?>" list="codigo-categorias-editar" required>
                            <datalist id="codigo-categorias-editar">
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="fd-settings-field">
                            <label class="form-label small">Tipo</label>
                            <input type="text" name="tipo" class="form-control" value="<?= htmlspecialchars((string) ($codigo['tipo'] ?? 'Snippet')) ?>">
                        </div>
                        <div class="fd-settings-field">
                            <label class="form-label small">Dificuldade</label>
                            <select name="dificuldade" class="form-select">
                                <?php foreach ($dificuldadeLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= (($codigo['dificuldade'] ?? 'basico') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fd-settings-field fd-settings-field-span-2">
                            <label class="form-label small">Instrucoes de uso</label>
                            <textarea name="instrucoes" rows="4" class="form-control"><?= htmlspecialchars((string) ($codigo['instrucoes'] ?? '')) ?></textarea>
                        </div>
                        <div class="fd-settings-field fd-settings-field-span-2">
                            <label class="form-label small">Imagem de preview</label>
                            <?php if ($previewImage !== ''): ?>
                                <div class="fd-codigo-edit-preview-current">
                                    <img src="<?= htmlspecialchars($previewImage) ?>" alt="Preview atual">
                                    <span>Imagem atual. Envie outra imagem para substituir.</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="preview_image" class="form-control" accept="image/png,image/jpeg,image/gif">
                            <p class="fd-text-muted fd-settings-help">Opcional. PNG, JPEG ou GIF ate 8MB.</p>
                        </div>
                        <div class="fd-settings-field fd-settings-field-span-2">
                            <label class="form-label small">Conteudo do codigo</label>
                            <textarea name="conteudo" rows="10" class="form-control fd-code-textarea" required><?= htmlspecialchars((string) ($codigo['conteudo'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer fd-modal-footer">
                    <button type="button" class="fd-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="fd-btn-primary">
                        <i class="ri-save-line"></i>
                        <span>Salvar alteracoes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/modal_excluir_codigo.php'; ?>
<?php endif; ?>
