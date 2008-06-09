UPDATE tconfig SET value = '1.3.1' WHERE token = 'db_scheme_version';
UPDATE tconfig SET value = 'PD080429' WHERE token = 'db_scheme_build';
INSERT INTO `ttipo_modulo` VALUES (100,'keep_alive',-1,'KeepAlive','mod_keepalive.png');
ALTER TABLE tagente_datos ADD INDEX `data_index1` (`id_agente_modulo`);