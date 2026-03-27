<?php if (fd_has_any_role(['owner', 'admin', 'financeiro'])): ?>
<div class="modal fade" id="modalNovoOrcamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="orcModalTitulo">Novo orcamento</span>
                </h5>
                <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form id="formOrcamento" method="post" action="<?= ($base ?? '') ?>/orcamentos/criar">
                <div class="modal-body">
                    <input type="hidden" name="id" id="orcId">

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">ID orcamento</label>
                            <div class="form-control-plaintext fw-semibold">
                                <span class="text-primary me-1"><i class="ri-hashtag"></i></span>
                                <span id="orcDisplayId">Novo</span>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label">Cliente</label>
                            <select class="form-control" name="cliente_id" id="clienteSelect" required>
                                <option value="">Selecionar cliente...</option>
                                <?php if (!empty($clientesTodos)): ?>
                                    <?php foreach ($clientesTodos as $c): ?>
                                        <option value="<?= (int) $c['id']; ?>">
                                            <?= htmlspecialchars($c['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="m-0">Servico</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de servico</label>
                                    <select class="form-control" name="servico_principal" id="servicoPrincipal" required>
                                        <option value="">Selecionar...</option>
                                        <option value="landing_page">Landing Page</option>
                                        <option value="configuracao">Configuracao</option>
                                        <option value="stream_overlay">Stream Overlay</option>
                                        <option value="criativos">Criativos</option>
                                        <option value="identidade_visual">Identidade Visual</option>
                                    </select>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">Descricao do servico</label>
                                    <textarea class="form-control" name="descricao_servico" id="descricaoServico" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Pagamento</label>
                            <select class="form-control" name="forma_pagamento" id="formaPagamento">
                                <option value="Pix">Pix</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="statusOrcamento">
                                <option value="Enviado">Enviado</option>
                                <option value="Aceito">Aceito</option>
                                <option value="Sem Resposta">Sem Resposta</option>
                                <option value="Recusado">Recusado</option>
                            </select>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="m-0">Itens do orcamento</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAdicionarItem">
                                <i class="ri-add-line me-1"></i>Adicionar item
                            </button>
                        </div>
                        <div class="card-body" id="itensContainer">
                            <!-- itens serao inseridos via JS -->
                        </div>
                    </div>

                    <div class="card border-success">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <h6 class="m-0">Valor total</h6>
                            <h4 class="m-0 text-success">
                                R$<span id="totalOrcamento">0,00</span>
                            </h4>
                            <input type="hidden" name="valor_total" id="valorTotalInput">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarOrcamento">Salvar orcamento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalFiltroOrcamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="get">
                <div class="modal-header">
                    <h5 class="modal-title">Filtrar orcamentos</h5>
                    <button type="button" class="btn btn-close-custom" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="mod" value="orcamentos">

                    <p class="small text-muted mb-2">Selecione os status que deseja visualizar:</p>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="status_orcamento[]" value="Enviado" id="st_enviado" <?= in_array('Enviado', $status_orcamento, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="st_enviado">Enviado</label>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="status_orcamento[]" value="Aprovado" id="st_aprovado" <?= in_array('Aprovado', $status_orcamento, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="st_aprovado">Aprovado</label>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="status_orcamento[]" value="Recusado" id="st_recusado" <?= in_array('Recusado', $status_orcamento, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="st_recusado">Recusado</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar filtros</button>
                </div>
            </form>
        </div>
    </div>
</div>

