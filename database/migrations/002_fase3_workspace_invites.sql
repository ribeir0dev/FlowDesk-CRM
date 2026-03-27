CREATE TABLE IF NOT EXISTS workspace_invites (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  invited_by_user_id INT UNSIGNED NOT NULL,
  email VARCHAR(254) NOT NULL,
  role ENUM('admin','operacional','financeiro','viewer') NOT NULL DEFAULT 'operacional',
  token CHAR(64) NOT NULL,
  status ENUM('pending','accepted','revoked','expired') NOT NULL DEFAULT 'pending',
  expires_at DATETIME NOT NULL,
  accepted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_workspace_invites_token (token),
  KEY idx_workspace_invites_workspace_status (workspace_id, status),
  KEY idx_workspace_invites_email_status (email, status),
  CONSTRAINT fk_workspace_invites_workspace
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_workspace_invites_user
    FOREIGN KEY (invited_by_user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
