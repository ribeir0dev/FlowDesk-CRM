<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    require __DIR__ . '/../../config/db.php';
}

require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Models/FinanceiroModel.php';
require_once __DIR__ . '/../Models/TelegramBotSessionModel.php';

handleTelegramWebhook($pdo);

function handleTelegramWebhook(PDO $pdo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo 'Method not allowed';
        exit;
    }

    $secret = trim((string) (getenv('TELEGRAM_WEBHOOK_SECRET') ?: ''));
    $token = trim((string) (getenv('TELEGRAM_BOT_TOKEN') ?: ''));
    $allowedTelegramId = (int) (getenv('TELEGRAM_ALLOWED_USER_ID') ?: 0);
    $workspaceId = (int) (getenv('TELEGRAM_WORKSPACE_ID') ?: 0);
    $appUserId = (int) (getenv('TELEGRAM_APP_USER_ID') ?: 0);

    if ($secret === '' || $token === '' || $allowedTelegramId <= 0 || $workspaceId <= 0 || $appUserId <= 0) {
        http_response_code(503);
        echo 'Telegram bot not configured';
        exit;
    }

    $headerSecret = trim((string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
    $querySecret = trim((string) ($_GET['secret'] ?? ''));
    if (!hash_equals($secret, $headerSecret) && !hash_equals($secret, $querySecret)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    $raw = file_get_contents('php://input') ?: '';
    $update = json_decode($raw, true);
    if (!is_array($update)) {
        http_response_code(400);
        echo 'Invalid payload';
        exit;
    }

    $callback = $update['callback_query'] ?? null;
    $message = $update['message'] ?? ($callback['message'] ?? null);
    $from = $callback['from'] ?? ($message['from'] ?? null);
    $chatId = (int) ($message['chat']['id'] ?? 0);
    $telegramUserId = (int) ($from['id'] ?? 0);
    $text = trim((string) ($message['text'] ?? ''));
    $callbackData = is_array($callback) ? trim((string) ($callback['data'] ?? '')) : '';

    if ($callbackData !== '' && !empty($callback['id'])) {
        telegramApi($token, 'answerCallbackQuery', ['callback_query_id' => $callback['id']]);
    }

    if ($chatId <= 0 || $telegramUserId <= 0) {
        http_response_code(200);
        echo 'OK';
        exit;
    }

    if ($telegramUserId !== $allowedTelegramId) {
        telegramSend($token, $chatId, 'Este bot e privado e nao esta autorizado para este usuario.');
        http_response_code(200);
        echo 'OK';
        exit;
    }

    $_SESSION['user_id'] = $appUserId;
    $_SESSION['current_workspace_id'] = $workspaceId;
    $_SESSION['current_workspace_role'] = 'financeiro';
    $_SESSION['auth_started_at'] = $_SESSION['auth_started_at'] ?? time();
    $_SESSION['auth_last_activity_at'] = time();
    $_SESSION['auth_last_regenerated_at'] = $_SESSION['auth_last_regenerated_at'] ?? time();

    $sessionModel = new TelegramBotSessionModel($pdo);
    $session = $sessionModel->get($telegramUserId);

    if ($text === '/start' || $text === '/cancelar' || $callbackData === 'cancel') {
        $sessionModel->reset($telegramUserId, $workspaceId, $appUserId);
        telegramShowStart($token, $chatId);
        http_response_code(200);
        echo 'OK';
        exit;
    }

    if ($callbackData !== '') {
        handleTelegramCallback($token, $chatId, $telegramUserId, $workspaceId, $appUserId, $callbackData, $sessionModel, $pdo);
    } else {
        handleTelegramText($token, $chatId, $telegramUserId, $workspaceId, $appUserId, $text, $session, $sessionModel, $pdo);
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

function handleTelegramCallback(
    string $token,
    int $chatId,
    int $telegramUserId,
    int $workspaceId,
    int $appUserId,
    string $callbackData,
    TelegramBotSessionModel $sessionModel,
    PDO $pdo
): void {
    $session = $sessionModel->get($telegramUserId);
    $payload = $session['payload'] ?? [];

    if ($callbackData === 'flow_saida') {
        $sessionModel->save($telegramUserId, $workspaceId, $appUserId, 'await_value', ['flow' => 'saida']);
        telegramSend($token, $chatId, "Perfeito. Qual foi o valor da saida?\n\nExemplo: 47,90");
        return;
    }

    if (str_starts_with($callbackData, 'type_')) {
        if (($session['state'] ?? '') !== 'await_type') {
            telegramSend($token, $chatId, 'Use /start para iniciar um novo lancamento.');
            return;
        }

        $type = substr($callbackData, 5);
        $allowed = telegramExpenseTypes();
        if (!isset($allowed[$type])) {
            telegramSend($token, $chatId, 'Tipo invalido. Escolha uma das opcoes.');
            telegramAskType($token, $chatId);
            return;
        }

        $payload['tipo'] = $type;
        $sessionModel->save($telegramUserId, $workspaceId, $appUserId, 'await_obs', $payload);
        telegramSend($token, $chatId, "Alguma observacao?\n\nDigite a observacao ou envie /pular.");
        return;
    }

    if ($callbackData === 'confirm_saida') {
        if (($session['state'] ?? '') !== 'await_confirm') {
            telegramSend($token, $chatId, 'Use /start para iniciar um novo lancamento.');
            return;
        }

        $ok = telegramCreateExpense($pdo, $payload);
        if ($ok) {
            $sessionModel->reset($telegramUserId, $workspaceId, $appUserId);
            telegramSend($token, $chatId, "Saida lancada com sucesso.\n\nUse /start para registrar outra.");
            return;
        }

        telegramSend($token, $chatId, 'Nao consegui salvar essa saida. Use /start e tente novamente.');
        return;
    }

    telegramShowStart($token, $chatId);
}

function handleTelegramText(
    string $token,
    int $chatId,
    int $telegramUserId,
    int $workspaceId,
    int $appUserId,
    string $text,
    array $session,
    TelegramBotSessionModel $sessionModel,
    PDO $pdo
): void {
    $state = (string) ($session['state'] ?? 'idle');
    $payload = $session['payload'] ?? [];

    if ($state === 'idle') {
        telegramShowStart($token, $chatId);
        return;
    }

    if ($state === 'await_value') {
        $value = telegramParseMoney($text);
        if ($value <= 0) {
            telegramSend($token, $chatId, 'Valor invalido. Envie algo como 47,90.');
            return;
        }

        $payload['valor'] = $value;
        $sessionModel->save($telegramUserId, $workspaceId, $appUserId, 'await_description', $payload);
        telegramSend($token, $chatId, 'Agora envie uma descricao curta para esse gasto.');
        return;
    }

    if ($state === 'await_description') {
        $description = mb_substr(trim($text), 0, 180);
        if ($description === '') {
            telegramSend($token, $chatId, 'A descricao nao pode ficar vazia.');
            return;
        }

        $payload['descricao'] = $description;
        $sessionModel->save($telegramUserId, $workspaceId, $appUserId, 'await_type', $payload);
        telegramAskType($token, $chatId);
        return;
    }

    if ($state === 'await_obs') {
        $payload['observacoes'] = in_array(mb_strtolower($text), ['/pular', 'pular', 'sem'], true)
            ? ''
            : mb_substr(trim($text), 0, 2000);
        $sessionModel->save($telegramUserId, $workspaceId, $appUserId, 'await_confirm', $payload);
        telegramAskConfirmation($token, $chatId, $payload);
        return;
    }

    telegramSend($token, $chatId, 'Nao entendi esse passo. Use /start para recomecar.');
}

function telegramCreateExpense(PDO $pdo, array $payload): bool
{
    $model = new FinanceiroModel($pdo);
    $ok = $model->criarSaida([
        'data_lancamento' => date('Y-m-d'),
        'descricao' => (string) ($payload['descricao'] ?? ''),
        'tipo' => (string) ($payload['tipo'] ?? 'outro'),
        'valor' => number_format((float) ($payload['valor'] ?? 0), 2, ',', '.'),
        'observacoes' => (string) ($payload['observacoes'] ?? ''),
    ]);

    if ($ok) {
        fd_audit_log('financeiro.telegram.saida.create', 'financeiro_saida', null, [
            'descricao' => (string) ($payload['descricao'] ?? ''),
            'tipo' => (string) ($payload['tipo'] ?? 'outro'),
            'valor' => (float) ($payload['valor'] ?? 0),
        ]);
    }

    return $ok;
}

function telegramShowStart(string $token, int $chatId): void
{
    telegramSend($token, $chatId, 'O que voce deseja lancar no financeiro?', [
        'inline_keyboard' => [
            [
                ['text' => 'Saida', 'callback_data' => 'flow_saida'],
            ],
        ],
    ]);
}

function telegramAskType(string $token, int $chatId): void
{
    $rows = [];
    foreach (telegramExpenseTypes() as $value => $label) {
        $rows[] = [['text' => $label, 'callback_data' => 'type_' . $value]];
    }
    telegramSend($token, $chatId, 'Selecione o tipo de gasto:', ['inline_keyboard' => $rows]);
}

function telegramAskConfirmation(string $token, int $chatId, array $payload): void
{
    $types = telegramExpenseTypes();
    $type = (string) ($payload['tipo'] ?? 'outro');
    $summary = "Confirma este lancamento?\n\n"
        . 'Valor: R$ ' . number_format((float) ($payload['valor'] ?? 0), 2, ',', '.') . "\n"
        . 'Descricao: ' . (string) ($payload['descricao'] ?? '-') . "\n"
        . 'Tipo: ' . ($types[$type] ?? 'Outro') . "\n"
        . 'Observacoes: ' . ((string) ($payload['observacoes'] ?? '') !== '' ? (string) $payload['observacoes'] : '-');

    telegramSend($token, $chatId, $summary, [
        'inline_keyboard' => [
            [
                ['text' => 'Confirmar', 'callback_data' => 'confirm_saida'],
                ['text' => 'Cancelar', 'callback_data' => 'cancel'],
            ],
        ],
    ]);
}

function telegramExpenseTypes(): array
{
    return [
        'mercado' => 'Mercado',
        'lanche' => 'Lanche',
        'almoco' => 'Almoco',
        'pagamentos' => 'Pagamentos',
        'retiradas' => 'Retiradas',
        'outro' => 'Outro',
    ];
}

function telegramParseMoney(string $value): float
{
    $value = trim(preg_replace('/[^\d,.\-]/', '', $value) ?? '');
    if ($value === '') {
        return 0.0;
    }

    if (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif (str_contains($value, ',')) {
        $value = str_replace(',', '.', $value);
    }

    return max(0, (float) $value);
}

function telegramSend(string $token, int $chatId, string $text, ?array $replyMarkup = null): void
{
    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
    ];

    if ($replyMarkup !== null) {
        $payload['reply_markup'] = $replyMarkup;
    }

    telegramApi($token, 'sendMessage', $payload);
}

function telegramApi(string $token, string $method, array $payload): void
{
    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 8,
        ],
    ]));
}

