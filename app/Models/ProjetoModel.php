<?php
// inc/models/ProjetoModel.php

require_once __DIR__ . '/../../config/db.php';

class ProjetoModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* PROJETOS */

    public function criarProjeto(array $dados): int
    {
        $sql = '
            INSERT INTO projetos
                (cliente_id, nome_projeto, tipo_projeto, descricao,
                 data_inicio, data_entrega, status, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
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
        $stmt = $this->pdo->prepare('DELETE FROM projetos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /* TAREFAS */

    public function getTarefaById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, projeto_id, titulo, descricao, coluna, data_entrega
            FROM projeto_tarefas
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        return $t ?: null;
    }

    public function salvarTarefa(array $dados): bool
    {
        if (!empty($dados['tarefa_id'])) {
            $sql = '
                UPDATE projeto_tarefas
                SET titulo = ?, descricao = ?, coluna = ?, data_entrega = ?, atualizado_em = NOW()
                WHERE id = ? AND projeto_id = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $dados['titulo'],
                $dados['descricao'],
                $dados['coluna'],
                $dados['data_entrega'],
                $dados['tarefa_id'],
                $dados['projeto_id'],
            ]);
        }

        $sql = '
            INSERT INTO projeto_tarefas
                (projeto_id, titulo, descricao, coluna, ordem, data_entrega, criado_em)
            VALUES (?, ?, ?, ?, 0, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
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
            WHERE id = ?
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$coluna, $tarefaId]);
    }

    public function excluirTarefa(int $tarefaId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM projeto_tarefas WHERE id = ?');
        return $stmt->execute([$tarefaId]);
    }

    public function atualizarProjeto(int $id, array $dados): bool
    {
        $sql = '
        UPDATE projetos
        SET nome_projeto = ?, tipo_projeto = ?, descricao = ?,
            cliente_id = ?, data_inicio = ?, data_entrega = ?, status = ?, atualizado_em = NOW()
        WHERE id = ?
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
        ]);
    }
    public function getProjetoById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM projetos WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        return $p ?: null;
    }

    public function buscarComCliente(int $id): ?array
    {
        $sql = "
          SELECT p.*, c.nome AS cliente_nome
          FROM projetos p
          LEFT JOIN clientes c ON c.id = p.cliente_id
          WHERE p.id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proj ?: null;
    }

    public function listarTarefasPorProjeto(int $projetoId): array
    {
        $sql = "
          SELECT *
          FROM projeto_tarefas
          WHERE projeto_id = ?
          ORDER BY coluna, ordem, id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodosComCliente(): array
    {
        $sql = "
      SELECT p.id,
             p.nome_projeto,
             p.tipo_projeto,
             p.data_inicio,
             p.data_entrega,
             p.status,
             c.nome AS cliente_nome
      FROM projetos p
      LEFT JOIN clientes c ON c.id = p.cliente_id
      ORDER BY p.data_inicio DESC, p.id DESC
    ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}


