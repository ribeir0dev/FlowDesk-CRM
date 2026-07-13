<?php

class NotificationModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function criar(array $data): int
    {
        $workspaceId = (int) ($data['workspace_id'] ?? 0);
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $tipo = trim((string) ($data['tipo'] ?? 'info'));

        if ($workspaceId <= 0 || $titulo === '') {
            return 0;
        }

        $payload = $data['payload'] ?? null;
        $payloadJson = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : ($payload !== null ? (string) $payload : null);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO notificacoes
                    (workspace_id, user_id, tipo, titulo, mensagem, entidade, entidade_id, url, payload)
                VALUES
                    (:workspace_id, :user_id, :tipo, :titulo, :mensagem, :entidade, :entidade_id, :url, :payload)
            ');
            $stmt->execute([
                ':workspace_id' => $workspaceId,
                ':user_id' => isset($data['user_id']) && (int) $data['user_id'] > 0 ? (int) $data['user_id'] : null,
                ':tipo' => mb_substr($tipo, 0, 80),
                ':titulo' => mb_substr($titulo, 0, 160),
                ':mensagem' => isset($data['mensagem']) ? trim((string) $data['mensagem']) : null,
                ':entidade' => isset($data['entidade']) ? mb_substr(trim((string) $data['entidade']), 0, 80) : null,
                ':entidade_id' => isset($data['entidade_id']) && (int) $data['entidade_id'] > 0 ? (int) $data['entidade_id'] : null,
                ':url' => isset($data['url']) ? mb_substr(trim((string) $data['url']), 0, 255) : null,
                ':payload' => $payloadJson,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log('[FlowDesk][Notification] ' . $e->getMessage());
            return 0;
        }
    }

    public function listarRecentes(int $workspaceId, ?int $userId = null, int $limit = 8): array
    {
        if ($workspaceId <= 0) {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $params = [$workspaceId];
        $whereUser = '';
        if ($userId !== null && $userId > 0) {
            $whereUser = ' AND (user_id IS NULL OR user_id = ?)';
            $params[] = $userId;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, tipo, titulo, mensagem, entidade, entidade_id, url, lida_em, criada_em
                FROM notificacoes
                WHERE workspace_id = ?{$whereUser}
                ORDER BY id DESC
                LIMIT {$limit}
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    public function contarNaoLidas(int $workspaceId, ?int $userId = null): int
    {
        if ($workspaceId <= 0) {
            return 0;
        }

        $params = [$workspaceId];
        $whereUser = '';
        if ($userId !== null && $userId > 0) {
            $whereUser = ' AND (user_id IS NULL OR user_id = ?)';
            $params[] = $userId;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM notificacoes
                WHERE workspace_id = ?{$whereUser}
                  AND lida_em IS NULL
            ");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function marcarLidas(int $workspaceId, ?int $userId = null): void
    {
        if ($workspaceId <= 0) {
            return;
        }

        $params = [$workspaceId];
        $whereUser = '';
        if ($userId !== null && $userId > 0) {
            $whereUser = ' AND (user_id IS NULL OR user_id = ?)';
            $params[] = $userId;
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE notificacoes
                SET lida_em = COALESCE(lida_em, NOW())
                WHERE workspace_id = ?{$whereUser}
                  AND lida_em IS NULL
            ");
            $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('[FlowDesk][Notification][Read] ' . $e->getMessage());
        }
    }
}
