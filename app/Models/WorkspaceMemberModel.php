<?php

require_once __DIR__ . '/../../config/db.php';

class WorkspaceMemberModel
{
    private PDO $pdo;
    private int $workspaceId;
    private const ALLOWED_ROLES = ['admin', 'financeiro', 'operacional', 'viewer'];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaceId = fd_current_workspace_id() ?? 0;
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para membros.');
        }

        return $this->workspaceId;
    }

    public function listarMembros(): array
    {
        $sql = "
            SELECT
                wm.id,
                wm.user_id,
                wm.role,
                wm.is_primary,
                wm.created_at,
                u.nome,
                u.email,
                u.foto_perfil
            FROM workspace_members wm
            INNER JOIN usuarios u ON u.id = wm.user_id
            WHERE wm.workspace_id = :workspace_id
            ORDER BY
                wm.is_primary DESC,
                CASE wm.role
                    WHEN 'owner' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'financeiro' THEN 3
                    WHEN 'operacional' THEN 4
                    WHEN 'viewer' THEN 5
                    ELSE 6
                END ASC,
                u.nome ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function contarMembros(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM workspace_members WHERE workspace_id = ?');
        $stmt->execute([$this->currentWorkspaceId()]);
        return (int) $stmt->fetchColumn();
    }

    public function buscarMembroPorId(int $memberId): ?array
    {
        $sql = "
            SELECT
                wm.id,
                wm.user_id,
                wm.role,
                wm.is_primary,
                u.nome,
                u.email
            FROM workspace_members wm
            INNER JOIN usuarios u ON u.id = wm.user_id
            WHERE wm.workspace_id = :workspace_id
              AND wm.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':id' => $memberId,
        ]);

        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        return $member ?: null;
    }

    public function atualizarPapel(int $memberId, string $newRole, int $actorUserId, string $actorRole): bool
    {
        $newRole = trim($newRole);
        if (!in_array($newRole, self::ALLOWED_ROLES, true)) {
            return false;
        }

        $member = $this->buscarMembroPorId($memberId);
        if (!$member) {
            return false;
        }

        if ((int) ($member['user_id'] ?? 0) === $actorUserId) {
            return false;
        }

        $currentRole = (string) ($member['role'] ?? 'viewer');

        if ($currentRole === 'owner') {
            return false;
        }

        if ($actorRole === 'admin' && in_array($currentRole, ['owner', 'admin'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE workspace_members
            SET role = :role
            WHERE id = :id
              AND workspace_id = :workspace_id
        ");

        return $stmt->execute([
            ':role' => $newRole,
            ':id' => $memberId,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function removerMembro(int $memberId, int $actorUserId, string $actorRole): bool
    {
        $member = $this->buscarMembroPorId($memberId);
        if (!$member) {
            return false;
        }

        if ((int) ($member['user_id'] ?? 0) === $actorUserId) {
            return false;
        }

        $currentRole = (string) ($member['role'] ?? 'viewer');

        if ($currentRole === 'owner') {
            return false;
        }

        if ($actorRole === 'admin' && in_array($currentRole, ['owner', 'admin'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM workspace_members
            WHERE id = :id
              AND workspace_id = :workspace_id
        ");

        return $stmt->execute([
            ':id' => $memberId,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }
}
