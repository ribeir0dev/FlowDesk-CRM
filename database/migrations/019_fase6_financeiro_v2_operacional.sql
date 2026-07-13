-- FlowDesk CRM
-- Fase 6 - Financeiro operacional v2
-- Execute este arquivo uma vez antes de usar as novas funcoes do financeiro.

CREATE TABLE IF NOT EXISTS financeiro_categorias (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  workspace_id BIGINT UNSIGNED NOT NULL,
  nome VARCHAR(120) NOT NULL,
  cor VARCHAR(20) NOT NULL DEFAULT '#5690D9',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_fin_cat_workspace_nome (workspace_id, nome),
  KEY idx_fin_cat_workspace (workspace_id),
  CONSTRAINT fk_fin_cat_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE financeiro_entradas
  ADD COLUMN status_pagamento VARCHAR(30) NOT NULL DEFAULT 'pendente' AFTER valor_recebido,
  ADD COLUMN categoria_financeira VARCHAR(120) NULL AFTER servico,
  ADD COLUMN moeda VARCHAR(10) NOT NULL DEFAULT 'BRL' AFTER categoria_financeira;

ALTER TABLE financeiro_saidas
  ADD COLUMN valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER valor,
  ADD COLUMN status_pagamento VARCHAR(30) NOT NULL DEFAULT 'pendente' AFTER valor_pago,
  ADD COLUMN categoria_financeira VARCHAR(120) NULL AFTER tipo,
  ADD COLUMN moeda VARCHAR(10) NOT NULL DEFAULT 'BRL' AFTER categoria_financeira,
  ADD COLUMN favorecido VARCHAR(160) NULL AFTER workspace_id,
  ADD COLUMN recorrente TINYINT(1) NOT NULL DEFAULT 0 AFTER status_pagamento;

UPDATE financeiro_entradas
SET status_pagamento = CASE
  WHEN valor_recebido >= valor_a_receber THEN 'pago'
  WHEN valor_recebido > 0 THEN 'parcial'
  ELSE 'pendente'
END
WHERE status_pagamento = 'pendente';

UPDATE financeiro_saidas
SET valor_pago = valor,
    status_pagamento = 'pago'
WHERE valor_pago = 0 AND status_pagamento = 'pendente';

