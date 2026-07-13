<?php

require_once __DIR__ . '/../../config/db.php';

class WorkspaceModel
{
    private PDO $pdo;
    private int $workspaceId;
    private array $columnCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaceId = fd_current_workspace_id() ?? 0;
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido.');
        }

        return $this->workspaceId;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return $this->columnCache[$key] = ((int) $stmt->fetchColumn() > 0);
    }

    public function buscarAtual(): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id,
                   nome,
                   slug,
                   status,
                   segmento,
                   objetivo_principal,
                   onboarding_tamanho_equipe,
                   onboarding_volume_clientes,
                   onboarding_modulo_inicial,
                   onboarding_migrar_dados,
                   onboarding_concluido_em
            FROM workspaces
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$this->currentWorkspaceId()]);
        $workspace = $stmt->fetch(PDO::FETCH_ASSOC);

        return $workspace ?: null;
    }

    public function concluirOnboarding(
        string $nome,
        string $segmento,
        string $objetivoPrincipal,
        ?string $tamanhoEquipe = null,
        ?string $volumeClientes = null,
        ?string $moduloInicial = null,
        bool $migrarDados = false
    ): bool {
        $stmt = $this->pdo->prepare('
            UPDATE workspaces
            SET nome = ?,
                segmento = ?,
                objetivo_principal = ?,
                onboarding_tamanho_equipe = ?,
                onboarding_volume_clientes = ?,
                onboarding_modulo_inicial = ?,
                onboarding_migrar_dados = ?,
                onboarding_concluido_em = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([
            $nome,
            $segmento,
            $objetivoPrincipal,
            $tamanhoEquipe,
            $volumeClientes,
            $moduloInicial,
            $migrarDados ? 1 : 0,
            $this->currentWorkspaceId(),
        ]);
    }

    public function atualizarConfiguracoes(
        string $nome,
        string $segmento,
        string $objetivoPrincipal,
        ?string $tamanhoEquipe = null,
        ?string $volumeClientes = null,
        ?string $moduloInicial = null,
        bool $migrarDados = false
    ): bool {
        $stmt = $this->pdo->prepare('
            UPDATE workspaces
            SET nome = ?,
                segmento = ?,
                objetivo_principal = ?,
                onboarding_tamanho_equipe = ?,
                onboarding_volume_clientes = ?,
                onboarding_modulo_inicial = ?,
                onboarding_migrar_dados = ?,
                updated_at = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([
            $nome,
            $segmento,
            $objetivoPrincipal,
            $tamanhoEquipe,
            $volumeClientes,
            $moduloInicial,
            $migrarDados ? 1 : 0,
            $this->currentWorkspaceId(),
        ]);
    }

    public function buscarPixManual(): array
    {
        if (!$this->hasColumn('workspaces', 'pix_chave')) {
            return [
                'pix_chave' => '',
                'pix_nome' => '',
                'pix_cidade' => '',
            ];
        }

        $stmt = $this->pdo->prepare('
            SELECT pix_chave, pix_nome, pix_cidade
            FROM workspaces
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$this->currentWorkspaceId()]);

        $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'pix_chave' => (string) ($config['pix_chave'] ?? ''),
            'pix_nome' => (string) ($config['pix_nome'] ?? ''),
            'pix_cidade' => (string) ($config['pix_cidade'] ?? ''),
        ];
    }

    public function atualizarPixManual(string $chave, string $nome, string $cidade): bool
    {
        if (!$this->hasColumn('workspaces', 'pix_chave')) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE workspaces
            SET pix_chave = ?,
                pix_nome = ?,
                pix_cidade = ?,
                updated_at = NOW()
            WHERE id = ?
        ');

        return $stmt->execute([
            mb_substr(trim($chave), 0, 160),
            mb_substr(trim($nome), 0, 80),
            mb_substr(trim($cidade), 0, 60),
            $this->currentWorkspaceId(),
        ]);
    }
}
