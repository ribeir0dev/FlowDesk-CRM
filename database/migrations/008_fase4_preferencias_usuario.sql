-- FlowDesk CRM
-- Fase 4 - Preferencias de usuario e central de configuracoes da conta

ALTER TABLE usuarios
  ADD COLUMN preferred_theme ENUM('dark','light') NOT NULL DEFAULT 'dark' AFTER foto_perfil,
  ADD COLUMN preferred_locale VARCHAR(10) NOT NULL DEFAULT 'pt-BR' AFTER preferred_theme,
  ADD COLUMN preferred_timezone VARCHAR(80) NOT NULL DEFAULT 'America/Sao_Paulo' AFTER preferred_locale;
