-- FlowDesk CRM
-- Fase 5 - Tarefas com editor rico, prioridade e tags

ALTER TABLE projeto_tarefas
  ADD COLUMN prioridade VARCHAR(20) NOT NULL DEFAULT 'media' AFTER data_entrega,
  ADD COLUMN tags VARCHAR(255) NULL AFTER prioridade;
