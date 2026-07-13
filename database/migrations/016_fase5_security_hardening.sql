-- FlowDesk CRM
-- Fase 5 - Security hardening
-- Rode apenas apos validar se os indices ainda nao existem no ambiente.

ALTER TABLE orcamentos
  ADD UNIQUE KEY uk_orcamentos_workspace_codigo (workspace_id, codigo);

ALTER TABLE workspace_invites
  ADD KEY idx_workspace_invites_limit_gate (workspace_id, status, expires_at);

ALTER TABLE codigos
  ADD KEY idx_codigos_workspace_updated (workspace_id, atualizado_em);
