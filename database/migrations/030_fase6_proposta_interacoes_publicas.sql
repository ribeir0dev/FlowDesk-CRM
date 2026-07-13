-- FlowDesk CRM
-- Fase 6 - Interacoes publicas de propostas e notificacoes internas
-- Rode este arquivo uma vez no banco antes de testar confirmacao/ajustes pela proposta publica.

CREATE TABLE IF NOT EXISTS orcamento_ajustes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  orcamento_id BIGINT UNSIGNED NOT NULL,
  public_code VARCHAR(32) NOT NULL,
  mensagem TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'aberto',
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_orcamento_ajustes_workspace (workspace_id),
  INDEX idx_orcamento_ajustes_orcamento (orcamento_id),
  INDEX idx_orcamento_ajustes_public_code (public_code),
  INDEX idx_orcamento_ajustes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  tipo VARCHAR(80) NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  mensagem TEXT NULL,
  entidade VARCHAR(80) NULL,
  entidade_id BIGINT UNSIGNED NULL,
  url VARCHAR(255) NULL,
  payload LONGTEXT NULL,
  lida_em DATETIME NULL,
  criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notificacoes_workspace (workspace_id, criada_em),
  INDEX idx_notificacoes_user (user_id, lida_em, criada_em),
  INDEX idx_notificacoes_entidade (entidade, entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
