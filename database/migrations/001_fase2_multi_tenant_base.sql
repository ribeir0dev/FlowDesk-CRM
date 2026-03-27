-- FlowDesk CRM
-- Fase 2 - Base inicial para multi-tenant
-- Schema alinhado com a pre-fase de padronizacao estrutural.
-- Execute por blocos, validando cada etapa antes de seguir.
-- Nao reexecute o arquivo inteiro cegamente se algum bloco ja tiver sido aplicado.

START TRANSACTION;

-- =====================================================
-- 1. TABELAS NOVAS
-- =====================================================

CREATE TABLE IF NOT EXISTS workspaces (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  status ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_workspaces_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workspace_members (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('owner','admin','operacional','financeiro','viewer') NOT NULL DEFAULT 'operacional',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_workspace_user (workspace_id, user_id),
  KEY idx_workspace_members_user (user_id),
  CONSTRAINT fk_workspace_members_workspace
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_workspace_members_user
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  code VARCHAR(50) NOT NULL,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  users_limit INT UNSIGNED DEFAULT NULL,
  clients_limit INT UNSIGNED DEFAULT NULL,
  projects_limit INT UNSIGNED DEFAULT NULL,
  storage_limit_mb INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_plans_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  status ENUM('trial','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trial',
  started_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  trial_ends_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subscriptions_workspace (workspace_id),
  CONSTRAINT fk_subscriptions_workspace
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscriptions_plan
    FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  workspace_id BIGINT UNSIGNED DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  acao VARCHAR(120) NOT NULL,
  entidade VARCHAR(120) NOT NULL,
  entidade_id BIGINT UNSIGNED DEFAULT NULL,
  payload JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_workspace (workspace_id),
  KEY idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. WORKSPACE_ID NAS TABELAS OPERACIONAIS
-- =====================================================
-- Rode cada ALTER apenas uma vez.

ALTER TABLE clientes ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_clientes_workspace (workspace_id);
ALTER TABLE cliente_blocos ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_cliente_blocos_workspace (workspace_id);
ALTER TABLE funil_estagios ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_funil_estagios_workspace (workspace_id);
ALTER TABLE oportunidades ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_oportunidades_workspace (workspace_id);
ALTER TABLE projetos ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_projetos_workspace (workspace_id);
ALTER TABLE projeto_tarefas ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_projeto_tarefas_workspace (workspace_id);
ALTER TABLE orcamentos ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_orcamentos_workspace (workspace_id);
ALTER TABLE orcamento_itens ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_orcamento_itens_workspace (workspace_id);
ALTER TABLE financeiro_entradas ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_fin_entradas_workspace (workspace_id);
ALTER TABLE financeiro_saidas ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_fin_saidas_workspace (workspace_id);
ALTER TABLE financeiro_fixos ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_fin_fixos_workspace (workspace_id);
ALTER TABLE hospedagens ADD COLUMN workspace_id BIGINT UNSIGNED NULL, ADD KEY idx_hospedagens_workspace (workspace_id);

-- =====================================================
-- 3. WORKSPACE PADRAO E MIGRACAO DOS DADOS
-- =====================================================

INSERT INTO workspaces (nome, slug, status)
VALUES ('FlowDesk Principal', 'flowdesk-principal', 'active');

SET @workspace_id = LAST_INSERT_ID();

UPDATE clientes SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE cliente_blocos SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE funil_estagios SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE oportunidades SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE projetos SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE projeto_tarefas SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE orcamentos SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE orcamento_itens SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE financeiro_entradas SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE financeiro_saidas SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE financeiro_fixos SET workspace_id = @workspace_id WHERE workspace_id IS NULL;
UPDATE hospedagens SET workspace_id = @workspace_id WHERE workspace_id IS NULL;

-- Ajuste manual recomendado:
-- troque o user_id abaixo para o usuario dono correto, se necessario.
INSERT INTO workspace_members (workspace_id, user_id, role, is_primary)
VALUES (@workspace_id, 2, 'owner', 1);

-- =====================================================
-- 4. TORNAR WORKSPACE_ID OBRIGATORIO
-- =====================================================

ALTER TABLE clientes MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE cliente_blocos MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE funil_estagios MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE oportunidades MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE projetos MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE projeto_tarefas MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE orcamentos MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE orcamento_itens MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE financeiro_entradas MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE financeiro_saidas MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE financeiro_fixos MODIFY workspace_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE hospedagens MODIFY workspace_id BIGINT UNSIGNED NOT NULL;

-- =====================================================
-- 5. FKS DE WORKSPACE
-- =====================================================

ALTER TABLE clientes
  ADD CONSTRAINT fk_clientes_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE cliente_blocos
  ADD CONSTRAINT fk_cliente_blocos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE funil_estagios
  ADD CONSTRAINT fk_funil_estagios_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE oportunidades
  ADD CONSTRAINT fk_oportunidades_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE projetos
  ADD CONSTRAINT fk_projetos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE projeto_tarefas
  ADD CONSTRAINT fk_projeto_tarefas_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE orcamentos
  ADD CONSTRAINT fk_orcamentos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE orcamento_itens
  ADD CONSTRAINT fk_orcamento_itens_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE financeiro_entradas
  ADD CONSTRAINT fk_financeiro_entradas_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE financeiro_saidas
  ADD CONSTRAINT fk_financeiro_saidas_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE financeiro_fixos
  ADD CONSTRAINT fk_financeiro_fixos_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;
ALTER TABLE hospedagens
  ADD CONSTRAINT fk_hospedagens_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE;

-- =====================================================
-- 6. UNIQUES E INDICES MULTI-TENANT
-- =====================================================

ALTER TABLE clientes DROP INDEX clientes_email_unique;
ALTER TABLE clientes ADD UNIQUE KEY uk_clientes_workspace_email (workspace_id, email);

ALTER TABLE funil_estagios DROP INDEX uk_funil_estagios_slug;
ALTER TABLE funil_estagios ADD UNIQUE KEY uk_funil_workspace_slug (workspace_id, slug);

ALTER TABLE oportunidades ADD KEY idx_oportunidades_workspace_estagio (workspace_id, funil_estagio_id);
ALTER TABLE projetos ADD KEY idx_projetos_workspace_cliente (workspace_id, cliente_id);
ALTER TABLE orcamentos ADD KEY idx_orcamentos_workspace_cliente (workspace_id, cliente_id);
ALTER TABLE financeiro_entradas ADD KEY idx_fin_entradas_workspace_cliente (workspace_id, cliente_id);

COMMIT;
