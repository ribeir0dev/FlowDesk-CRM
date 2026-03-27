<?php

require_once __DIR__ . '/../../config/db.php';

class WorkspaceModel
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
            throw new RuntimeException('Workspace atual nao definido.');
        }

        return $this->workspaceId;
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
}
