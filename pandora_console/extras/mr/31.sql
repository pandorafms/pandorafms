START TRANSACTION;

UPDATE `tconfig` SET `value` = 'mini_severity,evento,id_agente,estado,timestamp' WHERE `token` LIKE 'event_fields';

COMMIT;