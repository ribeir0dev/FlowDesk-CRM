<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/WorkspaceModel.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}

$workspaceModel = new WorkspaceModel($pdo);
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {
    case 'salvar_onboarding':
        salvarOnboarding($workspaceModel);
        break;

    case 'atualizar_configuracoes':
        atualizarConfiguracoes($workspaceModel);
        break;

    case 'atualizar_pix_manual':
        atualizarPixManual($workspaceModel);
        break;

    default:
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
}

function atualizarPixManual(WorkspaceModel $workspaceModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes#integracoes');
        exit;
    }

    fd_require_role(['owner', 'admin']);

    $chave = trim((string) ($_POST['pix_chave'] ?? ''));
    $nome = trim((string) ($_POST['pix_nome'] ?? ''));
    $cidade = trim((string) ($_POST['pix_cidade'] ?? ''));

    $dadosInvalidos = ($chave !== '' && mb_strlen($chave) > 160)
        || ($nome !== '' && mb_strlen($nome) > 80)
        || ($cidade !== '' && mb_strlen($cidade) > 60);

    if ($dadosInvalidos) {
        header('Location: ' . fd_base_path() . '/configuracoes?pix=erro&integration=pix_manual#integracoes');
        exit;
    }

    $ok = $workspaceModel->atualizarPixManual($chave, $nome, $cidade);

    if ($ok) {
        fd_audit_log('workspace.pix_manual.update', 'workspace', fd_current_workspace_id(), [
            'has_pix_key' => $chave !== '',
            'pix_nome' => $nome,
            'pix_cidade' => $cidade,
        ]);
    }

    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'pix=ok' : 'pix=erro') . '&integration=pix_manual#integracoes');
    exit;
}

function salvarOnboarding(WorkspaceModel $workspaceModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/onboarding');
        exit;
    }

    fd_require_role(['owner', 'admin']);

    try {
        try {
        [$nome, $segmento, $objetivo, $tamanhoEquipe, $volumeClientes, $moduloInicial, $migrarDados] = validateWorkspacePayload();
    } catch (InvalidArgumentException $exception) {
        header('Location: ' . fd_base_path() . '/configuracoes?workspace=erro');
        exit;
    }
    } catch (InvalidArgumentException $exception) {
        header('Location: ' . fd_base_path() . '/onboarding?erro=1');
        exit;
    }

    $ok = $workspaceModel->concluirOnboarding(
        $nome,
        $segmento,
        $objetivo,
        $tamanhoEquipe,
        $volumeClientes,
        $moduloInicial,
        $migrarDados
    );

    if ($ok) {
        $_SESSION['current_workspace_nome'] = $nome;

        fd_audit_log('workspace.onboarding.complete', 'workspace', fd_current_workspace_id(), [
            'nome' => $nome,
            'segmento' => $segmento,
            'objetivo_principal' => $objetivo,
            'tamanho_equipe' => $tamanhoEquipe,
            'volume_clientes' => $volumeClientes,
            'modulo_inicial' => $moduloInicial,
            'migrar_dados' => $migrarDados,
        ]);
    }

    header('Location: ' . fd_base_path() . ($ok ? '/dashboard?onboarding=ok' : '/onboarding?erro=1'));
    exit;
}

function atualizarConfiguracoes(WorkspaceModel $workspaceModel): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . fd_base_path() . '/configuracoes');
        exit;
    }

    fd_require_role(['owner', 'admin']);

    [$nome, $segmento, $objetivo, $tamanhoEquipe, $volumeClientes, $moduloInicial, $migrarDados] = validateWorkspacePayload();

    $ok = $workspaceModel->atualizarConfiguracoes(
        $nome,
        $segmento,
        $objetivo,
        $tamanhoEquipe,
        $volumeClientes,
        $moduloInicial,
        $migrarDados
    );

    if ($ok) {
        $_SESSION['current_workspace_nome'] = $nome;

        fd_audit_log('workspace.settings.update', 'workspace', fd_current_workspace_id(), [
            'nome' => $nome,
            'segmento' => $segmento,
            'objetivo_principal' => $objetivo,
            'tamanho_equipe' => $tamanhoEquipe,
            'volume_clientes' => $volumeClientes,
            'modulo_inicial' => $moduloInicial,
            'migrar_dados' => $migrarDados,
        ]);
    }

    header('Location: ' . fd_base_path() . '/configuracoes?' . ($ok ? 'workspace=ok' : 'workspace=erro'));
    exit;
}

function validateWorkspacePayload(): array
{
    $nome = trim((string) ($_POST['workspace_nome'] ?? ''));
    $segmento = trim((string) ($_POST['segmento'] ?? ''));
    $objetivo = trim((string) ($_POST['objetivo_principal'] ?? ''));
    $tamanhoEquipe = normalizeOptionalValue($_POST['tamanho_equipe'] ?? null);
    $volumeClientes = normalizeOptionalValue($_POST['volume_clientes'] ?? null);
    $moduloInicial = normalizeOptionalValue($_POST['modulo_inicial'] ?? null);
    $migrarDados = (string) ($_POST['migrar_dados'] ?? '0') === '1';

    $segmentosValidos = ['freelancer', 'studio', 'agencia', 'consultoria'];
    $objetivosValidos = ['vender_mais', 'entregar_melhor', 'controlar_financas'];
    $equipesValidas = ['solo', '2_5', '6_10', '11_plus'];
    $volumesValidos = ['ate_10', '11_25', '26_50', '50_plus'];
    $modulosValidos = ['crm', 'pipeline', 'projetos', 'financeiro'];

    $dadosObrigatoriosInvalidos = $nome === ''
        || !in_array($segmento, $segmentosValidos, true)
        || !in_array($objetivo, $objetivosValidos, true);

    $dadosOpcionaisInvalidos = ($tamanhoEquipe !== null && !in_array($tamanhoEquipe, $equipesValidas, true))
        || ($volumeClientes !== null && !in_array($volumeClientes, $volumesValidos, true))
        || ($moduloInicial !== null && !in_array($moduloInicial, $modulosValidos, true));

    if ($dadosObrigatoriosInvalidos || $dadosOpcionaisInvalidos) {
        throw new InvalidArgumentException('Payload de workspace invalido.');
    }

    return [$nome, $segmento, $objetivo, $tamanhoEquipe, $volumeClientes, $moduloInicial, $migrarDados];
}

function normalizeOptionalValue(mixed $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}
