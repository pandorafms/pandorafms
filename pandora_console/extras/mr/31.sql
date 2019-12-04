START TRANSACTION;

UPDATE `tconfig` SET `value` = 'mini_severity,evento,id_agente,estado,timestamp' WHERE `token` LIKE 'event_fields';

DELETE FROM `talert_commands` WHERE `id` = 11;

DELETE FROM `tconfig` WHERE `token` LIKE 'integria_enabled';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_api_password';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_inventory';
DELETE FROM `tconfig` WHERE `token` LIKE 'integria_url';

COMMIT;