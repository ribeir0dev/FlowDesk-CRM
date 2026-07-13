-- FlowDesk CRM
-- Fase 6 - Links publicos curtos para cobrancas/fechamentos

CREATE TABLE IF NOT EXISTS public_document_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(32) NOT NULL,
  codigo_hash CHAR(64) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  token_encrypted TEXT NOT NULL,
  tipo VARCHAR(60) NOT NULL DEFAULT 'cobranca',
  expires_at DATETIME NOT NULL,
  access_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_accessed_at DATETIME NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_public_document_codigo_hash (codigo_hash),
  UNIQUE KEY uq_public_document_token_hash (token_hash),
  INDEX idx_public_document_workspace (workspace_id),
  INDEX idx_public_document_expires (expires_at),
  CONSTRAINT fk_public_document_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
