<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/Models/HospedagemModel.php';

$model = new HospedagemModel($pdo);
$hospedagens = $model->listarTodas();

$mapIcones = [
    'wordpress' => ['icon' => 'ri-wordpress-fill', 'label' => 'WordPress', 'color' => '#81BEF0'],
    'vps' => ['icon' => 'ri-cloud-line', 'label' => 'VPS', 'color' => '#F0AC81'],
    'dominio' => ['icon' => 'ri-global-line', 'label' => 'Dominio', 'color' => '#C481F0'],
];

$hospedagemMensagens = [];
$canManageHospedagens = fd_has_any_role(['owner', 'admin', 'financeiro']);
$canDeleteHospedagens = fd_has_any_role(['owner', 'admin']);
if (isset($_GET['ok'])) {
    $hospedagemMensagens[] = ['type' => 'success', 'text' => 'Hospedagem salva com sucesso.'];
}
if (isset($_GET['excluida'])) {
    $hospedagemMensagens[] = ['type' => 'success', 'text' => 'Hospedagem excluida com sucesso.'];
}
if (isset($_GET['erro'])) {
    $hospedagemMensagens[] = ['type' => 'danger', 'text' => 'Nao foi possivel concluir a acao em hospedagens.'];
}
?>

<div class="fd-hospedagens">
    <section class="fd-page-header">
        <div>
            <p class="fd-page-eyebrow">Infra e renovacoes</p>
            <p class="fd-page-subtitle">Acompanhe dominios, VPS e hospedagens WordPress com datas de inicio e termino dentro do mesmo padrao do painel.</p>
        </div>

        <?php if ($canManageHospedagens): ?>
            <div class="fd-page-actions">
                <button type="button" class="fd-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaHospedagem">
                    <i class="ri-add-line"></i>
                    <span>Nova hospedagem</span>
                </button>
            </div>
        <?php endif; ?>
    </section>

    <?php foreach ($hospedagemMensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['type']) ?> mb-3" role="alert">
            <?= e($mensagem['text']) ?>
        </div>
    <?php endforeach; ?>

    <section class="fd-card fd-hospedagens-card">
        <div class="fd-card-header">
            <div>
                <p class="fd-card-title">
                    <span class="fd-section-icon">
                        <i class="ri-server-line"></i>
                    </span>
                    Lista de hospedagens
                </p>
                <p class="fd-card-subtitle">Visual unico para infraestrutura e vencimentos da operacao.</p>
            </div>
        </div>

        <?php if (empty($hospedagens)): ?>
            <p class="fd-empty-copy">Nenhuma hospedagem cadastrada.</p>
        <?php else: ?>
            <div class="fd-table-wrap">
                <table class="fd-table fd-hospedagens-table">
                    <thead>
                        <tr>
                            <th>Hospedagem</th>
                            <th>Tipo</th>
                            <th>Inicio</th>
                            <th>Termino</th>
                            <th class="fd-text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hospedagens as $h): ?>
                            <?php
                            $info = $mapIcones[$h['tipo']] ?? $mapIcones['dominio'];
                            $inicio = fd_format_date($h['data_inicio']);
                            $fim = fd_format_date($h['data_fim']);
                            ?>
                            <tr>
                                <td>
                                    <div class="fd-list-main fd-hospedagens-name-cell">
                                        <span class="fd-list-icon" style="color: <?= htmlspecialchars($info['color']) ?>; background: <?= htmlspecialchars($info['color']) ?>20;">
                                            <i class="<?= htmlspecialchars($info['icon']) ?>"></i>
                                        </span>
                                        <div class="fd-hospedagens-name-copy">
                                            <strong class="fd-list-name"><?= htmlspecialchars($h['nome']) ?></strong>
                                            <span class="fd-list-meta fd-hospedagens-mobile-meta"><?= htmlspecialchars($info['label']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($info['label']) ?></td>
                                <td><?= htmlspecialchars($inicio) ?></td>
                                <td><?= htmlspecialchars($fim) ?></td>
                                <td class="fd-text-right">
                                    <?php if ($canDeleteHospedagens): ?>
                                        <form method="post" action="<?= ($base ?? '') ?>/hospedagens/excluir" class="fd-inline-form js-confirm-delete" data-confirm-msg="Deseja mesmo excluir esta hospedagem?">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="hospedagem_id" value="<?= (int) $h['id'] ?>">
                                            <button type="submit" class="fd-btn-table fd-btn-table-danger" aria-label="Excluir hospedagem">
                                                <i class="ri-delete-bin-fill"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="fd-text-muted">Sem acao</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/partials/modal_hospedagem.php'; ?>
