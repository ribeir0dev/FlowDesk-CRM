-- FlowDesk CRM
-- Fase 4 - Onboarding em etapas e dados iniciais opcionais do workspace

ALTER TABLE workspaces
  ADD COLUMN onboarding_tamanho_equipe VARCHAR(30) NULL AFTER objetivo_principal,
  ADD COLUMN onboarding_volume_clientes VARCHAR(30) NULL AFTER onboarding_tamanho_equipe,
  ADD COLUMN onboarding_modulo_inicial VARCHAR(30) NULL AFTER onboarding_volume_clientes,
  ADD COLUMN onboarding_migrar_dados TINYINT(1) NOT NULL DEFAULT 0 AFTER onboarding_modulo_inicial;
