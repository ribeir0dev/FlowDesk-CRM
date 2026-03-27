<?php
// app/Models/HospedagemModel.php

require_once __DIR__ . '/../../config/db.php';

class HospedagemModel
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
            throw new RuntimeException('Workspace atual nao definido para hospedagens.');
        }

        return $this->workspaceId;
    }

    public function criar(string $nome, string $tipo, string $dataInicio, string $dataFim): bool
    {
        $sql = '
            INSERT INTO hospedagens (workspace_id, nome, tipo, data_inicio, data_fim, criado_em)
            VALUES (?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->currentWorkspaceId(), $nome, $tipo, $dataInicio, $dataFim]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM hospedagens WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }
    public function listarTodas(): array
    {
        $sql = "
      SELECT id, nome, tipo, data_inicio, data_fim
      FROM hospedagens
      WHERE workspace_id = ?
      ORDER BY data_fim ASC, nome ASC
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}

