-- FlowDesk CRM
-- Pre-Fase 2 - Padronizacao estrutural antes do multi-tenant
-- Execute por blocos. Valide cada etapa antes de seguir.

START TRANSACTION;

-- =====================================================
-- 1. PADRONIZACAO DE IDS
-- =====================================================

-- usuarios.id hoje está como INT sem UNSIGNED.
-- Ajustamos para combinar com o restante do schema legado.
ALTER TABLE usuarios
  MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- modelos.id hoje está como INT sem UNSIGNED.
ALTER TABLE modelos
  MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- =====================================================
-- 2. PADRONIZACAO DE COLLATION/CHARSET NO CORE
-- =====================================================

ALTER TABLE cliente_blocos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orcamentos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE orcamento_itens CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE projeto_tarefas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- 3. RECRIACAO/VALIDACAO DE FKS IMPORTANTES
-- =====================================================

-- orcamentos.cliente_id precisa existir como FK real para a fase multi-tenant.
-- Se já existir no seu ambiente, pule este bloco.
ALTER TABLE orcamentos
  ADD CONSTRAINT fk_orcamentos_cliente
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE;

-- =====================================================
-- 4. INDICES DE APOIO
-- =====================================================

-- Alguns índices que ajudam no uso atual e já favorecem a futura Fase 2.
ALTER TABLE financeiro_entradas
  ADD KEY idx_financeiro_entradas_data (data_lancamento);

ALTER TABLE financeiro_saidas
  ADD KEY idx_financeiro_saidas_data (data_lancamento);

ALTER TABLE projetos
  ADD KEY idx_projetos_status (status);

ALTER TABLE oportunidades
  ADD KEY idx_oportunidades_ativo (ativo);

COMMIT;

-- =====================================================
-- NOTAS
-- =====================================================
-- 1. Não converta as PKs legadas todas para BIGINT agora sem necessidade.
-- 2. Para o FlowDesk atual, a estratégia recomendada é:
--    - legado operacional: INT UNSIGNED
--    - fundação SaaS nova: BIGINT UNSIGNED onde fizer sentido
-- 3. Ao criar workspace_members, use user_id INT UNSIGNED para casar com usuarios.id.
