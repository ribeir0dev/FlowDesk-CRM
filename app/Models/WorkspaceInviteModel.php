<?php

require_once __DIR__ . '/../../config/db.php';

class WorkspaceInviteModel
{
    private PDO $pdo;
    private int $workspaceId;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaceId = fd_current_workspace_id() ?? 0;
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para convites.');
        }

        return $this->workspaceId;
    }

    public function listarPendentes(): array
    {
        $sql = "
            SELECT wi.*, u.nome AS invited_by_nome
            FROM workspace_invites wi
            INNER JOIN usuarios u ON u.id = wi.invited_by_user_id
            WHERE wi.workspace_id = :workspace_id
              AND wi.status = 'pending'
            ORDER BY wi.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function workspaceHasMemberEmail(string $email): bool
    {
        $sql = "
            SELECT 1
            FROM workspace_members wm
            INNER JOIN usuarios u ON u.id = wm.user_id
            WHERE wm.workspace_id = :workspace_id
              AND LOWER(u.email) = LOWER(:email)
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':email' => trim($email),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function hasPendingInvite(string $email): bool
    {
        $sql = "
            SELECT 1
            FROM workspace_invites
            WHERE workspace_id = :workspace_id
              AND LOWER(email) = LOWER(:email)
              AND status = 'pending'
              AND expires_at >= NOW()
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':email' => trim($email),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function criarInvite(string $email, string $role, int $invitedByUserId): ?array
    {
        if ($this->workspaceHasMemberEmail($email) || $this->hasPendingInvite($email)) {
            return null;
        }

        $token = gerarTokenPublico(64);
        $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO workspace_invites (workspace_id, invited_by_user_id, email, role, token, status, expires_at)
            VALUES (:workspace_id, :invited_by_user_id, :email, :role, :token, 'pending', :expires_at)
        ");

        $ok = $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':invited_by_user_id' => $invitedByUserId,
            ':email' => trim($email),
            ':role' => $role,
            ':token' => $token,
            ':expires_at' => $expiresAt,
        ]);

        if (!$ok) {
            return null;
        }

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function revogarInvite(int $inviteId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE workspace_invites
            SET status = 'revoked', updated_at = NOW()
            WHERE id = :id
              AND workspace_id = :workspace_id
              AND status = 'pending'
        ");

        return $stmt->execute([
            ':id' => $inviteId,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function buscarPorToken(string $token): ?array
    {
        $sql = "
            SELECT
                wi.*,
                w.nome AS workspace_nome,
                u.nome AS invited_by_nome
            FROM workspace_invites wi
            INNER JOIN workspaces w ON w.id = wi.workspace_id
            INNER JOIN usuarios u ON u.id = wi.invited_by_user_id
            WHERE wi.token = :token
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => trim($token),
        ]);

        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        return $invite ?: null;
    }

    public function aceitarInvite(string $token, int $userId, string $email): ?array
    {
        $invite = $this->buscarPorToken($token);
        if (!$invite) {
            return null;
        }

        if (($invite['status'] ?? '') !== 'pending') {
            return null;
        }

        if (strtotime((string) $invite['expires_at']) < time()) {
            $stmtExpire = $this->pdo->prepare("
                UPDATE workspace_invites
                SET status = 'expired', updated_at = NOW()
                WHERE id = :id
            ");
            $stmtExpire->execute([':id' => (int) $invite['id']]);
            return null;
        }

        if (mb_strtolower(trim($invite['email'])) !== mb_strtolower(trim($email))) {
            return null;
        }

        $this->pdo->beginTransaction();

        try {
            $stmtExists = $this->pdo->prepare("
                SELECT 1
                FROM workspace_members
                WHERE workspace_id = :workspace_id
                  AND user_id = :user_id
                LIMIT 1
            ");
            $stmtExists->execute([
                ':workspace_id' => (int) $invite['workspace_id'],
                ':user_id' => $userId,
            ]);

            if (!$stmtExists->fetchColumn()) {
                $stmtMember = $this->pdo->prepare("
                    INSERT INTO workspace_members (workspace_id, user_id, role, is_primary)
                    VALUES (:workspace_id, :user_id, :role, 0)
                ");
                $stmtMember->execute([
                    ':workspace_id' => (int) $invite['workspace_id'],
                    ':user_id' => $userId,
                    ':role' => $invite['role'],
                ]);
            }

            $stmtInvite = $this->pdo->prepare("
                UPDATE workspace_invites
                SET status = 'accepted', accepted_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmtInvite->execute([
                ':id' => (int) $invite['id'],
            ]);

            $this->pdo->commit();
            return [
                'workspace_id' => (int) $invite['workspace_id'],
                'workspace_nome' => $invite['workspace_nome'] ?? null,
                'role' => $invite['role'] ?? 'viewer',
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return null;
        }
    }
}
