-- FlowDesk CRM
-- Fase 6 - Pix manual por workspace

ALTER TABLE workspaces
  ADD COLUMN pix_chave VARCHAR(160) NULL AFTER onboarding_migrar_dados,
  ADD COLUMN pix_nome VARCHAR(80) NULL AFTER pix_chave,
  ADD COLUMN pix_cidade VARCHAR(60) NULL AFTER pix_nome;
