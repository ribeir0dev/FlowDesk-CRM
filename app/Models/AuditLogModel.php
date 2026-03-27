<?php

class AuditLogModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function registrar(
        int $workspaceId,
        ?int $userId,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        ?array $payload = null
    ): bool {
        if ($workspaceId <= 0 || trim($acao) === '' || trim($entidade) === '') {
            return false;
        }

        $payloadJson = null;
        if ($payload !== null) {
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO audit_logs (workspace_id, user_id, acao, entidade, entidade_id, payload)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        return $stmt->execute([
            $workspaceId,
            $userId,
            trim($acao),
            trim($entidade),
            $entidadeId,
            $payloadJson,
        ]);
    }
}
