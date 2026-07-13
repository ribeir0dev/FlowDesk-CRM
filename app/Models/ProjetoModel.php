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

    private function clientePertenceAoWorkspace(?int $clienteId): bool
    {
        if ($clienteId === null) {
            return true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM clientes WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$clienteId, $this->currentWorkspaceId()]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function projetoPertenceAoWorkspace(int $projetoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM projetos WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$projetoId, $this->currentWorkspaceId()]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criarProjeto(array $dados): int
    {
        $clienteId = $dados['cliente_id'] !== null ? (int) $dados['cliente_id'] : null;
        if (!$this->clientePertenceAoWorkspace($clienteId)) {
            return 0;
        }

        $sql = '
            INSERT INTO projetos
                (workspace_id, cliente_id, nome_projeto, tipo_projeto, descricao,
                 data_inicio, data_entrega, status, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->currentWorkspaceId(),
            $clienteId,
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

        $sql = '
            SELECT t.id, t.projeto_id, t.titulo, t.descricao, t.coluna, t.data_entrega, t.prioridade, t.tags
            FROM projeto_tarefas t
            INNER JOIN projetos p ON p.id = t.projeto_id AND p.workspace_id = t.workspace_id
            WHERE t.id = ? AND t.workspace_id = ?
        ';
        $params = [$id, $this->currentWorkspaceId()];
        if ($this->viewerClienteId() !== null) {
            $sql .= ' AND p.cliente_id = ?';
            $params[] = $this->viewerClienteId();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($t) {
            $t['checklist'] = $this->getChecklistByTaskId((int) $t['id']);
            $t['members'] = $this->getMembersByTaskId((int) $t['id']);
            $t['attachments'] = $this->getAttachmentsByTaskId((int) $t['id']);
            $t['comments'] = $this->getCommentsByTaskId((int) $t['id']);
        }
        return $t ?: null;
    }

    public function salvarTarefa(array $dados): bool
    {
        $workspaceId = $this->currentWorkspaceId();
        $projetoId = (int) ($dados['projeto_id'] ?? 0);
        if ($projetoId <= 0 || !$this->projetoPertenceAoWorkspace($projetoId)) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            if (!empty($dados['tarefa_id'])) {
                $sql = '
                    UPDATE projeto_tarefas
                    SET titulo = ?, descricao = ?, coluna = ?, data_entrega = ?, prioridade = ?, tags = ?, atualizado_em = NOW()
                    WHERE id = ? AND projeto_id = ? AND workspace_id = ?
                ';
                $stmt = $this->pdo->prepare($sql);
                $ok = $stmt->execute([
                    $dados['titulo'],
                    $dados['descricao'],
                    $dados['coluna'],
                    $dados['data_entrega'],
                    $dados['prioridade'],
                    $dados['tags'],
                    $dados['tarefa_id'],
                    $projetoId,
                    $workspaceId,
                ]);

                if (!$ok) {
                    throw new RuntimeException('Nao foi possivel atualizar a tarefa.');
                }

                $tarefaId = (int) $dados['tarefa_id'];
            } else {
                $sql = '
                    INSERT INTO projeto_tarefas
                        (workspace_id, projeto_id, titulo, descricao, coluna, ordem, data_entrega, prioridade, tags, criado_em)
                    VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())
                ';
                $stmt = $this->pdo->prepare($sql);
                $ok = $stmt->execute([
                    $workspaceId,
                    $projetoId,
                    $dados['titulo'],
                    $dados['descricao'],
                    $dados['coluna'],
                    $dados['data_entrega'],
                    $dados['prioridade'],
                    $dados['tags'],
                ]);

                if (!$ok) {
                    throw new RuntimeException('Nao foi possivel criar a tarefa.');
                }

                $tarefaId = (int) $this->pdo->lastInsertId();
            }

            $this->replaceChecklistItems($tarefaId, $dados['checklist'] ?? []);
            $this->replaceTaskMembers($tarefaId, $dados['members'] ?? []);
            $this->replaceTaskAttachments($tarefaId, $dados['attachments'] ?? []);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
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
        $clienteId = $dados['cliente_id'] !== null ? (int) $dados['cliente_id'] : null;
        if (!$this->clientePertenceAoWorkspace($clienteId)) {
            return false;
        }

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
            $clienteId,
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
          SELECT t.*,
                 (
                    SELECT COUNT(*)
                    FROM projeto_tarefa_checklist_items ci
                    WHERE ci.tarefa_id = t.id
                 ) AS checklist_total,
                 (
                    SELECT COUNT(*)
                    FROM projeto_tarefa_checklist_items ci
                    WHERE ci.tarefa_id = t.id AND ci.concluido = 1
                 ) AS checklist_done
          FROM projeto_tarefas
          t WHERE projeto_id = ? AND workspace_id = ?
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

    private function getChecklistByTaskId(int $tarefaId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, texto, concluido, ordem
            FROM projeto_tarefa_checklist_items
            WHERE tarefa_id = ?
            ORDER BY ordem ASC, id ASC
        ');
        $stmt->execute([$tarefaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function replaceChecklistItems(int $tarefaId, array $items): void
    {
        $delete = $this->pdo->prepare('DELETE FROM projeto_tarefa_checklist_items WHERE tarefa_id = ?');
        $delete->execute([$tarefaId]);

        $cleanItems = [];
        foreach ($items as $index => $item) {
            $texto = trim((string) ($item['texto'] ?? ''));
            if ($texto === '') {
                continue;
            }

            $cleanItems[] = [
                'texto' => mb_substr($texto, 0, 255),
                'concluido' => !empty($item['concluido']) ? 1 : 0,
                'ordem' => count($cleanItems),
            ];
        }

        if (!$cleanItems) {
            return;
        }

        $insert = $this->pdo->prepare('
            INSERT INTO projeto_tarefa_checklist_items (tarefa_id, texto, concluido, ordem, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ');

        foreach ($cleanItems as $item) {
            $insert->execute([
                $tarefaId,
                $item['texto'],
                $item['concluido'],
                $item['ordem'],
            ]);
        }
    }

    private function getMembersByTaskId(int $tarefaId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT tm.user_id, u.nome, u.email, u.foto_perfil
            FROM projeto_tarefa_members tm
            INNER JOIN usuarios u ON u.id = tm.user_id
            WHERE tm.tarefa_id = ?
            ORDER BY u.nome ASC
        ');
        $stmt->execute([$tarefaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function replaceTaskMembers(int $tarefaId, array $members): void
    {
        $delete = $this->pdo->prepare('DELETE FROM projeto_tarefa_members WHERE tarefa_id = ?');
        $delete->execute([$tarefaId]);

        $memberIds = array_values(array_unique(array_filter(array_map(
            static fn($value) => (int) $value,
            $members
        ))));

        if (!$memberIds) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $memberStmt = $this->pdo->prepare("
            SELECT user_id
            FROM workspace_members
            WHERE workspace_id = ?
              AND user_id IN ($placeholders)
        ");
        $memberStmt->execute(array_merge([$this->currentWorkspaceId()], $memberIds));
        $allowedMemberIds = array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$allowedMemberIds) {
            return;
        }

        $insert = $this->pdo->prepare('
            INSERT INTO projeto_tarefa_members (tarefa_id, user_id, criado_em)
            VALUES (?, ?, NOW())
        ');

        foreach (array_slice($allowedMemberIds, 0, 8) as $userId) {
            $insert->execute([$tarefaId, $userId]);
        }
    }

    private function getAttachmentsByTaskId(int $tarefaId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, label, url, ordem
            FROM projeto_tarefa_attachments
            WHERE tarefa_id = ?
            ORDER BY ordem ASC, id ASC
        ');
        $stmt->execute([$tarefaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function replaceTaskAttachments(int $tarefaId, array $attachments): void
    {
        $delete = $this->pdo->prepare('DELETE FROM projeto_tarefa_attachments WHERE tarefa_id = ?');
        $delete->execute([$tarefaId]);

        $clean = [];
        foreach ($attachments as $item) {
            if (!is_array($item)) {
                continue;
            }

            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $clean[] = [
                'label' => mb_substr($label !== '' ? $label : (parse_url($url, PHP_URL_HOST) ?: 'Anexo'), 0, 140),
                'url' => mb_substr($url, 0, 500),
                'ordem' => count($clean),
            ];
        }

        if (!$clean) {
            return;
        }

        $insert = $this->pdo->prepare('
            INSERT INTO projeto_tarefa_attachments (tarefa_id, label, url, ordem, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ');

        foreach (array_slice($clean, 0, 12) as $item) {
            $insert->execute([$tarefaId, $item['label'], $item['url'], $item['ordem']]);
        }
    }

    private function getCommentsByTaskId(int $tarefaId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT tc.id, tc.comentario, tc.criado_em, u.id AS user_id, u.nome, u.email, u.foto_perfil
            FROM projeto_tarefa_comments tc
            INNER JOIN projeto_tarefas t ON t.id = tc.tarefa_id
            INNER JOIN usuarios u ON u.id = tc.user_id
            WHERE tc.tarefa_id = ?
              AND t.workspace_id = ?
            ORDER BY tc.criado_em DESC, tc.id DESC
        ');
        $stmt->execute([$tarefaId, $this->currentWorkspaceId()]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($comments as &$comment) {
            $comment['criado_em_formatado'] = fd_format_datetime((string) $comment['criado_em']);
        }

        return $comments;
    }

    public function getComentarioTarefaById(int $commentId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT tc.id, tc.tarefa_id, tc.user_id, tc.comentario, tc.criado_em, u.nome, u.email, u.foto_perfil
            FROM projeto_tarefa_comments tc
            INNER JOIN projeto_tarefas t ON t.id = tc.tarefa_id
            INNER JOIN usuarios u ON u.id = tc.user_id
            WHERE tc.id = ?
              AND t.workspace_id = ?
            LIMIT 1
        ');
        $stmt->execute([$commentId, $this->currentWorkspaceId()]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comment) {
            return null;
        }
        $comment['criado_em_formatado'] = fd_format_datetime((string) $comment['criado_em']);
        return $comment;
    }

    public function adicionarComentarioTarefa(int $tarefaId, int $userId, string $comentario): ?array
    {
        if (!$this->tarefaPertenceAoWorkspace($tarefaId)) {
            return null;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO projeto_tarefa_comments (tarefa_id, user_id, comentario, criado_em)
            VALUES (?, ?, ?, NOW())
        ');
        $ok = $stmt->execute([$tarefaId, $userId, $comentario]);
        if (!$ok) {
            return null;
        }

        $commentId = (int) $this->pdo->lastInsertId();
        $fetch = $this->pdo->prepare('
            SELECT tc.id, tc.comentario, tc.criado_em, u.id AS user_id, u.nome, u.email, u.foto_perfil
            FROM projeto_tarefa_comments tc
            INNER JOIN usuarios u ON u.id = tc.user_id
            WHERE tc.id = ? AND tc.tarefa_id = ?
            LIMIT 1
        ');
        $fetch->execute([$commentId, $tarefaId]);
        $comment = $fetch->fetch(PDO::FETCH_ASSOC);
        if (!$comment) {
            return null;
        }

        $comment['criado_em_formatado'] = fd_format_datetime((string) $comment['criado_em']);
        return $comment;
    }

    public function atualizarComentarioTarefa(int $commentId, string $comentario): ?array
    {
        $stmt = $this->pdo->prepare('
            UPDATE projeto_tarefa_comments
            SET comentario = ?, atualizado_em = NOW()
            WHERE id = ?
              AND tarefa_id IN (
                  SELECT id FROM projeto_tarefas WHERE workspace_id = ?
              )
        ');
        if (!$stmt->execute([$comentario, $commentId, $this->currentWorkspaceId()])) {
            return null;
        }

        return $this->getComentarioTarefaById($commentId);
    }

    public function excluirComentarioTarefa(int $commentId): bool
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM projeto_tarefa_comments
            WHERE id = ?
              AND tarefa_id IN (
                  SELECT id FROM projeto_tarefas WHERE workspace_id = ?
              )
            LIMIT 1
        ');
        return $stmt->execute([$commentId, $this->currentWorkspaceId()]);
    }

    private function tarefaPertenceAoWorkspace(int $tarefaId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM projeto_tarefas WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$tarefaId, $this->currentWorkspaceId()]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
