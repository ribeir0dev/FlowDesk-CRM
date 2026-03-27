<?php
// FlowDesk ClienteModel

require_once __DIR__ . '/../../config/db.php';

class ClienteModel
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
            throw new RuntimeException('Workspace atual nao definido para clientes.');
        }

        return $this->workspaceId;
    }

    private function currentWorkspaceIdOrNull(): ?int
    {
        return $this->workspaceId > 0 ? $this->workspaceId : null;
    }

    private function viewerClienteId(): ?int
    {
        return fd_current_workspace_role() === 'viewer' ? $this->viewerClienteId : null;
    }

    private function viewerHasLinkedClient(): bool
    {
        return fd_current_workspace_role() !== 'viewer' || $this->viewerClienteId() !== null;
    }

    public function criar(array $dados): int
    {
        $sql = '
            INSERT INTO clientes
                (workspace_id, nome, whatsapp, email, status, observacoes, genero, token_publico, criado_em)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->currentWorkspaceId(),
            $dados['nome'],
            $dados['whatsapp'],
            $dados['email'],
            $dados['status'],
            $dados['observacoes'],
            $dados['genero'],
            $dados['token_publico'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = '
            UPDATE clientes
            SET nome = ?, whatsapp = ?, email = ?, status = ?, genero = ?, observacoes = ?, atualizado_em = NOW()
            WHERE id = ? AND workspace_id = ?
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['nome'],
            $dados['whatsapp'],
            $dados['email'],
            $dados['status'],
            $dados['genero'],
            $dados['observacoes'],
            $id,
            $this->currentWorkspaceId(),
        ]);
    }

    public function salvarFotoPerfil(int $id, string $caminhoDb): bool
    {
        $stmt = $this->pdo->prepare('UPDATE clientes SET foto_perfil = ? WHERE id = ? AND workspace_id = ?');
        return $stmt->execute([$caminhoDb, $id, $this->currentWorkspaceId()]);
    }

    public function buscarBloco(int $clienteId, string $slug): ?array
    {
        $sql = '
            SELECT titulo, conteudo, compartilhado
            FROM cliente_blocos
            WHERE cliente_id = ? AND slug = ? AND workspace_id = ?
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId, $slug, $this->currentWorkspaceId()]);
        $bloco = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bloco ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = "
          SELECT id, nome, whatsapp, email, status, genero, observacoes, foto_perfil, criado_em, token_publico
          FROM clientes
          WHERE id = ? AND workspace_id = ?
        ";
        $params = [$id, $this->currentWorkspaceId()];

        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND id = ?';
            $params[] = $this->viewerClienteId();
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cli ?: null;
    }

    public function buscarBlocos(int $clienteId): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
          SELECT slug, titulo, conteudo, compartilhado
          FROM cliente_blocos
          WHERE cliente_id = ? AND workspace_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId, $this->currentWorkspaceId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarFiltrados(array $statusList, string $busca): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        $sql = "
          SELECT
            c.id,
            c.nome,
            c.whatsapp,
            c.email,
            c.foto_perfil,
            c.genero,
            c.status,
            c.criado_em,
            COALESCE((
              SELECT SUM(fe.valor_recebido)
              FROM financeiro_entradas fe
              WHERE fe.cliente_id = c.id
                AND fe.workspace_id = c.workspace_id
            ), 0) AS receita_recebida
          FROM clientes c
          WHERE c.workspace_id = ?
        ";
        $params = [$this->currentWorkspaceId()];

        if ($this->viewerClienteId() !== null) {
            $sql .= " AND c.id = ?";
            $params[] = $this->viewerClienteId();
        }

        if (!in_array('todos', $statusList, true)) {
            $placeholders = implode(',', array_fill(0, count($statusList), '?'));
            $sql .= " AND c.status IN ($placeholders)";
            $params = array_merge($params, $statusList);
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= " AND (LOWER(c.nome) LIKE ? OR LOWER(c.email) LIKE ? OR LOWER(c.whatsapp) LIKE ?)";
            $like = '%' . mb_strtolower($busca) . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql .= " ORDER BY c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarResumoOperacional(int $id): ?array
    {
        if (!$this->viewerHasLinkedClient()) {
            return null;
        }

        $sql = "
          SELECT
            c.id,
            c.nome,
            c.whatsapp,
            c.email,
            c.status,
            c.genero,
            c.observacoes,
            c.foto_perfil,
            c.criado_em,
            c.token_publico,
            COALESCE((
              SELECT COUNT(*)
              FROM projetos p
              WHERE p.cliente_id = c.id
                AND p.workspace_id = c.workspace_id
                AND LOWER(COALESCE(p.status, '')) NOT IN ('concluido', 'finalizado')
            ), 0) AS projetos_abertos,
            COALESCE((
              SELECT COUNT(*)
              FROM orcamentos o
              WHERE o.cliente_id = c.id
                AND o.workspace_id = c.workspace_id
            ), 0) AS total_orcamentos,
            COALESCE((
              SELECT SUM(o.valor_total)
              FROM orcamentos o
              WHERE o.cliente_id = c.id
                AND o.workspace_id = c.workspace_id
            ), 0) AS valor_orcamentos,
            COALESCE((
              SELECT SUM(fe.valor_recebido)
              FROM financeiro_entradas fe
              WHERE fe.cliente_id = c.id
                AND fe.workspace_id = c.workspace_id
            ), 0) AS receita_recebida
          FROM clientes c
          WHERE c.id = ? AND c.workspace_id = ?
        ";
        $params = [$id, $this->currentWorkspaceId()];

        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND c.id = ?';
            $params[] = $this->viewerClienteId();
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cliente ?: null;
    }

    public function listarAtividadesRecentes(int $clienteId, int $limite = 5): array
    {
        if (!$this->viewerHasLinkedClient()) {
            return [];
        }

        if ($this->viewerClienteId() !== null && $clienteId !== $this->viewerClienteId()) {
            return [];
        }

        $limite = max(1, min($limite, 10));
        $workspaceId = $this->currentWorkspaceId();

        $sql = "
          SELECT *
          FROM (
            SELECT
              COALESCE(p.criado_em, p.atualizado_em) AS data_evento,
              'projeto' AS tipo,
              'Novo projeto criado' AS titulo,
              p.nome_projeto AS descricao
            FROM projetos p
            WHERE p.cliente_id = ?
              AND p.workspace_id = ?

            UNION ALL

            SELECT
              o.criado_em AS data_evento,
              'orcamento' AS tipo,
              'Proposta gerada' AS titulo,
              o.servico_principal AS descricao
            FROM orcamentos o
            WHERE o.cliente_id = ?
              AND o.workspace_id = ?

            UNION ALL

            SELECT
              COALESCE(fe.data_lancamento, fe.criado_em) AS data_evento,
              'financeiro' AS tipo,
              'Pagamento recebido' AS titulo,
              fe.descricao AS descricao
            FROM financeiro_entradas fe
            WHERE fe.cliente_id = ?
              AND fe.workspace_id = ?
              AND COALESCE(fe.valor_recebido, 0) > 0
          ) eventos
          WHERE data_evento IS NOT NULL
          ORDER BY data_evento DESC
          LIMIT {$limite}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $clienteId,
            $workspaceId,
            $clienteId,
            $workspaceId,
            $clienteId,
            $workspaceId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorToken(string $token): ?array
    {
        $workspaceId = $this->currentWorkspaceIdOrNull();

        $sql = "
          SELECT c.id, c.nome, c.whatsapp, c.email, c.foto_perfil, c.criado_em, c.workspace_id,
                 COALESCE((
                   SELECT COUNT(*)
                   FROM projetos p
                   WHERE p.cliente_id = c.id
                     AND p.workspace_id = c.workspace_id
                 ), 0) AS total_projetos
          FROM clientes c
          WHERE c.token_publico = ?";

        $params = [$token];
        if ($workspaceId !== null) {
            $sql .= " AND c.workspace_id = ?";
            $params[] = $workspaceId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cli ?: null;
    }

    public function buscarBlocosCompartilhados(int $clienteId, ?int $workspaceId = null): array
    {
        $workspaceId = $workspaceId ?? $this->currentWorkspaceIdOrNull();

        $sql = "
          SELECT slug, titulo, conteudo
          FROM cliente_blocos
          WHERE cliente_id = ? AND compartilhado = 1";
        $params = [$clienteId];

        if ($workspaceId !== null) {
            $sql .= " AND workspace_id = ?";
            $params[] = $workspaceId;
        }

        $sql .= " ORDER BY titulo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
