-- FlowDesk CRM
-- Fase 4 - Preferencias de modulos no menu do usuario

ALTER TABLE usuarios
  ADD COLUMN sidebar_modules_json JSON NULL AFTER preferred_timezone;
