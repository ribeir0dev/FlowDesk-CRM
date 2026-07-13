-- FlowDesk CRM
-- Fase 5 - Status flexivel de orcamentos
--
-- Alguns ambientes antigos podem ter orcamentos.status como ENUM.
-- Isso impede salvar novos status como "Sem Resposta".

ALTER TABLE orcamentos
  MODIFY status VARCHAR(40) NOT NULL DEFAULT 'Enviado';

UPDATE orcamentos
SET status = 'Aceito'
WHERE status IN ('Ativou', 'Aprovado');
