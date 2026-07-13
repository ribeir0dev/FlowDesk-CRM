-- FlowDesk CRM
-- Fase 5 - Checklist estruturada em tarefas de projeto

CREATE TABLE IF NOT EXISTS projeto_tarefa_checklist_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarefa_id INT UNSIGNED NOT NULL,
  texto VARCHAR(255) NOT NULL,
  concluido TINYINT(1) NOT NULL DEFAULT 0,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ptci_tarefa (tarefa_id),
  KEY idx_ptci_ordem (tarefa_id, ordem),
  CONSTRAINT fk_ptci_tarefa FOREIGN KEY (tarefa_id) REFERENCES projeto_tarefas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
