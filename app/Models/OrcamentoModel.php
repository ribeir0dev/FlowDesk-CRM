<?php

require_once __DIR__ . '/../../config/db.php';

class OrcamentoModel
{
    private PDO $pdo;
    private int $workspaceId;
    private ?int $viewerClienteId;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaceId = fd_current_workspace_id() ?? 0;
        $this->viewerClienteId = fd_current_cliente_id();
    }

    private function currentWorkspaceId(): int
    {
        if ($this->workspaceId <= 0) {
            throw new RuntimeException('Workspace atual nao definido para orcamentos.');
        }

        return $this->workspaceId;
    }

    private function viewerClienteId(): ?int
    {
        return fd_current_workspace_role() === 'viewer' ? $this->viewerClienteId : null;
    }

    private function viewerHasLinkedClient(): bool
    {
        return fd_current_workspace_role() !== 'viewer' || $this->viewerClienteId() !== null;
    }

    private function clientePertenceAoWorkspace(int $clienteId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM clientes WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$clienteId, $this->currentWorkspaceId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function orcamentoPertenceAoWorkspace(int $orcamentoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orcamentos WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$orcamentoId, $this->currentWorkspaceId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function atualizarOrcamentosVencidos(): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE orcamentos
            SET status = 'Vencida', atualizado_em = NOW()
            WHERE workspace_id = ?
              AND vencimento IS NOT NULL
              AND vencimento < CURDATE()
              AND status NOT IN ('Aprovada', 'Recusada', 'Vencida')
        ");
        $stmt->execute([$this->currentWorkspaceId()]);
    }

    private function randomCode(int $bytes = 5): string
    {
        return strtoupper(substr(bin2hex(random_bytes($bytes)), 0, $bytes * 2));
    }

    private function moneyValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return max(0, (float) $value);
        }

        $value = trim((string) $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return max(0, (float) preg_replace('/[^0-9.\-]/', '', $value));
    }

    private function nextProposalCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'PRP-' . date('Y') . '-' . $this->randomCode(3);
            $stmt = $this->pdo->prepare('SELECT 1 FROM orcamentos WHERE workspace_id = ? AND codigo = ? LIMIT 1');
            $stmt->execute([$this->currentWorkspaceId(), $code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar o numero da proposta.');
    }

    private function nextPublicCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = bin2hex(random_bytes(12));
            $stmt = $this->pdo->prepare('SELECT 1 FROM orcamentos WHERE public_code = ? LIMIT 1');
            $stmt->execute([$code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar o link publico da proposta.');
    }

    public function gerarNumeroProposta(): string
    {
        return $this->nextProposalCode();
    }

    public function listarClientes(): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = '
            SELECT id, nome, whatsapp, email, foto_perfil
            FROM clientes
            WHERE workspace_id = ?
        ';
        $params = [$this->currentWorkspaceId()];
        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND id = ?';
            $params[] = $this->viewerClienteId();
        }
        $sql .= ' ORDER BY nome ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function resumoGeral(): array
    {
        $this->atualizarOrcamentosVencidos();
        $sql = "
            SELECT
              COUNT(*) AS total,
              SUM(status IN ('Aguardando', 'Aguardando Aprovação')) AS aguardando,
              SUM(status = 'Aprovada') AS aprovadas,
              COALESCE(SUM(valor_total), 0) AS valor_total
            FROM orcamentos
            WHERE workspace_id = ?
        ";
        $params = [$this->currentWorkspaceId()];
        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'aguardando' => 0,
            'aprovadas' => 0,
            'valor_total' => 0,
        ];
    }

    public function listarPaginado(array $filters, int $page = 1, int $perPage = 5): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        }

        $this->atualizarOrcamentosVencidos();
        $where = ['o.workspace_id = ?', 'c.workspace_id = ?'];
        $workspaceId = $this->currentWorkspaceId();
        $params = [$workspaceId, $workspaceId];

        if ($this->viewerClienteId() !== null) {
            $where[] = 'o.cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(o.codigo LIKE ? OR c.nome LIKE ? OR o.servico_principal LIKE ? OR o.descricao_servico LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term);
        }

        $status = trim((string) ($filters['status'] ?? 'todos'));
        $allowed = ['Rascunho', 'Aguardando Aprovação', 'Aguardando', 'Aprovada', 'Recusada', 'Vencida'];
        if (in_array($status, $allowed, true)) {
            if ($status === 'Aguardando Aprovação') {
                $where[] = "o.status IN ('Aguardando Aprovação', 'Aguardando')";
            } else {
                $where[] = 'o.status = ?';
                $params[] = $status;
            }
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM orcamentos o
            INNER JOIN clientes c ON c.id = o.cliente_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("
            SELECT
              o.id, o.codigo, o.public_code, o.cliente_id, c.nome AS cliente_nome,
              c.foto_perfil AS cliente_foto, o.servico_principal, o.descricao_servico,
              o.forma_pagamento, o.parcelas,
              CASE WHEN o.status = 'Aguardando' THEN 'Aguardando Aprovação' ELSE o.status END AS status,
              o.valor_total, o.desconto_total,
              o.data_emissao, o.vencimento, o.prazo_estimado_dias, o.criado_em, o.atualizado_em
            FROM orcamentos o
            INNER JOIN clientes c ON c.id = o.cliente_id
            WHERE {$whereSql}
            ORDER BY o.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public function listarComClientes(array $statusList = ['todos']): array
    {
        $status = count($statusList) === 1 ? (string) reset($statusList) : 'todos';
        return $this->listarPaginado(['status' => $status], 1, 1000)['items'];
    }

    public function criar(
        int $clienteId,
        string $servicoPrincipal,
        string $descricaoServico,
        string $formaPagamento,
        string $status,
        float $valorTotal,
        string $vencimento,
        array $itens,
        array $extra = []
    ): int {
        if (!$this->clientePertenceAoWorkspace($clienteId)) {
            return 0;
        }

        $this->pdo->beginTransaction();
        try {
            $codigo = trim((string) ($extra['codigo'] ?? '')) ?: $this->nextProposalCode();
            $publicCode = $this->nextPublicCode();
            $dataEmissao = (string) ($extra['data_emissao'] ?? date('Y-m-d'));
            $prazo = max(1, min(365, (int) ($extra['prazo_estimado_dias'] ?? 7)));
            $parcelas = isset($extra['parcelas']) ? max(1, min(10, (int) $extra['parcelas'])) : null;
            $descontoTotal = max(0, (float) ($extra['desconto_total'] ?? 0));

            $stmt = $this->pdo->prepare("
                INSERT INTO orcamentos
                    (workspace_id, codigo, public_code, cliente_id, data_emissao,
                     servico_principal, descricao_servico, forma_pagamento, parcelas,
                     status, valor_total, desconto_total, vencimento, prazo_estimado_dias,
                     criado_em, atualizado_em)
                VALUES
                    (:workspace_id, :codigo, :public_code, :cliente_id, :data_emissao,
                     :servico_principal, :descricao_servico, :forma_pagamento, :parcelas,
                     :status, :valor_total, :desconto_total, :vencimento, :prazo,
                     NOW(), NOW())
            ");
            $stmt->execute([
                ':workspace_id' => $this->currentWorkspaceId(),
                ':codigo' => mb_substr($codigo, 0, 40),
                ':public_code' => $publicCode,
                ':cliente_id' => $clienteId,
                ':data_emissao' => $dataEmissao,
                ':servico_principal' => $servicoPrincipal,
                ':descricao_servico' => $descricaoServico,
                ':forma_pagamento' => $formaPagamento,
                ':parcelas' => $parcelas,
                ':status' => $status,
                ':valor_total' => $valorTotal,
                ':desconto_total' => $descontoTotal,
                ':vencimento' => $vencimento,
                ':prazo' => $prazo,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->salvarItens($id, $itens);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function atualizar(
        int $id,
        int $clienteId,
        string $servicoPrincipal,
        string $descricaoServico,
        string $formaPagamento,
        string $status,
        float $valorTotal,
        string $vencimento,
        array $itens,
        array $extra = []
    ): bool {
        if (!$this->clientePertenceAoWorkspace($clienteId) || !$this->orcamentoPertenceAoWorkspace($id)) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE orcamentos
                SET cliente_id = :cliente_id,
                    data_emissao = :data_emissao,
                    servico_principal = :servico_principal,
                    descricao_servico = :descricao_servico,
                    forma_pagamento = :forma_pagamento,
                    parcelas = :parcelas,
                    status = :status,
                    valor_total = :valor_total,
                    desconto_total = :desconto_total,
                    vencimento = :vencimento,
                    prazo_estimado_dias = :prazo,
                    atualizado_em = NOW()
                WHERE id = :id AND workspace_id = :workspace_id
            ");
            $ok = $stmt->execute([
                ':id' => $id,
                ':workspace_id' => $this->currentWorkspaceId(),
                ':cliente_id' => $clienteId,
                ':data_emissao' => (string) ($extra['data_emissao'] ?? date('Y-m-d')),
                ':servico_principal' => $servicoPrincipal,
                ':descricao_servico' => $descricaoServico,
                ':forma_pagamento' => $formaPagamento,
                ':parcelas' => isset($extra['parcelas']) ? max(1, min(10, (int) $extra['parcelas'])) : null,
                ':status' => $status,
                ':valor_total' => $valorTotal,
                ':desconto_total' => max(0, (float) ($extra['desconto_total'] ?? 0)),
                ':vencimento' => $vencimento,
                ':prazo' => max(1, min(365, (int) ($extra['prazo_estimado_dias'] ?? 7))),
            ]);
            $delete = $this->pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = ? AND workspace_id = ?');
            $delete->execute([$id, $this->currentWorkspaceId()]);
            $this->salvarItens($id, $itens);
            $this->pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function duplicar(int $id): int
    {
        $orcamento = $this->buscarPorId($id);
        if (!$orcamento) {
            return 0;
        }

        return $this->criar(
            (int) $orcamento['cliente_id'],
            (string) $orcamento['servico_principal'],
            (string) $orcamento['descricao_servico'],
            (string) $orcamento['forma_pagamento'],
            'Aguardando Aprovação',
            (float) $orcamento['valor_total'],
            date('Y-m-d', strtotime('+7 days')),
            $this->buscarItens($id),
            [
                'data_emissao' => date('Y-m-d'),
                'prazo_estimado_dias' => (int) ($orcamento['prazo_estimado_dias'] ?? 7),
                'parcelas' => $orcamento['parcelas'] ?? null,
                'desconto_total' => (float) ($orcamento['desconto_total'] ?? 0),
            ]
        );
    }

    public function excluir(int $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            $ok = $this->excluirSemTransacao($id);
            $this->pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function excluirSemTransacao(int $id): bool
    {
        $stmtItens = $this->pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = ? AND workspace_id = ?');
        $stmtItens->execute([$id, $this->currentWorkspaceId()]);
        $stmt = $this->pdo->prepare('DELETE FROM orcamentos WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$id, $this->currentWorkspaceId()]);
        return $stmt->rowCount() > 0;
    }

    public function buscarPorId(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = "
            SELECT o.*,
                   CASE WHEN o.status = 'Aguardando' THEN 'Aguardando Aprovação' ELSE o.status END AS status,
                   c.nome AS cliente_nome, c.email AS cliente_email,
                   c.whatsapp AS cliente_whatsapp, c.foto_perfil AS cliente_foto
            FROM orcamentos o
            INNER JOIN clientes c ON c.id = o.cliente_id AND c.workspace_id = o.workspace_id
            WHERE o.id = ? AND o.workspace_id = ?
        ";
        $params = [$id, $this->currentWorkspaceId()];
        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND o.cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarPorCodigoPublico(string $code): ?array
    {
        if (!preg_match('/^[a-f0-9]{24}$/', $code)) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT o.*,
                   CASE
                     WHEN o.status NOT IN ('Aprovada', 'Recusada', 'Vencida')
                          AND o.vencimento < CURDATE() THEN 'Vencida'
                     WHEN o.status = 'Aguardando' THEN 'Aguardando Aprovação'
                     ELSE o.status
                   END AS status,
                   c.nome AS cliente_nome,
                   c.email AS cliente_email,
                   c.whatsapp AS cliente_whatsapp,
                   c.foto_perfil AS cliente_foto
            FROM orcamentos o
            INNER JOIN clientes c ON c.id = o.cliente_id AND c.workspace_id = o.workspace_id
            WHERE o.public_code = ?
            LIMIT 1
        ");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarItens(int $orcamentoId, ?int $workspaceId = null): array
    {
        $workspaceId = $workspaceId ?: $this->currentWorkspaceId();
        $stmt = $this->pdo->prepare("
            SELECT id, descricao, quantidade, valor_unitario, desconto_percentual,
                   subtotal, valor
            FROM orcamento_itens
            WHERE orcamento_id = ? AND workspace_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$orcamentoId, $workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarAtividades(int $orcamentoId, int $limit = 3): array
    {
        $limit = max(1, min(10, $limit));
        $stmt = $this->pdo->prepare("
            SELECT a.acao, a.payload, a.created_at AS criado_em, u.nome AS usuario_nome
            FROM audit_logs a
            LEFT JOIN usuarios u ON u.id = a.user_id
            WHERE a.workspace_id = ?
              AND a.entidade = 'orcamento'
              AND a.entidade_id = ?
            ORDER BY a.id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$this->currentWorkspaceId(), $orcamentoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarAjustes(int $orcamentoId, ?int $workspaceId = null): array
    {
        $workspaceId = $workspaceId ?: $this->currentWorkspaceId();
        if ($orcamentoId <= 0 || $workspaceId <= 0) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, mensagem, status, criado_em
                FROM orcamento_ajustes
                WHERE orcamento_id = ?
                  AND workspace_id = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$orcamentoId, $workspaceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    public function aprovarPorCodigoPublico(string $code): ?array
    {
        $code = strtolower(trim($code));
        if (!preg_match('/^[a-f0-9]{24}$/', $code)) {
            return null;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, c.nome AS cliente_nome, c.email AS cliente_email, c.whatsapp AS cliente_whatsapp
                FROM orcamentos o
                INNER JOIN clientes c ON c.id = o.cliente_id AND c.workspace_id = o.workspace_id
                WHERE o.public_code = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$code]);
            $proposal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$proposal) {
                $this->pdo->rollBack();
                return null;
            }

            if ((string) ($proposal['status'] ?? '') !== 'Aprovada') {
                $update = $this->pdo->prepare("
                    UPDATE orcamentos
                    SET status = 'Aprovada', atualizado_em = NOW()
                    WHERE id = ? AND public_code = ?
                ");
                $update->execute([(int) $proposal['id'], $code]);
            }

            $this->registrarAtividadePublica(
                (int) $proposal['workspace_id'],
                (int) $proposal['id'],
                'orcamento.public_confirm',
                [
                    'codigo' => (string) $proposal['codigo'],
                    'cliente' => (string) $proposal['cliente_nome'],
                    'valor_total' => (float) $proposal['valor_total'],
                ]
            );

            $this->pdo->commit();
            return $this->buscarPorCodigoPublico($code);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('[FlowDesk][Orcamento][PublicConfirm] ' . $e->getMessage());
            return null;
        }
    }

    public function solicitarAjustesPorCodigoPublico(string $code, string $mensagem): ?array
    {
        $code = strtolower(trim($code));
        $mensagem = trim($mensagem);
        if (!preg_match('/^[a-f0-9]{24}$/', $code) || $mensagem === '') {
            return null;
        }

        $mensagem = mb_substr($mensagem, 0, 4000);

        try {
            $proposal = $this->buscarPorCodigoPublico($code);
            if (!$proposal || (string) ($proposal['status'] ?? '') === 'Aprovada') {
                return null;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO orcamento_ajustes
                    (workspace_id, orcamento_id, public_code, mensagem, ip_hash, user_agent)
                VALUES
                    (:workspace_id, :orcamento_id, :public_code, :mensagem, :ip_hash, :user_agent)
            ");
            $stmt->execute([
                ':workspace_id' => (int) $proposal['workspace_id'],
                ':orcamento_id' => (int) $proposal['id'],
                ':public_code' => $code,
                ':mensagem' => $mensagem,
                ':ip_hash' => hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '')),
                ':user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);

            $this->registrarAtividadePublica(
                (int) $proposal['workspace_id'],
                (int) $proposal['id'],
                'orcamento.adjustment_requested',
                [
                    'codigo' => (string) $proposal['codigo'],
                    'cliente' => (string) $proposal['cliente_nome'],
                    'mensagem' => $mensagem,
                ]
            );

            return $proposal;
        } catch (Throwable $e) {
            error_log('[FlowDesk][Orcamento][PublicAdjust] ' . $e->getMessage());
            return null;
        }
    }

    public function buscarCriadorId(int $workspaceId, int $orcamentoId): ?int
    {
        if ($workspaceId <= 0 || $orcamentoId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id
                FROM audit_logs
                WHERE workspace_id = ?
                  AND entidade = 'orcamento'
                  AND entidade_id = ?
                  AND user_id IS NOT NULL
                ORDER BY id ASC
                LIMIT 1
            ");
            $stmt->execute([$workspaceId, $orcamentoId]);
            $userId = (int) $stmt->fetchColumn();
            if ($userId > 0) {
                return $userId;
            }

            $owner = $this->pdo->prepare("
                SELECT user_id
                FROM workspace_members
                WHERE workspace_id = ?
                  AND role IN ('owner', 'admin')
                ORDER BY role = 'owner' DESC, id ASC
                LIMIT 1
            ");
            $owner->execute([$workspaceId]);
            $ownerId = (int) $owner->fetchColumn();
            return $ownerId > 0 ? $ownerId : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function registrarAtividadePublica(int $workspaceId, int $orcamentoId, string $acao, array $payload): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (workspace_id, user_id, acao, entidade, entidade_id, payload)
                VALUES (?, NULL, ?, 'orcamento', ?, ?)
            ");
            $stmt->execute([
                $workspaceId,
                $acao,
                $orcamentoId,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $e) {
            error_log('[FlowDesk][Orcamento][PublicAudit] ' . $e->getMessage());
        }
    }

    private function salvarItens(int $orcamentoId, array $itens): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO orcamento_itens
                (workspace_id, orcamento_id, descricao, quantidade, valor_unitario,
                 desconto_percentual, subtotal, valor)
            VALUES
                (:workspace_id, :orcamento_id, :descricao, :quantidade, :valor_unitario,
                 :desconto, :subtotal, :valor)
        ");

        foreach (array_slice($itens, 0, 40) as $item) {
            $descricao = mb_substr(trim((string) ($item['descricao'] ?? '')), 0, 255);
            $quantidade = max(0.01, (float) str_replace(',', '.', (string) ($item['quantidade'] ?? 1)));
            $unitarioRaw = $item['valor_unitario'] ?? $item['valor'] ?? 0;
            $unitario = $this->moneyValue($unitarioRaw);
            $desconto = max(0, min(100, (float) str_replace(',', '.', (string) ($item['desconto_percentual'] ?? 0))));
            $subtotal = $quantidade * $unitario * (1 - ($desconto / 100));

            if ($descricao === '') {
                continue;
            }

            $stmt->execute([
                ':workspace_id' => $this->currentWorkspaceId(),
                ':orcamento_id' => $orcamentoId,
                ':descricao' => $descricao,
                ':quantidade' => $quantidade,
                ':valor_unitario' => $unitario,
                ':desconto' => $desconto,
                ':subtotal' => $subtotal,
                ':valor' => $subtotal,
            ]);
        }
    }
}
