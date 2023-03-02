-- Active: 1653046769261@@172.16.0.2@3306@pandora
START TRANSACTION;

CREATE INDEX agente_modulo_estado ON tevento (estado, id_agentmodule);
CREATE INDEX idx_disabled ON talert_template_modules (disabled);

INSERT INTO `treport_custom_sql` (`name`, `sql`) VALUES ('Agent&#x20;safe&#x20;mode&#x20;not&#x20;enable', 'select&#x20;alias&#x20;from&#x20;tagente&#x20;where&#x20;safe_mode_module&#x20;=&#x20;0');

COMMIT;
