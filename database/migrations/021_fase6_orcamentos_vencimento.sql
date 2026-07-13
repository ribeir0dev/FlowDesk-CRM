-- FlowDesk CRM
-- Fase 6 - Vencimento de orcamentos

ALTER TABLE orcamentos
  ADD COLUMN vencimento DATE NULL AFTER valor_total,
  ADD KEY idx_orcamentos_vencimento (workspace_id, vencimento);

UPDATE orcamentos
SET vencimento = DATE(criado_em)
WHERE vencimento IS NULL;
