-- Normaliza propostas criadas antes da nomenclatura definitiva da Fase 6.
UPDATE orcamentos
SET status = 'Aguardando Aprovação'
WHERE status IN ('Aguardando', 'Enviado', 'Sem Resposta');
