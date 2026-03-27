<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db.php';

$cliente = is_array($cliente ?? null) ? $cliente : [];

$cliente_id = isset($cliente['id'])
    ? (int) $cliente['id']
    : (int) ($_GET['id'] ?? ($_GET['cliente'] ?? 0));

$canManageClientes = fd_has_any_role(['owner', 'admin', 'operacional']);
?>

<?php if ($canManageClientes): ?>
<div class="modal fade modal-right" id="modalBlocoCliente" tabindex="-1" aria-labelledby="modalBlocoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/clientes/blocos/salvar" id="form-bloco-cliente">
                <input type="hidden" name="cliente_id" id="blocoClienteId" value="<?= $cliente_id ?>">
                <input type="hidden" name="slug" id="blocoSlug" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalBlocoClienteLabel">Editar bloco</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Titulo</label>
                        <input type="text" class="form-control" name="titulo" id="blocoTitulo" required>
                    </div>

                    <div id="campoUrl" class="mb-3 d-none">
                        <label class="form-label small">URL</label>
                        <input type="url" class="form-control" name="url" id="blocoUrl" placeholder="https://exemplo.com">
                    </div>

                    <div id="campoUsuario" class="mb-3 d-none">
                        <label class="form-label small">Usuario</label>
                        <input type="text" class="form-control" name="usuario" id="blocoUsuario" placeholder="Usuario de acesso">
                    </div>

                    <div id="campoSenha" class="mb-3 d-none">
                        <label class="form-label small">Senha</label>
                        <input type="text" class="form-control" name="senha" id="blocoSenha" placeholder="Senha de acesso">
                    </div>

                    <div id="campoConteudoLivre" class="mb-3 d-none">
                        <label class="form-label small">Conteudo</label>
                        <textarea class="form-control" name="conteudo_livre" id="blocoConteudoLivre" rows="5" placeholder="Informacoes adicionais"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="blocoCompartilhado" name="compartilhado">
                        <label class="form-check-label small" for="blocoCompartilhado">
                            Compartilhar este bloco com o cliente no relatorio publico
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar bloco</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalBlocoCliente');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var slug = button.getAttribute('data-slug');
        var titulo = button.getAttribute('data-titulo') || '';

        document.getElementById('blocoSlug').value = slug;
        document.getElementById('blocoTitulo').value = titulo;

        document.getElementById('blocoUrl').value = '';
        document.getElementById('blocoUsuario').value = '';
        document.getElementById('blocoSenha').value = '';
        document.getElementById('blocoConteudoLivre').value = '';
        document.getElementById('blocoCompartilhado').checked = false;

        ['campoUrl', 'campoUsuario', 'campoSenha', 'campoConteudoLivre'].forEach(function (id) {
            document.getElementById(id).classList.add('d-none');
        });

        if (slug === 'website') {
            document.getElementById('campoUrl').classList.remove('d-none');
        } else if (slug === 'hospedagem' || slug === 'acesso_site' || slug === 'registro_br') {
            document.getElementById('campoUrl').classList.remove('d-none');
            document.getElementById('campoUsuario').classList.remove('d-none');
            document.getElementById('campoSenha').classList.remove('d-none');
        } else {
            document.getElementById('campoConteudoLivre').classList.remove('d-none');
        }

        fetch('<?= ($base ?? '') ?>/clientes/bloco?cliente_id=<?= $cliente_id ?>&slug=' + encodeURIComponent(slug))
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;

                if (data.titulo) document.getElementById('blocoTitulo').value = data.titulo;
                if (data.compartilhado === '1' || data.compartilhado === 1) {
                    document.getElementById('blocoCompartilhado').checked = true;
                }

                if (data.conteudo) {
                    try {
                        var c = JSON.parse(data.conteudo);
                        if (c.url) document.getElementById('blocoUrl').value = c.url;
                        if (c.usuario) document.getElementById('blocoUsuario').value = c.usuario;
                        if (c.senha) document.getElementById('blocoSenha').value = c.senha;
                        if (c.livre) document.getElementById('blocoConteudoLivre').value = c.livre;
                    } catch (e) {
                        document.getElementById('campoConteudoLivre').classList.remove('d-none');
                        document.getElementById('blocoConteudoLivre').value = data.conteudo;
                    }
                }
            })
            .catch(function () {});
    });
});
</script>

<?php if ($canManageClientes): ?>
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/clientes/atualizar" id="form-editar-cliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarClienteLabel">Editar cliente</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= isset($cliente['id']) ? (int) $cliente['id'] : 0; ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Nome completo</label>
                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($cliente['whatsapp'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">E-mail</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Genero / Tipo</label>
                            <select name="genero" class="form-select">
                                <?php $g = $cliente['genero'] ?? 'empresa'; ?>
                                <option value="masculino" <?= $g === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="feminino" <?= $g === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                                <option value="empresa" <?= $g === 'empresa' ? 'selected' : '' ?>>Empresa</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                <?php $s = $cliente['status'] ?? 'ativo'; ?>
                                <option value="ativo" <?= $s === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="potencial" <?= $s === 'potencial' ? 'selected' : '' ?>>Em potencial</option>
                                <option value="inativo" <?= $s === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Observacoes internas</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Notas, contexto e preferencias do cliente..."><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageClientes): ?>
<div class="modal fade" id="modalNovoCliente" tabindex="-1" aria-labelledby="modalNovoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/clientes/criar" id="form-novo-cliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoClienteLabel">Adicionar cliente</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Nome completo</label>
                            <input type="text" name="nome" class="form-control" placeholder="Nome do cliente" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control js-telefone" placeholder="(00) 00000-0000" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="email@cliente.com" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                <option value="ativo" selected>Ativo</option>
                                <option value="potencial">Em potencial</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small">Genero / Tipo</label>
                            <select name="genero" class="form-select">
                                <option value="masculino">Masculino</option>
                                <option value="feminino">Feminino</option>
                                <option value="empresa" selected>Empresa</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Observacoes internas (opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Notas sobre o cliente, contexto e preferencias..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageClientes): ?>
<div class="modal fade" id="modalFotoCliente" tabindex="-1" aria-labelledby="modalFotoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= ($base ?? '') ?>/clientes/upload-foto" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoClienteLabel">Alterar foto do cliente</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="cliente_id" value="<?= (int) ($cliente['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label small">Escolher arquivo</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar foto</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
