<?php
// FlowDesk ProjetoModel

require_once __DIR__ . '/../../config/db.php';

class ProjetoModel
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
            throw new RuntimeException('Workspace atual nao definido para projetos.');
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

    public function criarProjeto(array $dados): int
    {
        $sql = '
            INSERT INTO projetos
                (workspace_id, cliente_id, nome_projeto, tipo_projeto, descricao,
                 data_inicio, data_entrega, status, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->currentWorkspaceId(),
            $dados['cliente_id'],
            $dados['nome_projeto'],
            $dados['tipo_projeto'],
            $dados['descricao'],
            $dados['data_inicio'],
            $dados['data_entrega'],
            $dados['status'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function excluirProjeto(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM projetos WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function getTarefaById(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT id, projeto_id, titulo, descricao, coluna, data_entrega
            FROM projeto_tarefas
            WHERE id = ? AND workspace_id = ?
        ');
        $stmt->execute([$id, $this->currentWorkspaceId()]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        return $t ?: null;
    }

    public function salvarTarefa(array $dados): bool
    {
        if (!empty($dados['tarefa_id'])) {
            $sql = '
                UPDATE projeto_tarefas
                SET titulo = ?, descricao = ?, coluna = ?, data_entrega = ?, atualizado_em = NOW()
                WHERE id = ? AND projeto_id = ? AND workspace_id = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $dados['titulo'],
                $dados['descricao'],
                $dados['coluna'],
                $dados['data_entrega'],
                $dados['tarefa_id'],
                $dados['projeto_id'],
                $this->currentWorkspaceId(),
            ]);
        }

        $sql = '
            INSERT INTO projeto_tarefas
                (workspace_id, projeto_id, titulo, descricao, coluna, ordem, data_entrega, criado_em)
            VALUES (?, ?, ?, ?, ?, 0, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $this->currentWorkspaceId(),
            $dados['projeto_id'],
            $dados['titulo'],
            $dados['descricao'],
            $dados['coluna'],
            $dados['data_entrega'],
        ]);
    }

    public function moverTarefa(int $tarefaId, string $coluna): bool
    {
        $sql = '
            UPDATE projeto_tarefas
            SET coluna = ?, atualizado_em = NOW()
            WHERE id = ? AND workspace_id = ?
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$coluna, $tarefaId, $this->currentWorkspaceId()]);
    }

    public function excluirTarefa(int $tarefaId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM projeto_tarefas WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$tarefaId, $this->currentWorkspaceId()]);
    }

    public function atualizarProjeto(int $id, array $dados): bool
    {
        $sql = '
        UPDATE projetos
        SET nome_projeto = ?, tipo_projeto = ?, descricao = ?,
            cliente_id = ?, data_inicio = ?, data_entrega = ?, status = ?, atualizado_em = NOW()
        WHERE id = ? AND workspace_id = ?
    ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['nome_projeto'],
            $dados['tipo_projeto'],
            $dados['descricao'],
            $dados['cliente_id'],
            $dados['data_inicio'],
            $dados['data_entrega'],
            $dados['status'],
            $id,
            $this->currentWorkspaceId(),
        ]);
    }

    public function getProjetoById(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = 'SELECT * FROM projetos WHERE id = ? AND workspace_id = ?';
        $params = [$id, $this->currentWorkspaceId()];

        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        return $p ?: null;
    }

    public function buscarComCliente(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = "
          SELECT p.*, c.nome AS cliente_nome
          FROM projetos p
          LEFT JOIN clientes c ON c.id = p.cliente_id
          WHERE p.id = ? AND p.workspace_id = ? AND (c.id IS NULL OR c.workspace_id = ?)
        ";
        $workspaceId = $this->currentWorkspaceId();
        $params = [$id, $workspaceId, $workspaceId];

        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND p.cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proj ?: null;
    }

    public function listarTarefasPorProjeto(int $projetoId): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
          SELECT *
          FROM projeto_tarefas
          WHERE projeto_id = ? AND workspace_id = ?
          ORDER BY coluna, ordem, id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projetoId, $this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodosComCliente(): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
      SELECT p.id,
             p.cliente_id,
             p.nome_projeto,
             p.tipo_projeto,
             p.descricao,
             p.data_inicio,
             p.data_entrega,
             p.status,
             c.nome AS cliente_nome
      FROM projetos p
      LEFT JOIN clientes c ON c.id = p.cliente_id
      WHERE p.workspace_id = :workspace_id
        AND (c.id IS NULL OR c.workspace_id = :workspace_id)
        ";
        $params = [':workspace_id' => $this->currentWorkspaceId()];

        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND p.cliente_id = :viewer_cliente_id';
            $params[':viewer_cliente_id'] = $this->viewerClienteId();
        }

        $sql .= ' ORDER BY p.data_inicio DESC, p.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
