<?php
// app/Models/FinanceiroModel.php

class FinanceiroModel
{
    private PDO $pdo;
    private int $workspaceId;
    private array $columnCache = [];

    public function __construct(PDO $pdo, ?int $workspaceId = null)
    {
        $this->pdo = $pdo;
        $this->workspaceId = $workspaceId ?? (fd_current_workspace_id() ?? 0);
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para financeiro.');
        }

        return $this->workspaceId;
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

    private function clientePertenceAoWorkspace(?int $clienteId): bool
    {
        if ($clienteId === null) {
            return true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM clientes WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$clienteId, $this->currentWorkspaceId()]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function normalizeEnum(string $value, array $allowed, string $fallback): string
    {
        $value = trim($value);
        return in_array($value, $allowed, true) ? $value : $fallback;
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

    private function statusPagamento(float $total, float $pago, string $vencimento): string
    {
        if ($total > 0 && $pago >= $total) {
            return 'pago';
        }

        if ($pago > 0) {
            return 'parcial';
        }

        return $vencimento < date('Y-m-d') ? 'vencido' : 'pendente';
    }

    /* ------------ ENTRADAS ------------ */

    public function criarEntrada(array $data): bool
    {
        $cliente_id_raw = $data['cliente_id'] ?? null;
        $cliente_id = ($cliente_id_raw === '' || $cliente_id_raw === null)
            ? null
            : (int) $cliente_id_raw;
        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 180);
        $servico = mb_substr(trim((string) ($data['servico'] ?? 'outro')), 0, 80);
        $tipo = $this->normalizeEnum((string) ($data['tipo_pagamento'] ?? 'integral'), ['integral', 'parcelado', 'recorrente'], 'integral');
        $forma = mb_substr(trim((string) ($data['forma_pagamento'] ?? 'pix')), 0, 80);
        $valor_rec = $this->moneyToFloat($data['valor_a_receber'] ?? '0');
        $valor_recib = $this->moneyToFloat($data['valor_recebido'] ?? '0');
        if (($data['status_pagamento'] ?? '') === 'pago') {
            $valor_recib = $valor_rec;
        }
        $obs = mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 2000);
        $concluido = ($tipo === 'integral' && $valor_recib >= $valor_rec) ? 1 : 0;

        if ($descricao === '' || $valor_rec <= 0 || !$this->clientePertenceAoWorkspace($cliente_id)) {
            return false;
        }

        $sql = '
          INSERT INTO financeiro_entradas
          (workspace_id, cliente_id, data_lancamento, descricao, servico, tipo_pagamento, forma_pagamento,
           valor_a_receber, valor_recebido, concluido, observacoes, criado_em)
          VALUES (:workspace_id, :cliente_id, :data_lancamento, :descricao, :servico, :tipo_pagamento, :forma_pagamento,
                  :valor_a_receber, :valor_recebido, :concluido, :observacoes, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':workspace_id', $this->currentWorkspaceId(), PDO::PARAM_INT);

        if ($cliente_id === null) {
            $stmt->bindValue(':cliente_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':cliente_id', $cliente_id, PDO::PARAM_INT);
        }

        $stmt->bindValue(':data_lancamento', $data_lanc);
        $stmt->bindValue(':descricao', $descricao);
        $stmt->bindValue(':servico', $servico);
        $stmt->bindValue(':tipo_pagamento', $tipo);
        $stmt->bindValue(':forma_pagamento', $forma);
        $stmt->bindValue(':valor_a_receber', $valor_rec);
        $stmt->bindValue(':valor_recebido', $valor_recib);
        $stmt->bindValue(':concluido', $concluido, PDO::PARAM_INT);
        $stmt->bindValue(':observacoes', $obs);

        return $stmt->execute();
    }

    public function buscarEntrada(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT e.*, c.nome AS cliente_nome, c.whatsapp AS cliente_telefone
        FROM financeiro_entradas e
        LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
        WHERE e.id = ? AND e.workspace_id = ?
    ");
        $stmt->execute([$id, $this->currentWorkspaceId()]);
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
        $descricao = mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 180);
        $servico = mb_substr(trim((string) ($data['servico'] ?? 'outro')), 0, 80);
        $tipo = $this->normalizeEnum((string) ($data['tipo_pagamento'] ?? 'integral'), ['integral', 'parcelado', 'recorrente'], 'integral');
        $forma = mb_substr(trim((string) ($data['forma_pagamento'] ?? 'pix')), 0, 80);
        $valor_rec = $this->moneyToFloat($data['valor_a_receber'] ?? '0');
        $valor_recib = $this->moneyToFloat($data['valor_recebido'] ?? '0');
        if (($data['status_pagamento'] ?? '') === 'pago') {
            $valor_recib = $valor_rec;
        }
        $obs = mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 2000);
        $concluido = ($tipo === 'integral' && $valor_recib >= $valor_rec) ? 1 : 0;

        if ($descricao === '' || $valor_rec <= 0 || !$this->clientePertenceAoWorkspace($cliente_id)) {
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
          AND workspace_id = :workspace_id
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
        $stmt->bindValue(':workspace_id', $this->currentWorkspaceId(), \PDO::PARAM_INT);

        return $stmt->execute();
    }




    public function excluirEntrada(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM financeiro_entradas WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    /* ------------ SAÍDAS ------------ */

    public function criarSaida(array $data): bool
    {
        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 180);
        $tipo = mb_substr(trim((string) ($data['tipo'] ?? 'outro')), 0, 80);
        $valor = $this->moneyToFloat($data['valor'] ?? '0');
        $valorPago = $this->moneyToFloat($data['valor_pago'] ?? '0');
        if (($data['status_pagamento'] ?? '') === 'pago') {
            $valorPago = $valor;
        }
        $obs = mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 2000);

        if ($descricao === '' || $valor <= 0) {
            return false;
        }

        $columns = ['workspace_id', 'data_lancamento', 'tipo', 'descricao', 'valor', 'observacoes', 'criado_em'];
        $values = ['?', '?', '?', '?', '?', '?', 'NOW()'];
        $params = [$this->currentWorkspaceId(), $data_lanc, $tipo, $descricao, $valor, $obs];
        if ($this->hasColumn('financeiro_saidas', 'valor_pago')) {
            $columns[] = 'valor_pago';
            $values[] = '?';
            $params[] = $valorPago;
        }
        if ($this->hasColumn('financeiro_saidas', 'status_pagamento')) {
            $columns[] = 'status_pagamento';
            $values[] = '?';
            $params[] = $this->statusPagamento($valor, $valorPago, $data_lanc);
        }
        if ($this->hasColumn('financeiro_saidas', 'categoria_financeira')) {
            $columns[] = 'categoria_financeira';
            $values[] = '?';
            $params[] = mb_substr(trim((string) ($data['categoria_financeira'] ?? $tipo)), 0, 120);
        }
        if ($this->hasColumn('financeiro_saidas', 'moeda')) {
            $columns[] = 'moeda';
            $values[] = '?';
            $params[] = mb_substr(trim((string) ($data['moeda'] ?? 'BRL')), 0, 10);
        }
        if ($this->hasColumn('financeiro_saidas', 'favorecido')) {
            $columns[] = 'favorecido';
            $values[] = '?';
            $params[] = mb_substr(trim((string) ($data['favorecido'] ?? '')), 0, 160);
        }
        if ($this->hasColumn('financeiro_saidas', 'recorrente')) {
            $columns[] = 'recorrente';
            $values[] = '?';
            $params[] = $tipo === 'recorrente' ? 1 : 0;
        }
        $sql = 'INSERT INTO financeiro_saidas (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function excluirSaida(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM financeiro_saidas WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function criarSaidaParaFixo(array $fixo, float $valor): bool
    {
        $hoje = date('Y-m-d');
        $descricao = 'Gasto fixo: ' . $fixo['tipo_gasto'];
        $obs = 'Pagamento de gasto fixo ID ' . $fixo['id'];

        $sql = '
          INSERT INTO financeiro_saidas
          (workspace_id, fixo_id, data_lancamento, tipo, descricao, valor, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $this->currentWorkspaceId(),
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
        $tipo_gasto = mb_substr(trim((string) ($data['tipo_gasto'] ?? '')), 0, 120);
        $valor = $this->moneyToFloat($data['valor'] ?? '0');
        $data_inicio = $data['data_inicio'] ?? date('Y-m-d');
        $eh_parcelado = isset($data['eh_parcelado']) ? 1 : 0;
        $parcelas_tot = $eh_parcelado ? (int) ($data['parcelas_totais'] ?? 0) : null;
        $parcelas_res = $eh_parcelado ? (int) ($data['parcelas_restantes'] ?? 0) : null;
        $obs = mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 2000);

        if ($tipo_gasto === '' || $valor <= 0) {
            return false;
        }

        $sql = '
          INSERT INTO financeiro_fixos
          (workspace_id, tipo_gasto, valor, eh_parcelado, parcelas_totais, parcelas_restantes,
           data_inicio, ativo, observacoes, criado_em)
          VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $this->currentWorkspaceId(),
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
          WHERE id = ? AND ativo = 1 AND workspace_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function buscarFixoAtivo(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM financeiro_fixos WHERE id = ? AND ativo = 1 AND workspace_id = ?");
        $stmt->execute([$id, $this->currentWorkspaceId()]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return $f ?: null;
    }

    public function atualizarParcelasRestantes(int $id, int $restantes): bool
    {
        $ativo = $restantes > 0 ? 1 : 0;
        $sql = "
          UPDATE financeiro_fixos
          SET parcelas_restantes = ?, ativo = ?, atualizado_em = NOW()
          WHERE id = ? AND workspace_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$restantes, $ativo, $id, $this->currentWorkspaceId()]);
    }

    public function desativarFixo(int $id): bool
    {
        $sql = "UPDATE financeiro_fixos SET ativo = 0, atualizado_em = NOW() WHERE id = ? AND workspace_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function totaisMes(string $inicio, string $fim): array
    {
        // entradas do mês
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(valor_recebido), 0) AS total
        FROM financeiro_entradas
        WHERE workspace_id = ? AND data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        $entradas = (float) $stmt->fetchColumn();

        // saídas do mês
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) AS total
        FROM financeiro_saidas
        WHERE workspace_id = ? AND data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        $saidas = (float) $stmt->fetchColumn();

        // caixa total histórico
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(valor_recebido),0) FROM financeiro_entradas WHERE workspace_id = ?");
        $stmt->execute([$this->currentWorkspaceId()]);
        $caixaEntradas = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM financeiro_saidas WHERE workspace_id = ?");
        $stmt->execute([$this->currentWorkspaceId()]);
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
        LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
        WHERE e.workspace_id = ? AND e.data_lancamento BETWEEN ? AND ?
        ORDER BY e.data_lancamento DESC, e.id DESC
    ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSaidasMes(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM financeiro_saidas
        WHERE workspace_id = ? AND data_lancamento BETWEEN ? AND ?
        ORDER BY data_lancamento DESC, id DESC
    ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtividadesRecentes(string $inicio, string $fim, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));

        $sql = "
            (
                SELECT
                    'entrada' AS tipo_movimento,
                    e.id,
                    e.data_lancamento,
                    e.descricao,
                    e.valor_recebido AS valor,
                    COALESCE(c.nome, 'Sem cliente') AS contexto,
                    e.criado_em
                FROM financeiro_entradas e
                LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
                WHERE e.workspace_id = :workspace_id_entrada
                  AND e.data_lancamento BETWEEN :inicio_entrada AND :fim_entrada
            )
            UNION ALL
            (
                SELECT
                    'saida' AS tipo_movimento,
                    s.id,
                    s.data_lancamento,
                    s.descricao,
                    s.valor,
                    s.tipo AS contexto,
                    s.criado_em
                FROM financeiro_saidas s
                WHERE s.workspace_id = :workspace_id_saida
                  AND s.data_lancamento BETWEEN :inicio_saida AND :fim_saida
            )
            ORDER BY data_lancamento DESC, criado_em DESC, id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id_entrada' => $this->currentWorkspaceId(),
            ':inicio_entrada' => $inicio,
            ':fim_entrada' => $fim,
            ':workspace_id_saida' => $this->currentWorkspaceId(),
            ':inicio_saida' => $inicio,
            ':fim_saida' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function idsFixosPagosNoMes(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
        SELECT DISTINCT fixo_id
        FROM financeiro_saidas
        WHERE fixo_id IS NOT NULL
          AND workspace_id = ?
          AND data_lancamento BETWEEN ? AND ?
    ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarFixosAtivosAte(string $dataLimite): array
    {
        $stmt = $this->pdo->prepare("
        SELECT *
        FROM financeiro_fixos
        WHERE workspace_id = ?
          AND ativo = 1
          AND data_inicio <= ?
        ORDER BY tipo_gasto ASC
    ");
        $stmt->execute([$this->currentWorkspaceId(), $dataLimite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totalFixosMesNaoPagos(string $dataLimite, string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
        SELECT COALESCE(SUM(f.valor),0) AS total
        FROM financeiro_fixos f
        WHERE f.workspace_id = ?
          AND f.ativo = 1
          AND f.data_inicio <= ?
          AND (f.eh_parcelado = 0 OR (f.eh_parcelado = 1 AND f.parcelas_totais >= 1))
          AND f.id NOT IN (
              SELECT DISTINCT fixo_id
              FROM financeiro_saidas
              WHERE fixo_id IS NOT NULL
                AND workspace_id = ?
                AND data_lancamento BETWEEN ? AND ?
          )
    ");
        $stmt->execute([$this->currentWorkspaceId(), $dataLimite, $this->currentWorkspaceId(), $inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }

    public function totalEntradasMes(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor_recebido), 0) AS total
            FROM financeiro_entradas
            WHERE workspace_id = ? AND data_lancamento BETWEEN ? AND ?
        ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }

    public function totalSaidasMes(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total
            FROM financeiro_saidas
            WHERE workspace_id = ? AND data_lancamento BETWEEN ? AND ?
        ");
        $stmt->execute([$this->currentWorkspaceId(), $inicio, $fim]);
        return (float) $stmt->fetchColumn();
    }


    /*--------- Analises -----------*/

    public function totaisSaidasPorTipo(string $inicio, string $fim): array
    {
        $sql = "
        SELECT tipo, SUM(valor) AS total
        FROM financeiro_saidas
        WHERE workspace_id = :workspace_id
          AND data_lancamento BETWEEN :inicio AND :fim
        GROUP BY tipo
        ORDER BY total DESC
    ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);
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
        WHERE workspace_id = :workspace_id
          AND data_lancamento BETWEEN :inicio AND :fim
        GROUP BY mes
        ORDER BY mes
    ";
        $stEnt = $this->pdo->prepare($sqlEnt);
        $stEnt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);
        $rowsEnt = $stEnt->fetchAll(PDO::FETCH_ASSOC);

        // SAÍDAS
        $sqlSai = "
        SELECT DATE_FORMAT(data_lancamento, '%m') AS mes,
               SUM(valor) AS total
        FROM financeiro_saidas
        WHERE workspace_id = :workspace_id
          AND data_lancamento BETWEEN :inicio AND :fim
        GROUP BY mes
        ORDER BY mes
    ";
        $stSai = $this->pdo->prepare($sqlSai);
        $stSai->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);
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

    private function buildEntradaFilters(array $filters, array &$params): string
    {
        $where = '';
        if (($filters['status'] ?? '') !== '') {
            $status = (string) $filters['status'];
            if ($status === 'pago') {
                $where .= ' AND e.valor_recebido >= e.valor_a_receber';
            } elseif ($status === 'parcial') {
                $where .= ' AND e.valor_recebido > 0 AND e.valor_recebido < e.valor_a_receber';
            } elseif ($status === 'vencido') {
                $where .= ' AND e.data_lancamento < CURDATE() AND e.valor_recebido < e.valor_a_receber';
            } elseif ($status === 'pendente') {
                $where .= ' AND e.valor_recebido = 0 AND e.data_lancamento >= CURDATE()';
            }
        }
        if (($filters['categoria'] ?? '') !== '') {
            $column = $this->hasColumn('financeiro_entradas', 'categoria_financeira') ? 'e.categoria_financeira' : 'e.servico';
            $where .= " AND {$column} = ?";
            $params[] = (string) $filters['categoria'];
        }
        if (($filters['cliente_id'] ?? '') !== '') {
            $where .= ' AND e.cliente_id = ?';
            $params[] = (int) $filters['cliente_id'];
        }
        if (($filters['busca'] ?? '') !== '') {
            $where .= ' AND (e.descricao LIKE ? OR e.servico LIKE ? OR c.nome LIKE ?)';
            $like = '%' . (string) $filters['busca'] . '%';
            array_push($params, $like, $like, $like);
        }
        return $where;
    }

    private function buildSaidaFilters(array $filters, array &$params): string
    {
        $where = '';
        $paidColumn = $this->hasColumn('financeiro_saidas', 'valor_pago') ? 's.valor_pago' : 's.valor';
        if (($filters['status'] ?? '') !== '') {
            $status = (string) $filters['status'];
            if ($status === 'pago') {
                $where .= " AND {$paidColumn} >= s.valor";
            } elseif ($status === 'parcial') {
                $where .= " AND {$paidColumn} > 0 AND {$paidColumn} < s.valor";
            } elseif ($status === 'vencido') {
                $where .= " AND s.data_lancamento < CURDATE() AND {$paidColumn} < s.valor";
            } elseif ($status === 'pendente') {
                $where .= " AND {$paidColumn} = 0 AND s.data_lancamento >= CURDATE()";
            }
        }
        if (($filters['categoria'] ?? '') !== '') {
            $column = $this->hasColumn('financeiro_saidas', 'categoria_financeira') ? 's.categoria_financeira' : 's.tipo';
            $where .= " AND {$column} = ?";
            $params[] = (string) $filters['categoria'];
        }
        if (($filters['busca'] ?? '') !== '') {
            $where .= ' AND (s.descricao LIKE ? OR s.tipo LIKE ?' . ($this->hasColumn('financeiro_saidas', 'favorecido') ? ' OR s.favorecido LIKE ?' : '') . ')';
            $like = '%' . (string) $filters['busca'] . '%';
            $params[] = $like;
            $params[] = $like;
            if ($this->hasColumn('financeiro_saidas', 'favorecido')) {
                $params[] = $like;
            }
        }
        return $where;
    }

    public function resumoContasReceber(string $inicio, string $fim, array $filters = []): array
    {
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $where = $this->buildEntradaFilters($filters, $params);
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(e.valor_recebido), 0) AS recebido,
                COALESCE(SUM(GREATEST(e.valor_a_receber - e.valor_recebido, 0)), 0) AS pendente,
                COALESCE(SUM(CASE
                    WHEN e.data_lancamento < CURDATE() AND e.valor_recebido < e.valor_a_receber
                    THEN GREATEST(e.valor_a_receber - e.valor_recebido, 0)
                    ELSE 0
                END), 0) AS vencido,
                COALESCE(SUM(e.valor_a_receber), 0) AS total
            FROM financeiro_entradas e
            LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
            WHERE e.workspace_id = ?
              AND e.data_lancamento BETWEEN ? AND ?
              {$where}
        ");
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['recebido' => 0, 'pendente' => 0, 'vencido' => 0, 'total' => 0];
    }

    public function resumoContasPagar(string $inicio, string $fim, array $filters = []): array
    {
        $valorPago = $this->hasColumn('financeiro_saidas', 'valor_pago') ? 'valor_pago' : 'valor';
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $where = $this->buildSaidaFilters($filters, $params);
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(SUM({$valorPago}), 0) AS pago,
                COALESCE(SUM(GREATEST(valor - {$valorPago}, 0)), 0) AS pendente,
                COALESCE(SUM(CASE
                    WHEN data_lancamento < CURDATE() AND {$valorPago} < valor
                    THEN GREATEST(valor - {$valorPago}, 0)
                    ELSE 0
                END), 0) AS vencido,
                COALESCE(SUM(valor), 0) AS total
            FROM financeiro_saidas s
            WHERE s.workspace_id = ?
              AND s.data_lancamento BETWEEN ? AND ?
              {$where}
        ");
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['pago' => 0, 'pendente' => 0, 'vencido' => 0, 'total' => 0];
    }

    public function listarContasReceber(string $inicio, string $fim, array $filters = []): array
    {
        $categoria = $this->hasColumn('financeiro_entradas', 'categoria_financeira') ? 'e.categoria_financeira' : 'e.servico';
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $where = $this->buildEntradaFilters($filters, $params);
        $stmt = $this->pdo->prepare("
            SELECT
                e.*,
                c.nome AS cliente_nome,
                {$categoria} AS categoria_label,
                CASE
                    WHEN e.valor_recebido >= e.valor_a_receber THEN 'pago'
                    WHEN e.valor_recebido > 0 THEN 'parcial'
                    WHEN e.data_lancamento < CURDATE() THEN 'vencido'
                    ELSE 'pendente'
                END AS status_pagamento_calc,
                GREATEST(e.valor_a_receber - e.valor_recebido, 0) AS saldo_pendente
            FROM financeiro_entradas e
            LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
            WHERE e.workspace_id = ?
              AND e.data_lancamento BETWEEN ? AND ?
              {$where}
            ORDER BY e.data_lancamento ASC, e.id DESC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarContasPagar(string $inicio, string $fim, array $filters = []): array
    {
        $valorPago = $this->hasColumn('financeiro_saidas', 'valor_pago') ? 's.valor_pago' : 's.valor';
        $categoria = $this->hasColumn('financeiro_saidas', 'categoria_financeira') ? 's.categoria_financeira' : 's.tipo';
        $favorecido = $this->hasColumn('financeiro_saidas', 'favorecido') ? 's.favorecido' : 's.tipo';
        $recorrente = $this->hasColumn('financeiro_saidas', 'recorrente') ? 's.recorrente' : '0';
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $where = $this->buildSaidaFilters($filters, $params);
        $stmt = $this->pdo->prepare("
            SELECT
                s.*,
                {$valorPago} AS valor_pago_calc,
                {$favorecido} AS favorecido_label,
                {$categoria} AS categoria_label,
                {$recorrente} AS recorrente_calc,
                CASE
                    WHEN {$valorPago} >= s.valor THEN 'pago'
                    WHEN {$valorPago} > 0 THEN 'parcial'
                    WHEN s.data_lancamento < CURDATE() THEN 'vencido'
                    ELSE 'pendente'
                END AS status_pagamento_calc,
                GREATEST(s.valor - {$valorPago}, 0) AS saldo_pendente
            FROM financeiro_saidas s
            WHERE s.workspace_id = ?
              AND s.data_lancamento BETWEEN ? AND ?
              {$where}
            ORDER BY s.data_lancamento ASC, s.id DESC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarLogTransacoes(int $limit = 10, string $inicio = '', string $fim = '', array $filters = []): array
    {
        $limit = max(4, min(20, $limit));
        $inicio = $inicio !== '' ? $inicio : date('Y-m-01');
        $fim = $fim !== '' ? $fim : date('Y-m-t');
        $paramsEntrada = [$this->currentWorkspaceId(), $inicio, $fim];
        $paramsSaida = [$this->currentWorkspaceId(), $inicio, $fim];
        $whereEntrada = $this->buildEntradaFilters($filters, $paramsEntrada);
        $whereSaida = $this->buildSaidaFilters($filters, $paramsSaida);
        $stmt = $this->pdo->prepare("
            (
                SELECT e.descricao, COALESCE(c.nome, 'Sem cliente') AS fonte, e.servico AS categoria, e.data_lancamento, e.valor_a_receber AS valor, 'receber' AS tipo, e.criado_em
                FROM financeiro_entradas e
                LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
                WHERE e.workspace_id = ? AND e.data_lancamento BETWEEN ? AND ? {$whereEntrada}
            )
            UNION ALL
            (
                SELECT s.descricao, " . ($this->hasColumn('financeiro_saidas', 'favorecido') ? "COALESCE(s.favorecido, s.tipo)" : "s.tipo") . " AS fonte, s.tipo AS categoria, s.data_lancamento, s.valor, 'pagar' AS tipo, s.criado_em
                FROM financeiro_saidas s
                WHERE s.workspace_id = ? AND s.data_lancamento BETWEEN ? AND ? {$whereSaida}
            )
            ORDER BY criado_em DESC
            LIMIT {$limit}
        ");
        $stmt->execute(array_merge($paramsEntrada, $paramsSaida));

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarClientesParaLancamento(): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nome
            FROM clientes
            WHERE workspace_id = ?
            ORDER BY nome ASC
        ');
        $stmt->execute([$this->currentWorkspaceId()]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarCategorias(): array
    {
        if (!$this->hasColumn('financeiro_entradas', 'categoria_financeira')) {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT servico AS nome, '#5690D9' AS cor
                FROM financeiro_entradas
                WHERE workspace_id = ? AND servico IS NOT NULL AND servico <> ''
                ORDER BY servico ASC
            ");
            $stmt->execute([$this->currentWorkspaceId()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->pdo->prepare("
            SELECT nome, cor FROM financeiro_categorias
            WHERE workspace_id = ?
            ORDER BY nome ASC
        ");
        $stmt->execute([$this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function criarCategoria(string $nome, string $cor): bool
    {
        $nome = mb_substr(trim($nome), 0, 120);
        $cor = preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : '#5690D9';
        if ($nome === '') {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO financeiro_categorias (workspace_id, nome, cor)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE cor = VALUES(cor)
        ");
        return $stmt->execute([$this->currentWorkspaceId(), $nome, $cor]);
    }

    public function registrarPagamentoEntrada(int $id, float $valorPago): bool
    {
        if ($valorPago <= 0) {
            return false;
        }

        $entrada = $this->buscarEntrada($id);
        if (!$entrada) {
            return false;
        }

        $total = (float) $entrada['valor_a_receber'];
        $atual = (float) $entrada['valor_recebido'];
        $novo = min($total, $atual + $valorPago);
        $status = $this->statusPagamento($total, $novo, (string) $entrada['data_lancamento']);
        $concluido = $status === 'pago' ? 1 : 0;

        $sql = 'UPDATE financeiro_entradas SET valor_recebido = ?, concluido = ?, atualizado_em = NOW()';
        $params = [$novo, $concluido];
        if ($this->hasColumn('financeiro_entradas', 'status_pagamento')) {
            $sql .= ', status_pagamento = ?';
            $params[] = $status;
        }
        $sql .= ' WHERE id = ? AND workspace_id = ?';
        $params[] = $id;
        $params[] = $this->currentWorkspaceId();

        return $this->pdo->prepare($sql)->execute($params);
    }

    public function buscarSaida(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM financeiro_saidas WHERE id = ? AND workspace_id = ? LIMIT 1');
        $stmt->execute([$id, $this->currentWorkspaceId()]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function atualizarSaida(int $id, array $data): bool
    {
        $data_lanc = $data['data_lancamento'] ?? date('Y-m-d');
        $descricao = mb_substr(trim((string) ($data['descricao'] ?? '')), 0, 180);
        $tipo = mb_substr(trim((string) ($data['tipo'] ?? 'outro')), 0, 80);
        $valor = $this->moneyToFloat($data['valor'] ?? '0');
        $obs = mb_substr(trim((string) ($data['observacoes'] ?? '')), 0, 2000);
        if ($descricao === '' || $valor <= 0) {
            return false;
        }

        $sets = ['data_lancamento = ?', 'tipo = ?', 'descricao = ?', 'valor = ?', 'observacoes = ?', 'atualizado_em = NOW()'];
        $params = [$data_lanc, $tipo, $descricao, $valor, $obs];
        if ($this->hasColumn('financeiro_saidas', 'favorecido')) {
            $sets[] = 'favorecido = ?';
            $params[] = mb_substr(trim((string) ($data['favorecido'] ?? '')), 0, 160);
        }
        if ($this->hasColumn('financeiro_saidas', 'categoria_financeira')) {
            $sets[] = 'categoria_financeira = ?';
            $params[] = mb_substr(trim((string) ($data['categoria_financeira'] ?? $tipo)), 0, 120);
        }
        if ($this->hasColumn('financeiro_saidas', 'moeda')) {
            $sets[] = 'moeda = ?';
            $params[] = mb_substr(trim((string) ($data['moeda'] ?? 'BRL')), 0, 10);
        }
        if ($this->hasColumn('financeiro_saidas', 'recorrente')) {
            $sets[] = 'recorrente = ?';
            $params[] = (($data['tipo'] ?? '') === 'recorrente') ? 1 : 0;
        }
        $params[] = $id;
        $params[] = $this->currentWorkspaceId();
        $sql = 'UPDATE financeiro_saidas SET ' . implode(', ', $sets) . ' WHERE id = ? AND workspace_id = ?';
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function registrarPagamentoSaida(int $id, float $valorPago): bool
    {
        if ($valorPago <= 0 || !$this->hasColumn('financeiro_saidas', 'valor_pago')) {
            return false;
        }

        $saida = $this->buscarSaida($id);
        if (!$saida) {
            return false;
        }

        $total = (float) $saida['valor'];
        $atual = (float) ($saida['valor_pago'] ?? 0);
        $novo = min($total, $atual + $valorPago);
        $status = $this->statusPagamento($total, $novo, (string) $saida['data_lancamento']);

        $stmt = $this->pdo->prepare('
            UPDATE financeiro_saidas
            SET valor_pago = ?, status_pagamento = ?, atualizado_em = NOW()
            WHERE id = ? AND workspace_id = ?
        ');
        return $stmt->execute([$novo, $status, $id, $this->currentWorkspaceId()]);
    }

    public function listarFechamentoClientes(string $inicio, string $fim, array $filters = []): array
    {
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $where = '';
        if (($filters['busca'] ?? '') !== '') {
            $where .= ' AND c.nome LIKE ?';
            $params[] = '%' . (string) $filters['busca'] . '%';
        }

        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(c.id, 0) AS cliente_id,
                COALESCE(c.nome, 'Sem cliente vinculado') AS cliente_nome,
                MAX(c.whatsapp) AS cliente_telefone,
                MAX(c.foto_perfil) AS cliente_foto,
                GROUP_CONCAT(DISTINCT e.servico ORDER BY e.servico SEPARATOR ', ') AS servicos,
                COUNT(*) AS total_lancamentos,
                SUM(CASE WHEN e.valor_recebido < e.valor_a_receber THEN 1 ELSE 0 END) AS contas_pendentes,
                COALESCE(SUM(e.valor_recebido), 0) AS total_pago,
                COALESCE(SUM(e.valor_a_receber), 0) AS total_reais
            FROM financeiro_entradas e
            LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
            WHERE e.workspace_id = ?
              AND e.data_lancamento BETWEEN ? AND ?
              {$where}
            GROUP BY COALESCE(c.id, 0), COALESCE(c.nome, 'Sem cliente vinculado')
            ORDER BY total_reais DESC, cliente_nome ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarPendenciasCliente(int $clienteId, string $inicio, string $fim): array
    {
        $params = [$this->currentWorkspaceId(), $inicio, $fim];
        $clienteWhere = 'e.cliente_id IS NULL';
        if ($clienteId > 0) {
            $clienteWhere = 'e.cliente_id = ?';
            $params[] = $clienteId;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                e.*,
                COALESCE(c.nome, 'Sem cliente vinculado') AS cliente_nome,
                c.whatsapp AS cliente_telefone,
                GREATEST(e.valor_a_receber - e.valor_recebido, 0) AS saldo_pendente
            FROM financeiro_entradas e
            LEFT JOIN clientes c ON c.id = e.cliente_id AND c.workspace_id = e.workspace_id
            WHERE e.workspace_id = ?
              AND e.data_lancamento BETWEEN ? AND ?
              AND {$clienteWhere}
              AND e.valor_recebido < e.valor_a_receber
            ORDER BY e.data_lancamento ASC, e.id ASC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

}
