<?php
// app/Models/OrcamentoModel.php

require_once __DIR__ . '/../../config/db.php';

class OrcamentoModel
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Lista orçamentos com nome do cliente
   */
public function listarComClientes(array $statusList = ['todos']): array
{
    $sql = "
        SELECT
          o.id,
          o.codigo,
          c.nome AS cliente_nome,
          o.servico_principal,
          o.forma_pagamento,
          o.status,
          o.valor_total
        FROM orcamentos o
        JOIN clientes c ON c.id = o.cliente_id
        WHERE 1=1
    ";
    $params = [];

    // Se não tiver "todos", filtra pelos status marcados
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

  /**
   * Lista todos os clientes para popular o select do modal
   */
  public function listarClientes(): array
  {
    $sql = "
          SELECT id, nome, telefone
          FROM clientes
          ORDER BY nome ASC
        ";
    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  

  /**
   * Cria um novo orçamento
   */
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

    // gera um código simples tipo 0058 (você pode mudar a lógica)
    $stmt = $this->pdo->query('SELECT IFNULL(MAX(id),0) + 1 AS prox FROM orcamentos');
    $proxId = (int) $stmt->fetchColumn();
    $codigo = str_pad((string) $proxId, 4, '0', STR_PAD_LEFT);

    $sql = "
          INSERT INTO orcamentos
            (codigo, cliente_id, servico_principal, descricao_servico,
             forma_pagamento, status, valor_total, criado_em)
          VALUES
            (:codigo, :cliente_id, :servico_principal, :descricao_servico,
             :forma_pagamento, :status, :valor_total, NOW())
        ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':codigo' => $codigo,
      ':cliente_id' => $clienteId,
      ':servico_principal' => $servicoPrincipal,
      ':descricao_servico' => $descricaoServico,
      ':forma_pagamento' => $formaPagamento,
      ':status' => $status,
      ':valor_total' => $valorTotal,
    ]);

    $orcamentoId = (int) $this->pdo->lastInsertId();

    // itens (tabela orcamento_itens)
    if (!empty($itens)) {
      $sqlItem = "
              INSERT INTO orcamento_itens
                (orcamento_id, descricao, valor)
              VALUES
                (:orcamento_id, :descricao, :valor)
            ";
      $stmtItem = $this->pdo->prepare($sqlItem);

      foreach ($itens as $item) {
        $descricao = trim($item['descricao'] ?? '');
        $valor = (float) str_replace(',', '.', preg_replace('/\./', '', $item['valor'] ?? '0'));

        // permite zero, bloqueia só negativo
        if ($descricao === '' || $valor < 0) {
          continue;
        }

        $stmtItem->execute([
          ':orcamento_id' => $orcamentoId,
          ':descricao' => $descricao,
          ':valor' => $valor,
        ]);
      }
    }

    $this->pdo->commit();

    return $orcamentoId;
  }

  /**
   * Excluir orçamento (e itens)
   */
  public function excluir(int $id): bool
  {
    $this->pdo->beginTransaction();

    $stmtItens = $this->pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = ?');
    $stmtItens->execute([$id]);

    $stmt = $this->pdo->prepare('DELETE FROM orcamentos WHERE id = ?');
    $ok = $stmt->execute([$id]);

    $this->pdo->commit();

    return $ok;
  }

  public function buscarPorId(int $id): ?array
  {
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
      WHERE o.id = ?
      LIMIT 1
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id]);
    $orc = $stmt->fetch(PDO::FETCH_ASSOC);

    return $orc ?: null;
  }

  public function buscarItens(int $orcamentoId): array
  {
    $sql = "
      SELECT id, descricao, valor
      FROM orcamento_itens
      WHERE orcamento_id = ?
      ORDER BY id ASC
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$orcamentoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }


}


