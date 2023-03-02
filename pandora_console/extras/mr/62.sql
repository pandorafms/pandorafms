-- Active: 1653046769261@@172.16.0.2@3306@pandora
START TRANSACTION;

CREATE TABLE `tevent_sound` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `sound` TEXT NULL,
    `active` TINYINT NOT NULL DEFAULT '1',
PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX agente_modulo_estado ON tevento (estado, id_agentmodule);
CREATE INDEX idx_disabled ON talert_template_modules (disabled);

COMMIT;
