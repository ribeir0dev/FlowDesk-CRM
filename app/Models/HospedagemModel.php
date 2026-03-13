<?php
// inc/models/HospedagemModel.php

require_once __DIR__ . '/../../config/db.php';

class HospedagemModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function criar(string $nome, string $tipo, string $dataInicio, string $dataFim): bool
    {
        $sql = '
            INSERT INTO hospedagens (nome, tipo, data_inicio, data_fim, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ';
        $stmt = $this->pdo->prepare($sql);        // uso básico de prepared statements com PDO.[web:96][web:104]
        return $stmt->execute([$nome, $tipo, $dataInicio, $dataFim]);  // INSERT com parâmetros posicionais.[web:96]
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM hospedagens WHERE id = ?');  // DELETE simples com PDO.[web:100][web:103]
        return $stmt->execute([$id]);
    }
    public function listarTodas(): array
    {
        $sql = "
      SELECT id, nome, tipo, data_inicio, data_fim
      FROM hospedagens
      ORDER BY data_fim ASC, nome ASC
    ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
