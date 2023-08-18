START TRANSACTION;

ALTER TABLE `treport_content`  ADD COLUMN `cat_security_hardening` INT NOT NULL DEFAULT 0;

ALTER TABLE `treport_content`  ADD COLUMN `ignore_skipped` INT NOT NULL DEFAULT 0;

UPDATE `twelcome_tip`
	SET title = 'Scheduled&#x20;downtimes',
         url = 'https://pandorafms.com/manual/en/documentation/04_using/11_managing_and_administration#scheduled_downtimes'
	WHERE title = 'planned&#x20;stops';

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tpolicy_modules SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tnetwork_component SET `tcp_send` = '2c' WHERE `tcp_send` = '2';

COMMIT;
