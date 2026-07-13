-- FlowDesk CRM
-- Fase 5 - Preview visual opcional no modulo Codigos

ALTER TABLE codigos
  ADD COLUMN preview_image VARCHAR(255) NULL AFTER conteudo;
