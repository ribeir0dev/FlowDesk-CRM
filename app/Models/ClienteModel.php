<?php
// inc/models/ClienteModel.php

require_once __DIR__ . '/../../config/db.php';

class ClienteModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function criar(array $dados): int
    {
        $sql = '
            INSERT INTO clientes
                (nome, whatsapp, email, status, observacoes, genero, token_publico, criado_em)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
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
            WHERE id = ?
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
        ]);
    }

    public function salvarFotoPerfil(int $id, string $caminhoDb): bool
    {
        $stmt = $this->pdo->prepare('UPDATE clientes SET foto_perfil = ? WHERE id = ?');
        return $stmt->execute([$caminhoDb, $id]);
    }

    public function buscarBloco(int $clienteId, string $slug): ?array
    {
        $sql = '
            SELECT titulo, conteudo, compartilhado
            FROM cliente_blocos
            WHERE cliente_id = ? AND slug = ?
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId, $slug]);
        $bloco = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bloco ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
          SELECT id, nome, whatsapp, email, status, foto_perfil, criado_em, token_publico
          FROM clientes
          WHERE id = ?
          LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cli ?: null;
    }

    public function buscarBlocos(int $clienteId): array
    {
        $sql = "
          SELECT slug, titulo, conteudo, compartilhado
          FROM cliente_blocos
          WHERE cliente_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarFiltrados(array $statusList, string $busca): array
    {
        $sql = "
      SELECT id, nome, whatsapp, email, foto_perfil, genero, status, criado_em
      FROM clientes
      WHERE 1=1
    ";
        $params = [];

        // Se vier 'todos', não filtra por status
        if (!in_array('todos', $statusList, true)) {
            $placeholders = implode(',', array_fill(0, count($statusList), '?'));
            $sql .= " AND status IN ($placeholders)";
            $params = array_merge($params, $statusList);
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $sql .= " AND (LOWER(nome) LIKE ? OR LOWER(email) LIKE ? OR LOWER(whatsapp) LIKE ?)";
            $like = '%' . mb_strtolower($busca) . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function buscarPorToken(string $token): ?array
    {
        $sql = "
      SELECT id, nome, whatsapp, email, foto_perfil, criado_em
      FROM clientes
      WHERE token_publico = ?
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cli ?: null;
    }

    public function buscarBlocosCompartilhados(int $clienteId): array
    {
        $sql = "
      SELECT slug, titulo, conteudo
      FROM cliente_blocos
      WHERE cliente_id = ? AND compartilhado = 1
      ORDER BY titulo
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
