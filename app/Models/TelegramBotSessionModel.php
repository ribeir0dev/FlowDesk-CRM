<?php

class TelegramBotSessionModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function get(int $telegramUserId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM telegram_bot_sessions
            WHERE telegram_user_id = ?
            LIMIT 1
        ');
        $stmt->execute([$telegramUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'state' => 'idle',
                'payload' => [],
            ];
        }

        $payload = json_decode((string) ($row['payload'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $row['payload'] = $payload;
        return $row;
    }

    public function save(int $telegramUserId, int $workspaceId, int $appUserId, string $state, array $payload = []): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO telegram_bot_sessions (telegram_user_id, workspace_id, app_user_id, state, payload, atualizado_em)
            VALUES (:telegram_user_id, :workspace_id, :app_user_id, :state, :payload, NOW())
            ON DUPLICATE KEY UPDATE
                workspace_id = VALUES(workspace_id),
                app_user_id = VALUES(app_user_id),
                state = VALUES(state),
                payload = VALUES(payload),
                atualizado_em = NOW()
        ');

        return $stmt->execute([
            ':telegram_user_id' => $telegramUserId,
            ':workspace_id' => $workspaceId,
            ':app_user_id' => $appUserId,
            ':state' => $state,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function reset(int $telegramUserId, int $workspaceId, int $appUserId): bool
    {
        return $this->save($telegramUserId, $workspaceId, $appUserId, 'idle', []);
    }
}

