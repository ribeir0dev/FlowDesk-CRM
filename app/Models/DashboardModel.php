<?php
// app/Models/DashboardModel.php

class DashboardModel
{
    private int $workspaceId;

    public function __construct(private PDO $pdo)
    {
        $this->workspaceId = fd_current_workspace_id() ?? 0;
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para dashboard.');
        }

        return $this->workspaceId;
    }

    public function contextoWorkspace(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT nome,
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

        return $workspace ?: [];
    }

    public function contagensOperacionais(): array
    {
        $workspaceId = $this->currentWorkspaceId();

        return [
            'clientes' => $this->countByWorkspace('clientes', $workspaceId),
            'oportunidades' => $this->countByWorkspace('oportunidades', $workspaceId),
            'projetos' => $this->countByWorkspace('projetos', $workspaceId),
            'tarefas' => $this->countByWorkspace('projeto_tarefas', $workspaceId),
            'hospedagens' => $this->countByWorkspace('hospedagens', $workspaceId),
            'financeiro_entradas' => $this->countByWorkspace('financeiro_entradas', $workspaceId),
            'financeiro_saidas' => $this->countByWorkspace('financeiro_saidas', $workspaceId),
        ];
    }

    private function countByWorkspace(string $table, int $workspaceId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE workspace_id = ?");
        $stmt->execute([$workspaceId]);
        return (int) $stmt->fetchColumn();
    }

    public function totaisFinanceiroMes(int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim    = date('Y-m-t', strtotime($inicio));

        $fin = new FinanceiroModel($this->pdo);

        $totalEntradas = $fin->totalEntradasMes($inicio, $fim);
        $totalSaidas   = $fin->totalSaidasMes($inicio, $fim);

        return [$totalEntradas, $totalSaidas];
    }

    public function novosClientesMes(int $ano, int $mes): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes WHERE workspace_id = ? AND YEAR(criado_em) = ? AND MONTH(criado_em) = ?");
        $stmt->execute([$this->currentWorkspaceId(), $ano, $mes]);
        return (int) $stmt->fetchColumn();
    }

    public function tarefasPorStatus(string $statusFiltro): array
    {
        $where = "t.coluna IN ('backlog','andamento','revisao')";
        if ($statusFiltro === 'pendente') {
            $where = "t.coluna = 'backlog'";
        } elseif ($statusFiltro === 'andamento') {
            $where = "t.coluna = 'andamento'";
        } elseif ($statusFiltro === 'concluida') {
            $where = "t.coluna = 'concluido'";
        }

        $sql = "
          SELECT t.id, t.projeto_id, t.titulo, t.coluna, t.criado_em, t.data_entrega,
                 p.nome_projeto
          FROM projeto_tarefas t
          INNER JOIN projetos p ON p.id = t.projeto_id
          WHERE t.workspace_id = :workspace_id
            AND p.workspace_id = :workspace_id
            AND $where
          ORDER BY p.nome_projeto, t.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':workspace_id' => $this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hospedagensAtivas(DateTimeInterface $hoje): array
    {
        $hojeStr = $hoje->format('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT id, nome, tipo, data_inicio, data_fim FROM hospedagens WHERE workspace_id = ? AND data_fim >= ? ORDER BY data_fim ASC, nome ASC");
        $stmt->execute([$this->currentWorkspaceId(), $hojeStr]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function variacaoEntradasMes(int $ano, int $mes): ?float
    {
        $inicioAtual = sprintf('%04d-%02d-01', $ano, $mes);
        $fimAtual    = date('Y-m-t', strtotime($inicioAtual));

        $inicioAnterior = date('Y-m-01', strtotime('-1 month', strtotime($inicioAtual)));
        $fimAnterior    = date('Y-m-t', strtotime($inicioAnterior));

        $fin = new FinanceiroModel($this->pdo);

        $atual    = $fin->totalEntradasMes($inicioAtual, $fimAtual);
        $anterior = $fin->totalEntradasMes($inicioAnterior, $fimAnterior);

        if ($anterior <= 0) {
            return null;
        }

        return (($atual - $anterior) / $anterior) * 100.0;
    }

    public function variacaoSaidasMes(int $ano, int $mes): ?float
    {
        $inicioAtual = sprintf('%04d-%02d-01', $ano, $mes);
        $fimAtual    = date('Y-m-t', strtotime($inicioAtual));

        $inicioAnterior = date('Y-m-01', strtotime('-1 month', strtotime($inicioAtual)));
        $fimAnterior    = date('Y-m-t', strtotime($inicioAnterior));

        $fin = new FinanceiroModel($this->pdo);

        $atual    = $fin->totalSaidasMes($inicioAtual, $fimAtual);
        $anterior = $fin->totalSaidasMes($inicioAnterior, $fimAnterior);

        if ($anterior <= 0) {
            return null;
        }

        return (($atual - $anterior) / $anterior) * 100.0;
    }

    public function diferencaEntradasMes(int $ano, int $mes): ?float
    {
        $inicioAtual = sprintf('%04d-%02d-01', $ano, $mes);
        $fimAtual    = date('Y-m-t', strtotime($inicioAtual));

        $inicioAnterior = date('Y-m-01', strtotime('-1 month', strtotime($inicioAtual)));
        $fimAnterior    = date('Y-m-t', strtotime($inicioAnterior));

        $fin = new FinanceiroModel($this->pdo);

        $atual    = $fin->totalEntradasMes($inicioAtual, $fimAtual);
        $anterior = $fin->totalEntradasMes($inicioAnterior, $fimAnterior);

        if ($atual === 0.0 && $anterior === 0.0) {
            return null;
        }

        return $atual - $anterior;
    }

    public function diferencaSaidasMes(int $ano, int $mes): ?float
    {
        $inicioAtual = sprintf('%04d-%02d-01', $ano, $mes);
        $fimAtual    = date('Y-m-t', strtotime($inicioAtual));

        $inicioAnterior = date('Y-m-01', strtotime('-1 month', strtotime($inicioAtual)));
        $fimAnterior    = date('Y-m-t', strtotime($inicioAnterior));

        $fin = new FinanceiroModel($this->pdo);

        $atual    = $fin->totalSaidasMes($inicioAtual, $fimAtual);
        $anterior = $fin->totalSaidasMes($inicioAnterior, $fimAnterior);

        if ($atual === 0.0 && $anterior === 0.0) {
            return null;
        }

        return $atual - $anterior;
    }
}
