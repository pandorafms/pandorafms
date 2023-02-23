-- Active: 1653046769261@@172.16.0.2@3306@pandora
START TRANSACTION;

CREATE INDEX agente_modulo_estado ON tevento (estado, id_agentmodule);
CREATE INDEX idx_disabled ON talert_template_modules (disabled);

COMMIT;
