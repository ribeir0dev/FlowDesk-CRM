<?php

require_once __DIR__ . '/../../config/db.php';

class CodigoModel
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
            throw new RuntimeException('Workspace atual nao definido para codigos.');
        }

        return $this->workspaceId;
    }

    public function listarFiltros(): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT categoria FROM codigos WHERE workspace_id = ? ORDER BY categoria ASC");
        $stmt->execute([$this->currentWorkspaceId()]);

        return array_values(array_filter(array_map(static fn ($row) => trim((string) ($row['categoria'] ?? '')), $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [])));
    }

    public function listarTodos(array $filters = []): array
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $category = trim((string) ($filters['categoria'] ?? ''));
        $difficulty = trim((string) ($filters['dificuldade'] ?? ''));
        $sort = trim((string) ($filters['sort'] ?? 'recentes'));

        $sql = "
            SELECT c.*, u.nome AS autor_nome
            FROM codigos c
            INNER JOIN usuarios u ON u.id = c.user_id
            WHERE c.workspace_id = :workspace_id
        ";

        $params = [':workspace_id' => $this->currentWorkspaceId()];

        if ($search !== '') {
            $sql .= " AND (c.titulo LIKE :search OR c.descricao LIKE :search OR c.categoria LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($category !== '') {
            $sql .= " AND c.categoria = :categoria";
            $params[':categoria'] = $category;
        }

        if ($difficulty !== '') {
            $sql .= " AND c.dificuldade = :dificuldade";
            $params[':dificuldade'] = $difficulty;
        }

        $sql .= match ($sort) {
            'mais_copiados' => ' ORDER BY c.copias DESC, c.atualizado_em DESC',
            'favoritos' => ' ORDER BY c.favorito DESC, c.atualizado_em DESC',
            default => ' ORDER BY c.atualizado_em DESC, c.id DESC',
        };

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT c.*, u.nome AS autor_nome\n            FROM codigos c\n            INNER JOIN usuarios u ON u.id = c.user_id\n            WHERE c.id = ? AND c.workspace_id = ?\n            LIMIT 1\n        ");
        $stmt->execute([$id, $this->currentWorkspaceId()]);
        $codigo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $codigo ?: null;
    }

    public function criar(array $dados): bool
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO codigos (workspace_id, user_id, titulo, descricao, categoria, tipo, dificuldade, instrucoes, conteudo, criado_em, atualizado_em)\n            VALUES (:workspace_id, :user_id, :titulo, :descricao, :categoria, :tipo, :dificuldade, :instrucoes, :conteudo, NOW(), NOW())\n        ");

        $ok = $stmt->execute([
            ':workspace_id' => $this->currentWorkspaceId(),
            ':user_id' => (int) ($dados['user_id'] ?? 0),
            ':titulo' => trim((string) ($dados['titulo'] ?? '')),
            ':descricao' => trim((string) ($dados['descricao'] ?? '')) ?: null,
            ':categoria' => trim((string) ($dados['categoria'] ?? 'Sem categoria')),
            ':tipo' => trim((string) ($dados['tipo'] ?? 'Snippet')) ?: 'Snippet',
            ':dificuldade' => trim((string) ($dados['dificuldade'] ?? 'basico')) ?: 'basico',
            ':instrucoes' => trim((string) ($dados['instrucoes'] ?? '')) ?: null,
            ':conteudo' => trim((string) ($dados['conteudo'] ?? '')),
        ]);

        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new RuntimeException('Falha ao salvar codigo: ' . implode(' | ', array_filter($errorInfo ?: [])));
        }

        return true;
    }

    public function alternarFavorito(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE codigos SET favorito = IF(favorito = 1, 0, 1), atualizado_em = NOW() WHERE id = ? AND workspace_id = ?");
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function registrarCopia(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE codigos SET copias = copias + 1, atualizado_em = NOW() WHERE id = ? AND workspace_id = ?");
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM codigos WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$id, $this->currentWorkspaceId()]);
    }
}
