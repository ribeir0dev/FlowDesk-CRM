-- FlowDesk CRM
-- Fase 5 - Planos, limites e base inicial de billing

ALTER TABLE plans
  ADD COLUMN orcamentos_limit INT UNSIGNED NULL AFTER projects_limit,
  ADD COLUMN premium_features_json JSON NULL AFTER storage_limit_mb,
  ADD COLUMN trial_days INT UNSIGNED NOT NULL DEFAULT 14 AFTER premium_features_json,
  ADD COLUMN grace_days INT UNSIGNED NOT NULL DEFAULT 3 AFTER trial_days,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER grace_days,
  ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active;

CREATE TABLE IF NOT EXISTS invoices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'manual',
  provider_invoice_id VARCHAR(120) DEFAULT NULL,
  status ENUM('draft','open','paid','void','past_due','failed') NOT NULL DEFAULT 'draft',
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  due_at DATETIME DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_invoices_workspace (workspace_id),
  KEY idx_invoices_subscription (subscription_id),
  CONSTRAINT fk_invoices_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_invoices_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS billing_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED DEFAULT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'manual',
  event_type VARCHAR(100) NOT NULL,
  external_id VARCHAR(120) DEFAULT NULL,
  payload JSON DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_billing_events_workspace (workspace_id),
  KEY idx_billing_events_subscription (subscription_id),
  CONSTRAINT fk_billing_events_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_billing_events_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans (
  nome,
  code,
  preco,
  users_limit,
  clients_limit,
  projects_limit,
  orcamentos_limit,
  storage_limit_mb,
  premium_features_json,
  trial_days,
  grace_days,
  is_active,
  sort_order
) VALUES
  (
    'Free',
    'free',
    0.00,
    1,
    2,
    2,
    5,
    512,
    JSON_ARRAY(),
    14,
    3,
    1,
    10
  ),
  (
    'Starter',
    'starter',
    9.90,
    3,
    5,
    5,
    15,
    2048,
    JSON_ARRAY('portal_cliente', 'codigos'),
    14,
    3,
    1,
    20
  ),
  (
    'Pro',
    'pro',
    49.90,
    10,
    25,
    NULL,
    NULL,
    4096,
    JSON_ARRAY('portal_cliente', 'codigos', 'automacoes_basicas', 'relatorios_premium'),
    14,
    5,
    1,
    30
  ),
  (
    'Enterprise',
    'enterprise',
    209.90,
    NULL,
    NULL,
    NULL,
    NULL,
    20480,
    JSON_ARRAY('portal_cliente', 'codigos', 'automacoes_basicas', 'relatorios_premium', 'limites_configuraveis'),
    14,
    7,
    1,
    40
  )
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  preco = VALUES(preco),
  users_limit = VALUES(users_limit),
  clients_limit = VALUES(clients_limit),
  projects_limit = VALUES(projects_limit),
  orcamentos_limit = VALUES(orcamentos_limit),
  storage_limit_mb = VALUES(storage_limit_mb),
  premium_features_json = VALUES(premium_features_json),
  trial_days = VALUES(trial_days),
  grace_days = VALUES(grace_days),
  is_active = VALUES(is_active),
  sort_order = VALUES(sort_order);
