-- FlowDesk CRM
-- Fase 5 - Modulo Codigos

CREATE TABLE IF NOT EXISTS codigos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(160) NOT NULL,
  descricao TEXT NULL,
  categoria VARCHAR(120) NOT NULL,
  tipo VARCHAR(60) NOT NULL DEFAULT 'Snippet',
  dificuldade VARCHAR(30) NOT NULL DEFAULT 'basico',
  instrucoes TEXT NULL,
  conteudo LONGTEXT NOT NULL,
  favorito TINYINT(1) NOT NULL DEFAULT 0,
  copias INT UNSIGNED NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_codigos_workspace (workspace_id),
  INDEX idx_codigos_usuario (user_id),
  INDEX idx_codigos_categoria (categoria),
  INDEX idx_codigos_dificuldade (dificuldade),
  CONSTRAINT fk_codigos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_codigos_usuario FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
