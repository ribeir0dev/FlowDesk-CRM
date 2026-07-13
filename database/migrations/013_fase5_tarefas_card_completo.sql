-- FlowDesk CRM
-- Fase 5 - Card completo da tarefa (membros, anexos e comentarios)

CREATE TABLE IF NOT EXISTS projeto_tarefa_members (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarefa_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_ptm_task_user (tarefa_id, user_id),
  KEY idx_ptm_user (user_id),
  CONSTRAINT fk_ptm_tarefa FOREIGN KEY (tarefa_id) REFERENCES projeto_tarefas(id) ON DELETE CASCADE,
  CONSTRAINT fk_ptm_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projeto_tarefa_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarefa_id INT UNSIGNED NOT NULL,
  label VARCHAR(140) NOT NULL,
  url VARCHAR(500) NOT NULL,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pta_tarefa (tarefa_id),
  KEY idx_pta_ordem (tarefa_id, ordem),
  CONSTRAINT fk_pta_tarefa FOREIGN KEY (tarefa_id) REFERENCES projeto_tarefas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projeto_tarefa_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarefa_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  comentario TEXT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ptc_tarefa (tarefa_id),
  KEY idx_ptc_user (user_id),
  CONSTRAINT fk_ptc_tarefa FOREIGN KEY (tarefa_id) REFERENCES projeto_tarefas(id) ON DELETE CASCADE,
  CONSTRAINT fk_ptc_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
