-- FlowDesk CRM
-- Fase 6 - Links sociais do perfil

ALTER TABLE usuarios
  ADD COLUMN instagram_url VARCHAR(500) NULL AFTER foto_perfil,
  ADD COLUMN behance_url VARCHAR(500) NULL AFTER instagram_url,
  ADD COLUMN website_url VARCHAR(500) NULL AFTER behance_url;
