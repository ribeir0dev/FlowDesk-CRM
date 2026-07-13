-- FlowDesk CRM
-- Fase 6 - Base para storage em Google Drive

CREATE TABLE IF NOT EXISTS workspace_google_drive_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  status ENUM('disconnected', 'connected', 'error') NOT NULL DEFAULT 'disconnected',
  root_folder_id VARCHAR(255) NULL,
  root_folder_name VARCHAR(160) NOT NULL DEFAULT 'FlowDesk',
  access_token_encrypted TEXT NULL,
  refresh_token_encrypted TEXT NULL,
  token_expires_at DATETIME NULL,
  scope TEXT NULL,
  connected_by_user_id INT UNSIGNED NULL,
  connected_at DATETIME NULL,
  last_error TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_gdrive_settings_workspace (workspace_id),
  KEY idx_gdrive_settings_status (status),
  KEY idx_gdrive_settings_user (connected_by_user_id),
  CONSTRAINT fk_gdrive_settings_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_gdrive_settings_user FOREIGN KEY (connected_by_user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cliente_drive_folders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  drive_folder_id VARCHAR(255) NOT NULL,
  drive_folder_name VARCHAR(180) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cliente_drive_folder (workspace_id, cliente_id),
  KEY idx_cliente_drive_folder_drive_id (drive_folder_id),
  CONSTRAINT fk_cliente_drive_folder_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_drive_folder_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cliente_arquivos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  provider ENUM('local', 'google_drive') NOT NULL DEFAULT 'google_drive',
  tipo VARCHAR(60) NOT NULL DEFAULT 'arquivo',
  nome_original VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  local_path VARCHAR(500) NULL,
  drive_file_id VARCHAR(255) NULL,
  drive_folder_id VARCHAR(255) NULL,
  web_view_link VARCHAR(700) NULL,
  web_content_link VARCHAR(700) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cliente_arquivos_cliente (workspace_id, cliente_id),
  KEY idx_cliente_arquivos_user (user_id),
  KEY idx_cliente_arquivos_provider (provider),
  KEY idx_cliente_arquivos_drive_file (drive_file_id),
  CONSTRAINT fk_cliente_arquivos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_arquivos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_arquivos_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
