<?php
// app/Models/FinanceiroModel.php

class FinanceiroModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* Utilitário */
    private function moneyToFloat(string $value): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        // exemplo correto para formato brasileiro "1.234,56"
        $value = str_replace('.', '', $value);   // remove milhares
        $value = str_replace(',', '.', $value);  // troca vírgula por ponto

        return (float) $value;
    }

    /* ------------ ENTRADAS ------------ */

    public function criarEntrada(array $data): bool
    {
        $cliente_id = isset($data['cliente_id']) ? (int) $data['cliente_id'] : null;
        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = trim($data['descricao'] ?? '');
        $servico = $data['servico'] ?? 'outro';
        $tipo = $data['tipo_pagamento'] ?? 'integral';
        $forma = $data['forma_pagamento'] ?? 'pix';
        $valor_rec = $this->moneyToFloat($data['valor_a_receber'] ?? '0');
        $valor_recib = $this->moneyToFloat($data['valor_recebido'] ?? '0');
        $obs = trim($data['observacoes'] ?? '');
        $concluido = ($tipo === 'integral' && $valor_recib >= $valor_rec) ? 1 : 0;

        if ($descricao === '' || $valor_rec <= 0) {
            return false;
        }

        $sql = '
          INSERT INTO financeiro_entradas
          (cliente_id, data_lancamento, descricao, servico, tipo_pagamento, forma_pagamento,
           valor_a_receber, valor_recebido, concluido, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $cliente_id,
            $data_lanc,
            $descricao,
            $servico,
            $tipo,
            $forma,
            $valor_rec,
            $valor_recib,
            $concluido,
            $obs
        ]);
    }

    public function buscarEntrada(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM financeiro_entradas
        WHERE id = ?
    ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    public function atualizarEntrada(int $id, array $data): bool
    {
        $cliente_id_raw = $data['cliente_id'] ?? null;
        $cliente_id = ($cliente_id_raw === '' || $cliente_id_raw === null)
            ? null
            : (int) $cliente_id_raw;

        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = trim($data['descricao'] ?? '');
        $servico = $data['servico'] ?? 'outro';
        $tipo = $data['tipo_pagamento'] ?? 'integral';
        $forma = $data['forma_pagamento'] ?? 'pix';
        $valor_rec = $this->moneyToFloat($data['valor_a_receber'] ?? '0');
        $valor_recib = $this->moneyToFloat($data['valor_recebido'] ?? '0');
        $obs = trim($data['observacoes'] ?? '');
        $concluido = ($tipo === 'integral' && $valor_recib >= $valor_rec) ? 1 : 0;

        if ($descricao === '' || $valor_rec <= 0) {
            return false;
        }

        $sql = '
        UPDATE financeiro_entradas
        SET cliente_id      = :cliente_id,
            data_lancamento = :data_lanc,
            descricao       = :descricao,
            servico         = :servico,
            tipo_pagamento  = :tipo,
            forma_pagamento = :forma,
            valor_a_receber = :valor_rec,
            valor_recebido  = :valor_recib,
            concluido       = :concluido,
            observacoes     = :obs,
            atualizado_em   = NOW()
        WHERE id = :id
    ';

        $stmt = $this->pdo->prepare($sql);

        if ($cliente_id === null) {
            $stmt->bindValue(':cliente_id', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':cliente_id', $cliente_id, \PDO::PARAM_INT);
        }

        $stmt->bindValue(':data_lanc', $data_lanc);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':servico', $servico);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':forma', $forma);
        $stmt->bindValue(':valor_rec', $valor_rec);
        $stmt->bindValue(':valor_recib', $valor_recib);
        $stmt->bindValue(':concluido', $concluido, \PDO::PARAM_INT);
        $stmt->bindValue(':obs', $obs);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }




    public function excluirEntrada(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM financeiro_entradas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /* ------------ SAÍDAS ------------ */

    public function criarSaida(array $data): bool
    {
        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = trim($data['descricao'] ?? '');
        $tipo = $data['tipo'] ?? 'outro';
        $valor = $this->moneyToFloat($data['valor'] ?? '0');
        $obs = trim($data['observacoes'] ?? '');

        if ($descricao === '' || $valor <= 0) {
            return false;
        }

        $sql = '
          INSERT INTO financeiro_saidas
          (data_lancamento, tipo, descricao, valor, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$data_lanc, $tipo, $descricao, $valor, $obs]);
    }

    public function excluirSaida(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM financeiro_saidas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function criarSaidaParaFixo(array $fixo, float $valor): bool
    {
        $hoje = date('Y-m-d');
        $descricao = 'Gasto fixo: ' . $fixo['tipo_gasto'];
        $obs = 'Pagamento de gasto fixo ID ' . $fixo['id'];

        $sql = '
          INSERT INTO financeiro_saidas
          (fixo_id, data_lancamento, tipo, descricao, valor, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $fixo['id'],
            $hoje,
            'pagamentos',
            $descricao,
            $valor,
            $obs
        ]);
    }

    /* ------------ FIXOS ------------ */

    public function criarFixo(array $data): bool
    {
        $tipo_gasto = trim($data['tipo_gasto'] ?? '');
        $valor = $this->moneyToFloat($data['valor'] ?? '0');
        $data_inicio = $data['data_inicio'] ?? date('Y-m-d');
        $eh_parcelado = isset($data['eh_parcelado']) ? 1 : 0;
        $parcelas_tot = $eh_parcelado ? (int) ($data['parcelas_totais'] ?? 0) : null;
        $parcelas_res = $eh_parcelado ? (int) ($data['parcelas_restantes'] ?? 0) : null;
        $obs = trim($data['observacoes'] ?? '');

        if ($tipo_gasto === '' || $valor <= 0) {
            return false;
        }

        $sql = '
          INSERT INTO financeiro_fixos
          (tipo_gasto, valor, eh_parcelado, parcelas_totais, parcelas_restantes,
           data_inicio, ativo, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $tipo_gasto,
            $valor,
            $eh_parcelado,
            $parcelas_tot,
            $parcelas_res,
            $data_inicio,
            $obs
        ]);
    }

    public function marcarFixoPagoMes(int $id): bool
    {
        $sql = "
          UPDATE financeiro_fixos
          SET status_mes = 'pago', atualizado_em = NOW()
          WHERE id = ? AND ativo = 1
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function buscarFixoAtivo(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM financeiro_fixos WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return $f ?: null;
    }

    public function atualizarParcelasRestantes(int $id, int $restantes): bool
    {
        $ativo = $restantes > 0 ? 1 : 0;
        $sql = "
          UPDATE financeiro_fixos
          SET parcelas_restantes = ?, ativo = ?, atualizado_em = NOW()
          WHERE id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$restantes, $ativo, $id]);
    }

    public function desativarFixo(int $id): bool
    {
        $sql = "UPDATE financeiro_fixos SET ativo = 0, atualizado_em = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function totaisMes(string $inicio, string $fim): array
    {
        // entradas do mês
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(valor_recebido), 0) AS total
        FROM financeiro_entradas
        WHERE data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$inicio, $fim]);
        $entradas = (float) $stmt->fetchColumn();

        // saídas do mês
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) AS total
        FROM financeiro_saidas
        WHERE data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$inicio, $fim]);
        $saidas = (float) $stmt->fetchColumn();

        // caixa total histórico
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_entradas");
        $caixaEntradas = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM financeiro_saidas");
        $caixaSaidas = (float) $stmt->fetchColumn();

        $caixaTotal = $caixaEntradas - $caixaSaidas;
        $caixaMes = $entradas - $saidas;

        return [
            'entradas_mes' => $entradas,
            'saidas_mes' => $saidas,
            'caixa_total' => $caixaTotal,
            'caixa_mes' => $caixaMes,
        ];
    }

    public function listarEntradasMes(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
        SELECT e.*, c.nome AS cliente_nome
        FROM financeiro_entradas e
        LEFT JOIN clientes c ON c.id = e.cliente_id
        WHERE e.data_lancamento BETWEEN ? AND ?
        ORDER BY e.data_lancamento DESC, e.id DESC
    ");
        $stmt->execute([$inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSaidasMes(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM financeiro_saidas
        WHERE data_lancamento BETWEEN ? AND ?
        ORDER BY data_lancamento DESC, id DESC
    ");
        $stmt->execute([$inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function idsFixosPagosNoMes(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
        SELECT DISTINCT fixo_id
        FROM financeiro_saidas
        WHERE fixo_id IS NOT NULL
          AND data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarFixosAtivosAte(string $dataLimite): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM financeiro_fixos
        WHERE ativo = 1
          AND data_inicio <= ?
        ORDER BY tipo_gasto ASC
    ");
        $stmt->execute([$dataLimite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totalFixosMesNaoPagos(string $dataLimite, string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(f.valor),0) AS total
        FROM financeiro_fixos f
        WHERE f.ativo = 1
          AND f.data_inicio <= ?
          AND (f.eh_parcelado = 0 OR (f.eh_parcelado = 1 AND f.parcelas_totais >= 1))
          AND f.id NOT IN (
              SELECT DISTINCT fixo_id
              FROM financeiro_saidas
              WHERE fixo_id IS NOT NULL
                AND data_lancamento BETWEEN ? AND ?
          )
    ");
        $stmt->execute([$dataLimite, $inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }

    public function totalEntradasMes(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor_recebido), 0) AS total
            FROM financeiro_entradas
            WHERE data_lancamento BETWEEN ? AND ?
        ");
        $stmt->execute([$inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }

    public function totalSaidasMes(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total
            FROM financeiro_saidas
            WHERE data_lancamento BETWEEN ? AND ?
        ");
        $stmt->execute([$inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }


    /*--------- Analises -----------*/

    public function totaisSaidasPorTipo(string $inicio, string $fim): array
    {
        $sql = "
        SELECT tipo, SUM(valor) AS total
        FROM financeiro_saidas
        WHERE data_lancamento BETWEEN :inicio AND :fim
        GROUP BY tipo
        ORDER BY total DESC
    ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':inicio' => $inicio, ':fim' => $fim]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totaisAnoPorMes(string $ano): array
    {
        $inicio = "$ano-01-01";
        $fim = "$ano-12-31";

        // ENTRADAS
        $sqlEnt = "
        SELECT DATE_FORMAT(data_lancamento, '%m') AS mes,
               SUM(valor_recebido) AS total
        FROM financeiro_entradas
        WHERE data_lancamento BETWEEN :inicio AND :fim
        GROUP BY mes
        ORDER BY mes
    ";
        $stEnt = $this->pdo->prepare($sqlEnt);
        $stEnt->execute([':inicio' => $inicio, ':fim' => $fim]);
        $rowsEnt = $stEnt->fetchAll(PDO::FETCH_ASSOC);

        // SAÍDAS
        $sqlSai = "
        SELECT DATE_FORMAT(data_lancamento, '%m') AS mes,
               SUM(valor) AS total
        FROM financeiro_saidas
        WHERE data_lancamento BETWEEN :inicio AND :fim
        GROUP BY mes
        ORDER BY mes
    ";
        $stSai = $this->pdo->prepare($sqlSai);
        $stSai->execute([':inicio' => $inicio, ':fim' => $fim]);
        $rowsSai = $stSai->fetchAll(PDO::FETCH_ASSOC);

        // preenche 12 meses com zero
        $entradas = array_fill(1, 12, 0.0);
        $saidas = array_fill(1, 12, 0.0);

        foreach ($rowsEnt as $r) {
            $m = (int) $r['mes'];
            $entradas[$m] = (float) $r['total'];
        }
        foreach ($rowsSai as $r) {
            $m = (int) $r['mes'];
            $saidas[$m] = (float) $r['total'];
        }

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
        ];
    }


}
