-- FlowDesk CRM
-- Fase 6 - Propostas comerciais V2

ALTER TABLE orcamentos
  MODIFY codigo VARCHAR(40) NOT NULL,
  ADD COLUMN public_code VARCHAR(32) NULL AFTER codigo,
  ADD COLUMN data_emissao DATE NULL AFTER cliente_id,
  ADD COLUMN prazo_estimado_dias SMALLINT UNSIGNED NOT NULL DEFAULT 7 AFTER vencimento,
  ADD COLUMN parcelas TINYINT UNSIGNED NULL AFTER forma_pagamento,
  ADD COLUMN desconto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER valor_total,
  ADD UNIQUE KEY uq_orcamentos_public_code (public_code),
  ADD KEY idx_orcamentos_listagem (workspace_id, status, vencimento, criado_em);

ALTER TABLE orcamento_itens
  ADD COLUMN quantidade DECIMAL(10,2) NOT NULL DEFAULT 1.00 AFTER descricao,
  ADD COLUMN valor_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER quantidade,
  ADD COLUMN desconto_percentual DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER valor_unitario,
  ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER desconto_percentual;

UPDATE orcamentos
SET data_emissao = DATE(criado_em),
    atualizado_em = COALESCE(atualizado_em, criado_em),
    public_code = COALESCE(
      public_code,
      LOWER(SUBSTRING(SHA2(CONCAT('flowdesk-proposta:', workspace_id, ':', id), 256), 1, 24))
    ),
    status = CASE
      WHEN status IN ('Aceito', 'Aprovado', 'Ativou') THEN 'Aprovada'
      WHEN status = 'Recusado' THEN 'Recusada'
      WHEN status = 'Vencido' THEN 'Vencida'
      ELSE 'Aguardando Aprovação'
    END
WHERE data_emissao IS NULL OR atualizado_em IS NULL OR public_code IS NULL
   OR status IN ('Aceito', 'Aprovado', 'Ativou', 'Recusado', 'Vencido', 'Enviado', 'Sem Resposta');

UPDATE orcamento_itens
SET valor_unitario = valor,
    subtotal = valor
WHERE valor_unitario = 0 AND valor > 0;
