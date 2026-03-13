<?php
// app/Models/DashboardModel.php

class DashboardModel
{
    public function __construct(private PDO $pdo) {}

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
        $stmt = $this->pdo->prepare("
          SELECT COUNT(*)
          FROM clientes
          WHERE YEAR(criado_em) = ? AND MONTH(criado_em) = ?
        ");
        $stmt->execute([$ano, $mes]);
        return (int)$stmt->fetchColumn();
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
          SELECT t.id, t.titulo, t.coluna, t.criado_em, t.data_entrega,
                 p.nome_projeto
          FROM projeto_tarefas t
          INNER JOIN projetos p ON p.id = t.projeto_id
          WHERE $where
          ORDER BY p.nome_projeto, t.id ASC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hospedagensAtivas(DateTimeInterface $hoje): array
    {
        $hojeStr = $hoje->format('Y-m-d');
        $stmt = $this->pdo->prepare("
          SELECT id, nome, tipo, data_inicio, data_fim
          FROM hospedagens
          WHERE data_fim >= ?
          ORDER BY data_fim ASC, nome ASC
        ");
        $stmt->execute([$hojeStr]);
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
            return null; // evita divisão por zero
        }

        return (($atual - $anterior) / $anterior) * 100.0; // variação em %
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
            return null; // evita divisão por zero
        }

        return (($atual - $anterior) / $anterior) * 100.0; // variação em %
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
        return null; // nada para comparar
    }

    return $atual - $anterior; // valor em R$
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
