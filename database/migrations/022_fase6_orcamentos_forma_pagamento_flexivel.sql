-- FlowDesk CRM
-- Fase 6 - Forma de pagamento flexivel em orcamentos
--
-- O novo modal de orcamentos salva modos como "A Vista", "Parcelado" e
-- "Recorrente". Em ambientes antigos, essa coluna pode estar como ENUM
-- limitado, causando "Data truncated for column 'forma_pagamento'".

ALTER TABLE orcamentos
  MODIFY forma_pagamento VARCHAR(80) NOT NULL DEFAULT 'A Vista';

UPDATE orcamentos
SET forma_pagamento = 'A Vista'
WHERE forma_pagamento IN ('À Vista', 'A vista', 'avista');
