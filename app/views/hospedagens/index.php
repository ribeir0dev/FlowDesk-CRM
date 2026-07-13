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

$hojeHospedagem = new DateTimeImmutable('today');
$totalHospedagens = count($hospedagens);
$hospedagensAtivas = 0;
$renovacoesProximas = 0;
$wordpressAtivos = 0;
$dominiosAtivos = 0;
$proximosVencimentos = [];
foreach ($hospedagens as $hospedagemResumo) {
    $fimResumo = !empty($hospedagemResumo['data_fim']) ? new DateTimeImmutable($hospedagemResumo['data_fim']) : null;
    $diasResumo = $fimResumo ? (int) $hojeHospedagem->diff($fimResumo)->format('%r%a') : null;
    $estaAtiva = $diasResumo === null || $diasResumo >= 0;
    if ($estaAtiva) $hospedagensAtivas++;
    if ($estaAtiva && ($hospedagemResumo['tipo'] ?? '') === 'wordpress') $wordpressAtivos++;
    if ($estaAtiva && ($hospedagemResumo['tipo'] ?? '') === 'dominio') $dominiosAtivos++;
    if ($diasResumo !== null && $diasResumo >= 0 && $diasResumo <= 30) $renovacoesProximas++;
    if ($fimResumo) $proximosVencimentos[] = ['item' => $hospedagemResumo, 'dias' => $diasResumo];
}
usort($proximosVencimentos, static fn ($a, $b) => $a['dias'] <=> $b['dias']);
$proximosVencimentos = array_slice($proximosVencimentos, 0, 5);

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

    <section class="fd-hosting-reference">
        <div class="fd-hosting-kpis">
            <?php foreach ([
                ['Total de hospedagens', $totalHospedagens, 'ri-global-line', 'blue'],
                ['WordPress ativos', $wordpressAtivos, 'ri-wordpress-fill', 'cyan'],
                ['Dominios ativos', $dominiosAtivos, 'ri-earth-line', 'violet'],
                ['Renovacoes proximas', $renovacoesProximas, 'ri-calendar-event-line', 'orange'],
                ['Infraestruturas ativas', $hospedagensAtivas, 'ri-pulse-line', 'green'],
            ] as [$labelKpi, $valorKpi, $iconeKpi, $tomKpi]): ?>
                <article class="fd-hosting-kpi is-<?= $tomKpi ?>">
                    <span class="fd-reference-icon"><i class="<?= $iconeKpi ?>"></i></span>
                    <div><span><?= e($labelKpi) ?></span><strong><?= (int) $valorKpi ?></strong></div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="fd-hosting-reference-layout">
            <div class="fd-hosting-reference-main">
                <div class="fd-reference-toolbar">
                    <label class="fd-reference-search"><i class="ri-search-line"></i><input type="search" data-hosting-search placeholder="Buscar por nome, tipo ou dominio..."></label>
                    <select data-hosting-type><option value="">Tipo: Todos</option><option value="wordpress">WordPress</option><option value="dominio">Dominio</option><option value="vps">VPS</option></select>
                    <select data-hosting-status><option value="">Status: Todos</option><option value="ativa">Ativas</option><option value="atencao">Atencao</option></select>
                </div>

                <div class="fd-table-wrap">
                    <table class="fd-table fd-hosting-reference-table">
                        <thead><tr><th>Hospedagem</th><th>Tipo</th><th>Inicio</th><th>Vencimento</th><th>Status</th><th>Acoes</th></tr></thead>
                        <tbody>
                        <?php foreach ($hospedagens as $h):
                            $info = $mapIcones[$h['tipo']] ?? $mapIcones['dominio'];
                            $fimData = !empty($h['data_fim']) ? new DateTimeImmutable($h['data_fim']) : null;
                            $dias = $fimData ? (int) $hojeHospedagem->diff($fimData)->format('%r%a') : null;
                            $status = $dias !== null && $dias < 0 ? 'Expirada' : (($dias !== null && $dias <= 30) ? 'Atencao' : 'Ativa');
                            $statusFiltro = $status === 'Ativa' ? 'ativa' : 'atencao';
                        ?>
                            <tr data-hosting-row data-search-text="<?= e(mb_strtolower(($h['nome'] ?? '') . ' ' . ($h['tipo'] ?? ''))) ?>" data-type="<?= e($h['tipo'] ?? '') ?>" data-status="<?= $statusFiltro ?>">
                                <td><div class="fd-list-main"><span class="fd-list-icon" style="color:<?= e($info['color']) ?>;background:<?= e($info['color']) ?>20"><i class="<?= e($info['icon']) ?>"></i></span><div><strong><?= e($h['nome']) ?></strong><small><?= e($h['url_dominio'] ?? 'Infraestrutura do workspace') ?></small></div></div></td>
                                <td><span class="fd-badge fd-badge-info"><?= e($info['label']) ?></span></td>
                                <td><?= e(fd_format_date($h['data_inicio'])) ?></td>
                                <td><strong><?= e(fd_format_date($h['data_fim'])) ?></strong><?php if ($dias !== null): ?><small class="fd-table-subcopy <?= $dias <= 30 ? 'is-warning' : '' ?>"><?= $dias >= 0 ? 'em ' . $dias . ' dias' : abs($dias) . ' dias atras' ?></small><?php endif; ?></td>
                                <td><span class="fd-status-pill is-<?= $statusFiltro ?>"><?= e($status) ?></span></td>
                                <td><?php if ($canDeleteHospedagens): ?><form method="post" action="<?= ($base ?? '') ?>/hospedagens/excluir" class="fd-inline-form js-confirm-delete" data-confirm-msg="Deseja mesmo excluir esta hospedagem?"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="hospedagem_id" value="<?= (int) $h['id'] ?>"><button type="submit" class="fd-btn-table fd-btn-table-danger"><i class="ri-delete-bin-line"></i></button></form><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="fd-reference-sidebar">
                <section class="fd-reference-side-card"><div class="fd-card-header"><strong>Proximos vencimentos</strong><span>Ver todos</span></div><?php foreach ($proximosVencimentos as $vencimento): $itemV = $vencimento['item']; ?><div class="fd-reference-side-row"><span class="fd-list-icon"><i class="<?= e(($mapIcones[$itemV['tipo']] ?? $mapIcones['dominio'])['icon']) ?>"></i></span><div><strong><?= e($itemV['nome']) ?></strong><small><?= e(fd_format_date($itemV['data_fim'])) ?></small></div><b class="<?= $vencimento['dias'] <= 30 ? 'is-danger' : '' ?>"><?= $vencimento['dias'] ?> dias</b></div><?php endforeach; ?></section>
                <section class="fd-reference-side-card"><div class="fd-card-header"><strong>Saude da infraestrutura</strong></div><div class="fd-hosting-health"><div class="fd-health-ring" style="--health:<?= $totalHospedagens ? round(($hospedagensAtivas / $totalHospedagens) * 100) : 0 ?>"><strong><?= $totalHospedagens ?></strong><span>Total</span></div><div><p><i class="is-green"></i> Ativas <b><?= $hospedagensAtivas ?></b></p><p><i class="is-orange"></i> Atencao <b><?= $renovacoesProximas ?></b></p></div></div></section>
            </aside>
        </div>
    </section>

    <section class="fd-card fd-hospedagens-card fd-legacy-section">
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
