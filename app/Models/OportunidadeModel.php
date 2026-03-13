<?php
// app/Models/OportunidadeModel.php

require_once __DIR__ . '/../../config/db.php';
class OportunidadeModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarEstagiosAtivos(): array
    {
        $sql = "SELECT id, nome, slug, ordem, cor_hex
                  FROM funil_estagios
                 WHERE ativo = 1
              ORDER BY ordem ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorEstagio(int $estagioId): array
    {
        $sql = "
            SELECT o.*, c.nome AS cliente_nome
              FROM oportunidades o
              JOIN clientes c ON c.id = o.cliente_id
             WHERE o.funil_estagio_id = :estagio
               AND o.ativo = 1
          ORDER BY o.data_criacao DESC
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':estagio' => $estagioId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare("
            SELECT o.*, c.nome AS cliente_nome
              FROM oportunidades o
              JOIN clientes c ON c.id = o.cliente_id
             WHERE o.id = :id
        ");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criar(array $data): int
    {
        $sql = "
            INSERT INTO oportunidades
                (cliente_id, projeto_id, funil_estagio_id, titulo,
                 valor_previsto, probabilidade, origem_lead,
                 responsavel, data_prevista_fechamento, motivo_perda,
                 observacoes)
            VALUES
                (:cliente_id, :projeto_id, :funil_estagio_id, :titulo,
                 :valor_previsto, :probabilidade, :origem_lead,
                 :responsavel, :data_prevista_fechamento, :motivo_perda,
                 :observacoes)
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
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
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':id' => $id,
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
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $novoEstagioId,
            ':id' => $id,
        ]);
    }

    public function marcarGanha(int $id, int $estagioIdGanho): bool
    {
        $sql = "
            UPDATE oportunidades
               SET funil_estagio_id = :estagio,
                   data_fechamento = NOW()
             WHERE id = :id
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $estagioIdGanho,
            ':id' => $id,
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
        ";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':estagio' => $estagioIdPerdido,
            ':motivo' => $motivo,
            ':id' => $id,
        ]);
    }

    public function vincularProjeto(int $oportunidadeId, int $projetoId): bool
    {
        $st = $this->pdo->prepare("
        UPDATE oportunidades
           SET projeto_id = :projeto_id
         WHERE id = :id
    ");
        return $st->execute([
            ':projeto_id' => $projetoId,
            ':id' => $oportunidadeId,
        ]);
    }
    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare("DELETE FROM oportunidades WHERE id = :id");
        return $st->execute([':id' => $id]);
    }


    public function contarCriadasPeriodo(string $inicio, string $fim): int
    {
        $sql = "SELECT COUNT(*) AS total
              FROM oportunidades
             WHERE data_criacao BETWEEN :ini AND :fim";
        $st = $this->pdo->prepare($sql);
        $st->execute([':ini' => $inicio, ':fim' => $fim]);
        return (int) $st->fetchColumn();
    }

    public function contarGanhasPeriodo($inicio, $fim)
    {
        $sql = "SELECT COUNT(*) AS total
            FROM oportunidades o
            JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
            WHERE fe.slug = 'fechado_ganho'
              AND o.data_fechamento BETWEEN :inicio AND :fim
              AND o.ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
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
            WHERE fe.slug = 'Perdido'
              AND o.data_fechamento BETWEEN :inicio AND :fim
              AND o.ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
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
            SUM(CASE WHEN fe.slug = 'Perdido'       THEN 1 ELSE 0 END) AS perdidas
        FROM oportunidades o
        JOIN funil_estagios fe ON fe.id = o.funil_estagio_id
        WHERE o.data_fechamento >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL :meses MONTH)
          AND fe.slug IN ('fechado_ganho','Perdido')
          AND o.ativo = 1
        GROUP BY mes
        ORDER BY mes ASC
    ";

    $st = $this->pdo->prepare($sql);
    $st->execute([':meses' => $meses]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $ganhas   = (int) $r['ganhas'];
        $perdidas = (int) $r['perdidas'];
        $fechadas = $ganhas + $perdidas;
        $winRate  = $fechadas > 0 ? ($ganhas / $fechadas) * 100 : 0;

        $out[] = [
            'mes'      => $r['mes'],
            'ganhas'   => $ganhas,
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
          AND o.data_fechamento BETWEEN :inicio AND :fim
          AND o.ativo = 1
    ";
    $st = $this->pdo->prepare($sql);
    $st->execute([
        ':inicio' => $inicio,
        ':fim'    => $fim,
    ]);
    return (float) $st->fetchColumn();
}



}
