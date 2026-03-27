CREATE TABLE IF NOT EXISTS cliente_usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  cliente_id INT(10) UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cliente_usuario_workspace (workspace_id, cliente_id, user_id),
  KEY idx_cliente_usuarios_workspace_user (workspace_id, user_id),
  KEY idx_cliente_usuarios_workspace_cliente (workspace_id, cliente_id),
  CONSTRAINT fk_cliente_usuarios_workspace
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_usuarios_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cliente_usuarios_user
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
