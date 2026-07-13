-- FlowDesk CRM
-- Fase 5 - Bot privado Telegram para financeiro

CREATE TABLE IF NOT EXISTS telegram_bot_sessions (
  telegram_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  workspace_id INT UNSIGNED NOT NULL,
  app_user_id INT UNSIGNED NOT NULL,
  state VARCHAR(60) NOT NULL DEFAULT 'idle',
  payload JSON NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_telegram_bot_sessions_workspace (workspace_id),
  INDEX idx_telegram_bot_sessions_user (app_user_id),
  CONSTRAINT fk_telegram_bot_sessions_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_telegram_bot_sessions_user FOREIGN KEY (app_user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

