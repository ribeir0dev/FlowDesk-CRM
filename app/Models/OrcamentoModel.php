<?php
// app/Models/OrcamentoModel.php

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

    public function listarComClientes(array $statusList = ['todos']): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
            SELECT
              o.id,
              o.codigo,
              o.cliente_id,
              c.nome AS cliente_nome,
              o.servico_principal,
              o.descricao_servico,
              o.forma_pagamento,
              o.status,
              o.valor_total
            FROM orcamentos o
            JOIN clientes c ON c.id = o.cliente_id
            WHERE o.workspace_id = ?
              AND c.workspace_id = ?
        ";
        $workspaceId = $this->currentWorkspaceId();
        $params = [$workspaceId, $workspaceId];

        if ($this->viewerClienteId() !== null) {
            $sql .= " AND o.cliente_id = ?";
            $params[] = $this->viewerClienteId();
        }

        if (!in_array('todos', $statusList, true)) {
            $placeholders = implode(',', array_fill(0, count($statusList), '?'));
            $sql .= " AND o.status IN ($placeholders)";
            $params = array_merge($params, $statusList);
        }

        $sql .= " ORDER BY o.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarClientes(): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
            SELECT id, nome, whatsapp
            FROM clientes
            WHERE workspace_id = ?
            ORDER BY nome ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(
        int $clienteId,
        string $servicoPrincipal,
        string $descricaoServico,
        string $formaPagamento,
        string $status,
        float $valorTotal,
        array $itens
    ): int {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare('SELECT IFNULL(MAX(id),0) + 1 AS prox FROM orcamentos WHERE workspace_id = ?');
        $stmt->execute([$this->currentWorkspaceId()]);
        $proxId = (int) $stmt->fetchColumn();
        $codigo = str_pad((string) $proxId, 4, '0', STR_PAD_LEFT);

        $sql = "
            INSERT INTO orcamentos
                (workspace_id, codigo, cliente_id, servico_principal, descricao_servico,
                 forma_pagamento, status, valor_total, criado_em)
            VALUES
                (:workspace_id, :codigo, :cliente_id, :servico_principal, :descricao_servico,
                 :forma_pagamento, :status, :valor_total, NOW())
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':codigo' => $codigo,
            ':cliente_id' => $clienteId,
            ':servico_principal' => $servicoPrincipal,
            ':descricao_servico' => $descricaoServico,
            ':forma_pagamento' => $formaPagamento,
            ':status' => $status,
            ':valor_total' => $valorTotal,
        ]);

        $orcamentoId = (int) $this->pdo->lastInsertId();
        $this->salvarItens($orcamentoId, $itens);

        $this->pdo->commit();

        return $orcamentoId;
    }

    public function atualizar(
        int $id,
        int $clienteId,
        string $servicoPrincipal,
        string $descricaoServico,
        string $formaPagamento,
        string $status,
        float $valorTotal,
        array $itens
    ): bool {
        $this->pdo->beginTransaction();

        $sql = "
            UPDATE orcamentos
            SET cliente_id = :cliente_id,
                servico_principal = :servico_principal,
                descricao_servico = :descricao_servico,
                forma_pagamento = :forma_pagamento,
                status = :status,
                valor_total = :valor_total
            WHERE id = :id
              AND workspace_id = :workspace_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':id' => $id,
            ':workspace_id' => $this->currentWorkspaceId(),
            ':cliente_id' => $clienteId,
            ':servico_principal' => $servicoPrincipal,
            ':descricao_servico' => $descricaoServico,
            ':forma_pagamento' => $formaPagamento,
            ':status' => $status,
            ':valor_total' => $valorTotal,
        ]);

        $stmtItens = $this->pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = ? AND workspace_id = ?');
        $stmtItens->execute([$id, $this->currentWorkspaceId()]);
        $this->salvarItens($id, $itens);

        $this->pdo->commit();

        return $ok;
    }

    public function excluir(int $id): bool
    {
        $this->pdo->beginTransaction();

        $stmtItens = $this->pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = ? AND workspace_id = ?');
        $stmtItens->execute([$id, $this->currentWorkspaceId()]);

        $stmt = $this->pdo->prepare('DELETE FROM orcamentos WHERE id = ? AND workspace_id = ?');
        $ok = $stmt->execute([$id, $this->currentWorkspaceId()]);

        $this->pdo->commit();

        return $ok;
    }

    public function buscarPorId(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = "
            SELECT
              o.id,
              o.codigo,
              o.cliente_id,
              o.servico_principal,
              o.descricao_servico,
              o.forma_pagamento,
              o.status,
              o.valor_total
            FROM orcamentos o
            WHERE o.id = ? AND o.workspace_id = ?
        ";
        $params = [$id, $this->currentWorkspaceId()];
        if ($this->viewerClienteId() !== null) {
            $sql .= " AND o.cliente_id = ?";
            $params[] = $this->viewerClienteId();
        }
        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $orcamento ?: null;
    }

    public function buscarItens(int $orcamentoId): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
            SELECT id, descricao, valor
            FROM orcamento_itens
            WHERE orcamento_id = ? AND workspace_id = ?
            ORDER BY id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orcamentoId, $this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function salvarItens(int $orcamentoId, array $itens): void
    {
        if (empty($itens)) {
            return;
        }

        $sql = "
            INSERT INTO orcamento_itens (workspace_id, orcamento_id, descricao, valor)
            VALUES (:workspace_id, :orcamento_id, :descricao, :valor)
        ";
        $stmt = $this->pdo->prepare($sql);

        foreach ($itens as $item) {
            $descricao = trim($item['descricao'] ?? '');
            $valor = (float) str_replace(',', '.', preg_replace('/\./', '', $item['valor'] ?? '0'));

            if ($descricao === '' || $valor < 0) {
                continue;
            }

            $stmt->execute([
                ':workspace_id' => $this->currentWorkspaceId(),
                ':orcamento_id' => $orcamentoId,
                ':descricao' => $descricao,
                ':valor' => $valor,
            ]);
        }
    }
}
