<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}

require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/BillingModel.php';
require_once __DIR__ . '/../Models/FinanceiroModel.php';
require_once __DIR__ . '/../Models/OrcamentoModel.php';

$model = new OrcamentoModel($pdo);
$acao = $_REQUEST['acao'] ?? 'listar';

function normalizarStatusOrcamento(string $status): string
{
    $aliases = [
        'Aguardando' => 'Aguardando Aprovação',
        'Enviado' => 'Aguardando Aprovação',
        'Sem Resposta' => 'Aguardando Aprovação',
        'Aceito' => 'Aprovada',
        'Aprovado' => 'Aprovada',
        'Ativou' => 'Aprovada',
        'Recusado' => 'Recusada',
        'Vencido' => 'Vencida',
    ];
    $status = $aliases[trim($status)] ?? trim($status);
    return in_array($status, ['Rascunho', 'Aguardando Aprovação', 'Aprovada', 'Recusada', 'Vencida'], true)
        ? $status
        : 'Aguardando Aprovação';
}

function normalizarVencimentoOrcamento(string $value): string
{
    $value = trim($value);
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

function normalizarFormaPagamentoOrcamento(string $value): string
{
    $aliases = [
        'Ã€ Vista' => 'A Vista',
        'À Vista' => 'A Vista',
        'A vista' => 'A Vista',
        'avista' => 'A Vista',
    ];
    $value = $aliases[trim($value)] ?? trim($value);
    return in_array($value, ['A Vista', 'Parcelado', 'Recorrente', '50/50'], true)
        ? $value
        : 'A Vista';
}

function parseMoneyOrcamento(mixed $value): float
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0.0;
    }

    if (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (str_contains($value, ',')) {
        $value = str_replace(',', '.', $value);
    }

    return max(0, (float) preg_replace('/[^0-9.\-]/', '', $value));
}

function dadosOrcamentoRequest(): array
{
    $itens = is_array($_POST['itens'] ?? null) ? $_POST['itens'] : [];
    $subtotalBruto = 0.0;
    $valorTotal = 0.0;

    foreach ($itens as &$item) {
        $quantidade = max(0.01, (float) str_replace(',', '.', (string) ($item['quantidade'] ?? 1)));
        $valorUnitario = parseMoneyOrcamento($item['valor_unitario'] ?? $item['valor'] ?? 0);
        $desconto = max(0, min(100, (float) str_replace(',', '.', (string) ($item['desconto_percentual'] ?? 0))));
        $subtotalBruto += $quantidade * $valorUnitario;
        $valorTotal += $quantidade * $valorUnitario * (1 - $desconto / 100);
        $item['quantidade'] = $quantidade;
        $item['valor_unitario'] = $valorUnitario;
        $item['desconto_percentual'] = $desconto;
    }
    unset($item);

    $vencimento = normalizarVencimentoOrcamento((string) ($_POST['vencimento'] ?? ''));
    $status = normalizarStatusOrcamento((string) ($_POST['status'] ?? 'Aguardando Aprovação'));
    if (!in_array($status, ['Aprovada', 'Recusada'], true) && $vencimento !== '' && $vencimento < date('Y-m-d')) {
        $status = 'Vencida';
    }

    return [
        'cliente_id' => (int) ($_POST['cliente_id'] ?? 0),
        'codigo' => mb_substr(trim((string) ($_POST['codigo'] ?? '')), 0, 40),
        'data_emissao' => normalizarVencimentoOrcamento((string) ($_POST['data_emissao'] ?? '')),
        'vencimento' => $vencimento,
        'status' => $status,
        'servico_principal' => mb_substr(trim((string) ($_POST['servico_principal'] ?? '')), 0, 160),
        'descricao_servico' => mb_substr(trim((string) ($_POST['descricao_servico'] ?? '')), 0, 4000),
        'forma_pagamento' => normalizarFormaPagamentoOrcamento((string) ($_POST['forma_pagamento'] ?? 'A Vista')),
        'parcelas' => max(1, min(10, (int) ($_POST['parcelas'] ?? 1))),
        'prazo_estimado_dias' => max(1, min(365, (int) ($_POST['prazo_estimado_dias'] ?? 7))),
        'itens' => $itens,
        'valor_total' => round($valorTotal, 2),
        'desconto_total' => round(max(0, $subtotalBruto - $valorTotal), 2),
    ];
}

function validarDadosOrcamento(array $data): bool
{
    return $data['cliente_id'] > 0
        && $data['servico_principal'] !== ''
        && $data['data_emissao'] !== ''
        && $data['vencimento'] !== ''
        && $data['valor_total'] > 0
        && !empty($data['itens']);
}

if ($acao === 'buscar') {
    buscarOrcamento($model);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    match ($acao) {
        'criar' => criarOrcamento($model),
        'atualizar' => atualizarOrcamento($model),
        'duplicar' => duplicarOrcamento($model),
        'confirmar' => confirmarOrcamento($model),
        'excluir' => excluirOrcamento($model),
        default => listarOrcamentos(),
    };
}

listarOrcamentos();

function listarOrcamentos(): never
{
    header('Location: ' . fd_base_path() . '/orcamentos');
    exit;
}

function criarOrcamento(OrcamentoModel $model): never
{
    $data = dadosOrcamentoRequest();
    // Toda proposta nova entra no fluxo aguardando a decisao do cliente.
    $data['status'] = 'Aguardando Aprovação';
    if (!validarDadosOrcamento($data)) {
        header('Location: ' . fd_base_path() . '/orcamentos/novo?erro=validacao');
        exit;
    }

    $billingModel = new BillingModel($GLOBALS['pdo']);
    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
        header('Location: ' . fd_base_path() . '/orcamentos/novo?erro=1');
        exit;
    }

    try {
        $gate = $billingModel->getResourceGate($workspaceId, 'orcamentos');
        if (!$gate['allowed']) {
            header('Location: ' . fd_base_path() . '/configuracoes#pagamentos');
            exit;
        }

        $id = $model->criar(
            $data['cliente_id'],
            $data['servico_principal'],
            $data['descricao_servico'],
            $data['forma_pagamento'],
            $data['status'],
            $data['valor_total'],
            $data['vencimento'],
            $data['itens'],
            $data
        );
    } catch (Throwable $e) {
        error_log('[FlowDesk][Orcamento][Criar] ' . $e->getMessage());
        header('Location: ' . fd_base_path() . '/orcamentos/novo?erro=1');
        exit;
    } finally {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
    }

    fd_audit_log('orcamento.create', 'orcamento', $id, [
        'cliente_id' => $data['cliente_id'],
        'valor_total' => $data['valor_total'],
        'status' => $data['status'],
    ]);
    header('Location: ' . fd_base_path() . '/orcamentos?criado=1&selecionado=' . $id);
    exit;
}

function atualizarOrcamento(OrcamentoModel $model): never
{
    $id = (int) ($_POST['id'] ?? 0);
    $data = dadosOrcamentoRequest();
    if ($id <= 0 || !validarDadosOrcamento($data)) {
        header('Location: ' . fd_base_path() . '/orcamentos/editar/' . $id . '?erro=validacao');
        exit;
    }

    try {
        $ok = $model->atualizar(
            $id,
            $data['cliente_id'],
            $data['servico_principal'],
            $data['descricao_servico'],
            $data['forma_pagamento'],
            $data['status'],
            $data['valor_total'],
            $data['vencimento'],
            $data['itens'],
            $data
        );
    } catch (Throwable $e) {
        error_log('[FlowDesk][Orcamento][Atualizar] ' . $e->getMessage());
        $ok = false;
    }

    if (!$ok) {
        header('Location: ' . fd_base_path() . '/orcamentos/editar/' . $id . '?erro=1');
        exit;
    }

    fd_audit_log('orcamento.update', 'orcamento', $id, [
        'cliente_id' => $data['cliente_id'],
        'valor_total' => $data['valor_total'],
        'status' => $data['status'],
    ]);
    header('Location: ' . fd_base_path() . '/orcamentos?atualizado=1&selecionado=' . $id);
    exit;
}

function duplicarOrcamento(OrcamentoModel $model): never
{
    $id = (int) ($_POST['orcamento_id'] ?? 0);
    $billingModel = new BillingModel($GLOBALS['pdo']);
    $workspaceId = (int) (fd_current_workspace_id() ?? 0);

    if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
        header('Location: ' . fd_base_path() . '/orcamentos?erro=1');
        exit;
    }

    try {
        $gate = $billingModel->getResourceGate($workspaceId, 'orcamentos');
        if (!$gate['allowed']) {
            header('Location: ' . fd_base_path() . '/configuracoes#pagamentos');
            exit;
        }
        $newId = $id > 0 ? $model->duplicar($id) : 0;
    } catch (Throwable $e) {
        error_log('[FlowDesk][Orcamento][Duplicar] ' . $e->getMessage());
        $newId = 0;
    } finally {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
    }

    if ($newId <= 0) {
        header('Location: ' . fd_base_path() . '/orcamentos?erro=1');
        exit;
    }

    fd_audit_log('orcamento.duplicate', 'orcamento', $newId, ['origem_id' => $id]);
    header('Location: ' . fd_base_path() . '/orcamentos/editar/' . $newId . '?duplicado=1');
    exit;
}

function excluirOrcamento(OrcamentoModel $model): never
{
    $id = (int) ($_POST['orcamento_id'] ?? 0);
    if ($id > 0 && $model->excluir($id)) {
        fd_audit_log('orcamento.delete', 'orcamento', $id);
        header('Location: ' . fd_base_path() . '/orcamentos?excluido=1');
        exit;
    }

    header('Location: ' . fd_base_path() . '/orcamentos?erro=1');
    exit;
}

function confirmarOrcamento(OrcamentoModel $model): never
{
    $id = (int) ($_POST['orcamento_id'] ?? 0);
    $pdo = $GLOBALS['pdo'];

    try {
        $orcamento = $id > 0 ? $model->buscarPorId($id) : null;
        if (!$orcamento) {
            throw new RuntimeException('Proposta nao encontrada.');
        }

        $pagamentoMap = [
            'A Vista' => 'integral',
            '50/50' => 'parcial',
            'Parcelado' => 'parcelado',
            'Recorrente' => 'recorrente',
        ];
        $pdo->beginTransaction();
        $financeiroModel = new FinanceiroModel($pdo);
        $entrada = $financeiroModel->criarEntrada([
            'cliente_id' => (int) $orcamento['cliente_id'],
            'data_lancamento' => (string) ($orcamento['vencimento'] ?? date('Y-m-d')),
            'descricao' => (string) ($orcamento['descricao_servico'] ?: $orcamento['codigo']),
            'servico' => (string) $orcamento['servico_principal'],
            'tipo_pagamento' => $pagamentoMap[$orcamento['forma_pagamento']] ?? 'integral',
            'forma_pagamento' => (string) $orcamento['forma_pagamento'],
            'valor_a_receber' => number_format((float) $orcamento['valor_total'], 2, ',', '.'),
            'valor_recebido' => '0,00',
            'status_pagamento' => 'pendente',
            'observacoes' => 'Venda gerada automaticamente a partir da proposta ' . $orcamento['codigo'] . '.',
        ]);
        if (!$entrada || !$model->excluirSemTransacao($id)) {
            throw new RuntimeException('Nao foi possivel confirmar a proposta.');
        }
        $pdo->commit();

        fd_audit_log('orcamento.confirm', 'orcamento', $id, [
            'cliente_id' => (int) $orcamento['cliente_id'],
            'valor_total' => (float) $orcamento['valor_total'],
        ]);
        header('Location: ' . fd_base_path() . '/orcamentos?confirmado=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[FlowDesk][Orcamento][Confirmar] ' . $e->getMessage());
        header('Location: ' . fd_base_path() . '/orcamentos?erro=1');
        exit;
    }
}

function buscarOrcamento(OrcamentoModel $model): never
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $id = (int) ($_GET['id'] ?? 0);
        $orcamento = $id > 0 ? $model->buscarPorId($id) : null;
        if (!$orcamento) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Proposta nao encontrada.']);
            exit;
        }
        echo json_encode([
            'success' => true,
            'orcamento' => $orcamento,
            'itens' => $model->buscarItens($id),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Nao foi possivel carregar a proposta.']);
        exit;
    }
}
