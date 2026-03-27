<?php
// app/Models/OportunidadeModel.php

require_once __DIR__ . '/../../config/db.php';

class OportunidadeModel
{
    private PDO $pdo;
    private int $workspaceId;
    private const DEFAULT_STAGES = [
        ['nome' => 'Lead', 'slug' => 'lead', 'ordem' => 1, 'cor_hex' => '#2563eb'],
        ['nome' => 'Proposta enviada', 'slug' => 'proposta_enviada', 'ordem' => 2, 'cor_hex' => '#0ea5e9'],
        ['nome' => 'Fechado (ganho)', 'slug' => 'fechado_ganho', 'ordem' => 3, 'cor_hex' => '#22c55e'],
        ['nome' => 'Perdido', 'slug' => 'perdido', 'ordem' => 4, 'cor_hex' => '#ef4444'],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaceId = fd_current_workspace_id() ?? 0;
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para oportunidades.');
        }

        return $this->workspaceId;
    }

    private function seedDefaultStagesIfEmpty(): void
    {
        $workspaceId = $this->currentWorkspaceId();
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM funil_estagios WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $insert = $this->pdo->prepare("
            INSERT INTO funil_estagios (workspace_id, nome, slug, ordem, cor_hex, ativo, criado_em)
            VALUES (:workspace_id, :nome, :slug, :ordem, :cor_hex, 1, NOW())
        ");

        foreach (self::DEFAULT_STAGES as $stage) {
            $insert->execute([
                ':workspace_id' => $workspaceId,
                ':nome' => $stage['nome'],
                ':slug' => $stage['slug'],
                ':ordem' => $stage['ordem'],
                ':cor_hex' => $stage['cor_hex'],
            ]);
        }
    }

    public function listarEstagiosAtivos(): array
    {
        $this->seedDefaultStagesIfEmpty();

        $sql = "SELECT id, nome, slug, ordem, cor_hex
                  FROM funil_estagios
                 WHERE workspace_id = ? AND ativo = 1
              ORDER BY ordem ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorEstagio(int $estagioId): array
    {
        $sql = "
            SELECT o.*, c.nome AS cliente_nome
              FROM oportunidades o
              JOIN clientes c ON c.id = o.cliente_id
             WHERE o.funil_estagio_id = :estagio
               AND o.workspace_id = :workspace_id
               AND c.workspace_id = :workspace_id
               AND o.ativo = 1
          ORDER BY o.data_criacao DESC
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':estagio' => $estagioId,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare("
            SELECT o.*, c.nome AS cliente_nome
              FROM oportunidades o
              JOIN clientes c ON c.id = o.cliente_id
             WHERE o.id = :id
               AND o.workspace_id = :workspace_id
               AND c.workspace_id = :workspace_id
        ");
        $st->execute([
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criar(array $data): int
    {
        $sql = "
            INSERT INTO oportunidades
                (workspace_id, cliente_id, projeto_id, funil_estagio_id, titulo,
                 valor_previsto, probabilidade, origem_lead,
                 responsavel, data_prevista_fechamento, motivo_perda,
                 observacoes)
            VALUES
                (:workspace_id, :cliente_id, :projeto_id, :funil_estagio_id, :titulo,
                 :valor_previsto, :probabilidade, :origem_lead,
                 :responsavel, :data_prevista_fechamento, :motivo_perda,
                 :observacoes)
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':cliente_id' => (int) $data['cliente_id'],
            ':projeto_id' => $data['projeto_id'] ?? null,
            ':funil_estagio_id' => (int) $data['funil_estagio_id'],
            ':titulo' => trim($data['titulo']),
            ':valor_previsto' => (float) $data['valor_previsto'],
            ':probabilidade' => (int) ($data['probabilidade'] ?? 0),
            ':origem_lead' => $data['origem_lead'] ?? null,
            ':responsavel' => $data['responsavel'] ?? null,
            ':data_prevista_fechamento' => $data['data_prevista_fechamento'] ?: null,
            ':motivo_perda' => $data['motivo_perda'] ?? null,
            ':observacoes' => $data['observacoes'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE oportunidades
               SET cliente_id = :cliente_id,
                   projeto_id = :projeto_id,
                   funil_estagio_id = :funil_estagio_id,
                   titulo = :titulo,
                   valor_previsto = :valor_previsto,
                   probabilidade = :probabilidade,
                   origem_lead = :origem_lead,
                   responsavel = :responsavel,
                   data_prevista_fechamento = :data_prevista_fechamento,
                   motivo_perda = :motivo_perda,
                   observacoes = :observacoes
             WHERE id = :id
               AND workspace_id = :workspace_id
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
            ':cliente_id' => (int) $data['cliente_id'],
            ':projeto_id' => $data['projeto_id'] ?? null,
            ':funil_estagio_id' => (int) $data['funil_estagio_id'],
            ':titulo' => trim($data['titulo']),
            ':valor_previsto' => (float) $data['valor_previsto'],
            ':probabilidade' => (int) ($data['probabilidade'] ?? 0),
            ':origem_lead' => $data['origem_lead'] ?? null,
            ':responsavel' => $data['responsavel'] ?? null,
            ':data_prevista_fechamento' => $data['data_prevista_fechamento'] ?: null,
            ':motivo_perda' => $data['motivo_perda'] ?? null,
            ':observacoes' => $data['observacoes'] ?? null,
        ]);
    }

    public function moverEstagio(int $id, int $novoEstagioId): bool
    {
        $sql = "
            UPDATE oportunidades
               SET funil_estagio_id = :estagio
             WHERE id = :id
               AND workspace_id = :workspace_id
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $novoEstagioId,
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function marcarGanha(int $id, int $estagioIdGanho): bool
    {
        $sql = "
            UPDATE oportunidades
               SET funil_estagio_id = :estagio,
                   data_fechamento = NOW()
             WHERE id = :id
               AND workspace_id = :workspace_id
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $estagioIdGanho,
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function marcarPerdida(int $id, int $estagioIdPerdido, string $motivo): bool
    {
        $sql = "
            UPDATE oportunidades
               SET funil_estagio_id = :estagio,
                   data_fechamento = NOW(),
                   motivo_perda = :motivo
             WHERE id = :id
               AND workspace_id = :workspace_id
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $estagioIdPerdido,
            ':motivo' => $motivo,
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function vincularProjeto(int $oportunidadeId, int $projetoId): bool
    {
        $st = $this->pdo->prepare("
        UPDATE oportunidades
           SET projeto_id = :projeto_id
         WHERE id = :id
           AND workspace_id = :workspace_id
    ");
        return $st->execute([
            ':projeto_id' => $projetoId,
            ':id' => $oportunidadeId,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM oportunidades WHERE id = :id AND workspace_id = :workspace_id");
        return $st->execute([
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
    }

    public function contarCriadasPeriodo(string $inicio, string $fim): int
    {
        $sql = "SELECT COUNT(*) AS total
              FROM oportunidades
             WHERE workspace_id = :workspace_id
               AND data_criacao BETWEEN :ini AND :fim";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':ini' => $inicio,
            ':fim' => $fim,
        ]);
        return (int) $st->fetchColumn();
    }

    public function contarGanhasPeriodo($inicio, $fim)
    {
        $sql = "SELECT COUNT(*) AS total
            FROM oportunidades o
            JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
            WHERE fe.slug = 'fechado_ganho'
              AND o.workspace_id = :workspace_id
              AND fe.workspace_id = :workspace_id
              AND o.data_fechamento BETWEEN :inicio AND :fim
              AND o.ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim' => $fim
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function contarPerdidasPeriodo($inicio, $fim)
    {
        $sql = "SELECT COUNT(*) AS total
            FROM oportunidades o
            JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
            WHERE fe.slug = 'perdido'
              AND o.workspace_id = :workspace_id
              AND fe.workspace_id = :workspace_id
              AND o.data_fechamento BETWEEN :inicio AND :fim
              AND o.ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim' => $fim
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function resumoConversaoMensal(int $meses = 6): array
    {
        $sql = "
            SELECT
                DATE_FORMAT(o.data_fechamento, '%Y-%m-01') AS mes,
                SUM(CASE WHEN fe.slug = 'fechado_ganho' THEN 1 ELSE 0 END) AS ganhas,
                SUM(CASE WHEN fe.slug = 'perdido' THEN 1 ELSE 0 END) AS perdidas
            FROM oportunidades o
            JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
            WHERE o.data_fechamento >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL :meses MONTH)
              AND o.workspace_id = :workspace_id
              AND fe.workspace_id = :workspace_id
              AND fe.slug IN ('fechado_ganho','perdido')
              AND o.ativo = 1
            GROUP BY mes
            ORDER BY mes ASC
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':meses' => $meses,
            ':workspace_id' => $this->currentWorkspaceId(),
        ]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $ganhas = (int) $r['ganhas'];
            $perdidas = (int) $r['perdidas'];
            $fechadas = $ganhas + $perdidas;
            $winRate = $fechadas > 0 ? ($ganhas / $fechadas) * 100 : 0;

            $out[] = [
                'mes' => $r['mes'],
                'ganhas' => $ganhas,
                'perdidas' => $perdidas,
                'win_rate' => $winRate,
            ];
        }

        return $out;
    }

    public function somarGanhasPeriodo($inicio, $fim): float
    {
        $sql = "
            SELECT COALESCE(SUM(o.valor_previsto), 0) AS soma
            FROM oportunidades o
            JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
            WHERE fe.slug = 'fechado_ganho'
              AND o.workspace_id = :workspace_id
              AND fe.workspace_id = :workspace_id
              AND o.data_fechamento BETWEEN :inicio AND :fim
              AND o.ativo = 1
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim'    => $fim,
        ]);
        return (float) $st->fetchColumn();
    }
}
