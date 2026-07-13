<?php
// app/Controllers/FinanceiroController.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/FinanceiroModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_REQUEST['acao'] ?? '') === 'documento_publico')) {
    $shortCode = trim((string) ($_GET['codigo'] ?? ''));
    $payload = $shortCode !== ''
        ? fd_financeiro_public_document_payload($shortCode)
        : fd_financeiro_documento_payload(trim((string) ($_GET['token'] ?? '')));
    if (!$payload) {
        http_response_code(404);
        echo 'Documento nao encontrado ou expirado.';
        exit;
    }

    $model = new FinanceiroModel($pdo, (int) $payload['workspace_id']);
    renderDocumentoFinanceiro($model, $payload, true);
    exit;
}

$model = new FinanceiroModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_REQUEST['acao'] ?? '') === 'buscar_entrada')) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([]);
        exit;
    }

    $entrada = $model->buscarEntrada($id);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($entrada ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_REQUEST['acao'] ?? '') === 'buscar_saida')) {
    $id = (int)($_GET['id'] ?? 0);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($id > 0 ? ($model->buscarSaida($id) ?: []) : []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_REQUEST['acao'] ?? '') === 'documento')) {
    renderDocumentoFinanceiro($model);
    exit;
}



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectFinanceiro();
}

$acao = $_POST['acao'] ?? '';

switch ($acao) {
    case 'adicionar_entrada':
        criarEntrada($model);
        break;

    case 'salvar_entrada':
        salvarEntrada($model);
        break;

    case 'registrar_pagamento_entrada':
        registrarPagamentoEntrada($model);
        break;

    case 'excluir_entrada':
        excluirEntrada($model);
        break;

    case 'adicionar_saida':
        criarSaida($model);
        break;

    case 'salvar_saida':
        salvarSaida($model);
        break;

    case 'registrar_pagamento_saida':
        registrarPagamentoSaida($model);
        break;

    case 'excluir_saida':
        excluirSaida($model);
        break;

    case 'criar_categoria':
        criarCategoriaFinanceira($model);
        break;

    case 'adicionar_fixo':
        criarFixo($model);
        break;

    case 'marcar_fixo_pago':
        marcarFixoPago($model);
        break;

    case 'pagar_fixo':
        pagarFixo($model);
        break;

    case 'remover_fixo':
        removerFixo($model);
        break;

    default:
        redirectFinanceiro();
}




/* ------- Funções ------- */

function criarEntrada(FinanceiroModel $model): void
{
    if ($model->criarEntrada($_POST)) {
        fd_audit_log('financeiro.entrada.create', 'financeiro_entrada', null, [
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'valor_a_receber' => (float) ($_POST['valor_a_receber'] ?? 0),
            'valor_recebido' => (float) ($_POST['valor_recebido'] ?? 0),
        ]);
        redirectFinanceiro('ok=1');
    }
    redirectFinanceiro('erro=1');
}

function excluirEntrada(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $model->excluirEntrada($id)) {
        fd_audit_log('financeiro.entrada.delete', 'financeiro_entrada', $id);
        redirectFinanceiro('ok=1');
    }
    redirectFinanceiro('erro=1');
}

function criarSaida(FinanceiroModel $model): void
{
    if ($model->criarSaida($_POST)) {
        fd_audit_log('financeiro.saida.create', 'financeiro_saida', null, [
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'valor' => (float) ($_POST['valor'] ?? 0),
        ]);
        redirectFinanceiro('ok_saida=1');
    }
    redirectFinanceiro('erro_saida=1');
}

function excluirSaida(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $model->excluirSaida($id)) {
        fd_audit_log('financeiro.saida.delete', 'financeiro_saida', $id);
        redirectFinanceiro('ok_saida=1');
    }
    redirectFinanceiro('erro_saida=1');
}

function salvarSaida(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $model->atualizarSaida($id, $_POST)) {
        fd_audit_log('financeiro.saida.update', 'financeiro_saida', $id, [
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'valor' => (float) ($_POST['valor'] ?? 0),
        ]);
        redirectFinanceiro('ok_saida=1');
    }
    redirectFinanceiro('erro_saida=1');
}

function registrarPagamentoEntrada(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $valor = (float) str_replace(',', '.', str_replace('.', '', (string) ($_POST['valor_pago'] ?? '0')));
    if ($id > 0 && $model->registrarPagamentoEntrada($id, $valor)) {
        fd_audit_log('financeiro.entrada.payment', 'financeiro_entrada', $id, ['valor' => $valor]);
        redirectFinanceiro('ok=1&aba=receber');
    }
    redirectFinanceiro('erro=1&aba=receber');
}

function registrarPagamentoSaida(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $valor = (float) str_replace(',', '.', str_replace('.', '', (string) ($_POST['valor_pago'] ?? '0')));
    if ($id > 0 && $model->registrarPagamentoSaida($id, $valor)) {
        fd_audit_log('financeiro.saida.payment', 'financeiro_saida', $id, ['valor' => $valor]);
        redirectFinanceiro('ok_saida=1&aba=pagar');
    }
    redirectFinanceiro('erro_saida=1&aba=pagar');
}

function criarCategoriaFinanceira(FinanceiroModel $model): void
{
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $cor = trim((string) ($_POST['cor'] ?? '#5690D9'));
    if ($model->criarCategoria($nome, $cor)) {
        fd_audit_log('financeiro.categoria.create', 'financeiro_categoria', null, ['nome' => $nome, 'cor' => $cor]);
        redirectFinanceiro('ok=1');
    }
    redirectFinanceiro('erro=1');
}

function criarFixo(FinanceiroModel $model): void
{
    if ($model->criarFixo($_POST)) {
        fd_audit_log('financeiro.fixo.create', 'financeiro_fixo', null, [
            'tipo_gasto' => trim((string) ($_POST['tipo_gasto'] ?? '')),
            'valor' => (float) ($_POST['valor'] ?? 0),
        ]);
        redirectFinanceiro('ok_fixo=1');
    }
    redirectFinanceiro('erro_fixo=1');
}

function marcarFixoPago(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $model->marcarFixoPagoMes($id)) {
        fd_audit_log('financeiro.fixo.mark_paid', 'financeiro_fixo', $id);
        redirectFinanceiro('ok_fixo_pago=1');
    } else {
        redirectFinanceiro('erro_fixo=1');
    }
}

function pagarFixo(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirectFinanceiro('erro_fixo=1');
    }

    $fixo = $model->buscarFixoAtivo($id);
    if (!$fixo) {
        redirectFinanceiro('erro_fixo=1');
    }

    $valor = (float) $fixo['valor'];

    // cria saída
    if (!$model->criarSaidaParaFixo($fixo, $valor)) {
        redirectFinanceiro('erro_fixo=1');
    }

    // controla parcelas
    if ((int) $fixo['eh_parcelado'] === 1) {
        $restantes = (int) $fixo['parcelas_restantes'];
        if ($restantes > 0) {
            $novo = $restantes - 1;
            $model->atualizarParcelasRestantes($id, $novo);
        }
    }

    fd_audit_log('financeiro.fixo.pay', 'financeiro_fixo', $id, [
        'valor' => $valor,
    ]);

    redirectFinanceiro('ok_fixo_pago=1');
}

function removerFixo(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($model->desativarFixo($id)) {
            fd_audit_log('financeiro.fixo.remove', 'financeiro_fixo', $id);
            redirectFinanceiro('ok_fixo_removido=1');
        }
        redirectFinanceiro('erro_fixo=1');
    }
    redirectFinanceiro();
}

function redirectFinanceiro(string $query = ''): void
{
    $params = [];

    if ($query !== '') {
        parse_str($query, $params);
    }

    $aba = trim((string) ($_POST['aba'] ?? $_GET['aba'] ?? ''));
    $validTabs = ['visao', 'receber', 'pagar', 'clientes'];
    if (in_array($aba, $validTabs, true)) {
        $params['aba'] = $aba;
    }

    $url = fd_base_path() . '/financeiro';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url);
    exit;
}

function salvarEntrada(FinanceiroModel $model): void
{
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        // editar
        if ($model->atualizarEntrada($id, $_POST)) {
            fd_audit_log('financeiro.entrada.update', 'financeiro_entrada', $id, [
                'descricao' => trim((string) ($_POST['descricao'] ?? '')),
                'valor_a_receber' => (float) ($_POST['valor_a_receber'] ?? 0),
                'valor_recebido' => (float) ($_POST['valor_recebido'] ?? 0),
            ]);
            redirectFinanceiro('ok=1');
        }
        redirectFinanceiro('erro=1');
    } else {
        // fallback: se vier sem id, trata como criar
        if ($model->criarEntrada($_POST)) {
            fd_audit_log('financeiro.entrada.create', 'financeiro_entrada', null, [
                'descricao' => trim((string) ($_POST['descricao'] ?? '')),
                'valor_a_receber' => (float) ($_POST['valor_a_receber'] ?? 0),
                'valor_recebido' => (float) ($_POST['valor_recebido'] ?? 0),
            ]);
            redirectFinanceiro('ok=1');
        }
        redirectFinanceiro('erro=1');
    }
}

function renderDocumentoFinanceiro(FinanceiroModel $model, ?array $payload = null, bool $publico = false): void
{
    $source = $payload ?? $_GET;
    $tipo = (string) ($source['tipo'] ?? 'cobranca');
    $id = (int) ($source['id'] ?? 0);
    $clienteId = (int) ($source['cliente_id'] ?? 0);
    $titulo = in_array($tipo, ['fechamento_cliente', 'recibo_cliente'], true) ? 'Fechamento emitido' : 'Cobranca emitida';
    $linhas = [];
    $clienteNome = 'Cliente';
    $clienteTelefone = '';
    $formaPagamento = 'pix';
    $condicaoPagamento = 'Valor Integral';
    $prazoPagamento = date('Y-m-d');
    $pixConfig = $model->buscarPixManual();

    if ($tipo === 'cobranca' && $id > 0) {
        $entrada = $model->buscarEntrada($id);
        if ($entrada) {
            $clienteNome = (string) ($entrada['cliente_nome'] ?? 'Cliente');
            $clienteTelefone = (string) ($entrada['cliente_telefone'] ?? '');
            $formaPagamento = (string) ($entrada['forma_pagamento'] ?? 'pix');
            $condicaoPagamento = fd_financeiro_label_pagamento((string) ($entrada['tipo_pagamento'] ?? 'integral'));
            $prazoPagamento = (string) ($entrada['data_lancamento'] ?? date('Y-m-d'));
            $saldo = max(0, (float) $entrada['valor_a_receber'] - (float) $entrada['valor_recebido']);
            $linhas[] = [
                'descricao' => $entrada['descricao'],
                'vencimento' => $entrada['data_lancamento'] ?? null,
                'valor' => (float) $entrada['valor_a_receber'],
                'pago' => (float) $entrada['valor_recebido'],
                'saldo' => $saldo,
            ];
        }
    } elseif (in_array($tipo, ['fechamento_cliente', 'recibo_cliente'], true)) {
        $inicio = (string) ($source['inicio'] ?? date('Y-m-01'));
        $fim = (string) ($source['fim'] ?? date('Y-m-t'));
        foreach ($model->listarPendenciasCliente($clienteId, $inicio, $fim) as $row) {
            $clienteNome = (string) ($row['cliente_nome'] ?? $clienteNome);
            if ($clienteTelefone === '') {
                $clienteTelefone = (string) ($row['cliente_telefone'] ?? '');
            }
            if (!empty($row['forma_pagamento'])) {
                $formaPagamento = (string) $row['forma_pagamento'];
            }
            if (!empty($row['tipo_pagamento'])) {
                $condicaoPagamento = fd_financeiro_label_pagamento((string) $row['tipo_pagamento']);
            }
            if (!empty($row['data_lancamento'])) {
                $prazoPagamento = (string) $row['data_lancamento'];
            }
            $linhas[] = [
                'descricao' => $row['descricao'],
                'vencimento' => $row['data_lancamento'] ?? null,
                'valor' => (float) $row['valor_a_receber'],
                'pago' => (float) $row['valor_recebido'],
                'saldo' => (float) ($row['saldo_pendente'] ?? 0),
            ];
        }
    }

    $total = array_sum(array_map(static fn($l) => (float) ($l['saldo'] ?? 0), $linhas));
    $pixPayload = fd_financeiro_pix_payload($pixConfig, $total, $clienteNome);
    $qrSrc = $pixPayload !== '' ? 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=12&data=' . rawurlencode($pixPayload) : '';
    $badgeTitulo = 'FlowDesk - ' . (in_array($tipo, ['fechamento_cliente', 'recibo_cliente'], true) ? 'Fechamento Emitido' : 'Cobrança Emitida');

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($titulo) ?> - FlowDesk</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
            :root{color-scheme:light}
            *{box-sizing:border-box}
            html{background:#fff}
            body{font-family:Inter,Arial,Helvetica,sans-serif;background:#fff;color:#2b365c;margin:0;min-height:100vh;padding:86px 24px 72px;-webkit-text-size-adjust:100%;text-size-adjust:100%}
            .payment-page{width:min(100%,850px);margin:0 auto}
            .payment-badge{display:block;width:max-content;max-width:100%;margin:0 auto 48px;padding:10px 14px;border-radius:10px;background:#dff4ff;color:#0068ff;font-size:14px;font-weight:800;letter-spacing:-.02em}
            .payment-grid{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:24px;align-items:start}
            .left-stack{display:flex;flex-direction:column;align-items:center;gap:28px}
            .side-stack{display:flex;flex-direction:column;gap:26px}
            .doc-card{width:100%;background:#f6f8fb;border-radius:18px;padding:20px 20px 18px;color:#2b365c}
            .card-title{display:flex;align-items:center;gap:10px;margin:0 0 18px;font-size:17px;font-weight:500;color:#2b365c}
            .card-title svg{width:15px;height:15px;stroke:#2b365c;stroke-width:2;fill:none;flex:0 0 auto}
            .doc-table{display:grid;gap:8px}
            .doc-table-head,.doc-row{display:grid;grid-template-columns:minmax(0,1fr) 118px;align-items:center}
            .doc-table-head{padding:0 12px 8px;font-size:12px;text-transform:uppercase;font-weight:500;color:#2b365c}
            .doc-row{min-height:40px;border-radius:9px;background:#dff4ff;padding:0 12px;font-size:12px;color:#2b365c}
            .doc-row-value{text-align:right;white-space:nowrap}
            .empty{padding:16px;border:1px dashed #cdd8e7;border-radius:12px;color:#667390;font-size:13px}
            .total{margin:16px 12px 0;font-size:17px;font-weight:500;color:#2b365c}
            .total strong{color:#00aa4f;font-weight:500}
            .pix-section{display:flex;flex-direction:column;align-items:center;gap:8px}
            .qr{width:220px;height:220px;border:1px solid #d6e2f1;border-radius:19px;display:grid;place-items:center;color:#667390;text-align:center;padding:22px;background:#f9fbff}
            .qr img{width:172px;height:172px;display:block}
            .pix-code{width:220px;min-height:92px;height:auto;resize:none;border:1px solid #d6e2f1;border-radius:9px;background:#f9fbff;color:#64748b;padding:10px 11px;font:11px/1.35 Inter,Arial,sans-serif;overflow:hidden;word-break:break-all}
            .copy{width:220px;min-height:38px;border:1px solid #d6e2f1;border-radius:9px;background:#f9fbff;color:#1d294e;font-size:14px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer}
            .copy svg{width:15px;height:15px;stroke:currentColor;stroke-width:2;fill:none}
            .info-card{background:#f6f8fb;border-radius:18px;padding:20px 22px;color:#2b365c}
            .info-title{margin:0 0 18px;font-size:17px;font-weight:500;color:#2b365c}
            .info-list{display:grid;gap:18px}
            .info-item{display:grid;grid-template-columns:16px minmax(0,1fr);gap:8px;align-items:start}
            .info-item svg{width:14px;height:14px;margin-top:1px;stroke:#2b365c;stroke-width:2;fill:none}
            .info-label{display:block;font-size:12px;font-weight:500;line-height:1.15;color:#2b365c}
            .info-value{display:block;margin-top:1px;font-size:16px;font-weight:500;line-height:1.15;color:#2b365c;word-break:break-word}
            .toast{position:fixed;left:50%;bottom:28px;transform:translate(-50%,18px);background:#1d294e;color:#fff;border-radius:999px;padding:12px 18px;font-size:13px;font-weight:800;box-shadow:0 18px 50px rgba(16,24,40,.18);opacity:0;pointer-events:none;transition:opacity .2s ease,transform .2s ease;z-index:20}
            .toast.is-visible{opacity:1;transform:translate(-50%,0)}
            @media (max-width:820px){body{padding:80px 20px 72px;overflow-x:hidden}.payment-page{width:100%;max-width:390px}.payment-badge{margin-bottom:48px}.payment-grid{display:flex;flex-direction:column;gap:38px;align-items:stretch}.left-stack{width:100%;gap:36px}.side-stack{width:100%;gap:26px}.doc-card,.info-card{width:100%;box-sizing:border-box}.doc-card{padding:20px}.doc-row{min-height:40px}.qr{width:220px;height:220px}.qr img{width:172px;height:172px}.toast{width:calc(100vw - 32px);max-width:360px;text-align:center;border-radius:16px}}
        </style>
    </head>
    <body>
        <main class="payment-page">
            <strong class="payment-badge"><?= htmlspecialchars($badgeTitulo) ?></strong>

            <div class="payment-grid">
                <div class="left-stack">
                    <section class="doc-card" aria-label="Detalhes do fechamento">
                        <h1 class="card-title">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>
                            Detalhes do fechamento
                        </h1>

                        <?php if (empty($linhas)): ?>
                            <p class="empty">Nenhuma pendência encontrada para este cliente no período selecionado.</p>
                        <?php else: ?>
                            <div class="doc-table">
                                <div class="doc-table-head">
                                    <span>Serviço</span>
                                    <span class="doc-row-value">Valor</span>
                                </div>
                                <?php foreach ($linhas as $linha): ?>
                                    <div class="doc-row">
                                        <span><?= htmlspecialchars((string) $linha['descricao']) ?></span>
                                        <span class="doc-row-value">R$ <?= money((float) $linha['saldo']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="total">Total em Aberto: <strong>R$ <?= money($total) ?></strong></p>
                    </section>

                    <section class="pix-section" aria-label="Pagamento via Pix">
                        <div class="qr">
                            <?php if ($qrSrc !== ''): ?>
                                <img src="<?= htmlspecialchars($qrSrc) ?>" alt="QR Code Pix">
                            <?php else: ?>
                                Configure o Pix manual no workspace para gerar QR Code
                            <?php endif; ?>
                        </div>

                        <?php if ($pixPayload !== ''): ?>
                            <textarea class="pix-code" id="pixCode" readonly><?= htmlspecialchars($pixPayload) ?></textarea>
                            <button class="copy" id="copyPixButton" type="button" aria-label="Copiar codigo Pix">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h10v12H8z"/><path d="M6 16H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                Copiar
                            </button>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="side-stack">
                    <section class="info-card" aria-label="Informações do Cliente">
                        <h2 class="info-title">Informações do Cliente</h2>
                        <div class="info-list">
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>
                                    <span class="info-label">Nome:</span>
                                    <strong class="info-value"><?= htmlspecialchars($clienteNome) ?></strong>
                                </span>
                            </div>
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L9 10.59a16 16 0 0 0 4.41 4.41l1.15-1.15a2 2 0 0 1 2.11-.45c.84.27 1.71.47 2.61.59A2 2 0 0 1 22 16.92z"/></svg>
                                <span>
                                    <span class="info-label">Telefone:</span>
                                    <strong class="info-value"><?= htmlspecialchars($clienteTelefone !== '' ? $clienteTelefone : 'Nao informado') ?></strong>
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="info-card" aria-label="Condições">
                        <h2 class="info-title">Condições</h2>
                        <div class="info-list">
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v10H3z"/><path d="M3 10h18"/></svg>
                                <span>
                                    <span class="info-label">Forma de pagamento:</span>
                                    <strong class="info-value"><?= htmlspecialchars(fd_financeiro_label_forma_pagamento($formaPagamento)) ?></strong>
                                </span>
                            </div>
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>
                                <span>
                                    <span class="info-label">Condições de Pagamento:</span>
                                    <strong class="info-value"><?= htmlspecialchars($condicaoPagamento) ?></strong>
                                </span>
                            </div>
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <span>
                                    <span class="info-label">Prazo de Pagamento:</span>
                                    <strong class="info-value"><?= htmlspecialchars(fd_format_date($prazoPagamento)) ?></strong>
                                </span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <div class="toast" id="copyPixToast" role="status" aria-live="polite">Codigo Pix copiado com sucesso.</div>
        </main>
        <script>
            (function () {
                const button = document.getElementById('copyPixButton');
                const field = document.getElementById('pixCode');
                const toast = document.getElementById('copyPixToast');
                if (!button || !field || !toast) return;
                field.style.height = 'auto';
                field.style.height = field.scrollHeight + 'px';

                const showToast = function () {
                    toast.classList.add('is-visible');
                    button.classList.add('is-copied');
                    window.setTimeout(function () {
                        toast.classList.remove('is-visible');
                        button.classList.remove('is-copied');
                    }, 2200);
                };

                button.addEventListener('click', async function () {
                    const value = (field.value || field.innerText || '').trim();

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(value);
                        } else {
                            field.select();
                            field.setSelectionRange(0, value.length);
                            document.execCommand('copy');
                        }
                        showToast();
                    } catch (error) {
                        field.select();
                        field.setSelectionRange(0, value.length);
                        document.execCommand('copy');
                        showToast();
                    }
                });
            })();
        </script>
    </body>
    </html>
    <?php
}

function fd_financeiro_label_pagamento(string $value): string
{
    return match (strtolower(trim($value))) {
        'parcelado' => 'Parcelado',
        'recorrente' => 'Recorrente',
        default => 'Valor Integral',
    };
}

function fd_financeiro_label_forma_pagamento(string $value): string
{
    $value = strtolower(trim($value));

    return match ($value) {
        'cartao', 'cartao_credito', 'cartão', 'cartão de crédito' => 'Cartão',
        'boleto' => 'Boleto',
        'transferencia', 'transferência' => 'Transferência',
        'dinheiro' => 'Dinheiro',
        default => 'pix',
    };
}

function fd_financeiro_pix_payload(array $config, float $amount, string $txidSeed = 'FLOWDESK'): string
{
    $key = substr(trim((string) ($config['pix_chave'] ?? '')), 0, 77);
    if ($key === '' || $amount <= 0) {
        return '';
    }

    $merchantName = fd_financeiro_pix_text((string) ($config['pix_nome'] ?? 'FLOWDESK'), 25);
    $merchantCity = fd_financeiro_pix_text((string) ($config['pix_cidade'] ?? 'SAO PAULO'), 15);
    $txid = fd_financeiro_pix_text('FD' . substr(sha1($txidSeed . microtime(true)), 0, 12), 25);

    $merchantAccount = fd_financeiro_emv('00', 'br.gov.bcb.pix') . fd_financeiro_emv('01', $key);
    $payload = fd_financeiro_emv('00', '01')
        . fd_financeiro_emv('26', $merchantAccount)
        . fd_financeiro_emv('52', '0000')
        . fd_financeiro_emv('53', '986')
        . fd_financeiro_emv('54', number_format($amount, 2, '.', ''))
        . fd_financeiro_emv('58', 'BR')
        . fd_financeiro_emv('59', $merchantName)
        . fd_financeiro_emv('60', $merchantCity)
        . fd_financeiro_emv('62', fd_financeiro_emv('05', $txid));

    $payloadForCrc = $payload . '6304';

    return $payloadForCrc . fd_financeiro_crc16($payloadForCrc);
}

function fd_financeiro_emv(string $id, string $value): string
{
    return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function fd_financeiro_pix_text(string $value, int $max): string
{
    $value = strtoupper(trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value));
    $value = preg_replace('/[^A-Z0-9 .-]/', '', $value) ?: 'FLOWDESK';

    return substr($value, 0, $max);
}

function fd_financeiro_crc16(string $payload): string
{
    $crc = 0xFFFF;
    $length = strlen($payload);

    for ($offset = 0; $offset < $length; $offset++) {
        $crc ^= ord($payload[$offset]) << 8;
        for ($bit = 0; $bit < 8; $bit++) {
            if (($crc & 0x8000) !== 0) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }

    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}
