-- FlowDesk CRM
-- Fase 6 - Controle administrativo manual de planos

ALTER TABLE subscriptions
  ADD COLUMN billing_cycle ENUM('monthly', 'annual') NOT NULL DEFAULT 'monthly'
  AFTER status;

