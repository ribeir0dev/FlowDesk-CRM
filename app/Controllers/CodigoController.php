<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/CodigoModel.php';

$model = new CodigoModel($pdo);
$acao = $_REQUEST['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . fd_base_path() . '/codigos');
    exit;
}

switch ($acao) {
    case 'favoritar':
        favoritarCodigo($model);
        break;
    case 'copiar':
        copiarCodigo($model);
        break;
    case 'excluir':
        excluirCodigo($model);
        break;
    case 'criar':
    default:
        criarCodigo($model);
        break;
}

function criarCodigo(CodigoModel $model): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $categoria = trim((string) ($_POST['categoria'] ?? ''));
    $conteudo = trim((string) ($_POST['conteudo'] ?? ''));

    if ($userId <= 0 || $titulo === '' || $categoria === '' || $conteudo === '') {
        header('Location: ' . fd_base_path() . '/codigos?erro=1');
        exit;
    }

    try {
        $ok = $model->criar([
            'user_id' => $userId,
            'titulo' => $titulo,
            'descricao' => trim((string) ($_POST['descricao'] ?? '')),
            'categoria' => $categoria,
            'tipo' => trim((string) ($_POST['tipo'] ?? 'Snippet')),
            'dificuldade' => trim((string) ($_POST['dificuldade'] ?? 'basico')),
            'instrucoes' => trim((string) ($_POST['instrucoes'] ?? '')),
            'conteudo' => $conteudo,
        ]);
    } catch (\Throwable $e) {
        $_SESSION['codigo_error_detail'] = $e->getMessage();
        $ok = false;
    }

    if ($ok) {
        fd_audit_log('codigo.create', 'codigo', null, [
            'titulo' => $titulo,
            'categoria' => $categoria,
        ]);
    }

    header('Location: ' . fd_base_path() . '/codigos?' . ($ok ? 'ok=1' : 'erro=1'));
    exit;
}

function favoritarCodigo(CodigoModel $model): void
{
    $id = (int) ($_POST['codigo_id'] ?? 0);
    try {
        $ok = $id > 0 ? $model->alternarFavorito($id) : false;
    } catch (\Throwable $e) {
        $_SESSION['codigo_error_detail'] = $e->getMessage();
        $ok = false;
    }
    header('Location: ' . fd_base_path() . '/codigos?' . ($ok ? 'favorite=ok' : 'erro=1'));
    exit;
}

function copiarCodigo(CodigoModel $model): void
{
    $id = (int) ($_POST['codigo_id'] ?? 0);
    try {
        $ok = $id > 0 ? $model->registrarCopia($id) : false;
    } catch (\Throwable $e) {
        $_SESSION['codigo_error_detail'] = $e->getMessage();
        $ok = false;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok]);
    exit;
}

function excluirCodigo(CodigoModel $model): void
{
    $id = (int) ($_POST['codigo_id'] ?? 0);
    try {
        $ok = $id > 0 ? $model->excluir($id) : false;
    } catch (\Throwable $e) {
        $_SESSION['codigo_error_detail'] = $e->getMessage();
        $ok = false;
    }

    if ($ok) {
        fd_audit_log('codigo.delete', 'codigo', $id);
    }

    header('Location: ' . fd_base_path() . '/codigos?' . ($ok ? 'deleted=1' : 'erro=1'));
    exit;
}
