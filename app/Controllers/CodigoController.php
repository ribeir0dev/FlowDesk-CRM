<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Models/BillingModel.php';
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
    case 'atualizar':
        atualizarCodigo($model);
        break;
    case 'criar':
    default:
        criarCodigo($model);
        break;
}

function criarCodigo(CodigoModel $model): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 160);
    $categoria = mb_substr(trim((string) ($_POST['categoria'] ?? '')), 0, 120);
    $conteudo = trim((string) ($_POST['conteudo'] ?? ''));

    if ($userId <= 0 || $titulo === '' || $categoria === '' || !codigoConteudoValido($conteudo)) {
        header('Location: ' . fd_base_path() . '/codigos?erro=1');
        exit;
    }

    try {
        $previewImage = handleCodigoPreviewUpload();
        $ok = $model->criar([
            'user_id' => $userId,
            'titulo' => $titulo,
            'descricao' => mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 1000),
            'categoria' => $categoria,
            'tipo' => mb_substr(trim((string) ($_POST['tipo'] ?? 'Snippet')), 0, 60),
            'dificuldade' => normalizarDificuldadeCodigo((string) ($_POST['dificuldade'] ?? 'basico')),
            'instrucoes' => mb_substr(trim((string) ($_POST['instrucoes'] ?? '')), 0, 8000),
            'conteudo' => $conteudo,
            'preview_image' => $previewImage,
        ]);
    } catch (\Throwable $e) {
        error_log('[FlowDesk][Codigo] ' . $e->getMessage());
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

function handleCodigoPreviewUpload(?string $replacingPreviewImage = null): ?string
{
    if (empty($_FILES['preview_image']['name'])) {
        return null;
    }

    $file = $_FILES['preview_image'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nao foi possivel enviar a imagem de preview.');
    }

    $maxBytes = 8 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('A imagem de preview deve ter no maximo 8MB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Upload de imagem invalido.');
    }

    $imageInfo = @getimagesize($tmpName);
    $allowedMimeTypes = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
    ];
    $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    $originalExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];

    if (!isset($allowedMimeTypes[$mime]) || !in_array($originalExtension, $allowedExtensions, true)) {
        throw new RuntimeException('Use uma imagem PNG, JPEG ou GIF para o preview.');
    }

    $workspaceId = (int) (fd_current_workspace_id() ?? 0);
    $billingModel = null;
    if ($workspaceId > 0) {
        $billingModel = new BillingModel($GLOBALS['pdo']);
        if (!$billingModel->acquireWorkspaceBillingLock($workspaceId)) {
            throw new RuntimeException('Nao foi possivel validar o limite de armazenamento agora.');
        }

        $replacingBytes = $replacingPreviewImage !== null
            ? $billingModel->getLocalUploadSize($replacingPreviewImage)
            : 0;
        $gate = $billingModel->getStorageGate($workspaceId, (int) ($file['size'] ?? 0), $replacingBytes);
        if (!$gate['allowed']) {
            $billingModel->releaseWorkspaceBillingLock($workspaceId);
            throw new RuntimeException((string) $gate['message']);
        }
    }

    $uploadDir = __DIR__ . '/../../public/uploads/codigos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = 'codigo_preview_' . bin2hex(random_bytes(12)) . '.' . $allowedMimeTypes[$mime];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        if ($billingModel instanceof BillingModel) {
            $billingModel->releaseWorkspaceBillingLock($workspaceId);
        }
        throw new RuntimeException('Nao foi possivel salvar a imagem de preview.');
    }

    if ($billingModel instanceof BillingModel) {
        $billingModel->releaseWorkspaceBillingLock($workspaceId);
    }

    return '/uploads/codigos/' . $filename;
}

function atualizarCodigo(CodigoModel $model): void
{
    $id = (int) ($_POST['codigo_id'] ?? 0);
    $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 160);
    $categoria = mb_substr(trim((string) ($_POST['categoria'] ?? '')), 0, 120);
    $conteudo = trim((string) ($_POST['conteudo'] ?? ''));

    if ($id <= 0 || $titulo === '' || $categoria === '' || !codigoConteudoValido($conteudo)) {
        header('Location: ' . fd_base_path() . '/codigo?id=' . $id . '&erro=1');
        exit;
    }

    try {
        $codigoAtual = $model->buscarPorId($id);
        if (!$codigoAtual) {
            header('Location: ' . fd_base_path() . '/codigos?erro=1');
            exit;
        }

        $previewImage = handleCodigoPreviewUpload((string) ($codigoAtual['preview_image'] ?? ''));
        $dados = [
            'titulo' => $titulo,
            'descricao' => mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 1000),
            'categoria' => $categoria,
            'tipo' => mb_substr(trim((string) ($_POST['tipo'] ?? 'Snippet')), 0, 60),
            'dificuldade' => normalizarDificuldadeCodigo((string) ($_POST['dificuldade'] ?? 'basico')),
            'instrucoes' => mb_substr(trim((string) ($_POST['instrucoes'] ?? '')), 0, 8000),
            'conteudo' => $conteudo,
        ];

        if ($previewImage !== null) {
            $dados['preview_image'] = $previewImage;
        }

        $ok = $model->atualizar($id, $dados);
        if ($ok && $previewImage !== null) {
            removerCodigoPreviewArquivo((string) ($codigoAtual['preview_image'] ?? ''));
        }
    } catch (\Throwable $e) {
        error_log('[FlowDesk][Codigo] ' . $e->getMessage());
        $ok = false;
    }

    if ($ok) {
        fd_audit_log('codigo.update', 'codigo', $id, [
            'titulo' => $titulo,
            'categoria' => $categoria,
        ]);
    }

    header('Location: ' . fd_base_path() . '/codigo?id=' . $id . ($ok ? '&updated=1' : '&erro=1'));
    exit;
}

function favoritarCodigo(CodigoModel $model): void
{
    $id = (int) ($_POST['codigo_id'] ?? 0);
    try {
        $ok = $id > 0 ? $model->alternarFavorito($id) : false;
    } catch (\Throwable $e) {
        error_log('[FlowDesk][Codigo] ' . $e->getMessage());
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
        error_log('[FlowDesk][Codigo] ' . $e->getMessage());
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
        $codigo = $id > 0 ? $model->buscarPorId($id) : null;
        $ok = $id > 0 ? $model->excluir($id) : false;
        if ($ok && $codigo) {
            removerCodigoPreviewArquivo((string) ($codigo['preview_image'] ?? ''));
        }
    } catch (\Throwable $e) {
        error_log('[FlowDesk][Codigo] ' . $e->getMessage());
        $ok = false;
    }

    if ($ok) {
        fd_audit_log('codigo.delete', 'codigo', $id);
    }

    header('Location: ' . fd_base_path() . '/codigos?' . ($ok ? 'deleted=1' : 'erro=1'));
    exit;
}

function codigoConteudoValido(string $conteudo): bool
{
    $maxBytes = 1024 * 1024;
    return $conteudo !== '' && strlen($conteudo) <= $maxBytes;
}

function normalizarDificuldadeCodigo(string $dificuldade): string
{
    $dificuldade = trim($dificuldade);
    return in_array($dificuldade, ['basico', 'intermediario', 'avancado'], true) ? $dificuldade : 'basico';
}

function removerCodigoPreviewArquivo(string $previewImage): void
{
    if ($previewImage === '' || filter_var($previewImage, FILTER_VALIDATE_URL)) {
        return;
    }

    $relativePath = '/' . ltrim($previewImage, '/');
    if (!str_starts_with($relativePath, '/uploads/codigos/')) {
        return;
    }

    $filePath = realpath(__DIR__ . '/../../public' . $relativePath);
    $uploadsDir = realpath(__DIR__ . '/../../public/uploads/codigos');
    if (!$filePath || !$uploadsDir || !str_starts_with($filePath, $uploadsDir)) {
        return;
    }

    if (is_file($filePath)) {
        @unlink($filePath);
    }
}
