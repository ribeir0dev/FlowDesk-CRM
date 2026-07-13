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

    public function listarAtividadesRecentes(int $limit = 12): array
    {
        $workspaceId = $this->currentWorkspaceId();
        $limit = max(4, min(24, $limit));

        $sql = "
            SELECT * FROM (
                SELECT
                    'cliente.create' AS tipo,
                    c.id AS entidade_id,
                    'Novo Lead Capturado' AS titulo,
                    c.nome AS descricao,
                    c.criado_em AS data_evento,
                    'ri-user-add-line' AS icone
                FROM clientes c
                WHERE c.workspace_id = :workspace_id_clientes

                UNION ALL

                SELECT
                    'financeiro.saida.create' AS tipo,
                    s.id AS entidade_id,
                    'Despesa registrada' AS titulo,
                    CONCAT(s.descricao, ' - R$ ', REPLACE(REPLACE(REPLACE(FORMAT(s.valor, 2), ',', '#'), '.', ','), '#', '.')) AS descricao,
                    s.criado_em AS data_evento,
                    'ri-arrow-right-up-long-line' AS icone
                FROM financeiro_saidas s
                WHERE s.workspace_id = :workspace_id_saidas

                UNION ALL

                SELECT
                    'financeiro.entrada.payment' AS tipo,
                    e.id AS entidade_id,
                    'Pagamento Recebido' AS titulo,
                    CONCAT(e.descricao, ' - R$ ', REPLACE(REPLACE(REPLACE(FORMAT(e.valor_recebido, 2), ',', '#'), '.', ','), '#', '.')) AS descricao,
                    COALESCE(e.atualizado_em, e.criado_em) AS data_evento,
                    'ri-arrow-right-down-long-line' AS icone
                FROM financeiro_entradas e
                WHERE e.workspace_id = :workspace_id_entradas
                  AND e.valor_recebido > 0

                UNION ALL

                SELECT
                    'projeto.create' AS tipo,
                    p.id AS entidade_id,
                    'Novo Projeto' AS titulo,
                    CONCAT(p.nome_projeto, ' - ', COALESCE(c.nome, 'Sem cliente vinculado')) AS descricao,
                    p.criado_em AS data_evento,
                    'ri-folder-add-line' AS icone
                FROM projetos p
                LEFT JOIN clientes c ON c.id = p.cliente_id AND c.workspace_id = p.workspace_id
                WHERE p.workspace_id = :workspace_id_projetos

                UNION ALL

                SELECT
                    'orcamento.confirm' AS tipo,
                    a.entidade_id,
                    'Orcamento Aprovado' AS titulo,
                    CONCAT(
                        '#',
                        COALESCE(a.entidade_id, 0),
                        ' - R$ ',
                        REPLACE(REPLACE(REPLACE(FORMAT(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(a.payload, '$.valor_total')) AS DECIMAL(12,2)), 0), 2), ',', '#'), '.', ','), '#', '.')
                    ) AS descricao,
                    a.created_at AS data_evento,
                    'ri-file-list-3-line' AS icone
                FROM audit_logs a
                WHERE a.workspace_id = :workspace_id_audit
                  AND a.acao = 'orcamento.confirm'
            ) atividades
            WHERE data_evento IS NOT NULL
            ORDER BY data_evento DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id_clientes' => $workspaceId,
            ':workspace_id_saidas' => $workspaceId,
            ':workspace_id_entradas' => $workspaceId,
            ':workspace_id_projetos' => $workspaceId,
            ':workspace_id_audit' => $workspaceId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
