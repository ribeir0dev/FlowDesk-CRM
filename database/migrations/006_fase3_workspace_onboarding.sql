ALTER TABLE workspaces
  ADD COLUMN segmento VARCHAR(80) NULL DEFAULT NULL AFTER nome,
  ADD COLUMN objetivo_principal VARCHAR(80) NULL DEFAULT NULL AFTER segmento,
  ADD COLUMN onboarding_concluido_em DATETIME NULL DEFAULT NULL AFTER objetivo_principal;

UPDATE workspaces
SET onboarding_concluido_em = COALESCE(onboarding_concluido_em, NOW())
WHERE onboarding_concluido_em IS NULL;
