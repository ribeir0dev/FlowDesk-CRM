<?php

class GoogleDriveStorageModel
{
    private PDO $pdo;
    private int $workspaceId;

    public function __construct(PDO $pdo, ?int $workspaceId = null)
    {
        $this->pdo = $pdo;
        $this->workspaceId = $workspaceId ?? (fd_current_workspace_id() ?? 0);
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para Google Drive.');
        }

        return $this->workspaceId;
    }

    public function settings(): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
              FROM workspace_google_drive_settings
             WHERE workspace_id = ?
             LIMIT 1
        ');
        $stmt->execute([$this->currentWorkspaceId()]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        return $settings ?: null;
    }

    public function upsertSettings(array $data): bool
    {
        $sql = '
            INSERT INTO workspace_google_drive_settings
                (workspace_id, status, root_folder_id, root_folder_name, access_token_encrypted,
                 refresh_token_encrypted, token_expires_at, scope, connected_by_user_id,
                 connected_at, last_error)
            VALUES
                (:workspace_id, :status, :root_folder_id, :root_folder_name, :access_token_encrypted,
                 :refresh_token_encrypted, :token_expires_at, :scope, :connected_by_user_id,
                 :connected_at, :last_error)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                root_folder_id = VALUES(root_folder_id),
                root_folder_name = VALUES(root_folder_name),
                access_token_encrypted = VALUES(access_token_encrypted),
                refresh_token_encrypted = VALUES(refresh_token_encrypted),
                token_expires_at = VALUES(token_expires_at),
                scope = VALUES(scope),
                connected_by_user_id = VALUES(connected_by_user_id),
                connected_at = VALUES(connected_at),
                last_error = VALUES(last_error)
        ';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':status' => $data['status'] ?? 'disconnected',
            ':root_folder_id' => $data['root_folder_id'] ?? null,
            ':root_folder_name' => $data['root_folder_name'] ?? (getenv('GOOGLE_DRIVE_ROOT_FOLDER_NAME') ?: 'FlowDesk'),
            ':access_token_encrypted' => $data['access_token_encrypted'] ?? null,
            ':refresh_token_encrypted' => $data['refresh_token_encrypted'] ?? null,
            ':token_expires_at' => $data['token_expires_at'] ?? null,
            ':scope' => $data['scope'] ?? (getenv('GOOGLE_DRIVE_SCOPES') ?: 'https://www.googleapis.com/auth/drive.file'),
            ':connected_by_user_id' => $data['connected_by_user_id'] ?? null,
            ':connected_at' => $data['connected_at'] ?? null,
            ':last_error' => $data['last_error'] ?? null,
        ]);
    }

    public function disconnect(?string $lastError = null): bool
    {
        return $this->upsertSettings([
            'status' => $lastError ? 'error' : 'disconnected',
            'root_folder_id' => null,
            'access_token_encrypted' => null,
            'refresh_token_encrypted' => null,
            'token_expires_at' => null,
            'connected_by_user_id' => null,
            'connected_at' => null,
            'last_error' => $lastError,
        ]);
    }

    public function clientFolder(int $clienteId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
              FROM cliente_drive_folders
             WHERE workspace_id = ?
               AND cliente_id = ?
             LIMIT 1
        ');
        $stmt->execute([$this->currentWorkspaceId(), $clienteId]);
        $folder = $stmt->fetch(PDO::FETCH_ASSOC);

        return $folder ?: null;
    }

    public function saveClientFolder(int $clienteId, string $driveFolderId, string $driveFolderName): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO cliente_drive_folders
                (workspace_id, cliente_id, drive_folder_id, drive_folder_name)
            VALUES
                (:workspace_id, :cliente_id, :drive_folder_id, :drive_folder_name)
            ON DUPLICATE KEY UPDATE
                drive_folder_id = VALUES(drive_folder_id),
                drive_folder_name = VALUES(drive_folder_name)
        ');

        return $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':cliente_id' => $clienteId,
            ':drive_folder_id' => $driveFolderId,
            ':drive_folder_name' => $driveFolderName,
        ]);
    }

    public function registerClientFile(int $clienteId, array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO cliente_arquivos
                (workspace_id, cliente_id, user_id, provider, tipo, nome_original, mime_type,
                 tamanho_bytes, local_path, drive_file_id, drive_folder_id, web_view_link, web_content_link)
            VALUES
                (:workspace_id, :cliente_id, :user_id, :provider, :tipo, :nome_original, :mime_type,
                 :tamanho_bytes, :local_path, :drive_file_id, :drive_folder_id, :web_view_link, :web_content_link)
        ');
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':cliente_id' => $clienteId,
            ':user_id' => $data['user_id'] ?? null,
            ':provider' => $data['provider'] ?? 'google_drive',
            ':tipo' => $data['tipo'] ?? 'arquivo',
            ':nome_original' => $data['nome_original'] ?? 'arquivo',
            ':mime_type' => $data['mime_type'] ?? null,
            ':tamanho_bytes' => (int) ($data['tamanho_bytes'] ?? 0),
            ':local_path' => $data['local_path'] ?? null,
            ':drive_file_id' => $data['drive_file_id'] ?? null,
            ':drive_folder_id' => $data['drive_folder_id'] ?? null,
            ':web_view_link' => $data['web_view_link'] ?? null,
            ':web_content_link' => $data['web_content_link'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function clientFiles(int $clienteId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
              FROM cliente_arquivos
             WHERE workspace_id = ?
               AND cliente_id = ?
             ORDER BY criado_em DESC, id DESC
        ');
        $stmt->execute([$this->currentWorkspaceId(), $clienteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
