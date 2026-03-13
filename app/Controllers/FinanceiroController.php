<?php
// app/Controllers/FinanceiroController.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Models/FinanceiroModel.php';

$model = new FinanceiroModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'buscar_entrada') {
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

    case 'excluir_entrada':
        excluirEntrada($model);
        break;

    case 'adicionar_saida':
        criarSaida($model);
        break;

    case 'excluir_saida':
        excluirSaida($model);
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
        redirectFinanceiro('ok=1');
    }
    redirectFinanceiro('erro=1');
}

function excluirEntrada(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $model->excluirEntrada($id);
    }
    redirectFinanceiro();
}

function criarSaida(FinanceiroModel $model): void
{
    if ($model->criarSaida($_POST)) {
        redirectFinanceiro('ok_saida=1');
    }
    redirectFinanceiro('erro_saida=1');
}

function excluirSaida(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $model->excluirSaida($id);
    }
    redirectFinanceiro();
}

function criarFixo(FinanceiroModel $model): void
{
    if ($model->criarFixo($_POST)) {
        redirectFinanceiro('ok_fixo=1');
    }
    redirectFinanceiro('erro_fixo=1');
}

function marcarFixoPago(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $model->marcarFixoPagoMes($id)) {
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
    $model->criarSaidaParaFixo($fixo, $valor);

    // controla parcelas
    if ((int) $fixo['eh_parcelado'] === 1) {
        $restantes = (int) $fixo['parcelas_restantes'];
        if ($restantes > 0) {
            $novo = $restantes - 1;
            $model->atualizarParcelasRestantes($id, $novo);
        }
    }

    redirectFinanceiro('ok_fixo_pago=1');
}

function removerFixo(FinanceiroModel $model): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $model->desativarFixo($id);
        redirectFinanceiro('ok_fixo_removido=1');
    }
    redirectFinanceiro();
}

function redirectFinanceiro(string $query = ''): void
{
    $url = '/modules/painel.php?mod=financeiro';
    if ($query !== '') {
        $url .= '&' . $query;
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
            redirectFinanceiro('ok=1');
        }
        redirectFinanceiro('erro=1');
    } else {
        // fallback: se vier sem id, trata como criar
        if ($model->criarEntrada($_POST)) {
            redirectFinanceiro('ok=1');
        }
        redirectFinanceiro('erro=1');
    }
}
